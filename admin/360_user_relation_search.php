<?php

/**
 * PGNAME:ユーザ回答者関連付け検索
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
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
if(!$_POST["csvdownload"])
    encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/

define('PHP_SELF', getPHP_SELF() . '?' . getSID());

/****************************************************************************************************/
function main()
{
    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new SortTable(new SortAdapter(), new ThisSortView(800), true);
    $sl = & new SearchList($c, $s);

    //141 検索ボタンを押す前は結果を表示しない
    $body = $sl->show(array());

    $body = str_replace('%%%%PHP_SELF%%%%', $s->getLink().'&sort='.(int) $_REQUEST['sort'].'&desc='.(int) $_REQUEST['desc'], $body);
    $objHtml = new MreAdminHtml("回答者選定検索");
    $objHtml->setExFix();
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $DIR_IMG = DIR_IMG;
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>

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
<div class="button-container float-left" style="margin-left:15px"><div class="button-group">
    <input type="submit" name="csvdownload" value="結果をダウンロード"class="button white">
</div></div>
<div class="button-container float-left" style="margin-left:15px"><div class="button-group">
    <input type="submit" name="op[search]" value="最新の情報に更新"class="button white">
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
    /**
     * @param string $key   safe html
     * @param string $value safe html
     */
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
    public function getResult($where)
    {
        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $INPUTER_COUNT = (int) INPUTER_COUNT;

        foreach (range(1, INPUTER_COUNT) as $t) {
            $each_count[] = <<<__SQL__
(select count(*) as count from {$T_USER_RELATION} where uid_a = uid and user_type= {$t}) as count{$t}
__SQL__;
        }
        $each_count = implode(',', $each_count);
        $sql =<<<SQL
select *,
(select count(*) as count from {$T_USER_RELATION} where uid_a = uid and user_type <= {$INPUTER_COUNT}) as countall,
{$each_count}
from {$T_USER_MST} {$where};
SQL;

        return FDB :: getAssoc($sql);
    }

    public function getCount($where)
    {

        $T_USER_MST = T_USER_MST;
        $count = FDB :: getAssoc("SELECT count(*) as count FROM {$T_USER_MST} {$where}");

        return $count[0]['count'];
    }
    public function getCsvFileName()
    {
        return date('Ymd').'回答者'.DATA_FILE_EXTENTION;
    }
    public function getColumns()
    {
        global $_360_user_type;
        $array = array();
        $array['button'] = " ";
        $array['select_status'] = "選定状況";
        $array = array_merge($array,getColmunLabel('user_relation_search'));
        $array['countall'] = '回答者<br>合計数';
        foreach ($_360_user_type as $k => $v) {
            if (!$k || $k>INPUTER_COUNT)
                continue;

            $array['count' . $k] = $v . "数";
        }

        return limitColumn($array);
    }

    public function getNoSortColumns()
    {
        return array (
            "button"
        );
    }

    public function makeCond($values, $key)
    {
        $T_USER_RELATION = T_USER_RELATION;
        $value = $values[$key];
        switch ($key) {
            case "div1" :
            case "div2" :
            case "div3" :

                if ($value != "default")
                    return "{$key} = " . FDB :: escape($value);
                else
                    return null;
            case "mflag" :
                switch ($value) {
                    case 'all';

                        return null;
                    case '1' :
                        return "mflag = 1";
                    case '0' :
                        return "mflag = 0";
                }
            case "sheet_type" :
                switch ($value) {
                    case 'all';

                        return null;
                    default :
                        return "{$key} = " . FDB :: escape($value);
                }
            case "select_status":

                if(is_numeric($value))

                    return "{$key} = " .(int) $value;
            default :
                if ($value !== null && $value !== '') {
                    return $key . " like " . FDB :: escape('%' . $value . '%');
                }
                break;
        }

        return null;
    }
    public function getDefaultCond()
    {
        return 'mflag = 1  and ' . getDivWhere();
    }

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'mflag DESC, '.$this->getSecondOrder();
    }

    public function getSecondOrder()
    {
        return 'uid DESC';
    }

    public function getColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case 'button' :
                if (hasAuthUserEdit()) {
                    return getHtmlUserEditButton($data['serial_no']);
                }
            case 'mflag' :
                switch ($val) {
                    case 1 :
                        return "対象者";

                    default :
                        return "非対象者";
                }
            case 'select_status' :
                if($val==count($GLOBALS['_360_select_status'])-1)

                    return '<span style="color:blue;font-weight:bold">'.getSelectStatusName($val).'</span>';
                else
                    return '<span style="color:red;font-weight:bold">'.getSelectStatusName($val).'</span>';
            case "div1" :
                return getDiv1NameById($val);
            case "div2" :
                return getDiv2NameById($val);
            case "div3" :
                return getDiv3NameById($val);
            case 'sheet_type' :
                return getSheetTypeNameById($val);
            case "send_mail_flag":
                if ($val)
                    return '停止';

                return '送信';
            default :
                return html_escape($data[$key]);
        }
    }
}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
        global $_360_user_type;
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        $array = array (
            "sheet_type" => $label['sheet_type'],
            "select_status"=>"選定状況",
            "div1" => $label['div1'],
            "div2" => $label['div2'],
            "div3" => $label['div3'],
            "name" => $label['name'],
            "name_" => $label['name_'],
            "uid" => $label['uid'],
            "email" => $label['email'],
        );

        return $array;
    }
    public function getColumnForms($def, $key)
    {
        global $_360_sheet_type,$_360_select_status;

        switch ($key) {
            case "div1" :
            case "div2" :
            case "div3" :
                $div = array (
                    'default' => '指定しない'
                );
                foreach (getDivList($key) as $k => $v) {
                    $div[$k] = $v;
                }
                if ($key == 'div1')
                    return FForm :: replaceSelected(FForm :: select($key, $div, "style='width:230px' onChange='reduce_options(\"id_div1\",\"id_div2\");reduce_options(\"id_div2\",\"id_div3\");' id='id_div1'"), $def[$key]);

                if ($key == 'div2')
                    return FForm :: replaceSelected(FForm :: select($key, $div, "style='width:230px' onChange='reduce_options(\"id_div2\",\"id_div3\");' id='id_div2'"), $def[$key]);

                return FForm :: replaceSelected(FForm :: select($key, $div, "style='width:230px' id='id_div3'"), $def[$key]);
            case "sheet_type" :
                $tmp = array ();
                $tmp['all'] = "指定しない";
                foreach ($_360_sheet_type as $k => $v)
                    $tmp[$k] = $v;

                return FForm :: replaceSelected(FForm :: select($key, $tmp), $def[$key]);
            case "select_status" :
                $tmp = array ();
                $tmp[''] = "指定しない";
                if(!$def[$key] && $def[$key]!=="0")
                    $def[$key] = '';
                foreach ($_360_select_status as $k => $v)
                    $tmp[$k] = $v;

                return FForm :: replaceChecked(implode(' ',FForm :: radiolist($key, $tmp)), $def[$key]);
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

function getHtmlUserEditButton($serial_no)
{
    $hash = getHash360($serial_no);
    $SID=getSID();

    return<<<HTML
<form action="360_user_relation_view.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="top">
<input type="submit" value="詳細"class="imgbutton35">
</form>
HTML;

}
/****************************************************************************************************/
main();
