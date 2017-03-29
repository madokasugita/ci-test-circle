<?php

//デバッグ用。登録せずにSQLを表示してくれる。
define('THISPAGE_NO_INSERT', 0);
if (THISPAGE_NO_INSERT) {
    define('DEBUG', 1);
}
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . '360_Importer.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseCSV.php');

require_once (DIR_ADMIN_CLASSES . 'EnqUpdateAll' . DIRECTORY_SEPARATOR . 'EnqUpdateAll.php');
require_once (DIR_ADMIN_CLASSES . 'EnqUpdateAll' . DIRECTORY_SEPARATOR . 'ThisImportDesign.php');
require_once (DIR_ADMIN_CLASSES . 'EnqUpdateAll' . DIRECTORY_SEPARATOR . 'ThisImportModel.php');

session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', '一括更新(詳細)');
/****************************************************************************************************************************/

/********************************************************************************************************************/
if ($_REQUEST['csvdownload']) {
    $EnqUpdateAll = new SmartReview\Admin\EnqUpdateAll\EnqUpdateAll();
    $EnqUpdateAll->prmStrCol = 'seid as now_seid,seid,evid,title,hissu,choice,chtable,num,num_ext,category1,category2,html2,ext';
    $EnqUpdateAll->csvdownload();
}

$main = new Importer360(new SmartReview\Admin\EnqUpdateAll\ThisImportModel(), new SmartReview\Admin\EnqUpdateAll\ThisImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;
