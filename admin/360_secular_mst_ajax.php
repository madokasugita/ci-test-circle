<?php
define('DIR_ROOT', '../');
// define('ENCODE_WEB_OUT', 'UTF-8');
// define('MODE', 'AJAX');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
// require_once (DIR_LIB . 'functions_ajax.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . 'SecularApp.php');
session_start();
Check_AuthMng('360_secular_mst.php');
// mb_http_output("pass");
// ob_start("ajaxPrint");


$SecularMstAjax = new SecularMstAjax();
$SecularMstAjax->main();

exit;

/*****************************************************************************************************************/
class SecularMstAjax {
    public $result = 0;
    public function main()
    {
        // 経年比較用DBへ接続して実行
        $SecularApp = new SecularApp();
        $SecularApp->connectSecularDatabase();
        if ($this->isExecute()) {
            if (!$this->hasError()) {
                $this->execute();
            }
        }
        $SecularApp->connectDefaultDatabase();
        echo $this->result;
        exit;
    }

    public function isExecute()
    {
        if (is_good($_REQUEST['secular_id'])
            && is_good($_REQUEST['target_flag'])
            && ($_REQUEST['target_flag'] == 0 || $_REQUEST['target_flag'] == 1))
        {
            return true;
        }

        return false;
    }

    public function hasError()
    {
        if ($_REQUEST['target_flag'] == 1) {
            $data = FDB::select1(T_SECULARS, 'count(*) as count','where target_flag = 1');
            if ($data['count'] >= SECULAR_TARGET_LIMIT_COUNT) {
                $this->result = 2;
                return true;
            }
            $data = FDB::select1(T_SECULARS, 'count(*) as count','where target_flag = 1 AND ymd = ' . FDB::escape($_REQUEST['ymd']));
            if ($data['count'] >= 1) {
                $this->result = 3;
                return true;
            }
        }
        return false;
    }

    public function execute()
    {
        if (FDB::update(T_SECULARS, array('target_flag' => FDB::escape($_REQUEST['target_flag'])), 'WHERE id = '.FDB::escape($_REQUEST['secular_id'])) !== false) {
            $this->result = 1;
        } else {
            $this->result = 9;
        }
        return $this;
    }
}
