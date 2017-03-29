<?php
    //
    define('DIR_ROOT', "../");
    require_once(DIR_ROOT."crm_define.php");
    require_once(DIR_LIB.'CbaseEncoding.php');
    encodeWebAll();

    $path = DIR_LIB;
    //require_once($path."CbaseFManage.php");

    session_start();
    if (!$_SESSION["muid"] || !$_SESSION['pdate']) {
        header("Location: index.php?mode=re_auth");
        exit;
    }
    //Check_AuthMng(basename(__FILE__));

    $RESEARCH_TITLE = RESEARCH_TITLE;
    $SID = getSID();

$html = '';
$strLastCat = '';
$strPend = "</ul>\n</div>\n\n" ;

foreach ($arMenu as $menu) {
    if (strstr($_SESSION["permitted"], $menu[1]) == false || $menu[0]=="edit")
        continue;
    if ($strLastCat <> $menu[0]) {
        //閉じタグ
        if ($strLastCat)
            $html .= $strPend;
        //開始タグ
        $toptarget = explode(",", $menu[1]);
        $toptarget = $toptarget[0];
        $html .= Get_MenuTitle($menu[0],$toptarget);
    }
    //リンク調整 
    if (strstr($menu[1], ",")) {
        $aryMenu1 = array ();
        $aryMenu1 = explode(",", $menu[1]);
        $menu[1] = $aryMenu1[0];
    }
    //メニュー出し
    $tmpurl = $menu[1] . ((strstr($menu[1], "http") || strstr($menu[1], ".html")) ? "" : "?" . getSID()); //他サイトリンクはSID除去
    $html .= Get_MenuLink($menu[0], $menu[2], $tmpurl, $menu[3], "", $menu);
    //カテゴリー記録
    $strLastCat = $menu[0];
}
$html .= $strPend;
/**********************************************************************************************************************/
/**
 * メニュータイトルの取得
 * @param string menutype メニューのカテゴリ名
 * @return string タイトル部分のhtml
 */
function Get_MenuTitle($menutype,$target)
{
    global $nameMenu;
    $SID = getSID();

    return<<<EOM
<div class="off" onclick="topmenu_onclick(this)">
<a class="menu-icon menu-{$menutype}" style="cursor:pointer;user-select: none;-moz-user-select: none;-webkit-user-select: none;-ms-user-select: none;"><span class="menu-title">{$nameMenu[$menutype][0]}</span></a>

<ul class="sub">
EOM;
}

/**
 * メニューのリンク生成
 * @param stirng title メニュー名
 * @param string link aタグのリンク
 * @param string target aタグのtarget属性
 * @return 型名 戻り値の説明
 */
function Get_MenuLink($menutype, $title, $link, $target, $ext = "", $menu = array ())
{
    if ($menu[4])
        $adminmark = '<font color="red">*</font>';
    else
        $adminmark = '';

    return "<li><a href=\"{$link}\" target=\"{$target}\" {$ext}>{$title}{$adminmark}</a></li>\n";
}
/**********************************************************************************************************************/
$SID = getSID();
foreach ($arMenu as $menu) {
    if (strstr($_SESSION["permitted"], $menu[1]) == false || $menu[0]!="edit")
        continue;

    $menu[1] = array_shift(explode(",", $menu[1]));
    $edit .= "<li><a href=\"{$menu[1]}?{$SID}\" target=\"{$menu[3]}\">{$menu[2]}</a> | </li>";
}

$logout = Get_MenuLink("logout", "ログアウト", "logout.php?" . getSID(), "_top", "alt=\"{$_SESSION['id']}\"");
$img = DIR_IMG;
$js = DIR_JS;

//ログイン期限表示
$ADMIN_LOGIN_PERIOD = "";
if(!is_zero(ADMIN_LOGIN_PERIOD)) {
    $ADMIN_LOGIN_PERIOD = "利用期間 ".date("Y年m月d日 H:i", strtotime(ADMIN_LOGIN_PERIOD))." まで&nbsp&nbsp&nbsp&nbsp";
    if (strtotime(ADMIN_LOGIN_PERIOD) < strtotime('+10 day'))
        $ADMIN_LOGIN_PERIOD = '<font color="#ff0000">'.$ADMIN_LOGIN_PERIOD.'</font>';
}

    echo <<<__HTML__
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="x-ua-compatible" content="IE=10" >
<meta http-equiv="x-ua-compatible" content="IE=EmulateIE10" >
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title>{$RESEARCH_TITLE}</title>
<link rel="shortcut icon" href="{$GDF->get('DIR_IMG')}favicon.ico">
<link rel="apple-touch-icon-precomposed" href="{$GDF->get('DIR_IMG')}favicon.png">

