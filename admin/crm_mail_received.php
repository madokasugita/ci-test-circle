<?php
/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'ResearchSortListView.php');
require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'JSON.php');
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . 'DateForm.php');

if(!$_POST["csvdownload"])
    encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/

define('PHP_SELF', getPHP_SELF() . '?' . getSID());
define('PAGE_DETAIL_VIEW', 'crm_mail_received_view.php'. '?' . getSID());
/****************************************************************************************************/

function main()
{
    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new SortTable(new SortAdapter(), new ThisSortView(800), true);
    $sl = & new SearchList($c, $s);
    $body = $sl->show(array ( 'get_limit' => '50', 'op' => 'search'));
    $body = str_replace('%%%%PHP_SELF%%%%', $s->getLink().'&sort='.(int) $_REQUEST['sort'].'&desc='.(int) $_REQUEST['desc'], $body);
    $objHtml = & new ResearchAdminHtml("受信メール一覧");
    $DIR_IMG = DIR_IMG;
//	$getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}scrolltopcontrol.js"></script>
<h1>受信メール一覧</h1>
{$message}
{$body}
{$getHtmlReduceSelect}
HTML;
    print $objHtml->getMainHtml($body);
    exit;
}
/****************************************************************************************************/
class ThisSortView extends ResearchSortTableView
{
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
<input type="submit" name="op[search]" value="検索">
__HTML__;
    }

    public function getBox($row, $hidden, $action)
    {
        $body = $this->getBody($row);
        $submit = $hidden . $this->getSubmitButton();

        return<<<__HTML__
<form action="{$action}" method="post">
<style>
.searchbox{
    border-collapse:collapse;
    margin-bottom:10px;

}
.searchbox td{
    border:solid 1px black;
    padding:2px;

}

.tr1{
    background-color:#666666;
    color:white;
}
.tr2{
    background-color:#f2f2f2;
}
</style>
<div style="text-align:left;margin:20px 0 0 30px">
<table class="searchbox">
{$body}
</table>
{$submit}
</div>

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
    <td class="tr1">{$key}</td>
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
        $T_MAIL_RECEIVED = T_MAIL_RECEIVED;
        $sql =<<<SQL
select * from
{$T_MAIL_RECEIVED}
{$where};
SQL;

        return FDB :: getAssoc($sql);
    }

    public function getCount($where)
    {
        $T_MAIL_RECEIVED = T_MAIL_RECEIVED;
        $sql =<<<SQL
select count(*) as count from
{$T_MAIL_RECEIVED}
{$where};
SQL;
        $count = FDB :: getAssoc($sql);

        return $count[0]['count'];
    }
//	function getCsvFileName()
//	{
//		return date('Ymd').'承認者情報'.DATA_FILE_EXTENTION;
//	}
    public function getColumns()
    {
        global $_360_user_type;
        $array = array (
//			"name" => "名前",
//			"response_flag" => "返信",
            "mail_from" => "from",
            "mail_to" => "to",
            "title" => "件名",
//			"body" => "本文",
            "rdate" => "日時",
//			"response_status" => "ステータス",
        );

        return limitColumn($array);
    }

    public function getNoSortColumns()
    {
        return array (
                        "response_flag",
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];

        switch ($key) {
            case 'rdate':
                $s = new DateForm();;
                $cond = array();
                if ($s-> isSetAllValue($value['s'])) {
                    $s->setValue($value['s']);
                    $cond[] = FDB::escape($s->format('Y-m-d')).'<='.$key;;
                }
                $e = new DateForm();;
                if ($e->isSetAllValue($value['e'])) {
                     $e->setValue($value['e']);
                    $cond[] = $key.'<='.FDB::escape($e->format('Y-m-d'));
                }
                if($cond) return '('.implode(' AND ', $cond).')';
                else return '';

            case 'response_status':
            case 'response_flag':
                if (isset($value) && $value !== "") {
                    $cond = array();
                    if (in_array(0, $value)) {
                        $cond[] = $key.' IS NULL';
                    }
                    $cond[] =  $key.' IN ('.implode(',', FDB::escapeArray($value)).')';

                    return '('.implode(' OR ', $cond).')';
                }

                return '';


            default :
                if ($value !== null && $value !== '') {
                    return $key . " like " . FDB :: escape('%' . $value . '%');
                }
                break;
        }

        return null;
    }

    public function getDefaultOrder()
    {
        return 'rdate DESC';
    }

    public function getColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case 'mail_from':
                $page_detail_view = PAGE_DETAIL_VIEW;
                $from = html_escape($val);

                return $from;
//				return <<<__HTML__
//<a href="{$page_detail_view}&mail_from={$from}">{$from}</a>
//__HTML__;
            case 'title':
                $page_detail_view = PAGE_DETAIL_VIEW;
                $val = html_escape($val);
                $from = html_escape($data['mail_from']);
                $rid = html_escape($data['mail_received_id']);

                return <<<__HTML__
<a href="{$page_detail_view}&mail_from={$from}#mail-rid-{$rid}">{$val}</a>
__HTML__;
            case 'response_status':
                $res = getResponseStatusArray();

                return $res[$val] ? html_escape($res[$val]) : "";
            case 'response_flag':
                $res = getResponseFlagArray();

                return $res[$val] ? html_escape($res[$val]) : "";
            default :
                return html_escape($data[$key]);
        }
    }
}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
//		global $_360_user_type;
        $array = array (
            "mail_from" => "from",
            "mail_to" => "to",
            "title" => "件名",
            "body" => "本文",
//			"response_flag" => "返信",
//			"response_status" => "ステータス",
            "rdate" => "日時",
        );

        return $array;
    }
    public function getColumnForms($def, $key)
    {
//		global $_360_sheet_type,$_360_select_status;

        switch ($key) {
            case 'rdate':
                $s = new DateForm();;
                if($def['rdate']['s']) $s->setValue($def['rdate']['s']);
                $e = new DateForm();;
                if($def['rdate']['e']) $e->setValue($def['rdate']['e']);

                return $s->getSimpleForm('rdate[s]').'〜'.$e->getSimpleForm('rdate[e]');
            case 'response_status':
                $list = getResponseStatusArray ();

                return implode('', FForm::checkboxlistDef($key.'[]', $list, $def[$key]));
                break;
            case 'response_flag':
                $list = getResponseFlagArray ();

                return implode('', FForm::checkboxlistDef($key.'[]', $list, $def[$key]));
                break;

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

function getResponseStatusArray()
{
    return array(
        10 => '未対応',
        20 => '対応済',
    );
}

function getResponseFlagArray()
{
    return array(
        0=>'未返信',
        1=>'返信済',
    );
}
/****************************************************************************************************/
main();
