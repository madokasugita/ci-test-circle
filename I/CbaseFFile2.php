<?php
/**
 * ファイル関連関数集
 */

//crm_defineなどで define('DIR_TMP',DIR_ROOT.'temp/');を指定しておく必要があります。

require_once 'cbase/CbaseClustering.php';
$GLOBAL_CC=new CbaseClustering(array());

/**
 * データをファイルに書き出す(必要があればクラスタリング間同期も行なう)
 * @param text $file ファイル名(相対パスor絶対パス)
 * @param text $data 書き出す内容
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 */
function s_write($file,$data, $async=false)
{
    $fp = fopen($file, "w");
    if(is_false($fp))

        return false;
    if(is_false(fwrite($fp, $data)))

        return false;
    if(is_false(fclose($fp)))

        return false;
    return syncCopy($file,$async);
}

/**
 * ファイルに削除する(必要があればクラスタリング間同期も行なう)
 * @param text $file ファイル名(相対パスor絶対パス)
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 */
function s_unlink($file, $async=false)
{
    @unlink($file);
    if(file_exists($file))

        return false;
    return syncDelete($file,$async);
}

/**
 * ファイルを更新時にクラスタリング間同期を行なう
 * @param text $file ファイル名(相対パスor絶対パス)
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 */
function syncCopy($file, $async=false)
{
    global $GLOBAL_CC;
    $filepath = realpath($file);
    $file_dir = dirname($filepath)."/";
    $file_name = basename($filepath);

    return $GLOBAL_CC->doExec($file_dir,"copyFile", $file_name, $async);
}

/**
 * ファイルを削除時にクラスタリング間同期を行なう
 * @param text $file ファイル名(相対パスor絶対パス)
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 */
function syncDelete($file, $async=false)
{
    global $GLOBAL_CC;
    $filepath = my_realpath($file);
    $file_dir = dirname($filepath) . "/";
    $file_name = basename($filepath);

    return $GLOBAL_CC->doExec($file_dir, "deleteFile", $file_name, $async);
}

/**
 * 一時ファイルを作成する
 * @param text $data ファイルの内容
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 * @return text 一時ファイルのID
 */
function temp_write($data, $async=false)
{
    //一時間以上前にあるやつは消しとく
    if(!is_false(strpos($_SERVER['SCRIPT_NAME'], DIR_MNG)) && rand(0, 10)==5)
        temp_gc();
    //重複しないファイル名生成
    do {
        $tmp_id = md5(microtime());
        $filename = temp_file_path($tmp_id);
    } while (file_exists($filename));

    if(is_false(s_write($filename,$data,$async)))

        return false;
    return $tmp_id;
}

/**
 * 一時ファイル削除する
 * @param text 一時ファイルのID
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 */
function temp_delete($tmp_id, $async=false)
{
    $filename = temp_file_path($tmp_id);

    return s_unlink($filename, $async);
}

/**
 * 一時ファイルの内容を読み込む
 * @param text $tmp_id 一時ファイルのID
 * @param bool $unlink_flag 読み込んだ後にファイルを削除するかどうか
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 * @return text ファイルの内容
 */
function temp_read($tmp_id, $unlink_flag = false, $async=false)
{
    $filename = temp_file_path($tmp_id);
    $data = file_get_contents($filename);
    if($unlink_flag)
        s_unlink($filename, $async);

    return $data;
}

/**
 * 一時ファイルのファイルを開いてファイルパスを返す
 * @param text $tmp_id 一時ファイルのID
 * @param string $mode オープン方法
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 * @return resource ファイルパス
 */
function temp_open($tmp_id, $mode)
{
    $filename = temp_file_path($tmp_id);

    return fopen($filename, $mode);
}

function new_temp_filename($uniquekey = "")
{
    //重複しないファイル名生成
    do {
        $tmp_id = ereg_replace('[\\/:]','_',DIR_SYS_ROOT).md5(microtime()).ereg_replace('[\\/:]','_',$uniquekey);
        $filename = temp_file_path($tmp_id);
    } while (file_exists($filename));

    return $filename;
}

/**
 * ファイル名を変更して一時ファイル化する。
 * @param string $file_path
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 * @return string 一時ファイルのID
 */
function temp_rename($file_path, $async=false)
{
    if(!is_false(strpos($_SERVER['SCRIPT_NAME'], DIR_MNG)) && rand(0, 10)==5)
        temp_gc();
    //重複しないファイル名生成
    do {
        $tmp_id = ereg_replace('[\\/:]','_',DIR_SYS_ROOT).md5(microtime());
        $filename = temp_file_path($tmp_id);
    } while (file_exists($filename));

    if(is_false(rename($file_path, $filename)))

        return false;
    if(is_false(syncCopy($filename, $async)))

        return false;
    return $tmp_id;
}

