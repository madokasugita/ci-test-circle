<?php

define('DIR_ROOT', "../");
require_once(DIR_ROOT . 'crm_define.php');
require_once(DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();


error_log(implode("\t", array(
        date("Ymd His")
    , $_SERVER['REMOTE_ADDR']
    , $_SERVER['HTTP_X_FORWARDED_FOR']
    , getIpAddr()
    , $_SERVER['HTTP_USER_AGENT']
    )) . "\n", 3, LOG_CHECK_ENV);

$HTTP_USER_AGENT = html_escape($_SERVER['HTTP_USER_AGENT']);
$DIR_IMG = DIR_IMG;
$DIR_IMG_LOCAL = DIR_IMG_USER_LOCAL . "../"; //ユーザーにはアクセス許可のないパスを指定
echo <<<__HTML__
<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="{$DIR_IMG_LOCAL}check_env.css">
<style type="text/css">
<!--
section#check table td.item {
	background-image: url({$DIR_IMG_LOCAL}img_check01.png) ;
}
section#check table td.item.js {
	background-image: url({$DIR_IMG_LOCAL}img_check02.png) ;
}
section#check table td.result {
	background-image: url({$DIR_IMG_LOCAL}img_check01.png) ;
}
section#check table td.result.js {
	background-image: url({$DIR_IMG_LOCAL}img_check02.png) ;
}
//-->
</style>
<title>動作確認ページ</title>
</head>
<body>
<header>
<h1><img src="{$DIR_IMG_LOCAL}img_check_logo.png" alt="スマレビ"></h1>
</header>

<section id="check">
<h2>環境チェック</h2>
<table>
<tr>
<td class="item">ユーザーエージェント</td>
<td class="result">
__HTML__;
if (preg_match('/Edge\/([0-9\.]+)/', $HTTP_USER_AGENT) ||
    preg_match('/Chrome\/([0-9\.]+)/', $HTTP_USER_AGENT) ||
    preg_match('/\/([0-9\.]+)(\sMobile\/[A-Z0-9]{6})?\sSafari/', $HTTP_USER_AGENT) ||
    preg_match('/trident\/[7]/', $HTTP_USER_AGENT) ||
    preg_match('/Firefox\/([0-9\.]+)/', $HTTP_USER_AGENT) ||
    preg_match('/(Trident.*rv:)([0-9\.]+)/', $HTTP_USER_AGENT)) {
    //Windows Phoneのみ除外
    if (preg_match('/Windows Phone/', $HTTP_USER_AGENT)) {
        echo <<<__HTML__
<img src="{$DIR_IMG_LOCAL}img_check_cross.png"></td>
<td>推奨ブラウザではありません</td>
</tr>
__HTML__;
    } else {
        echo <<<__HTML__
<img src="{$DIR_IMG_LOCAL}img_check_correct.png"></td>
<td>問題ありません</td>
</tr>
__HTML__;
    }
} else {
    echo <<<__HTML__
<img src="{$DIR_IMG_LOCAL}img_check_cross.png"></td>
<td>推奨ブラウザではありません</td>
</tr>
__HTML__;
}

echo <<<__HTML__

<tr>
<td class="item js">JavaScript動作</td>

<script type="text/javascript">
//<!--
document.write('<td class="result js">');
document.write('<img src="{$DIR_IMG_LOCAL}img_check_correct.png">');
document.write('</td>');
document.write('<td class="js">');
document.write('問題ありません');
document.write('</td>')
//-->

</script>
<noscript>
<td class="result js"><img src="{$DIR_IMG_LOCAL}img_check_cross.png"></td>
<td class="js">ブラウザのJavaScriptを有効化してください</td>
</noscript>
</tr>
__HTML__;

echo <<<__HTML__
<tr>
<td class="item">S3サーバー読み込み</td>
<td class="result" id="s3_result"></td>
<td  id="s3_message"></td>
__HTML__;
$filename = "{$DIR_IMG}favicon.png";

echo <<<__HTML__
</tr>
</table>
<script type="text/javascript">
//<!--
var img = new Image();
img.src = '{$filename}';

img.onload = function() {
    document.getElementById('s3_result').innerHTML = '<img src="{$DIR_IMG_LOCAL}img_check_correct.png">';
    document.getElementById('s3_message').innerHTML = '問題ありません';
}
img.onerror = function() {
    document.getElementById('s3_result').innerHTML = '<img src="{$DIR_IMG_LOCAL}img_check_cross.png">';
    document.getElementById('s3_message').innerHTML = 'S3サーバーに接続できません';
}
//-->
</script>
</section>
<section id="support">
<h2>サポート情報</h2>
<table>
<tr>
<th colspan="2">管理画面</th>
</tr>
<tr>
<td class="item">OS</td>
<td class="detail">Windows7、Windows8.1、Windows10</td>
</tr>
<tr>
<td class="item">ブラウザ</td>
<td class="detail">Internet Explorer　11.x<br>Firefox　最新版<br>Safari　最新版<br>Google Chrome 最新版<br>Microsoft Edge 最新版</td>
</tr>
</table>
</section>

</body>
</html>

__HTML__;


exit;

?>