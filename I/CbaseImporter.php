<?php
//ハッシュ作成に使うランダムコード
define('IMPORT_HASH_CODE', 'lfheawig');
require_once 'CbaseFFile2.php';

//FForm必須
//FEncode必須

//2008/10/07 1.0 akama 360度評価のインポートをベースに作成
//2008/12/17 1.1 akama ライブラリとして使いやすく整形

//文字コードはENCODE_FILE_IN依存

//　「//5」を空白に置き換えると、php5対応のより安全なモードになります

class Importer
{
    protected $model;
    protected $design;

    public function __construct(ImportModel $model, ImportDesign $design = null)
    {
        $this->setLocale();
        if($design === null) $design = new ImportDesign();

        $this->model = $model;
        $this->design = $design;
        $this->design->model = $model;
        $this->design->action = $this->getAction();
    }

    /**
     * fgetcsv用のロケールをセットする
     */
    public function setLocale()
    {
        //setlocale( LC_ALL, "ja_JP.eucJP");
    }
    //プロパティ

    /**
     * trueの場合、登録中にプログレスバーが表示される。
     */
    public $useProgressbar = true;

    /**
     * trueの場合、トランザクションを利用する。SQLエラーがあっても続行したい場合は外す。
     */
    public $useTransaction = true;

    /**
     * trueの場合、セッションIDを受け渡すようになる。
     */
    public $useSession = false;

    /**
     * 処理を実行し、表示用htmlを返す。外部からはこのメソッドだけを使えばよい。
     */
    public function run($post)
    {
        if ($post) {
            $this->setModelValueFromPost ($post);
        }

        switch ($post['import']) {
            case 'confirm':
                return $this->getOuterHtml($this->confirm($post));
                break;
            case 'submit':
                //backでPOSTされたときは何もしない（トップに移動
                if(!$post['back'])	return $this->getOuterHtml($this->submit($post));
                break;
            default:
        }

        return $this->getOuterHtml($this->top ());
    }

    /**
     * modelで設定された列のPOST値を読み取り、modelにセットする
     */
    public function setModelValueFromPost($post)
    {
        $keys = $this->model->getFormKeys();
        foreach ($keys as $v) {
            $this->model->post[$v] = $post[$v];
        }
    }

    /**
     * @return string $bodyにデザインクラスのヘッダとフッタをくっつけて返す
     */
    public function getOuterHtml($body)
    {
        return $this->design->getHeader().$body.$this->design->getFooter();
    }

