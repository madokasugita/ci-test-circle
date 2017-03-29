<?php

define('NOT_CONVERT',1);
//eventのデータを更新する
//define('DEBUG',1 );
define("DIR_ROOT", "../");
require_once (DIR_ROOT . "crm_define.php");
require_once (DIR_LIB . "CbaseFEnquete.php");
require_once (DIR_LIB . "CbaseEnquete.php");
require_once (DIR_LIB . "CbaseFGeneral.php");
require_once (DIR_LIB . "CbaseFError.php");
require_once (DIR_LIB . "CbaseFCheckModule.php");
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . '360Design.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));
if(!is_null($_GET['evid']))	$evid = Check_AuthMngEvid($_GET['evid']);
else							$evid = null;

//アンケートデータ取得
if ($evid) {
    $array1 = Get_Enquete("id", $evid, "", "");

    $array = $array1[-1];
}
define('FILE_AUTH_HTML',DIR_ROOT.'auth_'.$array['rid'].'.html');

//更新
if ($_POST["main"] || $_POST["main_x"]||$_POST["create_auth_html"]) {
    unset ($array);
    $array = $_POST;

    $array['flgo'] = $array['flgo'] + $array['flgo_'] * 2;

    //日付データ処理
    //sdate(開始日時)
    //日時指定
    $tmp = Convert_Date("db", array (
        $_POST["s_y"],
        $_POST["s_m"],
        $_POST["s_d"],
        $_POST["s_h"],
        $_POST["s_i"],
        "0"
    ));
    if ($tmp)
        $array["sdate"] = $tmp;
    //設定するを選択したとき
    if ($_POST["on_sdate"] == "1")
        $array["sdate"] = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));
    //使用しないを選択したとき
    if ($_POST["on_sdate"] == "2")
        unset ($array["sdate"]);
    //edate(終了日時)
    //日時指定
    $tmp = Convert_Date("db", array (
        $_POST["e_y"],
        $_POST["e_m"],
        $_POST["e_d"],
        $_POST["e_h"],
        $_POST["e_i"],
        "0"
    ));
    if ($tmp)
        $array["edate"] = $tmp;
    //設定するを選択したとき
    if ($_POST["on_edate"] == "1")
        $array["edate"] = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));
    //使用しないを選択したとき
    if ($_POST["on_edate"] == "2")
        unset ($array["edate"]);

    //各html 指定html or 雛形呼び出し
    if (!$array["htmlh"]) {
        //$array["htmlh"] = encodeFileIn(file_get_contents(DIR_TMPL . "tmpl_htmlh.txt"));
        //$array["htmlh"] = $array["htmlh"];
    }
    if (!$array["htmlf"]) {
    //	$array["htmlf"] = encodeFileIn(file_get_contents(DIR_TMPL . "tmpl_htmlf.txt"));
    //	$array["htmlf"] = $array["htmlf"];
    }
    if (!$array["htmls"]) {
        $array["htmls"] = encodeFileIn(file_get_contents(DIR_TMPL . "tmpl_htmls.txt"));
        $array["htmls"] = $array["htmls"];
    }
    if (!$array["htmlm"]) {
        $array["htmlm"] = encodeFileIn(file_get_contents(DIR_TMPL . "tmpl_htmlm.txt"));
        $array["htmlm"] = $array["htmlm"];
    }
    $postError = EnqEvent :: checkError($array);
    if (!$postError) {
        //データ更新
        $key = "update";
        if (!$_POST["evid"])
            $key = "new";
        $evid = Save_Enquete($key, $array);
        //再度データ取得
        unset ($array);
        unset ($array1);
        $array1 = Get_Enquete("id", $evid, "", "");
        $array = $array1[-1];
        $message = <<<__HTML__
<br><span style="color:red;">編集した内容は、"更新を本番に反映"ボタンを押さないと、本番に反映されません。</span>
__HTML__;
    }
} elseif ($_POST['cclear']) {
    FEnqueteCache::setLatestBackUpEvent($array["rid"], true);/* backup_event追加 */
    transClearCache($array["rid"]); //キャッシュ消しとく。
    $message = '<br><span style="color:red;font-weight:bold">更新を反映しました。</span>';
}

