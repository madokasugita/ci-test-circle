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
require_once (DIR_LIB . '360_EnqueteRelace.php');
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', '	評価データインポート');
/****************************************************************************************************************************/
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
class ThisImportModel extends ImportModel360
{
    public function setData($data)
    {
        $array = array();

        $array['uid'] = $data[1];
        $array['uid_t'] = $data[0];
        $array['serial_no'] = $this->users[$array['uid']]['serial_no'];
        $array['serial_no_t'] = $this->users[$array['uid_t']]['serial_no'];

        unset($data[0]);
        unset($data[1]);
        foreach ($data as $v) {
            $array['answer'][] = $v;
        }

        return $array;
    }

    public function importLine($line_no, $data) //override
    {
        $array = $this->setData($data);
        $evid = $this->getEvid($array);
        $user = $this->users[$array['uid']];
        $user_t = $this->users[$array['uid_t']];

        FDB::begin();
        $a = FDB::select1(T_EVENT_DATA,'answer_state,event_data_id	','where evid = '.FDB::escape($evid).' and serial_no = '.FDB::escape($user['serial_no']).' and target = '.FDB::escape($user_t['serial_no']));
        if ($a) {
            FDB::delete(T_EVENT_SUB_DATA,'where event_data_id = '.FDB::escape($a['event_data_id']));
            FDB::delete(T_EVENT_DATA,'where event_data_id = '.FDB::escape($a['event_data_id']));
        }
        $answer = $array['answer'];
        $subevents = $this->subevents[$this->getEvid($array)];

        $data = array();
        //$event_data_id = $data['event_data_id'] = FDB::getNextVal('event_data_event_data_id');
        $data['evid'] = $evid;
        $data['serial_no'] = $user['serial_no'];
        $data['target'] = $user_t['serial_no'];
        $data['answer_state'] = ($_POST['answer_state']==10)? 10:0;
        $data['cdate'] = date('Y-m-d H:i:s');
        $data['udate'] = date('Y-m-d H:i:s');
        $data['flg'] = 1;
        $data['ucount'] = 0;
        if (is_false(FDB::insert(T_EVENT_DATA,FDB::escapeArray($data)))) {
            FDB::rollback();

            return false;
        }
        $event_data_id = FDB::getLastInsertedId();
        $i=0;
        foreach ($subevents as $subevent) {
            $data = array();
            $data['event_data_id'] = $event_data_id;
            $data['evid'] = $evid;
            $data['seid'] = $subevent['seid'];
            $data['serial_no'] = $user['serial_no'];
            if ($subevent['type2']=='r' || $subevent['type2']=='p') {
                $ch = array_flip(explode(',',$subevent['chtable']));
                $c = (is_good($subevent['chtable']))? $ch[$answer[$i]]:$answer[$i];

                $data['choice'] = (is_void($c)) ? '9998' : $c;
                if (is_false(FDB::insert(T_EVENT_SUB_DATA,FDB::escapeArray($data)))) {
                    FDB::rollback();

                    return false;
                }
            } elseif ($subevent['type2']=='t') {
                $data['choice'] = '-1';
                $data['other'] = $answer[$i];
                if (is_false(FDB::insert(T_EVENT_SUB_DATA,FDB::escapeArray($data)))) {
                    FDB::rollback();

                    return false;
                }
            }
            $i++;
        }
        FDB::commit();
    }

    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data) //override
    {
        $error = array ();
        $array = $this->setData($data);

        $user = $this->users[$array['uid']];
        $user_t = $this->users[$array['uid_t']];

        if(!$user['serial_no'])
            $error[] = $line_no."行目:回答者({$array['uid']})が存在しません。";

        if(!$user_t['serial_no'])
            $error[] = $line_no."行目:対象者({$array['uid_t']})が存在しません。";
        elseif(!$user_t['mflag'])
            $error[] = $line_no."行目:({$array['uid_t']})は対象者ではありません。";

        if($error)

            return $error;

        if(!isset($this->user_relation[$array['uid_t']][$array['uid']]))
            $error[] = $line_no."行目:({$array['uid']})は({$array['uid_t']})の回答者ではありません。";

        if($error)

            return $error;

        $answer = $array['answer'];
        $subevents = $this->subevents[$this->getEvid($array)];
        $i=-1;
        foreach ($subevents as $subevent) {
            $i++;
            $r = $i+3;
        /*	if ($subevent['hissu'] && $answer[$i]==="") {
                $error[] = $line_no."行目 {$r}列目:({$array['uid']}<-{$array['uid_t']}) 必須回答が未入力です。";
                continue;
            }
        */
            if ($answer[$i]!=="" && ($subevent['type2']=='r' || $subevent['type2']=='p') && is_good($subevent['chtable'])) {
                if (!in_array($answer[$i],explode(',',$subevent['chtable']))) {
                    $error[] = $line_no."行目 {$r}列目:({$array['uid']}<-{$array['uid_t']}) 不正な回答です。".$answer[$i].$subevent['chtable'];
                    continue;
                }
            }
        }

        return $error;
    }

    public function getEvid($array)
    {
        $user_type = $this->user_relation[$array['uid_t']][$array['uid']];
        $sheet_type = $this->users[$array['uid_t']]['sheet_type'];

        return $sheet_type*100+$user_type;
    }
    /**
     * @return array エラーチェックを行い
     */
    public function getCautions($line_no, $data) //override
    {
        $error = array ();
        $array = $this->setData($data);
        $evid = $this->getEvid($array);
        $user = $this->users[$array['uid']];
        $user_t = $this->users[$array['uid_t']];
        $a = FDB::select1(T_EVENT_DATA,'answer_state','where evid = '.FDB::escape($evid).' and serial_no = '.FDB::escape($user['serial_no']).' and target = '.FDB::escape($user_t['serial_no']));
        if ($a && $a['answer_state']==0) {
            $error[] = $line_no."行目:({$array['uid']}<-{$array['uid_t']}) 回答済みデータを上書きします。";
        }
        if ($a && $a['answer_state']==10) {
            $error[] = $line_no."行目:({$array['uid']}<-{$array['uid_t']}) 途中保存データを上書きします。";
        }

        return $error;
    }

    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport() //override
    {
        foreach (FDB::select(T_USER_MST,'serial_no,uid,mflag,sheet_type') as $user) {
            $this->users[$user['uid']] = $user;
            if($user['mflag'])
                $this->user_relation[$user['uid']][$user['uid']] =0;
        }
        foreach (FDB::select(T_USER_RELATION, '*', "WHERE user_type <= ".INPUTER_COUNT) as $user) {
            $this->user_relation[$user['uid_a']][$user['uid_b']] = $user['user_type'];
        }
        foreach (FDB::select(T_EVENT_SUB,'*',"where type2 <> 'n' order by seid") as $subevent) {
            $this->subevents[$subevent['evid']][$subevent['seid']] = $subevent;
        }
    }
    /**
     * エラーチェック処理直前、ファイルアップロード処理後に呼び出されるメソッド
     */
    public function onBeforeErrorCheck()//override
    {
        $this->onBeforeImport();
    }
    public function getFormNames()
    {
        return array(
            'last_file' => '前回取り込みファイル',
            'udate' => '更新日時',
        //	'error_end' => 'エラー検出時の動作',
        //	'checkemail' => 'メールアドレス重複チェック',
            'answer_state' => '回答ステータス'
        );
    }
    public function isPassableError()
    {
        return false;
    }
    public function onAfterImport()
    {
        if (!$this->upsertImportFile($_SESSION["muid"], $this->getExecFile())) {
            // エラー発生時
        }
    }
}

