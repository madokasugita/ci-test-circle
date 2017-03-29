<?php
/**
 * PG名称：パスワード再発行ページ
 * 日　付：2007/03/12
 * 作成者：cbase Kido
 */
/******************************************************************************************************/
//define("DEBUG", 1);
/** ルートディレクトリ */
define('DIR_ROOT','');
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'360_FHtml.php');
require_once(DIR_LIB.'CbaseFunction.php');
require_once(DIR_LIB.'CbaseFErrorMSG.php');
require_once(DIR_LIB.'CbaseFDB.php');
require_once(DIR_LIB.'CbaseEncoding.php');
require_once(DIR_LIB.'CbaseMailBody.php');
require_once (DIR_LIB . '360_Smarty.php');
require_once (DIR_LIB . 'Encryption.php');//追加2012-05-18
encodeWebAll();
session_start();

/** ページタイトル */
define('PAGE_TITLE','####reissue_title####');

$C_PHP_SELF = getPHP_SELF();

if ($_POST['mode']==reissue && is_void($errors = checkInput())) {
    $id = trim($_POST['id']);
    $email = trim($_POST['email']);
    //データベースから指定したユーザＩＤのユーザを取得
    $user = FDB::select1(T_USER_MST,"*","where uid = ".FDB::escape($id)." AND email = ".FDB::escape($email));
    if(count($user) == 0)
        $errors[] = getMessage('reissue_user_notfound');

    //もしも規定回数以上にパスワードを間違えていたら
    if(!$_SESSION['muser'] && $Setting->limitPwLessOrEqual($user['pwmisscount']))
        $errors[] = getMessage('pwmiss_infomation');

    if (is_void($errors)) {
        $main_token = sha1(uniqid(mt_rand(), true));
        $encrypt_token = Encryption::getInstance()->enc($main_token);

        $user['PW_URL'] = DOMAIN.DIR_MAIN.'360_pw.php?token='.urlencode($encrypt_token);

        $format = Get_MailFormat(MFID_PASSWD_REISSU);

        //reissueデータ登録
        $reissue_url['token'] = FDB::escape($encrypt_token,true);
        //$reissue_url['token'] = $encrypt_token;
        $reissue_url['serial_no'] = FDB::escape($user['serial_no'],true);
        $reissue_url['cdate'] = FDB::escape(date("Y/m/d H:i:s"),true);;

        FDB::begin();
        $token_insert = FDB::insert('reissue_url', $reissue_url);
        if (is_false($token_insert)) {
            FDB::rollback();
            $errors[] = getMessage('token_insertion_error');
        } else {
            $result = Pc_Mail_Send($user, $format[0], array('mrid'=>-1));
            error_log(date("Y/m/d H:i:s")."\t".$user["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);

            if (!is_false($result)) {
                FDB::commit();
                $errors[] = getMessage('reissue_message1');
            } else {
                FDB::rollback();
                $errors[] = getMessage('send_mail_error');
            }
        }
    }
}

$smarty = new MreSmarty();

$smarty->assign('errors', $errors);
$smarty->display('360_reissue.tpl');
exit;

function checkInput()
{
    $res = array();
    if(is_void($_POST["id"]))
        $res[] = getMessage('login_error_id_invalid');

    if(is_void($_POST["email"]))
        $res[] = getMessage('login_error_mail_invalid');

    return $res;
}
