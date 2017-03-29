<?php

//define('DEBUG',1 );
define("DIR_ROOT", "../");
//必須require
require_once (DIR_ROOT . "crm_define.php");
require_once (DIR_LIB . 'CbaseEncoding.php');
//サポート
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseDataEditor.php');
require_once (DIR_LIB . 'CbaseHtml.php');
define('FFORM_ESCAPE', 1);
require_once (DIR_LIB . 'CbaseFForm.php');

//データライブラリ
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
require_once (DIR_LIB . 'CbasePage.php');
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
        $columns = array (
            "serial_no"=>"",
            "uid"=>"ユーザID",
            "pw"=>"パスワード(上書き)",
            "nowpw"=>"現在のパスワード",
            "pwmisscount"=>"パスワード間違い回数",
            "login_flag"=>"ログインフラグ",
            "pw_flag"=>"パスワード変更フラグ",
            "news_flag"=>"お知らせ通過フラグ",
        );
        if(!OPTION_LOGIN_FLAG)
            unset($columns['login_flag']);

        if(!isReversiblePw())
            unset($columns['nowpw']);

        return limitColumn($columns);
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
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            switch ($key) {
                case 'pw':
                    if(is_void($data[$key]))
                        break;

                    $array[$key] = FDB::escape(getPwHash($data[$key]));
                    break;
                default:
                    $array[$key] = FDB::escape($data[$key]);
                    break;
            }
        }
        $serial_no = $array['serial_no'];
        unset($array['serial_no']);
        unset($array['nowpw']);
        $rs = FDB::update(T_USER_MST, $array, 'where serial_no = '.$serial_no);

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
            case 'serial_no':
                return FForm :: hidden($col, $value);
            case 'uid':
                return html_escape($value).FForm :: hidden($col, $value);
            case 'pw_flag':
                $array=array("0"=>"未変更","1"=>"変更済");

                return FForm :: replaceChecked(implode('',FForm :: radioList($col, $array)), $value);
            case 'login_flag':
                $array=array("0"=>"未ログイン","1"=>"ログイン済");

                return FForm :: replaceChecked(implode('',FForm :: radioList($col, $array)), $value);
            case 'news_flag':
                $array=array("0"=>"未通過","1"=>"通過済");

                return FForm :: replaceChecked(implode('',FForm :: radioList($col, $array)), $value);
            case 'pw' :
                return FForm :: text($col, null, null,'style="ime-mode:disabled;width:230px"');
            case 'nowpw' :
                $nowpw = (is_void($value))? $data['pw']:$value;

                return getDisplayPw($nowpw).FForm :: hidden($col, $nowpw);
            default :
                return FForm :: text($col, $value,null,'style="ime-mode:disabled;width:230px"');
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
            case "pw":
            //	if($value==="")
            //		return "必須項目です。";
                if(is_good($value) && is_good($error = get360PwError($value)))

                    return $error;
                break;
            case 'pwmisscount':
                if($value==="")

                    return "必須項目です。";
                if(!ereg("^[0-9]+$",$value))

                    return "数字以外が入力されています。";
                if($GLOBALS['Setting']->limitPwLess($value))

                    return LIMIT_PW_MISS."を超える数字が入力されています。";
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
            case 'login_flag' :
                switch ($val) {
                    case 1 :
                        return "ログイン済";

                    default :
                        return "未ログイン";
                }
            case 'news_flag' :
                switch ($val) {
                    case 1 :
                        return "通知済";

                    default :
                        return "未通知";
                }
            case 'mflag' :
                switch ($val) {
                    case 1 :
                        return "対象者";

                    default :
                        return "非対象者";
                }
            case "div1" :
                return getDiv1NameById($val);
            case "div2" :
                return getDiv2NameById($val);
            case "div3" :
                return getDiv3NameById($val);
            case 'sheet_type' :
                if(!$data['mflag'])

                    return "-";
                return $_360_sheet_type[$val];
            case 'pw':
                return preg_replace('/./', '*', $val);
            case 'nowpw' :
                return getDisplayPw($val);
            default :
                return html_escape($val);
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
        $user = FDB::select1(T_USER_MST, "uid,email", "WHERE serial_no = ".FDB::escape($_POST['serial_no']));
        $uid = html_escape($user['uid']);
        $email = html_escape($user['email']);
        $html=<<<__HTML__

<style>
.searchbox{
    border-collapse:collapse;

}
.searchbox td{
    border:solid 1px black;
    padding:2px;
    height:30px;


}

.tr1{
    background-color:#666666;
    color:white;
}
.tr2{
    background-color:#f2f2f2;
    width:235px;
}
</style>
<table class="searchbox">
__HTML__;
        foreach (ThisAdapter :: setupColumns() as $colkey => $colval) {
            if (!$colval) {
                $html.=$show[$colkey];
                continue;
            }
            $html .=<<<__HTML__
<tr>
<td class="tr1">{$colval}</td>
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

        if($this->adapter->now == "top")
            $html .= <<<__HTML__
</form>
<hr>
<form action="../passwd_reissue.php" method="POST" onSubmit="return this.flag?false:this.flag=true;">
<input name="id" type="hidden" class="form-top-login" type="text" value="{$uid}">
<input name="email" type="hidden" class="form-top-login" type="text" value="{$email}">
<input type="hidden" name="mode" value="reissue">
<input type="submit" class="button white" name="mode:reissue" value="パスワード再発行メールを送る">
__HTML__;

        return $html.getHtmlReduceSelect();
    }

    /**
     * ◆virtual
     * 確認画面の表示を行う
     * @return string html
    */
    public function getConfirmView($show,$data='')
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
        $sub1 = RD :: getSubject("完了しました");

        return<<<__HTML__
<span style="color:red;size:13px;font-weight:bold">完了しました</span>

<br><br>
<button onclick="window.close()"> 閉じる </button>
</form>
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
    //	/**
    //	 * 確認ボタンを取得する
    //	 * @param string $name submitタグのname部分
    //	 * @return string html $nameを用いたsubmitを含めること
    //	 */
    //	function getConfirmButton ($name)
    //	{
    //		return <<<__HTML__
    //<input type="submit" name="{$name}" value="確認">
    //__HTML__;
    //	}
    //
    //	/**
    //	 * 戻るボタンを取得する
    //	 * @param string $name submitタグのname部分
    //	 * @return string html $nameを用いたsubmitを含めること
    //	 */
    //	function getPreviousButton ($name)
    //	{
    //		return <<<__HTML__
    //<input type="submit" name="{$name}" value="戻る">
    //__HTML__;
    //	}
    //
    //	/**
    //	 * 登録ボタンを取得する
    //	 * @param string $name submitタグのname部分
    //	 * @return string html $nameを用いたsubmitを含めること
    //	 */
    //	function getRegisterButton ($name)
    //	{
    //		return <<<__HTML__
    //<input type="submit" name="{$name}" value="登録する">
    //__HTML__;
    //
    //	}
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


$html = $editor->run();

$title = RD :: getTitle('', '');

$self = getPHP_SELF();
$DIR_IMG = DIR_IMG;
$html =<<<__HTML__
<div style="text-align:left;width:800px;margin-bottom:5px;border-top:dotted 1px #222222;border-bottom:dotted 1px #222222;margin-bottom:5px;border-bottom:dotted 1px #222222;padding:10px;">

<img src="{$DIR_IMG}icon_inf.gif" width="13" height="13"> ユーザパスワード編集

</div>
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

$objHtml = & new ResearchAdminHtml("ユーザパスワード編集");
echo $objHtml->getMainHtml($html);
exit;
