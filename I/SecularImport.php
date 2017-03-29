<?php
require_once('SecularApp.php');
require_once('SecularCreate.php');
require_once (DIR_LIB . '360_Importer.php');

class SecularImport extends SecularApp
{

    public $file = '';

    public $headerLines = 2;

    public $uidSerialComparison = array();

    /**
     * メイン処理
     */
    public function execute()
    {
        $this->connectSecularDatabase();
        $this->proxyExecution('prepare')
            ->proxyExecution('setS3Instance')
            ->proxyExecution('saveDumpFileFromS3')
            ->proxyExecution('parseInformationsFromDump')
            ->proxyExecution('deleteExistsTables')
            ->proxyExecution('deleteIdentifyYmd')
            ->proxyExecution('createSecularConductors')
            ->proxyExecution('importDumpFile')
            ->proxyExecution('updateUsesStatusToImported')
            ->proxyExecution('rrmdir')
            ;
        if (!$this->resultStatus) {
            $this->rrmdir();
        }
        $this->connectDefaultDatabase();
        return $this;
    }

    /**
     * 事前準備
     */
    public function prepare()
    {
        $this->setSecularData();
        $this->tmpDir = ($dir) ? $dir : DIR_TMP.$this->secular['hash'].'_secular/';
        if (!file_exists($this->tmpDir)) mkdir($this->tmpDir, 0777, true);
        $this->tmpFile = tempnam($this->tmpDir, '');
        $this->importCmd = sprintf(
            "mysql --default-character-set=utf8 -h %s -u %s -p%s -P %s %s < %s",
            DB_HOST, DB_USER, DB_PASSWD, DB_PORT, DB_NAME . DB_NAME_SECULAR_SUFFIX, $this->tmpFile
        );
        return $this;
    }

    /**
     * S3のダンプファイルをtmpFileに保存
     */
    public function saveDumpFileFromS3()
    {
        $key = SECULAR_AWS_S3_PREFIX . PROJECT_NAME . '/dump/' . $this->secular['hash'];
        $response = $this->s3->getObject(SECULAR_AWS_S3_BUCKET, $key, array('fileDownload' => $this->tmpFile));
        if ($response->status != 200) {
            $this->errorLog('SecularImport::saveDumpFileFromS3', 'Status['.$response->status.'] Code['.$response->body->Code.'] Message['.$response->body->Message.']');
        }
        return $this;
    }

    /**
     * ダンプファイルから対象年月、対象テーブル情報の取得
     */
    public function parseInformationsFromDump()
    {
        $fp = fopen($this->tmpFile, 'r');
        $str = fgets($fp);
        $str = preg_replace('/^-- |\n/', '', $str);
        list($this->targetDate, $this->targetTables) = explode(':', $str);
        $this->targetTables = explode(',', $this->targetTables);
        return $this;
    }

    /**
     * 対象年月のテーブルがあれば削除を実施
     */
    public function deleteIdentifyYmd()
    {
        $seculars = FDB::select(T_SECULARS, '*', 'where ymd = "' . $this->secular['ymd'] . '" AND id != ' . $this->secular['id']);
        if (count($seculars) == 0) {
            return $this;
        }
        $ids = array();
        foreach($seculars as $v) {
            $ids[] = $v['id'];
        }
        $ids = implode(',', $ids);
        $secularConductors = FDB::select(T_SECULAR_CONDUCTORS, '*', 'where secular_id IN ('.$ids.')');
        foreach ($secularConductors as $data) {
            $sql = 'DROP TABLE IF EXISTS '.$data['table_name'].';';
            if (FDB::sql($sql, true) === false) {
                $this->errorLog('SecularImport::deleteIdentifyYmd', 'SQL実行エラー');
                $this->resultStatus = false;
            }
        }
        if (FDB::delete(T_SECULAR_CONDUCTORS, 'where secular_id IN ('.$ids.')') === false) {
            $this->errorLog('SecularImport::deleteIdentifyYmd', 'SQL実行エラー');
            $this->resultStatus = false;
        }
        if (FDB::update(T_SECULARS, array('target_flag' => 0), 'WHERE id IN ('.$ids.')') === false) {
            $this->errorLog('SecularCreate::deleteIdentifyYmd', 'SQL実行エラー');
            $this->resultStatus = false;
        }
        return $this;
    }

