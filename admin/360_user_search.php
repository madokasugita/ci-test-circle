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
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'JSON.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . 'CbaseFCondition.php');
if(!$_POST["csvdownload"])
    encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/

define('PHP_SELF', getPHP_SELF() . '?' . getSID());
define('MAILTARGET_NAME', "mailtarget_360_user_search");
/****************************************************************************************************/
function main()
{

    $MAILTARGET_NAME = MAILTARGET_NAME;

    if ($_POST['mode'] == 'delete') {
        if ($_POST['hash'] != getHash360($_POST['serial_no'])) {
            $p = new CbasePageToast();
            $p->addErrorMessage("削除に失敗しました。");
            $message =  $p->getErrorMessage();
        } else {
            $message = deleteUser($_POST['serial_no']);

        }
    }
    $GLOBALS[$MAILTARGET_NAME] = array();
    foreach (explode('@',$_COOKIE[$MAILTARGET_NAME]) as $mail) {
        $GLOBALS[$MAILTARGET_NAME][] = $mail;
    }

    $ThisCond = new ThisCond();
    $c = & new CondTable($ThisCond, new ThisCondTableView(), true);
    $s = & new ThisSortTable($sa = new SortAdapter(), new ThisSortView(800), true);
    $sl = & new SearchList($c, $s);

    $body = $sl->show(array ( 'mflag' => 'all', 'sheet_type' => 'all', 'div1' => 'default', 'div2' => 'default', 'div3' => 'default', 'name' => '', 'uid' => '', 'email' => '', 'memo' => '', 'get_limit' => '50', 'op' => 'search'));

    if ($_POST['condsave']) {
        $data = array();
        if ($data['name'] = $_POST['condname']) {
            $data['strsql'] = $sa->SQL;
            $data['strsql'] = str_replace(' usr a ',' usr ',$data['strsql']);
            $data['strsql'] = str_replace(' a.',' usr.',$data['strsql']);
            $data['strsql'] = ereg_replace('ORDER BY.*$','',$data['strsql']);
            MailCondition::save($data);
            $message_cond = '<span style="color:red;font-weight:bold">メール条件を保存しました</span>';
        } else {
            $message_cond = '<span style="color:red;font-weight:bold">条件名を入力してください</span>';
        }
    }
    $body = str_replace('%%%%PHP_SELF%%%%',$s->getFullPageLink(),$body);

    $DIR_IMG = DIR_IMG;
    $objHtml = new MreAdminHtml("ユーザマスタ検索");
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $SID = getSID();
    $PHP_SELF = PHP_SELF;
    $action = $PHP_SELF;
    $body =<<<HTML
{$message}
{$body}
{$getHtmlReduceSelect}

<div id="scrollmenu" class="button-container"><div class="button-group">
    <button class='button white' id="totop">トップへ</button>
    <form action="360_user_edit.php?{$SID}&mode=new"method="post"target="_blank"><input type="submit" class="button white" value="新規作成"></form>
    <button class='button white' onclick="resettarget();return false;">選択を解除する</button>
    <button class='button white' onclick="$('#mail_button').click(); return false;">メール配信予約を行なう</button>
</div></div>

<script type="text/javascript" src="{$DIR_IMG}jquery.exfixed.1.2.2-min.js"></script>
<script type="text/javascript" src="{$DIR_IMG}scrollmenu.js"></script>
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>
<script type="text/javascript" src="{$DIR_IMG}searchlist.js"></script>
<script type="text/javascript" src="{$DIR_IMG}jquery.tools.min.js"></script>
<script language="JavaScript" type="text/javascript">
<!--
function resettarget()
{
    var eles =  document.getElementsByTagName('input');
    for (var i = 0; i < eles.length; i++) {
        if (eles[i].name == 'mail_serial[]') {
            eles[i].checked = false;
            check(eles[i]);
        }
    }
    CookieWrite('{$MAILTARGET_NAME}', '', 1);
}

function reverse()
{
    var eles =  document.getElementsByTagName('input');
    for (var i = 0; i < eles.length; i++) {
        if (eles[i].name == 'mail_serial[]') {
            eles[i].checked = !(eles[i].checked);
            check(eles[i]);
        }
    }

    return false;
}
function check(obj)
{
    var cookie = CookieRead('{$MAILTARGET_NAME}').split('@');
    var cookie_val = '';
    for (var i=0;i<cookie.length;i++) {
        if(cookie[i] ==obj.value)
            continue;

        if(cookie[i])
            cookie_val += cookie[i]+'@';
    }
    var tr = $(obj).closest("tr");
    if (obj.checked) {
        cookie_val += obj.value+'@';
        var color = $(tr).css("background-color");
        $(tr).attr("default_bg", color);
        $(tr).css("background-color", "#FFC");
    } else {
        var color = $(tr).attr("default_bg");
        $(tr).css("background-color", color);
    }

    CookieWrite('{$MAILTARGET_NAME}', cookie_val, 1);
}

var coockie_cache = '';
function CookieRead(kword)
{
    if(coockie_cache)

        return coockie_cache;
    kword = kword + "=";
    kdata = "";
    scookie = document.cookie + ";";
    start = scookie.indexOf(kword);
    if (start != -1) {
        end = scookie.indexOf(";", start);
        kdata = unescape(scookie.substring(start + kword.length, end));
    }

    return kdata;
}

function CookieWrite(kword, kdata, kday)
{
    if (!navigator.cookieEnabled) {
        alert("クッキーへの書き込みができません");

        return;
    }
    sday = new Date();
    sday.setTime(sday.getTime() + (kday * 1000 * 60 * 60 * 24));
    s2day = sday.toGMTString();
    document.cookie = kword + "=" + escape(kdata) + ";expires=" + s2day;
    coockie_cache = kdata;
}
function disp()
{
    document.getElementById('cond').style.display='block';
    document.getElementById('condbutton').style.display='none';

    return false;
}

function disp_()
{
    document.getElementById('condbutton').style.display='inline';
    document.getElementById('cond').style.display='none';

    return false;
}
function addForm(formname)
{
    document.getElementById('td_'+formname).appendChild(document.createElement('br'));
    input = document.createElement('input');
    input.name = formname+'[]';
    input.style.width='230px';
    document.getElementById('td_'+formname).appendChild(input);

}
var addDivCount = {$ThisCond->divPulldownMaxNum};
function addDivForm()
{
    addDivCount++;
    div = document.createElement('div');
    div.innerHTML = document.getElementById('div_org').innerHTML;
    div.innerHTML = div.innerHTML.split('%s').join(addDivCount);
    document.getElementById('td_div').appendChild(div);
}
$(function () {
    document.getElementById('on_mdisplay').style.display = 'none';
    document.getElementById('off_display').style.display = 'none';
    document.getElementById('search_table').style.display = 'none';
    document.getElementById('search_main').style.display = '';
    //$('textarea.resizable:not(.resize)').TextAreaResizer();

    $("table.cont :checkbox").each(function () {
        var chk = this;
        var tr = $(this).closest("tr");
        var td = $(this).closest("td");

        $(chk).css("cursor", "pointer");
        $(td).css("cursor", "pointer");

        $(td).click(function (event) {
            if(event.target == chk)

                return;

            chk.checked = !chk.checked;
            check(chk);
        })

        if (this.checked) {
            $(tr).attr("default_bg", $(tr).css("background-color"));
            $(tr).css("background-color", "#FFC");

        }
    });

    $("input[rel]").overlay({
        mask: {
            color: '#fff',
            loadSpeed: 100,
            opacity: '0.3'
        }
    });
});
function button_block()
{
document.getElementById("search_table").style.display="";
document.getElementById('on_display').style.display = 'none';
document.getElementById('off_display').style.display = '';
}
function button_hidden()
{
document.getElementById("search_table").style.display="none";
document.getElementById('on_display').style.display = '';
document.getElementById('off_display').style.display = 'none';
}
function button_mblock()
{
document.getElementById("search_main").style.display="";
document.getElementById('on_mdisplay').style.display = 'none';
document.getElementById('off_mdisplay').style.display = '';
}
function button_mhidden()
{
document.getElementById("search_main").style.display="none";
document.getElementById('on_mdisplay').style.display = '';
document.getElementById('off_mdisplay').style.display = 'none';
}
//-->
</script>

HTML;
$body = str_replace('%%%%mail_cond_message%%%%',$message_cond,$body);
    print $objHtml->getMainHtml($body);
    exit;
}
class ThisSortTable extends SortTable
{
    /**
     * 条件とPOSTされた追加条件から結果を取得してセットする
     * @param array $cond 検索条件の配列
     * @param array $post 追加で利用する条件の配列
     */
    public function setResult ($cond=array(), $post=array())
    {
        $this->cond =& $cond;
        if(!$this->cond) $this->cond = array();
        $test_flag = ($this->adapter->makeCond($cond, 'test_flag'))? ' AND a.'.$this->adapter->makeCond($cond, 'test_flag') : '';
        unset($cond['test_flag']);
        $where = implode(' AND ', $this->makeCond($cond));
        $where = $where? ' WHERE '.$where: '';
        $limit = $test_flag.' AND '.getDivWhere('a');
        $this->count = $this->adapter->getCount($where, $limit);
        $limit .= $this->getOrder($post).' LIMIT '.$this->limit.$this->getOffset($post);
        $this->result = $this->adapter->getResult($where, $limit);

        $this->isSetResult = true;
    }
}

