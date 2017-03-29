<?php
/*
 * 評価集計値データダウンロード
 * 2008/09/18
 */

/** ルートディレクトリ */
//define("DEBUG", 1);
define('DIR_ROOT','../');
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'360_FHtml.php');
require_once(DIR_LIB.'CbaseFunction.php');
require_once(DIR_LIB.'360_Function.php');
require_once(DIR_LIB.'CbaseFErrorMSG.php');
require_once(DIR_LIB.'CbaseFDB.php');
require_once(DIR_LIB.'CbaseFError.php');
require_once(DIR_LIB.'CbaseFForm.php');
require_once(DIR_LIB.'CbaseFFile2.php');
require_once(DIR_LIB.'CbaseEncoding.php');
require_once(DIR_LIB.'CbaseFManage.php');
require_once(DIR_LIB.'360_ExportTotal.php');
require_once(DIR_LIB.'360_EnqueteRelace.php');
require_once(DIR_LIB.'CbaseFEnquete.php');
class ResultTotalThis extends ResultTotal
{
    public $aryIndex = array('評価者タイプ', 'シート', '回答人数');

    // ページタイトル
    public function getPageTitle()
    {
        global $type_name;

        return "{$type_name[$this->sheet_type]} 集計値ダウンロード(FB用)";
    }
    function &create($prm)
    {
        $instance = new ResultTotalThis();
        if (FError::is($err = $instance->initialize ($prm))) {
            return $err;
        }
        $instance->total = new ResultTotalCal();

        return $instance;
    }
    public function getListLine($show, $cntData)
    {
        $this->total->setTargetSerial($show['serial_no']);
        $this->total->prepare();
        $this->total->run();

        $user_info = $this->total->getUserInfo();
        //評価者の数をカウント
        $inputer = $this->total->getInputerData();

        $NAPool = array();
        $BarGraphPool = array();
        foreach ((array) $inputer as $k=>$v) {
            $ary_comment = array();
            $arrayData = $category1List = $category2Data = array();
            $arrayData = $NAData = $BarGraphData = $user_info;
            $user_type = getUserTypeNameById($k);
            if ($k=="others") {
                $MinData = $MaxData = $BaratukiData = $user_info;
            }

            $arrayData[] = $user_type;
            $NAData[] = $user_type."(#N/A)";
            $BarGraphData[] = $user_type."(棒グラフ用)";
            if ($k=="others") {
                $MinData[] = "最小値(他者)";
                $MaxData[] = "最大値(他者)";
                $BaratukiData[] = "ばらつき(他者)";
            }

            $arrayData[] = $NAData[] = $BarGraphData[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
            if ($k=="others") {
                $MinData[] = $MaxData[] = $BaratukiData[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
            }
            $count = $this->total->count[$k];
            $arrayData[] = $NAData[] = $BarGraphData[] = $count;
            if ($k=="others") {
                $MinData[] = $MaxData[] = $BaratukiData[] = $count;
            }

            $arrayData = array_merge($arrayData, $this->total->getAverageArray($k));
            $NAData = array_merge($NAData, $this->total->getAverageArray($k, "#N/A"));
            $BarGraphData = array_merge($BarGraphData, $this->total->getAverageArray($k, "#N/A", true));

            $arrayData = array_merge($arrayData, $this->total->getCommentJoinArray($k));
            $NAData = array_merge($NAData, $this->total->getCommentJoinArray($k));
            $BarGraphData = array_merge($BarGraphData, $this->total->getCommentJoinArray($k));

            $arrayData = array_merge($arrayData, $this->total->getCategoryArray($k, 1));
            $NAData = array_merge($NAData, $this->total->getCategoryArray($k, 1, "#N/A"));
            $BarGraphData = array_merge($BarGraphData, $this->total->getCategoryArray($k, 1, "#N/A", true));

            $arrayData = array_merge($arrayData, $this->total->getCategoryArray($k, 2));
            $NAData = array_merge($NAData, $this->total->getCategoryArray($k, 2, "#N/A"));
            $BarGraphData = array_merge($BarGraphData, $this->total->getCategoryArray($k, 2, "#N/A", true));

            $this->writeTmpFile($arrayData);
            $NAPool[] = $NAData;
            $BarGraphPool[] = $BarGraphData;
        }

        $MinData = array_merge($MinData, $this->total->MinData['others']);
        $MaxData = array_merge($MaxData, $this->total->MaxData['others']);
        $BaratukiData = array_merge($BaratukiData, $this->total->BaratukiData['others']);

        if (is_good($NAPool)) {
            $this->writeTmpFile(array());
            foreach ($NAPool as $v) {
                $this->writeTmpFile($v);
            }
        }
        if (is_good($BarGraphPool)) {
            $this->writeTmpFile(array());
            foreach ($BarGraphPool as $v) {
                $this->writeTmpFile($v);
            }
        }
        $this->writeTmpFile(array());
        $this->writeTmpFile($MinData);
        $this->writeTmpFile($MaxData);
        $this->writeTmpFile($BaratukiData);
        $this->writeTmpFile(array());	//改行

        return null;
    }
    public function getLimitListLine($show)
    {
        $this->total->setTargetSerial($show['serial_no']);
        $this->total->limitRun();
        return null;
    }
    public function getGrandTotal()
    {
        foreach ((array) $this->total->targetData as $display_type => $val) {
            $arrayData = $NAData = $BarGraphData = array();
            $user_type = getUserTypeNameById($display_type);
            $arrayData[] = $user_type;
            $NAData[] = $user_type."(#N/A)";
            $BarGraphData[] = $user_type."(棒グラフ用)";
            $arrayData[] = $NAData[] = $BarGraphData[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
            $arrayData[] = $NAData[] = $BarGraphData[] = array_sum($this->total->inputerCount[$display_type]);
            if ($display_type=="others") {
                $MinData = $MaxData = $BaratukiData = array();
                $MinData[] = "最小値(他者)";
                $MaxData[] = "最大値(他者)";
                $BaratukiData[] = "ばらつき(他者)";
                $MinData[] = $MaxData[] = $BaratukiData[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
                $MinData[] = $MaxData[] = $BaratukiData[] = array_sum($this->total->inputerCount[$display_type]);
            }
            foreach ($val as $num => $answers) {
                $this->total->average[$display_type][$num] = $this->total->getAverage($answers);
                $this->total->MaxData[$display_type][$num] = $this->total->getMax($this->total->MaxData[$display_type][$num]);
                $this->total->MinData[$display_type][$num] = $this->total->getMin($this->total->MinData[$display_type][$num]);
                $this->total->BaratukiData[$display_type][$num] = $this->total->getBaratuki($this->total->BaratukiData[$display_type][$num]);
            }
            $this->total->divideCategory($answers);

            $arrayData = array_merge($arrayData, $this->total->getAverageArray($display_type));
            $NAData = array_merge($NAData, $this->total->getAverageArray($display_type, "#N/A"));
            $BarGraphData = array_merge($BarGraphData, $this->total->getAverageArray($display_type, "#N/A", true));

            $arrayData = array_merge($arrayData, $this->total->getCategoryArray($display_type, 1));
            $NAData = array_merge($NAData, $this->total->getCategoryArray($display_type, 1, "#N/A"));
            $BarGraphData = array_merge($BarGraphData, $this->total->getCategoryArray($display_type, 1, "#N/A", true));

            $arrayData = array_merge($arrayData, $this->total->getCategoryArray($display_type, 2));
            $NAData = array_merge($NAData, $this->total->getCategoryArray($display_type, 2, "#N/A"));
            $BarGraphData = array_merge($BarGraphData, $this->total->getCategoryArray($display_type, 2, "#N/A", true));

            $this->writeTmpFile($arrayData);
            $NAPool[] = $NAData;
            $BarGraphPool[] = $BarGraphData;
        }
        $this->writeTmpFile(array());
        foreach ($NAPool as $v)
            $this->writeTmpFile($v);

        $this->writeTmpFile(array());
        foreach ($BarGraphPool as $v)
            $this->writeTmpFile($v);

        $MinData = array_merge($MinData, $this->total->MinData['others']);
        $MaxData = array_merge($MaxData, $this->total->MaxData['others']);
        $BaratukiData = array_merge($BaratukiData, $this->total->BaratukiData['others']);
        $this->writeTmpFile(array());
        $this->writeTmpFile($MinData);
        $this->writeTmpFile($MaxData);
        $this->writeTmpFile($BaratukiData);
    }

    /**
     * CSVインデックス取得
     */
    public function getCsvIndexByTotalType($total_type, $obj)
    {

        $this->total->setSheetType($this->sheet_type);
        $aryIndex2 = array('対象者ID',replaceMessage('対象者####div_name_1####'),replaceMessage('対象者####div_name_2####'),replaceMessage('対象者####div_name_3####'),'対象者氏名','対象者役職','評価者タイプ','シート','回答人数');
        if (!is_zero($this->total_limit)) {
            $aryIndex2 = $this->aryIndex;
        }
        $aryIndex1 = $fb_order = array_pad(array(),count($aryIndex2),'');
        foreach ($this->total->getSheetData() as $num=>$data) {
            if(!$data)
                continue;
            $num_ext = '';

            if (preg_match('/_[0-9]+$/', $this->total->header_num2seid[$num], $match)) {
                $num_ext.= $match[0];
            }
            $subevent_key = preg_replace('/_[0-9]+$/', '', $this->total->header_num2seid[$num]);
            $fb_order[] = $this->total->subevents[$subevent_key]['num_ext'].$num_ext;
            $fb_order = array_pad($fb_order,count($aryIndex2),'');
            $aryIndex1[] = $num;
            $aryIndex2[] = $data;
        }
        if (is_zero($this->total_limit)) {
            foreach ($this->total->comments as $num=>$t) {
                $fb_order[] = $this->total->subevents[$this->total->header_num2seid[$num]]['num_ext'];
                $aryIndex1[] = $num;
                $aryIndex2[] = $t;
            }
        }
        foreach ($this->total->category1 as $category1) {
            $fb_order[] = "";
            $aryIndex1[] = "大-".$category1;
            $aryIndex2[] = "";
        }
        foreach ($this->total->category2 as $category2) {
            $fb_order[] = "";
            $aryIndex1[] = "中-".$category2;
            $aryIndex2[] = "";
        }

        return array($fb_order,$aryIndex1,$aryIndex2);
    }
}
encodeWebInAll();
session_start();
Check_AuthMng(basename(__FILE__));
$PHP_SELF = getPHP_SELF();
$SID = getSID();
ResultTotalThis::run($main =& ResultTotalThis::Create($_GET));
$main->setCsvName('(FB用)');
ResultTotalThis::run($view = $main->main($_POST));
print $view;
exit;
