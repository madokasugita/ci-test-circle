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
require_once 'Excel/reader.php';

session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', 'ユーザマスタインポート(Excel)');
/****************************************************************************************************************************/

$GLOBALS['transdata']['日本語'] = '0';
$GLOBALS['transdata']['繁体字中国語'] = '2';
$GLOBALS['transdata']['簡体字中国語'] = '3';
$GLOBALS['transdata']['韓国語'] = '4';
$GLOBALS['transdata']['㈱パーソンズ'] = '102';
$GLOBALS['transdata']['㈱ベネッセビジネスメイト'] = '103';
$GLOBALS['transdata']['㈱プランディット'] = '104';
$GLOBALS['transdata']['㈱ラーンズ'] = '105';
$GLOBALS['transdata']['㈱ベネッセ・ベースコム'] = '106';
$GLOBALS['transdata']['㈱お茶の水ゼミナール'] = '107';
$GLOBALS['transdata']['㈱ベネッセアンファミーユ'] = '108';
$GLOBALS['transdata']['ベルリッツ・ジャパン㈱'] = '109';
$GLOBALS['transdata']['Berlitz International, Inc.'] = '110';
$GLOBALS['transdata']['㈱テレマーケティングジャパン'] = '111';
$GLOBALS['transdata']['㈱ベネッセコーポレーション'] = '205';
$GLOBALS['transdata']['上司(上位者)'] = '1';
$GLOBALS['transdata']['部下(下位者)'] = '2';
$GLOBALS['transdata']['同僚(その他)'] = '3';
$GLOBALS['transdata']['中国事業部'] = '201';
$GLOBALS['transdata']['台北支社'] = '202';
$GLOBALS['transdata']['ベネッセコリア'] = '203';
$GLOBALS['transdata']['ベネッセ香港・深圳'] = '204';
$GLOBALS['transdata']['ベネッセコーポレーション'] = '205';
$GLOBALS['transdata']['日文'] = '0';
$GLOBALS['transdata']['繁体中文'] = '2';
$GLOBALS['transdata']['简体中文'] = '3';
$GLOBALS['transdata']['韩文'] = '4';
$GLOBALS['transdata']['中国事业部'] = '201';
$GLOBALS['transdata']['台北支社'] = '202';
$GLOBALS['transdata']['倍乐生韩国'] = '203';
$GLOBALS['transdata']['倍乐生香港・深圳'] = '204';
$GLOBALS['transdata']['倍乐生(日本)'] = '205';
$GLOBALS['transdata']['上司(职位高者)'] = '1';
$GLOBALS['transdata']['部下(职位低者)'] = '2';
$GLOBALS['transdata']['同事(其他)'] = '3';
$GLOBALS['transdata']['日文'] = '0';
$GLOBALS['transdata']['繁體中文 　'] = '2';
$GLOBALS['transdata']['簡體中文'] = '3';
$GLOBALS['transdata']['韓文'] = '4';
$GLOBALS['transdata']['中國事業部'] = '201';
$GLOBALS['transdata']['台北支社'] = '202';
$GLOBALS['transdata']['倍樂生韓國'] = '203';
$GLOBALS['transdata']['倍樂生香港・深圳'] = '204';
$GLOBALS['transdata']['倍樂生(日本)'] = '205';
$GLOBALS['transdata']['上司(職位高者)'] = '1';
$GLOBALS['transdata']['部下(職位低者)'] = '2';
$GLOBALS['transdata']['同事(其他)'] = '3';
$GLOBALS['transdata']['일본어'] = '0';
$GLOBALS['transdata']['중국어 번체'] = '2';
$GLOBALS['transdata']['중국어 간체'] = '3';
$GLOBALS['transdata']['한국어'] = '4';
$GLOBALS['transdata']['중국사업부'] = '201';
$GLOBALS['transdata']['대만지사'] = '202';
$GLOBALS['transdata']['베네세 코리아'] = '203';
$GLOBALS['transdata']['베네세 홍콩・심천'] = '204';
$GLOBALS['transdata']['Benesse Corporation(JAPAN)'] = '205';
$GLOBALS['transdata']['상사(상급자)'] = '1';
$GLOBALS['transdata']['부하(하급자)'] = '2';
$GLOBALS['transdata']['동료(기타)'] = '3';

