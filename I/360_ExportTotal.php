<?php
require_once(DIR_LIB.'ExportPage.php');
require_once(DIR_LIB.'360_Function.php');
require_once(DIR_LIB.'360_EnqueteRelace.php');

class ResultTotal extends ExportPage
{
    public function getListView($shows)
    {
        $max = count($shows);
        $aryIndexS = $this->getListIndex();
        $cntData = count($aryIndexS[0]);
        foreach($aryIndexS as $aryIndex)
            $this->writeTmpFile($aryIndex);
        foreach ($shows as $i => $show) {
            echo encodeWebOut($this->DL_Percent(round(100*$i/$max), $i, $max));
            ob_end_flush();
            $this->writeTmpFile($this->getListLine($show, $cntData));
        }
        echo encodeWebOut($this->DL_Percent(100, $max, $max));
        ob_end_flush();

        return;
    }
    public function getAllListView($shows)
    {
        $max = count($shows);
        $aryIndexS = $this->getListIndex();
        foreach($aryIndexS as $aryIndex)
            $this->writeTmpFile($aryIndex);
        foreach ($shows as $i => $show) {
            echo encodeWebOut($this->DL_Percent(round(100*$i/$max), 0, 1));
            ob_end_flush();
            $this->getLimitListLine($show);
        }
        echo encodeWebOut($this->DL_Percent(100, 1, 1));
        ob_end_flush();
        $this->getGrandTotal();

        return;
    }
    public function getListIndex()
    {
        return $this->getCsvIndexByTotalType($this->total_type, $this);
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
}

class ResultTotalCal
{
    public $isFeedback = false;
    public function enableFeedback()
    {
        $this->isFeedback = true;
    }

    public function setTargetSerial($serial_no)
    {
        $user = FDB::select1(T_USER_MST, "*", "WHERE serial_no = ".FDB::escape($serial_no));
        $this->target = $this->setDivName($user);
    }

    public function setTargetUid($uid)
    {
        $user = FDB::select1(T_USER_MST, "*", "WHERE uid = ".FDB::escape($uid));
        $this->target = $this->setDivName($user);
    }

    public function setDivName($user)
    {
        $user['div1'] = getDiv1NameById($user['div1']);
        $user['div2'] = getDiv2NameById($user['div2']);
        $user['div3'] = getDiv3NameById($user['div3']);

        return $user;
    }

    public function prepare()
    {
        //集計データ下準備
        $sheet_type = FDB::escape($this->getSheetType());
        $serial_no = FDB::escape($this->target['serial_no']);
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;

        $SQL=<<<SQL
SELECT a.* FROM {$T_EVENT_SUB_DATA} a left join {$T_EVENT_DATA} b using(event_data_id) WHERE answer_state = 0 and (a.evid - a.evid % 100) / 100 = {$sheet_type} and target = {$serial_no} order by seid;
SQL;

        $this->runTargets = FDB::getAssoc($SQL);
    }

