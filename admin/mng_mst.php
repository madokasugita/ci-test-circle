<?php

/**
 * 管理者マスタ
 */

//define('DEBUG'		,1);
define('DIR_ROOT'	,"../");
require_once(DIR_ROOT."crm_define.php");
require_once(DIR_LIB."CbaseFDB.php");
require_once(DIR_LIB."CbaseFGeneral.php");
require_once(DIR_LIB."CbaseFunction.php");
require_once(DIR_LIB."CbaseSortList.php");
require_once(DIR_LIB."CbaseMailBody.php");
require_once(DIR_LIB."ResearchSortListView.php");
require_once(DIR_LIB."MreAdminHtml.php");
require_once(DIR_LIB."CbaseEncoding.php");
if(!$_POST["csvdownload"])
    encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

$DIR_IMG = DIR_IMG;
$PHP_SELF = getPHP_SELF();
$SID = getSID();

$_GET = array_reflex($_GET, 'trim');
$_POST = array_reflex($_POST, 'trim');

if(is_good($_POST['mode']))
    $_GET['mode'] = $_POST['mode'];
if (is_good($_GET['mode'])) {
    switch ($_GET['mode']) {
        case "mail":
            list($ok, $ng) = mailPasswd($_POST['muid']);
            break;
        case "mailpw":
            list($ok, $ng) = mailPasswdWithRandomPw($_POST['muid']);
            break;
        case "update":
            if (isset($_POST['makepw'])) {
                $_POST['pw'] = get360RandomPw();
                $_GET['mode'] = "edit";
                $_GET['muid'] = $_POST['muid'];
                break;
            }
            if (existsEditError()) {
                $_GET['mode'] = "edit";
                $_GET['muid'] = $_POST['muid'];
                break;
            }
            if ($_POST['muid']=="new") {
                if(Set_Musr($_POST,"insert"))
                    $ok = "登録しました";
                else
                    $ng = "登録に失敗しました";
            } else {
// 				if(!isSuperUser())	//Set_Musrで全ての項目がないと更新されないため、pwを取得
// 				{
// 					$musr = Get_Musr($_POST['muid']);
// 					$_POST['pw'] = $musr['pw'];
// 				}
                if(Set_Musr($_POST,"update",$_POST['muid']))
                    $ok = "更新しました";
                else
                    $ng = "更新に失敗しました";
            }
            break;
        case "edit":
            if ($_GET['muid']=="new") {
                $_POST['muid'] = "new";
            } else {
                $_POST = Get_Musr($_GET['muid']);
            }
            break;
        case "delete":
            if(Unset_Musr($_GET['muid']))
                $ok = "削除しました";
            else
                $ng = "削除に失敗しました";
            break;
        default:
            break;
    }
}

class SortAdapter extends SortTableAdapter
{
    public function getResult($where)
    {
        return FDB::select(T_MUSR, '*', $where);
    }

    public function getCount($where)
    {
        $count =  FDB::select1(T_MUSR, 'count(muid) as count', $where);

        return $count['count'];
    }

    public function getColumns()
    {
        return array(
            "button" => "編集/削除"
            ,"muid" => "ID"
            ,"id" => "ログインID"
            ,"pw" => "ログインPW"
            ,"divs" => "部署"
            ,"name" => "氏名"
            ,"email"=>"メールアドレス"
            ,"flg" => "管理者"
        );
    }

    public function getCsvColumns()
    {
        $a = $this->getColumns ();
        unset($a['button']);
        if (!isSuperUser() || !isReversiblePw()) {
            unset($a['pw']);
        }

        return $a;
    }

