<?php

function printTrace()
{
    foreach (debug_backtrace() as $debug) {
        if(basename($debug['file']) == basename(__FILE__))
            continue;

        print "(".basename($debug['file']).":".$debug['line'].") ";
    }
}

/**
 * Cbase共通関数
 */

/**
 * 変数の値が0か
 * @param mixed $var
 * @return	boolean
 */
function is_zero($var)
{
    return (is_numeric($var) && $var==0);
}

/**
 * 変数の値が無効か
 * @param mixed $var
 * @return	boolean
 */
function is_void($var)
{
    return (empty($var) && !is_zero($var));
}

/**
 * 変数の値が有効か
 * @param mixed $var
 * @return	boolean
 */
function is_good($var)
{
    return (!is_void($var));
}

/**
 * 変数の値がfalseか
 * @param mixed $var
 * @return	boolean
 */
function is_false($var)
{
    return ($var===false);
}

/**
 * 変数の値がtrueか
 * @param mixed $var
 * @return boolean
 */
function is_true($var)
{
    return ($var===true);
}

/**
 * 変数が半角数字か
 */
function is_123($var)
{
    return (preg_match("/^[0-9]*$/", $var)==1);
}

/**
 * 変数が半角英字か
 */
function is_abc($var)
{
    return (preg_match("/^[a-zA-Z]*$/", $var)==1);
}

/**
 * 変数が半角英数字か
 */
function is_abc123($var)
{
    return (preg_match("/^[a-zA-Z0-9]*$/", $var)==1);
}

/**
 * 変数が半角か
 */
function is_hankaku($var)
{
    return (preg_match("/^[ -~]*$/", $var)==1);
}

/**
 * 変数が全角か
 */
function is_zenkaku($var)
{
    return (preg_match("/^[^ -~]*$/", $var)==1);
}

/**
 * 変数がカタカナか
 */
function is_katakana($var)
{
    return (mb_ereg("^[ァ-ヶー　]*$", $var)==1);
}

/**
 * 変数がひらがなか
 */
function is_hiragana($var)
{
    return (mb_ereg("^[ぁ-んー　]*$", $var)==1);
}

/**
 * 変数がメールアドレスか
 */
function is_email($var, $dns=false)
{
    if (!(preg_match(PREG_EMAIL, $var, $matches)==1)) {
        return false;
    }
    if (is_true($dns)) {
        return true;
    }

    return (checkdnsrr($matches[1], 'MX') || checkdnsrr($matches[1], 'A') || checkdnsrr($matches[1], 'CNAME'));
}

/**
 * 文字列を囲む
 * @param string $string
 * @param string $enclosure
 * @return string
 */
function enclose($string, $enclosure)
{
    return $enclosure.$string.$enclosure;
}

/**
 * 配列に関数を再帰的に適用
 * （指定された関数の戻り値が配列に代入される）
 * @param array $array
 * @param string $funcname
 * @return array
 */
function array_reflex($array, $funcname, $first=true)
{
    if ($first) {
        if (!is_callable($funcname)) {
            return false;
        }
    }

    if (is_array($array)) {
        foreach ($array as $key => $value) {
            $array[$key] = call_user_func(__FUNCTION__, $value, $funcname, false);
        }
    } else {
        $array = call_user_func($funcname, $array);
    }

    return $array;
}

/**
 * HTMLエスケープ適用
 * @param string $string
 * @return string
 */
function html_escape($string)
{
    $string = htmlentities($string, ENT_QUOTES, INTERNAL_CHARSET);

    return preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", $string);
}

/**
 * HTMLエスケープデコード
 * @param string $string
 * @return string
 */
function html_unescape($string)
{
    return html_entity_decode($string, ENT_QUOTES, INTERNAL_CHARSET);
}

/**
 * HTMLエスケープ適用
 * @param mixed $value
 * @return mixed
 */
function escapeHtml($value)
{
    return array_reflex($value, 'html_escape');
}

