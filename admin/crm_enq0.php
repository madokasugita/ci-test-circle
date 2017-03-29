<?php
define('DIR_ROOT', "../");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseEnquete.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'ResearchSortListView.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));

/* 複製モード */
if ($_POST['copy']) {
    if (is_good($_POST['from']) && is_good($_POST['to'])) {
        $from = (int) $_POST['from'];
        $to = (int) $_POST['to'];

        $res = array();
        $res[] = FDB::begin();
        $event = FDB::select1(T_EVENT,'*',"where evid = {$from}");
        FDB::delete(T_EVENT,'where evid = '.$to);
        $event['evid'] = $to;
        $event['rid'] = 'rid00'.$to;
        $res[] = FDB::insert(T_EVENT,FDB::escapeArray($event));
        $subevents = FDB::select(T_EVENT_SUB,'*',"where evid = {$from} order by seid");
        $res[] = FDB::delete(T_EVENT_SUB,'where evid = '.$to);
        $res[] = FDB::delete(T_EVENT_SUB,'where evid = 0');
        foreach ($subevents as $k => $subevent) {
            $subevent['evid'] = $to;
            $subevent['seid'] = $to*1000+$k;
            $subevent['html2'] = str_replace("%%%%id{$from}","%%%%id".$to,$subevent['html2']);
            $subevent['html2'] = str_replace("%%%%messageid{$from}","%%%%messageid".$to,$subevent['html2']);
            $subevent['html2'] = str_replace("%%%%messageid_div{$from}","%%%%messageid_div".$to,$subevent['html2']);
            $subevent['html2'] = str_replace("%%%%num_ext:id{$from}","%%%%num_ext:id".$to,$subevent['html2']);
            $subevent['html2'] = str_replace("%%%%title:id{$from}","%%%%title:id".$to,$subevent['html2']);
            $subevent['html2'] = str_replace("%%%%category1:id{$from}","%%%%category1:id".$to,$subevent['html2']);
            $subevent['html2'] = str_replace("%%%%category2:id{$from}","%%%%category2:id".$to,$subevent['html2']);

            /* 条件置き換え */
            foreach (range(1,5) as $i) {
                $ii = ($i == 1)? "":$i;
                if ($subevent['cond'.$ii]) {
                    $subevent['cond'.$ii] = preg_replace("/[0-9]{3}([0-9]{3}):/",$to."$1:",$subevent['cond'.$ii]);
                }
            }

            $res[] = FDB::insert(T_EVENT_SUB,FDB::escapeArray($subevent));
        }
        $result = true;
        foreach ($res as $r) {if(is_false($r)) $result=false;}
        $result = ($result)? FDB::commit():false;
        clearSheetCache();
        if($result)
            $MESSAGE = "<script>$().toastmessage('showSuccessToast', 'シートが複製されました');</script>";
        else
            $MESSAGE = "<script>$().toastmessage('showNoticeToast', 'シートが複製に失敗しました');</script>";
    } else {
        $MESSAGE = "<script>$().toastmessage('showWarningToast', '入力値が不正です');</script>";
    }
}
/* 削除モード */
if ($_POST['mode'] == "delete") {
    if(deleteSheet((int) $_POST['sheet_type']))
        $MESSAGE = "<script>$().toastmessage('showSuccessToast', 'シートが削除されました');</script>";
}
/* 追加モード */
if ($_POST['add']) {
    if(addSheet())
        $MESSAGE = "<script>$().toastmessage('showSuccessToast', '新しいシートが追加されました');</script>";
}
/* キャッシュ削除 */
if ($_POST['clear_all']) {
    foreach (FDB::select(T_EVENT,'rid') as $array) {
        FEnqueteCache::setLatestBackUpEvent($array["rid"], true);
        transClearCache($array["rid"]);
    }
    clearSheetCache();
    $MESSAGE = "<script>$().toastmessage('showNoticeToast', '全てのシートのキャッシュをクリアしました');</script>";
}

$evid_arr = array();

$body .= $MESSAGE;
$sheet_table = getSheetTable($evid_arr);
$clear_cache_button = getClearCacheButton($evid_arr);
$body .= $clear_cache_button . $sheet_table;

$objHtml = new MreAdminHtml("評価シート一覧");
$objHtml->setTools();
echo $objHtml->getMainHtml($body);
exit;