    public function run()
    {
        //集計データ下準備
        $sheetData = $this->getSheetData();
        $qcount = count($sheetData);
        $sheet_type = FDB::escape($this->getSheetType());
        $serial_no = FDB::escape($this->target['serial_no']);
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;
        $seids = array();
        foreach ($this->runTargets as $v) {
            $seids[] = FDB::escape($v['seid']);
        }
        $seids = implode(',', $seids);

        $SQL=<<<SQL
SELECT c.seid, c.num,c.type2,c.choice as subevent_choice,c.rows FROM {$T_EVENT_SUB} c WHERE seid IN ({$seids}) order by seid;
SQL;

        $tmp = array();
        foreach (FDB::getAssoc($SQL) as $v) {
            $tmp[$v['seid']] = $v;
        }
        $lists = array();
        foreach ($this->runTargets as $data) {
            $lists[] = array_merge($data, $tmp[$data['seid']]);
        }

//         $SQL=<<<SQL
// SELECT a.*,c.num,c.type2,c.choice as subevent_choice,c.rows FROM {$T_EVENT_SUB_DATA} a left join {$T_EVENT_DATA} b using(event_data_id) left join {$T_EVENT_SUB} c using(seid) WHERE answer_state = 0 and (a.evid - a.evid % 100) / 100 = {$sheet_type} and target = {$serial_no} order by seid;
// SQL;

        //RAWデータ取得
        $this->answers = array();
        $this->c_answers = array();
        $this->users = array();
        $comments = array();
//         foreach (FDB::getAssoc($SQL) as $data) {
        foreach ($lists as $data) {
            $type = $data['evid']%100;
            $num = $this->seid2num[$data['seid']];
            if ($data['type2'] == 't' && $data['rows']>1) {
                $comments[$type][$num][] = $data['other'];
            }

            $ans = $data['other']?$data['other'] : $this->getConvertChoice($data['choice'],$data['seid']);
            $this->putInAnswers($data,$num,$type,$ans);
            $this->users[$type][$data['serial_no']]=true;
        }

        // 複数選択のデータをCSV用に入替え
        $this->changeCheckboxAnswer();

        $this->count = array();
        $this->MaxData = array();
        $this->MinData = array();
        $this->StdevData   = array();
        $this->BaratukiData = array();
        $this->ary_comment = array();
        $this->ary_category = array();
        foreach ((array) $this->getInputerData() as $k=>$v) {
            $category1List = array();
            $category2Data = array();

            foreach($v as $i)
                $this->count[$k] += count($this->users[$i]);

            foreach ($sheetData as $num=>$line) {
                $a2 =array();
                foreach((array) $v as $type)
                    foreach((array) $this->answers[$num][$type] as $a)
                        $a2[] = $a;

                $ave = $this->getAverage($a2);
                $this->average[$k][$num] = $ave;

                if (is_good($ave)) {
                    $seid = $this->header_num2seid[$num];
                    $c1 = $this->subevents[$seid]['category1'];
                    $c2 = $this->subevents[$seid]['category2'];
                    if (!is_zero($c1) && !is_zero($c2)) {
                        $category1List[$c1][$c2] = 1;
                    }
                    if (!is_zero($c2)) {
                        $category2Data[$c2][] = $ave;
                    }
                }
                $this->MaxData[$k][$num] = $this->getMax($a2);
                $this->MinData[$k][$num] = $this->getMin($a2);
                $this->StdevData[$k][$num]   = $this->getStdev($a2);
                $this->BaratukiData[$k][$num] = $this->getBaratuki($a2);
            }

            foreach ($this->comments as $num => $title) {
                foreach ((array) $v as $type) {
                    if (is_good($comments[$type]) && is_good($comments[$type][$num])) {
                        foreach ($comments[$type][$num] as $cm2) {
                            $this->ary_comment[$k][$num][] = $cm2;
                        }
                    }
                }
            }

            ksort($category1List);
            ksort($category2Data);
            foreach ($this->category1 as $c1) {
                $aryc2 = $category1List[$c1];
                if (is_void($aryc2)) {
                    $this->ary_category[1][$k][$c1] = null;
                    continue;
                }

                $category1Data = array();
                foreach ($aryc2 as $c2=>$d) {
                    $category1Data[] = $this->array_average($category2Data[$c2]);
                }
                $ave = $this->array_average($category1Data);
                $this->ary_category[1][$k][$c1] = $ave;
            }
            foreach ($this->category2 as $c2) {
                $data = $category2Data[$c2];
                if (is_void($data)) {
                    $this->ary_category[2][$k][$c2] = null;
                    continue;
                }

                $ave = $this->array_average($data);
                $this->ary_category[2][$k][$c2] = $ave;
            }
        }

        return $this->target;
    }
    public function limitRun()
    {
        // 回答データ取得
        $subevent_data = $this->getSubeventDataBySerial($this->target['serial_no']);

        $this->answers = array();
        $this->c_answers = array();
        $this->target = array();
        $inputer = $this->getInputerData();
        $sheetData = $this->getSheetData();

        //RAWデータ取得
        foreach ($subevent_data as $data) {
            $type = $data['evid']%100;
            $num = $this->seid2num[$data['seid']];
            $ans = $this->getConvertChoice($data['choice'],$data['seid']);
            $this->putInAnswers($data,$num,$type,$ans);
        }

        // 複数選択のデータをCSV用に入替え
        $this->changeCheckboxAnswer();

        foreach ((array) $this->getInputerData() as $display_type => $types) {
            $category1List = array();
            $category2Data = array();

            $this->inputerCount[$display_type][] = $this->countInputer($types);

            foreach ($sheetData as $num=>$line) {
                $a2 =array();
                foreach((array) $types as $type)
                    foreach((array) $this->answers[$num][$type] as $a)
                        $a2[] = $a;
                $ave = $this->getAverage($a2);
                $this->targetData[$display_type][$num][] = $ave;

                $this->MaxData[$display_type][$num][] = $this->getMax($a2);
                $this->MinData[$display_type][$num][] = $this->getMin($a2);
                $this->BaratukiData[$display_type][$num] = array_merge((array)$this->BaratukiData[$display_type][$num], $a2);
            }
        }
    }
    public function divideCategory()
    {
        foreach ($this->average as $display_type => $value) {
            $category1List = array();
            $category2Data = array();

            foreach ($value as $num => $ave) {
                if (is_good($ave)) {
                    $seid = $this->header_num2seid[$num];
                    $c1 = $this->subevents[$seid]['category1'];
                    $c2 = $this->subevents[$seid]['category2'];
                    if (!is_zero($c1) && !is_zero($c2)) {
                        $category1List[$c1][$c2] = 1;
                    }
                    if (!is_zero($c2)) {
                        $category2Data[$c2][] = $ave;
                    }
                }
            }
            ksort($category1List);
            ksort($category2Data);
            foreach ($this->category1 as $c1) {
                $aryc2 = $category1List[$c1];
                if (is_void($aryc2)) {
                    $this->ary_category[1][$display_type][$c1] = null;
                    continue;
                }

                $category1Data = array();
                foreach ($aryc2 as $c2=>$d) {
                    $category1Data[] = $this->array_average($category2Data[$c2]);
                }
                $ave = $this->array_average($category1Data);
                $this->ary_category[1][$display_type][$c1] = $ave;
            }
            foreach ($this->category2 as $c2) {
                $data = $category2Data[$c2];
                if (is_void($data)) {
                    $this->ary_category[2][$display_type][$c2] = null;
                    continue;
                }
                $ave = $this->array_average($data);
                $this->ary_category[2][$display_type][$c2] = $ave;
            }
        }
    }
    public function getSubeventDataBySerial($serial_no)
    {
        $serial_no = FDB::escape($serial_no);
        $sheet_type = FDB::escape($this->sheet_type);
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;
        $SQL=<<<SQL
SELECT a.evid, a.serial_no, a.seid, a.choice, a.event_data_id, c.num, c.type2, c.rows
FROM {$T_EVENT_SUB_DATA} a
LEFT JOIN {$T_EVENT_DATA} b USING(event_data_id)
LEFT JOIN {$T_EVENT_SUB} c USING(seid)
WHERE answer_state = 0
AND (a.evid - a.evid % 100) / 100 = {$sheet_type}
AND target = {$serial_no}
AND a.choice > -1
ORDER BY seid;
SQL;
        return FDB::getAssoc($SQL);
    }
    public function setSheetType($sheet_type)
    {
        $this->sheet_type = $sheet_type;
    }

