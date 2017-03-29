<?php

/*
    fgeneral.php
 */
/**
 * リサーチ関数集
 * @package Cbase.Research.Lib
 * @version 1.1
 * 2007/08/22 ver1.1 集計時(enq_ttl.php)記入回答一覧へのリンクを追加
 */
/***************************************/

/*
 * Flashからなげられたフォームデータを変換する(文字コード,urlエンコード)
 * @param  フォームデータ($_GET["abc"]などのデータを投入する)
 * 				変換元の文字コード
 * 				変換後の文字コード
 * @return   処理後の文字列
 */
function transDataFromSWF($prmData, $prmCodeFrom = "UTF-8", $prmCodeTo = "EUC-JP")
{
    if ($prmData == "undefined")
        return null; //空のフォームがundefinedで飛んでくるときあり
    $strData = $prmData;
    $strData = urldecode($strData);
    $strData = mb_convert_encoding($strData, $prmCodeTo, $prmCodeFrom);

    return $strData;
}

/**
 * 完了メッセージを出力します。
 * @param string $prmFile コンテンツのhtml
 * @param string $prmEmail メールアドレス
 * @return string HTMLタグ
 */
function printForm($prmFile, $prmEmail)
{
    $strHTML = getStringFromFile($prmFile);

    //TODO 文字コード設定により適切に差し替える

    //	$strHTML=mb_convert_encoding($strHTML,"EUC-JP","SJIS");
    //	$strHTML=mb_eregi_replace("shift-jis|shift_jis","euc_jp",$strHTML);
    $strHTML = mb_ereg_replace("%%%%mail%%%%", $prmEmail, $strHTML);

    return $strHTML;
}

/**
 * 配列の内容をテキストファイルに保存可能な形式に変換します。
 * @param $array $prmArray 保存する配列
 * @param string $prmPath 格納先パス
 */
function setArrayToFile($prmArray, $prmPath)
{
    unlink($prmPath);
    error_log(serialize($prmArray), 3, $prmPath);
}

/**
 * シリアライズしたファイルからデータを取得します。
 * @param string $prmPath 格納先パス
 * @return mixed デシリアライズされたデータ
 */
function getFileToArray($prmPath)
{
    $arySerialize = array ();
    $fp = @ fopen($prmPath, "r"); // シリアライズファイルをオープンします。
    if ($fp) { // 対象ファイルが存在しているかを確認します。
        $strSerialize = fread($fp, filesize($prmPath));
        $arySerialize = unserialize($strSerialize); // アンシリアライズします。
        @ fclose($fp); // ファイルを閉じます。
        //		unlink($prmPath);	// ファイルを削除します。
    }

    return $arySerialize;
}

/**
 * アンケート回答用の引数を生成する
 * @param string $uid ユーザID
 * @param string $rid RID
 * @return string 引数
 */
function Create_QueryString($uid, $rid, $type = 1, $flg = "Z")
{
    //uidとridをmd5で固めてランダム文字列の代わりとする
    $str = md5($uid . $rid);
    $head = substr($str, 0, 1);
    $foot = substr($str, 5, 4);

    return $head . $rid . $flg . $type . $uid . $foot;
}
/**
 * アンケート回答時引数を分解して引数で返す
 * @param string $qs 引数
 * @return array データ
 */
function Resolve_QueryString($qs)
{
    if (strlen($qs) <> LENGTH_QS)
        return false;
    $sa["type"] = substr($qs, 10, 1);
    $sa["rid"] = substr($qs, 1, 8);
    $sa["uid"] = substr($qs, 11, 8);
    $sa["flg"] = substr($qs, 9, 1);
    $str = md5($sa["uid"] . $sa["rid"]);
    $head = substr($str, 0, 1);
    $foot = substr($str, 5, 4);
    if (ereg("[^0-9]", $sa["type"]))
        return false;
    if (ereg("[^0-9a-zA-Z]", $sa["uid"]))
        return false;
    if (ereg("[^0-9a-zA-Z]", $sa["rid"]))
        return false;
    if (ereg("[^0-9a-zA-Z]", $sa["flg"]))
        return false;
    if (USE_HASHURL)
        if ($head != substr($qs, 0, 1) || $foot != substr($qs, -4))
            return false;
    return $sa;
}

function getAuthPageURL($event)
{
    switch (AUTH_QUERY_STRING) {
        case 0 :
            $query = Create_QueryString(Get_RandID(8), $event['rid'], "1", "Z");
            break;
        case 1 :
            $query = $event['rid'];
            break;
        case 2 :
            $query = $event['evid'];
            break;
    }

    return DOMAIN . DIR_MAIN . "auth.php?" . $query;
}

/**
 * ファイルの中身を文字列として取得
 * @param string $prmFile ファイル名
 * @return string ファイルの中身
 *
 */
function getStringFromFile($prmFile, $mode = "")
{
    $fp = fopen($prmFile, "r");
    if (!$fp)
        return false;
    while (!feof($fp)) {
        $tmp = fgets($fp);
        if (trim($tmp) == "")
            continue;
        switch ($mode) {
            case "nl2br" :
                $data .= str_replace(array (
                    "\r\n",
                    "\r",
                    "\n"
                ), "<br/>", $tmp);
                break;
            case "noedit" :
                $data .= $tmp;
                break;
            default :
                $data .= trim($tmp);
                break;
        }
    }
    fclose($fp);

    return $data;
}

/**
 * 稼働率のパーセンテージにより、表示する色を自動算出するファンクション
 * @param int $prmRate 稼働率(%)
 * @return string RGB
 */
