<?php
/**
 * PG名称：ユーザ用マイページ
 * 日　付：2007/03/12
 * 作成者：cbase Kido Akama
 */
/******************************************************************************************************/
/** ルートディレクトリ */
define("DEBUG", 1);
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB. '360_MakeGraph.php');

$aryData = explode('|',$_GET['chd']);
foreach($aryData as $k => $v)
    $aryData[$k] = explode(',', $v);

$aryTitle = array (
    "1.論理構築力",
    "2.アイデア創出力",
    "3.課題設定力",
    "4.課題解決力",
    "5.関係性構築力",
    "6.総合評価",
);

$aryData = array (
    "本人評価" => $aryData[0],
    "他者評価 平均" => $aryData[1]
);
$graph = & new MakeRadarGraph(700, 300);
$graph->setScale(0, 5, 1);

//$imageName = getGraphImageName($id, 3);
$graph->makeImage($aryTitle, $aryData, $imageName);
//return getGraphImageName($id, 3, true);
