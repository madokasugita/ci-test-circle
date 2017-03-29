<?php

//define('DEBUG',1 );

define("NOT_CONVERT", 1);

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
require_once (DIR_LIB . '360_Function.php');
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
        global $_360_language;
        $array = array();
        $array['msgid'] = "";
        $array['mkey'] = "キー";

        $array['place1'] = "場所1";
        $array['place2'] = "場所2";
        $array['type'] = "種類";
        $array['name'] = "名称";
        //$array['memo'] = "説明";
        foreach ($_360_language as $k => $v) {
            $array['body_'.$k] = "内容({$v})";
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
        if($data['msgid'])

            return $this->update($data);
        else
            return $this->insert($data);
    }

    public function update($data)
    {
        FDB::begin();
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        $rs = FDB::update(T_MESSAGE,$array,'where msgid = '.$array['msgid']);
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            clearMessageCache();
            FDB::commit();

            return true;
        }

    }
    public function insert($data)
    {
        FDB::begin();
        $array = array();
        foreach ($this->setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
        unset($array['msgid']);
        $rs = FDB::insert(T_MESSAGE,$array);
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            clearMessageCache();
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
            case 'msgid':
                return FForm :: hidden($col, $value);
            case 'body_0':
            case 'body_1':
            case 'body_2':
            case 'body_3':
            case 'body_4':
                return FForm::textarea($col, $value,'style="width:600px;height:200px"');
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
            case 'msgid':
                return "";
            case 'body_0':
            case 'body_1':
            case 'body_2':
            case 'body_3':
            case 'body_4':
                    return "<pre style='margin:0px;padding:2px;width:600px;min-height:200px;height:auto !important;height:200px;background-color:#ffffff;border:1px solid #7F9DB9;word-wrap:break-word;white-space:pre-wrap;'>".preg_replace('|<!--to([^-]+)-->|e', "dateCal('\\1')", html_unescape($val))."</pre>";
            case 'memo':
                return "<pre style='margin:0'>{$val}</pre>";
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
        else if($_POST['mode']=='dup')
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

if ($_REQUEST['mode'] == 'edit' && $_REQUEST['msgid']) {
    if ($_REQUEST['hash'] != getHash360($_REQUEST['msgid'])) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }
    $editor->setTarget(FDB::select1(T_MESSAGE,'*','where msgid = '.FDB::escape($_REQUEST['msgid'])));
} elseif ($_REQUEST['mode'] == 'dup' && $_REQUEST['msgid']) {
    if ($_REQUEST['hash'] != getHash360($_REQUEST['msgid'])) {
        print "error!<br><button onclick='window.close()'>close window</button>";
        exit;
    }
    $data = FDB::select1(T_MESSAGE,'*','where msgid = '.FDB::escape($_REQUEST['msgid']));
    unset($data['msgid']);
    $editor->setTarget($data);
}

if ($_REQUEST['mode'] == 'new') {
    $data=array();
    $editor->setTarget($data);
}


$html = $editor->run();

$title = RD :: getTitle('', '');

$self = getPHP_SELF();
$DIR_IMG = DIR_IMG;
$SID = getSID();
$html =<<<__HTML__
<table>
<td style="vertical-align: top;">
<button onclick="window.close()">ウィンドウを閉じる</button>
<div style="text-align:left;width:800px;margin-bottom:5px;border-top:dotted 1px #222222;border-bottom:dotted 1px #222222;margin-bottom:5px;border-bottom:dotted 1px #222222;padding:10px;">

<img src="{$DIR_IMG}icon_inf.gif" width="13" height="13"> 文言編集

</div>
<form action="{$self}" method="post">
{$html}
</form>
__HTML__;


$html .= <<<__HTML__
</td>
<td class="sample" style="vertical-align: top;">
<table width="480" align="center" cellpadding="5" style="border:solid 1px;">

＜よく使うHTMLタグの記述方法＞
<td>

<b>■文字を太くする　&lt;b&gt;～&lt;/b&gt;</b><br>
　タグで囲われた部分を、太字にします。<br>

<div id="samplepush1" class="samplepush">[サンプルを表示]</div>
<div id="samplebox1" class="samplebox">
入力：&lt;b&gt;囲われた部分&lt;/b&gt;が、太字になります。<br>
表示：<b>囲われた部分</b>が、太字になります。
</div>

<b>■アンダーラインを引く　&lt;u&gt;～&lt;/u&gt;</b><br>
　タグで囲われた部分に、アンダーラインを引きます。<br>

<div id="samplepush2" class="samplepush">[サンプルを表示]</div>
<div id="samplebox2" class="samplebox">
入力：&lt;u&gt;囲われた部分&lt;/u&gt;に、アンダーラインが引かれます。<br>
表示：<u>囲われた部分</u>に、アンダーラインが引かれます。
</div>

<b>■文字の色を変える　&lt;font color="#カラーコードor色名"&gt;～&lt;/font&gt;</b><br>
　タグで囲われた部分の文字色を変更します。<br>

<div id="samplepush3" class="samplepush">[サンプルを表示]</div>
<div id="samplebox3" class="samplebox">
入力：&lt;font color="#ff0000"&gt;囲われた部分&lt;/font&gt;が、赤色になります。<br>
表示：<font color="#ff0000">囲われた部分</font>が、赤色になります。<br><br>
入力：&lt;font color="red"&gt;色名でも&lt;/font&gt;、赤色になります。<br>
表示：<font color="red">色名でも</font>、赤色になります。<br><br>

※カラーコードについて<br>
#と6桁の英数字で表されます。<br>
赤：#ff0000　緑：#00ff00　青：#0000ff　白：#ffffff　黒：#000000　など。<br><br>

※使用可能な色名の一部<br>
赤：red　緑：green　青：blue　白：white　黒：black　など。<br>

</div>

<b>■文字の大きさを変える　&lt;font size="文字サイズ"&gt;～&lt;/font&gt;</b><br>
　タグで囲われた部分の文字サイズを変更します。<br>
　フォントタグのサイズの指定範囲は、半角で｢1～7｣を入力します。<br>

<div id="samplepush4" class="samplepush">[サンプルを表示]</div>
<div id="samplebox4" class="samplebox">
入力：&lt;font size="1"&gt;囲われた部分&lt;/font&gt;のサイズが変わります。<br>
表示：<font size="1">囲われた部分</font>のサイズが変わります。<br><br>
入力：&lt;font size="4"&gt;囲われた部分&lt;/font&gt;のサイズが変わります。<br>
表示：<font size="4">囲われた部分</font>のサイズが変わります。
</div>

<b>■色とサイズを変える</b><br>
　上記、文字のサイズと色の、変更を組み合わせます。<br>

<div id="samplepush5" class="samplepush">[サンプルを表示]</div>
<div id="samplebox5" class="samplebox">
入力：&lt;font size="2" color="red"&gt;サイズ2の赤文字になります。&lt;/font&gt;<br>
表示：<font size="2" color="red">サイズ2の赤文字になります。</font><br><br>
入力：&lt;font size="5" color="#0000ff"&gt;サイズ5の青文字になります。&lt;/font&gt;<br>
表示：<font size="5" color="#0000ff">サイズ5の青文字になります。</font>
</div>

<b>■URLリンクを設定する　&lt;a href="遷移先のURL"&gt;～&lt;/a&gt;</b><br>
　タグで囲われた箇所をクリックで、別URLへと遷移します。<br>
　「href=""」の「""」内には、遷移先のURLを入力します。<br>

<div id="samplepush6" class="samplepush">[サンプルを表示]</div>
<div id="samplebox6" class="samplebox">
入力：&lt;a href="http://cbase.co.jp/"&gt;クリックで、別ページへと遷移。&lt;/a&gt;<br>
表示：<a href="http://cbase.co.jp/">クリックで、別ページへと遷移。</a>


<br><br>
※別ウインドウにて画面を遷移させる<br>
URLの後に「target="_blank"」と入力することで、新しいウィンドウ（別の枠）でリンクを開くことができます。<br><br>

入力：&lt;a href="http://cbase.co.jp/" target="_blank"&gt;別ウィンドウ表示。&lt;/a&gt;<br>
表示：<a href="http://cbase.co.jp/" target="_blank"&gt>別ウィンドウ表示。</a>
</div>


<font color="red">※[サンプルを表示]クリックで、記述例が表示されます。</font><br><br>

</td>
</table>
<style>
.samplebox {
margin-top: 0px;
margin-bottom: 10px;
margin-left: 10px;
margin-right: 10px;
padding: 5px;
border-width: 1px;
border-style: dotted;
}
.samplepush {
font-size: 7pt;
color: #336699;
margin-top: 10px;
margin-left: 10px;
margin-bottom: 10px;
text-decoration: underline;
}
</style>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
<script type="text/javascript">
$(function () {
    $("#samplebox1").css("display", "none");
    $("#samplepush1").click(function () {
        $("#samplebox1").toggle();
    });
    $("#samplebox2").css("display", "none");
    $("#samplepush2").click(function () {
        $("#samplebox2").toggle();
    });
    $("#samplebox3").css("display", "none");
    $("#samplepush3").click(function () {
        $("#samplebox3").toggle();
    });
    $("#samplebox4").css("display", "none");
    $("#samplepush4").click(function () {
        $("#samplebox4").toggle();
    });
    $("#samplebox5").css("display", "none");
    $("#samplepush5").click(function () {
        $("#samplebox5").toggle();
    });
    $("#samplebox6").css("display", "none");
    $("#samplepush6").click(function () {
        $("#samplebox6").toggle();
    });
});
</script>

__HTML__;

$objHtml = & new ResearchAdminHtml("文言編集");
echo $objHtml->getMainHtml($html);
exit;
