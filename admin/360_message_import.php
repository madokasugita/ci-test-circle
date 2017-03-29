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
require_once (DIR_LIB . '360_Importer.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFForm.php');

session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', '文言一括更新');
/****************************************************************************************************************************/

class ThisImportModel extends ImportModel360
{
    public function importLine($line_no, $data)
    {
        $array['mkey'] = $data[0];
        $array['place1'] = $data[1];
        $array['place2'] = $data[2];
        $array['type'] = $data[3];
        $array['name'] = $data[4];

        $i = 5;
        foreach ($GLOBALS['_360_language'] as $k => $v) {
            $array['body_'.$k] = $data[$i];
            $i++;
        }
        $array['memo'] = $data[$i];
        if (!$this->message[$array['mkey']]) {
            FDB :: insert(T_MESSAGE,  FDB :: escapeArray($array));
            $this->message[$array['mkey']] = true;
        } else {
            FDB :: update(T_MESSAGE,  FDB :: escapeArray($array),'where mkey = '.FDB::escape($array['mkey']));
        }

        return '';
    }
    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data)
    {

        $array['mkey'] = $data[0];
        $array['place1'] = $data[1];
        $array['place2'] = $data[2];
        $array['type'] = $data[3];
        $array['name'] = $data[4];

        $i = 5;
        foreach ($GLOBALS['_360_language'] as $k => $v) {
            $array['body_'.$k] = $data[$i];
            $i++;
        }
        $array['memo'] = $data[$i];

        $error = array();
        if (!$array['mkey']) {
            $error[] = $line_no."行目:キーが不正です。";
        }

        return $error;
    }

    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport()
    {
        foreach (FDB :: select(T_MESSAGE,'mkey') as $tmp) {
            $this->message[$tmp['mkey']] = true;
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

}

class ThisImportDesign extends ImportDesign360
{
    public function getFirstViewMessage()
    {
        return '<div style="margin-top:30px"><a href="360_message_view.php?csvdownload=1&'.getSID().'">インポート済みデータをダウンロード</a></div>';
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
