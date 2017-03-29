<?php
/**
 * 日  付：2006/12/06
 * 作成者：cbase Kido
 *
 * 更新履歴
 */
define('DIR_ROOT', '../');

define('ENCODE_WEB_OUT', 'UTF-8');
define('MODE', 'AJAX');
//require_once('../bat/feedback.php');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'functions_ajax.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . 'SecularCreate.php');
require_once (DIR_LIB . 'SecularOperate.php');
require_once (DIR_LIB . 'SecularImport.php');
session_start();
Check_AuthMng('360_admin_operate.php');
mb_http_output("pass");
ob_start("ajaxPrint");

main();
exit;

function ajaxPrint($html)
{
    list($sql,$js) = explode('#boundary#',$html);
    $html = $js;
    if (DEBUG) {
        $js = addslashes($sql."\n".$js);
        $js = str_replace("\r\n","\n",$js);
        $js = str_replace("\r","\n",$js);
        $js = str_replace("\n",'\n',$js);
        $js ='alert("'.$js.'");';
        $html.=$js;
    }
    $html = encodeWebOut($html);
    header("Content-Type: text/html; charset=".ENCODE_WEB_OUT);
    header("Content-Length: ".strlen($html));

    return $html;
}

/*****************************************************************************************************************/
function _pass_set()
{
    FDB::begin();
    foreach (FDB::select(T_USER_MST, 'serial_no') as $user) {
        if (is_false(FDB::update(T_USER_MST, array('pw'=> FDB::escape(getPwHash("1111"))), "WHERE serial_no = ".FDB::escape($user['serial_no'])))) {
            FDB::rollback();

            return false;
        }
    }
    FDB::commit();

    return true;
}

function _clear_mail_rsv()
{
    return FDB :: sql("delete from mail_rsv;",true);
}

function _pass_reset()
{
    if(is_false(FDB::update(T_MUST, array('pw'=> FDB::escape(getPwHash("cbase"))), "WHERE id = ".FDB::escape("super"))))

        return false;
    return true;
}

function _clear_user()
{
    $result = array();
    $result[] = FDB :: sql("delete from usr;",true);
    $result[] = FDB :: sql("delete from usr_relation;",true);
    $result[] = FDB :: sql("delete from subevent_data;",true);
    $result[] = FDB :: sql("delete from event_data;",true);
    $result[] = FDB :: sql("delete from backup_data;",true);
    foreach($result as $r)
        if(is_false($r)) return false;

    return true;
}

function _clear_user_relation()
{
    $result = array();
    $result[] = FDB :: sql("delete from usr_relation;",true);
    $result[] = FDB :: sql("delete from subevent_data where evid%100<>0;",true);
    $result[] = FDB :: sql("delete from event_data where evid%100<>0;",true);
    $result[] = FDB :: sql("update usr set select_status = 0;");
    foreach($result as $r)
        if(is_false($r)) return false;

    return true;
}

function _clear_div()
{
    return FDB :: sql("delete from divs;",true);
}

function _clear_all()
{
    foreach(glob(DIR_DATA.'*.ccache') as $file)
        @s_unlink($file);

    foreach (glob(DIR_CACHE.'*.ccache') as $file) {
        if(!ereg('_[0-9]+\.ccache$',$file))
            @s_unlink($file);
    }

    $result = array();
    $result[] = FDB :: sql("delete from subevent_data;",true);
    $result[] = FDB :: sql("delete from event_data;",true);
    $result[] = FDB :: sql("delete from backup_event;",true);
    $result[] = FDB :: sql("delete from backup_data;",true);
    $result[] = FDB :: sql("delete from usr;",true);
    $result[] = FDB :: sql("delete from divs;",true);
    $result[] = FDB :: sql("delete from usr_relation;",true);
    $result[] = FDB :: sql("delete from uniqserial;",true);
    $result[] = FDB :: sql("delete from mail_rsv;",true);
    foreach($result as $r)
        if(is_false($r)) return false;

    return true;
}

function _clear_log()
{
    foreach(glob(DIR_LOG.'*.clog') as $file)
        @s_unlink($file);

    return true;
}

