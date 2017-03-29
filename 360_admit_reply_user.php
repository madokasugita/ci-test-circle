<?php

//define('DEBUG', 1);
define('DIR_ROOT', "");
require_once (DIR_ROOT . 'crm_define.php');

require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . '360_Function.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360_RelationEdit.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseFErrorMSG.php');
encodeWebAll();
session_start();
checkAuthUsr360();

$serial_no = $_REQUEST['serial_no'];
if (getHash360($serial_no) != $_REQUEST['hash']) {
    print "invali hash!";
    exit;
}

$user = getUserBySerial($serial_no);

if (!$user) {
    print "error NoUser";
    exit;
}

define('PAGE_TITLE', MSG_MENU_TITLE);

if (is_good($_POST['admit'])) {
    $result = sendAdmitMail($type, $user, $body);
    if (!is_false($result)) {
        setSelectStatus(2, $_SESSION['login']['uid'],$user);
        $name = getUserName($user);
        $tmp=str_replace('NNN', $name, replaceMessage('####sent_approval_mail####'));
        $ERROR->addMessage("####complete_approval####( {$tmp} )");
    } else {/* 承認メール送信失敗 */
        $ERROR->addMessage("####send_mail_error####");
    }

    //回答依頼メール
    $T_USER_MST = T_USER_MST;
    $T_USER_RELATION = T_USER_RELATION;
    $UID_ESCAPED = FDB :: escape($user['uid']);
    $users = FDB :: getAssoc("select * from {$T_USER_RELATION} a left join {$T_USER_MST} b on a.uid_b = b.uid where a.uid_a = {$UID_ESCAPED} AND a.add_type IN (0, 1) GROUP BY a.uid_b order by div1,div2,div3,uid;");
    $result = sendAnswerRequestMail($user['uid'], $users);
    if (is_true($result)) {
        $ERROR->addMessage('####send_answer_mail####');
    } elseif ($result=="no_send") {
    } else {
        $ERROR->addMessage('####send_answer_mail_error####');
    }
} else
    if (is_good($_POST['deny'])) {
        $admitForm = getDenyMailForm($user);
    } else
        if (is_good($_POST['sendmail'])) {
            $body = trim($_POST['body']);
            if (is_good($body)) {
                $result = sendDenyMail($type, $user, $body);
                if (!is_false($result)) {
                    setSelectStatus(0, $_SESSION['login']['uid'], $user);
                    $ERROR->addMessage("####send_reject_mail####");
                    $admitForm = "";
                } else {/* 差し戻しメール送信失敗 */
                    $ERROR->addMessage("####send_mail_error####");
                    $admitForm = getDenyMailForm($user);
                }
            } else {
                $ERROR->addMessage("####input_reject_mail####");
                $admitForm = getDenyMailForm($user);
            }
        }

if (is_void($admitForm)) {
    $admitForm = getAdmitForm($user);
}

$RE = new RelationEdit($user);
$user_info = $RE->getHtmlUserInfo();
$replyUserTable = $RE->getHtmlRelationInfo();

if($ERROR->isError())
    $message = '<div style="margin-bottom:20px">'.$ERROR->show(650).'</div>';

$SID = getSID();
$body =<<<__HTML__
<div style="margin:5px auto;width:880px;text-align:left;">

<div style="text-align:left;margin:10px auto 15px auto;">
[ <a href="360_menu.php?{$SID}">####linkname_mypage####</a> ]
</div>

{$message}
{$user_info}
{$replyUserTable}
{$stateMsg}
{$admitForm}
</div>
__HTML__;

$objHtml = & new UserHtml("####mypage_menu5####");
print $objHtml->getMainHtml($body);
exit;

/**
 * 承認フォーム取得
 */
function getAdmitForm($user)
{
    if ($user['select_status'] != 1)
        return<<<__HTML__

__HTML__;


    $action = getThisPHPSELF();
    $antiDoubleClick = JSCODE_ANTI_DOUBLE_CLICK;

    return<<<__HTML__

<div style="text-align:center;margin:20px 0;background-color:#ffcccc;padding:10px">
<form action="{$action}" method="POST" style="display:inline">
<input type="submit" name="admit" value="####approval_btn####" onClick="{$antiDoubleClick}">
<input type="submit" name="deny" value="####reject_btn####" onClick="{$antiDoubleClick}" style="margin-left:30px">
</form>
</div>
__HTML__;
}

/**
 * 差し戻しメールフォーム取得
 */
function getDenyMailForm($user)
{
    $action = getThisPHPSELF();
    $antiDoubleClick = JSCODE_ANTI_DOUBLE_CLICK;
    $tmp=str_replace('NNN', getUserName($user), replaceMessage('####reject_approval####'));

    return<<<__HTML__
<form action="{$action}" method="POST">
<div style="color:#0000ff;">####reject_attention_message####</div><br>
<div>{$tmp}</div><br>
<div>

####reject_message####

</div><br>
<textarea name="body" cols="50" rows="10" style="width:800px"></textarea><br>
<input type="submit" name="sendmail" value="####send_mail####" onClick="{$antiDoubleClick}">
</form>
__HTML__;
}
function getThisPHPSELF()
{
    return getPHP_SELF() . '?' . getSID() . '&serial_no=' . html_escape($_REQUEST['serial_no']) . '&hash=' . getHash360($_REQUEST['serial_no']);
}

/**
 * 差し戻しメール
 */
function sendDenyMail($type, $user, $body_)
{
    $user['admit_name'] = $_SESSION['login']['name'];
    $user['admit_name_'] = $_SESSION['login']['name_'];
    $user['body'] = $body_;

    $format = Get_MailFormat(MFID_3);
    $result = Pc_Mail_Send($user, $format[0], array('mrid'=>-1));
    error_log(date("Y/m/d H:i:s")."\t".$user["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);

    return $result;
}

/**
 * 承認完了
 */
function sendAdmitMail($type, $user, $body)
{
    $user['admit_name'] = $_SESSION['login']['name'];
    $user['admit_name_'] = $_SESSION['login']['name_'];
    $user['body'] = $body;

    $format = Get_MailFormat(MFID_4);
    $result = Pc_Mail_Send($user, $format[0], array('mrid'=>-1));
    error_log(date("Y/m/d H:i:s")."\t".$user["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);

    return $result;
}

function getHtmlAddButton()
{
    return "";
}
