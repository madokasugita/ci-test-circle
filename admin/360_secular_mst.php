<?php

/**
 * PGNAME:経年比較マスタ管理
 * DATE  :2016/05/10
 * AUTHOR:cbase yamazaki
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'ResearchSortListView.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'JSON.php');
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'SecularApp.php');
if(!$_POST["csvdownload"])
    encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/

define('PHP_SELF', getPHP_SELF() . '?' . getSID());

// 経年比較用DBへ接続して実行
$SecularApp = new SecularApp();
$SecularApp->connectSecularDatabase();

/****************************************************************************************************/
function main()
{
    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new SortTable(new SortAdapter(), new ThisSortView(800), true);
    $sl = & new SearchList($c, $s);

    //141 検索ボタンを押す前は結果を表示しない
    $body = $sl->show(array());

    $body = str_replace('%%%%PHP_SELF%%%%', $s->getLink().'&sort='.(int) $_REQUEST['sort'].'&desc='.(int) $_REQUEST['desc'], $body);
    $objHtml = new MreAdminHtml("経年比較マスタ検索");
    $objHtml->setExFix();
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $DIR_IMG = DIR_IMG;
    $SID = getSID();
    $SECULAR_TARGET_LIMIT_COUNT = SECULAR_TARGET_LIMIT_COUNT;
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>
<script type="text/javascript" src="{$DIR_IMG}jquery/jquery.toastmessage.js"></script>
<script>
<!--
$(function() {
    // チェックボックスAjaxの処理
    $('.checkbox-target_flag').click(function() {
        var elem = $(this);
        var target_flag = (elem.prop('checked')) ? 1 : 0;
        var secular_id = elem.val();
        var ymd = elem.attr('data-ymd');
        elem.attr('disabled', true);
        $.ajax({
            type: 'POST',
            data: '{$SID}&target_flag='+target_flag+'&secular_id='+secular_id+'&ymd='+ymd,
            url: '360_secular_mst_ajax.php',
            complete: function(response) {
                elem.attr('disabled', false);
                if (response['status'] == 200 && response['responseText'] != 1) {
                    var prev_check = (elem.prop('checked')) ? false : true;
                    var result_status = response['responseText'];

                    elem.attr('checked', prev_check);
                    if (result_status == 2) {
                        alert('経年比較対象は{$SECULAR_TARGET_LIMIT_COUNT}個までしか設定できません。');
                    } else if (result_status == 3) {
                        alert('同一年月日を複数設定することはできません。');
                    } else if (result_status == 9) {
                        alert('サーバにエラーが発生しました。時間を置いてから再度実行をお願いします。');
                    } else {
                        alert('ハッシュ値「'+elem.attr('data-hash')+'」の更新処理に失敗しました。');
                    }
                } else {
                    console.log('ok');
                    $().toastmessage('showSuccessToast', '経年比較対象を更新しました。');
                }
            }
        })
    });
});
-->
</script>

{$message}
{$body}
{$getHtmlReduceSelect}
<div id="scrollmenu" class="button-container"><div class="button-group">
    <button class='button white' id="totop">トップへ</button>
</div>
HTML;
    print $objHtml->getMainHtml($body);
    exit;
}
/****************************************************************************************************/
class ThisSortView extends ResearchSortTableView
{

    public function __construct()
    {
        parent::__construct();
        $width = array();
        $width = array_merge($width,getColmunWidth('user_relation_search'));
        foreach ($width as $k => $v) {
            if(!is_numeric($v))
                continue;
            if($this->colGroup[$k])
                $this->colGroup[$k] = str_replace('style="','style="width:'.$v.'px;',$this->colGroup[$k]);
            else
                $this->colGroup[$k] = 'style="width:'.$v.'px;"';
        }
    }

