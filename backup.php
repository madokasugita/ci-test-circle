<?php
/**
 * PG名称：回答後ページ
 * 日　付：2007/04/29
 * 作成者：cbase Kido
 */
/******************************************************************************************************/

define("DIR_ROOT", "");
require_once DIR_ROOT."crm_define.php";
require_once DIR_LIB.'360_FHtml.php';
require_once DIR_LIB.'CbaseEncoding.php';
encodeWebAll();
session_start();
define('PAGE_TITLE','####backup_title####');
define("C_PAGE_BACK","360_menu.php?".getSID());

if($_SESSION['muid'])
    $strEnd = '<button onclick="window.close()">閉じる</button>';
else
    $strEnd = '<a href="'.C_PAGE_BACK.'">[ ####linkname_mypage#### ]</a>';

$strHtml_ =<<<HTML
<div style="width:700px;text-align:right;margin-left:auto;margin-right:auto" align="center" >####backup_title####</div>
<br>
<br>

<table width="700" border="0" cellpadding="10" cellspacing="0" bgcolor="#CCCCCC" align="center">
<tr>
<td align="center">
<pre>####backup_1####</pre>
<p>{$strEnd}</p>
</td>
</tr>
</table>
<br>
<br>
HTML;

$strHTML =HTML_header();
$strHTML .= HTML_top();//ロゴの帯
$strHTML .= $strHtml_;
$strHTML .= HTML_bottom();//コピーライト帯
$strHTML .= HTML_footer();//フッタ
print $strHTML;
exit;
