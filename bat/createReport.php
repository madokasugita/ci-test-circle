<?php
chdir(dirname(__FILE__));
/**
 * PGNAME:
 * DATE  :2009/07/13
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
/****************************************************************************************************/

$r = FDB::getAssoc('select count(*) as count from usr where mflag = 1;');
$countAll[0] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from usr_relation a left join usr b on a.uid_a = b.uid where b.mflag = 1 and a.user_type = 1;');
$countAll[1] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from usr_relation a left join usr b on a.uid_a = b.uid where b.mflag = 1 and a.user_type = 2;');
$countAll[2] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from usr_relation a left join usr b on a.uid_a = b.uid where b.mflag = 1 and a.user_type = 3;');
$countAll[3] = $r[0]['count'];

$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 0 and evid % 100 = 0;');
$count0[0] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 0 and evid % 100 = 1;');
$count0[1] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 0 and evid % 100 = 2;');
$count0[2] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 0 and evid % 100 = 3;');
$count0[3] = $r[0]['count'];

$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 10 and evid % 100 = 0;');
$count1[0] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 10 and evid % 100 = 1;');
$count1[1] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 10 and evid % 100 = 2;');
$count1[2] = $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from event_data where answer_state = 10 and evid % 100 = 3;');
$count1[3] = $r[0]['count'];
chmod(DIR_DATA.'dairy_report_answer.dat',0777);

$count20 = array_sum($countAll)-array_sum($count0)-array_sum($count1);
error_log(date('n/d').",".array_sum($countAll).','.$count20.",".array_sum($count1).",".array_sum($count0).",\n",3,DIR_DATA.'dairy_report_answer.dat');

syncCopy(DIR_DATA.'dairy_report_answer.dat');

$r = FDB::getAssoc('select count(*) as count from usr where mflag = 1 and select_status = 0;');
$c[0]= $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from usr where mflag = 1 and select_status = 1;');
$c[1]= $r[0]['count'];
$r = FDB::getAssoc('select count(*) as count from usr where mflag = 1 and select_status = 2;');
$c[2]= $r[0]['count'];

chmod(DIR_DATA.'dairy_report_select.dat',0777);
error_log(date('n/d').",".array_sum($c).",".$c[0].",".$c[1].",".$c[2].",\n",3,DIR_DATA.'dairy_report_select.dat');
syncCopy(DIR_DATA.'dairy_report_select.dat');
/****************************************************************************************************/
