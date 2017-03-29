<?php

/**
 * PGNAME:ユーザ回答者関連付け詳細参照
 * DATE  :2008/11/28
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseFErrorMSG.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . '360_RelationEdit.php');

/****************************************************************************************************/

/****************************************************************************************************/
session_start();
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
encodeWebAll();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/

function main()
{
    global $ERROR;
    global $error_message;

$serial_no = $_REQUEST['serial_no'];
if (getHash360($serial_no) != $_REQUEST['hash']) {
    print "invali hash!";
    exit;
}
$user = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($serial_no) . ' and ' . getDivWhere());
if (!$user) {
    print "error ユーザが見つかりません";
    exit;
}

if (is_good($error_message)) {
    $ERROR->addMessage($error_message);
}
/* 削除モード */
elseif (($_POST['mode'] == 'delete' || $_POST['mode'] == 'delete_relation') && $_POST['respondent_serial_no']) {
    if ($_POST['respondent_hash'] != getHash360($_POST['respondent_serial_no'])) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }

    if ($message = getDeleteRelationError($_POST['respondent_serial_no'], $user)) {
        $ERROR->addMessage($message);
    } else {
        deleteRelation($_POST['respondent_serial_no'], $user);
    }
}

if ($_POST['mode'] == 'select_status_edit') {

    $ERROR->addMessage(selectStatusEdit($user));
    $user['select_status'] = (int) $_POST['select_status'];
}

if($ERROR->isError())
    $message = '<div style="margin:20px">'.$ERROR->show(650).'</div>';

$RE = new AdminRelationEdit($user, true);
$RE->setSelf(PHP_SELF.'&serial_no='.$serial_no.'&hash='.getHash360($serial_no));
$RE->setEditUrl('360_user_relation_view_wrapper.php?'.getSID().'&target_serial_no='.$serial_no.'&hash='.getHash360($serial_no));

$user_info = $RE->getHtmlUserInfo();
$relation_area = $RE->getHtmlRelationView();

$objHtml = & new ResearchAdminHtml("回答者情報閲覧");

$status_select_button = getHtmlSelectStatusButton($user);

$body =<<<HTML
<div style="text-align:left;width:880px;margin:0px auto 0px auto;">
<div style="text-align:left;width:100%;margin-bottom:5px;border-top:dotted 1px #222222;border-bottom:dotted 1px #222222;margin-bottom:5px;border-bottom:dotted 1px #222222;padding-top:10px;">
<table>
<tr>
  <td>回答者情報閲覧</td>
  <td valign="middle"></td>
</tr>
</table>

</div>
{$message}
{$user_info}

{$relation_area}

{$status_select_button}
<div style="background-color:#f0f0f0;width:860px;padding:10px;text-align:center;">
<button onclick="window.close()">このウィンドウを閉じる</button>
</div>
</div>
HTML;
print $objHtml->getMainHtml($body);
exit;
}
/************************************************************************************/

class AdminRelationEdit extends RelationEdit
{
    public function getHtmlAddButton1()
    {
        $user = $this->user;
        $serial_no = $user['serial_no'];

        if($user['select_status'])
            $disabled = ' disabled';
        else
            $disabled = '';

        $hash = getHash360($serial_no);
        $SID = getSID();

        return<<<HTML
    <div style="text-align:center;margin:10px 0px; background-color:#cccccc;padding:10px">
    <form action="360_user_relation_edit.php?{$SID}" method="post" style="display:inline;margin:0px;">
    <input type="hidden" name="serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="edit">
    <input type="submit" value="####user_relation_view_button_1####" style="width:300px;"{$disabled}>
    </form>
    </div>
HTML;
    }

    public function getHtmlAddButton2()
    {
        $user = $this->user;
        $serial_no = $user['serial_no'];

        if($user['select_status'])
            $disabled = ' disabled';
        else
            $disabled = '';

        $hash = getHash360($serial_no);
        $SID = getSID();

        return<<<HTML
    <div style="text-align:center;margin:10px 0px; background-color:#cccccc;padding:10px">
    <form action="360_user_respondent_new.php?{$SID}" method="post" style="display:inline;margin:0px;">
    <input type="hidden" name="mode" value="new">
    <input type="hidden" name="target_serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}">
    <input type="submit" value="####user_relation_view_button_1####" style="width:300px;"{$disabled}>
    </form>
    </div>
HTML;
    }
}

function selectStatusEdit($user)
{

    $data = array();
    $data['select_status'] = FDB::escape($_POST['select_status']);
    FDB::update(T_USER_MST,$data,'where serial_no = '.FDB::escape($user['serial_no']));

    if($_POST['select_status'])

        return "選定を確定しました";
    else
        return "確定を解除しました";
}

function getHtmlSelectStatusButton($user)
{

    $radio = FForm :: replaceChecked(implode(' ',FForm :: radiolist(select_status, $GLOBALS['_360_select_status'])), $user['select_status']);
    $serial_no = $user['serial_no'];

    $hash = getHash360($serial_no);
    $PHP_SELF = PHP_SELF;

    return<<<HTML
<div style="margin:20px 0;width:860px;text-align:center;background-color:ffcccc;padding:10px">
<form action="{$PHP_SELF}" method="post" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="select_status_edit">
<input type="hidden" name="select_status" value="{$val}">
{$radio}
<input type="submit" value="ステータス変更">
</form>
</div>
HTML;
}

function getDeleteRelationError($serial_no, $self)
{
    $self_uid = $self['uid'];
    $where = 'where answer_state in(10,0) and target = (select serial_no from usr where uid = '.FDB::escape($self_uid).') and serial_no = '.FDB::escape($serial_no);
    $result = FDB::select(T_EVENT_DATA,'answer_state', $where);

    if($result && $result[0]['answer_state']==0)

        return "既に回答が完了しているため選定内容を変更できませんでした。<br/>「回答状況検索(詳細)/代理入力」にて該当する回答を「未回答」状態に戻したうえで再度変更して下さい。";
    if($result && $result[0]['answer_state']==10)

        return "既に回答を始めているため選定状況を変更できませんでした。<br/>「回答状況検索(詳細)/代理入力」にて該当する回答を「未回答」状態に戻したうえで再度変更して下さい。";

    return '';
}

function deleteRelation($serial_no, $self)
{
    $self_uid = $self['uid'];

    FDB::begin();

    /* 管理画面では紐付けのみ削除する */
    //$where = 'WHERE serial_no = '.FDB::escape($serial_no);

    //$r[] =FDB :: sql("delete from subevent_data where event_data_id in (select event_data_id from event_data {$where})");
    //$r[] =FDB :: delete(T_EVENT_DATA, $where);
    //$r[] =FDB :: delete(T_BACKUP_DATA, $where);

    $r[] =FDB::delete(T_USER_RELATION,  'WHERE user_type <= '.INPUTER_COUNT.' AND uid_a = '.FDB::escape($self_uid).' AND uid_b = (SELECT uid FROM '.T_USER_MST.' WHERE serial_no = '.FDB::escape($serial_no).')');

    //$r[] = FDB :: delete(T_USER_MST, 'where serial_no = ' . FDB :: escape($serial_no));

    foreach ($r as $result) {
        if (FDB::isError($result)) {
            FDB::rollback();

            return false;
        }
    }
    FDB::commit();

    return true;
}

/********************************************************************/
main();
