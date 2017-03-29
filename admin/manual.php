<?php
define("DIR_ROOT", "../");
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
require_once (DIR_LIB . 'CbaseFManage.php');
// Check_AuthMng(basename(__FILE__));

$smarty = new MreSmarty();
//$smarty->assign('message', $message);

$smarty->display('admin_manual.tpl');
exit;
