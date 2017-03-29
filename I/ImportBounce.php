<?php

define('DIR_ROOT', "../");
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'CbaseFgetcsv.php');
require_once(DIR_LIB.'CbaseEncoding.php');

define('MAIL_ERROR_MESSAGE', DIR_DATA."bounce.clog");//メールエラーメッセージ格納ファイルのパス


error_log(date("Ymd") . "\t" . date("His") . "\t" . "CRON_START" . "\t" . "OK" . "\n", 3, LOG_IMPORT_BOUNCE);
$fp = fopen(MAIL_ERROR_MESSAGE, "r");
if(is_false($fp))
    exit;

$firstRow = CbaseFgetcsv($fp);
$messageIndex = array_search( "Message", $firstRow);
if(is_false($messageIndex))
    exit;

while(!feof($fp))
{
    $row = CbaseFgetcsv($fp);
    setMailError($row[$messageIndex]);
}
fclose($fp);
error_log(date("Ymd") . "\t" . date("His") . "\t" . "CRON_END" . "\t" . "OK" . "\n", 3, LOG_IMPORT_BOUNCE);
exit;


function setMailError($json)
{
    if(is_void($json))
        return false;

    $json = json_decode($json, true);
    if(is_void($json))
        return false;

    $json = $json['bounce'];
    if(is_void($json))
        return false;

    $email = $json['bouncedRecipients'][0]['emailAddress'];
    if(is_void($email))
        return false;

    if(checkMstatus($json['bouncedRecipients'][0]['status']))
        return false;

    $user['send_mail_flag'] = "1";
    return (FDB::setData("update", T_USER_MST, $user, "WHERE email=".FDB::escape($email)));
}

function checkMstatus($mstatus)
{
    switch($mstatus)
    {
        case "4.4.7":
        case "5.1.1":
        case "5.2.1":
        case "5.3.0":
        case "5.7.1":
            return false;
        default:
            break;
    }
    return true;
}

?>
