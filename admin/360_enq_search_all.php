<?php
/**
 * PGNAME:回答状況確認
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/
define('PAGE_TITLE',"回答状況検索(詳細)");
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
    global $MESSAGE;

    if ($_POST['delete']) {
        deleteAnswer();
    }
    if ($_POST['return']) {
        returnAnswer();
    }
    if ($_POST['bulk_delete']) {
        bulkDeleteAnswer();
    }
    if ($_POST['bulk_return']) {
        bulkReturnAnswer();
    }
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
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $DIR_IMG = DIR_IMG;
    $body .= $MESSAGE;
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>
<script type="text/javascript" src="{$DIR_IMG}searchlist.js"></script>
<script type="text/javascript" src="{$DIR_IMG}table_check.js"></script>
{$body}
{$getHtmlReduceSelect}
<div id="scrollmenu" class="button-container"><div class="button-group">
    <button class='button white' id="totop">トップへ</button>
    <button class='button white' onclick="resettarget();return false;">選択を解除する</button>
    <button class='button white' onclick="$('#mail_button').click(); return false;">メール配信予約を行なう</button>
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
        $count = $GLOBALS['answer_state'][20]+$GLOBALS['answer_state'][10]+$GLOBALS['answer_state'][0];
        $percent20 = sprintf("%01.1f", ($GLOBALS['answer_state'][20] / $count * 100));
        $percent10 = sprintf("%01.1f", ($GLOBALS['answer_state'][10] / $count * 100));
        $percent0 = sprintf("%01.1f", ($GLOBALS['answer_state'][0] / $count * 100));
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

    <div align="left" style="margin-top:20px;background-color:#ffffff;font-weight:bold;">
        全体:{$count}　×未回答:{$GLOBALS['answer_state'][20]} ({$percent20}%)　△回答途中:{$GLOBALS['answer_state'][10]} ({$percent10}%)　○回答済み:{$GLOBALS['answer_state'][0]} ({$percent0}%)　
    </div>
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
<div class="button-container"><div class="button-group">
    <input type="submit" name="op[search]" value="　　　検索 　　　"class="white button">
    <input type="submit" name="csvdownload" value="結果をダウンロード"class="white button">
    <input type="submit" name="bulk_delete" value="選択回答を一括削除" class="white button" onclick="return myconfirm('削除しますか？')">
    <input type="submit" name="bulk_return" value="選択回答を一括戻し" class="white button" onclick="return myconfirm('回答途中状態に戻しますか？')">
</div></div>
__HTML__;
    }
    public function getBox($row, $hidden, $action)
    {
        $body = $this->getBody($row);
        $submit = $hidden . $this->getSubmitButton();
        $SID =getSID();

        return<<<__HTML__
<form action="enq_mailrsv.php?{$SID}" method="post" target="_blank" onSubmit="getCheckedIds(this, 'mail_serial', 'mailtarget');">
    <div class="button-container"><div class="button-group">
        <button onclick="resettarget();return false;"class="white button">選択を解除する</button>
        <input type="submit" value="メール配信予約を行なう" class="white button" id="mail_button">
        <input type="hidden" name="mailtarget" value="">
        <input type="hidden" name="mailcookie" value="1">
    </div></div>
</form>
<form action="{$action}" method="post">
<div style="clear:both;"></div>
<table class="searchbox" style="width:auto;">
{$body}
</table>
{$submit}

__HTML__;
    }

    public function getBody($row)
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        return<<<__HTML__
<tr>
    <th class="tr1">回答状況</th>
    <td class="tr2" colspan="3">{$row['回答状況']}</td>
</tr>
<tr>
    <th class="tr1">{$label['sheet_type']}</th>
    <td class="tr2" colspan="3">{$row['シートタイプ']}</td>
</tr>
<tr>
    <th class="tr1">入力区分</th>
    <td class="tr2" colspan="3">{$row['入力区分']}</td>
</tr>
<tr>
    <th class="tr1">所属</th>
    <td class="tr2" colspan="3">{$row['####div_name_1####']}{$row['####div_name_2####']}{$row['####div_name_3####']}</td>
</tr>
<tr>
    <th class="tr1" style="width:130px">{$label['name']}</td>
    <td class="tr2" id="td_name">{$row['名前']}</td>
    <th class="tr1" style="width:130px">{$label['name_']}</td>
    <td class="tr2" id="td_name_">{$row['ローマ字']}</td>
</tr>
<tr>
    <th class="tr1">{$label['uid']}</td>
    <td class="tr2" id="td_uid">{$row['ID ']}</td>
    <th class="tr1">{$label['email']}</td>
    <td class="tr2" id="td_email">{$row['メールアドレス']}</td>
</tr>
<tr>
    <th class="tr1" style="width:130px">対象者{$label['name']}</td>
    <td class="tr2" id="td_name">{$row['対象者名前']}</td>
    <th class="tr1" style="width:130px">対象者{$label['name_']}</td>
    <td class="tr2" id="td_name_">{$row['対象者ローマ字']}</td>
</tr>
<tr>
    <th class="tr1">対象者{$label['uid']}</td>
    <td class="tr2" id="td_uid">{$row['対象者ID']}</td>
    <th class="tr1">対象者{$label['email']}</td>
    <td class="tr2" id="td_email">{$row['対象者メールアドレス']}</td>
</tr>
<tr>
    <th class="tr1">テストユーザー</th>
    <td class="tr2" colspan="3">{$row['テストユーザー']}</td>
</tr>
<tr>
    <th class="tr1">表示数</th>
    <td class="tr2" colspan="3">{$row['表示数']}</td>
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
        global $GDF;

        if (!$_REQUEST['op'])
            return array ();
        $T_USER_MST = T_USER_MST;
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_USER_RELATION = T_USER_RELATION;
        $evid = EVID;

        $sheet_type = getSheetTypeByEvid(EVID);
        $user_type = getUserTypeByEvid(EVID);

        $columns = getColmunSetting();
        $columns = array_keys($columns['label']);
        unset($columns[array_search('sheet_type', $columns)]);
        array_unshift($columns, "serial_no");

        foreach($columns as $column)
            $uColumns[] = "u.".$column;

        $columns = implode(",", $columns);
        $uColumns = implode(",", $uColumns);

        $SQL=<<<SQL
SELECT
u1.*,
u2.serial_no as target_serial_no,
u2.uid as target_id,
u2.name as target_name,
u2.name_ as target_name_,
u2.div1 as target_div1,
u2.div2 as target_div2,
u2.div3 as target_div3,
user_type,
u2.sheet_type,
ev.evid,
ev.udate,
ev.answer_state,
ev.event_data_id
FROM
(
    SELECT {$columns},0 as user_type ,uid as target FROM {$T_USER_MST} WHERE mflag = 1
    UNION ALL
    SELECT {$uColumns},r.user_type,r.uid_a as target from {$T_USER_RELATION} r LEFT JOIN  {$T_USER_MST} u on r.uid_b = u.uid AND r.user_type <= {$GDF->get('INPUTER_COUNT')}
) as u1
LEFT JOIN {$T_USER_MST} u2 on u1.target = u2.uid
LEFT JOIN {$T_EVENT_DATA} ev on ev.evid = u2.sheet_type*100+user_type and ev.serial_no = u1.serial_no and ev.target = u2.serial_no
{$where}
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

            $SQL=<<<SQL
SELECT
count(*) as count,answer_state
FROM
(SELECT * FROM
    (SELECT email,serial_no,uid,name,name_,div1,div2,div3,test_flag,0 as user_type ,uid as target FROM {$T_USER_MST} WHERE mflag = 1) as dummy
    UNION ALL
    (SELECT u.email,u.serial_no,u.uid,u.name,u.name_,u.div1,u.div2,u.div3,u.test_flag,r.user_type,r.uid_a as target from {$T_USER_RELATION} r LEFT JOIN  {$T_USER_MST} u on r.uid_b = u.uid AND r.user_type <= {$GDF->get('INPUTER_COUNT')})
) as u1
LEFT JOIN {$T_USER_MST} u2 on u1.target = u2.uid
LEFT JOIN {$T_EVENT_DATA} ev on ev.evid = u2.sheet_type*100+user_type and ev.serial_no = u1.serial_no and ev.target = u2.serial_no
{$where}
group by answer_state
SQL;

        $GLOBALS['answer_state'] = array();
        foreach (FDB :: getAssoc($SQL) as $data) {
            if ($data['answer_state'] === null || $data['answer_state']<0) {
                $data['answer_state'] = 20;
            }
            $GLOBALS['answer_state'][$data['answer_state']] = $data['count'];
        }

            $SQL=<<<SQL
SELECT
count(*) as count
FROM
(SELECT * FROM
    (SELECT email,serial_no,uid,name,name_,div1,div2,div3,test_flag,0 as user_type ,uid as target FROM {$T_USER_MST} WHERE mflag = 1) as dummy
    UNION ALL
    (SELECT u.email,u.serial_no,u.uid,u.name,u.name_,u.div1,u.div2,u.div3,u.test_flag,r.user_type,r.uid_a as target from {$T_USER_RELATION} r LEFT JOIN  {$T_USER_MST} u on r.uid_b = u.uid AND r.user_type <= {$GDF->get('INPUTER_COUNT')})
) as u1
LEFT JOIN {$T_USER_MST} u2 on u1.target = u2.uid
LEFT JOIN {$T_EVENT_DATA} ev on ev.evid = u2.sheet_type*100+user_type and ev.serial_no = u1.serial_no and ev.target = u2.serial_no
{$where}
SQL;

        $count = FDB :: getAssoc($SQL);

        return $count[0]['count'];
    }

    public function getCsvColumns()
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        return array_merge(array (
            "answer_state" => "回答状況",
            "sheet_type" => "シートタイプ",
            "user_type" => "入力区分",
        ),
        getColmunLabel('enq_search_all'),
        array(
            "target_name"=>"対象者".$label['name'],
            "target_name_"=>"対象者".$label['name_'],
            "target_id"=>"対象者".$label['uid'],
            "udate" => "更新日時",
        ));
    }
    public function getCsvFilename()
    {
        return date('YmdHis').'回答状況詳細'.DATA_FILE_EXTENTION;
    }
    public function getCsvColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
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
            "checkbox" => "<a href='#' onclick='reverse();return false;'>反転</a>",
            "answer_state" => "",
            "delete" => "回答<br>削除",
            "return" => "回答<br>中に<br>戻す",
            "url" => "回答用<br>URL",
            "user_type" => "評価者<br>タイプ",
        ));
        $array = array_merge($array,getColmunLabel('enq_search_all'));
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        $array["target_name"]="対象者<br>".$label['name'];
        $array["target_name_"]="対象者<br>".$label['name_'];
        $array["target_id"]="対象者<br>".$label['uid'];
        $array["udate"]="更新日時";

        return $array;

    }

    public function getNoSortColumns()
    {
        return array (
            "checkbox",
            "pw",
            "url",
            "delete",
            "return"
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
                    return "u1.{$key} = " . FDB :: escape($value);
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


            case "name":
                return "u1.name like " . FDB :: escape('%' . $value . '%');
            case "name_":
                return "u1.name_ like " . FDB :: escape('%' . $value . '%');
            case "email":
                return "u1.email like " . FDB :: escape('%' . $value . '%');
            case "uid":
                return "u1.uid like " . FDB :: escape('%' . $value . '%');
            case "target_id":
                return "u2.uid like " . FDB :: escape('%' . $value . '%');
            case "target_name":
                return "u2.name like " . FDB :: escape('%' . $value . '%');

            case "target_name_":
                return "u2.name_ like " . FDB :: escape('%' . $value . '%');

            case "target_email":
                return "u2.email like " . FDB :: escape('%' . $value . '%');

            case "test_flag":
                if($value == 1)

                    return null;
                if($value == 2)

                    return "(u1.test_flag = 1 OR u2.test_flag = 1)";

                return "u1.test_flag != 1 AND u2.test_flag != 1";

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
/*		$sheet_type = getSheetTypeByEvid(EVID);
        $user_type = getUserTypeByEvid(EVID);
        if($user_type == 0)

            return "A.sheet_type = {$sheet_type} and".getDivWhere("A");
        else
            return "D.sheet_type = {$sheet_type} and".getDivWhere("A");*/

        return getDivWhere("u1");
    }



    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'answer_state DESC, '.$this->getSecondOrder();
    }

    public function getSecondOrder()
    {
        return 'uid DESC';
    }

    public function getColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case 'checkbox':
                return getHtmlMailCheckBox($data['serial_no'], $data['event_data_id']);
            case 'delete' :
                if ($data['answer_state'] === "10" || $data['answer_state'] === "0")
                    return "<input type=submit name='delete[{$data['event_data_id']}]' value='削除' class='white button' onclick=\"return myconfirm('削除しますか？')\">";
                else
                return "";

            case 'return' :
                if ($data['answer_state'] === "0")
                    return "<input type=submit name='return[{$data['event_data_id']}]' value='戻す' class='white button' onclick=\"return myconfirm('回答途中状態に戻しますか？')\">";
                else
                return "";

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
            "evid" => "",
            "answer_state" => "回答状況",
            "sheet_type" => "シートタイプ",
            "user_type" => "入力区分",
            "div1" => "####div_name_1####",
            "div2" => "####div_name_2####",
            "div3" => "####div_name_3####",
            "name" => "名前",
            "name_" => "ローマ字",
            "uid" => "ID ",
            "email" => "メールアドレス",
            "target_name"=>"対象者名前",
            "target_name_"=>"対象者ローマ字",
            "target_id"=>"対象者ID",
            "target_email" => "対象者メールアドレス",
            "test_flag" => "テストユーザー",
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
                return getHtmlSheetTypeCheck($val);

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
function deleteAnswer()
{
    $res = array();
    $res[] = FDB :: begin();
    foreach ($_POST['delete'] as $key => $val) {
        if (ENQ_DATA_DELETE_MODE == 0) {
            $res[] = FDB :: delete(T_EVENT_DATA, 'where event_data_id = ' . FDB :: escape($key));
            $res[] = FDB :: delete(T_BACKUP_DATA, 'where event_data_id = ' . FDB :: escape($key));
            $res[] = FDB :: delete(T_EVENT_SUB_DATA, 'where event_data_id = ' . FDB :: escape($key));
            $method = "delete";
        } elseif (ENQ_DATA_DELETE_MODE == 1) {
            $res[] = FDB :: update(T_EVENT_DATA, array ('answer_state' => '-10'), 'where event_data_id = ' . FDB :: escape($key));
            $method = "update";
        }
        completeDeleteAnswer($res, $key, $method);
    }

}

