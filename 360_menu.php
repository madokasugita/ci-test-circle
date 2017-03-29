<?php
/**
 * PG名称：ユーザ用マイページ
 * 日　付：2010/04/14
 * 作成者：cbase Kido
 */
/******************************************************************************************************/
/** ルートディレクトリ */
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseFForm.php');
require_once (DIR_LIB . '360_FHtml.php');
require_once (DIR_LIB . '360_Smarty.php');
encodeWebAll();
session_start();
clearSessionExceptForLoginData360();
checkAuthUsr360();
replaceMessage('');
if ($_POST['reflesh']) {
    $result = FDB :: select1(T_USER_MST, "*", "where " . C_ID . " = " . FDB :: escape($_SESSION['login'][C_ID]));
    setSessionLoginData360($result);
}

if ($_REQUEST['undo']) {
    $_POST['div1'] = $_REQUEST['div1']=null;
    $_POST['div2'] = $_REQUEST['div2']=null;
    $_POST['div3'] = $_REQUEST['div3']=null;
    $_POST['fb_name'] = $_REQUEST['fb_name']=null;
}

$smarty = new MreSmarty();
$smarty->assign('link', getMypageLink());
$smarty->assign('infomation', getMyPageInfomation());
$menu_ext = array(
    "self" => "####mypage_self####",
    "select" => "####mypage_select####",
    "admit" => "####mypage_admit####",
    "fb" => "####mypage_feedback####",
    "review" => "####mypage_review####"
);
$smarty->assign('sheet_name', $GLOBALS['_360_sheet_type'] + $menu_ext);
$smarty->assign('admin_flag', $_SESSION['login']['auth']);

$smarty->assign('menu', getMyPageMenu());
$smarty->display('360_menu.tpl');

exit;
/******************************************************************************************************/

class MyPage
{
    public $term;
    //▼▼----テンプレート終わり----▼▼
    /**
     * $type => 入力シートタイプ
     *
     */
    public function getMenuContents($sheet_type)
    {
        //typeから設定情報を取得
        $link = array ();
        foreach ($GLOBALS['_360_menu_type'] as $menu_type => $menu_name) {
            /* softbankカスタマイズ シートごとのメニューには他者回答のみ表示 */
            if ($menu_type != 'input')
                continue;

            $link = array_merge($link, $this->getContentsLinks($menu_type, $sheet_type));
        }
        //ここの動作を変えればサブタイトル挿入も可
        $menu = implode($this->getSeparator(), $link);

        //MYPAGE_MENU_MODE
        $menu= str_replace('####mypage_menu_mode_replace1####','<tr><td class="menu_type menu_input menu_type_user_1">####mypage_input9_title####</td></tr>',$menu);

        if ($menu) {
            return <<<__HTML__
<table class="menu_table">
{$menu}
</table>
<hr class="menu_hr">

__HTML__;
        }

        return "";
    }

    public function getSeparator()
    {
        if($GLOBALS['Setting']->menuModeIs1())

            return '';
        return '<tr><td class="separator"><img src="' . DIR_SRC . 'space.gif"></td></tr>' . "\n";
    }

