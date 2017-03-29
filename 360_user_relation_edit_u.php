<?php

/**
 * PGNAME:ユーザ回答者関連付け検索
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '');
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
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . '360_Smarty.php');
encodeWebAll();
session_start();
checkAuthUsr360();
/****************************************************************************************************/

define('PAGE_TITLE', '####respondent_edit####');
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
//$GLOBALS['NOT_DISP_DIV'] = " and div1 not in ('&%&%&%&%')";
/****************************************************************************************************/
function main()
{
    global $return_url;
    $SID = getSID();
    $serial_no = $_REQUEST['target_serial_no'];
    $hash = getHash360($serial_no);
    $return_url = "360_user_relation_view_u.php?{$SID}&serial_no={$serial_no}&hash={$hash}";
    if ($hash != $_REQUEST['hash']) {
        print "invalid hash!";
        exit;
    }
    $target_user = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($serial_no) . ' and ' . getDivWhere());
    if (!$target_user) {
        print "error ユーザが見つかりません";
        exit;
    }
    define('TARGET_USR_UID', $target_user['uid']);

    if(!is_numeric($_REQUEST['sort']))
        $_REQUEST['sort'] = '4';
    if (!$_REQUEST['div1']) {
        $_REQUEST['div1'] = $target_user['div1'];
        $GLOBALS['flag'] = true;
        $_REQUEST['search'] = '1';
    }
    $_SESSION['__FILE__']['post'] = $_REQUEST;

    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);

    $sv = new ThisSortView(800);
    $sv->RDTable = new ThisRDTable();
    $sv->setColStyle('button', 'style="text-align:center; white-space: nowrap"');
    $s = & new ThisSortTable(new SortAdapter(), $sv, true);
    $sl = & new SearchList($c, $s);
    $body = $sl->show(array (
        'op' => 'search',
        'div1' => $target_user['div1'],
        //'div1' => 'default',
        'div2' => 'default',
        'div3' => 'default',
        'name' => '',
        'name_'=>'',
        'email' => '',
        'user_type' => '',
        'get_limit' => '50',
        'sort' => '4'
    ));

    $body = str_replace('%%%%PHP_SELF%%%%', $s->getLink() . '&sort=' . (int) $_REQUEST['sort'] . '&desc=' . (int) $_REQUEST['desc']. '&offset=' . (int) $_REQUEST['offset'], $body);
    $body = str_replace('"' . getPHP_SELF() . '?', '"' . getPHP_SELF() . "?target_serial_no={$serial_no}&hash={$hash}&", $body);
    $body = str_replace('"' . getPHP_SELF() . '"', '"' . getPHP_SELF() . "?target_serial_no={$serial_no}&hash={$hash}&" . '"', $body);
    $objHtml = & new UserHtml("ユーザ一覧");
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $ajaxcscript = get_relation_ajax($serial_no,$hash);

    $smarty = new MreSmarty();
    $smarty->assign('return_url', $return_url);
    $smarty->assign('body', $ajaxcscript. $body. $getHtmlReduceSelect);
    $smarty->display('360_relation_edit.tpl');
    exit;
}
/****************************************************************************************************/

