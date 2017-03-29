<?php
require_once (DIR_LIB.'CbaseFDB.php');
require_once (DIR_LIB.'360_FHtml.php');
require_once (DIR_LIB.'CbaseFunction.php');
require_once (DIR_LIB.'CbaseFCrypt.php');
require_once (DIR_LIB.'CbaseFFile2.php');
require_once (DIR_LIB.'AccessLogDAO.php');

function getMailSender($user)
{
    if (!$GLOBALS['DEFINED_CONST'])
        $GLOBALS['DEFINED_CONST'] = get_defined_constants();

    $lang_type = (is_good($user['lang_type']))? $user['lang_type']:0;

    return array (
        $GLOBALS['DEFINED_CONST']['MAIL_SENDERNAME' . $lang_type],
        $GLOBALS['DEFINED_CONST']['MAIL_SENDER' . $lang_type]
    );
}

function get360RandomPw($id=null)
{
    $password = getRandomPassword();
    while(is_good(get360PwError($password, $id)))
        $password = getRandomPassword();

    return $password;
}

function get360PwError($password, $id=null)
{
    if(is_good($id) && $id == $password)

        return '####pw_error_1####';//IDと同じパスワードは設定できません
    if($GLOBALS['Setting']->pwLengthGreater($password))

        return str_replace("***", DEFAULT_PW_LENGTH, getMessage('pw_error_2'));//パスワードは***桁以上でお願いします。

//	if(strlen($password)>20)
//		return 'パスワードは20桁以下でお願い致します';

    if(!ereg('^[0-9a-zA-Z]+$', $password))

        return '####pw_error_3####';

    if(ereg('^[0-9]+$', $password))

        return '####pw_error_3####';

    if(ereg('^[a-zA-Z]+$', $password))

        return '####pw_error_3####';

    return null;
}

function getPwHash($password)
{
    $pass = "";
    if (isReversiblePw()) {
        $pass = encrypt($password);
    } else {
        $pass = sha1(SYSTEM_RANDOM_STRING.sha1($password.SYSTEM_RANDOM_STRING));
    }

    return $pass;
}

function validPwHash($password, $hash)
{
    $pass = "";
    if (isReversiblePw()) {
        $pass = encrypt($password);
    } else {
        $pass = sha1(SYSTEM_RANDOM_STRING.sha1($password.SYSTEM_RANDOM_STRING));
    }

    if($pass == $hash)

        return true;
    return false;
}

function getDisplayPw($password)
{
    if (isReversiblePw()) {
        return decrypt($password);
    }

    return false;
}

function isReversiblePw()
{
    return $GLOBALS['Setting']->reversiblePw();
}

function getUserName($user)
{
    if ($user['name_'] && $GLOBALS['Setting']->nameModeIs1()) {
        return html_escape($user['name'] . '(' . $user['name_'] . ')');

    } else {
        $lang_type = (int) $_SESSION['login']['lang_type'];
        if($lang_type != 0)

            return html_escape($user['name_']);
        else
            return html_escape($user['name']);
    }
}
function getUserName_($user)
{
    if ($user['name_']) {
        return html_escape($user['name']) . '<br>(' . html_escape($user['name_']) . ')<br>';

    } else {
        return html_escape($user['name'])."<br>";
    }
}

function getUserDiv($user, $lang="0")
{
    $div = array();
    if(is_good(getDiv1NameById($user['div1'], $lang))) $div[] = getDiv1NameById($user['div1'], $lang);
    if(is_good(getDiv2NameById($user['div2'], $lang))) $div[] = getDiv2NameById($user['div2'], $lang);
    if(is_good(getDiv3NameById($user['div3'], $lang))) $div[] = getDiv3NameById($user['div3'], $lang);

    return implode(" ", $div);
}

function getHtmlReduceSelect($all_flag = false)
{
    $children['default']['default'] = 'default';
    if ($all_flag)
        $divlist = getDivListAll();
    else
        $divlist = getDivList();
    foreach ($divlist as $div) {

        if (!$children[$div['div1']])
            $children[$div['div1']]['default'] = 'default';
        if (!$children[$div['div2']])
            $children[$div['div2']]['default'] = 'default';
        if ($div['div1'] === "" || $div['div2'] === "" || $div['div3'] === "")
            continue;
        if (!in_array($div['div2'], $children[$div['div1']]))
            $children[$div['div1']][$div['div2']] = $div['div2'];

        if (!in_array($div['div3'], $children[$div['div2']]))
            $children[$div['div2']][$div['div3']] = $div['div3'];
    }
    $jsonObj = json_encode($children);

    return<<<HTML
<script>
function reduce_options(id1,id2)
{
    if (!document.getElementById(id1) || !document.getElementById(id1).options.length) {
        return;
    }
    var relation ={$jsonObj};
    if (!this.options_backup) {
        this.options_backup = new Object();
    }
    if (!this.options_backup[id2]) {
        this.options_backup[id2] = document.getElementById(id2).cloneNode(true).options;
    }

    var id2_options = document.getElementById(id2).options;
    var id2_options_default = this.options_backup[id2];
    var id1_selected = document.getElementById(id1).options[document.getElementById(id1).selectedIndex].value;
    var id2_selected = document.getElementById(id2).options[document.getElementById(id2).selectedIndex].value;

    id2_options.length = 0;
    for (i=0;id2_options_default.length>i;i++) {
        if (typeof relation[id1_selected][id2_options_default[i].value] != 'undefined') {
            if(id2_selected == id2_options_default[i].value)
                selected = "selected";
            else
                selected = null;
            id2_options[id2_options.length] = new Option(id2_options_default[i].text,id2_options_default[i].value,null,selected);
        }
    }
}
function onload__()
{
    if (typeof other_onload__ == 'function') {
        other_onload__();
    }
    reduce_options('id_div1','id_div2');
    reduce_options('id_div2','id_div3');
}

window.onload= onload__;

</script>
HTML;
}

function getInputLink($evid, $serial_no, $target)
{
    return "";

}

function getEvidBySheetTypeAndUserType($sheet_type, $user_type)
{
    return $sheet_type * 100 + $user_type;
}

function getSheetTypeByEvid($evid)
{
    return (int) ($evid / 100);
}

function getUserTypeByEvid($evid)
{
    return $evid % 100;
}

function getMenuName($id)
{
    global $_360_menu_type;

    return $_360_menu_type[$id];
}

function getUserTypeName($id)
{
    global $_360_user_type;

    return $_360_user_type[$id];
}

function getSelectStatusName($id)
{
    global $_360_select_status;

    return $_360_select_status[$id];
}

function clearSessionExceptForLoginData360()
{
    $tmp = $_SESSION['login'];
    $_SESSION = array ();
    $_SESSION['login'] = $tmp;
}

function checkAuthUsr360()
{
    if ($_SESSION['login']['DIR_MAIN'] != DIR_MAIN) {
        _360_error(0);
    }
    AccessLog::writeLogActionFront();
}
/**
 * @param $message int メッセージID
 * @param $back マイページの戻れるかどうか
 */
function _360_error($message, $back = 0)
{
    header('Location: ' . DIR_ROOT . '360_error.php?message=' . $message . '&back=' . $back . '&' . getSID());
    exit;
}

function getTermData()
{
    global $GLOBAL_getTermData;
    if(is_array($GLOBAL_getTermData)) return $GLOBAL_getTermData;

    /* 設定されている期間を取得 */
    $datas = array ();
    foreach (getFromtoArray() as $term_data) {
        $datas[$term_data['evid']][$term_data['type']]['s'] = date('Y/m/d H:i:s', strtotime($term_data['sdate']));
        $datas[$term_data['evid']][$term_data['type']]['e'] = date('Y/m/d H:i:s', strtotime($term_data['edate']));
    }

    /* シートの方が多い場合があるので当てはめていく */
    $events = array();
    foreach (getAllEventArray() as $event) {
        $events[$event['evid']][$event['type']]['s'] = $datas[$event['evid']][$event['type']]['s'];
        $events[$event['evid']][$event['type']]['e'] = $datas[$event['evid']][$event['type']]['e'];
    }

    return $GLOBAL_getTermData = $events;
}

