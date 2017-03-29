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
require_once (DIR_LIB . 'JSON.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . 'CbaseFCrypt.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
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
        if ($_POST['hash'] != getHash360($_POST['serial_no'])) {
            $p = new CbasePage();
            $p->addErrorMessage("削除に失敗しました。");
            $message =  $p->getErrorMessage();
        } else {
            $message = deleteUser($_POST['serial_no']);

        }
    }
    $c = & new CondTable(new ThisCond(), new ThisCondTableView(), true);
    $s = & new SortTable(new SortAdapter(), new ThisSortView(800), true);
    $sl = & new SearchList($c, $s);
    //141 検索ボタンを押す前は結果を表示しない
    $body = $sl->show(array());

    $body = str_replace('%%%%PHP_SELF%%%%',$s->getLink(),$body);

    $objHtml = new MreAdminHtml("ログイン管理");
    $objHtml->setExFix();
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式
    $SID = getSID();
    $DIR_IMG = DIR_IMG;
    $body =<<<HTML
<script type="text/javascript" src="{$DIR_IMG}clickcellsort.js"></script>

{$message}
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
    public function __construct()
    {
        parent::__construct();
        $width = array();
        $width = array_merge($width,getColmunWidth('user_pw_search'));
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
        $T_USER_MST = T_USER_MST;

        return FDB :: getAssoc("SELECT * FROM {$T_USER_MST} {$where}");
    }

    public function getCount($where)
    {
        $T_USER_MST = T_USER_MST;
        $count = FDB :: getAssoc("SELECT count(*) as count FROM {$T_USER_MST} {$where}");

        return $count[0]['count'];
    }
    public function getCsvFileName()
    {
        return date('Ymd').'ユーザパスワード'.DATA_FILE_EXTENTION;
    }
    public function getColumns()
    {
        $array = array (
            "button" => "　",
        );
        $array = array_merge($array,getColmunLabel('user_pw_search'));
        if (isReversiblePw()) {
            $array['pw'] = 'パスワード';
        }
        $array["url"]="認証省略<br/>URL";
        $array["pwmisscount"] = 'パスワード<br>間違い回数';

        if(!OPTION_LOGIN_FLAG)
            unset($array['login_flag']);

        return limitColumn($array,2);

    }
    public function getCsvColumns()
    {
        $a = $this->getColumns ();
        unset($a['button']);
        unset($a['url']);
        $a['url_'] = '認証省略URL';

        return $a;
    }
    public function getNoSortColumns()
    {
        return array (
            "button",
            //"pw",
            "url",
        );
    }

    public function makeCond($values, $key)
    {
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
            case "pwmisscount":
                if($value == -1)

                    return "pwmisscount < ".LIMIT_PW_MISS;
                elseif($GLOBALS['Setting']->limitPwEqual($value))
                    return "pwmisscount = ".LIMIT_PW_MISS;
                else
                    return null;
            case "login_flag":
                if(is_zero($value))

                    return "login_flag = 0";
                elseif($value == 1)
                    return "login_flag = 1";
                else
                    return null;
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
        return getDivWhere();
    }

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'pwmisscount DESC, '.$this->getSecondOrder();
    }

    public function getSecondOrder()
    {
        return 'uid DESC';
    }

    public function getColumnValue($data, $key)
    {
        $val = $data[$key];
        switch ($key) {
            case 'url':
                $id = $data['uid'];
                $pw = $data['pw'];
                $hash = substr(getHash360($id,$pw),0,4);
                $q = urlencode(encrypt("{$id}/{$pw}/{$hash}"));

                return '<a href="'.LOGIN_URL_S.'?q='.$q.'a'.'" target="_blank">URL</a>';
            case 'url_':
                return _360_getSloginURL($data);
            case 'button':
                if (hasAuthUserEdit()) {
                    return getHtmlUserEditButton($data['serial_no']);
                }

                return '';
            default :
                return get360Value($data,$key);
        }
    }

    public function getCsvColumnValue($data, $key)
    {
        if ($key=='div1')
            return getDiv1NameById($data[$key]);

        if ($key=='div2')
            return getDiv2NameById($data[$key]);

        if ($key=='div3')
            return getDiv3NameById($data[$key]);

        if ($key=='pw') {
            if (isReversiblePw())
                return getDisplayPw($data[$key]);

            return;
        }
        return $data[$key];
    }
}

class ThisCond extends CondTableAdapter
{
    public function getColumns()
    {
        $column_data = getColmunSetting();
        $label = $column_data['label'];
        $columns =  array (
            "uid" => $label['uid'],
            "name" => $label['name'],
            "div1" => $label['div1'],
            "div2" => $label['div2'],
            "div3" => $label['div3'],
            "email" => $label['email'],
            "pwmisscount"=>"ログイン制限",
            "login_flag"=>"ログイン有無",
            "memo" => $label['memo']
        );
        if(!OPTION_LOGIN_FLAG)
            unset($columns['login_flag']);

        return $columns;
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
            case "pwmisscount":

                $array=array("default"=>"指定しない","-1"=>"ログイン可能",LIMIT_PW_MISS=>"ログイン不可能");
                if(!$def[$key])
                    $def[$key] = "default";

                return FForm :: replaceChecked(implode('',FForm :: radioList($key, $array)), $def[$key]);
            case "login_flag":

                $array=array("default"=>"指定しない","0"=>"未ログイン","1"=>"ログイン済");
                if(is_void($def[$key]))
                    $def[$key] = "default";

                return FForm :: replaceChecked(implode('',FForm :: radioList($key, $array)), $def[$key]);
            case "mflag" :
                if ($def[$key] === null)
                    $def[$key] = 'all';
                $radiolist = implode('', FForm :: radiolist('mflag', array (
                    'all' => '全て',
                    '1' => '対象者',
                    '0' => '非対象者'
                )));

                return FForm :: replaceChecked($radiolist, $def[$key]);
            case "sheet_type" :

                $tmp = array ();
                $tmp['all'] = "指定しない";
                foreach ($_360_sheet_type as $k => $v)
                    $tmp[$k] = $v;

                return FForm :: replaceSelected(FForm :: select($key, $tmp), $def[$key]) . " (対象者のみに限定されます)";
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


function getHtmlUserEditButton($serial_no)
{
    $hash = getHash360($serial_no);
    $SID =getSID();

    return<<<HTML
<form action="360_user_pw_edit.php?{$SID}" method="post" target="_blank" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="edit">
<input type="submit" value="編集"class="imgbutton35">
</form>
HTML;

}

/****************************************************************************************************/
main();