    /**
     * @return string 最初の画面の処理を行い、htmlを取得
     */
    public function top()
    {
        $hidden = $this->design->getHidden('import', 'confirm').$this->getHiddenSID ();
        $forms = $this->model->getFormKeys();
        $forms[] = 'file';
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

        $result = $this->importConfirmExec(temp_file_path($temp_id), $post);
        if($this->showError)

            return $result;

        list($ng_line, $total, $ok_count) = $result;
        $errors = array();

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
            $this->design->getFormatValues($this->model->getFormKeys(), $post), $ok_count, $errors);
    }

    public function importConfirmExec($file, $post = array())
    {
        $tmp = file_get_contents($file);
        $mb = mb_detect_encoding($tmp);
        if($mb=='SJIS')
            $mb = 'sjis-win';

        if($mb)
            file_put_contents($file,mb_convert_encoding($tmp, "UTF-8",$mb));
        else
            file_put_contents($file,mb_convert_encoding($tmp, "UTF-8","Unicode"));

        syncCopy($file);

        $this->showError = false;
        $fp = fopen($file, 'r');

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
            $this->model->header = $this->getRowFromFile ($fp, $post, true);
            $ng_line[] = $i;
        }

        $this->model->onBeforeErrorCheck();

        //グローバルエラーのチェックを行う
        $errors = $this->model->getGlobalErrors();
        if ($errors) {
            $this->showError = true;

            return $this->showError($errors, $post);
        }

        $errors = array ();
        $ok_count = 0;
        $max_error = $this->model->getMaxErrorCount();
        $total = 0;
        $this->model->mflagCount = 0;
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
                $this->showError = true;

                return $this->showError($errors, $post);
            }
        }
        $errors = array_merge($this->model->getTotalErrors(), $errors);

        /******************************/

        /** 設定によっては一件でもエラーがあれば終了 */
        if (!$this->model->isPassableError() && 0 < count($errors)) {
            $this->showError = true;

            return $this->showError($errors, $post);
        }

        return array($ng_line, $total, $ok_count);
    }

    /**
     * @return array ファイルパスからcsvを一行取得する。取得後は文字コードが変換されて返る。
     */
    public function getRowFromFile ($fp, $post=array(), $delete_bom=false)
    {
        if(!$this->delimiter)
            $this->delimiter = ',';
        $res = CbaseFgetcsv($fp, $this->delimiter, "\"", "UTF-8", $delete_bom);
        if (is_array($res))
            foreach ($res as &$tmp)
                $tmp = str_replace(chr(0),"",$tmp);

        if(count($res)==1 && $res[0]==="")

            return false;
        return $res;
    }

    /**
     * @return string model設定のpost値をhiddenにして返す処理
     */
    public function getHiddenFromPost($post)
    {
        $hidden = '';
        if ($post) foreach ($this->model->getFormKeys() as $v) {
            $hidden .= $this->design->getHidden($v, $post[$v]);
        }

        return $hidden;
    }

    /**
     * @return string エラー画面のhtmlを作成して返す
     */
    public function showError ($message, $post=array())
    {
        $hidden = $this->getHiddenSID ().$this->getHiddenFromPost ($post);
        $forms = array('back');

        return $this->design->getErrorView($hidden, $this->design->getForms($forms), $message);
    }

    /**
     * このクラスから直接画面に書き出すための命令。useProgressbar時のみ使用
     */
    public function directPrint($body)
    {
        print encodeWebOut($body);
    }

    /**
     * 不正文字列防止用のハッシュ文字列を取得
     */
    public function getHash($ngcode, $temp_id)
    {
        return substr(sha1($ngcode.$temp_id.IMPORT_HASH_CODE), 0, 16);
    }

    /**
     * useProgressbar有効の際は、このメソッドから結果を出力してプログラムを終了する。
     * @return string 最終画面の登録処理を実行してhtmlを返す
     */
    public function submit($post)
    {
        if ($post['import_id'] !== $this->getHash($post['ng_line'], $post['temp_id'])) {
            return $this->showError(array('不正なデータ送信です'), $post);
        }

        if ($this->useProgressbar) {
            $this->directPrint($this->design->getHeader());
            $this->directPrint($this->design->getSubmitBeforeProgressView());
            $this->directPrint($this->design->getProgressBar());
            ob_end_flush();
        }

        $this->importExec(temp_file_path($post['temp_id']), $post['ng_line'], $post['total'], $post);

        if ($this->useProgressbar) {
            $this->directPrint($this->design->getPercentUpdate(100,$max,$max));
            $this->directPrint($this->design->getSubmitAfterProgressView($errors));
            $this->directPrint($this->design->getFooter());
            exit;
        }

        return $this->design->getSubmitNoProgressView($errors);
    }

    public function importExec($file, $ng_line, $total, $post = array())
    {
        $fp = fopen($file, 'r');
        $line =	fgets($fp);
        $line .=	fgets($fp);
        $line .=	fgets($fp);
        rewind($fp);
        if(count(explode("\t",$line)) < count(explode(",",$line)))
            $this->delimiter = ",";
        else
            $this->delimiter = "\t";

        /******************************/
        $i = 0;
        //ヘッダを除去
        if ($this->model->isHavingHeader()) {
            $i++;
            $this->model->header = $this->getRowFromFile ($fp, $post, true);
        }

        $ng_line = explode(':', $ng_line);

        $max = $post['total'];
        $n = ceil($max/100);

        if($this->useTransaction) FDB::begin();
        $this->model->onBeforeImport();
        $errors = array ();
        while ($row = $this->getRowFromFile ($fp, $post)) {
            $i++;
            if (in_array($i, $ng_line)) continue;
            if ($error = $this->model->importLine($i, $row)) {
                $errors[] = $error;
                if ($this->useTransaction) {
                    FDB::rollback();
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
                if ($i%$n==0 || $i == $max) {
                    $this->directPrint($this->design->getPercentUpdate(round($i/$max*100),$i,$max));

                    ob_flush();
                    flush();
                }
            }
        }
        fclose($fp);
        if($this->useTransaction) FDB::commit();
        $this->model->onAfterImport();
    }

    /**
     * このメソッドは、案件単位で共通関数へ書き換えた方がよい
     * @return string SIDを示すhiddenフォームを含むhtml
     */
    public function getHiddenSID()
    {
        if($this->useSession)

            return $this->design->getHidden(SESSIONID, html_escape(session_id()));
    }

    /**
     * このメソッドは、案件単位で共通関数へ書き換えた方がよい
     * @return string 自分自身のアドレス（パラメータはできるだけつけない）
     */
    public function getAction()
    {
        return html_escape(basename($_SERVER['SCRIPT_NAME']));
    }
}

