<?php
require_once('SecularApp.php');

class SecularFeedbackController extends SecularApp
{

    public $feedbackTargets = array();

    public function setTargetDatas()
    {
        $T_SECULARS = T_SECULARS;
        $T_SECULAR_CONDUCTORS = T_SECULAR_CONDUCTORS;
        $sql = <<<__SQL__
SELECT
    s.hash,
    s.ymd,
    s.name,
    sc.table_name
FROM {$T_SECULARS} s
    INNER JOIN {$T_SECULAR_CONDUCTORS} sc ON sc.secular_id = s.id
WHERE target_flag = 1
;
__SQL__;

        $tmp = array();
        foreach (FDB::getAssoc($sql) as $v) {
            $tmp[$v['ymd']]['hash'] = $v['hash'];
            $tmp[$v['ymd']]['name'] = $v['name'];
            $tmp[$v['ymd']]['tables'][] = $v['table_name'];
        }
        $this->feedbackTargets = $tmp;
    }

    public function createAccessTables($ymd)
    {
        foreach ($this->feedbackTargets[$ymd]['tables'] as $fromTable) {
            $table = str_replace($ymd . '_', '', $fromTable);
            $this->createTemporaryTable($table, $fromTable);
        }
    }

    public function dropAccessTables($ymd)
    {
        foreach ($this->feedbackTargets[$ymd]['tables'] as $fromTable) {
            $table = str_replace($ymd . '_', '', $fromTable);
            $this->dropTemporaryTable($table);
        }
    }

    public function createTemporaryTable($table, $fromTable)
    {
        $sql = "CREATE TEMPORARY TABLE {$table} SELECT * FROM {$fromTable}";
        FDB::sql($sql, true);
    }

    public function dropTemporaryTable($table)
    {
        $sql = "DROP TEMPORARY TABLE IF EXISTS {$table}";
        FDB::sql($sql, true);
    }
}
