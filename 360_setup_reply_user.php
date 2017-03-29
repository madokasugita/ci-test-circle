<?php
//define('DEBUG', 1);
define('DIR_ROOT', "");
require_once (DIR_ROOT . 'crm_define.php');

require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseEncoding.php');

encodeWebAll();
session_start();

define('PAGE_TITLE', MSG_MENU_TITLE);
/**************************************************************************************************************************/
function main()
{
    $self = getSelfData();
    $admitData = getAdmitData();
    $admit = $admitData['uid'];

    if (!$admit) {
        if (is_good($_POST['request']) && is_good($_POST['admit'])) {
            $admit = $_POST['admit'];
        }
    }
    if (is_good($admit)) { // admit選択済なら
        //!notEditReplyUser() &&
        if (is_good($_POST['sendmail'])) {
            $admitArray = sendRequestMail($admit, $self);
            if (!is_false($admitArray)) {
                setSelectStatus(1,$admit,$self);
                $name = getUserName($admitArray);
                $tmp=str_replace('NNN', $name, replaceMessage('####send_approval_request####'));
                $sysMsg =<<<__HTML__
<br><div style="color:#0000ff;">{$tmp}</div>

__HTML__;
            } else {	/* 承認依頼メール送信失敗 */
                $sysMsg =<<<__HTML__
<br><div style="color:#e9102d;">####send_mail_error####</div>

__HTML__;
            }
        } else {
            $admitname = getUserName($admitData);
            $action = getLinkPHP();
            $antiDoubleClick = JSCODE_ANTI_DOUBLE_CLICK;
            $userTable =<<<__HTML__
<form action="{$action}" method="post">
<input type="hidden" name="request" value="1">
<input type="hidden" name="sendmail" value="1">

####adminpage_user5####：{$admitname}####mypage_one####
<input type="submit" value="####setup_reply_user_1####" onClick="{$antiDoubleClick}">
</form>
__HTML__;
        }

    } else {
        $PHP_SELF = getPHP_SELF();

        if (is_good($_REQUEST['search'])) {

            $_SESSION[$PHP_SELF] = $_REQUEST;
            $userTable = getSearchUserTable($_SESSION[$PHP_SELF]);
        } else {
            $_SESSION[$PHP_SELF] = array ();
            unset ($_SESSION[$PHP_SELF]);
        }
        $userSearchBox = getUserSearchBox($_SESSION[$PHP_SELF], "####mypage_approval_serach_title####") . "<b style='color:red'>####setup_reply_user_message_1####</b><br><br>";
    }

    $action1 = is_good($admit) ? getLinkPHP() : getLinkPHP("setup_reply_user.php");
    $SID = getSID();

    $serial_no = html_escape($_SESSION['login']['serial_no']);
    $hash = getHash360($serial_no);
    $body =<<<__HTML__
<div align="right"><a href="360_user_relation_view_u.php?{$SID}&hash={$hash}&serial_no={$serial_no}">####enq_button_pb####</a></div>
<div align="left">■{$name} ####setup_reply_user_1####</div>
{$userSearchBox}
{$sysMsg}


{$userTable}
__HTML__;

    echo getMyPageHtml($body);
    exit;
}
/**************************************************************************************************************************/


class SearchUserAdapter extends SortTableAdapter
{
    public function getResult($where)
    {
        $userMst = T_USER_MST;
        $column = "div1,div2,div3,uid,name,class";
        $table = sprintf("(%s) {$userMst}", str_replace(";", "", FDB :: getSelectSql($userMst, $column, $where)));
        //		foreach(getAryReplyType() as $key => $reply)
        //		{
        //			$replyMst = $type."_user_".$reply;
        //			$cond = sprintf("%s.uid=%s and {$reply}=%s.uid", $replyMst, sql_escape($_SESSION['login'][$type]['self']['uid']), $userMst);
        //			$table = "{$table} left join {$replyMst} on {$cond}";
        //			$column .= ",{$reply}";
        //		}
        return FDB :: select($table, $column);
    }

    public function getCount($where)
    {
        $count = FDB :: select1(T_USER_MST, 'count(uid) as count', $where);

        return $count['count'];
    }

    public function getColumns()
    {
        return array (
            'div' => "####div####",
            'name' => "#####name####",
            'class' => "####Post####",//役職の表示
            'request' => "####request####"
        );
    }

    public function getNoSortColumns()
    {
        return array (
            'request'
        );
    }


    public function getDefaultCond()
    {
        if(TEST!="1")

            return getDivWhere() .' and test_flag = 0 and uid != ' . FDB :: escape($_SESSION['login']['uid']);
        return getDivWhere() .' and uid != ' . FDB :: escape($_SESSION['login']['uid']);
    }
    public function makeCond($values, $key)
    {
        $value = $values[$key];
        if ($value === '---')
            return null;
        if (is_void($value))
            return null;

        $aryWhere = array ();
        switch ($key) {

            case 'div1' :
                if (is_good($value))
                    $aryWhere[] = "div1=" . sql_escape($value);
                break;
            case 'div2' :
                if (is_good($value))
                    $aryWhere[] = "div2=" . sql_escape($value);
                break;

            case 'div3' :
                if (is_good($value))
                    $aryWhere[] = "div3=" . sql_escape($value);
                break;

            case 'div' :
                $value = explode(",", $value);
                foreach ($value as $_key => $_value) {
                    $_key++;
                    if (is_good($_value))
                        $aryWhere[] = "div{$_key}=" . sql_escape($_value);
                }
                break;
            case 'uid' :
            case 'name' :
                $value = explode(" ", mb_convert_kana($value, 's'));
                foreach ($value as $_value) {
                    if (is_good($_value))
                        $aryWhere[] = "{$key} like " . sql_escape("%{$_value}%");
                }
                break;
            default :
                break;
        }

        return (empty ($aryWhere)) ? null : implode(" and ", $aryWhere);
    }