function transGetBgColor($prmRate)
{

    if ($prmRate > 100) {
        $prmRate = 100;
    }
    $intNum = 100 - $prmRate;
    $intRate = $intNum / 100;

    // 表示する色の計算(少数箇所の調整で、最も薄い時の色を調整可能です)
    $intColorNum = dechex(255 * $intRate * 0.825);
    $intBgColor = $intColorNum . $intColorNum . $intColorNum;

    return $intBgColor;
}

/**
 * 文字列をHTMLエンティティに変換します。
 * @param  string $prmVal 文字列
 * @return string 文字列
 */
function transHtmlentities($prmVal)
{
    return html_escape($prmVal);
}

/**
 * @return string タグを除去し、不正文字をエスケープしたものを返す
 */
function getPlainText($str)
{
    if (is_array($str)) {
        $res = array();
        foreach ($str as $k => $v) {
            $res[$k] = getPlainText($v);
        }

        return $res;
    }

    return transHtmlentities(strip_tags($str));
}

/**
 * 配列全要素にtransHtmlentities
 */
function transHtmlentitiesAll($array)
{
    return escapeHtml($array);
}

/**
 * 配列全要素にstripSlashes
 */
function stripSlashesAll($array)
{
    return array_reflex($array, 'stripslashes');
}

/**
 * HTMLエンティティ変換された文字列を元に戻します。
 * @param  string $prmVal 文字列
 * @return string 文字列
 */
function transUnHtmlentities($prmVal)
{
    if (NO_CONVERT === 1) {
        return $prmVal;
    }

    return html_unescape($prmVal);
}

/*
    DBのdatetime型を配列に展開する
        $mode	array (DB->配列)
                    $value	「2005-01-01 00:00:00」形式
                    $data	array(	"y"=>2005,
                                    "m"=>1,
                                    "d"=>1,
                                    "h"=>0,
                                    "i"=>0,
                                    "s"=>0)
        $mode	db (配列->DB)
                    $value	array(	"y"=>2005,
                                    "m"=>1,
                                    "d"=>1,
                                    "h"=>0,
                                    "i"=>0,
                                    "s"=>0)
                    $data	「2005-01-01 00:00:00」形式
    */
/**
 * DBのdatetime型を配列に展開する
 * @param string $mode "array" or "db"
 * @param mixied 日付
 * @return mixied 日付
 */
function Convert_Date($mode, $value)
{
    if (!$mode)
        return false;
    if (!$value)
        return false;
    if ($mode == "array") {
        $ar = explode(" ", $value);
        $ar1 = explode("-", $ar[0]);
        $ar2 = explode(":", $ar[1]);

        return array (
            "y" => $ar1[0],
            "m" => (int) $ar1[1],
            "d" => (int) $ar1[2],
            "h" => (int) $ar2[0],
            "i" => (int) $ar2[1],
            "s" => (int) $ar2[2]
        );
    } elseif ($mode == "db") {
        if (!checkdate($value[1], $value[2], $value[0]))
            return false;
        return sprintf("%04d-%02d-%02d %02d:%02d:%02d", $value[0], $value[1], $value[2], $value[3], $value[4], $value[5]);
    }
}

//----------------------------------------------------------
//	ファイルを読み込みファイル内のserializeされた配列を
//	unserializeし配列で返す
//	$file->読み込むファイル
//	$mode->
//			0=>一次元配列でreturn
//			0以外=>二次元配列でreturn
//	戻り値：OK->配列
//		　：NG->false;
//----------------------------------------------------------
/**
 * ファイルを読み込みファイル内のserializeされた配列をunserializeし配列で返す
 * @param string $file 読み込むファイル
 * @param int $mode 0=>一次元配列でreturn
 * @return mixied OK->配列 NG->false
 */
function Do_FileRead($file, $mode = 0)
{
    //$fileをオープン
    $fp = fopen($file, "r+");
    if (!$fp) {
        //オープン失敗時
        return false;
    } else {
        //オープン成功時
        //ロック
        flock($fp, LOCK_SH);
        if ($mode == 0) {
            //一次元配列作成
            while ($dt = fgets($fp)) {
                $array = (unserialize($dt));
            }
        } else {
            //二次元配列作成
            $i = 0;
            while ($dt = fgets($fp)) {
                $array[$i] = unserialize($dt);
                $i++;
            }
        }
        //ロック解除
        flock($fp, LOCK_UN);
        //ファイルを閉じる
        fclose($fp);

        return $array;
    }
}
//乱数生成
/*
function Get_RandID($length=8)
{
    mt_srand((double) microtime()*1000000);
    $newstring="";
    if ($length>0) {
        while (strlen($newstring)<$length) {
            switch (mt_rand(1,3)) {
                case 1: $newstring.=chr(mt_rand(48,57)); break;  // 0-9
                case 2: $newstring.=chr(mt_rand(65,90)); break;  // A-Z
                case 3: $newstring.=chr(mt_rand(97,122)); break; // a-z
            }
        }
    }

    return $newstring;
}
*/
/**
 * ランダムな文字列を返す
 * @param int $length 長さ
 * @param int $mode モード
 * @return string ランダムな文字列
 */
