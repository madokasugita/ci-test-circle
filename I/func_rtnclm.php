<?php
/**
 * PG名称：テーブルを入れるとDBからカラムを返す関数
 * 日  付：2005/04/13
 * 作成者：cbase Akama
 * @package Cbase.Research.Lib
 */

require_once '../crm_define.php';
require_once '../I/CbaseFDB.php';

//2007/2/26-akama getColumnNameが上位互換のため以後そちらを使用すること
/**
 * DBからtableのcolumnの配列を返す
 * @param object DB $prmDb PEARDBのインスタンス
 * @param string $prmTable table
 * @return	array columnの配列
 */
function getColumn($prmDb,$prmTable)
{
    $info = $prmDb->tableInfo($prmTable);
    foreach ($info as $v) {
        $result[] = $v["name"];
    }

    return $result;
}

/**
 * ■DBからtableのcolumnの名前とコメントの配列を返す。コメントがない場合はNULLが入る。
 * @param object DB $prmDb PEAR DBオブジェクト
 * @param string $prmTable 取得したいテーブルの名前
 * @return	array("name" => "カラム名"
 * 				,"comment"=>"コメント"
 */
function getColumnName($prmDb,$prmTable)
{
    //2008.2.15　FDB対応。下位互換のため微妙に異なる仕様の調整をする
    $res = array();
    foreach (FDB::getComment($prmTable) as $v) {
        $res[] = array(
            'name' => $v['column'],
            'comment' => $v['comment']
        );
    }

    return $res;
//	$strSQL ="SELECT attname AS name, col_description(attrelid,attnum) AS comment "
//			."FROM pg_attribute "
//			."WHERE attrelid in (SELECT relfilenode FROM pg_class WHERE relname = ".FDB::quoteSmart($prmTable).") "
//			."AND attnum > 0 ORDER BY pg_attribute.attnum";
//
//	//SQL実行
//	$rs = $prmDb->query($strSQL);
//	if (is_false($rs)) {
//		echo $rs->getDebuginfo();
//		return false;
//	}
//	//データを配列に展開
//	$array = array();
//	$row = '';
//	while ($rs->fetchInto($row,DB_FETCHMODE_ASSOC))
//	{
//	     $result[] = $row;
//	}
//
//	return $result;
}

/**
 * コメントを取得し、コメントが無い場合はNULLを返す。単純な表示時には便利。
 * @param object DB $prmDb PEAR DBオブジェクト
 * @param string $prmTable 取得したいテーブルの名前
 * @return array string[] = コメントまたはcolumn名
 * @author Cbase akama
 */
function getComment($prmDb, $prmTable)
{
    $res = getColumnName($prmDb, $prmTable);
    foreach ($res as $v) {
        $result[] = $v["comment"]? $v["comment"]: $v["name"];
    }

    return $result;
}

/*サンプルデータ
    //getColumn(データベース,テーブル名)
    $arydata = getColumn($con,"event");

    //$arydataに"event"のカラムが入る
    print_r($arydata);
*/