    /**
     *
     * $menu_type 入力 閲覧 フィードバックなど
     * $type シートタイプ
     *
     */
    public function getContentsLinks($menu_type, $sheet_type, $only_self=false)
    {
        global $Setting;
        $array = array ();
        foreach ($GLOBALS['_360_user_type'] as $user_type => $user_type_name) {
            /* softbankカスタマイズ 入力は本人、他者分ける */
            if($menu_type == "input" && (($only_self && $user_type != 0) || (!$only_self && $user_type == 0)))
                continue;

            if($menu_type == "fb" && (($only_self && $user_type != 0) || (!$only_self && $user_type == 0)))
                continue;

            $l = $this->getContentsLink($user_type, $menu_type, $sheet_type);

            if ($Setting->menuModeIs1() && $l && !$GLOBALS['fflag'][$sheet_type] && $user_type<>0 && $menu_type == 'input') {
                $l =<<<HTML
####mypage_menu_mode_replace1####{$l}
HTML;
                $GLOBALS['fflag'][$sheet_type] = true;
            }

            if ($l && $user_type != 0 && $menu_type == 'input' && ($Setting->menuModeIsNot1() || $Setting->sheetModeNotCollect()))
                $l = $this->getFormTag($l, $user_type, $sheet_type);
            if ($l) {
                $array[] = $l;
            }
        }

        if (!$only_self && is_good($array) && $menu_type == 'input' && $Setting->menuModeIs1() && $Setting->sheetModeCollect())
            $array = array($this->getFormTag(implode("", $array), 1, $sheet_type));

        return $array;
    }
    public function getFormTag($body, $user_type, $sheet_type)
    {
        $hiddenSID = getHiddenSID();
        $diag = $sheet_type;
        $uid = $this->getSelfSerialNo();
        if ($GLOBALS['Setting']->multiAnswerModeValid()) {
            $multi_button=<<<HTML
<tr class="multi_answer_mode">
<td>
<input type="hidden" name="diagnosis" value="{$diag}">
{$hiddenSID}
<input type="hidden" name="answer_mode" value="lump">
<input type="hidden" name="position" value="{$user_type}">
<input type="hidden" name="self_id" value="{$uid}">
<input type="submit" value="####mypage_input_button####" class="multi_answer_mode">
</td>
</tr>
HTML;

        } else {
            $multi_button = '';
        }

        return<<<__HTML__
<form action="index.php" method="post">
{$body}
{$multi_button}
</form>

__HTML__;
    }
    public function getTargets($user_type, $sheet_type)
    {
        return $_SESSION['login'][$sheet_type][$user_type];
    }
    public function getContentsLink($user_type, $menu_type, $sheet_type)
    {
        if (!$this->isInTerm($user_type, $menu_type, $sheet_type) || !($targets = $this->getTargets($user_type, $sheet_type)))
            return;
        $result = "";
        foreach ($targets as $target) {
            if ($target === 'blank') {
                $result .= '<tr><td height="5"></td></tr>';
                continue;
            }
            $result .= $this->getMenuTr($user_type, $target, $menu_type);
        }
        if (!$result && $user_type != VIEWER_USER_TYPE)
            return "";
        if ($user_type == 0 && $menu_type == 'select')
            return $this->getSubject("####mypage_menu_select####", "select") . $result;

        if ($user_type == ADMIT_USER_TYPE && $menu_type == 'admit')
            return $result;

        //if (($menu_type == 'fb' || $menu_type == 'review') && is_void($result))
        //	return "";

        if ($menu_type == "fb") {
            if($user_type==0)

                return $this->getSubject('####mypage_feedback####', "fb") . $result;
            else
                return $this->getSubject('####mypage_feedback_refer####', "fb") . $result;
        }

        if ($menu_type == "review") {
            if($user_type==0)

                return $this->getSubject('####mypage_review####', "review") . $result;
            else
                return $this->getSubject('####mypage_review_refer####', "review") . $result;
        }

        if ($GLOBALS['Setting']->menuModeIs1() && $user_type != 0) {//上司などの区分をなくす
            if($result)
                $GLOBALS['replace_flag']=1;

            return $result;
        }

        return $this->getSubject("####mypage_input{$user_type}_title####", "input", $user_type) . $result;
    }

