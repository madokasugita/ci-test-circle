<?php

/**
 * PG名称：コメント訂正機能メニュー画面
 * 日  付：
 * 作成者：
 *
 * 更新履歴
 */
/**************************************************************************/

define('DIR_ROOT', '../');
require_once DIR_ROOT . "crm_define.php";
require_once DIR_LIB . "CbaseFunction.php";
require_once DIR_LIB . "360_FHtml.php";
require_once DIR_LIB . "CbaseFErrorMSG.php";
require_once DIR_LIB . "CbaseFForm.php";
require_once DIR_LIB . "CbaseFManage.php";
require_once DIR_LIB . "CbaseEncoding.php";

session_start();
Check_AuthMng(basename(__FILE__));

define('PHP_SELF', getPHP_SELF() . '?' . getSID());
$PHP_SELF = PHP_SELF;
if ($_POST['dl']) {
    if ($_POST['dl']==1) {
        $filename = date('Y-m-d') . '回答者選択･承認状況推移.csv';
        $strFile = file_get_contents(DIR_DATA.'dairy_report_select.dat');
        $strFile = "日付,対象者数,選択中,承認依頼中,承認済み\n".$strFile;
    } else {
        $filename = date('Y-m-d') . '回答状況推移.csv';
        $strFile = file_get_contents(DIR_DATA.'dairy_report_answer.dat');
        $strFile = "日付,回答すべき数,未回答,回答中,回答済み\n".$strFile;
    }
    $strFile = mb_convert_encoding($strFile, "SJIS-win",INTERNAL_ENCODE);
    $filename = encodeDownloadFilename(replaceMessage($filename));
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Content-length: " . strlen($strFile));
    print $strFile;
    exit;
}
encodeWebAll();
$i=0;
foreach (file(DIR_DATA.'dairy_report_select.dat') as $line) {
    list($day,$sum,$state20,$state10,$state0) = explode(",",$line);
    $days[] = "{v:{$i}, label:'{$day}'}";
    $p_state0[] = '['.$i.','.sprintf("%0.1f",$state0/$sum*100).']';
    $p_state10[] = '['.$i.','.sprintf("%0.1f",$state10/$sum*100).']';
    $i++;
}
$dp_state20 = sprintf("%0.1f",($state20)/$sum*100);
$dp_state10 = sprintf("%0.1f",($state10)/$sum*100);
$dp_state0 = sprintf("%0.1f",($state0)/$sum*100);
$width=count($days)*20+50;
$days = implode(',',$days);
$p_state0 = implode(',',$p_state0);
$p_state10 = implode(',',$p_state10);

$_i=0;
foreach (file(DIR_DATA.'dairy_report_answer.dat') as $_line) {
    list($_day,$_sum,$_state20,$_state10,$_state0) = explode(",",$_line);
    $_days[] = "{v:{$_i}, label:'{$_day}'}";
    $_p_state0[] = '['.$_i.','.sprintf("%0.1f",$_state0/$_sum*100).']';
    $_p_state10[] = '['.$_i.','.sprintf("%0.1f",$_state10/$_sum*100).']';
    $_i++;
}
$_dp_state20 = sprintf("%0.1f",($_state20)/$_sum*100);
$_dp_state10 = sprintf("%0.1f",($_state10)/$_sum*100);
$_dp_state0 = sprintf("%0.1f",($_state0)/$_sum*100);
$_width=count($_days)*20+50;
$_days = implode(',',$_days);
$_p_state0 = implode(',',$_p_state0);
$_p_state10 = implode(',',$_p_state10);

$DIR_IMG = DIR_IMG;

print<<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ja" lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="ja" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link rel="stylesheet" href="{$DIR_IMG}common.css" type="text/css">
<script src="{$DIR_IMG}common.js" type="text/javascript"></script>
<script src="{$DIR_IMG}myconfirm.js" type="text/javascript"></script>
<title>進捗状況推移確認</title>