function getClearCacheButton($evid_arr)
{
    $PHP_SELF = getPHP_SELFwithSID();
    $D360 = new D360();
    $html_options = array();
    foreach ($evid_arr as $v) {
        $html_options[] = '<option value="'.$v['evid'].'">'.$v['evid_disp'].' : '.$v['sheet_name'].' '.$v['user_name'].'</option>';
    }
    $html_options = implode("\n", $html_options);

    return<<<HTML

<div class="button-container">

<div class="button-group">

<form action="{$PHP_SELF}" method="post">
{$D360->getIconButton("submit", "add", "ui-icon-plusthick", "新しいシートを追加する")}
</form>

<form action="{$PHP_SELF}" method="post">
<input type="hidden" name="clear_all" value="1">
<input type="submit" value="全評価シートのキャッシュをクリア" class="white button">
</form>

<input type="button" value="上書きコピーする" class="white button" rel="#mies1">

</div>

</div>

<div class="simple_overlay" id="mies1">
    <div class="simple_overlay_title">シートIDを選択して下さい</div>
    <div class="simple_overlay_details">
<form action="{$PHP_SELF}" method="post" name="override_form">
コピー元ID:
<select name="from">
{$html_options}
</select>
<br>
コピー先ID:
<select name="to">
{$html_options}
</select>
<br>

<input type="hidden" name="copy" value="1">
<input type="submit" value="上書きコピーする" class="white button">
</form>
<script type="text/javascript">
$(function () {
    $('form[name="override_form"]').submit(function () {
        var from = $('form[name="override_form"] :input[name="from"] :selected').text();
        var to   = $('form[name="override_form"] :input[name="to"] :selected').text();

        return confirm('『'+from+'』から、\\n『'+to+'』へコピーします。\\nよろしいですか？');
    });
});
</script>
</div>
</div>
HTML;
}

function getSheetTable(&$evid_arr)
{
    global $Setting;
    $PHP_SELF = getPHP_SELFwithSID();
    $html =<<<HTML
<table class="cont">
<tr>
    <th style="width:30px">ID</th><th style="width:150px">シート名</th><th style="width:100px">動作確認</th><th style="width:100px">画面確認</th><th style="width:140px">編集</th><th style="width:40px">削除</th>
</tr>
HTML;
    foreach ($GLOBALS['_360_sheet_type'] as $sheet_type => $sheet_name) {
        foreach ($GLOBALS['_360_user_type'] as $user_type => $user_name) {
            if ($user_type > INPUTER_COUNT || $Setting->sheetModeCollect() && $user_type == 2)
                break;
            $evid = $sheet_type*100+$user_type;
            if ($Setting->sheetModeCollect() && $user_type == 1) {
                $user_names = array();
                $evids = array();
                foreach ($GLOBALS['_360_user_type'] as $user_type_ => $user_name_) {
                    if(!$user_type_)
                        continue;
                    if ($user_type_ > INPUTER_COUNT)
                        break;
                    $evids[] = $sheet_type*100+$user_type_;
                    $user_names[] = $user_name_;
                }
                $evid_disp = implode(',',$evids);
                $user_name = implode(' , ',$user_names);
            } else {
                $evid_disp = $evid;
            }

            $links = array();
            $links2 = array();
            foreach ($GLOBALS['_360_language'] as $k => $l) {
                if($k==-1)
                    continue;
                $k = (int) $k;
                $prev   = DOMAIN.DIR_MAIN.PG_PREVIEW.'?lang360='.$k.'&rid='.Create_QueryString(Get_RandID(8),getRidByEvid($evid), 1, 'A');
                $links[] = "<a href=\"{$prev}\" target=\"_blank\">{$l}</a>";

                $prev   = DOMAIN.DIR_MAIN.'preview.php'.'?lang360='.$k.'&rid='.Create_QueryString(Get_RandID(8),getRidByEvid($evid), 1, 'A');
                $links2[] = "<a href=\"{$prev}\" target=\"_blank\">{$l}</a>";

            }
            $link = implode(' / ',$links);
            $link2 = implode(' / ',$links2);

            $edit = '<button onClick="location.href=\'enq_event.php?evid='.$evid.'&'.getSID().'\'" class="white button">シート設定</button>';
            $edit .= '<button onClick="window.open(\'enq_subevent.php?evid='.$evid.'&'.getSID().'\')" target="_blank" class="white button">質問設定</button>';
            $delete = "";
            $separator = "";
            if ($before_sheet_type != $sheet_type) {
                $span = ($Setting->sheetModeCollect())? 2:INPUTER_COUNT+1;
                $onclick = 'return myconfirm("対象シートの回答データも削除されます。\nまた、シートの対象者設定、紐付けも削除されます。\n\n削除実行しますか？")';
                $delete = <<<__HTML__
<td rowspan="{$span}" align=center><form action="{$PHP_SELF}" method="post"><input value="削除" class="white button" type="submit" onClick='{$onclick}'><input type="hidden" value="delete" name="mode"><input type="hidden" name="sheet_type" value="{$sheet_type}"></form></td>
__HTML__;
                $before_sheet_type = $sheet_type;
            }

            $evid_arr[] = array('evid' => $evid, 'evid_disp' => $evid_disp, 'sheet_name' => $sheet_name, 'user_name' => $user_name);

            $html .=<<<HTML
<tr>
    <td align=center>{$evid_disp}</td>
    <td>{$sheet_name} {$user_name}</td>
    <td>{$link}</td>
    <td>{$link2}</td>
    <td align=center>{$edit}</td>
    {$delete}
</tr>
HTML;
        }
    }
    $html .= <<<__HTML__
</table>
__HTML__;

    return $html;
}

