<?php
//検索条件データ
/**
 * 検索条件データ
 * @package Cbase.Research.Lib
 */
//sq,name,cDate,uDate,SQL,serialize($array),timingFLG

include_once 'CbaseFDB.php';

//下記ライブラリをラップして使いやすくするクラス
//TODO:そのうち、関数がクラスをラップするような形にして移行してください
//TODO:そのうち、クラスはCbaseMailCondition,phpに移動をおねがいします
class MailCondition
{
    public function delete($cnid)
    {
        return FDB::delete(T_COND,'where cnid = '.FDB::escape($cnid));
    }
    public function save($data)
    {
        $mode = ($data['cnid'])? 'update': 'new';

        return Save_Condition($mode, $data);
    }

    public function getByName($name, $cols='*')
    {
        return $this->getByCond('WHERE name = '.FDB::escape($name), $cols);
    }

    public function getByCond($cond, $cols='*')
    {
        return FDB::select(T_COND, $cols, $cond);
    }

    public function getSqlWhere($sql)
    {
        eregi("(where .+)$", $sql, $matches);

        return  preg_replace("/order.+$/i", "",$matches[1]);
    }
    /**
     * 返す結果は一件
     */
    public function getById($id, $cols='*')
    {
        $res = $this->getByCond('WHERE cnid = '.FDB::escape($id).' LIMIT 1', $cols);

        return $res[0];
    }

    /**
     * condデータを指定し、もしその条件で検索したら何件のデータが取れるかどうかを確認します
     */
    public function getInnnerSqlCount($condData)
    {
            $sql = preg_replace("/order.+$/i", "", $condData['strsql']);
            $count = FDB::getAssoc(preg_replace("/\*/", 'count(*) as count', $sql));

            return $count? $count[0]['count']: 'SQLエラー';
    }

    /**
     * condデータを指定し、その条件で検索した結果を返す
     */
    public function runInnnerSql($condData)
    {
            return FDB::getAssoc($condData['strsql']);
    }
}
/**
 * 条件の複製
 * @param int $cnid 条件id
 * @return int 条件id
 */
function Duplicate_Condition($cnid)
{
    if (!$cnid) return false;

    $array = Get_Condition("id",$cnid);
    if (!$array) return false;
    return Save_Condition("new",$array);
}

//$mode	all(limit 30),checkevent,id
/**
 * 条件の取得
 * @param string $mode 取得モード
 * @param string $value モードの条件値
 * @param int $muid
 * @param string $prmOrderColumn ソート列
 * @param string $prmOrder ソート方法
 * @return array 条件レコード
 */
function Get_Condition($mode="",$value, $muid="", $prmOrderColumn="cnid",$prmOrder="desc")
{
    global $con;
    //SQL文生成
    $sql = "select * from ".T_COND." ";
    if ($mode=="id") {
        $sql.= "where cnid = ".$value." ";
    }
    if ($prmOrder && $prmOrderColumn)
        $sql.= "order by $prmOrderColumn $prmOrder";

    //SQL実行

    $rs = $con->query($sql);
    //if (FDB::isError($rs)) return false;
    if (FDB::isError($rs)) {
        echo $rs->getDebuginfo();

        return false;
    }
    //データを配列に展開
    $row="";
    $array=array();
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    //while ($rs->fetchInto($row, DB_FETCHMODE_ASSOC)) {
        $array[] = $row;
    }

    return $array;
}

/**
 * 条件をinsert,updateしても問題ない値かどうか。
 * (このバージョンでは特にチェックは無し)
 * @param string $mode 保存モード
 * @param array $array 条件データ
 * @return bool 問題なければtrue
 */
function Audit_Condition($mode="new",$array)
{
    //insert,updateしても問題ない値かどうか。
    if ($mode=="new") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..sql,array,
    } elseif ($mode=="update") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..sql,array,sq
    }

    return true;
}

/**
 * 条件の保存
 * @param string $mode 保存モード
 * @param array $array 条件データ
 * @return int 条件ID
 */
