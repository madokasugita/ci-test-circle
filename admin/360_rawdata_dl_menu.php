<?php

/**
 * PGNAME:
 * DATE  :2009/06/29
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . '360_FHtml.php');
/****************************************************************************************************/
session_start();
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
/****************************************************************************************************/
encodeWebAll();
/****************************************************************************************************/
Check_AuthMng(basename(__FILE__));
foreach ($_360_user_type as $k => $v) {
    if($k<=INPUTER_COUNT)

$options1 .=<<<HTML
    <input type="checkbox" name="user_type[]" value="{$k}" checked>{$v}
HTML;
}

$options2 = getHtmlSheetTypeRadio();

$options3 = implode("", FForm::replaceArrayChecked(FForm::radiolist('test_flag', array("含まない", "含む", "テストユーザーのみ")), 0));

foreach (array(1=>"タブ削除", 2=>"改行コード置換（CRLF→LF)") as $k => $v) {
    $options4 .=<<<HTML
    <input type="checkbox" name="replace_type[]" value="{$k}" checked>{$v}
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
<title>評価Rawデータダウンロード</title>
</head>
<body>
<div id="container-iframe">
<div id="main-iframe">
<h1>評価Rawデータダウンロード</h1>
<form method="POST" action="DLspecial.php" enctype="multipart/form-data" target="_blank">
{$SID}
<table class="searchbox" style="width:auto;margin:20px 30px">
<th bgcolor="#eeeeee" align="right">シート</th>
<td bgcolor="#ffffff">{$options2}</td>
</tr>
<tr>
<th bgcolor="#eeeeee" align="right">評価者タイプ</th>
<td bgcolor="#ffffff">{$options1}</td>
</tr>
<tr>
<th bgcolor="#eeeeee" align="right">状態</th>
<td bgcolor="#ffffff"><input type="radio" name="answer_state" id="answer_state0" value="1" checked><label for="answer_state0">完了のみ</label>
<input type="radio" name="answer_state" id="answer_state10" value="2"><label for="answer_state10">途中保存のみ</label>
<input type="radio" name="answer_state" id="answer_state" value="3"><label for="answer_state">両方</label>
</td>
</tr>
<tr>
<th bgcolor="#eeeeee" align="right">テストユーザー</th>
<td bgcolor="#ffffff">{$options3}</td>
</tr>
<tr>
<th bgcolor="#eeeeee" align="right">データ変換項目</th>
<td bgcolor="#ffffff">{$options4}</td>
</tr>
<tr>
<th bgcolor="#eeeeee" align="right">並び順</th>
<td bgcolor="#ffffff"><input type="radio" name="order" id="order1" value="1" checked><label for="order1">評価者タイプ順</label>
<input type="radio" name="order" id="order2" value="2"><label for="order2">対象者のID</label>
</td>
</tr>
<tr>
  <th bgcolor="#eeeeee"></th>
  <td bgcolor="#ffffff" align="center"><input type="submit" id="next" name="next" value="ダウンロード" class="white button wide"/></td>
</tr>
</table>
</form>

</div>
</div></div>
</BODY>
</HTML>
HTML;