class UserImportModel extends ImportModel360
{
    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($i, $data) //override
    {
        $email2name = array();
        $error = array ();
        if ($data[67][10] != "j67" || $data[67][2] != "b67" || $data[66][11] != "k66" || $data[37][11] != "k37" || $data[31][9] != "i31") {
            $error[] = $i . "シート目:フォーマットが不正です。";

            return $error;
        }
        if ($data[31][2] == "")
            $error[] = $i . "シート目:対象者の氏名は必須です。";

        if ($data[31][2] || $data[31][3]) {
            $name = 	'('.$data[31][2].' '.$data[31][3].')';
        } else {
            $name = '';
        }

        if ($data[31][5-1] == "")
            $error[] = $i . "シート目{$name}:対象者の所属は必須です。";
        if ($data[31][6] == "")
            $error[] = $i . "シート目{$name}:対象者のメールアドレスは必須です。";
        elseif (!FCheck :: isEmail($data[31][6])) $error[] = $i . "シート目:対象者のメールアドレスの書式が不正です。";
        if ($data[31][8-1] === null && $data[1][1] != '360度サーベイ　回答者選定書(出向者）')
            $error[] = $i . "シート目{$name}:対象者の使用言語は必須です。";

        $targetname = $email2name[$data[31][6]] = $data[31][2].'('.$data[31][3].')';

        $p = 0;
        for ($row = 37; $row <= 66; $row++) {
            $p++;
            if ($data[$row][2] == "" && $data[$row][5] == "" && $data[$row][6] == "" && $data[$row][8] === null)
                continue;
            if ($data[$row][2] == "")
                $error[] = $i . "シート目{$name} : 回答者[{$p}]の氏名は必須です。";
            if ($data[$row][5-1] == "")
                $error[] = $i . "シート目{$name} : 回答者[{$p}]の所属は必須です。";
            if ($data[$row][6] == "")
                $error[] = $i . "シート目{$name} : 回答者[{$p}]のメールアドレスは必須です。";
            if ($data[$row][10-1] === null)
                $error[] = $i . "シート目{$name} : 回答者[{$p}]の区分は必須です。";

            elseif (!FCheck :: isEmail($data[$row][6]))
                $error[] = $i . "シート目{$name} : 回答者[{$p}]のメールアドレスの書式が不正です。";
            elseif($data[31][6] == $data[$row][6])
                $error[] = $i . "シート目{$name} : 回答者[{$p}]のメールアドレスが対象者のアドレスと同じです。";
            elseif($email2name[$data[$row][6]])
                $error[] = $i . "シート目{$name}:{$data[$row][2]}({$data[$row][3]}) と {$email2name[$data[$row][6]]}のメールアドレスが同じです。";
            if ($data[$row][8-1] === null && $data[1][1] != '360度サーベイ　回答者選定書(出向者）')
                $error[] = $i . "シート目:回答者[{$p}]の使用言語は必須です。";

            $email2name[$data[$row][6]] = $data[$row][2].'('.$data[$row][3].')';
        }

        return $error;
    }
    public function importLine($line_no, $data) //override
    {
        $array = array ();
        $array['name'] = $data[31][2];
        $array['name_'] = $data[31][3];
        $array['div1'] = $GLOBALS['transdata'][$data[31][5-1]];
        $array['email'] = $data[31][6];
        $array['lang_type'] = (int) $GLOBALS['transdata'][$data[31][8-1]];
        $array['lang_flag'] = 1;
        $array['memo'] = 'excel';
        $array['mflag'] = 1;
        $array['sheet_type'] = 3;
        $array['select_status'] = 2;

        if ($this->users[$array['email']])
            $rs = $this->user_update($array);
        else
            $rs = $this->user_insert($array);
        $uid_a = $this->users[$array['email']];

        FDB :: delete(T_USER_RELATION, 'where user_type in(1,2,3) and uid_a = ' . FDB :: escape($uid_a));
        $p = 0;
        for ($row = 37; $row <= 66; $row++) {
            $array = array ();
            $p++;
            if ($data[$row][2] == "" && $data[$row][5] == "" && $data[$row][6] == "" && $data[$row][8] === null)
                continue;
            $array['name'] = $data[$row][2];
            $array['name_'] = $data[$row][3];
            $array['div1'] = $GLOBALS['transdata'][$data[$row][5-1]];
            $array['email'] = $data[$row][6];
            $array['lang_type'] = (int) $GLOBALS['transdata'][$data[$row][8-1]];
            $array['lang_flag'] = 1;
            $array['memo'] = 'excel';
            $array['mflag'] = 0;
            $array['sheet_type'] = 0;
            if (!$this->users[$array['email']])
                $rs = $this->user_insert($array);
            $uid_b = $this->users[$array['email']];
            $array = array ();
            $array['uid_a'] = $uid_a;
            $array['uid_b'] = $uid_b;
            $array['user_type'] = $GLOBALS['transdata'][$data[$row][10-1]];
            $rs = FDB :: insert(T_USER_RELATION, FDB :: escapeArray($array));
        }
        if (is_false($rs))
            return "{$line_no}シート目:エラー";
    }

