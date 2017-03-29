<?php
/**
 * PG名称：回答後ページ
 * 日　付：2007/04/29
 * 作成者：cbase Kido
 */
/******************************************************************************************************/

define("DIR_ROOT", "");
require_once DIR_ROOT."crm_define.php";
require_once DIR_LIB.'360_FHtml.php';
require_once DIR_LIB.'CbaseEncoding.php';
require_once(DIR_LIB.'CbaseMailBody.php');
require_once(DIR_LIB.'CbaseFDB.php');
encodeWebAll();
session_start();
checkAuthUsr360();
define('PAGE_TITLE','####thanks_title####');
define("C_PAGE_BACK","360_menu.php?".getSID());

//全回答完了メールを送信
if ($Setting->mailFlagValid()) {
    //判定
    $comp_flag = TRUE;
    foreach ($GLOBALS['_360_sheet_type'] as $sheettype => $value) {
        if (is_array($_SESSION['login'][$sheettype])) {
            foreach ($_SESSION['login'][$sheettype] as $usertype => $member) {

                /* 回答者タイプ以外判定しない */
                if(!in_array($usertype, range(0, INPUTER_COUNT))) continue;

                foreach ($member as $udata) {
                    $rid = "rid".sprintf("%03d",$sheettype).sprintf("%02d",$usertype);
                    $serial_no = $udata['serial_no'];
                    if ($_SESSION['login']['eventlist'][$rid][$serial_no] != 2) {
                        $comp_flag = FALSE;
                        break;
                    }
                }
            }
        }
    }
    //メール送信
    if ($comp_flag == TRUE) {
        $user=$_SESSION['login'];
        $format = Get_MailFormat(MFID_COMPLETE);
        $result = Pc_Mail_Send($user, $format[0], array('mrid'=>-1));
        error_log(date("Y/m/d H:i:s")."\t".$user["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);
    }
}

if($_SESSION['muid'])
    $strEnd = '<button onclick="window.close()">閉じる</button>';
else
    $strEnd = '<a href="'.C_PAGE_BACK.'">####linkname_mypage####</a>';

if ($comp_flag == TRUE && $Setting->mailFlagValid()) {
    $strHtml_ ='
<div style="padding-top:20px;width:700px;margin:0 auto" >####complete_title####</div>
<br>
<br>

<table style="width:700px;text-align:right;margin-left:auto;margin-right:auto" width="800" border="0" cellpadding="10" cellspacing="0" bgcolor="#CCCCCC">
<tr>
<td align="center">
<pre>####complete_1####</pre>
<p>'.$strEnd.'</p>
</td>
</tr>
</table>
<br>
<br>
';
} else {
    $strHtml_ ='
<div style="padding-top:20px;width:700px;margin:0 auto" >####thanks_title####</div>
<br>
<br>

<table style="width:700px;text-align:right;margin-left:auto;margin-right:auto" width="800" border="0" cellpadding="10" cellspacing="0" bgcolor="#CCCCCC">
<tr>
<td align="center">
<pre>####thanks_1####</pre>
<p>'.$strEnd.'</p>
</td>
</tr>
</table>
<br>
<br>
';
}
$strHTML =HTML_header();
$strHTML .= HTML_top();//ロゴの帯
$strHTML .= $strHtml_;
$strHTML .= HTML_bottom();//コピーライト帯
$strHTML .= HTML_footer();//フッタ
print $strHTML;
exit;
