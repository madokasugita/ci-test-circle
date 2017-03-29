<?php
/**
 * PG名称：CSVをダウンロードする
 * 日　付：2007
 * 作成者：cbase Kido
 */
/****************************************************************************************************/

define('DIR_ROOT', '../');
require_once DIR_ROOT . "crm_define.php";
require_once DIR_LIB . "CbaseFunction.php";
require_once DIR_LIB . "360_Function.php";
require_once DIR_LIB . "CbaseFDB.php";
require_once DIR_LIB . "CbaseEncoding.php";
require_once (DIR_LIB . 'CbaseFManage.php');
//セッションチェック
session_start();
Check_AuthMng(basename(__FILE__));

$mode = "dlcsv_".$_GET['mode'];
$mode();

function dlcsv_relation()
{
    relation_dl($_GET['type'], $GLOBALS['_360_user_type'][$_GET['type']]);
}

function dlcsv_all_input()
{
    global $userMasterTable,$aryComparisons;
    $label = getColmunLabel('user_search');
    $data = array(array('対象者ID','対象者名前','人数','回答者'));
    $user_ = array();
    $sheet_type = (int) $_POST['sheet_type'];
    $sheet_type_where = $sheet_type ? ' and a.sheet_type = '.$sheet_type.' ':'';
    $sheet_type_name = $sheet_type ? $GLOBALS['_360_sheet_type'][$sheet_type]:'';
    $T_USER_MST = T_USER_MST;
    $T_USER_RELATION = T_USER_RELATION;
    $inputer = '('.implode(',', range(1,INPUTER_COUNT)).')';
    $sql=<<<SQL
SELECT a.uid,a.name,c.uid as t_uid,c.name as t_name,b.user_type
FROM {$T_USER_MST} a
LEFT JOIN {$T_USER_RELATION} b on a.uid = b.uid_a
LEFT JOIN {$T_USER_MST} c on c.uid = b.uid_b
WHERE b.user_type in {$inputer} {$sheet_type_where}
ORDER BY a.sheet_type,uid,user_type
SQL;
    $tmps=array();
    foreach (FDB::getAssoc($sql) as $tmp) {
        $tmps[$tmp['uid']][]= array($tmp['user_type'],$GLOBALS['_360_user_type'][$tmp['user_type']],$tmp['t_uid'],$tmp['t_name']);
        $names[$tmp['uid']]=$tmp['name'];
    }
    foreach ($tmps as $uid => $temp2) {
        $line = array();
        $line[]= $uid;
        $line[]= $names[$uid];
        $line[]= count($temp2);
        usort($temp2,'user_type_sort');
        foreach ($temp2 as $user) {
            $line[] = $user[1];
            $line[] = $user[2];
            $line[] = $user[3];
        }


        $data[] = $line;
    }

    csv_download_utf8($data,date('Ymd')."_回答者一覧".$sheet_type_name.DATA_FILE_EXTENTION);
    exit;
}

function user_type_sort($a,$b)
{
    if($a[0] < $b[0])

        return -1;
    else
        return 1;
}


function relation_dl($user_type, $user_typename)
{
    global $userMasterTable,$aryComparisons;
    $label = getColmunLabel('user_search');
    $data = array(array('対象者ID','対象者名前','人数',$user_typename.''));
    $user_ = array();
    $user_type = (int) $user_type;
    $sheet_type = (int) $_POST['sheet_type'];
    $sheet_type_where = $sheet_type ? ' and a.sheet_type = '.$sheet_type.' ':'';
    $sheet_type_name = $sheet_type ? $GLOBALS['_360_sheet_type'][$sheet_type]:'';
    $T_USER_MST = T_USER_MST;
    $T_USER_RELATION = T_USER_RELATION;
    $sql=<<<SQL
SELECT a.uid,a.name,c.uid as t_uid,c.name as t_name
FROM {$T_USER_MST} a
LEFT JOIN {$T_USER_RELATION} b on a.uid = b.uid_a
LEFT JOIN {$T_USER_MST} c on c.uid = b.uid_b
WHERE user_type = {$user_type} {$sheet_type_where}
ORDER BY a.sheet_type,uid,user_type
SQL;
    $tmps=array();
    foreach (FDB::getAssoc($sql) as $tmp) {
        $tmps[$tmp['uid']][]= array($tmp['t_uid'],$tmp['t_name']);
        $names[$tmp['uid']]=$tmp['name'];
    }

    foreach ($tmps as $uid => $users) {
        $line = array();
        $line[] = $uid;
        $line[] = $names[$uid];
        $line[] = count($users);
        foreach ($users as $user) {
            $line[] = $user[0];
            $line[] = $user[1];
        }

        $data[] = $line;
    }

    csv_download_utf8($data,date('Ymd')."_".$user_typename.'一覧'.$sheet_type_name.DATA_FILE_EXTENTION);
    exit;
}

function dlcsv_admit()
{
    global $userMasterTable,$aryComparisons;
    $label = getColmunLabel('user_search');
    $header=array('承認状況','ユーザーＩＤ','本人氏名','所属コード(大)','所属コード(中)','所属コード(小)','承認者ユーザーＩＤ','承認者氏名','所属コード(大)','所属コード(中)','所属コード(小)');
    $data = array($header);
    $user_ = array();
    $user_type = ADMIT_USER_TYPE;
    $sheet_type = (int) $_POST['sheet_type'];
    $sheet_type_where = $sheet_type ? ' and a.sheet_type = '.$sheet_type.' ':'';
    $sheet_type_name = $sheet_type ? $GLOBALS['_360_sheet_type'][$sheet_type]:'';
    $T_USER_MST = T_USER_MST;
    $T_USER_RELATION = T_USER_RELATION;
    $sql=<<<SQL
SELECT
a.select_status,
a.uid,
a.name,
a.div1,
a.div2,
a.div3,
c.uid as t_uid,
c.name as t_name,
c.div1 as t_div1,
c.div2 as t_div2,
c.div3 as t_div3
FROM {$T_USER_MST} a
LEFT JOIN {$T_USER_RELATION} b on a.uid = b.uid_a
LEFT JOIN {$T_USER_MST} c on c.uid = b.uid_b
WHERE user_type = {$user_type} {$sheet_type_where}
ORDER BY a.sheet_type,uid,user_type
SQL;
    foreach (FDB::getAssoc($sql) as $tmp) {
        $line = array();
        $line[] = $GLOBALS['_360_select_status'][$tmp['select_status']];
        $line[] = $tmp['uid'];
        $line[] = $tmp['name'];
        $line[] = getDiv1NameById($tmp['div1']);
        $line[] = getDiv2NameById($tmp['div2']);
        $line[] = getDiv3NameById($tmp['div3']);
        $line[] = $tmp['t_uid'];
        $line[] = $tmp['t_name'];
        $line[] = getDiv1NameById($tmp['t_div1']);
        $line[] = getDiv2NameById($tmp['t_div2']);
        $line[] = getDiv3NameById($tmp['t_div3']);
        $data[]= $line;
    }
    csv_download_utf8($data,date('Ymd')."_承認状況一覧".$sheet_type_name.DATA_FILE_EXTENTION);
    exit;
}