function returnAnswer()
{
    foreach ($_POST['return'] as $key => $val) {
        $res = FDB :: update(T_EVENT_DATA, array ('answer_state' => '10'), 'where answer_state = 0 and event_data_id = ' . FDB :: escape($key));
        $method = "update";
    }
    completeReturnAnswer($res);
}

function bulkDeleteAnswer()
{
    if (!isset($_POST['mail_serial']) || is_void($_POST['mail_serial']))
        return;

    $res = array();
    $res[] = FDB :: begin();
    $event_data_id = implode(' , ', array_keys($_POST['mail_serial']));
    if (ENQ_DATA_DELETE_MODE == 0) {
        $res[] = FDB :: delete(T_EVENT_DATA, 'where event_data_id in ('.$event_data_id.')');
        $res[] = FDB :: delete(T_BACKUP_DATA, 'where event_data_id in ('.$event_data_id.')');
        $res[] = FDB :: delete(T_EVENT_SUB_DATA, 'where event_data_id in ('.$event_data_id.')');
        $method = "delete";
    } elseif (ENQ_DATA_DELETE_MODE == 1) {
        $res[] = FDB :: update(T_EVENT_DATA, array ('answer_state' => '-10'), 'where event_data_id in ('.$event_data_id.')');
        $method = "update";
    }
    completeDeleteAnswer($res, $event_data_id, $method);
}

