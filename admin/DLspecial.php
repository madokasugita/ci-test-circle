<?php
define('DIR_ROOT', '../');
require_once 'cbase/crm_define3.php';
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'func_rtnclm.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360_EnqueteRelace.php');
encodeWebInAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));
//$evid = Check_AuthMngEvid($_GET['evid']);

/******************************************************************************************************************/
$SID= getSID();

/** ファイル名の接頭語 */
define("PRI_DL", "reseDL_");

/******************************************************************************************************************/
mb_http_output("pass");
ob_implicit_flush(true);
ob_end_flush();
/*******************************************************************************************************/
$html= getHeader(DEFAULT_CHARSET);
$html .=<<<HTML
<p align="left" style="font-size:15px;">
ダウンロード用ファイルを作成しています。しばらくお待ちください。<br>
ファイルの作成完了後、ダウンロード用リンクが表示されます。
</p>

<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9312;データ処理中</b><br>
人数分のデータを処理しています。
</p>

<style>
.graph {
    position: relative;
    width: 400px;
    border: 1px solid #F17662;
    padding: 2px;
}
.graph .bar {
    display: block;
    position: relative;
    background: #F17662;
}
</style>
<script>
function p(p,i,m)
{
    document.getElementById('table1').style.display="";
    document.getElementById('percent').innerHTML = p + '%' + '('+i+'/'+m+')';
    document.getElementById('bar').style.width = p + '%';
}
</script>
<table id="table1" align="left">
<tr><td class="graph"><span class="bar" style="width: 0%;" id="bar"><br></span></td><td id="percent"></td></tr>
</table>
<br>
<br>
HTML;
print encodeWebOut($html);
ob_end_flush();
/*******************************************************************************************************/
$filename = createCSV();
/*******************************************************************************************************/
//ダウンロードリンク出力

$hash = md5($filename.SYSTEM_RANDOM_STRING);

$html=<<<HTML
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9314;ファイル作成完了</b><br>
ファイルの作成が完了しました。
<br><br>
<a style="font-weight:bold; "href="DLdata2.php?file={$filename}&hash={$hash}&{$SID}&param={$param}&evid={$evid}">ダウンロードする</a>
</p>
HTML;
print encodeWebOut($html);
ob_end_flush();
exit;
/*******************************************************************************************************/


function createCSV()
{
    global $evid,$param,$order;
    $evid = (int) $_POST['sheet_type'];
    $mode = (int) $_POST['answer_state'];
    $order = (int) $_POST['order'];
    $test_flag = (int) $_POST['test_flag'];

    foreach ($_POST['user_type'] as $usert_type) {
        $param .=((int) $usert_type).',';
    }
    
    foreach ($_POST['replace_type'] as $replace_type) {
        $replace_param .=((int) $replace_type).',';
    }
    $filename = getFileName($evid);
    $muid = $_SESSION['muid'];
    $params = array($evid, $param, $mode, $filename, $muid, $order, $test_flag, $replace_param);
    createDownloadFile($params);
    if (!empty($cmd)) {
        passthru($cmd);
    }

    return $filename;
}


function getFileName($evid)
{
    $i=0;
    do {
        $filename= PRI_DL. substr(md5(DIR_MAIN . DIR_MAIN), 0, 5) .str_replace('/','',DIR_MAIN).$evid . md5(microtime()). ".tsv";
        if ($i>100) {
            print "ERROR: FileError;";
            exit;
        }
    } while (is_file(DIR_TMP.$filename));
    touch(DIR_TMP.$filename);
    gc(DIR_TMP.PRI_DL.substr(md5(DIR_MAIN . DIR_MAIN), 0, 5) .str_replace('/','',DIR_MAIN));

    return $filename;
}

/**
 * 24時間以上前に生成されたファイルは削除する。
 */
function gc($p)
{
    foreach (glob($p.'*') as $file) {
        if (time()-fileatime($file)>3600 * 24) {
            unlink($file);
        }
    }
}

