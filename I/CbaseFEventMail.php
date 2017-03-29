<?php

//メール送信予約
/**
 * メール配信予約関連
 * @package Cbase.Research.Lib
 */
require_once 'CbaseFDB.php';
require_once 'CbaseFGeneral.php';
//statusflg 0->初期,1->済,2->中止
//sq,name,cnid,logflg,statusflg,Muserid,mDate,cdate,udateaddresscount

//Cron実行時、配信対象のレコードがあるかチェック
/**
 * 配信対象メール配信の取得
 * @param string $mode 取得モード
 * @return array メール配信予約データ
 */
function Check_MailEvent($mode = "checkevent")
{
    //statusが0で、配信日時が今より前のものを検索
    $array = Get_MailEvent($mode, date("Y-m-d H:i:s"), "mrid");
    if (!$array)
        return false;
    return $array;
}

/**
 * メール配信予約データの複製
 * @param int $mrid メール配信予約id
 * @return int  メール配信予約id
 */
function Duplicate_MailEvent($mrid)
{
    if (!$mrid)
        return false;

    $array = Get_MailEvent("id", $mrid, "", "");
    if (!$array)
        return false;
    $array[0]["name"] = getCopyName($array[0]["name"]);

    return Save_MailEvent("new", $array[0]);
}

//$mode	all(limit 30),checkevent,id
/**
 *  メール配信予約の削除
 * @param int $id  メール配信予約id
 * @return bool 削除結果
 */
function Delete_MailEvent($id)
{
    global $con; //Pear::DBconnect
    //SQL文生成
    $sql = "delete from " . T_MAIL_RSV . " ";
    $sql .= "where mrid = " . $id . " ";
    //SQL実行
    $rs = $con->query($sql);
    if (FDB :: isError($rs)) {
        echo $rs->getMessage();
        echo $rs->getDebuginfo();

        return false;
    }

    return true;
}

//$mode	all(limit 30),checkevent,id
/**
 *  メール配信予約データの取得
 * @param string $mode モード
 * @param string $value モードの条件値
 * @param string $orderk ソート列
 * @param string $orderflg ソート方法
 * @param int $muid 管理者id
 * @return array メール配信予約データ
 */
function Get_MailEvent($mode = -1, $value, $orderk="", $orderflg="", $muid="")
{
    global $con; //Pear::DBconnect
    //SQL文生成
    $sql = "select r.*, ";
    $sql .= "f.name as mfname, ";
    $sql .= "c.name as cndname, ";
    $sql .= "c.strsql as cndsql ";
    $sql .= "from " . T_MAIL_RSV . " r ";
    $sql .= "left join " . T_MAIL_FORM . " f using (mfid) ";
    $sql .= "left join " . T_COND . " c on r.cnid = c.cnid ";
    if ($mode == "checkevent") {
        $sql .= "where r.mdate <= '" . $value . "' ";
        $sql .= "and   r.flgs = 0 ";
    } elseif ($mode == "ML") {
        $sql .= "where r.mdate <= '" . $value . "' ";
        $sql .= "and   r.flgs = " . FLGS_NUMBER . " ";
    } elseif ($mode == "id") {
        $sql .= "where r.mrid = " . $value . " ";
    }
    if ($orderk)
        $sql .= "order by r." . $orderk . " ";
    if ($orderk && $orderflg == "desc")
        $sql .= "desc ";

    return FDB::getAssoc($sql);
    }

/**
 * この関数は現在実装されておりません
 *
 */
function Audit_MailEvent($mode = "new", $array)
{
    //insert,updateしても問題ない値かどうか。
    if ($mode == "new") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..mDate
    } elseif ($mode == "update") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..mDate,sq
    }

    return true;
}

/**
 * メール配信予約データの保存
 * @param string $mode 保存モード
 * @param array $array メール配信予約データ
 * @return int メール配信予約id
 */
function Save_MailEvent($mode = "new", $array)
{
    $MAIL_RSV_COLS = array (
        'mrid',
        'name',
        'mfid',
        'cnid',
        'flgs',
        'flgl',
        'mdate',
        'count',
        'cdate',
        'udate',
        'muid'
    //	'evid'
    );

    if ($mode == "new") {
        //$nid = $array["mrid"] = FDB::getNextVal('mrid');
        //if (MDB2 :: isError($nid))
        //	return false;

        if (!$array["mfid"])
            $array["mfid"] = 0; //ひな型デフォルト全員
        if (!$array["cnid"])
            $array["cnid"] = 0; //配信条件デフォルト全員
        if (!$array["flgl"])
            $array["flgl"] = 0; //ログ記録条件デフォルトOFF
        if (!$array["muid"])
            $array["muid"] = 0; //管理者IDデフォルト
        if (!$array["count"])
            $array["count"] = 0; //count
        if (!$array["count"])
            $array["count"] = 0; //count
        if (!$array["flgs"] && $array["flgs"] <> "0")
            $array["flgs"] = 2;

        $array['cdate'] = date("Y-m-d H:i:s");
        $array['udate'] = date("Y-m-d H:i:s");

        $array_ = array();
        foreach ($MAIL_RSV_COLS as $col) {
            if(isset($array[$col]))
                $array_[$col] = $array[$col];
        }

        FDB::begin();
        $array["mrid"] = null;
        $result = FDB :: insert(T_MAIL_RSV, FDB :: escapeArray($array_));
        $nid = FDB :: getLastInsertedId();
        FDB::commit();

    } elseif ($mode == "update") {
        $nid = $array["mrid"];

        if (!$array["flgl"])
            $array["flgl"] = 0;

        $array['udate'] = date("Y-m-d H:i:s");

        $array_ = array();
        foreach ($MAIL_RSV_COLS as $col) {
            if(isset($array[$col]))
                $array_[$col] = $array[$col];
        }
        $result = FDB :: update(T_MAIL_RSV, FDB :: escapeArray($array_), 'where mrid='.FDB :: escape($array_["mrid"]).' and flgs!=1 and flgs!=3 and flgs!=13');
    }
    if(!$result)

        return false;
    return $nid;
}

//NO123 メール配信履歴追加 *1 <--
function Save_MailLog($mrid, $user, $isSend)
{
    $MAIL_LOG_COLS = array (
        'mrid',
        'serial_no',
        'result',
    );
    $insert = array();
    foreach ($MAIL_LOG_COLS as $col) {
        switch ($col) {
            case "mrid":
                $insert[$col] = $mrid;
                break;
            case "serial_no":
                $insert[$col] = $user[$col];
                break;
            case "result":
                $insert[$col] = $isSend ? 1 : 0;	//成功=>1,失敗=>0
                break;
            default:
                break;
        }
    }
    $result = FDB :: insert(T_MAIL_LOG, FDB :: escapeArray($insert));

    return $result;
}
//NO123 メール配信履歴追加 *1 -->
