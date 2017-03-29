<?php

//set_time_limit(1);

define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

/**************************************************************************/
/** 対象のディレクトリ */
define('DIR_TARGET', DIR_LOG);

/** 対象のファイル(正規表現) */
define('FILE_TARGET', '*');

/** 新規ファイル作成できる？ 0->できない 1->できる */
define('FLG_NEW_FILE', 0);

/** 書き込みできる？ 0->できない 1->できる */
define('FLG_WRITE',0);

/** 表示？ 0->できない 1->できる */
define('FLG_VIEW',0);

/** 削除できる？ 0->できない 1->できる */
define('FLG_DELETE', 1);

/** テキストサイズ */
define('TEXT_COLS', 'cols="105"');
define('TEXT_ROWS', 'rows="25"');

/** 対象外のファイル */
$ng_list = array(LOG_DELETE_LOGFILE,LOG_DELETE_ANSWER,LOG_DELETE_USER);

/** ファイル名変換テーブル */

/**************************************************************************/

foreach ($_POST as $k => $v) {
    if (ereg("^submit", $k))
        $mode = str_replace('submit:', '', $k);
}

$dr = $_POST["dr"];

/**
 * mode : insert 新規登録
 * mode : udpate 上書き登録
 * mode : edit   フォーム表示
 * mode : default top画面
**/

switch ($mode) {

    case "insert" :
        $data = $_POST["insert"];
        $html .= Insert_check($data);
        $html .= TopView();

        break;
    case "update" :
        $html .= Update_Form($dr);
        $html .= TopView();
        break;
    case "delete" :
        if (!in_array($dr,$ng_list) && ereg(DIR_TARGET,$dr) && s_unlink($dr) == true ) {
            $msg = getNormalMsgDiv("削除しました。");
            operationLog(LOG_DELETE_LOGFILE,"file={$dr}");
        } else
            $msg = getErrorMsgDiv("削除に失敗しました。");
        $html .= TopView($msg);
        break;
    case "edit" :
        $html .= Print_header("ファイル編集", "※ファイルを編集します。");

        $html .= Edit_Form($dr);
        break;
    default :
        $html .= TopView();
        break;
}

$objHtml =& new MreAdminHtml("各種ログ削除");
echo $objHtml->getMainHtml($html);
exit;

function Insert_check($data)
{

    //ファイルチェック
    $regular_chk = false;
    $name_chk = true;
    $target = '.' . FILE_TARGET . '$';
    //正規表現
    $chk = array (
        '/',
        '\\',
        '<',
        '>',
        '"',
        ':',
        '*',
        '?'
    );
    $regular_chk = ereg($target, $data);
    $regular_chk2 = str_replace($chk, '', $data);
    //同名ファイルがあるか

    $name_chk = file_exists(DIR_TARGET . $data);
    if ($name_chk == false && $regular_chk == true && $data == $regular_chk2) {
        $handle = DIR_TARGET . $_POST["insert"];
        if (touch($handle)) {
            syncCopy($handle);

            return "登録しました";

        }
    } else {
        return '<font color="red">ファイル作成に失敗しました</font>';
    }
}

function Edit_Form($dr)
{
    $data = file_get_contents(DIR_TARGET.$dr);
    $data = encodeFileIn($data);
    $data = transHtmlentities($data);
    //読み取りか判断(読み込み属性の場合も更新させない)
    if (FLG_WRITE != 1 || is_writable(DIR_TARGET.$dr) == false) {
        $update = '';
        $read = 'readonly';
    } else
        $update = '<input type="submit" name="submit:update" value="登録">';

    $page = getPHP_SELF()."?".getSID();

    $back = '<a href="' . $page . '"><font size="2">一覧へ戻る</font></a>';

    $form = '<form method="post" action="' . $page . '">
                        <input type="hidden" name="dr" value="' . $dr . '">
                        <textarea ' . TEXT_COLS . ' ' . TEXT_ROWS . ' name="update" ' . $read . '>' . $data . '</textarea>
                        ' . $update . '
                        </form>';

    $html = '<table><tr><td>' . $form . '</td></tr><tr><td>' . $back . '</tr></td></table>';

    return $html;
}

function TopView($msg="")
{
    global $name_table,$ng_list;

    if (FLG_NEW_FILE == 1) {
        $insert_form = '<form method="post" action="' . getPHP_SELF()."?".getSID() . '">
                                       <input type="text" name="insert" value="">
                                       <input type="submit" name="submit:insert" value="新規作成">';

        $html .= Print_header("新規作成", "※新しくファイルを作成します。");

        $html .= '<table><tr><td>
                                       ' . $insert_form . '</td></tr></table><br>';
    }

    $html .= Print_header("ファイル一覧");
    $html .= $msg;
    $html .= '<table width="450" border="0" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
                                <tr><td valign="top">
                                <table width="450" border="0" cellpadding="1" cellspacing="1">
                                <tr align="center">
                                    <td bgcolor="#CCCCCC" width="80%"><font size="2">ファイル名</font></td>
                                    <td bgcolor="#CCCCCC" width="20%"><font size="2"><br></font></tr>';

    foreach (glob(DIR_TARGET.FILE_TARGET) as $v) {
        if(in_array($v,$ng_list))
            continue;
        if(is_dir($v))
            continue;
        if(ereg("^\.",basename($v)))
            continue;
        $file_form = '<form method="post" action="' . getPHP_SELF()."?".getSID() . '">
                                                    <input type="hidden" name="dr" value="' . $v . '">';

        $html .= '<tr bgcolor="#FFFFFF">' . $file_form . '<td><font size="2">' . basename($v) . '</font></td>';
        $html .= '<td><center>';

        if(FLG_VIEW)
            $html .= '<input type="submit" name="submit:edit" value="表示">';

        if (FLG_DELETE)
            $html .= '<input type="submit" value="削除" name="submit:delete" onClick="return confirm(\'削除しますか？\');">';
        $html .= '</center></td></tr></form>';
    }
    $html .= '</table>
                                </td></tr></table>';

    return $html;
}

function Update_Form($dr)
{
    $handle = fopen(DIR_TARGET.$dr, "w");
    //ファイルコード変換
    $update = encodeFileOut($_POST["update"]);

    if (fwrite($handle, $update) == true) {
        $html .= "登録しました";
        syncCopy($handle);
    } elseif ($update === "") {
        $html = "登録しました";
    } else {
        $html .= "書き込みに失敗しました";
    }

    return $html;
}

function Print_Header($tittle, $mes = '')
{
    $DIR_IMG = DIR_IMG;

    return '<table width="430" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td width="13" valign="middle">
                            <center>
                            <img src="{$DIR_IMG}icon_inf.gif" width="13" height="13">
                            </center>
                            </td>
                            <td width="107" valign="middle"><font size="2">' . $tittle . '</font></td>
                            <td width="287" valign="middle"><font color="#999999" size="2">' . $mes . '</font></td>
                        </tr>
                        <tr valign="top">
                            <td height="13" colspan="3">
                    <table width="430" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td height="1" background="{$DIR_IMG}line_r.gif"><img src="{$DIR_IMG}spacer.gif" width="1" height="1"></td>
                        </tr>
                    </table><br>';
}
