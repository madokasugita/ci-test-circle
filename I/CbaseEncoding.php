<?php

/**
 * 文字コード管理クラス
 */
require_once (DIR_LIB . 'CbaseCommon.php');
require_once (DIR_LIB . 'CbaseFgetcsv.php');
require_once (DIR_LIB . '360_Function.php');
define('INTERNAL_ENCODE', 'UTF-8'); /** 内部エンコード */
define('ENCODE_WEB_IN'	,'UTF-8');		/** ブラウザからの文字を受け取ったのエンコード */
define('ENCODE_WEB_OUT'	,'UTF-8');		/** ブラウザへ文字を出力するときのエンコード */
define('ENCODE_FILE_IN', 'SJIS-win'); /** 外部ファイルを読み込む時のエンコード */
define('ENCODE_FILE_OUT', 'SJIS-win'); /** 外部ファイルを書き出す時のエンコード */
define('ENCODE_HTML_IN', 'eucJP-win'); /** 外部HTMLファイルを読み込む時のエンコード */
define('ENCODE_HTML_OUT', 'eucJP-win'); /** 外部HTMLファイルを書き出す時のエンコード */
define('ENCODE_DB_IN', 'eucJP-win'); /** DBから文字を受け取るときのエンコード */
define('ENCODE_DB_OUT', 'eucJP-win'); /** DBへ文字を投げるときのエンコード */
define('ENCODE_UPLOAD_IN', 'SJIS-win'); /** アップロードされたファイルのエンコード */
define('ENCODE_DOWNLOAD_OUT', 'SJIS-win'); /** ファイルをダウンロードするときのエンコード */
define('ENCODE_MAIL_OUT', 'ISO-2022-JP'); /** メールの送る時のエンコード */
define('ENCODE_JP_GRAPH', 'eucJP-win'); /** 外部HTMLファイルを読み込む時のエンコード */

define('ENCODE_INTERNAL', INTERNAL_ENCODE); /** 内部エンコード */
define('INTERNAL_CHARSET', getCharset(INTERNAL_ENCODE)); /** 内部文字セット */
define('DEFAULT_CHARSET', getCharset(ENCODE_WEB_OUT)); /** ブラウザ出力文字セット */
/********************************************************************************************************************/

ini_set('default_charset', '');
ini_set('mbstring.http_input', 'pass');
mb_http_output('pass');
mb_internal_encoding(INTERNAL_ENCODE);
mb_regex_encoding(INTERNAL_ENCODE);

function encodeJpGraph($string)
{
    return mb_convert_encoding($string, ENCODE_JP_GRAPH, INTERNAL_ENCODE);
}
function encodeWebAll()
{
    encodeWebInAll();
    encodeWebOutAll();
}

function encodeWebInAll()
{
    encodeWebInUrldecode();
    mb_convert_variables(INTERNAL_ENCODE, ENCODE_WEB_IN, $_GET);
    mb_convert_variables(INTERNAL_ENCODE, ENCODE_WEB_IN, $_POST);
    mb_convert_variables(INTERNAL_ENCODE, ENCODE_WEB_IN, $_FILES);
    mb_convert_variables(INTERNAL_ENCODE, ENCODE_WEB_IN, $_COOKIE);
    mb_convert_variables(INTERNAL_ENCODE, ENCODE_WEB_IN, $_REQUEST);
    encodeWebInStripslashes();
}
function encodeWebOutAll()
{
    ob_start("convertWebOut");
}

function encodeWebIn($string)
{
    return mb_convert_encoding($string, INTERNAL_ENCODE, ENCODE_WEB_IN);
}

function encodeWebOut($string)
{
    return mb_convert_encoding($string, ENCODE_WEB_OUT, INTERNAL_ENCODE);
}

function encodeFileIn($string)
{
    return mb_convert_encoding($string, INTERNAL_ENCODE, ENCODE_FILE_IN);
}

function encodeFileOut($string)
{
    return mb_convert_encoding($string, ENCODE_FILE_OUT, INTERNAL_ENCODE);
}