    public function getSheetType()
    {
        return (is_good($this->sheet_type))? $this->sheet_type:$this->target['sheet_type'];
    }

    public function getUserInfo()
    {
        $column = array('uid', 'div1', 'div2', 'div3', 'name', 'class');
        $tmpData = array();
        foreach ($column as $c)
            $tmpData[$c] = $this->target[$c];

        return $tmpData;
    }
    public function getSheetData()
    {
        global $Setting;
        if($this->SheetData)

            return $this->SheetData;
        //一番質問数が多いeventを基準とする
        $max = 0;
        $evid = $this->getSheetType()*100;
        foreach (FDB::getAssoc('select count(*) as seid_count,evid from subevent where evid-evid%100 = '.FDB::escape($evid).' group by evid') as $event) {
            if($Setting->sheetModeCollect() && $event['evid']%100 > 1)
                continue;

            if ($max<$event['seid_count']) {
                $max = $event['seid_count'];
                $evid = $event['evid'];
            }
        }
        define('ENQ_RID',getRidByEvid($evid));
        prepareEnqMatrix();
        $c1 = $c2 = array();
        foreach (FDB::select(T_EVENT_SUB, '*', "WHERE (evid - evid % 100) / 100 = {$this->sheet_type} ORDER BY num") as $subevent) {
            if($Setting->sheetModeCollect() && $subevent['evid']%100 > 1)
                continue;

            $this->subevents[$subevent['seid']] = $subevent;
            $this->subevents[$subevent['seid']]['chtable_'] = explode(',',$subevent['chtable']);

            if ($subevent['type2'] != 'r' && $subevent['type2'] != 'p' && $subevent['type2'] != 'c')
                continue;

            if($subevent['category1'] !== 0 && is_good($subevent['category1']))	$c1[] = $subevent['category1'];
            if($subevent['category2'] !== 0 && is_good($subevent['category2']))	$c2[] = $subevent['category2'];
        }
        $this->category1 = array_unique($c1);
        //asort($this->category1);
        $this->category2 = array_unique($c2);
        //asort($this->category2);
        $flag = false;
        foreach (FDB::getAssoc("select seid,(select count(*) as count from subevent b where a.evid = b.evid and a.seid > b.seid) as new_num,num from subevent a where a.type2 <> 'n' order by a.seid;") as $subevent) {
            if($Setting->sheetModeCollect() && $subevent['evid']%100 > 1)
                continue;

            if($subevent['num'])
                $flag = true;
            $seid2num[$subevent['seid']] = $subevent['num'];
            $seid2num_[$subevent['seid']] = $subevent['new_num'];

            if ($Setting->sheetModeCollect() && $subevent['evid']%100 == 1) {
                foreach (range(2,INPUTER_COUNT) as $i) {
                    $_seid = round($subevent['evid']/100)*100000+$i*1000+round($subevent['seid']%1000);
                    $seid2num[$_seid] = $subevent['num'];
                    $seid2num_[$_seid] = $subevent['new_num'];
                }
            }
        }
        $this->seid2num = $flag ? $seid2num:$seid2num_;

        $TSV = array();
        $title = "";
        foreach (FDB::select(T_EVENT_SUB,'*',"where evid = {$evid} order by seid") as $subevent) {
            $num = $this->seid2num[$subevent['seid']];
            $this->header_seid2num[$subevent['seid']] = $num;

            if ($subevent['type2'] == 't' && $subevent['rows'] >1) {
                $this->header_seid2num[$subevent['seid']] = $num;
                $this->comments[$num] = preg_replace_callback("/####enqmatrix(.*?)_(.*?)####/i", "ENQ_RepalaceMatrix", $subevent['title']);
                //$this->header_comments[$subevent['seid']] = $num;
            }
            if ($subevent['type2'] != 'r' && $subevent['type2'] != 'p' && $subevent['type2'] != 'c') {
                $this->header_seid2num[$subevent['seid']] = $num;
                continue;
            }

            if ($subevent['type2'] == 'c') {
                $choices = explode(',',$subevent['choice']);
                $i = 1;
                foreach ($choices as $choice) {
                    $title = preg_replace_callback("/####enqmatrix(.*?)_(.*?)####/i", "ENQ_RepalaceMatrix_Strip", $subevent['title']);
                    $TSV[$num."_".(string) sprintf("%04d", $i)] = strip_tags($title);
                    $this->header_seid2num[$subevent['seid']."_".(string) $i] = $num."_".(string) $i;
                    $i++;
                }
            } elseif ($subevent['type2'] == 'r' || $subevent['type2'] == 'p') {
                $this->header_seid2num[$subevent['seid']] = $num;
                $title = preg_replace_callback("/####enqmatrix(.*?)_(.*?)####/i", "ENQ_RepalaceMatrix_Strip", $subevent['title']);
                $TSV[$num]= strip_tags($title);
            }
        }
        $header_seid2num = $this->header_seid2num;
        foreach ($header_seid2num as $key => $value) {
            if (is_null($value)) {
                unset($header_seid2num[$key]);
            }
        }
        $this->header_num2seid = array_flip($header_seid2num);
        ksort($this->comments);
        ksort($TSV);

        $array = array();
        foreach ($TSV as $num=>$line) {
            if(!$line)
                continue;
            $line = str_replace("\r","",$line);
            $num = preg_replace("/_0+/u", "_", $num);
            $array[$num] = $line;
        }

        return $this->SheetData = $array;
    }

