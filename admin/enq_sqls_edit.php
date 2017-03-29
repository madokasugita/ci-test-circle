<?php
/*
 * PG名称：sqlsearchから使えるマスタ編集ツール
 * 日  付：2005/05/25
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
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

//-----------------------------------------------------------------
//定数定義

//戻り先（トップ画面）のプログラム名
define("C_BACK_PG","enq_sqlsearch.php");

//無条件の一時識別記号
define("C_NULL_CHAR",'/%&%::null::&%&/');

//-----------------------------------------------------------------
//実行部

if ($_POST) {
    $postData = $_POST;
    if ($_POST["main"]=="edit") {
        for ($i=0;$i<count($_POST["sb"]);$i++) {
            if ($_POST["cb"][$i] =="cb".$i) {
                $aryPost[0] = C_NULL_CHAR;
                $postData["tb1"][$i] = "";
            } elseif (!is_null($_POST["tb1"][$i])) {
                $aryPost[0] = $_POST["tb1"][$i];
            }
            if (!is_null($_POST["tb2"][$i])) {
                $aryPost[1] = $_POST["tb2"][$i];
            }
            if ($aryPost) {
                $aryData[$_POST["sb"][$i]][] = $aryPost;
            }
        }
        //$aryData 条件テーブル
        if ($_POST["plus"]) {
            $postData["num"]++;
        } elseif ($_POST["minus"]) {
            $postData["num"]--;
        } elseif ($_POST["clear"]) {
            $postData = '';
        }
        if ($aryData) {
            if (transDBConvert($_SESSION["data"],$aryData)) {
                $strHtml.='編集を実行しました<br><br>';
            }
        }
    } elseif ($_POST["main"]=="csv") {
        $aryData = getTablefromCsv($_FILES["ufile"],$_POST);
        echo "aaa";
        if ($aryData) {
            if (transDBConvert($_SESSION["data"],$aryData)) {
                $strHtml.='編集を実行しました<br><br>';
            }
        }
    }

}
if($postData["num"]<1) $postData["num"]=1;
$strHtml.= getHtmlMain($postData);

$objHtml =& new ResearchAdminHtml("複合条件リスト");
echo $objHtml->getMainHtml($strHtml);
exit;

//-----------------------------------------------------------------
//関数
//-----------------------------------------------------------------

/*
 * メイン画面を取得
 * @param  post
 * @return html
 */
function getHtmlMain($prmData)
{
    $table = getHtmlTablefromAry(getSQLSelect(getSQL($_SESSION["data"])));
    if (!$table) {
        $strResult = '
条件に一致するログがありませんでした。<br>
    <form action='.C_BACK_PG.'?'.getSID().' method="post" style="display:inline">
    <input type="hidden" name="main" value="enter">
    <input type="image" src="'.DIR_IMG.'m_back.gif" width="100" height="20" name="back" />
    </form>
';

    return $strResult;
    }

    $strResult= getDlHtmlSubject('検索結果','指定条件で検索されたデータです。')
    .$table.'<br><form action='.getPHP_SELF().'?'.getSID().' method="post" style="display:inline">
    '.getDlHtmlSubject('置換処理','検索されたデータについて置換を行えます。')
    .'<table border="0">
        <tr>
            <td></td>
            <td><font size="2">カラム</font></td>
            <td><font size="2">条件</font></td>
            <td><font size="2">置換文字列</font></td>
        </tr>
';

    for ($i=0;$i<$prmData["num"];$i++) {
        $strSb = ereg_replace('(value="'.$prmData["sb"][$i].'")','\1 selected',getHtmlColumnSb("sb[".$i."]",T_USER_MST));
        $strCb = ereg_replace('(value="'.$prmData["cb"][$i].'")','\1 checked','（<input type="checkbox" name="cb['.$i.']" value="cb'.$i.'">無条件）');
        $strResult.= '
        <tr>
            <td>
                '.($i+1).'
            </td>
            <td>
                '.$strSb.'が
            </td>
            <td>
                <input type="text" name="tb1['.$i.']" value="'.$prmData["tb1"][$i].'">'.$strCb.'なら
            </td>
            <td>
                <input type="text" name="tb2['.$i.']" value="'.$prmData["tb2"][$i].'">にする。
            </td>
        </tr>
';
    }
    $strResult.= '

        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td align="right"><input type="submit" name="plus" value="条件を増やす">';
    if ($prmData["num"] > 1) {
        $strResult.= '<input type="submit" name="minus" value="条件を減らす">';
    }

    $strResult.= '
        <input type="submit" name="clear" value="クリア">
        <input type="hidden" name="num" value="'.$prmData["num"].'">
</td>
        </tr>
    </table>


<table width="400" border="0" cellspacing="1" cellpadding="0">
    <tr>
    <td align="center">
        <input type="image" src="'.DIR_IMG.'menu_edit.gif" width="100" height="24" name="final" />
        <input type="hidden" name="main" value="edit">
        </form>
        <form action='.C_BACK_PG.'?'.getSID().' method="post" style="display:inline">
        <input type="hidden" name="main" value="enter">
        <input type="image" src="'.DIR_IMG.'m_back.gif" width="100" height="24" name="back" />
        </form>
    </td>
    </tr>
</table>
<br><br>
'.getDlHtmlSubject('CSVから置換','CSVの変換テーブルを用いて置換を行います。')
.'
<form action='.getPHP_SELF().'?'.getSID().' method="post" encType=multipart/form-data style="display:inline">
<table border="1">
    <tr>
        <td>処理対象列
        </td>
        <td>
';
    $aryCb = getHtmlColumnCb('cbcsv',T_USER_MST);
    $aryCb = implode("<br>",$aryCb);

    $strResult.= $aryCb.'
        </td>
    </tr>
    <tr>
        <td>CSVファイル
        </td>
        <td><INPUT type="file" name="ufile" size="40"></td>
    </tr>
</table>
<br>
<table width="400" border="0" cellspacing="1" cellpadding="0">
    <tr>
    <td align="center">
        <input type="image" src="'.DIR_IMG.'menu_edit.gif" width="100" height="24" name="final" />
        <input type="hidden" name="main" value="csv">
        </form>
        <form action='.C_BACK_PG.'?'.getSID().' method="post" style="display:inline">
        <input type="hidden" name="main" value="enter">
        <input type="image" src="'.DIR_IMG.'m_back.gif" width="100" height="24" name="back" />
        </form>
    </td>
    </tr>
</table>
';

    return $strResult;
}

