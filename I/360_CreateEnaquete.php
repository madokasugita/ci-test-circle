<?php

/**
 * PGNAME:
 * DATE  :2009/04/08
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */

require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFgetcsv.php');
require_once (DIR_LIB . 'CbaseFFile2.php');

/*************************************************************************************************/
function createSubevent($evid, $cells, $i, $rowspansize,$colspansize)
{
    global $title_a, $title_b, $title_c,$cell_count;
    if(!$cells[3])

        return;
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 + $i;
    $data['title'] =  "####enqmatrix{$i}_2####. ####enqmatrix{$i}_3####";
    $data['type1'] = '1';
    $data['type2'] = 'p';
    $data['choice'] = '####enq_rate_5####,####enq_rate_4####,####enq_rate_3####,####enq_rate_2####,####enq_rate_1####,####enq_rate_Na####';
    $data['chtable'] = '5,4,3,2,1,NA';
    $data['width'] = '3';
    $data['rows'] = '0';
    //$data['cond4'] = 'limit,false,「%%%%title%%%%」####enq_error_numeric####,12345';
    //$data['ext'] = 'style="width:24px;text-align:center" maxlength="1"';
    $data['hissu'] = '1';
    $data['page'] = '1';
    $rownum = $i -1;
    $data['html2'] = "<tr class=\"matrix_body_row_{$rownum}\">";
    $colnum = 0;

    foreach ($cells as $cell) {
        if ($rowspansize[$colnum] && !($colspansize[$colnum-1]>1 || $colspansize[$colnum-2]>2)) {
            if ($colnum == 4)
                $id = " id=\"error{$data['seid']}\"";
            else
                $id = "";
            if($colspansize[$colnum]>1)
                $data['html2'] .=<<<HTML
<td rowspan="{$rowspansize[$colnum]}" colspan="{$colspansize[$colnum]}" class="matrix_body_col_{$colnum}"{$id}>####enqmatrix{$i}_{$colnum}####</td>

HTML;
            else
                $data['html2'] .=<<<HTML
<td rowspan="{$rowspansize[$colnum]}" class="matrix_body_col_{$colnum} matrix_col_width_{$colnum} cell{$i}_{$colnum}"{$id}>####enqmatrix{$i}_{$colnum}####</td>

HTML;
        }
        $colnum++;
    }
    $data['html2'] .=<<<HTML
%%%%form%%%%
</tr>

HTML;
    $cell_count = count($cells);
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}



function createSubeventConfirm($evid, $cells, $i, $rowspansize,$colspansize)
{
    global $title_a, $title_b, $title_c,$cell_count;

    if(!$cells[3])

        return;
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 +100+ $i;
    $confirmid =  $evid * 1000 + $i;
    $data['title'] =  "####enqmatrix{$i}_2####. ####enqmatrix{$i}_3####";
    $data['type1'] = '0';
    $data['type2'] = 'n';
    $data['hissu'] = '0';
    $data['page'] = '2';
    $rownum = $i -1;
    $data['html2'] = "<tr class=\"matrix_body_row_{$rownum}\">";
    $colnum = 0;

    foreach ($cells as $cell) {
        if ($rowspansize[$colnum] && !($colspansize[$colnum-1]>1 || $colspansize[$colnum-2]>2)) {
            if ($colnum == 4)
                $id = " id=\"error{$data['seid']}\"";
            else
                $id = "";
            if($colspansize[$colnum]>1)
                $data['html2'] .=<<<HTML
<td rowspan="{$rowspansize[$colnum]}" colspan="{$colspansize[$colnum]}" class="matrix_body_col_{$colnum}"{$id}>####enqmatrix{$i}_{$colnum}####</td>

HTML;
            else
                $data['html2'] .=<<<HTML
<td rowspan="{$rowspansize[$colnum]}" class="matrix_body_col_{$colnum} matrix_col_width_{$colnum} cell{$i}_{$colnum}"{$id}>####enqmatrix{$i}_{$colnum}####</td>

HTML;
        }
        $colnum++;
    }
    $data['html2'] .=<<<HTML
%%%%id{$confirmid}%%%%
</tr>

HTML;
    $cell_count = count($cells);
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}









function createTableFooter($evid, $i)
{
    global $cell_count;
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 + $i;
    $data['page'] = '1';

    $data['title'] = "テーブルフッター";
    $data['type1'] = '0';
    $data['type2'] = 'n';
    $data['hissu'] = '0';
    $data['html2'] =<<<HTML
<tr style="visibility:hidden;border:none;display:none;">

HTML;
    for($i=0;$i<$cell_count;$i++)
        $data['html2'] .=<<<HTML
<td class="matrix_col_width_{$i}" style="border-bottom:none;"></td>

HTML;

    $data['html2'] .=<<<HTML
</tr>
</table>
</div>

HTML;
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}

