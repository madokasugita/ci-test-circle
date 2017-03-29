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
define('PAGE_TITLE', '回答者選定インポート');
/****************************************************************************************************************************/
class ThisImportModel extends ImportModel360
{

    public function convertUsertype($user_type)
    {
        if($user_type == '削除')

            return 0;
        if(is_numeric($user_type))

            return $user_type;

        if (!is_array($GLOBALS['convertUsertype'])) {
            foreach ($GLOBALS['_360_user_type'] as $k => $v) {
                $types[$k] = replaceMessage($v);
            }
            $GLOBALS['convertUsertype'] = array_flip($types);
        }

        return !is_null($GLOBALS['convertUsertype'][$user_type]) ? $GLOBALS['convertUsertype'][$user_type] : $user_type;
    }
    public function importLine($line_no, $data) //override
    {
        $array['uid_a'] = trim($data[0]);
        $array['uid_b'] = trim($data[1]);
        $array['uid_a'] = $this->name2uid[$array['uid_a']] ? $this->name2uid[$array['uid_a']] : $array['uid_a'];
        $array['uid_b'] = $this->name2uid[$array['uid_b']] ? $this->name2uid[$array['uid_b']] : $array['uid_b'];
        $array['uid_a'] = $this->name_2uid[$array['uid_a']] ? $this->name_2uid[$array['uid_a']] : $array['uid_a'];
        $array['uid_b'] = $this->name_2uid[$array['uid_b']] ? $this->name_2uid[$array['uid_b']] : $array['uid_b'];
        $array['user_type'] = $this->convertUsertype($data[2]);

        $rs = $this->relation_dalete($array);
        if($array['user_type'])
            $rs = $this->relation_insert($array);

        if (is_false($rs))
            return "{$line_no}行目:エラー";
    }

    private function relation_dalete($data)
    {
        return FDB :: delete(T_USER_RELATION, 'where user_type <= '.INPUTER_COUNT.' and uid_a = ' . FDB :: escape($data['uid_a']).' and uid_b = ' . FDB :: escape($data['uid_b']));
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
        $array['user_type'] = $this->convertUsertype($data[2]);

        if (FCheck :: isBlank($array['uid_a']))
            $error[] = "{$line_no}行目:対象者は必須です。";
        elseif(!$this->users[$array['uid_a']])
            $error[] = "{$line_no}行目:指定した対象者は登録されていません。( <b>{$o_uida}</b> )";
        elseif($this->users[$array['uid_a']]=='-1')
            $error[] = "{$line_no}行目:指定した対象者のユーザは非対象者です。( <b>{$o_uida}</b> )";

        if (FCheck :: isBlank($array['uid_b']))
            $error[] = "{$line_no}行目:回答者は必須です。";
        elseif(!$this->users[$array['uid_b']])
            $error[] = "{$line_no}行目:指定した回答者は登録されていません。( <b>{$o_uidb}</b> )";
        if (!FCheck :: isBlank($array['uid_a']) && !FCheck :: isBlank($array['uid_b']) && $array['uid_a']==$array['uid_b'])
            $error[] = "{$line_no}行目:対象者と回答者が同一です。( <b>{$o_uida}</b>, <b>{$o_uidb}</b> )";
        if ($array['user_type'] && !getUserTypeNameById($array['user_type']))
            $error[] = "{$line_no}行目:回答者タイプの値が不正です。( <b>{$o_uidb}</b> )";

        if ($this->user_relation[$array['uid_a']][$array['uid_b']] && $this->user_relation[$array['uid_a']][$array['uid_b']]!=$array['user_type']) {
            $b = $GLOBALS['_360_user_type'][$array['user_type']];
            $a = $GLOBALS['_360_user_type'][$this->user_relation[$array['uid_a']][$array['uid_b']]];
            $error[] = "{$line_no}行目:対象者「<b>{$o_uida}</b>」 => 回答者「<b>{$o_uidb}</b>」 既に<b>{$a}</b>となっていますが、<b>{$b}</b>で上書きしようとしています。";
        }

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
        $this->user_relation = array();

        foreach (FDB::select(T_USER_RELATION,'uid_a,uid_b,user_type','where user_type <= '.INPUTER_COUNT) as $relation) {
            $this->user_relation[$relation['uid_a']][$relation['uid_b']]=$relation['user_type'];
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
        return '<div style="margin-top:30px"><a href="https://s3-ap-southeast-1.amazonaws.com/smart-review/samplecsv/relation.csv">サンプルCSVダウンロード</a></div>';
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
