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
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
require_once (DIR_LIB . '360_Importer.php');
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', '所属別権限インポート');
/****************************************************************************************************************************/

class ThisImportModel extends ImportModel360
{
    public function importLine($line_no, $data) //override
    {
        $array['muid'] = $this->musers[$data[0]];
        $array['div1'] = $data[1];
        $array['div2'] = $data[2] ? $data[1].'_'.$data[2] : '*';
        $array['div3'] = $data[3] ? $data[1].'_'.$data[2].'_'.$data[3] : '*';
        $delete_flag = $data[4];

        $rs = $this->muser_delete($array);
        if (!$delete_flag)
            $rs = $this->muser_insert($array);

        if (is_false($rs))
            return "{$line_no}行目:エラー";
    }

    private function muser_delete($data)
    {
        return FDB :: delete(T_AUTH_SET_DIV, 'where muid = ' . FDB :: escape($data['muid']).' and div1 = ' . FDB :: escape($data['div1']).' and div2 = ' . FDB :: escape($data['div2']).' and div3 = ' . FDB :: escape($data['div3']));
    }

    private function muser_insert($data)
    {
        $data = FDB :: escapeArray($data);

        return FDB :: insert(T_AUTH_SET_DIV, $data);
    }
    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data) //override
    {
        global $GLOBAL_DIV1_LIST, $GLOBAL_DIV2_LIST, $GLOBAL_DIV3_LIST;
        $array['id'] = $data[0];
        $array['div1'] = $data[1];
        $array['div2'] = $data[2] ? $array['div1'].'_'.$data[2] : '*';
        $array['div3'] = $data[3] ? $array['div2'].'_'.$data[3] : '*';
        $delete_flag = $data[4];

        if (FCheck :: isBlank($array['id']))
            $error[] = "{$line_no}行目:IDは必須です。";
        elseif(!$this->musers[$array['id']])
            $error[] = "{$line_no}行目:指定したIDは登録されていません。( <b>{$array['id']}</b> )";

        if ($array['div1'] != '*' && !getDiv1NameById($array['div1']))
            $error[] = "{$line_no}行目:存在しない所属コード(大)です。( <b>{$data[1]}</b> )";

        if ($array['div2'] != '*' && !getDiv2NameById($array['div2']) && getDiv2NameById($array['div2'])!=="")
            $error[] = "{$line_no}行目:存在しない所属コード(中)です。( <b>{$data[2]}</b> )";

        if ($array['div3'] != '*' && !getDiv3NameById($array['div3']) && getDiv2NameById($array['div3'])!=="")
            $error[] = "{$line_no}行目:存在しない所属コード(小)です。( <b>{$data[3]}</b> )";

        if($delete_flag && $delete_flag != '1')
            $error[] = "{$line_no}行目:削除フラグが不正な値です。( <b>{$delete_flag}</b> )";

        return $error;
    }
    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport() //override
    {
        // id->muid　のテーブルを作成しておく
        $array = FDB :: select(T_MUSR, 'id,muid');
        $this->musers = array ();
        foreach ($array as $data) {
            $this->musers[$data['id']] = $data['muid'];
        }
    }
    public function onAfterImport()
    {
        clearMessageCache();
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

    /**
     * エラーチェック処理直前、ファイルアップロード処理後に呼び出されるメソッド
     */
    public function onBeforeErrorCheck()//override
    {
        // id->muid　のテーブルを作成しておく
        $array = FDB :: select(T_MUSR, 'id,muid');
        $this->musers = array ();
        foreach ($array as $data) {
            $this->musers[$data['id']] = $data['muid'];
        }
    }
}

class ThisImportDesign extends ImportDesign360
{
    public function getFirstViewMessage()
    {
        return '<div style="margin-top:30px"><a href="https://s3-ap-southeast-1.amazonaws.com/smart-review/samplecsv/muser_div.csv">サンプルCSVダウンロード</a></div>';
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

$main = new Importer360(new ThisImportModel(), new ThisImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;