    private function user_update($data)
    {
        $data = FDB :: escapeArray($data);

        return FDB :: update(T_USER_MST, $data, 'where memo = ' . FDB :: escape("excel") . ' and email = ' . $data['email']);
    }

    private function user_insert($data)
    {
        $data['serial_no'] = getUniqueIdWithTable(T_UNIQUE_SERIAL, "serial_no", 8);
        $data['uid'] = getUniqueIdWithTable_UID(T_UNIQUE_UID, "uid");
        $data['pw'] = get360RandomPw();
        $this->users[$data['email']] = $data['uid'];
        $data = FDB :: escapeArray($data);

        return FDB :: insert(T_USER_MST, $data);
    }
    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport() //override
    {
        // uid->serial_no　のテーブルを作成しておく
        $array = FDB :: select(T_USER_MST, 'email,uid');
        $this->users = array ();
        foreach ($array as $data) {
            $this->users[$data['email']] = $data['uid'];
        }
    }

    /**
     * エラーチェック処理直前、ファイルアップロード処理後に呼び出されるメソッド
     */
    public function onBeforeErrorCheck() //override
    {
        getDivList(); //所属コードをチェックするために、globalで所属リストを読み込んでおく。
    }
}

class UserImportDesign extends ImportDesign360
{
    /**
     * @param string $hidden     hiddenの値。formタグ内のどこかに含めてください
     * @param array  $forms      フォームセット。backとsubmitのみ
     * @param array  $values     表示用の値セット。model依存
     * @param int    $line_count 取り込み可能な行数
     * @param array  $errors     エラーが発生していれば、その内容の配列
     */
    public function getConfirmView($hidden, $forms, $values, $line_count, $errors = array ())
    {
        $action = $this->getAction();
        $html = $this->getFormArea(<<<HTML
{$hidden}
{$line_count}シートをインポートしますか？<br><br>
{$forms['back']}
{$forms['submit']}
</form>
HTML
);
        if (0 < count($errors)) {
            $html .=<<<HTML
<br><br>
以下のエラーがありました。<br>
このままインポートしますと、正常なシートのみ取り込まれます。
<br><br>
HTML;
            $html .= $this->getErrorShow($errors);
        }

        return $html;
    }
    /**
     * フォームを返す
     * 既定値はfile, next,back,submit,error_end
     */
    public function getFormCallback($name, $default=null)
    {
        switch ($name) {
            case 'file':
                return FForm::file($name,null,null,'style="width:220px;"');
            case 'next':
                return $this->getNextButton($name);
            case 'back':
                return $this->getBackButton($name);
            case 'submit':
                return $this->getSubmitButton($name);
            case 'error_end':
                return FForm::radio($name, 0, 'エラーのあるシートを無視して続行').
                    '<br>'.FForm::radio($name, 1, '処理を中断する', 'checked');
            default:
                if(isset($default))

                    return $this->getHidden($name, $default);
                break;
        }
    }
    /**
     * @return string submit処理開始前に表示されるhtmlを返す
     */
    public function getSubmitBeforeProgressView()
    {
        $html = <<<__HTML__
<div style="width:500px;color:#aaaaaa;text-align:left;margin:20px;">
情報をデータベースに登録しています。<br>
しばらくお待ちください
</div>

<div style="width:500px;text-align:left;margin:20px;">
(1) ファイル内のシート数分のデータを処理しています。
</div>
__HTML__;

        return $html;
    }
}

