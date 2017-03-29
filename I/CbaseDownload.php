<?php

require_once(DIR_LIB.'CbaseCommon.php');
require_once(DIR_LIB.'CbaseEncoding.php');

if ($_GET['mode']=="download") {
    encodeWebInAll();
    if(md5($_GET['file']."/".$_GET['filename']."/".SYSTEM_RANDOM_STRING)!=$_GET['check'])
        die("アクセスが不正です");
    $file = DIR_DATA.basename($_GET['file']);
    if(!file_exists($file))
        die("ファイルが存在しません");

    downloadFile($file, $_GET['filename']);
    exit;
}

function getDownloadLink($file, $filename)
{
    $PHP_SELF = getPHP_SELF();
    $SID = getSID();
    $check = md5($file."/".$filename."/".SYSTEM_RANDOM_STRING);
    $file = html_escape($file);
    $filename = html_escape($filename);
    $filename_ = urlencode(html_escape($filename));

    return <<<__HTML__
<a href="{$PHP_SELF}?{$SID}&mode=download&file={$file}&filename={$filename_}&check={$check}" target="_blank">{$filename}</a>
__HTML__;
}
