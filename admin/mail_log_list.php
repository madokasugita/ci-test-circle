<?php
//define('DEBUG', 1);
define('DIR_ROOT', "../");
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'CbaseFDB.php');
require_once(DIR_LIB.'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'CbaseFCondition.php');
require_once (DIR_LIB . 'ResearchSortListView.php');
require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
if(!$_POST["csvdownload"])
    encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

//
class MailTargetCondTableAdapter extends CondTableAdapter
{
    public $id;
    public function MailTargetCondTableAdapter($id)
    {
        $this->id = $id;
    }

    public function setHiddenValue($array)
    {
        $array['id'] = html_escape($this->id);

        return $array;
    }
}

class SortAdapter  extends SortTableAdapter
{

    public $dao;
    public $id;
    public function SortAdapter($id)
    {
        $this->id = $id;
        $this->dao = new MailCondition();

    }
    public $condData;
    public $where;
    public function getFormatCond()
    {
        $this->where = "WHERE a.mrid = ".FDB::escape($this->id);

        return $this->where;
    }

    public function getResult($where)
    {
        return FDB::select(T_MAIL_LOG." a LEFT JOIN ".T_USER_MST." b ON a.serial_no=b.serial_no", '*', $this->getFormatCond().$where);
    }

    public function getCount($where)
    {
        $r =  FDB::select(T_MAIL_LOG." a LEFT JOIN ".T_USER_MST." b ON a.serial_no=b.serial_no", 'count(*) as count', $this->getFormatCond());

        return $r[0]['count'];
    }

    public function getColumns()
    {
        //NO123 メール配信履歴追加 *2
        return limitColumn(array(
            "result" => "結果",
            "uid" => "ユーザID",
            "name" => "名前",
            "email" => "email",
            "div1" => "div1",
            "div2" => "div2",
            "div3" => "div3",
            "memo" => "メモ"
        ));
    }

    public function getNoSortColumns()
    {
        return array(
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
        return 'upload_id';
    }

    public function getColumnValue($data, $key)
    {
        $value = $data[$key];
        switch ($key) {
            case 'uid':
                if(!$value) $value = $data['syaincode'];

                return $value;
            case "div1" :
                return getDiv1NameById($value);
            case "div2" :
                return getDiv2NameById($value);
            case "div3" :
                return getDiv3NameById($value);
            case "result":
                return ($value==1)? "◯":"×";
            default:
                return $value;
        }
    }

    public function getCsvColumnValue($data, $key)
    {
        return $this->getColumnValue($data, $key);
    }

    public function isVisibleColumn($columnName)
    {
        return true;
    }

    public function setHiddenValue($array)
    {
        $array['id'] = html_escape($this->id);

        return $array;
    }
}

//--------------

$myId = $_GET['id']? $_GET['id']: $_POST['id'];

if (!$myId) {
    echo "IDを指定してください";
    exit;
}

$c =& new CondTable(new MailTargetCondTableAdapter($myId), new ResearchLimitCondTableView(), true);
//$c->visible = false;
$s =& new SortTable(new SortAdapter($myId), new ResearchSortTableView(), true);
$s->csvdownload_additional_params = array(
    'id' => html_escape($_REQUEST['id']),
);

$sl =& new SearchList($c, $s);
$topage = $_SESSION['enq_target_list']['recent_page'];
$body = $sl->show(array('op'=>'sort'));
$sid=getSID();
$DIR_IMG = DIR_IMG;
$backBar = RD::getBackBar($topage.'?op=back&'.$sid);
$body = <<<__HTML__
{$backBar}
<br>
<table width="430" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width="13" valign="middle" align="center"><img src="{$DIR_IMG}icon_inf.gif" width="13" height="13"></td>
        <td width="107" valign="middle"><font size="2">配信対象者一覧</font></td>
        <td width="287" valign="middle"><font color="#999999" size="2"><!-- コメント --></font></td>
    </tr>
    <tr valign="top">
        <td height="2" colspan="3">
        </td>
    </tr>
</table>
<br>
{$body}
__HTML__;

$objHtml =& new ResearchAdminHtml("配信対象者一覧");
echo $objHtml->getMainHtml($body);
exit;
