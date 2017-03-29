<?php

/* ---------------------------------------------------------
   ;;;;;;;;;;;;
   ;;;.php;;  by  ipsyste@cbase.co.jp
   ;;;;;;;;;;;;
--------------------------------------------------------- */
//変数セット

define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

$url1 = "enq_ttl.php";
$len1 = 1118; //複製新規のところの文字数制限
$len2 = 1120; //リストの名称のところ

$PHP_SELF = getPHP_SELF()."?".getSID();

if ($_POST['mode'] == 'delete') {
    $evid = Check_AuthMngEvid($_POST['evid']);
    $result = delete_enq_data();

}

//Enqリスト出力
$array = Get_Enquete(-1, "", "evid", "desc", $_SESSION["muid"]);

/* ----Start HTML----- */
$refreshBar = D360::getRefreshBar();
$sub_title=D360::getSubject("アンケート一覧","","","","margin-left:20px");
$DIR_IMG = DIR_IMG;
$html .=<<<HTML
<table width="610" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="431" align="left">
<div style="width:250px;padding:20px;border:dotted 2px black;background-color:#ffff66;margin:20px">
<img src="{$DIR_IMG}caution.gif"> <span style="color:red;font-size:20px;font-weight:bold">[注意]</span><br><br>
<font size="2">回答データを削除します。<br> 削除したデータは復元できません。</font>
</div>
{$sub_title}
</td>
</tr>
</table>
<br>
{$result}
<table style="margin-left:30px;"width="450" border="0" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
<tr>
<td valign="top">
<table class="cont" style="margin:0;width:auto"width="450" border="0" cellpadding="1" cellspacing="1">
<tr>
<th width="50">ID</th>
<th width="320">名称</th>
<th width="100">回答データ削除</th>
</tr>
HTML;

foreach ($array as $ar) {
    $enq_href = DOMAIN . DIR_MAIN . '?' . Create_QueryString(Get_RandID(8), $ar["rid"], 1, "A");
    $name = mb_strimwidth($ar["name"], 0, $len2, '...');

    $html .=<<<HTML
<form action="{$PHP_SELF}" method="POST" onsubmit="return myconfirm('ID:{$ar['evid']}の回答データを削除しますか？')">
<input type="hidden" name="mode" value="delete">
<input type="hidden" name="evid" value="{$ar["evid"]}">
<tr>
<td>{$ar["evid"]}</td>
<td>{$name}</td>
<td align=center><input type="submit" value="削除"class="imgbutton35"></td>
</tr>
</form>
HTML;
    //	++$i;//行のカラーわけの為の変数
} //end for

$html .=<<<HTML
</table>
</td></tr></table>
<br>
<table width="430" border="0" cellpadding="0" cellspacing="0">
<tr>
<td valign="middle">
<center>
<font color="#999999" size="2">※最新情報に更新されない時は、「更新」を行ってください。</font>
</center>
</td>
</tr>
</table>
</td>
<td width="10">　</td>
</tr>
</table>
HTML;

//HTML排出
$objHtml =& new MreAdminHtml("回答データ削除");
echo $objHtml->getMainHtml($html);
exit;

function delete_enq_data()
{
    if (SHOW_ABOLITION) {
        echo '類似の機能がEnqueteAnswer::deleteInfoで用意されています。<hr>';
    }
    global $evid;
    $count = FDB :: select(T_EVENT_DATA, 'count(*) as count','where answer_state <> -10 and evid = ' . FDB :: escape($evid));
    $count = $count[0]['count'];

    if (ENQ_DATA_DELETE_MODE == 0) {
        FDB :: delete(T_EVENT_DATA, 'where evid = ' . FDB :: escape($evid));
        FDB :: delete(T_EVENT_SUB_DATA, 'where evid = ' . FDB :: escape($evid));
        $method="delete";
    } elseif (ENQ_DATA_DELETE_MODE == 1) {
        FDB :: update(T_EVENT_DATA, array (
            'answer_state' => '-10'
        ), 'where evid = ' . FDB :: escape($evid));
        $method="update";
    }
    operationLog(LOG_DELETE_ANSWER,"evid={$evid},count={$count},method={$method}");

    return "<font color=red><b>ID:{$evid}のアンケート回答データを削除しました</b></font>";
}
