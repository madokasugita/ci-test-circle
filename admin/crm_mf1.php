<?php
/* ---------------------------------------------------------
   ;;;;;;;;;;;;
   ;;;.php;;  by  ipsyste@cbase.co.jp
   ;;;;;;;;;;;;
--------------------------------------------------------- */
//変数セット
define('DIR_ROOT', "../");
require_once(DIR_ROOT."crm_define.php");
require_once(DIR_LIB."CbaseFDB.php");
require_once(DIR_LIB."CbaseFMail.php");
require_once(DIR_LIB."CbaseSortList.php");
require_once(DIR_LIB."ResearchSortListView.php");
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360_Setting.php');
if(!$_POST["csvdownload"])
    encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

$url1 = "crm_mf2.php";
$len1 = 1116;//複製新規のところの文字数制限
$len2 = 1116;//リストの名称のところ
$len3 = 1116;//リストの件名のところ

//ソート処理
$ids = $_GET['sort_mfids'];
if (!is_null($ids)) {
    if (is_good($ids)) {
        $ids = explode(",", $ids);
        $res = Sort_MailFormat($ids);
    } else {
        $res = false;
    }
    echo json_encode($res);
    exit;
}
//複製新規処理
else if (is_good($_POST['duplicate']) && is_good($_POST['dup_mfid'])) {
    $res = Duplicate_MailFormat($_POST['dup_mfid']);
    if(is_false($res))
        $dup_ng = "ひな型の複製に失敗しました。";
    else
        $dup_ok = "ひな型の複製を完了しました。";
}
//削除処理
else if (is_good($_POST['delete']) && is_good($_POST['mfid'])) {
    $res = Delete_MailFormat($_POST['mfid']);
    $mfid = html_escape($_POST['mfid']);
    if(is_false($res))
        $del_ng = "ID:{$mfid}のひな型の削除に失敗しました。";
    else
        $del_ok = "ID:{$mfid}のひな型の削除を完了しました。";
}

$html .= $MESSAGE;

class SortAdapter extends SortTableAdapter
{
    /**
     * オブジェクトをクラス変数にセット
     *
     * @param $class String クラス名
     * @return $this Object
     */
    public function setObject($class)
    {
        $this->{$class} = new $class();

        return $this;
    }
    /**
     * 配信予約されたmfidをセット
     *
     * @return object $this
     */
    public function setReservedMfid()
    {
        $this->reservedMfid = array();
        $result = FDB::getAssoc("SELECT DISTINCT mfid FROM mail_rsv");
        foreach ($result as $v) {
            $this->reservedMfid[] = $v['mfid'];
        }

        return $this;
    }
    public function getResult($where)
    {
        $unbuffered = (bool) $_REQUEST['csvdownload'];
        //return Get_MailFormat(-1,"mfodr","", $_SESSION["muid"], $unbuffered);
        return Get_MailFormat(-1,"mfodr","", $_SESSION["muid"]);
    }

    public function getCount($where)
    {
        $array = Get_MailFormat(-1,"mfodr","", $_SESSION["muid"]);

        return count($array);
    }

    public function getColumns()
    {
        return array(
            "mfid" => "ID"
            ,"name" => "名称"
            ,"title" => "メール件名"
            ,"file" => "添付"
            ,"button" => "編集/削除"
            ,"order" => <<<__HTML__
<nobr>順序<a class="h" title="矢印をクリックしたままスクロールすると<br>順序を並び替えることができます。">？</a></nobr>
__HTML__
        );
    }

    public function getNoSortColumns()
    {
        return array_keys($this->getColumns());
    }

    public function makeCond($values, $key)
    {
        $value = $values[$key];
        switch ($key) {
            default:
                if ($value !== null && $value !== '') {
                    return $key." = ".FDB::escape($value);
                }
                break;
        }

        return null;
    }

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder()
    {
        return 'mfid DESC';
    }

