<?php

require_once 'CbaseFDB.php';
require_once 'CbaseFGeneral.php';

/**
 * CbaseFEnqueteTotal
 * 		enq_ttl.php
 * 		votesresult.php
 * 		にて使用
 * @package Cbase.Research.Lib
 */
class CbaseResearchTotal
{
    //全変数private扱いとし、直接取得しない
    public $enquete;
    public $answer;
    public $total;
    public $totalcountl;
    public $subeventBySeid;
    public $choice;

    /**
     * コンストラクタ
     * @param string $key  ridやevidなど
     * @param string $mode GetEnqueteのモード
     */
    public function CbaseResearchTotal($key, $mode="rid")
    {
        $enquete = Get_Enquete($mode, $key, "", "");
        if (!$enquete) {
            echo "Error: Param NG";
//新しいresearch4に以下のクラスがあればイキにする
//			return new CbaseException("ridからアンケートが取得できません。");
            exit;
        }
        $this->enquete=$enquete;
    }

    /**
     * データを配列にセット
     * seid=>選択肢=>回答数 の多次元配列形式にして返す
     *
     */
    public function setTotal()
    {
        $this->getAnswer();
        $this->setSubEvent();
        $this->setChoice();
        foreach ($this->answer as $vAnswer) {
            if ($vAnswer["choice"]=="") continue;//単数・複数回答以外のものは対象外
            $intTmpSeid=0;
            $intTmpChoice=0;
            $strTmpUser="";
            $aryTmpChoice=array();
            $intTmpSeid=$vAnswer["seid"];
            $intTmpChoice=$vAnswer["choice"];
            $strTmpUser=$vAnswer["serial_no"];
            $this->total[$intTmpSeid][$this->choice[$intTmpSeid][$intTmpChoice]]++;//選択肢ごとのカウント
//			$this->total[$intTmpSeid][$intTmpChoice]++;//選択肢#ごとのカウント
            $this->totalcount[$intTmpSeid][$strTmpUser]++;//件数取得 count($this->totalcount[$seid])
        }

        return true;
    }

    /**
     * 選択肢順に集計データをソート
     */
    public function sortData()
    {
        if (!$this->total||!$this->subeventBySeid) {
            echo 'ERROR: Sort : No Data';
            exit;
        }
        $aryTmp=$this->total;
        $this->total=array();
        foreach ($aryTmp as $k=>$v) {
            //$k=seid, $v= (choice=>count)
            $aryChoice=$this->choice[$k];
            foreach ($aryChoice as $val) $this->total[$k][$val]=$v[$val];
        }
    }

    /**
     * 回答を取得して自身に保持する
     */
    public function getAnswer()
    {
        $answer = FDB::select(T_EVENT_SUB_DATA, "*", FDB::where("evid=".FDB::escape($this->enquete[-1]["evid"])));
        $this->answer = $answer;
    }

    /**
     * 回答を取得して保持する
     */
    public function setAnswerCount()
    {
        $answer = FDB::select(T_EVENT_DATA, "count(*) as count", FDB::where("evid=".FDB::escape($this->enquete[-1]["evid"])));
        $this->answercount = $answer[0]["count"];
    }

    /**
     * 回答数を返す
     */
    public function getAnswerCount()
    {
        return $this->answercount;
    }

    /**
     * seid毎の回答ユーザ数を取得
     * 回答率用
     * @param  int $seid seid
     * @return int 回答ユーザ数
     */
    public function getTotalCount($seid)
    {
        if (!$this->total) {
            $this->setTotal();
        }

        return count($this->totalcount[$seid]);
    }

    /**
     * 集計結果を取得
     * @param  int $seid seid
     * @return int 集計結果
     */
    public function getTotal($seid)
    {
        if (!$this->total) {
            $this->setTotal();
        }

        return $this->total[$seid];
    }

    /**
     * 質問基本設定を取得
     * @return array event
     */
    public function getEvent()
    {
        return $this->enquete[-1];
    }

    /**
     * seidから質問を取得
     * @param  int   $seid seid
     * @return array subevent
     */
    public function getSubEvent($seid = "")
    {
        if($seid === "") return $this->enquete[0];
        if (!$this->subeventBySeid) {
            foreach ($this->enquete[0] as $v) {
                $this->subeventBySeid[$v["seid"]] = $v;
            }
        }

        return $this->subeventBySeid[$seid];
    }
    /**
     * seidから質問にアクセスできるようにセットする
     */
    public function setSubEvent()
    {
        foreach ($this->enquete[0] as $v) {
            $this->subeventBySeid[$v["seid"]] = $v;
        }
    }

    /**
     * seidから選択肢にアクセスできるようにセットする
     */
    public function setChoice()
    {
        foreach ($this->enquete[0] as $v) {
            $this->choice[$v["seid"]] = explode(",", $this->subeventBySeid[$v["seid"]]["choice"]);
        }
    }

}

/**
 * ユーザマスタの指定カラムでGroupByしたときのカウント数を取得する
 * @param string $prmColumn カラム名
 * @return mixed arrayまたはfalse
 */
