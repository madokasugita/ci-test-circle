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
    public $aryIndex = array('シート');

    // ページタイトル
    public function getPageTitle()
    {
        global $type_name;

        return "{$type_name[$this->sheet_type]} 集計値ダウンロード(旧)";
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
        $aryData = $this->getUserInfo($show['serial_no']);

        //集計データ下準備
        $sheetData = $this->total->getSheetData();
        $qcount = count($sheetData);
        $sheet_type = FDB::escape($this->sheet_type);
        $serial_no = FDB::escape($show['serial_no']);
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;
        $SQL=<<<SQL
SELECT a.*,c.num,c.type2,c.rows FROM {$T_EVENT_SUB_DATA} a left join {$T_EVENT_DATA} b using(event_data_id) left join {$T_EVENT_SUB} c using(seid) WHERE answer_state = 0 and (a.evid - a.evid % 100) / 100 = {$sheet_type} and target = {$serial_no};

SQL;

        //RAWデータ取得
        $this->total->answers = array();
        $this->total->c_answers = array();
        $users = array();
        foreach (FDB::getAssoc($SQL) as $key => $data) {

            $num = $this->total->seid2num[$data['seid']];
            if($num > $qcount)
                continue;
            $type = $data['evid']%100;
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

        //小項目ごとの集計を計算
        $total = array();
        foreach ($sheetData as $key => $line) {
            foreach ((array) $inputer as $k=>$v) {
                $a2 =array();
                foreach((array) $v as $type)
                    foreach((array) $this->total->answers[$key][$type] as $a)
                        $a2[] = $a;
                $total[$key][$k] = $this->getAverage($a2);

                if($this->total_type==2)
                    $aryData[] = $total[$key][$k];
            }
        }

        if($this->total_type==2)

            return $aryData;
        /** 中項目以上なら */
        $p =0;
        $array=array();
        foreach ($sheetData as $key => $line) {
            if ($line[1]) {
                $p=$key;
            }
            $array[$p][] = $key;
        }
        $total2 = array();
        foreach ($array as $t) {
            foreach ((array) $inputer as $k=>$v) {
                $a2 = array();
                foreach ($t as $num) {
                    $a2[] = $total[$num][$k];
                }
                $total2[$num][$k] = $this->getAverage($a2);
                if($this->total_type==1)
                    $aryData[] = $total2[$num][$k];
            }
        }

        /** 大項目以上なら */
        if($this->total_type==1)

            return $aryData;
        $p =0;
        $array=array();
        foreach ($sheetData as $key => $line) {
            if ($line[0]) {
                $p=$key;
            }
            $array[$p][] = $key;
        }
        $total3 = array();
        foreach ($array as $t) {
            foreach ((array) $inputer as $k=>$v) {
                $a2 = array();
                foreach ($t as $num) {
                    $a2[] = $total2[$num][$k];
                }
                $total3[$num][$k] = $this->getAverage($a2);
                $aryData[] = $total3[$num][$k];
            }
        }

        return $aryData;
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
                $this->targetData[$num][$display_type][] = $this->getAverage($a2);
            }
        }
        return null;
    }
    public function getGrandTotal()
    {
        $arrayData[] = replaceMessage($GLOBALS['_360_sheet_type'][$this->sheet_type]);

        $inputer = $this->getInputerData();
        foreach ((array) $inputer as $display_type => $types)
            $arrayData[] = array_sum($this->inputerCount[$display_type]);

        foreach ((array) $this->targetData as $num => $val) {
            foreach ($val as $display_type => $answers)
                $arrayData[] = $this->getAverage($answers);
        }
        $this->writeTmpFile($arrayData);
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
        $aryData[] = $tmpData['uid'];
        $aryData[] = $tmpData['name'];
        $aryData[] = $this->divs['div1'][$tmpData['div1']];
        $aryData[] = $this->divs['div2'][$tmpData['div2']];
        $aryData[] = $this->divs['div3'][$tmpData['div3']];
        $aryData[] = $tmpData['class'];
        $aryData[] = replaceMessage($GLOBALS['_360_sheet_type'][$tmpData['sheet_type']]);

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
        $inputer = array_keys($this->getInputerData());
        $inputer_count = count($inputer);
        $aryIndex1 = array();
        $aryIndex2 = array();
        $aryIndex3 = array();
        $aryIndex3 = array('対象者ID','対象者氏名',replaceMessage('対象者####div_name_1####'),replaceMessage('対象者####div_name_2####'),replaceMessage('対象者####div_name_3####'),'対象者役職','シート');
        if (!is_zero($this->total_limit)) {
            $aryIndex3 = $this->aryIndex;
        }
        $aryIndex1 = array_pad($aryIndex1,count($aryIndex3),'');
        $aryIndex2 = array_pad($aryIndex2,count($aryIndex3),'');

        $aryIndex1 = array_pad($aryIndex1,count($aryIndex1)+$inputer_count,'');
        $aryIndex2[] = '回答人数';
        $aryIndex2 = array_pad($aryIndex2,count($aryIndex2)+$inputer_count-1,'');
        $aryIndex3 = array_merge($aryIndex3,$inputer);
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
            $aryIndex1 = array_pad($aryIndex1,count($aryIndex1)+4,'');
            $aryIndex2 = array_pad($aryIndex2,count($aryIndex2)+4,'');
            $aryIndex3 = array_merge($aryIndex3,$inputer);
        }

        return array($aryIndex1,$aryIndex2,$aryIndex3);
    }
    public function getInputerData()
    {
        $inputer = array();
        //$inputer['全員'] = array(0,1,2,3);

        foreach (range(0, INPUTER_COUNT) as $type) {
            if($type != 0)
                $inputer['他者'][] = $type;

            $name = getMessage(str_replace("#", "", $GLOBALS['_360_user_type'][$type]));
            $inputer[$name] = array($type);
        }

        /*
        $inputer['本人'] = array(0);
        $inputer['他者'] = array(1,2,3);
        $inputer['上司'] = array(1);
        $inputer['部下'] = array(2);
        $inputer['同僚'] = array(3);
        */

        return $inputer;
    }
    public function getMainHtml($show)
    {
        global $PHP_SELF,$SID;
//■■■ HTML ■■■
        $radio = getHtmlSheetTypeRadio();

        return <<<__HTML__
<form method="POST" action="{$PHP_SELF}?{$SID}&type={$this->lfbType}" target="_blank">
<table class="cont"style="width:auto;margin:20px 30px"border="0" cellspacing="1" cellpadding="3" bgcolor="#000000">
<tr>
  <th bgcolor="#eeeeee" align="right">シート</th>
  <td bgcolor="#ffffff">{$radio}</td>
</tr>

<!--
<tr>
  <th bgcolor="#eeeeee" align="right">集計タイプ</th>
  <td bgcolor="#ffffff">{$show['total_type']}</td>
</tr>
-->

<tr>
  <th bgcolor="#eeeeee" align="right">テストユーザー</th>
  <td bgcolor="#ffffff">{$show['test_flag']}</td>
</tr>

<tr>
  <th bgcolor="#eeeeee" align="right">集計枠</th>
  <td bgcolor="#ffffff">{$show['total_limit']}</td>
</tr>

<tr>
  <th bgcolor="#eeeeee"></th>
  <td bgcolor="#ffffff" align="center">{$show['submit']}</td>
</tr>
</table>
</form>
__HTML__;
    }

}
encodeWebInAll();
session_start();
Check_AuthMng(basename(__FILE__));
$PHP_SELF = getPHP_SELF();
$SID = getSID();
ResultTotalThis::run($main =& ResultTotalThis::Create($_GET));
$main->setCsvName('(旧)');
ResultTotalThis::run($view = $main->main($_POST));
print $view;
exit;
