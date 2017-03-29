<?php
//define("DEBUG",1);
define('DIR_ROOT', '../');
require_once DIR_ROOT . "crm_define.php";
require_once DIR_LIB . "CbaseFunction.php";
require_once DIR_LIB . "360_FHtml.php";
require_once DIR_LIB . "CbaseFErrorMSG.php";
require_once DIR_LIB . "CbaseFForm.php";
require_once DIR_LIB . "CbaseFManage.php";
require_once DIR_LIB . "CbaseEncoding.php";
require_once DIR_LIB . "CbaseFError.php";
require_once DIR_LIB . "CbaseFFile2.php";
require_once(DIR_LIB.'CbaseFunction.php');
encodeWebInAll();
session_start();

Check_AuthMng(basename(__FILE__));

if (is_good($_GET['tmpFile']) && $_GET['tmpHash']==md5(SYSTEM_RANDOM_STRING.$_GET['tmpFile'])) {
    garbage_collection(TMP_FILE_PREFIX);
    $tmpFile = DIR_TMP.$_GET['tmpFile'];
    if (file_exists($tmpFile)) {
        if($_GET['sheet']=='all')
            $n.='_全て';
        else
            $n.='_'.replaceMessage($_360_sheet_type[$_GET['sheet']]);

        if($_GET['type']=='all')
            $n.='_全て';
        else
            $n.='_'.replaceMessage($_360_user_type[$_GET['type']]);

        if($_GET['lang']=='all')
            $n.='_全て';
        else
            $n.='_'.replaceMessage($_360_language[$_GET['lang']]);

        $filename = date("Ymd")."_回答コメント{$n}".DATA_FILE_EXTENTION;
        download_dialog($tmpFile, $filename);
        exit;
    } else {
        encodeWebOutAll();
        echo <<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">■既にダウンロード済みです。</b><br>
<br><br>
<a href="#" style="font-weight:bold;" onClick="window.close();">ウィンドウを閉じる</a>
</p>
__HTML__;
        exit;
    }
}

class Page
{

    /**
     * 引数を確認し、エラーなら表示して終了。クラス関数でクラス外の処理すべてにつける。
     * @param  mixed $obj なんでもいい
     * @return mixed $objが返る
     * @author Cbase akama
     */
    public function run($obj)
    {
        if (FError :: is($obj)) {
            echo $obj->getInfo();
            exit;
        }

        return $obj;
    }

    /**
     * ファクトリーメソッド。=&で受け取ること。
     * @param  int    $evid evid
     * @return object ConfirmMaker 生成したオブジェクト
     * @author Cbase akama
     */
    function & create($prm)
    {
        $instance = new Page();
        if (FError :: is($err = $instance->initialize($prm))) {
            return $err;
        }

        return $instance;
    }

    /**
     * POSTから操作を取得する
     * @param  array $prmPost $_POST
     * @return array 1=>操作 2以降=>引数
     * @author Cbase akama
     */
    public function getOperation($prmPost)
    {
        foreach ($prmPost as $key => $val) {
            $op = explode(":", $key);
            if ($op[0] == "op")
                return $op;
        }

        return false;
    }

    //画面が順番に移るタイプのmain
    public function main($post)
    {
        $op = $this->getOperation($post);
        mb_http_output("pass");
        ob_implicit_flush(true);
        echo replaceCharsetTag(encodeWebOut($this->getHeaderHtml()));
        echo encodeWebOut($this->DL_Msg0());

        $tmpFile = $this->openTmpFile();

        // データ処理
        echo encodeWebOut($this->DL_Msg1());
        echo encodeWebOut($this->DL_Bar());

        ob_end_flush();
        $this->order($op, $post);
        // ファイル作成
        echo encodeWebOut($this->DL_Msg2());
        ob_end_flush();

        $this->closeTmpFile();

        // ファイル作成完了
        echo encodeWebOut($this->DL_Msg3($tmpFile));
        ob_end_flush();

        echo encodeWebOut($this->getFooterHtml());
        exit;

    }