class ThisSortTable extends SortTable
{
    /**
     * 現在の検索条件のリンクを取得する
     * @return string リンク文字列
     */
    public function getLink()
    {
        $self = getPHP_SELF();
        $cond = array('op=search');

        $addValue = $this->adapter->setHiddenValue($this->cond);
        foreach ($addValue as $k => $v) {
            $cond[] = $this->getLinkParam($k, $v);
        }
        if ($this->useSession) {
            $cond[] = $this->getSID();
        }
        $cond = implode('&', $cond);
        if ($cond) {
            $cond = '?'.$cond;
        }

        return $self.$cond;
    }
}
class ThisRDTable extends RDTable
{
    /**
     * @return string テーブルの囲み枠部分
     */
    public function getTBody($body, $width=450)
    {
        return <<<__HTML__
<table class="cont2">
    {$body}
</table>
__HTML__;
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

        $counter = replaceMessage('####paging####');
        $counter = str_replace('XXX',$sortTable->count,$counter);
        $counter = str_replace('YYY',$offset,$counter);
        $counter = str_replace('ZZZ',$max,$counter);
        $table = ThisRDTable :: getTBody($body, $this->tableWidth);

        return<<<__HTML__

<div class="user_relation_info">####user_relation_info####</div>

<div class="page">
{$counter}　
{$prv}｜{$navi}｜{$next}
</div>
</form>
{$table}
<div class="page">
{$counter}　
{$prv}｜{$navi}｜{$next}
</div>
__HTML__;
    }
}
class ThisCondTableView extends CondTableView
{
        function getSubmitButton()
    {
        global $return_url;

        return <<<__HTML__

<div style="margin-top:20px;width:320px;text-align:center;padding:10px;background-color:#ffcccc">
<button onclick="location.href='{$return_url}';return false;">####enq_button_return####</button>
</div>

__HTML__;
    }
    public function getBox($row, $hidden, $action)
    {
        global $_360_user_type;

        $body = $this->getBody($row);

        $submit = $this->getSubmitButton();

        $TARGET_USR_UID = TARGET_USR_UID;
        $T_USER_RELATION = T_USER_RELATION;

        foreach (FDB :: getAssoc("select count(*) as count,user_type from {$T_USER_RELATION} where uid_a = '{$TARGET_USR_UID}' group by user_type;") as $tmp) {
            $c[$tmp['user_type']] = $tmp['count'];
        }

        foreach ($_360_user_type as $k => $v) {
            if (!$k || $k > INPUTER_COUNT)
                continue;
            $c[$k] = $c[$k] ? $c[$k] : 0;
            $count .= $v . " : " . $c[$k] . "####person####　";
        }

        return<<<__HTML__

<style>
.searchbox{
    border-collapse:collapse;

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
<!--
<div style="border:solid 1px black;padding:5px;margin:5px;width:300px;background-color:#ffffcc">
####preset_number####　　　{$count}
</div>
//-->
<form action="{$action}" method="post">
<table><tr><td valign="top" align="center">
    <table class="searchbox">
    <tr><td class="tr1">####div_name_1####</td><td>{$row['####div_name_1####']}</td></tr>
    <tr><td class="tr1">####div_name_2####</td><td>{$row['####div_name_2####']}</td></tr>
    <tr><td class="tr1">####div_name_3####</td><td>{$row['####div_name_3####']}</td></tr>
    <tr><td class="tr1">####mypage_fb_name####</td><td>{$row['名前']}</td></tr>
    <tr><td class="tr1">####mypage_name_####</td><td>{$row['ローマ字']}</td></tr>
    </table>
{$hidden}
    <input type="submit" name="search" value="####mypage_fb_submit####">
</td>
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
        global $GDF,$target_user;

        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $TARGET_USR_UID = TARGET_USR_UID;
        $sql =<<<SQL
select * from {$T_USER_MST} a left join {$T_USER_RELATION} b on b.user_type <= {$GDF->get('INPUTER_COUNT')} and a.uid = b.uid_b and b.uid_a = '{$TARGET_USR_UID}' {$where};
SQL;

        return FDB :: getAssoc($sql);
    }

    public function getSecondOrder()
    {
        return 'uid';
    }

    public function getCount($where)
    {
        global $GDF,$target_user;

        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $TARGET_USR_UID = TARGET_USR_UID;
        $sql =<<<SQL
select count(*) as count from {$T_USER_MST} a left join {$T_USER_RELATION} b on b.user_type <= {$GDF->get('INPUTER_COUNT')} and a.uid = b.uid_b and b.uid_a = '{$TARGET_USR_UID}' {$where};
SQL;
        $count = FDB :: getAssoc($sql);

        return $count[0]['count'];

    }

    public function getColumns()
    {
        global $_360_user_type;
        $array = array (
            "name" => "####mypage_fb_name####",
            "name_" => "####mypage_name_####",
            "class" => "####Post####",//<!-- 役職の表示 -->
            "div1" => "####div_name_1####",
            "div2" => "####div_name_2####",
            "div3" => "####div_name_3####",
            "user_type" => "####Respondent_status####",
            "button" => "####diagnosiser_select####"
        );

        return $array;

    }

    public function getNoSortColumns()
    {
        return array (
            "button"
        );
    }

    public function makeCond($values, $key)
    {
        global $_360_user_type;
        $T_USER_RELATION = T_USER_RELATION;
        $value = $values[$key];
        switch ($key) {
            case "div1" :
            case "div2" :
            case "div3" :
//				if($GLOBALS['flag'] && $key=='div1')
//				{
//					return "({$key} = " . FDB :: escape($value).' or user_type < 4 )';
//				}
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
            case "name" :
                if ($value !== null && $value !== '') {
                    return $key . " like " . FDB :: escape('%' . $value . '%');
                }

            case "name_" :
                if ($value !== null && $value !== '') {
                    $addSql = array();
                    $zenkaku = mb_convert_kana($value, "KV", INTERNAL_ENCODE);
                    $hankaku = mb_convert_kana($value, "k", INTERNAL_ENCODE);
                    if($zenkaku != $value)
                        $addSql[] = " REPLACE(REPLACE(lower(".$key."),' ',''),'　','') like " . FDB :: escape('%' . str_replace(' ','',str_replace('　','',strtolower($zenkaku))) . '%');
                    if($hankaku != $value)
                        $addSql[] = " REPLACE(REPLACE(lower(".$key."),' ',''),'　','') like " . FDB :: escape('%' . str_replace(' ','',str_replace('　','',strtolower($hankaku))) . '%');
                    $addSql = (is_good($addSql)) ? " or ".implode(" or ", $addSql):"";

                    return "(REPLACE(REPLACE(lower(".$key."),' ',''),'　','') like " . FDB :: escape('%' . str_replace(' ','',str_replace('　','',strtolower($value))) . '%').$addSql.')';
//                    return "(REPLACE(REPLACE(lower(name),' ',''),'　','') like " . FDB :: escape('%' . str_replace(' ','',str_replace('　','',strtolower($value))) . '%')." or REPLACE(REPLACE(lower(".$key."),' ',''),'　','') like " . FDB :: escape('%' . str_replace(' ','',str_replace('　','',strtolower($value))) . '%').$addSql.')';
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
        if(TEST!="1")

            return getDivWhere() .' and test_flag = 0 and uid <> ' . FDB :: escape(TARGET_USR_UID);
        return getDivWhere() .' and uid <> ' . FDB :: escape(TARGET_USR_UID);
    }

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'mflag DESC';
    }

    public function getColumnValue($data, $key)
    {
        global $_360_sheet_type, $_360_user_type;
        $val = $data[$key];
        switch ($key) {
            case 'user_type' :
                if (!$val)
                    $val = "-";
                else
                    $val = getUserTypeNameById($val);

                return '<b class="user_type">' . $val . '</b>';

            case 'button' :
                $data['user_type'] = $data['user_type'] ? $data['user_type'] : 0;
                $disabled[$data['user_type']] = ' disabled';

                foreach ($_360_user_type as $k => $v) {
                    if (!$k || $k > INPUTER_COUNT)
                        continue;

                    $button .=<<<HTML
<input type="submit" name="edit:{$k}" relation="{$k}" class="relation_eb" onClick="change_relation(this)" value="{$v}" {$disabled[$k]}>

HTML;

                }

                $hash = getHash360($data['uid']);

                return<<<HTML
{$button}<input type="submit" name="edit:{0}" relation="0" class="relation_eb" onClick="change_relation(this)" value="####no_set####" {$disabled[0]}>
<input type="hidden" name="target_uid" value="{$data['uid']}">
<input type="hidden" name="target_uid_hash" value="{$hash}">
<input type="hidden" name="mode" value="edit_2">
HTML;

            case "div1" :
                return getDiv1NameById($val, getMyLanguage());
            case "div2" :
                return getDiv2NameById($val, getMyLanguage());
            case "div3" :
                return getDiv3NameById($val, getMyLanguage());

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
        $array = array (
            "div1" => "####div_name_1####",
            "div2" => "####div_name_2####",
            "div3" => "####div_name_3####",
            "name" => "名前",
            "name_" => "ローマ字",
            "uid" => "ユーザID",
            "email" => "メールアドレス",
            "user_type" => "設定状態"
        );

        return $array;
    }
    public function getColumnForms($def, $key)
    {
        global $_360_sheet_type, $_360_user_type;

        switch ($key) {

            case "user_type" :
                $_360_user_type_ = $_360_user_type;
                unset ($_360_user_type_[0]);
                unset ($_360_user_type_[ADMIT_USER_TYPE]);
                unset ($_360_user_type_[VIEWER_USER_TYPE]);
                $array = array (
                    '' => '####not_set####'
                );
                $array['all'] = implode('/', $_360_user_type_);
                foreach ($_360_user_type_ as $k => $v) {
                    $array[$k] = $v;
                }

                return FForm :: replaceSelected(FForm :: select($key, $array, "style='width:230px' id='id_div3'"), $def[$key]);

            case "div1" :
            case "div2" :
            case "div3" :
                //print $def[$key];

                $div = array (
                    'default' => '####not_set####'
                );
                foreach (getDivList($key, getMyLanguage()) as $k => $v) {
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

function get_relation_ajax($serial_no, $hash)
{
    $PHP_SELF = "360_user_relation_edit_u_ajax.php"."?".getSID();

    return <<<__JS__
<script>
<!--

function change_relation(clicked)
{
    $(now_event).removeData();
    var now_event = clicked;
    var now_select = $(now_event).parent().find(".relation_eb:disabled").attr("relation");
    var now_select_txt = $(now_event).parent().parent().find(".user_type").html();
    $(now_event).parent().parent().find(".user_type").html("");
    var loading = setInterval(function () {
        if($(now_event).parent().parent().find(".user_type").text().length > 14)
            $(now_event).parent().parent().find(".user_type").html("");
        $(now_event).parent().parent().find(".user_type").append("<font color='#02a7f7'>|</font>");
    },200);
    $(now_event).parent().find(".relation_eb").attr("disabled","disabled");
    var uid = $(now_event).parent().find("input[name='target_uid']").val();
    var hash = $(now_event).parent().find("input[name='target_uid_hash']").val();
    $.post("{$PHP_SELF}", { target_uid: uid, target_uid_hash: hash, relation: $(now_event).attr("relation"), mode: "edit_2", serial_no: "{$serial_no}", hash: "{$hash}" },
    function (data,result) {
        if (isNaN(data)) {
            $(now_event).parent().find(".relation_eb[relation!='"+now_select+"']").removeAttr("disabled");
            $(now_event).parent().parent().find(".user_type").html(now_select_txt);
            if (typeof data == 'string' && data.length > 1) {
                clearInterval(loading);
                alert(data);

                return;
            }

            return;
        }
        $(now_event).parent().find(".relation_eb[relation!='"+data+"']").removeAttr("disabled");
        clearInterval(loading);
        var select = (data==0)? "-":$(now_event).parent().find(".relation_eb[relation='"+data+"']").val();
        $(now_event).parent().parent().find(".user_type").html(select);
    });
};

//-->
</script>
__JS__;
}
/****************************************************************************************************/
main();
