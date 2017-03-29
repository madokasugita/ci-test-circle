<?php

/**
 * PG名称：期間設定
 * 日  付：
 * 作成者：
 *
 * 更新履歴
 */
/**************************************************************************/
define('DIR_ROOT', '../');
require_once DIR_ROOT . "crm_define.php";
require_once DIR_LIB . "CbaseFunction.php";
require_once DIR_LIB . "CbaseFErrorMSG.php";
require_once DIR_LIB . "CbaseFFile2.php";
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
//セッションチェック
session_start();
encodeWebAll();
Check_AuthMng(basename(__FILE__));
$page_name = "リンク表示期間設定";
$PHP_SELF = getPHP_SELF();
$SID = getSID();
$PHP_SELF .= "?" . $SID;

$events = getAllEventArray();

switch (getMode()) {
    case "write" :
        $html = write_term();
        break;
    default :
        $html = getHtmlForm();
}
$DATEPICKER_YEAR_RANGE_DEFAULT = DATEPICKER_YEAR_RANGE_DEFAULT;
$objHtml = new MreAdminHtml($page_name);
$objHtml->setSrcJs(<<<__JS__
$(".date").datepicker({
    altFormat: 'yy/mm/dd 00:00:00',
    yearRange: '{$DATEPICKER_YEAR_RANGE_DEFAULT}',
    dateFormat: 'yy/mm/dd 00:00:00',
    changeMonth: true,
    changeYear: true,
    showMonthAfterYear: true,
    yearSuffix:"\u5e74",
    monthNamesShort:["1\u6708","2\u6708","3\u6708","4\u6708","5\u6708","6\u6708","7\u6708","8\u6708","9\u6708","10\u6708","11\u6708","12\u6708"],
    dayNamesMin:["\u65e5","\u6708","\u706b","\u6c34","\u6728","\u91d1","\u571f"]
});
__JS__
);
echo $objHtml->getMainHtml($html);
exit;

function write_term()
{
    global $ERROR,$GLOBAL_getTermData;
    foreach ($_POST['term'] as $evid => $term) {
        foreach ($term as $type => $data) {
            if (!strtotime($data['s']) or !strtotime($data['e'])) {
                $ERROR->addMessage('日付の書式が正しくありません');

                return getHtmlForm();
            }
            $datas[$evid][$type]['s'] = date('Y/m/d H:i:s', strtotime($data['s']));
            $datas[$evid][$type]['e'] = date('Y/m/d H:i:s', strtotime($data['e']));
        }
    }

    setTermData($datas);
    $GLOBAL_getTermData = null;

    return getHtmlForm();
}

function getHtmlForm()
{
    global $events, $term_names, $page_name, $PHP_SELF, $ERROR, $permitEvids;
    $term_datas = getTermData();
    $error = $ERROR->show();
    $time = date('Y/m/d H:i:s');
    $common = isOutOfAnswerPeriod() ? <<<__HTML__
<div class="alert">基本設定の「loginページのフォーム非表示」設定日時を過ぎています。</div>
__HTML__
:"";
    $html =<<<HTML
<table class="cont">
<tr><th>現在の日時 : </th><td><b>{$time}</b></td></tr>
</table>
{$common}

{$error}

<form action="{$PHP_SELF}" method="POST">
<table class="searchbox">
HTML;

    foreach ($events as $v) {

        $name = $v['name'];
        $evid = $v['evid'];
        $type = $v['type'];
        if(is_void($prev_type))
            $prev_type = $type;

        /* 全表示に変更 */
//		if (!isset ($term_datas[$evid][$type]))
//			continue;

        $data = $term_datas[$evid][$type];

        $data['s'] = (is_void($data['s']))? "2001/01/01 00:00:00":$data['s'];
        $data['e'] = (is_void($data['e']))? "2000/01/01 00:00:00":$data['e'];
        $s = strtotime($data['s']);
        $e = strtotime($data['e']);
        $now = time();
        if ($s > $e) {
            $koukai = '<span style="color:black;">公開しない</span>';
        } elseif ($s < $now && $now < $e) {
            $koukai = '<span style="color:red;font-weight:bold;">公開中</span>';
        } elseif ($e < $now) {
            $koukai = '<span style="color:black;">公開済み</span>';
        } elseif ($s > $now) {
            $koukai = '<span style="color:blue;font-weight:bold;">公開予定</span>';
        }

        if ($prev_type != $type) {
            $html .= "<tr><td colspan='2'>&nbsp;</td></tr>";
            $prev_type = $type;
        }

        $html .=<<<HTML
<tr>
<th class="td1" style="text-align:left;">{$name}</th>
<td class="td2"><nobr>
<input class="date" name="term[{$evid}][{$type}][s]" value="{$data['s']}" style="width:140px;ime-mode:disabled;"{$disabled}> 〜
<input class="date" name="term[{$evid}][{$type}][e]" value="{$data['e']}" style="width:140px;ime-mode:disabled;"{$disabled}>　{$koukai}
</nobr></td>
</tr>
HTML;

    }
    $html .=<<<HTML
</table>
<div style="width:800px;text-align:center">
　<input type="submit" name="mode:write" value="設定" style="width:120px" class="white button">
</div>
</form>
HTML;

    return $html;
}

function setTermData($new_term_datas)
{
    $term_datas = array();
    foreach ($new_term_datas as $evid => $term) {
        foreach ($term as $type => $data) {
            $term_datas[$evid][$type] = $data;
        }
    }

    FDB::begin();
    foreach ($term_datas as $evid => $term) {
        foreach ($term as $type => $data) {
            $indb = FDB::select1(T_FROMTO, "evid,type", "WHERE evid = ".FDB::escape($evid)." AND type = ".FDB::escape($type));
            if (is_good($indb)) {
                $array = array("sdate" => FDB::escape($data['s']), "edate" => FDB::escape($data['e']));
                $res = FDB::update(T_FROMTO, $array, "WHERE evid = ".FDB::escape($evid)." AND type = ".FDB::escape($type));
            } else {
                $array = array(
                    "evid" => FDB::escape($evid),
                    "type" => FDB::escape($type),
                    "sdate" => FDB::escape($data['s']),
                    "edate" => FDB::escape($data['e'])
                );
                $res = FDB::insert(T_FROMTO, $array);
            }
            if (is_false($res)) {
                FDB::rollback();

                return false;
            }
        }
    }
    FDB::commit();
    clearFromtoCache();

    return true;
}
