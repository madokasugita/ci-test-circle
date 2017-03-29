<?php

//define('DEBUG', 1);
define('DIR_ROOT', "../");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFunction.php');

session_start();
require_once (DIR_LIB . 'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

$dl_colmun = array (
    "uid" => "ユーザID",
    "name" => "名前",
    "email" => "email",
    "div1" => "div1",
    "div2" => "div2",
    "div3" => "div3",
    "memo" => "メモ"

);
$dl_colmun = limitColumn($dl_colmun);
$sql = FDB :: select1(T_COND, '*', 'where cnid = ' . FDB :: escape($_REQUEST['id']));

$data=array();
foreach (FDB :: getAssoc($sql['strsql']) as $tmp) {
    if(getDiv1NameById($tmp['div1']))
        $tmp['div1'] =  getDiv1NameById($tmp['div1']);
    $tmp['div2'] =  getDiv2NameById($tmp['div2']);
    $tmp['div3'] =  getDiv3NameById($tmp['div3']);
    $row = array();
    foreach ($dl_colmun as $k=>$v) {
        $row[] = $tmp[$k];
    }
    $data[] = $row;
}
csv_download(array_merge(array($dl_colmun),$data),date('YmdHis').'_'.$sql['name'].'.csv');
