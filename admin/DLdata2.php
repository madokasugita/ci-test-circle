<?php

/**
 * クラスタリング対応CSVDLプログラム
 *
 * @version 1.1
 * 更新履歴
 * 2007/10/02 ver1.1 クラスタリングしないときはコマンドをログに書くように
 */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB. 'CbaseFFile2.php');
require_once (DIR_LIB. 'CbaseHtml.php');
require_once (DIR_LIB. 'CbaseEncoding.php');
encodeWebInAll();

require_once(DIR_LIB.'CbaseFManage.php');
$fname = Check_ArgHash($_GET['file'], $_GET['hash']);

define("PRI_DL", "reseDL_");

define("THE_FILE", DIR_TMP . $fname);

$filename = date('Ymd').'評価Rawデータ'.$_360_sheet_type[$_GET['evid']];
foreach (explode(',',$_GET['param']) as $user_type) {
    if($user_type!='')
        $filename.=$_360_user_type[$user_type];
}
$filename.=DATA_FILE_EXTENTION;
$filename = encodeDownloadFilename(replaceMessage($filename));

if (!file_exists(THE_FILE)) {
    encodeWebOutAll();
    $html = <<<HTML
一度しかダウンロードできません。<br>
再度作成からやり直してください(管理メニュー -> データ取得・選択肢一覧 -> データDLボタン)
<br>
<br>
<button onclick="window.close()">閉じる</button>
HTML;
    $objHtml =& new ResearchAdminHtml("ダウンロードエラー");
    echo $objHtml->getMainHtml($html);
    exit;
}

$ad = file_get_contents(THE_FILE);
$ad = mb_convert_encoding($ad,OUTPUT_CSV_ENCODE,INTERNAL_ENCODE);

if($Setting->csvEncodeUtf16le())//BOMを追加
    $ad = chr(255) . chr(254).$ad;
if($Setting->csvEncodeUtf8())//BOMを追加
    $ad = chr(0xEF).chr(0xBB).chr(0xBF).$ad;

//$ad = $ad;

s_unlink(THE_FILE);

header("Pragma: private");
header("Cache-Control: private");
header("Content-disposition: attachment; filename=\"{$filename}\"");
header("Content-type: application/octet-stream; name=\"{$filename}\"");
header("Content-Length: " . strlen($ad));
print $ad;
exit;
