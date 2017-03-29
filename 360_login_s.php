<?php

/**
 * PGNAME:
 * DATE  :2009/04/17
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFCrypt.php');
/****************************************************************************************************/
list ($id, $pw, $hash) = explode('/', decrypt(substr($_REQUEST['q'], 0, -1)));

if (substr(getHash360($id, $pw), 0, 4) != substr($hash, 0, 4)) {
    print "error121";
    exit;
}

//データベースから指定したユーザーＩＤのユーザを取得
$result = FDB :: select1(T_USER_MST, '*', 'where ' . C_ID . ' = ' . FDB :: escape($id) . ' and pw = ' . FDB :: escape($pw));

//データベースに指定したユーザーＩＤのレコードが見つからない場合
if (!count($result)) {
    write_log_login_user("failure", $id);
    print "error211";
    exit;
}

//初回ログイン時
if (OPTION_LOGIN_FLAG && is_zero($result[C_LOGIN_FLAG]) && preg_match("/^".preg_quote(DOMAIN.DIR_MAIN.DIR_MNG, "/")."/", $_SERVER['HTTP_REFERER'])==0) {
    $data = array();
    $data[C_LOGIN_FLAG] = 1; //ログインフラグを立てる
    FDB :: update(T_USER_MST, $data, "where " . C_ID . " = " . FDB :: escape($id));
}
session_start();
//データを取得してセッションに入れる
setSessionLoginData360($result);
write_log_login_user("success", $id);
location("360_menu.php?" . getSID());
