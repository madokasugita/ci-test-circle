<?php
require_once 'CbaseHtml.php';
class UserHtml extends CbaseHtml
{
    public function getHeaderHtml()
    {
        $strHTML = HTML_header(); //ヘッダ
        $strHTML .= HTML_top(); //ロゴの帯

        return $strHTML;
    }

    public function getBodyFooterHtml()
    {
        $strHTML = HTML_bottom(); //コピーライト帯
        $strHTML .= HTML_footer(); //フッタ

        return $strHTML;
    }
}

/**
 * 右寄せ
 */
function HTML_right($str = '')
{
    return '<div class="right">' . $str . '</div>' . "\n";
}

/**
 * 左寄せ
 */
function HTML_left($str = '')
{
    return '<div class="left">' . $str . '</div>' . "\n";
}

/**
 *真ん中
 */
function HTML_middle($str = '')
{
    return '<div class="middle">' . $str . '</div>' . "\n";
}

/**
 * ログアウト用リンクを返す
 * @return string HTML
 */
function HTML_logout()
{
    return '<div id="logout"><a href="' . DIR_ROOT . '360_login.php?mode=logout&' . getSID() . '">####mypage_logout####</a></div>';
}

/**
 * お知らせ画面リンクを返す
 * @return string HTML
 */
function HTML_news()
{
    return '<div id="news"><a href="' . DIR_ROOT . '360_news.php?' . getSID() . '">####news_title####</a></div>';
}

/**
 * ヘッダーを出力
 * @param string $prmTitle ページタイトル
 * @param string $prmParam ヘッダに記述
 * @param string $prmBody BODYタグ内に記述
 * @return	string HTML
 */
function HTML_header($prmTitle = PAGE_TITLE, $prmParam = "", $prmBody = "")
{
    $prmTitle = html_escape($prmTitle);
    $prmBody = $prmBody ? " " . $prmBody : "";

    if(!preg_match('/ id=/',$prmBody))
        $prmBody = $prmBody.=' id="'.preg_replace('/[\d]|[\\.php]|[_]/','',basename(getPHP_SELF())).'"';

    $aryCharset['EUC-JP'] = 'EUC-JP';
    $aryCharset['UTF-8'] = 'UTF-8';
    $aryCharset['SJIS'] = 'Shift_JIS';
    $aryCharset['JIS'] = 'iso-2022-jp';
    $strCharset = $aryCharset[OUTPUT_ENCODE];
    $FILE_CSS = FILE_CSS;
    $FILE_JS = FILE_JS;
    $Jquery = DIR_IMG."jquery-1.7.1.min.js";

    return<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset={$strCharset}">
<meta http-equiv="content-script-type" content="text/javascript">
<meta http-equiv="content-style-type" content="text/css">
<title>{$prmTitle}</title>
<link rel="stylesheet" type="text/css" href="{$FILE_CSS}">
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.js"></script>
<script>
<!--
window.jQuery || document.write('<script src="{$Jquery}">\x3C/script>')
//-->
</script>
<script type="text/javascript" src="{$FILE_JS}"></script>
{$prmParam}
</head>
<body{$prmBody}>
<div id="container-iframe">
<div id="main-iframe">
HTML;
}

/**
 * フッターを出力
 * @param string $strFooter
 * @return
 */
function HTML_footer($strFooter = "")
{
    return $strFooter . "\n</div>\n</div>\n</body>\n</html>";
}

/**
 * ページタイトルを出力
 * @param string $prmTitle ページタイトル
 * @return	string HTML
 */
function HTML_pageTitle($prmTitle = PAGE_TITLE)
{
    $SID = getSID();
    $prmTitle = html_escape($prmTitle);

    return<<<HTML
<h1>{$prmTitle}</h1>
HTML;

}
function HTML_top()
{
    $DIR_IMG = DIR_IMG_USER;
    $logo = 'logo.png';

    $files = getMessage('files');
    if ($files) {
        $files=<<<HTML
<div class="infolink">
<div class="mp">####manualqa####</div>
<div class="bd">
{$files}
</div>
</div>
HTML;
    }

    return<<<HTML
<!-- top -->
<div id="top"><img src="{$DIR_IMG}{$logo}" border="0">
</div>
<!-- /top -->
{$files}
HTML;
}

function HTML_languageSwitch($target)
{
    global $_360_language_org;
    $html .=<<<HTML
<div id="languagebar">
HTML;
    foreach ($_360_language_org as $k => $v) {
        if ($_COOKIE['lang360'] == $k) {
            $html .=<<<HTML
[ <b>{$v}</b> ]
HTML;
        } else {
            $hash = md5($target . $k . SYSTEM_RANDOM_STRING);
            $html .=<<<HTML
[ <a href="360_language.php?h={$hash}&l={$k}&t={$target}">{$v}</a> ]
HTML;
        }
    }

    $html .=<<<HTML
</div>
HTML;

    return $html;
}

function HTML_vspace($height = 0)
{
    return<<<HTML
<div style="height:{$height}px"></div>

HTML;
}