class ImportDesign
{
    public $model;

    public $action;

    /**
     * @return string formのactionに設定すべきアドレスを取得する
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return string 一画面目での次へボタンのhtml
     */
    public function getNextButton($name)
    {
        return FForm::submit ($name, "選択したファイルから取り込み",' onSubmit="return this.flag?false:this.flag=true;"class="imgbutton160"');
    }

    /**
     * @return string 二画面目以降での戻るボタン
     */
    public function getBackButton($name)
    {
        return FForm::submit ($name, "アップロード画面へ戻る");
    }

    /**
     * @return string 二画面目での登録実行ボタン
     */
    public function getSubmitButton($name)
    {
        return FForm::submit ($name, "インポートする",' onClick="'.JSCODE_ANTI_DOUBLE_CLICK.'"');
    }

    /**
     * @return string hiddenタグを作成する
     */
    public function getHidden($name, $value)
    {
        //旧バージョン対応
        if (!defined('FFORM_ESCAPE') || !FFORM_ESCAPE) {
            $value = transHtmlentities($value);
        }

        return FForm::hidden($name, $value);
    }

    /**
     * @return string アップロード時エラーの文言。falseでエラー無し。
     */
    public function getUploadErrorMessage($errorcode)
    {
        switch ($errorcode) {
            case UPLOAD_ERR_INI_SIZE :
            case UPLOAD_ERR_FORM_SIZE :
                return "アップロード可能な容量制限を超えています";
            case UPLOAD_ERR_PARTIAL :
                return "ファイルアップロードに失敗しました";
            case UPLOAD_ERR_NO_FILE :
                return "ファイルを選択してください";
            case UPLOAD_ERR_OK :
                return false;
            default :
                return "ファイルアップロードに失敗しました";
        }
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
                return FForm::radio($name, 0, 'エラー行を無視して続行').
                    '<br>'.FForm::radio($name, 1, '処理を中断する', 'checked');
            default:
                if(isset($default))

                    return $this->getHidden($name, $default);
                break;
        }
    }

    /**
     * 確認画面用の変換を行う。hiddenは別途渡されるため、表示値のみ返せばよい。
     */
    public function getFormValueCallback($name, $post)
    {
        return $post[$name];
    }

    /**
     * @return bool ここでtrueを返した列は、テーブル変換されずフォームが直接表示される。hiddenタグ用
     */
    public function isHiddenLine($name)
    {
        return false;
    }

    public function getForms($names)
    {
        $res = array();
        foreach ($names as $v) {
            $res[$v] = $this->getFormCallback($v, $this->model->getFormDefaultValue($v));
        }

        return $res;
    }

    public function getFormatValues($names, $post)
    {
        $res = array();
        foreach ($names as $v) {
            $res[$v] = $this->getFormValueCallback($v, $post);
        }

        return $res;
    }

    public function getFirstViewLine($subject, $form)
    {
        return <<<__HTML__
<tr>
  <th bgcolor="#eeeeee" align="right">{$subject}</th>
  <td bgcolor="#ffffff">{$form}</td>
</tr>
__HTML__;
    }


    public function getFirstViewTable($line, $forms)
    {
        return <<<__HTML__
<table class="cont"border="0" cellspacing="1" cellpadding="3" bgcolor="#000000"style="width:auto;margin:30px">
{$line}
</table>
__HTML__;
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
        foreach ($this->model->getFormNames() as $k => $v) {
            if ($this->isHiddenLine($k)) {
                $line .= $forms[$k];
            } else {
                $line .= $this->getFirstViewLine($v, $forms[$k]);
            }
        }
        $line .= $this->getFirstViewLine('', $forms['next']);
        $table = $this->getFirstViewTable($line, $forms);

        return $this->getFormArea($hidden.$table, 'enctype="multipart/form-data"').$this->getFirstViewMessage();
    }

    public function getFirstViewMessage()
    {
        return "";
    }

    public function getFormArea($body, $enctype='')
    {
        if($enctype) $enctype = ' '.$enctype;
        $action = $this->getAction();

        return <<<__HTML__
<form method="POST" action="{$action}"{$enctype}>
{$body}
</form>
__HTML__;
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

        $countHtml = $this->model->countFlag?$this->getCountShow():'';

        $html = $this->getFormArea(<<<HTML
{$hidden}
{$line_count}行をインポートしますか？
<br>
{$countHtml}
<br>
{$forms['back']}
{$forms['submit']}
</form>
HTML
);
        if (0 < count($this->model->caution)) {
            $html .=<<<HTML
<br><br>
HTML;
            $html .= $this->getErrorShow($this->model->caution);
        }
        if (0 < count($errors)) {
            $html .=<<<HTML
<br><br>
以下のエラーがありました。<br>
このままインポートしますと、正常な行のみ取り込まれます。
<br><br>
HTML;
            $html .= $this->getErrorShow($errors);
        }

        return $html;
    }

    public function getCountShow()
    {
        $counti = (int) $this->model->countI;
        $countu = (int) $this->model->countU;
        $countd = (int) $this->model->countD;
        $countn = (int) $this->model->countN;

        return<<<HTML

<div style="margin:10px auto;width:250px;text-align:left;padding:20px;border:dotted 1px black;">
新規追加　{$counti}件<br>
更新　{$countu}件<br>
削除　{$countd}件<br>
変更なし　{$countn}件<br>
</div>
HTML;
    }

    public function getErrorView ($hidden, $forms, $errors=array())
    {
        $action = $this->getAction ();
        $html = $this->getFormArea(<<<HTML
{$hidden}
致命的なエラーが発生したため、インポートを中止します。<br><br>
{$forms['back']}
HTML
);
        if (0 < count($errors)) {
            $html .=<<<HTML
<br><br>
以下のエラーがありました。<br>
<br><br>
HTML;
            $html .= $this->getErrorShow($errors);
        }

        return $html;
    }





    public function getErrorShow($errors)
    {
        $html = <<<HTML
<div style="width:700px;line-height:20px;text-align:left;padding:10px;border:dotted 1px black;">
HTML;
        foreach ($errors as $tmp) {
            $html .=<<<HTML
{$tmp}<br>

HTML;
        }
        $html .=<<<HTML
</div>
HTML;

        return $html;
    }



    /**
     * @return string progressbar未使用時、完了時のhtmlを返す
     */
    public function getSubmitNoProgressView ($errors=array())
    {
        $html = <<<HTML
<hr>
<h1>インポート完了しました。</h1>
<hr>
HTML;
        $html .= $this->getSubmitErrorView($errors);

        return $html;
    }

    /**
     * @return string progressbar未使用時、submit処理がエラー終了した時のhtmlを返す
     */
    public function getSubmitErrorEndNoProgressView ($errors=array())
    {
        $html = <<<HTML
<hr>
<h1>エラー発生のため処理を中断しました。</h1>
<hr>
HTML;
        $html .= $this->getSubmitErrorView($errors);

        return $html;
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
(1) ファイル内の行数分のデータを処理しています。
</div>
__HTML__;

        return $html;
    }


    /**
     * @return string submitが完了した際のhtmlを返す
     */
    public function getSubmitAfterProgressView ($errors=array())
    {
        $html = <<<__HTML__
<div style="width:500px;text-align:left;margin:20px;">
(2)情報の登録が完了しました。
</div>
__HTML__;
        $html .= $this->getSubmitErrorView($errors);

        return $html;
    }

    /**
     * @return string submit時にエラーが発生して強制終了した際のhtmlを返す
     */
    public function getSubmitErrorEndView ($errors=array())
    {
        $html = <<<__HTML__
<div style="width:500px;text-align:left;margin:20px;">
(2)エラー発生のため処理を中断しました。
</div>
__HTML__;
        $html .= $this->getSubmitErrorView($errors);

        return $html;
    }

    /**
     * @return string submit時のエラーを整形したhtmlを返す
     */
    public function getSubmitErrorView ($errors=array())
    {
        $html = '';
        if (0 < count($errors)) {
            $html .=<<<HTML
インポート時に以下のエラーがありました。<br>
<br><br>
HTML;
            foreach ($errors as $tmp) {
                    $html .=<<<HTML
{$tmp}<br>
HTML;
            }
        }

        return $html;
    }

    /**
     * プログレスバー不使用の場合はここで設定せず、出力結果を別のデザインクラスに通してもok
     * @return string 常に出力するヘッダ部分のhtmlを返す。
     */
    public function getHeader()
    {
        return <<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML><HEAD>
<META http-equiv=Content-Type content="text/html; charset=UTF-8"></HEAD>
<BODY>
<br>
<br>
<div align="center">
__HTML__;
    }

    /**
     * @return string 常に出力するフッタ部分のhtmlを返す。ヘッダと対にすること。
     */
    public function getFooter()
    {
        return <<<__HTML__
</div>
</BODY></HTML>
__HTML__;
    }

    public function getProgressBar()
    {
        return<<<HTML
<style>
.graph {
    width: 400px;
    border: 1px solid #2e8b57;
    padding: 2px;
}
.graph .bar {
    height: 20px;
    display: block;
    position: relative;
    background: #2e8b57;
}
#percent{
    width:100px;
}
</style>
<script>
function p(p,i,m)
{
    document.getElementById('table1').style.display="";
    document.getElementById('percent').innerHTML = p + '%' + '('+i+'/'+m+')';
    document.getElementById('bar').style.width = p + '%';
}
</script>
<table style="margin-left:20px;display:none" align="left" id="table1">
<tr><td class="graph"><span class="bar" style="width: 0%;" id="bar"><br></span></td><td id="percent"></td></tr>
</table>
HTML;
    }

    public function getPercentUpdate($p,$i,$m)
    {
        return "<script>p({$p},{$i},{$m});</script>";
    }
}

