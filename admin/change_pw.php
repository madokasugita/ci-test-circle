<?php
//define("DEBUG",1);
define('DIR_ROOT', "../");
require_once(DIR_ROOT."crm_define.php");
require_once (DIR_LIB . 'CbaseFDB.php');
require_once(DIR_LIB.'CbaseEncoding.php');
encodeWebAll();

session_start();
if (!$_SESSION["muid"]) {
    header("Location: index.php?mode=re_auth");
    exit;
}
//Check_AuthMng(basename(__FILE__));

// ボタンで処理を切り替えます。
if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['button'] === "Enter") {
    if ($_POST['pw'] != $_POST['confirm']) {
        $body = getFormTable("確認用パスワードが異なっています。");
    } elseif ($_POST['pw'] == $_SESSION['pw']) {
        $body = getFormTable("変更前と異なるパスワードを入力ください。");
    } elseif (is_good($error = get360PwError($_POST['pw'], $_SESSION['id']))) {
        $body = getFormTable($error);
    } else {
        $data = array(
            "pw" => getPwHash(trim($_POST['pw'])),
            "pdate" => date("Y-m-d H:i:s"),
            C_PASSWORD_MISS => 0,
        );

        if (!is_false(FDB :: update(T_MUSR, FDB::escapeArray($data), "where muid = " . FDB::escape($_SESSION['muid'])))) {
            $_SESSION['pw'] = $data['pw'];
            $_SESSION['pdate'] = $data['pdate'];
            $_SESSION['C_PASSWORD_MISS'] = $data[C_PASSWORD_MISS];
        }
        $URL = "./index2.php?" . getSID();
        $body = <<<__HTML__
<script>
<!--
function jumpPage()
{
 location.href = '{$URL}';
}
setTimeout(jumpPage,3000);
//-->
</script>
<table id="form" border="0" cellpadding="0" cellspacing="0" width="600">
    <tr class="tr1">
        <td colspan="3">パスワードの変更</td>
    </tr>
    <tr>
        <td class="tr3" colspan="3">
            <p>変更が完了しました。メニュー画面へ遷移します。</p>
            自動で遷移しない場合は<a href="{$URL}">こちらのリンク</a>をクリックください。
        </td>
    </tr>
</table>
__HTML__;
    }
} else {
    $body = getFormTable();
}
$html = <<<__HTML__
<!--[if IE ]>
<style type="text/css">
<!--
input { font-family:'MS UI Gothic'; }
//-->
</style>
<![endif]-->

<div id="header">
    &nbsp;{$GDF->get('RESEARCH_TITLE')}
</div>

<div id="container">
    <div id="contents">
    <h2>新しいパスワードへの変更をお願いします。</h2>
    <div align="center">
        {$body}
    </div>

    </div><!-- contents -->
</div><!-- container -->

<div id="footer">
    {$GDF->get('CBASE_COPYRIGHT')}&nbsp;
</div>
__HTML__;

$css = <<<__CSS__
body,div,h2{ margin:0; padding:0;}
table{ font-size:12px; }

h2{
    font-size:14px;
    text-align:center;
    height: 20px;
    padding-bottom: 10px;
}

#header
{
    font-size:15px;
    color:#555555;
    background:#cccccc;
    font-weight:bold;
    position:absolute;
    top:0;
    padding:10px 0 10px 0;
    width:100%;
}

div#container{
    position:relative;
    min-height: 100%;
}
* html div#container {
    height:100%;
}

#contents{
    padding: 60px;
}

#footer
{
    font-size:10px;
    color:#666666;
    background:#cccccc;
    text-align:right;
    position:absolute;
    bottom:0;
    padding:10px 0 10px 0;
    width:100%;
}

table#form {
    border-radius: 3px;
    -webkit-border-radius: 3px;
    -moz-border-radius: 3px;
    border: 1px solid #CCC;
    background-image: -moz-linear-gradient(top, #EFEFEF, #F3F4F5);
    background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#EFEFEF), to(#F3F4F5), color-stop(1,#C8C9CA));
    box-shadow: 1px 1px 1px #efefef;
    -moz-box-shadow: 1px 1px 1px #ddd;
    -webkit-box-shadow: 1px 1px 3px #efefef;
}

.tr1 td
{
  font-size:15px;
  background:#cccccc;
  color:#224466;
  font-weight:bold;
  text-align:center;
  padding:10px;
}

.alert td
{
    text-align: center;
    font-weight:bold;
    color:#ff4500;
    height: 20px;
}
.tr2
{
  font-size:12px;
  color:#336699;
  font-weight:bold;
}

.tr3
{
    text-align: center;
    padding:10px;
    font-size:14px;
}

.submit{
    padding: 15px;
    text-align:center;
}

.exam
{
  font-size:10px;
  color:#666666;
  font-weight:normal;
  line-height:1.5em;
}

/* button */
.button {
    display: inline-block;
    zoom: 1; /* zoom and *display = ie7 hack for display:inline-block */
    *display: inline;
    vertical-align: baseline;
    margin: 0 2px;
    outline: none;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    font: 12px/100% Arial, Helvetica, "MS Gothic",  Osaka-mono, monospacesans-serif;
    padding: .2em .4em .26em;
    padding: .36em .6em .36em\9; /*IE8*/
    *padding: .3em .2em .2em; /*IE7*/
    _padding: .3em .2em .2em; /*IE6*/
    text-shadow: 0 1px 1px rgba(0,0,0,.1);
    -webkit-border-radius: .2em;
    -moz-border-radius: .2em;
    border-radius: .2em;
    -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.1);
    -moz-box-shadow: 0 1px 2px rgba(0,0,0,.1);
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}