    public function getDefaultOrder()
    {
        return "div1,div2,div3,uid";
    }

    public function formatOrder($key)
    {
        switch ($key) {
            case 'div' :
                return $this->getDefaultOrder();
            default :
                return $key;
        }
    }

    public function getColumnValue($data, $key)
    {
        switch ($key) {
            case 'div' :
                $data = html_escape(getUserDiv($data));
                break;
            case 'request' :
                $data = $this->getRequestForm($data);
                break;
            default :
                $data = html_escape($data[$key]);
                break;
        }

        return<<<__HTML__
<nobr>{$data}</nobr>
__HTML__;
    }

    public $requestForm;
    public function getRequestForm($data)
    {
        if (is_null($this->requestForm)) {
            $action = getLinkPHP();
            $antiDoubleClick = JSCODE_ANTI_DOUBLE_CLICK;
            $this->requestForm =<<<__HTML__
<input type="radio" name="admit" value="%%uid%%">
__HTML__;
        }

        return str_replace("%%uid%%", html_escape($data['uid']), $this->requestForm);
    }
}

class SearchUserView extends SortTableView
{
    public function getEmptyHtml()
    {
        return<<<__HTML__
####no_data####
__HTML__;
    }
    public function getBox(& $sortTable, $body)
    {
        $link = $sortTable->getChangePageLink();
        $next = $sortTable->getNextPageLink($link);
        $prv = $sortTable->getPreviousPageLink($link);
        $navi = $sortTable->getPageNavigateLink($link);
        //$action = $sortTable->getNowPageLinkHref($link);
        $antiDoubleClick = JSCODE_ANTI_DOUBLE_CLICK;
        $action =  getLinkPHP();

        return<<<__HTML__
{$prv}{$navi}{$next}
<form action="{$action}" method="post">
<table class="table111">
{$body}
</table>
<input type="hidden" name="request" value="1">
<input type="hidden" name="sendmail" value="1">

<input type="submit" value="####setup_reply_user_1####" onClick="{$antiDoubleClick}">
</form>


__HTML__;
    }

    public function getHeaderTr($body)
    {
        return<<<__HTML__
<tr style="background-color:#888888;color:white;">
{$body}

</tr>\n
__HTML__;
    }

    public function getListTr($body)
    {
        return<<<__HTML__
<tr style="background-color:#ffffff;">
{$body}</tr>\n
__HTML__;
    }
}

class SearchUser extends SortTable
{
    public $limit = 10;

    public function getOrder($post)
    {
        if (is_numeric($post['sort']) || ctype_digit($post['sort'])) {
            $keys = array_keys($this->getColumns());
            $sort = $keys[$post['sort']];
            if ($this->isEnableSortColumn($sort)) {
                $order = $this->adapter->formatOrder($sort);
                $this->order = $sort;
                if ($post['desc']) {
                    $order = str_replace(',', ' DESC,', $order);
                    $order .= ' DESC';
                    $this->desc = true;
                } else {
                    $this->desc = false;
                }
            }
        } else {
            $order = $this->adapter->getDefaultOrder();
        }

        return $order ? ' ORDER BY ' . $order : '';
    }
}

/**************************************************************************************************************************/
/**
 * 検索ユーザテーブル取得
 */
function getSearchUserTable($post)
{

    $sortTable = & new SearchUser(new SearchUserAdapter(), new SearchUserView(), true);
    $sortTable->setResult($post, $_GET);

    return $sortTable->show();
}

function setCheckMsg($msg)
{
    global $_checkMsg;
    $_checkMsg[] = $msg;
}

function getCheckMsg()
{
    global $_checkMsg;

    return implode("\n", (array) $_checkMsg);
}

/**
 * 承認依頼メール
 */
function sendRequestMail($admit, $self)
{
    $admit = getUserByuid($admit);
    /* 承認依頼メール */
    $admit['target_uid'] = $self['uid'];
    $admit['target_name'] = $self['name'];
    $admit['target_name_'] = $self['name_'];

    $format = Get_MailFormat(MFID_1);
    $result = Pc_Mail_Send($admit, $format[0], array('mrid'=>-1));
    error_log(date("Y/m/d H:i:s")."\t".$admit["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);

    /* 承認依頼メール(パスワード) 雛型IDが0ならば送らない */
    if (!is_false($result) && $GLOBALS['Setting']->Mfid2IsNot0()) {
        sleep(1); //パスワードメールが先に届くのを防ぐ

        $format = Get_MailFormat(MFID_2);
        $result = Pc_Mail_Send($admit, $format[0], array('mrid'=>-1));
        error_log(date("Y/m/d H:i:s")."\t".$admit["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);
    }

    return (!is_false($result))? $admit: false;
}

main();
