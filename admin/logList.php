<?php
define('DIR_ROOT', '../');

require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

define("PATH_FILE", DIR_LOG);
$PHP_SELF = getPHP_SELF();
$SID = getSID();
if ($_GET['view_log']) {
    $filename = basename($_GET['view_log']);

    $file = file_get_contents(PATH_FILE . $filename);
    $file = str_replace("\r\n","\n",$file);//改行コードを\r\nに統一する。
    $file = str_replace("\r","\n",$file);
    $file = str_replace("\n","\r\n",$file);

    download($file, $filename);
    exit;
}

$html = doDir(PATH_FILE);
$html = <<<__HTML__
<table class="cont">
<tr>
  <th>ファイル名</th>
  <th><br></th>
</tr>
{$html}
</table>
__HTML__;

$objHtml =& new MreAdminHtml("各種ログ確認", "現在のサーバー: ".sha1($GLOBAL_CC->myhost.SYSTEM_RANDOM_STRING));
echo $objHtml->getMainHtml($html);
exit;

function doDir($path)
{
    global $PHP_SELF,$SID;
    $D360 = new D360();
    static $dir = "";
    static $count = 0;

    if (is_file($path)) {
        $filename = basename($path);

        return <<<__HTML__
<tr bgcolor="#ffffff">
  <td>{$filename}</td>
  <td align="center">
    <form action="{$PHP_SELF}?{$SID}&view_log={$filename}" method="POST" style="display:inline;">
    {$D360->getIconButton("submit", "download", "ui-icon-arrowthickstop-1-s", "")}
    </form>
  </td>
</tr>\n
__HTML__;
    }
    foreach (glob($path . "/*") as $file) {
        $count++;
        if ($count <= 1)
            $dir .= doDir($file);
        $count--;
    }

    return $dir;
}
