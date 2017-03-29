<?php
require_once('SecularApp.php');

class SecularCreate extends SecularApp
{

    /**
     * 経年比較用データ構築、メイン処理
     */
    public function execute()
    {
        if (DB_TYPE !== 'mysql') {
            $this->errorLog('SecularCreate::execute', 'DB_TYPEが[mysql]以外が指定されています');
        }

        $this->proxyExecution('prepare')
            ->proxyExecution('makeSecularTables')
            ->proxyExecution('makeDump')
            ->proxyExecution('deleteSecularTables')
            ->proxyExecution('updateUsesStatusToCreated')
            ->proxyExecution('setS3Instance')
            ->proxyExecution('saveTmpFileToS3')
            ->proxyExecution('rrmdir')
            ;
        if (!$this->resultStatus) {
            $this->deleteSecularTables()
                ->updateUsesStatusToUnused()
                ->rrmdir()
                ;
        }
        return $this;
    }

    /**
     * 事前準備
     */
    public function prepare()
    {
        $this->connectSecularDatabase();
        $this->setSecularData();
        $this->connectDefaultDatabase();
        $this->targetTables = explode(',', SECULAR_TARGET_TABLES);
        $this->today = date('Ymd');
        $this->tablePrefix = $this->today . '_';
        $this->tmpDir = ($dir) ? $dir : DIR_TMP.$this->secular['hash'].'_secular/';
        if (!file_exists($this->tmpDir)) mkdir($this->tmpDir, 0777, true);
        $this->tmpFile = tempnam($this->tmpDir, '');

        $dumpTable = $this->getTargetTablesWithPrefix();
        $dumpTable = implode(' ', $dumpTable);

        $this->dumpCmd = sprintf(
            "mysqldump -c --skip-comments --default-character-set=utf8 -h %s -u %s -p%s -P %s %s %s >> %s",
            DB_HOST, DB_USER, DB_PASSWD, DB_PORT, DB_NAME, $dumpTable, $this->tmpFile
        );

        $this->writeInformationToTmpFile();
        return $this;
    }

    /**
     * 年月付き対象テーブルの配列を拾得
     */
    public function getTargetTablesWithPrefix()
    {
        return array_map(function($table) {
            return $this->tablePrefix . $table;
        }, $this->targetTables);
    }

    /**
     * ダンプ用一時ファイルにシステム用の情報を格納
     */
    public function writeInformationToTmpFile()
    {
        $dumpTable = $this->getTargetTablesWithPrefix();
        $dumpTable = implode(',', $dumpTable);
        $str = '-- ' . $this->today . ':' . $dumpTable . "\n";
        $fp = fopen($this->tmpFile, 'a+');
        fwrite($fp, $str);
        fclose($fp);
    }

    /**
     * 一時テーブルの作成
     */
    public function makeSecularTables()
    {
        foreach ($this->targetTables as $fromTable) {
            $toTable = $this->tablePrefix . $fromTable;
            $sql = 'CREATE TABLE '.$toTable.' LIKE '.$fromTable.';';
            if (FDB::sql($sql, true) === false) {
                $this->errorLog('SecularCreate::makeSecularTables', 'SQL実行エラー');
                $this->resultStatus = false;
            }
            $sql = 'INSERT INTO '.$toTable.' SELECT * FROM '.$fromTable.';';
            if (FDB::sql($sql, true) === false) {
                $this->errorLog('SecularCreate::makeSecularTables', 'SQL実行エラー');
                $this->resultStatus = false;
            }
        }
        return $this;
    }

    /**
     * ダンプの作成
     */
    public function makeDump()
    {
        exec($this->dumpCmd, $output, $return_var);
        if ($return_var !== 0) {
            $this->errorLog('SecularCreate::makeDump', 'コマンド実行エラー。実行コマンド['.$this->dumpCmd.']');
            $this->resultStatus = false;
        }
        return $this;
    }

    /**
     * 一時テーブルの削除
     */
    public function deleteSecularTables()
    {
        foreach ($this->targetTables as $fromTable) {
            $toTable = $this->tablePrefix . $fromTable;
            $sql = 'DROP TABLE IF EXISTS '.$toTable.';';
            if (FDB::sql($sql, true) === false) {
                $this->errorLog('SecularCreate::deleteSecularTables', 'SQL実行エラー');
                $this->resultStatus = false;
            }
        }
        return $this;
    }

    /**
     * ステータスをデータ未使用に更新
     */
    public function updateUsesStatusToUnused()
    {
        $this->connectSecularDatabase();
        $this->secular['uses_status'] = SECULAR_USES_STATUS_UNUSED;
        $this->secular['muid'] = $_SESSION['muid'];
        $this->secular['ymd'] = $this->today;
        $this->secular['modified_at'] = date('Y-m-d H:i:s');
        if (FDB::update(T_SECULARS, FDB::escapeArray($this->secular), 'WHERE id = '.FDB::escape($this->secular['id'])) === false) {
            $this->errorLog('SecularCreate::updateUsesStatusToUnused', 'SQL実行エラー');
            $this->resultStatus = false;
        }
        $this->connectDefaultDatabase();
        return $this;
    }

    /**
     * ステータスをデータ作成済みに更新
     */
    public function updateUsesStatusToCreated()
    {
        $this->connectSecularDatabase();
        $this->secular['uses_status'] = SECULAR_USES_STATUS_CREATED;
        $this->secular['muid'] = $_SESSION['muid'];
        $this->secular['ymd'] = $this->today;
        $this->secular['modified_at'] = date('Y-m-d H:i:s');
        if (FDB::update(T_SECULARS, FDB::escapeArray($this->secular), 'WHERE id = '.FDB::escape($this->secular['id'])) === false) {
            $this->errorLog('SecularCreate::updateUsesStatusToCreated', 'SQL実行エラー');
            $this->resultStatus = false;
        }
        $this->connectDefaultDatabase();
        return $this;
    }


    /**
     * S3へファイルを保存
     */
    public function saveTmpFileToS3()
    {
        $response = $this->s3->create_object(SECULAR_AWS_S3_BUCKET,
            SECULAR_AWS_S3_PREFIX . PROJECT_NAME . '/dump/' . $this->secular['hash'],
            array(
                'fileUpload'  => $this->tmpFile,
                'acl'         => AmazonS3::ACL_PRIVATE,
                'contentType' => 'text/plain',
            )
        );
        if (!$response->isOK()) {
            $this->errorLog('SecularImport::saveTmpFileToS3', 'Status['.$response->status.'] Code['.$response->body->Code.'] Message['.$response->body->Message.']');
            $this->resultStatus = false;
        }
        return $this;
    }
}