    public function getNoSortColumns()
    {
        return array(
            "pw",
            "button"
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
//				if($shMuid == 1 && $data['pw'] == 'cbase')
//					return;
                global $DIR_IMG,$PHP_SELF,$SID;
                $shMuid = html_escape($shMuid);

                return <<<__HTML__
<form action="{$PHP_SELF}?{$SID}&mode=edit&muid={$shMuid}" method="post">
<input type="submit" name="submit" value="編集" class="white button">
</form>
<form action="{$PHP_SELF}?{$SID}&mode=delete&muid={$shMuid}" method="post">
<input type="submit" name="submit" value="削除" class="white button" onClick="return window.confirm('削除しますか？');">
</form>
__HTML__;
                break;
            case 'flg':
                return ($data[$key]==1)? "○":"×";
            case 'pw':
                return "....";
            default:
                return html_escape($data[$key]);
        }
    }

    public function getCsvColumnValue($data, $key)
    {
        if ($key=='div1') {
            return getDiv1NameById($data[$key]);
        }
        if ($key=='div2') {
            return getDiv2NameById($data[$key]);
        }
        if ($key=='div3') {
            return getDiv3NameById($data[$key]);
        }
        if ($key=='pw') {
            if (isSuperUser() || isReversiblePw()) {
                return getDisplayPw($data[$key]);
            }

            return;
        }

        return $data[$key];
    }
}


//--------------

$title = "管理者マスタ";

if ($_GET['mode']=="mail") {
    $backline = D360::getBackBar();

    $sub = D360::getSubject(
        '編集',
        '管理者のデータを編集します'
    );

    $body = <<<__HTML__
{$backline}
<div style="margin-left:30px">
<br>

{$sub}
<div align="left" style="color:#ff0000;">{$ng}</div>
<div align="left" style="color:#0000ff;">{$ok}</div>


<form action="{$PHP_SELF}?{$SID}" method="POST">
<input type="submit" value="戻る" class="white button">
</form>
</div>
__HTML__;
} elseif ($_GET['mode']=="update") {
    $backline = D360::getBackBar();

    $sub = D360::getSubject(
        '編集',
        '管理者のデータを編集します'
    );

    $mail_disabled = " disabled";
    if(is_good($ok) && is_good($_POST['email']))
        $mail_disabled = "";
    else if(is_good($ok))
        $mail_error = "メールアドレスが設定されていません";


    $muid = html_escape($_POST['muid']);
    $button_class = (is_good($mail_disabled)) ? "":<<<__HTML__
class="white button"
__HTML__;

    $body = <<<__HTML__
{$backline}
<div style="margin-left:30px">
<br>

{$sub}
<div align="left" style="color:#ff0000;">{$ng}</div>
<div align="left" style="color:#0000ff;">{$ok}</div>


<form action="{$PHP_SELF}?{$SID}" method="POST">
<input type="submit" value="戻る" class="white button">
</form>
<hr>
<div style="color:#ff0000;">{$mail_error}</div>


<form action="{$PHP_SELF}?{$SID}" method="POST" onSubmit="return this.flag?false:this.flag=true;">
<input type="hidden" name="mode" value="mail">
<input type="hidden" name="muid" value="{$muid}">
<input type="submit" value="パスワードを自動生成してメール通知"{$mail_disabled}{$button_class}>
</form>

<!--
<form action="{$PHP_SELF}?{$SID}" method="POST" onSubmit="return this.flag?false:this.flag=true;">
<input type="hidden" name="mode" value="mailpw">
<input type="hidden" name="muid" value="{$muid}">
<input type="submit" value="パスワードを自動生成してメール送信"{$mail_disabled}{$button_class}>
</form>
-->
</div>
__HTML__;
} elseif ($_GET['mode']=="edit" && is_good($_GET['muid'])) {
    $body = getEditTable();


    //--------------

    $backline = D360::getBackBar();

    $sub = D360::getSubject(
        '編集',
        '管理者のデータを編集します'
    );

    $body = <<<__HTML__
{$backline}
<div style="margin-left:30px">
<br>

{$sub}
{$body}
</div>
__HTML__;
} else {
    $c =& new CondTable(new CondTableAdapter(), new ResearchLimitCondTableView(), true);
    //$c->visible = false;
    $v =& new ResearchSortTableView();
    $v->setColStyle('button', 'align="center" style="width:100px;"');
    $s =& new SortTable(new SortAdapter(), $v, true);

    $sl =& new SearchList($c, $s);

    $body = $sl->show(array('op'=>'sort'));


    //--------------

    $backline = D360::getRefreshBar();

    $sub1 = D360::getSubject(
        '新規登録',
        '新たに管理者を登録します'
    );

    $sub2 = D360::getSubject(
        '一覧表示',
        '管理者の編集/削除を行います'
    );

    $info = D360::getUpdateInfo();

    /* 追加はSuperのみ */
    if(isSuperUser())
    $addHtml = <<<__HTML__
{$sub1}
<table border="0" cellpadding="0" cellspacing="0" width="420">
<tr>
  <td width="170" height="24" align="center" style="font-size:12px;">新規作成</td>
  <td width="18"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
  <td width="232">
    <form action="{$PHP_SELF}?{$SID}&mode=edit&muid=new" method="post">
      <input type="submit" name="submit" value="新規作成" class="white button">
    </form>
  </td>
</tr>
</table>
<br>
__HTML__;

    $body = <<<__HTML__
{$backline}
<div style="margin-left:30px">

{$addHtml}

{$sub2}
<div align="left" style="color:#ff0000;">{$ng}</div>
<div align="left" style="color:#0000ff;">{$ok}</div>


{$body}
</div>
{$info}
__HTML__
;
}

