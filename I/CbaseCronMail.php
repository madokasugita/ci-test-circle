<?php
define('DEBUG',0);

//メール配信実行
//変数セット,インクルード

define('NOT_USE_CHACHE',1);
require_once 'cbase/crm_define3.php';
require_once '../crm_define.php';
$lock = DIR_DATA . "mr.lock";
$path = "";

//ログ書き
//ロックファイルをセット
if (is_file($lock)) {
    error_log(date("Ymd") . "\t" . date("His") . "\t" . "CRON_END" . "\t" . "NG_FileLock" . "\n", 3, LOG_CRONMAIL2);
    $filetime = fileatime($lock);
    //MAIL_LOCK_REPORT_MINS分以上ロック掛かりっぱなし
    if (MAIL_LOCK_REPORT_MINS != 0 && time() - $filetime > MAIL_LOCK_REPORT_MINS * 60 && $filetime != file_get_contents(LOG_CRON_ERROR)) {
        mail_report($filetime,$lock);
    }
    exit;
} else {
    error_log(date("Ymd") . "\t" . date("His") . "\t" . "CRON_START" . "\t" . "OK" . "\n", 3, LOG_CRONMAIL);
}

touch($lock);

//インクルード
require_once ($path . "CbaseFDB.php");
require_once ($path . "CbaseFEventMail.php");
require_once ($path . "CbaseFEvent.php");
require_once ($path . "CbaseFEnquete.php");
require_once ($path . "CbaseFMail.php");
require_once ($path . "CbaseFCondition.php");
require_once ($path . "CbaseFGeneral.php");
require_once ($path . "CbaseFUser.php");
require_once ($path . "CbaseFSendmail.php");

FDB::setData("update", T_MAIL_RSV, array('flgs'=>13,'count'=>0), "where flgs=12");
//配信予約データをチェック,取得
$array1 = Check_MailEvent();
//配信予約対象が取れないので実行終了
if (!$array1) {
    //ログ書き？
    error_log(date("Ymd") . "\t" . date("His") . "\t" . "CRON_END" . "\t" . "OK_NoEvent" . "\n", 3, LOG_CRONMAIL);
    //ロックファイルを解除
    unlink($lock);
    exit;
}

//配信実行
//複数配信に対応
foreach ($array1 as $array) {
    unset ($cond);
    unset ($mode);
    unset ($key);
    unset ($value);
    unset ($member);
    //ログ書き
    error_log(date("Ymd") . "\t" . date("His") . "\t" . " MAIL_START" . "\t" . $array["mrid"] . "\n", 3, LOG_CRONMAIL);
    //配信ステータス変更
    $array["flgs"] = 9;
    Save_MailEvent("update", $array);
    //配信条件取得//$cnid==0 ->全員に配信
    if ($array["cnid"] > 0) { //配信条件がある場合
        $cond = Get_Condition("id", $array["cnid"]);
    }
    $format = Get_MailFormat($array["mfid"]);
    if (!$format) {
        //配信ステータス変更
        $array["flgs"] = 4;
        Save_MailEvent("update", $array);
        //ログ記録
        error_log(date("Ymd") . "\t" . date("His") . "\t" . " MAIL_END" . "\t" . "NG_MailFormat" . "\n", 3, LOG_CRONMAIL);
        continue;
    }
    //会員データ取得
    if ($array["cnid"] == -1) {
        //メールフォーマット中からridを取得して、evidを取る→$valueにセットする
        preg_match("/(EV_)([a-z0-9]{8})/i", $format[0]["body"], $aryTmpData);
        $aryTmpEvent = Get_Enquete("rid", $aryTmpData[2], "", "");
        $mode = "reminder";
        $key = "";
        $value = $aryTmpEvent[-1]["evid"];
    } elseif ($array["cnid"] > 0) {
        $mode = "sql";
        $key = "";
        $value = $cond[0]["strsql"];
    } else {
        $mode = "all";
        $key = "";
        $value = "";
    }
    $member = Get_UserData($mode, $key, $value, "");
    if (!$member) {
        //配信ステータス変更
        $array["flgs"] = 3;
        Save_MailEvent("update", $array);
        //ログ記録
        error_log(date("Ymd") . "\t" . date("His") . "\t" . " MAIL_END" . "\t" . "NG_NoUser" . "\n", 3, LOG_CRONMAIL);
        continue;
    }

    if ($array['evid']) {
        $enq = Get_Enquete_Main('id', $array['evid'], '', '');
        $event = $enq[-1];
    }

    //ログ記録
    //配信実行
    //NO123 メール配信履歴追加 *1
    CbaseMassMailer($member, $format[0],$array,$cond, "addlog", $lock);

    //配信ステータス変更
    $array["flgs"] = 1;
    $array["count"] = count($member);
    Save_MailEvent("update", $array);

    //配信ログ記録
    //			if ($array["flgl"]==1) Save_EventLog();
    //ログ書き
    error_log(date("Ymd") . "\t" . date("His") . "\t" . " MAIL_END" . "\t" . "OK" . "\n", 3, LOG_CRONMAIL);

} //foreach

//ロックファイルを解除
unlink($lock);

//ログ書き
error_log(date("Ymd") . "\t" . date("His") . "\t" . "CRON_END" . "\t" . "OK" . "\n", 3, LOG_CRONMAIL);
exit;
/**
 * ロックが掛かりっぱなしの時に管理者に連絡する。
 */
function mail_report($filetime,$lock)
{
    $fp = fopen(LOG_CRON_ERROR, 'w');
    fwrite($fp, $filetime);
    fclose($fp);
    $DOMAIN = DOMAIN;
    $FILE = __FILE__;
    $since = date('Y-m-d H:i:s', $filetime);

    $MAIL_BODY =<<<MAIL
DOMAIN={$DOMAIN}
FILE = {$FILE}
LOCKFILE = {$lock}
since {$since}MailLocked

MAIL;
    error_log($MAIL_BODY, 1, MAIL_LOCK_REPORT_ADDRESS);
    error_log(date("Ymd") . "\t" . date("His") . "\t" . "ErrorReportSend" . "\n", 3, LOG_CRONMAIL2);
}
