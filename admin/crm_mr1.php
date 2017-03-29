<?php

/**
 * ---------------------------------------------------------
 * crm_mr1.php	by ipsystem@cbase.co.jp
 * ---------------------------------------------------------
 */
define('PAGE_TITLE',"予約一覧");
//define('DEBUG'		, 0);
define('DIR_ROOT', "../");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFEventMail.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'CbaseFunction.php');
//require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFCondition.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
encodeWebAll();

session_start();
Check_AuthMng(basename(__FILE__));
$_SESSION['enq_target_list']['recent_page'] = basename(__FILE__);
/************************************************************************************************************/
define('PHP_SELF', getPHP_SELF() . "?" . getSID());
define('URL_1', "crm_mr2.php" . "?" . getSID());
define('URL_2', "crm_mf2.php" . "?" . getSID());
define('LENGTH_2', 20); //リストの名称のところ
define('LENGTH_3', 30); //リストの配信条件のところ
/************************************************************************************************************/
if ($_POST['mode'] == 'delete' && $_POST['mrid']) { //削除処理
    $tmpMail = Get_MailEvent('id', $_POST['mrid'], 'mrid', 'desc', $_SESSION['muid']); //権限チェック
    Delete_MailEvent($tmpMail[0]['mrid']);
} elseif ($_POST['mode'] == 'stop' && $_POST['mrid']) { //中止処理
    $tmpMail = Get_MailEvent('id', $_POST['mrid'], 'mrid', 'desc', $_SESSION['muid']); //権限チェック
    $newMail = $tmpMail[0];
    $tmp = explode(' ', $newMail['mdate']);
    $mdate = mktime(substr($tmp[1], 0, 2), substr($tmp[1], 3, 2) - 5, 0, substr($tmp[0], 5, 2), substr($tmp[0], 8, 2), substr($tmp[0], 0, 4));
    if ($mdate > mktime()) {
        $newMail['flgs'] = 2;
        $result = Save_MailEvent('update', $newMail);
    } else {
        $tmpMail = Get_MailEvent('id', $_POST['mrid'], 'mrid', 'desc', $_SESSION['muid']); //権限チェック
        $newMail = $tmpMail[0];
        $newMail['flgs'] = 12;
        $result = Save_MailEvent('update', $newMail);
        $stopFile = getMailForcedStopFile($newMail['mrid']);
        if(touch($stopFile)) syncCopy($stopFile);
    }
    if ($result==0) {
        $error = "配信は既に完了しています。";
    }
}
/*********************************************************************************************************************/
$mail_events = Get_MailEvent(-1, '', 'mdate', '', $_SESSION['muid']);
//$tophtml = ResearchAdminHtml :: getTopHtml('maillistbanner.gif', '配信予約内容を確認・変更できます。', '100%');
$html1 = getHtml1($mail_events, $error); //予約中リスト
$html2 = getHtml2($mail_events); //実行完了リスト
$date = date('Y/m/d H:i:s');
$body =<<<__HTML__
<div style="width:600px;text-align:right">{$date}</div>
{$html1}
{$html2}
__HTML__;
$objHtml = & new MreAdminHtml(PAGE_TITLE);
$objHtml->setSideHtml(D360::getAdvice('配信予約リスト', array(
    '[済]ステータス' => 'チェックが付いているのは配信予約が完了している予約データです。',
    '[条件名]ステータス' => 'ここに表示されている絞込み条件を使用し予約配信します。',
    '[編集・削除]ステータス' => '内容を編集する場合には「編集」を、削除する場合には「削除」を選択することでそれぞれ内容を変更します。',
)));
print $objHtml->getMainHtml($body);
exit;
/*********************************************************************************************************************/
/**
 * 予約中リスト
 */
function getHtml1($mail_events, $error="")
{
    $caption = MreAdminHtml :: getCaption('配信予約リスト', '現在メール配信予約済/予約が可能なリストです。');
    $URL2 = URL_2;
    $DIR_IMG = DIR_IMG;
    $PHP_SELF = PHP_SELF;
    $table_body = '';
    foreach ($mail_events as $mail_event) {
        if (isMailStatusFinish($mail_event['flgs'])) //配信済みのデータは飛ばす
            continue;
        $mail_flgs = getMailStatus($mail_event['flgs'], $mail_event['mrid']);
        $id = html_escape($mail_event['mrid']);
        //$name = mb_strimwidth(html_escape($mail_event['name']), 0, LENGTH_2, '...');
        $name = html_escape($mail_event['name']);
        $cond_name = mb_strimwidth(getConditionName($mail_event['cnid']), 0, LENGTH_3, '...');
        $targetlistPage = 'mail_target_list.php?' . getSID() . '&id=' . $mail_event['cnid'];
        $format_name = $mail_event['mfid'];
        $mdate_disp = date('Y/m/d H:i', strtotime($mail_event['mdate']));

        $edit_button = getEditButton($mail_event);
        $delete_button = getDeleteButton($mail_event);
        $table_body .=<<<__HTML__
<tr>
<td style="width:40px;text-align:center;">{$mail_flgs}</td>
<td style="width:30px;text-align:center;">{$id}</td>
<td>{$name}</td>
<td><a href="{$targetlistPage}">{$cond_name}</a></td>
<td style="width:100px;text-align:center;">{$mdate_disp}</td>
<td style="width:30px;text-align:center;"><a href="{$URL2}&mfid={$mail_event['mfid']}">{$format_name}</a></td>
<td style="width:85px;text-align:center;">{$edit_button} {$delete_button}</td>
</tr>
__HTML__;
    }
    if (!$table_body) {
$table_body=<<<__HTML__
<tr><td style="text-align:center;padding:10px;" colspan="7">配信予約なし</td></tr>
__HTML__;
    }
    $error = html_escape($error);

    return<<<__HTML__
{$caption}
<br>
<div style="color:#ff0000;">{$error}</div>
<table class="cont"style="width:auto;width:600px;">
<tr><th></th><th>ID</th><th>名称</th><th>条件名</th><th>配信日時</th><th>雛形</th><th>編集・削除</th></tr>
{$table_body}
</table>
__HTML__;
}

