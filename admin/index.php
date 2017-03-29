<?php
//define('DEBUG', 1);
/**
 *
 * @version 1.2
 *
 * 2007/07/27 ver1.1 認証の結果をログ出力するように
 * 2007/10/04 ver1.2 ログイン時にセッションにauthsetを追加
 */

define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFCheck.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();
setSslOnly();
//2007/10/04追加
require_once(DIR_LIB.'CbaseAuthSet.php');
// 実行しているファイル名を取得します。
$strPageName = getPHP_SELF();

// 入力チェックの定義を設定します。
$input_check = array (
        "id" => "1&2", //&3",  //入力チェックから英数チェックを排除
    "pw" => "1&2"
); //&3");

// ボタンで処理を切り替えます。
if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['button'] === "Enter") {
    //IDとパスワードに含まれる空白文字を取り除く
    $_POST['id'] = trim($_POST['id']);
    $_POST['pw'] = trim($_POST['pw']);

    // POST値を一括入力チェックを行います。
    $aryErrMsg = array ();
    list ($intErrCount, $aryErrMsg) = checkBatchPostData($_POST);

    if ($intErrCount == 0) {
        //$db = getDbConnection();	// DBをオープンします。
        $db = $con;
        if ($db) {
            // SQL文を取得します。
            $strSql = "";
            $strSql = "SELECT * ";
            $strSql .= sprintf("FROM %s ", T_MUSR);
            //$strSql .= sprintf("WHERE id = %s ", FDB::quoteSmart($_POST['id']));
            $strSql .= sprintf("WHERE id = %s ", FDB::quoteSmart($_POST['id']));
            //$strSql .= sprintf("AND pw = %s;", FDB::quoteSmart($_POST['pw']));
            $ret = $db->query($strSql);

            // SQL実行エラーをチェックします。
            $aryMad = array ();
            if (MDB2 :: isError($ret)) {
                $db->disconnect(); // DBをクローズします。
                $strMessage = "認証エラー";
                echo $strMessage;

                login_log('dberror', $_POST['id'], $_POST['pw']); //ver1.1/
                exit;
            } else {
            	if ($ret->numRows() <= 0) {
                    //ver1.1/
                    login_log('failure', $_POST['id'], $_POST['pw']);
                } else {
                    $aryMad = $ret->fetchRow(MDB2_FETCHMODE_ASSOC);
                    $ret->free();

                    //ログイン期限切れ処理
                    if ($aryMad['muid'] != 1 && !is_zero(ADMIN_LOGIN_PERIOD) && strtotime(ADMIN_LOGIN_PERIOD) < time()) {
                        $expiration = true;
                    //もしも規定回数以上にパスワードを間違えていたら
                    } elseif ($Setting->limitPwLessOrEqual($aryMad[C_PASSWORD_MISS])) {
                        $pwmissflag = true;
                    } elseif (!validPwHash($_POST['pw'], $aryMad['pw'])) {
                        $data = array();
                        //パスワードが間違っていたときの処理
                        if (!$aryMad[C_PASSWORD_MISS]) {
                            $data = array (	C_PASSWORD_MISS => 1); //はじめてパスワードを間違えたときの処理 パスワード間違い回数を1とする。
                        } else {
                            $data = array (
                                C_PASSWORD_MISS => C_PASSWORD_MISS . "+1"
                            ); //パスワード間違い回数を1増やす
                        }
                        FDB :: update(T_MUSR, $data, "where muid = " . FDB::escape($aryMad['muid']));
                        $_POST['pw'] = "";
                        login_log('pwmiss', $_POST['id'], $_POST['pw']); //ver1.1/
                    } else {
                        if($aryMad[C_PASSWORD_MISS])
                            FDB :: update(T_MUSR, array(C_PASSWORD_MISS=>0), "where muid = " . FDB::escape($aryMad['muid']));

                        // セッションを開始します。
                        $GLOBALS['AuthSession']->sessionRestart();
                        unset($aryMad['pw']);

                        foreach ($aryMad as $k => $v)
                            $_SESSION[$k] = $v;
                        //リモートIPセット
                        $_SESSION["IP"] = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
                        setDivAuth($_SESSION['muid']);//360度カスタマイズ
                        $_SESSION["DIR"] = DIR_MAIN.DIR_MNG;
                        //2007/10/04追加
                        $dao =& new AuthSetDAO();
                        $_SESSION['auth_set'] = $dao->getByMuid($_SESSION['muid']);

                        login_log('success', $_POST['id'], $_POST['pw']); //ver1.1/

                        //mailtargetのcookie削除
                        foreach ($_COOKIE as $k=>$v) {
                            if(is_zero(strpos($k, "mailtarget")))
                                setcookie ($k, "", time() - 3600);
                        }

                        //pw変更済み、変更後6ヶ月以内なら次へ
                        if(is_good($_SESSION['pdate']) && strtotime($_SESSION['pdate']) > strtotime("-6 month"))
                            header("Location: index2.php?" . getSID());
                        else
                            header("Location: change_pw.php?" . getSID());
                        exit;
                    }
                }
            }
        }
    }

    //エラーチェック
    if ($aryErrMsg['id'][0] || $aryErrMsg['pw'][0]) {
        $strMessage = "IDとパスワードを入力してください。";
    } elseif ($pwmissflag) {
        $strMessage = getMessage('pwmiss_infomation');
    } elseif ($expiration) {
        $strMessage = "利用期限を過ぎています。";
    } else {
        $strMessage = "IDまたはパスワードが不正です。";
    }
}