/**
 * ユーザテーブルのselect結果を受け取り、セッションにログイン情報を書き込む
 */
function setSessionLoginData360($data)
{
    //--
    //代理入力などの管理情報があれば、持ち越すようにする
    if ($_SESSION['login']['auth'])
        $auth_info = $_SESSION['login']['auth'];
    //--

    if (!$data['mflag'])
        unset ($data['sheet_type']);
    $_SESSION['login'] = array ();
		unset($data['pw']);
    $_SESSION['login'] = $data; //本人

    //言語情報をセット
    if(is_good($data['lang_type']))
        setcookie('lang360', $data['lang_type']);

    //本人の所属名称をセット
    $_SESSION['login']['div1_name'] = ($_SESSION['login']['div1'])? getDiv1NameById($_SESSION['login']['div1']):"";
    $_SESSION['login']['div2_name'] = ($_SESSION['login']['div2'])? getDiv2NameById($_SESSION['login']['div2']):"";
    $_SESSION['login']['div3_name'] = ($_SESSION['login']['div3'])? getDiv3NameById($_SESSION['login']['div3']):"";

    //評価すべきユーザ一覧
    if ($data['mflag'])
        $_SESSION['login'][$data['sheet_type']][0][] = $data; //対象者だった場合

    $T_USER_MST = T_USER_MST;
    $T_USER_RELATION = T_USER_RELATION;
    $escaped_uid = FDB :: escape($data['uid']);
    $users = FDB :: getAssoc("select b.*,a.user_type from {$T_USER_RELATION} a left join {$T_USER_MST} b on a.uid_a = b.uid where a.uid_b = {$escaped_uid} ORDER BY b.uid");
    foreach ($users as $user) {
        $_SESSION['login'][$user['sheet_type']][$user['user_type']][] = $user;
    }

    //評価シートの回答状況を入れておく
    $event_datas = FDB :: select(T_EVENT_DATA, "(".FDB::concat(array("'rid'", "trim(lpad(evid, 5, '0'))")).") as rid,target,answer_state", 'where serial_no =' . FDB :: escape($_SESSION['login']['serial_no']));
    foreach ($event_datas as $event_data) {
        $rid = $event_data['rid'];
        $target = $event_data['target'];
        $status = $event_data['answer_state'] ? 1 : 2;
        $_SESSION['login'][C_EVENT_LIST][$rid][$target] = $status;
    }

    $_SESSION['login']['DIR_MAIN'] = DIR_MAIN; //権限チェック用


    //--
    if ($auth_info)
        $_SESSION['login']['auth'] = $auth_info;
    //--
}

/**
 * セッションから自分の情報を取り出す
 */
function getSelfData()
{
    $data = $_SESSION['login'];

    foreach ($GLOBALS['_360_sheet_type'] as $k => $v) {
        unset ($data[$k]);
    }

    return $data;
}

/**
 * 承認者の情報を取り出す
 */
function getAdmitData()
{
    global $GDF;

    if ($GLOBALS['ADMIT_CACHE_FLAG'])
        return $GLOBALS['ADMIT_CACHE'];
    $GLOBALS['ADMIT_CACHE_FLAG'] = true;
    $T_USER_RELATION = T_USER_RELATION;
    $escaped_uid = FDB :: escape($_SESSION['login']['uid']);

    return $GLOBALS['ADMIT_CACHE'] = FDB :: select1(T_USER_MST, '*', "where uid = (select uid_b from {$T_USER_RELATION} where uid_a = {$escaped_uid} and user_type = {$GDF->get('ADMIT_USER_TYPE')} limit 1)");
}

function getUserByuid($uid)
{
    $escaped_uid = FDB :: escape($uid);

    return FDB :: select1(T_USER_MST, '*', "where uid = {$escaped_uid}");
}

function getTargetUserCount()
{
    $T_USER_MST = T_USER_MST;
    $user = FDB::getAssoc("select count(serial_no) as count from {$T_USER_MST} where mflag = 1 and test_flag != 1;");
    return $user[0]['count'];
}

/**
 *
 */
function _360_resolveQueryString($q)
{
    $q_ = $q = ereg_replace('^q=', '', $q);

    //未実装です。誰か実装してください。
    $return = array ();
    $hash = substr($q, 0, 4);
    $q = substr($q, 4);
    $sa["type"] = 1;
    $sa["flg"] = 1;
    $user_type = substr($q, 0, 1);
    $self = substr($q, 1, 8);
    $target = substr($q, 9, 8);
    $dinognosis = substr($q, 17, 10);
    $sa["rid"] = getRidByDinognosisAndUserType($dinognosis, $user_type);
    $sa["uid"] = $self;
    $sa["target"] = $target;

    if ($hash != substr(md5($user_type . $self . $target . $dinognosis), 0, 4) || (!MODE_PRINT && $_GET['p'] != substr(md5($hash), 0, 4))) {
        $sa = Resolve_QueryString($q_);
        if (!$sa)
            _360_error(1);
    }

    return $sa;
}

function _360_getSloginURL($user)
{

    $id = $user['uid'];
    $pw = $user['pw'];
    $hash = substr(getHash360($id,$pw),0,4);
    $q = urlencode(encrypt("{$id}/{$pw}/{$hash}"));

    return LOGIN_URL_S.'?q='.$q.'a';
}

function _360_getSelectURL($user_type, $self, $target)
{
    $hash = getHash360($target['serial_no']);

    return "360_user_relation_view_u.php?serial_no={$target['serial_no']}&hash={$hash}&" . getSID();
}

function _360_getAdmitURL($user_type, $self, $target)
{
    $hash = getHash360($target['serial_no']);

    /* 承認者が選定するならば回答者選定URLを返す */
    if($GLOBALS['Setting']->adminModeEqual(3))

        return "360_user_relation_view_u.php?serial_no={$target['serial_no']}&hash={$hash}&" . getSID();

    if ($target['select_status'] == 0)
        return false;

    return "360_admit_reply_user.php?serial_no={$target['serial_no']}&hash={$hash}&" . getSID();
}

function _360_getSelectStatus($target)
{
    return $GLOBALS['_360_select_status'][$target['select_status']];
}

/**
 * 回答用URL取得
 * @param string $user_type L:リーダ回答 B:上司（上位者）回答 M:メンバ回答
 * @param string $self 回答者シリアル
 * @param string $target 被回答者シリアル
 * @param string $dinognosis 被回答者の回答パターン
 * @return string URL
 */
function _360_getEnqueteURL($user_type, $self, $target, $dinognosis)
{
    $hash = substr(md5($user_type . $self . $target . $dinognosis), 0, 4);
    $hash2 = substr(md5($hash), 0, 4);

    return './?q=' . $hash . $user_type . $self . $target . $dinognosis . '&' . getSID() . '&p=' . $hash2;
}
/**
 * アンケートの状態取得
 * @param string $user_type L:リーダ回答 B:上司（上位者）回答 M:メンバ回答
 * @param string $self 回答者シリアル
 * @param string $target 被回答者シリアル
 * @param string $dinognosis 被回答者の回答パターン
 * @return string 未入力 or 一時保存中 or 送信済み
 */