/**
 * HTMLエスケープデコード
 * @param mixed $value
 * @return mixed
 */
function unescapeHtml($value)
{
    return array_reflex($value, 'html_unescape');
}

/**
 * JavaScriptエスケープ適用
 * @param string $string
 * @return string
 */
function js_escape($string)
{
    return str_replace("&", "&amp;", html_escape($string));
}

/**
 * JavaScriptエスケープ適用
 * @param mixed $value
 * @return mixed
 */
function escapeJs($value)
{
    return array_reflex($value, 'js_escape');
}

/**
 * SQLエスケープ適用
 * @param string $string
 * @return string
 */
function sql_escape($string, $quote=true)
{
    if (is_null($string)) {
        return 'NULL';
    } elseif (is_bool($string)) {
        return ($string)? 'TRUE':'FALSE';
    } elseif (is_int($string) || is_float($string)) {
        return $string;
    }

    //AWS対応
    global $con;
    if (DB_STRICT_MODE==1) {
        mb_substitute_character("long");
        //AWS対応
        $tmp = $con->escape($string);
        if (ereg("BAD\\+[0-9A-F]+", $tmp)) {
            echo DB_ENCODE."の範囲外の文字が含まれています。(1)";
            exit;
        }
    }
    //AWS対応
    $string = $con->escape(str_replace(chr(0x00), "", $string));

    return ($quote)? enclose($string, "'"):$string;
}

/**
 * SQLエスケープ適用（クォートしない）
 * @param string $string
 * @return string
 */
function sql_escape_noquote($string)
{
    return sql_escape($string, false);
}

/**
 * SQLエスケープ適用
 * @param mixed $value
 * @param boolean $quote
 * @return mixed
 */
function escapeSql($value, $quote=true)
{
    return array_reflex($value, ($quote)? 'sql_escape':'sql_escape_noquote');
}

/**
 * 英字カナ変換
 * @param string $string
 * @return string;
 */
function abc2kana($string)
{
    global $_CbaseABC;
    if (is_null($_CbaseABC)) {
        require_once(DIR_LIB.'CbaseABC.php');
        $_CbaseABC =& new CbaseABC();
    }

    return $_CbaseABC->getKana($string);
}

/**
 * ffgetcsv
 * @param resource $handle
 * @param int $length
 * @param string $delimiter
 * @param string $enclosure
 * @param string $file_encoding
 * @return array
 */
function ffgetcsv($handle, $length=0, $delimiter=",", $enclosure="\"", $file_encoding="SJIS-win")
{
    $_CbaseCSV = _getCbaseCSV($file_encoding);

    return $_CbaseCSV->getCsvData($handle, $length, $delimiter, $enclosure);
}

/**
 * ffputcsv
 * @param resource $handle
 * @param array $fields
 * @param string $delimiter
 * @param string $enclosure
 * @param string $file_encoding
 * @return int
 */
function ffputcsv($handle, $fields, $delimiter=",", $enclosure="\"", $file_encoding="SJIS-win")
{
    $_CbaseCSV = _getCbaseCSV($file_encoding);

    return $_CbaseCSV->putCsvData($handle, $fields, $delimiter, $enclosure);
}

function _getCbaseCSV($file_encoding)
{
    global $_CbaseCSV;
    if (is_null($_CbaseCSV)) {
        require_once(DIR_LIB.'CbaseCSV.php');
        $_CbaseCSV =& new CbaseCSV($file_encoding);
    } elseif ($_CbaseCSV->getFileEncoding()!=$file_encoding) {
        $_CbaseCSV->setFileEncoding($file_encoding);
    }

    return $_CbaseCSV;
}

function getMailObject()
{
    global $mail_object;
    if (is_null($mail_object)) {
        $params = array();
        $params['host'] = SMTP_HOST;
        $params['port'] = SMTP_PORT;
        $params['auth'] = SMTP_AUTH;
        $params['username'] = SMTP_USERNAME;
        $params['password'] = SMTP_PASSWORD;
        $params['persist'] = SMTP_PERSIST;
        $params['timeout'] = SMTP_TIMEOUT;
        $mail_object = Mail::factory('smtp', $params);
    }

    return $mail_object;
}

