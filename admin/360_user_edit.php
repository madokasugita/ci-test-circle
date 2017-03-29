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
        $array['serial_no'] = "";

        return array_merge($array,getColmunLabel('user_edit'));
    }

    /**
     * ◆abstruct
     * 保存用のデータが送られてくる
     * @param  array $data 保存用のデータ
     * @return bool  保存に成功すればtrue
     */
    public function save($data)
    {
        if(!$data['mflag'])
            $data['sheet_type'] = 0;
        if($data['serial_no'] == 'new')

            return $this->insert($data);
        else
            return $this->update($data);
    }

    public function insert($data)
    {
        $data['serial_no'] = getUniqueIdWithTable(T_UNIQUE_SERIAL , "serial_no", 8);
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        $array['pw'] = FDB::escape(getPwHash(get360RandomPw()));
        $rs = FDB::insert(T_USER_MST,$array);
        if(is_false($rs))

            return false;
        return true;
    }

    public function update($data)
    {
        $before_data = FDB::select1(T_USER_MST,'*','where serial_no = '.FDB::escape($data['serial_no']));
        FDB::begin();
        if ($data['mflag'] && $before_data['sheet_type'] != $data['sheet_type']) {
            $where = 'where target = ' . FDB :: escape($data['serial_no']);
            FDB :: sql("delete from subevent_data where event_data_id in (select event_data_id from event_data {$where})",true);
            FDB :: delete(T_EVENT_DATA, $where);
            FDB :: delete(T_BACKUP_DATA, $where);
        }

        if ($before_data['mflag'] && !$data['mflag']) {
            $where = 'where target = ' . FDB :: escape($data['serial_no']);
            FDB :: sql("delete from subevent_data where event_data_id in (select event_data_id from event_data {$where})",true);
            FDB :: delete(T_EVENT_DATA, $where);
            FDB :: delete(T_BACKUP_DATA, $where);
            FDB :: delete(T_USER_RELATION,"WHERE uid_a = ".FDB::escape($before_data['uid']));
        }

        if ($before_data['uid'] != $data['uid']) {
            FDB::update(T_USER_RELATION,array('uid_b'=>FDB::escape($data['uid'])),'where uid_b = '.FDB::escape($before_data['uid']));
            FDB::update(T_USER_RELATION,array('uid_a'=>FDB::escape($data['uid'])),'where uid_a = '.FDB::escape($before_data['uid']));
        }

        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        $rs = FDB::update(T_USER_MST,$array,'where serial_no = '.$array['serial_no']);

        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            if (LOG_MODE_PHP>=1) {
                $ip = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
                error_log(date('Y-m-d H:i:s')."\t".$ip."\t".$_SESSION['muid']."\t".$_SERVER['SCRIPT_NAME']."\tedit=>{$array['serial_no']}"."\n", 3, LOG_FILE_PHP);
            }
            FDB::commit();

            return true;
        }
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
            case "div1" :
            case "div2" :
            case "div3" :
                $div["default"] = "";
                foreach (getDivList($col) as $k => $v) {
                    $div[$k] = $v;
                }
                if ($col == 'div1')
                    return FForm :: replaceSelected(FForm :: select($col, $div, "style='width:230px' onChange='reduce_options(\"id_div1\",\"id_div2\");reduce_options(\"id_div2\",\"id_div3\");' id='id_div1'"), $data[$col]);

                if ($col == 'div2')
                    return FForm :: replaceSelected(FForm :: select($col, $div, "style='width:230px' onChange='reduce_options(\"id_div2\",\"id_div3\");' id='id_div2'"), $data[$col]);

                return FForm :: replaceSelected(FForm :: select($col, $div, "style='width:230px' id='id_div3'"), $data[$col]);
            case 'serial_no':
                return FForm :: hidden($col, $value);

            case "lang_flag" :
                if ($data[$col] === null)
                    $data[$col] = '0';
                $radiolist = implode('', FForm :: radiolist('lang_flag', array (
                    '0' => 'なし',
                    '1' => 'あり'
                ),''));

                return FForm :: replaceChecked($radiolist, $data[$col]);

            case "mflag" :
                if ($data[$col] === null)
                    $data[$col] = 'all';
                $radiolist = implode('', FForm :: radiolist('mflag', array (
                    '1' => '対象者',
                    '0' => '非対象者'
                ),'onclick="checkMflag()"'));

                return FForm :: replaceChecked($radiolist, $data[$col]);
            case "sheet_type" :

                $tmp = array ();
                foreach ($_360_sheet_type as $k => $v)
                    $tmp[$k] = $v;

                return FForm :: replaceSelected(FForm :: select($col, $tmp), $data[$col]);
            case "lang_type" :

                $tmp = array ();
                foreach ($GLOBALS['_360_language'] as $k => $v)
                    $tmp[$k] = $v;

                return FForm :: replaceSelected(FForm :: select($col, $tmp), $data[$col]);

            case "test_flag":
                if ($data[$col] === null)
                    $data[$col] = '0';
                $radiolist = implode('', FForm :: radiolist('test_flag', array (
                    '0' => '通常ユーザ',
                    '1' => 'テストユーザ',
                )));

                return FForm :: replaceChecked($radiolist, $data[$col]);
            case "ext1":
            case "ext2":
            case "ext3":
            case "ext4":
            case "ext5":
            case "ext6":
            case "ext7":
            case "ext8":
            case "ext9":
            case "ext10":
                return FForm :: textarea($col, $value, 'style="width:230px"');
            case "send_mail_flag":
                if ($data[$col] === null)
                    $data[$col] = '0';
                $radiolist = implode('', FForm :: radiolist('send_mail_flag', array (
                    '0' => '送信',
                    '1' => '停止',
                )));
                return FForm :: replaceChecked($radiolist, $data[$col]);
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
            case 'email' :
                if (FCheck::isBlank($value))
                    return '必須事項です';
                if (!FCheck::isEmail($value))
                    return '書式が不正です';
                break;
            case 'uid':
                if (FCheck::isBlank($value))
                    return '必須事項です';

                if(FDB::is_exist(T_USER_MST,'where uid ='.FDB::escape($value).' and serial_no <> '.FDB::escape($data['serial_no'])))

                    return '指定したIDのユーザが他に存在します。';

                if(!ereg(EREG_LOGIN_ID,$value))

                    return "書式がおかしいです。";

                break;
            case 'name':
                if (FCheck::isBlank($value))
                    return '必須事項です';
                break;

            case 'mflag':
                // 人数制限がない場合
                if (!$GLOBALS['Setting']->limitUserNumberValid())
                    break;

                // 変更前の状態取得
                $mflagCount = 0;
                $before_data = FDB::select1(T_USER_MST,'*','where serial_no = '.FDB::escape($_POST['serial_no']));
                $before_mflag = ($before_data)? $before_data['mflag'] : 0;

                // 対象者にする場合
                if(!is_zero($value) && is_zero($before_mflag))
                    $mflagCount++;

                // 評価者にする場合
                if(is_zero($value) && !is_zero($before_mflag))
                    $mflagCount--;

                if(getTargetUserCount() + $mflagCount > LIMIT_USER_NUMBER)
                    return '登録制限数を超えています';
                break;

            case 'test_flag':
                // 人数制限がない場合
                if (!$GLOBALS['Setting']->limitUserNumberValid())
                    break;

                if (!is_zero($value) && !is_zero($data['mflag']))
                    return '対象者はテストユーザに設定できません';
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
            default :
                return get360Value($data, $col);
        }
    }
}