    public function getMenuTr($user_type, $target, $menu_type)
    {
        $targetLink = $this->getLinkByContents($user_type, $target, $menu_type);
        if (!$targetLink)
            return "";

        $link_tag = $this->createMenuLink($user_type, $menu_type, $target, $targetLink);

        return<<<__HTML__
<tr><td class="menu">{$link_tag}</td></tr>

__HTML__;
    }
    public function getSubject($sub, $menu_type="", $user_type="")
    {
        $class = "menu_type";
        $class = (is_good($menu_type))? $class." menu_".$menu_type : $class;
        $class = (is_good($user_type))? $class." menu_type_user_".$user_type : $class;

        return<<<__HTML__
<tr><td class="{$class}">{$sub}</td></tr>

__HTML__;
    }
    public function getLinkByContents($user_type, $target, $menu_type)
    {
        $selfSerial = $this->getSelfSerialNo();
        switch ($menu_type) {
            case "select" :
                $res["href"] = _360_getSelectURL($user_type, $selfSerial, $target);

                $res["status"] = _360_getSelectStatus($target);
                break;
            case "admit" :
                $res["href"] = _360_getAdmitURL($user_type, $selfSerial, $target);
                $res["status"] = _360_getSelectStatus($target);
                break;
            case "input" :
                $res["href"] = _360_getEnqueteURL($user_type, $selfSerial, $target['serial_no'], $target['sheet_type']);
                $res["status"] = _360_getEnqueteStatus($user_type, $selfSerial, $target['serial_no'], $target['sheet_type']);
                break;
            case "view" :
                $res["href"] = _360_getViewURL($user_type, $selfSerial, $target['serial_no'], $target['sheet_type']);
                break;
            case "review" :
                $res["href"] = _360_getReviewURL($user_type, $selfSerial, $target['serial_no'], $target['sheet_type']);
                break;
            case "fb" :
                if ($target['uid'] == $_SESSION['login']['uid']) {
                    if (is_fb_exist($target, $user_type))
                        $res["href"] = _360_getFBURL($user_type, $target);
                    break;
                } else {
                    if (is_fb_exist($target, $user_type)) {
                        $GLOBALS['fb_search_flag'] = true;
                        $cond = array ();
                        if ($_POST['fb_name'])
                            $cond['fb_name'] = ereg($_POST['fb_name'], $target['name']);
                        else
                            $cond['fb_name'] = 1;
                        if ($_POST['div1'] != 'default' && $_POST['div1'])
                            $cond['div1'] = ($_POST['div1'] == $target['div1']);
                        else
                            $cond['div1'] = 1;
                        if ($_POST['div2'] != 'default' && $_POST['div2'])
                            $cond['div2'] = ($_POST['div2'] == $target['div2']);
                        else
                            $cond['div2'] = 1;
                        if ($_POST['div3'] != 'default' && $_POST['div3'])
                            $cond['div3'] = ($_POST['div3'] == $target['div3']);
                        else
                            $cond['div3'] = 1;

                        if ($cond['fb_name'] && $cond['div1'] && $cond['div2'] && $cond['div3'])
                            $res["href"] = _360_getFBURL($user_type, $target);
                        break;
                    }
                }

                break;
            default :
                echo "getLinkByContents:不正なコンテンツ指定:" . $menu_type;
                exit;
        }

        return $res;
    }

    /**
     * @return string 自分のserial_noを取得
     */
    public function getSelfSerialNo()
    {
        return $_SESSION['login']['serial_no'];
    }

    public function sort_menu($type, $user_type, $target)
    {
        $a = array ();
        $b = array ();
        $c = array ();

        $rid = getRidByDinognosisAndUserType($type, $user_type);
        foreach ($target as $v) {

            $input_status = $_SESSION['login'][C_EVENT_LIST][$rid][$v['serial_no']];

            if ($v !== 'blank' && $input_status == 0) {
                $a[] = $v;
            } elseif ($v !== 'blank' && $input_status == 1) {
                $b[] = $v;
            } else {
                $c[] = $v;
            }

        }

        return array_merge($a, $b, $c);
    }

    public function sort_select_menu($type, $target)
    {

        $a = array ();
        $b = array ();
        $c = array ();
        foreach ($target as $v) {
            if ($v !== 'blank' && getSelectStatus($type, $v[C_SYAIN_CODE]) === '選択中') {
                $a[] = $v;
            } elseif ($v !== 'blank' && getSelectStatus($type, $v[C_SYAIN_CODE]) === '承認依頼中') {
                $b[] = $v;
            } else {
                $c[] = $v;
            }
        }

        return array_merge($a, $b, $c);
    }