    /**
     * 対象年月のテーブルがあれば削除を実施
     */
    public function deleteExistsTables()
    {
        $secularConductors = FDB::select(T_SECULAR_CONDUCTORS, '*', 'where secular_id = "' . $this->secular['id'] . '"');
        if (is_void($secularConductors)) {
            return $this;
        }
        foreach ($secularConductors as $data) {
            $sql = 'DROP TABLE IF EXISTS '.$data['table_name'].';';
            if (FDB::sql($sql, true) === false) {
                $this->errorLog('SecularImport::deleteExistsTables', 'SQL実行エラー');
                $this->resultStatus = false;
            }
        }
        if (FDB::delete(T_SECULAR_CONDUCTORS, 'where secular_id = "' . $this->secular['id'] . '"') === false) {
            $this->errorLog('SecularImport::deleteExistsTables', 'SQL実行エラー');
            $this->resultStatus = false;
        }
        return $this;
    }

    /**
     * 経年テーブル管理マスタを作成
     */
    public function createSecularConductors()
    {
        $this->connectSecularDatabase();
        FDB::begin();
        foreach ($this->targetTables as $tableName) {
            $tableName = $this->tablePrefix . $tableName;
            $save = array(
                'table_name' => $tableName,
                'secular_id' => $this->secular['id'],
            );
            if (FDB::insert(T_SECULAR_CONDUCTORS, FDB::escapeArray($save)) === false) {
                $this->errorLog('SecularImport::createSecularConductors', 'SQL実行エラー');
                $this->resultStatus = false;
            }
        }
        FDB::commit();
        $this->connectDefaultDatabase();
        return $this;
    }


    /**
     *  インポートコマンド実行
     */
    public function importDumpFile()
    {
        exec($this->importCmd, $output, $return_var);
        if ($return_var !== 0) {
            $this->errorLog('SecularCreate::importDumpFile', 'コマンド実行エラー。実行コマンド['.$this->importCmd.']');
        }
        return $this;
    }

    /**
     * ステータスをインポート済みに更新
     */
    public function updateUsesStatusToImported()
    {
        $this->connectSecularDatabase();
        FDB::begin();
        $this->secular['uses_status'] = SECULAR_USES_STATUS_IMPORTED;
        $this->secular['muid'] = $_SESSION['muid'];
        $this->secular['modified_at'] = date('Y-m-d H:i:s');
        if (FDB::update(T_SECULARS, FDB::escapeArray($this->secular), 'WHERE id = '.FDB::escape($this->secular['id'])) === false) {
            $this->errorLog('SecularCreate::updateUsesStatusToImported', 'SQL実行エラー');
            $this->resultStatus = false;
        }
        FDB::commit();
        $this->connectDefaultDatabase();
        return $this;
    }

    /**
     * RAWファイルからのインポートメイン処理
     */
    public function importByRaw($ymd)
    {
        $this->connectDefaultDatabase();
        $this->importByRawErrors = array();
        $this->prepareImportByRaw($ymd)
            ->prepareImporter()
            ->proxyExecution('setS3Instance')
            ->proxyExecution('saveRawFileToS3')
            ->proxyExecution('deleteExistsTables')
            ->proxyExecution('deleteIdentifyYmd')
            ->proxyExecution('deleteAndCreateTables')
            ->proxyExecution('createSecularConductors')
            ->proxyExecution('executeInsertByRaw')
            ->proxyExecution('updateUsesStatusToRawImported')
            ;

        $this->connectDefaultDatabase();

        if (is_good($this->importByRawErrors)) {
            $body = $this->getBodyOfImportByRawErrors();
            $this->alertSendMailToAdministrator($body);
        }

        return $this;
    }

    public function updateUsesStatusToRawImported()
    {
        $this->connectSecularDatabase();
        FDB::begin();
        $this->secular['uses_type'] = SECULAR_USES_TYPE_RAW;
        $this->secular['uses_status'] = SECULAR_USES_STATUS_IMPORTED;
        $this->secular['muid'] = $_SESSION['muid'];
        $this->secular['ymd'] = $this->today;
        $this->secular['modified_at'] = date('Y-m-d H:i:s');
        if (FDB::update(T_SECULARS, FDB::escapeArray($this->secular), 'WHERE id = '.FDB::escape($this->secular['id'])) === false) {
            $this->errorLog('SecularImport::updateUsesStatusToCreated', 'SQL実行エラー');
            $this->resultStatus = false;
        }
        FDB::commit();
        $this->connectDefaultDatabase();
        return $this;
    }