$enquete = Enquete::fromArray($array1);

//sdateを配列に格納
$s_datear = Convert_Date("array", $array["sdate"]);
//edateを配列に格納
$e_datear = Convert_Date("array", $array["edate"]);

$tmp = $array;
$array = array ();
foreach ($tmp as $k => $v) {
    $array[$k] = transHtmlentities($v);
}
$phpSelf = getPHP_SELF();
$evid = html_escape($evid);
$title= D360::getTitle('アンケート新規作成/編集','アンケートの登録・編集を行います。','message');
$form_tag = '<form style="display:inline" action="' . $phpSelf . '?' . getSID() . ((is_null($evid))? '':'&evid='.$evid) . '" method="post">';
$DIR_IMG = DIR_IMG;
//フォーム表示
$html .= '
<script>imgPreLoad(new Array("'.$DIR_IMG.'box_top.gif","'.$DIR_IMG.'box_tale.gif","'.$DIR_IMG.'box_bottom.gif","'.$DIR_IMG.'box_bg.gif"));</script>
'.$title.'
<table width="600" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="431"> <center>
        <table width="550" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td width="260" height="13">　 </td>
            <td width="140" align="right">
            <a href="' . $phpSelf . '?' . getSID() . ((is_null($evid))? '':'&evid='.$evid) . '">
            <img src="'.$DIR_IMG.'change.gif" width="61" height="12" border=0></a>
            <a href="crm_enq0.php?' . getSID() . '">
            <img src="'.$DIR_IMG.'back.gif" width="61" height="12" border=0></a>
            </td>
          </tr>
        </table>';
$html .= $postError["error"] . '
<table width="550" border="0" cellpadding="0" cellspacing="0">

          <tr>
            <td width="13" valign="middle"> <center>
                <img src="'.$DIR_IMG.'icon_inf.gif" width="13" height="13"> </center></td>
            <td width="135" valign="middle"><font size="2">アンケート基本設定</font></td>
            <td width="295" valign="middle">';


//$formFlgs[2] = '<input name="flgs" value="2" type="radio"'.($array["flgs"]==2? " checked":"").'> ID,PW認証連動';

$tmpurl = Create_QueryString(Get_RandID(8), $array["rid"], 1, "A");
$prev = DOMAIN . DIR_MAIN . PG_PREVIEW . '?rid=' . $tmpurl;
$test = DOMAIN . DIR_MAIN . 'test_index.php' . '?' . $tmpurl;
$url = DOMAIN . DIR_MAIN . '?' . $tmpurl;

$linkPreview = '<a href="' . $prev . '" target="_blank">プレビュー</a>';

$cacheClearButton =<<<HTML
{$form_tag}
<input id="cache_control" type="submit" name="cclear" value="更新を本番に反映" onclick="return myconfirm('更新した内容を本番に反映しますか？')">{$message}
</form>
HTML;

$setup = "";
foreach ($GLOBALS['_360_language'] as $k => $l) {
    if($k==-1)
        continue;
    $k = (int) $k;
    $file = (is_zero($k))? "{$array['evid']}.csv":"{$array['evid']}_{$k}.csv";
    $file = DIR_ROOT."enqcsv/{$file}";
    $time = (file_exists($file))? date("Y/m/d H:i:s", filemtime($file)):"未設定";
    $setup .= <<<__HTML__
{$l} {$time}<br>
__HTML__;
}

$html .=<<<__HTML__
                </td>
          </tr>
        </table>
        <table width="550" border="0" cellpadding="0" cellspacing="0">
