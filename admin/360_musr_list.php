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

class SortAdapter  extends SortTableAdapter
{

    public function getResult($where)
    {
        return FDB::select(T_MUSR, '*', $where);
    }

    public function getCount($where)
    {
        $count =  FDB::select(T_MUSR, 'count(muid) as count', $where);

        return $count[0]['count'];
    }

    public function getColumns()
    {
        return array(
            "id" => "ID"
            ,"name" => "名前"
            ,"button" => "権限編集"
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
        return 'muid DESC';
    }

    public function getColumnValue($data, $key)
    {
        switch ($key) {
            case 'button':
                //muidは自動入力かつintなので安全と見なす
                $shMuid = $data['muid'];

                return '<a href="360_musr_authedit.php?id='.$shMuid.'&'.getSID().'">権限編集</a>';
                break;
            default:
                return html_escape($data[$key]);
        }
    }

}

//--------------

$c =& new CondTable(new CondTableAdapter(), new ResearchLimitCondTableView(), true);
//$c->visible = false;
$s =& new SortTable(new SortAdapter(), new ResearchSortTableView(), true);
$s->view->colGroup = array(
    'name' => 'align="left"'
);

$sl =& new SearchList($c, $s);

$body = $sl->show(array('op'=>'sort'));

$info = D360::getUpdateInfo();

$body = <<<__HTML__
{$body}
{$info}
__HTML__;

$objHtml =& new MreAdminHtml("所属別権限管理");
echo $objHtml->getMainHtml($body);
exit;