/*****************************************************************************************************/
class ThisSortView extends ResearchSortTableView
{
    public function __construct()
    {
        parent::__construct();
        $width['checkbox']='20';
        $width['button']='100';
        $width = array_merge($width,getColmunWidth('user_search'));
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
<input type="submit" name="op[search]" value="　　　検索　　　"class="white button">
__HTML__;
    }
    public function getBox($row, $hidden, $action)
    {
        $body = $this->getBody($row);
        $submit = $hidden . $this->getSubmitButton();
        $SID = SID;
        $action = PHP_SELF;
        $DIR_IMG = DIR_IMG;

        return<<<__HTML__
<div class="button-container"><div class="button-group">
    <form action="360_user_edit.php?{$SID}&mode=new" method="post"target="_blank"><input type="submit" class="button white" value="新規作成"></form>
    <form action="360_user_import.php?{$SID}"method="post"target="_blank"><input class="button white" value="インポート" type="submit"></form>
</div></div>

<form action="{$action}" method="post">
    <div style="width:1050px; border-bottom:1px solid #888; margin-bottom:10px;">
    検索条件
    <span id="on_mdisplay"><input src="{$DIR_IMG}display_on_off.gif" onmouseover="this.src='{$DIR_IMG}display_on_on.gif'" onmouseout="this.src='{$DIR_IMG}display_on_off.gif'" value="表示" onclick="button_mblock();return false;" type="image"></span>
    <span id="off_mdisplay"><input src="{$DIR_IMG}display_off_off.gif" onmouseover="this.src='{$DIR_IMG}display_off_on.gif'" onmouseout="this.src='{$DIR_IMG}display_off_off.gif'" value="非表示" onclick="button_mhidden();return false;" type="image"></span></div>

    <div style="" id="search_main">
    <table class="searchbox" width="1050">
    <tbody>
    {$body}
    </tbody>
    </table>
    </div>

    <div class="simple_overlay" id="overlay">
        <div class="simple_overlay_title">メール配信条件名を入力して下さい</div>
        <div class="simple_overlay_details">
            メール配信条件名:<input name="condname" value="">
        <input type="submit" name="condsave" value="保存"class="button white">
        <a class="close button white">キャンセル</a>
        </div>
    </div>

    <div class="button-container" style="float:left">
        <div class="button-group">
            {$submit}
        </div>
        <div class="button-group">
            <input name="csvdownload" value="結果をダウンロード" class="button white" type="submit">
            <span id="condbutton"><input type="button" rel="#overlay" onclick="return disp();"class="button white" value="メール配信条件として保存">%%%%mail_cond_message%%%%</span>
        </div>
    </div>

</form>

<div class="button-container" style="float:left;margin-left:15px;"><div class="button-group">
<form action="enq_mailrsv.php?{$SID}" method="post" target="_blank" onSubmit="getCheckedIds(this, 'mail_serial', 'mailtarget');">
    <input type="button" onclick="resettarget();return false;"class="white button"style="width:200px" value="メール配信対象をリセットする">
    <input type="submit" value="メール配信予約を行なう" class="white button" id="mail_button">
    <input type="hidden" name="mailtarget" value="">
    <input type="hidden" name="mailcookie" value="1">
</form>
</div></div>
<div style="clear:both"></div>
__HTML__;
    }