    public function getColumnValue($data, $key)
    {
        global $len2,$len3,$url1;
        $value = $data[$key];
        switch ($key) {
            case 'name':
            case 'title':
                if($key=="name")	$len = $len2;
                else if($key=="title")	$len = $len3;
                if(mb_strlen($value)>$len)
                    $value = mb_substr($value,0,$len)."...";

                return html_escape($value);
                break;
            case 'file':
                $file_flag = false;
                foreach ($GLOBALS['_360_language'] as $k => $v) {
                    if (isset($data['file_'.$k]) || isset($value)) {
                        $file_flag = true;
                        break;
                    }
                }
                if ($file_flag) {
                    $DIR_IMG = DIR_IMG;

                    return <<<__HTML__
<img src="{$DIR_IMG}clip_icon.gif">
__HTML__;
                }
                break;
            case 'button':
                $SID = getSID();
                $mfid =  html_escape($data['mfid']);
                $edit = <<<__HTML__
<form action="{$url1}?{$SID}" method="post" style="display:inline;">
<input type="hidden" name="edit" value="1">
<input type="hidden" name="mfid" value="{$mfid}">
<input type="submit" value="編集" class="white button">
</form>
__HTML__;
                $PHP_SELF = getPHP_SELF();
                $delete = "";
                // 配信予約されたものは削除ボタン非表示
                if (is_array($this->reservedMfid) && !in_array($mfid, $this->reservedMfid)) {
                    $delete = <<<__HTML__
<form action="{$PHP_SELF}?{$SID}" method="post" style="display:inline;">
<input type="hidden" name="delete" value="1">
<input type="hidden" name="mfid" value="{$mfid}">
<input type="submit" value="削除" class="white button" onClick="return confirm('ID:{$mfid}のひな型を削除しますか？');">
</form>
__HTML__;
                }

                return $edit.$delete;
            case 'order':
                $mfid =  html_escape($data['mfid']);

                return <<<__HTML__
<input type="hidden" name="sort_mfids[]" value="{$mfid}">
__HTML__;
            default:
                return html_escape($data[$key]);
        }
    }

    public function getCsvFileName()
    {
        return date('Ymd').'メールひな型'.DATA_FILE_EXTENTION;
    }
    public function getCsvColumnValue($data, $key)
    {

        global $Setting;
        $val = $data[$key];
        switch ($key) {
            case 'body':
            case 'body_1':
            case 'body_2':
            case 'body_3':
            case 'body_4':
                if ($Setting->htmlMail()) {
                    $data[$key] = mb_ereg_replace("[\r\n]", "", $data[$key]);
                    $data[$key] = mb_ereg_replace("<br>", "\n", $data[$key]);
                }

                return $data[$key];
            default :
                return $data[$key];
        }
    }
    public function getCsvColumns()
    {
        $res = array(
            "mfid" => "ID"
            ,"name" => "名称"
        );
        foreach ($GLOBALS['_360_language'] as $k=>$v) {
            $num = $k;
            $key = "_".$num;
            if (is_zero($k)) {	//日本語だけ名称が異なる
                $num = "";
                $key = "";
            }
            $res['title'.$key] = "件名"."(".$v.")";
            $res['body'.$key] = "本文"."(".$v.")";
        }
//		$res['mfodr'] = "並び順";
        return $res;
    }
}

class MailFormatCondTableView extends CondTableView
{
    public function getBox($row, $hidden, $action)
    {
        return "";
    }
}
class MailFormatSortTableView extends ResearchSortTableView
{
    public function __construct()
    {
        parent::__construct();
        $this->setColStyle('mfid', 'align="right" style="width:25px;"');
        $this->setColStyle('name', 'align="left" style="width:180px;font-size:14px;"');
        $this->setColStyle('title', 'align="left" style="width:280px;font-size:14px;"');
        $this->setColStyle('file', 'align="center" style="width:40px;"');
        $this->setColStyle('button', 'align="center" style="width:90px;"');
        $this->setColStyle('order', 'align="center" style="width:40px;" class="dragHandle"');
    }

    public function getBox(&$sortTable, $body)
    {
        return $this->RDTable->getTBody($body, $this->tableWidth, 'id="list"');
    }
}


//メール雛形リスト出力
$array = Get_MailFormat(-1,"mfodr","", $_SESSION["muid"]);

if ($_REQUEST['csvdownload']) {
    $c =& new CondTable(new CondTableAdapter(), new MailFormatCondTableView(), true);
    $s =& new SortTable(new SortAdapter(), new MailFormatSortTableView(), true);
    $sl =& new SearchList($c, $s);
    $sl->show(array('op'=>'search'));
}
/* ----Start HTML----- */

$url_mf_import = "crm_mf_import.php?".getSID();
$url_mf_export = getPHP_SELF()."?".getSID();
$html .=
'<div style="color:#ff0000;">'.$dup_ng.'</div>
<div style="color:#0000ff;">'.$dup_ok.'</div>

