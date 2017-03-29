<?php

define('TEST_MODE',1);
/**
 *
 * 2007/07/30 ver1.01 エラーがあった場合returnしていなかったバグを修正
 * 2007/10/24 ver2.00 処理をクラスに分散
 */

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
require_once (DIR_LIB . 'CbaseEnqueteControlerTest.php');
require_once (DIR_LIB . '360_Smarty.php');
encodeWebAll();
session_start();

/************************************************************************************************************/

class THisRender extends _360_EnqueteRender
{
    /**
     * formタグで囲む
     * @param  string $body formで囲まれるべき本文
     * @return string html
     */
    public function getFormArea($body)
    {
        $l = (int) $_GET['lang360'];
        $html = '<form action="' . getPHP_SELF() . '?lang360='.$l.'" method="post">';
        $html .= $body;
        $html .= '</form>';

        return $html;
    }
}

$viewer = & new _360_EnqueteViewer();
$viewer->setRender(new THisRender());

//携帯判定
if (IS_MOBILE) {
    $viewer->render->isMobile = true;
}
$controler = & new EnqueteControlerTest($viewer);
$html = $controler->show();
$html = replaceEnq($html);

$smarty = new MreSmarty();
$smarty->assign('event',$controler->enquete->enquete[-1]);
$smarty->assign('enqbody', $html);
/*
if ($_SESSION['muid'])
    $smarty->assign('back_button',  '<button onclick="window.close()">閉じる</button>');
else
    $smarty->assign('back_button', '<a href="360_menu.php?' . getSID() . '">[ ####linkname_mypage#### ]</a>');
*/
$smarty->assign('back_button', '<a href="#">[ ####linkname_mypage#### ]</a>');
$smarty->display('360_enq.tpl');

//動作確認モード　と左上に表示
print<<<HTML
<div style="font-weight:bold;color:white;background-color:red;padding:3px;position:absolute;top:0px;right:0px">動作確認モード</div>
HTML;
exit;

/************************************************************************************************************/