function Get_RandID($length = 8, $mode = 0)
{
    //	mt_srand((double) microtime()*1000000);
    //TODO:CbaseFunction::getRandomStringを利用する形に置換すること
    $newstring = "";
    if ($length > 0) {
        while (strlen($newstring) < $length) {
            //			mt_srand((double) microtime()*1000000);
            switch ($mode) {
                case 1 : //数字のみ
                    $newstring .= chr(mt_rand(48, 57)); // 0-9
                    break;
                case 2 : //英数字
                    switch (mt_rand(1, 3)) {
                        case 1 :
                            $newstring .= chr(mt_rand(48, 57));
                            break; // 0-9
                        case 2 :
                            $newstring .= chr(mt_rand(65, 90));
                            break; // A-Z
                        case 3 :
                            $newstring .= chr(mt_rand(97, 122));
                            break; // a-z
                    }
                    break;
                case 3 : //英語のみエル(小)とアイ(大)は除く
                    switch (mt_rand(2, 3)) {
                        case 2 :
                            $intTmp = mt_rand(65, 90);
                            if ($intTmp == 73)
                                continue; //I
                            $newstring .= chr($intTmp);
                            break; // A-Z
                            break;
                        case 3 :
                            $intTmp = mt_rand(97, 122);
                            if ($intTmp == 108)
                                continue; //l (エル)
                            $newstring .= chr($intTmp);
                            break; // a-z
                            break;
                    }
                    break;
                case 4 : //数字のみ(1-9)
                    $newstring .= chr(mt_rand(49, 57)); // 0-9
                    break;
                case 5 : //英語のエル(小)とアイ(大)及びオー(大小)、数字のゼロは除く
                    unset ($intTmp);
                    switch (mt_rand(1, 3)) {
                        case 1 :
                            $intTmp = mt_rand(48, 57);
                            if ($intTmp == 48)
                                continue; //0
                            $newstring .= chr($intTmp);
                            break; // 0-9
                            break;
                        case 2 :
                            $intTmp = mt_rand(65, 90);
                            if ($intTmp == 73)
                                continue; //I
                            if ($intTmp == 79)
                                continue; //O (オー)
                            $newstring .= chr($intTmp);
                            break; // A-Z
                            break;
                        case 3 :
                            $intTmp = mt_rand(97, 122);
                            if ($intTmp == 108)
                                continue; //l (エル)
                            if ($intTmp == 111)
                                continue; //O (オー)
                            $newstring .= chr($intTmp);
                            break; // a-z
                            break;
                    }
                    break;
                case 6 : //英語のエル(小)とアイ(大)及びオー(大小)、数字のゼロは除く
                    unset ($intTmp);
                    switch (mt_rand(1, 2)) {
                        case 1 :
                            $intTmp = mt_rand(48, 57);
                            if ($intTmp == 48)
                                continue; //0
                            $newstring .= chr($intTmp);
                            break; // 0-9
                            break;
                        case 2 :
                            $intTmp = mt_rand(65, 90);
                            if ($intTmp == 73)
                                continue; //I
                            if ($intTmp == 79)
                                continue; //O (オー)
                            $newstring .= chr($intTmp);
                            break; // A-Z
                            break;
                        case 3 :
                            $intTmp = mt_rand(97, 122);
                            if ($intTmp == 108)
                                continue; //l (エル)
                            if ($intTmp == 111)
                                continue; //O (オー)
                            $newstring .= chr($intTmp);
                            break; // a-z
                            break;
                    }
                    break;
                default :
                    switch (mt_rand(1, 3)) {
                        case 1 :
                            $newstring .= chr(mt_rand(48, 57));
                            break; // 0-9
                        case 2 :
                            $newstring .= chr(mt_rand(65, 90));
                            break; // A-Z
                        case 3 :
                            $newstring .= chr(mt_rand(97, 122));
                            break; // a-z
                    }
                    break;
            }
        }
    }

    return $newstring;
}

/*
 * *****************************************************
    BuildForm()
        入力したhtmlの%%%%で囲まれたフォーム部分を置換して返す

        $formatにデザインhtmlを入力

 *選択肢の並び替え案
 *並び替えた表示値とvalueを対で持つ配列を生成
 *その配列のキーを特定する変数を用意
         $aryTmp[]=array("表示値"=>choiceのx番目,"value"=>choiceのx番目);
         $aryTmpをソート
         $formKey=0をbuildformで宣言

        //replacePartsで以下を取得
         $aryTmp[$formKey]["表示値"]
         $aryTmp[$formKey]["value"]

*案２
*
*array 表示上の順番=>choiceの番号 を宣言
0=>1  1=>0  2=>2

//従来の処理
a value=k
b value=k
c value=k

a value=0
b value=1
c value=2

↓

//arrayを用いた処理
b value =array[k]
a value =array[k]
c value =array[k]

b value =1
a value =0
c value =2

    備考：キャッシュについて　変更の必要あり
        例えばユーザーごとにキャッシュを作る等
        （並びが毎回ランダムになってしまうので）

******************************************************/
/**
 * タグを変換してフォームを作成
 * @param string $format 変換タグの含まれる文章
 * @param int $type フォームの作成タイプ
 * @return string html
 */
function BuildForm($format, $type = 0)
{
    global $choicec, $formc;
    if (SHOW_ABOLITION) {
        echo 'BuildFormは廃止関数です。CbaseEnqueteViewerを使用してください<hr>';
    }

    $choicec = 0;
    $formc = 0;

    //置き換えファンクション実行
    switch ($type) {
        case "1" :
            $html = preg_replace_callback("/%%%%(err)%%%%/i", "ReplaceParts", $format);
            break;
        case "2" : //集計用
            $html = preg_replace_callback("/%%%%([a-zA-Z0-9]+)%%%%/", "ReplaceParts2", $format);
            break;
        default :
            $html = preg_replace_callback("/%%%%([a-zA-Z0-9]+)%%%%/", "ReplaceParts", $format);
            break;
    }
    //リターン
    return $html;
}
/*
 * *****************************************************
    ReplaceParts()
        フォームのinputタグを構築して返す

        $match -> preg_replace_callbackからの情報
        $array -> formアイテムの定義
        $formc -> form毎の出力済み要素数
                    (どの選択肢を出力するかの判断基準)
        $error -> エラーメッセージの配列
******************************************************/
/**
 * BuildFormから呼ばれて、マッチした文字列に応じて適切なものに置き換える
 * @param string $match マッチした文字列
 * @return string 置き換え後の文字列
 */