/**
 * ログ保存
 * @param string $__FILE__
 * @param string $__LINE__
 * @param string $logMsg
 * @param string $logFile
 * @return boolean
 */
function save_log($__FILE__, $__FUNCTION__, $__LINE__, $logMsg, $logFile)
{
    $aryLog = array();
    $aryLog[] = date("Y-m-d H:i:s");
    $aryLog[] = $__FILE__;
    $aryLog[] = $__FUNCTION__;
    $aryLog[] = $__LINE__;
    $aryLog[] = $logMsg;

    return error_log(implode("\t", $aryLog)."\n", 3, $logFile);
}

/**
 * ダウンロード
 * @param string $file
 * @param string $filename
 * @param string $contentType
 */
function download($file, $filename, $contentType="application/octet-stream")
{
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-Type: ".$contentType);
    header("Content-Disposition: attachment; filename=".encodeDownloadFilename($filename));
    header("Content-Length: ".strlen($file));
    echo $file;
    exit;
}

/**
 * ダウンロードファイル
 * @param string $file
 * @param string $filename
 * @param string $contentType
 */
function downloadFile($file, $filename, $contentType="application/octet-stream")
{
    if(!file_exists($file) || is_void($filename))

        return false;
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-Type: ".$contentType);
    header("Content-Disposition: attachment; filename=".encodeDownloadFilename($filename));
    header("Content-Length: ".filesize($file));
    readFile($file);
    exit;
}

/**
 * PHP_SELF取得
 * @return string
 */
function getPHP_SELF()
{
    return html_escape(basename($_SERVER['SCRIPT_NAME']));
}

/**
 * PHP_SELF取得
 * @return string
 */
function getPHP_SELFwithSID()
{
    return html_escape(basename($_SERVER['SCRIPT_NAME'])).'?'.SID;
}
/**
 * SID取得
 * @return string
 */
function getSID()
{
    if($GLOBALS['Setting']->sessionModeCookie())

        return "";
    return html_escape(SESSIONID."=".session_id());
}

/**
 * SID取得(hidden)
 * @return html
 */
function getHiddenSID()
{
    if($GLOBALS['Setting']->sessionModeCookie())

        return "";
    $name = html_escape(SESSIONID);
    $value = html_escape(session_id());

    return <<<__HTML__
<input type="hidden" name="{$name}" value="{$value}">
__HTML__;
}

/**
 * メッセージdiv取得
 * @return html
 */
function getNormalMsgDiv($msg)
{
    return <<<__HTML__
<div style="color:#0000ff;">{$msg}</div>
__HTML__;
}

/**
 * エラーメッセージdiv取得
 * @return html
 */
function getErrorMsgDiv($msg)
{
    return <<<__HTML__
<div style="color:#ff0000;">{$msg}</div>
__HTML__;
}

/**
 * IEか
 * @return boolean
 */
function isIE()
{
    return (preg_match("/ MSIE /", $_SERVER['HTTP_USER_AGENT']) == 1 || preg_match("/Trident/", $_SERVER['HTTP_USER_AGENT']) == 1);
}

function setSslOnly()
{
    if (SSL_ON==1) {
        //AWS対応
        if((is_null($_SERVER['HTTPS']) || $_SERVER['HTTPS']=='off')
            && (is_null($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO']=="http"))
        {
            header("Location: https://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");
            exit;
        }
    }
}

/**
 * 有料マーク取得
 * @return html
 */
function getMoneyMark()
{
    if(MONEY_MARK_ON!=1) return "";
    $DIR_IMG = DIR_IMG;

    return <<<__HTML__
<span style="color:#ff0000;"><img src="{$DIR_IMG}research_charge.gif" alt="[有料]"></span>
__HTML__;
}

function getIpAddr()
{
    return array_shift(explode(",", (!is_null($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']));
}
