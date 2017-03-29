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
define('PAGE_TITLE', 'ユーザマスタインポート');
/****************************************************************************************************************************/
class UserImportModel extends ImportModel360
{
    private $duplicate_column = array('email'=>'Eメール','name'=>'名前');//重複チェック設定
    public function importLine($line_no, $data) //override
    {

        $array['uid'] = $data[0];
        $array['mflag'] = (trim($data[1])=="")? "0":$data[1];
        $array['sheet_type'] = (trim($data[2])=="")? 0:(int) $data[2];
        $array['name'] = $data[3];
        $array['name_'] = $data[4];
        $array['div1'] = $data[5];
        $array['div2'] = $data[6];
        $array['div3'] = $data[7];
        $array_ = $array;
        $array['div1'] = md5($array['div1']);
        $array['div2'] = $array['div1'].'_'.md5($array['div2']);
        $array['div3'] = $array['div2'].'_'.md5($array['div3']);
        if (!$this->div3_code[$array['div3']]) {
            $d = array();
            $d['div1_name'] = $array_['div1'];
            $d['div1'] = $array['div1'];
            $d['div2_name'] = $array_['div2'];
            $d['div2'] = $array['div2'];
            $d['div3_name'] = $array_['div3'];
            $d['div3'] = $array['div3'];
            FDB::insert(T_DIV,FDB::escapeArray($d));
            $this->div3_code[$array['div3']] = true;
        }
        $array['email'] = $data[8];
        $array['class'] = $data[9];
        $array['lang_flag'] = (int) $data[10];
        $array['lang_type'] = (int) $data[11];

        $array['memo'] = $data[12];

        for($i=1; $i<=10; $i++)
            $array['ext'.$i] = $data[12+$i];

        $array['test_flag'] = (int) $data[23];

        if(!$array['mflag'])
            $array['sheet_type'] = 0;
        if ($this->users[$array['uid']])
            $rs = $this->user_update($array);
        else
            $rs = $this->user_insert($array);

        if (is_false($rs))
            return "{$line_no}行目:エラー";
    }
    public function getFormNames()
    {
        return array(
            'last_file' => '前回取り込みファイル',
            'udate' => '更新日時',
            'error_end' => 'エラー検出時の動作',
            'checkemail' => 'メールアドレス重複チェック',
            'checkemail_blank' => 'メールアドレス空白',
        );
    }
    private function user_update($data)
    {
        $serial_no = $this->users[$data['uid']];
        $data = FDB :: escapeArray($data);

        return FDB :: update(T_USER_MST, $data, 'where serial_no = ' . FDB :: escape($serial_no));
    }

    private function user_insert($data)
    {
        $data['serial_no'] = getUniqueIdWithTable(T_UNIQUE_SERIAL, "serial_no", 8);
        $data['pw'] = getPwHash(get360RandomPw());
        if (!$data['uid']) {
            $res = FDB::select(T_USER_MST, 'uid');
            if ($res) {
                $uids = array();
                foreach ($res as $k => $v) {
                    $uids[$v['uid']] = 1;
                }

                $overflow = 0;
                do {
                    if (1000000 < $overflow++) {
                        echo 'overflow error';
                        exit;
                    }
                    $data['uid'] = get360RandomPw();
                } while (isset($uids[$data['uid']]));
            }
        }
        $this->users[$data['uid']] = $data['serial_no'];
        $data = FDB :: escapeArray($data);

        return FDB :: insert(T_USER_MST, $data);
    }

    private $emailAndName;	//e-mailとnameの配列

