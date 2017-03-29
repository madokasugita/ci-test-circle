<?php
/**
 * 汎用ログアウト処理php
 * 内容：セッションを削除してpageに戻る
 * 依存：
 * 作成日：2006/09/07
 * @author	Cbase akama
 */
define('DIR_ROOT', "../");
require_once(DIR_ROOT.'crm_define.php');

transLogout();

function transLogout()
{
    session_start();
    $strPage = $_POST["page"]? $_POST["page"]: $_GET["page"];
    if(!$strPage) $strPage = "./";
    $GLOBALS['AuthSession']->sessionReset();
    $strUrl = "Location: ";
    $strUrl.= $strPage;
    header($strUrl);
    exit;
}
