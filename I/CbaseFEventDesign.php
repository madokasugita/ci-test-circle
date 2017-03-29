<?php
require_once 'CbaseFDBClass.php';
//デザインデータを読み出す
/**
 * デザインデータを読み出す
 * @package Cbase.Research.Lib
 */

//event_html関係

//$mode	all(limit 30),checkevent,id
/**
 * EventHtmlのデータを読み込む
 * @param string $mode モード(idのみ対応)
 * @param int $value モードがidの時、条件とするid
 * @return array 読み込んだデータ
 */
function Get_Html($mode = "", $value)
{
    $sql = "select * from " . T_EVENT_HTML . " ";
    if ($mode == "id") {
        $sql .= "where evid = " . $value;
    }

    return FDB :: getAssoc($sql);
}

/**
 * EventHtmlを保存する
 * @param string $mode モード（new|update)
 * @param array $array 保存するデータ
 * @return mixed 成功すればevid、失敗すればfalse
 */
function Save_Html($mode = "new", $array)
{
    //sq,name,cDate,uDate,SQL,serialize($array),timingFLG
    global $con;
    if ($mode == "new") {
        if ($array["evid"] == "") {
            $nid = $con->nextId('evid');
            if (DB :: isError($nid))
                return false;
        } else {
            $nid = $array["evid"];
        }

        $sql = "insert into " . T_EVENT_HTML . " ";
        $sql .= "values (" .
        $con->quoteSmart($nid) . "," .
        $con->quoteSmart($array["thanks"]) . "," .
        $con->quoteSmart($array["closed"]) . "," .
        $con->quoteSmart($array["no_entry"]) . "," .
        $con->quoteSmart($array["already_entry"]) . "," .
        $con->quoteSmart($array["error"]) .
        ")";
        $sql .= ";";
    } elseif ($mode == "update") {
        $sql = "update " . T_EVENT_HTML . " ";
        $sql .= "set ";
        $sql .= "    thanks       = " . $con->quoteSmart($array["thanks"]) . ", ";
        $sql .= "    closed       = " . $con->quoteSmart($array["closed"]) . ", ";
        $sql .= "    no_entry     = " . $con->quoteSmart($array["no_entry"]) . ", ";
        $sql .= "    already_entry= " . $con->quoteSmart($array["already_entry"]) . ", ";
        $sql .= "    error   = " . $con->quoteSmart($array["error"]) . " ";
        $sql .= "where evid  = " . $array["evid"];
        $sql .= ";";
        $nid = $array["evid"];
    }
    $rs = $con->query($sql);
    //	output_sql($sql);
    if (DB :: isError($rs)) {
        echo $rs->getDebuginfo();

        return false;
    }

    return $nid;
}

//event_design関係

//$mode	all(limit 30),checkevent,id
/**
 * EventDesignのデータを読み込む
 * @param string $mode モード(idのみ対応)
 * @param int $value モードがidの時、条件とするid
 * @return array 読み込んだデータ
 */
function Get_Design($mode = "", $value)
{
    $sql = "select * from " . T_EVENT_DESIGN . " ";
    if ($mode == "id") {
        $sql .= "where evid = " . $value;
    }

    return FDB :: getAssoc($sql);
}

/**
 * EventDesignを保存する
 * @param string $mode モード（new|update)
 * @param array $array 保存するデータ
 * @return mixed 成功すればevid、失敗すればfalse
 */