function _360_getEnqueteStatus($user_type, $self, $target, $dinognosis)
{

    $rid = getRidByDinognosisAndUserType($dinognosis, $user_type);
    switch ($_SESSION['login'][C_EVENT_LIST][$rid][$target]) {
        case '1' :
            return '####status_unfinished_####';
        case '2' :
            return '####status_finished####';
        default :
            return '####status_unfinished####';
    }
}

function getUserTypeByRid($rid)
{
    return substr($rid, -1, 1);
}

function getRidByEvid($evid)
{
    return "rid".sprintf("%05d", $evid);
}

function getEvidByRid($rid)
{
    return (int) str_replace('rid', '', $rid);
}

function getTypeByEvid($evid)
{
    return substr($evid, 0, 1);
}

function adjustEvidByUserType($evid, $user_type)
{
    return floor($evid/100)*100 + $user_type;
}

function adjustSeidByUserType($seid, $user_type)
{
    return (floor($seid/100000)*100000 + $user_type*1000) + $seid%1000;
}

function _360_past_fb_link($uid, $name)
{
    $link = array ();
    if (is_file("2006_lfb_result/{$uid}.html")) {
        $SID = getSID();
        $link[] = "<a href='view_past_fb.php?year=2006&{$SID}'>2006年度フィードバック</a>";

    }

    return $link;
}

/**
 * ターゲット配列を名前リストにする
 */
function _360_target2NameList($targets)
{
    //name,postname,uidの配列が入ってくる
    usort($targets, "_360_callbackSortTarget");
    $post1 = array ();
    $post2 = array ();
    foreach ($targets as $val) {
        if ($val["post"] == "全社員版") {
            $post1[] = $val;
        } else {
            $post2[] = $val;
        }
    }
    $result = "";
    foreach ($post1 as $v) {
        $result .= $v["name"] . "さん\n";
    }
    $result .= "\n";
    foreach ($post2 as $v) {
        $result .= $v["name"] . "さん\n";
    }

    return $result;
}

function _360_callbackSortTarget($a, $b)
{
    return ($a["uid"] <= $b["uid"]) ? -1 : 1;
}

/**
 * イベントリスト(回答状況)をセットする
 * @param string $self 回答者シリアル
 * @param string $target 被回答者シリアル
 * @param string $rid Rid
 * @param int $number 0->クリア 途中保存->1 送信済み->2
 * 注意：すでに2の状態で途中保存しても1には戻らない
 * 注意：いつでも0にすることはできる
 */
function _360_setEventList($self, $target, $rid, $number)
{

    if ($number == 1 && $_SESSION['login'][C_EVENT_LIST][$rid][$target] == 2) {
        return; //既に送信済みなら、途中保存状態にはしない
    }
    if ($number == $_SESSION['login'][C_EVENT_LIST][$rid][$target]) {
        return; //状態が同じなら更新しない。
    }
    $_SESSION['login'][C_EVENT_LIST][$rid][$target] = $number;
}

/**
 * 対象と自分のserial_noを設定し、複数対象用のハッシュを取得する
 */
function _360_getEnqueteHash_LumpMode($self, $target)
{
    return substr(sha1($self . $target . 'fouawgel'), 0, 12);
}

function _360_menu_link_tag($href, $message)
{
    if (is_void($href))
        return "$message";

    return<<<HTML
<a href="{$href}">{$message}</a>
HTML;

}

/**
 * 閲覧用URL取得
 * @param string $user_type L:リーダ回答 B:上司（上位者）回答 M:メンバ回答
 * @param string $self 回答者シリアル
 * @param string $target 被回答者シリアル
 * @param string $dinognosis 被回答者の回答パターン
 * @return string URL
 */
function _360_getViewURL($user_type, $self, $target, $dinognosis)
{
    $hash = substr(md5($user_type . $self . $target . $dinognosis), 0, 4);

    return 'print.php?q=' . $hash . $user_type . $self . $target . $dinognosis . '&' . getSID();
}
/**
 * WebFB用URL取得
 * @param string $user_type
 * @param string $self 回答者シリアル
 * @param string $target 被回答者シリアル
 * @param string $dinognosis 被回答者の回答パターン
 * @return string URL
 */
function _360_getReviewURL($user_type=0, $self, $target, $dinognosis)
{
    if($user_type==VIEWER_USER_TYPE){
        $user_type = 0;
    }
    $hash = substr(md5($user_type . $self . $target . $dinognosis), 0, 4);

    return 'review.php?q=' . $hash . $user_type . $self . $target . $dinognosis . '&' . getSID();
}
/**
 * FB用URL取得
 * @param string $user_type L:リーダ回答 B:上司（上位者）回答 M:メンバ回答
 * @param string $self 回答者シリアル
 * @param string $target 被回答者シリアル
 * @param string $dinognosis 被回答者の回答パターン
 * @return string URL
 */
function _360_getFBURL($user_type, $user)
{
    $file = $user['uid'];
    $hash = substr(md5($file . "tawedklfahcklaop"), 0, 8);

    return 'fb.php?file=' . $file . '&hash=' . $hash . '&' . getSID();
}

/**
 * 該当社員のFBが存在しているかどうか
 * @return bool FBが存在している場合はtrue
 */
function is_fb_exist($target, $user_type)
{
    return is_file(DIR_FB . $target['uid'] . '.pdf');
}

/**
 * シートタイプとユーザタイプからridを得る
 * @return string Rid
 */
function getRidByDinognosisAndUserType($sheet_type, $user_type)
{
    return "rid00{$sheet_type}0{$user_type}";
}

function deleteUser($serial_no)
{
    FDB :: begin();
    $p = new CbasePageToast();
    if (getDivWhere())
        $divwhere = ' and ' . getDivWhere();

    $user = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($serial_no) . $divwhere);
    if (!$user) {
        $p->addErrorMessage("失敗しました(ユーザが見つかりません)");

        return $p->getErrorMessage();
    }

    FDB :: delete(T_USER_MST, 'where serial_no = ' . FDB :: escape($serial_no));
    $p->addErrorMessage("ID:{$user['uid']}のユーザを削除しました");
    //回答　関連なども消す
    {
        $where = 'where serial_no = ' . FDB :: escape($serial_no) . ' or target = ' . FDB :: escape($serial_no);
        FDB :: sql("delete from subevent_data where event_data_id in (select event_data_id from event_data {$where})",true);
        FDB :: delete(T_EVENT_DATA, $where);
        FDB :: delete(T_BACKUP_DATA, $where);
        $where = 'where uid_a = ' . FDB :: escape($user['uid']) . ' or uid_b = ' . FDB :: escape($user['uid']);
        FDB :: delete(T_USER_RELATION, $where);
    }
    FDB :: commit();
    if (LOG_MODE_PHP >= 1) {
        error_log(date('Y-m-d H:i:s') . "\t" . $_SERVER['REMOTE_ADDR'] . "\t" . $_SESSION['muid'] . "\t" . $_SERVER['SCRIPT_NAME'] . "\tdelete=>{$user['uid']}" . "\n", 3, LOG_FILE_PHP);
    }

    return $p->getErrorMessage();
}

/**
 * ユーザマスタを編集する権限があるかどうか
 */
function hasAuthUserEdit()
{
    //TODO:実装
    return true;
}

function setDivAuth($muid)
{
    $_SESSION['div_auth'] = FDB :: select(T_AUTH_SET_DIV, '*', 'where muid=' . FDB :: escape($muid));
}

/**
 * 権限によってユーザの所属を縛る
 */