class ImportModel
{
    /**
     * ヘッダーありの場合、ここにヘッダが格納される
     */
    public $header;

    /**
     * getFormNamesで指定したデータのPOST値がここに入る
     */
    public $post;

    /**
     * トップ画面で表示するフォームを追加できる。
     * 実際のフォームの組み立てはviewで行い、viewを設定しなければhiddenになる
     * @return array name=>名称の配列で返す
     */
    public function getFormNames()
    {
        return array(
            'error_end' => 'エラー検出時の動作'
        );
    }

    public function getFormKeys()
    {
        return array_keys($this->getFormNames ());
    }

    /**
     * @return mixed フォームのデフォルト値を返す。hiddenの場合nullを返すと表示無し。
     */
    public function getFormDefaultValue($key)
    {
        switch ($key) {
            case 'error_end':
                return true;
                break;
            default:
                return null;
        }

        return null;
    }

    /**
     * @return array インポートファイルの列フォーマットを指定。
     */
    public function getRows()
    {
        return array();
    }

    public $hasHeader = true;
    /**
     * @return bool インポートファイルがヘッダを持つかどうかを返す。trueでヘッダあり。
     */
    public function isHavingHeader()
    {
        return $this->hasHeader;
    }

    /**
     * @return bool エラーを無視して次に進めるかどうかを返す。trueで無視する。
     */
    public function isPassableError()
    {
        return $this->post['error_end']? false: true;
    }