class ThisImportDesign extends ImportDesign360
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
            case 'submit':
                return $this->getSubmitButton($name);
            case 'udate':
                return $model->getUdateImportFile ($model->getExecFile());
            case 'last_file':
                return $model->getLastFileName ($model->getExecFile());
            case 'error_end':
                return FForm::radio($name, 0, 'エラー行を無視して続行').
                    '<br>'.FForm::radio($name, 1, '処理を中断する', 'checked');
            case 'checkemail':
                return FForm::radio($name, 0, 'チェックしない').
                    '<br>'.FForm::radio($name, 1, 'チェックする', 'checked');
            case 'answer_state':
                return FForm::radio($name, 10, '回答中').FForm::radio($name, 0, '回答完了', 'checked');
            default:
                if(isset($default))

                    return $this->getHidden($name, $default);
                break;
        }
    }

    public function getFirstViewMessage()
    {
        $PHP_SELF = PHP_SELF;
        $csvtable=<<<HTML
<div><a href="{$PHP_SELF}&csvdownload=1">全シートのヘッダーをダウンロード</a></div>
<h2>シート毎のインポート用ヘッダーをダウンロード</h2>
<table class="cont" style="width:500px">
<tr><th style="width:30px">ID</th><th>シート名</th><th>ダウンロード</th><tr>
HTML;

        foreach ($GLOBALS['_360_sheet_type'] as $k1 => $sheet_type) {
            foreach ($GLOBALS['_360_user_type'] as $k2 => $user_type) {
                if ($k2>INPUTER_COUNT) {
                    continue;
                }
                $evid = $k1 *100+$k2;
                $csvtable.=<<<HTML
<tr><td style="width:30px;text-align:center;">{$evid}</td><td>{$sheet_type} {$user_type}</td><td style="width:30px;text-align:center;">
<a href="{$PHP_SELF}&csvdownload=1&evid={$evid}">ダウンロード</a>　
<br>
HTML;
                $csvtable.="</td><tr>";
            }
        }

        return $csvtable;
    }
    /**
     * @param string $hidden     hiddenの値。formタグ内のどこかに含めてください
     * @param array  $forms      フォームセット。backとsubmitのみ
     * @param array  $values     表示用の値セット。model依存
     * @param int    $line_count 取り込み可能な行数
     * @param array  $errors     エラーが発生していれば、その内容の配列
     */
    public function getConfirmView ($hidden, $forms, $values, $line_count, $errors=array())
    {
        $action = $this->getAction();
        $html = $this->getFormArea(<<<HTML
{$hidden}
{$line_count}行をインポートしますか？<br><br>
{$forms['back']}
{$forms['submit']}
</form>
HTML
);
        if (0 < count($errors)) {
            $html .=<<<HTML
<br><br>
HTML;
            $html .= $this->getErrorShow($errors);
        }

        return $html;
    }
}

