<?php
//変数セット
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . '360_Function.php');

encodeWebAll();
session_start();

Check_AuthMng(basename(__FILE__));
/************************************************************************************************************/
$colmuns = array(
    'uid'=>'ユーザＩＤ',
    'name'=>'名称',
    'name_'=>'ローマ字',
    'div1'=>'所属（大）',
    'div2'=>'所属（中）',
    'div3'=>'所属（小）',
    'mflag'=>'対象者フラグ',
    'sheet_type'=>'シートタイプ',
    'email'=>'メールアドレス',
    'class'=>'役職',
    'lang_flag'=>'多言語対応',
    'lang_type'=>'言語タイプ',
    'test_flag'=>'テストフラグ',
    'memo'=>'メモ',
    'ext1'=>'予備1',
    'ext2'=>'予備2',
    'ext3'=>'予備3',
    'ext4'=>'予備4',
    'ext5'=>'予備5',
    'ext6'=>'予備6',
    'ext7'=>'予備7',
    'ext8'=>'予備8',
    'ext9'=>'予備9',
    'ext10'=>'予備10',
    'send_mail_flag'=>'メール送信停止フラグ'
);
$pages = array(
    'user_search'=>'ユーザ<br>検索',
    'user_edit'=>'ユーザ<br>編集',
    'user_relation_search'=>'回答者<br>選定',
    'target_relation_search'=>'対象者<br>選定',
    'admit_relation_search'=>'承認者<br>選定',
    'viewer_relation_search'=>'参照者<br>選定',
    'user_evaluator_search'=>'代理<br>ログイン',
    'enq_search_all'=>'回答<br>状況',
    'user_pw_search'=>'ログイン<br>URL／PW管理',
    'fb_search'=>'対象者<br>FB'
);
/************************************************************************************************************/
if ($_POST['save']) {
    $data = saveColmunSetting($_POST['data']);
} else {
    $data = getColmunSetting();
}

$pages_count = count($pages);
$PHP_SELF = getPHP_SELF().'?'.getSID();
$html .=<<<HTML
<style type="text/css">
<!--
.tr1{
    background-color:#666666;
    color:white;
}
.tr2{
    background-color:#f2f2f2;
}
//-->
</style>
<form action="{$PHP_SELF}" method="post">
<table width="1050" cellpadding="0" cellspacing="0">
<tbody>
<tr>
<td><input name="save" value="　設定を保存する　" type="submit"></td>
<td align="right">
<input name="default" value="　デフォルト設定に戻す　" type="submit">
</td>
</tr>
</tbody>
</table>
<table width="1050" cellpadding="5" cellspacing="0" class="cont">
<tbody>
<tr>
<td rowspan="2" class="tr2">マスタ項目</td>
<td style="width:150px" rowspan="2"  class="tr2">ラベル名称</td>
<td style="width:120px" rowspan="2"  class="tr2">表示幅(width)</td>
<td colspan="{$pages_count}" class="tr2"><div align="center">マスタ／検索　表示制御</div></td>
</tr>
<tr>
HTML;
foreach ($pages as $k => $v) {
    $html .=<<<HTML
<td width="60" class="tr2">{$v}
HTML;
}
$html .=<<<HTML
</tr>
HTML;
    foreach ($colmuns as $k => $v) {
        $html .=<<<HTML
<tr>
<td class="tr2">{$v}</td>
<td class="tr2" style="width:150px" ><input name="data[label][$k]" type="text" value="{$data['label'][$k]}" size="20"></td>
<td class="tr2" align="center"><input name="data[width][$k]" type="text" value="{$data['width'][$k]}" size="4">px</td>
HTML;
        foreach ($pages as $k1 => $v1) {
            $checked = ($data['colmun'][$k1][$k]) ? ' checked':'';
            $html .=<<<HTML
<td class="tr2" align="center"><input type="checkbox" name="data[colmun][$k1][$k]" value="1"{$checked}></td>
HTML;
        }
        $html .=<<<HTML
</tr>
HTML;
}
$html .=<<<HTML
</table>
<table width="1050" cellpadding="0" cellspacing="0">
<tbody>
<tr>
<td><input name="save" value="　設定を保存する　" type="submit"></td>
<td align="right">
<input name="default" value="　デフォルト設定に戻す　" type="submit">
</td>
</tr>
</tbody>
</table>
</form>
HTML;
$objHtml = & new MreAdminHtml('ユーザマスタ項目設定');
echo $objHtml->getMainHtml($html);
exit;
