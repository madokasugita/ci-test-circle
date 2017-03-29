<?php

define('DIR_ROOT', "../");
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'CbaseFDB.php');
require_once(DIR_LIB.'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'ResearchSortListView.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
if(!$_POST["csvdownload"])
    encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

class SortAdapter extends SortTableAdapter
{
    public $max_other_user_type_id;
    public function getResult($where)
    {
        global $con;
        $con->options['result_buffering'] = true;
        $max = FDB::select1(T_USER_TYPE, 'MAX(user_type_id) as max', "WHERE utype = 1");
        $this->max_other_user_type_id = $max['max'];
        if ($_REQUEST['csvdownload']) {
            $con->options['result_buffering'] = false;
        }

        return FDB::select(T_USER_TYPE, '*', $where);
    }

    public function getCount($where)
    {
        $count =  FDB::select(T_USER_TYPE, 'count(user_type_id) as count', $where);

        return $count[0]['count'];
    }

    public function getColumns()
    {
        return array(
            "user_type_id" => "ID"
            ,"name" => "名称"
            ,"admin_name" => "管理画面名称"
            ,"utype" => "タイプ"
            ,"button" => ""
        );
    }

    public function getNoSortColumns()
    {
        return array(
            'button'
        );
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];
        switch ($key) {
            default:
                if ($value !== null && $value !== '') {
                    return $key." = ".FDB::escape($value);
                }
                break;
        }

        return null;
    }

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'user_type_id';
    }

    public function getColumnValue($data, $key)
    {
        switch ($key) {
            case 'button':
                $user_type_id = $data['user_type_id'];
                $hash = getHash360($user_type_id);
                $SID = getSID();
                $button = <<<__HTML__
<form action="360_user_type_edit.php?{$SID}" target="_blank" method="POST">
<input type="hidden" name="user_type_id" value="{$user_type_id}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="edit">
<button type="submit" class="white button">編集</button>
</form>
__HTML__;
                if($user_type_id == $this->max_other_user_type_id)
                $button .= <<<__HTML__
<form action="%%%%PHP_SELF%%%%" method="POST">
<input type="hidden" name="user_type_id" value="{$user_type_id}">
<input type="hidden" name="hash" value="{$hash}">
<input type="hidden" name="mode" value="delete">
<button type="submit" class="white button" onClick="return myconfirm('削除しようとしているユーザータイプに関する、<br/>「登録済みの紐付け」、「回答シート」、「回答データ」も同時に削除されます。<br/>実行しますか？');">削除</button>
</form>
__HTML__;

                return $button;
                break;
            case 'utype':
                return $GLOBALS['user_type_utype'][$data[$key]];

            default:
                return html_escape($data[$key]);
        }
    }
}


//--------------

if ($_POST['mode']=="delete" && getHash360($_POST['user_type_id']) == $_POST['hash']) {
    $result = array();
    $result[] = FDB::begin();
    $result[] = FDB::delete(T_USER_TYPE, "WHERE user_type_id = ".FDB::escape($_POST['user_type_id']));
    $result[] = FDB::delete(T_USER_RELATION, "WHERE user_type = ".FDB::escape($_POST['user_type_id']));
    $result[] = FDB::delete(T_EVENT, "WHERE (evid%100) = ".FDB::escape($_POST['user_type_id']));
    $result[] = FDB::delete(T_EVENT_SUB, "WHERE (evid%100) = ".FDB::escape($_POST['user_type_id']));
    $result[] = FDB::delete(T_EVENT_DATA, "WHERE (evid%100) = ".FDB::escape($_POST['user_type_id']));
    $result[] = FDB::delete(T_EVENT_SUB_DATA, "WHERE (evid%100) = ".FDB::escape($_POST['user_type_id']));
    if (!in_array(false, $result, true) && FDB::commit()) {
        clearUserTypeCache();
        $MESSAGE = "<script>$().toastmessage('showSuccessToast', 'ユーザータイプを削除しました');</script>";
    } else {
        FDB::rollback();
        $MESSAGE = "<script>$().toastmessage('showNoticeToast', 'ユーザータイプの削除に失敗しました');</script>";
    }
}

$c = new CondTable(new CondTableAdapter(), new ResearchLimitCondTableView(), true);
//$c->visible = false;
$s = new SortTable(new SortAdapter(), new ResearchSortTableView(), true);
// $s->view->colGroup = array(
// 	'name' => 'align="left"'
// );

$sl = new SearchList($c, $s);

$body = $sl->show(array('op'=>'sort'));
$body = str_replace('%%%%PHP_SELF%%%%', $s->getLink(), $body);

$SID = getSID();
$body = <<<__HTML__
<div style="text-align: left; width: 1050px;">
<div class="button-container"><div class="button-group">
    <form action="360_user_type_edit.php?{$SID}" method="post" target="_blank">
    <input type="hidden" name="mode" value="new">
    <input id="newdata"type="submit" value="新規作成" class="white button">
    </form>
</div></div>
</div>
{$body}
{$MESSAGE}
__HTML__;

$objHtml = new MreAdminHtml("ユーザータイプ管理");
echo $objHtml->getMainHtml($body);
exit;
