<?php
//$_SERVER["QUERY_STRING"] = $_GET['rid'];
if ($_GET['lang360']) {
    $_COOKIE['lang360'] = $_GET['lang360'];
}
//1
$_SESSION['login']['lang_type'] = $_GET['lang360'];

if ($_GET['mobile']) {
    //crm_defineにて設定の文字列ならなんでもいい
    $_SERVER['HTTP_USER_AGENT'] = 'DoCoMo';
}

if (!$_GET['page'] || !ctype_digit($_GET['page'])) {
    $_GET['page'] = 1;
}

define('NOT_USE_CHACHE'		,1); //キャッシュを読まない

define('TEST_MODE',1);

define('DIR_ROOT', "");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFDB.php');
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
encodeWebOutAll();
session_start();

class EnquetePrevControler extends EnqueteControler
{
    public function show()
    {
        $this->setEnquete(_360_Enquete :: fromQuery($_GET['rid']));
        $event = & $this->enquete->getEvent();
        $this->viewer->initialize($this->enquete, $this->getAnswers());

        $page = $_GET['page'];

        if ($event['lastpage'] < $page || $page < 0) {
            return '指定されたページは存在しません';
        }

        return $this->viewer->show($page);
    }
}

class EnquetePrevRender extends _360_EnqueteRender
{

    public function getFormArea($body)
    {
        //FORMいらないので消しておく
        return $body;
    }
}

/************************************************************************************************************/

$viewer =& new _360_EnqueteViewer();
$render = new EnquetePrevRender();
$viewer->setRender($render);
//携帯判定
if (IS_MOBILE) {
    $viewer->render->isMobile = true;
}
$controler =& new EnquetePrevControler($viewer);
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

$DIR_IMG = DIR_IMG;
//画面確認モード　と左上に表示

        $get = $_GET;
        $page = $get['page'];
        $event = $controler->enquete->getEvent();

        //基本
        $href = (is_good($get['lang360']))? 'preview.php?rid='.$get['rid'].'&lang360='.$get['lang360']:'preview.php?rid='.$get['rid'];
        if ($get['mobile']) {
            $mobile = <<<__HTML__
<a href="{$href}&page={$page}"><img src="{$DIR_IMG}navi_pc_off.gif" onMouseOut="this.src='{$DIR_IMG}navi_pc_off.gif'" onMouseOver="this.src='{$DIR_IMG}navi_pc_on.gif'" border="0" alt="PC版を表示" style="margin-bottom:-5px;" /></a>
__HTML__;
        } else {
            $mobile = <<<__HTML__
<a href="{$href}&mobile=1&page={$page}"><img src="{$DIR_IMG}navi_mobile_off.gif" onMouseOut="this.src='{$DIR_IMG}navi_mobile_off.gif'" onMouseOver="this.src='{$DIR_IMG}navi_mobile_on.gif'" border="0" alt="携帯版を表示" style="margin-bottom:-5px;" /></a>
__HTML__;

//			$w = WIDTH_BACKUP;
//			$mobile = '<div style="text-align:right;width:'.$w.'px"><a href="'.$href.'&mobile=1&page='.$page.'">[携帯版]</a></div>';
        }

        $next = ($page < $event['lastpage'])? $next = $href.'&page='.($page + 1): '';
        $pre = (1 < $page)? $href.'&page='.($page - 1): '';
        $link = array();
        if ($pre) {
            $link[] = <<<__HTML__
 <a href="{$pre}"><img src="{$DIR_IMG}cursor_left.gif" onMouseOut="this.src='{$DIR_IMG}cursor_left.gif'" onMouseOver="this.src='{$DIR_IMG}cursor_left_on.gif'" border="0" alt="前のページへ" style="margin-bottom:-3px;" /></a>
 &nbsp;&nbsp;
__HTML__;
        }
        if ($next) {
            $link[] = <<<__HTML__
<a href="{$next}"><img src="{$DIR_IMG}cursor_right.gif" onMouseOut="this.src='{$DIR_IMG}cursor_right.gif'" onMouseOver="this.src='{$DIR_IMG}cursor_right_on.gif'" border="0" alt="次のページへ" style="margin-bottom:-3px;" /></a>
__HTML__;
        }
        $link = implode('&nbsp;&nbsp;', $link);
echo <<<__HTML__
<style type="text/css"><!--
/* ページ数表記等 */
#navi {
position: fixed;
z-index: 9999;
left: 0px;
top: 0px;
color:#444444;
background:url({$DIR_IMG}prevtop_bg.gif) no-repeat bottom right;
margin:0;
padding:4px 7px 7px 5px;
text-align:left;
}
* html #navi {
position: absolute;
top: expression((documentElement.scrollTop || document.body.scrollTop) + 0 + 'px' );
left: expression((documentElement.scrollLeft || document.body.scrollLeft) + 0 + 'px');
}
--></style>

<div id="navi">
 <strong style="color:#3366cc; font-size:14px;">デザインプレビュー</strong>&nbsp;

 [{$page}/{$event['lastpage']}ページ]&nbsp;
{$link}
</div>
<!-- #navi end -->

__HTML__;
exit;

/************************************************************************************************************/
