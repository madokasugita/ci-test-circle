<?php
/**
 * PG名称：回答後ページ
 * 日　付：2007/04/29
 * 作成者：cbase Kido
 */
/******************************************************************************************************/

define("DIR_ROOT", "");
require_once (DIR_ROOT.'crm_define.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once DIR_LIB.'CbaseFunction.php';
require_once DIR_LIB.'CbaseEncoding.php';
encodeWebOutAll();
$login=<<<HTML
<a href="360_login.php">[ ログインページ ]</a>
HTML;
if (ereg(DIR_MNG,$_SERVER['HTTP_REFERER'])) {

$DIR_MNG = DIR_MNG;
$login=<<<HTML
<a href="{$DIR_MNG}" target="_top">[ 管理ログインページ ]</a>
HTML;

}

session_start();
define('PAGE_TITLE','');
define("C_PAGE_BACK","360_menu.php?".getSID());
$strEnd = '<a href="'.C_PAGE_BACK.'">戻る</a>';
$message = $_GET['message'];
if($_GET['e'])
    $message = urldecode($message);

$message = html_escape($message);

$message = str_replace('&lt;br&gt;','<br>',$message);

if($_GET['back'])
    $login = $strEnd;






if($_GET['nb'])
    $login="";
$strHtml_ =<<<HTML
<div style="width:800px;text-align:right;margin-left:auto;margin-right:auto" ></div>
<br>
<br>

<table style="width:800px;text-align:right;margin-left:auto;margin-right:auto" width="800" border="0" cellpadding="10" cellspacing="0" bgcolor="#CCCCCC">
<tr>
<td align="left">
<p></p>
<p>{$message}</p>
<br><br>
{$login}
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
