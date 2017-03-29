<?php

/**
 * PGNAME:ユーザ回答者関連付け検索
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/
//define("DEBUG", 1);
/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseDataEditor.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');

require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'JSON.php');
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . '360_FHtml.php');
encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************/
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
$GLOBALS['NOT_DISP_DIV'] = " and div1 not in ('&%&%&%&%')";

$OK_DOMAIN = array(
    "@nittsu.co.jp"
);

/****************************************************************************************************/
function main()
{
    global $GDF, $return_url;
    $SID = getSID();

    $serial_no = $_REQUEST['target_serial_no'];
    $hash = getHash360($serial_no);
    if ($hash!= $_REQUEST['hash']) {
        print "invali hash!";
        exit;
    }
    $target_user = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($serial_no) . ' and ' . getDivWhere());
    if (!$target_user) {
        print "error ユーザが見つかりません";
        exit;
    }
    define('TARGET_USR_UID',$target_user['uid']);

    $return_url = "360_user_relation_view.php?{$SID}&serial_no={$serial_no}&hash={$hash}";

    $edit = new ThisAdapter();
    $design = new ThisDesign();
    $editor = new ThisDataEditor($edit, $design);

    if ($_POST['mode'] == 'edit' && $_POST['respondent_serial_no']) {
        if ($_POST['respondent_hash'] != getHash360($_POST['respondent_serial_no'])) {
            print "error!<br><button onclick='window.close()'>close window</button>";
            exit;
        }
        $user = FDB::select1(T_USER_MST,'*','where serial_no = '.FDB::escape($_POST['respondent_serial_no']));
        $user['user_type'] = $_POST['respondent_type'];
        $editor->setTarget($user);
    }

    if ($_POST['mode'] == 'new') {

        $data=array();
        $data['serial_no'] = 'new';

        $editor->setTarget($data);

    }

    $respondent_serial_no = $data['serial_no'] ? $data['serial_no'] : $_POST['serial_no'];

    if($respondent_serial_no == 'new')
        $mode = "新規登録";
    else
        $mode = "編集";

    $body = $editor->run();

    define('PAGE_TITLE', '####respondent_edit#### '.$mode);
    $objHtml = & new MreAdminHtml("####respondent_edit#### ".$mode);
    $body =<<<HTML
