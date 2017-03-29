<?php

/**
 * PG名称：回答後ページ
 * 日　付：2007/04/29
 * 作成者：cbase Kido
 */
/******************************************************************************************************/

define("DIR_ROOT", "");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once DIR_LIB . 'CbaseFunction.php';
require_once DIR_LIB . 'CbaseEncoding.php';
encodeWebOutAll();

session_start();
define('PAGE_TITLE', 'Error!');
define('C_PAGE_BACK', '360_menu.php?' . getSID());

$message = getErrorMessage($_GET['message']);
$link = getLinkTag();

$strHtml_ =<<<HTML
<div style="width:800px;text-align:right;margin-left:auto;margin-right:auto" ></div>
<br>
<br>
<table style="width:800px;text-align:right;margin-left:auto;margin-right:auto" width="800" border="0" cellpadding="10" cellspacing="0" bgcolor="#CCCCCC">
<tr>
<td align="left">
<pre>{$message}</pre>
<br><br>
{$link}
</td>
</tr>
</table>
<br>
<br>
HTML;
$html = HTML_header();
$html .= HTML_top(); //ロゴの帯
$html .= $strHtml_;
$html .= HTML_bottom(); //コピーライト帯
$html .= HTML_footer(); //フッタ
print $html;
exit;
/****************************************************************************************************/
function getLinkTag()
{
    if (ereg(DIR_MNG, $_SERVER['HTTP_REFERER'])) {
        $DIR_MNG = DIR_MNG;
        $login =<<<HTML
<a href="{$DIR_MNG}" target="_top">[ 管理ログインページ ]</a>
HTML;
    } elseif ($_GET['back']) return '<a href="' . C_PAGE_BACK . '">####linkname_mypage####</a>';
    else
        return<<<HTML
<a href="360_login.php">[ ####linkname_login#### ]</a>
HTML;
}

function getErrorMessage($message)
{
    if(is_void($message)) $message = 0;
    if(is_numeric($message))

        return "####error_{$message}####";
    else
        return html_escape($message);
}
