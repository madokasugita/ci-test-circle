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
define('PAGE_TITLE', '承認者設定インポート');
/****************************************************************************************************************************/
class ThisImportModel extends ImportModel360
{
    public function importLine($line_no, $data) //override
    {

        $array['uid_a'] = trim($data[0]);
        $array['uid_b'] = trim($data[1]);
        $array['uid_a'] = $this->name2uid[$array['uid_a']] ? $this->name2uid[$array['uid_a']] : $array['uid_a'];
        $array['uid_b'] = $this->name2uid[$array['uid_b']] ? $this->name2uid[$array['uid_b']] : $array['uid_b'];
        $array['uid_a'] = $this->name_2uid[$array['uid_a']] ? $this->name_2uid[$array['uid_a']] : $array['uid_a'];
        $array['uid_b'] = $this->name_2uid[$array['uid_b']] ? $this->name_2uid[$array['uid_b']] : $array['uid_b'];
        $array['user_type'] = ADMIT_USER_TYPE;
        $rs = $this->relation_dalete($array);
        if (is_false($rs))
            return "{$line_no}行目:エラー";
        $rs = $this->relation_insert($array);

        if (is_false($rs))
            return "{$line_no}行目:エラー";
    }

    private function relation_dalete($data)
    {
        return FDB :: delete(T_USER_RELATION, 'where user_type = '.ADMIT_USER_TYPE.' and uid_a = ' . FDB :: escape($data['uid_a']));
    }

    private function relation_insert($data)
    {
        $data = FDB :: escapeArray($data);

        return FDB :: insert(T_USER_RELATION, $data);
    }

    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data) //override
    {
        global $GLOBAL_DIV1_LIST, $GLOBAL_DIV2_LIST, $GLOBAL_DIV3_LIST;
        $error = array ();

        $o_uida = html_escape($data[0]);
        $o_uidb = html_escape($data[1]);

        $array['uid_a'] = trim($data[0]);
        $array['uid_b'] = trim($data[1]);
        $array['uid_a'] = $this->name2uid[$array['uid_a']] ? $this->name2uid[$array['uid_a']] : $array['uid_a'];
        $array['uid_b'] = $this->name2uid[$array['uid_b']] ? $this->name2uid[$array['uid_b']] : $array['uid_b'];
        $array['uid_a'] = $this->name_2uid[$array['uid_a']] ? $this->name_2uid[$array['uid_a']] : $array['uid_a'];
        $array['uid_b'] = $this->name_2uid[$array['uid_b']] ? $this->name_2uid[$array['uid_b']] : $array['uid_b'];

        //$array['user_type'] = $data[2];

        if (FCheck :: isBlank($array['uid_a']))
            $error[] = "{$line_no}行目:対象者は必須です。";
        elseif(!$this->users[$array['uid_a']])
            $error[] = "{$line_no}行目:指定した対象者は登録されていません。( <b>{$o_uida}</b> )";
        elseif($this->users[$array['uid_a']]=='-1')
            $error[] = "{$line_no}行目:指定した対象者の対象者フラグが0です。( <b>{$o_uida}</b> )";

        if (FCheck :: isBlank($array['uid_b']))
            $error[] = "{$line_no}行目:承認者は必須です。".$array['uid_b'];
        elseif(!$this->users[$array['uid_b']])
            $error[] = "{$line_no}行目:指定した承認者は登録されていません。( <b>{$o_uidb}</b> )";

        if (!FCheck :: isBlank($array['uid_a']) && !FCheck :: isBlank($array['uid_b']) && $array['uid_a']==$array['uid_b'])
            $error[] = "{$line_no}行目:対象者と承認者が同一です。( <b>{$o_uida}</b>, <b>{$o_uidb}</b> )";

        return $error;
    }
    /**
     * エラーチェック処理直前、ファイルアップロード処理後に呼び出されるメソッド
     */
    public function onBeforeImport()//override
    {

        $users = FDB::select(T_USER_MST,'uid,mflag,name,name_');

        $this->users = array();
        foreach ($users as $user) {
            $this->name2uid[trim($user['name'])] = $user['uid'];
            $this->name_2uid[trim($user['name_'])] = $user['uid'];
            if($user['mflag'])
                $this->users[$user['uid']] = 1;
            else
                $this->users[$user['uid']] = -1;
        }
    }
    /**
     * エラーチェック処理直前、ファイルアップロード処理後に呼び出されるメソッド
     */
    public function onBeforeErrorCheck()//override
    {

        $users = FDB::select(T_USER_MST,'uid,mflag,name,name_');

        $this->users = array();
        foreach ($users as $user) {
            $this->name2uid[trim($user['name'])] = $user['uid'];
            $this->name_2uid[trim($user['name_'])] = $user['uid'];
            if($user['mflag'])
                $this->users[$user['uid']] = 1;
            else
                $this->users[$user['uid']] = -1;
        }
    }
    public function onAfterImport()
    {
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

class ThisImportDesign extends ImportDesign360
{
    public function getFirstViewMessage()
    {
        return '<div style="margin-top:30px"><a href="https://s3-ap-southeast-1.amazonaws.com/smart-review/samplecsv/admit.csv">サンプルCSVダウンロード</a></div>';
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