function getDivWhere($table = '')
{
    if ($_SESSION['login']['uid']) { //ユーザの場合

        return "(1=1{$GLOBALS['NOT_DISP_DIV']})";
    }

    if ($table)
        $table = $table . '.';

    $array_auth = array ();
    foreach ($_SESSION['div_auth'] as $div_auth) {
        if ($div_auth['div1'] == '*') {
            $array_auth[] = '(1=1)';
            break;
        }
        if ($div_auth['div2'] == '*') {
            $array_auth[] = "({$table}div1 = '{$div_auth['div1']}')";
            continue;
        }
        if ($div_auth['div3'] == '*') {
            $array_auth[] = "({$table}div1 = '{$div_auth['div1']}' and {$table}div2 = '{$div_auth['div2']}')";
            continue;
        }
        $array_auth[] = "({$table}div1 = '{$div_auth['div1']}' and {$table}div2 = '{$div_auth['div2']}' and {$table}div3 = '{$div_auth['div3']}')";
    }
    if (!count($array_auth))
        return "(1=0)"; //ありえない条件を返す

    return "(" . implode(' or ', $array_auth) . ")";
}

function getHash360($t)
{
    return md5($t . SYSTEM_RANDOM_STRING);
}

function getSheetList()
{
    //ファイルキャッシュする
    if (!file_exists(FILE_SHEET_CACHE)) {
        $GLOBAL_SHEET_LIST = FDB::select(T_EVENT,"DISTINCT(FLOOR(evid/100)) as sheet_type");
        if(!is_false($GLOBAL_SHEET_LIST))
            file_put_contents(FILE_SHEET_CACHE, serialize($GLOBAL_SHEET_LIST));
    } else {
        $GLOBAL_SHEET_LIST = unserialize(file_get_contents(FILE_SHEET_CACHE));
    }

    return $GLOBAL_SHEET_LIST;
}

function clearSheetCache()
{
    s_unlink(FILE_SHEET_CACHE);
}

function getUserTypes()
{
    //ファイルキャッシュする
    if (!file_exists(FILE_USER_TYPE_CACHE)) {
        $GLOBAL_USER_TYPES = FDB::select(T_USER_TYPE, "*", "ORDER BY user_type_id");
        if(!is_false($GLOBAL_USER_TYPES))
            file_put_contents(FILE_USER_TYPE_CACHE, serialize($GLOBAL_USER_TYPES));
    } else {
        $GLOBAL_USER_TYPES = unserialize(file_get_contents(FILE_USER_TYPE_CACHE));
    }

    return $GLOBAL_USER_TYPES;
}

function clearUserTypeCache()
{
    s_unlink(FILE_USER_TYPE_CACHE);
}

function getSetting()
{
    //ファイルキャッシュする
    if (!file_exists(FILE_SETTING_CACHE)) {
        $GLOBAL_SETTINGS = FDB::select(T_SETTING, "*", "ORDER BY setting_id");
        if(!is_false($GLOBAL_SETTINGS))
            file_put_contents(FILE_SETTING_CACHE, serialize($GLOBAL_SETTINGS));
    } else {
        $GLOBAL_SETTINGS = unserialize(file_get_contents(FILE_SETTING_CACHE));
    }

    return $GLOBAL_SETTINGS;
}

function clearSettingCache()
{
    s_unlink(FILE_SETTING_CACHE);
}

function getFromtoArray()
{
    //ファイルキャッシュする
    if (!file_exists(FILE_FROMTO_CACHE)) {
        $GLOBAL_FROMTO_ARRAY = FDB::select(T_FROMTO, "*");
        if(!is_false($GLOBAL_FROMTO_ARRAY))
            file_put_contents(FILE_FROMTO_CACHE, serialize($GLOBAL_FROMTO_ARRAY));
    } else {
        $GLOBAL_FROMTO_ARRAY = unserialize(file_get_contents(FILE_FROMTO_CACHE));
    }

    return $GLOBAL_FROMTO_ARRAY;
}

function clearFromtoCache()
{
    s_unlink(FILE_FROMTO_CACHE);
}

function getSeid2NumArray()
{
    static $global_seid2num;
    if(is_good($global_seid2num)) return $global_seid2num;

    $flag = false;
    foreach (FDB::getAssoc("select evid,seid,(select count(*) as count from subevent b where a.evid = b.evid and a.seid > b.seid and b.type2 <> 'n' )+1 as new_num,num from subevent a where a.type2 <> 'n' order by a.seid;") as $subevent) {
        if($subevent['num'])
            $flag = true;
        $seid2num[$subevent['seid']] = $subevent['num'];
        $seid2num_[$subevent['seid']] = $subevent['new_num'];

        if ($GLOBALS['Setting']->sheetModeCollect() && $subevent['evid']%100 == 1) {
            foreach (range(2,INPUTER_COUNT) as $i) {
                $_seid = round($subevent['evid']/100)*100000+$i*1000+round($subevent['seid']%1000);
                $seid2num[$_seid] = $subevent['num'];
                $seid2num_[$_seid] = $subevent['new_num'];
            }
        }
    }
    $global_seid2num = $flag ? $seid2num:$seid2num_;

    return $global_seid2num;
}

function getSecularUsesStatuses()
{
    $list = array(
        SECULAR_USES_STATUS_UNUSED   => '未使用',
        SECULAR_USES_STATUS_CREATED  => 'データ作成済み',
        SECULAR_USES_STATUS_IMPORTED => 'データインポート済み',
        SECULAR_USES_STATUS_DISPOSAL => '-',
    );
    return $list;
}

function getSecularUsesTypes()
{
    $list = array(
        SECULAR_USES_TYPE_DUMP   => 'DBダンプ',
        SECULAR_USES_TYPE_RAW  => 'RAWデータ',
    );
    return $list;
}

function getDivList($level = '', $lang="0")
{
    global $GLOBAL_DIV_LIST, $GLOBAL_DIV1_LIST, $GLOBAL_DIV2_LIST, $GLOBAL_DIV3_LIST;
    if (!is_array($GLOBAL_DIV_LIST[$lang])) {
        $GLOBAL_DIV_LIST[$lang] = array ();
        $GLOBAL_DIV1_LIST[$lang] = array();
        $GLOBAL_DIV2_LIST[$lang] = array();
        $GLOBAL_DIV3_LIST[$lang] = array();
        $where = getDivWhere();
        if ($where)
            $where = 'where ' . $where;
        $GLOBAL_DIV_LIST[$lang] = FDB :: select(T_DIV, '*', $where.' ORDER BY div1_sort,div2_sort,div3_sort');
        foreach ($GLOBAL_DIV_LIST[$lang] as $div) {
            switch ($lang) {
                case "0":
                    $GLOBAL_DIV1_LIST[$lang][$div['div1']] = $div['div1_name'];
                    $GLOBAL_DIV2_LIST[$lang][$div['div2']] = $div['div2_name'];
                    $GLOBAL_DIV3_LIST[$lang][$div['div3']] = $div['div3_name'];
                    break;
                default:
                    if (is_good($div['div1_name_'.$lang])) {
                        $GLOBAL_DIV1_LIST[$lang][$div['div1']] = $div['div1_name_'.$lang];
                        $GLOBAL_DIV2_LIST[$lang][$div['div2']] = $div['div2_name_'.$lang];
                        $GLOBAL_DIV3_LIST[$lang][$div['div3']] = $div['div3_name_'.$lang];
        }
                    break;
            }
        }
    }

    switch ($level) {
        case 'div1' :
            return $GLOBAL_DIV1_LIST[$lang];
        case 'div2' :
            return $GLOBAL_DIV2_LIST[$lang];
        case 'div3' :
            return $GLOBAL_DIV3_LIST[$lang];
        default :
            return $GLOBAL_DIV_LIST[$lang];
    }
}

/**
 * 権限に縛られない
 */
