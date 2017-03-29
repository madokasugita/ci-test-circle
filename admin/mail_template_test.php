<?php

/**
 * PGNAME:
 * DATE  :2009/03/03
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseCommon.php');
require_once (DIR_LIB . 'CbaseFSendmail.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFManage.php');

/****************************************************************************************************/
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
/****************************************************************************************************/
session_start();
encodeWebAll();
/****************************************************************************************************/
if (FCheck :: isEmail($_POST['email'])) {
    $html = sendTestMail();
} else {
    $html = emailForm();
}
$objHtml = & new ResearchAdminHtml("メールテンプレートテスト");
print $objHtml->getMainHtml($html);

//var_dump($_POST);
exit;
/****************************************************************************************************/

function sendTestMail()
{
    $format =  $_POST;

    if (!is_null($_POST['evid']))
        $evid = Check_AuthMngEvid($_POST['evid']);
    else
        $evid = null;

    $usr = FDB :: select1(T_USER_MST, '*', 'where email = ' . FDB :: escape($_POST['email']));

    if (!$usr) {
        $usr = getInsertionTag();
        $usr['email'] = $_POST['email'];
        $usr['serial_no'] = "serialno";
    }

    $event = FDB :: select1(T_EVENT, '*', 'where evid = ' . FDB :: escape($evid));
    $res = mailSend($usr, $format, $event);

    $mail = html_escape($_POST['email']);
    if (is_false($res)) {
        return<<<HTML
<div id="test_mail" style="text-align:center;padding:20px;width:400px;background-color:#f0f0f0;border:dotted 1px black;margin-left:10px;margin-top:5px;margin-right:30px">
<b>{$mail}</b> へのテストメール送信に失敗しました。<br><br>
このウィンドウを閉じて、再度テスト配信を実行してください。
<br><br>
<center>
<button onclick="window.close();">閉じる</button>
</center>
</div>
HTML;
    }

    return<<<HTML
<div id="test_mail" style="text-align:center;padding:20px;width:400px;background-color:#f0f0f0;border:dotted 1px black;margin-left:10px;margin-top:5px;margin-right:30px">
テストメールを <b>{$mail}</b> に送信しました。<br><br>
届いたメールの本文の確認をお願い致します。<br>
問題がなければひな型を<b>登録</b>してください。
<br><br>
<center>
<button onclick="window.close();">閉じる</button>
</center>
</div>
HTML;
}

function mailSend($ad, $format, $event)
{
    $flag = true;
    define('TEST_MAIL', 1); // 差し込み文字をテスト用に置き換える
    foreach ($GLOBALS['_360_language'] as $k => $v) {
        $ad['lang_type'] = $k;
        $res = Pc_Mail_Send($ad, $format, null);
        if(is_false($res))
            $flag = false;
    }

    return $flag;
}

function emailForm()
{
    $SID = getSID();

    $title = html_escape($_POST['title']);
    $body = html_escape($_POST['body']);

    return<<<HTML
<form action="mail_template_test.php?{$SID}" method="post">
<span style="color:red;font-weight:bold">送信先Emailアドレスを正しく入力してください。</span>
<div id="test_mail" style="width:400px;background-color:#f0f0f0;border:dotted 1px black;margin-left:10px;margin-top:5px;margin-right:30px">
<input type="hidden" name="title" value="{$title}">
<input type="hidden" name="body" value="{$body}">
<table>
<tr><td colspan="2"><b>1通だけテスト配信する</b></td></tr>
<tr><td>Email : </td><td><input name="email" style="width:270px"><input type="submit" value="送信"></td></tr>
</table>
</div>
</form>
HTML;
}

function getInsertionTag()
{
    $res = array ();

    $res['URL'] = 'アンケートURL';

    $aryComment = FDB :: getComment(T_USER_MST);
    $denyColumn = explode(",", DENY_USER_COLUMN);
    foreach ($aryComment as $com) {
        if (in_array($com['column'], $denyColumn))
            continue;
        $comment = (is_null($com['comment']) ? $com['column'] : $com['comment']);
        $res[$com['column']] = $comment;
    }

    return $res;
}