    public $tmpFile;
    public $tmpFp;
    public function openTmpFile()
    {
        do {
            $this->tmpFile = TMP_FILE_PREFIX . md5(uniqid(rand(), true));
        } while (file_exists(DIR_TMP . $this->tmpFile));
        $this->tmpFp = @ fopen(DIR_TMP . $this->tmpFile, "w");
        if (!$this->tmpFp)
            return false;
        return $this->tmpFile;
    }

    public function writeTmpFile($aryData)
    {
        fwrite($this->tmpFp, implode(OUTPUT_CSV_DELIMITER, csv_quoteArray($aryData)) . "\r\n");

        return;
    }

    public function closeTmpFile()
    {


        fclose($this->tmpFp);
        syncCopy(DIR_TMP . $this->tmpFile);

        return;
    }

    public function DL_Bar()
    {
        return<<<__HTML__
<style type="text/css">
.graph
{
    position: relative;
    width: 400px;
    border: 1px solid #f17662;
    padding: 2px;
}
.graph .bar
{
    display: block;
    position: relative;
    background: #f17662;
}
</style>
<script type="text/javascript">
function p(p,i,m)
{
    document.getElementById('percent').innerHTML = p + '%' + '('+i+'/'+m+')';
    document.getElementById('bar').style.width = p + '%';
}
</script>
<table align="left">
<tr>
  <td class="graph"><span class="bar" style="width: 0%;" id="bar"><br></span></td>
  <td id="percent"></td>
</tr>
</table>
<br><br>
__HTML__;
    }

    public function DL_Percent($p, $i, $m)
    {
        return<<<__HTML__
<script>p({$p},{$i},{$m});</script>
__HTML__;
    }

    public function DL_Msg0()
    {
        return<<<__HTML__
<p align="left" style="font-size:15px;">
ダウンロード用ファイルを作成しています。しばらくお待ちください。<br>
ファイルの作成完了後、ダウンロード用リンクが表示されます。
</p>
__HTML__;
    }

    public function DL_Msg1()
    {
        return<<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9312;データ処理中</b><br>
人数分のデータを処理しています。
</p>
__HTML__;
    }

    public function DL_Msg2()
    {
        return<<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9313;ファイル作成中</b><br>
ファイルを作成しています。10秒程度かかります。
</p>
__HTML__;
    }

    public function DL_Msg3($tmpFile)
    {
        global $PHP_SELF;
        $SID = getSID();
        if (is_false($tmpFile)) {
            return<<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9314;ファイル作成エラー</b><br>
ファイルの作成に失敗しました。
</p>
__HTML__;
        }
        $tmpHash = md5(SYSTEM_RANDOM_STRING . $tmpFile);
        $QUERY = html_escape("tmpFile={$tmpFile}&tmpHash={$tmpHash}&enquete={$_POST['enquete']}");

        $q['sheet'] =$_REQUEST['sheet'];
        $q['type'] = $_REQUEST['type'];
        $q['lang'] = $_REQUEST['lang'];
        $qqq = '';
        foreach ($q as $k => $v) {
            $qqq .=$k.'='.html_escape($v).'&';

        }

        return<<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9314;ファイル作成完了</b><br>
ファイルの作成が完了しました。
<br><br>
<a href="{$PHP_SELF}?{$qqq}{$SID}&{$QUERY}" style="font-weight:bold;">ダウンロードする</a>
</p>
__HTML__;
    }

    //------------------------------------------------------------------
    //継承部分ここまで
    //------------------------------------------------------------------

    //----------------------------------
    //メンバ変数とプロパティ

    public $term;
    public $lfbType;
    public $permitted;

    //----------------------------------

    //▼▼　Controler　▼▼

    public function initialize($prm)
    {

    }

    public function order($op, $post)
    {
        $this->getListView($this->getListSet($post));
    }

