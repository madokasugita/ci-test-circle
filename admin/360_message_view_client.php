<?php

/**
 * PGNAME:文言編集
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
//require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
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
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . 'CbaseFCrypt.php');
require_once (DIR_LIB . 'MreAdminHtml.php');

if ($_GET['csvdownload']) {
    $_POST['op'] = 'search';
    $_POST["csvdownload"] = '1';
}

if(!$_POST["csvdownload"])
    encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/

define('PHP_SELF', getPHP_SELF() . '?' . getSID());

/****************************************************************************************************/

function main()
{
    if ($_POST['mode'] == 'delete') {
        if ($_POST['hash'] != getHash360($_POST['msgid'])) {
            $p = new CbasePage();
            $p->addErrorMessage("削除に失敗しました。");
            $message =  $p->getErrorMessage();
        } else {
            $p = new CbasePage();
            $p->addErrorMessage("削除しました。");
            $message =  $p->getErrorMessage();
            FDB::delete(T_MESSAGE,'where msgid = '.FDB::escape($_POST['msgid']));

        }
    }
    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new ThisSortTable(new SortAdapter(), new ThisSortView(800), true);
    $sl = & new SearchList($c, $s);
    $body = $sl->show(array ('get_limit' => '50', 'op' => 'search' ) );

    $body = str_replace('%%%%PHP_SELF%%%%',$s->getLink(),$body);

    $objHtml = new MreAdminHtml("文言管理", $titleExp="", $refresh = true, $standAlone = false, $blank = false);
    $objHtml->setExFix();
    $SID = getSID();
    $SESSION_key = session_name();
    $SESSION_val = session_id();
    $hash = getHash360("");
    $DIR_IMG = DIR_IMG;
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>

<body>
<form action="360_message_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="msgid" value="">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="edit">
<div class="button-container"><div class="button-group">
    <input id="newdata"type="submit" value="新規登録"class="white button">
</div></div>
</form>
{$message}
{$body}
<div id="scrollmenu" class="button-container"><div class="button-group">
    <input type='button' class='button white' id="totop"value="トップへ"></button>
    <form action="360_message_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;"><input type="hidden" name="msgid" value=""><input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="edit"><input id="newdata"type="submit" value="新規登録"class="button white"></form>
</div></div>
HTML;
    print $objHtml->getMainHtml($body);
    exit;
}
/****************************************************************************************************/



class ThisSortTable extends SortTable
{
    public function downLoadCsv($cond=array(), $post=array())
    {
        $this->cond =& $cond;
        if(!$this->cond) $this->cond = array();

        $where = implode(' AND ', $this->makeCond($cond));

        $where = $where? ' WHERE '.$where: '';

        $header = $this->adapter->getCsvColumns();
        $csv = array();
        $csv[] = $header;
        foreach ($this->adapter->getResult($where) as $data) {
            $line = array();
            foreach ($header as $k=>$v) {
                $line[] = html_unescape($this->adapter->getCsvColumnValue($data ,$k));
            }
            $csv[] = $line;
        }
        $filename = $this->adapter->getCsvFilename();
        csv_download_utf8_tag($csv,$filename);
        exit;
    }

}



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
    public function getListTd($body, $colname)
    {
        $s = $this->getColStyle($colname);

        switch ($colname) {
            case 'button':
                return "<td width='100'><nobr>{$body}</nobr></td>";
            default:
                return "<td id=\"{$colname}\"><nobr>{$body}</nobr></td>";
        }
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
        $TABLE = T_MESSAGE;

        return FDB :: getAssoc("SELECT * FROM {$TABLE} {$where}");
    }

    public function getCount($where)
    {
        $TABLE = T_MESSAGE;
        $count = FDB :: getAssoc("SELECT count(*) as count FROM {$TABLE} {$where}");

        return $count[0]['count'];
    }
    public function getCsvFileName()
    {
        return date('Ymd').'文言管理'.DATA_FILE_EXTENTION;
    }
    public function getColumns()
    {
        $array = array (
            "button" => "　",
            "mkey" => "キー",
            "place1" => "場所1",
            "place2" => "場所2",
            "type" => "種類",
            "name" => "タイトル",
            //"memo" => "説明",
            "body_0" => "日本語",
        );

        return $array;

    }
    public function getCsvColumns()
    {
        $array = array(
            "mkey" => "キー",
            "place1" => "場所1",
            "place2" => "場所2",
            "type" => "種類",
            "name" => "タイトル"
        );
        foreach ($GLOBALS['_360_language'] as $k => $v) {
            $array['body_'.$k] = $v;
        }
        $array['memo'] = 'メモ';

        return $array;
    }
    public function getNoSortColumns()
    {
        return array (
            "button",
            "pw",
            "url",
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];


        switch ($key) {
            case "div1" :
                if ($value != "default")
                    return "{$key} = " . FDB :: escape($value);
                else
                    return null;
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
        $keys = implode(',',array_map(create_function('$v','return FDB::escape($v);'),explode(',',MESSAGE_KEY_CLIENT_EDIT)));

        return "mkey in({$keys})";
    }

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'msgid DESC';
    }

    public function getColumnValue($data, $key)
    {
        global $_360_sheet_type;
        $val = $data[$key];
        switch ($key) {
            case 'button':
                if (hasAuthUserEdit()) {
                    return getHtmlEditButton($data['msgid']);
                }
            case 'name':
            case 'body_0':
            case 'body_1':
            case 'body_2':
            case 'body_3':
            case 'body_4':
            case 'body_5':
                return mb_strimwidth(strip_tags($val),0,40,'...');
            default :
                return html_escape($data[$key]);
        }
    }
}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
        return array (
            "mkey" => "キー",
            "place1" => "場所1",
            "place2" => "場所2",
            "type" => "種類",
            "name" => "タイトル",
            //"memo" => "説明",
            "body_0" => "日本語",
            "body_1" => "英語",

        );
    }
    public function getColumnForms($def, $key)
    {
        global $_360_sheet_type;
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


function getHtmlEditButton($msgid)
{
    $hash = getHash360($msgid);
    $SID =getSID();

    return<<<HTML
<form action="360_message_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="msgid" value="{$msgid}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="dup">
<input type="submit" value="複製"class="imgbutton35">
</form>
<form action="360_message_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="msgid" value="{$msgid}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="edit">
<input type="submit" value="編集"class="imgbutton35">
</form>
<form action="360_message_view.php?{$SID}" method="post" style="display:inline;margin:0px;">
<input type="hidden" name="msgid" value="{$msgid}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="delete">
<input type="submit" value="削除" onclick="return confirm('本当に削除しますか？')"class="imgbutton35">
</form>
HTML;

}

/****************************************************************************************************/
main();