function getDivListAll($level = '', $lang="0")
{
    global $GLOBAL_DIV_LIST_ALL, $GLOBAL_DIV1_LIST_ALL, $GLOBAL_DIV2_LIST_ALL, $GLOBAL_DIV3_LIST_ALL;
    if (!is_array($GLOBAL_DIV_LIST_ALL[$lang])) {
        $GLOBAL_DIV_LIST_ALL[$lang]  = array();
        $GLOBAL_DIV1_LIST_ALL[$lang] = array();
        $GLOBAL_DIV2_LIST_ALL[$lang] = array();
        $GLOBAL_DIV3_LIST_ALL[$lang] = array();
        //ファイルキャッシュする
        if (!file_exists(FILE_DIV_CACHE)) {
            $GLOBAL_DIV_LIST_ALL[$lang] = FDB :: select(T_DIV, '*');
            file_put_contents(FILE_DIV_CACHE,serialize($GLOBAL_DIV_LIST_ALL[$lang]));
        } else {
            $GLOBAL_DIV_LIST_ALL[$lang] = unserialize(file_get_contents(FILE_DIV_CACHE));
        }
        foreach ($GLOBAL_DIV_LIST_ALL[$lang] as $div) {
            switch ($lang) {
                case "0":
                    $GLOBAL_DIV1_LIST_ALL[$lang][$div['div1']] = $div['div1_name'];
                    $GLOBAL_DIV2_LIST_ALL[$lang][$div['div2']] = $div['div2_name'];
                    $GLOBAL_DIV3_LIST_ALL[$lang][$div['div3']] = $div['div3_name'];
                    break;
                default:
                    if (is_good($div['div1_name_'.$lang])) {
                        $GLOBAL_DIV1_LIST_ALL[$lang][$div['div1']] = $div['div1_name_'.$lang];
                        $GLOBAL_DIV2_LIST_ALL[$lang][$div['div2']] = $div['div2_name_'.$lang];
                        $GLOBAL_DIV3_LIST_ALL[$lang][$div['div3']] = $div['div3_name_'.$lang];
                    }
                    break;
            }
        }
    }

    switch ($level) {
        case 'div1' :
            return $GLOBAL_DIV1_LIST_ALL[$lang];
        case 'div2' :
            return $GLOBAL_DIV2_LIST_ALL[$lang];
        case 'div3' :
            return $GLOBAL_DIV3_LIST_ALL[$lang];
        default :
            return $GLOBAL_DIV_LIST_ALL[$lang];
    }
}

function clearDivCache()
{
    s_unlink(FILE_DIV_CACHE);
}

function getDiv1NameById($id, $lang="0")
{
    global $GLOBAL_DIV_LIST_ALL, $GLOBAL_DIV1_LIST_ALL, $GLOBAL_DIV2_LIST_ALL, $GLOBAL_DIV3_LIST_ALL;
    if (!is_array($GLOBAL_DIV_LIST_ALL[$lang])) {
        getDivListAll("", $lang);
    }

    return $GLOBAL_DIV1_LIST_ALL[$lang][$id];
}
function getDiv2NameById($id, $lang="0")
{
    global $GLOBAL_DIV_LIST_ALL, $GLOBAL_DIV1_LIST_ALL, $GLOBAL_DIV2_LIST_ALL, $GLOBAL_DIV3_LIST_ALL;
    if (!is_array($GLOBAL_DIV_LIST_ALL[$lang])) {
        getDivListAll("", $lang);
    }

    return $GLOBAL_DIV2_LIST_ALL[$lang][$id];
}

function getDiv3NameById($id, $lang="0")
{
    global $GLOBAL_DIV_LIST_ALL, $GLOBAL_DIV1_LIST_ALL, $GLOBAL_DIV2_LIST_ALL, $GLOBAL_DIV3_LIST_ALL;
    if (!is_array($GLOBAL_DIV_LIST_ALL[$lang])) {
        getDivListAll("", $lang);
    }

    return $GLOBAL_DIV3_LIST_ALL[$lang][$id];
}

function getDivNameById($id, $type, $lang="0")
{
    $func = "getDiv".preg_replace('/div/i', '', $type)."NameById";

    return $func($id, $lang);
}

function getSheetTypeNameById($id)
{
    global $_360_sheet_type;

    return $_360_sheet_type[$id];
}

function getUserTypeNameById($id)
{
    global $_360_user_type;
    $a = $_360_user_type;
    $a['others'] = "他者";

    return replaceMessage($a[$id]);
}

function dateCal($date)
{
    $d = ceil((strtotime($date) - time()) / (3600 * 24) + 1);
    if ($d <= 0)
        $d = 0;

    return $d;
}

/**
 * 代理入力者かどうかを判定します
 */
function isProxyInputUser()
{
    return ($_SESSION['login']['auth'] === true);
}

/**
 * 代理入力者としてセッションを登録します
 * @param array $admin 代理入力する管理者
 */
function setProxyInputStatus($admin)
{
    $_SESSION['login']['auth'] = true;
}

/**
 * serial_noからユーザを取得
 */

function getUserBySerial($serial_no, $refresh=false)
{
    static $getUserBySerial;

    if(!$refresh && is_good($getUserBySerial[$serial_no])) return $getUserBySerial[$serial_no];

    /*
    //とりあえずセッションを見てみる
    foreach ($_SESSION['login'] as $v1) {
        foreach ((array) $v1 as $v2) {
            foreach ((array) $v2 as $v3) {
                if (is_array($v3) && $v3['serial_no'] == $serial_no)
                    return $v3;
            }
        }
    }
    */

    $getUserBySerial[$serial_no] = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($serial_no));

    return $getUserBySerial[$serial_no];
}

/**
 * serial_noからユーザを取得
 */

function getUserByUid_($uid)
{
    //とりあえずセッションを見てみる
    foreach ($_SESSION['login'] as $v1) {
        foreach ((array) $v1 as $v2) {
            foreach ((array) $v2 as $v3) {
                if (isset($v3['uid']) && is_good($v3['uid']) && $v3['uid'] == $uid)
                    return $v3;
            }
        }
    }

    return $user = FDB :: select1(T_USER_MST, '*', 'where uid = ' . FDB :: escape($uid));

}


function setSelectStatus2($status, & $user)
{
    $user['select_status'] = $status;
    $data['select_status'] = FDB :: escape($status);
    FDB :: update(T_USER_MST, $data, 'where uid = ' . FDB :: escape($user['uid']));

    foreach ($_SESSION['login'][$user['sheet_type']][4] as & $u) {
        if ($u['uid'] == $user['uid']) {
            $u['select_status'] = $status;
        }
    }
}

function setSelectStatus($status, & $admit, & $user)
{
    $data = array ();
    $user['select_status'] = $status;
    $data['select_status'] = FDB :: escape($status);
    FDB :: update(T_USER_MST, $data, 'where uid = ' . FDB :: escape($user['uid']));
    FDB :: delete(T_USER_RELATION, 'where user_type = '.FDB::escape(ADMIT_USER_TYPE).' and uid_a = ' . FDB :: escape($user['uid']));
    $data = array ();
    $data['user_type'] = ADMIT_USER_TYPE;
    $data['uid_a'] = FDB :: escape($user['uid']);
    $data['uid_b'] = FDB :: escape($admit);
    FDB :: insert(T_USER_RELATION, $data);

    if ($_SESSION['login'][C_ID]) {
        $result = FDB :: select1(T_USER_MST, "*", "where " . C_ID . " = " . FDB :: escape($_SESSION['login'][C_ID]));
        setSessionLoginData360($result);
    }
}