    public function getEmailAndNameArray()
    {
        return FDB :: select('usr', 'email,name,uid');
    }

    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data) //override
    {
        global $GLOBAL_DIV1_LIST, $GLOBAL_DIV2_LIST, $GLOBAL_DIV3_LIST;
        $error = array ();
        $array['uid'] = $data[0];
        $array['mflag'] = (trim($data[1])=="")? "0":$data[1];
        $array['sheet_type'] = (trim($data[2])=="")? 0:(int) $data[2];
        $array['name'] = $data[3];
        $array['name_'] = $data[4];

        $array['email'] = $data[8];
        $array['class'] = $data[9];
        $array['lang_flag'] = (int) $data[10];
        $array['lang_type'] = (int) $data[11];

        $array['memo'] = $data[12];

        for($i=1; $i<=10; $i++)
            $array['ext'.$i] = $data[12+$i];

        $array['test_flag'] = (int) $data[23];

        //追加/更新/削除 の数をカウント
        $this->countFlag = true;
        $user = false;
        if (!$this->users[$array['uid']]) {//新規だったら
            $this->countI++;
        } else {
            $keys = array_keys($array);
            $user = FDB::select1(T_USER_MST,implode(',',$keys),'where uid = '.FDB::escape($array['uid']));
            if ($user == $array) {
                $this->countN++;
            } else {
                $this->countU++;
            }
        }

        if ($this->post['checkemail']) {
            $duplicate_flg = false;
            //新しい行の場合DBからemailArrayを取得
            if ($line_no == 2) {
                $this->emailAndName = $this->getEmailAndNameArray ();
            }

            //email重複チェック
            foreach ($this->emailAndName as $v) {
                if ($v['email'] == $array['email'] && $v['uid'] != $array['uid']) {
                    $duplicate_column = $this->duplicate_column;
                    $error[] = "{$line_no}行目:Emailが重複しています。"
                                ."( {$duplicate_column['email']}:{$array['email']}"
                                ."{$duplicate_column['name']}:{$array['name']} )";
                    $duplicate_flg = true;
                    break;
                }
            }

            //重複していなければ、読み込んだ1行をemailArrayに追加
            if (!$duplicate_flg)
                $this->emailAndName[] = array('email' => $array['email'], 'name' => $array['name']);
        }
        if (FCheck :: isBlank($array['uid'])) {
//			$error[] = "{$line_no}行目:ユーザIDは必須です。";
        } elseif (!ereg(EREG_LOGIN_ID, $array['uid'])) $error[] = "{$line_no}行目:ユーザIDの書式が正しくありません。( <b>{$array['uid']}</b> )";

        if ($array['mflag'] && !is_numeric($array['mflag']))
            $error[] = "{$line_no}行目:対象者フラグの値が不正です。( <b>{$array['mflag']}</b> )";
        elseif ($array['mflag'] && !$array['sheet_type']) $error[] = "{$line_no}行目:対象者の場合、シートタイプの設定は必須です。";

        if ($array['sheet_type'] && !getSheetTypeNameById($array['sheet_type']))
            $error[] = "{$line_no}行目:シートタイプの値が不正です。( <b>{$array['sheet_type']}</b> )";

        if (FCheck :: isBlank($array['name']))
            $error[] = "{$line_no}行目:名前は必須です。";

    /*	if (!getDiv1NameById($array['div1']))
            $error[] = "{$line_no}行目:存在しない所属コード(大)です。( <b>{$array['div1']}</b> )";
        elseif (!$GLOBAL_DIV1_LIST[$array['div1']])
            $error[] = "{$line_no}行目:権限範囲外の所属コード(大)です。( <b>{$array['div1']}</b> )";
    */
        if ("1"==$this->post['checkemail_blank'] && is_void($array['email'])) {
            $error[] = "{$line_no}行目:Emailは必須です。";
        }
        if (is_good($array['email']) && !FCheck :: isEmail($array['email']))
            $error[] = "{$line_no}行目:Emailの書式が不正です。( <xmp>{$array['email']}</xmp> )";

        // 人数制限がない場合
        if (!$GLOBALS['Setting']->limitUserNumberValid())
            return $error;

        // 非対象者にする場合
        if($user && is_zero($array['mflag']) && FDB::is_exist(T_EVENT_DATA, 'where target = ' . FDB :: escape($this->users[$array['uid']])))
            $error[] = "{$line_no}行目:回答済ユーザのため対象者フラグを変更できません。";

        if (!is_zero($array['test_flag']) && !is_zero($array['mflag']))
            $error[] = "{$line_no}行目:対象者はテストユーザに設定できません。";

        // 対象者フラグの変更前の状態取得
        $before_mflag = ($user)? $user['mflag'] : 0;

        // 対象者フラグを対象者にする場合
        if(!is_zero($array['mflag']) && is_zero($before_mflag))
            $this->mflagCount++;

        // 対象者フラグを評価者にする場合
        if(is_zero($array['mflag']) && !is_zero($before_mflag))
            $this->mflagCount--;

        return $error;
    }

    public function getTotalErrors() //override
    {
        $error = array ();
        if (!$GLOBALS['Setting']->limitUserNumberValid())
            return $error;

        if(getTargetUserCount() + $this->mflagCount > LIMIT_USER_NUMBER)
            $error[] = '登録制限数を超えています。';

        return $error;
    }

    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport() //override
    {
        // uid->serial_no　のテーブルを作成しておく
        $array = FDB :: select(T_USER_MST, 'uid,serial_no');
        $this->users = array ();
        foreach ($array as $data) {
            $this->users[$data['uid']] = $data['serial_no'];
        }
        $this->div3_code = array();
        foreach (FDB :: select(T_DIV) as $div) {
            $this->div3_code[$div['div3']] = true;
        }

    }

    /**
     * エラーチェック処理直前、ファイルアップロード処理後に呼び出されるメソッド
     */
    public function onBeforeErrorCheck()//override
    {
        getDivList();//所属コードをチェックするために、globalで所属リストを読み込んでおく。

        $this->div1_code = array();
        $this->div2_code = array();
        $this->div3_code = array();
        foreach (FDB :: select(T_DIV) as $div) {
            $this->div1_code[$div['div1_name']] = $div['div1'];
            $this->div2_code[$div['div2_name']] = $div['div2'];
            $this->div3_code[$div['div3_name']] = $div['div3'];
        }
        $array = FDB :: select(T_USER_MST, 'uid,serial_no');
        $this->users = array ();
        foreach ($array as $data) {
            $this->users[$data['uid']] = $data['serial_no'];
        }
    }

    public function onAfterImport()
    {
        clearDivCache();
        if (!$this->upsertImportFile($_SESSION["muid"], $this->getExecFile())) {
            // エラー発生時
        }
    }
}

class UserImportDesign extends ImportDesign360
{
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
            case 'udate':
                return $model->getUdateImportFile ($model->getExecFile());
            case 'last_file':
                return $model->getLastFileName ($model->getExecFile());
            case 'submit':
                return $this->getSubmitButton($name);
            case 'error_end':
                return FForm::radio($name, 0, 'エラー行を無視して続行').
                    '<br>'.FForm::radio($name, 1, '処理を中断する', 'checked');
            case 'checkemail':
                return FForm::radio($name, 0, 'チェックしない').
                    '<br>'.FForm::radio($name, 1, 'チェックする', 'checked');
            case 'checkemail_blank':
                return FForm::radio($name, 0, '許可する').
                    '<br>'.FForm::radio($name, 1, 'エラーとして扱う', 'checked');
            default:
                if(isset($default))

                    return $this->getHidden($name, $default);
                break;
        }
    }
    public function getFirstViewMessage()
    {
        return '<div style="margin-top:30px"><a href="https://s3-ap-southeast-1.amazonaws.com/smart-review/2.1/samplecsv/user.csv">サンプルCSVダウンロード</a></div>';
    }

}

$main = new Importer360(new UserImportModel(), new UserImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;