    public function executeInsertByRaw()
    {
        $serialNoArray = $this->findSerialNoArrayByRows();
        if (is_void($serialNoArray)) {
            $this->importByRawErrors['prepare'][] = 'インポート対象となるusrがゼロ件のため処理を中断しました。';
            $this->errorLog('SecularImport::executeInsertByRaw', 'インポート対象となるusrがゼロ件のため処理を中断しました。');
            $this->resultStatus = false;
            return $this;
        }
        // usrインサート
        $this->executeUsrInsertByRaw($serialNoArray);

        $this->connectSecularDatabase();
        $line = $this->headerLines;
        $this->resetFilePoint();

        $errorLines = [];
        if (is_good($this->importByRawErrors['csv'])) {
            $errorLines = array_keys($this->importByRawErrors['csv']);
        }

        while ($row = $this->Importer->getRowFromFile($this->fp, array())) {
            $line++;
            // エラーが発生している行はスキップ
            if (array_search($line, $errorLines) !== false) {
                continue;
            }

            $this->executeUsrRelationInsertByRaw($row, $line)
                ->executeEventDataInsertByRaw($row, $line)
                ->executeSubEventDataInsertByRaw($row, $line)
                ;
            unset($row);
        }
        return $this;
    }

    /**
     * S3へRAWファイルを保存
     */
    public function saveRawFileToS3()
    {
        $response = $this->s3->create_object(SECULAR_AWS_S3_BUCKET,
            SECULAR_AWS_S3_PREFIX . PROJECT_NAME . '/csv/' . $this->secular['hash'],
            array(
                'fileUpload'  => $this->file,
                'acl'         => AmazonS3::ACL_PRIVATE,
                'contentType' => 'text/plain',
            )
        );
        if (!$response->isOK()) {
            $this->errorLog('SecularImport::saveRawFileToS3', 'Status['.$response->status.'] Code['.$response->body->Code.'] Message['.$response->body->Message.']');
            $this->importByRawErrors['prepare'][] = 'S3へのRAWファイル保存に失敗しました。Status['.$response->status.'] Code['.$response->body->Code.'] Message['.$response->body->Message.']';
            $this->resultStatus = false;
        }
        unset($this->s3);
        return $this;
    }

    public function executeUsrInsertByRaw($serialNoArray)
    {
        FDB::begin();

        $toTable = DB_NAME . DB_NAME_SECULAR_SUFFIX . '.' . $this->tablePrefix . T_USER_MST;
        $fromTable = DB_NAME . '.' . T_USER_MST;

        $serialNoArray = implode(',', FDB::escapeArray($serialNoArray));
        $sql = "INSERT INTO $toTable SELECT * FROM $fromTable WHERE $fromTable.serial_no IN ($serialNoArray)";
        if (FDB::sql($sql, true) === false) {
            $this->errorLog('SecularImport::insertUsrByRaw', 'SQL実行エラー');
        }
        FDB::commit();
        unset($serialNoArray);

        return $this;
    }

    public function executeUsrRelationInsertByRaw($row, $line)
    {
        FDB::begin();
        $save = array(
            'uid_a'     => FDB::escape($row[2]),
            'uid_b'     => FDB::escape($row[7]),
            'user_type' => FDB::escape($row[10]),
        );
        if (FDB::insert($this->tablePrefix . T_USER_RELATION, $save) === false) {
            $this->importByRawErrors['csv'][$line] = 'usr_relationテーブルのINSERTに失敗しました。uid_a['.$row[2].'] uid_b['.$row[7].']';
        }
        FDB::commit();
        unset($save);
        return $this;
    }

