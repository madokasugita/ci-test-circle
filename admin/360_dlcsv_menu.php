<?php

/**
 * PG名称：CSVダウンロードメニュー
 * 日  付：
 * 作成者：
 *
 * 更新履歴
 */
/**************************************************************************/

define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . '360_Function.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . 'CbaseFErrorMSG.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseEncoding.php');

//セッションチェック

session_start();
Check_AuthMng(basename(__FILE__));
encodeWebAll();
$page_name = '各種データダウンロード';

$PHP_CSV_DL = "360_dlcsv.php?" . getSID();

//$csvmenus[] = array ('pw','パスワード一覧','上級,一般,総合　全て共通のCSVが出力されます');

$tmp = array('0'=>'全て');
foreach ($_360_sheet_type as $k => $v)
    $tmp[$k] = $v;
$form = FForm :: select('sheet_type', $tmp);

$csvmenus[] = array ('admit','承認状況/承認者一覧','&nbsp;',$form);
foreach (range(1, INPUTER_COUNT) as $i) {
    $csvmenus[] = array ('relation','回答者選定・'.$GLOBALS['_360_user_type'][$i].'一覧','未承認のデータも含みます',$form, $i);
}
$csvmenus[] = array ('all_input','回答者選定・回答者一覧','未承認のデータも含みます',$form);
$csvmenus[] = array ('relation','参照者一覧','フィードバックを参照できる方',$form, VIEWER_USER_TYPE);

$html = getHtmlCSVMenuTable();
$objHtml = & new ResearchAdminHtml($page_name);
echo $objHtml->getMainHtml($html);
exit;

function getHtmlCSVMenuTable()
{
    global $page_name ,$csvmenus,$PHP_CSV_DL;
    $html .=<<<HTML
<h1>$page_name</h1>
<table width="90%" border="1" bordercolor="#333333" class="table1" cellpadding="5" cellspasing="5" style="margin-left:20px">
<colgroup class="td2" width="20%">
<colgroup class="td2" width="70%">
<colgroup class="td2" width="10%">
<tr align="center" class="td1">
<td>
CSVの種類
</td>
<td>
設定・説明
</td>
<td>
条件
</td>
<td>
ダウンロード
</td>
</tr>
HTML;
$backgroundcolor[0] = "#f6f6f6";
$backgroundcolor[1] = "#ffffff";
$i = 0;
foreach ($csvmenus as $csvmenu) {
    $type = (is_good($csvmenu[4]))? "&type=".$csvmenu[4]:"";
    $html .=<<<HTML
<form action="{$PHP_CSV_DL}&mode={$csvmenu[0]}{$type}" method="post">
<tr align="left" style="background-color:{$backgroundcolor[++$i%2]}">
<td>
$csvmenu[1]
</td>
<td>
$csvmenu[2]
</td>
<td>
$csvmenu[3]
</td>
<td>
<input type="submit" value="ダウンロード"class="imgbutton90">
</td>
</tr>
</form>
HTML;
}

$html .=<<<HTML
</table>
HTML;

    return $html;
}