function createTableFooterConfirm($evid, $i)
{
    global $cell_count;
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 +100+ $i;
    $data['page'] = '2';

    $data['title'] = "テーブルフッター";
    $data['type1'] = '0';
    $data['type2'] = 'n';
    $data['hissu'] = '0';
    $data['html2'] =<<<HTML
<tr style="visibility:hidden;border:none;display:none;">

HTML;
    for($i=0;$i<$cell_count;$i++)
        $data['html2'] .=<<<HTML
<td class="matrix_col_width_{$i}" style="border-bottom:none;"></td>

HTML;

    $data['html2'] .=<<<HTML
</tr>
</table>
</div>

HTML;
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}

function createTableHeaderConfirm($evid, $cells)
{
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000+100;
    $data['page'] = '2';

    $data['title'] = "テーブルヘッダー";
    $data['type1'] = '0';
    $data['type2'] = 'n';
    $data['hissu'] = '0';
    $data['html2'] =<<<HTML
%%%%MSG_ENQ_base%%%%
<table id="targetname">
    <tr>
        <th>####enq_message5####</td>
        <td>%%%%targetname%%%%</td>
    </tr>
</table>
<table class="matrix_header_table">
<tr>
HTML;
    $colnum = 0;
    foreach ($cells as $cell) {
        $data['html2'] .=<<<HTML
<td class="matrix_header_col_{$colnum} matrix_col_width_{$colnum}">####enqmatrix0_{$colnum}####</td>

HTML;
        $colnum++;
    }
    $data['html2'] .=<<<HTML
%%%%targets%%%%
<td class="matrix_header_scroll_margin"> </td>
</tr>
</table>
<div class="matrix_div">
<table class="matrix_body_table">
HTML;
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}

function createTableHeader($evid, $cells)
{
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000;
    $data['page'] = '1';

    $data['title'] = "テーブルヘッダー";
    $data['type1'] = '0';
    $data['type2'] = 'n';
    $data['hissu'] = '0';
    $data['html2'] =<<<HTML
%%%%MSG_ENQ_base%%%%
<table id="targetname">
    <tr>
        <th>####enq_message5####</td>
        <td>%%%%targetname%%%%</td>
    </tr>
</table>
<table class="matrix_header_table">
<tr>

HTML;
    $colnum = 0;
    foreach ($cells as $cell) {
        $data['html2'] .=<<<HTML
<td class="matrix_header_col_{$colnum} matrix_col_width_{$colnum}">####enqmatrix0_{$colnum}####</td>

HTML;
        $colnum++;
    }
    $data['html2'] .=<<<HTML
%%%%targets%%%%
<td class="matrix_header_scroll_margin"> </td>
</tr>
</table>

<div class="matrix_div">
<table class="matrix_body_table">

HTML;
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}

function createEvent($evid)
{
    global $_360_sheet_type, $_360_user_type;
    $enqname = $_360_sheet_type[floor($evid / 100)] . ' ' . $_360_user_type[$evid % 100].'####enq_sheet####';
    $sheet = floor($evid / 100);
    $user = $evid % 100;
    $DIR_IMG_USER = DIR_IMG_USER;
    $htmlh =<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<title>{$enqname}</title>
<meta http-equiv="content-script-type" content="text/javascript">
<meta http-equiv="content-style-type" content="text/css">
<link href="{$DIR_IMG_USER}360_userpage.css" type="text/css" rel="stylesheet">
<link href="{$DIR_IMG_USER}360_enq.css" type="text/css" rel="stylesheet">
<link href="{$DIR_IMG_USER}360_enqmatrix.css" type="text/css" rel="stylesheet">
%%%%MSG_ENQ_JS%%%%
%%%%MSG_ENQ_header%%%%
</head>
<body class="sheet{$sheet}">
<div id="maincontainer" class="user{$user}">
<div id="back">%%%%back_button%%%%</div>
<div id="top"><img src="{$DIR_IMG_USER}logo.png" alt="logoimage"></div>
<div id="userinfo">####USERINFO:uid#### : ####USERINFO:name####</div>
%%%%MSG_ENQ_files%%%%
<div id="title">{$enqname}</div>
%%%%MSG_ENQ_top%%%%
HTML;
    $MSG_CBASE_COPY_RIGHT = MSG_CBASE_COPY_RIGHT;
    $htmlf =<<<HTML
<div id="copyright">####copyright####</div>
<div id="cbase">{$MSG_CBASE_COPY_RIGHT}</div>
</div>
</body>
</html>
HTML;
$htmlh = '';
$htmlf = '';
    $data = array ();
    $data['evid'] = FDB :: escape($evid);
    $data['name'] = FDB :: escape($enqname);
    $data['rid'] = FDB :: escape('rid00' . $evid);
    $data['type'] = 1;
    $data['flgs'] = 2;
    $data['flgl'] = 0;
    $data['flgo'] = 0;
    $data['limitc'] = 0;
    $data['htmlh'] = FDB :: escape($htmlh);
    $data['htmlf'] = FDB :: escape($htmlf);
    $data['htmls'] = FDB :: escape('<input type="submit" name="main" value="送信">');
    $data['lastpage'] = 2;
    $data['muid'] = 1;
    FDB :: insert(T_EVENT, $data);
}