    public function createMenuLink($user_type, $menu_type, $target, $targetLink)
    {
        $message = $this->getMenuMessage($user_type, $menu_type, $target);
        $status = $targetLink["status"] ? $targetLink["status"] : "";
        $href = $targetLink["href"];
        global $Setting;

        $class = "fb";

        if ($menu_type === 'input') {
            if ($status === '####status_finished####') {
                if($Setting->reopenTypeEqual(1))/* 印刷画面を表示 */
                    $href = str_replace('./', './print.php', $href);
                elseif($Setting->reopenTypeEqual(2))/* 再度開くのを禁止 */
                    unset($href);
                $class = 'input_1';
            } else {
                $class = 'input_2';
            }
        }

        if ($menu_type === 'input' && $user_type != '0') {
            if ($Setting->reopenTypeNotEqual(3) && $status === '####status_finished####')
                $head = FForm :: checkbox('', '', '', 'disabled="disabled" class="multi_answer_mode"');
            else
                $head = FForm :: checkbox('targets[]', $target['serial_no'] . ':' . _360_getEnqueteHash_LumpMode($this->getSelfSerialNo(), $target['serial_no']), '', ' class="multi_answer_mode"');
        }

        if($GLOBALS['Setting']->multiAnswerModeInvalid())
            $head = '';
        //elseif ($menu_type === 'fb' && $user_type != '0')
        //{
        //	$head = FForm::checkbox('targets[]', $target['serial_no'].':'._360_getEnqueteHash_LumpMode($this->getSelfSerialNo(),$target['serial_no']));
        //}

        $head = '<span class="check">'.$head.'</span>';

        if ($menu_type === 'select') {
            if ($status === $GLOBALS['_360_select_status'][0]) {
                $status = '####select_status1####';
                $class = 'select_1';
            } elseif ($status === $GLOBALS['_360_select_status'][1]) {
                $status = '####select_status2####';
                $class = 'select_2';
            } else {
                $status = '####select_status3####';
                $class = 'select_3';
            }
        }

        if ($menu_type === 'admit') {
            if ($status === $GLOBALS['_360_select_status'][0]) {
                $status = '####admit_status1####';
                $class = 'admit_1';
            } elseif ($status === $GLOBALS['_360_select_status'][1]) {
                $status = '####admit_status2####';
                $class = 'admit_2';
            } else {
                $status = '####admit_status3####';
                $class = 'admit_3';
            }
        }

        if($Setting->reopenTypeEqual(2) && !is_false(strpos($href, "print.php")))
            unset($href);

        $message = "<span class=\"menu_{$class}\">" . $message . "</span>";
        $status = ($status)? "<span class='icon_{$class}'>" . $status . "</span>":"";

        return $head . $status . _360_menu_link_tag($href, $message);
    }

    public function isInputable($target)
    {
        $i = $target['select_status'] ? $target['select_status'] : 0;

        return $i;
    }

    //期間を比較して期間内ならtrueを返す
    public function isInTerm($user_type, $menu_type, $sheet_type)
    {
        global $GLOBAL_TERM_DATA;

        if (!$GLOBAL_TERM_DATA)
            $GLOBAL_TERM_DATA = getTermData();

        $evid = $sheet_type * 100 + $user_type;

        $s = strtotime($GLOBAL_TERM_DATA[$evid][$menu_type]['s']);
        $e = strtotime($GLOBAL_TERM_DATA[$evid][$menu_type]['e']);
        $now = time();

        if (isset ($GLOBAL_TERM_DATA[$evid][$menu_type]) && isProxyInputUser()) {
            return true; //ただし代理入力時は無条件でtrue
        }

        if ($s < $now && $now < $e) {
            return true;
        }

        return false;
    }

    //プロパティ
    public function getTerm($key = "")
    {
        if (!$this->term) {
            $this->term = getTermData();
        }

        if ($key) {
            return $this->term[$key];
        }

        return $this->term;
    }
    public function getMenuMessage($user_type, $menu_type, $target = "", $option = "")
    {
        global $_360_user_type, $_360_menu_type;

//		if ($menu_type == 'fb' && $user_type == 0)
//		{
//			return getUserName($target) . $_360_menu_type[$menu_type];
//		}
        if ($menu_type == 'fb' || $menu_type == 'review') {
            return getUserName($target) . "####menu_fb_each####";
        }

        if ($menu_type == 'select') {
            //$name = $target['name'] . '####mypage_ones####'; //さんの
            //return $name . $_360_menu_type[$menu_type];
            return $_360_menu_type[$menu_type];

        }
        if ($menu_type == 'admit') {
            $name = '####mypage_ones0####' . getUserName($target) . '####mypage_ones####'; //さんの

            return $name . $_360_menu_type[$menu_type];
        }

        $name = "####mypage_input{$user_type}####";
        $name = replaceMessage($name);
        $name = str_replace('$target', getUserName($target), $name);

        return $name;
    }
}

