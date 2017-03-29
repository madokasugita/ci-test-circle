<?php
//NO140 プレビューは反映ボタンを押さなくても更新が反映されるように
define('NOT_USE_CHACHE',1);
$_SERVER["QUERY_STRING"] = $_GET['rid'];
if ($_GET['lang360']) {
    $_COOKIE['lang360'] = $_GET['lang360'];
}
//1
$_SESSION['login']['lang_type'] = $_GET['lang360'];
require_once 'test_index.php';
