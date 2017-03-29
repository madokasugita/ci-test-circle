<?php
/**
 * PG名称：sqlsearch用関数郡
 * 日  付：2005/05/25
 * 作成者：cbase Akama
 * @package Cbase.Research.Lib
 */

 /*
  * FDBを有効にしてから使うこと
  */

/*
 * 目次
 * ==============================================================
 * ・テーブル名からvalue=カラム名のセレクトボックス
 * ・テーブル名からvalue=カラム名のチェックボックスの配列
 * ・DBから取得したデータをテーブルにする
 * ・セッションデータからwhere節を作成
 * ・セッションデータからSQLを作成
 * ・sqlでselectを実行
 *
 */

/**
 * テーブル名からvalue=カラム名のセレクトボックスを返す
 * @param string $prmName inputタグのname
 * @param string $prmTable テーブル名
 * @param string $prmStr 一番上に表示する文字列（省略可）
 * @return string html
 */
function getHtmlColumnSb($prmName,$prmTable,$prmStr="")
{
    global $con;
    $strResult = '';
    $aryColumn = getColumnName($con,$prmTable);
    //prmTableに対応した名称変換テーブルがあれば使う(未実装)
    foreach ($aryColumn as $value) {
        //$strResult.= '<option value="'.$value.'">'.$value.'</option>';
        $strResult.= '<option value="'.$value["name"].'">'.($value["comment"]?
$value["comment"]:$value["name"]).'</option>';

    }

    if ($strResult) {
        if ($prmStr) {
            $strResult = '<option value="">'.$prmStr.'</option>'.$strResult;
        }
        $strResult = '<select name="'.$prmName.'">'.$strResult.'</select>';

    }

    return $strResult;
}

/**
 * テーブル名からvalue=カラム名のチェックボックスの配列を返す
 * @param string $prmName inputタグのname
 * @param string $prmTable テーブル名
 * @return string html
 */
function getHtmlColumnCb($prmName,$prmTable)
{
    global $con;
    $strResult = '';
    $aryColumn = getColumnName($con,$prmTable);
    //prmTableに対応した名称変換テーブルがあれば使う(未実装)
    foreach ($aryColumn as $value) {
        $strResult[] = '<input type="checkbox" name="'.$prmName.'[]" value="'.$value["name"].'">'.($value["comment"]?
$value["comment"]:$value["name"]).'</option>';
    }

    return $strResult;
}

/**
 * dataをテーブルにする
 * @param array $prmData テーブルにするデータ
 * @return string html
 */
function getHtmlTablefromAry($prmData)
{
    if(!$prmData) return;
    $strIndex = array_keys($prmData[0]);
    foreach ($strIndex as $index) {
        $strRowIndex.= '<td><font size="2">'.$index.'</font></td>';
        foreach ($prmData as $key => $value) {
            $strRowData[$key].= '<td><font size="2">'.$value[$index].'</font></td>';
        }
    }
    $strRowIndex= '<tr bgcolor="#CCCCCC" align="center"> '.$strRowIndex.'</tr>';
    $i=0;
    $strBgcolor[0]= "bgcolor = #ffffff";
    $strBgcolor[1]= "bgcolor = #f6f6f6";
    foreach ($strRowData as $value) {
        $strResult.= '<tr '.$strBgcolor[$i % 2].'>'.$value.'</tr>';
        $i++;
    }
    $strResult = '
        <table width="675" border="0" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
            <tr>
            <td valign="bottom">
            <table width="675" border="0" cellpadding="0" cellspacing="1">
'.$strRowIndex.$strResult.'
            </table>
            </td>
            </tr>
        </table>
';

    return $strResult;
}

/**
 * dataからWhere部分のSQLを生成して返す
 * @param array $prmData dataデータ($_SESSION["data"])
 * @return	string SQL文
 */
function getSQLWhere($prmData)
{
    if (!$prmData["cond"]) {
        return "";

    }
    //condデータをmstとenqに分ける
    foreach ($prmData["cond"] as $value) {
        $arySQL[$value["type"]][] = $value["SQL"];
    }
    //SQL文生成

    if (count($arySQL) > 0) {
        $strSQL.= ' WHERE ';
        if (count($arySQL["mst"]) > 0) {
            $strMst = implode(" ".$prmData["op"]." ",$arySQL["mst"]);
            if (count($arySQL["enq"]) > 0) {
                $strMst = "(".$strMst.") AND ";
            } else {
                $strMst.= ' ';
            }
            $strSQL.= $strMst;
        }
        if (count($arySQL["enq"]) > 0) {
            foreach ($arySQL["enq"] as $key => $value) {
                if (substr($value,0,5)=="%NOT%") {
                    $arySQL["enq"][$key]= 'serial_no NOT IN ';
                    $arySQL["enq"][$key].= '('.substr($value,5).') ';
                } else {
                    $arySQL["enq"][$key]= 'serial_no IN ';
                    $arySQL["enq"][$key].= '('.$value.') ';
                }
            }
            $strEnq = implode(" ".$prmData["op"]." ",$arySQL["enq"]);
            if (count($arySQL["mst"]) > 0) {
                $strEnq = "(".$strEnq.")";
            }
            $strSQL.= $strEnq.' ';
        }
    }

    return $strSQL;
}

/**
 * dataからSQLを生成して返す
 * @param array $prmData dataデータ($_SESSION["data"])
 * @return	string SQL文
 */
function getSQL($prmData)
{
    if (!$prmData["cond"]) {
        $strSQL = 'SELECT * FROM '.T_USER_MST;

        return $strSQL;

    }
    //condデータをmstとenqに分ける
    foreach ($prmData["cond"] as $value) {
        $arySQL[$value["type"]][] = $value["SQL"];
    }
    //SQL文生成
    $strSQL = 'SELECT * FROM ';
    $strSQL.= T_USER_MST;
    $strSQL.= getSQLWhere($prmData);
    $strSQL.= 'ORDER BY '.'uid';

    return $strSQL;
}

/**
 * sqlでselectして返す
 * @param string $prmSQL sql文
 * @return	array データ
 */
function getSQLSelect($prmSQL)
{
    global $con;
    //SQL実行
    $rs = $con->query($prmSQL);
    if (FDB::isError($rs)) return false;
    //データを配列に展開
    $array = array();
    $row = '';
    //while ($rs->fetchInto($row,DB_FETCHMODE_ASSOC)) {
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $array[] = $row;
    }

    return $array;
}