<div style="width:880px;margin:auto;">
<div style="text-align:left">[ <a href="{$return_url}">####backto_res_info####</a> ]</div>

<div style="text-align:left;width:800px;margin-bottom:5px;border-top:dotted 1px #222222;border-bottom:dotted 1px #222222;margin-bottom:5px;margin-top:5px;border-bottom:dotted 1px #222222;padding:10px;">
<table>
<tr>
  <td >####respondent_edit#### {$mode}</td>
  <td valign="middle"></td>
</tr>
</table>
</div>

<div style="width:800px;text-align:center">
####respondent_edit_message####

{$body}

</div>
</div>
HTML;
    print $objHtml->getMainHtml($body);
    exit;
}
/****************************************************************************************************/
class ThisAdapter extends DataEditAdapter
{
    /**
     * ◆abstract
     * @return array このデータで使えるカラム名=>名称のリストを返す
     */
    public function setupColumns()
    {
        return array (
            "serial_no"=>"",
            "name" => "####name####",
            //"class" => "####Post####",
            //"div_ext" => "####div####",
            "email" => "####mail####",
            "user_type" => "####Respondent####<br>####Type####",
            "lang_type" => "####language####",
        );
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

    /* 新規登録 は 360_user_respondent_new.php
    public function insert($data)
    {
        $target_uid = TARGET_USR_UID;

        FDB::begin();

        $data['serial_no'] = getUniqueIdWithTable(T_UNIQUE_SERIAL , "serial_no", 8);
        $type = $data['user_type'];
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        unset($array['user_type']);
        $array['register_id'] = FDB::escape($target_uid);
        $array['uid'] = FDB::escape(getUniqueIdWithTable_UID(T_UNIQUE_UID, "uid"));
        $array['pw'] = FDB::escape(get360RandomPw());
        // 差し戻しユーザを修正した場合にフラグをつけるなら以下を使用
        if($_SESSION['login']['select_status']==3)
            $array['correction_flag'] = FDB::escape(1);
        $rs = FDB::insert(T_USER_MST,$array);
        if (PEAR::isError($rs)) {
            FDB::rollback();

            return false;
        }

        $where = 'where user_type in (1,2,3) and uid_a = ' . FDB :: escape($target_uid) . ' and uid_b = ' . $array['uid'];
        $relation = FDB::select1(T_USER_RELATION, 'user_type', $where);
        if ($relation) {
            //TODO:回答データも削除
        }

        $data = array ();
        $data['user_type'] = (int) $type;
        $data['uid_a'] = FDB::escape($target_uid);
        $data['uid_b'] = $array['uid'];
        if ($relation) {
            $res = FDB::update(T_USER_RELATION, $data, $where);
        } else {
            $res = FDB::insert(T_USER_RELATION, $data, $where);
        }
        if (PEAR::isError($res)) {
            FDB::rollback();

            return false;
        }

        FDB::commit();

        return true;
    }
    */

    public function update($data)
    {
        $before_data = FDB::select1(T_USER_MST,'*','where serial_no = '.FDB::escape($data['serial_no']));
        $target_uid = TARGET_USR_UID;
        $where = 'where user_type in ('.implode(',', range(1,INPUTER_COUNT)).') and uid_a = ' . FDB::escape($target_uid) . ' and uid_b = ' . FDB::escape($before_data['uid']);
        $before_relation = FDB::select1(T_USER_RELATION, 'user_type', $where);

        $type = $data['user_type'];
        FDB::begin();
        $array = array();

        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        unset($array['user_type']);
        /* 差し戻しユーザを修正した場合にフラグをつけるなら以下を使用 */
        //if($_SESSION['login']['select_status']==3)
        //	$array['correction_flag'] = FDB::escape(1);
        $rs = FDB::update(T_USER_MST,$array,'where serial_no = '.$array['serial_no']);
        if (PEAR::isError($rs)) {
            FDB::rollback();

            return false;
        }

        if ($before_relation['user_type'] != $type) {
            $data = array ();
            $data['user_type'] = (int) $type;
            $data['uid_a'] = FDB::escape($target_uid);
            $data['uid_b'] = FDB::escape($before_data['uid']);

            $res = FDB :: update(T_USER_RELATION, $data, $where);
            if (PEAR::isError($res)) {
                FDB::rollback();

                return false;
            }
        }

        FDB::commit();

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
        global $_360_language_org, $_360_sheet_type;

        $value = $data[$col];
        switch ($col) {
            case 'serial_no':
                return FForm :: hidden($col, $value);
            case 'user_type':
                for ($i=1; $i <= INPUTER_COUNT; $i++) {
                    $arr[$i] = $GLOBALS['_360_user_type'][$i];
                }

                return FForm :: replaceSelected(FForm :: select($col, $arr, 'style="width:100px"'), $data[$col]);
            case 'lang_type':
                return FForm :: replaceSelected(FForm :: select($col, $_360_language_org, 'style="width:100px"'), $data[$col]);
            default :
                return FForm :: text($col, $value,null,'style="width:170px"');
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
        global $OK_DOMAIN;
        $value = $data[$col];
        switch ($col) {
            case 'email' :
                if (FCheck::isBlank($value))
                    return '<br>▲####necessary_alert####';
                if (!FCheck::isEmail($value))
                    return '<br>▲####format_invalid####';
/* 管理画面ではドメイン許可
                $ok = false;
                foreach($OK_DOMAIN as $DOMAIN)
                    if(!is_false(strpos($value, $DOMAIN))) $ok = true;
                if(!$ok)

                    return implode(",",$OK_DOMAIN).'以外のアドレスへは送信できません';
*/
                break;
            case 'name':
                if (FCheck::isBlank($value))
                    return '▲####necessary_alert####';
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
            case 'lang_type' :
                return $GLOBALS['_360_language_org'][$val];
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
        global $GDF;
        $serial_no = $_REQUEST['target_serial_no'];
        $hash = getHash360($serial_no);

        if(is_good($error))
            $alert = "<ul class='errors'><li class='error'>####error_alert####</li></ul>";

        $html=<<<__HTML__

<style>
.searchbox{
    border-collapse:collapse;
    margin:15px auto 10px;
}
.searchbox td{
    border:solid 1px gray;
    padding:2px;
    height:30px;
}

.tr1{
    background-color:#666666;
    color:white;
}
.tr2{
    background-color:#f2f2f2;
    width:170px;
    vertical-align:top;
}
</style>

####edit_message####

{$alert}

<form action="{$GDF->get('PHP_SELF')}" method="post">
<input type="hidden" name="target_serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}">
<table class="searchbox">
__HTML__;
        foreach (ThisAdapter :: setupColumns() as $colkey => $colval) {
            if (!$colval) {
                $html.=$show[$colkey];
                continue;
            }
            $header .=<<<__HTML__
<td class="tr1">{$colval}</td>
__HTML__;
            $form .=<<<__HTML__
<td class="tr2">
{$show[$colkey]}{$error[$colkey]}
</td>
__HTML__;

        }

        $html .= '<tr>'.$header.'</tr><tr>'.$form.'</tr>';
        $html .=<<<__HTML__
</table>
{$show['previous']}
{$show['next']}
</form>
__HTML__;

        return $html;
    }

    /**
     * ◆virtual
     * 確認画面の表示を行う
     * @return string html
    */
    public function getConfirmView($show,$data='')
    {
        return str_replace("####edit_message####", "####confirm_message####", $this->getEditView($show));
    }

    /**
     * ◆virtual
     * 登録完了画面の表示を行う
     * @return string html
     */

    public function getCompleteView($show)
    {
        global $GDF, $return_url;
        //return $this->getEditView();
        $serial_no = $_POST['target_serial_no'];
        $hash = getHash360($serial_no);

        return<<<__HTML__
<br/>
<span style="color:red;size:13px;font-weight:bold">登録完了しました</span>

<br/><br/>

<!--
<form action="{$GDF->get('PHP_SELF')}" method="post">
<input type="hidden" name="mode" value="new">
<input type="hidden" name="target_serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}">
<input type="submit" value="続けて新規登録する"  style="width:250px;height:25px;">
</form>
<br/><br/>
-->

<button style="width:250px;height:25px;" onclick="location.href='{$return_url}'">####backto_res_info####</button>
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

    //	/**
    //	 * ◆virtual
    //	 * エラー画面の表示を行う
    //	 * @param string $message エラーメッセージ
    //	 * @return string html
    //	 */
    //	function getErrorView($message)
    //	{
    //		return $message;
    //	}
    //
        /**
         * 確認ボタンを取得する
         * @param string $name submitタグのname部分
         * @return string html $nameを用いたsubmitを含めること
         */

        function getConfirmButton($name)
        {
            return <<<__HTML__
    <input type="submit" style="width:250px;height:25px;" name="{$name}" value="確認">
__HTML__;
        }


        /**
         * 戻るボタンを取得する
         * @param string $name submitタグのname部分
         * @return string html $nameを用いたsubmitを含めること
         */
        function getPreviousButton($name)
        {
            return <<<__HTML__
    <input type="submit" style="width:250px;height:25px;" name="{$name}" value="戻る">
__HTML__;
        }

        /**
         * 登録ボタンを取得する
         * @param string $name submitタグのname部分
         * @return string html $nameを用いたsubmitを含めること
         */
        function getRegisterButton($name)
        {
            return <<<__HTML__
    <input type="submit" style="width:250px;height:25px;" name="{$name}" value="登録する">
__HTML__;

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
        if($_POST['mode'])

            return array();
        return $_POST;
    }

    /**
     * 確認画面の処理と表示を行う
     * @return string html
     */
/*
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
            $show['next'] = $this->design->getRegisterButton('data_editor_mode:complete')
                . $this->getSessionHidden();

            return $this->design->getConfirmView($show,$post);
        }
    }
*/
    //多重投稿禁止機能は使わない
/*
    public function validateSession() {}
*/
}

/****************************************************************************************************/
main();
