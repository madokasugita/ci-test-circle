<?php

/**
 * PGNAME:subevent一覧
 * DATE  :2007/12/12
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEnqListMVC.php');
require_once (DIR_LIB . 'CbaseEncoding.php');

require_once (DIR_LIB . '360_EnqueteRelace.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));
$evid = Check_AuthMngEvid($_GET['evid']);

define('PHP_SELF', getPHP_SELF()."?".getSID());
define('EVENT_ID', $evid);
define('ENQ_RID', getRidByEvid($evid));
/****************************************************************************************************/

$model = & new EnqListModel();
$view = & new EnqListView();

$controller = & new EnqListController($model, $view);

print replaceEnq($controller->show());
exit;