.button:not(:target) {
    display: inline-block;
    zoom: 1; /* zoom and *display = ie7 hack for display:inline-block */
    *display: inline;
    vertical-align: baseline;
    margin: 0 2px;
    outline: none;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    font: 12px/100% Arial, Helvetica, "MS Gothic",  Osaka-mono, monospacesans-serif;
    padding: .46em .6em .46em\9;
    text-shadow: 0 1px 1px rgba(0,0,0,.1);
    -webkit-border-radius: .2em;
    -moz-border-radius: .2em;
    border-radius: .2em;
    -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.1);
    -moz-box-shadow: 0 1px 2px rgba(0,0,0,.1);
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}

@media screen and (-webkit-min-device-pixel-ratio:0) {
    .button {
    display: inline-block;
    zoom: 1; /* zoom and *display = ie7 hack for display:inline-block */
    *display: inline;
    vertical-align: baseline;
    margin: 0 2px;
    outline: none;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    font: 12px/100% Arial, Helvetica, "MS Gothic",  Osaka-mono, monospacesans-serif;
    padding: .36em .64em .56em;
    text-shadow: 0 1px 1px rgba(0,0,0,.1);
    -webkit-border-radius: .2em;
    -moz-border-radius: .2em;
    border-radius: .2em;
    -webkit-box-shadow: 0 1px 2px rgba(0,0,0,.1);
    -moz-box-shadow: 0 1px 2px rgba(0,0,0,.1);
    box-shadow: 0 1px 2px rgba(0,0,0,.1);
}
}

.button:hover {
    text-decoration: none;
}
.button:active {
    position: relative;
    top: 1px;
}

/* color styles
---------------------------------------------- */

/* white */
.white {
    color: #454545;
    border: solid 1px #b7b7b7;
    background-color: #fff;
    background-image: -webkit-gradient(linear, left top, left bottom, from(#fff), to(#ededed));
    background-image: -moz-linear-gradient(top,  #fff,  #ededed);
    filter:  progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffff', endColorstr='#ededed');
    -webkit-transition: border,text-shadow 0.5s;
    -moz-transition: border,text-shadow 0.5s;
    -o-transition: border,text-shadow 0.5s;
    transition: border,text-shadow 0.5s;
}
.white:hover {
    color: #369;
    border: solid 1px #369;
    text-shadow: 1px 1px 1px #a6abad;
    background: rgb(250,250,250); /* Old browsers */
    background: -moz-linear-gradient(top,  rgba(250,250,250,1) 0%, rgba(238,238,238,1) 50%, rgba(232,232,232,1) 51%, rgba(250,250,250,1) 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(250,250,250,1)), color-stop(50%,rgba(238,238,238,1)), color-stop(51%,rgba(232,232,232,1)), color-stop(100%,rgba(250,250,250,1))); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top,  rgba(250,250,250,1) 0%,rgba(238,238,238,1) 50%,rgba(232,232,232,1) 51%,rgba(250,250,250,1) 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top,  rgba(250,250,250,1) 0%,rgba(238,238,238,1) 50%,rgba(232,232,232,1) 51%,rgba(250,250,250,1) 100%); /* Opera 11.10+ */
    background: -ms-linear-gradient(top,  rgba(250,250,250,1) 0%,rgba(238,238,238,1) 50%,rgba(232,232,232,1) 51%,rgba(250,250,250,1) 100%); /* IE10+ */
    background: linear-gradient(top,  rgba(250,250,250,1) 0%,rgba(238,238,238,1) 50%,rgba(232,232,232,1) 51%,rgba(250,250,250,1) 100%); /* W3C */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#fafafa', endColorstr='#fafafa',GradientType=0 ); /* IE6-9 */
}
.white:active {
    color: #999;
}

.button{
    width: 100px;
}
__CSS__;

$js = <<<__JS__
window.onload = function () { document.getElementsByName('pw')[0].focus(); }
__JS__;

$objHtml =& new CbaseHtml(RESEARCH_TITLE);
$objHtml->setSrcCss($css);
$objHtml->setSrcJs($js);
echo $objHtml->getMainHtml($html);
exit;

function getFormTable($strMessage="")
{
    $strPageName = getPHP_SELF().'?'.getSID();

    return <<<__HTML__
        <form method="post" action="./{$strPageName}">
        <table id="form" border="0" cellpadding="0" cellspacing="0" width="300">
            <tr class="tr1">
                <td colspan="3">パスワードの変更</td>
            </tr>
            <tr class="alert">
                <td colspan="3">{$strMessage}</td>
            </tr>
            <tr>
                <td width="100" class="tr2" align="right">新PW ：</td>
                <td width="190"><input name="pw" type="password" value="" size="20" style="width:100px"></td>
            </tr>
            <tr>
                <td class="tr2" align="right">新PW(確認) ：</td>
                <td><input name="confirm" type="password" value="" size="20" style="width:100px"></td>
            </tr>
            <tr>
                <td class="submit" colspan="3"><input type="submit" name="button" value="Enter" class="white button"></td>
            </tr>
        </table>
    </form>
__HTML__;
}
