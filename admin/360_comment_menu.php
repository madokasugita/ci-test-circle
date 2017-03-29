<?php

/**
 * PG名称：コメント訂正機能メニュー画面
 * 日  付：
 * 作成者：
 *
 * 更新履歴
 */
/**************************************************************************/

define('DIR_ROOT', '../');
require_once DIR_ROOT . "crm_define.php";
require_once DIR_LIB . "CbaseFunction.php";
require_once DIR_LIB . "360_FHtml.php";
require_once DIR_LIB . "CbaseFErrorMSG.php";
require_once DIR_LIB . "CbaseFForm.php";
require_once DIR_LIB . "CbaseFManage.php";
require_once DIR_LIB . "CbaseEncoding.php";
encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));

foreach ($_360_user_type as $k => $v) {
    if($k <= INPUTER_COUNT)
$options1 .=<<<HTML
    <option value="{$k}">{$v}</option>
HTML;
}

foreach ($_360_sheet_type as $k => $v) {
    if($k)
$options2 .=<<<HTML
    <option value="{$k}">{$v}</option>
HTML;
}

foreach ($_360_language as $k => $v) {
$options3 .=<<<HTML
    <option value="{$k}">{$v}</option>
HTML;
}


$SID = getHiddenSID();
$DIR_IMG = DIR_IMG;
print<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<link rel="stylesheet" href="{$DIR_IMG}360_adminpage.css" type="text/css">
<script src="{$DIR_IMG}common.js" type="text/javascript"></script>
<script src="{$DIR_IMG}myconfirm.js" type="text/javascript"></script>
<title>コメント訂正機能メニュー</title>
</head>
<body>
<div id="container-iframe">
<div id="main-iframe">
<h1>回答コメント訂正</h1>
<div class="sub_title"style="margin-left:20px">
1. コメントダウンロード
</div>
<form method="POST" action="360_comment_export.php" enctype="multipart/form-data" target="_blank">
{$SID}
<table style="margin-left:30px"border="0" cellspacing="1" cellpadding="3" bgcolor="#000000">
<td bgcolor="#ffffff">コメントの一覧をダウンロードする</td>
<td bgcolor="#ffffff">

<select name="sheet">
<option value="all">全て</option>
{$options2}
</select>

<select name="type">
<option value="all">全て</option>
{$options1}
</select>
回答者言語:
<select name="lang_">
<option value="all">全て</option>
{$options3}
</select>
対象者言語:
<select name="lang">
<option value="all">全て</option>
{$options3}
</select>

<select name="status">
<option value="0">完了のみ</option>
<option value="10">途中のみ</option>
<option value="all">全て</option>


</select>
<input type="submit" id="next" name="next" value="コメントダウンロード" onSubmit="return this.flag?false:this.flag=true;"class="imgbutton120" />
</td>
</tr>
</table>
</form>

<br><br>

<div class="sub_title"style="margin-left:20px">2. コメントを編集</div>
<div style="text-align:left">
<pre style="margin-left:40px;line-height:20px;">
2.1 手順(1)でダウンロードしたファイルをエクセルで開く。

2.2 コメント列を必要に応じて修正する。
　　　<span style="color:red">* コメントID列を変更しないように注意をお願い致します。</span>

2.3 修正を行なった行の修正チェック列を "1" に変更する。
　　　<span style="color:red">* 手順(3)でファイルをインポートすると、修正チェック列が"1"のコメントのみを更新します。</span>

2.4 上書き保存をする。
</pre>
</div>
<div class="sub_title"style="margin-left:20px">3. コメントインポート</div>
<form method="POST" action="360_comment_import.php" enctype="multipart/form-data" target="_blank">
{$SID}
<table style="margin:10px 30px"border="0" cellspacing="1" cellpadding="3" bgcolor="#000000">
<td bgcolor="#ffffff">手順(2.4)で保存したCSVを取り込み、コメントを更新します。</td>
<td bgcolor="#ffffff"><input type="submit" id="next" name="next" value="コメントインポート" onSubmit="return this.flag?false:this.flag=true;" class="imgbutton120"/>
</td>
</tr>
</table>
</form>
</div>
</div></div>
</BODY>
</HTML>
HTML;