function ReplaceParts($match)
{
    global $choicec, $formc, $error;

    if (SHOW_ABOLITION) {
        echo 'ReplacePartsは廃止関数です。CbaseEnqueteViewerを使用してください<hr>';
    }

    //pregのマッチ部分の取り出し
    $fn = $match[1];

    //エラーメッセージ出力用
    if (eregi("err", $fn)) {
        if ($error != "") {
            foreach ($error as $er) {
                $msg .= '<li>' . $er;
            }
        }

        return $msg;
    }

    //選択肢の配列生成
    $choices = getRandomChoices($_SESSION["tm"]);
    $ca = $choices["value"];
    $choiceKey = $choices["key"];
    //subevent id
    $seid = $_SESSION["tm"]["seid"];

    //生成
    if ($fn == "title") {
        return $_SESSION["tm"]["title"];
        //return $seid.'/'.$_SESSION["tm"]["title"];
        //return ereg_replace("\n","<br>",$_SESSION["tm"]["title"]);

        //} elseif (ereg("^(ID|id)[0-9]+$",$fn)) {
        //	//テキスト回答を返す
        //	$key="T_".ereg_replace("(ID|id)","",$fn);
        //	//選択回答を返す
        //
        //	//記入欄回答を返す
        //	return mb_convert_encoding($_SESSION[$key],"EUC-JP","SJIS");
    } elseif (ereg("^(ID|id)[0-9]+$", $fn)) {
        $key = ereg_replace("(ID|id)", "", $fn);
        //テキスト回答を返す
        if ($_SESSION["T_" . $key])
            //return mb_convert_encoding($_SESSION["T_".$key],"EUC-JP","SJIS");
            return $_SESSION["T_" . $key];
        //pulldown回答を返す
        //if ($_SESSION["P_".$key]&&!is_array($_SESSION["P_".$key]))
        //	return mb_convert_encoding($_SESSION["P_".$key],"EUC-JP","SJIS");
        //radio,checkbox回答を返す
        $strPChoice = $_SESSION["P_" . $key];
        if (!$strPChoice && $strPChoice != "0")
            return;
        //選択肢データを取得
        foreach ($_SESSION["ed"][0] as $a) {
            if ($a["seid"] != $key)
                continue;
            $tchoice = explode(",", $a["choice"]);
            break;
        }
        //回答選択肢を展開
        $tval = array ();
        if (!is_array($strPChoice)) {
            $tval[] = $tchoice[$strPChoice];
        } else {
            foreach ($strPChoice as $ans) {
                $tval[] = $tchoice[$ans];
            }
        }

        return implode(",", $tval);
    } elseif ($fn == "choice") {
        $dt = $ca[$choicec];
        ++ $choicec;

        return $dt;

    } elseif ($fn == "choiceV") {
        $dt = Char_ToVert($ca[$choicec], " ");
        ++ $choicec;

        return $dt;

    } elseif ($fn == "other") {
        //フォーム名
        $parts[] = ' name="E_' . $seid . '"';
        //スタイルシート
        //if ($array[$fn]["style"]) $parts[] = " style=".$array[$fn]["style"];

        if ($_SESSION["tm"]["rows"] <= 1) { //textboxタイプ
            //タイプ
            $parts[] = ' type="text"';
            //ボックスサイズ
            $parts[] = ' size="' . $_SESSION["tm"]["width"] . '"';
            //値
            //	if (isset($_SESSION["E_".$seid]))
            //$parts[] = ' value="'.stripslashes(mb_convert_encoding($_SESSION["E_".$seid],"EUC-JP","SJIS")).'"';
            $parts[] = ' value="' . stripslashes($_SESSION["E_" . $seid]) . '"';
            //タグ展開
            $tag = '<input';
            foreach ($parts as $pt)
                $tag .= $pt;
            $tag .= '>';
        } else {
            //cols
            $parts[] = ' cols="' . $_SESSION["tm"]["width"] . '"';
            //rows
            $parts[] = ' rows="' . $_SESSION["tm"]["rows"] . '"';
            //タグ展開
            $tag = '<textarea';
            foreach ($parts as $pt)
                $tag .= $pt;
            $tag .= '>';
            //		if (isset($_SESSION["E_".$seid]))
            //if (isset($_SESSION["E_".$seid])||!empty($_SESSION["E_".$seid]))
            //$tag .= stripslashes(mb_convert_encoding($_SESSION["E_".$seid],"EUC-JP","SJIS"));
            $tag .= stripslashes($_SESSION["E_" . $seid]);
            $tag .= '</textarea>';
        }

        return $tag;

    } elseif ($fn == "form") {

        //フォーム組上げ
        unset ($tag);
        unset ($parts);
        //個別対応
        switch ($_SESSION["tm"]["type2"]) {
            case "t" :
                //フォーム名
                $parts[] = ' name="T_' . $seid . '"';
                //スタイルシート/JavaScript
                if ($_SESSION["tm"]["ext"])
                    $parts[] = " " . $_SESSION["tm"]["ext"];

                if ($_SESSION["tm"]["rows"] <= 1) { //textboxタイプ
                    //タイプ
                    $parts[] = ' type="text"';
                    //ボックスサイズ
                    $parts[] = ' size="' . $_SESSION["tm"]["width"] . '"';
                    //値
                    if (!empty ($_SESSION["T_" . $seid]) || $_SESSION["T_" . $seid] == "0")
                        //					if (!empty($_SESSION["T_".$seid]))
                        //$parts[] = ' value="'.stripslashes(mb_convert_encoding($_SESSION["T_".$seid],"EUC-JP","SJIS")).'"';
                        $parts[] = ' value="' . stripslashes($_SESSION["T_" . $seid]) . '"';
                    //タグ展開
                    $tag = '<input';
                    foreach ($parts as $pt)
                        $tag .= $pt;
                    $tag .= '>';
                } else {
                    //cols
                    $parts[] = ' cols="' . $_SESSION["tm"]["width"] . '"';
                    //rows
                    $parts[] = ' rows="' . $_SESSION["tm"]["rows"] . '"';
                    //タグ展開
                    $tag = '<textarea';
                    foreach ($parts as $pt)
                        $tag .= $pt;
                    $tag .= '>';
                    if (!empty ($_SESSION["T_" . $seid]))
                        //$tag.= stripslashes(mb_convert_encoding($_SESSION["T_".$seid],"EUC-JP","SJIS"));
                        $tag .= stripslashes($_SESSION["T_" . $seid]);
                    //if (isset($_SESSION["P_".$seid])) $tag .= stripslashes($_SESSION["P_".$seid]);
                    $tag .= '</textarea>';
                }
                break;
            case "p" :
                //フォーム名
                $parts[] = ' name="' . $seid . '"';
                //スタイルシート/JavaScript
                if ($_SESSION["tm"]["ext"])
                    $parts[] = " " . $_SESSION["tm"]["ext"];

                //タグ展開
                //タグの先頭
                $tag .= '<select';
                //固定フォームの要素展開
                foreach ($parts as $pt)
                    $tag .= $pt;
                $tag .= '>';

                //プルダウンの先頭
                $tag .= '<option value="ng">';
                $tag .= TEXT_PULLDOWN_DEFAULT;
                $tag .= '</option>';

                //ngの値はsessionから削除する
                if ($_SESSION["P_" . $seid] == "ng")
                    unset ($_SESSION["P_" . $seid]);

                //選択肢の展開
                //cond5 seidは一つしか対応していない そのseidは必須であることが前提

                //cond5フォーマット
                //[seid]:[seid's choice]:[choice.choice....],[seid......

                $aryTmpCa = array (); //表示許可される選択肢番号を格納

                if ($_SESSION["tm"]["cond5"]) {
                    //cond5をarray化
                    $tmpCond5 = explode(",", $_SESSION["tm"]["cond5"]);

                    foreach ($tmpCond5 as $tc5) {

                        $aryTc5 = array ();
                        $aryTc5 = explode(":", $tc5);
                        //対象seidの回答が指定の選択肢でなければcontinue
                        if ($_SESSION["P_" . $aryTc5[0]] <> $aryTc5[1])
                            continue;
                        $aryTmpCa = explode(".", $aryTc5[2]);

                        break;
                    }
                    if (count($aryTmpCa) == 0)
                        $aryTmpCa[] = 9999;
                }

                for ($i = 0; $i < count($ca); ++ $i) {
                    /////配列があり表示指定のものがなければcontinue
                    if (count($aryTmpCa) > 0 && !in_array($i, $aryTmpCa))
                        continue;
                    $tag .= '<option value=' . $choiceKey[$i];
                    //一致のとき･･･$tag .= '';
                    if ($choiceKey[$i] == $_SESSION["P_" . $seid] && isset ($_SESSION["P_" . $seid]))
                        $tag .= ' selected';
                    $tag .= '>' . $ca[$i] . '</option>';
                }
                if ($aryTmpCa[0] == 9999)
                    $tag .= '<option value=9999 selected>該当なし</option>';
                //タグの閉じ
                $tag .= '</select>';
                break;
            case ("c") :
                //ダミー文字列入れ
                if ($formc == 0)
                    $tag .= '<input type="hidden" name="DM_' . $seid . '" value="dum">';
                //フォーム名
                $parts[] = ' name="' . $seid . '[]"';
                //スタイルシート/JavaScript
                if ($_SESSION["tm"]["ext"])
                    $parts[] = " " . $_SESSION["tm"]["ext"];
                //タイプ
                $parts[] = ' type="checkbox"';
                //選択肢の展開
                $tag .= '<input';
                foreach ($parts as $pt)
                    $tag .= $pt;
                $tag .= ' value="' . $choiceKey[$formc] . '"';
                //一致のときにchecked(radio)等をつける
                //					if (@in_array(mb_convert_encoding($ca[$i],"SJIS"),$_POST[$fn])) $tag .= ' checked';
                if (@ in_array($choiceKey[$formc], $_SESSION["P_" . $seid]))
                    $tag .= ' checked';
                $tag .= '>';
                //どの選択肢を表示するかの基準の変数に加算
                ++ $formc;
                break;
            case ("r") :
                //ダミー文字列入れ
                if ($formc == 0)
                    $tag .= '<input type="hidden" name="DM_' . $seid . '" value="dum">';
                //フォーム名
                $parts[] = ' name="' . $seid . '[]"';
                //スタイルシート/JavaScript
                if ($_SESSION["tm"]["ext"])
                    $parts[] = " " . $_SESSION["tm"]["ext"];
                //タイプ
                $parts[] = ' type="radio"';
                //選択肢の展開
                $tag .= '<input';
                foreach ($parts as $pt)
                    $tag .= $pt;
                $tag .= ' value="' . $choiceKey[$formc] . '"';
                //一致のときにchecked(radio)等をつける
                //					if (@in_array(mb_convert_encoding($ca[$i],"SJIS"),$_POST[$fn])) $tag .= ' checked';
                if (@ in_array($choiceKey[$formc], $_SESSION["P_" . $seid]))
                    $tag .= ' checked';
                $tag .= '>';
                //どの選択肢を表示するかの基準の変数に加算
                ++ $formc;
                break;

        }

        //リターン
        return $tag;
        //	}
        //	else if(substr($fn,0,3) == "out")
        //	{
        //			//値を選択肢に置き換えて出力する
        //			return ReplaceOut1($fn);
    } else {
        if (substr($fn, 0, 4) == "sess") {
            return $_SESSION[substr($fn, 4)];
        }
    //	else if(substr($fn,0,5) == "info:")
    //	{
    //		return ReplaceInfo();
    //	} else {
        return "<font color=red>%%%%設定エラー%%%%</font>";
    } //choice/form
}