if ($_GET['mode']=="re_auth") {
    $strMessage = "IDまたはパスワードが不正です。";
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

    <div align="center">
    <form method="post" action="./{$strPageName}">
            <table id="form" border="0" cellpadding="0" cellspacing="0" width="300">
                <tr class="tr1">
                    <td colspan="3">ログイン情報を入力</td>
                </tr>
                <tr class="alert">
                    <td colspan="3">{$strMessage}</td>
                </tr>
                <tr>
                    <td width="100" class="tr2" align="right">I D ：</td>
                    <td width="190"><input name="id" type="text" value="" size="20" style="width:100px"></td>
                </tr>
                <tr>
                    <td class="tr2" align="right">PW ：</td>
                    <td><input name="pw" type="password" value="" size="20" style="width:100px"></td>
                </tr>
                <tr>
                    <td class="submit" colspan="3"><input type="submit" name="button" value="Enter" class="white button"></td>
                </tr>
            </table>
    </form>
    </div>

    <div align="center">
        <table width="450" border="0" cellspacing="1" cellpadding="3">
            <tr>
                <td width="140"> <script type="text/javascript" src="{$GDF->get('SSL_SEAL')}"></script>
                </td>
                <td class="exam">このページでは、お客さまの個人情報をご入力いただくページについて、「SSL」（Secure Socket Layer）と呼ばれる特殊暗号通信技術を使用しています。当社のウェブサーバはThawte社より認証された安全な通信が可能となっています。左記のシールをクリックしていただくと、Thawte社の発行する証明書が表示されます。</td>
            </tr>
        </table>
    </div>
    </div><!-- contents -->
</div><!-- container -->

<div id="footer">
    {$GDF->get('CBASE_COPYRIGHT')}&nbsp;
</div>
__HTML__;

$css = <<<__CSS__
body,div{ margin:0; padding:0; font-size:12px;}
table{ font-size:12px; }

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
    padding: 90px;
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
window.onload = function () { document.getElementsByName('id')[0].focus(); }
__JS__;

$objHtml =& new CbaseHtml(RESEARCH_TITLE);
$objHtml->setSrcCss($css);
$objHtml->setSrcJs($js);
echo $objHtml->getMainHtml($html);
exit;

/**
 * ログイン認証結果のログを取る
 */
function login_log($result, $id, $pw)
{ //ver1.1/

    $data[] = date('Y-m-d H:i:s');
    $data[] = $result; //failure or success or dberror
    $data[] = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
    $data[] = $id;
    //	$data[] = $pw;

    error_log(implode("\t", $data) . "\n", 3, LOG_LOGIN_MNG);
}
