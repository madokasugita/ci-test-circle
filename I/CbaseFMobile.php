<?php

$C_REPLACE_TAGS = array
    ("<hr"   => "<br><hr"
    ,"<table" => "<br><table"
    ,"</table>" => "<br>"
    ,"</tr>" => "<br>"
    ,"</td>" => " "
    );

$C_PERMIT_TAGS = "<br><form><input><select><option><textarea><button><font><a>";

/**
 * 携帯用に整形
 * @param	文字列
 * @return	a
 * @author Cbase akama
 */
function getMobileHtml($prmStr)
{
    global $C_REPLACE_TAGS, $C_PERMIT_TAGS;
    preg_match("/<body.*?>(.*)<\/body>/si", $prmStr, $body);
    $body = $body[1];

    $strHtml = stripTagsWithTable($body, $C_PERMIT_TAGS, $C_REPLACE_TAGS);
    $strHeader = '<html><head>';
    $strHeader.= '<meta http-equiv="Content-Type" content="text/html; charset=shift_jis">';
    $strHeader.= '</head><body>';
    $strFooter = '</body></html>';

    return mb_convert_kana($strHeader.$strHtml.$strFooter, "k");
}

/**
 * テーブルを用いて置き換えとstripTags
 * @param	文字列
 * 			許可するタグ
 * 			置き換えるタグ
 * @return	a
 * @author Cbase akama
 */
function stripTagsWithTable($prmStr, $prmPermit, $prmReplace=array())
{
    $result = $prmStr;

    foreach ($prmReplace as $key => $val) {
        $result = str_replace($key, $val, $result);
    }
    $result = preg_replace('/<img.*?alt="*(.*?)"*>/i', "$1", $result);
    $result = strip_tags($result, $prmPermit);
    $result = ereg_replace("\r\n", "\n", $result);
    $result = ereg_replace("\r", "\n", $result);
    $result = ereg_replace("[ \t]+", " ", $result);
    $result = ereg_replace(" +\n", "", $result);
    $result = ereg_replace("\n+", "\n", $result);
    $result = mb_ereg_replace("<br>([ 　\t\n]*<br>)+", "<br>", $result);

    //input type=imageは無効のため
    $result = preg_replace_callback('/<input(.*?)type="image"(.*?)>/i', 'replaceImageToSubmit', $result);

    return $result;
}

function replaceImageToSubmit($match)
{
    $result = $match[0];
    $result = str_replace('"image"', '"submit"', $result);
    $result = str_replace('alt', 'value', $result);
    $result = preg_replace('/src="(.*?)"/i', "", $result);

    return $result;
}

/**
 * 携帯かどうかの判定
 * @param	ユーザーエージェント
 * @return	bool (true=携帯)
 * @author Cbase moto
 */
function isMobile($prmUserAgent, $prmPattern)
{
    if (!$prmPattern) return false;
    return ereg($prmPattern,$prmUserAgent);
}
