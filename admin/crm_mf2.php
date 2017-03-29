<?php
/* ---------------------------------------------------------
   ;;;;;;;;;;;;
   ;;;MF1.php;;  by  ipsyste@cbase.co.jp
   ;;;;;;;;;;;;
--------------------------------------------------------- */
define('DENY_USER_COLUMN', "serial_no,syaincode,ip_address,upload_id,evid,note");

define('DIR_ROOT', "../");
require_once(DIR_ROOT."crm_define.php");
require_once(DIR_LIB."CbaseFDB.php");
//require_once(DIR_LIB."CbaseFDBClass.php");
require_once(DIR_LIB."CbaseFMail.php");
require_once(DIR_LIB."CbaseFEvent.php");
require_once(DIR_LIB."CbaseFCheckModule.php");
require_once(DIR_LIB."CbaseFGeneral.php");
require_once(DIR_LIB.'func_rtnclm.php');
define('FFORM_ESCAPE', 1);
require_once(DIR_LIB.'CbaseFForm.php');
require_once(DIR_LIB.'MreAdminHtml.php');
require_once(DIR_LIB."CbaseDataEditor.php");
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once(DIR_LIB.'CbaseFFile2.php');
require_once(DIR_LIB.'CbaseEncoding.php');
require_once (DIR_LIB . '360_Setting.php');
encodeWebInAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

require_once(DIR_LIB.'CbaseDownload.php');
encodeWebOutAll();

$file_errors = array();
$file_keys = array('file','file_1','file_2','file_3','file_4');
foreach ($file_keys as $key) {
    list($_POST[$key], $file_errors[$key]) = getPostFileUp($_POST, $_FILES, $key);
}

class ThisAdapter extends DataEditAdapter
{
    /**
     * ◆abstract
     * @return array このデータで使えるカラム名=>名称のリストを返す
     */
    public function setupColumns()
    {
        $array = array();
        $array['info'] = '基本情報';
        $array['mfid'] = '本文ひな型';
        $array['name'] = 'ひな型名称';
        $array['cdate'] = '作成日時';
        $array['udate'] = '更新日時';
        foreach ($GLOBALS['_360_language'] as $k => $v) {
            if (!$k) {
                $array['title'] = "件名({$v})";
                $array['body'] = "内容({$v})";
                $array['file'] = "添付({$v})";
            } else {
                $array['title_'.$k] = "件名({$v})";
                $array['body_'.$k] = "内容({$v})";
                $array['file_'.$k] = "添付({$v})";
            }
        }

        return $array;
    }

