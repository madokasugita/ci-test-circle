<?php
/*
 * PG名称：検索用SQL文を出力するプログラム
 * 日  付：2005/04/14
 * 作成者：cbase Akama
 */

define('DIR_ROOT', "../");
require_once(DIR_ROOT."crm_define.php");
require_once(DIR_LIB.'CbaseFEnquete.php');
require_once(DIR_LIB.'CbaseFCondition.php');

require_once(DIR_LIB.'func_rtnclm.php');
require_once(DIR_LIB.'func_pd.php');
require_once(DIR_LIB.'enq_design_lib.php');
require_once(DIR_LIB.'enq_sqls_func.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360Design.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

//-----------------------------------------------------------------
//定数定義

//戻り先（トップ画面）のプログラム名
define("C_BACK_PG","enq_cond.php");
//編集エディタのpg名
define("C_EDIT_PG","enq_sqls_edit.php");

//-----------------------------------------------------------------

//実行部分

switch ($_POST["main"]) {
    case "enter":
        $showHtml = transEnter();
        break;
    case "c_first":
        $showHtml = transCondFirst();
        break;
    case "c_second":
        //POST代入
        $_SESSION["condtemp"]["evid"] = $_POST["evid"];
        $showHtml = getHtmlCondThird($_SESSION["condtemp"]);
        break;
    case "c_third":
        //POST代入
        if ($_POST["seidrb"]=="nodata") {
            $_SESSION["condtemp"]["seid"] = "";
            $showHtml = getHtmlCondEvent($_SESSION["condtemp"]);
        } else {
            $_SESSION["condtemp"]["seid"] = $_POST["seid"];
            $showHtml = getHtmlCondSubEvent($_SESSION["condtemp"]);
        }
        break;
    case "c_mst":
        $showHtml = transCondMst();
        break;
    case "c_subevent":
        $showHtml = transCondSubEvent();

        break;
    case "c_event":
        $showHtml = transCondEvent();
        break;
    case "c_final":
        //cond登録後の処理
        $_SESSION["data"]["cond"][$_SESSION["key"]] = $_SESSION["condtemp"];
        $_SESSION["condtemp"] = "";
        $showHtml = getHtmlFirst($_SESSION["data"]);
        break;
    default:
        $showHtml = transStart();
}

$objHtml =& new ResearchAdminHtml("複合条件リスト");
echo $objHtml->getMainHtml($showHtml);
exit;

//------------------------------------------------------------------

/*
 * 開いた瞬間の処理
 * @param
 * @return html
 */
function transStart()
{
    //もしもセッションに以前のデータが残っていたら消す
    $_SESSION["data"] = "";

    //本来idは呼び出し元から送られてくる
    if (isset($_GET["cnid"])) {
        $_SESSION["data"]["cnid"] = $_GET["cnid"];
        if (isset($_GET["copy"])) {
            $strCondData = Get_Condition("id",$_GET["copy"], $_SESSION['muid']);
            $strCondData = $strCondData[0];
            if (isset($strCondData["pgcache"])) {
                $_SESSION["data"] = unserialize($strCondData["pgcache"]);
            }
            $_SESSION["data"]["cnid"] = $_GET["cnid"];
            $_SESSION["new"] = true;
        } else {
            if ($_GET["cnid"] <> "new") {
                $strCondData = Get_Condition("id",$_GET["cnid"], $_SESSION['muid']);
                //条件に一致するデータは一件のみなので一件取り出す
                $strCondData = $strCondData[0];
                if (isset($strCondData["pgcache"])) {
                    $_SESSION["data"] = unserialize($strCondData["pgcache"]);
                    $_SESSION["new"] = false;
                } else $_SESSION["new"] = true;
            } else {
                $_SESSION["new"] = true;
            }
        }

        return getHtmlFirst($_SESSION["data"]);
    } else {
        header("Location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/".C_BACK_PG.'?'.getSID());
        exit;
    }

}

/*
 * 初期画面からボタンを押した時の処理
 * @param
 * @return html
 */
function transEnter()
{
    //POSTデータを代入
    if($_POST["nameform"]) $_SESSION["data"]["name"] = strip_tags($_POST["nameform"]);
    if($_POST["oprb"]) $_SESSION["data"]["op"] = $_POST["oprb"];

    $strState="";

    if (isset($_POST["final_x"]) || isset($_POST["final_y"])) {
        $strState="final";
    } elseif (isset($_POST["show_x"]) || isset($_POST["show_y"])) {
        $strState="show";
    } elseif (isset($_POST["download_x"]) || isset($_POST["download_y"])) {
        $strState="download";
    } elseif (isset($_POST["back_x"]) || isset($_POST["back_y"])) {
        $strState="back";
    } elseif (isset($_POST["dataedit_x"]) || isset($_POST["dataedit_y"])) {
        $strState="dataedit";
    } else {
        foreach ($_POST as $key => $value) {
            if (substr($key,0,3) == "del") {
                $strState="del";
                $strKey = substr($key,0,strlen($key)-2);
                $_SESSION["key"]=ereg_replace("del","",$strKey); //$key=編集番号
                break;
            }
            if (substr($key,0,4) == "cond") {
                $strState="edit";
                $strKey = substr($key,0,strlen($key)-2);
                $_SESSION["key"]=ereg_replace("cond","",$strKey); //$key=編集番号
            break;
            }
        }
    }
    //処理部分
    switch ($strState) {
        case "edit":
            $_SESSION["condtemp"] = $_SESSION["data"]["cond"][$_SESSION["key"]];
            $showHtml = getHtmlCondFirst($_SESSION["condtemp"]);
            break;
        case "del":
            $aryData =$_SESSION["data"];
            unset($aryData["cond"][$_SESSION["key"]]);
            $_SESSION["data"]["cond"] = array_values($aryData["cond"]);
            $showHtml = getHtmlFirst($aryData);
            break;
        case "final":
            $strSendData = transDBResist();
            $showHtml = getHtmlFinal($strSendData,$_SESSION["data"]["cond"],$_SESSION["data"]["op"]);
            break;
        case "show":
            //現在の条件で表示
            $showHtml = getHtmlCsvTable($_SESSION["data"]);
            break;
        case "download":
            //現在の条件で出力
            $strCsv = getCsvData(getSQLSelect(getSQL($_SESSION["data"])));
            transOutput($strCsv);
            break;
        case "back":
            $showHtml = getHtmlFirst($_SESSION["data"]);
            break;
        case "dataedit":
            header("Location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/".C_EDIT_PG.'?'.getSID());
            exit;
            break;
    }

    return $showHtml;
}

/*
 * 条件分岐設定1ページ目の選択後の処理
 * @param
 * @return html
 */
function transCondFirst()
{
    //POSTデータ代入
    $_SESSION["condtemp"]["type"] = $_POST["typerb"];
    if ($_POST["typerb"] == "mst") {
        $showHtml = getHtmlCondMst($_SESSION["condtemp"]);
    } else {
        $showHtml = getHtmlCondSecond($_SESSION["condtemp"]);
    }

    return $showHtml;
}

/*
 * 条件分岐設定マスタ条件の選択後の処理
 * @param
 * @return html
 */
function transCondMst()
{
        //POST代入
        if (($_POST["column"] == "")or($_POST["textform"]=="")) {
            $showHtml = "内容が不足しています";
        } else {
            $_SESSION["condtemp"]["column"] = $_POST["column"];
            $_SESSION["condtemp"]["comp"] = $_POST["comp"];
            $_SESSION["condtemp"]["strcond"] = $_POST["textform"];
            $_SESSION["condtemp"]["day"]= "";
            $_SESSION["condtemp"]["SQL"] = getCondSQL($_SESSION["condtemp"]);
            $showHtml = getHtmlCondFifth($_SESSION["condtemp"]);
        }

        return $showHtml;
}

/*
 * 条件分岐設定サブイベント条件の選択後の処理
 * @param
 * @return html
 */
function transCondSubEvent()
{
        //POST代入
        $_SESSION["condtemp"]["column"] = $_POST["column"];
        if ($_POST["column"] == "strcond") {
            $_SESSION["condtemp"]["comp"] = $_POST["comp"];
            $_SESSION["condtemp"]["strcond"] = $_POST["textform"];
        } else {
            $_SESSION["condtemp"]["comp"] = "";
            $_SESSION["condtemp"]["strcond"] = "";
        }
        $_SESSION["condtemp"]["day"]= "";
        $_SESSION["condtemp"]["SQL"] = getCondSQL($_SESSION["condtemp"]);
        $showHtml = getHtmlCondFifth($_SESSION["condtemp"]);

        return $showHtml;
}

/*
 * 条件分岐設定イベント条件の選択後の処理
 * @param
 * @return html
 */
function transCondEvent()
{
    //POST代入
    //年から順にあれば代入、なければ無視
    $bolDay = false;
    $aryDay = array();
    switch ($_POST["column"]) {
        case "cdate":
            if ($_POST["year1"] <> "") {
                $bolDay = true;
                $aryDay["b_year"] =$_POST["year1"];
                $aryDay["b_comp"] =$_POST["comp1"];
                if ($_POST["month1"] <> "") {
                    $aryDay["b_month"]=$_POST["month1"];
                    if($_POST["day1"] <> "") $aryDay["b_day"]=$_POST["day1"];
                }
            }
            if ($_POST["year2"] <> "") {
                $bolDay = true;
                $aryDay["e_year"] =$_POST["year2"];
                $aryDay["e_comp"] =$_POST["comp2"];
                if ($_POST["month2"] <> "") {
                    $aryDay["e_month"]=$_POST["month2"];
                    if($_POST["day2"] <> "") $aryDay["e_day"]=$_POST["day2"];
                }
            }
            $_SESSION["condtemp"]["day"]= $aryDay;
            break;
        case "true":
                $_SESSION["condtemp"]["day"]= "";
            break;
        case "false":
                $_SESSION["condtemp"]["day"]= "";
            break;
    }
    $_SESSION["condtemp"]["column"] = $_POST["column"];
    //使わない部分を初期化
    $_SESSION["condtemp"]["comp"] = "";
    $_SESSION["condtemp"]["strcond"] = "";
    $_SESSION["condtemp"]["SQL"] = getCondSQL($_SESSION["condtemp"]);
    $showHtml = getHtmlCondFifth($_SESSION["condtemp"]);

    return $showHtml;
}

//------------------------------------------------------------------

/*
 * タイトル表示部のテンプレを取得
 * @param  	現在の位置(0で無し)
 * 			str"タイトル"
 * 			str"コメント"
 * 			トップか設定ページか(0or1)
 * @return html
 */
function getHtmlTop($prmNum,$prmTitle,$prmMsg,$prmType=1)
{

    $strResult = '
<form action='.getPHP_SELF().'?'.getSID().' method="post" encType=multipart/form-data>
';
    if ($prmType==0) {
        $strResult.= D360::getTitle("複合条件リスト","複合条件リストの詳細設定をします。",'comment');
    } else {
        $strResult.= D360::getTitle("検索条件設定","検索条件を設定します。",'comment');
    }

    //タイトル部分

    if ($prmNum > 0) {
        $strResult.= '
        <table width="430" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td valign="middle"> <font color="#666666" size="2">';

        $i=1;
        while ($i<$prmNum) {
            $strResult.= $i." &gt; ";
            $i++;
        }
        $strResult.= $prmNum.".".$prmTitle.'</font></td>
            </tr>
        </table>
        <br>
        <br>
';
    }

    return $strResult;
}

/*
 * タイトル表示部のタグを閉じるhtmlを取得
 * @param
 * @return html
 */
function getHtmlBottom()
{
    $strResult = '
</form>
';

    return $strResult;

}

/*
 * 見出しのテンプレを取得
 * @param  	見出しのタイトル
 * 			見出し横のコメント
 * @return html
 */
function getHtmlSub($prmTitle,$prmMsg)
{
    $strHtml.=getDlHtmlSubject($prmTitle,$prmMsg);

    return $strHtml;
}

/*
 * 初期画面を表示。
 * @param  dataデータ
 * @return html
 */
function getHtmlFirst($prmData)
{
    $sName = transHtmlentities($prmData["name"]);
    $strHtml = getHtmlTop(0,'基本設定','※複合条件リストで表示される名前を設定します。',0)
.'
        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td width="10">　</td>
            <td width="124"><font size="2">ＩＤ</font></td>
            <td width="20">　</td>
            <td width="240"> '.$prmData["cnid"].'</td>
            </tr>
            <tr>
            <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="5"></td>
            </tr>
            <tr>
            <td align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td>　</td>
            <td><font size="2">名称</font></td>
            <td>　</td>
            <td> <input type="text" name="nameform" value="'.$sName.'" /> </td>
            </tr>
            <tr>
            <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="5"></td>
            </tr>
            <tr>
            <td align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td>　</td>
            <td><font size="2">条件連結</font></td>
            <td>　</td>
';
    $strHtml2.= '

            <td><input type="radio" name="oprb" value="AND" />
                AND　
                <input type="radio" name="oprb" value="OR" />
                OR
            </td>
';
    $strHtml.= $prmData["op"]? ereg_replace('(value="'.$prmData["op"].'")','\1 checked',$strHtml2):ereg_replace('(value="AND")','\1 checked',$strHtml2);
    $strHtml.='
            </tr>
        </table>
        <br>
'.'
        <table width="430" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="27" valign="middle" align="left">
                <img src="'.DIR_IMG.'icon_inf.gif" width="13" height="13">
            </td>
            <td width="115" valign="middle"><font size="2">検索条件追加</font></td>
            <td width="168" valign="middle"><font color="#999999" size="2">※新規に条件を追加します。</font></td>
            <td width="120" valign="middle"><input type="image" src="'.DIR_IMG.'add2.gif" width="120" height="20" name="cond'.count($prmData["cond"]).'" /></td>
            </tr>
            <tr valign="top">
            <td height="2" colspan="4">
            <table width="430" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
                </tr>
            </table>
            </td>
            </tr>
        </table>
        <br>
';
    //condがないなら消す
    if (count($prmData["cond"])>0) {
        $strHtml.= getHtmlSub("検索条件一覧","※検索条件一覧です。");
        $strHtml.='
        <table width="450" border="0" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
            <tr>
            <td width="270" valign="bottom">
            <table width="450" border="0" cellpadding="0" cellspacing="1">
                <tr bgcolor="#CCCCCC" align="center">
                <td>
                    <font size="2">条件</font></td>
                <td width="80">
                    <font size="2"> 再編集</font></td>
                </tr>
';
        $i=0;
        $strBgcolor[0]= "bgcolor = #ffffff";
        $strBgcolor[1]= "bgcolor = #f6f6f6";
        foreach ($prmData["cond"] as $key => $value) {
            $i++;
            $strHtml.= '
                <tr '.$strBgcolor[$i % 2].'>
                <td><font size="2">'.getSQLComment($value["SQL"]).'</font></td>
                <td align="center">
                    <table width="80" border="0" align="center" cellpadding="0" cellspacing="0">
                        <tr align="center">
                        <td width="40">
                            <input type="image" src="'.DIR_IMG.'edit.gif" width="35" height="17" border="0" align="middle" name="cond'.$key.'" />
                        </td>
                        <td width="40">
                            <input type="image" src="'.DIR_IMG.'del.gif" width="35" height="17" border="0" align="middle" name="del'.$key.'" />
                        </td>
                        </tr>
                    </table>
                </td>
                </tr>';
        }

        $strHtml.= '
            </table>
            </td>
            </tr>
        </table>
        <br>
';
    }
    $strHtml.= '
        <br>
        <table width="430" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td align="center">
                <input type="image" src="'.DIR_IMG.'save.gif" width="100" height="20" name="final" />
            </td>
            </tr>
        </table>

        <input type="hidden" name="main" value="enter">
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 条件作成画面1を表示。
 * @param  condデータ
 * @return html
 */
function getHtmlCondFirst($prmData)
{
    $strHtml= getHtmlTop(1,'検索元種別','※検索元を指定します。');

    $strHtml2.= '
        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="30"> </td>
            <td width="10"> <input type="radio" name="typerb" value="mst" />
            </td>
            <td width="170"><font size="2">マスタ条件</font></td>
            <td width="20"> <input type="radio" name="typerb" value="enq" />
            </td>
            <td width="170"><font size="2">アンケート条件</font></td>
            <td width="30"> </td>
            </tr>
        </table>
';
    $strHtml.= $prmData["type"]? ereg_replace('(value="'.$prmData["type"].'")','\1 checked',$strHtml2):ereg_replace('(value="mst")','\1 checked',$strHtml2);
    $strHtml.='
        <br>
        <table width="400" border="0" cellspacing="1" cellpadding="3">
            <tr>
            <td width="20"><font color="#FF0000" size="2">※</font></td>
            <td width="371"> <font color="#FF0000" size="2">「マスタ条件」→予め登録された属性データで条件作成</font></td>
            </tr>
            <tr>
            <td> </td>
            <td> <font color="#FF0000" size="2">「アンケート条件」→指定されたアンケートの回答データで条件作成</font> </td>
            </tr>
        </table>
        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="221" height="20"> <table width="400" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
            </tr>
        </table>
        </td>
        </tr>
        <br>
        <table width="400" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td colspan="6" align="center">
                <input type="submit" value="次へ" />
            </td>
            </tr>
        </table>

        <input type="hidden" name="main" value="c_first">
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 条件作成画面2を表示。
 * @param  condデータ
 * @return html
 */
function getHtmlCondSecond($prmData)
{
    $strHtml= getHtmlTop(2,'対象アンケート設定','※対象アンケートを設定します。')
.'
        <table width="400" border="0" cellpadding="3" cellspacing="0">
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td width="10"></td>
            <td width="100"><font size="2">アンケートを選択</font></td>
            <td width="20" align="center"> ： </td>
            <td>
                <select name="evid">
';
    //アンケートのリストを取得
    $aryData = Get_Enquete(-1,"","evid","desc",$_SESSION['muid']);
    foreach ($aryData as $value) {
        $strSb .= '<OPTION value="'.html_escape($value['evid']).'">';
        $strSb .= html_escape(sprintf("%02d", $value['evid'])." ".$value['name']).'</OPTION>';
    }
    $strHtml.= ereg_replace('(value="'.html_escape($prmData['evid']).'")','\1 selected',$strSb)
.'
                </select>
            </td>
            </tr>
        </table>
        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="221" height="20">
                <table width="400" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
                    </tr>
                </table>
            </td>
            </tr>
        </table>
        <br>
        <table width="400" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td colspan="6" align="center">
                <input type="submit" value="次へ" />
            </td>
            </tr>
        </table>
<input type="hidden" name="main" value="c_second">
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 条件作成画面3を表示。
 * @param  condデータ
 * @return html
 */
function getHtmlCondThird($prmData)
{

    $strHtml= getHtmlTop(3,'対象質問設定','※対象アンケート内の質問を限定します。');

    $strHtml2.= '
        <table width="400" border="0" cellpadding="3" cellspacing="0">
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td width="10"><input type="radio" name="seidrb" value="nodata" /></td>
            <td> <font size="2">指定なし</font> </td>
            </tr>
            <tr>
            <td align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td><input type="radio" name="seidrb" value="set" /></td>
            <td>
                <select name="seid">
';
    $strHtml.= $prmData["seid"]==""? ereg_replace('(value="nodata")','\1 checked',$strHtml2):ereg_replace('(value="set")','\1 checked',$strHtml2);

    //設問のリストを取得
    $aryData = Get_Enquete("id",$prmData["evid"],"","");
    foreach ($aryData[0] as $value) {
        $strSb.='<OPTION value="'.$value["seid"].'">'.$value["title"].'</OPTION>';
    }
    $strHtml.= ereg_replace('(value="'.$prmData["seid"].'")','\1 selected',$strSb)
.'
                </select>
            </td>
            </tr>
        </table>
        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="221" height="20">
                <table width="400" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
                            </tr>
                </table>
            </td>
            </tr>
        </table>
        <br>
        <table width="400" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td colspan="6" align="center">
                <input type="submit" value="次へ" />
            </td>
            </tr>
        </table>
    <br>
<input type="hidden" name="main" value="c_third">
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 条件作成画面3'(マスタ条件)を表示。
 * @param
 * @return html
 */
function getHtmlCondMst($prmData)
{
    global $con,$C_OPERATOR_SET;


    $strHtml= getHtmlTop(2,'マスタ条件設定','※対象マスタ内の条件を設定します。');

    $strHtml.= '
        <table width="400" border="0" cellpadding="3" cellspacing="0">
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
'.'
            <td width="100"><font size="2">対象カラム</font></td>
            <td>
';
    $strHtml.= ereg_replace('(value="'.$prmData["column"].'")','\1 selected',getHtmlColumnSb('column',T_USER_MST,'-- 選択してください --'));
    $strHtml.= '
            </td>
            </tr>
            <tr>
            <td align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
'.'
            <td width="100"><font size="2">対象文字列</font></td><td></td>
'.'
            </tr>
            <tr>
            <td align="center"></td>
            <td>
';

    $strHtml.= getSelectHtml($C_OPERATOR_SET,"comp","",$prmData["comp"]);

    $strHtml.= '
            </td>
            <td>
                <input name="textform" type="text" value="'.transHtmlentities($prmData["strcond"]).'" size="35" />
            </td>
            </tr>
        </table>

        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="221" height="20">
                <table width="400" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
                    </tr>
                </table>
            </td>
            </tr>
        </table>
        <br>
        <table width="400" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td colspan="6" align="center">
                <input type="submit" value="次へ" />
            </td>
            </tr>
        </table>
        <input type="hidden" name="main" value="c_mst">
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 条件作成画面4を表示。
 * @param
 * @return html
 */
function getHtmlCondSubEvent($prmData)
{
    global $C_OPERATOR_SET;
    $strF = '';
    $strT = '';
    $strHtml= getHtmlTop(4,'アンケート条件設定','※対象アンケート内の条件を設定します。');

    $strHtml.= '
        <table width="400" border="0" cellpadding="3" cellspacing="0">
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
';

    $strHtml2.= '
            <td width="100"><font size="2"><input type="radio" name="column" value=""'.$strF.' />回答した</font></td><td></td>
'.'
            </tr>
            <tr>
            <td align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
'.'
            <td width="100"><font size="2"><input type="radio" name="column" value="strcond"'.$strT.' />回答内容</font></td><td></td>';


    $strHtml.= $prmData["column"]=="strcond"? ereg_replace('(value="'.$prmData["column"].'")','\1 checked',$strHtml2):ereg_replace('(value="")','\1 checked',$strHtml2);


    $strHtml.= '
            </tr>
            <tr>
            <td align="center"></td>
            <td>
'.getSelectHtml($C_OPERATOR_SET,"comp","",$prmData["comp"]).'
            </td>
            <td>
                <input name="textform" type="text" value="'.transHtmlentities($prmData["strcond"]).'" size="35" />
            </td>
            </tr>
        </table>

        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="221" height="20">
                <table width="400" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
                    </tr>
                </table>
            </td>
            </tr>
        </table>
        <br>
        <table width="400" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td colspan="6" align="center">
                <input type="submit" value="次へ" />
            </td>
            </tr>
        </table>

<input type="hidden" name="main" value="c_subevent">
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 条件作成画面4'を表示。
 * @param
 * @return html
 */
function getHtmlCondEvent($prmData)
{
    global $C_OPERATOR_AFTER,$C_OPERATOR_BEFORE;

    $strHtml= getHtmlTop(4,'回答条件設定','※回答状況及び対象日時を指定します。');

    $strHtml.= '<table width="400" border="0" cellpadding="3" cellspacing="0">';

    $strHtml2='
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td><font size="2"><input type="radio" name="column" value="true" />回答した</font></td>
            </tr>
'.'
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>
            <td><font size="2"><input type="radio" name="column" value="false" />回答していない</font></td>
            </tr>
'.'
            <tr>
            <td width="16" align="center">
                <img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle">
            </td>

            <td><font size="2"><input type="radio" name="column" value="cdate" />日付指定</font></td>
            </tr>
';
    $strHtml.= ($prmData["column"]=="true"||$prmData["column"]=="false"||$prmData["column"]=="cdate")? ereg_replace('(value="'.$prmData["column"].'")','\1 checked',$strHtml2):ereg_replace('(value="true")','\1 checked',$strHtml2);

    $strHtml.= '
        </table>


        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td colspan="7"> <div align="left"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></div></td>
            </tr>
            <tr>
            <td align="center">　</td>
            <td>　</td>
            <td width="100"> <font size="2">検索開始日</font></td>
            <td width="10" align="center">：</td>
            <td>
                <table width="185" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td><input name="year1" type="text" value="'.$prmData["day"]["b_year"].'" size="4" /></td>

                    <td><font size="2">年</font></td>
                    <td>
                        <select name="month1">
';
    $strTmpMonth = '
                            <option value="01"> 1</option>
                            <option value="02"> 2</option>
                            <option value="03"> 3</option>
                            <option value="04"> 4</option>
                            <option value="05"> 5</option>
                            <option value="06"> 6</option>
                            <option value="07"> 7</option>
                            <option value="08"> 8</option>
                            <option value="09"> 9</option>
                            <option value="10">10</option>
                            <option value="11">11</option>
                            <option value="12">12</option>
';
    $strHtml .= ereg_replace("(value=\"".$prmData["day"]["b_month"]."\")","\\1 selected",$strTmpMonth);

    $strHtml.='
                        </select>
                    </td>
                    <td><font size="2">月</font></td>
                    <td>
                        <select name="day1">
';
    for ($i=1;$i<32;++$i) {
        if ($i<10) {
            $no="0".$i;
        } else {
            $no=$i;
        }
        $strTempDay.= '
                            <option value="'.$no.'">'.$i.'</option>';
    }

    $strHtml .= ereg_replace("(value=\"".$prmData["day"]["b_day"]."\")","\\1 selected",$strTempDay);

    $strHtml.= '
                        </select>
                    </td>
                    <td><font size="2">日</font></td>
                    </tr>
                </table>
            </td>
            <td>
';
    $strHtml.= getSelectHtml($C_OPERATOR_AFTER,"comp1","",$prmData["day"]["b_comp"]);

    $strHtml.= '
            </td>
            </tr>
            <tr>
            <td colspan="7"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
            </tr>
            <tr>
            <td width="16" align="center">　</td>
            <td width="10">　</td>
            <td> <font size="2">検索終了日</font></td>
            <td align="center">：</td>
            <td width="185">
                <table width="185" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td>
                        <input name="year2" type="text" value="'.$prmData["day"]["e_year"].'" size="4" />
                    </td>
                    <td><font size="2">年</font></td>
                    <td>
                        <select name="month2">
';

    $strHtml .= ereg_replace("(value=\"".$prmData["day"]["e_month"]."\")","\\1 selected",$strTmpMonth);

    $strHtml.='
                        </select>
                    </td>
                    <td><font size="2">月</font></td>
                    <td>
                        <select name="day2">
';

    $strHtml .= ereg_replace("(value=\"".$prmData["day"]["e_day"]."\")","\\1 selected",$strTempDay);
    $strHtml.= '
                        </select>
                    </td>
                    <td><font size="2">日</font></td>
                    </tr>
                </table>
            </td>
            <td width="45">
';
    $strHtml.= getSelectHtml($C_OPERATOR_BEFORE,"comp2","",$prmData["day"]["e_comp"]);

    $strHtml.= '
            </td>
            </tr>

            <tr>
            <td colspan="6">
                <table>
                    <tr>
                    <td width="20"><font color="#FF0000" size="2">※</font></td>
                    <td width="371"> <font color="#FF0000" size="2">「検索開始日時」を入力しない場合→終了日時以前を検索の全対象</font></td>
                    </tr>
                    <tr>
                    <td> </td>
                    <td> <font color="#FF0000" size="2">「検索終了日時」を入力しない場合→開始日時以降を検索の全対象</font> </td>
                    </tr>
                </table>
            </td>
            </tr>


            <tr>
            <td height="20" colspan="6">
                <table width="400" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                    <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
                    </tr>
                </table>
            </td>
            </tr>
        </table>
        <br>
        <table width="400" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td colspan="6" align="center">
                <input type="submit" value="次へ">
            </td>
            </tr>
        </table>

<input type="hidden" name="main" value="c_event">
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 結果表示画面の中身を取得。
 * @param	文章
 * 			中身
 * @return html
 */
function getHtmlResultTable($prmMsg1,$prmMsg2)
{
    $strResult =getDlHtmlTableItem($prmMsg1,$prmMsg2);

    return $strResult;
}
/*
 * 条件作成画面5を表示。
 * @param  condデータ
 * 			↑より作ったSQL文
 * @return html
 */
function getHtmlCondFifth($prmData)
{
    if ($prmData["type"]=="mst") {
        $strType='マスタ';
        $strMsg1 = 3;
    } else {
        $strType.='アンケート';
        $strMsg1 = 5;
    }

    $strHtml= getHtmlTop($strMsg1,'設定確認','&nbsp;');

    $strHtml.= '
    <table width="380" border="0" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
        <tr>
        <td width="270" valign="bottom">
            <table width="380" border="0" cellpadding="0" cellspacing="1">
                <tr>
                <td width="380" bgcolor="#f6f6f6" align="center">
                    <table width="380" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
                        <tr>
                        <td colspan="5" align="center">
                            <font size="2">下記設定内容で条件を保存しますか？</font>
                        </td>
                        </tr>
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
';

    $strHtml.= getHtmlResultTable("検索元種別",$strType);
    $strHtml.= getHtmlResultTable("対象アンケート",$prmData["evid"]);
    $strHtml.= getHtmlResultTable("対象質問",$prmData["seid"]);
    $strHtml.= getHtmlResultTable("対象カラム",$prmData["column"]);
    $strHtml.= getHtmlResultTable("条件",getSQLComment($prmData['SQL']));

    $strHtml.='
                        <tr>
                        <td colspan="5" align="center">
                                <input type="image"  src="'.DIR_IMG.'add.gif" width="70" height="17" />
                                <input type="hidden" name="main" value="c_final">
                        </td>
                        </tr>
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
                    </table>
                </td>
                </tr>
            </table>
        </td>
        </tr>
    </table>
'.getHtmlBottom();

return $strHtml;

}

/*
 * 登録完了画面を取得。
 * @param  登録データ
 * @return html
 */
function getHtmlFinal($prmData,$prmCond,$prmOp)
{
    $strHtml = getHtmlTop(0,"登録完了",'',0);

    $strHtml.= '
    <table width="380" border="0" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
        <tr>
        <td width="270" valign="bottom">
            <table width="380" border="0" cellpadding="0" cellspacing="1">
                <tr>
                <td width="380" bgcolor="#f6f6f6" align="center">
                    <table width="380" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
                        <tr>
                        <td colspan="5" align="center">
                            <font size="2">下記設定内容で複合条件リストを保存しました。</font>
                        </td>
                        </tr>
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
';
    $strHtml.= getHtmlResultTable("ＩＤ",$prmData["cnid"]);
    $strHtml.= getHtmlResultTable("名称",transHtmlentities($prmData["name"]));
    foreach ($prmCond as $value) {
        $strHtml2[] = getSQLComment($value["SQL"])."<br>";
    }

    if($prmOp == "AND") $strOp = "かつ";
    if($prmOp == "OR") $strOp = "又は";

    if ($strHtml2) {
        $strHtml2 = implode($strOp."<br>",$strHtml2);
        $strHtml.= getHtmlResultTable("条件",$strHtml2);
    } else {
        $strHtml.= getHtmlResultTable("条件","全員");
    }

//	$strHtml.= getHtmlResultTable("条件文",$prmData["strsql"]);

    $strHtml.='
                        <tr>
                        <td colspan="5" align="center">
                            <a href="'.C_BACK_PG.'?'.getSID().'"><img src="'.DIR_IMG.'m_back.gif" width="100" height="24" align="middle" border=0></a>
                        </td>
                        </tr>
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
                    </table>
                </td>
                </tr>
            </table>
        </td>
        </tr>
    </table>
'.getHtmlBottom();

    return $strHtml;
}

/*
 * 条件による結果表示を取得
 * @param  登録データ
 * @return html
 */
function getHtmlCsvTable($prmData)
{
    $table = getHtmlTablefromAry(getSQLSelect(getSQL($prmData)));
    $phpself = getPHP_SELF()."?".getSID();
    if (!$table) {
        $strResult= '条件に一致するログはありませんでした。
    <form action='.$phpself.' method="post" encType=multipart/form-data style="display:inline">
    <input type="hidden" name="main" value="enter">
    <input type="image" src="'.DIR_IMG.'m_back.gif" width="100" height="20" name="back" />
    </from>';

        return $strResult;
    }

    $strResult= getHtmlSub('検索結果','指定条件で検索されたデータです。').'
        <form action='.$phpself.' method="post" encType=multipart/form-data>'
        .$table
        .'<br>
        <table width="400" border="0" cellspacing="1" cellpadding="0">
            <tr>
            <td align="center">
                <input type="image" src="'.DIR_IMG.'save.gif" width="100" height="20" name="final" />
                <input type="image" src="'.DIR_IMG.'s_dl.gif" width="100" height="20" name="download" />
                <input type="image" src="'.DIR_IMG.'s_back.gif" width="100" height="20" name="back" />
            </td>
            </tr>
        </table>
        <input type="hidden" name="main" value="enter">
</form>';

    return $strResult;
}

//-----------------------------------------------------------------

/*
 * SQL文から内容解説を作成
 *
 *
 */
function getSQLComment($prmSQL)
{
    if (substr($prmSQL,0,5) == "%NOT%") {
        $strNot = "でない";
    } else {
        $strNot = "である";
    }

    $strSQL = explode("WHERE",$prmSQL);
    $strCond = array_pop($strSQL);
    $strSQL = explode("FROM",$strSQL[0]);
    $strSelect = ereg_replace("SELECT ","",$strSQL[0]);
    if($strSQL[1]) $strFrom = $strSQL[1]."で";

    $strCond = ereg_replace("cdate","日時",$strCond);
    $strCond = ereg_replace(">=","≧",$strCond);
    $strCond = ereg_replace("<=","≦",$strCond);
    $strCond = ereg_replace("<>(.*)","が\\1に不一致",$strCond);
    $strCond = ereg_replace("LIKE '%(.*)%'","が\\1に全文一致",$strCond);
    $strCond = ereg_replace("LIKE '(.*)%'","が\\1に前方一致",$strCond);
    $strCond = ereg_replace("LIKE '%(.*)'","が\\1に後方一致",$strCond);
    $strCond = ereg_replace("AND","かつ",$strCond);
    $strCond = ereg_replace("OR","又は",$strCond);

    $strCond = $strFrom.$strCond.$strNot;

    return transHtmlentities($strCond);
}

/*
 * condからSQLを生成して返す
 * @param	condデータ
 * @return	SQL文
 */
function getCondSQL($prmData)
{
    global $C_OPERATOR_SET,$C_OPERATOR_AFTER,$C_OPERATOR_BEFORE;
    $_SESSION['data']['evid'] = $prmData["evid"];
    if ($prmData["type"] =="mst") {
        if ($prmData["column"]<>"") {
            $strSQL.=$prmData["column"]." ".getSelectValue($C_OPERATOR_SET,$prmData["comp"],$prmData["strcond"]);
        }
    } else {
        $strSQL ="SELECT serial_no FROM ";
        if ($prmData["seid"] =="") {
            $strSQL.="event_data WHERE ";
            if($prmData["column"] == "false") $strSQL="%NOT%".$strSQL;
            $strSQL.="answer_state = 0 and evid = ".$prmData["evid"];
            if ($prmData["column"] == "cdate") {
                if ($prmData["day"]["b_year"] <> "") {
                    $strSQL.=" AND ";
                    $strDay1 = $prmData["day"]["b_year"];
                    if ($prmData["day"]["b_month"] <> "") {
                        $strDay1.= "-".$prmData["day"]["b_month"];
                        if ($prmData["day"]["b_day"] <> "") {
                            $strDay1.= "-".$prmData["day"]["b_day"];
                        }
                    }
                    $strSQL.=$prmData["column"]." ";
                    $strSQL.=getSelectValue($C_OPERATOR_AFTER,$prmData["day"]["b_comp"],$strDay1);
                }
                if ($prmData["day"]["e_year"] <> "") {
                    $strSQL.=" AND ";
                    $strDay2 = $prmData["day"]["e_year"];
                    if ($prmData["day"]["e_month"] <> "") {
                        $strDay2.= "-".$prmData["day"]["e_month"];
                        if ($prmData["day"]["e_day"] <> "") {
                            $strDay2.= "-".$prmData["day"]["e_day"];
                        }
                    }
                    $strSQL.=$prmData["column"]." ".getSelectValue($C_OPERATOR_BEFORE,$prmData["day"]["e_comp"],$strDay2);
                }
            }
        } else {
            $strSQL.="subevent_data WHERE seid = ".$prmData["seid"];

            if ($prmData["column"] == "strcond") {
                $strSQL.=" AND ";
                $aryData = Get_Enquete("id",$prmData["evid"],"","");
                $aryData = $aryData[0];
                if ($aryData[$prmData["seid"]]["type1"] >= 3) {
                    $strSQL.="other ";
                } else {
                    $strSQL.="choice ";
                }
                $strSQL.=getSelectValue($C_OPERATOR_SET,$prmData["comp"],$prmData["strcond"]);
            }
        }
    }

    return $strSQL;
}

/*
 * sqlを作って実行
 * @param	sql文
 * @return	データ
 */
function transDBResist()
{
    if ($_SESSION["new"]) {
        $strSendData = array(
            "name" => "testdata"
            ,"strsql" => NULL
            ,"pgcache" => NULL
            ,"flgt" => NULL
            ,"muid" => $_SESSION["muid"]
        );
        $_SESSION["data"]["cnid"] = Save_Condition("new",$strSendData);
        $_SESSION["data"]["cdate"] = date("Y-m-d H:i:s");
    }

    $_SESSION["data"]["udate"] = date("Y-m-d H:i:s");
    $strSendData = array(
         "cnid" => $_SESSION["data"]["cnid"]
        ,"name" => $_SESSION["data"]["name"]
        ,"strsql" => getSQL($_SESSION["data"])
        ,"pgcache" => serialize($_SESSION["data"])
        ,"cdate" => $_SESSION["data"]["cdate"]
        ,"udate" => $_SESSION["data"]["udate"]
    );
    Save_Condition("update",$strSendData);

    return $strSendData;
}

/*
 * keyをindexにdataをcsvにする
 * @param	sql文
 * @return	strデータ
 */
function getCsvData($prmData)
{
    if(!$prmData) return;
    $strIndex = array_keys($prmData[0]);
    foreach ($strIndex as $index) {
            $strData[0].= $index.',';
        foreach ($prmData as $key => $value) {
            $strData[$key+1].= $value[$index].',';
        }
    }
    foreach ($strData as $key=> $value) {
        $Data[$key] = substr($value,0,-1);
    }
    $strData = implode("\n",$Data);

    return $strData;
}

/*
 * 指定した文字列を出力してスクリプトを終了する
 * @param
 * @return html
 */
function transOutput($prmStr)
{
    if (strlen($prmStr)>0) {
        $strStr = $prmStr;
        $strStr = mb_convert_encoding($strStr,"SJIS","EUC-JP");
        $strFileName='datafile'.date("YmdHi").'.csv';
        header(sprintf("Content-disposition: attachment; filename=%s", $strFileName));
        header(sprintf("Content-type: application/octet-stream; name=%s", $strFileName));
        //header(sprintf('Content-Length: %d', strlen($strStr)));
        echo $strStr;
        exit;
    } else {
        echo '条件に一致するログはありませんでした。
    <form action='.getPHP_SELF().'?'.getSID().' method="post" encType=multipart/form-data style="display:inline">
    <input type="hidden" name="main" value="enter">
    <input type="image" src="'.DIR_IMG.'m_back.gif" width="100" height="20" name="back" />
    </from>';
        exit;
    }
}