class ThisDesign extends DataEditDesign
{
    /**
     * ◆virtual
     * 編集画面の表示を行う
     * @return string html
     */
    public function getEditView($show, $error = array ())
    {

        $html=<<<__HTML__
<table class="searchbox">
__HTML__;
        foreach (ThisAdapter :: setupColumns() as $colkey => $colval) {
            if (!$colval) {
                $html.=$show[$colkey];
                continue;
            }
            $html .=<<<__HTML__
<tr>
<th>{$colval}</th>
<td class="tr2">
{$show[$colkey]}{$error[$colkey]}
</td>
</tr>
__HTML__;

        }

        $html .=<<<__HTML__
</table>
{$show['previous']}
{$show['next']}
__HTML__;

        return $html.getHtmlReduceSelect();
    }

    /**
     * ◆virtual
     * 確認画面の表示を行う
     * @return string html
    */
    public function getConfirmView($show, $data='')
    {
        if ($_POST && $_POST['serial_no'] != 'new') {
            $before_data = FDB::select1(T_USER_MST,'*','where serial_no = '.FDB::escape($_POST['serial_no']));
            $escaped_uid = escapeHtml($_POST['uid']);
            if ($_POST['mflag'] && $before_data['sheet_type'] != $_POST['sheet_type']) {
                $bsheetname = getSheetTypeNameById($before_data['sheet_type']);
                $sheetname = getSheetTypeNameById($_POST['sheet_type']);
                $message=<<<HTML
<br>
<br>
<br>
<div style="border:dotted 1px black;padding:20px;width:500px;background-color:#ffffcc">
シートタイプを<b>{$bsheetname}</b>から<b>{$sheetname}</b>に変更しようとしています。<br>
このまま変更を行なうと、{$escaped_uid}に対しての<b>{$bsheetname}</b>の「回答データ」は<span style="color:red;font-weight:bold">削除</span>されます。
</div>
HTML;
            }

            if ($before_data['mflag'] && !$_POST['mflag']) {
                $message=<<<HTML
<br>
<br>
<br>
<div style="border:dotted 1px black;padding:20px;width:500px;background-color:#ffffcc">
対象者フラグを<b>対象者</b>から<b>非対象者</b>に変更しようとしています。<br>
このまま変更を行なうと、{$escaped_uid}に対しての「回答データ」及び「回答者選定、承認、参照紐付け」は<span style="color:red;font-weight:bold">削除</span>されます。
</div>
HTML;
            }




        }

        return $this->getEditView($show).$message;
    }
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
    /**
     * 確認画面の処理と表示を行う
     * @return string html
     */
    //function runConfirmView() {}
    //多重投稿禁止機能は使わない
    public function validateSession() {}
}