class MyPageWithFB extends Mypage
{

    public function getFBMenu()
    {
        $link = array ();
        $all_targets = array();
        $self_link = $this->getContentsLinks('fb', $_SESSION['login']['sheet_type'], true);

        foreach ($GLOBALS['_360_sheet_type'] as $sheet_type => $sheet_name) {
            foreach ($GLOBALS['_360_user_type'] as $user_type => $user_type_name) {
                if (!$user_type)
                    continue;

                if (!$this->isInTerm($user_type, "fb", $sheet_type) || !($targets = $this->getTargets($user_type, $sheet_type)))
                    continue;

                $all_targets = array_merge($all_targets, $targets);
            }
        }

        $all_targets = $this->sort_FB($all_targets);

        foreach ($all_targets as $target) {
            if ($target === 'blank') {
                $link[] = '<tr><td height="5"></td></tr>';
                continue;
            }
            $link[] = $this->getMenuTr($user_type, $target, "fb");
        }
        //ここの動作を変えればサブタイトル挿入も可
        $link = array_filter($link);
        $menu = implode('', $link);
        if (is_good($menu))
            $menu = $this->getSubject('####mypage_feedback_refer####', " colspan=2") . $menu;

        /*
        if ($GLOBALS['fb_search_flag'])
            $menu = '<tr><td>' . $this->getFBSearchBox() . '</td></tr>' . $menu;
        */

        if ($self_link)
            $menu =  $self_link[0] . $menu;
//		$infomation = $this->getInfomationByType('mypage_feedback');
        $show = "";
        if ($menu) {
            $show =<<<__HTML__
<table class="menu_table">
{$menu}
</table>
<hr class="menu_hr">

__HTML__;
        }

        return $show;
    }

    public function getMenu($menu_type)
    {
        $link = array ();

        $self_flag = ($menu_type == "self")? true:false;
        $menu_type = ($menu_type == "self")? "input":$menu_type;

        foreach ($GLOBALS['_360_sheet_type'] as $sheet_type => $sheet_name) {
            $link = array_merge($link, $this->getContentsLinks($menu_type, $sheet_type, $self_flag));
        }

        $menu = implode($this->getSeparator(), $link);

        /*
        if ($menu_type == "select") {
            $admit_link = array();
            $menu_type = "admit";
            foreach ($GLOBALS['_360_sheet_type'] as $sheet_type => $sheet_name) {
                $admit_link = array_merge($admit_link, $this->getContentsLinks($menu_type, $sheet_type, $self_flag));
            }
            $menu .= (is_good($admit_link))? $this->getSubject("####mypage_menu_admit####"). implode("", $admit_link):"";
        }
        */

        if($menu_type == "admit" && is_good($menu))
            $menu = $this->getSubject("####mypage_menu_admit####") . $menu;

        $show = "";
        if ($menu) {
            $show =<<<__HTML__
<table class="menu_table">
{$menu}
</table>
<hr class="menu_hr">

__HTML__;
        }

        return $show;
    }

    public function sort_FB($targets)
    {
        usort($targets, array (
            $this,
            'sort_FB_callback'
        ));

        return $targets;
    }

    public function sort_FB_callback($a, $b)
    {
        $aaa = $a['div2_code'] . $a['div3_code'];
        $bbb = $b['div2_code'] . $b['div3_code'];
        if ($aaa == $bbb)
            return 0;
        return ($aaa < $bbb) ? -1 : 1;
    }
    public function getFBFormTag($body, $user_type, $type)
    {
        $hiddenSID = getHiddenSID();
        $diag = substr($type, -1, 1);
        $uid = $this->getSelfSerialNo();

        return<<<__HTML__
<form action="fb.php" method="post" target="_blank">
{$body}
<tr>
<td>
<input type="hidden" name="diagnosis" value="{$diag}">
{$hiddenSID}
<input type="hidden" name="answer_mode" value="lump">
<input type="hidden" name="position" value="{$user_type}">
<input type="hidden" name="self_id" value="{$uid}">
<!-- <input type="submit" value="####mypage_view_button####"> --></TD></TR>
</form>

__HTML__;
    }