/**
 * 実行完了リスト
 */
function getHtml2($mail_events)
{
    $caption = MreAdminHtml :: getCaption('配信完了リスト', '既に配信が完了したリストです。 (全件表示)');
    $URL2 = URL_2;
    $DIR_IMG = DIR_IMG;
    $table_body = '';
    $i = 0;
    $mail_events = array_reverse($mail_events);//最新のデータから表示したいのでひっくり返す
    $failureIds = array();
    foreach ($mail_events as $mail_event) {
        if (isMailStatusFinish($mail_event['flgs'])) { //未配信のデータは飛ばす
            $failureIds[] = FDB::escape($mail_event['mrid']);
        }
    }
    if (is_good($failureIds))
    {
        $sql = "SELECT mrid, count(*) as cnt FROM ".T_MAIL_LOG." WHERE mrid IN (".implode(',', $failureIds).") AND result != 1 GROUP BY mrid";
        $failureIds = array();
        foreach (FDB::getAssoc($sql) as $v) {
            $failureIds[$v['mrid']] = $v['cnt'];
        }
    }
    foreach ($mail_events as $mail_event) {
        if (!isMailStatusFinish($mail_event['flgs'])) //未配信のデータは飛ばす
            continue;

        $mail_flgs = getMailStatus($mail_event['flgs'], $mail_event['mrid']);
        $id = html_escape($mail_event['mrid']);
        //$name = mb_strimwidth(html_escape($mail_event['name']), 0, LENGTH_2 + 40, '...');
        $name = html_escape($mail_event['name']);

        $failure_count = (isset($failureIds[$mail_event['mrid']])) ? $failureIds[$mail_event['mrid']] : 0;
        $failure_style = ($failure_count > 0) ? 'style="background-color:orange;"' : '';

        $count = html_escape($mail_event['count']);
        $count_str = (String)($count - $failure_count) . '/' . $count;
        //NO123 メール配信履歴追加 *1<--
        $loglistPage = 'mail_log_list.php?' . getSID() . '&id=' . $mail_event['mrid'];
        $loglistPage = $count>0 ? <<<__HTML__
<a href="{$loglistPage}">{$count_str}</a>
__HTML__
: $count;

        $format_name = $mail_event['mfid'];
        $mdate_disp = date('Y/m/d H:i', strtotime($mail_event['mdate']));
        $table_body .=<<<__HTML__
<tr {$failure_style}>
<td style="width:40px;text-align:center;">{$mail_flgs}</td>
<td style="width:30px;text-align:center;">{$id}</td>
<td>{$name}</td>
<td style="width:40px;text-align:center;">{$loglistPage}</td>
<td style="width:100px;text-align:center;">{$mdate_disp}</td>
<td style="width:30px;text-align:center;"><a href="{$URL2}&mfid={$mail_event['mfid']}">{$format_name}</a></td>
</tr>
__HTML__;
    }
        //NO123 メール配信履歴追加 *1-->

    if (!$table_body) {
$table_body=<<<__HTML__

<tr><td style="text-align:center;padding:10px;" colspan="6">配信完了なし</td></tr>
__HTML__;
    }

    return<<<__HTML__
{$caption}
<br>
<table class="cont"style="width:auto;width:600px;">
<tr><th></th><th>ID</th><th>名称</th><th>配信数</th><th>配信日時</th><th>雛形</th></tr>
{$table_body}
</table>

__HTML__;
}

function getEditButton($mail_event)
{
    if (!isMailStatusWait($mail_event['flgs']))
        return '';

    $DIR_IMG = DIR_IMG;
    $URL_1 = URL_1;

    return<<<HTML
<form action="{$URL_1}" method="post" style="display:inline;">
<input type="hidden" name="mrid" value="{$mail_event['mrid']}">
<input type="image" src="{$DIR_IMG}edit.gif" width="35" height="17" align="middle" name="edit">
</form>
HTML;
}

function getDeleteButton($mail_event)
{
    $DIR_IMG = DIR_IMG;
    $PHP_SELF = PHP_SELF;
    $delete_button = '';
    if (isMailStatusFinish($mail_event['flgs']) || isMailStatusWait($mail_event['flgs']) || isMailStatusError($mail_event['flgs'])) {
        //削除ボタン：flgsが済,wait,エラー
        $delete_button =<<<__HTML__
<form action="{$PHP_SELF}" method="post" style="display:inline;">
<input type="hidden" name="mrid" value="{$mail_event['mrid']}">
<input type="hidden" name="mode" value="delete">
<input type="image" src="{$DIR_IMG}del.gif" width="35" height="17" align="middle" name="delete" onClick="return myconfirm('削除しますか？');">
</form>
__HTML__;
    } elseif (isMailStatusRsv($mail_event['flgs']) || isMailStatusExe($mail_event['flgs'], $mail_event['mrid'])) {
        //中止ボタン：flgsが予約中,配信中
        $delete_button =<<<__HTML__
<form action="{$PHP_SELF}" method="post" style="display:inline;">
<input type="hidden" name="mrid" value="{$mail_event['mrid']}">
<input type="hidden" name="mode" value="stop">
<input type="image" src="{$DIR_IMG}stop.gif" align="middle" name="stop" onclick="return myconfirm('中止しますか？');">
</form>
__HTML__;
    }

    return $delete_button;
}
