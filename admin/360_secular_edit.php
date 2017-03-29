<?php

//define('DEBUG',1 );
define("DIR_ROOT", "../");
//必須require
require_once (DIR_ROOT . "crm_define.php");
require_once (DIR_LIB . 'CbaseEncoding.php');
//サポート
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseDataEditor.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
define('FFORM_ESCAPE', 1);
require_once (DIR_LIB . 'CbaseFForm.php');

//データライブラリ
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
encodeWebAll();

session_start();
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'SecularApp.php');
Check_AuthMng(basename(__FILE__));

class ThisAdapter extends DataEditAdapter
{
    /**
     * ◆abstract
     * @return array このデータで使えるカラム名=>名称のリストを返す
     */
    public function setupColumns()
    {
        return limitColumn(array (
            'id'   => '',
            'name' => '名称',
        ));
    }

    /**
     * ◆abstruct
     * 保存用のデータが送られてくる
     * @param  array $data 保存用のデータ
     * @return bool  保存に成功すればtrue
     */
    public function save($data)
    {
        return $this->update($data);
    }

    public function update($data)
    {
        FDB::begin();

        $saveList = array('name' => FDB::escape($data['name']));
        $rs = FDB::update(T_SECULARS, $saveList, 'where id = '.FDB::escape($data['id']));
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            FDB::commit();

            return true;
        }
    }

    public function getSaveValueCallback($data, $col)
    {
        return $data[$col];
    }

    /**
     * ◆abstruct
     * 列ごとに作成したフォームを返す
     * @param  array  $data 各列の初期値の配列
     * @param  string $col  処理対象列
     * @return string 作成したフォーム
     */
    public function getFormCallback($data, $col)
    {
        $value = $data[$col];
        switch ($col) {
            case 'id':
                return FForm :: hidden($col, $value);
            default :
                return FForm :: text($col, $value,null,'style="width:230px"');
        }

        return $data[$col];

    }
    /**
     * ◆virtual
     * 列ごとにエラーチェックを行う(nullでエラーなし)
     * @param  array  $data 入力値の配列
     * @param  string $col  処理対象列
     * @return string この列に対するエラー文言
     */
    public function validateCallback($data, $col)
    {
        $value = $data[$col];
        switch ($col) {
            case 'name':
                if (mb_strlen($value) > 10) {
                    return '10文字以内でご入力ください';
                }
                break;
            default:
                break;
        }

        return null;
    }

    /**
     * ◆virtual
     * 列ごとに画面表示用の値への変換を行う
     * @param  array  $data 入力値の配列
     * @param  string $col  処理対象列
     * @return string この列の表示値
     */
    public function getFormatValueCallback($data, $col)
    {
        $val = $data[$col];
        switch ($col) {
            case 'id':
                return "";
            default :
                return html_escape($val);
        }
    }
}

class ThisDesign extends DataEditDesign
{

}

class ThisDataEditor extends DataEditor
{
    /**
     * Postを取得する。
     * user_serachからきた場合はnullを返しとく
     */
    public function getPost()
    {
        if($_POST['mode']=='edit')
            return array();
        return $_POST;
    }

    //function runConfirmView() {}
    //多重投稿禁止機能は使わない
    public function validateSession() {}
}

$edit = new ThisAdapter();

$design = new ThisDesign();

$editor = new ThisDataEditor($edit, $design);

$secularApp = new SecularApp();
$secularApp->connectSecularDatabase();

if ($_POST['mode'] == 'edit' && $_POST['id'] && $_POST['hash']) {
    $data = FDB::select1(T_SECULARS,'*','where id = '.FDB::escape($_POST['id']));
    if (is_false($data) || $_POST['hash'] != $data['hash']) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }
    $editor->setTarget($data);
}

$html = $editor->run();

$self = getPHP_SELF();
$DIR_IMG = DIR_IMG;
$html =<<<__HTML__
<form action="{$self}" method="post">
{$html}
</form>
__HTML__;

$secularApp->connectDefaultDatabase();
$objHtml = new MreAdminHtml("経年比較情報 編集", "", false, true);
echo $objHtml->getMainHtml($html);
exit;
