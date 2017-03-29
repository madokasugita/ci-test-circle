<?php

/**
 * PG名称：各種バッチ実行
 * 日  付：
 * 作成者：
 *
 * 更新履歴
 */
/**************************************************************************/

define('DIR_ROOT', '../');
require_once DIR_ROOT . "crm_define.php";
require_once DIR_LIB . "CbaseFunction.php";
require_once DIR_LIB . "360_FHtml.php";
require_once DIR_LIB . "CbaseFErrorMSG.php";
require_once DIR_LIB . "CbaseFForm.php";
require_once DIR_LIB . "CbaseFManage.php";
require_once DIR_LIB . "CbaseEncoding.php";
require_once DIR_LIB . "CbaseFEnquete.php";
require_once DIR_LIB . "AllExport.php";
require_once DIR_LIB . "AllImport.php";
require_once DIR_LIB . "SecularOperate.php";
require_once DIR_LIB . "SecularImport.php";
require_once DIR_LIB . "SecularCreate.php";
encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));
define('PAGE_TITLE', 'Cbase用データ操作');
$backgroundcolor[0] = "#f6f6f6";
$backgroundcolor[1] = "#ffffff";
$datas = array ();

$importResult = '';
if (is_good($_REQUEST['mode'])) {
    $mode = is_array($_REQUEST['mode']) ? array_shift(array_keys($_REQUEST['mode'])) : $_REQUEST['mode'];
    switch ($mode) {
        case 'all_export' :
            $AllExport = new AllExport();
            $AllExport->execute();
            break;
        case 'all_import' :
            $AllImport = new AllImport();
            $importResult = $AllImport->execute();
            break;
    }
}

// $secularImport = new SecularImport(); $secularImport->execute(); exit;
// $secularCreate = new SecularCreate(); $secularCreate->execute(); exit;
$secularOperate = new SecularOperate();
$secularOperate->connectSecularDatabase();
$secularOperate->setSecularData();
$secularOperate->connectDefaultDatabase();

$datas[] = array (
    '全ユーザのパスワードを1111に',
    'pass_set'
);
$datas[] = array('-');//仕切り
$datas[] = array (
    '組織マスタ削除',
    'clear_div'
);
$datas[] = array (
    '全ユーザを削除(回答者関連付けと回答データも削除',
    'clear_user'
);

$datas[] = array (
    '回答者関連付け削除',
    'clear_user_relation'
);

$datas[] = array (
    '全評価データ削除(途中保存も含む)',
    'clear_enq_data'
);
$datas[] = array (
    'アンケートキャッシュ削除',
    'clear_enq_cache'
);
$datas[] = array('-');//仕切り
$datas[] = array (
    'メール配信設定削除',
    'clear_mail_rsv'
);
$datas[] = array('-');//仕切り

$datas[] = array (
    '本番開始用データ初期化<br>(アンケートキャッシュ,組織マスタ,ユーザマスタ,回答者,回答データ,メール予約)削除',
    'clear_all'
);

$datas[] = array (
    'ログレコード削除',
    'clear_access_log'
);

$datas[] = array (
    'ログファイル削除',
    'clear_log'
);

$datas[] = array (
    'FBディレクトリ内PDF全削除',
    'clear_pdf'
);

//201012 No95 <--
$datas[] = array('-');//仕切り

$datas[] = array (
    '本画面へのアクセス権限削除',
    'clear_admin_operate_access'
);
//201012 No95  -->

//201401 <--
$datas[] = array('-');//仕切り

$datas[] = array (
    '一括インポート<br>(評価シート管理[import/export], 評価シート管理[一括更新(詳細)])',
    'all_import'
);
$datas[] = array (
    '一括エクスポート<br>(評価シート管理[import/export], 評価シート管理[一括更新(詳細)])',
    'all_export'

);

//201401 -->

/***********************************************************************************************/
$i = 0;
$page_name = PAGE_TITLE;
$SID = getSID();
$DIR_IMG = DIR_IMG;
$HTML =<<<HTML
<h1>$page_name</h1>

<div style="margin:20px auto;width:380px;padding:20px;border:dotted 2px black;background-color:#ffff66">
<img src="{$DIR_IMG}caution.gif" alt="caution"> <span style="color:red;font-size:20px;font-weight:bold">[注意]</span><br><br>
<font size="2">この画面で行なった操作は取り消すことができません。<br>慎重に作業するようにお願い致します</font>