<form action="'.getPHP_SELF().'?'.getSID().'" method="post">
';
$select = '<select name="dup_mfid">';
    $select .= '<option value="">-- ここから選択してください --</option>';
foreach ($array as $ar) {
    if(is_void($ar['name']))
        continue;

    if(mb_strlen($ar['name'])>$len1)
        $ar['name'] = mb_substr($ar['name'],0,$len1)."...";
    $ar = escapeHtml($ar);
    $select.= '<option value="'.$ar['mfid'].'">'.$ar['name'].'</option>';
}
$select .= '</select>';
$SID = getSID();
$html.= <<<__HTML__

<div class="simple_overlay" id="mies1">
        <div class="simple_overlay_title">複製元ひな型を選択して下さい</div>
        <div class="simple_overlay_details">
            {$select}
            <input type="submit" name="duplicate" value="複製実行" class="white button">
        </div>
</div>
</form>

<div class="button-container">
<div class="button-group">

<form action="{$url1}?{$SID}" method="post">
    <input type="submit" value="新規作成" class="white button">
</form>
<input type="button" value="複製新規" class="white button" rel="#mies1">

</div><!-- button-group -->
<div class="button-group">

<form action="{$url_mf_import}" method="post" target="_blank">
<input type="submit" name="import" value="インポート" class="white button">
</form>
<form action="{$url_mf_export}" method="post">
<input type="hidden" name="op[search]" value="1">
<input type="submit" name="csvdownload" value="ダウンロード" class="white button">
</form>

</div><!-- button-group -->
</div>
__HTML__
.<<<HTML
<div style="color:#ff0000;">{$del_ng}</div>
<div style="color:#0000ff;">{$del_ok}</div>
HTML;

$c =& new CondTable(new CondTableAdapter(), new MailFormatCondTableView(), true);
$adapter = new SortAdapter();
$adapter->setObject('_360_Setting')->setReservedMfid();
$s =& new SortTable($adapter, new MailFormatSortTableView(), true);
$sl =& new SearchList($c, $s);
$html .= $sl->show(array('op'=>'search'));

$html .='
<table width="430" border="0" cellpadding="0" cellspacing="0">
<tr>
<td valign="middle">
<center>
<font color="#999999" size="2">※最新情報に更新されない時は、「更新」を行ってください。</font>
</center>
</td>
</tr>
</table>
</td>
<td width="10">　</td>
<td width="169" valign="top"><br>
<table width="300" border="0" align="center" cellpadding="0" cellspacing="0" bgcolor="4c4c4c"class="helparea">
<tr>
<td width="270" valign="bottom">
</td>
</tr>

</table>
</td>
</tr>
</table>';

$DIR_IMG = DIR_IMG;
$PHP_SELF = getPHP_SELF();

//HTML排出
$objHtml = new MreAdminHtml("ひな型管理");
$objHtml->setTools();

$objHtml->addFileJs(DIR_JS.'jquery.tablednd_0_5.js');
$css = <<<__CSS__
td.dragHandle{

}
td.showDragHandle{
  background-image:url('{$DIR_IMG}updown.gif');
  background-position:center center;
  background-repeat:no-repeat;
  cursor:move;
}
__CSS__;
$objHtml->setSrcCss($css);
$js = <<<__JS__
$(function () {
  $('#list').tableDnD({
    onDrop: function (table, row) {
      var ids = '';
      var elms = document.getElementsByName('sort_mfids[]');
      for (var i=0; i<elms.length; ++i) {
        ids += ',' + elms[i].value;
      }
      ids = ids.replace(/^,/, '');
      $.getJSON('{$PHP_SELF}?{$SID}', {sort_mfids: ids}, function (res) {
          if(res!=true)
            alert('表示順の変更に失敗しました。\\nメニューより、再度ページを開き、変更してください。');
      });
    }
    ,onDragStyle: {'color':'#ff00ff'}
    ,onDragClass: null
    ,onDropStyle: {'color':''}
    ,onDropClass: null
    ,dragHandle: 'dragHandle'
  });
  $('#list .dragHandle').addClass('showDragHandle');
    $('.h').hoverbox();
});


__JS__;
$objHtml->setSrcJs($js);
$objHtml->addFileJs(DIR_JS."jquery.hoverbox.min.js");

echo $objHtml->getMainHtml($html);
exit;