function getMailFormat($mfid)
{
    return FDB :: select1(T_MAIL_FORM, '*', 'where mfid = ' . FDB :: escape($mfid));
}
function getMailFormatLang($mfid,$lang_type=0)
{
    $format =  FDB :: select1(T_MAIL_FORM, '*', 'where mfid = ' . FDB :: escape($mfid));
    if ($lang_type) {
        $body = $format["body_".$lang_type];
        $title = $format["title_".$lang_type];
    } else {
        $body = $format["body"];
        $title = $format["title"];
    }

    return array($title,$body);
}
function sendAnswerRequestMail($admit, $users)
{
    $result = true;
    if ($GLOBALS['Setting']->Mfid6Is0()) {
        return "no_send";
    } else {
        $admit = getUserByuid($admit);
        $format = Get_MailFormat(MFID_6);
        if(is_array($users))
        foreach ($users as $user) {
            /* 承認依頼メール */
            $user['target_uid'] = $admit['uid'];
            $user['target_name'] = $admit['name'];
            $user['target_name_'] = $admit['name_'];
            $result = Pc_Mail_Send($user, $format[0], array('mrid'=>-1));
            error_log(date("Y/m/d H:i:s")."\t".$user["email"]."\t".$format[0]['title']."\t".$result."\n", 3, LOG_FILE_MAIL);
        }
    }

    return $result;
}

function getHtmlSheetTypeRadio()
{
    $options = '';
    foreach ($GLOBALS['_360_sheet_type'] as $k => $v) {
        if ($k == 1) {
            $checked = ' checked';
        } else {
            $checked = '';
        }
        if ($k)
            $options .=<<<HTML
    <input type="radio" name="sheet_type" value="{$k}"{$checked}>{$v}
HTML;
    }

    return $options;
}

function getHtmlSheetTypeCheck($val="")
{
    $sheet_type = $GLOBALS['_360_sheet_type'];
    if (is_void($val))
       $val = range(0, count($sheet_type));

    $list = implode('', FForm :: checkboxlist('sheet_type[]', $sheet_type));
    foreach($val as $v)
        $list = FForm :: replaceChecked($list,$v);

    return $list;
}



function getMyLanguage()
{
    if(isset($_SESSION['login']['lang_type']))

        return $_SESSION['login']['lang_type'];
    return $_COOKIE['lang360'] ? $_COOKIE['lang360'] : 0;
}
getMessage();
function getMessage($key=null,$language=null)
{

    if($language===null)
        $language = getMyLanguage();
    if (is_array($GLOBALS['GLOBAL_MESSAGE'])) {
        return $GLOBALS['GLOBAL_MESSAGE'][$language][$key];
    }

    if (file_exists(FILE_MESSAGE_CACHE)) {
        $GLOBALS['GLOBAL_MESSAGE'] = unserialize(file_get_contents(FILE_MESSAGE_CACHE));
        if (is_array($GLOBALS['GLOBAL_MESSAGE'])) {
            return $GLOBALS['GLOBAL_MESSAGE'][$language][$key];
        }
        s_unlink(FILE_MESSAGE_CACHE);
    }
    $GLOBALS['GLOBAL_MESSAGE'] = array();
    foreach (FDB::select(T_MESSAGE,'*') as $message) {
        if (ereg('(.+)\[(.+)\]',$message['mkey'],$match)) {
            for ($i=0;$i<=4;$i++) {
                //print $match[1]."<>".$match[2]."<hr>";
                $GLOBALS['GLOBAL_MESSAGE'][$i][$match[1]][$match[2]] = $message['body_'.$i];
            }
        } else {
            for ($i=0;$i<=4;$i++) {
                $GLOBALS['GLOBAL_MESSAGE'][$i][$message['mkey']] = $message['body_'.$i];
            }
        }

    }
    s_write(FILE_MESSAGE_CACHE,serialize($GLOBALS['GLOBAL_MESSAGE']));

    return $GLOBALS['GLOBAL_MESSAGE'][$language][$key];
}

function clearMessageCache()
{
    s_unlink(FILE_MESSAGE_CACHE);
}



function limitColumn($array,$flag=0)
{
    //$array = array_flip($array);

    foreach ($GLOBALS['aryColumn'] as $v) {
        if (!ereg($v[1],$_SESSION["permitted_column"])) {
            unset($array[$v[1]]);
            if ($flag!=2 && $v[1]=='answer') {
                unset($array['url']);
            }
            if ($v[1]=='name') {
                unset($array['name_']);
                unset($array['admit_name']);
                unset($array['admit_name_']);
                unset($array['target_name']);
                unset($array['target_name_']);
            }
            if ($flag==1 && $v[1]=='mypage') {
                unset($array['button']);
            }
            if ($flag==2 && $v[1]=='mypage') {
                unset($array['url']);
            }
            if ($v[1]=='enq_delete') {
                unset($array['delete']);
            }
        }
    }

    return $array;
}


function hiddenColumn($array)
{
    //$array = array_flip($array);

    foreach ($GLOBALS['aryColumn'] as $v) {
        if (!ereg($v[1],$_SESSION["permitted_column"])) {
            $array[$v[1]]= '***';
            if ($v[1]=='answer') {
                $array['url'] = '***';
            }
            if ($v[1]=='name') {
                $array['target_name'] = '***';
            }
        }
    }

    return $array;
}

function limitAction($permitted)
{
    if (strpos($_SESSION['permitted_column'], $permitted) === false)
        return false;

    return true;
}

function get360Value($data,$col)
{
        $val = $data[$col];
        switch ($col) {
            case 'serial_no':
                return "";
            case 'mflag' :
            case 'lang_flag' :
            case 'test_flag' :
            case 'login_flag' :

                switch ($val) {
                    case 1 :
                        return '<img src="'.DIR_SRC.'flag1.gif" height="22" width="18">';
                    default :
                        return '<img src="'.DIR_SRC.'flag0.gif" height="22" width="18">';
                }
            case "div1" :
                return getDiv1NameById($val);
            case "div2" :
                return getDiv2NameById($val);
            case "div3" :
                return getDiv3NameById($val);
            case 'sheet_type' :
                if(!$data['mflag'])

                    return "-";
                return $GLOBALS['_360_sheet_type'][$val];
            case 'lang_type' :
                return $GLOBALS['_360_language'][$val];
            case 'user_type' :
                return $GLOBALS['_360_user_type'][$val];
            case 'pw' :
                return html_escape(getDisplayPw($val));
            case "ext1":
            case "ext2":
            case "ext3":
            case "ext4":
            case "ext5":
            case "ext6":
            case "ext7":
            case "ext8":
            case "ext9":
            case "ext10":
                return nl2br(html_escape($val));
            case "send_mail_flag":
                if ($val)
                    return '停止';

                return '送信';
            default :
                return html_escape($val);
        }
}

/**
 * ダウンロードダイアログ
 */
function download_dialog($tmpFile, $filename)
{

    $file = file_get_contents($tmpFile);
    $file = mb_convert_encoding($file,OUTPUT_CSV_ENCODE,INTERNAL_ENCODE);

    global $Setting;
    if($Setting->csvEncodeUtf16le())//BOMを追加
        $file = chr(255) . chr(254).$file;
    if($Setting->csvEncodeUtf8())//BOMを追加
        $file = chr(0xEF).chr(0xBB).chr(0xBF).$file;


    header("Pragma:private");
    header("Cache-Control:private");
    header("Content-Disposition:attachment;filename=\"".encodeDownloadFilename($filename)."\"");
    header("Content-Length:".strlen($file));
    header("Content-Type:application/octet-stream");
    print $file;
    s_unlink($tmpFile);
    exit;
}
function is_pc()
{
    if ($_SESSION['login']['agent']=='pc') {
        return true;
    } else {
        return false;
    }
}

function getLoginPage()
{
    if ($_SESSION['login']['agent']=='pc') {
        return 'pc.php';
    } else {
        return 'm.php';
    }
}

