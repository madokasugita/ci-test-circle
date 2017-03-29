<?php

/*
 * 検索画面
 * （回答状況&URL取得）
 */

define('ADD_COLUMN', "add1,add2,add3,add4,add5,add6,add7,add8,add9,add10,add11,add12,add13,add14,add15,add16,add17,add18,add19,add20");
define('ALL_COLUMN', "respond,syaincode,url,div1,div2,div3,uid,name,id,pw,email,serial_no,cdate1," . ADD_COLUMN);
define('SU_WEB_DENY_COLUMN', "syaincode,serial_no," . ADD_COLUMN);
define('SU_CSV_DENY_COLUMN', "syaincode,serial_no");
define('USER_WEB_DENY_COLUMN', "syaincode,uid,email,serial_no," . ADD_COLUMN);
define('USER_CSV_DENY_COLUMN', "syaincode,uid,email,serial_no," . ADD_COLUMN);

//セッション保存キー
define('ENQ_SEARCH_SKEY', "enq_search");

/** ユーザマスタのevidカラムを考慮 0=>しない 1=>する */
define('USER_MST_EVID_MODE',1);

//必要外部ファイルの読み込み
define('DIR_ROOT', "../");
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'func_pd.php');
require_once (DIR_LIB . 'func_rtnclm.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFCheck.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebInAll();

Check_AuthMng(basename(__FILE__));
$evid = Check_AuthMngEvid($_REQUEST['evid']);
$PHP_SELF = getPHP_SELF()."?".getSID()."&".html_escape("evid={$evid}");

// セッション削除
if (!$_POST) {
    $_SESSION[ENQ_SEARCH_SKEY] = array ();
    unset ($_SESSION[ENQ_SEARCH_SKEY]);
}

foreach ($_POST as $key => $value) {
    if (ereg('div', $key))
        $_SESSION[ENQ_SEARCH_SKEY][$key] = $value;
}

if (isset ($_GET['evid']))
    $_SESSION[ENQ_SEARCH_SKEY]['evid'] = $_GET['evid'];
if (isset ($_POST['mode']))
    $_SESSION[ENQ_SEARCH_SKEY]['mode'] = $_POST['mode'];

$aryWhere = array ();
foreach ($_SESSION[ENQ_SEARCH_SKEY] as $key => $value) {
    if (ereg('div', $key) && $value != "*")
        $aryWhere[] = ($value != "null") ? "{$key}=" . FDB :: escape($value) : "{$key} is null";
}
$strWhere = implode(" and ", $aryWhere);

// データ取得 //2008/02/28 最初の画面では、データを取得しないように。

list ($aryUserData, $intTtl, $intAns) = transRespondStatus_($evid, "a.div1,a.div2,a.div3", $strWhere, $_SESSION[ENQ_SEARCH_SKEY]['mode']);

echo downRespondCSV($aryUserData);
exit;

/*
 * 表示許可カラム取得
 */
function getRespondColumn($isCSV = false)
{
    return explode(",", ALL_COLUMN);
}

/*
 * インデックスの日本語名取得
 */
function getRespondComment()
{
    global $con;
    $aryComment = array ();
    $tmpComment = getColumnName($con, T_USER_MST);
    foreach ($tmpComment as $comment) {
        $aryComment[$comment['name']] = (is_null($comment['comment'])) ? $comment['name'] : $comment['comment'];
    }
    $aryComment['respond'] = "回答状況";
    $aryComment['url'] = "URL";
    $aryComment['cdate1'] = "回答時間";

    return $aryComment;
}

/*
 * 条件選択肢取得
 */
function getDivSelect($column)
{
    global $con;

    if(USER_MST_EVID_MODE)
        $where  =  " where evid={$_SESSION[ENQ_SEARCH_SKEY]['evid']}";
    $strSql = "select distinct {$column} from " . T_USER_MST .$where;
    $tmpSelect = getDbDataPlural($con, $strSql);

    $arySelect = array ();
    $arySelect['*'] = "指定しない";
    foreach ($tmpSelect as $select) {
        if (is_null($select[$column]))
            $select[$column] = "null";
        $arySelect[$select[$column]] = (trim($select[$column]) == "") ? "'{$select[$column]}'" : $select[$column];
    }

    return $arySelect;
}

/*
 * CSVダウンロード
 */
function downRespondCSV($aryUserData)
{
    $aryColumn = getRespondColumn(true);
    $aryComment = getRespondComment();

    $strCSV = "";

    $indexCSV = array ();
    foreach ($aryColumn as $column) {
        $indexCSV[] = $aryComment[$column];
    }
    $strCSV .= implode(",", $indexCSV) . "\n";

    foreach ($aryUserData as $userData) {
        $dataCSV = array ();
        foreach ($aryColumn as $column) {
            switch ($column) {
                case 'url' :
                    $userData['url'] = DOMAIN . DIR_MAIN . "?{$userData['url']}";
                    break;
                case 'name' :
                    $userData['name'] = "{$userData['name']} {$userData['name2']}";
                default :
                    break;
            }
            $dataCSV[] = $userData[$column];
        }
        $strCSV .= implode(",", $dataCSV) . "\n";
    }
    ob_end_flush();
    $strCSV = mb_convert_encoding(str_replace("\n", "\r\n", $strCSV), 'SJIS', 'EUC-JP');
    header("Pragma:private");
    header("Cache-Control:private");
    header("Content-Disposition:attachment;filename=enq_data.csv");
    header("Content-Length:" . strlen($strCSV));
    header("Content-Type:application/octet-stream");
    echo $strCSV;
    exit;
}

function transRespondCount($prmEvid, $prmSort,$strWhere = '1=1',$strMode = '')
{
    if (USER_MST_EVID_MODE) {
        $usrevid = ' and a.evid = b.evid ';
    }

    $strSql = sprintf("select count(*) as count from %s a left join %s b on a.serial_no = b.serial_no and b.evid = %s{$usrevid}", T_USER_MST, T_EVENT_DATA, FDB :: escape($prmEvid));
    $strSql.= "where 1=1";
    if($strWhere)
        $strSql.= " and {$strWhere}";

    if (USER_MST_EVID_MODE) {
        $strSql.= " and a.evid = ".FDB :: escape($prmEvid);
    }
    switch ($strMode) {
        case 'no_respond':
            $strSql.= ' and (answer_state < 0 or answer_state is null)';
            break;
        case 'respond':
            $strSql.= ' and answer_state = 0';
            break;
        case 'bdid':
            $strSql.= ' and answer_state = 10';
            break;
    }
    $result = FDB :: getAssoc($strSql);

    return $result[0]['count'];
}

function transRespondStatus_($prmEvid, $prmSort,$strWhere = '1=1',$strMode = '')
{

    if (USER_MST_EVID_MODE) {
        $usrevid = ' and a.evid = b.evid ';
    }

    $strSql = sprintf("select *,a.serial_no as ans,b.udate as cdate1 from %s a left join %s b on a.serial_no = b.serial_no and b.evid = %s{$usrevid}", T_USER_MST, T_EVENT_DATA, FDB :: escape($prmEvid));
    $strSql.= "where 1=1";
    if($strWhere)
        $strSql.= " and {$strWhere}";

    if (USER_MST_EVID_MODE) {
        $strSql.= " and a.evid = ".FDB :: escape($prmEvid);
    }
    switch ($strMode) {
        case 'no_respond':
            $strSql.= ' and (answer_state < 0 or answer_state is null)';
            break;
        case 'respond':
            $strSql.= ' and answer_state = 0';
            break;
        case 'bdid':
            $strSql.= ' and answer_state = 10';
            break;
    }

    $strSql .= sprintf(" order by %s", $prmSort);

    $result = FDB :: getAssoc($strSql);
    $aryEvent1 = Get_Enquete("id", $prmEvid, "", "");
    $aryEvent = $aryEvent1[-1];

    //回答済み数
    $intAl = 0;
    for ($i = 0; $i < count($result); $i++) {
        $strStatus = $result[$i]["answer_state"];
        $strSerialNo = $result[$i]["ans"];
        $result[$i]["url"] = Create_QueryString($strSerialNo, $aryEvent["rid"]);

        if ($strStatus == '0') {
            $result[$i]["respond"] = "○";
            ++ $intAl;
        } elseif ($strStatus == '10') {
            $result[$i]["respond"] = "△";
        } else {
            $result[$i]["respond"] = "×";
        }
    }

    return array (
        $result,
        count($result
    ), $intAl);
}
