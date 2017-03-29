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
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . '360_Function.php');

encodeWebAll();

session_start();
require_once (DIR_LIB . 'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

/**********************/

class ThisAdapter extends DataEditAdapter
{
    /**
     * ◆virtual
     * 確認画面にて、登録画面へと受け渡す値を返す
     * 基本的にはPOSTされる値をそのまま渡すようにしており、特殊なケースのみ書き換える
     * @param  array  $data 各列の初期値の配列
     * @param  string $col  処理対象列
     * @return string 作成したフォームのhtml
     */
    public function getHiddenValue($data, $col, $colname = '')
    {
        $colname = $colname ? $colname : $col;
        if (is_array($data[$col])) {
            $form = '';
            foreach ($data[$col] as $k => $v) {
                $form .= $this->getHiddenValue($data[$col], $k, $colname . '[' . $k . ']');
            }

            return $form;
        }

        return $this->makeHiddenValueTag($colname, str_replace('&','&amp;',$data[$col]));
    }
    public function setupColumnsCSV($column)
    {
        $this->columnsCSV = $column;
    }

    /**
     * ◆abstract
     * @return array このデータで使えるカラム名=>名称のリストを返す
     */
    public function setupColumns()
    {
        $c = $this->columnsCSV;

        return $c;
    }

    /**
     * ◆abstruct
     * 保存用のデータが送られてくる
     * @param  array $data 保存用のデータ
     * @return bool  保存に成功すればtrue
     */
    public function save($data)
    {
        $array = array();
        FDB::begin();
        foreach ($this->columnsCSV as $k => $v) {
            $savedata = "";
            switch ($k) {
                case '_360_language':
                    $savedata = implode(",", $data[$k]);
                    break;
                case "OUT_OF_ANSWER_PERIOD":
                    $savedata = date("Y/m/d H:i:s", strtotime($data[$k]));
                    break;
                default:
                    $savedata = $data[$k];
                    break;
            }

            $res = FDB::update(T_SETTING, array("value" => FDB::escape($savedata)), "WHERE define = ".FDB::escape($k));
            if (is_false($res)) {
                FDB::rollback();

                return false;
            }
        }
        FDB::commit();
        clearSettingCache();

        return true;
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
        global $langs;
        $value = $data[$col];

        switch ($col) {
            case '_360_language':
                $list = implode("", FForm :: checkboxListDef($col."[]",$langs['_360_language'], $value,'style="width:10px"'));
                break;
            default:
                if(!$this->choice[$col])

                    return FForm :: text($col, str_replace('&','&amp;',$value), null, 'style="width:430px"')."<br>".$this->default[$col];

                $array = array();
                foreach (explode(',',$this->choice[$col]) as $v) {
                    $array[$v] = $v;
                }
                $list = FForm :: selectDef($col,$array, $value,'style="width:430px"');
                break;
        }

        return $list."<br>".$this->default[$col];
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
            case "LIMIT_PW_MISS":
            case "_360_ANSWER_TIMEOUT":
            case "LIMIT_USER_NUMBER":
                if(!is_numeric($value))
                    return "数値以外が入力されています。";

                break;
            case "OUT_OF_ANSWER_PERIOD":
                if (!strtotime($value))
                    return '日付の書式が正しくありません';

                break;
            case "ADMIN_LOGIN_PERIOD":
                if (!strtotime($value) && !is_zero($value))
                    return '日付の書式が正しくありません';

                break;
            case "MAIL_SENDER0":
            case "MAIL_SENDER1":
            case "MAIL_SENDER2":
            case "MAIL_SENDER3":
            case "MAIL_SENDER4":
                if (FCheck::isBlank($value))
                    return '入力してください';

                if (!FCheck::isPermittedDomain($value))
                    return 'このアドレスは使用できません';

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
        global $langs;
        $val = $data[$col];
        switch ($col) {
            case '_360_language':
                $res = array();
                if (is_array($val))	foreach ($val as $v) {
                    $res[] = $langs['_360_language'][$v];
                }
                $val = implode(",", $res);
                break;
            case "OUT_OF_ANSWER_PERIOD":
                $val = date("Y/m/d H:i:s", strtotime($val));
                break;
            default :
                break;
        }

        return html_escape($val);
    }

    public function getEditValueCallback($data, $col)
    {
        $val = $data[$col];
        switch ($col) {
            case '_360_language':
                $val = explode(",", $val);
                break;
            default :
                break;
        }

        return $val;
    }
}

class ThisDesign extends DataEditDesign
{
    public function setAdapter($a)
    {
        $this->adapter = $a;
    }
    /**
     * ◆virtual
     * 編集画面の表示を行う
     * @return string html
     */
    public function getEditView($show, $error = array ())
    {

        $html =<<<__HTML__
<table class="cont" style="width:640px">
__HTML__;
        $trnum=0;
        foreach ($this->adapter->setupColumns() as $colkey => $colval) {
            if (!$colval) {
                $html .= $show[$colkey];
                continue;
            }
            ++$trnum;
            $html .=<<<__HTML__
<tr>
<td style="color:white" align="center" bgcolor="#3080BB">{$trnum}</td>
<th class="tr1">{$colval}</th>
<td class="tr2">
{$show[$colkey]}{$error[$colkey]}
</td>
</tr>

__HTML__;

        }

        $html .=<<<__HTML__
</table>
<br>
<div style="width:640px;text-align:center;">
{$show['previous']}
{$show['next']}
</div>
__HTML__;

        return $html . getHtmlReduceSelect();
    }
    /**
     * ◆virtual
     * 確認画面の表示を行う
     * @return string html
    */
    public function getConfirmView($show, $data = '')
    {
        return $this->getEditView($show);
    }

    /**
     * ◆virtual
     * 登録完了画面の表示を行う
     * @return string html
     */
    public function getCompleteView($show)
    {
        return<<<__HTML__
<span style="color:red;size:13px;font-weight:bold">完了しました</span>

<br><br>


__HTML__;
    }

    /**
     * ◆virtual
     * 文字列をエラー表示用タグで囲んで返す
     * @param  string $str 囲む文字列
     * @return string html
     */
    public function getErrorFormat($str)
    {
        return '<span style="color:#F00">' . $str . '</span>';
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
        if ($_POST['mode'] == 'edit')
            return array ();
        return $_POST;
    }
    /**
     * 確認画面の処理と表示を行う
     * @return string html
     */
    public function runConfirmView()
    {
        $post = $this->getPost();
        $error = $this->data->validate($post);
        if ($error) {
            $error = $this->formatErrorMessages($error);

            return $this->runEditView($post, $error);
        } else {
            $show = $this->data->getFormatValue($this->arrayEscape($post));

            $show['previous'] = $this->design->getPreviousButton('data_editor_mode:top');
            $show['next'] = $this->design->getRegisterButton('data_editor_mode:complete') . $this->getSessionHidden();

            return $this->design->getConfirmView($show, $post);
        }
    }
    //多重投稿禁止機能は使わない
    public function validateSession()
    {
    }
}

if ($_POST['export']) {
    $array = array();
    foreach (FDB::select(T_SETTING, "*") as $setting) {
        $tmp = array();
        $tmp['define'] = $setting['define'];
        $tmp['value'] = $setting['value'];
        $tmp['title'] = $setting['title'];
        $tmp['choice'] = $setting['choice'];
        $tmp['explain'] = $setting['explain'];
        $array[] = $tmp;
    }
    csv_download_utf8_tag($array, date("Y-m-d")."_".PROJECT_NAME."_setting.csv");
    exit;
}

$data = array ();
$columns = array ();
foreach (FDB::select(T_SETTING, "*") as $setting) {
    if(is_void($setting['define'])) continue;

    $data[$setting['define']] = $setting['value'];
    $columns[$setting['define']] = $setting['title'];
    $choice[$setting['define']] = $setting['choice'];
    $default[$setting['define']] = $setting['explain'];
}

$edit = new ThisAdapter();
$edit->setupColumnsCSV($columns);
$edit->choice = $choice;
$edit->default = $default;

$design = new ThisDesign();
$design->setAdapter($edit);

$editor = new ThisDataEditor($edit, $design);
$editor->setTarget($data);

$html = $editor->run();

$self = getPHP_SELF();
$html =<<<__HTML__
<form action="{$self}" method="post">
{$html}
</form>
__HTML__;

$objHtml = new MreAdminHtml("基本設定");
if(is_good($message))
    $objHtml->setMessage($message);

$SESSION_ID = SESSIONID;
$SID = session_id();
$objHtml->setSideHtml(D360::getSideBox("設定エクスポート",<<<__BOX__
<form action="{$self}" method="post">
<input type="hidden" name="{$SESSION_ID}" value="{$SID}">
<input type="submit" name="export" value="エクスポート" class="imgbutton120">
</form>
__BOX__
));
$objHtml->setSideHtml(D360::getSideBox("設定インポート",<<<__BOX__
<form action="360_define_import.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="{$SESSION_ID}" value="{$SID}">
<input type="submit" name="import" value="インポート" class="imgbutton120">
</form>
__BOX__
));
echo $objHtml->getMainHtml($html);
exit;