    public function countInputer($types)
    {
        //評価者の数をカウント
        $count = 0;
        foreach((array) $types as $type)
        {
            if (is_array($this->answers[1][$type]))
                $count +=count($this->answers[1][$type]);
        }
        return $count;
    }

    public function putInAnswers($data,$num,$type,$ans)
    {
        // 複数選択とそれ以外で配列の形を変える
        if ($data['type2'] == 'c' && $data['rows']>0)
        {
            $this->c_answers[$num] = $data['seid'];
            $this->answers[$num][$type][$data['event_data_id']][] = $ans;
        }
        else
        {
            $this->c_answers[$num] = false;
            $this->answers[$num][$type][] = $ans;
        }
        return;
    }

    public function changeCheckboxAnswer()
    {
        // $subData : seidでT_EVENT_SUBをリスト化した配列
        $subDatas = FDB::select(T_EVENT_SUB,'type2,seid,evid,choice');
        foreach ($subDatas as $val)
        {
            $subData[$val['seid']] = $val;
        }
        // 複数選択を選択肢ごとに配列に入れなおす
        foreach ($this->answers as $num => $types)
        {
            if (!$this->c_answers[$num])
            {
              continue;
            }
            $answersS = array();
            $ansData = explode(',',$subData[$this->c_answers[$num]]['choice']);
            foreach ((array) $types as $type => $event_datas)
            {
                foreach ((array) $event_datas as $event_data_id => $one_usr_choice)
                {
                    foreach ((array) $ansData as $key => $choice)
                    {
                        $c_num = $key+1;
                        if (in_array($choice, (array) $one_usr_choice))
                            $this->answers[$num.'_'.$c_num][$type][] = "1";
                        else
                            $this->answers[$num.'_'.$c_num][$type][] = "0";
                    }
                }
            }
            unset($this->answers[$num]);
        }
        return;
    }

