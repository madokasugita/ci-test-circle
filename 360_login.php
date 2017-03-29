<?php

/**
 * PG名称：ユーザ用ログインページ
 * 日　付：2007/03/12
 * 作成者：cbase Kido
 */
/******************************************************************************************************/

/** ルートディレクトリ */
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . '360_Function.php');
require_once (DIR_LIB . 'CbaseFErrorMSG.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360_Smarty.php');
encodeWebAll();
/******************************************************************************************************/
$language = $_REQUEST['l'];
if (isset($language)) {
    setcookie('lang360',$language);
    $_COOKIE['lang360'] = $language;
}
/** ページタイトル */
define('PAGE_TITLE', MSG_LOGIN_PAGE_TITLE);

switch (getMode()) {
    case "login" :
        login();
        break;
    case "logout" :
        logout();
        $ERROR->addMessage(MSG_LOGOUT);
        break;
}
$smarty = new MreSmarty();
$smarty->assign('errors',$ERROR->getErrorMessages());
$smarty->assign('post_id',html_escape($_POST['id']));
$smarty->assign('post_pw',html_escape($_POST['pw']));
if (isOutOfAnswerPeriod()) {
    $smarty->display('360_login_out_of_answer_period.tpl');
} elseif (SSO_LOGOUT_FLAG === 1) {
    $smarty->display('360_login_sso.tpl');
} else {
    $smarty->display('360_login.tpl');
}
exit;
/******************************************************************************************************/
/**
 * ログアウト処理を行なう
 */
function logout()
{
    global $ERROR;
    session_start();
    if ($_SESSION['login']['sso'])
        define('SSO_LOGOUT_FLAG', 1);
    // セッション終了
    //$_SESSION = array ();
    $GLOBALS['AuthSession']->sessionReset();
}
/**
 * ログイン処理を試行する　失敗した場合エラーオブジェクトにaddしてreturn
 */
function login()
{
    global $ERROR,$Setting;
    $id = $_POST["id"];
    $pw = $_POST["pw"];
    $id = ereg_replace("[ 　\n\t]", '', $id); //IDに含まれる空白文字は取り除く 2008/12/17
    $pw = ereg_replace("[ 　\n\t]", '', $pw); //パスワードに含まれる空白文字は取り除く 07/0514

    if ($id === "")
        $ERROR->addMessage(MSG_ERROR_LOGIN_1);
    elseif (!ereg(EREG_LOGIN_ID, $id)) $ERROR->addMessage(MSG_ERROR_LOGIN_2);

    if ($pw === "")
        $ERROR->addMessage(MSG_ERROR_LOGIN_3);
    elseif (ereg('[^a-zA-Z0-9]', $pw)) $ERROR->addMessage(MSG_ERROR_LOGIN_4);

    if ($ERROR->isError())
        return;

    //データベースから指定したユーザーＩＤのユーザを取得
    $result = FDB :: select1(T_USER_MST, "*", "where " . C_ID . " = " . FDB :: escape($id));

    //データベースに指定したユーザーＩＤのレコードが見つからない場合
    if (!count($result))
        return $ERROR->addMessage(MSG_ERROR_LOGIN_5);

    //もしも規定回数以上にパスワードを間違えていたら
    if ($Setting->limitPwLessOrEqual($result[C_PASSWORD_MISS]))
        return $ERROR->addMessage(getMessage('pwmiss_infomation'));

    //if ($result['pw'] == $pw)//削除2012-05-18
    if (validPwHash($pw, $result['pw'])) {//追加2012-05-18
        $data = array();
        //パスワードがあっていた時
        if ($result[C_PASSWORD_MISS]) {
            $data[C_PASSWORD_MISS] = 0; //パスワード間違い回数を0に戻す
        }
        //初回ログイン時
        if (OPTION_LOGIN_FLAG && is_zero($result[C_LOGIN_FLAG])) {
            $data[C_LOGIN_FLAG] = 1; //ログインフラグを立てる
        }
        if(!empty($data))
            FDB :: update(T_USER_MST, $data, "where " . C_ID . " = " . FDB :: escape($id));
        // セッションを開始します。
        $GLOBALS['AuthSession']->sessionRestart();

        //データを取得してセッションに入れる
        setSessionLoginData360($result);
        write_log_login_user("success", $id);

        if($_SESSION['login']['pw_flag']==0)
            location("360_pw.php?" . getSID());
        elseif($GLOBALS['Setting']->useNewsDisplay() || $_SESSION['login']['news_flag']==1)
            location("360_menu.php?" . getSID());
        else
            location("360_news.php?" . getSID());

    } else {
        //パスワードが間違っていたときの処理
        if (!$result[C_PASSWORD_MISS]) {
            $data = array (	C_PASSWORD_MISS => 1); //はじめてパスワードを間違えたときの処理 パスワード間違い回数を1とする。
        } else {
            $data = array (
                C_PASSWORD_MISS => C_PASSWORD_MISS . "+1"
            ); //パスワード間違い回数を1増やす
        }
        FDB :: update(T_USER_MST, $data, "where " . C_ID . "  = " . FDB :: escape($id));
        $_POST['pw'] = "";
        write_log_login_user("failure", $id);

        return $ERROR->addMessage(MSG_ERROR_LOGIN_6);
    }

}