    public function getBody($row)
    {

        $DIR_IMG = DIR_IMG;
        $divs = '';
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        for ($i=1;$i<=3;$i++) {
            $key ="div".$i;
            $div = array ('default' => '指定しない');
            if(is_array(getDivList($key)))
            foreach (getDivList($key) as $k => $v) {
                $div[$k] = $v;
            }
            if ($key == 'div1')
                $divs.= FForm :: select('div1[]', $div, "style='width:230px' id='id_div1_%s' onChange='reduce_options(\"id_div1_%s\", \"id_div2_%s\");reduce_options(\"id_div2_%s\", \"id_div3_%s\");'");
            if ($key == 'div2')
                $divs.= FForm :: select('div2[]', $div, "style='width:230px' id='id_div2_%s' onChange='reduce_options(\"id_div2_%s\", \"id_div3_%s\");'");
            if ($key == 'div3')
                $divs.= FForm :: select('div3[]', $div, "style='width:230px' id='id_div3_%s'");
        }
        $html=<<<HTML
<tr>
    <th class="tr1">　</td>
    <td class="tr2" colspan="3">{$row['　']}</td>
</tr>
<tr>
    <th class="tr1">{$label['sheet_type']}</td>
    <td class="tr2" colspan="3">{$row['シートタイプ']}</td>
</tr>
<tr>
    <th class="tr1">所属<br>[<a href="javascript:addDivForm()" style="color:#0066aa">条件を追加する</a>]</td>
    <td class="tr2" id="td_div" colspan="3">

<div id="div_org" style="display:none">{$divs}</div>
{$row['####div_name_1####']}

</td>
</tr>
<tr>
    <th class="tr1" style="width:130px">{$label['name']}</td>
    <td class="tr2" id="td_name">{$row['名前']}</td>
    <th class="tr1" style="width:130px">{$label['name_']}</td>
    <td class="tr2" id="td_name_">{$row['ローマ字']}</td>
</tr>
<tr>
    <th class="tr1">{$label['uid']}</td>
    <td class="tr2" id="td_uid">{$row['ユーザID']}</td>
    <th class="tr1">{$label['email']}</td>
    <td class="tr2" id="td_email">{$row['メールアドレス']}</td>
</tr>
</tbody>
</table>
</div>

<div style="width:1050px; border-bottom:1px solid #888; margin-bottom:10px;">
詳細検索<span id="on_display">
<input src="{$DIR_IMG}display_on_off.gif" onmouseover="this.src='{$DIR_IMG}display_on_on.gif'" onmouseout="this.src='{$DIR_IMG}display_on_off.gif'" value="表示" onclick="button_block();return false;" type="image"></span>
<span id="off_display"><input src="{$DIR_IMG}display_off_off.gif" onmouseover="this.src='{$DIR_IMG}display_off_on.gif'" onmouseout="this.src='{$DIR_IMG}display_off_off.gif'" value="非表示" onclick="button_hidden();return false;" type="image"></span></div>
<div style="display:none;" id="search_table">
<table class="searchbox" width="1050">
<tbody>
<tr>
    <th class="tr1" style="width:130px">{$label['class']}</td>
    <td class="tr2" id="td_memo">{$row['役職']}</td>
    <th class="tr1" style="width:130px">{$label['memo']}</td>
    <td class="tr2" id="td_memo">{$row['メモ']}</td>
</tr>
<tr>
    <th class="tr1">{$label['lang_flag']}</td>
    <td class="tr2" colspan="3">{$row['多言語対応']}</td>
</tr>
<tr>
    <th class="tr1">{$label['lang_type']}</td>
    <td class="tr2" colspan="3">{$row['言語タイプ']}</td>
</tr>
<tr>
    <th class="tr1">テストユーザー</td>
    <td class="tr2" colspan="3">{$row['テストユーザー']}</td>
</tr>

<tr>
    <th class="tr1">表示数</td>
    <td class="tr2" colspan="3">{$row['表示数']}</td>
</tr>
HTML;

    return $html;
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
    <td class="tr2">{\$row['{$key}']}</td>
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
    public function getResult($where,$limit='')
    {
        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $SQLS = array();
        $SQLS[] = "a.serial_no in(SELECT serial_no FROM {$T_USER_MST} table1 {$where})";
        $SQL = implode(' or ',$SQLS);
        $SQL = <<<SQL
SELECT * FROM usr a WHERE {$SQL} {$limit}
SQL;
        $this->SQL = $SQL;

        return FDB::getAssoc($SQL);
    }

    public function getCount($where, $limit='')
    {
        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $SQLS = array();
        $SQLS[] = "a.serial_no in(SELECT serial_no FROM {$T_USER_MST} table1 {$where})";
        $SQL = implode(' or ',$SQLS);
        $SQL = <<<SQL
SELECT count(*) as count FROM usr a WHERE {$SQL} {$limit}
SQL;
        $count = FDB::getAssoc($SQL);

        return $count[0]['count'];
    }
    public function getCsvFileName()
    {
        return date('Ymd').'ユーザマスタ'.DATA_FILE_EXTENTION;
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
                return $data[$key];
        }
    }
    public function getCsvColumns()
    {
        $array = $this->getColumns();
        unset($array["checkbox"]);
        unset($array["button"]);

        return $array;
    }
    public function getColumns()
    {
        $array = array();
        $array["checkbox"] ="<a href='#' onclick='reverse();return false;'>反転</a>";
        $array["button"] ="　";
        $array = array_merge($array,getColmunLabel('user_search'));
        $array = limitColumn($array);
        // TODO ソートをインポートに合わせる修正は、項目設定自体にソートを持たせる対応とした方が良い。
        $sort = array(
            'checkbox',
            'button',
            'uid',
            'mflag',
            'sheet_type',
            'name',
            'name_',
            'div1',
            'div2',
            'div3',
            'email',
            'class',
            'lang_flag',
            'lang_type',
            'memo',
            'ext1',
            'ext2',
            'ext3',
            'ext4',
            'ext5',
            'ext6',
            'ext7',
            'ext8',
            'ext9',
            'ext10',
            'test_flag',
            'send_mail_flag',
        );
        $return = array();
        foreach ($sort as $column) {
            if (is_good($array[$column])) {
                $return[$column] = $array[$column];
            }
        }

        return $return;
    }

