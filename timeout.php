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
$AuthSession->sessionReset();
$login=<<<HTML
<a href="360_login.php">[ ####linkname_login#### ]</a>
HTML;

define('PAGE_TITLE','####timeout_title####');




if($_GET['nb'])
    $login="";
$strHtml_ =<<<HTML
<div style="width:800px;text-align:right;margin-left:auto;margin-right:auto" ></div>
<br>
<br>

<table style="width:600px;text-align:right;margin-left:auto;margin-right:auto" border="0" cellpadding="10" cellspacing="0" bgcolor="#CCCCCC">
<tr>
<td align="left">
<pre>
####timeout_1####

</pre>
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
