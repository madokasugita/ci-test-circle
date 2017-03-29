<?php

/**
 * PGNAME:ユーザ承認者関連付け検索
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
require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'JSON.php');
require_once (DIR_LIB . 'CbasePage.php');
if(!$_POST["csvdownload"])
    encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/

define('PHP_SELF', getPHP_SELF() . '?' . getSID());

/****************************************************************************************************/

function main()
{
    $serial_no = $_REQUEST['serial_no'];
    $hash = getHash360($serial_no);
    if ($hash!= $_REQUEST['hash']) {
        print "invali hash!";
        exit;
    }
    $target_user = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($serial_no) . ' and ' . getDivWhere());
    if (!$target_user) {
        print "error ユーザが見つかりません";
        exit;
    }
    define('TARGET_USR_UID',$target_user['uid']);
    if($_REQUEST['mode'] == 'edit_2')
        relationEdit($target_user);

    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new SortTable(new SortAdapter(), new ThisSortView(800), true);
    $s->csvdownload_additional_params = array(
        'hash'      => $_REQUEST['hash'],
        'serial_no' => $_REQUEST['serial_no'],
        'mode'      => $_REQUEST['mode'],
    );
    $sl = & new SearchList($c, $s);
    $body = $sl->show(array ('op' => 'search', 'div1' => 'default', 'div2' => 'default', 'div3' => 'default', 'name' => '', 'uid' => '', 'email' => '', 'user_type' => '', 'get_limit' => '50','sort' => 0));

    $body = str_replace('%%%%PHP_SELF%%%%', $s->getLink().'&sort='.(int) $_REQUEST['sort'].'&desc='.(int) $_REQUEST['desc'], $body);
    $body = str_replace('"'.getPHP_SELF().'?','"'.getPHP_SELF()."?serial_no={$serial_no}&hash={$hash}&",$body);
    $body = str_replace('"'.getPHP_SELF().'"','"'.getPHP_SELF()."?serial_no={$serial_no}&hash={$hash}&".'"',$body);
    $objHtml = & new ResearchAdminHtml("ユーザ一覧");
    $getHtmlReduceSelect = getHtmlReduceSelect(true); //select option 絞込み機能一式
    $SID = getSID();
    $body =<<<HTML


<div style="text-align:left;width:800px;margin-bottom:5px;border-top:dotted 1px #222222;border-bottom:dotted 1px #222222;margin-bottom:5px;margin-top:5px;border-bottom:dotted 1px #222222;padding:10px;">
<table>
<tr>
  <td >承認者設定検索/編集</td>
  <td valign="middle"></td>
</tr>
</table>
</div>
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
<div class="page">
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
        global $GDF, $_360_user_type;
        $body = $this->getBody($row);
        $submit = $hidden . $this->getSubmitButton();

        $TARGET_USR_UID = TARGET_USR_UID;
        $T_USER_RELATION = T_USER_RELATION;
        $T_USER_MST = T_USER_MST;

        $user = FDB::getAssoc("select b.* from {$T_USER_RELATION} a left join {$T_USER_MST} b on user_type = {$GDF->get('ADMIT_USER_TYPE')} and a.uid_b = b.uid where uid_a = '{$TARGET_USR_UID}' and b.uid is not null;") ;
        $user = escapeHtml($user[0]);

        if (isset($user['uid'])) {
            $admit = "<br><br><b>ID</b>:{$user['uid']}<br><b>名前</b>:{$user['name']}<br><b>所属</b>:".getDiv1NameById($user['div1']).' '.getDiv2NameById($user['div2']).' '.getDiv3NameById($user['div3']);
        } else {
            $admit = "設定なし";
        }

        return<<<__HTML__
<form action="{$action}" method="post">
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
<div style="border:solid 1px black;padding:5px;margin:5px;width:300px;background-color:#ffffcc">
承認者　{$admit}
<br>
<div align="center"><button onclick="javascript:window.close()">決定して一覧に戻る</button></div>
</div>
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
        global $GDF, $target_user;

        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $TARGET_USR_UID = TARGET_USR_UID;
        $sql =<<<SQL