function setSelectStatus_($status,$user)
{
    global $ERROR;
    $data = array ();
    $user['select_status'] = $status;
    $data['select_status'] = FDB :: escape($status);
    FDB :: update(T_USER_MST, $data, 'where uid = ' . FDB :: escape($user['uid']));
    /* 承認者が選定するモードであれば紐付けを削除しない */
    if ($GLOBALS['Setting']->adminModeNotEqual(3)) {
        FDB :: delete(T_USER_RELATION, 'where user_type = '.ADMIT_USER_TYPE.' and uid_a = ' . FDB :: escape($user['uid']));
    }

    if ($_SESSION['login'][C_ID]) {
        $result = FDB :: select1(T_USER_MST, "*", "where " . C_ID . " = " . FDB :: escape($_SESSION['login'][C_ID]));
        setSessionLoginData360($result);

        //回答依頼メール
        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $UID_ESCAPED = FDB :: escape($user['uid']);
        $users = FDB :: getAssoc("select * from {$T_USER_RELATION} a left join {$T_USER_MST} b on a.uid_b = b.uid where a.uid_a = {$UID_ESCAPED} AND a.add_type IN (0, 1) GROUP BY a.uid_b order by div1,div2,div3,uid;");
        $result = sendAnswerRequestMail($user['uid'], $users);
        if (is_true($result)) {
            $ERROR->addMessage('####send_answer_mail####');
        } elseif ($result=="no_send") {
        } else {
            $ERROR->addMessage('####send_answer_mail_error####');
        }

        return $result;
    }
}

function getSheetNames($evid_flag=true)
{
    foreach ($GLOBALS['_360_sheet_type'] as $k1 => $sheet_type) {
        foreach ($GLOBALS['_360_user_type'] as $k2 => $user_type) {
            if ($k2>INPUTER_COUNT) {
                continue;
            }
            $evid = $k1 *100+$k2;
            if($evid_flag)
                $array[$evid] = $sheet_type.' '.$user_type;
            else
                $array[$evid] = $evid.'. '.$sheet_type.' '.$user_type;
        }
    }

    return $array;
}

function saveColmunSetting($data)
{
    foreach ($data['label'] as $k=>$v) {
        $data['label'][$k] = html_escape($v);
    }
    foreach ($data['width'] as $k=>$v) {
        $data['width'][$k] = $v ? (int) $v : '';
    }
    s_write(DIR_DATA.'colmun_setting.cdat',serialize($data));

    return $data;
}
function getColmunSetting()
{
    if(!$GLOBALS['getColmunSetting'])
        $GLOBALS['getColmunSetting'] = unserialize(file_get_contents(DIR_DATA.'colmun_setting.cdat'));

    return $GLOBALS['getColmunSetting'];
}
function getColmunLabel($type)
{
    $data = getColmunSetting();
    $array = array();
    foreach ((array) $data['colmun'][$type] as $k => $v) {
        if ($v) {
            $array[$k] = $data['label'][$k];
        }
    }

    return $array;
}

function getColmunWidth($type)
{
    $data = getColmunSetting();
    $array = array();
    foreach ((array) $data['colmun'][$type] as $k => $v) {
        if ($v) {
            $array[$k] = $data['width'][$k];
        }
    }

    return $array;
}

/************************
 * 2012/01/18
 * 以下BenesseFunction.phpから移植
 ************************/


/**
 * ユーザ検索ボックス
 */
function getUserSearchBox($post, $title="####search####", $doForm=true)
{
    require_once (DIR_LIB."JSON.php");
    if (is_void($post['search'])) {
        $post['div1'] = $_SESSION['login']['div1'];
        $post['div2'] = $_SESSION['login']['div2'];
        $post['div3'] = $_SESSION['login']['div3'];
    }


    $ary_div1['---'] = "---####no_set####---";
    $ary_div2['---'] = "---####no_set####---";
    $ary_div3['---'] = "---####no_set####---";
    $children['---'][] = '---';
    foreach (FDB::select(T_DIV,"*","order by div1,div2,div3") as $div) {

        if (!$ary_div1[$div['div1']]) {
            $children[$div['div1']][] = '---';
        }
        if (!$ary_div1[$div['div2']]) {
            $children[$div['div2']][] = '---';
        }


        $ary_div1[$div['div1']] = $div['div1_name'];
        $ary_div2[$div['div2']] = $div['div2_name'];
        $ary_div3[$div['div3']] = $div['div3_name'];
        $children[$div['div1']][] = $div['div2'];
        $children[$div['div2']][] = $div['div3'];
    }

    $json = new Services_JSON();
    $jsonObj = $json->encode($children);


    $div1_select = FForm::select("div1",$ary_div1,"onChange='reduce_options_div2()' id='id_div1'");
    $div2_select = FForm::select("div2",$ary_div2,"onChange='reduce_options_div3()' id='id_div2'");
    $div3_select = FForm::select("div3",$ary_div3,"id='id_div3'");

    $div1_selecto = FForm::select("div1o",$ary_div1,"id='id_div1o' style='display:none'");
    $div2_selecto = FForm::select("div2o",$ary_div2,"id='id_div2o' style='display:none'");
    $div3_selecto = FForm::select("div3o",$ary_div3,"id='id_div3o' style='display:none'");

    $div1_select = FForm::replaceSelected($div1_select,$post['div1']);
    $div2_select = FForm::replaceSelected($div2_select,$post['div2']);
    $div3_select = FForm::replaceSelected($div3_select,$post['div3']);

    $post = escapeHtml($post);
    $title = html_escape($title);
    //$div = FForm::replaceSelected(FForm::select('div', getAssocDiv()), $post['div']);
    $antiDoubleClick = JSCODE_ANTI_DOUBLE_CLICK;
    $userSearchBox = <<<__HTML__

{$div1_selecto}{$div2_selecto}{$div3_selecto}


<table class="table112">
<tr><td colspan="3" align="center" style="background-color:#555555;color:white">{$title}</td></tr>
<tr><td>####div####</td><td>
{$div1_select}<br>
{$div2_select}<br>
{$div3_select}</td>
</tr>
<tr><td>####name####</td><td><input name="name" value="{$post['name']}" style="width:200px"></td>

</tr>
</table>
<input type="submit" name="search" value="####search####" onClick="{$antiDoubleClick}">

<script>
document.getElementById('id_div1').style.visibility = 'hidden';
document.getElementById('id_div2').style.visibility = 'hidden';
document.getElementById('id_div3').style.visibility = 'hidden';

var group_r = $jsonObj;
function reduce_options_div2()
{
    div1 = document.getElementById('id_div1');
    div2 = document.getElementById('id_div2');
    div2_op = div2.options;

    div2o_op = document.getElementById('id_div2o').options;
    div1_selected = getSelectedOptions(div1)[0].value;
    div2_selected = getSelectedOptions(div2)[0].value;

    div2_op.length=0;

    for (i=0;i<div2o_op.length;i++) {
        for (j = 0;j<group_r[div1_selected].length;j++) {
                if (group_r[div1_selected][j] == div2o_op[i].value) {

                    if(div2_selected == div2o_op[i].value)
                        selected = "selected";
                    else
                        selected = null;

                    div2_op[div2_op.length] = new Option(div2o_op[i].text,div2o_op[i].value,null,selected);
                    break;
                }
        }
    }

    setTimeout('reduce_options_div3()',100);
}
function reduce_options_div3()
{
    div2 = document.getElementById('id_div2');
    div3 = document.getElementById('id_div3');

    div3_op = div3.options;
    div3o_op = document.getElementById('id_div3o').options;
    div2_selected = getSelectedOptions(div2)[0].value;
    div3_selected = getSelectedOptions(div3)[0].value;


    div3_op.length=0;
    for (i=0;i<div3o_op.length;i++) {
        for (j = 0;j<group_r[div2_selected].length;j++) {
                if (group_r[div2_selected][j] == div3o_op[i].value) {

                    if(div3_selected == div3o_op[i].value)
                        selected = "selected";
                    else
                        selected = null;
                    div3_op[div3_op.length] = new Option(div3o_op[i].text,div3o_op[i].value,null,selected);
                    break;
                }
        }
    }

    document.getElementById('id_div1').style.visibility = 'visible';
    document.getElementById('id_div2').style.visibility = 'visible';
    document.getElementById('id_div3').style.visibility = 'visible';
}

function getSelectedOptions(obj)
{
    var tmp = new Array();
    for (var i = 0; i < obj.options.length; i++) {
          if (obj.options[i].selected) {
              tmp.push(obj.options[i]);
          }
    }

    return tmp;
}

setTimeout('reduce_options_div2();',300);

</script>
__HTML__;
    if ($doForm) {
        $action = getLinkPHP();
        $userSearchBox = <<<__HTML__
<form action="{$action}" method="POST">
{$userSearchBox}
</form>
__HTML__;
    } else {
        $userSearchBox .= "\n<br><br>";
    }

    return $userSearchBox;
}