function encodeHtmlIn($string)
{
    return mb_convert_encoding($string, INTERNAL_ENCODE, ENCODE_HTML_IN);
}

function encodeHtmlOut($string)
{
    return mb_convert_encoding($string, ENCODE_HTML_OUT, INTERNAL_ENCODE);
}

function encodeDbIn($string)
{
    return mb_convert_encoding($string, INTERNAL_ENCODE, ENCODE_DB_IN);
}

function encodeDbOut($string)
{
    return mb_convert_encoding($string, ENCODE_DB_OUT, INTERNAL_ENCODE);
}

function encodeUploadIn($string)
{
    return mb_convert_encoding($string, INTERNAL_ENCODE, ENCODE_UPLOAD_IN);
}

function encodeDownloadOut($string)
{
    return mb_convert_encoding($string, ENCODE_DOWNLOAD_OUT, INTERNAL_ENCODE);
}

function encodeDownloadFilename($string)
{
    $string = preg_replace("/ |\//", "_", $string);
    $to_encoding = (preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT']) == 1 || preg_match("/Trident/", $_SERVER['HTTP_USER_AGENT']) == 1) ? 'SJIS-win' : 'UTF-8';

    return mb_convert_encoding($string, $to_encoding, INTERNAL_ENCODE);
}

function encodeMailOut($string)
{
    return mb_convert_encoding(mb_convert_kana($string, 'KV'), ENCODE_MAIL_OUT, INTERNAL_ENCODE);
}

function encodeMimeHeader($string)
{
    return mb_encode_mimeheader(mb_convert_kana($string, 'KV'), ENCODE_MAIL_OUT);
}

function getMailCharset()
{
    return getCharset(ENCODE_MAIL_OUT);
}

function convertWebOut($html)
{
    if(NOT_CONVERT!==1)
    $html = replaceMessage($html); //多言語対応
    //$html = encodeWebOut($html);
    $html = replaceCharsetTag($html);
    $html = str_replace('<html lang="ja">','<html>',$html);

    header("Content-Type: text/html; charset=" . DEFAULT_CHARSET);
    header("Content-Length: " . strlen($html));

    return $html;
}

//多言語対応
function replaceMessage($html)
{
    return preg_replace_callback("/####(.*?)####/i", "replaceMessage_", $html);
}

//多言語対応
function replaceMessage_($match)
{
    global $GLOBAL_360_LANG;
    $key = $match[1];
    if (ereg('^USERINFO:(.*)$',$key,$match2)) {
        return html_escape($_SESSION['login'][$match2[1]]);
    }
    if (ereg('^sort:(.*)$',$key,$match2)) {
        return '';
    }
    $language = $_COOKIE['lang360'] ? $_COOKIE['lang360'] : 0;

    return getMessage($key);
}

function replaceCharsetTag($html)
{
    $charset = DEFAULT_CHARSET;
    $meta_charset = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset={$charset}\">";

    return preg_replace("/<meta[^>]*content-type[^>]*charset[^>]*>/si", $meta_charset, $html);
}

function getCharset($charset)
{
    if (preg_match("/SJIS/i", $charset) == 1) {
        return 'Shift_JIS';
    } elseif (preg_match("/EUC/i", $charset) == 1) {
        return 'EUC-JP';
    } elseif (preg_match("/JIS/i", $charset)==1) {
        return 'ISO-2022-JP';
    }

    return $charset;
}

function encodeWebInUrldecode()
{
    if (preg_match("/UP\.Browser|^KDDI\-/", $_SERVER['HTTP_USER_AGENT']) != 1)
        return;
    if (preg_match("/multipart\/form-data/i", $_SERVER['CONTENT_TYPE']) != 1)
        return;
    $_POST = array_reflex($_POST, 'urldecode');
}

function encodeWebInStripslashes()
{
    if (get_magic_quotes_gpc()) {
        $_GET = array_reflex($_GET, 'stripslashes');
        $_POST = array_reflex($_POST, 'stripslashes');
        $_COOKIE = array_reflex($_COOKIE, 'stripslashes');
        $_REQUEST = array_reflex($_REQUEST, 'stripslashes');
    }
}
