<?php
// define('DEBUG',1 );
define("NOT_CONVERT", 1);
define("DIR_ROOT", "../");

require_once (DIR_ROOT . "crm_define.php");
require_once (DIR_LIB . 'CbaseEncoding.php');

require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseDataEditor.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
define('FFORM_ESCAPE', 1);
require_once (DIR_LIB . 'CbaseFForm.php');

require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
require_once (DIR_LIB . '360_EventSubTemplate.php');
encodeWebAll();

session_start();
require_once (DIR_LIB . 'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));
$PHP_SELF = getPHP_SELF();
$SID = getSID();
$version = date("YmdHis");

if (is_good($_POST['page_1'])) {
    if (is_true($update1 = updateSetting1())) {
        $MESSAGE = "<script>$().toastmessage('showSuccessToast', '設定を更新しました');</script>";
    } else {
        $MESSAGE = "<script>$().toastmessage('showWarningToast', '{$update1}');</script>";
    }
}

if (is_good($_POST['page_2'])) {
    if (is_true($update2 = updateSetting2())) {
        $MESSAGE = "<script>$().toastmessage('showSuccessToast', '設問を再作成しました');</script>";
    } else {
        $MESSAGE = "<script>$().toastmessage('showWarningToast', '{$update2}');</script>";
    }
}

$html = <<<__HTML__
{$MESSAGE}
<div id="tabs">
  <ul>
    <li><a href="#tabs-1">全体設定</a></li>
    <li><a href="#tabs-2">質問を一括作成</a></li>
    <li><a href="360_enq_update_all.php?{$SID}">質問を一括更新</a></li>
  </ul>
__HTML__;

$main_title = FDB::select1(T_MESSAGE, "*", "WHERE mkey = ".FDB::escape("main_title"));
$main_title = $main_title['body_0'];

$mail_from = FDB::select1(T_SETTING, "*", "WHERE define = ".FDB::escape("MAIL_SENDERNAME0"));
$mail_from = $mail_from['value'];

$mail_sender = FDB::select1(T_SETTING, "*", "WHERE define = ".FDB::escape("MAIL_SENDER0"));
$mail_sender = $mail_sender['value'];

$res = FDB::select1(T_FROMTO, "*", "WHERE evid = ".FDB::escape("100")." AND type = ".FDB::escape("input"));
$term_input['s'] = date('Y/m/d H:i:s', strtotime($res['sdate']));
$term_input['e'] = date('Y/m/d H:i:s', strtotime($res['edate']));

$res = FDB::select1(T_FROMTO, "*", "WHERE evid = ".FDB::escape("100")." AND type = ".FDB::escape("fb"));
$term_fb['s'] = date('Y/m/d H:i:s', strtotime($res['sdate']));
$term_fb['e'] = date('Y/m/d H:i:s', strtotime($res['edate']));

$image_color = FDB::select1(T_MESSAGE, "*", "WHERE mkey = ".FDB::escape("image_color"));
$image_color = $image_color['body_0'];

$infomation = FDB::select1(T_MESSAGE, "*", "WHERE mkey = ".FDB::escape("mypage_infomation"));
$infomation = $infomation['body_0'];

$user_types = "";
foreach (getUserTypes() as $user_type) {
    if(!in_array($user_type['utype'], array(0,1))) continue;

    $title = ($user_type['utype']==0)? "回答者タイプ" : "評価者タイプ".$user_type['user_type_id'];

    $user_types .= <<<__HTML__
<tr>
    <th class="tr1">{$title}</th>
    <td class="tr2">
    <input type="text" id="user_type_{$user_type['user_type_id']}" name="user_type[{$user_type['user_type_id']}]" value="{$user_type['name']}" style="width:230px">
    </td>
</tr>
__HTML__;
}

$html .= <<<__HTML__
<div id="tabs-1">
<form action="{$PHP_SELF}?{$SID}" method="post" enctype="multipart/form-data">
    <div align="left">

    <h2>案件概要</h2>

    <table class="searchbox"><tbody>
    <tr>
        <th class="tr1">案件（評価タイトル）名</th><td class="tr2"><input type="text" id="main_title" name="main_title" value="{$main_title}" style="width:260px"></td>
    </tr>
    <tr>
        <th class="tr1">事務局（メール配信元）名称</th><td class="tr2"><input type="text" id="mail_from" name="mail_from" value="{$mail_from}" style="width:260px"></td>
    </tr>
    <tr>
        <th class="tr1">メールアドレス</th><td class="tr2"><input type="text" id="mail_sender" name="mail_sender" value="{$mail_sender}" style="width:260px"></td>
    </tr>
    </tbody></table>

    <h2>スケジュール</h2>

    <table class="searchbox"><tbody>
    <tr>
        <th class="tr1">回答期間</th>
        <td class="tr2">
        <input type="text" id="term_input_s" name="term_input[s]" class="date" value="{$term_input['s']}" style="width:160px"> <->
        <input type="text" id="term_input_e" name="term_input[e]" class="date" value="{$term_input['e']}" style="width:160px">
        </td>
    </tr>
    <tr>
        <th class="tr1">FB期間</th>
        <td class="tr2">
        <input type="text" id="term_fb_s" name="term_fb[s]" class="date" value="{$term_fb['s']}" style="width:160px"> <->
        <input type="text" id="term_fb_e" name="term_fb[e]" class="date" value="{$term_fb['e']}" style="width:160px">
        </td>
    </tr>
    </tbody></table>

    <h2>マイページ設定</h2>

    <table class="searchbox"><tbody>
    <tr>
        <th class="tr1">社名ロゴのアップロード</th>
        <td class="tr2">
            <img src="{$GDF->get('DIR_IMG_USER')}logo.png?v={$version}"/ height="35px"><br/>
            <input type="file" id="logo" name="logo">
        </td>
    <tr>
        <th class="tr1">イメージカラー</th>
        <td class="tr2">
            <input type="text" id="colorpicker" name="image_color" value="{$image_color}">
        </td>
    </tr>

    </tbody></table>

    <table class="searchbox"><tbody>
    <tr>
        <th class="tr1">お知らせ文章</th>
        <td class="tr2">
        <textarea id="infomation" name="infomation" cols="50" rows="10">{$infomation}</textarea>
        </td>
    <tr>

    </tbody></table>

    <table class="searchbox"><tbody>
        {$user_types}
    </tbody></table>

    </div>
    <p>
        <input type="submit" name="page_1" value="更新" class="big white button"
            onClick='return myconfirm("全てのシートに関する設定を変更します。よろしいですか？")'/>
    </p>
</form>
</div>
__HTML__;

$sheets = getSheetList();
$subevents = FDB::select(T_EVENT_SUB, "*", "WHERE evid = ".FDB::escape($sheets[0]['sheet_type']*100));
$select_question = 0;
$message_question = 0;

foreach ($subevents as $subevent) {
    if($subevent['type1'] == 4 && $subevent['type2'] == "t")
        ++$message_question;

    if($subevent['type1'] != 1) continue;

    ++$select_question;
    $choice = implode("\n", explode(",", $subevent['choice']));
    $chtable = implode("\n", explode(",", $subevent['chtable']));
}

$chrate = array();
foreach (explode("\n", $chtable) as $ch) {
    $b = FDB::select1(T_MESSAGE, "*", "WHERE mkey = ".FDB::escape("enq_rate_".$ch));
    $chrate[] = $b['body_0'];
}
$chrate = implode("\n", $chrate);

$html .= <<<__HTML__
<div id="tabs-2">
<form action="{$PHP_SELF}?{$SID}" method="post" enctype="multipart/form-data">

<div align="left">
<h2>選択肢設問</h2>
<table class="searchbox"><tbody>
<tr>
    <th class="tr1">質問数</th>
    <td class="tr2" colspan="3"><input type="text" id="select_question" name="select_question" value="{$select_question}" style="width:230px"></td>
</tr>
<tr>
    <th class="tr1">表示用選択肢</th>
    <td class="tr2"><textarea name="choice" rows="7" cols="20">{$choice}</textarea></td>
    <th class="tr1">集計用選択肢</th>
    <td class="tr2"><textarea name="chtable" rows="7" cols="20">{$chtable}</textarea></td>
</tr>
<tr>
    <th class="tr1">定義</th>
    <td class="tr2" colspan="3"><textarea name="chrate" rows="7" cols="40">{$chrate}</textarea></td>
</tr>
</tbody></table>

<h2>コメント設問</h2>
<table class="searchbox"><tbody>
<tr>
    <th class="tr1">質問数</th>
    <td class="tr2"><input type="text" id="message_question" name="message_question" value="{$message_question}" style="width:230px"></td>
</tr>

</tbody></table>


</div>

<p>
    <input type="submit" name="page_2" value="一括作成" class="big white button"
        onClick='return myconfirm("【注意】<br>全てのシートの質問設定を削除し、再作成します。よろしいですか？")'/>
</p>

</form>
</div>
__HTML__;

$html .= <<<__HTML__
<!--
    <div id="tabs-3">

    <div align="center">

    <h2>現在の設定をダウンロードする</h2>
    <p><a class="btn" href="360_enq_update_all.php?csvdownload=1&{$SID}">　ダウンロード　</a></p>

    <br>

    <h2>インポートして上書きする</h2>
    <p><a class="btn" href="360_enq_update_all.php?{$SID}">　インポート　</a></p>
    </div>

    </div>
-->
</div>
__HTML__;

$objHtml = new MreAdminHtml("かんたん設定", "", false);
$objHtml->addFileCss(DIR_IMG."evol.colorpicker.css");
$objHtml->addFileJs(DIR_IMG."evol.colorpicker.min.js");
$active = (is_good($_POST['page_2']))? 1:0;
$objHtml->setSrcJs(
<<<__JS__
<!--
$(function () {
$("#tabs").tabs({active:{$active}});
$("#colorpicker").colorpicker();
});
$(".date").datepicker({
    altFormat: 'yy/mm/dd 00:00:00',
    dateFormat: 'yy/mm/dd 00:00:00',
    changeMonth: true,
    changeYear: true,
    showMonthAfterYear: true,
    yearSuffix:"\u5e74",
    monthNamesShort:["1\u6708","2\u6708","3\u6708","4\u6708","5\u6708","6\u6708","7\u6708","8\u6708","9\u6708","10\u6708","11\u6708","12\u6708"],
    dayNamesMin:["\u65e5","\u6708","\u706b","\u6c34","\u6728","\u91d1","\u571f"]
});
//-->
__JS__
);
echo $objHtml->getMainHtml($html);
exit;

/**/
function updateSetting1()
{
    $res = array();
    FDB::begin();

    $array = array();
    foreach(range(0,4) as $i)
        $array['body_'.$i] = $_POST['main_title'];
    $res[] = FDB::update(T_MESSAGE, FDB::escapeArray($array), "WHERE mkey = ".FDB::escape("main_title"));

    foreach (range(0,4) as $i) {
        $array = array();
        $array['value'] = $_POST['mail_from'];
        $res[] = FDB::update(T_SETTING, FDB::escapeArray($array), "WHERE define = ".FDB::escape("MAIL_SENDERNAME".$i));

        $array = array();
        $array['value'] = $_POST['mail_sender'];
        $res[] = FDB::update(T_SETTING, FDB::escapeArray($array), "WHERE define = ".FDB::escape("MAIL_SENDER".$i));
    }

    $array = array();
    $array['sdate'] = $_POST['term_input']['s'];
    $array['edate'] = $_POST['term_input']['e'];
    if (!strtotime($array['sdate']) || !strtotime($array['edate'])) {
        FDB::rollback();

        return "日付の形式が不正です";
    }
    $array['sdate'] = date('Y/m/d H:i:s', strtotime($array['sdate']));
    $array['edate'] = date('Y/m/d H:i:s', strtotime($array['edate']));
    $res[] = FDB::update(T_FROMTO, FDB::escapeArray($array), "WHERE type = ".FDB::escape("input"));

    $array = array();
    $array['sdate'] = $_POST['term_fb']['s'];
    $array['edate'] = $_POST['term_fb']['e'];
    if (!strtotime($array['sdate']) || !strtotime($array['edate'])) {
        FDB::rollback();

        return "日付の形式が不正です";
    }
    $array['sdate'] = date('Y/m/d H:i:s', strtotime($array['sdate']));
    $array['edate'] = date('Y/m/d H:i:s', strtotime($array['edate']));
    $res[] = FDB::update(T_FROMTO, FDB::escapeArray($array), "WHERE type = ".FDB::escape("fb")." OR type = ".FDB::escape("review"));

    $array = array();
    foreach(range(0,4) as $i)
        $array['body_'.$i] = $_POST['image_color'];
    $res[] = FDB::update(T_MESSAGE, FDB::escapeArray($array), "WHERE mkey = ".FDB::escape("image_color"));

    $array = array();
    foreach(range(0,4) as $i)
        $array['body_'.$i] = $_POST['infomation'];
    $res[] = FDB::update(T_MESSAGE, FDB::escapeArray($array), "WHERE mkey = ".FDB::escape("mypage_infomation"));

    foreach ($_POST['user_type'] as $user_type_id => $name) {
        $array = array();
        $array['name'] = $name;
        $array['admin_name'] = $name;
        $res[] = FDB::update(T_USER_TYPE, FDB::escapeArray($array), "WHERE user_type_id = ".FDB::escape($user_type_id));
    }

    if (in_array(false, $res, true)) {
        FDB::rollback();

        return "更新に失敗しました";
    }
    FDB::commit();
    clearFromtoCache();
    clearMessageCache();
    clearSettingCache();
    clearUserTypeCache();

    if ($_FILES['logo']['size'] > 0) {
        if ($_FILES['logo']['type'] != "image/png") {
            return "ロゴはpng形式のみアップロード可能です";
        }

        $DIR_IMG = DIR_IMG_USER_LOCAL;
        rename($_FILES['logo']['tmp_name'], $DIR_IMG . "logo.png");
        if (is_false(syncCopy($DIR_IMG . "logo.png"))) {
            return "ロゴのアップロードに失敗しました";
        }
    }

    return true;
}

function updateSetting2()
{
    FDB::begin();
    $new = array();
    $res = array();

    if(count(explode("\n", $_POST['choice'])) != count(explode("\n", $_POST['chtable']))
            || count(explode("\n", $_POST['choice'])) != count(explode("\n", $_POST['chrate'])))
    {
        FDB::rollback();

        return "選択肢、定義の数が一致しません";
    }

    $choice = array_map("atrim", explode("\n", $_POST['choice']));
    $chtable = array_map("atrim", explode("\n", $_POST['chtable']));
    $chrate = array_map("atrim", explode("\n", $_POST['chrate']));

    foreach ($chtable as $ch) {
        if (preg_match("/^[a-zA-Z0-9!-\/:-@¥[-`{-~]+$/ui", $ch) !== 1) {
            FDB::rollback();

            return "集計用選択肢に半角英数記号以外が含まれています";
        }
    }

    if(is_int($_POST['select_question']) && is_int($_POST['message_question'])
            && $_POST['select_question'] + $_POST['message_question'] < 900)
    {
        FDB::rollback();

        return "選択肢設問の数が不正です";
    }

    foreach ($chtable as $k => $ch) {
        $array = array();
        foreach(range(0,4) as $i)
            $array['body_'.$i] = $chrate[$k];
        if (is_good(FDB::select1(T_MESSAGE, "*", "WHERE mkey = ".FDB::escape("enq_rate_".$ch)))) {
            $res[] = FDB::update(T_MESSAGE, FDB::escapeArray($array), "WHERE mkey = ".FDB::escape("enq_rate_".$ch));
        } else {
            $array['mkey'] = "enq_rate_".$ch;
            $array['name'] = "回答基準 ".$ch;
            $res[] = FDB::insert(T_MESSAGE, FDB::escapeArray($array));
        }
    }

    foreach (FDB::select(T_EVENT, "evid") as $event) {
        $subevents = array();

        $evid = $event['evid'];
        $seid = (int) $evid * 1000;
        FDB::delete(T_EVENT_SUB, "WHERE evid = ".FDB::escape($evid));

        if ($_POST['select_question'] > 0) {
            $s = new SFreeSpace(array("evid" => $evid, "seid" => $seid));
            $s->title = "選択肢設問タイトル";
            $s->html2 = "<h3 class='qtitle'>####question1####</h3>";
            $subevents[] = $s->get();
        }

        $s = new SAnswerRateTable(array("evid" => $evid, "seid" => ++$seid));
        $rateArray = array();
        foreach($chtable as $i)
            $rateArray[] = "####enq_rate_".$i."####";
        $s->setRateArray($rateArray);

        $subevents[] = $s->get();

        $s = new STableHeader(array("evid" => $evid, "seid" => ++$seid));
        $subevents[] = $s->get();

        $num_count = 0;
        foreach (range(1, $_POST['select_question']) as $i) {
            $num_count++;
            $c1 = "カテゴリー".(floor(($i+3)/4));
            $c2 = "サブ".(floor(($i+1)/2));
            $setting = array(
                "evid" => $evid,
                "seid" => ++$seid,
                "num" => $num_count,
                "num_ext" => $num_count,
                "category1" => $c1,
                "category2" => $c2,
                "choice" => implode(",", $choice),
                "chtable" => implode(",", $chtable)
            );
            $s = new SSelectQuestion($setting);
            $subevents[] = $s->get();
        }

        $s = new STableFooter(array("evid" => $evid, "seid" => ++$seid));
        $subevents[] = $s->get();

        if ($_POST['message_question'] > 0) {
            $s = new SFreeSpace(array("evid" => $evid, "seid" => ++$seid));
            $s->title = "記述設問タイトル";
            $s->html2 = "<h3 class='qtitle'>####question2####</h3>";
            $subevents[] = $s->get();
        }

        foreach (range(1, $_POST['message_question']) as $i) {
            $num_count++;
            $s = new SMessageQuestion(array("evid" => $evid, "seid" => ++$seid));
            $s->num = $num_count;
            $s->num_ext = $num_count;
            $subevents[] = $s->get();
        }

        $confirm = new SConfirm(array("evid" => $evid, "seid" => ++$seid, "page" => 2));
        $confirm->setSubevents($subevents);

        $res[] = FDB::update(T_EVENT, array("lastpage" => FDB::escape(2)), "WHERE evid = ".FDB::escape($evid));

        $subevents[] = $confirm->get();
        $new = array_merge($new, $subevents);
    }

    foreach ($new as $n) {
        $n = FDB::escapeArray($n);
        $res[] = FDB::insert(T_EVENT_SUB, $n);
    }

    if (in_array(false, $res, true)) {
        FDB::rollback();

        return "作成に失敗しました";
    }
    FDB::commit();

    return true;
}