function getUserCountWithGrouping($prmColumn)
{
    global $con;
    if (!$prmColumn) return false;

    $strSql  = "select $prmColumn,count(*) as count ";
    $strSql .= "from ".T_USER_MST." ";
    $strSql .= "group by $prmColumn";
    $rs       = $con->query($strSql);
    if (FDB::isError($rs)) {
        if (DEBUG) echo $rs->getDebuginfo();
        return false;
    }
    $row = '';
    //while ($rs->fetchInto($row,MDB2_FETCHMODE_NUM)) {//削除2012-06-21
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_NUM)) {
        $tmp = $row[0];
        $aryTmp[$tmp] = $row[1];//$prmColumnを連想キーにもち、countを値に持つ
    }

    return $aryTmp;
}

/**
 * ユーザマスタの指定カラムでGroupByしたときのカウント数を取得する
 * @param int $prmEvid evid
 * @param string $prmColumn カラム名
 * @return mixed arrayまたはfalse
 *
 */
function getAnswerCountWithGrouping($prmEvid, $prmColumn)
{
    global $con;
    if (!$prmColumn||!$prmEvid) return false;
    $strSql  = "select $prmColumn,count(*) as count ";
    $strSql .= "from ".T_USER_MST." ";
    $strSql .= "where serial_no in (";
    $strSql .= 						" select serial_no ";
    $strSql .= 						" from ".T_EVENT_DATA." ";
    $strSql .= 						" where evid = $prmEvid ";
    $strSql .= ") ";
    $strSql .= "group by $prmColumn";
    $rs       = $con->query($strSql);
    if (FDB::isError($rs)) {
        if (DEBUG) echo $rs->getDebuginfo();
        return false;
    }
    $row = '';
    //while ($rs->fetchInto($row,MDB2_FETCHMODE_NUM)) {//削除2012-06-21
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_NUM)) {
        $tmp = $row[0];
        $aryTmp[$tmp] = $row[1];//$prmColumnを連想キーにもち、countを値に持つ
    }

    return $aryTmp;
}

/**
 * 回答率のhtmlコードを取得する
 * @param int $prmEvid evid
 * @param string $prmColumn カラム名
 * @return string html
 *
 */
function getCurrentAnswerRate($prmEvid, $prmColumn)
{
$aryTTL=getUserCountWithGrouping($prmColumn);
$aryAns=getAnswerCountWithGrouping($prmEvid, $prmColumn);

//index行出力
    $strHTML .=<<<HTML
    <TABLE border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD bgcolor="#000000">
        <TABLE border="0" cellspacing="1" cellpadding="3">
        <TR>
            <TD bgcolor="#eeeeee" align="right"><FONT size="-1">div</FONT></TD>
            <TD bgcolor="#eeeeee" align="right"><FONT size="-1">回答数</FONT></TD>
            <TD bgcolor="#eeeeee" align="right"><FONT size="-1">対象数</FONT></TD>
            <TD bgcolor="#eeeeee" align="right"><FONT size="-1">回答率</FONT></TD>
        </TR>
HTML;

//$prmColumn毎のデータ出力
    foreach ($aryTTL as $k=>$v) {
        $strPer2=sprintf("%01.1f",($aryAns[$k]/$v)*100)."%";
        $strCnt1=$aryAns[$k];
            $strHTML .=<<<HTML
            <TR>
                <TD bgcolor="#ffffff"><FONT size="-1">{$k}</FONT></TD>
                <TD bgcolor="#ffffff"><FONT size="-1">{$strCnt1}</FONT></TD>
                <TD bgcolor="#ffffff"><FONT size="-1">{$v}</FONT></TD>
                <TD bgcolor="#ffffff"><FONT size="-1">{$strPer2}</FONT></TD>
            </TR>
HTML;
    }

//TOTAL行出力
    $strTTL1=array_sum($aryTTL);
    $strTTL2=array_sum($aryAns);
    $strTTLp=sprintf("%01.1f",($strTTL2/$strTTL1)*100)."%";
            $strHTML .=<<<HTML
            <TR>
                <TD bgcolor="#ffffff"><FONT size="-1">=TOTAL=</FONT></TD>
                <TD bgcolor="#ffffff"><FONT size="-1">{$strTTL2}</FONT></TD>
                <TD bgcolor="#ffffff"><FONT size="-1">{$strTTL1}</FONT></TD>
                <TD bgcolor="#ffffff"><FONT size="-1">{$strTTLp}</FONT></TD>
            </TR>
HTML;
    $strHTML .= "</TABLE></TD></TR></TABLE>";

return $strHTML;
}

/**
 * レポート用レポート日時データ出力
 * @return string html
 *
 */
function getReportDate()
{
    $strDate=date("Y年m月d日 H時");
    $strHTML .=<<<HTML
    <TABLE border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD>
        <TABLE border="0" cellspacing="1" cellpadding="3">
        <TR>
            <TD align="center"><FONT size="-1">回答率レポート at {$strDate}</FONT></TD>
        </TR>
        </table>
        </td>
        </tr>
        </table>
HTML;

return $strHTML;
}