function bulkReturnAnswer()
{
    if (!isset($_POST['mail_serial']) || is_void($_POST['mail_serial']))
        return;

    $event_data_id = implode(' , ', array_keys($_POST['mail_serial']));
    $res = FDB :: update(T_EVENT_DATA, array ('answer_state' => '10'), 'where answer_state = 0 and event_data_id in ('.$event_data_id.')');
    completeReturnAnswer($res);
}

function completeDeleteAnswer($res, $event_data_id, $method)
{
    global $MESSAGE;
    $result = true;
    foreach ($res as $r)
        if(is_false($r))
            $result = false;

    if ($result) {
        FDB::commit();
        operationLog(LOG_DELETE_ANSWER, "where event_data_id in ({$event_data_id}),method={$method}");
        $MESSAGE = "<script>$().toastmessage('showSuccessToast', '回答が削除されました');</script>";
    } else {
        FDB::rollback();
        $MESSAGE = "<script>$().toastmessage('showNoticeToast', '回答削除に失敗しました');</script>";
    }
}

function completeReturnAnswer($res)
{
    global $MESSAGE;
    if (!is_false($res)) {
        $MESSAGE = "<script>$().toastmessage('showSuccessToast', '回答中に戻されました');</script>";
    } else {
        $MESSAGE = "<script>$().toastmessage('showNoticeToast', '回答中戻しに失敗しました');</script>";
    }
}

function getHtmlMailCheckBox($serial_no, $event_data_id)
{
    return<<<HTML
<input class="mail_serial" type="checkbox" name="mail_serial[{$event_data_id}]" value="{$serial_no}" onclick='check(this)'>
HTML;
/*	return<<<HTML
<input type="checkbox" name="mail_serial[]" value="{$serial_no}" onchange='check(this)'{$checked}>
HTML;
*/
}

/****************************************************************************************************/
main();