class ThisImporter extends Importer360
{

    public function downloadSubevents()
    {
        $evid = is_good($_REQUEST['evid']) ? "evid = ".FDB::escape($_REQUEST['evid'])." AND " : '';
        $sheet_name = is_good($_REQUEST['evid']) ? "評価インポートヘッダー_".$_REQUEST['evid'].".csv" : "評価インポートヘッダー各種.csv";
        $where = "WHERE ".$evid."type2 IN ('r','p','t') ORDER BY seid,evid";
        $_csv = FDB::select(T_EVENT_SUB, "evid,choice,seid,type2,title", $where);
        $csv = array();

        foreach ($_csv as $v) {
            if (is_void($csv[$v['evid']])) {
                $csv[$v['evid']][] = "対象者ID";
                $csv[$v['evid']][] = "回答者ID";
            }
            $csv[$v['evid']][] = $v['seid'];
        }
        $csv = array_values($csv);

        csv_download_utf8($csv, $sheet_name, false);
        exit;
    }

    /**
     * @return string 二番目の画面でエラーチェック処理を行い、結果のhtmlを取得
     */
    public function confirm($post)
    {
        //アップロードエラーのチェックを行う
        $errors = $this->design->getUploadErrorMessage ($_FILES['file']['error']);
        if ($errors) {
            return $this->showError(array($errors), $post);
        }
        $temp_id  = temp_rename($_FILES['file']['tmp_name']);
        $_SESSION['upload_file_name'] = $_FILES['file']['name'];

        $tmp = file_get_contents(temp_file_path($temp_id));
        $mb = mb_detect_encoding($tmp);
        if($mb=='SJIS')
            $mb = 'sjis-win';

        if($mb)
            file_put_contents(temp_file_path($temp_id),mb_convert_encoding($tmp, "UTF-8",$mb));
        else
            file_put_contents(temp_file_path($temp_id),mb_convert_encoding($tmp, "UTF-8","Unicode"));

        syncCopy(temp_file_path($temp_id));

        $fp = fopen(temp_file_path($temp_id), 'r');

        $line =	fgets($fp);
        $line .=	fgets($fp);
        $line .=	fgets($fp);
        rewind($fp);
        if(count(explode("\t",$line)) < count(explode(",",$line)))
            $this->delimiter = ",";
        else
            $this->delimiter = "\t";

        $ng_line = array ();
        $i = 0;
        /*
         * memo:
         * ループの最初で$iに加算しているのは、1始まりで処理したいため。
         * getRowFromFileは行番号を指定しないので、それ以外を1始まりで統一していれば問題ない。
         */

        //ヘッダを除去
        if ($this->model->isHavingHeader()) {
            $i++;
            $this->model->header = $this->getRowFromFile ($fp, $post);
            $ng_line[] = $i;
        }

        $this->model->onBeforeErrorCheck();

        //グローバルエラーのチェックを行う
        $errors = $this->model->getGlobalErrors();
        if ($errors) {
            return $this->showError($errors, $post);
        }

        $errors = array ();
        $cautions = array();
        $ok_count = 0;
        $max_error = $this->model->getMaxErrorCount();
        $total = 0;
        while ($row = $this->getRowFromFile ($fp, $post)) {
            $total++;
            $i++;
            $error = $this->model->getErrors($i, $row);
            if(!$errors)
                $caution = $this->model->getCautions($i, $row);
            if (0 < count($caution)) {
                $cautions = array_merge($cautions, $caution);
            }
            if (0 < count($error)) {
                $errors = array_merge($errors, $error);
                $ng_line[] = $i;
            } else {
                $ok_count++;
            }

            //エラー件数が多すぎるなら終了
            if ($max_error < count($errors)) {
                $errors[] = 'エラー件数が'.$max_error.'件を越えたため、インポートを実行できません';

                return $this->showError($errors, $post);
            }
        }
        /******************************/

        /** 設定によっては一件でもエラーがあれば終了 */
        if (!$this->model->isPassableError() && 0 < count($errors)) {
            return $this->showError($errors, $post);
        }

        $ngcode = implode(':', $ng_line);
        $hash = $this->getHash ($ngcode, $temp_id);
        $hidden = $this->design->getHidden('ng_line', $ngcode).
        $this->design->getHidden('import_id', $hash).
        $this->design->getHidden('total', $total).
        $this->design->getHidden('import', 'submit').
        $this->design->getHidden('temp_id', $temp_id).
        $this->getHiddenSID ().
        $this->getHiddenFromPost ($post);

        $forms = array('back', 'submit');

        return $this->design->getConfirmView($hidden, $this->design->getForms($forms),
            $this->design->getFormatValues($this->model->getFormKeys(), $post), $ok_count, $cautions);
    }
}

if($_REQUEST['csvdownload'])
    ThisImporter::downloadSubevents();

$main = new ThisImporter(new ThisImportModel(), new ThisImportDesign());
$main->useSession = true;

$body = $main->run($_POST);
encodeWebAll();
print $body;

exit;
