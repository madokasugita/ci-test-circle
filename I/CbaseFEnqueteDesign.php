<?php
//データなしの場合はfalseを返す
/**
 * アンケートデザイン関連
 * @package Cbase.Research.Lib
 */

require_once 'QuestionType.php';

/**
 * nullでないかどうかチェック
 * @param string $str 文字列
 * @return bool nullならfalse
 */
function Check_NullText($str)
{
    //半角スペース\n\t\r
    $str=preg_replace("/(\s|\n|\t|\r)/","",$str);
    if (!$str) return false;
    return true;
}

/**
 * 関数の説明
 * @param 型名 引数名 引数の説明
 * @param 型名 引数名 引数の説明
 * @return 型名 戻り値の説明
 */
function Do_CreateDesign($fid=0)
{
    global $design,$array1,$con;
//	$sql = "lock tables ".T_EVENT_SUB." write";
//	$rs=$con->query($sql);
//	if (!$rs) return false;
    $flg=0;
    for ($z=0;$z<count($_POST["seidar"]);++$z) {
        $ar=$_POST["seidar"][$z];
//	foreach ($_POST["seidar"] as $ar) {
        //choiceの列生成用

//		$_POST["tmpseid"]=$ar;
        //データ用意
        for ($i=0;$i<count($array1[0]);++$i) {
            if ($ar==$array1[0][$i]["seid"]) {
                $array=$array1[0][$i];
                $_POST["tmpseid"]=$array1[0][$i];
                break;
            }
        }
        //type1,type2の更新
        list($_POST["tmpseid"]["type1"], $_POST["tmpseid"]["type2"])
            = QuestionType::resolveTypeString($_POST["typeA"]);
        //$_POST["tmpseid"]["type1"]=substr($_POST["typeA"],0,1);
        //$_POST["tmpseid"]["type2"]=substr($_POST["typeA"],1,1);

        //$html1更新
        if ($design[$fid]["type"]=="matrix" && $flg==0) {
            $array["html1"]=Build_DesignFormat($design[$fid]["html1"],"matrix-html1",$flg);
        } elseif ($flg==0) {
            $array["html1"]=Build_DesignFormat($design[$fid]["html1"],"html1",$flg);
        } else {
            $array["html1"]=" ";
        }

        //$html2更新
        $array["html2"]=Build_DesignFormat($design[$fid]["html2_main"],$design[$fid]["type"],$flg);
        if (($z+1)==count($_POST["seidar"])) $array["html2"].=addslashes($design[$fid]["html2_footer"]);
        //DB更新
        Save_SubEnquete("update_nocond",$array);
        $flg=1;
    }
//	$sql = "unlock tables";
//	$con->query($sql);
}

/**
 * 関数の説明
 * @param 型名 引数名 引数の説明
 * @param 型名 引数名 引数の説明
 * @return 型名 戻り値の説明
 */
function Build_DesignFormat($dn,$type,$flg)
{
    switch ($type) {
        case "matrix-html1"://matrixのhtml1を構築するとき
            //parameter差替え
            $html1 = preg_replace_callback("/%%%%(P[0-9]{1,2})%%%%/i","ReplaceParameter",stripslashes($dn));
            $html = preg_replace_callback("/%%%%%%%%(.*)%%%%%%%%/i","BuildChoices",$html1);

            return $html;
            //return addslashes($html);
            break;
        case "html1"://matrix以外のhtml1を構築するとき
            //parameter差替え
            $html = preg_replace_callback("/%%%%(P[0-9]{1,2})%%%%/i","ReplaceParameter",stripslashes($dn));
            //return addslashes($html);
            return $html;
            break;
        case "matrix":
            //parameter差替え
            $html1= preg_replace_callback("/%%%%(P[0-9]{1,2})%%%%/i","ReplaceParameter",stripslashes($dn));
            $html = preg_replace_callback("/%%%%(.*)%%%%/i","BuildChoices1",$html1);

            return $html;
            //return addslashes($html);
            break;
        case "multi":
            //parameter差替え
            $html1= preg_replace_callback("/%%%%(P[0-9]{1,2})%%%%/i","ReplaceParameter",stripslashes($dn));
            $html = preg_replace_callback("/%%%%(.*)%%%%/i","BuildChoices2",$html1);

            return $html;
            //return addslashes($html);
            break;
        case "textbox":
            //parameter差替え
            $html= preg_replace_callback("/%%%%(P[0-9]{1,2})%%%%/i","ReplaceParameter",stripslashes($dn));

            return $html;
            //return addslashes($html);
            break;
        case "pulldown":
            //parameter差替え
            $html= preg_replace_callback("/%%%%(P[0-9]{1,2})%%%%/i","ReplaceParameter",stripslashes($dn));

            return $html;
            //return addslashes($html);
            break;
        case "":
            break;
    }
}

/**
 * 関数の説明
 * @param 型名 引数名 引数の説明
 * @param 型名 引数名 引数の説明
 * @return 型名 戻り値の説明
 */
function ReplaceParameter($match)
{
    global $design;
    $fn = $match[1];
    foreach ($design[ $_POST["format"] ]["parameter"] as $key => $val) {
        if ($fn==$key) {
            $tkey = "F".$_POST["format"]."_".$key;

            return $_POST[$tkey];
        }
    }

    return "%%%%".$fn."%%%%";
}

/**
 * 関数の説明
 * @param 型名 引数名 引数の説明
 * @param 型名 引数名 引数の説明
 * @return 型名 戻り値の説明
 */
function BuildChoices($match) {//マトリックス選択肢表示部分
    global $array1;
    $fn = $match[1];
    $ca = explode(",",$_POST["tmpseid"]["choice"]);
    for ($i=0;$i<count($ca);++$i) {
        $tmphtml.= $fn."\n";
    }

    return $tmphtml;
}

/**
 * 関数の説明
 * @param 型名 引数名 引数の説明
 * @param 型名 引数名 引数の説明
 * @return 型名 戻り値の説明
 */
function BuildChoices1($match)
{
    global $array1;
    $fn = $match[1];
    if ($fn=="title") return "%%%%".$fn."%%%%";
    $ca = explode(",",$_POST["tmpseid"]["choice"]);
    for ($i=0;$i<count($ca);++$i) {
        $tmphtml.= $fn."\n";
    }

    return $tmphtml;
}

/**
 * 関数の説明
 * @param 型名 引数名 引数の説明
 * @param 型名 引数名 引数の説明
 * @return 型名 戻り値の説明
 */
function BuildChoices2($match)
{
    global $array1,$designDefault;
    $fn = $match[1];
    if ($fn=="title") return "%%%%".$fn."%%%%";
    if ($fn=="choice") return "%%%%".$fn."%%%%";
    if ($_POST["tmpseid"]["type2"]=="p") {
        $tmphtml=$fn;
    } else {
        $PRE="F".$_POST["format"]."_";
        $ca = explode(",",$_POST["tmpseid"]["choice"]);
        $tc = $designDefault[ $_POST["format"] ][9];
        for ($i=0;$i<count($ca);++$i) {
            if ($i>0&&($i%$_POST[$PRE."P2"])==0) {
                $tmphtml.='</tr><tr bgcolor="'.$tc.'">';
            }
            $tmphtml.= $fn."\n";
        }
        if ((count($ca)%$_POST[$PRE."P2"])!=0) {//あまりのtdを閉じる
            $tmphtml.= '<td colspan='.count($ca)%$_POST[$PRE."P2"].'>&nbsp</td>';
        }
    }

    return $tmphtml;
}
