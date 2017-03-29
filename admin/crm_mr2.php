<?php
/**
 * ---------------------------------------------------------
 * crm_mr2.php	by ipsystem@cbase.co.jp
 * ---------------------------------------------------------
 */

define('CNT_FINISHED_RSV',	30);	//済みの予約リストの表示件数

//define('DEBUG'		, 0);
define('DIR_ROOT'	, "../");
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'CbaseFEventMail.php');
require_once(DIR_LIB.'CbaseFMail.php');
require_once(DIR_LIB.'CbaseFGeneral.php');
require_once(DIR_LIB.'CbaseFunction.php');
require_once(DIR_LIB.'MreAdminHtml.php');
require_once(DIR_LIB.'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

$DIR_IMG = DIR_IMG;
$PHP_SELF = getPHP_SELF()."?".getSID();

$url1 = "crm_mr1.php";
$len1 = 8;//ひな型プルダウン内の文字数

//日付処理
if ($_POST['t']) {
    $tmp = Convert_Date('db',array($_POST['y'],$_POST['m'],$_POST['d'],$_POST['h'],$_POST['i'],$_POST['s']));
    if($tmp)	$_POST['mdate'] = $tmp;
}

$msg1 = "配信設定編集";
$msg2 = "※既存の配信予約を編集します。";

//変数チェック
if ($_POST['doreserve_x'] && $_POST['mrid']) {
    //データ取得
    $aryEvent = Get_MailEvent('id',$_POST['mrid'],'mrid','desc', $_SESSION['muid']);
    $event = $aryEvent[0];

    //日付が10分以上将来で
    $tmp = explode(' ', $event['mdate']);
    $mdate = mktime(
                    substr($tmp[1],0,2),
                    substr($tmp[1],3,2)+10,
                    0,
                    substr($tmp[0],5,2),
                    substr($tmp[0],8,2),
                    substr($tmp[0],0,4)
                );

    if ($mdate>mktime()) {
        //更新
        $event['flgs'] = 0;
        Save_MailEvent('update', $event);
    } else
        $msg2 =<<<__HTML__
<font color=#ff0000>※設定した配信日の日付が有効ではありません。</font>
__HTML__;
} elseif (is_good($_POST['save_x']) && (is_void($_POST['name']) || !$_POST['mdate'] && $_POST['t'])) {
    $msg2 = "";
    if(is_void($_POST['name']))
        $msg2 = "<font color=\"#ff0000\">※配信名称が指定されておりません。</font><br>";

    if(!$_POST['mdate'] && $_POST['t'])
        $msg2 .= "<font color=\"#ff0000\">※配信日が不正な日付です。</font>";
} elseif (!$_POST['mrid']) {
    $msg1 = "配信設定追加";
    $msg2 = "※新しく配信予約を行います。";

    if ($_POST['t']>0) {
        $_POST['muid'] = $_SESSION['muid'];
        $_POST['mrid'] = Save_MailEvent('new', $_POST);
        if (!$_POST['mrid']) {
            echo 'NG insert';
        }
    }
} else {
    /*
     *
     * 必須チェック
     *
     */

    if ($_POST['t']>0) {
        if(!Save_MailEvent('update', $_POST))		echo 'NG update';

        $msg2 =<<<__HTML__
<font color=#ff0000>※保存しました。</font>
__HTML__;
    }
}


//メール雛形リスト出力
if ($_POST['mrid']) {
    $aryEvent = Get_MailEvent('id',$_POST['mrid'],'mrid','desc', $_SESSION['muid']);
    $event = $aryEvent[0];
    $aryDate = Convert_Date('array', $event['mdate']);
}

$time = time();

$aryMail = Get_MailFormat(-1,'mfodr','', $_SESSION['muid']);
$mfidOption = '';
foreach ($aryMail as $mail) {
    $selected = ($mail['mfid']==$event['mfid'])? ' selected':'';
    if($mail['name']=="")
        $mail['name'] = '雛形#'.$mail['mfid'];
    elseif(mb_strlen($mail['name'])>$len1)
        $mail['name'] = @mb_substr($mail['name'], 0, $len1).'...';
    $mail = escapeHtml($mail);
    $mfidOption .= <<<__HTML__
<option value="{$mail['mfid']}"{$selected}>{$mail['name']}</option>\n
__HTML__;
}

$flglRadio = <<<__HTML__
<input type="radio" name="flgl" value="0"> 記録しない
<input type="radio" name="flgl" value="1"> 記録する
__HTML__;
$flglRadio = preg_replace("/value=\"{$event['flgl']}\"/", "value=\"{$event['flgl']}\" checked", $flglRadio);

//タグ処理
$event['name'] = html_escape($event['name']);

$yOption = getOptionTag2($yyyy, (!$aryDate['y'])? date('Y'):$aryDate['y']);
$mOption = getOptionTag2($mm, (!$aryDate['m'])? date('m'):$aryDate['m']);
$dOption = getOptionTag2($dd, (!$aryDate['d'])? date('d')+1:$aryDate['d']);
$hOption = getOptionTag2($hh, (!$aryDate['h'])? date('H')+1:$aryDate['h']);
$iOption = getOptionTag2($ii, (!$aryDate['i'])? 0:$aryDate['i']);

if ($event['flgs']!=2) {
    $url = "crm_mr1.php?".getSID();
    $js = <<<__JS__
window.onload = function () {
    location.href = '{$url}';
}
__JS__;
}

/* ----Start HTML----- */

$backBar = D360::getBackBar($url1.'?'.getSID());
$html = <<<__HTML__
{$backBar}
<table width="610" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="431">
<table width="430" border="0" cellpadding="0" cellspacing="0">
<tr>
<td width="13" valign="middle">
<center>
<img src="{$DIR_IMG}icon_inf.gif" width="13" height="13">
</center>
</td>
<td width="80" valign="middle"><font size="2">基本情報</font></td>
<td width="300" valign="middle"><font color="#999999" size="2">※作成時の基本情報は変更することはできません。</font></td>
</tr>
<tr valign="top">
<td height="2" colspan="3">
</td>
</tr>


</table>
<br>
<table align="center" width="300" border="0" cellpadding="0" cellspacing="0" bgcolor="#4c4c4c">
<tr>
<td width="270" valign="top">
<table width="300" border="0" cellpadding="0" cellspacing="1">
<tr>
<td width="300" valign="middle" bgcolor="#f6f6f6">


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

{$event['mrid']}</td>
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
<td>{$event['cdate']}</td>
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
<td>{$event['udate']}</td>
</tr>
<tr>
<td colspan="4">

<div align="left"><img src="{$DIR_IMG}spacer.gif" width="1" height="5"></div>
</td>
</tr>
<!--
<tr>
<td>　</td>
<td>



<div align="left"><font size="2">管理者ID</font>
</div>
</td>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"></font></td>
<td>252411C5253

</td>
</tr>
-->
</table>
<br>
</div>
</td>
</tr>

</table>
</td>
</tr>

</table>
<br>
<table width="430" border="0" cellpadding="0" cellspacing="0">
<form action="{$PHP_SELF}" method="post">
<input type="hidden" name="mrid" value="{$event['mrid']}">
<input type="hidden" name="t" value="{$time}">
<tr>
<td width="13" valign="middle">
<center>
<img src="{$DIR_IMG}icon_inf.gif" width="13" height="13">
</center>
</td>
<td width="107" valign="middle"><font size="2">{$msg1}</font></td>
<td width="287" valign="middle"><font color="#999999" size="2">{$msg2}</font></td>
</tr>
<tr valign="top">
<td height="13" colspan="3">
</td>
</tr>

</table>

<table align="center" width="400" border="0" cellpadding="0" cellspacing="0" bgcolor="#4c4c4c">
<tr>
<td width="270" valign="bottom">

<table width="400" border="0" cellpadding="0" cellspacing="1">

<tr>
<td width="130" bgcolor="ffffff">　
<font size="2">名称</font>
</td>
<td width="270" bgcolor="ffffff"><font size="2">　
<input name="name" type="text" size="40" value="{$event['name']}">
</font></td>
</tr>

<tr>
<td width="130" bgcolor="ffffff">　
<font size="2">メールひな型</font>
</td>
<td width="270" bgcolor="ffffff"><font size="2">　
<select name="mfid">
<option value="">--選択ください--</option>
{$mfidOption}
</select>
</font></td>
</tr>

<!--
<tr>
<td width="130" bgcolor="ffffff">　
<font size="2">配信対象</font>
</td>
<td width="270" bgcolor="ffffff"><font size="2">　
<select name="cnid">
<option value="">全員</option>
</select>
</font></td>
</tr>

<tr>
<td width="130" bgcolor="ffffff">　
<font size="2">ログフラグ</font>
</td>
<td width="270" bgcolor="ffffff"><font size="2">　
{$flglRadio}
</font></td>
</tr>
 -->

<tr>
<td width="130" bgcolor="ffffff">　
<font size="2">配信日</font>
</td>
<td width="270" bgcolor="ffffff"><font size="2">　
<select name="y">{$yOption}</select>年
<select name="m">{$mOption}</select>月
<select name="d">{$dOption}</select>日
</font></td>
</tr>

<tr>
<td width="130" bgcolor="ffffff">　
<font size="2">配信時間</font>
</td>
<td width="270" bgcolor="ffffff"><font size="2">　
<select name="h">{$hOption}</select>時
<select name="i">{$iOption}</select>分
<input type="hidden" name="s" value="0">
</font></td>
</tr>
</table>
</td></tr></table>
<table align="center">
<tr>
<td colspan=2 bgcolor="ffffff">
<img src="{$DIR_IMG}spacer.gif" height="20" width="5">
</td>
</tr>

<tr>
<td colspan=2 align="center" bgcolor="ffffff">
<!--
<input type="image" src="{$DIR_IMG}save.gif" width="100" height="20" name="save">
-->
<input type="image" src="{$DIR_IMG}save.gif" width="100" height="20" name="save">
<input type="hidden" name="flgs" value="2">
<input type="image" src="{$DIR_IMG}cset.gif" width="70" height="21" name="doreserve">
</td>
</tr>
</table>

<!- ->
</td>
<td width="10">　</td>
<td width="169" valign="top"><br>
<table width="150" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
<tr>
<td width="270" valign="bottom">
<table width="150" border="0" cellpadding="0" cellspacing="1">
<tr>
<td width="180" valign="middle" bgcolor="#f6f6f6">
<table width="140" border="0" align="center" cellpadding="0" cellspacing="4">
<tr>
<td><img src="{$DIR_IMG}overview.gif" width="100" height="16"></td>
</tr>
<tr>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"> 配信タイトル等</font></td>
</tr>
<tr>
<td><font size="2">現在保存されている配信内容データです。</font></td>
</tr>
<tr>
<td><img src="{$DIR_IMG}spacer.gif" width="1" height="2"></td>
</tr>
<tr>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"> 配信設定</font></td>
</tr>
<tr>
<td><font size="2">メール定型文・対象・ログ記録・配信日時の設定を行います。</font></td>
</tr>
<tr>
<td><font size="2"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle"> テストメール送信</font></td>
</tr>
<tr>
<td><font size="2">指定のメールアドレスにテストメールを送信チェックが行えます。</font></td>
</tr>
<tr>
<td><img src="{$DIR_IMG}spacer.gif" width="1" height="2"></td>
</tr>




</table>
</td>
</tr>

</table>
</td>
</tr>

</table>
</td>
</tr>
</table>
__HTML__;

$objHtml =& new MreAdminHtml("メール配信予約新規設定", "設定済みのメール配信予約内容を変更します。");
if(is_good($js)) $objHtml->setSrcJs($js);
echo $objHtml->getMainHtml($html);
exit;


/**
 * オプションタグ取得
 */
function getOptionTag2($array, $select_value=null)
{
    $option = '';
    foreach ($array as $value) {
        $selected = ($value==$select_value)? ' selected':'';
        $option .= <<<__HTML__
<option value="{$value}"{$selected}>{$value}</option>\n
__HTML__;
    }

    return $option;
}
