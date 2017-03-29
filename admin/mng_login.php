<?php
/*
 * PG名称：ログイン画面
 * 日付　：2005.09.28
 * 作成者：cbase
 *
 */
define('DIR_ROOT', "../");
require_once(DIR_ROOT.'crm_define.php');	// 外部ファイルの読み込みます。
require_once(DIR_LIB.'CbaseFDB.php');	// 外部ファイルの読み込みます。
require_once(DIR_LIB.'CbaseFManage.php');	// 外部ファイルの読み込みます。
require_once(DIR_LIB.'CbaseFCheck.php');	// 外部ファイルの読み込みます。
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();		// セッションを開始します。
// 実行しているファイル名を取得します。
$strPageName = getPHP_SELF();
$strPageTitle = "ログイン";
$strUrl      = "menu_top.php";

// 入力チェックの定義を設定します。
$input_check = array("id" => "1&2&3",
                    "pw" => "1&2&3");

// 入力項目名の定義を設定します。
$input_name = array("id" => "ID",
                    "pw" => "パスワード");

// ボタンで処理を切り替えます。
if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['button'] === "ログイン") {
    // POST値を一括入力チェックを行います。
    $aryErrMsg = array();
    list($intErrCount, $aryErrMsg) = checkBatchPostData($_POST);

    if ($intErrCount == 0) {
        //$db = getDbConnection();	// DBをオープンします。
        $db=$con;
        if ($db) {
            // SQL文を取得します。
            $strSql = "";
            $strSql = "SELECT * ";
            $strSql .= sprintf("FROM %s ", T_MUSR);
            $strSql .= sprintf("WHERE id = %s ", FDB::quoteSmart($_POST['id']));
            $strSql .= sprintf("AND pw = %s;", FDB::quoteSmart($_POST['pw']));
            $ret = $db->query($strSql);

            // SQL実行エラーをチェックします。
            $aryMad = array();
            if (FDB::isError($ret)) {
                $db->disconnect();	// DBをクローズします。
                $strMessage = "認証エラー";
                //printReturnForm($prmPageTitle, $strMessage, $strPageName);
                echo $strMessage;
                exit;
            } else {
                if ($ret->numRows() > 0) {
                    $aryMad = $ret->fetchRow(MDB2_FETCHMODE_ASSOC);
                }
                $ret->free();
                foreach ($aryMad as $k=>$v) $_SESSION[$k]=$v;
                //リモートIPセット
                $_SESSION["IP"] = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
            }
        }

        header("Location: ".$strUrl."?".getSID());		// リダイレクトします。
        exit;
    }

    $strMessage = "IDとパスワードを入力してください。";

$html = <<<HTML
<TABLE border="0" cellspacing="1" cellpadding="4">

<FORM method="post" action="./{$strPageName}">
<TABLE width="400" border="0" cellspacing="1" cellpadding="4">
<TR>
    <TD width="40" valign="top" bgcolor="#E8E8E8"><SPAN class="grey_13">ID</SPAN></TD>
    <TD width="80" bgcolor="#F5F5F5"><INPUT type="text" name="id" size="20" value="{$_POST['maid']}"></TD></TR>
<TR>
    <TD valign="top" bgcolor="#E8E8E8"><SPAN class="grey_13">パスワード</SPAN></TD>
    <TD bgcolor="#F5F5F5"><INPUT type="password" name="pw" size="20" value="{$_POST['ma_pw']}"></TD></TR>
</TABLE>
<BR>
<TABLE border="0" cellspacing="1" cellpadding="4">
<TR>
    <TD><INPUT type="submit" name="button" value="ログイン"></TD></TR>
</TABLE>
</FORM>
HTML;
} else {
    $strMessage = "<br> ログイン";
    $html = $strMessage;

$html .= <<<HTML
<FORM method="post" action="./{$strPageName}">
<TABLE border="0" cellspacing="1" cellpadding="4">
<TR>
    <TD width="40" valign="top" bgcolor="#E8E8E8"><SPAN class="grey_13">ID</SPAN></TD>
    <TD width="50" bgcolor="#F5F5F5"><INPUT type="text" name="id" size="20"></TD></TR>
<TR>
    <TD valign="top" bgcolor="#E8E8E8"><SPAN class="grey_13">PW</SPAN></TD>
    <TD bgcolor="#F5F5F5"><INPUT type="password" name="pw" size="20"></TD></TR>
</TABLE>
<BR>
<TABLE border="0" cellspacing="1" cellpadding="4">
<TR>
    <TD><INPUT type="submit" name="button" value="ログイン"></TD></TR>
</TABLE>
</FORM>
<br>
<br>
<script src=https://seal.verisign.com/getseal?host_name=www.cbase.co.jp&size=S&use_flash=YES&use_transparent=YES&lang=ja></script>
HTML;
}

$objHtml =& new ResearchAdminHtml(RESEARCH_TITLE);
echo $objHtml->getMainHtml($html);
exit;