$edit = new ThisAdapter();

$design = new ThisDesign();

$editor = new ThisDataEditor($edit, $design);

if ($_POST['mode'] == 'edit' && $_POST['serial_no']) {
    if ($_POST['hash'] != getHash360($_POST['serial_no'])) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }
    $editor->setTarget(FDB::select1(T_USER_MST,'*','where serial_no = '.FDB::escape($_POST['serial_no'])));
}

if ($_GET['mode'] == 'new') {
    $data=array();
    $data['mflag'] = '0';
    $data['serial_no'] = 'new';
    $editor->setTarget($data);
}

$serial_no = $data['serial_no'] ? $data['serial_no'] : $_POST['serial_no'];
if($serial_no == 'new')
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

<script>
function checkMflag()
{
    if(!document.getElementsByName('mflag')[0])

        return;
    if(document.getElementsByName('mflag')[0].type=="hidden")

        return;

    if (document.getElementsByName('mflag')[0].checked) {
        document.getElementsByName('sheet_type')[0].disabled = false;
    } else {
        document.getElementsByName('sheet_type')[0].disabled = true;
    }
}
checkMflag();
</script>
__HTML__;

$objHtml = new MreAdminHtml("ユーザ情報".$message, "", false, true);
echo $objHtml->getMainHtml($html);
exit;
