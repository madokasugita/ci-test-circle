<?php
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once 'simpletest/autorun.php';

$dbgroup = new GroupTest("running SmartReview tests");
// $dbgroup->addTestFile('tests/sample.php');
//$dbgroup->addTestFile('tests/enviewer.php');
$dbgroup->addTestFile('tests/360_demo.php');
$dbgroup->addTestFile('tests/CbaseEnqueteView.php');
$dbgroup->run(new HtmlReporter($character_set = 'UTF-8'));
