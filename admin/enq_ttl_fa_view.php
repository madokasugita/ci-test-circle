<?php

/**
 * 自由回答の一覧を表示する
 * 引数 seid offset
 * @version 1.0
 */
/************************************************************************************/
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

/************************************************************************************/

unset($_SESSION['enq_ttl_fa_view']);

/** 何件ずつ表示するか */
define('ENQ_TTL_LIMIT', 10);

$seid = (int) $_GET['seid']; //サニタイズ
$evid = getEvidBySeid($seid); //同時に権限チェックも行なう
$offset = (int) $_GET['offset']; //サニタイズ
if ($_POST['offset']) {
    $offset = (int) $_POST['offset'] - 1;
}
$limit = ENQ_TTL_LIMIT;
if($_POST['keyword'])
    $keyword = $_POST['keyword'];
else
    $keyword = $_GET['keyword'];

$max = getFreeAnswerCount($seid,$keyword);

//設問のタイトルがセッションに入ってない場合は入れておく
if (is_null($_SESSION['enq_ttl_fa_view'][$seid]['title'])) {
    $tmp = FDB :: select1(T_EVENT_SUB, 'title', "where seid = {$seid}");
    $_SESSION['enq_ttl_fa_view'][$seid]['title'] = $tmp['title'];
}
$title = $_SESSION['enq_ttl_fa_view'][$seid]['title']; //設問のタイトル

if ($offset >= $max) {
    $offset = $max - $limit;
}
if ($offset <= 0) {
    $offset = 0;
}

/************************************************************************************/
$html .= enq_ttl_fa_title($title); //設問のタイトル
$html .= enq_ttl_fa_table($seid, $offset, $limit, $max,$keyword); //記述回答表示