/**
 * PHPのリンク取得
 */
function getLinkPHP($PHP = "", $extra = true)
{
    if (is_void($PHP)) {
        $PHP = getPHP_SELF();
    }
    $PHP .= "?".getSID();
    if (is_true($extra)) {
        if(is_good($_GET['type']))		$PHP .= html_escape("&type={$_GET['type']}");
        if(is_good($_GET['target']))	$PHP .= html_escape("&target={$_GET['target']}");
    }

    return $PHP;
}

/**
 * マイページのHTML
 */
function getMyPageHtml($body)
{
    $html = HTML_header();
    $html .= HTML_top();
    $html .= HTML_logout();
    $html .= HTML_pageTitle();
    $html .= getMyPageTable($body);
    $html .= HTML_bottom();
    $html .= HTML_footer();

    return $html;
}

/**
 * マイページのtable
 */
function getMyPageTable($body)
{
    $userProfile = LFB_getUserProfile();
    $bgcolor = getMyPageBgcolor();

    return<<<__HTML__
<table class="login-entry" cellspacing="0" cellpadding="15" width="900" align="center" border="0">
<tr>
  <td align="center" valign="middle" width="200" bgcolor="#e6e6e6">{$userProfile}</td>
  <td align="left" bgcolor="{$bgcolor}">
{$body}
  </td>
</tr>
</table>\n
__HTML__;
}

/**
 * マイページの背景色
 */
function getMyPageBgcolor()
{
    global $type_color;
    //$bgcolor = $type_color[getTypeLFB($_GET['type'])];
    return '#f5f5f5';
}

function LFB_getUserProfile()
{
    global $type_name;
    $nowType = $_SESSION['login']['auth']['type'];
    if (is_good($nowType)) {
        $proxy = '<b>(' . html_escape($type_name[$nowType]) . ' 代理入力)</b><br><br>';
    }
    $loginName = html_escape($_SESSION['login']['name']).'<br>'.html_escape($_SESSION['login']['name_']);

    return<<<HTML
{$proxy}{$loginName}
HTML;
}

/**
 * div連想配列取得
 */
function getAssocDiv()
{
    global $__assocDiv;
    if (is_null($__assocDiv)) {
        $aryDiv = FDB::select(T_DIV, "div1,div2,div3,div1_name,div2_name,div3_name");
        $__assocDiv = array();
        $__assocDiv[''] = "指定しない";
        foreach ($aryDiv as $div) {
            $__assocDiv[$div['div1']] = $div['div1_name']." すべて";
            $__assocDiv[$div['div1'].",".$div['div2']] = $div['div1_name']." ".$div['div2_name']." すべて";
            $__assocDiv[getDivCode($div)] = $div['div1_name']." ".$div['div2_name']." ".$div['div3_name'];
        }
    }

    return $__assocDiv;
}

/**
 * div連想配列から名前取得
 */
function getDivName($aryDivCode)
{
    global $__assocDiv;
    if (is_null($__assocDiv)) {
        getAssocDiv();
    }

    return $__assocDiv[getDivCode($aryDivCode)];
}

/**
 * div取得
 */
function getDivCode($aryDivCode)
{
    return $aryDivCode['div1'].",".$aryDivCode['div2'].",".$aryDivCode['div3'];
}

function getAllEventArray()
{
    global $_360_menu_type,$_360_user_type,$_360_sheet_type;
    foreach ($_360_menu_type as $menu_type => $menu_type_name) {
        foreach ($_360_user_type as $user_type => $user_type_name) {
            if($menu_type == "select" && $user_type != 0)
                continue;

            if($menu_type == "admit" && $user_type != ADMIT_USER_TYPE)
                continue;

            if($menu_type == "input" && !in_array($user_type, range(0, INPUTER_COUNT)))
                continue;

            if(in_array($menu_type, array("fb","review")) && !in_array($user_type, array(0, VIEWER_USER_TYPE)))
                continue;

            foreach ($_360_sheet_type as $sheet_type => $sheet_type_name) {
                $events[] = array (
                    'evid' => $sheet_type * 100 + $user_type,
                    'type' => $menu_type,
                    'name' => $sheet_type_name . ' ' . $user_type_name . ' ' . $menu_type_name
                );
            }
        }
    }

    return $events;
}

/**
 * ユーザログインのログを取る
 */
function write_log_login_user($result, $id, $proxy_id="")
{
    if($GLOBALS['Setting']->loginLogInvalid())

        return;
    $data[] = date('Y-m-d H:i:s');
    $data[] = $result; //failure or success
    $data[] = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
    $data[] = $id;
    if (is_good($proxy_id)) {
        $data[] = $proxy_id;
    }
    error_log(implode("\t", $data) . "\n", 3, LOG_LOGIN_USER);
}
/**
 * ユーザダウンロードのログを取る
 */
function write_log_dl_user($result, $id, $proxy_id="")
{
    if($GLOBALS['Setting']->loginLogInvalid())

        return;
    $data[] = date('Y-m-d H:i:s');
    $data[] = $result; //failure or success
    $data[] = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
    $data[] = $id;
    if (is_good($proxy_id)) {
        $data[] = $proxy_id;
    }
    error_log(implode("\t", $data) . "\n", 3, LOG_DOWNLOAD_USER);
}

function isOutOfAnswerPeriod()
{
    return (time() > strtotime(OUT_OF_ANSWER_PERIOD));
}

class AccessLog
{

    /**
     * フロント画面用アクションログ出力
     * @return void
     */
    public static function writeLogActionFront()
    {
        $proxy_flg = (isset($_SESSION['login']['auth']) && $_SESSION['login']['auth']) ? 1 : 0;
        $save_data = array(
            'script_name' => $_SERVER['SCRIPT_NAME'],
            'date'        => date('Y-m-d h:i:s'),
            'uid'         => $_SESSION['login']['uid'],
            'muid'        => null,
            'proxy_flg'   => $proxy_flg,
        );
        $AccessLog = AccessLogDAO::instance();
        $AccessLog->insert($save_data);
    }

    /**
     * 管理画面用アクションログ出力
     * @return void
     */
    public static function writeLogActionAdmin()
    {
        $save_data = array(
            'script_name' => $_SERVER['SCRIPT_NAME'],
            'date'        => date('Y-m-d h:i:s'),
            'uid'         => null,
            'muid'        => $_SESSION['muid'],
            'proxy_flg'   => 0,
        );
        $AccessLog = AccessLogDAO::instance();
        $AccessLog->insert($save_data);
    }

}
