<?php
/*
 * PG名称 ユーザをTreeで選ぶ Ajax用
 * 日  付：2006/12/06
 * 作成者：cbase Kido
 *
 * 更新履歴
 */
define('DIR_ROOT', '../');

//define('OUTPUT_ENCODE','UTF-8');
//define('MODE','AJAX');

require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . '360_FHtml.php');

$AuthSession->switchToGetSession();
session_start();

//ハッシュ値をチェックしてuidにセットする

$serial = $_POST['serial_no'];
if ($_POST['hash'] !== getHash360($serial)) {
    encodeWebOutAll();
    print 'proxy_input_error:<br>この方のマイページにアクセスする権限がありません。<br>代理ログイントップからやり直してください';
    exit;
}

session_regenerate_id();
setProxyInputStatus($_SESSION);
$result = FDB :: select1(T_USER_MST, "*", "where serial_no = " . FDB :: escape($serial));
setSessionLoginData360($result);
write_log_login_user("success", $result['uid'], $_SESSION['muid']);
if ($GLOBALS['Setting']->sessionModeCookie()) {
    session_name("PROXYSESSID");
    location(DIR_ROOT.'360_menu.php?'.html_escape("PROXYSESSID=".session_id()));
}
//※この関数は代理情報も持ち越すはずなので
location(DIR_ROOT.'360_menu.php?'.getSID());