    public function getBox(& $sortTable, $body)
    {
        $action = PHP_SELF;
        $link = $sortTable->getChangePageLink();
        $next = $sortTable->getNextPageLink($link);
        $prv = $sortTable->getPreviousPageLink($link);
        $navi = $sortTable->getPageNavigateLink($link);
        $offset = $sortTable->offset + 1;
        $max = min($sortTable->count, $sortTable->offset + $sortTable->limit);

        $table = RDTable :: getTBody($body, $this->tableWidth);

        return<<<__HTML__
<div class="page"id="page">
全{$sortTable->count}件中{$offset}～{$max}件を表示　
{$prv}｜{$navi}｜{$next}
</div>
</form>
{$table}
<div class="page">
全{$sortTable->count}件中{$offset}～{$max}件を表示　
{$prv}｜{$navi}｜{$next}
</div>
__HTML__;
    }
}
class ThisCondTableView extends CondTableView
{
    public function getSubmitButton()
    {
        return <<<__HTML__
<div class="button-container float-left"><div class="button-group">
    <input type="submit" name="op[search]" value="検索"class="button white">
</div></div>
<div class="clear"></div>
__HTML__;
    }
    public function getBox($row, $hidden, $action)
    {
        $body = $this->getBody($row);
        $submit = $hidden . $this->getSubmitButton();

        return<<<__HTML__
<form action="{$action}" method="post">
<table class="searchbox">
{$body}
</table>
{$submit}

__HTML__;
    }

    public function getRow($key, $value)
    {
        if (!$key) {
            return $value;
        }

        return<<<__HTML__
<tr>
    <th class="tr1">{$key}</td>
    <td class="tr2">{$value}</td>
</tr>
__HTML__;
    }
    public function getLimitChoices()
    {
        return array (
            50,
            100,
            150,
            200
        );
    }
}

class SortAdapter extends SortTableAdapter
{
    public $musrCache = array();

    public function getResult($where)
    {
        global $SecularApp;
        $T_SECULARS = T_SECULARS;
        $T_SECULAR_CONDUCTORS = T_SECULAR_CONDUCTORS;
        $sql = <<<__SQL__
SELECT
    s.hash,
    sc.table_name
FROM {$T_SECULARS} s
    INNER JOIN {$T_SECULAR_CONDUCTORS} sc ON sc.secular_id = s.id
    {$where};
__SQL__;

        $tables = array();
        foreach (FDB::getAssoc($sql) as $v) {
            $tables[$v['hash']][] = $v['table_name'];
        }

        $sql = <<<__SQL__
SELECT
    s.id,
    s.ymd,
    s.hash,
    s.uses_status,
    s.uses_type,
    s.name,
    s.modified_at,
    s.muid,
    s.target_flag
FROM {$T_SECULARS} s
    {$where};
__SQL__;

        $tmp = array();
        foreach (FDB::getAssoc($sql) as $k => $v) {
            $tmp[$k] = $v;
            $tmp[$k]['modified_at'] = date('Y年m月d日 H時i分', strtotime($v['modified_at']));
            $tmp[$k]['ymd_org'] = $v['ymd'];
            $tmp[$k]['ymd'] = substr($v['ymd'], 0, 4) . '年' . substr($v['ymd'], 4, 2) . '月' . substr($v['ymd'], 6, 2) . '日';
            $tmp[$k]['table_name'] = implode("\n", $tables[$v['hash']]);
        }
        if (is_good($tmp)) {
            $SecularApp->connectDefaultDatabase();
            foreach (FDB::select(T_MUSR, '*') as $v) {
                $this->musrCache[$v['muid']] = $v;
            }
            $SecularApp->connectSecularDatabase();
        }
        return $tmp;
    }

    public function getCount($where)
    {
        $T_SECULARS = T_SECULARS;
        $T_SECULAR_CONDUCTORS = T_SECULAR_CONDUCTORS;
        $sql = <<<__SQL__
SELECT
    count(*) as count
FROM {$T_SECULARS} s
    {$where};
__SQL__;
        $count = FDB::getAssoc($sql);
        return $count[0]['count'];
    }