function HTML__class($prmStr, $class)
{
    return "<span class=\"{$class}\">{$prmStr}</span>";
}

function HTML_page_path($prmStr)
{
    return '<div style="width:900px;text-align:left;margin-bottom:20px">■　' . $prmStr . '</div>';
}

function HTML_user_info()
{
    return<<<HTML
<!-- user_info -->

<table width="250" border="0" cellpadding="5" cellspacing="1" class="line">
<tr bgcolor="#e6e6e6">
<td width="60"align="right">
<nobr><strong>ユーザーＩＤ：</strong></nobr>
</td>
<td nowrap>
{$_SESSION['uid']}
</td>
</tr>
<tr bgcolor="#e6e6e6">
<td align="right" nowrap>
<strong>名前：</strong>
</td>
<td nowrap>
<nobr>{$_SESSION['name']}</nobr>
</td>
</tr>
<tr bgcolor="#e6e6e6">
<td align="right" nowrap>
<strong>所属：</strong>
</td>
<td nowrap>

<nobr>{$_SESSION['div1_n']}<nobr><br>
<nobr>{$_SESSION['div2_n']}</nobr><br>
<nobr>{$_SESSION['div3_n']}</nobr>
</td>
</tr>
</table>

<br><br>
<table width="100%" border="0" cellpadding="5" cellspacing="1" class="line">
<tr bgcolor="#e6e6e6">
<td colspan = "2" nowrap align="center">
<strong>本人情報</strong>
</td>
</tr>

<tr bgcolor="#e6e6e6">
<td width="60"align="right" nowrap>
<strong>ユーザーＩＤ：</strong>
</td>
<td nowrap>
{$_SESSION['b_uid']}
</td>
</tr>
<tr bgcolor="#e6e6e6">
<td align="right" nowrap>
<strong>名前：</strong>
</td>
<td nowrap>
{$_SESSION['b_name']}
</td>
</tr>
</table>



<!-- /user_info -->

HTML;
}

/**
 * aタグを出力
 * @param	リンク先
 * 			InnerHtml
 * 			リンクに付けるQuery
 */
function HTML_link($prmJumpTo, $prmInner = "", $prmTitle = "", $prmTarget = "", $prmQuery = "")
{
    $strQuery = $prmQuery ? '?' . $prmQuery : "";
    $strTitle = $prmTitle ? ' title="' . $prmTitle : "";
    $strTarget = $prmTarget ? ' target="' . $prmTarget : "";
    $strHtml = FHtml :: tag('a', $prmInner, 'href="' . $prmJumpTo . $strQuery . '"' . $strTitle . $strTarget);

    return $strHtml;
}

/**
 * 最も基本的なタグの仕様で出力する
 * @param	タグ名
 * 			InnerHtml
 * 			タグのパラメータ
 */
function HTML_tag($prmTag, $prmInner = "", $prmParam = "")
{
    $strHtml = '<' . $prmTag . ' ' . $prmParam . '>' . $prmInner . '</' . $prmTag . '>';

    return $strHtml;
}

//===================================================================================
//
//  組み立て系
//
//===================================================================================

function HTML_window_close()
{
    $SID = getSID();

    return<<<HTML
<!-- close -->

<div style="text-align:right;width:900">
<a href="javascript:window.close()">閉じる</a>
</div>
<!-- /close -->


HTML;
}

function HTML_bottom()
{
    $MSG_CBASE_COPY_RIGHT = MSG_CBASE_COPY_RIGHT;

    return<<<HTML
<!-- bottom -->
<div id="copyright">
####copyright####
</div>
<div id="cbase">
{$MSG_CBASE_COPY_RIGHT}
</div>
<!-- /bottom -->

HTML;
}

function HTML_notice($prmNotice, $mode = null)
{

    if (!is_array($prmNotice))
        $prmNotice = array (
            $prmNotice
        );

    if ($mode == "strong")
        $style = " style=\"font-size:15px;font-weight:bold\"";
    $strHTML =<<<HTML
<table width="680" border="0" cellpadding="0" cellspacing="0">

HTML;
    foreach ($prmNotice as $strNotice) {
        $strHTML .=<<<HTML
<tr>
<td height="25"{$style}>$strNotice</td>
</tr>

HTML;
    }
    $strHTML .=<<<HTML
</table>

HTML;

    return $strHTML;
}

function HTML_muser_info()
{
    $term = SYSTEM_TERM;

    return<<<HTML
<!-- muser_info -->

<table width="230" border="0" cellpadding="5" cellspacing="1" class="line">
<tr bgcolor="#e6e6e6">
<td width="60"align="right" nowrap>
<strong>管理者ID：</strong>
</td>
<td nowrap>
{$_SESSION['id']}
</td>
</tr>


<tr bgcolor="#e6e6e6">
<td width="60"align="right" nowrap>
<strong>期間：</strong>
</td>
<td nowrap>
第{$term}期間
</td>
</tr>


</table>

<!-- /muser_info -->

HTML;

}
