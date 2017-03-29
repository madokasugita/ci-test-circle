<?php
/**
 * PGNAME:回答状況確認
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/
define('PAGE_TITLE',"対象者FB検索");
/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'ResearchSortListView.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');

if(!$_POST["csvdownload"])
    encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));
//$evid = Check_AuthMngEvid($_REQUEST['evid']);
$sheet_type = getSheetTypeByEvid($evid);
$user_type = getUserTypeByEvid($evid);
/****************************************************************************************************/
define('EVID', $evid);
define('SHEET_TYPE', $sheet_type);
define('USER_TYPE', $user_type);
define('PHP_SELF', getPHP_SELF() . '?' . getSID() . '&evid=' . $evid);

/****************************************************************************************************/
function main()
{
    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new SortTable(new SortAdapter(), new ThisSortView(1000), true);
    $sl = & new SearchList($c, $s);
    $body = $sl->show(array (
        'op' => 'sort',
        'evid'=>EVID
    ));
    $objHtml = new MreAdminHtml(PAGE_TITLE);
    $objHtml->setExFix();
    $title = PAGE_TITLE;
    $DIR_IMG = DIR_IMG;
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $body =<<<HTML
{$body}
{$getHtmlReduceSelect}
<div id="scrollmenu" class="button-container"><div class="button-group">
    <button class='button white' id="totop">トップへ</button>
</div></div>
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>
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
        //$this->colGroup['url'] = 'style="text-align:center"';

        $width = array();
        //$width['url'] = '100';
        $width = array_merge($width,getColmunWidth('enq_search_all'));
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
{$table}
</form>
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
        $D360 = new D360();

        return <<<__HTML__
<div class="button-container float-left"><div class="button-group">
    {$D360->getIconButton("submit", "op[search]", "ui-icon-search", "検索")}
</div></div>
<div class="button-container float-left" style="margin-left:15px"><div class="button-group">
    {$D360->getIconButton("submit", "csvdownload", "ui-icon-arrowthickstop-1-s", "結果をダウンロード")}
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
<table class="searchbox" style="width:auto;">
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
    <th class="tr1">{$key}</th>
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
        /* 即時検索させるためにコメントアウト */
//		if (!$_REQUEST['op'])
//			return array ();
        $T_USER_MST = T_USER_MST;
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_USER_RELATION = T_USER_RELATION;
        $evid = EVID;

        $sheet_type = getSheetTypeByEvid(EVID);
        $user_type = getUserTypeByEvid(EVID);

        $SQL=<<<SQL
SELECT
*
FROM usr
{$where}
SQL;

        return FDB :: getAssoc($SQL);
    }

    public function getCount($where)
    {
        /* 即時検索させるためにコメントアウト */
//		if (!$_REQUEST['op'])
//			return 0;
        $T_USER_MST = T_USER_MST;
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_USER_RELATION = T_USER_RELATION;
        $evid = EVID;
        $sheet_type = getSheetTypeByEvid(EVID);
        $user_type = getUserTypeByEvid(EVID);

        $SQL=<<<SQL
SELECT
count(*) as count
FROM usr
{$where}
SQL;

        $count = FDB :: getAssoc($SQL);

        return $count[0]['count'];
    }

    public function getCsvColumns()
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        return array (
            "sheet_type" => $label['sheet_type'],
            "div1" => $label['div1'],
            "div2" => $label['div2'],
            "div3" => $label['div3'],
            "name" => $label['name'],
            "name_"=>$label['name_'],
            "uid" => $label['uid'],
        );

    }
    public function getCsvFilename()
    {
        return date('YmdHis').'対象者FB'.DATA_FILE_EXTENTION;
    }
    public function getCsvColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case "div1" :
                return getDiv1NameById($val);
            case "div2" :
                return getDiv2NameById($val);
            case "div3" :
                return getDiv3NameById($val);
            default :
                return html_escape($data[$key]);
        }
    }

    public function getColumns()
    {

        $array = limitColumn(array (
            "url" => "WebFB",
        ));
        $array = array_merge($array,getColmunLabel('fb_search'));

        return $array;

    }

    public function getNoSortColumns()
    {
        return array (
            "url",
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];

        if ($value === null || $value === '')
            return null;
        switch ($key) {
            case "evid" :
                return null;
            case "div1" :
            case "div2" :
            case "div3" :
                if ($value != "default")
                    return "{$key} = " . FDB :: escape($value);
                else
                    return null;
            case "sheet_type";
                if(count($value)==count($GLOBALS['_360_sheet_type']) || count($value)==0)

                    return null;
                return 'sheet_type in ('.implode(' , ',$value).')';
            case "name":
                return "name like " . FDB :: escape('%' . $value . '%');
            case "name_":
                return "name_ like " . FDB :: escape('%' . $value . '%');
            case "uid":
                return "uid like " . FDB :: escape('%' . $value . '%');
            default :
                return $key . " like " . FDB :: escape('%' . $value . '%');
        }

        return null;
    }
    /**
     * ◆virtual
     * 検索時に追加される固定の条件(ユーザIDなど)があればここに書く
     * @return string where節に追加される条件部分のSQL
     */
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
            case 'url' :
                /* 本人だから user_type = 0; この時点で一番質問数が多いシートを sheet_type で指定したほうが良い? */
                $url=_360_getReviewURL( 0, $data['serial_no'], $data['serial_no'], $data['sheet_type']);
                $url=str_replace('review.php?q=','proxy_input_redirect2.php?page=review&q=',$url);
                $url .='&serial_no='.$data['serial_no'];
                $url .='&hash='.getHash360($data['serial_no']);
                $D360 = new D360();

                return $D360->getIconMiniButton("button", "fb", "ui-icon-newwin", "View", "onClick='window.open(\"{$url}\", \"_blank\")'");
                //IEのみ -> <button onclick="clipboardData.setData('Text','{$url}');alert('クリップボードにコピーしました')">コピー</button>
            case 'sheet_type' :
                return $GLOBALS['_360_sheet_type'][$val];
            default :
                return get360Value($data,$key);
            }
    }

}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        return array (
            "evid" => "",
            "sheet_type" => $label['sheet_type'],
            "div1" => $label['div1'],
            "div2" => $label['div2'],
            "div3" => $label['div3'],
            "name" => $label['name'],
            "name_" => $label['name_'],
            "uid" => $label['uid'],
            "email" => $label['email'],
        );

    }
    public function getColumnForms($def, $key)
    {
        $val = $def[$key];
        switch ($key) {
            case "evid" :
                return FForm :: hidden($key, $def[$key]);
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
                return getHtmlSheetTypeCheck($val);

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