    public function getColumns()
    {
        $array = array(
            'button' => '　',
            'checkbox'    => '経年比較対象',
            'ymd'         => '作成年月日',
            'name'        => '名称',
            'hash'        => 'ハッシュ',
            'uses_status' => 'ハッシュステータス',
            'uses_type'   => '種別',
            'modified_at' => '最終更新日時',
            'muid'        => '最終更新者',
            'table_name'  => 'テーブル名',
        );
        return limitColumn($array);
    }

    public function getNoSortColumns()
    {
        return array (
            "button",
            "checkbox",
            "muid",
            "table_name"
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];
        switch ($key) {
            case 'year':
                if ($value !== null && $value !== '') {
                    return "ymd like " . FDB :: escape($value . '%');
                }
                break;
            case 'name':
                if ($value !== null && $value !== '') {
                    return "name like " . FDB :: escape('%' . $value . '%');
                }
                break;
            default :
                if ($value !== '') {
                    return $key . "=" . FDB::escape($value);
                }
                break;
        }

        return null;
    }

    public function getDefaultOrder()
    {
        return 'ymd DESC, modified_at DESC';
    }

    public function getColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case 'button':
                $id = $data['id'];
                $hash = $data['hash'];
                $SID =getSID();
                $html = <<<__HTML__
<form action="360_secular_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="edit">
<input type="submit" value="編集"class="button white">
</form>
__HTML__;
                return $html;
            case 'checkbox':
                $secularId = $data['id'];
                $hash      = $data['hash'];
                $ymd       = $data['ymd_org'];
                $checked   = '';
                if ($data['target_flag'] == 1) {
                    $checked = 'checked="checked"';
                }
                $html = '-';
                if (!is_null($data['table_name']) && ($data['uses_status'] == SECULAR_USES_STATUS_IMPORTED || $data['uses_status'] == SECULAR_USES_STATUS_DISPOSAL)) {
                    $html = <<<HTML
<input class="checkbox-target_flag" type="checkbox" data-hash="{$hash}" data-ymd="{$ymd}" value="{$secularId}" {$checked}>
HTML;
                }
                return $html;
            case "muid" :
                return html_escape($this->musrCache[$val]['name']);
            case "uses_status" :
                $statuses = getSecularUsesStatuses();
                return html_escape($statuses[$val]);
            case "uses_type" :
                if ($data['uses_status'] == SECULAR_USES_STATUS_UNUSED) {
                    return '未使用';
                }
                $statuses = getSecularUsesTypes();
                return html_escape($statuses[$val]);
            case "table_name" :
                return nl2br(html_escape($val));
            default :
                return html_escape($val);
        }
    }
}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
        $array = array (
            "year" => '作成年',
            "name" => '名称',
            "target_flag" => '経年比較対象',
        );

        return $array;
    }
    public function getColumnForms($def, $key)
    {
        global $_360_sheet_type,$_360_select_status;

        switch ($key) {
            case "year" :
                $tmp = array ('' => '指定しない');
                foreach (FDB::select(T_SECULARS, 'ymd') as $k => $v) {
                    $year = substr($v['ymd'], 0, 4);
                    $tmp[$year] = $year . '年';
                }
                return FForm :: replaceSelected(FForm :: select($key, $tmp, "style='width:231px'"), $def[$key]);
            case "target_flag" :
                $tmp = array ('' => '指定しない');
                $tmp['0'] = '非対象';
                $tmp['1'] = '対象';
                return FForm :: replaceSelected(FForm :: select($key, $tmp, "style='width:100px'"), $def[$key]);

            default :

                return FForm :: text($key, $def[$key], '', 'style="width:230px"');
        }
    }
    public function getColumnValues($post, $key)
    {
        switch ($key) {
            default :
                return $post[$key];
        }
    }
}

/****************************************************************************************************/

main();
$SecularApp->connectDefaultDatabase();