function deleteSheet($sheet_type)
{
    FDB::begin();

    $where = 'where (FLOOR(evid/100)) = '.$sheet_type;
    /* シート削除 */
    $r = FDB::delete(T_EVENT, $where);
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }
    /* 質問削除 */
    $r = FDB::delete(T_EVENT_SUB, $where);
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }
    /* 回答データ削除 */
    $r = FDB::delete(T_EVENT_DATA, $where);
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }
    /* 質問回答データ削除 */
    $r = FDB::delete(T_EVENT_SUB_DATA, $where);
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }
    /* シート名削除 */
    $r = FDB::delete(T_MESSAGE, 'WHERE mkey = '.FDB::escape("sheet_type".$sheet_type));
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }
    /* 紐付削除 */
    $r = FDB::delete(T_USER_RELATION, 'WHERE uid_a IN (SELECT uid FROM '.T_USER_MST.' WHERE  sheet_type = '.FDB::escape($sheet_type).')');
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }
    /* 対象者解除 */
    $r = FDB::update(T_USER_MST, FDB::escapeArray(array('sheet_type'=>0, 'mflag'=>0)), 'WHERE sheet_type = '.FDB::escape($sheet_type));
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }

    FDB::commit();
    clearSheetCache();
    unset($GLOBALS['_360_sheet_type'][$sheet_type]);

    return true;
}

function addSheet()
{
    FDB::begin();
    $sheet_type = max(array_keys($GLOBALS['_360_sheet_type']))+1;

    /* 本人シート追加 */
    $event = array();
    $event['evid'] = $sheet_type*100;
    $event['rid'] = 'rid'.sprintf("%05d",$event['evid']);
    $r = FDB::insert(T_EVENT,FDB::escapeArray($event));
    if (is_false($r)) {
        FDB::rollback();

        return false;
    }

    /* 他者シート追加 */
    foreach (range(1, INPUTER_COUNT) as $user_type) {
        $event = array();
        $event['evid'] = $sheet_type*100 + $user_type;
        $event['rid'] = 'rid'.sprintf("%05d",$event['evid']);
        $r = FDB::insert(T_EVENT, FDB::escapeArray($event));
        if (is_false($r)) {
            FDB::rollback();

            return false;
        }
    }

    clearSheetCache();
    $GLOBALS['_360_sheet_type'][$sheet_type] = add_or_get_SheetName($sheet_type);
    FDB::commit();

    return true;
}

function add_or_get_SheetName($sheet_type)
{
    $message = FDB::select1(T_MESSAGE, "body_0", 'WHERE mkey = '.FDB::escape("sheet_type".$sheet_type));
    if(is_good($message))

        return $message['body_0'];

    $message = array();
    $message['mkey'] = "sheet_type".$sheet_type;
    $message['place1'] = $message['place2'] = "全体";
    foreach(range(0,4) as $i)
        $message['body_'.$i] = "360-degree ".sprintf("%03d", $sheet_type);
    if(is_false(FDB::insert(T_MESSAGE, FDB::escapeArray($message))))

        return false;
    clearMessageCache();

    return $message['body_0'];
}
