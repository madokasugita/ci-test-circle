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
define('PAGE_TITLE', '管理者マスタインポート');
/****************************************************************************************************************************/

function getPermittedByType($type)
{
    global $GLOBAL_getPermittedByType;
    if (!is_array($GLOBAL_getPermittedByType)) {
        $array = array();
        $array[0] = 'null';
        $array[1] = 'crm_enq3.php,360_enq_search.php,enq_search_csv.php,360_user_search.php,360_user_edit.php,360_user_pw_search.php,360_user_pw_edit.php,360_user_relation_search.php,360_user_relation_view.php,360_user_relation_edit.php';
        $array[2] = 'crm_enq3.php,360_enq_search.php,enq_search_csv.php,DLspecial.php,enq_getChoice.php,360_export_result_total.php,360_user_search.php,360_user_edit.php,360_user_pw_search.php,360_user_pw_edit.php,360_user_relation_search.php,360_user_relation_view.php,360_user_relation_edit.php';
        $array[3] = 'crm_enq3.php,360_enq_search.php,enq_search_csv.php,DLspecial.php,enq_getChoice.php,360_export_result_total.php,360_user_import.php,360_user_search.php,360_user_edit.php,360_user_pw_search.php,360_user_pw_edit.php,360_relation_import.php,360_user_relation_search.php,360_user_relation_view.php,360_user_relation_edit.php,360_div_search.php';
    //	$array[4] = '360_user_import.php,360_user_search.php,360_user_edit.php,360_relation_import.php,360_user_relation_search.php,360_user_relation_view.php,360_user_relation_edit.php';
    }

    return $array[$type];
}

class ThisImportModel extends ImportModel360
{
    public function importLine($line_no, $data) //override
    {
        $array['id'] = $data[0];
        $array['divs'] = $data[1];
        $array['name'] = $data[2];
        $type = $data[3];
        $array['permitted'] = getPermittedByType($type);
        $array['email'] = trim($data[4]);

        if ($this->musers[$array['id']])
            $rs = $this->muser_update($array);
        else
            $rs = $this->muser_insert($array);

        if (is_false($rs))
            return "{$line_no}行目:エラー";
    }

    private function muser_update($data)
    {
        $muid = $this->musers[$data['id']];
        $data = FDB :: escapeArray($data);

        return FDB :: update(T_MUSR, $data, 'where muid = ' . FDB :: escape($muid));
    }

    private function muser_insert($data)
    {
        //$data['muid'] = FDB::getNextVal("muid");
        $data['pw'] = getPwHash(get360RandomPw());
        $this->musers[$data['id']] = $data['muid'];
        $data = FDB :: escapeArray($data);

        return FDB :: insert(T_MUSR, $data);
    }
    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data) //override
    {
        global $GLOBAL_DIV1_LIST, $GLOBAL_DIV2_LIST, $GLOBAL_DIV3_LIST;
        $array['id'] = $data[0];
        $array['divs'] = $data[1];
        $array['name'] = $data[2];
        $type = $data[3];
        $array['email'] = trim($data[4]);

        if($array['email'] && !FCheck :: isEmail($array['email']))
            $error[] = "{$line_no}行目:メールアドレスの書式がおかしいです。";

        if (FCheck :: isBlank($array['id']))
            $error[] = "{$line_no}行目:IDは必須です。";
        elseif (!ereg('^[0-9a-zA-Z]+$', $array['id']))
            $error[] = "{$line_no}行目:IDに使用できる文字は、半角英数字のみです。( <b>{$array['id']}</b> )";

        if (!getPermittedByType($type))
            $error[] = "{$line_no}行目:管理者タイプの値が不正です。( <b>{$type}</b> )";

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

    }
}

class ThisImportDesign extends ImportDesign360
{
    public function getFirstViewMessage()
    {
        return '<div style="margin-top:30px"><a href="https://s3-ap-southeast-1.amazonaws.com/smart-review/samplecsv/muser.csv">サンプルCSVダウンロード</a></div>';
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
