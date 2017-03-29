<?php

/** 暗号化のキー ( 本来はcrm_define2.phpのものを使うが、別環境にまたがる可能性があるので固定にする)*/
define('SYSTEM_RANDOM_STRING', 'cbase.research.enq_copy');

/** ルートディレクトリ */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseFCrypt.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once(DIR_LIB.'CbaseFManage.php');

require_once(DIR_ADMIN_CLASSES . DIRECTORY_SEPARATOR . 'EnqCopy' . DIRECTORY_SEPARATOR . 'EnqCopy.php');
session_start();
Check_AuthMng(basename(__FILE__));

/** 自身のファイル名 */
define('PHP_SELF', getPHP_SELF()."?".getSID());

/***********************************************************************************/
$EnqCopy = new \SmartReview\Admin\EnqCopy\EnqCopy();
//モード分け
switch ($_REQUEST['mode']) {
    case 'export' :
        $contents = $EnqCopy->getHtmlExport();
        break;
    case 'import' :
        encodeWebAll();
        $contents = $EnqCopy->getHtmlImport();
        break;
    default :
        encodeWebAll();
        $contents = $EnqCopy->getHtmlDefault();
}

$objHtml =& new ResearchAdminHtml("import/export");
echo $objHtml->getMainHtml($contents);
exit;
