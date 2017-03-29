<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>{$event.name}</title>
<meta http-equiv="content-script-type" content="text/javascript">
<meta http-equiv="content-style-type" content="text/css">
<link href="{$dir_user}360_userpage-min.css" type="text/css" rel="stylesheet">
<link href="{$dir_user}360_enq-min.css" type="text/css" rel="stylesheet">
<link href="{$dir_user}360_enqmatrix-min.css" type="text/css" rel="stylesheet">
<script src="{$dir_img}jquery-1.4.2.min.js" type="text/javascript"></script>
<script src="{$dir_img}360_userpage.js" type="text/javascript" ></script>
</head>
<body>

<div class="exfix" class="noprint">
<div id="bar" class="noprint">
<input type="submit" id="menuButton" value="メニューに戻る" class="btn" onclick="location.href='{$menu_link}'; return false;">
※回答内容の保存はされません。
</div>
</div>

<div id="maincontainer">
<div id="top"><img src="{$dir_user}logo.png" alt="logoimage"></div>
<div id="userinfo">{$userinfo.uid} : {$userinfo.name}　{$userinfo.name_}</div>
<div id="title">{$event.name}</div>

<table class="message1">
<tr>
<td width="620" valign="top">
	<pre style="line-height:20px">{$text.enq_caution_}</pre>
</td>
</tr>
</table>
{$event.htmlh}
{$enqbody}
{$event.htmlf}
<div id="copyright">{$text.copyright}</div>
<div id="cbase">{$text.cbase}</div>
</div>

<!-- セル結合js -->
{literal}
<script>
//<!--

var catNum, str1, str2;
var $category1 = $(".category1");
var $category2 = $(".category2");

catNum = 0;
$category1.each(function(i, c){
    $(c).addClass("cat1color" + catNum);

    str1 = $(c).text();
    str2 = $category1.eq(i+1).text();
    
    if(str1==str2){
	$category1.eq(i+1).addClass("vanish");
    }else{
	catNum++;
    }
});

catNum = 0;
$category2.each(function(i, c){
    $(c).addClass("cat2color" + catNum);

    str1 = $(c).text();
    str2 = $category2.eq(i+1).text();

    if(str1==str2){
	$category2.eq(i+1).addClass("vanish");
    }else{
	catNum++;
    };
});

$(".vanish").css("border-top", "hidden");
$(".vanish").text("");

//-->
</script>
{/literal}


{literal}
<script>
var nav = $('.criteria.fix');
if(nav.size() > 0){
    var offset = nav.offset();
    var navContainer = $("<div>");
    navContainer.css("height", nav.height());
    //navContainer.css("height", "auto");
    navContainer.css("margin", "20px auto");
    nav.css("margin-top", 0);
    nav.wrap(navContainer);
    
    $(window).scroll(function () {
      if($(window).scrollTop() > offset.top - 45) {
        nav.css('position', 'fixed');
        nav.css('top', 45);
        nav.css('left', offset.left);
      } else {
        nav.css('position', 'relative');
        nav.css('top', 0);
        nav.css('left', 0);
      }
    });
}
nav.find("strong").click(function(){ nav.find(".smalltable").toggle() });
</script>
{/literal}

</body>
</html>