<?php

/**
 * ユーザを取得する
 * @package Cbase.Research.Lib
 */

/**
 * Save_User(新規登録のみ)
 * @param array $prmData 登録するデータの連想配列（カラム=>データ）
 * @param bool $prmRun 実行するかどうか（falseでSQL文のみを返す）
 * @return mixed 成功したらtrueまたはSQL文
 */
function setUserMst($prmData,$prmRun=true)
{
    global $con;

    $aryData = $prmData;

    //uidを付与する場合はここを使う
    //$newid = $con->nextId('userm_uid');
    //$array["uid"] = $newid;
    //if (FDB :: isError($newid)) return false;

    //登録時に強制的に決定されるカラム

    $aryData["serial_no"] = $prmData["serial_no"]?  $prmData["serial_no"]:getUniqueIdWithTable(T_UNIQUE_SERIAL , "serial_no", 8);

    //SQL文の作成
    //カラム名が有効であるか等のチェックは一切行っていない
    $aryColumn = array();
    $aryValue = array();
    foreach ($aryData as $key => $value) {
        //カラム名の存在チェックをするならここで
        $aryColumn[] = $key;
        $aryValue[] = sql_escape(stripslashes($value),"EUC-JP","EUC-JP");
    }

    $strSql = "insert into ".T_USER_MST
            ." (".implode(",", $aryColumn).")"
            ." values (".implode(",", $aryValue).")";

    if ($prmRun) {
        //DB登録モード
        $con->query($strSql);
            //★ここは新しいDBClassで行ったほうがよい

        //newidを返すと便利だが、付与していないので返さない
        return true;
    } else {
        //SQL文作成モード
        return $strSql;
    }
}

/*
 * *****************************************************
    Get_UserData()

        return $array;	$array[] = array("key"=>"value");で返す
    一人のデータを取得	mode=>sp,key=>column,value=>value
    全員のデータ取得	mode=>all
    特定のデータ取得	mode=>sql,value=>sql文
******************************************************/
/**
 * ユーザのデータを取得
 * @param string $mode モード
 * @param string $key 検索キー(カラム名)
 * @param string $value 検索値
 * @param string $order 未使用
 * @return array 結果配列
 */
function Get_UserData($mode,$key,$value,$order="")
{
    if($mode=="sp"&&!$value) return false;
    //SQL文生成
    if ($mode=="sql") {//condによる使用がメイン。デフォルトでorder by uid
        $sql = $value;
    } elseif ($mode=="sp") {
        $sql ="select * ";
        $sql.="from ".T_USER_MST." ";
        $sql.="where ".$key." = '".$value."'";
    } elseif ($mode=="where") {
        $sql ="select * ";
        $sql.="from ".T_USER_MST." ";
        $sql.="where ".$value;
    } elseif ($mode=="reminder") {
        $sql ="select * ";
        $sql.="from ".T_USER_MST." ";
        $sql.="where serial_no not in (";
        $sql.="select serial_no from event_data ";
        $sql.="where evid = $value )";
    } elseif ($mode=="all") {
        $sql ="select * ";
        $sql.="from ".T_USER_MST." ";
    }

    return FDB::getAssoc($sql);
}//function

/**
 * showUserMst.php,editUserMst.php
 * DBアクセス制限
 */
function setLimitByMuid($option="", $muid)
{
    return $option;
}

/**
 * ユーザ数
 */
function getUserCnt($option)
{
    $max_count = FDB::select(T_USER_MST, "count(*) as count", setLimitByMuid($option, $_SESSION['muid']));

    return $max_count[0]['count'];
}

/**
 * ユーザマスタ一覧
 */