__HTML__;

      if ($array["evid"]) {
$html .=<<<__HTML__
          <tr>
            <td valign="middle"> <div align="right"><font size="2">基本情報</font>
              </div></td>
            <td valign="middle"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
            <td valign="middle"><font size="2">
              ID : {$array["evid"]}&nbsp;&nbsp; RID : {$array["rid"]}&nbsp;&nbsp; {$cacheClearButton}</font></td>
          </tr>
           <tr>
            <td valign="middle"> <div align="right"><font size="2">URL</font>
              </div></td>
            <td valign="middle"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
            <td valign="middle"><font size="2">
               {$link}&nbsp;&nbsp; {$linkTest}&nbsp;&nbsp; {$linkPreview}</font></td>
          </tr>
           <tr>
            <td valign="middle"> <div align="right"><font size="2">設定ファイル<br>最終更新日時</font>
              </div></td>
            <td valign="middle"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
            <td valign="middle"><font size="2">
               {$setup}</font></td>
          </tr>
__HTML__;
      }
$html .=<<<__HTML__
{$form_tag}
          <tr>
            <td width="170" valign="middle"> <div align="right"><font size="2">管理用タイトル</font>
              </div></td>
            <td valign="middle"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
            <td width="300" valign="middle"><font size="2">
              <input name="name" type="text" size="40" value="{$array["name"]}"> {$postError["name"]}
              </font></td>
          </tr>
__HTML__;


    $html .= '
                  <tr>
                    <td valign="top"> <div align="right"><font size="2">HTMLヘッダ</font>
                      </div></td>
                    <td valign="top"><img src="'.$DIR_IMG.'arrow_r.gif" width="16" height="16"></td>
                    <td valign="top"><font size="2">
                      <textarea name="htmlh" cols="50" rows="15">' . $array["htmlh"] . '</textarea>
                      </font> </td>
                  </tr>
                  <tr>
                    <td valign="top"> <div align="right"><font size="2">HTMLフッタ</font>
                      </div></td>
                    <td valign="top"><img src="'.$DIR_IMG.'arrow_r.gif" width="16" height="16"></td>
                    <td valign="top"><font size="2">
                      <textarea name="htmlf" cols="50" rows="8">' . $array["htmlf"] . '</textarea>
                      </font> </td>
                  </tr>

                          ';

if ($array["evid"]) {
    $cntSubevent = count($array1[0]);
//	$linkSubevent2 = '<a href="enq_subevent2.php?evid=' . $array["evid"]. '&' . getSID() . '" target="_blank">新質問設定</a>';
    $linkSubevent = '<a href="enq_subevent.php?evid=' . $array["evid"] . ($cntSubevent > 0 ? "" : "&seid=new") . '&' . getSID() . '" target="_blank">質問設定</a>';

    /*
     * 質問がない場合非表示の対応
     * 質問作成時に即反映されないため、コメントアウト
     */
    $linkPageEdit = /*($cntSubevent<=0)? "":*/'<a href="enq_list.php?evid=' . $array["evid"] . '&' . getSID() . '">ページ分割・条件設定</a>';
    $linkConfirm = '<a href="enq_confirm_maker.php?evid=' . $array["evid"] . '&' . getSID() . '" target="_blank">確認画面作成</a>';

    $html .=<<<__HTML__
  <tr>
    <td valign="top"> <div align="right"><font size="2">質問設定</font></div></td>
    <td valign="top"><img src="{$DIR_IMG}arrow_r.gif" width="16" height="16"></td>
    <td valign="middle"><font size="2">
      {$linkSubevent}&nbsp;&nbsp;&nbsp;&nbsp;{$linkPageEdit}
      <hr align="left" width="200" color="#dddddd" noshade>
      {$linkConfirm}
      </font>
    </td>
  </tr>
__HTML__;
}

$html .=<<<__HTML__
        </table>
        <br>
        <table width="550" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td> <center>
                <input type="image" name="main" src="{$DIR_IMG}data_add.gif" width="70" height="21" onclick="{$GDF->get('JSCODE_ANTI_DOUBLE_CLICK')}">
                <input type="hidden" name="evid" value="{$evid}">
                </center></td>
          </tr>
        </table>
        <br>
        <table width="550" border="0" cellpadding="0" cellspacing="0">
          <tr>
            <td valign="middle"> <center>
                <font color="#999999" size="2">※最新情報に更新されない時は、「更新」を行ってください。</font>
              </center></td>
          </tr>
        </table>
      </center></td>
  </tr>