    /**
     * ◆abstruct
     * 保存用のデータが送られてくる
     * @param  array $data 保存用のデータ
     * @return bool  保存に成功すればtrue
     */
    public function save($data)
    {
        $save = array(
            'mfid' => $data['mfid'],
            'name' => $data['name'],
            'title' => $data['title'],
            'body' => $data['body'],
            'file' => $data['file'],
            'title_1' => $data['title_1'],
            'body_1' => $data['body_1'],
            'file_1' => $data['file_1'],
            'title_2' => $data['title_2'],
            'body_2' => $data['body_2'],
            'file_2' => $data['file_2'],
            'title_3' => $data['title_3'],
            'body_3' => $data['body_3'],
            'file_3' => $data['file_3'],
            'title_4' => $data['title_4'],
            'body_4' => $data['body_4'],
            'file_4' => $data['file_4'],
            'muid' => $_SESSION["muid"],
        );

        $command = $data['mfid']? 'update': 'new';

        Save_MailFormat($command, $save);

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
        $dis = isDisableMode()? ' disabled': '';
        $value = $data[$col];
        $DIR_IMG = DIR_IMG;
        switch ($col) {
            case 'info':
                if ($data['mfid']) {
                    $cdate = FForm::hidden('cdate', $data['cdate']);
                    $udate = FForm::hidden('udate', $data['udate']);
                    $info = FForm::hidden('info', 1);
                    $data['cdate'] = html_escape($data['cdate']);
                    $data['udate'] = html_escape($data['udate']);
                    $data['mfid'] = html_escape($data['mfid']);

                    $body= <<<__HTML__
<div align="left"><br>
<table width="300" border="0" align="center" cellpadding="0" cellspacing="0">
<tr>
<td width="50">　</td>
<td width="80">



<div align="left"><font size="2">ＩＤ</font>
</div>
</td>
<td width="20"><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"></font></td>
<td width="150">
<font size="2">
{$data["mfid"]}
</font></td>
</tr>
<tr>
<td colspan="4">

<div align="left"><img src="{$DIR_IMG}spacer.gif" width="1" height="5"></div>
</td>
</tr>
<tr>
<td>　</td>
<td>

<div align="left"><font size="2">作成日</font>
</div>
</td>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"></font></td>
<td><font size="2">{$data["cdate"]}{$cdate}</font></td>
</tr>
<tr>
<td colspan="4">

<div align="left"><img src="{$DIR_IMG}spacer.gif" width="1" height="5"></div>
</td>
</tr>
<tr>
<td>　</td>
<td>



<div align="left"><font size="2">更新日</font>
</div>
</td>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"></font></td>
<td>
<font size="2">
{$data["udate"]}{$udate}{$info}
</font>
</td>
</tr>
<tr>
<td colspan="4">

<div align="left"><img src="{$DIR_IMG}spacer.gif" width="1" height="5"></div>
</td>

</table>
<br>
</div>

__HTML__;
                } else {
                    $body = '<br><div align=center>新規作成</div><br>';
                }

                    return <<<__HTML__
<table align="center" width="300" border="0" cellpadding="0" cellspacing="0" bgcolor="#4c4c4c">
<tr>
<td width="270" valign="bottom">
<table width="300" border="0" cellpadding="0" cellspacing="1">
<tr>
<td width="300" valign="middle" bgcolor="#f6f6f6">

{$body}
</td>
</tr>

</table>
</td>
</tr>

</table>
__HTML__;


            case 'mfid':
                return FForm::hidden($col, $value);
            case 't':
                return FForm::hidden($col, time());
            case 'name':
            case 'title':
            case 'title_1':
            case 'title_2':
            case 'title_3':
            case 'title_4':
                return FForm::text($col, $value, '', 'size=60'.$dis);
                break;
            case 'body':
            case 'body_1':
            case 'body_2':
            case 'body_3':
            case 'body_4':
                return FForm::textarea($col, $value, 'cols=70 rows=30 id="'.$col.'" class="body"'.$dis);
                break;
            case 'file':
            case 'file_1':
            case 'file_2':
            case 'file_3':
            case 'file_4':
                $attach = (is_good($value))? unserialize($value):array();

                $table = "";
                if(is_array($attach))
                foreach ($attach as $file => $f) {
                    $size = round(($f['size']/1024)+0.05,1).'KB';
                    $link = getDownloadLink($file, $f['name']);
                    $delete = FForm::checkbox("{$col}_delete[]", $file, "", $dis);
                    $warning = (!file_exists(DIR_DATA.$file))? "※ファイルが存在しません":"";
                    $table .= <<<__HTML__
<tr>
  <td>{$link} {$size}</td>
  <td>{$delete} 削除</td>
  <td><div style="color:#ff0000;font-weight:bold;">{$warning}</div></td>
</tr>\n
__HTML__;
                }
                if (is_good($table)) {
                    $table = <<<__HTML__
<table border="0" cellspacing="0" cellpadding="2" style="display:inline;">
{$table}
</table>
__HTML__;
                }

                global $editor;
                $_col = $col."_insert";

                return FForm::hidden($col, $value)
                    .FForm::file("{$_col}[file]", "", "",  "onChange=\"document.getElementById('{$_col}[filename]').value=document.getElementById('{$_col}[file]').value;\"".$dis)
                    .FForm::hidden("{$_col}[filename]", "")
                    .preg_replace("/value=\"([^\"]*)\"/", "value=\"追加\"", $editor->getConfirmButton())
                    ."<br>".$table;
                break;
            default:
                break;
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
                $cols = $this->getColumnNames();
                if (!$value) {
                    return $cols[$col].'は必ず入力してください';
                }
                break;
            case 'body':
            case 'body_1':
            case 'body_2':
            case 'body_3':
            case 'body_4':
                preg_match_all('/%%%%pw%%%%/', $value, $matches);
                if (count($matches[0]) > 1) {
                    return 'パスワードの差しこみ文字は1度しか使用できません';
                }
                break;
            case 'file':
            case 'file_1':
            case 'file_2':
            case 'file_3':
            case 'file_4':
                global $file_errors;
                $error = $file_errors[$col];
                if (is_good($error)) {
                    return $error;
                }
                $attach = (is_good($value))? unserialize($value):array();
                $size_sum = 0;
                if(is_array($attach))
                foreach($attach as $file => $f)
                    $size_sum += $f['size'];


                if ($GLOBALS['Setting']->fileSizeLess($size_sum)) {
                    return "ファイルの合計容量は".MAIL_FILE_SUM_SIZE."KBまでです";
                }



                if ($data['data_editor_mode']['confirm']=="追加") {
                    /* ファイル追加時は確認画面に進まないようにエラーチェックで止める */

                    return "";
                }
                break;
//			case 'evid':
//			case 'mfid':
//				if($value < 0)
//				{
//					return '選択してください';
//				}
//				break;
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
        global $Setting;
        $value = $data[$col];
        switch ($col) {
            case 'mfid':
                return '';
            case 'info':
                return $this-> getFormCallback($data, $col);
                break;
            case 'title':
            case 'title_1':
            case 'title_2':
            case 'title_3':
            case 'title_4':
                if(ereg('%%%',$value))

                    return $value.'<br><span style="color:red;font-weight:bold"> ! 件名にはデータ差込することができません</span>';
                return $value;
            case 'name':
                return $value;
                break;
            case 'body':
            case 'body_1':
            case 'body_2':
            case 'body_3':
            case 'body_4':
                $value = ($Setting->htmlMail())? html_unescape($value) : html_escape($value);
                $value = replaceToHighlight($value);
                $value = nl2br($value);

                $message= <<<HTML
<br><span style="color:#999999">※<span class='replace_ok'>青い</span>部分は、それぞれのユーザのデータに置換されます。</span><br><br>
HTML;


                //$value = nl2br($value);
                return <<<__HTML__
{$message}
<div style='word-wrap:break-word;margin:0px;padding:4px;width:630px;background-color:#ffffff;border:1px solid #7F9DB9;font-size:12px'>{$value}</div>{$test}
__HTML__;
                break;
            case 'file':
            case 'file_1':
            case 'file_2':
            case 'file_3':
            case 'file_4':
                $value = html_unescape($value);
                $attach = (is_good($value))? unserialize($value):array();

                $table = "";
                if(is_array($attach))
                foreach ($attach as $file => $f) {
                    $link = getDownloadLink($file, $f['name']);
                    $size = round(($f['size']/1024)+0.05,1).'KB';
                    $table .= <<<__HTML__
<tr>
  <td>{$link} {$size}</td>
</tr>\n
__HTML__;
                }
                if (is_good($table)) {
                    $table = <<<__HTML__
<table border="0" cellspacing="0" cellpadding="2" style="display:inline;">
{$table}
</table>
__HTML__;
                }

                return $this->addHiddenValue($table, $data, $col);
                break;
            default:
                break;
        }
    }
}

class  ThisDesign extends DataEditDesign
{
    /**
     * 確認ボタンを取得する
     * @param  string $name submitタグのname部分
     * @return string html $nameを用いたsubmitを含めること
     */
    public function getConfirmButton($name)
    {

        if(isDisableMode ())

            return '<a href="crm_mr1.php?'.getSID().'">[ 戻る ]</a>';
        return <<<__HTML__
<input type="submit" name="{$name}" value="確認"class="imgbutton90" onClick='$(":submit").not(this).attr("disabled", "disabled");'>
__HTML__;
    }
    public function getPreviousButton($backcommand='top')
    {
        return <<<__HTML__
<div id="test_mail" style="display:none;background-color:#f0f0f0;border:dotted 1px black;margin:30px;width:450px;">
<table>
<tr><td colspan="2"><b>1回だけテスト配信する</b> ( 言語数分メールが届きます )<br>
<span style="color:#999999">※メール本文に差し込んだデータが正常に置換されているか、<br>
テスト配信をして確認することが出来ます。</span>
</td></tr>
<tr><td>Email : </td><td><input name="email" style="width:270px"><input type="submit" value="送信" onclick="rewriteFormTag1()"></td></tr>
</table>
</div>
<script>
function rewriteFormTag1()
{
    document.getElementsByTagName('form')[0].action = "mail_template_test.php";
    document.getElementsByTagName('form')[0].target = "_blank";
}
function rewriteFormTag2()
{
    document.getElementsByTagName('form')[0].action = "crm_mf2.php";
    document.getElementsByTagName('form')[0].target = "_self";
}
document.getElementById('test_mail').style.display="";
</script>

<input type="submit" name="{$backcommand}" value="戻る" onclick="rewriteFormTag2()"class="imgbutton90">
__HTML__;
    }
    public function getRegisterButton($name)
    {
        return <<<__HTML__
<input type="submit" name="{$name}" value="登録する" onclick="rewriteFormTag2()"class="imgbutton90">
__HTML__;
    }