    /**
     * @return int 何件以上のエラーで無効とするかを返す。overflowにならない程度で。
     */
    public function getMaxErrorCount()
    {
        return 100;
    }

    /**
     * @return bool 同じ列数と判断できるならtrue。列数に揺れがある場合などは上書きする。
     */
    public function isSameRowCount($row)
    {
        return (count($row) == count($this->getRows()));
    }

    /**
     * @return array 行毎ではなく全体でのエラーチェックが必要な場合ここで行い、
     *               エラーがあればエラー文言の配列を返す。
     */
    public function getGlobalErrors()
    {
        /*
         * ヘッダが完全に設定値どおりかチェックするサンプル
        if (!$this->isSameRowCount($this->header)) {
            return array("ヘッダが不正です。");
        }
        */

        return array();
    }

    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $row)
    {
        $error = array();
        if (!$this->isSameRowCount($row)) {
            $error[] = $line_no."行目:列数が不正です。".count($row).'列になっています。';
        }

        return $error;
    }

    /**
     * @return array 登録数エラー等、複数行合わせたエラーの配列を返す。
     */
    public function getTotalErrors()
    {
        return array();
    }

    /**
     * @return string インポートを実行し、エラーがあればエラー文言を返す。書き換え必須。
     */
    public function importLine($line_no, $row)
    {
        //ここでインポート処理
        return '';
    }

    //イベント類

    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport()
    {

    }
    /**
     * インポート処理後、トランザクション処理終了後に呼び出されるメソッド
     */
    public function onAfterImport()
    {

    }
    /**
     * エラーチェック処理直前、ファイルアップロード処理後に呼び出されるメソッド
     */
    public function onBeforeErrorCheck()
    {

    }
}
/*
require_once ($path.'CbaseImporter.php');
class XXXImportModel extends ImportModel
{
    public function importLine($line_no, $row)
    {
        //ここでインポート処理
        return '';
    }
}

class XXXImportDesign extends ImportDesign
{

}

$main = new Importer(new XXXImportModel(), new XXXImportDesign());
$main->useSession = true;
echo $main->run($_POST);
*/