function getUserMst($option, $edit=false, $column="*")//2007/09/12
{
    $DIR_IMG = DIR_IMG;
    $PHP_SELF = getPHP_SELF()."?".getSID();

    $evid2name = array();
    foreach (FDB::select(T_EVENT,'evid,name') as $tmp) {
        $evid2name[$tmp['evid']] = $tmp['name'];
    }

    /*
     * ページ遷移
     */
    if(isset($_GET['page']))
        $_SESSION[SHOW_USER_MST_SKEY]['page'] = (!is_numeric($_GET['page']) || $_GET['page']<0)? 0:$_GET['page'];
    if(isset($_GET['sort']))
        $_SESSION[SHOW_USER_MST_SKEY]['sort'] = FDB::escape(html_escape(trim($_GET['sort'])), false);

    $option = preg_replace("/( order by .+)/i", "", $option);
    $max_count = FDB::select(T_USER_MST, "count(*) as count", setLimitByMuid($option, $_SESSION['muid']));//2007/09/12
    $max_count = $max_count[0]['count'];//2007/09/12
    $order = "order by {$_SESSION[SHOW_USER_MST_SKEY]['sort']}";
    $result	= FDB::select(T_USER_MST, $column, setLimitByMuid($option, $_SESSION['muid'])."{$order} limit ".PAGE_SHOW_NUM." offset ".PAGE_SHOW_NUM*$_SESSION[SHOW_USER_MST_SKEY]['page']);//2007/09/12
    $comment = FDB::getComment(T_USER_MST);
    $aryColumn = array();
    $aryComment = array();
    foreach ($comment as $value) {
        $aryColumn[] = $value['column'];
        $aryComment[$value['column']]= (is_null($value['comment']))? $value['column']:$value['comment'];
    }

    $tableHtml = getPaging($max_count);//2007/09/12

    $resultHtml = "";
    if (count($result)!=0) {
        if($edit)	$_SESSION[SHOW_USER_MST_SKEY]['serial_no_array'] = array();
        $count = 0;
        foreach ($result as $value) {//2007/09/12
            $tmpHtml = "";
            if ($edit) {
                $_SESSION[SHOW_USER_MST_SKEY]['serial_no_array'][] = $value['serial_no'];
                $value['serial_no'] = html_escape($value['serial_no']);
                $checked = (ereg($value['serial_no'], $_SESSION[SHOW_USER_MST_SKEY]['serial_no_list']))? " checked":"";
                $tmpHtml .= <<<__HTML__
  <td><input type="checkbox" name="serial_no[{$value['serial_no']}]" value="1"{$checked}></td>
  <td><nobr>
    <a href="{$PHP_SELF}&mode=edit&serial_no={$value['serial_no']}"><img src="{$DIR_IMG}edit.gif" width="35" height="17" align="middle" border="0"></a>
    <a href="{$PHP_SELF}&mode=del&serial_no={$value['serial_no']}" onClick="if ( !confirm('削除しますか？') ) { return false; }"><img src="{$DIR_IMG}del.gif" width="35" height="17" align="middle" border="0"></a>
  </nobr></td>
__HTML__;
            }

            foreach ($value as $key => $val) {
                $val = html_escape($val);

                if ($key =='evid') {
                    $evid2name[$val] = html_escape($evid2name[$val]);
                    $val = "<span onmouseover=\"showHelp('{$evid2name[$val]}',this,event)\">{$val}</span>";
                }

                $tmpHtml .= <<<__HTML__
  <td><nobr>{$val}</nobr></td>\n
__HTML__;
            }

            $bgcolor = ($count++%2==0)? "#ffffff":"#f6f6f6";
            $resultHtml .= <<<__HTML__
<tr bgcolor="{$bgcolor}" align="center" height="21" style="font-size:9pt;">
{$tmpHtml}
</tr>
__HTML__;
        }

        if ($column!="*") {
            $aryColumn = explode(",", $column);
        }
        $indexHtml = "";
        if ($edit) {
            $indexHtml .= <<<__HTML__
  <td><br></td>
  <td>編集</td>\n
__HTML__;
        }
        foreach ($aryColumn as $column) {
            $value = html_escape($aryComment[$column]);
            $sorting = getSorting($column);
            $indexHtml .= <<<__HTML__
  <td><nobr>{$value}</nobr><br>{$sorting}</td>\n
__HTML__;
        }

        $tableHtml .= <<<__HTML__
<table width="450" border="0" cellpadding="1" cellspacing="1" bgcolor="#4c4c4c">
<tr bgcolor="#cccccc" align="center" height="21" style="font-size:9pt;">
{$indexHtml}
</tr>
{$resultHtml}
</table>\n
__HTML__;
    }

    return $tableHtml;
}