//Show_EnqueteTotal用(集計用)
/**
 * BuildFormから呼ばれて、マッチした文字列に応じて適切なものに置き換える
 * @param string $match マッチした文字列
 * @return string 置き換え後の文字列
 */
function ReplaceParts2($match)
{
    global $choicec, $formc, $error;
    if (SHOW_ABOLITION) {
        echo 'ReplaceParts2は廃止関数です。enq_ttl::CbaseEnqueteTotalRenderを使用してください<hr>';
    }

    //pregのマッチ部分の取り出し
    $fn = $match[1];

    //選択肢の配列生成
    $ca = explode(",", $_SESSION["tm"]["choice"]);
    //subevent id
    $seid = $_SESSION["tm"]["seid"];

    //生成
    if ($fn == "title") {
        return $_SESSION["tm"]["title"];
        //return $seid.'/'.$_SESSION["tm"]["title"];
        //return ereg_replace("\n","<br>",$_SESSION["tm"]["title"]);

    } elseif ($fn == "choice") {
        $dt = $ca[$choicec];
        ++ $choicec;

        return $dt;

    } elseif ($fn == "other") {
        //スタイルシート
        return "記入回答"; //ver1.1/

    } elseif ($fn == "form") {

        //////////////////結果取得
        if ($_SESSION["tm"]["type1"] <> 4) {
            //$seid;
            $dar = Get_TTLSubevent($seid);
        }

        $tc = $dar ? array_sum($dar) : 0;
        //フォーム組上げ
        $tag = '';
        $parts = array ();
        //個別対応
        switch ($_SESSION["tm"]["type2"]) {
            case "t" :
                $SID = getSID();
                $tag .=<<<HTML
<a href="enq_ttl_fa_view.php?{$SID}&seid={$seid}" target="_blank">内容を見る</a>
HTML;

                break;
            case "p" :
                //フォーム名
                $parts[] = ' name="' . $seid . '"';
                //スタイルシート
                //if ($array[$fn]["style"]) $parts[] = " style=".$array[$fn]["style"];

                //タグ展開
                //タグの先頭
                $tag .= '<select';
                //固定フォームの要素展開
                foreach ($parts as $pt)
                    $tag .= $pt;
                $tag .= '>';

                //プルダウンの先頭
                $tag .= '<option value="ng">';
                $tag .= TEXT_PULLDOWN_DEFAULT;
                $tag .= '</option>';
                //選択肢の展開
                for ($i = 0; $i < count($ca); ++ $i) {
                    $tag .= '<option value=' . $i;
                    //一致のとき･･･$tag .= '';
                    if ($i == $_SESSION["P_" . $seid] && isset ($_SESSION["P_" . $seid]))
                        $tag .= ' selected';
                    $tag .= '>' . $ca[$i];
                    //回答数表示
                    $tag .= '(' . $dar[$i] . '回答)</option>';
                }
                //タグの閉じ
                $tag .= '</select>';

                break;
            case "c" :
                //formcが選択肢の番号
                //回答数を表示
                $tmprate = (($tc ? $dar[$formc] / $tc : 0) * 100);
                $bgc = transGetBgColor($tmprate);
                $tag .= '<font size=2>' . $dar[$formc] . '</font><br>';
                $tag .= '<font size=2 color="' . $bgc . '">' . sprintf("%01.1f", $tmprate) . '%</font>';
                ++ $formc;
                break;
            case "r" :
                //formcが選択肢の番号
                //回答数を表示
                //$tag.= $dar[$formc];
                unset ($tmprate);
                unset ($bgc);
                $tmprate = (($tc ? $dar[$formc] / $tc : 0) * 100);
                $bgc = transGetBgColor($tmprate);
                $tag .= '<font size=2>' . $dar[$formc] . '</font><br>';
                $tag .= '<font size=2 color="' . $bgc . '">' . sprintf("%01.1f", $tmprate) . '%</font>';
                ++ $formc;
                break;
        }

        //リターン
        return '<font color=green>' . $tag . '</font>';
    } else {
        return "<font color=red>%%%%設定エラー%%%%</font>";
    } //choice/form
}

