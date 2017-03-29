<?php
define("DIR_ROOT", "");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
session_start();
checkAuthUsr360();

$files = array ();

$file = $_GET['file'];
$hash = substr(md5($file . "tawedklfahcklaop"), 0, 8);

if ($hash != $_GET['hash']) {
    print "invalid hash";
    exit;
}

if (!ereg('^[0-9a-zA-Z]+$', $file)) {
    print "invalid file";
    exit;
}
$lang_type = (int) $_SESSION['login']['lang_type'];

$user = getUserByUid_($file);
if (!$user) {
    print "no user";
    exit;
}

if (!$user['lang_type']) {
    //対象者が日本語
    $server_filename = $file . '.pdf';
    //$filename = $user['uid'] . '_' . $user['name'] . '_' . replaceMessage($GLOBALS['_360_sheet_type'][$user['sheet_type']]) . '_日.pdf';
    $filename = $user['uid'] . '_' . $user['name'].'.pdf';

/* } elseif ($lang_type && file_exists(DIR_FB . $file . '_' . $lang_type . '.pdf')) {
    $result = FDB::select1(T_USER_MST,'div1','where uid = '.FDB::escape($user['uid']));

    switch ($result['div1']) {
        case"201":
            $div1 = "SHANGHAI";
            break;
        case"202":
            $div1 = "TAIPEI";
            break;
        case"203":
            $div1 = "KOREA";
            break;
        case"204":
            $div1 = "BHK_BSZ";
            break;
    }
    //対象者がその他言語　かつ　DL使用としている人と同じ言語
    $server_filename = $file . '_' . $lang_type . '.pdf';
    $name = $user['name_'] ? $user['name_'] : $user['name'];
    //TODO:場所によってかわる
    $filename = $user['uid'] . '_' . $name . '_' . $user['lang_type'].'.pdf'; //TODO:言語 */
} else {
    $name = $user['name_'] ? $user['name_'] : $user['name'];
    $filename = $user['uid'] . '_' . $name . '_' . $user['lang_type'].'.pdf';
    $server_filename = $file . '.pdf';
}

$filename = encodeDownloadFilename($filename);

header("Pragma: private");
header("Cache-Control: private");
header("Content-disposition: attachment; filename=\"{$filename}\"");
header("Content-type: application/octet-stream; name=\"{$filename}\"");
readfile(DIR_FB . $server_filename);

write_log_dl_user("start", $user['uid']);
exit;