function getHeader($encode="EUC-JP")
{
        $DIR_IMG = DIR_IMG;
        $title = "評価Rawデータダウンロード";

    $html=<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset={$encode}">
<title>{$title}</title>
<link rel="stylesheet" href="{$DIR_IMG}360_adminpage.css" type="text/css">
<script src="{$DIR_IMG}common.js" type="text/javascript"></script>
<script src="{$DIR_IMG}myconfirm.js" type="text/javascript"></script>
<style type="text/css">
td{
    font-size:13px;
}
.state0{
    background-color:#ffb0b0;
}
.state1{
    background-color:#ffd0d0;
    filter: progid:DXImageTransform.Microsoft.gradient(startcolorstr=#ffb0b0, endcolorstr=#ffffff, gradienttype=1);
}
.state2{
    background-color:#ffffff;
}
.state9{
    background-color:#999999;
}
</style>
</head>
<body>
<div id="container-iframe">
<div id="main-iframe">
<h1>{$title}</h1>
HTML;

    return $html;
}

function createDownloadFile($params)
{
    list($evid, $param, $mode, $filename, $muid, $order, $test_flag, $replace_type) = $params;
    $ignore_columun = array('email','pw','serial_no','evid','upload_id','note','mflag','sheet_type','pwmisscount');
    $evid = $evid*100;
    /** 一時ファイル */
    define('TMP_CSVFILE', DIR_TMP . $filename);
    /** 1=>完全回答のみ 2=>途中保存のみ 3=>両方 */
    define('DL_MODE', $mode);
    /** 1=>対象者の所属 2=>対象者のID */
    define('DL_ORDER', $order);
    /** 記入回答置換タイプ */
    define('REPLACE_TYPE', $replace_type);
    
    /*******************************************************************************************************/
    $user_types = array();
    foreach (explode(',', preg_replace('/,$/', '', $param)) as $v) {
        if (SHEET_MODE ==1 && $v > 1) {
            if (!in_array("'1'", $user_types)) {
                $user_types[] = FDB::escape('1');
            }
            continue;
        }
        $user_types[] = FDB::escape($v);
    };

    //一番質問数が多いeventを基準とする
    foreach (FDB::getAssoc('select count(*) as seid_count,evid from subevent where evid-evid%100 = '.FDB::escape($evid).' AND SUBSTRING(evid, -1) IN ('.implode(',', $user_types).') group by evid') as $event) {
        if(SHEET_MODE ==1 && $event['evid']%100 > 1)
            continue;

        if ($max<$event['seid_count']) {
            $max = $event['seid_count'];
            $header_evid = $event['evid'];
        }
    }

    $array = Get_Enquete('id', $header_evid, '', '');
    //seidがキーになるように変換
    $subevents = array ();

    define('ENQ_RID',$array[-1]['rid']);

    //seidをnumに変換するための配列を作成<--
    $flag = false;
    $_subevents = array();
    foreach (FDB::getAssoc("select evid,seid,chtable,(select count(*) as count from subevent b where a.evid = b.evid and a.seid > b.seid) as new_num,num from subevent a where a.type2 <> 'n' order by a.seid;") as $_tmp) {
        if(SHEET_MODE ==1 && $_tmp['evid']%100 > 1)
            continue;

        $_subevents[$_tmp['seid']] = $_tmp;

        if (SHEET_MODE ==1 && $_tmp['evid']%100 == 1) {
            foreach (range(2,INPUTER_COUNT) as $i) {
                $_seid = round($_tmp['evid']/100)*100000+$i*1000+round($_tmp['seid']%1000);
                $_evid = round($_tmp['evid']/100)*100+$i;
                $_tmp['seid'] = $_seid;
                $_tmp['evid'] = $_evid;
                $_subevents[$_seid] = $_tmp;
            }
        }
    }

    foreach ($_subevents as $subevent) {
        if($subevent['num'])
            $flag = true;

        $seid2num[$subevent['seid']] = $subevent['num'];
        $seid2num_[$subevent['seid']] = $subevent['new_num'];
    }
    $seid2num = $flag ? $seid2num:$seid2num_;
    /* chtable変換用に_subeventを残す */
    // unset($_subevents);
    //seidをnumに変換するための配列を作成-->

    foreach ($array[0] as $tmp_) {
        if ($tmp_['type2'] == 'n')
            continue;
        $subevents[$seid2num[$tmp_['seid']]] = $tmp_;
    }
    unset ($tmp_);
    ksort($subevents);
    //クローズアンケートならフラグを立てる(ユーザマスタも出力するため)
    if (!isOpenEnqueteByFlgo($array[-1]['flgo'])) {
        define('CLOSE_FLG', true);
    } else {
        define('CLOSE_FLG', false);
    }
    unset ($array);

    @ unlink(TMP_CSVFILE);
    insertIndexLine($subevents, $ignore_columun); //インデックス行をCSVに書き出す。
    insertDataLines($evid, $param, $muid, $subevents, $_subevents, $seid2num, $ignore_columun, $test_flag); //回答データをCSVに書き出す。

    syncCopy(TMP_CSVFILE); //クラスタリング対応

    return;
}

/**
 * 回答データをCSVに書き出す(まずはevent_dataから対象の回答を取得する。)
 */
function insertDataLines($evid, $param, $muid, $subevents, $_subevents, $seid2num, $ignore_columun, $test_flag)
{
    list($option, $order_by) = getSqlOption();

    //回答完了時間(udate)でソートして回答データを取得
//	$event_datas = FDB :: select(T_EVENT_DATA, '*', "where evid = {$evid} {$option} order by udate desc");
    $T_EVENT_DATA = T_EVENT_DATA;
    $T_USER_MST = T_USER_MST;

    setDivAuth($muid);

    $evids = array();
    foreach (explode(',', $param) as $user_type) {
        if($user_type!='')
            $evids[] =(int) ($evid+$user_type);
    }
    $evids = implode(',',$evids);

    $DivWhere = getDivWhere('B');

    $SQL=<<<SQL
SELECT Event.*, Answer.uid AS ans_uid, Answer.name AS ans_name, Answer.class AS ans_class, Target.uid AS tar_uid, Target.name AS tar_name, Target.class AS tar_class, Target.div1, Target.div2, Target.div3
 FROM {$T_EVENT_DATA} AS Event
 LEFT JOIN {$T_USER_MST} AS Answer using(serial_no)
 LEFT JOIN {$T_USER_MST} AS Target ON Event.target = Target.serial_no
 WHERE {$DivWhere}
 and Event.evid in({$evids}) {$option}
SQL;
    if ($test_flag != 1) {
        $test_flag_sql = ($test_flag == 2) ? " = 1" : " != 1";
        $test_flag_sql_conjunction = ($test_flag == 2) ? " OR " : " AND ";
        $SQL.=<<<SQL
 and (Answer.test_flag {$test_flag_sql}{$test_flag_sql_conjunction}Target.test_flag {$test_flag_sql})
SQL;
    }
    $SQL.=$order_by;

    $event_datas = FDB :: getAssoc($SQL);

    $max = count($event_datas);
    $n = ceil($max/100);
    $i=0;
    foreach ($event_datas as $event_data) {
        insertDataLine($_subevents, $subevents, $seid2num, $ignore_columun, $event_data); //一人分ずつ処理
        if (++$i%$n==0) {
            setPercent(round($i/$max*100),$i,$max);
            ob_end_flush();
        }

    }
    setPercent(100,$max,$max);
    print encodeWebOut("<p align='left' style='font-size:15px;'><b style='color:#f17662;'>&#9313;ファイル作成中</b><br>ファイルを作成しています。10秒程度かかります。</p>");
    ob_end_flush();
}

/**
 * 回答データをCSVに書き出す(一人分ずつ書き出す)
 */
function insertDataLine($_subevents, $subevents, $seid2num, $ignore_columun, $event_data)
{
    usleep(DLCSV_USLEEP_TIME);//CPUの負荷をさげるためsleep

    $line = array ();
    $serial_no = $event_data['serial_no'];
    $event_data_id = $event_data['event_data_id'];
    //$line[] = $serial_no; //キー

    if (SHOW_STATUS) {
        if ($event_data['answer_state'] == 0) {
            $line[] = '完了';
        } elseif ($event_data['answer_state'] == 10) {
            $line[] = '途中';
        } else {
            $line[] = '不明';
        }
    }
    $line[] = date('Y/m/d H:i:s', strtotime(substr($event_data['udate'], 0, 19))); //回答日

    //回答者情報
    $line[] = $event_data['ans_uid'];
    $line[] = $event_data['ans_name'];

    //対象者情報
    $line[] = getDiv1NameById($event_data['div1']);
    $line[] = getDiv2NameById($event_data['div2']);
    $line[] = getDiv3NameById($event_data['div3']);

    $line[] = $event_data['tar_uid'];
    $line[] = $event_data['tar_name'];
    $line[] = $event_data['tar_class'];

    $line[] = $user_type = $event_data['evid']%100;//入力者区分
    $line[] = round(($event_data['evid']-$user_type)/100);//回答パターン

    $tmp = FDB :: select(T_EVENT_SUB_DATA,"*", "where event_data_id =" . FDB :: escape($event_data_id)." ORDER BY seid");

    /*************** seidがキーになるように変換 ******************/
    $subevent_datas = array ();
    foreach ($tmp as $tmp_) {
        $subevent_datas[$seid2num[$tmp_['seid']]][] = $tmp_; //複数行ある可能性あるので、配列
    }
    unset ($tmp, $tmp_);
    /*************************************************************/

    //subeventをベースに、一問ずつ回答を書き出す。
    $tmp_counter=1;
    foreach ($subevents as $seid => $subevent) {
        $tmp_counter++;
        $other = '';
        $choices = array ();
        $choices_ma = array ();

        if (is_array($subevent_datas[$seid])) {
            //subevent_dataは複数行あるが、最大2列 (回答,その他)
            foreach ($subevent_datas[$seid] as $subevent_data) {
                $other = $subevent_data['other'];
                $choice = $subevent_data['choice'];
                $choices_ma[$choice] = 1;
                $chtable = false;
                if ($_subevents[$subevent_data['seid']]['chtable']) {
                    $chtable = explode(',', $_subevents[$subevent_data['seid']]['chtable']);
                }

                if ($chtable && $choice != '9998') {//2008/09/29 表示されたが、飛ばされた設問は変換テーブルを用いて変換しない
                    $choice = $chtable[$choice];
                } else {
                    $choice = $choice +1;
                    if($choice == 9999)
                        $choice = '';
                }
                $choices[] = $choice; //あとでカンマ区切りにするのでとりあえず配列にいれておく。
            }
        }

        if ($subevent['type2'] == 't') {
            //記入回答だったらotherを強制的に入れるように
            $subevent['other'] = 1;
        } elseif (MA_EXPLODE_MODE && $subevent['type1']==2) {
            $i=0;
            foreach (explode(',',$subevent['choice']) as $choice) {
                if($choices_ma[$i])
                    $line[] = 1;
                else
                    $line[] = 0;
                $i++;
            }
        } else {
            if (NO_9999 && !$choices) {
                $choices = array (
                );
            }
            //選択式の場合はchoiceを必ず入れる
            $line[] = implode(',', $choices); //選択肢はカンマ区切りにして1列に収める。
        }

        //その他欄があるならor記入回答なら
        if ($subevent['other']) {
            //エクセルでエラーを起こす可能性のある先頭文字前に空白挿入
            $other = preg_replace("/^(\*|\+|%|\/|-|=)/", " $1", $other);
            if (CSV_TSV=='tsv' || !OTHER_NOESCAPE_MODE) {
                $other = str_replace("\r\n", " ", $other);
                $other = str_replace("\r", " ", $other);
                $other = str_replace("\n", " ", $other);
                $other = str_replace("\t", " ", $other);
            }
            if(is_good(REPLACE_TYPE))
            {
                foreach(explode(",", REPLACE_TYPE) as $type)
                {
                    switch ($type)
                    {
                        case 1:
                            $other = str_replace("\t", " ", $other);
                            break;
                        case 2:
                            $other = str_replace("\r", "\n", str_replace("\r\n", "\n", $other));
                            break;
                    }
                }
            }
            $line[] = $other;
        }
    }
    error_log(line2csv($line), 3, TMP_CSVFILE);
}
function line2csv($line)
{
    foreach ($line as $k => $v) {
        $line[$k] = csv_quote($v);
    }

    return implode(OUTPUT_CSV_DELIMITER, $line) . "\r\n";
}

/**
 * subeventの配列を受け取り、インデックス行をCSVに書き出す
 * @param array $subevents
 */
function insertIndexLine($subevents, $ignore_columun)
{
    global $con;
    $line = array('状態','回答時間','回答者ID','回答者氏名','対象者####div_name_1####','対象者####div_name_2####','対象者####div_name_3####','対象者ID','対象者氏名','対象者役職','評価者タイプ','シート');
    $line2 = array_pad(array(),count($line),'');

    foreach ($subevents as $subevent) {
        $num_ext = (is_good($subevent["num_ext"]) && $subevent["num_ext"]>0)? $subevent["num_ext"]:"";
        $title = trim(ereg_replace("\r|\n", "", strip_tags(stripslashes($subevent["title"]))));
        switch ($subevent['type1']) {
            case '2':
                foreach (explode(',', $subevent['choice']) as $choice) {
                    $line[] = $title."\n".strip_tags(stripslashes($choice));
                    $line2[] = $num_ext;
                }
                break;
            default:
                $line[] = $title;
                $line2[] = $num_ext;
                break;
        }
    }

    $line2 = line2csv($line2);
    $line = line2csv($line);
    //$line = replaceMessage($line);

    prepareEnqMatrix();
    $line = preg_replace_callback("/####enqmatrix(.*?)_(.*?)####/i", "ENQ_RepalaceMatrix_Strip", $line);
    $line = replaceMessage($line);
    error_log($line2, 3, TMP_CSVFILE);
    error_log($line, 3, TMP_CSVFILE);
}

function setPercent($p,$i,$m)
{
    print "<script>p({$p},{$i},{$m});</script>";
    ob_end_flush();
}

function getSqlOption()
{
    //1=>完全回答のみ 2=>途中保存のみ 3=>両方
    switch (DL_MODE) {
        case '1' :
            $option = ' and answer_state = 0';
            break;
        case '2' :
            $option = ' and answer_state = 10';
            break;
        case '3' :
            $option = ' and answer_state in (0,10)';
            break;
        default :
            $option = '';
            break;
    }

    //1=>対象者の所属 2=>対象者のID
    switch (DL_ORDER) {
        case '1' :
            $order_by = ' ORDER BY Event.evid';
            break;
        case '2' :
            $order_by = ' ORDER BY Target.uid, Answer.class, Answer.uid';
            break;
        default :
            $order_by = ' ORDER BY Event.evid';
            break;
    }

    return array($option, $order_by);
}
