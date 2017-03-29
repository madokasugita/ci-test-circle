<?php

$COLUMNLIST_EVENT = array(
     "evid"
    ,"rid"
    ,"name"
    ,"type"
    ,"flgs"
    ,"flgl"
    ,"flgo"
    ,"limitc"
    ,"point"
    ,"mfid"
    ,"htmlh"
    ,"htmlm"
    ,"htmlf"
    ,"url"
    ,"setting"
    ,"sdate"
    ,"edate"
    ,"cdate"
    ,"udate"
    ,"muid"
    ,"htmls"
    ,"htmls2"
    ,"lastpage"
    ,"stylesheet"
);

/**
 * アンケート関係CSVコピークラス(旧Fenquete対応版)
 * 内容：各テーブルをCSVに出力及びCSVから入力する
 * 依存：CbaseFCSV
 * 		CbaseFDBClass(又は新CbaseFDB)
 * 作成日：2006/09/12
 * @package Cbase.Research.Lib
 * @author	Cbase akama
 */
class FEnqueteCopy extends FCSV
{

    /**
     * テーブルのデータを取得
     * @param  string $prmTable テーブル
     * @return array  取得したデータ
     */
    public function getTableData($prmTable)
    {
        //eventの中身を取得
        $aryData = FDB::select($prmTable);
        //aryをcsvに
        //$aryCSV = FEnqueteCSV::getSerializeCSV($aryData);
        $aryCSV = serialize($aryData);
        //取得
        return $aryCSV;
    }

    /**
     * テーブルに格納
     * @param  striong $prmTable テーブル
     * @param  array   $prmCSV   格納するデータ
     * @return mixed   FDB::insertArrayと同じ
     */
    public function setTableData($prmTable, $prmCSV)
    {
        //$aryData = FEnqueteCSV::getUnSerializeArray($prmCSV, false, $COLUMNLIST_EVENT);
        $aryCSV = unserialize($prmCSV);
        foreach ($aryCSV as $key => $val) {
            $aryData[$key] = FDB::escapeArray($val);
        }

        return FDB::insertArray($prmTable, $aryData);
    }
    /**
     * eventテーブルを取得
     * @return eventテーブルの中身
     */
    public function getEvent()
    {
        return FEnqueteCopy::getTableData(T_EVENT);
    }

    /**
     * eventテーブルを格納(未使用)
     * @param  array $prmCSV 格納するデータ
     * @return mixed setTableDataと同
     */
    public function setEvent($prmCSV)
    {
        return FEnqueteCopy::setTableData(T_EVENT);
    }

    /**
     * subeventテーブルを取得
     * @return subeventテーブルの中身
     */
    public function getEvent()
    {
        return FEnqueteCopy::getTableData(T_EVENT_SUB);
    }

    /**
     * subeventテーブルを格納(未使用)
     * @param  array $prmCSV 格納するデータ
     * @return mixed setTableDataと同
     */
    public function setEvent($prmCSV)
    {
        return FEnqueteCopy::setTableData(T_EVENT_SUB);
    }

    /**
     * テスト中のもの(未使用)
     * @param resource $prmPath ファイルのパス
     */
    public function setEventFromFile($prmPath)
    {
        global $COLUMNLIST_EVENT;
        $aryData = FCSV::getArrayFromFile($prmPath, false, $COLUMNLIST_EVENT);
    }
}