</div>
<br><br><br>
<script>
function exec(obj)
{
    if (!confirm('実行しますか?')) {
        return;
    }
    document.getElementById(obj.id+'_result').innerHTML = '実行中';
    buttonDisable();
    ajax('360_admin_operate_ajax.php','{$SID}&mode='+obj.id+'&'+obj.name,'POST');

}
function buttonDisable()
{
    var objs = document.getElementsByTagName('button');
    for (var i=0;i<objs.length;i++) {
        objs[i].disabled = true;
    }
}
function buttonAble()
{
    var objs = document.getElementsByTagName('button');
    for (var i=0;i<objs.length;i++) {
        objs[i].disabled = false;
    }
}
function submitExec(obj)
{
    if (!checkYmd(obj)) {
        alert('有効な日付をご入力ください。');
        return false;
    }
    if (!checkFile(obj)) {
        alert('ファイルを選択してください。');
        return false;
    }
    if (!confirm('実行しますか?')) {
        return false;
    }
    var mode = $(obj).closest('form').find(':input[name="mode"]').val();
    document.getElementById(mode+'_result').innerHTML = '実行中';
    buttonDisable();
    var formData = new FormData($(obj).closest('form').get(0));
    $.ajax({
        url: '360_admin_operate_ajax.php?{$SID}',
        type: 'POST',
        dataType: 'html',
        data: formData,
        processData: false,
        contentType: false
    }).done(function( res ) {
        eval(res);
    }).fail(function( jqXHR, textStatus, errorThrown ) {
        eval(jqXHR);
    });
}
function checkFile(obj)
{
    if ($(obj).hasClass('check-file')) {
        hoge = $(obj).closest('form').find(':input[name="file"]');
        if ($(obj).closest('form').find(':input[name="file"]')[0].files.length == 0) {
            return false;
        }
    }
    return true;

}
function checkYmd(obj)
{
    if ($(obj).hasClass('check-ymd')) {
        var ymd = $(obj).closest('form').find(':input[name="ymd"]').val();
        var y = ymd.substr(0, 4),
            m = ymd.substr(4, 2),
            d = ymd.substr(6, 2);
        var dt = new Date(y, m-1, d);
        if (!(dt.getFullYear()==y && dt.getMonth()==m-1 && dt.getDate()==d)) {
            return false;
        }
    }
    return true;
}
</script>
<table align="center" width="560" border="1" bordercolor="#333333" class="table1" cellpadding="5" cellspasing="5">
<colgroup class="td2" width="*">
<colgroup class="td2" width="90">
<colgroup class="td2" width="100">
<tr align="center" class="td1">
<td>
処理
</td>
<td>
実行
</td>
<td>
結果
</td>
</tr>
HTML;

foreach ($datas as $data) {
    if ($data[0] == '-') {
        $HTML .=<<<HTML
<tr align="center" class="td1">
<td colspan="3" height="10">

</td>
</tr>
HTML;
        continue;
    }
    if ($data[1] == 'all_export' | $data[1] == 'all_import') {
        if ($data[1] == 'all_export') {
            $HTML .=<<<HTML
<tr align="left" style="background-color:{$backgroundcolor[++$i%2]}">
<td>
{$data[0]}
</td>
<td>
<form action="360_admin_operate.php?{$data[1]}=1&amp;{$SID}" method="post" enctype="multipart/form-data">
<div>
    <input type="submit" name="mode[all_export]" value="実行"class="imgbutton90">
</div>
</form>
</td>
<td id="{$data[1]}_result" style="text-align:center;font-weight:bold">
&nbsp;
</td>
</tr>
HTML;
        } else {
        $HTML .=<<<HTML
<tr align="left" style="background-color:{$backgroundcolor[++$i%2]}">
<td>
{$data[0]}
</td>
<td>
<form action="360_admin_operate.php?{$data[1]}=1&amp;{$SID}" method="post" enctype="multipart/form-data">
<div>
    <input type="file" name="file">
    <input type="submit" name="mode[all_import]" value="実行"class="imgbutton90">
</div>
</form>

</td>
<td id="{$data[1]}_result" style="text-align:center;font-weight:bold">
{$importResult}&nbsp;
</td>
</tr>
HTML;
        }
    } else {
        $HTML .=<<<HTML
<tr align="left" style="background-color:{$backgroundcolor[++$i%2]}">
<td>
{$data[0]}
</td>
<td>
<button id="{$data[1]}" onclick="exec(this)" name="{$data[2]}"class="imgbutton90">実行</button>
</td>
<td id="{$data[1]}_result" style="text-align:center;font-weight:bold">
&nbsp;
</td>
</tr>
HTML;
    }
}
$objHtml = new ResearchAdminHtml(PAGE_TITLE);
//201012 No86 akama
$objHtml->addFileJs(DIR_JS.'360_userpage.js');
$objHtml->setSrcCss(<<<__HTML__
#main-iframe{
    text-align:center;
}
__HTML__
);

$statusString = array($secularOperate->getUsesStatusString());
if (!is_void($secularOperate->secular)) {
    $statusString[] = '現在値：' . $secularOperate->secular['hash'];
    $statusString[] = '最終更新者：' . html_escape($secularOperate->getLastModifiedUserName());
    $statusString[] = '最終更新日：' . date('Y年m月d日', strtotime($secularOperate->secular['modified_at']));
}
$statusString = implode('<br>', $statusString);

