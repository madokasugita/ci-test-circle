<?php
/**
 * イベント回答基本データ操作
 * @package Cbase.Research.Lib
 */
//メール送信予約
require_once 'CbaseFDB.php';
require_once 'CbaseFGeneral.php';

/**
 * イベント回答データ件数チェックの特殊条件版
 * @param int $rid イベントID
 * @param string $urid ユーザー特定キー
 * @param string $flg ユーザー層データ
 * @return bool 問題なければtrue
 */
function Check_TohoData($rid,$urid="",$flg="")
{
    global $con;//Pear::DBconnect
    //SQL文生成
    $sql = "select * ";
    $sql.= "from ".T_EVENT_DATA." ";
    $sql.= "where serial_no = '".$urid."' ";
    //日付の条件:二ヶ月八週間以内に回答はできない
    $sql.= "and cdate > '".date("Y-m-d",mktime(0,0,0,date("m")-2,date("d"),date("Y")))."'";
    //SQL実行
    $rs = $con->query($sql);
    if (FDB::isError($rs)) return false;
    if ($rs->numRows()==0) return false;
    return true;
}

/**
 * イベント回答データ件数チェック
 * @param int $rid イベントID
 * @param string $urid ユーザー特定キー
 * @param string $flg ユーザー層データ
 * @return int データ件数
 */
function Check_Data($rid,$urid="",$flg="")
{
    global $con;//Pear::DBconnect
    //SQL文生成
    $sql = "select * ";
    $sql.= "from ".T_EVENT_DATA." ";
    $sql.= "where evid       = ".$rid." ";
    if ($urid<>"") {
    $sql.= "and   serial_no = '".$urid."' ";
    }
    if ($flg<>"") {
    $sql.= "and   flg = '".$flg."' ";
    }
    $sql.= "and answer_state=0 ";

    //SQL実行
    $rs = $con->query($sql);
    if (FDB::isError($rs)) return false;
    return $rs->numRows();
}

/**
 * イベント複製
 * @param int $evid イベントID
 * @return int イベントID
 */
function Duplicate_Event($evid)
{
    if (!$evid) return false;

    $array = Get_Event("id",$evid,"","");
    if (!$array) return false;
    $array[0]["name"] = getCopyName($array[0]["name"]);

    return Save_Event("new",$array[0]);
}

//$mode	all(limit 30),id
/**
 * イベント取得
 * @param string $mode モード
 * @param string $value モードの条件値
 * @param string $orderk ソート列
 * @param string $orderflg ソート方法
 * @return 型名 戻り値の説明
 */
function Get_Event($mode=-1,$value,$orderk,$orderflg)
{
    global $con;//Pear::DBconnect
    //SQL文生成
    $sql = "select * ";
    $sql.= "from ".T_EVENT." ";
    if ($mode=="id") {
        $sql.= "where evid = ".$value." ";
    }
    if ($orderk)						$sql.="order by ".$orderk." ";
    if ($orderk && $orderflg=="desc")		$sql.="desc ";
    //SQL実行
    $rs = $con->query($sql);
    if (FDB::isError($rs)) return false;
/*	if (FDB::isError($rs)) {
        echo $rs->getMessage();
        echo $rs->getDebuginfo();

        return false;
    }
*/
    //データを配列に展開
    $array = array();
    $row = '';
    //while ($rs->fetchInto($row,DB_FETCHMODE_ASSOC)) {
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $array[] = $row;
    }

    return $array;
}

/**
 * この関数は実装されていません
 */
function Audit_Event($mode="new",$array)
{
    //insert,updateしても問題ない値かどうか。
    if ($mode=="new") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..mDate
    } elseif ($mode=="update") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..mDate,sq
    }

    return true;
}

/**
 * イベントデータ保存
 * @param array $data イベントデータ
 * @return bool 実行結果
 */
function Save_EventData($data)
{
    global $con;
    //event_dataに登録
    $sql = 'insert into '.T_EVENT_DATA.' values(';
    $sql.= $data["evid"].',';
    $sql.= "'".$data["uid"]."',";
    $sql.= "'".date("Y-m-d H:i:s")."',";
    $sql.= "'".$data["flg"]."'";
    $sql.= ')';
    $rs = $con->query($sql);
    if (FDB::isError($rs)) {
        if (DEBUG) echo $rs->getMessage();
        if (DEBUG) echo $rs->getDebuginfo();
        return false;
    }

    return true;
}
