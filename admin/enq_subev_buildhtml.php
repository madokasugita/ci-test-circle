<?php
/*
 * htmlを出力する関数ライブラリ
 * 日付　：2006/04/07
 * 作成者：cbase Akama
 */

/*前提条件
 * 各テンプレートから受け渡される項目
        define("SA_COLUMN_NAME","sa_title,sa_cheader,sa_cbody,sa_cother,sa_cfooter");
        define("MA_COLUMN_NAME","ma_title,ma_cheader,ma_cbody,ma_cother,ma_cfooter");
        define("FA_COLUMN_NAME","fa_title,fa_cheader,fa_cbody,fa_cother,fa_cfooter");
        define("MX_COLUMN_NAME","mx_title,mx_cheader,mx_cbody,mx_cother,mx_cfooter");
    Format配列(saからmxまで全部入ってる)
    処理用連想配列
        title
        cheader
        cbody
        cother
        cfooter
*/

/*
 * Formatの内Modeに該当する変数を取得し返す。
 * @param  Format配列(連想配列)
 * 			Mode(sa,ma,fa,mx)
 * @return 処理用連想配列
 */
function getFormat($prmFormat,$prmMode)
{
    switch ($prmMode) {
        case "sa":
            $aryFormat["title"]  = $prmFormat["sa_title"];
            $aryFormat["header"]= $prmFormat["sa_cheader"];
            $aryFormat["body"]  = $prmFormat["sa_cbody"];
            $aryFormat["other"] = $prmFormat["sa_cother"];
            $aryFormat["footer"]= $prmFormat["sa_cfooter"];
            break;
        case "ma":
            $aryFormat["title"]  = $prmFormat["ma_title"];
            $aryFormat["header"]= $prmFormat["ma_cheader"];
            $aryFormat["body"]  = $prmFormat["ma_cbody"];
            $aryFormat["other"] = $prmFormat["ma_cother"];
            $aryFormat["footer"]= $prmFormat["ma_cfooter"];
            break;
        case "fa":
            $aryFormat["title"]  = $prmFormat["fa_title"];
            $aryFormat["header"]= $prmFormat["fa_cheader"];
            $aryFormat["body"]= $prmFormat["fa_cbody"];
            $aryFormat["footer"]= $prmFormat["fa_cfooter"];
            break;
        case "mx":
            $aryFormat["title"]  = $prmFormat["mx_title"];
            $aryFormat["header"]= $prmFormat["mx_cheader"];
            $aryFormat["body"]  = $prmFormat["mx_cbody"];
            $aryFormat["choise"] = $prmFormat["mx_cchoice"];
            $aryFormat["footer"]= $prmFormat["mx_cfooter"];
            break;
        default:
            return false;
    }

    return $aryFormat;
}

/*
 * 単数/複数回答のhtmlをbuildする。
 * @param  フォーマット配列(連想配列)
 * 			使用するフォーマット(sa,ma対応)
 * 			選択肢(カンマ区切り)
 * 			改行までの選択肢数(省略時1) ※ 省略可能
 * 			記入回答フラグ(0or1)		※ 省略可能
 * 			0:ラジオ 1:プルダウン(0or1)		※ 省略可能
 * @return htmlコード
 */
