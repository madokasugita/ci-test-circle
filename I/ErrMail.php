#!/usr/local/bin/php
<?php
/*
 * i@cbase.co.jp
 * 著作権は株式会社シーベースが保有しております。
 */

//定義
    define('DIR_ROOT','../');
    define("FILE_NAME","EmailErr_".date("Ym").".dat");

//変数
    $aryEmail=array();

//メールデーモンからデータ取得
    $stdin = file("php://stdin");
    $mail = implode("", $stdin);

//Fromとbodyを切り取り
    list($headers, $body1) = split("\n\n", $mail, 2);
    $body = trim($body1);

//bodyからemailアドレス取得
    preg_replace_callback('/[a-zA-Z0-9_\.\-]+@[A-Za-z0-9_\.\-].[A-Za-z0-9_\.\-]+/',"GetEmail",$body);

//ログ書き
    foreach ($aryEmail as $addr) transWriteLog(DIR_LOG.FILE_NAME,$addr);

//ローカルファンクション
function transWriteLog($prmFile,$prmString)
{
    error_log(date("Ymd")."\t".date("His")."\t".$prmString."\n",3,$prmFile);
}
function GetEmail($match)
{
    global $aryEmail;
    $aryEmail[]=$match[1];
}

?>
