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
require_once (DIR_LIB . '360_CreateEnaquete.php');
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', 'CSVから作成/更新');
/****************************************************************************************************************************/
class ThisImportModel extends ImportModel360
{

    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $row)
    {
        $error = array();
    //	if(!$this->isSameRowCount($row))
    //	{
    //		$error[] = $line_no."行目:列数が不正です。".count($row).'列になっています。';
    //	}
        return $error;
    }
}

class ThisImportDesign extends ImportDesign360
{
    public function getSubmitNoProgressView ($errors=array())
    {
        $tmpurl = Create_QueryString(Get_RandID(8), 'rid00'.$_POST['evid'], 1, "A");
        $prev   = DOMAIN.DIR_MAIN.PG_PREVIEW.'?lang360=0&rid='.$tmpurl;
        $SID =getSID();
        $html = <<<HTML
<hr>
<h1>完了しました。</h1>
<hr>
<a href="{$prev}" target="_blank">画面確認</a>

<hr>


<a href="360_enq_import.php?{$SID}">続けて作成/更新する</a>

HTML;
        $html .= $this->getSubmitErrorView($errors);

        return $html;
    }

    /**
     * フォームを返す
     * 既定値はfile, next,back,submit,error_end
     */
    public function getFormCallback($name, $default=null)
    {
        global $_360_sheet_type,$_360_user_type;
        switch ($name) {
            case 'evid':
                $array = array();
                foreach ($_360_sheet_type as $k1 => $sheet_type) {
                    foreach ($_360_user_type as $k2 => $user_type) {						if ($k2>INPUTER_COUNT) {
                            continue;
                        }
                        $evid = $k1 *100+$k2;
                        $array[$evid] = $sheet_type.' '.$user_type;
                    }
                }

                return FForm::select($name,$array);
            case 'file':
                return FForm::file($name,null,null,'style="width:220px;"');
            case 'next':
                return $this->getNextButton($name);
            case 'back':
                return $this->getBackButton($name);
            case 'submit':
                return $this->getSubmitButton($name);
            //case 'error_end':
            //	return FForm::radio($name, 0, 'エラー行を無視して続行').
            //		'<br>'.FForm::radio($name, 1, '処理を中断する', 'checked');
            default:
                if(isset($default))

                    return $this->getHidden($name, $default);
                break;
        }
    }
    /**
     * 自動でテーブルを組んでくれるが、不要の場合はこのメソッドをoverrideする
     * @param  string $hidden hidden値。formタグの中に含めてください
     * @param  array  $forms  フォーム列。file,nextおよびmodelで追加したフォームが使える
     * @return string トップ画面のhtmlを返す
     */
    public function getFirstView($hidden, $forms)
    {
        $line = '';
        $line .= $this->getFirstViewLine('ファイル', $forms['file']);
        $line .= $this->getFirstViewLine('シート名', $forms['evid']);
        foreach ($this->model->getFormNames() as $k => $v) {
            if ($this->isHiddenLine($k)) {
                $line .= $forms[$k];
            } else {
                $line .= $this->getFirstViewLine($v, $forms[$k]);
            }
        }
        $line .= $this->getFirstViewLine('', $forms['next']);
        $table = $this->getFirstViewTable($line, $forms);

        return $this->getFormArea($hidden.$table, 'enctype="multipart/form-data"');
    }
    /**
     * @return bool ここでtrueを返した列は、テーブル変換されずフォームが直接表示される。hiddenタグ用
     */
    public function isHiddenLine($name)
    {
        if($name == 'error_end')

            return true;
        return false;
    }
}