    public function getNoSortColumns()
    {
        return array (
            "checkbox",
            "button",
            "pw"
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];
        switch ($key) {
            case "div2":
            case "div3":
            case "div1" :
                $array = array();
                if(is_array($values['div1']))
                foreach ($values['div1'] as $k=>$v) {
                    $array2=array();
                    if ($values['div1'][$k] != "default")
                        $array2[] = "table1.div1 = " . FDB :: escape($values['div1'][$k]);
                    if ($values['div2'][$k] != "default")
                        $array2[] = "table1.div2 = " . FDB :: escape($values['div2'][$k]);
                    if ($values['div3'][$k] != "default")
                        $array2[] = "table1.div3 = " . FDB :: escape($values['div3'][$k]);

                    if($array2)
                        $array[] = '('.implode(' and ',$array2).')';
                }
                if($array)

                    return '('.implode(' or ',$array).')';
                return null;




            case "mflag" :
                switch ($value) {
                    case 'all';

                        return null;
                    case '1' :
                        return "table1.mflag = 1";
                    case '0' :
                        return "table1.mflag = 0";
                }
            case "lang_flag" :
                switch ($value) {
                    case 'all';

                        return null;
                    case '1' :
                        return "table1.lang_flag = 1";
                    case '0' :
                        return "table1.lang_flag = 0";
                }
            case "sheet_type" :
                switch ($value) {
                    case 'all';

                        return null;
                    default :
                        return "table1.{$key} = " . FDB :: escape($value);
                }
            case "lang_type" :
                switch ($value) {
                    case 'all';

                        return null;
                    default :
                        return "table1.{$key} = " . FDB :: escape($value);
                }
            case "name":
            case "name_":
            case "email":
            case "uid":
            case "memo":
            case "class":
                foreach (explode("\n",$value) as $v) {
                    $v = trim($v);
                    if ($v !== null && $v !== '') {
                        $array[] = 'table1.'.$key . " like " . FDB :: escape('%' . $v . '%');
                    }
                }
                if ($array) {
                    return '('.implode(' or ',$array).')';
                }

                break;
            case "test_flag":
                if($value == 1)

                    return null;
                if($value == 2)

                    return "test_flag = 1";

                return "test_flag != 1";
            default :
                $array = array();
                if(is_array($value))
                foreach ($value as $v) {
                    if ($v !== null && $v !== '') {
                        $array[] = 'table1.'.$key . " like " . FDB :: escape('%' . $v . '%');
                    }
                }
                if ($array) {
                    return '('.implode(' or ',$array).')';
                }
                break;
        }

        return null;
    }
    public function getDefaultCond()
    {

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
            case 'checkbox':
                return getHtmlMailCheckBox($data['serial_no']);

            case 'button':
                if (hasAuthUserEdit()) {
                    return getHtmlUserEditButton($data['serial_no'],$data['uid']);
                }
            default :
                return get360Value($data,$key);
        }
    }
}

