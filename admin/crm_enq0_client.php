<?php
define('DIR_ROOT', "../");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseEnquete.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseSortList.php');
require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'ResearchSortListView.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
encodeWebAll();
session_start();
Check_AuthMng(basename(__FILE__));

$body .= '<h1>評価シート一覧</h1>';
//$body .= getClearCacheButton($MESSAGE); //クライアントバージョンはボタンなし
$body .= getSheetTable();

$objHtml = & new ResearchAdminHtml("評価シート一覧");
echo $objHtml->getMainHtml($body);
exit;

function getClearCacheButton($MESSAGE='')
{
    return "";
    $PHP_SELF = getPHP_SELFwithSID();

    return<<<HTML
<div style="margin-top:20px;margin-bottom:20px;width:600px;text-align:right">
<form action="{$PHP_SELF}" method="post">
<input type="hidden" name="clear_all" value="1">
{$MESSAGE}<input type="submit" value="全評価シートのキャッシュをクリア">
</form>
</div>
HTML;
}

function getSheetTable()
{
    global $Setting;
    $html =<<<HTML
<table class="cont" style="width:680px">
<tr>
    <th style="width:30px">ID</th><th>シート名</th><th style="width:100px">動作確認</th><th style="width:100px">画面確認</th>
</tr>
HTML;
    foreach ($GLOBALS['_360_sheet_type'] as $sheet_type => $sheet_name) {
        foreach ($GLOBALS['_360_user_type'] as $user_type => $user_name) {
            if ($user_type > INPUTER_COUNT || $Setting->sheetModeCollect() && $user_type == 2)
                break;
            $evid = $sheet_type*100+$user_type;
            if ($Setting->sheetModeCollect() && $user_type == 1) {
                $user_names = array();
                $evids = array();
                foreach ($GLOBALS['_360_user_type'] as $user_type_ => $user_name_) {
                    if(!$user_type_)
                        continue;
                    if ($user_type_ > INPUTER_COUNT)
                        break;
                    $evids[] = $sheet_type*100+$user_type_;
                    $user_names[] = $user_name_;
                }
                $evid_disp = implode(',',$evids);
                $user_name = implode(' , ',$user_names);
            } else {
                $evid_disp = $evid;
            }


            $links = array();
            $links2 = array();
            foreach ($GLOBALS['_360_language'] as $k => $l) {
                if($k==-1)
                    continue;
                $k = (int) $k;
                $prev   = DOMAIN.DIR_MAIN.PG_PREVIEW.'?lang360='.$k.'&rid='.Create_QueryString(Get_RandID(8),getRidByEvid($evid), 1, 'A');
                $links[] = "<a href=\"{$prev}\" target=\"_blank\">{$l}</a>";

                $prev   = DOMAIN.DIR_MAIN.'preview.php'.'?lang360='.$k.'&rid='.Create_QueryString(Get_RandID(8),getRidByEvid($evid), 1, 'A');
                $links2[] = "<a href=\"{$prev}\" target=\"_blank\">{$l}</a>";

            }
            $link = implode(' / ',$links);
            $link2 = implode(' / ',$links2);



            $edit = '<a href="enq_event.php?evid='.$evid.'&'.getSID().'"><img src="'.DIR_IMG.'edit.gif" width="35" height="17" align="middle" border=0></a>';
            $html .=<<<HTML
<tr>
    <td>{$evid_disp}</td>
    <td>{$sheet_name} {$user_name}</td>
    <td>{$link}</td>
    <td>{$link2}</td>
</tr>
HTML;
        }
    }
    $html .= <<<__HTML__
</table>
__HTML__;

    return $html;
}