$objHtml = new MreAdminHtml($title, "", false);
echo $objHtml->getMainHtml($body);
exit;


function mailPasswd($muid)
{
    $musr = Get_Musr($muid);

    //もしも規定回数以上にパスワードを間違えていたら
    if(is_void($musr['email']))

        return array("", "メールアドレスが設定されていません");
    if(is_void($musr['pw']))

        return array("", "パスワードが設定されていません");
    $format = Get_MailFormat(MFID_PASSWD_REISSU_ADMIN);
    if(is_void($format))

        return array("", "メールの雛型が設定されていません");

    $musr['lang_type'] = 0;

    if (isReversiblePw()) {
        $retry = 0;
        do {
            $pw = get360RandomPw();
            $musr['pw'] = getPwHash($pw);
            $result = FDB::update(T_MUSR, FDB::escapeArray(array('pw'=>$musr['pw'], 'pwmisscount'=>0, 'pdate'=>null)), "WHERE muid = ".FDB::escape($musr['muid']));
            $retry++;
        } while (is_false($result) && $retry < 3);
    }
    $res = Pc_Mail_Send($musr, $format[0], array('mrid'=>-1));
    if($res)

        return array("メールを送信しました", "");
    else
        return array("メール送信に失敗しました", "");
}

function mailPasswdWithRandomPw($muid)
{
    $musr = Get_Musr($muid);

    if(is_void($musr['email']))

        return array("", "メールアドレスが設定されていません");

    /* 雛型に設定されている差し込み文字pwの機能で書き換え
    $musr['pw'] = get360RandomPw();

    if(!Set_Musr($musr,"update",$musr['muid']))

        return array("", "パスワードの生成に失敗しました");
    */

    $format = Get_MailFormat(MFID_PASSWD_REISSU_ADMIN);
    if(is_void($format))

        return array("", "メールの雛型が設定されていません");

    $musr['lang_type'] = 0;

    $res = Pc_Mail_Send($musr, $format[0], array('mrid'=>-1));
    if($res)

        return array("パスワード通知メールを送信しました", "");
    else
        return array("メール送信に失敗しました", "");
}

function getEditColumns()
{
    return array(
        "muid" => "ID"
        ,"pdate" => "PW変更日時"
        ,"id" => "ログインID"
        ,"pw" => "ログインPW(上書き)"
        ,"nowpw" => "現在のログインPW"
        ,"divs" => "部署"
        ,"name" => "氏名"
        ,"email"=>"メールアドレス"
        ,"flg" => "管理者"
        ,"pwmisscount" => "パスワード間違い回数"
    );
}

