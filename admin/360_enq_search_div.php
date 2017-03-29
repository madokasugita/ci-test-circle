<?php
/**
 * PGNAME:回答状況確認
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/
define('PAGE_TITLE',"回答状況検索(所属別)");
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
require_once (DIR_LIB . 'MreAdminHtml.php');

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
    $v = new ThisSortView(1000);
    $v->setColStyle('count', 'style="text-align:right;"');
    $v->setColStyle('count1', 'style="text-align:right;"');
    $v->setColStyle('count2', 'style="text-align:right;"');
    $v->setColStyle('count3', 'style="text-align:right;"');
    $s = & new ThisSortTable(new SortAdapter(), $v, true);
    $sl = & new SearchList($c, $s);
    $body = $sl->show(array (
        'op' => 'sort',
        'evid'=>EVID
    ));
    $title = PAGE_TITLE;
    $objHtml = new MreAdminHtml(PAGE_TITLE);
    $objHtml->setExFix();
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $body =<<<HTML
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
        return <<<__HTML__
<div class="button-container float-left"><div class="button-group">
    <input type="submit" name="op[search]" value="検索" class="white button">
</div></div>
<div class="button-container float-left" style="margin-left:15px;"><div class="button-group">
    <input type="submit" name="csvdownload" value="結果をダウンロード"class="white button">
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
<table class="searchbox"style="width:auto">
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
    public function getResult($where, $limit="")
    {
        global $GDF;

        if (!$_REQUEST['op'])
            return array ();
        $T_USER_MST = T_USER_MST;
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_USER_RELATION = T_USER_RELATION;
        $evid = EVID;

        $sheet_type = getSheetTypeByEvid(EVID);
        $user_type = getUserTypeByEvid(EVID);

        switch ($_REQUEST['div']) {
            case 2:
                $div = "u1.div1,u1.div2,u1.div3";
                break;
            case 1:
                $div = "u1.div1,u1.div2";
                break;
            case 0;
            default:
                $div = "u1.div1";
                break;
        }

        $SQL=<<<SQL
SELECT
count(u1.serial_no) as count,
{$div},
sum(CASE WHEN answer_state=20 THEN 1 WHEN answer_state IS NULL THEN 1 ELSE 0 END) AS count1,
sum(CASE WHEN answer_state=10 THEN 1 ELSE 0 END) AS count2,
sum(CASE WHEN answer_state=0 THEN 1 ELSE 0 END) AS count3
FROM
(SELECT * FROM
    (SELECT serial_no,uid,name,name_,div1,div2,div3,test_flag,0 as user_type ,uid as target FROM {$T_USER_MST} WHERE mflag = 1) as dummy
    UNION ALL
    (SELECT u.serial_no,u.uid,u.name,u.name_,u.div1,u.div2,u.div3,u.test_flag,r.user_type,r.uid_a as target from {$T_USER_RELATION} r LEFT JOIN  {$T_USER_MST} u on r.uid_b = u.uid AND r.user_type <= {$GDF->get('INPUTER_COUNT')})
) as u1
LEFT JOIN {$T_USER_MST} u2 on u1.target = u2.uid
LEFT JOIN {$T_EVENT_DATA} EV on EV.evid = u2.sheet_type*100+user_type and EV.serial_no = u1.serial_no and EV.target = u2.serial_no
{$where}
GROUP BY {$div}
{$limit}
SQL;

        return FDB :: getAssoc($SQL);
    }

    public function getCount($where)
    {
        global $GDF;

        if (!$_REQUEST['op'])
            return 0;
        $T_USER_MST = T_USER_MST;
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_USER_RELATION = T_USER_RELATION;
        $evid = EVID;
        $sheet_type = getSheetTypeByEvid(EVID);
        $user_type = getUserTypeByEvid(EVID);

        switch ($_REQUEST['div']) {
            case 2:
                $div = "u1.div1,u1.div2,u1.div3";
                break;
            case 1:
                $div = "u1.div1,u1.div2";
                break;
            case 0;
            default:
                $div = "u1.div1";
                break;
        }

        $SQL=<<<SQL
SELECT count(*) as count FROM (
SELECT
{$div}
FROM
(SELECT * FROM
    (SELECT serial_no,uid,name,name_,div1,div2,div3,test_flag,0 as user_type ,uid as target FROM {$T_USER_MST} WHERE mflag = 1) as dummy
    UNION ALL
    (SELECT u.serial_no,u.uid,u.name,u.name_,u.div1,u.div2,u.div3,u.test_flag,r.user_type,r.uid_a as target from {$T_USER_RELATION} r LEFT JOIN  {$T_USER_MST} u on r.uid_b = u.uid AND r.user_type <= {$GDF->get('INPUTER_COUNT')})
) as u1
LEFT JOIN {$T_USER_MST} u2 on u1.target = u2.uid
LEFT JOIN {$T_EVENT_DATA} EV on EV.evid = u2.sheet_type*100+user_type and EV.serial_no = u1.serial_no and EV.target = u2.serial_no
{$where}
GROUP BY {$div}
) as U
SQL;

        $count = FDB :: getAssoc($SQL);

        return $count[0]['count'];
    }

    public function getCsvColumns()
    {

        $column_data = getColmunSetting();
        $label = $column_data['label'];
        $array = array (
        //	"answer_state" => "回答状況",
        //	"sheet_type" => "シートタイプ",
        //	"user_type" => "入力区分",
            "div1" => $label['div1'],
            "div2" => $label['div2'],
            "div3" => $label['div3'],
            "count" => "全体",
            "count1" => "×未回答",
            "count2" => "△回答途中",
            "count3" => "○回答済み"
        );

        switch ($_REQUEST['div']) {
            case 2:
                break;
            case 1:
                unset($array['div3']);
                break;
            case 0;
            default:
                unset($array['div3']);
                unset($array['div2']);
                break;
        }

        return $array;

    }
    public function getCsvFilename()
    {
        return date('YmdHis').'所属別回答状況'.DATA_FILE_EXTENTION;
    }
    public function getColumns()
    {

        $column_data = getColmunSetting();
        $label = $column_data['label'];
        $array = limitColumn(array (
        //	"sheet_type"=>"シート<br>タイプ",
        //	"user_type"=>"入力<br>区分",
            "div1" => $label['div1'],
            "div2" => $label['div2'],
            "div3" => $label['div3'],
            "count" => "全体",
            "count1" => "×未回答",
            "count2" => "△回答途中",
            "count3" => "○回答済み"
        ));

        switch ($_REQUEST['div']) {
            case 2:
                break;
            case 1:
                unset($array['div3']);
                break;
            case 0;
            default:
                unset($array['div3']);
                unset($array['div2']);
                break;
        }

        return $array;

    }

    public function getNoSortColumns()
    {
        return array (
            "count",
            "count1",
            "count2",
            "count3"
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];

        if ($value === null || $value === '')
            return null;
        switch ($key) {
            case "evid" :
            case "div" :
                return null;
            case "div1" :
            case "div2" :
            case "div3" :
                if ($value != "default")
                    return "u1.{$key} = " . FDB :: escape($value);
                else
                    return null;

            case "test_flag":
                if($value == 1)

                    return null;
                if($value == 2)

                    return "u1.test_flag = 1";

                return "u1.test_flag != 1";

            default :
                return $key . " like " . FDB :: escape('%' . $value . '%');
                break;
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
        return getDivWhere("u1");
    }



    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return $_POST['div'];
    }

    public function getColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case 'sheet_type':
                return $GLOBALS['_360_sheet_type'][$val];
            case 'user_type':
                return $GLOBALS['_360_user_type'][$val];
            case "div1" :
                return getDiv1NameById($val);
            case "div2" :
                return getDiv2NameById($val);
            case "div3" :
                return getDiv3NameById($val);
            case "count1" :
            case "count2" :
            case "count3" :
                $per = sprintf("%01.1f", (!is_zero((int) $data['count']))? ($val / $data['count'] * 100):0);

                return "{$val} ({$per}%)";
            default :
                return html_escape($data[$key]);
        }
    }

}

class ThisSortTable extends SortTable
{
    public function setResult ($cond=array(), $post=array())
    {
        $this->cond =& $cond;
        if(!$this->cond) $this->cond = array();
        $where = implode(' AND ', $this->makeCond($cond));
        $where = $where? ' WHERE '.$where: '';
        $this->count = $this->adapter->getCount($where);
        $limit = $this->getOrder($post).' LIMIT '.$this->limit.$this->getOffset($post);
        $this->result = $this->adapter->getResult($where, $limit);

        $this->isSetResult = true;
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
            "div" => "集計レベル",
            "div1" => $label['div1'],
            "div2" => $label['div2'],
            "div3" => $label['div3'],
            "test_flag" => "テストユーザー"
        );

    }
    public function getColumnForms($def, $key)
    {
        $val = $def[$key];
        switch ($key) {
            case "evid" :
                return FForm :: hidden($key, $def[$key]);
            case "div" :
                $array = array("####div_name_1####", "####div_name_2####", "####div_name_3####");

                return implode("", FForm::replaceArrayChecked(FForm::radiolist($key, $array), (is_good($def[$key]))? $def[$key]:0));
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

            case "test_flag":
                $array = array("含まない", "含む", "テストユーザーのみ");

                return implode("", FForm::replaceArrayChecked(FForm::radiolist($key, $array), (is_good($def[$key]))? $def[$key]:0));


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

/*
CSVをダウンロードする場合
上部に追加
if(!$_POST["csvdownload"])
    encodeWebAll();


class ThisCondTableView
{
    public function getSubmitButton()
    {
        return <<<__HTML__
<input type="submit" name="op[search]" value="検索"> <input type="submit" name="csvdownload" value="結果をダウンロード">
__HTML__;
    }
}

class SortAdapter
{
    public function getCsvFilename()
    {
        return date('YmdHis').'回答状況_'.replaceMessage(ENQ_NAME).'.csv';
    }
    public function getCsvColumns()
    {
        return array (
            "uid" => "ユーザID",
            "name" => "名前",
            "div1" => "####div_name_1####",
            "div2" => "####div_name_2####",
            "div3" => "####div_name_3####",
            "mflag" => "対象者フラグ",
            "sheet_type" => "シートタイプ",
            "email" => "メールアドレス",
            "memo" => "メモ"
        );
    }
}
*/
