<?php
/**
 * 回答済みデータの取得
 *
 * @package Cbase.Research.Lib
 */

 /**
 * 回答済みデータの取得
 * @param int $prmEvid イベントID
 * @param string $prmSerialNo ユーザ特定キー
 * @param string $mode モード
 * @return array 回答データ
 */
function Get_EnqueteAnswerData($prmEvid,$prmSerialNo, $mode="session")
{
    global $con;//Pear::DBconnect
    //SQL文生成
    $sql = "select a.*,b.type1,b.type2 ";
    $sql.= "from ".T_EVENT_SUB_DATA." a ";
    $sql.= "left join ".T_EVENT_SUB." b on a.seid = b.seid ";
    $sql.= "where a.evid       = ".$prmEvid." ";
    $sql.= "and   a.serial_no = '".$prmSerialNo."' ";
    $sql.= "order by a.seid";
    //SQL実行
    $rs = $con->query($sql);
    if (FDB::isError($rs)) return false;
    $row = '';
    //while ($rs->fetchInto($row,DB_FETCHMODE_ASSOC)) {
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $tmp=$row["type2"];
        if ($mode=="seid") $tmp="seid";
        $tid=$row["seid"];
        switch ($tmp) {
            case "seid":
                $array[$tid]["o"]=$row["other"];
                $array[$tid]["c"]=$row["choice"];
            case "t":
//				$array["T_".$tid]=mb_convert_encoding($row["other"],OUTPUT_ENCODE,INTERNAL_ENCODE);
                $array["T_".$tid]=$row["other"];
                break;
            case "p":
                $array["P_".$tid]=$row["choice"];
                $array["E_".$tid]=$row["other"];
                break;
            case "r":
                $array["P_".$tid][]=$row["choice"];
                $array["E_".$tid]=$row["other"];
                break;
            case "c":
                $array["P_".$tid][]=$row["choice"];
                $array["E_".$tid]=$row["other"];
                break;
        }
        unset($row);
    }

    return $array;
/*
    case "t":
            $parts[] = ' name="T_'.$seid.'"';
    case "p":
            $parts[] = ' name="'.$seid.'"';
    case "c":
            $parts[] = ' name="'.$seid.'[]"';
    case "r":
            $parts[] = ' name="'.$seid.'[]"';
    --------------------------------------------------------
    if (!ereg("[^0-9]",$k)) {
        unset($_SESSION["P_".$k]);
        $_SESSION["P_".$k] = $v;//["P_1234"]形式
    } elseif (ereg("^T_",$k)) {//テキスト回答
        unset($_SESSION[$k]);
        $_SESSION[$k] = html_escape(strip_tags(trim($v)));//["T_1234"]形式
    }
*/
}
