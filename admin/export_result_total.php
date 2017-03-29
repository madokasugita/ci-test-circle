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
    function &create($prm)
    {
        $instance = new ResultTotalThis();
        if (FError::is($err = $instance->initialize ($prm))) {
            return $err;
        }

        return $instance;
    }
    public function getListLine($show, $cntData)
    {
        //ユーザ情報を出力データに追加
        $aryData = $this->getUserInfo($show['serial_no']);

        //集計データ下準備
        $sheetData = $this->getSheetData();
        $qcount = count($sheetData);
        $sheet_type = FDB::escape($this->sheet_type);
        $serial_no = FDB::escape($show['serial_no']);
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;
        $SQL=<<<SQL
SELECT a.*,c.num FROM {$T_EVENT_SUB_DATA} a left join {$T_EVENT_DATA} b using(event_data_id) left join {$T_EVENT_SUB} c using(seid) WHERE answer_state = 0 and (a.evid - a.evid % 100) / 100 = {$sheet_type} and target = {$serial_no};

SQL;

        //RAWデータ取得
        $answers = array();
        $users = array();
        foreach (FDB::getAssoc($SQL) as $data) {

            $num = $this->seid2num[$data['seid']];
            if($num > $qcount)
                continue;
            $type = $data['evid']%100;
            $ans = $data['other']?$data['other'] : $this->getConvertChoice($data['choice'],$data['seid']);
            $answers[$num][$type][] = $ans;
            $users[$type][$data['serial_no']]=true;
        }

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
        $num=1;
        foreach ($sheetData as $line) {
            foreach ((array) $inputer as $k=>$v) {
                $a2 =array();
                foreach((array) $v as $type)
                    foreach((array) $answers[$num][$type] as $a)
                        $a2[] = $a;
                $total[$num][$k] = $this->getAverage($a2);

                if($this->total_type==2)
                    $aryData[] = $total[$num][$k];
            }
            $num++;
        }

        if($this->total_type==2)

            return $aryData;
        /** 中項目以上なら */
        $num=1;
        $p =0;
        $array=array();
        foreach ($sheetData as $line) {
            if ($line[1]) {
                $p=$num;
            }
            $array[$p][] = $num;
            $num++;
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
        $num=1;
        $p =0;
        $array=array();
        foreach ($sheetData as $line) {
            if ($line[0]) {
                $p=$num;
            }
            $array[$p][] = $num;
            $num++;
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
        if($answer < 1)

            return false;
        if($answer == 9)

            return false;
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
        $column = 'uid,name,div1,div2,div3,memo,sheet_type';
        $tmpData = FDB::select1(T_USER_MST, $column, $where);
        $aryData[] = $tmpData['uid'];
        $aryData[] = $tmpData['name'];
        $aryData[] = $this->divs['div1'][$tmpData['div1']];
        $aryData[] = $this->divs['div2'][$tmpData['div2']];
        $aryData[] = $this->divs['div3'][$tmpData['div3']];
        $aryData[] = $tmpData['memo'];
        $aryData[] = replaceMessage($GLOBALS['_360_sheet_type'][$tmpData['sheet_type']]);

        return $aryData;
    }
    public function getConvertChoice($c,$seid)
    {
        if (!is_array($this->chtable)) {
            $this->chtable = array();
            foreach (FDB::select(T_EVENT_SUB,'chtable,seid') as $s) {
                $this->chtable[$s['seid']]=explode(',',$s['chtable']);
    }
        }
        ereg('^[0-9]{2}([0-9])',$seid,$match);
        if($match[1]>1)
            $seid = $seid - ($match[1]-1)*1000;

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
        $aryIndex1 = array_pad($aryIndex1,7,'');
        $aryIndex2 = array_pad($aryIndex2,7,'');
        $aryIndex3[] = "対象者ID";
        $aryIndex3[] = "対象者氏名";
        $aryIndex3[] = "対象者所属1";
        $aryIndex3[] = "対象者所属2";
        $aryIndex3[] = "対象者所属3";
        $aryIndex3[] = "役職";
        $aryIndex3[] = "シートタイプ";

        $aryIndex1 = array_pad($aryIndex1,count($aryIndex1)+$inputer_count,'');
        $aryIndex2[] = '回答人数';
        $aryIndex2 = array_pad($aryIndex2,count($aryIndex2)+$inputer_count-1,'');
        $aryIndex3 = array_merge($aryIndex3,$inputer);
        $i=1;
        foreach ($this->getSheetData() as $data) {
            $t = $data[$total_type];
            if(!$t)
                continue;
            $aryIndex1[] = $i;
            $aryIndex2[] = $t;
            $aryIndex1 = array_pad($aryIndex1,count($aryIndex1)+4,'');
            $aryIndex2 = array_pad($aryIndex2,count($aryIndex2)+4,'');
            $aryIndex3 = array_merge($aryIndex3,$inputer);
            $i++;
        }

        return array($aryIndex1,$aryIndex2,$aryIndex3);
    }
    public function getSheetData()
    {
        $evid = $this->sheet_type*100+1;
        define('ENQ_RID',getRidByEvid($evid));
        prepareEnqMatrix();
        foreach (FDB::select(T_EVENT_SUB,'*') as $subevent) {
            $this->subevents[$subevent['seid']] = $subevent;
            $this->subevents[$subevent['seid']]['chtable_'] = explode(',',$subevent['chtable']);
        }
        $flag = false;
        foreach (FDB::getAssoc("select seid,(select count(*) as count from subevent b where a.evid = b.evid and a.seid > b.seid) as new_num,num from subevent a where a.type2 <> 'n' order by a.seid;") as $subevent) {
            if($subevent['num'])
                $flag = true;
            $seid2num[$subevent['seid']] = $subevent['num'];
            $seid2num_[$subevent['seid']] = $subevent['new_num'];
        }
        $this->seid2num = $flag ? $seid2num:$seid2num_;
        foreach (FDB::select(T_EVENT_SUB,'*',"where evid = {$evid} order by seid") as $subevent) {
            if ($subevent['type2'] != 'r' && $subevent['type2'] != 'p') {
                continue;
            }

            $TSV.="{$subevent['title']}	{$subevent['title']}	{$subevent['title']}\n";
        }
        $TSV = preg_replace_callback("/####enqmatrix(.*?)_(.*?)####/i", "ENQ_RepalaceMatrix", $TSV);
        $TSV = strip_tags($TSV);
        $array = array();
        foreach (explode("\n",$TSV) as $line) {
            if(!$line)
                continue;
            $line = str_replace("\r","",$line);
            $array[] = explode("\t",$line);
        }

        return $array;
    }
    public function getInputerData()
    {
        $inputer = array();
        //$inputer['全員'] = array(0,1,2,3);
        $inputer['本人'] = array(0);
        $inputer['他者'] = array(1,2,3);
        $inputer['上司'] = array(1);
        $inputer['部下'] = array(2);
        $inputer['同僚'] = array(3);

        return $inputer;
    }

}
encodeWebInAll();
session_start();
Check_AuthMng(basename(__FILE__));
$PHP_SELF = getPHP_SELF();
$SID = getSID();
ResultTotalThis::run($main =& ResultTotalThis::Create($_GET));
ResultTotalThis::run($view = $main->main($_POST));
print $view;
exit;