    public function getFBSearchBox()
    {
        $uid = html_escape($_REQUEST['fb_uid']);
        $name = html_escape($_REQUEST['fb_name']);
        $PHP_SELF = getPHP_SELF() . '?' . SID;
        $html =<<<HTML

<button onclick = "searchbox_switch()" id="sbs_button">####mypage_fb_boxdisp####</button>
<div style="display:none" id="searchbox">
<form action="{$PHP_SELF}#fbsearch" method="post">
<table>
<tr><td><a name="fbsearch">####mypage_fb_name####</a></td><td><input name="fb_name" value="{$name}"></td></tr>

HTML;
        foreach (array (
                'div1',
                'div2',
                'div3'
            ) as $key)
        {
            $div = array (
                'default' => '-'
            );
            foreach (getDivList($key, getMyLanguage()) as $k => $v) {
                $div[$k] = $v;
            }
            if ($key == 'div1')
                $html .= '<tr><td>####div_name_1####</td><td>' .
                FForm :: replaceSelected(FForm :: select($key, $div, "style='width:230px' onChange='reduce_options(\"id_div1\",\"id_div2\");reduce_options(\"id_div2\",\"id_div3\");' id='id_div1'"), $_REQUEST['div1']) . "</td></tr>";
            if ($key == 'div2')
                $html .= '<tr><td>####div_name_2####</td><td>' .
                FForm :: replaceSelected(FForm :: select($key, $div, "style='width:230px' onChange='reduce_options(\"id_div2\",\"id_div3\");' id='id_div2'"), $_REQUEST['div2']) . "</td></tr>";
            if ($key == 'div3')
                $html .= '<tr><td>####div_name_3####</td><td>' . FForm :: replaceSelected(FForm :: select($key, $div, "style='width:230px' id='id_div3'"), $_REQUEST['div3']) . "</td></tr>";
        }

        if (($_REQUEST['div1'] && $_REQUEST['div1'] != 'default') || $_REQUEST['fb_name'])
            $UNDO_BUTTON =<<<HTML
<input type="submit" name="undo" value="####mypage_fb_submit2####">

HTML;

        $html .=<<<HTML
<tr><td align="center"><input type="submit" value="####mypage_fb_submit####">
{$UNDO_BUTTON}
</td></tr>
</table>
</form>
</div>

<script>
function searchbox_switch()
{
    if(document.getElementById('searchbox').style.display=='none')
        document.getElementById('searchbox').style.display = 'block';
    else
        document.getElementById('searchbox').style.display = 'none';
    document.getElementById('sbs_button').style.display = 'none';

}
if(window.location.hash=='#fbsearch')
    searchbox_switch();
</script>

HTML;
        $html .= getHtmlReduceSelect();

        return $html;
    }
}
function getInfomationByType($type = '')
{
    $infomation = getMessage("mypage{$type}_infomation");
    $infomation = preg_replace('|<!--to([^-]+)-->|e', "dateCal('\\1')", $infomation);

    return $infomation;
}

function getMyPageLink()
{
    $link = array();
    $link['logout'] = DIR_ROOT . '360_login.php?mode=logout&' . getSID();
    $link['news'] = DIR_ROOT . '360_news.php?' . getSID();
    $link['refresh'] = getPHP_SELF() . '?' . getSID();

    return $link;
}

function getMyPageMenu()
{
    $mypage = new MyPageWithFB();
    $menu = array();
    foreach ($GLOBALS['_360_sheet_type'] as $k => $v) {
        $menu[$k] = $mypage->getMenuContents($k);
    }
    $menu['fb'] = $mypage->getFBMenu();
    $menu['self'] = $mypage->getMenu("self");
    $menu['admit'] = $mypage->getMenu("admit");
    $menu['select'] = $mypage->getMenu("select");
    $menu['review'] = $mypage->getMenu("review");

    return $menu;
}

function getMyPageInfomation()
{
    $infomation = array();
    $infomation[0] = getInfomationByType('');
    foreach ($GLOBALS['_360_sheet_type'] as $k => $v) {
        $infomation[$k] = getInfomationByType($k);
    }

    return $infomation;
}
