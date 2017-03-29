<?php
/**
 * PG名称：ユーザ用マイページ
 * 日　付：2007/03/12
 * 作成者：cbase Kido Akama
 */
/******************************************************************************************************/
/** ルートディレクトリ */
// define("DEBUG", 1);
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . '360_Smarty.php');
require_once (DIR_LIB . '360_Function.php');
require_once(DIR_LIB.'360_ExportTotal.php');
require_once(DIR_LIB.'360_EnqueteRelace.php');
require_once (DIR_LIB . 'SecularApp.php');
require_once (DIR_LIB . 'SecularFeedbackController.php');
require_once (DIR_LIB . 'FeedbackController.php');

session_start();
clearSessionExceptForLoginData360();
checkAuthUsr360();

$FeedbackController = new FeedbackController();

$FeedbackController->init();
encodeWebAll();
$FeedbackController->displayLoadingTemplate();

exit;
