<?php
// define('DEBUG',1 );
define("NOT_CONVERT", 1);
define("DIR_ROOT", "../");

require_once (DIR_ROOT . "crm_define.php");
require_once (DIR_LIB . 'CbaseEncoding.php');

require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseDataEditor.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
define('FFORM_ESCAPE', 1);
require_once (DIR_LIB . 'CbaseFForm.php');

require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
encodeWebAll();

session_start();
require_once (DIR_LIB . 'CbaseFManage.php');
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
            'user_type_id' => "ID",
            'name' => "名称",
            'admin_name' => "管理画面名称",
            'utype' => "タイプ"
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
        if($data['user_type_id'] == 'new')

            return $this->insert($data);
        else
            return $this->update($data);
    }

    public function insert($data)
    {
        FDB::begin();
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = $data[$key];
        }

        $rs = array();
        if(is_false($max = FDB::select1(T_USER_TYPE, "MAX(user_type_id) as max", "WHERE utype = 1"))
                || $max['max'] + 1 == ADMIT_USER_TYPE)
        {
            FDB::rollback();

            return false;
        }
        $array['user_type_id'] = $max['max'] + 1;
        $array['utype'] = 1;//他者のみに限定

        $rs[] = FDB::insert(T_USER_TYPE, FDB::escapeArray($array));

        foreach ($GLOBALS['_360_sheet_type'] as $sheet_type => $name) {
            $event = array();
            $event['evid'] = $sheet_type*100 + $array['user_type_id'];
            $event['rid'] = 'rid'.sprintf("%05d",$event['evid']);
            $rs[] = FDB::insert(T_EVENT, FDB::escapeArray($event));
        }

        $title_message = FDB::select1(T_MESSAGE, "*", 'WHERE mkey = '.FDB::escape("mypage_input".$array['user_type_id']."_title"));
        if (is_void($title_message)) {
            $message = array();
            $message['mkey'] = "mypage_input".$array['user_type_id']."_title";
            $message['place1'] = $message['place2'] = "マイページ";
            foreach ($GLOBALS['_360_language'] as $k => $v) {
                $message['body_'.$k] = "他者回答 ".$array['user_type_id'];
            }
            $rs[] = FDB::insert(T_MESSAGE, FDB::escapeArray($message));
        }

        $input_message = FDB::select1(T_MESSAGE, "*", 'WHERE mkey = '.FDB::escape("mypage_input".$array['user_type_id']));
        if (is_void($input_message)) {
            $message = array();
            $message['mkey'] = "mypage_input".$array['user_type_id'];
            $message['place1'] = $message['place2'] = "マイページ";
            foreach ($GLOBALS['_360_language'] as $k => $v) {
                $message['body_'.$k] = '$targetさんへの回答';
            }
            $rs[] = FDB::insert(T_MESSAGE, FDB::escapeArray($message));
        }

        if (in_array(false, $rs, true)) {
            FDB::rollback();

            return false;
        }

        FDB::commit();
        clearSheetCache();
        clearUserTypeCache();
        clearMessageCache();

        return true;
    }

    public function update($data)
    {
        FDB::begin();
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        $escape_user_type_id = $array['user_type_id'];
        unset($array['user_type_id']);
        unset($array['utype']);
        $rs = FDB::update(T_USER_TYPE, $array, "WHERE user_type_id = ".$escape_user_type_id);
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            clearUserTypeCache();
            FDB::commit();

            return true;
        }
    }

// 	function getSaveValueCallback($data, $col) {}

    /**
     * ◆abstruct
     * 列ごとに作成したフォームを返す
     * @param  array  $data 各列の初期値の配列
     * @param  string $col  処理対象列
     * @return string 作成したフォーム
     */
    public function getFormCallback($data, $col)
    {
        global $_360_sheet_type;
        $value = $data[$col];
        switch ($col) {
            case 'user_type_id':
                return $value.FForm :: hidden($col, $value);
            case 'utype':
                return $GLOBALS['user_type_utype'][$value].FForm :: hidden($col, $value);
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
                if (FCheck::isBlank($value))
                    return '必須事項です';
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
        global $_360_sheet_type;
        $val = $data[$col];
        switch ($col) {
            case 'utype':
                return $GLOBALS['user_type_utype'][$data[$col]];
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
     * listからきた場合はnullを返しとく
     */
    public function getPost()
    {
        if(is_good($_POST['mode']))

            return array();
        return $_POST;
    }

    //function runConfirmView() {}
    //多重投稿禁止機能は使わない
    public function validateSession() {}
}

$editor = new ThisDataEditor(new ThisAdapter(), new ThisDesign());

if ($_POST['mode'] == 'edit' && is_good($_POST['user_type_id'])) {
    if ($_POST['hash'] != getHash360($_POST['user_type_id'])) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }

    $data = FDB::select1(T_USER_TYPE,'*','where user_type_id = '.FDB::escape($_POST['user_type_id']));
    $editor->setTarget($data);
}

if ($_POST['mode'] == 'new') {
    $data=array();
    $data['user_type_id'] = 'new';
    $data['utype'] = '1';
    $editor->setTarget($data);
}

if($data['user_type_id'] == 'new')
    $message = "新規登録";
else
    $message = "編集";

$html = $editor->run();

$self = getPHP_SELF();
$DIR_IMG = DIR_IMG;
$html =<<<__HTML__
<form action="{$self}" method="post">
{$html}
</form>
__HTML__;

$objHtml = new MreAdminHtml("ユーザータイプ".$message, "", false, true);
echo $objHtml->getMainHtml($html);
exit;