/**
 * チェックボックスやラジオボタンのフォームタグを生成して返す
 * @param int $type タイプを指定する 0:1列ずつ 1:2列ずつ 2:3列ずつ
 * @param array $choice 選択肢を配列で
 * @return string HTMLタグ
 */
function BuildFormTag($type = "0", $choice)
{
    if ($type <> "T" && !$choice)
        return "";
    $c = explode(",", $choice);
    //フォーマット読み込み
    $file = "format" . $type . ".txt";
    $fd = Read_File($file, 0);
    if ($type == "T") {
        foreach ($fd as $f) {
            $html .= $f;
        }

        return $html;
    }
    $html .= $fd[0];
    if ($type == 0) {
        //type=0 1列ずつ表示
        for ($i = 0; $i < count($c); $i++) {
            $html .= $fd[1] . "\n";
        }
    } elseif ($type == 1) {
        //type=1 2列ずつ表示
        for ($i = 0; $i < count($c); $i++) {
            if (($i % 2) == 0 && $i <> 0)
                $html .= "</tr><tr>";
            $html .= $fd[1] . "\n";
        }
        //あまりをどうするか
        if (($c % 2) <> 0)
            $html .= "<td>&nbsp;</td>";
    } else {
        //type=2 3列ずつ表示
        for ($i = 0; $i < count($c); $i++) {
            if (($i % 3) == 0 && $i <> 0)
                $html .= "</tr><tr>";
            $html .= $fd[1] . "\n";
        }
        //あまりをどうするか
        if (($c % 3) == 1) {
            $html .= "<td>&nbsp;</td>";
        } elseif (($c % 3) == 2) {
            $html .= "<td colspan=2>&nbsp;</td>";
        }
    }
    $html .= $fd[2];

    return $html;
}

