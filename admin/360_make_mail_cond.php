<?php
define('PAGE_TITLE',"メール配信条件作成");
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
    $s = & new ThisSortTable($sa = new SortAdapter(), new ThisSortView(1000), true);
    $sa->condsave = is_good($_POST['condsave']);
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
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>
<script type="text/javascript" src="{$DIR_IMG}searchlist.js"></script>
<script type="text/javascript" src="{$DIR_IMG}table_check.js"></script>
<script type="text/javascript" src="{$DIR_IMG}jquery.exfixed.1.2.2-min.js"></script>
<script type="text/javascript" src="{$DIR_IMG}jquery.tools.min.js"></script>
{$body}
{$getHtmlReduceSelect}
<div id="scrollmenu" class="button-container"><div class="button-group">
    <button class='button white' id="totop">トップへ</button>
</div></div>
HTML;

    if (is_good($_POST['condsave'])) {
        $data = array();
        if ($data['name'] = $_POST['condname']) {
            $data['strsql'] = $sa->strsql;
            $data['muid'] = $_SESSION["muid"];
            MailCondition::save($data);
            $message_cond = '<span style="color:red;font-weight:bold">メール条件を保存しました</span>';
        } else {
            $message_cond = '<span style="color:red;font-weight:bold">条件名を入力してください</span>';
        }
    }
    $body = str_replace('%%%%mail_cond_message%%%%',$message_cond,$body);
    print $objHtml->getMainHtml($body);
    exit;
}
/****************************************************************************************************/
class ThisSortView extends ResearchSortTableView
{
    public function __construct()
    {
        parent::__construct();
        $this->colGroup['answer_state'] = 'style="text-align:center"';
        $this->colGroup['delete'] = 'style="text-align:center"';
        $this->colGroup['return'] = 'style="text-align:center"';
        $this->colGroup['url'] = 'style="text-align:center"';

        $width = array();
        $width['answer_state'] = '25';
        $width['delete'] = '30';
        $width['return'] = '30';
        $width['url'] = '40';
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
        /***************************************/
        //回答率計算
        /*
        $count = $GLOBALS['answer_state'][20]+$GLOBALS['answer_state'][10]+$GLOBALS['answer_state'][0];
        $percent20 = sprintf("%01.1f", ($GLOBALS['answer_state'][20] / $count * 100));
        $percent10 = sprintf("%01.1f", ($GLOBALS['answer_state'][10] / $count * 100));
        $percent0 = sprintf("%01.1f", ($GLOBALS['answer_state'][0] / $count * 100));
        *?
        /***************************************/

        $action = PHP_SELF;
        $link = $sortTable->getChangePageLink();
        $next = $sortTable->getNextPageLink($link);
        $prv = $sortTable->getPreviousPageLink($link);
        $navi = $sortTable->getPageNavigateLink($link);
        $offset = $sortTable->offset + 1;
        $max = min($sortTable->count, $sortTable->offset + $sortTable->limit);

        $table = RDTable :: getTBody($body, $this->tableWidth);

        return<<<__HTML__
<!--
    <div align="left" style="background-color:#ffffff;font-weight:bold;">
        全体:{$count}　×未回答:{$GLOBALS['answer_state'][20]} ({$percent20}%)　△回答途中:{$GLOBALS['answer_state'][10]} ({$percent10}%)　○回答済み:{$GLOBALS['answer_state'][0]} ({$percent0}%)　
    </div>
-->

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
        $SID =getSID();
        $html = <<<__HTML__
<div class="button-container" style="float:left;"><div class="button-group">
    <input type="submit" name="op[search]" value="　　　検索 　　　" class="white button">
</div></div>
__HTML__;

        if (isset($_POST['op']['search'])) {
            $html.= <<<__HTML__
<script>
$(function () {
    $("input[rel]").overlay({
        mask: {
            color: '#fff',
            loadSpeed: 100,
            opacity: '0.3'
        }
    });
    $('#show-save-mail').click(function (ev) {
        ev.preventDefault();
        $('#cond').css('display', 'block');
    });
});
</script>
<div class="simple_overlay" id="overlay">
    <div class="simple_overlay_title">メール配信条件名を入力して下さい</div>
    <div class="simple_overlay_details">
        メール配信条件名:<input name="condname" value="">
    <input type="submit" name="condsave" value="保存"class="button white">
    <a class="close button white">キャンセル</a>
    </div>
</div>

<div class="button-container" style="float:left;margin-left:15px;"><div class="button-group">
    <span id="condbutton"><input type="submit" name="op[save]" value="　　　メール配信条件として保存 　　　" class="white button" id="show-save-mail" rel="#overlay">%%%%mail_cond_message%%%%</span>
</div></div>

<div style="clear:both;"></div>
__HTML__;
        }
        $html.= "</form>";

        return $html;
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

class ThisSortTable extends SortTable
{
}

class SortAdapter extends SortTableAdapter
{
    public function getResult($where)
    {
        global $GDF;

        if (!$_REQUEST['op'])
            return array ();

        if ($this->condsave) {
            $where = preg_replace('/ORDER BY.+$/i', '', $where);
            $this->strsql = "SELECT * FROM usr ".$where;
        }

        $users = FDB :: SELECT(T_USER_MST, "*", $where);
        if(is_void($users))

            return array();

        $serial = array();
        foreach ($users as $v) {
            $serial[] = $v['serial_no'];
        }
        $serial = implode(",", FDB::escapeArray($serial));

        $SQL = <<<SQL
SELECT
u1.serial_no,user_type,answer_state
FROM
(SELECT serial_no,user_type,target FROM
    (SELECT serial_no,0 as user_type ,uid as target FROM {$GDF->get('T_USER_MST')} WHERE mflag = 1) as dummy
    UNION ALL
    (SELECT u.serial_no,r.user_type,r.uid_a as target from {$GDF->get('T_USER_RELATION')} r LEFT JOIN {$GDF->get('T_USER_MST')} u on r.uid_b = u.uid AND r.user_type <= {$GDF->get('INPUTER_COUNT')})
) as u1
LEFT JOIN {$GDF->get('T_USER_MST')} on u1.target = usr.uid
LEFT JOIN {$GDF->get('T_EVENT_DATA')} ev on ev.evid = usr.sheet_type*100+user_type and ev.serial_no = u1.serial_no and ev.target = usr.serial_no
WHERE u1.serial_no IN({$serial})
SQL;
        $_states = FDB :: getAssoc($SQL);
        $states = array();
        foreach ($_states as $_state) {
            if($_state['answer_state'] === null || $_state['answer_state']<0)
                $_state['answer_state'] = 20;
            $states[$_state['serial_no']][$_state['user_type']][$_state['answer_state']]++;
        }
        unset($_states, $serial);

        foreach ($users as $k => $user) {
            foreach ($GLOBALS['_360_user_type'] as $type => $type_name) {
                if($type > INPUTER_COUNT) continue;
                $users[$k]['done'] += $states[$user['serial_no']][$type][0];
                $users[$k]['yet'] += $states[$user['serial_no']][$type][10];
                $users[$k]['none'] += $states[$user['serial_no']][$type][20];
                $users[$k]['done_'.$type] = $states[$user['serial_no']][$type][0];
                $users[$k]['yet_'.$type] = $states[$user['serial_no']][$type][10];
                $users[$k]['none_'.$type] = $states[$user['serial_no']][$type][20];
            }
        }

        return $users;
    }

    public function getCount($where)
    {
        global $GDF;

        if (!$_REQUEST['op'])
            return 0;

        $count = FDB :: SELECT(T_USER_MST, "count(serial_no) as count", $where);

        return $count[0]['count'];
    }

    public function getColumns()
    {
        $array = limitColumn(array (
        ));
        $array = array_merge($array,getColmunLabel('enq_search_all'));
        //$array["udate"]="更新日時";
        $array["done"]="回<br>答<br>済";
        $array["yet"]="途<br>中";
        $array["none"]="未<br>回<br>答";

        return $array;

    }

    public function getNoSortColumns()
    {
        return array (
            "checkbox",
            "pw",
            "url",
            "done",
            "yet",
            "none"
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
            case "answer_state";
                if(count($value)==3 || count($value)==0)

                    return null;
                $or = array();
                foreach ($value as $v) {
                    switch ($v) {
                        case '-10' :
                            $or[] = "(answer_state is null or answer_state = -10)";
                            break;
                        case '10' :
                            $or[] = "answer_state = 10";
                            break;
                        case '0' :
                            $or[] = "answer_state = 0";
                            break;
                    }
                }
                if(count($or))

                    return '('.implode(' or ',$or).')';

            case "user_type";
                if(count($value)==INPUTER_COUNT+1 || count($value)==0)

                    return sprintf('user_type < %d', INPUTER_COUNT+1);
                return 'user_type in ('.implode(' , ',$value).')';

            case "sheet_type";
                if(count($value)==count($GLOBALS['_360_sheet_type']) || count($value)==0)

                    return null;
                return 'sheet_type in ('.implode(' , ',$value).')';

            case "cond":
                if($value == 0) return null;

                $not_exist_cond = '';
                if($value == 1) {
                    $res = "NOT EXISTS (";
                    $not_exist_cond = ' AND usr.serial_no = a.serial_no ';
                } else {
                    $res = "serial_no IN (";
                }


                $res .= <<<__SQL__
SELECT a.serial_no FROM usr a
LEFT JOIN event_data b ON a.serial_no = b.serial_no and b.evid%100 = 0
WHERE a.mflag = 1 and (b.answer_state <> 0 or b.answer_state is null) {$not_exist_cond} 
__SQL__;
                foreach (range(1, INPUTER_COUNT) as $type) {
                    $res .= <<<__SQL__
UNION ALL
SELECT a.serial_no FROM usr_relation c
LEFT JOIN usr d ON c.uid_a = d.uid and c.user_type = {$type}
LEFT JOIN usr a ON c.uid_b = a.uid and c.user_type = {$type}
LEFT JOIN event_data b ON a.serial_no = b.serial_no and b.evid%100 = {$type} and d.serial_no = b.target WHERE d.mflag = 1 and (answer_state <> 0 or answer_state is null) {$not_exist_cond} 
__SQL__;
                }
                $res .= ")";

                return $res;

            case "test_flag":
                if($value == 1)

                    return null;
                if($value == 2)

                    return "test_flag = 1";

                return "test_flag != 1";

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
        global $GDF;
        $divs = getDivWhere();

        return <<<__SQL__
((
mflag = 1 OR
serial_no IN(SELECT serial_no FROM {$GDF->get('T_USER_RELATION')} r LEFT JOIN {$GDF->get('T_USER_MST')} u on r.uid_b = u.uid AND r.user_type <= {$GDF->get('INPUTER_COUNT')})) AND
{$divs}
)
__SQL__;
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
                $url=_360_getEnqueteURL($data['user_type'], $data['serial_no'], $data['target_serial_no'], $data['sheet_type']);
                $hash =
                $url=str_replace('./?q=','proxy_input_redirect2.php?q=',$url);
                $url .='&serial_no='.$data['serial_no'];
                $url .='&hash='.getHash360($data['serial_no']);

                return<<<HTML
<a href="{$url}" target="_blank">URL</a>
HTML;
                //IEのみ -> <button onclick="clipboardData.setData('Text','{$url}');alert('クリップボードにコピーしました')">コピー</button>
            case 'answer_state' :
                switch ($val) {
                    case null :
                        return "×";
                    case 10 :
                        return "△";
                    case 0 :
                        return "〇";
                    default :
                        return "×";
                }
            case 'sheet_type' :
                return $GLOBALS['_360_sheet_type'][$val];

            case 'done':
            case 'yet':
            case 'none':
                return ($val==0)? "":$val;

            default :
                return get360Value($data,$key);
            }
    }

}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
        return array (
            //"answer_state" => "回答状況",
            "cond" => "回答状況",
            "div1" => "####div_name_1####",
            "div2" => "####div_name_2####",
            "div3" => "####div_name_3####",
            "name" => "名前",
            "name_" => "ローマ字",
            "uid" => "ID ",
            "email" => "メールアドレス",
            "test_flag" => "テストユーザー"
        );

    }
    public function getColumnForms($def, $key)
    {
        $val = $def[$key];
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

            case "answer_state" :
                if ($val === null)
                    $val = array(-10,10,0);
                $list = implode('', FForm :: checkboxlist('answer_state[]', array ('-10' => '×未回答','10' => '△回答中','0' => '○回答済み')));
                foreach($val as $v)
                    $list = FForm :: replaceChecked($list,$v);

                return $list;

            case "user_type" :
                if ($val === null)
                    $val = range(0, INPUTER_COUNT);

                $user_type = $GLOBALS['_360_user_type'];
                unset($user_type[ADMIT_USER_TYPE]);
                unset($user_type[VIEWER_USER_TYPE]);
                $list = implode('', FForm :: checkboxlist('user_type[]', $user_type));
                foreach($val as $v)
                    $list = FForm :: replaceChecked($list,$v);

                return $list;

            case "sheet_type" :
                if ($val === null)
                    $val = range(0, INPUTER_COUNT);

                $user_type = $GLOBALS['_360_sheet_type'];
                unset($user_type[ADMIT_USER_TYPE]);
                unset($user_type[VIEWER_USER_TYPE]);
                $list = implode('', FForm :: checkboxlist('sheet_type[]', $user_type));
                foreach($val as $v)
                    $list = FForm :: replaceChecked($list,$v);

                return $list;

            case "cond":
                $array = array("全て", "未回答がない", "未回答がある");

                return implode("", FForm::replaceArrayChecked(FForm::radiolist($key, $array), (is_good($def[$key]))? $def[$key]:0));
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
