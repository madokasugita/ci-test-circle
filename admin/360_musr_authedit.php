<?php

/*
 * こんながめんにしたい
 *
 * [アンケート権限設定]
 *
 * [アンケート名]     　権限　権限　[編集]
 * [アンケート選択|▼]　権限　権限  [登録]　
 * [新規追加]
 *
 * [その他権限設定]（カテゴリ別など）
 * 権限　権限　権限
 *
 */

//define('DEBUG', 1);
define('DIR_ROOT', "../");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . 'CbaseAuthSet.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();
session_start();
require_once (DIR_LIB . 'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

define('MUID', (int) $_GET['id']);
define('PHP_SELF', getPHP_SELF() . '?' . getSID() . '&id=' . MUID);

if ($_POST['mode']['insert']) {
    $data = array ();
    $data['muid'] = MUID;
    FDB :: insert(T_AUTH_SET_DIV, $data);
}
if ($_POST['mode']['delete']) {
    FDB :: delete(T_AUTH_SET_DIV, 'where asd_id = ' . FDB :: escape($_POST['asd_id']));
}

if ($_POST['mode']['update']) {
    $data = array ();
    if($_POST['div1']=='default')
        $_POST['div1'] = '*';
    if($_POST['div2']=='default')
        $_POST['div2'] = '*';
    if($_POST['div3']=='default')
        $_POST['div3'] = '*';

    $data['div1'] = FDB::escape($_POST['div1']);
    $data['div2'] = FDB::escape($_POST['div2']);
    $data['div3'] = FDB::escape($_POST['div3']);
    FDB :: update(T_AUTH_SET_DIV, $data,'where asd_id = ' . FDB :: escape($_POST['asd_id']));
}

function getHtmlTable()
{
    $PHP_SELF = PHP_SELF;
    $getHtmlReduceSelect = getHtmlReduceSelect(); //select option 絞込み機能一式

    $getHtmlReduceSelect = str_replace('指定しない', '全ての組織', $getHtmlReduceSelect);

    $html =<<<HTML
<table class="admintable3">
<tr class="admintable_header">
<td style="width:205px">####div_name_1####</td>
<td style="width:205px">####div_name_2####</td>
<td style="width:205px">####div_name_3####</td>
<td></td>
</tr>
HTML;
    $array_div_auth = FDB :: select(T_AUTH_SET_DIV, '*', 'where muid = ' . FDB :: escape(MUID).' order by div1,div2,div3');
    if ($_POST['mode']['edit'])
        $disabled = ' disabled';
    foreach ($array_div_auth as $div_auth) {

        if ($_POST['mode']['edit'] && $_POST['asd_id'] == $div_auth['asd_id']) {
            $disabled = ' disabled';
            $html .= getHtmlEditLine($div_auth);
            continue;
        }
        $div1_name = getDiv1NameById($div_auth['div1']);
        $div2_name = getDiv2NameById($div_auth['div2']);
        $div3_name = getDiv3NameById($div_auth['div3']);

        $div1_bgcolor="#ffffff";
        $div2_bgcolor="#ffffff";
        $div3_bgcolor="#ffffff";
        if (!$div_auth['div1']) {
            $div1_name = '権限無し';
            $div1_bgcolor="#bbbbbb";
        }
        if (!$div_auth['div2']) {
            $div2_name = '権限無し';
            $div2_bgcolor="#bbbbbb";
        }
        if (!$div_auth['div3']) {
            $div3_name = '権限無し';
            $div3_bgcolor="#bbbbbb";
        }
        if ($div_auth['div1']=='*') {
            $div1_name = '全ての組織';
            $div1_bgcolor="#ffd0d0";
        }
        if ($div_auth['div2']=='*') {
            $div2_name = '全ての組織';
            $div2_bgcolor="#ffd0d0";

        }
        if ($div_auth['div3']=='*') {
            $div3_name = '全ての組織';
            $div3_bgcolor="#ffd0d0";

        }

        $html .=<<<HTML
<tr>
<td bgcolor="{$div1_bgcolor}">{$div1_name}</td>
<td bgcolor="{$div2_bgcolor}">{$div2_name}</td>
<td bgcolor="{$div3_bgcolor}">{$div3_name}</td>
<td style="text-align:center;">
<form action="{$PHP_SELF}" method="post" style="display:inline">
<nobr>
<input type="submit" name="mode[edit]" value="編集"{$disabled}>
<input type="submit" name="mode[delete]" value="削除"{$disabled} onclick="return confirm('削除しますか')"></nobr>
<input type="hidden" name="asd_id" value="{$div_auth['asd_id']}">
</form>
</td>
</tr>
HTML;
    }

    $html .=<<<HTML
</table>
<form action="{$PHP_SELF}" method="post">
<input type="submit" name="mode[insert]" value="新規追加"{$disabled}>
</form>
{$getHtmlReduceSelect}
HTML;

    return $html;
}

function getHtmlEditLine($div_auth)
{

    if($div_auth['div1'] == '*')
        $div_auth['div1'] = 'default';
    if($div_auth['div2'] == '*')
        $div_auth['div2'] = 'default';
    if($div_auth['div3'] == '*')
        $div_auth['div3'] = 'default';

    $div_auth['div1'] = $div_auth['div1']?$div_auth['div1']:'default';
    $div_auth['div2'] = $div_auth['div2']?$div_auth['div2']:'default';
    $div_auth['div3'] = $div_auth['div3']?$div_auth['div3']:'default';


    $div = array (
        'default' => '全ての組織'
    );
    foreach (getDivList('div1') as $k => $v)
        $div[$k] = $v;
    $div1_form = FForm :: replaceSelected(FForm :: select('div1', $div, "style='width:200px' onChange='reduce_options(\"id_div1\",\"id_div2\");reduce_options(\"id_div2\",\"id_div3\");' id='id_div1'"), $div_auth['div1']);

    $div = array (
        'default' => '全ての組織'
    );
    foreach (getDivList('div2') as $k => $v)
        $div[$k] = $v;

    $div2_form = FForm :: replaceSelected(FForm :: select('div2', $div, "style='width:200px' onChange='reduce_options(\"id_div2\",\"id_div3\");' id='id_div2'"), $div_auth['div2']);

    $div = array (
        'default' => '全ての組織'
    );
    foreach (getDivList('div3') as $k => $v)
        $div[$k] = $v;

    $div3_form = FForm :: replaceSelected(FForm :: select('div3', $div, "style='width:200px' id='id_div3'"), $div_auth['div3']);
    $PHP_SELF = PHP_SELF;

    return<<<HTML
<form action="{$PHP_SELF}" method="post" style="display:inline">
<tr>
<td>{$div1_form}</td>
<td>{$div2_form}</td>
<td>{$div3_form}</td>
<td style="text-align:center;">
<nobr>
<input type="submit" name="mode[update]" value="更新">
<input type="submit" name="mode[null]" value="取消">
</nobr>
<input type="hidden" name="asd_id" value="{$div_auth['asd_id']}">

</td>
</tr>
</form>
HTML;
}

$html = getHtmlTable();
$objHtml = & new MreAdminHtml("所属別権限設定");
echo $objHtml->getMainHtml($html);
exit;
