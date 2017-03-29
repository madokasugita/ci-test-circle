<?php

/**
 * PGNAME:ユーザ対象者関連付け詳細参照
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
/****************************************************************************************************/

/****************************************************************************************************/
session_start();
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
encodeWebAll();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/
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
if ($_POST['mode'] == 'select_status_edit') {

    $ERROR->addMessage(selectStatusEdit($user));
    $user['select_status'] = (int) $_POST['select_status'];
}

if($ERROR->isError())
    $message = '<div style="margin:20px">'.$ERROR->show(650).'</div>';

$user_info = getHtmlUserInfo($user);

$relation_info = getHtmlRelationInfo($user);

$objHtml = & new ResearchAdminHtml("対象者情報閲覧");

$body =<<<HTML
<div style="text-align:left;width:800px;margin-bottom:5px;border-top:dotted 1px #222222;border-bottom:dotted 1px #222222;margin-bottom:5px;border-bottom:dotted 1px #222222;padding-top:10px;">
<table>
<tr>
  <td>対象者情報閲覧</td>
  <td valign="middle"></td>
</tr>
</table>

</div>
{$message}
{$user_info}
{$add_button}
{$relation_info}
<div style="margin:20px;background-color:#f0f0f0;width:650px;padding:10px;text-align:center;">
<button onclick="window.close()">このウィンドウを閉じる</button>
</div>
HTML;
print $objHtml->getMainHtml($body);
exit;
/****************************************************************************************************/

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


function getHtmlAddButton($user)
{
    $serial_no = $user['serial_no'];



    $hash = getHash360($serial_no);
    $SID = getSID();

    return<<<HTML
<div style="text-align:center;margin:10px 0px; background-color:#cccccc;padding:10px">
<form action="360_target_relation_edit.php?{$SID}" method="post" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="edit">
<input type="submit" value="####target_relation_view_button_1####" style="width:300px;">
</form>
</div>
HTML;
}

function getHtmlUserInfo($user)
{
    $user['name'] = html_escape($user['name']);
    $user['uid'] = html_escape($user['uid']);
    $user['email'] = html_escape($user['email']);
    $user['div1'] = getDiv1NameById($user['div1']);
    $user['div2'] = getDiv2NameById($user['div2']);
    $user['div3'] = getDiv3NameById($user['div3']);
    $user['sheet_type'] = getSheetTypeNameById($user['sheet_type']);

    if($user['select_status']==count($GLOBALS['_360_select_status'])-1)
        $user['select_status'] = '<span style="color:blue;font-weight:bold">'.getSelectStatusName($user['select_status']).'</span>';
    else
        $user['select_status'] = '<span style="color:red;font-weight:bold">'.getSelectStatusName($user['select_status']).'</span>';


    $user=hiddenColumn($user);

    return<<<HTML
<div style="margin:20px;background-color:#f0f0f0;width:650px;padding:10px">
[回答者情報]
<table class="admintable2" style="background-color:#ffffff;">
<tr class="admintable_header">
<td>シートタイプ</td>
<td>ユーザID</td>
<td>名前</td>
<td>ローマ字</td>
<td>####div_name_1####</td>
<td>####div_name_2####</td>
<td>####div_name_3####</td>
<td>メールアドレス</td>
</tr>
<tr>
<td style="text-align:center">{$user['sheet_type']}</td>
<td style="text-align:center">{$user['uid']}</td>
<td>{$user['name']}</td>
<td>{$user['name_']}</td>
<td>{$user['div1']}</td>
<td>{$user['div2']}</td>
<td>{$user['div3']}</td>

<td>{$user['email']}</td>
</tr>
</table>
</div>

HTML;
}

function getHtmlRelationInfo($user)
{
    $add_button = getHtmlAddButton($user);
    global $_360_user_type;

    $T_USER_MST = T_USER_MST;
    $T_USER_RELATION = T_USER_RELATION;
    $UID_ESCAPED = FDB :: escape($user['uid']);
    $tmp = FDB :: getAssoc("select * from {$T_USER_RELATION} a left join {$T_USER_MST} b on a.uid_a = b.uid where a.uid_b = {$UID_ESCAPED} order by div1,div2,div3,uid;");
    $users = array ();
    foreach ($tmp as $user) {
        $users[$user['user_type']][] = $user;
    }
    $count ="";
    foreach ($_360_user_type as $k => $v) {
        if(!$k || $k>INPUTER_COUNT)
            continue;
        $count.=$v." : ".count($users[$k])."人　";
    }


    $html =<<<HTML
<div style="margin:20px;background-color:#f0f0f0;width:650px;padding:10px">
[対象者選定情報]<br><br>
{$count}

{$add_button}

<table class="admintable2" style="background-color:#ffffff;">
<tr class="admintable_header">
<td>対象者<br>タイプ</td>
<td>ユーザID</td>
<td>名前</td>
<td>ローマ字</td>
<td>####div_name_1####</td>
<td>####div_name_2####</td>
<td>####div_name_3####</td>
<td>メールアドレス</td>
</tr>
HTML;


    foreach ($_360_user_type as $k => $v) {
        if (!$k || $k>INPUTER_COUNT)
            continue;

        if ($k > 1) {
            $html .=<<<HTML
<tr>
<td style="height:5px;background-color:#444444" colspan="8"></td>
</tr>
HTML;
        }

        $count = count($users[$k]);
        if (!$count) {
            $count = (is_zero($count))? $count+1:$count;
            $html .=<<<HTML

<tr>
<td rowspan="{$count}" style="text-align:center;font-weight:bold;">{$v}</td>
<td style="text-align:center" colspan="7"> 設定なし </td>
</tr>
HTML;
            continue;
        }
        $user_type =<<<HTML
<td rowspan="{$count}" style="text-align:center;font-weight:bold;">{$v}</td>
HTML;
        foreach ($users[$k] as $user) {
            $user['name'] = html_escape($user['name']);
            $user['name_'] = html_escape($user['name_']);
            $user['uid'] = html_escape($user['uid']);
            $user['email'] = html_escape($user['email']);
            $user['div1'] = getDiv1NameById($user['div1']);
            $user['div2'] = getDiv2NameById($user['div2']);
            $user['div3'] = getDiv3NameById($user['div3']);
            $user = hiddenColumn($user);
            $html .=<<<HTML
<tr>
{$user_type}
<td style="text-align:center">{$user['uid']}</td>
<td>{$user['name']}</td>
<td>{$user['name_']}</td>
<td>{$user['div1']}</td>
<td>{$user['div2']}</td>
<td>{$user['div3']}</td>
<td>{$user['email']}</td>
</tr>
HTML;
            $user_type = '';
        }

    }
    $html.="</table></div>";

    return $html;
}
