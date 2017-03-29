<?php
define("DEBUG",1);

define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFFile2.php');

system('chmod -R 777 ../*');

FDB::DELETE('backup_event');
foreach (glob(DIR_DATA."{*.lock,*.ccache}", GLOB_BRACE) as $file) {
    unlink($file);
    echo "unlink ".$file."<br>";
}
echo "<hr>";

FDB::DELETE('mail_rsv');
FDB::DELETE('mail_log');
FDB::DELETE('cond', "WHERE name = ''");
FDB::DELETE('reissue_url');
