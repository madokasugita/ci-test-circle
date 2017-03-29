<?php
require_once (DIR_ADMIN_CLASSES . 'EnqCopy' . DIRECTORY_SEPARATOR . 'EnqCopy.php');
require_once (DIR_ADMIN_CLASSES . 'EnqUpdateAll' . DIRECTORY_SEPARATOR . 'EnqUpdateAll.php');
class AllExport
{
    /**
     * エクスポートメイン処理
     */
    public function execute()
    {
        // 作業ディレクトリ作成
        $this->setTmpDir();
        // 評価シート管理[import/export]
        $this->enqCopy();
        // 評価シート管理[一括更新(詳細)]
        $this->enqUpdateAll();
        // Zipファイル生成
        $this->makeZip();
        // ダンロード処理実行
        $this->download($this->zipFileName, $this->outZipPath);
        // 作業ディレクトリ削除
        $this->rrmdir($this->tmpDir);
    }

    /**
     * 作業ディレクトリ作成
     */
    public function setTmpDir($dir = null)
    {
        $this->tmpDir = ($dir) ? $dir : DIR_TMP.DIR_ZIP.date('YmdHis').'export/';

        return $this;
    }

    /**
     * 評価シート管理[import/export]
     */
    public function enqCopy()
    {
        $EnqCopy = new \SmartReview\Admin\EnqCopy\EnqCopy();
        $evids   = array_keys(getSheetNames());
        foreach ($evids as $evid) {
            $enquete  = $EnqCopy->getEnqueteByEvid($evid);
            $filename = $EnqCopy->getHtmlExportFilename($evid);
            $this->writeFile($filename, serialize($enquete), 'EnqCopy');
        }

        return $this;
    }

    /**
     * 評価シート管理[一括更新(詳細)]
     */
    public function enqUpdateAll()
    {
        $EnqUpdateAll = new SmartReview\Admin\EnqUpdateAll\EnqUpdateAll();
        $data         = $EnqUpdateAll->getDownloadSubEvents();
        $csv          = $EnqUpdateAll->getCsvDownloadUtf8($data);
        $this->writeFile($csv['filename'], $csv['strFile'], 'EnqUpdateAll');

        return $this;
    }

    /**
     * ZIP作成
     */
    public function makeZip()
    {
        $this->zipFileName = date("Ymd")."_all_template.zip";
        $this->outZipPath  = $this->tmpDir.$this->zipFileName;
        $this->zipDir($this->tmpDir, $this->outZipPath);

        return $this;
    }

    /**
     * ダウンロード実施
     */
    public function download($zfilename, $zfilepath)
    {
        header('Pragma: private');
        header('Cache-Control: private');
        header('Content-Type: application/octet-stream; name=\"{$zfilename}\"');
        header('Content-Disposition: attachment; filename="'.$zfilename.'"');
        header('Content-Length: '.filesize($zfilepath));
        readfile($zfilepath);

        return $this;
    }

    /**
     * ファイル作成
     */
    public function writeFile($filename, $data, $subDir = '')
    {
        $path = $this->tmpDir.$subDir.'/';
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $sPath = $path.$filename;
        //ファイルを作成
        if (!touch($sPath)) {
            echo '・ファイル作成失敗。<br/>';
            exit;
        }
        //ファイルのパーティションの変更
        if (!chmod($sPath,0644)) {
            echo '・ファイルパーミッション変更失敗。<br/>';
            exit;
        }
        //ファイルをオープン
        if (!$filepoint = fopen($sPath,"w")) {
            echo '・ファイルオープン失敗。<br/>';
            exit;
        }
        //ファイルのロック
        if (!flock($filepoint, LOCK_EX)) {
            echo '・ファイルロック失敗。<br/>';
            exit;
        }
        //ファイルへ書き込み
        fwrite($filepoint, $data);
        if (!fclose($filepoint)) {
            echo '・ファイルクローズ失敗。<br/>';
            exit;
        }

        return $this;
    }

    /**
     * Zip a folder (include itself).
     * Usage:
     *   HZip::zipDir('/path/to/sourceDir', '/path/to/out.zip');
     *
     * @param string $sourcePath Path of directory to be zip.
     * @param string $outZipPath Path of output zip file.
     */
    private function zipDir($sourcePath, $outZipPath)
    {
        $sourcePath = preg_replace('/\\/$/i', '', $sourcePath);
        $pathInfo   = pathInfo($sourcePath);
        $parentPath = $pathInfo['dirname'];
        $dirName    = $pathInfo['basename'];

        $Zip = new ZipArchive();
        $Zip->open($outZipPath, \ZipArchive::CREATE);
        $Zip->addEmptyDir($dirName);
        self::folderToZip($sourcePath, $Zip, strlen("$parentPath/"));
        $Zip->close();
    }
    private static function folderToZip($folder, &$zipFile, $exclusiveLength)
    {
        $handle = opendir($folder);
        while (false !== $subFolder = readdir($handle)) {
            if ($subFolder != '.' && $subFolder != '..') {
                $filePath  = "$folder/$subFolder";
                $localPath = substr($filePath, $exclusiveLength);
                if (is_file($filePath)) {
                    if (preg_match("/MSIE/", $_SERVER['HTTP_USER_AGENT']) == 1 || preg_match("/Trident/", $_SERVER['HTTP_USER_AGENT']) == 1) {
                        $zipFile->addFile($filePath, $localPath);
                    } else {
                        $zipFile->addFile($filePath, mb_convert_encoding($localPath, "CP932"));
                    }
                } elseif (is_dir($filePath)) {
                    $zipFile->addEmptyDir($localPath);
                    self::folderToZip($filePath, $zipFile, $exclusiveLength);
                }
            }
        }
        closedir($handle);
    }

    /**
     * 再帰的なディレクトリ削除
     */
    public function rrmdir($dir)
    {
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dir);
    }
}
