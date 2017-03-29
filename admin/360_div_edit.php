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
            'serial_no'=>'',
            'div1'=>'####div_name_1####コード',
            'div1_name'=>'####div_name_1####表示名(日本語)',
            'div1_sort'=>'####div_name_1####並び順',
            'div2'=>'####div_name_2####コード',
            'div2_name'=>'####div_name_2####表示名(日本語)',
            'div2_sort'=>'####div_name_2####並び順',
            'div3'=>'####div_name_3####コード',
            'div3_name'=>'####div_name_3####表示名(日本語)',
            'div3_sort'=>'####div_name_3####並び順',
            'div1_name_1'=>'####div_name_1####表示名(English)',
            'div2_name_1'=>'####div_name_2####表示名(English)',
            'div3_name_1'=>'####div_name_3####表示名(English)',
            'div1_name_2'=>'####div_name_1####表示名(繁体字)',
            'div2_name_2'=>'####div_name_2####表示名(繁体字)',
            'div3_name_2'=>'####div_name_3####表示名(繁体字)',
            'div1_name_3'=>'####div_name_1####表示名(簡体字)',
            'div2_name_3'=>'####div_name_2####表示名(簡体字)',
            'div3_name_3'=>'####div_name_3####表示名(簡体字)',
            'div1_name_4'=>'####div_name_1####表示名(韓国語)',
            'div2_name_4'=>'####div_name_2####表示名(韓国語)',
            'div3_name_4'=>'####div_name_3####表示名(韓国語)',
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
        if($data['serial_no'] == 'new')

            return $this->insert($data);
        else
            return $this->update($data);
    }

    public function insert($data)
    {
        FDB::begin();
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        unset($array['serial_no']);
        $rs = FDB::insert(T_DIV,$array);
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            clearDivCache();
            FDB::commit();

            return true;
        }
    }

    public function update($data)
    {
        FDB::begin();
        $divs = unserialize($data['serial_no']);
        if(is_false($divs))

            return false;

        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        unset($array['serial_no']);

        $where = array();
        $where[] = "div1=".FDB::escape($divs[0]);
        $where[] = "div2=".FDB::escape($divs[1]);
        $where[] = "div3=".FDB::escape($divs[2]);
        $rs = FDB::update(T_DIV,$array,'where '.implode(' and ', $where));
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            clearDivCache();
            FDB::commit();

            return true;
        }
    }

    public function getSaveValueCallback($data, $col)
    {
        if(!$data['div1'])
            $data['div1'] = md5($data['div1_name']);

        if(!$data['div2'])
            $data['div2'] = $data['div1'].'_'.md5($data['div2_name']);

        if(!$data['div3'])
            $data['div3'] = $data['div2'].'_'.md5($data['div3_name']);

        if(!preg_match('/^'.$data['div1'].'_/',$data['div2']))
            $data['div2'] = $data['div1'].'_'.$data['div2'];

        if(!preg_match('/^'.$data['div2'].'_/',$data['div3']))
            $data['div3'] = $data['div2'].'_'.$data['div3'];

        if(!$data['div1_sort'])
            $data['div1_sort'] = 0;
        if(!$data['div2_sort'])
            $data['div2_sort'] = 0;
        if(!$data['div3_sort'])
            $data['div3_sort'] = 0;

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
        global $_360_sheet_type;
        $value = $data[$col];
        switch ($col) {
            case 'serial_no':
                return FForm :: hidden($col, $value);

            case 'div1':
            case 'div2':
            case 'div3':
                return FForm :: text($col, preg_replace('/^.*_/','',$value),null,'style="width:230px"');

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
            case 'div1_name':
                if (FCheck::isBlank($value))
                    return '必須事項です';
                break;
            case 'div1_sort':
            case 'div2_sort':
            case 'div3_sort':
                if ($value && preg_match("/^[0-9]+$/", $value)!=1)
                    return '数値項目です';
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
            case 'serial_no':
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

if ($_POST['mode'] == 'edit' && $_POST['serial_no']) {
    $divs = unserialize($_POST['serial_no']);
    if ($_POST['hash'] != getHash360($_POST['serial_no']) || is_false($divs)) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }
    $where = array();
    $where[] = "div1=".FDB::escape($divs[0]);
    $where[] = "div2=".FDB::escape($divs[1]);
    $where[] = "div3=".FDB::escape($divs[2]);
    $data = FDB::select1(T_DIV,'*','where '.implode(' and ', $where));
    $data['serial_no'] = $_POST['serial_no'];
    $editor->setTarget($data);
}

if ($_GET['mode'] == 'new') {
    $data=array();
    $data['serial_no'] = 'new';
    $editor->setTarget($data);
}

if($data['serial_no'] == 'new')
    $message = "組織情報 新規登録";
else
    $message = "組織情報 編集";

$html = $editor->run();

$self = getPHP_SELF();
$DIR_IMG = DIR_IMG;
$html =<<<__HTML__
<form action="{$self}" method="post">
{$html}
</form>
__HTML__;

$objHtml = new MreAdminHtml($message, "", false, true);
echo $objHtml->getMainHtml($html);
exit;
