<?php

define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . '360_Function.php');
require_once (DIR_LIB . 'CbaseFErrorMSG.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360_Smarty.php');
encodeWebAll();
/******************************************************************************************************/
$language = $_REQUEST['l'];
if (isset($language)) {
    setcookie('lang360',$language);
    $_COOKIE['lang360'] = $language;
}

$SUCCESS = new C_ERROR_MESSAGE();
switch (getMode()) {
    case "demo" :
        demo();
        break;
    default:
        break;
}
$smarty = new MreSmarty();
$smarty->assign('errors',$ERROR->getErrorMessages());
$smarty->assign('success',$SUCCESS->getErrorMessages());
$smarty->assign('mypage_360_demo_mail_message',$SUCCESS->getErrorMessages());
$smarty->assign('post_name',html_escape($_POST['name']));
$smarty->assign('post_email',html_escape($_POST['email']));
$smarty->display('360_demo.tpl');
exit;
/******************************************************************************************************/
function demo()
{
    global $ERROR,$SUCCESS;
    $name = $_POST["name"];
    $email = $_POST["email"];
    $name = preg_replace("/[ 　\n\t]/u", '', $name); //nameに含まれる空白文字は取り除く 2008/12/17
    $email = preg_replace("/[ 　\n\t]/u", '', $email); //emailに含まれる空白文字は取り除く 07/0514

    if (is_void($name))
        $ERROR->addMessage('####mypage_360_demo_error_no_name####');
    else if (is_void($email))
        $ERROR->addMessage('####mypage_360_demo_error_no_email####');
    else if (!FCheck::isEmail($email))
        $ERROR->addMessage('####mypage_360_demo_error_valid_email####');

    if ($ERROR->isError())
        return false;

    $user = array(
        'name' => $name,
        'email' => $email,
        'serial_no' => getUniqueIdWithTable(T_UNIQUE_SERIAL , "serial_no", 8),
        'uid' => getUniqueIdWithTable_UID(T_UNIQUE_UID, "uid"),
        'pw' => getPwHash(get360RandomPw()),
        'mflag' => 1,
        'sheet_type' => DEMO_SHEET_TYPE || 1,
        'lang_type' => 0,
    );
    $rs = FDB::insert(T_USER_MST, FDB::escapeArray($user));
    if (is_false($rs)) {
        $ERROR->addMessage('####mypage_360_demo_error_user_insert####');

        return false;
    }

    $result = true;
    if ($GLOBALS['Setting']->Mfid5IsNot0()) {
        $format = Get_MailFormat(MFID_5);
        $result = Pc_Mail_Send($user, $format[0], array('mrid'=>-1));
        error_log(date("Y/m/d H:i:s")."\t".$user["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);
    }

    if(is_good($result))
        $SUCCESS->addMessage('####mypage_360_demo_mail_message####');

    return $result;
}
