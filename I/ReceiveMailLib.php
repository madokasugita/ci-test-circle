<?php
define('LOG_ENTRYMAIL', DIR_LOG . 'entrymail'.date('Ym').'.clog');
define('LOG_ENTRYDATA', DIR_DATA . 'entrydata.cdat');

//ファイルロック書き込み
function writeEntry($header, $body)
{
    $enc = new MailToFileEncoder();
    $line = $enc->encode($header, $body);
    $fr = new FileReader();
    $fp = $fr->open(LOG_ENTRYDATA, 'a');
    fwrite($fp, $line."\n");
    $fr->close($fp);
}

class MailToFileEncoder
{
    /** headerのkeyとvalueの区切り */
    private $hkv = '<#:#>';

    /** headerの行区切り */
    private $hnl = '<#n#>';

    /** headerとbodyの区切り */
    private $bst = '<#body#>';

    /** bodyの行区切り */
    private $bnl = '<#n#>';

    public function encode($header, $body)
    {
        $line = '';
        $h = array();
        foreach ($header as $k => $v) {
            $h[] = $k.$this->hkv.$v;
        }

        $body = str_replace("\r\n", "\n", $body);
        $body = str_replace("\r", "\n", $body);
        $body = str_replace("\n", $this->bnl, $body);

        $line = implode($this->hnl, $h).$this->bst.$body;

        return $line;
    }

    public function decode($line)
    {
        //headerとbodyを分割
        $tmp = explode($this->bst, $line);
        $headers = array_shift($tmp);
        $header = array();
        foreach (explode($this->hnl, $headers) as  $v) {
            $t = explode($this->hkv, $v);
            $key = array_shift($t);
            $header[$key] = implode($this->hkv, $t);
        }

        //もしかしたら本文に区切り文字があるかもしれないので
        $body = implode($this->bst, $tmp);
        $body = str_replace($this->bnl, "\n", $body);

        return array(
            'header' => $header,
            'body' => $body
        );
    }
}
//ファイルロック書き込み
function readEntries()
{
    $fr = new FileReader();
    $fp = $fr->open(LOG_ENTRYDATA, 'a');
    rewind($fp);
    $res = array();
    $enc = new MailToFileEncoder();
    while (!feof($fp)) {
        $line = trim(fgets($fp));
        if ($line) {
            writeEntryMailLog ('read:'.$line);
            $res[] = $enc->decode($line);
        }
    }
    ftruncate($fp, 0);
    $fr->close($fp);

    return $res;
}

class FileReader
{
    private $lockfp=null;

    public function open($filename, $flag)
    {
        $lockfilename = DIR_DATA.'entrydatalock.dat';
        //＊＊ロック用ファイルのオープン＊＊
        $this->lockfp = fopen($lockfilename,'w');
        //＊＊ロック用ファイルのロック＊＊
        flock($this->lockfp, LOCK_EX);

        //ファイルのオープン
        $fp = fopen($filename, 'ab+');
        //バッファを0に指定（排他制御の保証）
        stream_set_write_buffer($fp, 0);
        //ファイルのロック
        flock($fp, LOCK_EX);

        return $fp;

    }

    public function close($fp)
    {
        //ロックの開放
        flock($fp, LOCK_UN);
        //ファイルのクローズ
        fclose($fp);

//		unlink(DIR_DATA.'filereader.lock');
        //＊＊ロック用ファイルのロックの開放＊＊
        flock($this->lockfp, LOCK_UN);
        //＊＊ロック用ファイルのクローズ＊＊
        fclose($this->lockfp);
        $this->lockfp = null;
    }
}

//lockファイルによるバッチの実行制御

/**
 * ファイルを使ったロックを行うクラス
 */
class FileLocker
{
    private $lockfile;
    public function __construct($lockfile)
    {
        $this->lockfile = $lockfile;
    }

    public function lock()
    {
        $lock = $this->getLockPath();
        if (is_file($lock)) {
            $this->onNgLock($lock);

            return false;
        } else {
            $this->onLock($lock);
        }

        return touch($lock);
    }

    public function unlock()
    {
        $lock = $this->getLockPath();
        unlink($lock);
        $this->onUnLock($lock);
    }

    /**
     * ロックファイルが置かれるフォルダを取得。
     * 設置フォルダを変える場合は継承してここを変更ください
     */
    protected function getLockFileFolder()
    {
        return DIR_DATA;
    }

    private function getLockPath()
    {
        return DIR_DATA.$this->lockfile.'.lock';
    }

    //-----------------------------------------
    //※以下はhEventHandlerとして切り出せば汎用的に使える
    //今のところ継承して使う

    public function onUnLock($lockfile)
    {

    }

    public function onLock($lockfile)
    {

    }

    public function onNgLock($lockfile)
    {

    }
}

class SendEntryMailFileLocker extends FileLocker
{
    public function onUnLock($lockfile)
    {
        $this->writeLog("CRON_END", "OK");
    }

    public function onLock($lockfile)
    {
        $this->writeLog("CRON_START", "OK");
    }

    public function writeLog()
    {
        $args = func_get_args();
        writeEntryMailLog ($args);
    }

    public function onNgLock($lockfile)
    {
        $lock = $lockfile;
        $this->writeLog("CRON_END", "NG_FileLock");
        $filetime = fileatime($lock);
        //MAIL_LOCK_REPORT_HOURS時間以上ロック掛かりっぱなし
        if (MAIL_LOCK_REPORT_HOURS != 0
            && time() - $filetime > MAIL_LOCK_REPORT_HOURS * 3600
            && $filetime != file_get_contents(DIR_DATA . 'cron_error.ctemp'))
        {
            mail_report($filetime,$lock);
        }
    }

    protected function getLockFileFolder()
    {
        return DIR_LOG;
    }

    private function getLockPath()
    {
        return DIR_LOG.$this->lockfile.'.lock';
    }
}

//バッチの実行ごとに生成される適当な数字
$global_batch_id = mt_rand();

function writeEntryMailLog($args)
{
    global $global_batch_id;
    $now = time();
    $log = array(
        '#'.$global_batch_id.'#',
        date("Ymd", $now),
        date("His", $now),
    );
    if(is_array($args))
        foreach ($args as $v) {
            $log[] = $v;
        } else
        $log[] = $args;
    error_log(implode("\t", $log). "\n", 3, LOG_ENTRYMAIL);
    if(DEBUG) echo 'LOG:'.implode("\t", $log). "\n".'<hr>';
}

/**
 * ヘッダから、アドレス部分のみを拾う
 * 誰々<cbase@cbase.co.jp>　のような記述からcbase@cbase.co.jpを抜き出す
 */
function getEmailFromHeader($prmData)
{
//
    preg_match("/<(.*?@.*?)>/",$prmData,$aryResult);
    $strResult = $aryResult[1];
    if (!$strResult) {
        $kor = explode(" ",$prmData);
        for ($ki=0;$ki<sizeof($kor);$ki++) {
            if (strstr($kor[$ki],"@")) {
                $kr = $kor[$ki];
                break;
            }
        }

        return $kr;
    } else return $strResult;
}