    public function getColumnNames()
    {
        $adpt = $this->getAdapter();

        return $adpt->getColumnNames();
    }

    /**
     * ◆virtual
     * 編集画面の表示を行う
     * @return string html
     */
    public function getEditView ($show, $error=array())
    {
        global $Setting;
        $sub1 = D360::getSubject("基本情報", '※作成時の基本情報は変更することはできません。', 600);
        $sub2 = D360::getSubject("ひな型追加", '※新しくひな型を追加作成します。', 600);

        $line = D360::getLine(400);

        $cols = $this->getColumnNames ();
        $name =  $this->getRow ($cols['name'], $show['name']);
        $title =  $this->getRow ($cols['title'], $show['title']);
        $DIR_IMG = DIR_IMG;
        $MAIL_FILE_SUM_SIZE = MAIL_FILE_SUM_SIZE;
        $res = <<<__HTML__
{$sub1}
{$show['info']}

<br>
<div>
{$sub2}

{$show['mfid']}

<div align=center>
<table border="0" cellpadding="0" cellspacing="0">
<tr>
    <td width=70>
        <div align="center">
        <font size="2">ひな型名称</font>
        </div>
    </td>
    <td width="18">
        <img src="{$DIR_IMG}arrow_r.gif" width="16" height="16">
    </td>
    <td>
        <font size="2">
        {$show['name']}<br>{$error['name']}
        </font>
    </td>
</tr>



__HTML__;

        $collect_word_options = htmlOptionsWithCollectWord();
        $collect_style_width = ($Setting->htmlMail()) ? 'width:850px;' : 'width:750px;';
        $collect_word_view = '';
        if ($this->adapter->mode == "top") {
            $collect_word_view .= <<<__HTML__
                <span>
                    <select name="collect_word" style="margin-left:15px;">
                        {$collect_word_options}
                    </select>
                    <br><br>
                    <input type="button" value="差込" class="button white collect_word" style="margin-left:15px;" />
                </span>
__HTML__;
        }

        foreach ($GLOBALS['_360_language'] as $k => $v) {
            if (!$k) {
                $tkey = 'title';
                $bkey = 'body';
                $fkey = 'file';
            } else {
                $tkey = 'title_'.$k;
                $bkey = 'body_'.$k;
                $fkey = 'file_'.$k;
            }
            $res .= <<<__HTML__
<tr valign="middle">
    <td height="10" colspan="3">

    </td>
</tr>
<tr valign="middle">
    <td height="2" colspan="3" style="height:1;background-color:black">

    </td>
</tr>
<tr valign="middle">
    <td height="10" colspan="3">

    </td>
</tr>
<tr>
    <td width=80>
    <div align="center"><font size="2">件名 {$v}</font>
    </div>
    </td>
    <td><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
    <td><font size="2">
    {$show[$tkey]}<br>{$error[$tkey]}
    </font>
    </td>
</tr>

<tr>
<td valign="top" width="70">
<div align="center"><font size="2">本文 {$v}</font>
</div>
</td>
<td valign="top" colspan="2" align="left"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
</tr>

<tr>
<td valign="top" colspan="3">
    <div data-bkey="{$bkey}" style="{$collect_style_width}">
        <span style="float:left;">
            <font size="2">
                {$show[$bkey]}<br>{$error[$bkey]}
            </font>
        </span>
        {$collect_word_view}
    </div>
</td>
</tr>

<tr>
    <td width="70">
    <div align="center"><font size="1">添付 {$v} ({$MAIL_FILE_SUM_SIZE}KBまで)</font>
    </div>
    </td>
    <td><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
    <td><font size="2">
    {$show[$fkey]}<br>{$error[$fkey]}
    </font>
    </td>
</tr>
__HTML__;
        }
$res .= <<<__HTML__

</table>

</div>
<div width="450" align="center">
{$show['previous']}
{$show['next']}
</div>
__HTML__;

        return $res;
    }