function existsEditError()
{
    global $editError;
    $editError = array();
    foreach (getEditColumns() as $key => $value) {
        switch ($key) {
            case "id":
                $where = array();
                $where[] = "id=".sql_escape($_POST[$key]);
                if($_POST['muid']!="new")	$where[] = "muid!=".sql_escape($_POST['muid']);
                if (FDB::is_exist(T_MUSR, "where ".implode(" and ", $where))) {
                    $editError[$key] = "▲指定されたIDは既に使用されています";
                }
                if (!is_abc123($_POST[$key])) {
                    $editError[$key] = "▲半角英数字で入力してください";
                }
                if (is_void($_POST[$key])) {
                    $editError[$key] = "▲入力必須項目です";
                }
                break;
            case "pw":
                if(!isSuperUser())
                    continue;

                if (is_good($_POST[$key]) && is_good($error = get360PwError($_POST[$key]))) {
                    $editError[$key] = "▲".$error;
                }
                if ($_POST['muid']=="new" && is_void($_POST[$key])) {
                    $editError[$key] = "▲入力必須項目です";
                }
                break;
            case "name";
                if (is_void($_POST[$key])) {
                    $editError[$key] = "▲入力必須項目です";
                }
                break;
            default:
                break;
        }
    }

    return (!empty($editError));
}

function getEditError()
{
    global $editError;

    return $editError;
}

function getEditForm($key)
{
    $name = html_escape($key);
    $value = html_escape($_POST[$key]);
    switch ($key) {
        case "muid":
            $show = ($value=="new")? "新規":$value;

            return <<<__HTML__
{$show}<input type="hidden" name="{$name}" value="{$value}">
__HTML__;
        case "pdate":
            return <<<__HTML__
{$value}<input type="hidden" name="{$name}" value="{$value}">
__HTML__;
        case "flg":
            $checked = ($value==1)? " checked":"";

            return <<<__HTML__
<input type="radio" name="{$name}" value="0" checked> ×
<input type="radio" name="{$name}" value="1"{$checked}> ○
__HTML__;
        case "pw":
            if (isSuperUser()) {
                $value = ($_POST['makepw'])? $value:"";

                return <<<__HTML__
<input type="text" name="{$name}" value="{$value}">
<input type="submit" name="makepw" value="PW生成" class="white button">
__HTML__;
            } else {
                return "*****";
            }
        case "nowpw":
            if (isSuperUser()) {
                return getDisplayPw($_POST['pw']);
            } else {
                return "*****";
            }
        default:
            return <<<__HTML__
<input type="text" name="{$name}" value="{$value}">
__HTML__;
    }
}

function getEditTable()
{
    global $DIR_IMG,$PHP_SELF,$SID;
    $error = getEditError();
    $table = "";
    foreach (getEditColumns() as $key => $value) {
        if(!isReversiblePw() && $key=="nowpw")
            continue;

        $value = html_escape($value);
        $form = getEditForm($key);
        $table .= <<<__HTML__
<tr>
  <td width="170" height="24" align="center" style="font-size:12px;">{$value}</td>
  <td width="18"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
  <td width="232" style="font-size:12px;">{$form}<div style="color:#ff0000;font-size:10px;">{$error[$key]}</div></td>
</tr>
__HTML__;
    }

    return <<<__HTML__
<form action="{$PHP_SELF}?{$SID}&mode=update" method="POST">
<table border="0" cellpadding="0" cellspacing="0" width="420">
{$table}
<tr>
  <td><br></td>
  <td><br></td>
  <td><input type="submit" name="submit" value="登録"  class="white button"></td>
</tr>
</table>
</form>
__HTML__;
}

function isSuperUser()
{
    return ($_SESSION['muid']=="1");
}
