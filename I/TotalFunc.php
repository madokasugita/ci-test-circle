<?php

/*
 * ■使い方
 *
 *
 * $変数 = getAnswerCountAuto ($C_LIST_SEID)　として呼び出す。
 *
 * $変数 に $変数[会社:部署] = 5 のような形式でデータが入る
 *
 * データの運用法など
 * foreachでまわして、部署などの表示は$keyを:でexplodeして使う。
 *
 * ※取得SEIDが変わる際は$C_LIST_SEIDを上書きするか、もしくは引数を変える
 * ※すでにGet_Enqueteしたデータがある場合など効率化の際は下のgetAnswerCount他を使う
 *
 */

//サンプル
/*

require_once '../crm_define.php';
require_once 'CbaseFDB.php';
require_once 'CbaseFDBClass.php';

    //■カウントするSEIDのリスト（入力順に評価される）
    $C_LIST_SEID = array(
        301,
        302,
        304
    );

    $a = getAnswerCountAuto ($C_LIST_SEID);
    ksort($a);

    $strCont="";
    $strCont.='<table>';
    foreach ($a as $k=>$v) {
        $strCont.='<tr>';
        $strCont.='<td>';
        $strCont.=$k;
        $strCont.='</td>';
        $strCont.='<td>';
        $strCont.=$v;
        $strCont.='</td>';
        $strCont.='</tr>';
        }
    $strCont.='</table>';

    echo $strCont;
    */

/**
 * 下の全ての関数を一括で行う
 * @param array $prmList seidのリスト
 * @return	array 右のような配列　array[seid[0]の回答：seid[1]の回答:…] = count
 * @author Cbase akama
 */
function getAnswerCountAuto($prmList)
{
    $aryData = getSubEventAndData($prmList);

    return getAnswerCount ($prmList, $aryData[0], $aryData[1]);
}

/**
 * 引数listに入力されたSEIDの必要なデータだけを取得する(要FDBクラス)
 * @param array $prmList seidのリスト
 * @return	array [0]=>subevent [1]=>subeventdata
 * @author Cbase akama
 */
function getSubEventAndData($prmList)
{
    foreach ($prmList as $val) {
        $aryWhere[] = "seid=".FDB::escape($val);
    }
    $strWhere = " WHERE ".implode(" OR ", $aryWhere);
    $result[0] = FDB::select(T_EVENT_SUB, "seid,choice", $strWhere);
    $result[1] = FDB::select(T_EVENT_SUB_DATA, "seid,choice,serial_no", $strWhere);

    return $result;
}

/*
 * ■以下の関数の使い方
 *
 * Get_SubEnqueteだかGet_Enquete[0]だかでsubeventを取得。Ａとする。
 * Get_AnswerDataではなく、EnqueteClassのgetSubEventDataなどを使い、
 * 集計用の形式などに変換せず、取得したデータそのままの形でsubeventdataを取得。Ｂとする。
 *
 * $変数 = getAnswerCount (Ａ,Ｂ)　として呼び出す。
 *
 * $変数 に $変数[会社:部署] = 5 のような形式でデータが入る
 *
 * データの運用法など
 * foreachでまわして、部署などの表示は$keyを:でexplodeして使う。
 *
 */

/**
 * 下二つの関数を一括で行う
 * @param array $prmList seidのリスト
 * @param array $prmSubEvent SubEventの配列
 * @param array $prmSEData SubEventDataの配列
 * @return	array 右のような配列　array[seid[0]の回答：seid[1]の回答:…] = count
 * @author Cbase akama
 */
function getAnswerCount($prmList, $prmSubEvent, $prmSEData)
{
    return calcAnswerCount ( getAllAnswerData ($prmSubEvent, $prmSEData), $prmList);
}

/**
 * ＤＢから取得したデータをgetAnswerCountで使う形式の全回答データに変換します。
 * @param array $prmSubEvent SubEventの配列
 * @param array $prmSEData SubEventDataの配列
 * @return	array getAnswerCountで使う形式の全回答データ
 * @author Cbase akama
 */
function getAllAnswerData($prmSubEvent, $prmSEData)
{
    foreach ($prmSubEvent as $val) {
//		$aryChoice[$val["seid"]] = explode(",", $val["choice"]);
        $tmp=array();
        $tmp1=explode(",", $val["choice"]);
        for ($i=0;$i<count($tmp1);$i++) {
            $tmp[]=sprintf("%03d", $i).$tmp1[$i];
        }
        $aryChoice[$val["seid"]] = $tmp;
    }

    foreach ($prmSEData as $val) {
        $result[$val["serial_no"]][$val["seid"]] = $aryChoice[$val["seid"]][$val["choice"]];
    }

    return $result;
}

/**
 * 全回答データから特定の質問でクロスした結果を返す
 * @param array $prmAnswer 全回答データ（array(seid=>value, seid=>value)の配列（上記テストデータ参照））
 * @param array $prmSeidAry クロスするseidの配列
 * @return	array 右のような配列　array[seid[0]の回答：seid[1]の回答:…] = count
 * @author Cbase akama
 */
function calcAnswerCount($prmAnswer, $prmSeidAry)
{
    $aryData = array();
    foreach ($prmAnswer as $val) {
        $aryName = array();
        foreach ($prmSeidAry as $valAry) {
            $aryName[] = $val[$valAry];
        }
        if ($aryName) {
            $aryData[implode(":", $aryName)]++;
        }
    }

    return $aryData;
}