    public function getListSet($post)
    {

        $where = array ();
        if ($post['type'] != 'all') {
            $where[] = "a.evid % 100 = ".FDB::escape($post['type']);
        }
        if ($post['sheet'] != 'all') {
            $where[] = "(a.evid - a.evid % 100)/100 = ".FDB::escape($post['sheet']);
        }
        if ($post['status'] != 'all') {
            $where[] = "a.answer_state = ".FDB::escape($post['status']);
        }

        if($post['lang']!='all')
            $where[] = "b.lang_type = ".FDB::escape($post['lang']);

        if($post['lang_']!='all')
            $where[] = "c.lang_type = ".FDB::escape($post['lang_']);
        $where[] = "answer_state in (0,10)";

        $where[] = getDivWhere('b');

        $where = implode(" AND ", $where);
        if ($where) {
            $where = " WHERE " . $where;
        }

        $T_EVENT_SUB_DATA = T_EVENT_SUB_DATA;
        $T_EVENT_DATA = T_EVENT_DATA;
        $T_EVENT_SUB = T_EVENT_SUB;
        $T_USER_MST = T_USER_MST;
        $SQL =<<<SQL
SELECT
event_data_id,
a.evid,
a.answer_state,
a.udate,
a.ucount,
c.name as name_a,
c.uid as uid_a,
c.div1 as div1_a,
c.div2 as div2_a,
c.lang_type as lang_,
b.name,
b.uid,
b.div1,
b.div2,
b.div3,
b.lang_type as lang
FROM {$T_EVENT_DATA} a
LEFT JOIN {$T_USER_MST} b on a.target = b.serial_no
LEFT JOIN {$T_USER_MST} c on a.serial_no = c.serial_no
{$where} ORDER BY answer_state,a.target,a.evid,a.serial_no
SQL;
        $users = array();
        foreach (FDB::getAssoc($SQL) as $user) {
            $event_data_id = FDB::escape($user['event_data_id']);
            $SQL =<<<SQL
select
a.other,a.seid
FROM {$T_EVENT_SUB_DATA} a
LEFT JOIN {$T_EVENT_SUB} b on a.seid = b.seid
WHERE event_data_id = {$event_data_id} and type2 = 't' and rows > 1 ORDER BY seid;
SQL;
            $user['others'] = FDB::getAssoc($SQL);

            if(is_void($user['others'])) continue;

            $users[] = $user;
        }

        return $users;

    }

    public function callbackListSort($a, $b)
    {
        $comp = 0; //$this->compareBS($a, $b);

        return $comp;
    }

    //二者の<>比較
    public function compareBS($a, $b)
    {
        if ($a == $b) {
            return 0;
        } elseif ($a > $b) {
            return 1;
        } else {
            return -1;
        }
    }

    //▼▼　View　▼▼

    public function getListView($shows)
    {
        $max = count($shows);
        $aryIndex = $this->getListIndex();
        $cntData = count($aryIndex);

        $this->writeTmpFile($aryIndex);
        foreach ($shows as $i => $show) {
            echo encodeWebOut($this->DL_Percent(round(100 * $i / $max), $i, $max));
            ob_end_flush();
            $this->writeTmpFile($this->getListLine($show, $cntData));
        }
        echo encodeWebOut($this->DL_Percent(100, $max, $max));
        ob_end_flush();

        return;
    }

    public function getListIndex()
    {
        $aryIndex = array ();
        $aryIndex[] = "コメントID";
        $aryIndex[] = "状況";
        $aryIndex[] = "シートタイプ";
        $aryIndex[] = "入力者区分";
        $aryIndex[] = "入力者言語";
        $aryIndex[] = "入力者ID";
        $aryIndex[] = "入力者氏名";
        $aryIndex[] = "入力者所属";

        $aryIndex[] = "対象者ID";
        $aryIndex[] = "対象者氏名";
        $aryIndex[] = "対象者所属";
        $aryIndex[] = "対象者言語";
        $aryIndex[] = "更新時間";
        $aryIndex[] = "コメント更新回数";
        $aryIndex[] = "修正チェック";

        $comment_count = FDB::select1(T_EVENT_SUB, "count(evid) as count", "WHERE type2 = 't' GROUP BY evid ORDER BY count DESC");
        $comment_count = (is_void($comment_count))? 1:$comment_count;
        foreach(range(1, $comment_count['count']) as $i)
            $aryIndex[] = "コメント".$i;

        return $aryIndex;
    }

