<?php
require_once (DIR_ADMIN_CLASSES . 'EnqCopy' . DIRECTORY_SEPARATOR . 'EnqCopy.php');
require_once (DIR_ADMIN_CLASSES . 'EnqUpdateAll' . DIRECTORY_SEPARATOR . 'EnqUpdateAll.php');
require_once (DIR_ADMIN_CLASSES . 'EnqUpdateAll' . DIRECTORY_SEPARATOR . 'ThisImportModel.php');
require_once (DIR_ADMIN_CLASSES . 'EnqUpdateAll' . DIRECTORY_SEPARATOR . 'ThisImportDesign.php');
class AllImport
{
    /**
     * インポートメイン処理
     */
    public function execute()
    {
        // 作業ディレクトリ作成
        $this->setTmpDir();
        // 解凍
        $this->extractZip();
        // 対象のファイルリストをセット
        $this->setFiles();
        // 評価シート管理[import/export]
        $this->enqCopy();
        // 評価シート管理[一括更新(詳細)]
        $this->enqUpdateAll();
        // 作業ディレクトリ削除
        $messages = $this->rrmdir($this->tmpDir);

        if (is_good($this->errors)) {
            return implode('<br/>', $this->errors);
        }

        return '完了';
    }

    /**
     * 作業ディレクトリ作成
     */
    public function setTmpDir($dir = null)
    {
        $this->tmpDir = ($dir) ? $dir : DIR_TMP.DIR_ZIP.date('YmdHis').'import/';

        return $this;
    }

    /**
     * 評価シート管理[import/export]
     */
    public function enqCopy()
    {
        $EnqCopy = new SmartReview\Admin\EnqCopy\EnqCopy();
        if(is_void($this->extractedTargets['EnqCopy']))

            return $this;

        foreach ($this->extractedTargets['EnqCopy'] as $file) {
            $pattern = '/([0-9]+)\./';
            if (preg_match($pattern, $file, $matches) && is_good($matches[1])) {
                $evid = $matches[1];
                $data = unserialize(file_get_contents($file));
                $EnqCopy->importExecTrigger($data, $evid);
            }
        }

        return $this;
    }

    /**
     * 評価シート管理[一括更新(詳細)]
     */
    public function enqUpdateAll()
    {
        $EnqUpdateAll = new SmartReview\Admin\EnqUpdateAll\EnqUpdateAll();
        if(is_void($this->extractedTargets['EnqUpdateAll']))

            return $this;

        foreach ($this->extractedTargets['EnqUpdateAll'] as $file) {
            $EnqUpdateAll->importConfirm($file);
            if ($EnqUpdateAll->Importer->showError) {
                echo "評価シート管理[一括更新(詳細)] のインポート初理に失敗しました<br>";

                return;
            }
            $EnqUpdateAll->import($file);
        }

        return $this;
    }

    /**
     * 解凍
     */
    public function extractZip()
    {
        if (is_false($_FILES['file']['tmp_name'])) {
            echo "ファイルを選択してください。";
            exit;
        }
        if (!file_exists($this->tmpDir)) {
            mkdir($this->tmpDir, 0777, true);
        }
        $Zip = new ZipArchive();
        if (true === $Zip->open($_FILES['file']['tmp_name'])) {
            $Zip->extractTo($this->tmpDir);
            $Zip->close();
        }

        return $this;
    }

    /**
     * 対象のファイルリストをセット
     */
    public function setFiles()
    {
        foreach (glob($this->tmpDir . "*") as $dir) {
            $this->extractedDir = $dir.DIRECTORY_SEPARATOR;
        }
        if (is_void($this->extractedDir)) {
            echo "解凍に失敗しました。";
            exit;
        }
        $this->extractedTargets = array();
        foreach (glob($this->extractedDir . "*") as $dir) {
            if (is_dir($dir)) {
                foreach (glob($dir . DIRECTORY_SEPARATOR . "*") as $file) {
                    $dir = explode(DIRECTORY_SEPARATOR, $dir);
                    $dir = end($dir);
                    $this->extractedTargets[$dir][] = $file;
                }
            }
        }

        return $this;
    }

    /**
     * 再帰的なディレクトリ削除
     */
    public function rrmdir($dir)
    {
        if (!$this->errors) {
            $this->errors = array();
        }
        foreach (glob($dir . '/*') as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                if (!unlink($file)) {
                    $this->errors[] = 'ファイル削除失敗：' . $file;
                }
            }
        }
        if (!rmdir($dir)) {
            $this->errors[] = 'ディレクトリ削除失敗' . $dir;
        }
    }
}
