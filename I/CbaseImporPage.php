<?php

//デバッグ用。登録せずにSQLを表示してくれる。
define('THISPAGE_NO_INSERT', 0);
require_once 'CbasePage.php';
require_once 'CbaseFFile2.php';
class CbaseImportPage extends CbasePage
{

    public function main()
    {
        switch ($this->getOperation()) {
            case 'step2' :
                return $this->step2();
            case 'step3' :
                return $this->step3();
            default :
                return $this->step1();
        }
    }

    public function getHtmlTop()
    {
        return "<h1>{$this->pagename}</h1>";
    }

    //step1 ファイル選択
    public function step1()
    {
        $error = $this->getErrorMessage();
        $PHP_SELF = $this->getPhpSelf();
        $html =<<<__HTML__
<form action="{$PHP_SELF}" method="POST" enctype="multipart/form-data" onsubmit="return this.flag?false:this.flag=true;">
<br>
{$error}
インポートするファイルを指定してください。
<span style="color:#999999;font-size:10px;">＊一行目(インデックス行)を除き、二行目からインポートされます。</span>
<table cellspacing="1" cellpadding="3">
    <tr>
        <td bgcolor="#eeeeee">ファイル</td>
        <td bgcolor="#ffffff"><input type="file" name="ufile" size="60"></td>
        <td bgcolor="#ffffff"><input type="hidden" name="mode" value="step2"><input type="submit" value=" インポート "></td>
    </tr>
</table>
</form>
__HTML__;

        return $this->getHtmlTop() . $html;
    }

    //step2 エラーチェックの結果表示
    public function step2()
    {
        //送信されたデータの取り込み
        $file = $_FILES["ufile"]["tmp_name"];
        if ($file == "") {
            $this->addErrorMessage("ファイルが指定されていません。");

            return $this->step1();
        } elseif (!is_uploaded_file($file)) { //アップロードされたファイルかチェック
            $this->addErrorMessage("不正なデータ送信です。");

            return $this->step1();
        }
        /*********************************************************************/
        //アップロードされたファイルを開く

        convertFile($file);
        setlocale(LC_ALL, 'ja_JP.eucJP');
        $fp = fopen($file, "r");
        if (!$fp) {
            $this->addErrorMessage("送信されたデータファイルが開けませんでした。");

            return $this->step1();
        }
        $filesize = filesize($file);
        $line = 0;
        $errors = array ();
        while (($data = fgetcsv($fp, $filesize, ",")) !== FALSE) {
            //ヘッダを飛ばす処理（できればフォーマット依存にしたい）
            if (++ $line == 1)
                continue;

            if ($error = $this->getError($line, $data)) {
                $errors[] = $error;
            } else {
                $tmpdata[] = $data;
            }
        }
        $errorcount = count($errors);
        $count = count($tmpdata);
        fclose($fp);
        unlink($file);

        //セッション変数に保存
        $loader = $this->getCacheLoader();
        $load_id = $loader->saveCache($tmpdata);
        //頭から五行分を表示
        //	$sName = regcsv_htmlEncode($file);

        if ($errors && $this->error_mode == 'const') {//エラーが一件でもあったら中止
            $errorm = '<br><br>以下のエラーがあります。<br><br>';
            $errorm .= '<blockquote>' . implode('<br>', $errors) . '</blockquote>';
            $PHP_SELF = $this->getPhpSelf();
            $html =<<<HTML
<form action="{$PHP_SELF}" method="POST" style="display:inline" onsubmit="return this.flag?false:this.flag=true;">
<br>
エラー行は <b>{$errorcount}</b> 行ありました。<br>
正常行 <b>{$count}</b> 行です。<br><br>
</form>
<form action="{$self}" style="display:inline" method="post">
    <input type="submit" name="mode:default" value="戻る">
</form>
{$errorm}
HTML;

            return $this->getHtmlTop() . $html;
        }

        if ($errors) {
            $_SESSION['import_page']['error'] = $errors;
            $errorm = '<br><br>以下のエラーがあります。<br>このままインポートしますと、エラー行は無視されます。<br><br>';
            $errorm .= '<blockquote>' . implode('<br>', $errors) . '</blockquote>';
        } else {
            $_SESSION['import_page']['error'] = null;
        }

        $PHP_SELF = $this->getPhpSelf();
        $html =<<<HTML
<form action="{$PHP_SELF}" method="POST" style="display:inline" onsubmit="return this.flag?false:this.flag=true;">
<br>
エラー行は <b>{$errorcount}</b> 行ありました。<br>
正常行 <b>{$count}</b> 行です。<br><br>
インポートをおこないますか?<br><br>
<input type="hidden" name="mode" value="step3">
<input type="submit" name="mode:import" value="はい">
<input type="hidden" name='load_id' value="{$load_id}">
</form>
<form action="{$self}" style="display:inline" method="post">
    <input type="submit" name="mode:default" value="いいえ">
</form>
{$errorm}
HTML;

        return $this->getHtmlTop() . $html;
    }

    //step3 インポート
    public function step3()
    {
        $loader = $this->getCacheLoader();
        $datas = $loader->loadCache($_POST['load_id']);
        if (!$datas)
            $datas = array ();

        /**********************************************/
        $this->beforeImport();
        foreach ($datas as $data) {
            $this->import($data);
        }
        $this->afterImport();
        /**********************************************/

        $count = count($datas);
        if ($_SESSION['import_page']['error']) {
            $error = '以下のエラーがあります。<br>エラー行以外をインポートしました。<br><br><blockquote>' .
            implode('<br>', $_SESSION['import_page']['error']) . '</blockquote>';
        }

        $html =<<<HTML
<h2>{$count}行インポートしました。</h2><br><br>
{$error}
HTML;

        return $this->getHtmlTop() . $html;
    }

    public function getCacheLoader()
    {
        if (!$this->global_upload_cache_loader) {
            $this->global_upload_cache_loader = new UploadCacheLoader();
        }

        return $this->global_upload_cache_loader;
    }

    /**
     * 1行分import処理する。
     * 継承先で実装する。
     */
    public function import($data)
    {
        print "import 未実装<br>;";
    }

    /**
     * 1行分のエラーを返す。
     * 継承先で実装する
     */
    public function getError($line, $data)
    {
        print "getError 未実装;";
    }

    public function beforeImport()
    {
        FDB :: begin();
    }

    public function afterImport()
    {
        if (!THISPAGE_NO_INSERT)
            FDB :: commit();
    }
}
class UploadCacheLoader
{
    public function loadCache($id)
    {
        $data = temp_read($id, true);
        if (!$data) {
            echo "キャッシュの読み込みに失敗しました。";
            exit;
        }

        return unserialize($data);
    }

    public function saveCache($data)
    {
        return temp_write(serialize($data));
    }
}
