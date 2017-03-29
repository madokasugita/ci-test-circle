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
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseDataEditor.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');

require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'JSON.php');
require_once (DIR_LIB . 'CbasePage.php');
require_once (DIR_LIB . '360_FHtml.php');
encodeWebAll();
session_start();
checkAuthUsr360();
/****************************************************************************************************/
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
//$GLOBALS['NOT_DISP_DIV'] = " and div1 not in ('&%&%&%&%')";

$OK_DOMAIN = array(
    "@cbase.co.jp"
);

/****************************************************************************************************/
function main()
{
    global $GDF, $return_url,$target_user;
    $SID = getSID();
    $target_serial_no = $_REQUEST['target_serial_no'];
    if (getHash360($target_serial_no) != $_REQUEST['hash']) {
        print "invali hash!";
        exit;
    }
    $hash = getHash360($target_serial_no);
    $return_url = "360_user_relation_view_u.php?{$SID}&serial_no={$target_serial_no}&hash={$hash}";
    $target_user = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($target_serial_no) . ' and ' . getDivWhere());
    if (!$target_user) {
        print "error ユーザが見つかりません";
        exit;
    }

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
    if ($_GET['mode'] == 'new') {
        $data=array();
        $data['serial_no'] = 'new';
        $editor->setTarget($data);
    }

    $respondent_serial_no = $data['serial_no'] ? $data['serial_no'] : $_POST['serial_no'];
    if($respondent_serial_no == 'new')
        $mode = "####new_registration####";
    else
        $mode = "####edit####";

    $body = $editor->run();

    define('PAGE_TITLE', '####respondent_edit#### '.$mode);
    $objHtml = & new UserHtml(PAGE_TITLE);
    $body =<<<HTML
<div style="width:880px;margin:auto;">
<div style="text-align:left">[ <a href="{$return_url}">####backto_res_info####</a> ]</div>

<h1>####respondent_edit#### {$mode}</h1>

<div style="width:880px;text-align:center">
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

    public function insert($data)
    {
        global $target_user;
        $target_uid = $target_user['uid'];

        FDB::begin();

        $data['serial_no'] = getUniqueIdWithTable(T_UNIQUE_SERIAL , "serial_no", 8);
        $type = (is_good($data['user_type']))? $data['user_type']:1;
        $data['email'] = (is_good($data['email']))? $data['email']:null;
        $data['lang_type'] = (is_good($data['lang_type']))? $data['lang_type']:0;
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        unset($array['user_type']);
        //$array['register_id'] = FDB::escape($target_uid);
        $array['uid'] = FDB::escape(getUniqueIdWithTable_UID(T_UNIQUE_UID, "uid"));
        $array['pw'] = FDB::escape(getPwHash(get360RandomPw()));
        /* 差し戻しユーザを修正した場合にフラグをつけるなら以下を使用 */
        //if($_SESSION['login']['select_status']==3)
        //	$array['correction_flag'] = FDB::escape(1);
        $rs = FDB::insert(T_USER_MST,$array);
        if (PEAR::isError($rs)) {
            FDB::rollback();

            return false;
        }

        $where = 'where user_type in ('.implode(',', range(1,INPUTER_COUNT)).') and uid_a = ' . FDB :: escape($target_uid) . ' and uid_b = ' . $array['uid'];
        $relation = FDB::select1(T_USER_RELATION, 'user_type', $where);
        if ($relation) {
            //TODO:回答データも削除
        }

        $data = array ();
        $data['user_type'] = (int) $type;
        $data['uid_a'] = FDB::escape($target_uid);
        $data['uid_b'] = $array['uid'];
        $data['add_type'] = 1;
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

    public function update($data)
    {
        global $target_user;

        $before_data = FDB::select1(T_USER_MST,'*','where serial_no = '.FDB::escape($data['serial_no']));
        $target_uid = $target_user['uid'];
        $where = 'where user_type in ('.implode(',', range(1,INPUTER_COUNT)).') and uid_a = ' . FDB::escape($target_uid) . ' and uid_b = ' . FDB::escape($before_data['uid']);
        $before_relation = FDB::select1(T_USER_RELATION, 'user_type', $where);

        $type = $data['user_type'];
        $data['email'] = ($data['email'])? $data['email']:null;
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
        global $_360_sheet_type, $_360_language_org;

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
                return FForm :: text($col, $value,null,'style="width:300px"');
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
                //$ok = false;
                //foreach($OK_DOMAIN as $DOMAIN)
                //	if(!is_false(strpos($value, $DOMAIN))) $ok = true;
                //if(!$ok)
                //	return implode(",",$OK_DOMAIN).'以外のアドレスへは送信できません';
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

    public function getEditValueCallback($data, $col)
    {
        $val = $data[$col];
        switch ($col) {
            default:
                return parent::getEditValueCallback($data, $col);
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
        global $GDF,$target_user;

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
    width:190px;
    //white-space: nowrap;
}
</style>

####edit_message####

{$alert}

<form action="{$GDF->get('PHP_SELF')}" method="post">
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
        $target = FForm::hidden('target_serial_no', $target_user['serial_no']);
        $hash = FForm::hidden('hash', getHash360($target_user['serial_no']));
        $html .= '<tr>'.$header.'</tr><tr>'.$form.'</tr>';
        $html .=<<<__HTML__
</table>
{$show['previous']}
{$show['next']}
{$target}
{$hash}
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
        global $return_url;
        //return $this->getEditView();
        $SID = getSID();

        return<<<__HTML__
<br/>
####register_complete_message####

<br/><br/>
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
    <input type="submit" style="width:250px;height:25px;" name="{$name}" value="####confirm####">
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
    <input type="submit" style="width:250px;height:25px;" name="{$name}" value="####enq_button_pb####">
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
    <input type="submit" style="width:250px;height:25px;" name="{$name}" value="####register####">
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
        if($_POST['mode']=='edit')

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