/*
 * @param	data
 * 			変換テーブル配列（aがa[0]のときa[1]に置換）
 *
 *
 */
function transDBConvert($prmData,$prmConv)
{
    global $con;
    foreach ($prmConv as $key => $v) {
        $strCond = $key.' = case ';
        foreach ($v as $value) {
            if (!is_null($value[0]) && $value[0] <> C_NULL_CHAR) {
                $strCond.= 'when '.$key.' = '.FDB::quoteSmart($value[0]).' then '.FDB::quoteSmart($value[1]);
            } elseif (!is_null($value[1])) {
                $aryData[] = $key.' = '.FDB::quoteSmart($value[1]);
                continue 2;

            }
        }
        $strCond.= ' else '.$key.' end ';
        $aryData[] = $strCond;
    }
    if(is_null($aryData)) return;
    $aryData = implode(",",$aryData);
    $strWhere = getSQLWhere($prmData);
    //DBに格納
    $sql = 'update '.T_USER_MST.' set '.$aryData.$strWhere;
    getSQLUpdate($sql);
}

/*
 * sqlでupdate
 * @param	sql文
 * @return
 */
function getSQLUpdate($prmSQL)
{
    global $con;
    //SQL実行
    if (DB_TYPE=="pgsql") $con->query("BEGIN;");

    $rs = $con->query($prmSQL);
    if (FDB::isError($rs)) {
        if (DEBUG) echo $rs->getMessage();
        if (DEBUG) echo $rs->getDebuginfo();
        if (DB_TYPE=="pgsql") $con->query("ROLLBACK;");
        return false;
    }
    if (DB_TYPE=="pgsql") $con->query("COMMIT;");
    return true;
}

/*
 * csvを読み込む
 * @param	csvファイル($_FILE)
 * @return
 */
function getLoadfromCsv(&$prmFile)
{
    $tmpdir = '';
    define("_FILE_",".txt"); //一時保存ファイルの拡張子
    if ($prmFile["name"]!="") {
        //アップロードされたファイルかチェック
        if (is_uploaded_file($prmFile["tmp_name"])) {
            //ファイルのtmpファイルを正規ファイルにmove
            $fname = "sqledit".Get_RandID(13);
            if (move_uploaded_file($prmFile["tmp_name"],$tmpdir.$fname._FILE_)) {
                //moveされたファイルを開く
                $fp = @fopen($tmpdir.$fname._FILE_,"r");
//				if ($_POST["dm"]=="tsv") {
//					$demi="\t";
//				} elseif ($_POST["dm"]=="csv") {
                    $demi=",";
//				}
                if ($fp) {
                    $lineNum = 0;
                    //while (($data = fgetcsv($fp,filesize($tmpdir.$fname._FILE_),$demi)) !== false) {
                    while ($strLine = @fgets($fp, filesize($tmpdir.$fname._FILE_))) {
                        if ( trim($strLine) == "") continue;
                        if (mb_detect_encoding($strLine,"auto",true) == "SJIS") {
                            // 文字コードを SJIS から EUC-JP に変換 コンバート時変更できない場合は「〓」に変更
                            mb_substitute_character(0x3013);
                            $strConvertLine = mb_convert_encoding($strLine, "EUC-JP", "SJIS");
                        } else {
                            $strConvertLine = $strLine;
                        }
                        if (strlen($strConvertLine) > 2) { //空行を配列データに追加しない為の処理
                            $strTrimData = explode($demi,$strConvertLine);
                            for ($i=0;$i<count($strTrimData);++$i) {
                                //$strTrimData[$i] = $strTrimData[$i] = ereg_replace("　+$", "", trim($strTrimData[$i],"　 \n\r"));
                                $strTrimData[$i] = trim($strTrimData[$i],"　 \n\r");
                            }
                            $data[] = $strTrimData;
                            $lineNum++;
                        }
                    }
                    fclose($fp);
                } else {
                    $data = "送信されたデータファイルが開けませんでした";
                }
            } else {
                $data="1_送信失敗　再送信してください";
            }
        } else {
            $data="3_送信失敗　不正データ送信です";
        }
    } else {
        $data="0_送信失敗　アップロードされたデータはありません";
    }
    @unlink($tmpdir.$fname._FILE_);

    return $data;
}

/*
 * csvを変換テーブルデータにする
 * @param	csvファイル($_FILE)
 * @return
 */
function getTablefromCsv(&$prmFile,$prmPost)
{
    $aryCsv = getLoadfromCsv($prmFile);
    foreach ($prmPost['cbcsv'] as $column) {
        foreach ($aryCsv as $value) {
            $aryData[$column][] = $value;
        }
    }

    return $aryData;
}

//--------------------------------------------------
