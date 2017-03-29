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
    public $aryIndex = array('タイプ', '評価者タイプ', 'シート', '回答人数');

    // ページタイトル
    public function getPageTitle()
    {
        global $type_name;

        return "{$type_name[$this->sheet_type]} 集計値ダウンロード(標準偏差)";
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
        //ユーザ情報を出力データに追加
        $user_info = $this->getUserInfo($show['serial_no']);

        //集計データ下準備
        $sheetData = $this->total->getSheetData();
        $qcount = count($sheetData);
        $sheet_type = FDB::escape($this->sheet_type);
        $serial_no = FDB::escape($show['serial_no']);
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;
        $SQL=<<<SQL
SELECT a.*,c.num,c.type2,c.rows FROM {$T_EVENT_SUB_DATA} a left join {$T_EVENT_DATA} b using(event_data_id) left join {$T_EVENT_SUB} c using(seid) WHERE answer_state = 0 and (a.evid - a.evid % 100) / 100 = {$sheet_type} and target = {$serial_no} order by seid;

SQL;

        //RAWデータ取得
        $this->total->answers = array();
        $this->total->c_answers = array();
        $users = array();
        $comments = array();
        foreach (FDB::getAssoc($SQL) as $data) {
            $type = $data['evid']%100;
            if ($data['type2'] == 't' && $data['rows']>1) {
                $comments[$type][$data['seid']][] = $data['other'];
            }
            $num = $this->total->seid2num[$data['seid']];
            if($num > $qcount)
                continue;

            $ans = $data['other']?$data['other'] : $this->getConvertChoice($data['choice'],$data['seid']);
            $this->total->putInAnswers($data,$num,$type,$ans);
            $users[$type][$data['serial_no']]=true;
        }

        // 複数選択のデータをCSV用に入替え
        $this->total->changeCheckboxAnswer();

        //評価者の数をカウント
        $inputer =$this->getInputerData();
        foreach ($inputer as $inputer_) {
            $count=0;
            foreach ($inputer_ as $i) {
                $count +=count($users[$i]);
            }
            $aryData[] = $count;
        }

        $arrayData = array();
        foreach ((array) $inputer as $k=>$v) {
            $ary_comment = array();
            $arrayData0 = $arrayData1 = $arrayData2 = $arrayData3  = $user_info;
            $arrayData0[] = '平均値';
            $arrayData1[] = '最小値';
            $arrayData2[] = '最大値';
            $arrayData3[] = '標準偏差';

            $arrayData0[] = $k;
            $arrayData0[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
            $arrayData1[] = $k;
            $arrayData1[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
            $arrayData2[] = $k;
            $arrayData2[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
            $arrayData3[] = $k;
            $arrayData3[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);

            $count = 0;
            foreach($v as $i)
                $count +=count($users[$i]);
            $arrayData0[] = $count;
            $arrayData1[] = $count;
            $arrayData2[] = $count;
            $arrayData3[] = $count;

            foreach ($sheetData as $key => $line) {
                $a2 =array();
                foreach((array) $v as $type)
                    foreach((array) $this->total->answers[$key][$type] as $a)
                        $a2[] = $a;

                $arrayData0[] =  $this->getAverage($a2);
                $arrayData1[] =  $this->getMin($a2);
                $arrayData2[] =  $this->getMax($a2);
                $arrayData3[] =  $this->getStdev($a2);
            }

            $this->writeTmpFile($arrayData0);
            $this->writeTmpFile($arrayData1);
            $this->writeTmpFile($arrayData2);
            $this->writeTmpFile($arrayData3);
        }

        return null;
    }
    public function getLimitListLine($show)
    {
        // 回答データ取得
        $subevent_data = $this->total->getSubeventDataBySerial($show['serial_no']);

        $this->total->answers = array();
        $this->total->c_answers = array();
        $this->total->target = array();
        $inputer = $this->getInputerData();
        $sheetData = $this->total->getSheetData();

        //RAWデータ取得
        foreach ($subevent_data as $data) {
            $type = $data['evid']%100;
            $num = $this->total->seid2num[$data['seid']];
            $ans = $this->getConvertChoice($data['choice'],$data['seid']);
            $this->total->putInAnswers($data,$num,$type,$ans);
        }

        // 複数選択のデータをCSV用に入替え
        $this->total->changeCheckboxAnswer();

        foreach ((array) $inputer as $display_type => $types) {
            $this->inputerCount[$display_type][] = $this->total->countInputer($types);
            foreach ($sheetData as $num => $line) {
                $a2 =array();
                foreach((array) $types as $type)
                    foreach((array) $this->total->answers[$num][$type] as $a)
                        $a2[] = $a;
                $this->targetData0[$display_type][$num][] = $this->getAverage($a2);
                $this->targetData1[$display_type][$num][] = $this->getMin($a2);
                $this->targetData2[$display_type][$num][] = $this->getMax($a2);
                $this->targetData3[$display_type][$num][] = $this->getStdev($a2);
            }
        }
        return null;
    }
    public function getGrandTotal()
    {
        foreach ((array) $this->targetData0 as $display_type => $val) {
            $arrayData0 = $arrayData1 = $arrayData2 = $arrayData3 = array();
            $arrayData0[] = '平均値';
            $arrayData1[] = '最小値';
            $arrayData2[] = '最大値';
            $arrayData3[] = '標準偏差';
            $arrayData0[] = $arrayData1[] = $arrayData2[] = $arrayData3[] = $display_type;
            $arrayData0[] = $arrayData1[] = $arrayData2[] = $arrayData3[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);
            $arrayData0[] = $arrayData1[] = $arrayData2[] = $arrayData3[] = array_sum($this->inputerCount[$display_type]);
            foreach ($val as $num => $answers) {
                $arrayData0[] =  $this->getAverage($this->targetData0[$display_type][$num]);
                $arrayData1[] =  $this->getMin($this->targetData1[$display_type][$num]);
                $arrayData2[] =  $this->getMax($this->targetData2[$display_type][$num]);
                $arrayData3[] =  $this->getAverage($this->targetData3[$display_type][$num]);
            }
            $this->writeTmpFile($arrayData0);
            $this->writeTmpFile($arrayData1);
            $this->writeTmpFile($arrayData2);
            $this->writeTmpFile($arrayData3);
        }
    }
    public function getMax($answers)
    {
        $max = -1;
        foreach ($answers as $a) {
            if(!$this->isValidAnswer($a))
                continue;

            if($max<$a)
                $max = $a;
        }
        if($max == -1)

            return '-';
        return $max;
    }
    public function getMin($answers)
    {
        $min = 9999999;
        foreach ($answers as $a) {
            if(!$this->isValidAnswer($a))
                continue;

            if($min>$a)
                $min = $a;
        }
        if($min == 9999999)

            return '-';
        return $min;
    }
    public function getStdev($answers)
    {
        $answers_ = array();
        foreach ($answers as $a) {
            if(!$this->isValidAnswer($a))
                continue;

            $answers_[] = $a;
        }
        if(!count($answers_))

            return '-';
        return mathStandardDeviation($answers_);
    }

    public function getAverage($answers)
    {
        $d = 0;
        $sum = 0;
        foreach ($answers as $a) {
            if(!$this->isValidAnswer($a))
                continue;
            $d++;
            $sum+=$a;
        }
        if(!$d)
            return '-';
        return $sum/$d;
    }
    public function isValidAnswer($answer)
    {
        if(!is_numeric($answer))

            return false;
        /* mysql版から数値以外は集計に含めるよう変更
        if($answer < 1)

            return false;
        if($answer == 9)

            return false;
        */

        return true;
    }

    public function getUserInfo($serial_no)
    {
        if (!is_array($this->divs)) {
            foreach (FDB::select(T_DIV,'*') as $tmp) {
                $this->divs['div1'][$tmp['div1']] = $tmp['div1_name'];
                $this->divs['div2'][$tmp['div2']] = $tmp['div2_name'];
                $this->divs['div3'][$tmp['div3']] = $tmp['div3_name'];
            }
        }
        $where = "where serial_no=".FDB::escape($serial_no);
        $column = 'uid,name,div1,div2,div3,class,sheet_type';
        $tmpData = FDB::select1(T_USER_MST, $column, $where);
        $aryData[] = $this->divs['div1'][$tmpData['div1']];
        $aryData[] = $this->divs['div2'][$tmpData['div2']];
        $aryData[] = $this->divs['div3'][$tmpData['div3']];
        $aryData[] = $tmpData['uid'];
        $aryData[] = $tmpData['name'];
        $aryData[] = $tmpData['class'];

        return $aryData;
    }

    public function getConvertChoice($c,$seid)
    {
        global $Setting;
        if (!is_array($this->chtable)) {
            $this->chtable = array();
            foreach (FDB::select(T_EVENT_SUB,'type2,chtable,seid,evid,choice') as $s) {
                if($Setting->sheetModeCollect() && $s['evid']%100 > 1)
                    continue;

                if ($s['type2'] == "c") {
                    $this->chtable[$s['seid']]=explode(',',$s['choice']);
                } else {
                    $this->chtable[$s['seid']]=explode(',',$s['chtable']);
                }
            }
        }
        /* シートをまとめる場合、上司シートの選択肢を使用 */
        if ($Setting->sheetModeCollect()) {
            ereg('^[0-9]{2}([0-9])',$seid,$match);
            if($match[1]>1)
                $seid = $seid - ($match[1]-1)*1000;
        }

        return $this->chtable[$seid][$c];
    }

    /**
     * CSVインデックス取得
     */
    public function getCsvIndexByTotalType($total_type, $obj)
    {
        $aryIndex2 = array(replaceMessage('対象者####div_name_1####'),replaceMessage('対象者####div_name_2####'),replaceMessage('対象者####div_name_3####'),'対象者ID','対象者氏名','対象者役職','評価者タイプ','タイプ','シート','回答人数');
        if (!is_zero($this->total_limit)) {
            $aryIndex2 = $this->aryIndex;
        }
        $aryIndex1 = array_pad(array(),count($aryIndex2),'');
        $this->total->setSheetType($this->sheet_type);
        foreach ($this->total->getSheetData() as $num => $data) {
            if(!$data)
                continue;
            $num_ext = '';

            if (preg_match('/_[0-9]+$/', $this->total->header_num2seid[$num], $match)) {
                $num_ext.= $match[0];
            }
            $subevent_key = preg_replace('/_[0-9]+$/', '', $this->total->header_num2seid[$num]);
            $aryIndex1[] = $this->total->subevents[$subevent_key]['num_ext'].$num_ext;
            $aryIndex2[] = $data;
        }

        return array($aryIndex1,$aryIndex2);
    }
    public function getInputerData()
    {
        $inputer = array();
        //$inputer['全員'] = array(0,1,2,3);

        foreach (range(1, INPUTER_COUNT) as $type) {
            $name = getMessage(str_replace("#", "", $GLOBALS['_360_user_type'][$type]));
            $inputer[$name] = array($type);
        }
        $inputer['他者'] = range(1, INPUTER_COUNT);
        /*
        $inputer['上司'] = array(1);
        $inputer['部下'] = array(2);
        $inputer['同僚'] = array(3);
        $inputer['他者'] = array(1,2,3);
        */

        return $inputer;
    }
}
encodeWebInAll();
session_start();
Check_AuthMng(basename(__FILE__));
$PHP_SELF = getPHP_SELF();
$SID = getSID();
ResultTotalThis::run($main =& ResultTotalThis::Create($_GET));
$main->setCsvName('(標準偏差)');
ResultTotalThis::run($view = $main->main($_POST));
print $view;
exit;

/**
 * 標準偏差
 */
function mathStandardDeviation($array)
{
    return sqrt(mathVariance($array));
}

/**
 * 分散
 */
function mathVariance($array)
{
    $average = mathAverage($array);
    $v = 0;
    foreach ($array as $n) {
        $v +=pow($n-$average,2);
    }
    if(count($array)<=1)

        return 0;
    return $v/(count($array)-1);
}

/**
 * 平均
 */
function mathAverage($array)
{
  return array_sum($array)/count($array);
}