    public function executeEventDataInsertByRaw($row, $line)
    {
        $evid = getEvidBySheetTypeAndUserType($row[11], $row[10]);
        FDB::begin();
        $save = array(
            'evid'         => $evid,
            'serial_no'    => FDB::escape($this->uidSerialComparison[$row[2]]),
            'target'       => FDB::escape($this->uidSerialComparison[$row[7]]),
            'udate'        => FDB::escape($row[1]),
            'flg'          => 1,
            'answer_state' => 0,
        );
        if (FDB::insert($this->tablePrefix . T_EVENT_DATA, $save) === false) {
            $this->importByRawErrors['csv'][$line] = 'event_dataテーブルのINSERTに失敗しました。evid['.$evid.']';
        }
        $this->lastEventDataId = FDB::getLastInsertedId();
        FDB::commit();
        return $this;
    }

    public function executeSubEventDataInsertByRaw($row, $line)
    {
        $evid = getEvidBySheetTypeAndUserType($row[11], $row[10]);
        $saveFormat = array(
            'evid'          => $evid,
            'serial_no'     => $this->uidSerialComparison[$row[2]],
            'event_data_id' => $this->lastEventDataId,
        );
        $slicedRow = array_slice($row, 12);

        $subevents = array();
        foreach (FDB::select(DB_NAME . '.' . T_EVENT_SUB, 'seid,type2,chtable', 'where evid = ' . FDB::escape($evid)) as $v) {
            $subevents[$v['seid']] = $v;
        }
        FDB::begin();
        for ($i = 0; $i < count($slicedRow); $i++) {
            $seid = (String)$evid . sprintf('%03d', ((int)$i + 1));
            $save = $saveFormat;
            $save['seid'] = $seid;

            if (is_void($subevents[$seid])) {
                $this->importByRawErrors['csv'][$line] = '対象の設問が存在しません。seid['.$seid.']';
                continue;
            }

            $answer = $slicedRow[$i];
            $subevent = $subevents[$seid];
            if ($subevent['type2']=='r' || $subevent['type2']=='p') {
                $ch = array_flip(explode(',',$subevent['chtable']));
                $c = (is_good($subevent['chtable'])) ? $ch[$answer] : $answer;

                $save['choice'] = (is_void($c)) ? '9998' : $c;
                if (FDB::insert($this->tablePrefix . T_EVENT_SUB_DATA, FDB::escapeArray($save)) === false) {
                    $this->errorLog('SecularImport::executeSubEventDataInsertByRaw', 'SQL実行エラー');
                }
            } elseif ($subevent['type2']=='t') {
                $save['choice'] = '-1';
                $save['other'] = $answer;
                if (FDB::insert($this->tablePrefix . T_EVENT_SUB_DATA, FDB::escapeArray($save)) === false) {
                    $this->errorLog('SecularImport::executeSubEventDataInsertByRaw', 'SQL実行エラー');
                }
            }
        }
        FDB::commit();
        unset($subevents);

        return $this;
    }

    public function prepareImportByRaw($ymd)
    {
        $this->connectSecularDatabase();
        $this->setSecularData();
        $this->isExecutableForImportByRaw();

        $this->targetTables = explode(',', SECULAR_TARGET_TABLES);
        $this->today = $ymd;
        $this->tablePrefix = $this->today . '_';
        return $this;
    }

    public function isExecutableForImportByRaw()
    {
        if ($this->secular['uses_status'] != SECULAR_USES_STATUS_UNUSED) {
            $this->errorLog('SecularImport::isExecutableForImportByRaw', '未使用以外のステータスで実行されました。');
            $this->resultStatus = false;
        }
        if (is_void($_FILES['file']['tmp_name'])) {
            $this->errorLog('SecularImport::isExecutableForImportByRaw', '添付ファイルが存在しない状態で実行されました。');
            $this->resultStatus = false;
        }
        return $this;
    }

    public function deleteAndCreateTables()
    {
        $this->connectSecularDatabase();
        FDB::begin();
        foreach ($this->targetTables as $fromTable) {
            $toTable = $this->tablePrefix . $fromTable;
            $sql = 'DROP TABLE IF EXISTS '.$toTable.';';
            if (FDB::sql($sql, true) === false) {
                $this->errorLog('SecularImport::deleteAndCreateTables', 'SQL実行エラー');
                $this->importByRawErrors['prepare'][] = 'テーブル削除処理に失敗しました。テーブル名['.$toTable.']';
                $this->resultStatus = false;
            }
            $sql = 'CREATE TABLE '.$toTable.' LIKE `' . DB_NAME . '`.'.$fromTable.';';
            if (FDB::sql($sql, true) === false) {
                $this->errorLog('SecularImport::deleteAndCreateTables', 'SQL実行エラー');
                $this->importByRawErrors['prepare'][] = 'テーブル作成処理に失敗しました。テーブル名['.$toTable.']';
                $this->resultStatus = false;
            }
        }
        FDB::commit();
        $this->connectDefaultDatabase();
        return $this;
    }

