<?php
/**
 * ファイルロック管理クラス
 * PHP4対応
 */
class CbaseFileLock
{
    public $extLockDir = ".lock";   //ロックディレクトリ拡張子
    public $extTmpFile = ".tmp";    //一時ファイル拡張子
    public $lockDir;	//ロックディレクトリ
    public $tmpFile;	//一時ファイル
    public $filename;	//作成ファイル
    public $fp;		//作成ファイルポインタ
    public $minutes; 	//ロック時間
    public $flgLock;	//ロック処理フラグ

    /**
     * コンストラクタ
     * @param string  $filename
     * @param integer $minutes(初期値は30)
     */
    public function CbaseFileLock($filename, $minutes=30)
    {
        $this->filename = $filename;
        $this->minutes = $minutes;
        $this->lockDir = $filename.$this->extLockDir;
        $this->tmpFile = $filename.$this->extTmpFile;
        $this->flgLock = false;
    }

    /**
     * ロック処理
     * (ロックディレクトリ作成前にロックディレクトリが存在し、かつ
     *  ロックディレクトリの作成時間が$minutes分（初期設定30分）を経過していれば
     *  ロックディレクトリ及び一時ファイルを削除)
     * @return boolean
     */
    public function setLock()
    {
        if (file_exists($this->lockDir) && (time()-filemtime($this->lockDir)) > $this->minutes*60) {
            @rmdir($this->lockDir);
            @unlink($this->tmpFile);
        }
        $this->flgLock = @mkdir($this->lockDir);

        return $this->flgLock;
    }

    /**
     * アンロック処理
     * （ロック処理フラグがtrueの時、ロックディレクトリ及び一時ファイルを削除）
     * @return boolean　ロックディレクトリが存在すればtrue、存在しなければfalse
     */
    public function setUnlock()
    {
        if(!$this->flgLock)

            return false;

        $this->flgLock = false;
        @rmdir($this->lockDir);
        @unlink($this->tmpFile);

        return !file_exists($this->lockDir);
    }

    /**
     * 一時ファイルをfopen(ロック処理も含む）
     * @return boolean 失敗したらアンロック処理を行ってfalse
     */
    public function fileOpen()
    {
        if(!$this->setLock())

            return false;

        $this->fp = @fopen($this->tmpFile, 'w');
        if ($this->fp === false) {
            $this->setUnlock();

            return false;
        }

        return true;
    }

    /**
     * 一時ファイルに$dataをfwrite（ロック処理者のみ可）
     * @param  string  $data
     * @return boolean 失敗したらアンロック処理を行ってfalse
     */
    public function filePut($data)
    {
        if(!$this->flgLock)

            return false;

        if (fwrite($this->fp, $data) === false) {
            $this->setUnlock();

            return false;
        }

        return true;
    }

    /**
     * 一時ファイルをfcloseしリネーム、アンロック処理（ロック処理者のみ可）
     * @return boolean 失敗したらアンロック処理を行ってfalse
     */
    public function fileClose()
    {
        if(!$this->flgLock)

            return false;

        if (!fclose($this->fp)) {
            $this->setUnlock();

            return false;
        }
        if (!@rename($this->tmpFile, $this->filename)) {
            $this->setUnlock();

            return false;
        }
        $this->setUnlock();

        return true;
    }

    /**
     * 一時ファイルに$dataをfile_put_contentsし、リネーム(ロック、アンロック処理を含む)
     * @param  string  $data
     * @return boolean 失敗したらアンロック処理を行ってfalse
     */
    public function filePutContents($data)
    {
        if(!$this->setLock())

            return false;
        if (file_put_contents($this->tmpFile, $data) === false) {
            $this->setUnlock();

            return false;
        }
        if (!@rename($this->tmpFile, $this->filename)) {
            $this->setUnlock();

            return false;
        }
        $this->setUnlock();

        return true;
    }
}