<link rel="stylesheet" href="{$img}360_adminpage.css" type="text/css" media="all" />
<script type="text/javascript" language="javascript" src="{$js}jquery-1.7.1.min.js" charset="UFT-8"></script>
<script type="text/javascript" language="javascript" src="{$js}common.js" charset="UFT-8"></script>
<script type="text/javascript" language="javascript" src="{$js}ajax.js" charset="UFT-8"></script>

<script type="text/javascript" language="javascript">
<!--
function resizeWindow()
{
    if ($(window).width()>1100) {
        $('#container').css('margin','0 30px');
        $('body').css('backgroundColor','#e0e0e0');
        $('#container').css('backgroundColor','#ffffff');
        $("#header").css('width', '97%');
        $('#container').css('-moz-box-shadow','0 0 7px 5px #323230');
        $('#container').css('-webkit-box-shadow','0 0 7px #323230');
    } else {
        $('#container').css('margin','0');
        $("#header").css('width', '980px');
    }

    var agent = navigator.userAgent;
    if (agent.search(/iPhone/) != -1 || agent.search(/iPad/) != -1 || agent.search(/Android/) != -1) {
        //$(".off").unbind("mouseover").unbind("mouseout");
    } else {
        $(".off").bind("mouseover", function () {
            topmenu_mouseover(this);
        });
        $(".off").bind("mouseout", function () {
            topmenu_mouseout(this);
        });
    }
}
function resize_iframe()
{
    var h = $(window).height();
    var t = $("#mainframe").offset().top;
    var s = (typeof document.body.style.maxHeight != "undefined") ? 18 :0;//IE6だったら0
    $("#mainframe").height(h-t-s);
}

$(function () {
    $(window).bind("resize", resizeWindow);
    $(window).resize(resize_iframe);
    resize_iframe();
    resizeWindow();
});
function topmenu_mouseover(obj)
{
    var now_menu;
    obj.opened = true;
    if(obj.closing) clearTimeout(obj.closing);
    obj.className = 'on';
    obj.children[1].style.zIndex=10;
    showIe6Iframe(obj.children[1], 0, 0, 0, 0);

    if (now_menu && now_menu != obj) {
        if(now_menu.closing) clearTimeout(now_menu.closing);
        now_menu.opened = false;
        now_menu.className = 'off';
    }
    now_menu = obj;
}

function topmenu_mouseout(obj)
{
    if (obj.opened) {
        obj.opened = false;
        if(obj.closing) clearTimeout(obj.closing);
        obj.closing = setTimeout(function () {
            if(!obj.opened) obj.className = 'off';
            hideIe6Iframe(obj);
        }, 1);
    }
}

function topmenu_onclick(obj)
{
    if (obj.opened) {
        obj.opened = false;
        obj.className = 'off';
        hideIe6Iframe(obj);
    } else {
        $(".on").each(function () {
            topmenu_mouseout(this);
        });
        topmenu_mouseover(obj)
    }
}

//-->
</script>
</head>
<body id="topmenu">
<div id="container">
  <div id="wrap">
   <div id="wrap-inner" class="clearfix">

    <div id="header">
     <div id="header-inner">
      <a href="index2.php?{$SID}"><div class="header-title">####main_title####</div></a>

    <ul class="sys-menu">
        <li>{$ADMIN_LOGIN_PERIOD}</li>
        <li><strong>ID:{$_SESSION['id']}</strong> | </li>
        {$edit}
        <li><a href="manual.php?{$SID}" target="main">マニュアル</a> | </li>
        <li><a href="check_env.php?{$SID}" target="main">環境チェック</a> | </li>
        {$logout}
    </ul>

     </div>
    </div><!-- header end -->
    <div id="contents">
     <div id="menu">
      <div id="menu-inner">
{$html}
      <div class="menu-clearit"></div>

      </div>
     </div><!-- menu end -->

     <div id="main">
      <div id="main-inner">
       <iframe src="main.php" name="main" id="mainframe" scrolling="auto" frameborder="0" marginwidth="0" marginheight="0" hspace="0" vspace="0" seamless>iframe対応</iframe>
      </div>
     </div><!-- main end -->
    </div><!-- contents end -->

   </div><!-- wrap-inner end -->
  </div><!-- wrap end -->

  <div id="footer">
   <div id="footer-inner">
    <!-- コピーライト -->
   </div>
  </div><!-- footer end -->

 </div><!-- container end -->
</body>
</html>
__HTML__;
    exit;
