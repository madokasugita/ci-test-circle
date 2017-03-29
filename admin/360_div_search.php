<?php
/**
 * PGNAME:回答状況確認
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
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . 'MreAdminHtml.php');

if(!$_POST["csvdownload"])
    encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

/****************************************************************************************************/

define('PHP_SELF', getPHP_SELF() . '?' . getSID());

/****************************************************************************************************/
function main()
{
    if ($_POST['mode'] == 'delete') {
        if ($_POST['hash'] != getHash360($_POST['serial_no'])) {
            $p = new CbasePage();
            $p->addErrorMessage("削除に失敗しました。");
            $message =  $p->getErrorMessage();
        } else {
            $message = deleteDiv($_POST['serial_no']);

        }
    }
    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new SortTable(new SortAdapter(),new ThisSortView(), true);
    $sl = & new SearchList($c, $s);
    $body = $sl->show(array (
        //141 検索ボタンを押す前は結果を表示しない
        //'op' => 'sort'
    ));
    $body = str_replace('%%%%PHP_SELF%%%%',$s->getLink(),$body);
    $objHtml = new MreAdminHtml("組織マスタ検索");
    $objHtml->setExFix();
    $DIR_IMG = DIR_IMG;
    $SID = getSID();
    $action = PHP_SELF;
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>
<div style="text-align: left; width: 1050px;">
<form action="360_div_edit.php?{$SID}&mode=new" method="post" target="_blank">
<div class="button-container"><div class="button-group">
    <input id="newdata"type="submit" value="新規作成" class="white button">
</div></div>
</form>
</div>
{$message}
{$body}
<div id="scrollmenu" class="button-container"><div class="button-group">
    <button class='button white' id="totop">トップへ</button>
    <form action="360_div_edit.php?{$SID}"method="post"target="_blank"><input type="hidden" name="mode" value="new">
        <button class="button white">新規作成</button>
    </form>
</div></div>
HTML;
    print $objHtml->getMainHtml($body);
    exit;
}
/****************************************************************************************************/
class ThisSortView extends ResearchSortTableView
{
    public function getListTd($body, $colname)
    {
        if (in_array($colname,array('div1','div2','div3'))) {
            return <<<__HTML__
<td class="break">{$body}</td>
__HTML__;
        }


        $s = $this->getColStyle($colname);
        //デフォルト値を活かすため。
        return $s? RDTable::getTd ($body, $s): RDTable::getTd ($body);
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

        return<<<__HTML__
<form action="{$action}" method="post">
<table class="searchbox">
{$body}
</table>
{$submit}
</form>
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
        $TABLE = T_DIV;

        return FDB :: getAssoc("SELECT * FROM {$TABLE} {$where}");
    }

    public function getCount($where)
    {
        $TABLE = T_DIV;
        $count = FDB :: getAssoc("SELECT count(*) as count FROM {$TABLE} {$where}");

        return $count[0]['count'];
    }
    public function getCsvFileName()
    {
        return date('Ymd').'組織マスタ'.DATA_FILE_EXTENTION;
    }
    public function getCsvColumns()
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        $a = array_merge(parent::getCsvColumns(), array(
            'div1_name_1'=>$label['div1'].'表示名(English)',
            'div2_name_1'=>$label['div2'].'表示名(English)',
            'div3_name_1'=>$label['div3'].'表示名(English)',
            'div1_name_2'=>$label['div1'].'表示名(繁体字)',
            'div2_name_2'=>$label['div2'].'表示名(繁体字)',
            'div3_name_2'=>$label['div3'].'表示名(繁体字)',
            'div1_name_3'=>$label['div1'].'表示名(簡体字)',
            'div2_name_3'=>$label['div2'].'表示名(簡体字)',
            'div3_name_3'=>$label['div3'].'表示名(簡体字)',
            'div1_name_4'=>$label['div1'].'表示名(韓国語)',
            'div2_name_4'=>$label['div2'].'表示名(韓国語)',
            'div3_name_4'=>$label['div3'].'表示名(韓国語)',
            ));

        return $a;
    }
    public function getColumns()
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        $array = array (
            'button' => '　',
            'div1'=>$label['div1'].'コード',
            'div1_name'=>$label['div1'].'表示名(日本語)',
            'div1_sort'=>$label['div1'].'並び順',
            'div2'=>$label['div2'].'コード',
            'div2_name'=>$label['div2'].'表示名(日本語)',
            'div2_sort'=>$label['div2'].'並び順',
            'div3'=>$label['div3'].'コード',
            'div3_name'=>$label['div3'].'表示名(日本語)',
            'div3_sort'=>$label['div3'].'並び順',
        );

        return $array;

    }

    public function getNoSortColumns()
    {
        return array (
            "button",
            "pw"
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];
        switch ($key) {
            default :
                if ($value !== null && $value !== '') {
                    return $key . " like " . FDB :: escape('%' . trim($value) . '%');
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
        return 'div1_sort,div2_sort,div3_sort DESC';
    }

    public function getColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case 'button':
                return getHtmlDivEditButton(serialize(array($data['div1'], $data['div2'], $data['div3'])),"{$data['div1_name']} {$data['div2_name']} {$data['div3_name']}");
            case 'div2':
                list($div1,$div2)=explode('_',$data[$key]);

                return html_escape($div2);
            case 'div3':
                list($div1,$div2,$div3)=explode('_',$data[$key]);

                return html_escape($div3);

            default :
                return html_escape($data[$key]);
        }
    }
    public function getCsvColumnValue($data, $key)
    {
        return $this->getColumnValue($data, $key);
    }
}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        return array (
            'div1'=>$label['div1'].'コード',
            'div1_name'=>$label['div1'].'表示名(日本語)',
        //	'div1_sort'=>'####div_name_1####並び順',
            'div2'=>$label['div2'].'コード',
            'div2_name'=>$label['div2'].'表示名(日本語)',
        //	'div2_sort'=>'####div_name_2####並び順',
            'div3'=>$label['div3'].'コード',
            'div3_name'=>$label['div3'].'表示名(日本語)',
        //	'div3_sort'=>'####div_name_3####並び順',
        );
    }
    public function getColumnForms($def, $key)
    {
        switch ($key) {
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


function getHtmlDivEditButton($serial_no,$name)
{
    $hash = getHash360($serial_no);
    $serial_no = html_escape($serial_no);
    $name = html_escape($name);
    $SID =getSID();

    return<<<HTML
<nobr>
<form action="360_div_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="edit">
<input type="submit" value="編集"class="button white">
</form>
<form action="%%%%PHP_SELF%%%%" method="post" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="delete">
<input type="submit" value="削除" onclick="return myconfirm('「{$name}」 を削除しようとしています。<br>本当に削除してもよろしいですか？')"class="button white">
</form>
</nobr>
HTML;

}

function deleteDiv($serial_no)
{
    $p = new CbasePage();

    $divs = unserialize($serial_no);
    if (is_false($divs)) {
        $p->addErrorMessage("失敗しました");

        return $p->getErrorMessage();
    }

    $where = array();
    $where[] = "div1=".FDB::escape($divs[0]);
    $where[] = "div2=".FDB::escape($divs[1]);
    $where[] = "div3=".FDB::escape($divs[2]);
    $rs = FDB::delete(T_DIV,'where '.implode(' and ', $where));
    if (is_false($rs)) {
        $p->addErrorMessage("失敗しました");

        return $p->getErrorMessage();
    }
    clearDivCache();
    $p->addErrorMessage("組織を削除しました");

    return $p->getErrorMessage();
}

/****************************************************************************************************/
main();