$HTML .= <<<HTML
    <tr align="center" class="secular-accordion-parent td1" style="cursor: pointer;"><td colspan="3" height="10">経年比較<span id="secular-accordion-mark">▼</span></td></tr>
    <tr class="secular-accordion-child" align="left" style="background-color:#f6f6f6">
        <td colspan="3">
            <div style="padding: 10px; margin: 10px 0; border-left: 1px solid #eee; border-left-width: 5px; border-radius: 3px; border-left-color: #ce4844;">
                <p style="color: #ce4844; margin-top: 0; margin-bottom: 5px;">
                    経年比較の作業は元に戻すことが出来ないため、注意してご利用ください。
                </p>
                <p style="margin-top: 0; margin-bottom: 5px;">
                    <input type="checkbox" name="secular-checkbox" id="secular-checkbox">
                    <label for="secular-checkbox">経年比較を利用する</label>
                </p>
            </div>
        </td>
    </tr>
    <tr align="left" class="secular-accordion-child" style="background-color:#f6f6f6">
        <td>経年比較ステータス</td>
        <td>
            {$statusString}<br>
        </td>
        <td style="text-align:center;font-weight:bold">&nbsp;</td>
    </tr>
HTML;
//201604 <--
if (is_good($secularOperate->secular)) {
    if ($secularOperate->secular['uses_status'] == SECULAR_USES_STATUS_UNUSED || $secularOperate->secular['uses_status'] == SECULAR_USES_STATUS_CREATED) {
        $HTML .= <<<HTML
<tr class="secular-accordion-child" align="left" style="background-color:#f6f6f6">
    <td>経年比較用データ作成</td>
    <td><button id="create_secular_dump" name="" class="imgbutton90">実行</button></td>
    <td id="create_secular_dump_result" style="text-align:center;font-weight:bold">&nbsp;</td>
</tr>
HTML;

        if ($secularOperate->secular['uses_status'] == SECULAR_USES_STATUS_UNUSED) {
            $HTML .= <<<HTML
<tr class="secular-accordion-child" align="left" style="background-color:#f6f6f6">
<td>Rawデータからインポート</td>
<td>
    <form action="360_admin_operate_ajax.php?{$SID}" method="post" enctype="multipart/form-data">
        <div>
            <input type="hidden" name="mode" value="raw_secular_import">
            <input type="file" name="file">
            <input type="text" name="ymd" placeholder="年月日（例：20160718）">
            <input type="submit" value="実行" class="imgbutton90 check-ymd check-file">
        </div>
    </form>
</td>
<td id="raw_secular_import_result" style="text-align:center;font-weight:bold">&nbsp;</td>
</tr>
HTML;
        }
    }

    if ($secularOperate->secular['uses_status'] == SECULAR_USES_STATUS_CREATED) {
        $HTML .= <<<HTML
<tr class="secular-accordion-child" align="left" style="background-color:#f6f6f6">
    <td>経年比較用データインポート</td>
    <td><button id="import_secular_dump" name="" class="imgbutton90">実行</button></td>
    <td id="import_secular_dump_result" style="text-align:center;font-weight:bold">&nbsp;</td>
</tr>
HTML;
    }
}

if (is_void($secularOperate->secular) || $secularOperate->secular['uses_status'] == SECULAR_USES_STATUS_IMPORTED) {
    $HTML .= <<<HTML
<tr class="secular-accordion-child" align="left" style="background-color:#f6f6f6">
    <td>経年比較用ハッシュ発番</td>
    <td><button id="rehash_secular" name="" class="imgbutton90">実行</button></td>
    <td id="rehash_secular_result" style="text-align:center;font-weight:bold">&nbsp;</td>
</tr>
HTML;
}
//201604 -->

$HTML .= <<<HTML
</table>
<script>
<!--
//this is fast
$(function() {
    // アコーディオン
    $(".table1 tr.secular-accordion-child").hide();
    $(".table1 tr.secular-accordion-parent").toggle(
        function() { $('#secular-accordion-mark').text('▲'); },
        function() { $('#secular-accordion-mark').text('▼'); }
    );
    $(".table1 tr.secular-accordion-parent").click(function(){
        $(this).nextAll("tr").fadeToggle("fast");
    });

    // 同意チェック
    $('.secular-accordion-child :submit').click(function(ev) {
        if (!$('#secular-checkbox').prop('checked')) {
            ev.preventDefault();
            alert('『経年比較情報を利用する』チェックボックスにチェックを入れてご利用ください。');
            return false;
        } else {
            if (this.tagName.toLowerCase() == 'button') {
                exec(this);
            } else if(this.tagName.toLowerCase() == 'input') {
                ev.preventDefault();
                submitExec(this);
            }
        }
    });
});
-->
</script>
HTML;
echo $objHtml->getMainHtml($HTML);
exit;
