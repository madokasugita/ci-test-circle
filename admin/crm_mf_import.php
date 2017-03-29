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
require_once (DIR_LIB . '360_Setting.php');
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', 'メールひな型インポート');
/****************************************************************************************************************************/

class ThisImportModel extends ImportModel360
{
    /**
     * オブジェクトをクラス変数にセット
     *
     * @param $class String クラス名
     * @return $this Object
     */
    public function setObject($class)
    {
        $this->{$class} = new $class();

        return $this;
    }
    public function importLine($line_no, $data) //override
    {
        $array = $this->getFormatData($data);
        if ($array['mfid'])
            $rs = $this->update($array);
        else
            $rs = $this->insert($array);
        if (is_false($rs))
            return "{$line_no}行目:エラー";
    }

    public function getFormatData($data)
    {
        $array['mfid'] = $data[0];
        $array['name'] = $data[1];
        $array['title'] = $data[2];
        $array['body'] = $data[3];
        $array['title_1'] = $data[4];
        $array['body_1'] = $data[5];
        $array['title_2'] = $data[6];
        $array['body_2'] = $data[7];
        $array['title_3'] = $data[8];
        $array['body_3'] = $data[9];
        $array['title_4'] = $data[10];
        $array['body_4'] = $data[11];

        if ($this->_360_Setting->htmlMail()) {
            $array['body'] = mb_ereg_replace("[\n|\r|(\r\n)]", "<br>", $array['body']);
            $array['body_1'] = mb_ereg_replace("[\n|\r|(\r\n)]", "<br>", $array['body_1']);
            $array['body_2'] = mb_ereg_replace("[\n|\r|(\r\n)]", "<br>", $array['body_2']);
            $array['body_3'] = mb_ereg_replace("[\n|\r|(\r\n)]", "<br>", $array['body_3']);
            $array['body_4'] = mb_ereg_replace("[\n|\r|(\r\n)]", "<br>", $array['body_4']);
        }

        return $array;
    }

    private function update($data)
    {
        return Save_MailFormat("update", $data);
    }

    private function insert($data)
    {
        //$data['mfid'] = FDB::getNextVal("mfid");
        return Save_MailFormat("new", $data);
    }

    public function getErrors($line_no, $data) //override
    {
        global $GLOBAL_DIV1_LIST, $GLOBAL_DIV2_LIST, $GLOBAL_DIV3_LIST;
        $array = $this->getFormatData($data);
        if(is_good($array['mfid']) && !ctype_digit($array['mfid']))
            $error[] = "{$line_no}行目:IDは数値で入力してください";

        return $error;
    }
}

class ThisImportDesign extends ImportDesign360
{
//	function getFirstViewMessage()
//	{
//		return '<div style="margin-top:30px"><a href="samplecsv/muser.csv">サンプルCSVダウンロード</a></div>';
//	}
}

$ThisImportModel = new ThisImportModel();
$ThisImportModel->setObject('_360_Setting');
$main = new Importer360($ThisImportModel, new ThisImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;
