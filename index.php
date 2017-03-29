<?php

/**
 *
 * 2007/07/30 ver1.01 エラーがあった場合returnしていなかったバグを修正
 * 2007/10/24 ver2.00 処理をクラスに分散
 */
//define('DEBUG', 1);
define('DIR_ROOT', '');

require_once (DIR_ROOT.'crm_define.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFEvent.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseFError.php');
require_once (DIR_LIB . 'CbaseFNoDuplication.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'D.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
require_once (DIR_LIB . 'CbaseDAO.php');
require_once (DIR_LIB . 'CbaseFEnqueteCache.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseEnquete.php');
require_once (DIR_LIB . 'CbaseEnqueteAnswer.php');
require_once (DIR_LIB . 'CbaseEnqueteViewer.php');
require_once (DIR_LIB . 'CbaseEnqueteControler.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360_Enquete.php');
require_once (DIR_LIB . '360_EnqueteRelace.php');
require_once (DIR_LIB . '360_Smarty.php');
encodeWebAll();
session_start();
//意図が不明なので削除。必要ならEnqueteFirstPageに記述ください
//$_SESSION['answer'] = array();
/************************************************************************************************************/
$viewer = & new _360_EnqueteViewer();
$viewer->setRender(new _360_EnqueteRender());

//携帯判定
if (IS_MOBILE) {
    $viewer->render->isMobile = true;
}

$controler = & new _360_EnqueteControler($viewer);
//FDB::begin();
try {
    $html = $controler->show();
} catch (Exception $e) {
    //FDB::rollback();
    _360_error(100);
    exit;
}
//FDB::commit();
checkAuthUsr360();
$html = replaceEnq($html);

$smarty = new MreSmarty();
$smarty->assign('event',$controler->enquete->enquete[-1]);
$smarty->assign('enqbody', $html);

if ($_SESSION['muid'])
    $smarty->assign('back_button',  '<button onclick="window.close()">閉じる</button>');
else
    $smarty->assign('back_button', '<a href="360_menu.php?' . getSID() . '">[ ####linkname_mypage#### ]</a>');

$smarty->display('360_enq.tpl');
/***********************************************************************************************************/