    public function getRow($name, $value)
    {
        $DIR_IMG = DIR_IMG;

        return <<<__HTML__
<table width="400" border="0" cellpadding="0" cellspacing="0">
<tr>
    <td width="100">
        <div align="center">
        <font size="2">{$name}</font>
        </div>
    </td>
    <td width="18">
        <img src="{$DIR_IMG}arrow_r.gif" width="16" height="16">
    </td>
    <td width="282">
        <font size="2">
        {$value}
        </font>
    </td>
</tr>
</table>
__HTML__;
}

//	/**
//	 * ◆virtual
//	 * 確認画面の表示を行う
//	 * @return string html
//	 */
//	function getConfirmView ($show)
//	{
//		return $this->getEditView($show);
//	}

    /**
     * ◆virtual
     * 登録完了画面の表示を行う
     * @return string html
     */
    public function getCompleteView($show)
    {
        $sub1 = D360::getSubject("完了しました");
        $sid = getSID();

        return <<<__HTML__
{$sub1}
<p>
ひな型の編集を完了しました。
</p>

        <br><a href="crm_mf1.php?{$sid}">一覧へ戻る</a>
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
        return '<span style="color:#F00">'.$str.'</span>';
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

function isDisableMode()
{
    return $_GET["mfid"];
}

$edit = new ThisAdapter();

//$global_id = $_POST['user_id']? $_POST['user_id']: $_GET['id'];
//$mode = $global_id? '編集': '登録';
$editor = new DataEditor($edit, new ThisDesign());

//デフォルト値の割り当て処理
$id = $_GET["mfid"]? $_GET["mfid"]: $_POST["mfid"];
if ($id && !$editor->isPosted()) {
    $array1 = Get_MailFormat($id,"mfid","desc", $_SESSION["muid"]);
    $array  = $array1[0];

    //ひな型名称取得
    if (isDisableMode ()) {
        if($array["name"]  == "")
            $array["name"] = "(内部専用データの為、雛形名称なし)";
    }

    $editor->setTarget($array);
    $_POST = array();
    //$editor->def = $editor->target;
}

$html =  $editor->run();

$page = (isDisableMode ())? "crm_mr1.php":"crm_mf1.php";
$backline = D360::getBackBar($page."?".getSID());
//$title = RD::getTitle(DIR_IMG.'mailfbanner.gif', '新しくメールひな型を追加できます。<br>
//また、作成済みひな型の内容を変更します。');

$self = getPHP_SELF();
$MAX_FILE_SIZE = MAX_FILE_SIZE;
$DIR_IMG = DIR_IMG;
$html_mail_flag = ($Setting->htmlMail()) ? '1' : '0';
$html_js = <<<__HTML__
<script type="text/javascript">
<!--
    var htmlMail = '{$html_mail_flag}';
-->
</script>
__HTML__;
$html = $html_js . <<<__HTML__
{$backline}
<table border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="740"valign="top"style="padding:10px">
<form action="{$self}" method="POST" encType="multipart/form-data">
<input type="hidden" name="MAX_FILE_SIZE" value="{$MAX_FILE_SIZE}">
{$html}
</form>
</td>
<td width="10">　</td>
<!-- 説明の列 -->
<td width="169" valign="top"><br>
<table width="150" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="#4c4c4c"class="helparea">
<tr>
<td width="270" valign="bottom">
<table width="150" border="0" cellpadding="0" cellspacing="1">
<tr>
<td width="180" valign="middle" bgcolor="#f6f6f6">
<table width="300" border="0" align="center" cellpadding="0" cellspacing="4">
<tr>
<td><img src="{$DIR_IMG}overview.gif" width="100" height="16"></td>
</tr>
<tr>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"> データ差込</font></td>
</tr>
<tr>
<td><font size="2">「本文」に、以下の%%%%の文字列を指定します。</font></td>
</tr>
<tr>
<td><img src="{$DIR_IMG}spacer.gif" width="1" height="2"></td>
</tr>
<tr>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"> 差込可能データ</font></td>
</tr>





__HTML__;



foreach (getOkTag () as $k => $v) {
    if ($k == "_separator_") {
        $html .= <<<__HTML__
<tr>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"> 特殊差しこみ可能データ<br/><font color="red">(通常の予約メールでは置き換えられません)</font></font></td>
</tr>
__HTML__;
        continue;
    }
    $column = html_escape($k);
    $comment = html_escape($v);
    $html .= <<<__HTML__
<tr>
<td><font size="2">{$comment}<br>-> %%%%{$column}%%%%</font></td>
</tr>
<tr>
<td><img src="{$DIR_IMG}spacer.gif" width="1" height="2"></td>
</tr>
__HTML__;
}
$html .= <<<__HTML__

</table>

__HTML__;
$objHtml =& new MreAdminHtml("メールひな型 新規作成/編集", '新しくメールひな型を追加できます。また、作成済みひな型の内容を変更します。', false);
if ($Setting->htmlMail()) {
    $objHtml->addFileCss(DIR_IMG."yui_2.9.0/skin.css");
    $objHtml->addFileJs(DIR_IMG."yui_2.9.0/yahoo-dom-event.js");
    $objHtml->addFileJs(DIR_IMG."yui_2.9.0/element-min.js");
    $objHtml->addFileJs(DIR_IMG."yui_2.9.0/container_core-min.js");
    $objHtml->addFileJs(DIR_IMG."yui_2.9.0/menu-min.js");
    $objHtml->addFileJs(DIR_IMG."yui_2.9.0/button-min.js");
    $objHtml->addFileJs(DIR_JS."floatinghelp.js");
    $objHtml->addFileJs(DIR_IMG."yui_2.9.0/editor-min.js");

}
$objHtml->addFileJs(DIR_JS."admin/jquery.mail-format-collect.js");
echo $objHtml->getMainHtml($html);
exit;

function getPostFileUp($post, $files, $key)
{
    $error = "";
    $attach = (is_good($post[$key]))? unserialize($post[$key]):array();
    $files = $files[$key.'_insert'];
    if (is_good($files) && $files['error']['file']!=UPLOAD_ERR_NO_FILE) {
        if (!is_uploaded_file($files['tmp_name']['file']) || $files['error']['file']!=UPLOAD_ERR_OK || $files['size']['file']==0) {
            $error = "ファイルのアップに失敗しました";
        } else {
            $filename = $post[$key.'_insert']['filename'];
            if(is_void($filename))
                $filename = $files['name']['file'];
            $pos = mb_strrpos($filename, "\\");
            if(is_good($pos))
                $filename = mb_substr($filename, $pos+1);

            $i = 0;
            do {
                if(++$i>100)
                    break;
                $file = "attach_".md5(uniqid(rand(), true)).".cfile";
            } while (file_exists(DIR_DATA.$file));

            if (file_exists(DIR_DATA.$file) || !move_uploaded_file($files['tmp_name']['file'], DIR_DATA.$file)) {
                $error = 'ファイルの保存に失敗しました';
            } else {
                syncCopy(DIR_DATA.$file);
                $attach[$file] = array('name'=>$filename,'size'=>filesize(DIR_DATA.$file));
            }
        }
    }
    if(is_array($post[$key.'_delete']))
    foreach ($post[$key.'_delete'] as $file) {
            unset($attach[$file]);
    }
    $attach = (is_good($attach))? serialize($attach):"";

    return array($attach, $error);
}

function htmlOptionsWithCollectWord()
{
    $html = '';
    foreach (getOkTag() as $k => $v) {
        if ($k != '_separator_') {
            $html.= '<option value="%%%%'.$k.'%%%%">'.$v.'</option>';
        }
    }

    return $html;
}
