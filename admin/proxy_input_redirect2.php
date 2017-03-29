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

session_start();
$page = ($_GET['page'])? $_GET['page'].'.php':'index.php';
$serial = $_GET['serial_no'];
if ($_GET['hash'] !== getHash360($serial)) {
    encodeWebOutAll();
    print 'proxy_input_error2:<br>この方の回答ページにアクセスする権限がありません。';
    exit;
}

session_regenerate_id();
$q=$_GET['q'];
setProxyInputStatus($_SESSION);
$result = FDB :: select1(T_USER_MST, "*", "where serial_no = " . FDB :: escape($serial));
setSessionLoginData360($result);
write_log_login_user("success", $result['uid'], $_SESSION['muid']);
//※この関数は代理情報も持ち越すはずなので
location(DIR_ROOT.$page.'?q='.$q.'&'.getSID());