/**
 * ページング処理
 */
function getPaging($userNum)
{
    $comment	= "";
    $paging		= "";
    if ($userNum==0) {
        $comment = "表示対象ユーザが0件です。";
    } else {
        $now = $_SESSION[SHOW_USER_MST_SKEY]['page'];
        $begin = $now*PAGE_SHOW_NUM + 1;
        $end = $begin + PAGE_SHOW_NUM - 1;
        if($end>$userNum)	$end = $userNum;
        $comment = "全{$userNum}件中{$begin}～{$end}件を表示";

        $pageNum = (int) (($userNum+PAGE_SHOW_NUM-1)/PAGE_SHOW_NUM);
        $pre = $now-1;
        $next = $now+1;

        $paging .= setLink("[先頭]", 'page', ($pre<0)? null:0);
        $paging .= setLink("[前へ]", 'page', ($pre<0)? null:$pre);

        $pageBegin = $now-5;
        $pageEnd = $now+5;
        if ($pageBegin<0) {
            $pageBegin = 0;
            $pageEnd = ($pageNum<10)? $pageNum:10;
        }
        if ($pageEnd>$pageNum) {
            $pageEnd = $pageNum;
            $pageBegin = $pageNum-10;
            if($pageBegin<0) $pageBegin = 0;
        }
        if($pageBegin>0)		$paging .= setLink("…");
        for ($i=$pageBegin; $i<$pageEnd; $i++) {
            $paging .= setLink("", ($i==$now)? null:'page', $i);
        }
        if($pageEnd<$pageNum)	$paging .= setLink("…");

        $paging .= setLink("[次へ]", 'page', ($next>=$pageNum)? null:$next);
        $paging .= setLink("[最後]", 'page', ($next>=$pageNum)? null:$pageNum-1);
    }

    return <<<__HTML__
<div class="page">{$comment}
{$paging}
</div>\n
__HTML__;
}


/**
 * ソーティング処理
 */
function getSorting($column)
{
    if(is_null($column))	return "";
    $nodesc	= setLink("▲", 'sort', ($_SESSION[SHOW_USER_MST_SKEY]['sort']==$column)? null:$column);
    $desc	= setLink("▼", 'sort', ($_SESSION[SHOW_USER_MST_SKEY]['sort']=="{$column} desc")? null:"{$column} desc");

    return <<<__HTML__
<nobr>{$nodesc}{$desc}</nobr>
__HTML__;
}


/**
 * リンクする、リンクしない
 */
function setLink($string="", $name=null, $value=null)
{
    global $PHP_SELF;

    if($string=="" && is_numeric($value))
        $string = $value + 1;

    if (is_null($name) || is_null($value)) {
        return <<<__HTML__
 <span style="color:#999999;">{$string}</span>\n
__HTML__;
    } else {
        return <<<__HTML__
 <a href="{$PHP_SELF}&{$name}={$value}">{$string}</a>\n
__HTML__;
    }
}

/**
 * ユーザマスタの操作か
 */
function isControlUserMst()
{
    return (isset($_GET['page']) || isset($_GET['sort']));
}

/**
 * ユーザマスタの操作セッション消去
 */
function deleteControlUserMst()
{
    $_SESSION[SHOW_USER_MST_SKEY] = array();
    unset($_SESSION[SHOW_USER_MST_SKEY]);
}
