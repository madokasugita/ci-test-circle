<?php

/**
 * PGNAME:ユーザ回答者関連付け詳細参照
 * DATE  :2008/11/28
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFErrorMSG.php');
require_once (DIR_LIB . '360_RelationEdit.php');
require_once (DIR_LIB . '360_Smarty.php');

/****************************************************************************************************/
define('PAGE_TITLE','####user_relation_view_title####');
session_start();
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
encodeWebAll();
checkAuthUsr360();
/****************************************************************************************************/
$serial_no = $_REQUEST['serial_no'];
if (getHash360($serial_no) != $_REQUEST['hash']) {
    print "invali hash!";
    exit;
}
$user = getUserBySerial($serial_no);
if (!$user) {
    print "error NoUser";
    exit;
}

if (is_good($error_message)) {
    $ERROR->addMessage($error_message);
}
/* 削除モード */
elseif ($_POST['mode'] == 'delete' && $_POST['respondent_serial_no']) {
    if ($_POST['respondent_hash'] != getHash360($_POST['respondent_serial_no'])) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }
    if ($delete_error = getDeleteRelationError($_POST['respondent_serial_no'], $user)) {
        $delete_error = "<div style=\"margin-bottom:20px\"><table class=\"errors\"><tr><td class=\"error\">".$delete_error."</td></tr></table></div>";
    } else {
        deleteRelation($_POST['respondent_serial_no'], $user);
    }
}
/* 削除モード */
elseif ($_POST['mode'] == 'delete_relation' && $_POST['respondent_serial_no']) {
    if ($_POST['respondent_hash'] != getHash360($_POST['respondent_serial_no'])) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }
    if ($delete_error = getDeleteRelationError($_POST['respondent_serial_no'], $user)) {
        $delete_error = "<div style=\"margin-bottom:20px\"><table class=\"errors\"><tr><td class=\"error\">".$delete_error."</td></tr></table></div>";
    } else {
        deleteRelation($_POST['respondent_serial_no'], $user);
    }
}

$RE = new RelationEdit($user, true);

if ($_POST['mode'] == 'select_status_edit') {
    if (select_status_edit($user)) {
        $user = getUserBySerial($serial_no, true);
        $RE->setUser($user);
    }
}

$user_info = $RE->getHtmlUserInfo();

switch (RESPONDENT_MODE) {
    case 2:
        $relation_info2 = $RE->getHtmlRelationInfo2();
        $add_button2 = $RE->getHtmlAddButton2();
        break;
    case 3:
        $relation_info1 = $RE->getHtmlRelationInfo();
        $add_button1 = $RE->getHtmlAddButton1();
        $relation_info2 = $RE->getHtmlRelationInfo2();
        $add_button2 = $RE->getHtmlAddButton2();
        break;
    default:
        $relation_info1 = $RE->getHtmlRelationInfo();
        $add_button1 = $RE->getHtmlAddButton1();
        break;
}

$status_select_button = getHtmlSelectStatusButton($user);

if($ERROR->isError())
    $message = '<div style="margin-bottom:20px">'.$ERROR->show(650).'</div>';

$objHtml = & new UserHtml("####user_relation_view_title####");

$smarty = new MreSmarty();
$smarty->assign('message', $message);
$smarty->assign('delete_error', $delete_error);
$smarty->assign('user_info', $user_info);
$smarty->assign('add_button1', $add_button1);
$smarty->assign('relation_info1', $relation_info1);
$smarty->assign('add_button2', $add_button2);
$smarty->assign('relation_info2', $relation_info2);
$smarty->assign('status_select_button', $status_select_button);
$smarty->display('360_relation_view.tpl');
exit;
/****************************************************************************************************/

function selectStatusEdit($user)
{
    $data = array();
    $data['select_status'] = FDB::escape($_POST['select_status']);
    FDB::update(T_USER_MST,$data,'where serial_no = '.FDB::escape($user['serial_no']));

    if($_POST['select_status'])

        return "確定を解除しました";//選定を確定しました
    else
        return "確定を解除しました";//確定を解除しました
}

