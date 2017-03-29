<?php

/*
 * PG名称：検索用SQL文を出力するプログラムのトップメニュー
 * 日  付：2005/04/18
 * 作成者：cbase Akama
 */

define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFCondition.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

/** 本体部分のプログラム名 */
define("C_MAIN_PG", "enq_sqlsearch.php");

$_SESSION['enq_target_list']['recent_page'] = basename(__FILE__);

if ($_POST["main"] == "newcopy") {
    if ($_POST["dup_cnid"] <> "empty") {
        //コピーして新規作成
        header("Location: " . DOMAIN . DIR_MAIN . DIR_MNG . C_MAIN_PG . "?cnid=new&copy=" . $_POST["dup_cnid"] . '&' . getSID());
    } else {
        //無視
        header("Location: " . DOMAIN . DIR_MAIN . DIR_MNG . C_MAIN_PG . "?cnid=new" . '&' . getSID());
    }
}

if ($_REQUEST['mode']=='del' && $_REQUEST['hash'] == getHash360($_REQUEST['cnid'])) {
    MailCondition::delete($_REQUEST['cnid']);
}

$aryData = Get_Condition(null, null, $_SESSION["muid"]);
$showHtml = getFirst($aryData);

$objHtml =& new MreAdminHtml("配信条件設定");
echo $objHtml->getMainHtml($showHtml);
exit;

/*
 * 画面１のデータを取得
 * @param  conditionデータ
 * @return html
 */
function getFirst($prmData)
{
/*
        <table style="margin:20px 30px"width="610" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td width="431" align=left align="center">'
                    .$sub_title1.
                    '<table width="420" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="170" height="24" align="center">
                                    <font size="2">条件を新規作成</font>
                            </td>
                            <td width="18"><img src="' . DIR_IMG . 'arrow_r.gif" width="16" height="16"></td>
                            <td width="242"><a href="' . C_MAIN_PG . '?cnid=new&' . getSID() . '"><img src="' . DIR_IMG . 'new_l.gif" width="70" height="21" border=0></a></td>
                        </tr>
                        <tr>
                            <td colspan="3"><img src="' . DIR_IMG . 'spacer.gif" width="1" height="5"></td>
                        </tr>
                        <tr>
                            <td height="24" align="center">
                                <font size="2">条件をコピー作成</font>
                            </td>
                            <td><img src="' . DIR_IMG . 'arrow_r.gif" width="16" height="16"></td>
                            <td>
                                <table width="242" border="0" cellspacing="0" cellpadding="0">
                                    <form action="' . getPHP_SELF() . '?' . getSID() . '" method="POST">
                                    <tr>
                                        <td width="150" valign="middle"><font size="2">
                                            <select name="dup_cnid">
                                                <option value="empty">-- ここから選択してください --</option>
        ';

    foreach ($prmData as $value) {
        //TODO:name条件は不要ではないか？
        if($value["name"]==="" || is_null($value['pgcache'])) continue;
        $strResult .= '
                                                        <option value="' . $value["cnid"] . '">' . $value["name"] . '</option>';
    }

    $strResult .= '
                                            </select></font>
                                        </td>
                                        <td width="60" valign="middle" align="center">
                                            <input type="hidden" name="main" value="newcopy">
                                            <input type="submit" name="duplicate" value="複製新規">
                                        </td>
                                    </tr>
                                    </form>
                                </table>
                            </td>
                        </tr>
                    </table>
                    <br>'
                    */
    $strResult =<<<__HTML__
        <table class="cont">
            <tr>
                <th width="30">
                    ID
                </th>
                <th width="300">
                    名称
                </th>
                <th width="50">
                    対象者<br>確認
                </th>
                <th width="50">
                    CSV
                </th>
                <th width="50">
                    編集
                </th>
            </tr>
__HTML__;

    $mc = new MailCondition();

    $i = 0;
    foreach ($prmData as $key => $value) {
        if($value["name"]==="" || $value["name"]===null || $value["flgt"]==-1)
            continue;

        $edit = is_null($value['pgcache'])? '': '<a href="' . C_MAIN_PG . '?cnid=' . $value["cnid"] . '&' . getSID() . '"><img src="' . DIR_IMG . 'edit.gif" width="35" height="17" align="middle" border=0></a>';
        $hash = getHash360( $value["cnid"] );
        $edit.='<a onclick="return confirm(\'条件を削除しますがよろしいですか？\')" href="' . getPHP_SELF() . '?mode=del&hash='.$hash.'&cnid=' . $value["cnid"] . '&' . getSID() . '">
                <button class="white button">削除</button>
            </a>';

        $csv = '<a href="360_mail_target_list_dl.php?cnid=' . $value["cnid"] . '&' . getSID() . '"><img src="' . DIR_IMG . 'edit.gif" width="35" height="17" align="middle" border=0></a>';

        $list = 'mail_target_list.php?'.getSID().'&id='.$value['cnid'];
        $count = '<a href="'.$list.'">確認</a>';
        $csv = '<a href="'.'360_mail_target_list_dl.php?'.getSID().'&id='.$value['cnid'].'">CSV</a>';

        $strResult .= <<<__HTML__
                <tr>
                    <td height="21" align=center>{$value["cnid"]}</td>
                    <td>{$value["name"]}</td>
                    <td align="center">{$count}</td>
                    <td align="center">{$csv}</td>
                    <td align="center">
                        {$edit}
                    </td>
                </tr>
__HTML__;
    }

    $strResult .= '</table>';

    return $strResult;
}