<script src="{$DIR_IMG}lib/prototype/prototype.js" type="text/javascript"></script>
<script src="{$DIR_IMG}lib/excanvas/excanvas.js" type="text/javascript"></script>
<script src="{$DIR_IMG}plotr.js" type="text/javascript"></script>
<style>
.info{
    margin-left:30px;
    border-collapse:collapse;
    background-color:#ffffff;
    border:solid 1px #333333;
    text-align:left;
}
.info td{
    border:solid 1px #333333;
    font-size:12px;
    text-align:center;
    width:100px;
}
.info .header{
    background-color:#f0f0f0;
}
</style>
</head>
<body>
<div style="width:600px;text-align:left;font-weight:bold;font-size:17px;border-bottom:2px #005aa5 solid;padding:3px;margin:20px 0;">
進捗状況推移確認
</div>
<div style="padding-left:10px">

<div style="width:400px;text-align:left;font-weight:bold;font-size:12px;border-bottom:1px #005aa5 solid;padding:3px;margin:20px 0;">
回答者選択/承認状況確認
</div>
<table class="info">
<tr class="header">
<td>日時</td>
<td>対象者数</td>
<td>選択中</td>
<td>承認依頼中</td>
<td>承認済み</td>
</tr>
<tr>
<td>{$day}</td>
<td>{$sum}</td>
<td>{$state20} ({$dp_state20}%)</td>
<td>{$state10} ({$dp_state10}%)</td>
<td>{$state0} ({$dp_state0}%)</td>
</tr>
</table>

<div><canvas width="{$width}" height="300" id="lines1"></canvas></div>


<form action="{$PHP_SELF}" method="post" style="margin-left:30px;margin-top:10px;">
<input type="hidden" name="dl" value="1">
<input type="submit" value="CSVダウンロード">
</form>

<script type="text/javascript">
Plotr.Base.generateColorscheme = function (/*String*/hex, /*String[]*/keys) {
    if (keys.length === 0) {
        return new Hash();
    }
    var color = new Plotr.Color(hex);
    var result = new Hash();
    keys.each(function (index) {
        result[index] = color.lighten(75).toHexString();
    });
    result['承認済み'] = '#ff3141';
    result['回答済み'] = '#ff3141';

    return result;
};
var dataset = {"承認済み": [{$p_state0}],"承認依頼中": [{$p_state10}]};
var options = {
    padding: {left: 30, right: 0, top: 10, bottom: 10},
    backgroundColor: "#dbdbdb",
    colorScheme:'#1c1afe',
    shouldFill: false,
    axis: {
        y:{values:[	0,100]},
        x:{ticks: [{$days}]}
    }
};
var bar = new Plotr.LineChart("lines1", options);
bar.addDataset(dataset);
bar.render();
</script>






<div style="width:400px;text-align:left;font-weight:bold;font-size:12px;border-bottom:1px #005aa5 solid;padding:3px;margin:20px 0;">
回答状況確認
</div>

<table class="info">
<tr class="header">
<td>日時</td>
<td>回答すべき数</td>
<td>未回答</td>
<td>回答中</td>
<td>回答済み</td>
</tr>
<tr>
<td>{$_day}</td>
<td>{$_sum}</td>
<td>{$_state20} ({$_dp_state20}%)</td>
<td>{$_state10} ({$_dp_state10}%)</td>
<td>{$_state0} ({$_dp_state0}%)</td>
</tr>
</table>

<div><canvas width="{$_width}" height="300" id="lines2"></canvas></div>





<form action="{$PHP_SELF}" method="post" style="margin-left:30px;margin-top:10px;">
<input type="hidden" name="dl" value="2">
<input type="submit" value="CSVダウンロード">
</form>



</div>
<script type="text/javascript">
var dataset = {"回答済み": [{$_p_state0}],"回答中": [{$_p_state10}]};
var options = {
    padding: {left: 30, right: 0, top: 10, bottom: 10},
    backgroundColor: "#dbdbdb",
    colorScheme:'#1c1afe',
    shouldFill: false,
    axis: {
        y:{values:[	0,100]},
        x:{ticks: [{$_days}]}
    }
};
var bar = new Plotr.LineChart("lines2", options);
bar.addDataset(dataset);
bar.render();
</script>
</body>
</html>
HTML;