function _clear_access_log()
{
    $AccessLog = AccessLogDao::instance();
    $AccessLog->truncate();

    return true;
}

function _clear_pdf()
{
    foreach(glob(DIR_FEEDBACK.'*.pdf') as $file)
        @s_unlink($file);

    return true;
}

function _clear_enq_data()
{
    $result = array();
    $result[] = FDB :: sql("delete from subevent_data;",true);
    $result[] = FDB :: sql("delete from event_data;",true);
    $result[] = FDB :: sql("delete from backup_event;",true);
    $result[] = FDB :: sql("delete from backup_data;",true);
    foreach($result as $r)
        if(is_false($r)) return false;

    return true;
}

function _clear_enq_cache()
{
    @s_unlink(DIR_DATA.'events.ccache');
    foreach (glob(DIR_CACHE.'*.ccache') as $file) {
        if(!ereg('_[0-9]+\.ccache$',$file))
            @s_unlink($file);
    }

    return true;
}

//201012 No95 _clear_admin_operate_access追加
function _clear_admin_operate_access()
{
    global $arMenu;
    //自分の最新percmitを取得
    $musr = FDB::select(T_MUSR, 'permitted', 'WHERE muid='.FDB::escape($_SESSION['muid']));
    $permit = $musr[0]['permitted'];

    //admin_operateのpermitを取得
    //わざわざ検索しているのは、今後のphp追加に備えるため
    //ただし、メニュー名が変わった場合にはこちらも変えること
    $target_permit = '';
    foreach ($arMenu as $v) {
        if ($v[2] == 'Cbase用データ操作') {
            $target_permit = $v[1];
        }
    }
    //対象permitが取得できなかった場合は失敗
    if (!$target_permit) {
        return false;
    }

    //permitを削る。,の扱いが微妙なので一旦展開してから一つずつ消している
    $tp = explode(',', $target_permit);
    $resp = array();
    foreach (explode(',', $permit) as $v) {
        if(in_array($v, $tp)) continue;
        $resp[] = $v;
    }
    $res = implode(',', $resp);

    //上書き更新
    $rs = FDB::update(T_MUSR, array('permitted'=>FDB::escape($res)), 'WHERE muid='.FDB::escape($_SESSION['muid']));
    if (is_false($rs)) {
        return false;
    }

    //キャッシュも更新
    $_SESSION['permitted'] = $res;

    return true;
}

function _create_secular_dump()
{
    $SecularCreate = new SecularCreate();
    $secular = $SecularCreate->execute();
    if ($secular->isOK()) {
        page_reload();
    } else {
        return false;
    }
}

function _import_secular_dump()
{
    $SecularImport = new SecularImport();
    $secular = $SecularImport->execute();
    if ($secular->isOK()) {
        page_reload();
    } else {
        return false;
    }
}

function _raw_secular_import()
{
    $ymd = $_REQUEST['ymd'];
    if (!checkdate(substr($ymd, 4, 2), substr($ymd, 6, 2), substr($ymd, 0, 4))) {
        return false;
    }
    $SecularImport = new SecularImport();
    $secular = $SecularImport->importByRaw($ymd);
    if ($secular->isOK()) {
        page_reload();
    } else {
        return false;
    }
}

function _rehash_secular()
{
    $SecularOperate = new SecularOperate();
    $secular = $SecularOperate->rehashExecute();
    if ($secular->isOK()) {
        page_reload();
    } else {
        return false;
    }
}

function page_reload()
{
    $js = <<<JAVASCRIPT
#boundary#
document.location.reload();
setTimeout('buttonAble()',500);
JAVASCRIPT;
    print $js;
    exit;
}


/*****************************************************************************************************************/
function main()
{
    $mode = $_REQUEST['mode'];
    $func = '_' . $mode;

    if ($func ()) {
        $js .=<<<JAVASCRIPT
#boundary#
document.getElementById('{$mode}_result').innerHTML = '完了';
setTimeout('buttonAble()',500);
JAVASCRIPT;
    } else {
        $js .=<<<JAVASCRIPT
#boundary#
document.getElementById('{$mode}_result').innerHTML = '失敗';
setTimeout('buttonAble()',500);
JAVASCRIPT;
    }
    print $js;
    exit;
}
