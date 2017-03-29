<?php

/**
 * PG名称：ユーザ用マイページ
 * 日　付：2007/03/12
 * 作成者：cbase Kido Akama
 */
/******************************************************************************************************/
/** ルートディレクトリ */
//define('DEBUG', 0);
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
//削除2012-05-18↓
/*
session_start();
clearSessionExceptForLoginData360();
checkAuthUsr360();
*/
//削除2012-05-18↑
define('MSG_MENU_TITLE', MSG_NEWS_TITLE);
//追加2012-05-18↓
global $reissue_url_info;
$reissue_url_info = array();
if (isset($_REQUEST[SESSIONID])) {
    session_start();
    clearSessionExceptForLoginData360();
    checkAuthUsr360();
}
$errors = validIssue();

if ($_POST['mypage'] && is_void($errors = checkPwError()) &&
    is_void($errors = validIssue()))//追加2012-05-18
{
    $password = trim($_POST['pw']);

    FDB::begin();

    $result_reissue = "";

    if (isset($_REQUEST[SESSIONID])) {
        $result = FDB :: update(T_USER_MST, array('pw_flag'=>1,'pw'=>FDB::escape(getPwHash($password)), 'pwmisscount'=>0), "where " . C_ID . " = " . FDB :: escape($_SESSION['login'][C_ID]));
        if (!is_false($result)) {
            FDB::commit();
            $_SESSION['login']['pw'] = $password;
            $_SESSION['login']['pw_flag'] = 1;
            $_SESSION['login']['pwmisscount'] = 0;

            if($Setting->useNewsDisplay() || $_SESSION['login']['news_flag']==1)
                location("360_menu.php?pw=1&" . getSID());
            else
                location("360_news.php?pw=1&" . getSID());
        } else {
            FDB::rollback();
            $errors[] = "####failed_to_update####";
        }
    } else {
        $result = FDB :: update(T_USER_MST, array('pw_flag'=>1,'pw'=>FDB::escape(getPwHash($password)), 'pwmisscount'=>0), "where serial_no = " . FDB :: escape($reissue_url_info['serial_no']));
        $result_reissue = FDB :: update(T_REISSUE_URL, array('status'=>'1',), "where token = " . FDB::escape($_POST['token']));
        if (is_false($result) || is_false($result_reissue)) {
            FDB::rollback();
            $errors[] = "####failed_to_update####";
        } else {
            FDB::commit();
            $errors[] = "#####success_pw_update####";
        }
    }
}
//追加2012-05-18↑
//削除2012-05-18↓
/*
if ($_POST['mypage'] && is_void($errors = checkPwError())) {
    $password = trim($_POST['pw']);
    $result = FDB :: update(T_USER_MST, array('pw_flag'=>1,'pw'=>FDB::escape($password), 'pwmisscount'=>0), "where " . C_ID . " = " . FDB :: escape($_SESSION['login'][C_ID]));

    if (!is_false($result)) {
        $_SESSION['login']['pw'] = $password;
        $_SESSION['login']['pw_flag'] = 1;
        $_SESSION['login']['pwmisscount'] = 0;

        if($isPwReisue)
            $errors[] = "変更完了しました。<a href='./360_login.php'>こちら</a>からログインしてください。";
        elseif($Setting->useNewsDisplay() || $_SESSION['login']['news_flag']==1)
            location("360_menu.php?pw=1&" . getSID());
        else
            location("360_news.php?pw=1&" . getSID());
    } else {
        $errors[] = "更新に失敗しました";
    }
}
*/
//削除2012-05-18↑

$smarty = new MreSmarty();
$smarty->assign('errors', $errors);
$smarty->assign('token', $_GET['token']);//追加2012-05-18
$smarty->display('360_pw.tpl');

exit;

function checkPwError()
{
    $errors = array();
    if($_POST['pw'] != $_POST['confirm'])
        $errors[] = "####confirm_incorrect####";

    if(is_good($error = get360PwError($_POST['pw'], $_SESSION['login'][C_ID])))
        $errors[] = $error;

    return $errors;
}
//追加2012-05-18↓
function validIssue()
{
    global $reissue_url_info;
    $errors = array();

    $timediff = 0;

    if(isset($_REQUEST['token']) &&
        isset($_REQUEST[SESSIONID]) &&
        !empty($_REQUEST['token']))
    {
        return $errors[] = "SYSTEM ERROR query invalid!";
    }

    if(isset($_REQUEST[SESSIONID])) return $errors;

    if (empty($_REQUEST['token'])) {
        return $errors[] = "####url_expired####";
    }

    $reissue_url_info = FDB::select1(T_REISSUE_URL, "*", "where token = ".FDB::escape($_GET['token']));

    if (isset($reissue_url_info['status']) && $reissue_url_info['status'] == 0) {
        $timediff = (strtotime(date("Y/m/d H:i:s")) - strtotime($reissue_url_info['cdate']));
        //$timediff = floor($timediff/(60*60));
        $timediff = $timediff / (60 * 60);
    }
    //echo $timediff.'<hr>';
    if(!isset($reissue_url_info['serial_no']) ||
       $reissue_url_info['status'] > 0 ||
       ($timediff > 2))
    {
        if ($timediff > 2) {
            FDB::begin();
            $result = FDB :: update(T_REISSUE_URL, array('status'=>'2',), "where token = " . FDB::escape($_GET['token']));
            if (is_false($result)) {
                FDB::rollback();
            } else {
                FDB::commit();
            }
        }

        $errors[] = "####url_expired####";
    }

    return $errors;

}
//追加2012-05-18↑