/**
 * 一時ファイルIDを受け取ってそのファイルを削除する
 * @param string $tmp_id 一時ファイルのID
 * @param bool 非同期フラグ trueの場合は処理完了を待たない
 */
function temp_unlink($tmp_id, $async=false)
{
    return s_unlink(temp_file_path($tmp_id), $async);
}

/**
 * IDをもとにパスを返す
 * @param string $tmp_id 一時ファイルのID
 * @return string 一時ファイルのパス
 */
function temp_file_path($tmp_id)
{
    return DIR_TMP . $tmp_id.".tmp";
}

/**
 * 最終アクセス時間から1時間以上たったN時ファイルを削除する
 */
function temp_gc()
{
    return true;//2008/02/26 /tmp/を使うようにしたので、GCなし
    foreach (glob(DIR_TMP."*") as $file) {
        if (basename($file) == ".htaccess") {
            continue;
        }
        if (time()-fileatime($file)>3600 * 2) {
            s_unlink($file);
        }
    }
    if(DEBUG)
        print "GCしました<hr>";

}

/**
 * 相対パスから絶対パスを得る
 */
function my_realpath($path)
{
    if (!is_zero(strpos($path, "/"))) {
        $base = dirname($_SERVER['SCRIPT_FILENAME']);
        $path = $base . "/" . $path;
    }
    $path = explode("/", $path);
    $newpath = array ();
    for ($i = 0; $i < count($path); $i++) {
        if(is_void($path[$i]) || $path[$i] === ".")
            continue;
        if ($path[$i] === "..") {
            array_pop($newpath);
            continue;
        }
        array_push($newpath, $path[$i]);
    }

    return "/" . implode("/", $newpath);
}

/**
 * ファイルポインタから行を取得し、CSVフィールドを処理する
 * @param resource handle
 * @param int length
 * @param string delimiter
 * @param string enclosure
 * @return ファイルの終端に達した場合を含み、エラー時にFALSEを返します。
 */
function fgetcsv_reg(& $handle, $length = null, $d = ',', $e = '"')
{
    $d = preg_quote($d);
    $e = preg_quote($e);
    $_line = "";
    $eof = false;
    while (!$eof && !feof($handle)) {
        $_line .= (empty ($length) ? fgets($handle) : fgets($handle, $length));
        $itemcnt = preg_match_all('/' . $e . '/', $_line, $dummy);
        if ($itemcnt % 2 == 0)
            $eof = true;
    }
    $_csv_line = preg_replace('/(?:\r\n|[\r\n])?$/', $d, trim($_line));
    $_csv_pattern = '/(' . $e . '[^' . $e . ']*(?:' . $e . $e . '[^' . $e . ']*)*' . $e . '|[^' . $d . ']*)' . $d . '/';
    preg_match_all($_csv_pattern, $_csv_line, $_csv_matches);
    $_csv_data = $_csv_matches[1];
    for ($_csv_i = 0; $_csv_i < count($_csv_data); $_csv_i++) {
        $_csv_data[$_csv_i] = preg_replace('/^' . $e . '(.*)' . $e . '$/s', '$1', $_csv_data[$_csv_i]);
        $_csv_data[$_csv_i] = str_replace($e . $e, $e, $_csv_data[$_csv_i]);
    }

    return empty ($_line) ? false : $_csv_data;
}

/**
 * ファイルを指定した文字コードに変換する
 */
function convertFile($file, $to_code = 'EUC-JP')
{
    if (eregi('euc', $to_code))
        $to_code = 'EUC-JP';
    if (eregi('sjis', $to_code))
        $to_code = 'SJIS';
    $fp = @ fopen($file, "r");
    for ($i = 0, $str = ""; !feof($fp) && $i <= 10; $i++) {
        $str .= fgets($fp, 1000);
    }
    fclose($fp);
    $from_code = mb_detect_encoding($str, "ASCII,JIS,UTF-8,EUC-JP,SJIS", true);
    if($from_code === false)
        $from_code = 'SJIS';
    if ($to_code == $from_code)
        return;
    if ($to_code == 'EUC-JP')
        $to_code = 'eucJP-win';
    if ($from_code == 'EUC-JP')
        $from_code = 'eucJP-win';
    if ($to_code == 'SJIS')
        $to_code = 'SJIS-win';
    if ($from_code == 'SJIS')
        $from_code = 'SJIS-win';
    file_put_contents($file, mb_convert_encoding(file_get_contents($file), $to_code, $from_code));
}
