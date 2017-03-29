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
define('PAGE_TITLE', '組織マスタインポート');
/****************************************************************************************************************************/
    
class DivImportModel extends ImportModel360
{
    public function importLine($line_no, $data)
    {
        //$data[0] = str_pad($data[0], 8, "0", STR_PAD_LEFT);
        //$data[3] = str_pad($data[3], 8, "0", STR_PAD_LEFT);
        //$data[6] = str_pad($data[6], 8, "0", STR_PAD_LEFT);
        $array['div1'] = $data[0];
        $array['div1_name'] = $data[1];
        $array['div1_sort'] = (int) $data[2];
        $array['div2'] = $array['div1'].'_'.$data[3];
        $array['div2_name'] = $data[4];
        $array['div2_sort'] = (int) $data[5];
        $array['div3'] = $array['div2'].'_'.$data[6];
        $array['div3_name'] = $data[7];
        $array['div3_sort'] = (int) $data[8];
        $array['div1_name_1'] = $data[9];
        $array['div2_name_1'] = $data[10];
        $array['div3_name_1'] = $data[11];
        $array['div1_name_2'] = $data[12];
        $array['div2_name_2'] = $data[13];
        $array['div3_name_2'] = $data[14];
        $array['div1_name_3'] = $data[15];
        $array['div2_name_3'] = $data[16];
        $array['div3_name_3'] = $data[17];
        $array['div1_name_4'] = $data[18];
        $array['div2_name_4'] = $data[19];
        $array['div3_name_4'] = $data[20];
        $array = FDB :: escapeArray($array);
        FDB :: insert(T_DIV, $array);

        return '';
    }
    /**
     * @return array インポートファイルの列フォーマットを指定。
     */
    public function getRows()
    {
        return array (
            '####div_name_1####コード',
            '####div_name_1####表示名(日本語)',
            '####div_name_1####並び順',
            '####div_name_2####コード',
            '####div_name_2####表示名(日本語)',
            '####div_name_2####並び順',
            '####div_name_3####コード',
            '####div_name_3####表示名(日本語)',
            '####div_name_3####並び順',
            '####div_name_1####表示名(English)',
            '####div_name_2####表示名(English)',
            '####div_name_3####表示名(English)',
            '####div_name_1####表示名(繁体字)',
            '####div_name_2####表示名(繁体字)',
            '####div_name_3####表示名(繁体字)',
            '####div_name_1####表示名(簡体字)',
            '####div_name_2####表示名(簡体字)',
            '####div_name_3####表示名(簡体字)',
            '####div_name_1####表示名(韓国語)',
            '####div_name_2####表示名(韓国語)',
            '####div_name_3####表示名(韓国語)',
        );
    }

    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport()
    {
        FDB :: delete(T_DIV);
    }

    public function onAfterImport()
    {
        clearDivCache();
        if (!$this->upsertImportFile($_SESSION["muid"], $this->getExecFile())) {
            // エラー発生時
        }
    }

    public function getFormNames()
    {
        return array(
            'last_file' => '前回取り込みファイル',
            'udate' => '更新日時',
            'error_end' => 'エラー検出時の動作',
        );
    }
}

class DivImportDesign extends ImportDesign360
{
    public function getFirstViewMessage()
    {
        return '<div style="margin-top:30px"><a href="https://s3-ap-southeast-1.amazonaws.com/smart-review/samplecsv/div.csv">サンプルCSVダウンロード</a></div>';
    }

    /**
     * フォームを返す
     * 既定値はfile, next,back,submit,error_end
     */
    public function getFormCallback($name, $default=null)
    {
        $model = new ImportModel360();

        switch ($name) {
            case 'file':
                return FForm::file($name,null,null,'style="width:600px;"');
            case 'next':
                return $this->getNextButton($name);
            case 'back':
                return $this->getBackButton($name);
            case 'submit':
                return $this->getSubmitButton($name);
            case 'udate':
                return $model->getUdateImportFile ($model->getExecFile());
            case 'last_file':
                return $model->getLastFileName ($model->getExecFile());
            case 'error_end':
                return FForm::radio($name, 0, 'エラー行を無視して続行').
                    '<br>'.FForm::radio($name, 1, '処理を中断する', 'checked');
            default:
                if(isset($default))

                    return $this->getHidden($name, $default);
                break;
        }
    }

}

$main = new Importer360(new DivImportModel(), new DivImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;