class ThisCond extends CondTableAdapter
{
    public $divPulldownMaxNum = 0;
    public function getColumns()
    {
        return array (
            "mflag" => "　",
            "sheet_type" => "シートタイプ",
            "div1" => "####div_name_1####",
            "div2" => "####div_name_2####",
            "div3" => "####div_name_3####",
            "name" => "名前",
            "name_" => "ローマ字",
            "uid" => "ユーザID",
            "email" => "メールアドレス",
            "class"=>"役職",
            "memo" => "メモ",
            "lang_flag"=>"多言語対応",
            "lang_type"=>"言語タイプ",
            "searchtype"=>"検索タイプ",
            "test_flag" => "テストユーザー"
        );
    }
    public function getColumnForms($def, $key)
    {
        global $_360_sheet_type;
        switch ($key) {
            case "div2" :
            case "div3" :
                return '';
            case "div1" :
                $divs = '';
                $this->divPulldownMaxNum = 1;
                if(is_array($def['div1']))
                foreach ($def['div1'] as $dk => $v) {
                    if($v === 'default')
                        continue;
                    $divs.='<div>';
                    for ($i=1;$i<=3;$i++) {
                        $key ="div".$i;
                        $div = array ('default' => '指定しない');
                        foreach (getDivList($key) as $k => $v) {
                            $div[$k] = $v;
                        }

                        if ($key == 'div1')
                            $divs.= FForm :: replaceSelected(FForm :: select('div1[]', $div, "style='width:230px' onChange='reduce_options(\"id_div1_{$dk}\", \"id_div2_{$dk}\");reduce_options(\"id_div2_{$dk}\", \"id_div3_{$dk}\");' id='id_div1_{$dk}'"), $def['div1'][$dk]);
                        if ($key == 'div2')
                            $divs.= FForm :: replaceSelected(FForm :: select('div2[]', $div, "style='width:230px' onChange='reduce_options(\"id_div2_{$dk}\", \"id_div3_{$dk}\");' id=\"id_div2_{$dk}\""), $def['div2'][$dk]);
                        if ($key == 'div3')
                            $divs.= FForm :: replaceSelected(FForm :: select('div3[]', $div, "style='width:230px' id='id_div3_{$dk}'"), $def['div3'][$dk]);
                        $array[] = $dk;
                    }
                    $this->divPulldownMaxNum = $dk;
                    $divs.=<<<HTML
</div>
HTML;
                }

                if ($array) {
                    $divs.=<<<HTML
<script>
function other_onload__()
{
HTML;
                    foreach ($array as $dk) {
                        $divs.=<<<HTML
reduce_options('id_div1_{$dk}', 'id_div2_{$dk}');
reduce_options('id_div2_{$dk}', 'id_div3_{$dk}');
HTML;
                    }
                    $divs.=<<<HTML
}
</script>
HTML;
                }





                if (!$divs) {
                    $divs.='<div>';
                    for ($i=1;$i<=3;$i++) {
                        $key ="div".$i;
                        $div = array ('default' => '指定しない');
                        if(is_array(getDivList($key)))
                        foreach (getDivList($key) as $k => $v) {
                            $div[$k] = $v;
                        }
                        if ($key == 'div1')
                            $divs.= FForm :: replaceSelected(FForm :: select('div1[]', $div, "style='width:230px' id='id_div1_1' onChange='reduce_options(\"id_div1_1\", \"id_div2_1\");reduce_options(\"id_div2_1\", \"id_div3_1\");'"), $def['div1']);
                        if ($key == 'div2')
                            $divs.= FForm :: replaceSelected(FForm :: select('div2[]', $div, "style='width:230px' id='id_div2_1' onChange='reduce_options(\"id_div2_1\", \"id_div3_1\");'"), $def['div2']);
                        if ($key == 'div3')
                            $divs.= FForm :: replaceSelected(FForm :: select('div3[]', $div, "style='width:230px' id='id_div3_1' "), $def['div3']);

                    }
                    $divs.='</div>';
                }

                return $divs;
            case "mflag" :
                if ($def[$key] === null)
                    $def[$key] = 'all';
                $radiolist = implode('', FForm :: radiolist('mflag', array (
                    'all' => '全て',
                    '1' => '対象者',
                    '0' => '非対象者'
                )));

                return FForm :: replaceChecked($radiolist, $def[$key]);

            case "lang_flag" :
                if ($def[$key] === null)
                    $def[$key] = 'all';
                $radiolist = implode('', FForm :: radiolist('lang_flag', array (
                    'all' => '全て',
                    '1' => 'あり',
                    '0' => 'なし'
                )));

                return FForm :: replaceChecked($radiolist, $def[$key]);
            case "sheet_type" :

                $tmp = array ();
                $tmp['all'] = "指定しない";
                foreach ($_360_sheet_type as $k => $v)
                    $tmp[$k] = $v;

                return FForm :: replaceSelected(FForm :: select($key, $tmp), $def[$key]) . " (対象者のみに限定されます)";
            case "lang_type" :

                $tmp = array ();
                $tmp['all'] = "指定しない";
                foreach ($GLOBALS['_360_language'] as $k => $v)
                    $tmp[$k] = $v;

                return FForm :: replaceSelected(FForm :: select($key, $tmp), $def[$key]) ;

            case "name":
            case "name_":
            case "email":
            case "memo":
            case "class":
            case "uid":
                return FForm :: textarea($key, $def[$key], 'style="width:230px"');
                break;
            case "test_flag":
                $array = array("含まない", "含む", "テストユーザーのみ");

                return implode("", FForm::replaceArrayChecked(FForm::radiolist($key, $array), (is_good($def[$key]))? $def[$key]:0));
            default :



            $forms = array();
            if(is_array($def[$key]))
            foreach ($def[$key] as $d) {
                if ($d !== null && $d !== '') {
                    $forms[] = FForm :: text($key.'[]', $d, '', 'style="width:230px"');
                }
            }
            if ($forms) {
                $forms = implode('<br>',	$forms);
            } else {
                $forms = FForm :: text($key.'[]', '', '', 'style="width:230px"');
            }

            return $forms;
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


function getHtmlUserEditButton($serial_no,$uid)
{
    $hash = getHash360($serial_no);
    $SID =getSID();
    $html=<<<HTML
<form action="360_user_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="edit">
<input type="submit" value="編集"class="button white">
</form>
HTML;
    if(limitAction('user_delete'))
        $html.=<<<HTML
<form action="%%%%PHP_SELF%%%%" method="post" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="delete">
<input type="submit" value="削除"class="button white" onclick="return myconfirm('ID:{$uid} のユーザを削除しようとしています。<br>このユーザが行なった回答・紐付け / このユーザに対する回答・紐付けも含めて削除します。<br>本当に削除してもよろしいですか？')"class="imgbutton35">
</form>
HTML;
    return $html;

}




function getHtmlMailCheckBox($serial_no)
{
    $MAILTARGET_NAME = MAILTARGET_NAME;
    if (in_array($serial_no,$GLOBALS[$MAILTARGET_NAME])) {
        $checked = ' checked';
    } else {
        $checked = '';
    }

    return<<<HTML
<input class="mail_serial" type="checkbox" name="mail_serial[]" value="{$serial_no}" onclick='check(this)'{$checked}>
HTML;
}

/****************************************************************************************************/
main();
