<?php

/**
 * 共通ファンクション (プロジェクトを超えて利用できるもののみ)
 * @author Cbase
 * @package Cbase
 */

/******************************************************************************************************/

require_once 'CbaseFGeneral.php';
if (CRON_MAIL !== 1)
    require_once (DIR_LIB . 'CbaseFSendmail.php');

function operationLog($filename, $option = "")
{
    $log = array ();
    $log[] = date('Y-m-d H:i:s');
    $log[] = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
    $log[] = $_SESSION['muid'];
    $log[] = $option;
    error_log(implode("\t", $log) . "\n", 3, $filename);
}

/**
 * POST,GETからモードを判別
 * @return mode
 */
function getMode()
{
    foreach ($_POST as $key => $value) {
        $key = str_replace('_x', '', $key);
        $key = str_replace('_y', '', $key);
        if (ereg("^mode:(.*)$", $key, $match)) {
            return $match[1];
        }
    }
    if ($_POST['mode'])
        return $_POST['mode'];
    if ($_GET['mode'])
        return $_GET['mode'];
}

/**
 * 指定したURLへリダイレクトする
 * @param string $url
 */
function location($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * 文字列をN文字ずつ分割して配列にする
 *
 * @param string $string 対象文字列
 * @param int $n 何文字ずつ区切るか (1)
 * @param string $encoding 文字列の文字コード(内部コード)
 *
 * @return array N文字ずつ格納された配列
 */
function stringToArray($string, $n = 1, $encoding = null)
{
    if (is_null($encoding))
        $encoding = mb_internal_encoding();
    $array = array ();
    for ($i = 0; $i < mb_strlen($string, $encoding); $i += $n) {
        $array[] = mb_substr($string, $i, $n, $encoding);
    }

    return $array;
}

/**
 * 文字列を縦書きにする
 *
 * @param string $string 対象文字列
 * @param string $delimiter 区切り文字 (省略時<br />)
 * @param string $encoding 文字列の文字コード(省略時内部コード)
 *
 * @return string 縦書きになった文字列
 */
function stringToVirtical($string, $delimiter = "<br />", $encoding = null)
{
    return implode($delimiter, stringToArray($string, 1, $encoding));
}

/**
 * ランダムな文字列を返す
 *
 * @param int $length 文字列の長さ
 * @param array $nglist 使用しない文字を配列で
 * @param mixed $seed 種(同じ$seedを与えると同じ値を返す)
 *
 * @return string ランダムな文字列
 */
function getRandomString($length = 8, $nglist = array (), $seed = null)
{
    if ($seed) {
        srand(hexdec(substr(md5($seed), 0, 11)));
    }
    $chars = range('a', 'z');
    $chars = array_merge($chars, range('0', '9'));
    $chars = array_merge($chars, range('A', 'Z'));
    $chars = array_diff($chars, $nglist); //使用しない文字を取り除く
    $string = "";
    for ($i = 0; $i < $length; $i++)
        $string .= $chars[array_rand($chars)];

    return $string;
}

/**
 * ランダムなパスワードを返す
 *
 * @param int $length パスワードの長さ
 * @param array $nglist 使用しない文字を配列で
 * @param mixed $seed 種(同じ$seedを与えると同じ値を返す)
 *
 * @return string ランダムなパスワード
 */
function getRandomPassword($length = DEFAULT_PW_LENGTH, $nglist = array (), $seed = null)
{
    $nglist = array_merge($nglist, array (
        '0',
        '1',
        'o',
        'O',
        'l',
        'i',
        'j'
    )); //紛らわしい文字は使わない

    return getRandomString($length, $nglist, $seed);
}

/**
 * SYSTEM_RANDOM_STRINGと対象の文字列を結合したものを種にしてランダムな文字列を返す
 * @param string $str 元となる文字列
 * @param int $length ハッシュ値の長さ
 * @return string ハッシュ値
 */
function getHash($str, $length = 4)
{
    return getRandomString($length, array (), $str . SYSTEM_RANDOM_STRING);
}

/**
 * マルチバイト対応str_replace
 *
 * @param string $haystack 対象文字列
 * @param string $search 検索文字列
 * @param string $replace 置換文字列
 * @param int $offset 開始位置
 * @param string $encoding エンコード
 *
 * @return string 置換後文字列
 */
function mb_str_replace($haystack, $search, $replace, $offset = 0, $encoding = 'auto')
{
    $len_sch = mb_strlen($search, $encoding);
    $len_rep = mb_strlen($replace, $encoding);
    while (($offset = mb_strpos($haystack, $search, $offset, $encoding)) !== false) {
        $haystack = mb_substr($haystack, 0, $offset, $encoding) .
        $replace .
        mb_substr($haystack, $offset + $len_sch, 1000, $encoding);
        $offset = $offset + $len_rep;
        if ($offset > mb_strlen($haystack, $encoding))
            break;
    }

    return $haystack;
}

/**
 * URL埋め込み用 base64_encode
 * @param string $str 対象文字列
 * @return string base64でエンコードされた文字列
 */
function url_base64_encode($str)
{
    return trim(str_replace(array (
        '+',
        '/'
    ), array (
        '-',
        '_'
    ), base64_encode($str)), '=');
}

/**
 * URL埋め込み用 base64_decode
 * @param string $str base64でエンコードされた文字列
 * @param stirng デコードされた文字列
 */
function url_base64_decode($str)
{
    return base64_decode(str_replace(array (
        '-',
        '_'
    ), array (
        '+',
        '/'
    ), $str));
}

/**
 * where句用の条件を配列で作成
 * @param array $col カラム
 * @param array $com 比較演算子
 * @param array $val 値
 * @return array 条件用配列
 *
 *
 * 以下使用例:
 * 引数が以下の場合
 * $col = array('id','pass','flag');
 * $com = array('LIKE','=','!=');
 * $val = array('00','123','1');
 *
 * 以下のような結果がかえる 　(   implode(' and ',$return);とするとwhereとして使える)
 * $retrun = array("id LIKE '%00%'","pass = '123'","flag != '1'");
 */
function getAryConditions($col, $com, $val)
{
    $aryComparisons = array (
        '=',
        '!=',
        '<',
        '>',
        '<=',
        '>=',
        'LIKE',
        'NOT LIKE'
    );
    $conds = array ();
    foreach ($col as $key => $cond) {
        $compresion = $com[$key];
        $value = $val[$key];
        if (ereg('LIKE', $compresion))
            $p = '%';
        else
            $p = '';
        if (in_array($compresion, $aryComparisons) && $cond)
            $conds[] = FDB :: colescape($cond) . ' ' . $compresion . ' ' . FDB :: escape($p . $value . $p);
    }

    return $conds;
}

/**
 * CSVをダウンロードさせる。
 */
function csv_download($prmAry, $filename = '')
{
    if (!$filename)
        $filename = date('Y-m-d') . '.csv';
    $strFile = "";
    foreach ($prmAry as $strRow) {
        $strFile .= implode(",", csv_quoteArray($strRow)) . "\r\n";
    }
    $strFile = strip_tags(replaceMessage($strFile));
    $strFile = mb_convert_encoding($strFile, "SJIS-win",INTERNAL_ENCODE);
    $filename = encodeDownloadFilename(replaceMessage($filename));
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Content-length: " . strlen($strFile));
    print $strFile;
    exit;
}

/**
 * CSVをダウンロードさせる。
 */
function csv_download_utf8($prmAry, $filename = '',$convert = true)
{
    if (!$filename)
        $filename = date('Y-m-d') . '.csv';
    $strFile = "";
    foreach ($prmAry as $strRow) {
        $strFile .= implode(OUTPUT_CSV_DELIMITER, csv_quoteArray($strRow)) . "\r\n";
    }
    if($convert)
        $strFile = strip_tags(replaceMessage($strFile));
    $strFile = mb_convert_encoding($strFile,OUTPUT_CSV_ENCODE,INTERNAL_ENCODE);
    global $Setting;
    if($Setting->csvEncodeUtf16le())//BOMを追加
        $strFile = chr(255) . chr(254).$strFile;
    if($Setting->csvEncodeUtf8())//BOMを追加
        $strFile = chr(0xEF).chr(0xBB).chr(0xBF).$strFile;

    $filename = encodeDownloadFilename(replaceMessage($filename));
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Content-length: " . strlen($strFile));
    print $strFile;
    exit;
}
/**
 * CSVをダウンロードさせる。
 */
function csv_download_utf8_tag($prmAry, $filename = '')
{
    if (!$filename)
        $filename = date('Y-m-d') . '.csv';
    $strFile = "";
    foreach ($prmAry as $strRow) {
        $strFile .= implode("\t", csv_quoteArray($strRow)) . "\r\n";
    }
    $strFile = chr(255) . chr(254).mb_convert_encoding($strFile, "UTF-16LE",INTERNAL_ENCODE);
    $filename = encodeDownloadFilename(replaceMessage($filename));
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Content-length: " . strlen($strFile));
    print $strFile;
    exit;
}
/**
 *
 * BOMを削除する
 * @param $str 文字列
 */
function delete_bom($str)
{
    if ($str[0] == chr(0xEF) && $str[1] == chr(0xBB) && $str[2] == chr(0xBF))
        $str = substr($str, 3);
    elseif($str[0] == chr(255) && $str[1] == chr(254))
        $str = substr($str, 2);

    return $str;
}
/**
 * CSV用配列エスケープ
 */
function csv_quoteArray($prmAry)
{
    foreach ($prmAry as $key => $val) {
        $prmAry[$key] = csv_quote($val);
    }

    return $prmAry;
}
/**
 * CSV用文字列エスケープ
 */
function csv_quote($prmStr)
{
    if (is_int($prmStr) || is_double($prmStr)) {
        return $prmStr;
    } elseif (is_bool($prmStr)) {
        return $prmStr ? 'TRUE' : 'FALSE';
    } elseif (is_null($prmStr)) {
        return '';
    }
    $prmStr = str_replace('"', '""', $prmStr);

    return '"' . $prmStr . '"';
}

/**
 * 配列からcsv形式へ変換
 * @param   $list    配列
 * @param $sep    区切り文字（省略時はカンマ）
 * @return 変換済み文字列
 */
function array2csv($list, $sep = ',')
{
    if (!is_array($list))
        return false;
    foreach ($list as $line) {
        if (!is_array($line))
            return false;
        $tmpl = array ();
        foreach ($line as $clm) {

            $tmp = str_replace('"', '""', $clm); ///    引用符は２つ連続
            $tmpl[] = '"' . $tmp . '"'; /// 引用符で挟む

        }
        $str .= implode($sep, $tmpl) . "\n";
    }

    return $str;
}

if (!function_exists('array_combine')) {
    /**
    * 一方の配列をキーとして、もう一方の配列を値として、ひとつの配列を生成する (PHP5用関数)
    *
    * @param array $keys キーとなる配列
    * @param array $values 値となる配列
    *
    * @return array キーと値を統合した配列
    */
    function array_combine($keys, $values)
    {
        $keys = array_values($keys);
        $values = array_values($values);

        $n = count($keys);
        $m = count($values);
        if (!$n || !$m || ($n != $m)) {
            return false;
        }

        $combined = array ();
        for ($i = 0; $i < $n; $i++) {
            $combined[$keys[$i]] = $values[$i];
        }

        return $combined;
    }
}

/**
 * CSVエスケープ適用
 * @param mixed $value
 * @return mixed
 */
function escapeCsv($value)
{
    return array_reflex($value, 'csv_escape');
}
/**
 * ガーベジコレクション
 */
function garbage_collection($tmpFilePrefix)
{
    foreach (glob(DIR_TMP . $tmpFilePrefix . "*") as $tmpFile) {
        if (time() - fileatime($tmpFile) > 60 * 60) { // 1時間経過していたら
            s_unlink($tmpFile);
        }
    }
}

/* 全角スペース、タブも含めてトリムする */
function atrim($str)
{
    $str = mb_ereg_replace("^[[:space:]]+", "", $str);
    $str = mb_ereg_replace("[[:space:]]+$", "", $str);

    return $str;
}
