<?php
/**
 * 管理者を操作
 * @package Cbase.Research.Lib
 */
include_once 'CbaseAuthSet.php';
setcookie('lang360',0);
/**
 * POSTされた管理者情報をDBに登録
 * @param object $prmDb DB接続オブジェクト
 * @param string $mode モード
 * @param int $prmMuid 管理者ID
 */
function Set_Musr($prmData,$prmMode="insert",$prmMuid="")
{
    $aryData = array();

    switch ($prmMode) {
        case "insert":
            //$aryData['muid'] = FDB::getNextVal("muid");
            $aryData['muid'] = null;
            $aryData['pw'] = getPwHash($prmData['pw']);
            $aryData['permitted'] = null;

            foreach($GLOBALS['aryColumn'] as $tmp)
                $aryData['permitted_column'] .= $tmp[1].',';

            FDB::insert(T_AUTH_SET_DIV,FDB::escapeArray(array('muid'=>$aryData['muid'],'div1'=>'*','div2'=>'*','div3'=>'*')));

        case "update":
            if(is_null($prmData['id'])
//				|| is_null($prmData['pw'])
                || is_null($prmData['divs'])
                || is_null($prmData['name'])
                || is_null($prmData['flg']))

                return;

            $aryData['id'] = $prmData['id'];
            if(is_good($prmData['pw']))
                $aryData['pw'] = getPwHash($prmData['pw']);
            $aryData['divs'] = $prmData['div'];
            $aryData['name'] = $prmData['name'];
            $aryData['email'] = $prmData['email'];
            $aryData['flg'] = $prmData['flg'];
            $aryData['pwmisscount'] = $prmData['pwmisscount'];
            break;
        case "permit":
            $aryData['permitted'] = @implode(",", $prmData['ok']);

            if ($prmMuid === $_SESSION['muid']) {
                $_SESSION['permitted'] = $aryData['permitted'];
            }
            break;
        case "permit_column":
            $aryData['permitted_column'] = @implode(",", (array) $prmData['ok']);

            if ($prmMuid === $_SESSION['muid']) {
                $_SESSION['permitted_column'] = $aryData['permitted_column'];
            }
            break;
        default:
            return;
    }

    switch ($prmMode) {
        case "insert":
            return !is_false(FDB::setData("new", T_MUSR, $aryData));
        case "update":
        case "permit":
        case "permit_column":
            if(is_void($prmMuid))

                return;
            return !is_false(FDB::setData("update", T_MUSR, $aryData, "where muid=".sql_escape($prmMuid)));
        default:
            return;
    }
}

function Unset_Musr($prmMuid="")
{
    if(is_void($prmMuid))

        return;

    FDB::delete(T_AUTH_SET_DIV, "where muid=".sql_escape($prmMuid));

    return !is_false(FDB::delete(T_MUSR, "where muid=".sql_escape($prmMuid)));
}

/**
 * 管理者情報を取得
 * @param object $prmDb DB接続オブジェクト
 * @param int $prmMuid 管理者ID
 * @return array 結果配列
 */
function Get_Musr($prmMuid="")
{
    if (is_good($prmMuid)) {
        return FDB::select1(T_MUSR, "*", "where muid=".sql_escape($prmMuid));
    } else {
        return FDB::select(T_MUSR, "*", "order by muid desc");
    }
}

/**
 * 管理権限があるかどうかセッションを見てチェック(権限なしの場合強制終了)
 * @param string $prmPage php名
 */
function Check_AuthMng($prmPage)
{
    session_start();
    AccessLog::writeLogActionAdmin();
    //管理者ログイン情報をキャッシュ
    $musr = $_SESSION;

    if (!$musr['IP']) {
        error_exit('session timeout');//ERROR 2.0.1
        exit;
    }
    $ip = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
    if ($musr['IP'] <> $ip) {
        error_exit('ERROR 2.0.2');
        exit;
    }

    if ($musr['DIR'] <> DIR_MAIN.DIR_MNG) {
        error_exit('ERROR 2.0.4');
        exit;
    }

    //permittedにあれば救済
    if (!strstr($musr['permitted'], $prmPage)) {
        $auth =& AuthChecker::fromMusr($musr);
        if (!$auth->isAuthByManage($prmPage, $_GET['evid'])) {
            error_exit('誤操作防止のためアクセス権限がオフになっています。');
            exit;
        }
    }

    if (LOG_MODE_PHP>=1) {
        $ip = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
        error_log(date('Y-m-d H:i:s')."\t".$ip."\t".$_SESSION['muid']."\t".$_SERVER['SCRIPT_NAME']."\n", 3, LOG_FILE_PHP);
    }

    return true;
}

function Check_AuthMngEvid($evid)
{
    require_once(DIR_LIB.'CbaseFEnquete.php');
    $enq = Get_Enquete_Main('id', $evid, '', '', $_SESSION['muid']);
    if (is_void($enq[-1]['evid'])) {
        error_exit('ERROR 3.0.1');
        exit;
    }

    if (LOG_MODE_PHP>=1) {
        $ip = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
        error_log(date('Y-m-d H:i:s')."\t".$ip."\t".$_SESSION['muid']."\t".$_SERVER['SCRIPT_NAME']."\t".$evid."\n", 3, LOG_FILE_PHP);
    }

    return $evid;
}

function Check_Arg($arg)
{
    if (is_void($arg)) {
        error_exit('missing argument');//ERROR 4.0.1
        exit;
    }

    return $arg;
}

function Check_ArgHash($arg, $hash)
{
    if (md5($arg.SYSTEM_RANDOM_STRING)!=$hash) {
        error_exit('ERROR 4.1.1');
        exit;
    }

    return $arg;
}

function error_exit($message)
{
    require_once(DIR_LIB.'CbaseHtml.php');
    $objHtml =& new CbaseHtml("ERROR");
    echo $objHtml->getMainHtml($message);
    exit;
}
