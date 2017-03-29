<?php
/**
 * Created on 2007/09/05
 *
 *	@author motoyuki
 */
define('PAGE_TITLE',"配信予約");
define('SESS_ENQ_MAILRSV', "enq_mailrsv");

//define('DEBUG'		, 1);
define('DIR_ROOT'	, "../");
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'CbaseFDB.php');
require_once(DIR_LIB.'CbaseFMail.php');
require_once(DIR_LIB.'CbaseFEventMail.php');
require_once(DIR_LIB.'CbaseEnquete.php');
require_once(DIR_LIB.'CbaseFEnquete.php');
require_once(DIR_LIB.'CbaseFEvent.php');
require_once(DIR_LIB.'CbaseFUser.php');
require_once(DIR_LIB.'CbaseFGeneral.php');
require_once(DIR_LIB.'CbaseFCondition.php');
require_once(DIR_LIB.'CbaseFunction.php');
require_once(DIR_LIB.'CbaseFCheckModule.php');
require_once(DIR_LIB.'CbaseEncoding.php');
require_once(DIR_LIB.'CbaseFForm.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

$DIR_IMG = DIR_IMG;
$PHP_SELF = getPHP_SELF()."?".getSID();

$len1 = 50;	//メールリスト文字数制限

//条件名
/*
$aryCnid = array(
    '0'		=> "全員配信",
    '-1'	=> "リマインダ配信ALL（途中保存者を含む未回答ユーザ全員）",
    '-2'	=> "リマインダ配信未回答（途中保存者を含まない配信）",
    '-3'	=> "リマインダ配信途中保存者（途中保存者に対しての配信）",
    '-4'	=> "特定の方への配信",
    '-99'	=> "回答者への配信"
);
*/
//条件取得
$aryCond = Get_Condition(null,null, $_SESSION['muid']);

$aryCnid[''] = ' - - - - 任意条件 - - - - ';
foreach ($aryCond as $cond) {
    if($cond['name']=="" || $cond['flgt']==-1)	continue;
    if(!ereg('^データ登録名称が',$cond['name']))
        $aryCnid[$cond['cnid']] = $cond['name'];
}

foreach ($aryCond as $cond) {
    if($cond['name']=="")	continue;
    if(ereg('^データ登録名称が',$cond['name']))
        $aryCnid[$cond['cnid']] = $cond['name'];
}

//ユーザ項目取得
$aryUserClm['-1'] = ' - - - - 指定しない - - - - ';
$UsrColmun = getColmunSetting();
foreach ($UsrColmun['label'] as $key => $table) {
    $aryUserClm[$key] = $table;
}

if (!ereg('360_user_search.php', basename($_SERVER['HTTP_REFERER']))) {
    deleteControlUserMst();
    $_SESSION[SESS_ENQ_MAILRSV] = array();
    unset($_SESSION[SESS_ENQ_MAILRSV]);
}

if (empty($_POST)) {
    $_POST = unserialize($_SESSION[SESS_ENQ_MAILRSV]);
    unset($_POST['reservation']);
}

switch ($_POST['reservation']) {
    case "実行":
        //日付処理
        if ($_POST['t']) {
            $tmp = Convert_Date('db',array($_POST['y'],$_POST['m'],$_POST['d'],$_POST['h'],$_POST['i'],$_POST['s']));
            if($tmp)	$_POST['mdate'] = $tmp;
        }
        //mail_rsvにデータを入れる
        if ($_POST['mdate']) {
            $_POST['muid'] = $_SESSION['muid'];

            //リマインダ系、specialto系など特別なcond付け
            $condName='';
            if (is_good($_POST['cnid']) && $_POST['cnid']<=0) {
                $targets = array();
                foreach (explode('@',$_POST['mailtarget']) as $serial) {
                    if($serial)
                        $targets[] = FDB::escape($serial);
                }
                $targets = implode(',',$targets);
                $strSql="select * from usr where serial_no in({$targets})";
                $_POST['cnid'] = transSaveCond($condName, $strSql);
            } elseif ($_POST['userclm']!=-1) {
                $sql = getCondSql($_POST['evid'], $_POST['cnid'], $_POST['specialto'], true);
                switch ($_POST['userclm']) {
                    case "div1" :
                    case "div2" :
                    case "div3" :
                        $div["default"] = "";
                        foreach (getDivList($_POST['userclm']) as $k => $v) {
                            $div[$k] = $v;
                        }
                        $user_target = $div[$_POST[$_POST['userclm']]];
                        $usql = $_POST['userclm']."=".FDB::escape($_POST[$_POST['userclm']]);
                        break;
                    default:
                        $user_target = $_POST["uop"];
                        $usql = $_POST['userclm']."=".FDB::escape($_POST["uop"]);
                        break;
                }
                $condName = html_escape($aryUserClm[$_POST['userclm']])."が".html_escape($user_target);
                if (is_good($_POST['cnid'])) {
                    $condName = $aryCnid[$_POST['cnid']]."(".$condName.")";
                }
                $strSql = (is_void($sql))? "select * from usr where {$usql}":$sql." AND ".$usql;
                $_POST['cnid'] = transSaveCond($condName, $strSql, -1);
            }
            if(!is_false($_POST['cnid']))
                Save_MailEvent('new', $_POST);
        }
        break;

    case "次へ":
        if (!$error = getError($_POST)) {
            $_SESSION[SESS_ENQ_MAILRSV] = serialize($_POST);
            //メール雛形取得
            $aryMail = Get_MailFormat($_POST['dup_mfid'], 'mfid', 'desc', $_SESSION['muid']);
            $mail = $aryMail[0];
            $mail = ($Setting->htmlMail())? unescapeHtml($mail) : escapeHtml($mail);
            $mail['body'] = replaceToHighlight($mail['body']);
            if(!$Setting->htmlMail())
                $mail['body'] = nl2br($mail['body']);

            //アンケートデータ取得
            //$aryEnq = Get_Enquete('id', $_POST['evid'], '', '', $_SESSION['muid']);
            break;
        }

    default:
        $aryMfid = $aryEvid = array('-1' => "-- ここから選択してください --");

        //メール雛形取得
        $aryMail = Get_MailFormat(-1, 'mfodr', '', $_SESSION['muid']);
        foreach ($aryMail as $mail) {
            if($mail['name']=="")		continue;
            if(strlen($mail['name'])>$len1)	$mail['name'] = mb_strimwidth($mail['name'], 0, $len1,'...');
            $aryMfid[$mail['mfid']] = sprintf("%02d", $mail['mfid']).'　'.$mail['name'];
        }
}

/* ----Start HTML----- */
switch ($_POST['reservation']) {
    case "実行":
        $time = html_escape(time());
        if (!is_false($_POST['cnid'])) {
            $body = <<<__HTML__
<p>メールの送信予約が完了いたしました。</p>

<form action="{$PHP_SELF}" method="POST">
<input type="hidden" name="t" value="{$time}">
<input type="submit" name="reservation" value="続けてメールを予約する" class="white button big">
</form>
__HTML__;
        } else {
            $body = <<<__HTML__
<p class="alert">メールの送信予約が失敗いたしました。</p>

<form action="{$PHP_SELF}" method="POST">
<input type="hidden" name="t" value="{$time}">
<input type="submit" name="reservation" value="続けてメールを予約する" class="white button big">
</form>
__HTML__;
        }
        break;

    case "次へ":
        if (!$error) {
            $sql = getCondSql($_POST['evid'], $_POST['cnid'], $_POST['specialto'], true);
            if ($_POST['mailcookie']) {
                $count = 0;
                foreach (explode('@',$_POST['mailtarget']) as $serial) {
                    if($serial)
                        $count++;
                }
                $userCnt = $count.'<input type="hidden" name="mailcookie" value="1">';
                $mt = html_escape($_POST['mailtarget']);
                $userCnt .= <<<__HTML__
<input type="hidden" name="mailtarget" value="{$mt}">
__HTML__;
                $condname ="[ チェックを入れた人への配信 ]";
            } else {
                if ($_POST['userclm']==-1) {
                    $username = html_escape($aryUserClm[$_POST['userclm']]);
                } else {
                    switch ($_POST['userclm']) {
                        case "div1" :
                        case "div2" :
                        case "div3" :
                            $div["default"] = "";
                            foreach (getDivList($_POST['userclm']) as $k => $v) {
                                $div[$k] = $v;
                            }
                            $user_target = $div[$_POST[$_POST['userclm']]];
                            $usql = $_POST['userclm']."=".FDB::escape($_POST[$_POST['userclm']]);
                            break;
                        default:
                            $user_target = $_POST["uop"];
                            $usql = $_POST['userclm']."=".FDB::escape($_POST["uop"]);
                            break;
                    }
                    $username = html_escape($aryUserClm[$_POST['userclm']])."が".html_escape($user_target);
                    $sql = (is_void($sql))? "select * from usr where {$usql}":$sql." AND ".$usql;
                }
                $count = FDB::getAssoc("select count(*) as count from ({$sql}) as count_table;");
                $userCnt = $count[0]['count'];
                $condname = html_escape($aryCnid[$_POST['cnid']]);
            }

            $userMst = <<<__HTML__
<font size="2">※ {$userCnt}件のユーザが対象</font>
__HTML__;

            $time = html_escape(time());

            $aryEnq[-1]['name'] = html_escape($aryEnq[-1]['name']);

            $warn = (isOpenEnqueteByFlgo($aryEnq[-1]['flgo']))? "※オープンアンケートです":"";
            $alert = "";
            $mdate = Convert_Date('db',array($_POST['y'],$_POST['m'],$_POST['d'],$_POST['h'],$_POST['i'],$_POST['s']));
            $mailrsv_data = array();
            foreach($_POST as $key => $post)
            {
                $mailrsv_data[$key] = html_escape($post);
            }
            if (is_good($mdate) && (strtotime($mdate)<time())) {
                $alert = <<<__HTML__
<p class="alert"><b>過去の日時のため、すぐに配信されます</b></p>
__HTML__;
                $confirm = <<<__JS__
onclick="if (confirm('過去の日時のため、すぐに配信されますが、よろしいですか?')) { return true; } return false;"
__JS__;
            }
            $body = <<<__HTML__
<form action="{$PHP_SELF}" method="POST">
<!-- <input type="hidden" name="mrid" value=""> -->
<input type="hidden" name="mfid" value ="{$_POST['dup_mfid']}">
<input type="hidden" name="dup_mfid" value ="{$_POST['dup_mfid']}">
<input type="hidden" name="flgs" value ="0">
<input type="hidden" name="t" value="{$time}">
<table class="searchbox">
{$error}
{$alert}
  <tr>
    <th width="100" nowrap><font size="2">名称</font></td>
    <td>
      <font size="2">{$mailrsv_data['yoyaku_name']}</font>
      <input type="hidden" name="name" value="{$mailrsv_data['yoyaku_name']}">
      <input type="hidden" name="yoyaku_name" value="{$mailrsv_data['yoyaku_name']}">
    </td>
  </tr>
  <tr>
    <th nowrap><font size="2">配信日</font></td>
    <td>
      <font size="2">{$mailrsv_data['y']}年</font><font size="2">{$mailrsv_data['m']}月</font><font size="2">{$mailrsv_data['d']}日</font>
      <input type="hidden" name="y" value ="{$mailrsv_data['y']}">
      <input type="hidden" name="m" value="{$mailrsv_data['m']}">
      <input type="hidden" name="d" value="{$mailrsv_data['d']}">
    </td>
  </tr>
  <tr>
    <th nowrap><font size="2">配信時間</font></td>
    <td>
      <font size="2">{$mailrsv_data['h']}時</font><font size="2">{$mailrsv_data['i']}分</font>
      <input type="hidden" name="h" value="{$mailrsv_data['h']}">
      <input type="hidden" name="i" value="{$mailrsv_data['i']}">
    </td>
  </tr>
  <tr>
    <th nowrap><font size="2">配信条件</font></td>
    <td>
      <b>{$condname}</b>
      <input type="hidden" name="cnid" value="{$mailrsv_data['cnid']}">
      <input type="hidden" name="specialto" value="{$mailrsv_data['specialto']}">
    </td>
  </tr>
  <tr>
  <tr>
    <th nowrap><font size="2">ユーザ条件</font></td>
    <td>
      <b>{$username}</b>
      <input type="hidden" name="userclm" value="{$mailrsv_data['userclm']}">
      <input type="hidden" name="uop" value="{$mailrsv_data['uop']}">
      <input type="hidden" name="div1" value="{$mailrsv_data['div1']}">
      <input type="hidden" name="div2" value="{$mailrsv_data['div2']}">
      <input type="hidden" name="div3" value="{$mailrsv_data['div3']}">
      </td>
  </tr>
  <tr>
  <th nowrap><font size="2">メールひな型名称</font></td>
    <td>
      <font size="2">{$mail['name']}</font>
    </td>
  </tr>
  <tr>
    <th nowrap><font size="2">メール件名</font></td>
    <td>
      <font size="2">{$mail['title']}</font>
    </td>
  </tr>
  <tr>
    <th nowrap><font size="2">メール本文</font></td>
    <td width="600px">
      <font size="2">{$mail['body']}</font>
    </td>
  </tr>
  <tr>
    <th colspan="2" nowrap><font size="2">配信対象</font></td>
  </tr>
  <tr>
    <td colspan="2">
{$userMst}
    </td>
  </tr>
    <tr>
        <td valign="middle" align="center" colspan="2">
            <input type="submit" name="return" value="戻る"class="button white big">
            <input type="submit" name="reservation" value="実行"class="button white big" {$confirm}>
        </td>
    </tr>
</table>
</form>
__HTML__;
            break;
        }

    default:
        if ($error) {
            $error = implode("<br>", $error);
            $error = <<<__HTML__
  <tr>
    <td colspan="2">
      <font color="red" size="2">
        入力エラーです。以下の箇所を見直してください。<br>
        {$error}
      </font>
    </td>
  </tr>
__HTML__;
        }

        $evidHidden = $evidDisabled = '';


        if (isset($_SESSION[SHOW_USER_MST_SKEY]['evid']) && $_SESSION[SHOW_USER_MST_SKEY]['evid']!='empty') {
            $_POST['evid'] = $_SESSION[SHOW_USER_MST_SKEY]['evid'];
            $evidHidden = <<<__HTML__
<input type="hidden" name="evid" value="{$_POST['evid']}">
__HTML__;
            $evidDisabled = ' disabled';
        }

        $cnidHidden = $cnidDisabled = '';
        if (isset($_SESSION[SHOW_USER_MST_SKEY]['serial_no_list'])) {
            $arySerialNo = explode(",", $_SESSION[SHOW_USER_MST_SKEY]['serial_no_list']);
            foreach ($arySerialNo as $key => $value) {
                $arySerialNo[$key] = FDB::escape(trim($value));
            }
            $where = "where serial_no in (".implode(",", $arySerialNo).")";
            $specialto = getUserMst($where, false);
            $_POST['cnid'] = '-4';
            $cnidHidden = <<<__HTML__
<input type="hidden" name="cnid" value="{$_POST['cnid']}">
__HTML__;
            $cnidDisabled = ' disabled';
        } else {
            $specialto_data = html_escape($_POST['specialto']);
            $specialto = <<<__HTML__
<textarea name="specialto" cols="50" rows="5" style="font-size:small;">{$specialto_data}</textarea>
__HTML__;
        }

        $display = (isset($_SESSION[SHOW_USER_MST_SKEY]['serial_no_list']) || $_POST['cnid']=='-4')? '':'none';

        $dup_mfidOption	= getOptionTag(escapeHtml($aryMfid), $_POST['dup_mfid']);
        $yOption		= getOptionTag2($yyyy, ($_POST['y'])? $_POST['y']:date('Y'));
        $mOption		= getOptionTag2($mm, ($_POST['m'])? $_POST['m']:date('m'));
        $dOption		= getOptionTag2($dd, ($_POST['d'])? $_POST['d']:date('d')+1);
        $hOption		= getOptionTag2($mailrsv_hh, ($_POST['h'])? $_POST['h']:date('H')+1);
        $iOption		= getOptionTag2($ii, ($_POST['i'])? $_POST['i']:0);
        $evidOption		= getOptionTag($aryEvid, $_POST['evid']);
        $cnidOption		= getOptionTag($aryCnid, $_POST['cnid']);
        $uclmOption		= getOptionTag($aryUserClm, $_POST['userclm']);
        $userOption = html_escape($_POST['uop']);
        $div_ary = array("div1", "div2", "div3");
        foreach ($div_ary as $dvalue) {
            $div[$value]["default"] = "";
            foreach (getDivList($dvalue) as $k => $v) {
                $div[$dvalue][$k] = $v;
            }
        }
        $div1 = FForm :: replaceSelected(FForm :: select("div1", $div["div1"], "style='width:230px' id='id_div1' 'display:none'"), $_POST['div1']);
        $div2 = FForm :: replaceSelected(FForm :: select("div2", $div["div2"], "style='width:230px' id='id_div2' 'display:none'"), $_POST['div2']);
        $div3 = FForm :: replaceSelected(FForm :: select("div3", $div["div3"], "style='width:230px' id='id_div3' 'display:none'"), $_POST['div3']);
    if ($_POST['mailcookie']) {
        $targets = array();
        $target_list = array();
        if (is_good($_POST['mailtarget'])) {
            foreach (explode('@',$_POST['mailtarget']) as $serial) {
                if($serial)
                    $targets[] = FDB::escape($serial);
            }
            $targets = implode(',',$targets);
            foreach (FDB::select(T_USER_MST, "*", "where serial_no in({$targets})") as $usr) {
                $target_list[] = $usr['uid'].' '.$usr['name'];
            }
        }
        $count = count($target_list);
        $target_list = implode("<br>", $target_list);
        $mt = html_escape($_POST['mailtarget']);
$cnidhtml=<<<HTML
<input type="hidden" name="mailcookie" value="1">
<input type="hidden" name="mailtarget" value="{$mt}">
<input type="hidden" name="cnid" value="-1">
<span style="font-size:13px">[ チェックを入れた人への配信 ({$count}名)]</span>
<div>{$target_list}</div>
HTML;


    } else {
$cnidhtml=<<<HTML
      {$cnidHidden}
      <select name="cnid" onchange="sspecialto(this);"{$cnidDisabled}>{$cnidOption}</select>
HTML;

 $usrhtml=<<<HTML
      {$uclmHidden}
      <select name="userclm" onchange="getuserOption(this);"{$uclmDisabled}>{$uclmOption}</select>
HTML;
$usroptionhtml=<<<HTML
      <input type="text" name="uop" id="uop" value="{$userOption}" size="15" "style='display:none'">
      {$div1}
      {$div2}
      {$div3}
HTML;

    }

        $sYoyakuName = html_escape($_POST['yoyaku_name']);
        $userclm = (is_void($_POST['userclm']))? -1:html_escape($_POST['userclm']);
        $body .= <<<__HTML__
<script type="text/javascript">
<!--
function sspecialto(obj)
{
    if (obj.value=="-4") {
        document.getElementById("spto").style.display="";
        document.getElementById("spto2").style.display="";
    } else {
        document.getElementById("spto").style.display="none";
        document.getElementById("spto2").style.display="none";
    }
}

function getuserOption(obj)
{
    var ob = (obj==null)? "{$userclm}":obj.value;
    if (ob=="-1") {
        document.getElementById("useroption").style.display="none";
    } else {
        document.getElementById("useroption").style.display="";
        if (ob=="div1") {
            document.getElementById("uop").style.display="none";
            document.getElementById("id_div1").style.display="";
            document.getElementById("id_div2").style.display="none";
            document.getElementById("id_div3").style.display="none";
        } else if (ob=="div2") {
            document.getElementById("uop").style.display="none";
            document.getElementById("id_div1").style.display="none";
            document.getElementById("id_div2").style.display="";
            document.getElementById("id_div3").style.display="none";
        } else if (ob=="div3") {
            document.getElementById("uop").style.display="none";
            document.getElementById("id_div1").style.display="none";
            document.getElementById("id_div2").style.display="none";
            document.getElementById("id_div3").style.display="";
        } else {
            document.getElementById("uop").style.display="";
            document.getElementById("id_div1").style.display="none";
            document.getElementById("id_div2").style.display="none";
            document.getElementById("id_div3").style.display="none";
        }
    }
}

//-->
</script>
<script type="text/javascript">
<!--
$(function () {
    getuserOption();
});
//-->
</script>

<form action="{$PHP_SELF}" method="POST">
<table class="searchbox">
{$error}
  <tr>
    <th width="100" nowrap><font size="2">名称</font></td>
    <td><input type="text" name="yoyaku_name" value="{$sYoyakuName}" size="35"></td>
  </tr>
  <tr>
    <th nowrap><font size="2">ひな型選択</font></td>
    <td>
      <select name="dup_mfid">{$dup_mfidOption}</select>
    </td>
  </tr>
  <tr>
    <th nowrap><font size="2">配信日</font></td>
    <td><font size="2">　
      <select name="y">{$yOption}</select> 年
      <select name="m">{$mOption}</select> 月
      <select name="d">{$dOption}</select> 日
      <input type="hidden" class="date"/>
    </font></td>
  </tr>
  <tr>
    <th nowrap><font size="2">配信時間</font></td>
    <td><font size="2">　
      <select name="h">{$hOption}</select> 時
      <select name="i">{$iOption}</select> 分
      <input type="hidden" name="s" value="0">
    </font></td>
  </tr>
  <tr>
    <th nowrap><font size="2">配信条件</font></td>
    <td>
        {$cnidhtml}
    </td>
  </tr>
  <tr style="display:{$display};" id="spto">
    <th colspan="2" nowrap><font size="2">配信Email</font></td>
  </tr>
  <tr style="display:{$display};" id="spto2">
    <td colspan="2">
      {$specialto}
    </td>
  </tr>
  <tr>
    <th nowrap><font size="2">ユーザ条件</font></td>
    <td>
        {$usrhtml}
        <span id="useroption" sytle="float:left;margin-left:80px; 'display:none'">が{$usroptionhtml}</span>
    </td>
  </tr>
    <tr>
      <td colspan="2" style="text-align:center">
        <input type="submit" name="reservation" value="次へ"class="button white big">
      </td>
    </tr>
</table>
</form>
__HTML__;
}

$y_max = end($yyyy);
$y_min = reset($yyyy);
$objHtml = new MreAdminHtml(PAGE_TITLE, "配信予約します。", false);
$objHtml->setSrcJs(<<<__JS__
$(".date").datepicker({
    showOn: 'button',
    altFormat: 'yy/m/d',
    dateFormat: 'yy/m/d',
    changeMonth: true,
    changeYear: true,
    showMonthAfterYear: true,
    yearSuffix:"\u5e74",
    monthNamesShort:["1\u6708","2\u6708","3\u6708","4\u6708","5\u6708","6\u6708","7\u6708","8\u6708","9\u6708","10\u6708","11\u6708","12\u6708"],
    dayNamesMin:["\u65e5","\u6708","\u706b","\u6c34","\u6728","\u91d1","\u571f"]
});
$( ".date" ).datepicker( "option", "minDate", "{$y_min}/1/1" );
$( ".date" ).datepicker( "option", "maxDate", "{$y_max}/12/31" );
$('.date').live("change",function () {
    var val = $(this).val();
    var date = val.split("/");
    $('select[name="y"]').val(date[0]);
    $('select[name="m"]').val(date[1]);
    $('select[name="d"]').val(date[2]);
});
var setMailDate = function () {
    var y = $('select[name="y"]').val();
    var m = $('select[name="m"]').val();
    var d = $('select[name="d"]').val();
    $(".date").val(y+"/"+m+"/"+d);
}
$('select[name="y"], select[name="m"], select[name="d"]').live("change", setMailDate);
$(function () {
    setMailDate();
});
__JS__
);
echo $objHtml->getMainHtml($body);
exit;


/**
 * mailrsv用condテーブル用sql生成
 *
 *		@param int evid
 *		@param int mode 配信対象モード
 *		@param string specialto 一人に送る場合のあて先メアド
 *		@return string sql
 */
function getCondSql($evid, $mode, $specialto="", $onlyWhere=false)
{
    $res = FDB::select1(T_COND, "strsql", "where cnid=".FDB::escape($mode));

    return $res['strsql'];
}


/**
 * mailrsv用cond保存
 * 		※本来はFConditionと統合すべき→sqlsearchを含め再構築が必要
 */
function transSaveCond($prmName, $prmSql, $flgt="")
{
    //配列を生成
    $cdate = date("Y-m-d H:i:s");
    $strSendData = array(
        'name'		=> $prmName,
        'strsql'	=> $prmSql,
        'pgcache'	=> null,
        'muid'		=> $_SESSION['muid'],
        'flgt'		=> $flgt,
        'cdate'	=> $cdate,
        'udate'	=>$cdate
    );

    return Save_Condition('new', $strSendData);
}


/**
 * エラー取得
 */
function getError($post)
{
    if (empty($post['yoyaku_name'])) {			//名称
        $error[] = "配信名称が指定されておりません。";
    }
    if (!ereg("^[0-9]+$", $post['dup_mfid'])) {	//雛形
        $error[] = "メールひな型が選択されておりません。";
    }
    if (!ereg("^[0-9\-]+$", $post['cnid']) && $post['userclm']==-1) {		//アンケート
        $error[] = "条件が指定されておりません。";
    }
    if (!ereg("^[0-9\-]+$", $post['cnid']) && $post['userclm']!=-1) {		//アンケート
        switch ($_POST['userclm']) {
            case "div1" :
            case "div2" :
            case "div3" :
                if(is_void($post[$post['userclm']]))
                    $error[] = "ユーザ条件が指定されておりません。";
                break;
            default:
                if(is_void($_POST["uop"]))
                    $error[] = "ユーザ条件が指定されておりません。";
                break;
        }
    }

    if (Fcheck::isDate($post['y']."-".$post['m']."-".$post['d'])) {
        $error[] = "配信日が不正な日付です。";
    }

    return $error;
}


/**
 * オプションタグ取得
 */
function getOptionTag($array, $select_key=null)
{


    $option = '';
    foreach ($array as $key => $value) {
        $selected = ($key==$select_key)? ' selected':'';

        if(ereg('^kugiri',$key))
            $style = ' style="color:white;background-color:#666666"';
        else
            $style = '';
        $option .= <<<__HTML__
<option value="{$key}"{$selected}{$style}>{$value}</option>\n
__HTML__;
    }

    return $option;
}


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