    public function getInputerData()
    {
        if(is_good($this->inputer)) return $this->inputer;

        $inputer = array();
        //$inputer['全員'] = array(0,1,2,3);

        foreach (range(0, INPUTER_COUNT) as $type) {
            if($type != 0)
                $inputer['others'][] = $type;

            $inputer[$type] = array($type);
        }

        /*
            $inputer['本人'] = array(0);
        $inputer['他者'] = array(1,2,3);
        $inputer['上司'] = array(1);
        $inputer['部下'] = array(2);
        $inputer['同僚'] = array(3);
        */

        return $this->inputer = $inputer;
    }

    public function getAverageArray($type, $null="-", $adjust=false)
    {
        $res = array();
        foreach ($this->average[$type] as $a) {
            if(is_void($a)) $a = $null;
            if($adjust) $a = $this->getAdjustValue($a);
            $res[] = $a;
        }

        return $res;
    }

    public function getCategoryArray($type, $category, $null="-", $adjust=false)
    {
        $res = array();
        foreach ($this->ary_category[$category][$type] as $c => $a) {
            if(is_void($a)) $a = $null;
            if($adjust) $a = $this->getAdjustValue($a);
            $res[] = $a;
        }

        return $res;
    }

    public function getCommentJoinArray($type)
    {
        $res = array();
        foreach ($this->comments as $num => $title) {
            if (is_good($this->ary_comment[$type]) && is_good($this->ary_comment[$type][$num])) {
                $comments = $this->ary_comment[$type][$num];
                @shuffle($comments);
                $res[] = implode("\n\n", $comments);
            } else {
                $res[] = "";
            }
        }

        return $res;
    }

