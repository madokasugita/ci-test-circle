<?php
/**
 * PGNAME:
 * DATE  :2009/04/07
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
/****************************************************************************************************/
session_start();
/****************************************************************************************************/

$target = $_GET['t'];
$language = $_GET['l'];
if ($_GET['h'] != md5($target.$language.SYSTEM_RANDOM_STRING)) {
    print "error!";
    exit;
}
setcookie('lang360',$language);

header("Location: {$target}");