class ThisImporter extends Importer360
{
    /**
    * @return string 二番目の画面でエラーチェック処理を行い、結果のhtmlを取得
    */
    public function confirm($post)
    {
        //アップロードエラーのチェックを行う
        $errors = $this->design->getUploadErrorMessage($_FILES['file']['error']);
        if ($errors) {
            return $this->showError(array (
                $errors
            ), $post);
        }
        $temp_id = temp_rename($_FILES['file']['tmp_name']);
        $fp = fopen(temp_file_path($temp_id), 'r');

        $excel = new Spreadsheet_Excel_Reader();
        $excel->setUTFEncoder('mb');
        $excel->setOutputEncoding('UTF-8');
        $excel->read(temp_file_path($temp_id));

        $errors = array ();
        $ok_count = 0;
        $max_error = $this->model->getMaxErrorCount();
        $total = 0;
        $i = 0;
        $sheet_num = 0;
        while ($sheet = $excel->sheets[$sheet_num]) {
            $data = $sheet['cells'];
            $total++;
            $i++;
            $error = $this->model->getErrors($i, $data);

            if (0 < count($error)) {
                $errors = array_merge($errors, $error);
                $ng_line[] = $i;
            } else {
                $ok_count++;
            }
            $sheet_num++;
        }
        /******************************/

        /** 設定によっては一件でもエラーがあれば終了 */
        if (!$this->model->isPassableError() && 0 < count($errors)) {
            return $this->showError($errors, $post);
        }
        $ngcode = implode(':', $ng_line);
        $hash = $this->getHash($ngcode, $temp_id);
        $hidden = $this->design->getHidden('ng_line', $ngcode) .
        $this->design->getHidden('import_id', $hash) .
        $this->design->getHidden('total', $total) .
        $this->design->getHidden('import', 'submit') .
        $this->design->getHidden('temp_id', $temp_id) .
        $this->getHiddenSID() .
        $this->getHiddenFromPost($post);

        $forms = array (
            'back',
            'submit'
        );

        return $this->design->getConfirmView($hidden, $this->design->getForms($forms), $this->design->getFormatValues($this->model->getFormKeys(), $post), $ok_count, $errors);
    }
    /**
     * useProgressbar有効の際は、このメソッドから結果を出力してプログラムを終了する。
     * @return string 最終画面の登録処理を実行してhtmlを返す
     */
    public function submit($post)
    {
        if ($post['import_id'] !== $this->getHash($post['ng_line'], $post['temp_id'])) {
            return $this->showError(array (
                '不正なデータ送信です'
            ), $post);
        }
        if ($this->useProgressbar) {
            $this->directPrint($this->design->getHeader());
            $this->directPrint($this->design->getSubmitBeforeProgressView());
            $this->directPrint($this->design->getProgressBar());
            ob_end_flush();
        }
        $excel = new Spreadsheet_Excel_Reader();
        $excel->setUTFEncoder('mb');
        $excel->setOutputEncoding('UTF-8');
        $excel->read(temp_file_path($post['temp_id']));
        $ng_line = explode(':', $post['ng_line']);
        $max = $post['total'];
        $n = ceil($max / 100);
        if ($this->useTransaction)
            FDB :: begin();
        $this->model->onBeforeImport();
        $errors = array ();
        $sheet_num = 0;
        while ($sheet = $excel->sheets[$sheet_num]) {
            if (in_array($sheet_num +1, $ng_line)) {
                $sheet_num++;
                continue;
            }
            if ($error = $this->model->importLine($sheet_num, $sheet = $excel->sheets[$sheet_num]['cells'])) {
                $errors[] = $error;
                if ($this->useTransaction) {
                    FDB :: rollback();
                    if ($this->useProgressbar) {
                        $this->directPrint($this->design->getSubmitErrorEndView($errors));
                        $this->directPrint($this->design->getFooter());
                        exit;
                    } else {
                        return $this->design->getSubmitErrorEndNoProgressView($errors);
                    }
                }

            }
            if ($this->useProgressbar) {
                if ($sheet_num % $n == 0) {
                    $this->directPrint($this->design->getPercentUpdate(round($sheet_num / $max * 100), $sheet_num, $max));

                    ob_end_flush();
                }
            }
            $sheet_num++;
        }
        if ($this->useTransaction)
            FDB :: commit();
        if ($this->useProgressbar) {
            $this->directPrint($this->design->getPercentUpdate(100, $max, $max));
            $this->directPrint($this->design->getSubmitAfterProgressView($errors));
            $this->directPrint($this->design->getFooter());
            exit;
        }

        return $this->design->getSubmitNoProgressView($errors);
    }
}

$main = new ThisImporter(new UserImportModel(), new UserImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;