class ThisImporter extends Importer360
{
    /**
     * useProgressbar有効の際は、このメソッドから結果を出力してプログラムを終了する。
     * @return string 最終画面の登録処理を実行してhtmlを返す
     */
    public function submit($post)
    {
        if ($post['import_id'] !== $this->getHash($post['ng_line'], $post['temp_id'])) {
            return $this->showError(array('不正なデータ送信です'), $post);
        }
        $evid = $post['evid'];
        deleteEnquete($evid);
        $fp = fopen(temp_file_path($post['temp_id']), 'r');
        if (!$fp) {
            print "file opne error!";
            exit;
        }

        $line =	fgets($fp);
        $line .=fgets($fp);
        $line .=fgets($fp);
        rewind($fp);
        if(count(explode("\t",$line)) < count(explode(",",$line)))
            $this->delimiter = ",";
        else
            $this->delimiter = "\t";

        $rowspansize = getRowSpanSize($fp,$this->delimiter);
        $colspansize = getColSpanSize($fp,$this->delimiter);
        createEvent($evid);

        createTableHeader($evid,CbaseFgetcsv($fp, $this->delimiter, "\"", "UTF-8"));
        $i = 1;
        while (!feof($fp) && ($data = CbaseFgetcsv($fp, $this->delimiter, "\"", "UTF-8"))) {
            createSubevent($evid, $data, $i, $rowspansize[$i],$colspansize[$i]);
            if($data[3])
                $i++;
        }
        createTableFooter($evid, $i);
        switch ($evid %100) {
            case 0://本人
                insertMessageSubevent_($evid);
                insertMessageSubevent($evid);
                break;
            case 1://上司
                insertMessageSubevent_($evid);
                insertMessageSubevent($evid);
                break;
            case 2://部下
                insertMessageSubevent_($evid);
                insertMessageSubevent($evid);
                break;
            case 3://同僚
                insertMessageSubevent_($evid);
                insertMessageSubevent($evid);
                break;
        }

        rewind($fp);
        /*
        createTableHeaderConfirm($evid,CbaseFgetcsv($fp,$this->delimiter,"\"","UTF-8"));
        $i = 1;
        while (!feof($fp) && ($data = CbaseFgetcsv($fp,$this->delimiter,"\"","UTF-8"))) {
            createSubeventConfirm($evid, $data, $i, $rowspansize[$i],$colspansize[$i]);
            $i++;
        }
        createTableFooterConfirm($evid, $i);

        switch ($evid %100) {
            case 0://本人
                insertMessageSubeventConfirm_($evid);
                insertMessageSubeventConfirm($evid);
                break;
            case 1://上司
                insertMessageSubeventConfirm_($evid);
                insertMessageSubeventConfirm($evid);
                break;
            case 2://部下
                insertMessageSubeventConfirm_($evid);
                insertMessageSubeventConfirm($evid);
                break;
            case 3://同僚
                insertMessageSubeventConfirm_($evid);
                insertMessageSubeventConfirm($evid);
                break;
        }
        */
        fclose($fp);
        rename(temp_file_path($post['temp_id']),'../enqcsv/'.$evid.'.csv');
        syncCopy('../enqcsv/'.$evid.'.csv');
        s_unlink('../enqcsv/'.$evid.'.csv'.'.ctmp');

        return $this->design->getSubmitNoProgressView();
    }
    /**
     * @return string 最初の画面の処理を行い、htmlを取得
     */
    public function top()
    {
        $hidden = $this->design->getHidden('import', 'confirm').$this->getHiddenSID ();
        $forms = $this->model->getFormKeys();
        $forms[] = 'file';
        $forms[] = 'evid';
        $forms[] = 'next';

        return $this->design->getFirstView($hidden, $this->design->getForms($forms));
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
        $tmp = file_get_contents(temp_file_path($temp_id));
        $mb = mb_detect_encoding($tmp);

        if($mb)
            file_put_contents(temp_file_path($temp_id),mb_convert_encoding($tmp, "UTF-8",$mb));
        else
            file_put_contents(temp_file_path($temp_id),mb_convert_encoding($tmp, "UTF-8","Unicode"));

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
        $ok_count = 0;
        $max_error = $this->model->getMaxErrorCount();
        $total = 0;
        while ($row = $this->getRowFromFile ($fp, $post)) {
            $total++;
            $i++;
            $error = $this->model->getErrors($i, $row);
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

        //設定によっては一件でもエラーがあれば終了
        if (!$this->model->isPassableError() && 0 < count($errors)) {
            return $this->showError($errors, $post);
        }

        $ngcode = implode(':', $ng_line);
        $hash = $this->getHash ($ngcode, $temp_id);
        $hidden = $this->design->getHidden('ng_line', $ngcode).
            $this->design->getHidden('import_id', $hash).
            $this->design->getHidden('total', $total).
            $this->design->getHidden('import', 'submit').
            $this->design->getHidden('evid', $post['evid']).
            $this->design->getHidden('temp_id', $temp_id).
            $this->getHiddenSID ().
            $this->getHiddenFromPost ($post);

        $forms = array('back', 'submit');

        return $this->design->getConfirmView($hidden, $this->design->getForms($forms),
            $this->design->getFormatValues($this->model->getFormKeys(), $post), $ok_count, $errors);
    }
}

$main = new ThisImporter(new ThisImportModel(), new ThisImportDesign());
$main->useSession = true;
$main->useProgressbar = false;
$body = $main->run($_POST);
encodeWebAll();
print $body;