$css = <<<__CSS__
a:link{ color:#ff6600; }
a:visited{ color:#ff6600; }
a:active{ color:#ff6600; }

*{
    font-size:12px;
}
.title{
    width:760px;
    text-align:left;
    border:solid 1px black;
    padding:5px;
    margin-bottom:20px;
}
.link{
    width:760px;
    text-align:left;
    padding:5px;
}

.table1{
    border-collapse:collapse;
    border-width:1px;
    border-color:#333333;
    border-style:solid;
}

.tr0{
    background-color:#f0f0f0;
}
.tr1{
    background-color:#fffff;
}
__CSS__;

$objHtml =& new ResearchAdminHtml("記入回答表示");
$objHtml->setSrcCss($css);
echo $objHtml->getMainHtml("<center>".$html."</center>");
exit;
/************************************************************************************/

function getEvidBySeid($seid)
{
    if ($_SESSION['enq_ttl_fa_view'][$seid]['evid'])
        return $_SESSION['enq_ttl_fa_view'][$seid]['evid'];
    $tmp = FDB :: select1(T_EVENT_SUB, 'evid', "where seid = {$seid}");

    $enq = Get_Enquete_Main('id', $tmp['evid'], '', '', $_SESSION['muid']);
    if (!$enq[-1]) {
        print "error!";
        exit;
    }

    return $tmp['evid'];
}

/**
 * 記述回答を表示するテーブル
 * @return string html
 */
function enq_ttl_fa_table($seid, $offset, $limit, $max, $keyword)
{
    $keyword_e = urlencode(encodeWebOut($keyword));
    $keyword_e2 = html_escape($keyword);
    $SID = getSID();
    $answers = getFreeAnswers($seid, $offset, $limit,$keyword);

    $first = $offset +1;
    $last = $offset +count($answers);

    //前の設問がある場合はリンクを出す。無い場合は灰色で表示
    if ($offset > 0) {
        $before_offset = $offset - $limit;
        $before_link =<<<HTML
<a href="enq_ttl_fa_view.php?{$SID}&offset={$before_offset}&seid={$seid}&keyword={$keyword_e}">前の{$limit}件</a>
HTML;
    } else {
        $before_link =<<<HTML
<span style="color:#aaaaaa">前の{$limit}件</span>
HTML;
    }

    //次の設問がある場合はリンクを出す。無い場合は灰色で表示
    if ($offset + $limit < $max) {
        $next_offset = $offset + $limit;
        $next_link =<<<HTML
<a href="enq_ttl_fa_view.php?{$SID}&offset={$next_offset}&seid={$seid}&keyword={$keyword_e}">次の{$limit}件</a>
HTML;
    } else {
        $next_link =<<<HTML
<span style="color:#aaaaaa">次の{$limit}件</span>
HTML;
    }

    $html .=<<<HTML
<span>最大<b>{$max}</b>件中 <b>{$first}</b>件目から<b>{$last}</b>件目までを表示</span>
<br>


<br>
<table  width="760px" cellpadding="5" cellspacing="0">
<tr><td align="left">
{$before_link} {$next_link}
<form method="POST" style="display:inline;" action="enq_ttl_fa_view.php?{$SID}&offset={$offset}&seid={$seid}&keyword={$keyword_e}">
<input name="offset" style="width:40px;ime-mode:disabled">件目から<input type="submit" value="表示"></form>
</td><td align="right">　　　


<form method="POST" style="display:inline;" action="enq_ttl_fa_view.php?{$SID}&offset={$offset}&seid={$seid}">
キーワードで絞込み:
<input type="text" name="keyword" value="{$keyword_e2}">
<input type="submit" value="絞込み">
</form>
</td>
</tr>
</table>


<table class="table1" width="760px" border="1" bordercolor="#222222" cellpadding="5" cellspacing="0">

<tr style="background-color:#333333;color:white;font-weight:bold;">
<td width="30" align="center">件目</td>
<td align="center">回答</td>
</tr>

HTML;
    $i = $first;
    $tr_class_num = 0;
    foreach ($answers as $answer) {
        if($keyword)
            $answer = str_replace($keyword,'$$$bold$$$'.$keyword.'$$$/bold$$$',$answer);
        $answer = transHtmlentities($answer);
        $answer = str_replace('$$$bold$$$','<b>',$answer);
        $answer = str_replace('$$$/bold$$$','</b>',$answer);



        $answer = nl2br($answer);
        $tr_class_num = ($tr_class_num +1) % 2;
        $html .=<<<HTML
<tr class="tr{$tr_class_num}">
<td align="center">{$i}</td>
<td>{$answer}</td>
</tr>
HTML;
        $i++;
    }
    $html .=<<<HTML
</table>
HTML;

    return $html;
}

/**
 * タイトル部分のHTMLを返す
 * @return string html
 */
function enq_ttl_fa_title($title)
{
    return<<<HTML
<div class="title">Q.{$title}</div>
HTML;
}

/************************************************************************************/
/**
 * 記入回答を配列にして返す。
 *
 */
function getFreeAnswers($seid, $offset = 0, $limit = "20",$keyword='')
{
    if($keyword)
        $keyword = " and other like ".FDB::escape("%".$keyword."%")." ";

    //正確さを求めるなら order byをつけたほうが良いが、効率のため省略->order by が無いと不正な動作をするときがあるため追加
    $tmps = FDB :: getAssoc("SELECT other FROM subevent_data a inner join event_data b on a.event_data_id = b.event_data_id where  b.answer_state = 0 and seid = {$seid}  and other <> '' {$keyword} order by a.event_data_id offset {$offset} limit {$limit}");
    $array = array ();
    foreach ($tmps as $tmp) {
        $array[] = $tmp['other'];
    }

    return $array;
}

/**
 * 記入回答を配列にして返す。
 *
 */
function getFreeAnswerCount($seid,$keyword='')
{
    if($keyword)
        $keyword = " and other like ".FDB::escape("%".$keyword."%")." ";
    //設問の回答数がセッションに入ってない場合は入れておく
    if (!is_null($_SESSION['enq_ttl_fa_view'][$seid]['max'])) {
        return $_SESSION['enq_ttl_fa_view'][$seid]['max'];
    }
    $tmps = FDB :: getAssoc("SELECT count(*) as count FROM subevent_data a inner join event_data b on a.event_data_id = b.event_data_id where  b.answer_state = 0 and seid = {$seid} and other <> ''{$keyword}");

    return $_SESSION['enq_ttl_fa_view'][$seid]['max'] = $tmps[0]['count'];
}