select * from {$T_USER_MST} a left join {$T_USER_RELATION} b on b.user_type = {$GDF->get('ADMIT_USER_TYPE')} and a.uid = b.uid_b and b.uid_a = '{$TARGET_USR_UID}' {$where};
SQL;

        return FDB :: getAssoc($sql);
    }

    public function getCount($where)
    {
        global $GDF, $target_user;

        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $TARGET_USR_UID = TARGET_USR_UID;
        $sql =<<<SQL
select count(*) as count from {$T_USER_MST} a left join {$T_USER_RELATION} b on  b.user_type = {$GDF->get('ADMIT_USER_TYPE')} and a.uid = b.uid_b and b.uid_a = '{$TARGET_USR_UID}' {$where};
SQL;
        $count = FDB :: getAssoc($sql);

        return $count[0]['count'];

    }

    public function getColumns()
    {
        global $_360_user_type;
        $array = array (
            "uid" => "ユーザID",
            "name" => "名前",
            "div1" => "####div_name_1####",
            "div2" => "####div_name_2####",
            "div3" => "####div_name_3####",
            "user_type"=>"承認者<br>設定<br>状態",
            "button"=>"承認者設定"
        );

        return limitColumn($array);

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
        return 'uid <> '.FDB::escape(TARGET_USR_UID);//20090121 この画面では、管理者の権限による制限は行なわない
        //return getDivWhere().' and uid <> '.FDB::escape(TARGET_USR_UID);
    }

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'uid';
    }

    public function getColumnValue($data, $key)
    {
        global $_360_sheet_type,$_360_user_type;
        $val = $data[$key];
        switch ($key) {
            case 'user_type' :
                if($val != ADMIT_USER_TYPE)

                    return "-";
                return '<b>'.getUserTypeNameById($val).'</b>';

            case 'button':
                $data['user_type'] = ($data['user_type']==ADMIT_USER_TYPE) ? $data['user_type'] : 0;
                $disabled[$data['user_type']] = ' disabled';

                foreach ($_360_user_type as $k => $v) {
                    if($k!=ADMIT_USER_TYPE)
                        continue;

                    $button.=<<<HTML
<input type="submit" name="edit:{$k}" value="{$v}" {$disabled[$k]}>

HTML;

                }

                $hash = getHash360($data['uid']);

                return<<<HTML
<form action="%%%%PHP_SELF%%%%" method="post" style="display:inline">
{$button}<input type="submit" name="edit:{0}" value="未設定" {$disabled[0]}>
<input type="hidden" name="target_uid" value="{$data['uid']}">
<input type="hidden" name="target_uid_hash" value="{$hash}">
<input type="hidden" name="mode" value="edit_2">
</form>
HTML;

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
            "uid" => "ユーザID",
            "email" => "メールアドレス",
            "user_type" => "設定状態"
        );

        return $array;
    }
    public function getColumnForms($def, $key)
    {
        global $_360_sheet_type,$_360_user_type;

        switch ($key) {

            case "user_type":
                $array = array(''=>'指定しない');
                $array[ADMIT_USER_TYPE] = $_360_user_type[ADMIT_USER_TYPE];

                return FForm :: replaceSelected(FForm :: select($key, $array, "style='width:230px' id='id_div3'"), $def[$key]);

            case "div1" :
            case "div2" :
            case "div3" :
                $div = array (
                    'default' => '指定しない'
                );
                foreach (getDivListAll($key) as $k => $v) {
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

function relationEdit($user)
{
    if (getHash360($_POST['target_uid']) !=  $_POST['target_uid_hash']) {
        print "invalid hash";
        exit;

    }
    foreach ($_POST as $k => $v) {
        if(ereg('edit:([0-9]*)',$k,$match))
            $type = $match[1];
    }
    $where = 'where user_type = '.ADMIT_USER_TYPE.' and uid_a = '.FDB::escape($user['uid']);
    $relation = FDB::select1(T_USER_RELATION,'user_type',$where);
    if ($type) {
        $data = array();
        $data['user_type'] = (int) $type;
        $data['uid_a'] = FDB::escape($user['uid']);
        $data['uid_b'] =FDB::escape($_POST['target_uid']);
        if($relation)
            FDB::update(T_USER_RELATION,$data,$where);
        else
            FDB::insert(T_USER_RELATION,$data,$where);
    } else {
        FDB::delete(T_USER_RELATION,$where);
    }

}

/****************************************************************************************************/
main();