/**
 * ファイルの中身を配列にして返す
 * @param string $file ファイル名
 * @param int $mode 未使用
 * @return array 配列
 */
function Read_File($file, $mode = 0)
{
    //$mode=0 行毎の配列データで返す
    //$fileをオープン
    $fp = fopen($file, "r+");
    if (!$fp) {
        //オープン失敗時
        return false;
    } else {
        //オープン成功時
        if ($mode == 0) {
            //一次元配列作成
            while ($dt = fgets($fp)) {
                $array[] = $dt;
            }
        }
    }
    //ファイルを閉じる
    fclose($fp);

    return $array;
}

/**
 * 文字列を1文字ずつ区切って縦書きにする
 * @param string $_szStr 対象の文字列
 * @param string $_szInsertStr 区切り文字( <br> など)
 * @return string 縦書きになった文字列
 */
function Char_ToVert($_szStr, $_szInsertStr)
{
    $szStr = "";
    for ($i = 0; $i < strlen($_szStr); $i++) {
        if (ereg("[-!#$%&\'*+\\./=?^_`{|}~@:;<>0-9A-Z_a-z]+", substr($_szStr, $i, 1)) == false) {
            $szStr .= substr($_szStr, $i, 2) . $_szInsertStr; // 2byte
            ++ $i;
        } else { // 1byte
            $szStr .= substr($_szStr, $i, 1) . $_szInsertStr;
        }
    }

    return $szStr;
}

/**
 * 専用テーブルを用意して、ユニークなIDを管理する。FDB依存
 * @param string $prmStrTable テーブル名
 * @param string $prmStrColumn カラム名
 * @param int $prmLength IDの文字数
 * @return	stginr ユニークなID
 *
 *
 * ユニーク制限がついたカラムにinsertを試み、失敗したら再度IDを生成しなおす。
 */
function getUniqueIdWithTable($prmStrTable = T_UNIQUE_SERIAL, $prmStrColumn = "serial_no", $prmLength = "8")
{
    global $con;
    for ($i = 0; $i < 10000; $i++) {
        $uniqueId = Get_RandID($prmLength);
        $con->query("SAVEPOINT sp1;");
        $rs = $con->query("insert into {$prmStrTable} ({$prmStrColumn}) values('" . $uniqueId . "')");
        if (FDB :: isError($rs) && $rs->getCode() == -3) {
            $con->query("ROLLBACK TO sp1;");
            $flag = false;
            continue;
        }
        $flag = true;
        break;
    }
    if (!$flag) {
        print "ユニークIDなし";
        exit;
    }

    return $uniqueId;
}

function getUniqueIdWithTable_UID($prmStrTable,$prmStrColumn)
{
    global $con;
    for ($i = 0; $i < 10000; $i++) {
        $uniqueId = getRandomString1(4).getRandomString2(5);
        $con->query("SAVEPOINT sp1;");
        $rs = $con->query("insert into {$prmStrTable} ({$prmStrColumn}) values('" . $uniqueId . "')");
        if (FDB :: isError($rs) && $rs->getCode() == -3) {
            $con->query("ROLLBACK TO sp1;");
            $flag = false;
            continue;
        }
        $flag = true;
        break;
    }
    if (!$flag) {
        print "ユニークIDなし";
        exit;
    }

    return $uniqueId;
}


function getRandomString1($length = 8, $nglist = array (), $seed = null)
{
    if ($seed) {
        srand(hexdec(substr(md5($seed), 0, 11)));
    }
    $chars = range('a', 'z');
    $chars = array_diff($chars, $nglist); //使用しない文字を取り除く
    $string = "";
    for ($i = 0; $i < $length; $i++)
        $string .= $chars[array_rand($chars)];

    return $string;
}

