<?php namespace SmartReview\Admin\EnqUpdateAll;
/**
 * 評価シート管理[一括更新(詳細)]
 */
require_once (DIR_LIB . 'CbaseCSV.php');
class EnqUpdateAll
{
    public $CbaseCSV;
    public $Importer;
    public $prmStrCol = '*';

    public function __construct()
    {
        $this->CbaseCSV = new \CbaseCSV(OUTPUT_CSV_ENCODE);
    }
    public function getDownloadSubEvents()
    {
        $evids = array();
        $csv = array();
        $where_evid = '';
        $prmStrOption = '';
        $column = str_replace("seid as ", "", $this->prmStrCol);
        $column = explode(',', $column);
        if (is_good($_POST['sheet_type']) && is_good($_POST['user_type']))
        {
            foreach ($_POST['user_type'] as $user_type)
            {
                foreach($_POST['sheet_type'] as $sheet_type)
                    $evids[] = $user_type + $sheet_type * 100;
            }
            $prmStrOption = 'WHERE evid in ('.implode(',', \FDB :: escapeArray($evids,false)).') ';
            $prmStrOption .= 'ORDER BY evid, seid';
            $csv = \FDB::select(T_EVENT_SUB, $this->prmStrCol, $prmStrOption);
        }
        array_unshift($csv, $column);

        return $csv;
    }

    public function getUserTypeName()
    {
        global $_360_user_type;
        $typename = '';
        if (isset($_POST['user_type']))
            foreach ($_POST['user_type'] as $user_type)
                $typename .= ($user_type != '') ? $_360_user_type[$user_type] : '';

        return replaceMessage($typename);
    }

    public function getCsvDownloadUtf8($data)
    {
        $filename = date('Ymd') . '_' . PAGE_TITLE . $this->getUserTypeName() . '.csv';
        return $this->CbaseCSV->get_csv_download_utf8($data, $filename, false);
    }

    public function csvdownload()
    {
        $subevent = $this->getDownloadSubEvents();
        $this->CbaseCSV->execute_csv_download($this->getCsvDownloadUtf8($subevent));
    }

    public function importConfirm($file)
    {
        $this->Importer = new \Importer360(new ThisImportModel(), new ThisImportDesign());
        $this->importConfirmResult = $this->Importer->importConfirmExec($file);

        return $this;
    }

    public function import($file)
    {
        list($ng_line, $total) = $this->importConfirmResult;
        $this->Importer->useTransaction = true;
        $this->Importer->useProgressbar = false;
        $this->Importer->importExec($file, $ng_line, $total, array());

        return $this;
    }
}