function getHtmlSelectStatusButton($user)
{
    global $ERROR;
    $serial_no = $user['serial_no'];
    if ($user['select_status']) {
        return "";
    } else {
        $submit_option = "";

        $SUM_COUNT = array_sum($GLOBALS['RELATION_EDIT_COUNT']);
        if (REPLY_BOSS_NUM_ATTENTION && $GLOBALS['Setting']->bossNumAttentionGreater()) {
            $submit_option .= str_replace('XXX',REPLY_BOSS_NUM_ATTENTION,replaceMessage('####send_alert1####'))."\\n";
        }
        if (REPLY_MEMBER_NUM_ATTENTION && $GLOBALS['Setting']->memberNumAttentionGreater()) {
            $submit_option .= str_replace('XXX',REPLY_MEMBER_NUM_ATTENTION, replaceMessage('####send_alert2####'))."\\n";
        }
        if (REPLY_COWORKER_NUM_ATTENTION && $GLOBALS['Setting']->coworkerNumAttentionGreater()) {
            $submit_option .= str_replace('XXX',REPLY_COWORKER_NUM_ATTENTION,replaceMessage('####send_alert3####'))."\\n";
        }
        if (REPLY_ALL_NUM_ATTENTION && $GLOBALS['Setting']->allNumAttentionGreater($SUM_COUNT)) {
            $submit_option .= str_replace('XXX',REPLY_ALL_NUM_ATTENTION,replaceMessage('####send_alert4####'))."\\n";
        }
        if ($submit_option) {
            $submit_option = str_replace("'", "\'", $submit_option."\\n\\n".replaceMessage("####send_confirm####"));
            $submit_option=<<<HTML
onclick="return confirm('{$submit_option}')"
HTML;
        }

        if (REPLY_BOSS_NUM_WARNING && $GLOBALS['Setting']->bossNumWarningGreater()) {
            $ERROR->add(str_replace('XXX',REPLY_BOSS_NUM_WARNING,replaceMessage('####send_error1####')));
        }

        if (REPLY_MEMBER_NUM_WARNING && $GLOBALS['Setting']->memberNumWarningGreater()) {
            $ERROR->add(str_replace('XXX',REPLY_MEMBER_NUM_WARNING,replaceMessage('####send_error2####')));
        }

        if (REPLY_COWORKER_NUM_WARNING && $GLOBALS['Setting']->coworkerNumWarningGreater()) {
            $ERROR->add(str_replace('XXX',REPLY_COWORKER_NUM_WARNING,replaceMessage('####send_error3####')));
        }

        if (REPLY_ALL_NUM_WARNING && $GLOBALS['Setting']->allNumWarningGreater($SUM_COUNT)) {
            $ERROR->add(str_replace('XXX',REPLY_ALL_NUM_WARNING,replaceMessage('####send_error4####')));
        }

        if ($ERROR->isError()) {
            $submit_option="disabled";
            $message = $ERROR->show();
        }
        if (!$submit_option) {
            $submit_option = str_replace("'", "\'", replaceMessage("####send_confirm####"));
            $submit_option=<<<HTML
onclick="return confirm('{$submit_option}')"
HTML;
        }

        $button=<<<HTML
<input type="submit" value="####user_relation_view_button_2####" class="btn large" {$submit_option}>
HTML;
        $val = 1;
    }

    $hash = getHash360($serial_no);
    $PHP_SELF = PHP_SELF;
    $PHP_SELF = "#";

    return<<<HTML
<form action="{$PHP_SELF}" method="post" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="select_status_edit">
<input type="hidden" name="select_status" value="{$val}">
{$button}
</form>
</div>
HTML;
}

function select_status_edit($user)
{
    global $ERROR;

    if (REPLY_BOSS_NUM_WARNING && $GLOBALS['Setting']->bossNumWarningGreater()) {
        $ERROR->add(str_replace('XXX',REPLY_BOSS_NUM_WARNING,replaceMessage('####send_error1####')));

        return false;
    }

    if (REPLY_MEMBER_NUM_WARNING && $GLOBALS['Setting']->memberNumWarningGreater()) {
        $ERROR->add(str_replace('XXX',REPLY_MEMBER_NUM_WARNING,replaceMessage('####send_error2####')));

        return false;
    }

    if (REPLY_COWORKER_NUM_WARNING && $GLOBALS['Setting']->coworkerNumWarningGreater()) {
        $ERROR->add(str_replace('XXX',REPLY_COWORKER_NUM_WARNING,replaceMessage('####send_error3####')));

        return false;
    }

    if (REPLY_ALL_NUM_WARNING && $GLOBALS['Setting']->allNumWarningGreater(array_sum($GLOBALS['RELATION_EDIT_COUNT']))) {
        $ERROR->add(str_replace('XXX',REPLY_ALL_NUM_WARNING,replaceMessage('####send_error4####')));

        return false;
    }

    if ($GLOBALS['Setting']->adminModeEqual(1)) {
        header('Location: 360_setup_reply_user.php?'.getSID());
        exit;
    }
    $result = setSelectStatus_(2,$user);
    $ERROR->add("####user_relation_view_message_5####");

    return $result;
}

function deleteRelation($serial_no, $self, $delete_user=false)
{
    $self_uid = $self['uid'];

    FDB::begin();

    $r[] =FDB::delete(T_USER_RELATION,  'WHERE user_type <= '.INPUTER_COUNT.' AND uid_a = '.FDB::escape($self_uid).' AND uid_b = (SELECT uid FROM '.T_USER_MST.' WHERE serial_no = '.FDB::escape($serial_no).')');

    if ($delete_user) {
        $where = 'WHERE serial_no = '.FDB::escape($serial_no);
        $r[] =FDB :: sql("delete from subevent_data where event_data_id in (select event_data_id from event_data {$where})",true);
        $r[] =FDB :: delete(T_EVENT_DATA, $where);
        $r[] =FDB :: delete(T_BACKUP_DATA, $where);
        $r[] = FDB :: delete(T_USER_MST, 'where serial_no = ' . FDB :: escape($serial_no));
    }

    foreach ($r as $result) {
        if (FDB::isError($result)) {
            FDB::rollback();

            return false;
        }
    }
    FDB::commit();

    return true;
}

function getDeleteRelationError($serial_no, $self)
{
    $self_uid = $self['uid'];
    $where = 'where answer_state in(10,0) and target = (select serial_no from usr where uid = '.FDB::escape($self_uid).') and serial_no = '.FDB::escape($serial_no);
    $result = FDB::select(T_EVENT_DATA,'answer_state', $where);

    if($result && $result[0]['answer_state']==0)

        return "既に回答が完了しているため選定内容を変更できませんでした。";
    if($result && $result[0]['answer_state']==10)

        return "既に回答を始めているため選定状況を変更できませんでした。";

    return '';
}