function getRandomString2($length = 8, $nglist = array (), $seed = null)
{
    if ($seed) {
        srand(hexdec(substr(md5($seed), 0, 11)));
    }
    $chars=array();
    $chars = array_merge($chars, range('0', '9'));
    $chars = array_diff($chars, $nglist); //使用しない文字を取り除く
    $string = "";
    for ($i = 0; $i < $length; $i++)
        $string .= $chars[array_rand($chars)];

    return $string;
}

/**
 * 指定したsubeventからchoiceを読み込みランダムソートを適用して取得するが、
 * 既に読み込み済みであればそれを返す(つまり同じ質問を連続で処理している間はランダム結果が固定)
 * @param array $subevent 対象subevent
 * @return array 読み出された配列と変換テーブル(value, key)
 * @author Cbase akama
 */
function getRandomChoices($subevent, $user=array())
{
    global $NowLoadedChoices, $NowLoadedSeid;
    if ($NowLoadedSeid !== $subevent["seid"]) {
        $NowLoadedChoices = randomArraySort(getEnqueteChoice($subevent, $user), $subevent["randomize"]);
        if (FError::is($NowLoadedChoices)) {
            echo $NowLoadedChoices->getInfo();
            exit;
        }

        $NowLoadedSeid = $subevent["seid"];
    }

    return $NowLoadedChoices;
}

/**
 * 配列をルールに従って並び替えて返す
 *
 * @param array $array 並び替える配列
 * @param string $rule 並び替えルール文字列（1-2,3-6など）
 * @param string $mode 拡張用。eventなど
 * @return array 並び替えた配列
 * @author Cbase akama
 */
function randomArraySort($array, $rule, $mode = "")
{
    global $Grobal_Randomize_Conds;
    if (!$rule) {
        $result["value"] = $array;
        $result["key"] = array_keys($array);

        return $result;
    }
    //randomizeを解凍

    $randomize = getRandomizeFormat($rule);
    if (FError :: is($randomize)) {
        return new CbaseException($randomize->getInfo());
    }

    $nextNum = 0;
    $rindex = 0;
    $rdmz = $randomize[$rindex];
    $data = array ();
    $max = sizeof($array);
    while ($rdmz) {
        if (1000000 < $rindex)
            exit;
        for ($i = $nextNum; $i < $rdmz[0]; $i++) {
            $data[$i] = $array[$i];
        }
        $sort = array ();

        if ($mode == "subevent")
            $page = $array[$rdmz[0]]["page"];
        $maxlen = min($max -1, $rdmz[1]);
        for ($i = $rdmz[0]; $i <= $maxlen; $i++) {
            if (($mode == "subevent" && $page != $array[$i]["page"]) || $page == "error") {
                return new CbaseException(FError :: get("RANDOMIZE_STEP_PAGE"));
            }
            $sort[$i] = $array[$i];
        }

        //ランダム並び替え（簡易）
        uasort($sort, "callbackRandomSort");
        $data += $sort;

        //次があればセット
        ++ $rindex;
        $nextNum = $rdmz[1] + 1;
        $rdmz = $randomize[$rindex];
    }

    for ($i = $nextNum; $i < $max; $i++) {
        $data[$i] = $array[$i];
    }

    //連想配列を直す
    //	foreach ($data as $key => $val)
    //	{
    //		$res[$val["key"]] = $val["val"];
    //	}

    //番号を振る
    $i = 0;
    foreach ($data as $key => $val) {
        $result["value"][$i] = $val;
        $result["key"][$i] = $key;
        ++ $i;
    }

    return $result;
}

/**
 * ランダムソートのコールバック関数
 * @param mixed $a usortからの引数
 * @param mixed $b usortからの引数
 * @return int -1 <= 0 <= 1
 * @author Cbase akama
 */
function callbackRandomSort($a, $b)
{
    return mt_rand(-1, 1);
}

/**
 * ルール文字列からランダマイズフォーマットを取得。エラーチェックも兼ねる。
 * @param string $rule ランダマイズ文字列
 * @return 正しければarray[][0/1]の連想配列、正しくないフォーマットならfalse
 * @author Cbase akama
 */
function getRandomizeFormat($rule)
{
    //$randomize[] = explode("-", $rule);
    if (preg_match("/^\d*-\d*(,\d*-\d*)*$/", $rule) == 0) {
        return new CbaseException(FError :: get("RANDOMIZE_INVALID"));
    }
    $r = explode(",", $rule);
    foreach ($r as $val) {
        $randomize[] = explode("-", $val);
    }
    for ($i = 0; $i < sizeof($randomize); $i++) {
        if ($randomize[$i +1]) {
            if ($randomize[$i +1][0] <= $randomize[$i][1]) {
                return new CbaseException(FError :: get("RANDOMIZE_REPEAT"));
            }
        }
        if ($randomize[$i][1] < $randomize[$i][0]) {
            return new CbaseException(FError :: get("RANDOMIZE_REVERSE"));
        }

    }

    return $randomize;
}

/**
 * 画面下部のcopyrightを設定する
 * ただしcopyright.jsを用いている場合も有るので、変更の際はそちらも参照
 * またindexについてはテンプレを利用しているためそちらを書き換え
 */
function getCopyrightMessage()
{
    return '&copy; 2008 Cbase Corporation.';
}

/**
 * 複製作成時の名前取得
 */
function getCopyName($name)
{
    if (preg_match("/^\[複製(.*?)\](.*)$/", $name, $matches)==0) {
        // 複製1回目
        return "[複製]".$name;
    } else {
        // 複製2回目以降
        if(++$matches[1]==1)	$matches[1]++;

        return "[複製".$matches[1]."]".$matches[2];
    }
}
