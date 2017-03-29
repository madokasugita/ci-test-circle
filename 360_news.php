<?php

/**
 * PG名称：ユーザ用マイページ
 * 日　付：2007/03/12
 * 作成者：cbase Kido Akama
 */
/******************************************************************************************************/
/** ルートディレクトリ */
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . '360_Smarty.php');
require_once (DIR_LIB . '360_Function.php');
encodeWebAll();
session_start();
clearSessionExceptForLoginData360();
checkAuthUsr360();

define('MSG_MENU_TITLE', MSG_NEWS_TITLE);
if ($_POST['mypage']) {
    $news_flag = ($_POST['news_flag']==1)? 1:0;
    if ($news_flag != $_SESSION['login']['news_flag']) {
        FDB :: update(T_USER_MST, array('news_flag'=>$news_flag), "where " . C_ID . " = " . FDB :: escape($_SESSION['login'][C_ID]));
        $_SESSION['login']['news_flag'] = $news_flag;
    }
    location("360_menu.php?" . getSID());
}
$smarty = new MreSmarty();
$smarty->assign('link', getMypageLink());
$smarty->display('360_news.tpl');

exit;

function getMyPageLink()
{
    $link = array();
    $link['logout'] = DIR_ROOT . '360_login.php?mode=logout&' . getSID();
    $link['news'] = DIR_ROOT . '360_news.php?' . getSID();
    $link['refresh'] = getPHP_SELF() . '?' . getSID();

    return $link;
}