function Save_Condition($mode="new",$array)
{
//sq,name,cDate,uDate,SQL,serialize($array),timingFLG
    global $con;
    if ($mode=="new") {
        //$nid = $con->nextId('cnid');
        //if (FDB::isError($nid)) return false;
        //if (FDB::isError($nid)) {
        //	echo $nid->getDebuginfo();
        //	return false;
        //	}
        $sql = "insert into ".T_COND." ";
        $sql.= "values (NULL,".
                FDB::quoteSmart($array["name"]).",".
                FDB::quoteSmart($array["strsql"]).",".
                FDB::quoteSmart($array["pgcache"]).",".
                FDB::quoteSmart($array["flgt"]).",'".
                date("Y-m-d H:i:s")."','".
                date("Y-m-d H:i:s")."',".
                FDB::quoteSmart($array["muid"]).")";
//		$sql.= ";";
        FDB::begin();
        $rs = $con->query($sql);
        if (FDB::isError($rs)) {
            echo $rs->getDebuginfo();

            return false;
        }
        $nid = FDB::getLastInsertedId();
        FDB::commit();
    } elseif ($mode=="update") {
        $sql = "update ".T_COND." ";
        $sql.= "set name    = ".FDB::quoteSmart($array["name"]).", ";
        $sql.= "    strsql  = ".FDB::quoteSmart($array["strsql"]).", ";
        $sql.= "    pgcache = ".FDB::quoteSmart($array["pgcache"]).", ";
        $sql.= "    flgt    = ".FDB::quoteSmart($array["flgt"]).", ";
        $sql.= "    udate   = '".date("Y-m-d H:i:s")."' ";
        $sql.= "where cnid  = ".$array["cnid"];
//		$sql.= ";";
        $nid = $array["cnid"];
        $rs = $con->query($sql);
        if (FDB::isError($rs)) {
            echo $rs->getDebuginfo();

            return false;
        }
    }

    return $nid;
}

function getConditionName($id)
{
    global $GLOBAL_CONDNAME;
    if (!is_array($GLOBAL_CONDNAME)) {
        $GLOBAL_CONDNAME = array (
            '0' => "全員配信",
            '-1' => "リマインダ配信ALL（途中保存者を含む未回答ユーザ全員）",
            '-2' => "リマインダ配信未回答（途中保存者を含まない配信）",
            '-3' => "リマインダ配信途中保存者（途中保存者に対しての配信）",
            '-4' => "特定の方への配信",
            '-99' => "回答者への配信"
        );
        //条件取得
        $aryCond = Get_Condition(null, null, $_SESSION['muid']);
        foreach ($aryCond as $cond) {
            if ($cond['name'] == "") {
                $cond['name'] = '条件#' . $cond['cnid'];
            }
            $GLOBAL_CONDNAME[$cond['cnid']] = $cond['name'];
        }
    }

    return $GLOBAL_CONDNAME[$id];
}

function getMailStatus($flag, $mrid="")
{
    $DIR_IMG = DIR_IMG;

    switch ($flag) {
        case '0' :
            $flag = '<div style="color:#ff0000;">予約中</div>';
            break;
        case '1' :
            $flag = '<img src="'.$DIR_IMG.'check.gif" width="10" height="10" alt="済">';
            break;
        case '2' :
            $flag = 'wait';
            break;
        case '3' :
            $flag = '<div style="color:#ff0000;">対象0</div>';
            break;
        case '9' :
            $flag = '<div style="color:#ff0000;">配信中</div>';
            break;
/*		case '10':
            $flag = '<div style="color:#ff0000;">ML予約中</div>';
            break;
        case '11':
            $flag = '<div style="color:#ff0000;">ML配信中</div>';
            break;
*/
        case '12':
            if (!existsMailForcedStopFile($mrid)) {
                $flag = '<div style="color:#ff0000;">配信中</div>';
            }
            $flag = '<div style="color:#ff0000;font-weight:bold;">停止中</div>';
            break;
        case '13':
            $flag = '<div style="color:#ff0000;font-weight:bold;">停止済</div>';
            break;
        default :
            $flag = '<div style="color:#ff0000;">エラー</div>';
            break;
    }

    return $flag;
}

function isMailStatusFinish($flag)
{
    return (in_array($flag, array(1,3,13)));
}

function isMailStatusWait($flag)
{
    return (in_array($flag, array(2)));
}

function isMailStatusError($flag)
{
    return (getMailStatus($flag)=='<div style="color:#ff0000;">エラー</div>');
}

function isMailStatusRsv($flag)
{
    return (in_array($flag, array(0,10)));
}

function isMailStatusExe($flag, $mrid)
{
    return (in_array($flag, array(9,11)) || (isMailStatusForcedStop($flag) && !existsMailForcedStopFile($mrid)));
}

function isMailStatusForcedStop($flag)
{
    return (in_array($flag, array(12)));
}

function existsMailForcedStopFile($mrid)
{
    return (file_exists(getMailForcedStopFile($mrid)));
}

function getMailForcedStopFile($mrid)
{
    return DIR_DATA."stop_mr{$mrid}.lock";
}