    public function getAdjustValue($ave)
    {
        return ($GLOBALS['Setting']->adjustValueLessOrEqual($ave)) ? $ave+($ave-RESULT_TOTAL_2_BAR_GRAPH_ADJUST_VALUE) : $ave;
    }

    public function getMax($array)
    {
        if(!is_good($array))

            return "";
        $tmp = array();
        foreach ($array as $v) {
            if(!is_numeric($v))	//chtable変換後の数値以外は対象外
                continue;
            $tmp[] = $v;
        }

        return (is_good($tmp))? max($tmp):"";
    }
    public function getMin($array)
    {
        if(!is_good($array))

            return "";
        $tmp = array();
        foreach ($array as $v) {
            if(!is_numeric($v))	//chtable変換後の数値以外は対象外
                continue;
            $tmp[] = $v;
        }

        return (is_good($tmp))? min($tmp):"";
    }

    public $baratuki = array('max'=>3, 'min'=>1);
    public function getBaratuki($array)
    {
        if(is_void($max = $this->getMax($array)) || is_void($min = $this->getMin($array)))

            return "";
        $diff = $max - $min;
        switch (true) {
            case ($diff >= $this->baratuki['max']):
                return "+";
            case ($this->baratuki['min'] > $diff):
                return "-";
            default:
                return "";
        }
    }

    public function getAverage($answers)
    {
        $d = array();
        foreach ($answers as $a) {
            if(!$this->isValidAnswer($a))
                continue;
            $d[] = $a;
        }
        if(is_void($d)) return null;

        return $this->array_average($d);
    }

    public function array_average($data)
    {
        return array_sum($data)/count($data);
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

        $ret = $this->mathStandardDeviation($answers_);
        return round($ret, 2);
    }

    /**
     * 標準偏差
     */
    public function mathStandardDeviation($array)
    {
        return sqrt($this->mathVariance($array));
    }

    /**
     * 分散
     */
    public function mathVariance($array)
    {
        $average = $this->mathAverage($array);
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
    public function mathAverage($array)
    {
        return array_sum($array)/count($array);
    }
}

class ResultSecularTotalCal extends ResultTotalCal
{

    public function prepare()
    {
        //集計データ下準備
        $sheet_type = FDB::escape($this->getSheetType());
        $serial_no = FDB::escape($this->target['serial_no']);
        $user_type = FDB::escape($this->userType);
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;

        $userTypeWhere = ($this->userType == "others") ? " " : "and (a.evid % 100) = {$user_type}";

        $SQL=<<<SQL
SELECT a.* FROM {$T_EVENT_SUB_DATA} a left join {$T_EVENT_DATA} b using(event_data_id) WHERE answer_state = 0 {$userTypeWhere} and (a.evid - a.evid % 100) / 100 = {$sheet_type} and target = {$serial_no} order by seid;
SQL;

        $this->runTargets = FDB::getAssoc($SQL);
    }

    public function setUserType($userType)
    {
        $this->userType = $userType;
    }

    public function getInputerData()
    {
        if(is_good($this->inputer)) return $this->inputer;
        $inputer = array();
        foreach (range(0, INPUTER_COUNT) as $type) {
            if ($this->userType === 'others') {
                 if ($type !== 0) {
                    $inputer['others'][] = $type;
                 }
            } else {
                if ($this->userType == $type) {
                    $inputer[$type] = array($type);
                }
            }
        }
        return $this->inputer = $inputer;
    }
}