function deleteEnquete($evid)
{
    $event = FDB :: select1(T_EVENT, 'rid,evid', 'where evid = ' . FDB :: escape($evid));
    s_unlink(DIR_CACHE . $event['rid'] . '.ccache');
    FDB :: delete(T_BACKUP_DATA, 'where evid = ' . FDB :: escape($event['evid']));
    FDB :: delete(T_BACKUP_EVENT, 'where rid = ' . FDB :: escape($event['rid']));
    FDB :: delete(T_EVENT_SUB, 'where evid = ' . FDB :: escape($event['evid']));
    FDB :: delete(T_EVENT, 'where evid = ' . FDB :: escape($event['evid']));
}

function getRowSpanSize($fp,$delmiter=',')
{
    $count = count(CbaseFgetcsv($fp, $delmiter, "\"", "UTF-8"));
    $i = 1;
    $rowspansize = array ();
    $last = array ();
    while (!feof($fp)) {
        $data = CbaseFgetcsv($fp, $delmiter, "\"", "UTF-8");
        for ($colnum = 0; $colnum < $count; $colnum++) {
            if ($data[$colnum]) {
                $last[$colnum] = $i;
                $rowspansize[$i][$colnum] = 1;
            } elseif ($data[3]) {//
                $rowspansize[$last[$colnum]][$colnum]++;
            }
        }
        $i++;
    }
    rewind($fp);

    return $rowspansize;
}

function getColSpanSize($fp,$delmiter=',')
{
    $count = count(CbaseFgetcsv($fp, $delmiter, "\"", "UTF-8"));
    $i = 1;
    $rowspansize = array ();
    $last = array ();
    while (!feof($fp)) {
        $data = CbaseFgetcsv($fp, $delmiter, "\"", "UTF-8");
        for ($colnum = 0; $colnum < $count; $colnum++) {
            if ($data[$colnum] != '>>>') {
                $last[$i] = $colnum;
                $rowspansize[$i][$colnum] = 1;
            } elseif ($data[4]) {//ベネッセ用
                $rowspansize[$i][$last[$i]]++;
            }
        }
        $i++;
    }
    rewind($fp);

    return $rowspansize;
}

function insertMessageSubevent_($evid)
{

    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 + 98;
    $data['page'] = '1';

    $data['title'] = "####enqmatrix98_0####";
    $data['type1'] = '4';
    $data['type2'] = 't';
    $data['hissu'] = '1';
    $data['html2'] = <<<HTML
<div style="text-align:left;font-size:12px;margin:20px auto;width:950px;">
<b>####enqmatrix98_0####</b>
%%%%message%%%%
</div>
HTML;

    $data['rows'] = '3';
    $data['width'] = '200';

    $data['ext'] = 'style="width:930px;height:100px;" onblur="checkMainComment_Onblur(this)" onkeyup="checkMainComment(this)"';
    $data['cond4'] = 'len,true,「####enqmatrix31_0####」####enq_errror_message_count####,400';

    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}
function insertMessageSubevent($evid)
{

    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 + 99;
    $data['page'] = '1';

    $data['title'] = "####enqmatrix99_0####";
    $data['type1'] = '4';
    $data['type2'] = 't';
    $data['hissu'] = '1';
    $data['html2'] = <<<HTML
<div style="text-align:left;font-size:12px;margin:20px auto;width:950px;">
<b>####enqmatrix99_0####</b>
%%%%message2%%%%
</div>
HTML;
    $data['rows'] = '3';
    $data['width'] = '200';

    $data['ext'] = 'style="width:930px;height:100px;" onblur="checkMainComment_Onblur(this)" onkeyup="checkMainComment(this)"';
    $data['cond4'] = 'len,true,「####enqmatrix32_0####」####enq_errror_message_count####,400';

    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}
function insertMessageSubeventConfirm_($evid)
{
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 + 198;
    $confirmid = $evid * 1000 + 98;
    $data['page'] = '2';

    $data['title'] = "メッセージ";
    $data['type1'] = '0';
    $data['type2'] = 'n';
    $data['hissu'] = '0';
    $data['html2'] = '<div style="text-align:left;font-size:12px;margin:20px auto;width:950px;"><b>####enqmatrix31_0####</b><br>%%%%messageid'.$confirmid.'%%%%';
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}
function insertMessageSubeventConfirm($evid)
{
    $data = array ();
    $data['evid'] = $evid;
    $data['seid'] = $evid * 1000 + 199;
    $confirmid = $evid * 1000 + 99;
    $data['page'] = '2';

    $data['title'] = "メッセージ";
    $data['type1'] = '0';
    $data['type2'] = 'n';
    $data['hissu'] = '0';
    $data['html2'] = '<div style="text-align:left;font-size:12px;margin:20px auto;width:950px;"><b>####enqmatrix32_0####</b><br>%%%%messageid'.$confirmid.'%%%%';
    $data = FDB :: escapeArray($data);
    FDB :: insert(T_EVENT_SUB, $data);
}