function buildChoiseList($prmFormat,$prmMode,$prmSelect,$prmSize = 1,$prmFlag = 0)
{
    switch ($prmMode) {
        case "r":
            $strMode = "sa";
            break;
        case "p":
            $strMode = "sa";
            break;
        case "c":
            $strMode = "ma";
            break;
        default:
            return false;
    }
    $strFormat = getFormat($prmFormat,$strMode);
    $strResult = $strFormat["title"];
    $strResult.= $strFormat["header"];
    $strSelect = explode(",",$prmSelect);
    $intSelectCount =count($strSelect);
    $icmax = $intSelectCount / $prmSize;
    if (($prmMode == "r")or($prmMode == "c")) {
        for ($ic=0;$ic<$icmax;++$ic) {
            $strResult.= "<tr>\n";
            $intColumn = $ic * $prmSize;
            for ($i=0;$i<$prmSize;++$i) {
                if ($intColumn+$i < $intSelectCount) {
                    $strResult.= $strFormat["body"];
                } else {
                    $strEmpty = ereg_replace("%%%%choice%%%%","",$strFormat["body"]);
                    $strEmpty = ereg_replace("%%%%form%%%%","",$strEmpty);
                    $strResult.= $strEmpty."\n";
                }
            }
            $strResult.="</tr>\n";
        }
    } else {
        //プルダウン
        $strResult.= "<tr>";
        //tdの数を数える
        $strBody = strtolower($strFormat["body"]);
        ereg("<td", $strBody, $aryRegs);
        //$prmSize分掛ける
        $strSpan = count($aryRegs) * $prmSize;
        //頭のtdを拾う
        $strBpos = strpos ($strBody, "<td");
        $strEpos = strpos ($strBody, ">");
        $strTd = substr($strBody,$strBpos,$strEpos-$strBpos+1);
        //colspan設定
        $strTd = ereg_replace("<td",'<td colspan="'.$strSpan.'"',$strTd);
        //完了
        $strResult.= $strTd."%%%%form%%%%</td>";

        $strResult.="</tr>\n";
    }
    $strResult .= $strFormat["footer"];
    if ($prmFlag>0) {
        $strResult=str_replace("%%%%other%%%%",$strFormat["other"],$strResult);
    } else {
        $strResult=str_replace("%%%%other%%%%","",$strResult);
    }

    return $strResult;
}

/*
 * 数値回答/記入回答のhtmlをbuildする。
 * @param  フォーマット配列(連想配列)
 * 			フォーム横幅
 * 			フォーム行数
 * @return htmlコード
 */
function buildFreeForm($prmFormat,$prmCol,$prmRow)
{
    $strFormat = getFormat($prmFormat,"fa");

    $strResult = $strFormat["title"];
    $strResult.= $strFormat["header"];
    $strResult.= "<tr>\n".$strFormat["body"]."</tr>\n";
    $strResult .= $strFormat["footer"];

    return $strResult;
}

/*
 * マトリクスのhtmlをbuildする。
 * @param  フォーマット配列(連想配列)
 * 			type(0:header,1:body,2:footer)
 * 			選択肢の配列（選択肢数の判断に使用）	※省略可能(type=2の場合のみ)
 * 			選択肢のタイトル(type=0の時のみ有効)	※省略可能
 * @return htmlコード
 */
function buildMatrix($prmFormat,$prmType,$prmChoise="",$prmTitle="")
{
    $strFormat = getFormat($prmFormat,"mx");
    $strChoise = explode(",",$prmChoise);
    $intChoiseCount = count($strChoise);
    switch ($prmType) {
        case 0:
            //headerを書いてbodyへ(headerとbodyの処理は共通)
            $strResult = $strFormat["title"];
            $strResult .= $strFormat["header"];

            for ($i=0;$i<$intChoiseCount;++$i) {
                $strResult .= "  <td>".$strChoise[$i]."</td>\n";
            }
            $strResult .= "</tr>\n";
            //body
            $strResult.= "<tr>\n";
            $strResult .= $strFormat["body"];
            for ($i=0;$i<$intChoiseCount;++$i) {
                $strResult .= $strFormat["choise"];
            }
            $strResult .= "</tr>\n";
            break;
        case 1:
            $strResult = "<tr>\n";
            $strResult .= $strFormat["body"];
            for ($i=0;$i<$intChoiseCount;++$i) {
                $strResult .= $strFormat["choise"];
            }
            $strResult .= "</tr>\n";
            break;
        case 2:
            $strResult = "<tr>\n";
            $strResult .= $strFormat["body"];
            for ($i=0;$i<$intChoiseCount;++$i) {
                $strResult .= $strFormat["choise"];
            }
            $strResult .= "</tr>\n";
            //footerを表示して終了
            $strResult.= $strFormat["footer"];
            break;
        default:
            $strResult = "不正なタイプ指定エラー";
    }

    return $strResult;
}
