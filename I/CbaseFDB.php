<?php
require_once 'MDB2.php';
require_once 'CbaseFDBClass.php';

//2013-02-21 mysql版修正
//CbaseFDB,CbaseFDBClassそれぞれrequireしている箇所があり、2重にコネクションが貼られる場合があった。
//DB操作はCbaseFDBClassに任せる。

// //接続準備
// //echo $dsn = DB_TYPE."://".DB_USER.":".DB_PASSWD."@".DB_HOST.":".DB_PORT."/".DB_NAME;
// //exit;
// $op  = array("persistent"=>DB_PERSISTENT);

// // データベースに接続
// //$con = DB::connect($dsn,$op);
// $con = MDB2::factory($dsn,$op);
// if(!defined('DEBUG'))
// 	die("Error 9.0.0");
// // 接続に失敗したらエラー表示して終了
// if (PEAR::isError($con)) {
// 	if (DEBUG)	echo $con->getDebugInfo();
// 	else		echo "Error 9.0.1";
// 	exit;
// }

// //$con->autoCommit(true);

// /**
//  * Postgresqlの扱う時間帯を指定する
//  * @param string $prmZone タイムゾーン
//  */
// function transSetTimeZone($prmZone="GMT+9") {
// 	global $con;
// 	$strSql="SET TIME ZONE '$prmZone'";
// 	$rs = $con->query($strSql);
// 	return (!PEAR::isError($rs));
// }