    public function findSerialNoArrayByRows()
    {
        $serialNoArray = [];
        $line = $this->headerLines;
        $this->connectDefaultDatabase();
        $this->resetFilePoint();
        while ($row = $this->Importer->getRowFromFile($this->fp, array())) {
            $error = false;
            $line++;
            $usr = FDB::select1(T_USER_MST, 'serial_no', 'where uid = ' . FDB::escape($row[2]));
            if (is_void($usr)) {
                $this->importByRawErrors['csv'][$line] = '回答者が見つかりませんでした。回答者ID['.$row[2].']';
                $error = true;
            }
            $usr2 = FDB::select1(T_USER_MST, 'serial_no', 'where uid = ' . FDB::escape($row[7]));
            if (is_void($usr2)) {
                $this->importByRawErrors['csv'][$line] = '対象者が見つかりませんでした。対象者ID['.$row[7].']';
                $error = true;
            }
            if ($error) {
                continue;
            }
            $serialNoArray[] = $usr['serial_no'];
            $serialNoArray[] = $usr2['serial_no'];
            $this->uidSerialComparison[$row[2]] = $usr['serial_no'];
            $this->uidSerialComparison[$row[7]] = $usr2['serial_no'];
        }

        $serialNoArray = array_unique($serialNoArray);
        sort($serialNoArray);

        return $serialNoArray;
    }

    public function prepareImporter()
    {
        $this->Importer   = new Importer360(new ImportModel360());
        $temp_id    = temp_rename($_FILES['file']['tmp_name']);
        $this->file = temp_file_path($temp_id);

        $tmp = file_get_contents($this->file);
        $mb = mb_detect_encoding($tmp);
        if($mb=='SJIS')
            $mb = 'sjis-win';

        if($mb)
            file_put_contents($this->file, mb_convert_encoding($tmp, "UTF-8", $mb));
        else
            file_put_contents($this->file, mb_convert_encoding($tmp, "UTF-8", "Unicode"));

        $this->fp = fopen($this->file, 'r');

        for ($i = 0; $i < $this->headerLines; $i++) {
            $line =  fgets($this->fp);
        }
        $line = fgets($this->fp);
        if(count(explode("\t",$line)) < count(explode(",",$line)))
            $this->Importer->delimiter = ",";
        else
            $this->Importer->delimiter = "\t";

        $this->resetFilePoint();
        return $this;
    }

    public function resetFilePoint()
    {
        // ヘッダ部除去
        rewind($this->fp);
        for ($i=0; $i < $this->headerLines; $i++) {
            $this->Importer->getRowFromFile($this->fp, array(), true);
        }
        return $this;
    }

    public function getBodyOfImportByRawErrors()
    {
        $body = <<<MAILBODY
本メールはシステムから自動送信です。

SmartReviewにて経年比較のRAWデータインポート時にエラーが発生しています。
詳細は以下の通りです。

MAILBODY;
        if (is_good($this->importByRawErrors['prepare'])) {
            $error = implode("\n", $this->importByRawErrors['prepare']);
            $body .= <<<MAILBODY

■システムエラー
$error

MAILBODY;
        }
        if (is_good($this->importByRawErrors['csv'])) {
            $error = '';
            foreach ($this->importByRawErrors['csv'] as $line => $message) {
                $error .= $line . '行目：' . $message . "\n";
            }
            $body .= <<<MAILBODY

■RAWデータエラー
$error
MAILBODY;
        }

        $body .= <<<MAILBODY

エラー対象となったものはインポートされておりません。
RAWデータの内容を確認、更新していただき、
再度インポートを行うよう御願い申し上げます。
MAILBODY;

        return $body;
    }
}