__HTML__;

//$html.= '<input type="hidden" name="flgs" value="'.($array["flgs"]? $array["flgs"]:"0").'">';
$html .= '<input type="hidden" name="flgl" value="' . ($array["flgl"] ? $array["flgl"] : "0") . '">';
$html .= '<input type="hidden" name="type" value="1">';
$html .= '<input type="hidden" name="rid" value="' . $array["rid"] . '">';
//$html .= '<input type="hidden" name="lastpage" value="' . ($array["lastpage"] ? $array["lastpage"] : "1") . '">';

if (ENQ_OPEN_RESTORE) {
    $okok = 'return true;';
}
$html .=<<<HTML
</form>
</table>
HTML;

$css = <<<__CSS__
.h{
cursor: help;
background-color:#ee6633;
color:white;
font-weight: bold;
padding: 0 0 0 2;
margin-left:5px;
}
.hissu {color:#ff0000; text-align:center; }
.title {color:#888888; font-size:10px; }
.line {background:#ccddee; }
.odd {background:#ffffff; }
.even {background:#f5faff; }
.helpbox-text {
background:url({$DIR_IMG}box_bg.gif) repeat-y;
padding:0 8px 3px 30px;
color:#880000;
font-size:11px;
line-height:1.4em;
}
.helpbox-text strong {color:#cc0000; font-size:12px;}
.helpbox-tale {display:absolute; margin:-5px 10px 0 -30px;}
__CSS__;

$objHtml =& new ResearchAdminHtml("アンケート新規作成/編集");
$objHtml->addFileJs(DIR_JS."myconfirm.js");
$objHtml->addFileJs(DIR_JS."floatinghelp.js");
$objHtml->addFileJs(DIR_JS."research_common.js");
$objHtml->setSrcJs($js);
$objHtml->setSrcCss($css);
echo $objHtml->getMainHtml($html);
exit;
class EnqEvent
{
    public function checkError($post)
    {
        global $array1;
        $subevents = $array1[0];
        $result = array ();
        if (is_void(trim($post["name"])))
            $result["name"] = "<br>" . EnqEvent :: getErrorTag("管理用タイトルは必ず入力して下さい");
        if ($result)
            $result["error"] = "入力内容にエラーがあります<br>";

        return $result;
    }
    public function getErrorTag($msg)
    {
        return '<font size=1 color=red>' . $msg . '</font>';
    }
}

function createAuthHtml($enq)
{
    require_once (DIR_LIB . 'CbaseAuthMVC.php');
    /***************************************************************/
    /** テンプレート(ヘッダ) */
    define('TEMPLATE_HEADER', DIR_TMPL . 'tmpl_htmlh.txt');

    /** テンプレート(フォーム部) */
    define('TEMPLATE_FORM', DIR_TMPL . 'tmpl_htmlform.txt');

    /** テンプレート(フッタ) */
    define('TEMPLATE_FOOTER', DIR_TMPL . 'tmpl_htmlf.txt');

    /** テンプレートファイルの文字コード */
    define('TEMPLATE_ENCODE', 'EUC-JP');

    /** 日付のフォーマット (sdate,edateの置換用) */
    define('DATE_FORMAT','Y年m月d日');

    /** 認証OK後に飛ばすページ */
    define('REDIRECT_OK','./index.php');

    /** 致命的なエラーのとき(対象のアンケートがないor認証型(flgo=2or3)はない)に飛ばすページ */
    define('ERROR_PAGE','./error.html');
    /***************************************************************/

    $_POST['rid'] = $enq[-1]['rid'];
    $model = & new AuthModel();
    $view = & new AuthView();
    $view->HTML = str_replace('action="enq_event.php"','action="auth.php"',$view->HTML);
    $controller = & new AuthController($model, $view);
    s_write(FILE_AUTH_HTML,encodeHtmlOut($controller->show()));
}