    public function getListLine($show, $cntData)
    {
        global $type2evids,$_360_user_type,$_360_sheet_type,$_360_sheet_type;

        $seids = array();
        if(is_array($show['others']))
        foreach ($show['others'] as $other) {
            $seids[] = $other['seid'];
        }
        $aryData[] = getCommentId($show['event_data_id'], $show['evid'])."/".implode(':',$seids);
        $aryData[] = getAnswerStateName($show['answer_state']);
        $aryData[] = replaceMessage($_360_sheet_type[floor($show['evid']/100)]);
        $aryData[] = replaceMessage($_360_user_type[$show['evid']%100]);
        $aryData[] = $GLOBALS['_360_language'][$show['lang_']];

        $aryData[] = $show['uid_a'];
        $aryData[] = $show['name_a'];
        $aryData[] = getDiv1NameById($show['div1_a'])." ".getDiv2NameById($show['div2_a']);

        $aryData[] = $show['uid'];
        $aryData[] = $show['name'];
        $aryData[] = getDiv1NameById($show['div1'])." ".getDiv2NameById($show['div2']);
        $aryData[] = $GLOBALS['_360_language'][$show['lang']];
        $aryData[] = $show['udate'];
        $aryData[] = $show['ucount'];
        $aryData[] = "0";

        if(is_array($show['others']))
        foreach ($show['others'] as $other) {
            //エクセルでエラーを起こす可能性のある先頭文字前に空白挿入
            $other['other'] = preg_replace("/^(\*|\+|%|\/|-|=)/", " $1", $other['other']);
            $aryData[] = $other['other'];
        }

        return $aryData;
    }

    /**
     * 文字列をエラー表示用タグで囲んで返す
     * @author Cbase akama
     */
    public function getErrorTag($str)
    {
        $str = html_escape($str);

        return<<<__HTML__
<br><div style="font-size:smaller;color:#ff0000;">{$str}</div>
__HTML__;
    }

    /**
     * 全ページは出力前に必ずここを通り、ここの戻り値を表示する
     * 各ページ共通のヘッダーフッターなどを記述
     * @param string $html 表示予定内容
     * @author Cbase akama
     */
    public function getOutputHtml($html)
    {
        $header = $this->getHeaderHtml();
        $footer = $this->getFooterHtml();
        $html = $header . $html . $footer;

        return $html;
    }

    /**
     * ヘッダーとして使われるhtmlを取得
     * @author Cbase akama
     */
    public function getHeaderHtml()
    {
        return<<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=EUC-JP">
<meta http-equiv="Content-Style-Type" content="text/css">
<title>{$title}</title>
<style type="text/css">
td{
    font-size:13px;
}

.state0{
    background-color:#ffb0b0;
}
.state1{
    background-color:#ffd0d0;
    filter: progid:DXImageTransform.Microsoft.gradient(startcolorstr=#ffb0b0, endcolorstr=#ffffff, gradienttype=1);
}
.state2{
    background-color:#ffffff;
}
.state9{
    background-color:#999999;
}
</style>
</head>
<body>
<h1>{$title}</h1>
<div align="center">
__HTML__;
    }

    /**
     * フッターとして使われるhtmlを取得
     * @author Cbase akama
     */
    public function getFooterHtml()
    {
        return<<<__HTML__
</div>
</div></div>
</body>
</html>
__HTML__;
    }

}

function getCommentId($evdid, $evid)
{
    return $evdid.'_'.$evid.'_'.substr(sha1($evdid.$evid.SECRET_KEY), 0, 8);
}
function getAnswerStateName($val)
{
    $ary = array(
        0 => '完了',
        10 => '途中',
    );

    return $ary[$val];

}
Page :: run($main = & Page :: Create($_GET));
Page :: run($view = $main->main($_POST));

print $view;
exit;
