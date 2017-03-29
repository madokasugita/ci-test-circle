<?php

/**
 * PGNAME:subevent編集
 * DATE  :2007/11/22
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

define('NOT_CONVERT',1);

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEnqSubeventMVC.php');
require_once 'enq_subev_buildhtml.php';
require_once (DIR_LIB . 'CbaseEncoding.php');
//require_once (DIR_LIB . '360_EnqueteRelace.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

define('PHP_SELF', getPHP_SELF()."?".getSID());
/****************************************************************************************************/

$model = & new EnqSubeventModel();
$view = & new EnqSubeventView();
$controller = & new EnqSubeventController($model, $view);

print $controller->show();

exit;
