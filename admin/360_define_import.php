<?php
//デバッグ用。登録せずにSQLを表示してくれる。
define('THISPAGE_NO_INSERT', 0);
if (THISPAGE_NO_INSERT) {
    define('DEBUG', 1);
}
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . '360_Importer.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFForm.php');

session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', '基本設定インポート');
/****************************************************************************************************************************/

class ThisImportModel extends ImportModel360
{
    public function importLine($line_no, $data)
    {
        $array['define'] = $data[0];
        $array['value'] = $data[1];
        $array['title'] = $data[2];
        $array['choice'] = $data[3];
        $array['explain'] = $data[4];

        $res = FDB::update(T_SETTING, array("value" => FDB::escape($array['value'])), "WHERE define = ".FDB::escape($array['define']));
        if (is_false($res)) {
            return "登録に失敗しました。";
        }

        return '';
    }
    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data)
    {
        $array['define'] = $data[0];
        $array['value'] = $data[1];
        $array['title'] = $data[2];
        $array['choice'] = $data[3];
        $array['explain'] = $data[4];

        $error = array();

        $indb = FDB::select1(T_SETTING, "*", "WHERE define = ".FDB::escape($array['define']));
        if (is_void($indb)) {
            $error[] = $line_no."行目:廃止された設定項目はインポートできません。";
        }
        if (is_good($indb['choice']) && !in_array($array['value'], explode(",", $indb['choice']))) {
            $error[] = $line_no."行目:廃止された設定値はインポートできません。";
        }

        return $error;
    }

    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport()
    {

    }
    public function onAfterImport()
    {
        clearSettingCache();
    }
}

class ThisImportDesign extends ImportDesign360
{
    public function getFirstViewMessage()
    {
        return '';
    }
}

$main = new Importer360(new ThisImportModel(), new ThisImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;
