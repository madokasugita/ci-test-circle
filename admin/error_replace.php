<?php

/*
 * PG名称：エラーメッセージを変更
 * 日付　：2007.02.08
 * 作成者：cbase munakata
 *
 */

define("DIR_ROOT", "../");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseFError.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

define("ERROR_FILE_DIR", DIR_DATA);
define("ERROR_FILE", "error.txt");

$PHP_SELF = getPHP_SELF();
$SID = getSID();

if ($_POST) {
    $msg = setErrorMassage();
}
$form = getFormParts();

$html = getHtmlErrorMassegeForm($form, $msg);

$objHtml =& new MreAdminHtml("エラー文言編集", "<br />%%%%title%%%% はその質問のタイトルに、<br />%%%%num_ext%%%%は設問番号(表示用)に置き換えられます");
echo $objHtml->getMainHtml($html);
exit;

/* フォームのパーツ */
function getFormParts()
{
    global $PHP_SELF,$SID;
    $DIR_IMG = DIR_IMG;
    //エラーテキストからエラー項目取得
    $msg_old = FError :: loadErrorArray();
    $msg_const = FError :: getErrorConst();

    $txtSize = 'size="50"';

    foreach ($msg_old as $key => $val) {
        $val = html_escape($val);
        foreach ($msg_const as $k => $v) {
            if ($key == $k) {
                $form[] = "<td><font size=2>" . $v["comment"] . '</font></td><td><img src="'.$DIR_IMG.'arrow_r.gif" width="16" height="16"></td><td><font size=2>' .
                $v["prefix"] . "</font></td><td>" . FForm :: text($key, $val, "", $txtSize) . "</td><td><font size=2>" . $v["suffix"] . "</font></td>";
                unset($msg_const[$k]);
                break;
            }
        }
    }

    return $form;
}

///**　error.txtからvalue値取得　*/
//function getOriginErrorMAssage()
//{
//	$fp = fopen(ERROR_RESOURCE, 'r');
//	while (!feof($fp))
//	{
//		$msg_old[] = fgets($fp, 9182);
//	}
//	fclose($fp);
//
//	return $msg_old;
//}

/* POSTのエラーメッセージを取得　*/
function getErrorMessage($arrayPostData)
{
    $msg_const = FError :: getErrorConst();
    foreach ($arrayPostData as $key => $val) {
        if ($key !== "submit") {
            foreach ($msg_const as $k => $v) {
                if ($key == $k) {
                    $arrayMsg[] = html_escape($key) . " = " . $val;
                    unset($msg_const[$k]);
                    break;
                }
            }
        }
    }

    return $arrayMsg;
}

/*　エラーメッセージをファイルに書き込む　*/
function setErrorMassage()
{
    $msg = getErrorMessage($_POST);
    $rcd = file_exists(ERROR_FILE_DIR . ERROR_FILE);
    if ($rcd == true) {
        $fp = @ fopen(ERROR_RESOURCE, "w+");
        if ($fp) {
            $msg = join("\n", $msg);
            fwrite($fp, $msg);
            fclose($fp);
            syncCopy(ERROR_RESOURCE);
        } else {
            return getErrorMsgDiv("ファイルが開けません。");
        }
    } else {
        return getErrorMsgDiv("ファイルがありません。");
    }

    return getNormalMsgDiv("登録しました。");
}

function getHtmlErrorMassegeForm($formArrayData, $msg="")
{
    global $PHP_SELF,$SID;

    //エラー内容取得
    $errorContent = FError :: getErrorConst();

    $form1["submit"] = FForm :: submit("submit", "登録", "class='white button'");
    $form1["formExt"] = 'action="' . $PHP_SELF . '?' . $SID . '" method="post"';

    $html .= $msg.'
                <form ' . $form1["formExt"] . ' method="post">
                <table class="cont" width="800">';

    foreach ($formArrayData as $val) {
        $html .= '<tr>' . $val . '</tr>';
    }

    $html .=<<<__HTML__
                </table>
        <br>
        {$form1["submit"]}
        </form>
__HTML__;

    return $html;
}
