<?php

// 一時ファイルの接頭辞(ガーベジコレクションのため)
define('TMP_FILE_PREFIX', "360data_");

class ExportPage
{
    /**
     * 引数を確認し、エラーなら表示して終了。クラス関数でクラス外の処理すべてにつける。
     * @param  mixed $obj なんでもいい
     * @return mixed $objが返る
     * @author Cbase akama
     */
    public function run($obj)
    {
        if (FError::is($obj)) {
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
    function &create($prm)
    {
        $instance = new ExportPage();
        if (FError::is($err = $instance->initialize ($prm))) {
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
            if($op[0] == "op") return $op;
        }

        return false;
    }

    //画面が順番に移るタイプのmain
    public function main($post)
    {
        $this->DL_dialog();

        $op = $this->getOperation($post);
        if ($op) {
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
        } else {
            encodeWebOutAll();
            $html = $this->getMainView();
        }

        return $this->getOutputHtml($html);
    }

    public $tmpFile;
    public $tmpFp;
    public function openTmpFile()
    {
        do { $this->tmpFile = TMP_FILE_PREFIX.md5(uniqid(rand(), true)); } while (file_exists(DIR_TMP.$this->tmpFile));
        $this->tmpFp = @fopen(DIR_TMP.$this->tmpFile, "w");
        if(!$this->tmpFp) return false;

        return $this->tmpFile;
    }

    public function writeTmpFile($aryData)
    {
        $data = "";
        if(is_good($aryData))
            $data = implode(OUTPUT_CSV_DELIMITER, csv_quoteArray($aryData));
        fwrite($this->tmpFp, $data."\n");

        return;
    }

    public function closeTmpFile()
    {
        fclose($this->tmpFp);
        syncCopy(DIR_TMP.$this->tmpFile);

        return;
    }

    public function DL_Bar()
    {
        return <<<__HTML__
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
        return <<<__HTML__
<script>p({$p},{$i},{$m});</script>
__HTML__;
    }

    public function DL_Msg0()
    {
        return <<<__HTML__
<p align="left" style="font-size:15px;">
ダウンロード用ファイルを作成しています。しばらくお待ちください。<br>
ファイルの作成完了後、ダウンロード用リンクが表示されます。
</p>
__HTML__;
    }

    public function DL_Msg1()
    {
        return <<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9312;データ処理中</b><br>
人数分のデータを処理しています。
</p>
__HTML__;
    }

    public function DL_Msg2()
    {
        return <<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9313;ファイル作成中</b><br>
ファイルを作成しています。10秒程度かかります。
</p>
__HTML__;
    }

    public function DL_Msg3($tmpFile)
    {
        global $PHP_SELF,$SID;
        if (is_false($tmpFile)) {
            return <<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9314;ファイル作成エラー</b><br>
ファイルの作成に失敗しました。
</p>
__HTML__;
        }
        $tmpHash = md5(SYSTEM_RANDOM_STRING.$tmpFile);
        $QUERY = "";
        if(is_good($_POST['enquete']))		$QUERY .= "&enquete={$_POST['enquete']}";
        $QUERY = html_escape("tmpFile={$tmpFile}&tmpHash={$tmpHash}{$QUERY}");

        return <<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">&#9314;ファイル作成完了</b><br>
ファイルの作成が完了しました。
<br><br>
<a href="{$PHP_SELF}?{$SID}&type={$this->lfbType}&{$QUERY}" style="font-weight:bold;">ダウンロードする</a>
</p>
__HTML__;
    }

    public function DL_Dialog()
    {
        if (is_good($_GET['tmpFile']) && $_GET['tmpHash']==md5(SYSTEM_RANDOM_STRING.$_GET['tmpFile'])) {
            garbage_collection(TMP_FILE_PREFIX);
            $tmpFile = DIR_TMP.$_GET['tmpFile'];
            if (file_exists($tmpFile)) {
                download_dialog($tmpFile, $this->getDownloadFilename());
                exit;
            } else {
                encodeWebOutAll();
                echo $this->getOutputHtml($this->DL_Close());
                exit;
            }
        }
    }

    public function DL_Close()
    {
        return <<<__HTML__
<p align="left" style="font-size:15px;">
<b style="color:#f17662;">■既にダウンロード済みです。</b><br>
<br><br>
<a href="#" style="font-weight:bold;" onClick="window.close();">ウィンドウを閉じる</a>
</p>
__HTML__;
    }

    /**
     * 文字列をエラー表示用タグで囲んで返す
     * @author Cbase akama
     */
    public function getErrorTag($str)
    {
        $str = html_escape($str);

        return <<<__HTML__
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
        $html = $header.$html.$footer;

        return $html;
    }

    /**
     * ヘッダーとして使われるhtmlを取得
     * @author Cbase akama
     */
    public function getHeaderHtml()
    {
        $DIR_IMG = DIR_IMG;
        $bgcolor = html_escape($this->getBodyBgcolor());
        $title = html_escape($this->getPageTitle());

        return <<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=EUC-JP">
<meta http-equiv="Content-Style-Type" content="text/css">
<title>{$title}</title>
<link rel="stylesheet" href="{$DIR_IMG}360_adminpage.css" type="text/css">
<script src="{$DIR_IMG}common.js" type="text/javascript"></script>
<script src="{$DIR_IMG}myconfirm.js" type="text/javascript"></script>
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
<body bgcolor="{$bgcolor}">
<div id="container-iframe">
<div id="main-iframe">
<h1>{$title}</h1>
__HTML__;
    }

    /**
     * フッターとして使われるhtmlを取得
     * @author Cbase akama
     */
    public function getFooterHtml()
    {
        return <<<__HTML__
</div></div>
</body>
</html>
__HTML__;
    }

    public $term;
    public $lfbType;
    public $permitted;

    public function initialize($prm)
    {
        $result = $this->setLfbType($prm["type"]);
        if (FError::is($result))
            return $result;
        $ses = $_SESSION['auth'][$prm["type"]];
        $result = $this->setAuthLevel($ses["level"], $ses["div"]);//ver1.1

        return $result;
    }

    public function order($op, $post)
    {
        switch ($op[1]) {
            case "run":
                $this->switchTotalLimit($post);
                return;
            default:
                encodeWebOutAll();
                $result = new CbaseException("不正なPOST命令");
                break;
        }

        return $result;
    }

    public function switchTotalLimit($post)
    {
        switch ($post['total_limit']) {
            case "1": // 全体集計
                $this->getAllListView($this->getListSet($post));
                return;

            default:  // 個別集計
                $this->getListView($this->getListSet($post));
                break;
        }
    }

    public function setLfbType()
    {
        $this->lfbType = (int) $_REQUEST['sheet_type'];

        return true;
    }

    public function setAuthLevel($level, $div)
    {
        switch ($level) {
            case 0:
                $this->permitted = false;
                break;
            case 1:
            case 2:
            case 3:
            case 4:
                $this->permitted = $div;
                break;
            default:
                encodeWebOutAll();

                return new CbaseException("この機能を利用する権限がありません");
                break;
        }

        return true;
    }

    public function getBodyBgcolor()
    {
        global $type_color;

        return $type_color[$this->lfbType];
    }

    // ページタイトル
    public function getPageTitle()
    {
        global $type_name;
        return "{$type_name[$this->sheet_type]} 集計値ダウンロード";
    }

    // ダウンロードファイル名
    public function getDownloadFilename()
    {
        global $type_name;
        $csvName = (is_good($this->csvName)) ? $this->csvName : '';
        $csvName = date('Ymd').'評価集計値データ'.$csvName.$GLOBALS['_360_sheet_type'][$_GET['type']].'.csv';

        return replaceMessage($csvName);

    }

    public function setCsvName($name)
    {
        $this->csvName = $name;

        return $this;
    }

    /**
     * 最初に表示される画面のデザインを取得
     * $def デフォルト表示データ
     * $error エラー文字列データ
     * @author Cbase akama
     */
    public function getMainView ($def=array(), $error=array())
    {
        $show["submit"] = FForm::submit ("op:run", "ダウンロード",' onSubmit="return this.flag?false:this.flag=true;"class="white button wide"');

        $show["test_flag"] = implode("", FForm::replaceArrayChecked(FForm::radiolist('test_flag', array("含まない", "含む", "テストユーザーのみ")), 0));

        $show["total_limit"] = implode("", FForm::replaceArrayChecked(FForm::radiolist('total_limit', array("個別集計", "全体集計")), 0));

        $html = $this->getMainHtml($show);

        return $html;
    }

    /**
     * メイン画面のデザイン
     * $showに表示内容の連想配列が入ってくる
     * @author Cbase akama
     */
    public function getMainHtml($show)
    {
        global $PHP_SELF,$SID;
//■■■ HTML ■■■
        $radio = getHtmlSheetTypeRadio();

        return <<<__HTML__
<form method="POST" action="{$PHP_SELF}?{$SID}&type={$this->lfbType}" target="_blank">
<table class="cont"style="width:auto;margin:20px 30px"border="0" cellspacing="1" cellpadding="3" bgcolor="#000000">
<tr>
  <th bgcolor="#eeeeee" align="right">シート</th>
  <td bgcolor="#ffffff">{$radio}</td>
</tr>

<tr>
  <th bgcolor="#eeeeee" align="right">テストユーザー</th>
  <td bgcolor="#ffffff">{$show['test_flag']}</td>
</tr>

<tr>
  <th bgcolor="#eeeeee" align="right">集計枠</th>
  <td bgcolor="#ffffff">{$show['total_limit']}</td>
</tr>

<tr>
  <th bgcolor="#eeeeee"></th>
  <td bgcolor="#ffffff" align="center">{$show['submit']}</td>
</tr>
</table>
</form>
__HTML__;
    }

    public function getListSet($post)
    {
        $where = $this->makePostCondition($post);
        //条件を元にserial_noを取得
        return FDB::select(T_USER_MST, 'serial_no', $where." order by uid,div1,div2,div3");
    }

    public function makePostCondition($post)
    {
        $this->sheet_type = $post['sheet_type'];
        $this->total_limit = $this->total->total_limit = $post['total_limit'];
        $where = array();
        $where[] = "mflag = 1";
        $where[] = "sheet_type = ".FDB::escape($this->sheet_type);
        if($post['test_flag'] == '0')
            $where[] = "test_flag != 1";
        if($post['test_flag'] == '2')
            $where[] = 'test_flag = 1';
        $where = (count($where)) ? "WHERE ".implode(" AND ", $where) : "";

        return $where;
    }
}

/**
 * シート選択用
 */
function benesse_getEnquete($lfbType, $evid="")
{
    switch ($lfbType) {
        case 'lfb1':
            $count = 10;
            break;
        case 'lfb2':
            $count = 20;
            break;
        case 'lfb3':
            $count = 30;
            break;
        default:
            return;
    }
    $enq = array(
        $count		=> "本人"
        ,++$count	=> "同僚（その他）"
        ,++$count	=> "部下（下位者）"
        ,++$count	=> "上司（上位者）"
    );

    return (is_good($evid))? $enq[$evid]:$enq;
}

/**
 * 状態選択用
 */
function benesse_getAnswerState()
{
    return array(
        '0'		=> "完了のみ"
        ,'10'	=> "途中保存のみ"
        ,''		=> "両方"
    );
}

/**
 * 入力者区分
 */
function benesse_getEnqueteCls($evid)
{
    return $evid%10+1;
}
