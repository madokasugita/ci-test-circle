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
    $objHtml = new MreAdminHtml("代理ログイン");
    $objHtml->setExFix();
    $DIR_IMG = DIR_IMG;
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>
{$message}
{$body}
{$getHtmlReduceSelect}
<div id="scrollmenu" class="button-container"><div class="button-group">
    <button class='button white' id="totop">トップへ</button>
</div></div>
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
        $width = array_merge($width,getColmunWidth('user_evaluator_search'));
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
    public function getBox($row, $hidden, $action)
    {
        $body = $this->getBody($row);
        $submit = $hidden . $this->getSubmitButton();
        //201012 No106 ヒアドキュのtable部分に文言追加
        return<<<__HTML__
<form action="{$action}" method="post">
<table style="font-size:100%"><tr><td>
<table class="searchbox">
{$body}
</table>

</td><td>
<div style="margin:0px 60px;width:380px;padding:20px;border:dotted 2px black;background-color:#f2f2f2">
<font size="2">
この画面から代理ログインを行った場合、<br>
マイページリンク表示期間設定での設定値は反映されず、<br>
常に全てのリンクが表示されますので、ご注意ください。
</font>

</div>

</td>
</tr></table>
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
        $ADMIT_USER_TYPE = ADMIT_USER_TYPE;
        $VIEWER_USER_TYPE = VIEWER_USER_TYPE;
        $INPUTER = range(1, INPUTER_COUNT);
        $INPUTERS = implode(",", $INPUTER);

        $sql =<<<__SQL__
select *,
(select count(*) as count from {$T_USER_RELATION} where uid_b = uid and user_type in ({$INPUTERS})) as countall,
__SQL__;
        foreach($INPUTER as $i)
        $sql .= <<<__SQL__
(select count(*) as count from {$T_USER_RELATION} where uid_b = uid and user_type={$i}) as count{$i},
__SQL__;
        $sql .= <<<__SQL__
(select count(*) as count from {$T_USER_RELATION} where uid_b = uid and user_type={$ADMIT_USER_TYPE}) as count_a,
(select count(*) as count from {$T_USER_RELATION} where uid_b = uid and user_type={$VIEWER_USER_TYPE}) as count_v
from {$T_USER_MST} {$where};
__SQL__;

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
        return date('Ymd').'代理ログイン'.DATA_FILE_EXTENTION;
    }
    public function getColumns()
    {
        global $_360_user_type;
        $array = array();
        $array['button'] = " ";
        $array = array_merge($array,getColmunLabel('user_evaluator_search'));
        $array['countall'] = "回答数合計";
        foreach (range(1, INPUTER_COUNT) as $i)
            $array['count' . $i] = $_360_user_type[$i] . "<br>回答数";

        $array['count_a'] = "承認数";
        $array['count_v'] = "参照数";

        return limitColumn($array,1);

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
            case "mflag" :
                switch ($value) {
                    case 'all';

                        return null;
                    case '1' :
                        return "mflag = 1";
                    case '0' :
                        return "mflag = 0";
                }
            case "countall" :
                if (!is_numeric($value))
                    return null;
                return "(select count(*) as count from {$T_USER_RELATION} where uid_b = uid) = " . FDB :: escape($value);
            case "count1" :
                if (!is_numeric($value))
                    return null;
                return "(select count(*) as count from {$T_USER_RELATION} where uid_b = uid  and user_type=1) = " . FDB :: escape($value);
            case "count2" :
                if (!is_numeric($value))
                    return null;
                return "(select count(*) as count from {$T_USER_RELATION} where uid_b = uid  and user_type=2) = " . FDB :: escape($value);
            case "count3" :
                if (!is_numeric($value))
                    return null;
                return "(select count(*) as count from {$T_USER_RELATION} where uid_b = uid  and user_type=3) = " . FDB :: escape($value);
            case "count4" :
                if (!is_numeric($value))
                    return null;
                return "(select count(*) as count from {$T_USER_RELATION} where uid_b = uid  and user_type=4) = " . FDB :: escape($value);
            case "count5" :
                if (!is_numeric($value))
                    return null;
                return "(select count(*) as count from {$T_USER_RELATION} where uid_b = uid  and user_type=5) = " . FDB :: escape($value);

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
        return getDivWhere();
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

                return '';
            case 'select_status' :

                if($val)

                    return '<span style="color:blue;font-weight:bold">'.getSelectStatusName($val).'</span>';
                else
                    return '<span style="color:red;font-weight:bold">'.getSelectStatusName($val).'</span>';
            default :
                return get360Value($data,$key);
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
            "mflag" => "　",
            "sheet_type" => $label['sheet_type'],
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
            case "mflag" :
                if ($def[$key] === null)
                    $def[$key] = 'all';
                $radiolist = implode('', FForm :: radiolist('mflag', array (
                    'all' => '全て',
                    '1' => '対象者',
                    '0' => '非対象者'
                )));

                return FForm :: replaceChecked($radiolist, $def[$key]);
            case "countall" :
            case "count1" :
            case "count2" :
            case "count3" :
            case "count4" :
            case "count5" :
                return FForm :: text($key, $def[$key], '', 'style="ime-mode:disabled;text-align:center;width:20px"');
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
    $sid = getHiddenSID();

    return<<<HTML
<form action="proxy_input_redirect.php" method="post" target="_blank" style="display:inline;margin:0px;">
{$sid}
<input type="hidden" name="serial_no" value="{$serial_no}">
<input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="top">
<input type="submit" value="代理ログイン"class="imgbutton90">
</form>
HTML;

}
/****************************************************************************************************/
main();