function Save_Design($mode = "new", $array)
{
    //sq,name,cDate,uDate,SQL,serialize($array),timingFLG
    global $con;
    if ($mode == "new") {

        if ($array["evid"] == "") {
            $nid = $con->nextId('evid');
            if (DB :: isError($nid))
                return false;
        } else {
            $nid = $array["evid"];
        }

        $sql = "insert into " . T_EVENT_DESIGN . " ";
        $sql .= "values (" .
        $con->quoteSmart($nid) . "," .
        $con->quoteSmart($array["sa_title"]) . "," .
        $con->quoteSmart($array["sa_cheader"]) . "," .
        $con->quoteSmart($array["sa_cbody"]) . "," .
        $con->quoteSmart($array["sa_cother"]) . "," .
        $con->quoteSmart($array["sa_cfooter"]) . "," .
        $con->quoteSmart($array["ma_title"]) . "," .
        $con->quoteSmart($array["ma_cheader"]) . "," .
        $con->quoteSmart($array["ma_cbody"]) . "," .
        $con->quoteSmart($array["ma_cother"]) . "," .
        $con->quoteSmart($array["ma_cfooter"]) . "," .
        $con->quoteSmart($array["fa_title"]) . "," .
        $con->quoteSmart($array["fa_cheader"]) . "," .
        $con->quoteSmart($array["fa_cbody"]) . "," .
        $con->quoteSmart($array["fa_cfooter"]) . "," .
        $con->quoteSmart($array["mx_title"]) . "," .
        $con->quoteSmart($array["mx_cheader"]) . "," .
        $con->quoteSmart($array["mx_cbody"]) . "," .
        $con->quoteSmart($array["mx_cchoice"]) . "," .
        $con->quoteSmart($array["mx_cfooter"]) .
        ")";
        $sql .= ";";
    } elseif ($mode == "update") {
        $sql = "update " . T_EVENT_DESIGN . " ";
        $sql .= "set ";
        $sql .= "    sa_title  = " . $con->quoteSmart($array["sa_title"]) . ", ";
        $sql .= "    sa_cheader= " . $con->quoteSmart($array["sa_cheader"]) . ", ";
        $sql .= "    sa_cbody  = " . $con->quoteSmart($array["sa_cbody"]) . ", ";
        $sql .= "    sa_cother = " . $con->quoteSmart($array["sa_cother"]) . ", ";
        $sql .= "    sa_cfooter= " . $con->quoteSmart($array["sa_cfooter"]) . ", ";
        $sql .= "    ma_title  = " . $con->quoteSmart($array["ma_title"]) . ", ";
        $sql .= "    ma_cheader= " . $con->quoteSmart($array["ma_cheader"]) . ", ";
        $sql .= "    ma_cbody  = " . $con->quoteSmart($array["ma_cbody"]) . ", ";
        $sql .= "    ma_cother = " . $con->quoteSmart($array["ma_cother"]) . ", ";
        $sql .= "    ma_cfooter= " . $con->quoteSmart($array["ma_cfooter"]) . ", ";
        $sql .= "    fa_title  = " . $con->quoteSmart($array["fa_title"]) . ", ";
        $sql .= "    fa_cheader= " . $con->quoteSmart($array["fa_cheader"]) . ", ";
        $sql .= "    fa_cbody  = " . $con->quoteSmart($array["fa_cbody"]) . ", ";
        $sql .= "    fa_cfooter= " . $con->quoteSmart($array["fa_cfooter"]) . ", ";
        $sql .= "    mx_title  = " . $con->quoteSmart($array["mx_title"]) . ", ";
        $sql .= "    mx_cheader= " . $con->quoteSmart($array["mx_cheader"]) . ", ";
        $sql .= "    mx_cbody  = " . $con->quoteSmart($array["mx_cbody"]) . ", ";
        $sql .= "    mx_cchoice = " . $con->quoteSmart($array["mx_cchoice"]) . ", ";
        $sql .= "    mx_cfooter= " . $con->quoteSmart($array["mx_cfooter"]) . " ";
        $sql .= "where evid  = " . $array["evid"];
        $sql .= ";";
        $nid = $array["evid"];
    }
    $rs = $con->query($sql);
    //	output_sql($sql);
    if (DB :: isError($rs)) {
        echo $rs->getMessage();
        echo $rs->getDebuginfo();

        return false;
    }

    return $nid;
}
