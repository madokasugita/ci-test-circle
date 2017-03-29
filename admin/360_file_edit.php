<?php

/**
 * PGNAME:
 * DATE  :2010/02/01
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/
/** path */
define("NOT_CONVERT", 1);
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'MreAdminHtml.php');
require_once (DIR_LIB . 'CbaseFManage.php');
/****************************************************************************************************/
session_start();
Check_AuthMng(basename(__FILE__));
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
$PHP_SELF = PHP_SELF;
encodeWebInAll();
/****************************************************************************************************/
$id = 0;
$edit_files[++ $id] = array ('ユーザページCSS'			,DIR_IMG_USER_LOCAL.'360_userpage.css');
$edit_files[++ $id] = array ('ユーザページCSS(スマホのみ)',DIR_IMG_USER_LOCAL.'360_userpage_sp.css');
$edit_files[++ $id] = array ('評価シートCSS'			,DIR_IMG_USER_LOCAL.'360_enq.css');
$edit_files[++ $id] = array ('評価シートマトリクスCSS'	,DIR_IMG_USER_LOCAL.'360_enqmatrix.css');

$edit_files[++ $id] = array ('ユーザページ ヘッダ',	DIR_TEMPLATES . 'header.tpl','t');
$edit_files[++ $id] = array ('ユーザページ フッタ',	DIR_TEMPLATES . 'footer.tpl','t');
$edit_files[++ $id] = array ('マイページ',	DIR_TEMPLATES . '360_menu.tpl','t');
$edit_files[++ $id] = array ('マイページ(リンク部分)',	DIR_TEMPLATES . '360_menu_sheet.tpl','t');
$edit_files[++ $id] = array ('ログインページ',	DIR_TEMPLATES . '360_login.tpl','t');
$edit_files[++ $id] = array ('ログインページ(フォーム非表示)',	DIR_TEMPLATES . '360_login_out_of_answer_period.tpl','t');
$edit_files[++ $id] = array ('ログインページ(認証なし利用)',	DIR_TEMPLATES . '360_login_sso.tpl','t');
$edit_files[++ $id] = array ('パスワード変更ページ',	DIR_TEMPLATES . '360_pw.tpl','t');
$edit_files[++ $id] = array ('パスワード問い合わせページ',	DIR_TEMPLATES . '360_reissue.tpl','t');
$edit_files[++ $id] = array ('お知らせページ',	DIR_TEMPLATES . '360_news.tpl','t');
$edit_files[++ $id] = array ('回答画面',	DIR_TEMPLATES . '360_enq.tpl','t');
$edit_files[++ $id] = array ('選定一覧',	DIR_TEMPLATES . '360_relation_view.tpl','t');
$edit_files[++ $id] = array ('選定検索',	DIR_TEMPLATES . '360_relation_edit.tpl','t');
$edit_files[++ $id] = array ('承認依頼画面',	DIR_TEMPLATES . '360_setup_reply.tpl','t');
$edit_files[++ $id] = array ('承認画面',	DIR_TEMPLATES . '360_admit_reply.tpl','t');
$edit_files[++ $id] = array ('スマートレビュー',	DIR_TEMPLATES . 'review.tpl','t');
$edit_files[++ $id] = array ('スマートレビュー（経年比較）',	DIR_TEMPLATES . 'secular_review.tpl','t');
$edit_files[++ $id] = array ('デモ申込',	DIR_TEMPLATES . '360_demo.tpl','t');

$edit_files[++ $id] = array ('管理画面マニュアルリンク',	DIR_TEMPLATES . 'admin_manual.tpl','t');
$id = 0;
$upload_files[++ $id] = array ('ユーザページCSS'		,DIR_IMG_USER_LOCAL.'360_userpage.css');
$upload_files[++ $id] = array ('ユーザページCSS(スマホのみ)',DIR_IMG_USER_LOCAL.'360_userpage_sp.css');
$upload_files[++ $id] = array ('評価シートCSS'			,DIR_IMG_USER_LOCAL.'360_enq.css');
$upload_files[++ $id] = array ('評価シートマトリクスCSS',DIR_IMG_USER_LOCAL.'360_enqmatrix.css');
//$upload_files[++ $id] = array ('マイページ文言管理用csv',DIR_DATA . 'language.csv');

$allow_ext = array ('png',	'jpg',	'jpeg',	'gif',	'css',	'js');

/****************************************************************************************************/
$mode = is_array($_REQUEST['mode']) ? array_shift(array_keys($_REQUEST['mode'])) : $_REQUEST['mode'];
switch ($mode) {
    case 'edit' :
        $body = edit();
        break;
    case 'download' :
        _download();
        break;
    case 'upload' :
        $body = _upload();
        break;
    case 'imgupload' :
        $body = _imgupload();
        break;
    case 'update' :
        print json_encode(update());
        exit;
    case 'export' :
        export();
        exit;
    case 'import' :
        $body = import();
        break;
    case 'tmp_export' :
        tmp_export();
        exit;
    case 'tmp_import' :
        $body = tmp_import();
        break;
    default :
        $body = top();
}
encodeWebOutAll();
$html =<<<HTML
{$body}
HTML;

$subtitle = (is_good($_POST['id']))? $edit_files[(int) $_POST['id']][0]."を編集中":"";
$objHtml = new MreAdminHtml("テンプレート編集", $subtitle, false);
if ($mode == "edit"||$mode == "update") {
    $html = D360::getBackBar() . $html;
    $objHtml->addFileJs(DIR_IMG."jquery/behave.js");
    $objHtml->setSrcJs(<<<__JS__
if(!isIE || isIE >= 9)
    var editor = new Behave({textarea: document.getElementById('data')});
function sendData()
{
    $.ajax({
        url: "{$PHP_SELF}",
        type:"POST",
        data: {mode:$("[name='mode']").val(), id:$("[name='id']").val(), data:$("[name='data']").val() },
        dataType: "json",
        success: function (data) {
            $(data).each(function (k,d) {
                $().toastmessage('show'+d[1]+'Toast', d[0]);
            });
        }
    });

    return false;
}
$(document.forms[0]).bind("submit", function () { sendData(); return false; });
function keydown(e)
{
    var keycode = getKEYCODE(e);
    ev = e? e:event;
    if (keycode == 83 && ev.ctrlKey) {
        sendData();

        return false;
    }
}
window.document.onkeydown = keydown;
__JS__
    );
} else {
    $html = D360::getRefreshBar() . $html;
}
$objHtml->setTextAreaResizer();
print $objHtml->getMainHtml($html);
exit;
/****************************************************************************************************/
function _download()
{
    global $edit_files, $upload_files, $PHP_SELF;
    $id = (int) $_POST['id'];
    $strFile = file_get_contents($upload_files[$id][1]);
    $filename = encodeDownloadFilename(basename($upload_files[$id][1]));
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Content-length: " . strlen($strFile));
    print $strFile;
}
function _upload()
{
    global $edit_files, $upload_files, $PHP_SELF;
    $id = (int) $_POST['id'];

    if ($_FILES['file']['name'] != basename($upload_files[$id][1])) {
        return top("アップロードに失敗しました<br>ファイル名が「" . basename($upload_files[$id][1]) . "」の場合のみ上書きアップロードできます。");
    }
    rename($_FILES['file']['tmp_name'], $upload_files[$id][1]);
    syncCopy($upload_files[$id][1]);
    $message[] = basename($upload_files[$id][1])." ファイルをアップロードしました";
    $path = pathinfo($upload_files[$id][1]);
    if ($path['extension']=="css") {
        $minify = str_replace(".css", "-min.css", $upload_files[$id][1]);
        file_put_contents($minify, cssCompress(encodeFileOut(file_get_contents($upload_files[$id][1]))));
        syncCopy($minify);
        $message[] = basename($minify)." ファイルをアップロードしました";
    }

    return top(implode("<br>", $message));
}
function _imgupload()
{
    global $edit_files, $upload_files, $PHP_SELF, $allow_ext;
    $id = (int) $_POST['id'];
    $file_info = pathinfo($_FILES['file']['name']);
    $ext = $file_info["extension"];
    if (!in_array($ext, $allow_ext))
        return top("アップロードに失敗しました<br>許可されていない拡張子です。");
    if (!ereg('^[a-zA-Z0-9\._-]+$', $_FILES['file']['name']))
        return top("アップロードに失敗しました<br>ファイル名に不正な文字が含まれています。");

    $DIR_IMG = DIR_IMG_USER_LOCAL;
    rename($_FILES['file']['tmp_name'], $DIR_IMG . basename($_FILES['file']['name']));

    $URL = DIR_IMG_USER_LOCAL . basename($_FILES['file']['name']);
    $URL_R = '{$dir_user}' . basename($_FILES['file']['name']);
    syncCopy($DIR_IMG . $_FILES['file']['name']);

    return top("画像ファイルをアップロードしました<br>URL:<a href='{$URL}' target='_blank'>{$URL}</a><br>imgタグ:&lt;img src=\"{$URL_R}\"&gt;");
}
function export()
{
    $filename = date("Ymd")."_".PROJECT_NAME."_style.zip";
    $FILE = DIR_TMP.$filename;
    $DIR_IMG_USER = str_replace(DIR_ROOT, DIR_SYS_ROOT, DIR_IMG_USER_LOCAL);
    chdir($DIR_IMG_USER);
    exec("zip -D ".$FILE." "."*");
    $ad = file_get_contents($FILE);
    unlink($FILE);
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-disposition: attachment; filename=\"{$filename}\"");
    header("Content-type: application/zip; name=\"{$filename}\"");
    header("Content-Length: " . strlen($ad));
    print $ad;
    exit;
}
function import()
{
    global $edit_files, $upload_files, $PHP_SELF;

    $tmp_dir = DIR_TMP.date("YmdHis").PROJECT_NAME."_style";
    exec("unzip ".$_FILES['file']['tmp_name']." -d ".$tmp_dir,$output,$res);
    if($res!=0)

        return top("解凍に失敗しました");
    $DIR_IMG = DIR_IMG_USER_LOCAL;
    $result = array();
    foreach (glob($tmp_dir."/*") as $filename) {
        if (is_dir($filename)) {
            foreach (glob($filename."/*") as $_filename) {
                if ($ng = isNgFile($_filename)) {
                    unlink($_filename);
                    $result[] = $ng;
                    continue;
                }
                rename($_filename, $DIR_IMG . basename($_filename));
                touch($DIR_IMG . basename($_filename));
                syncCopy($DIR_IMG . basename($_filename));
                $result[] = basename($_filename)." アップロードしました";
            }
            rmdir($filename);
        } else {
            if ($ng = isNgFile($filename)) {
                unlink($filename);
                $result[] = $ng;
                continue;
            }
            rename($filename, $DIR_IMG . basename($filename));
            touch($DIR_IMG . basename($filename));
            syncCopy($DIR_IMG . basename($filename));
            $result[] = basename($filename)." アップロードしました";
        }
    }
    rmdir($tmp_dir);

    return top(implode("<br>", $result));
}
function tmp_export()
{
    $filename = date("Ymd")."_".PROJECT_NAME."_template.zip";
    $FILE = DIR_TMP.$filename;
    $DIR_TMPLATE = DIR_TEMPLATES;
    chdir($DIR_TMPLATE);
    exec("zip -D ".$FILE." "."*");
    $ad = file_get_contents($FILE);
    unlink($FILE);
    header("Pragma: private");
    header("Cache-Control: private");
    header("Content-disposition: attachment; filename=\"{$filename}\"");
    header("Content-type: application/octet-stream; name=\"{$filename}\"");
    header("Content-Length: " . strlen($ad));
    print $ad;
    exit;
}
function tmp_import()
{
    global $edit_files, $upload_files, $PHP_SELF;

    $tmp_dir = DIR_TMP.date("YmdHis").PROJECT_NAME."_template";
    exec("unzip ".$_FILES['file']['tmp_name']." -d ".$tmp_dir,$output,$res);
    if($res!=0)

        return top("解凍に失敗しました");
    $result = array();
    foreach (glob($tmp_dir."/*") as $filename) {
        if (is_dir($filename)) {
            foreach (glob($filename."/*") as $_filename) {
                if ($ng = isNgFile($_filename,array('tpl'))) {
                    unlink($_filename);
                    $result[] = $ng;
                    continue;
                }
                rename($_filename, DIR_TEMPLATES . basename($_filename));
                touch(DIR_TEMPLATES . basename($_filename));
                syncCopy(DIR_TEMPLATES . basename($_filename));
                $result[] = basename($_filename)." アップロードしました";
            }
            rmdir($filename);
        } else {
            if ($ng = isNgFile($filename,array('tpl'))) {
                unlink($filename);
                $result[] = $ng;
                continue;
            }
            rename($filename, DIR_TEMPLATES . basename($filename));
            touch(DIR_TEMPLATES . basename($filename));
            syncCopy(DIR_TEMPLATES . basename($filename));
            $result[] = basename($filename)." アップロードしました";
        }
    }
    rmdir($tmp_dir);

    return top(implode("<br>", $result));
}
function isNgFile($path, $allow = "")
{
    global $allow_ext;
    $ok_ext = ($allow)? $allow:$allow_ext;
    $file_info = pathinfo($path);
    $ext = $file_info["extension"];
    if (!in_array($ext, $ok_ext))
        return '<span style="color:red">'.basename($path)." アップロードに失敗しました<br>許可されていない拡張子です。</span>";
    if (!ereg('^[a-zA-Z0-9\._-]+$', basename($path)))
        return '<span style="color:red">'.basename($path)." アップロードに失敗しました<br>ファイル名に不正な文字が含まれています。</span>";
    return false;
}
function top($message = '')
{
    global $edit_files, $upload_files, $PHP_SELF, $allow_ext;
    if ($message) {
        $message =<<<HTML
<div style="padding:20px;color:blue;font-weight:bold;border:1px solid black;width:500px;">{$message}</div>
HTML;
    }

    $html =<<<HTML

{$message}
HTML;

    $html .=<<<HTML
<div class="sub_title left_margin">デザイン編集</div>
<table class="cont"style="text-align:left;margin:20px 30px;width:auto">
<tr>
<th style="width:30px">ID</th>
<th style="width:300px">名前</th>
<th style="width:140px">最終更新日時</th>
<th style="width:80px">編集</th>
</tr>

HTML;
    $s = 0;
    foreach ($edit_files as $id => $file) {
        if($file['2'])
            continue;
        $s = ($s +1) % 2;
        $basename = basename($file[1]);
        $modified_time = (file_exists($file[1])) ? filemtime($file[1]) : mktime(0, 0, 0, 1, 1, 1970);
        $time = date("Y年m月d日 H時i分", $modified_time);
        $html .=<<<HTML

<tr class="tr{$s}">
<form action="{$PHP_SELF}" method="post">
<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="mode" value="edit">
<td style="text-align:center">{$id}</td>
<td>{$file[0]} ({$basename})</td>
<td>{$time}</td>
<td style="text-align:center"><input type="submit" value="編集"class="imgbutton35"></td>
</form>
</tr>

HTML;
    }
    $html .=<<<HTML
</table>
HTML;

    $html .=<<<HTML
<div class="sub_title left_margin">テンプレート編集</div>
<table class="cont"style="text-align:left;margin:20px 30px;width:auto">
<tr>
<th style="width:30px">ID</th>
<th style="width:300px">名前</th>
<th style="width:140px">最終更新日時</th>
<th style="width:80px">編集</th>
</tr>

HTML;
    $s = 0;
    foreach ($edit_files as $id => $file) {
        if(!$file['2'])
            continue;
        $s = ($s +1) % 2;
        $basename = basename($file[1]);
        $modified_time = (file_exists($file[1])) ? filemtime($file[1]) : mktime(0, 0, 0, 1, 1, 1970);
        $time = date("Y年m月d日 H時i分", $modified_time);
        $html .=<<<HTML

<tr class="tr{$s}">
<form action="{$PHP_SELF}" method="post">
<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="mode" value="edit">
<td style="text-align:center">{$id}</td>
<td>{$file[0]} ({$basename})</td>
<td>{$time}</td>
<td style="text-align:center"><input type="submit" value="編集"class="imgbutton35"></td>
</form>
</tr>

HTML;
    }
    $html .=<<<HTML
</table>
HTML;

    $html .=<<<HTML
<div class="sub_title left_margin">ファイル差し替え</div>
<table class="cont"style="text-align:left;margin:20px 30px;width:auto">
<tr>
<th style="width:30px">ID</th>
<th style="width:300px">名前</th>
<th style="width:140px">最終更新日時</th>
<th style="width:80px">ダウンロード</th>
<th style="width:80px">アップロード</th>
</tr>

HTML;
    $s = 0;
    foreach ($upload_files as $id => $file) {
        $s = ($s +1) % 2;
        $basename = basename($file[1]);
        $modified_time = (file_exists($file[1])) ? filemtime($file[1]) : mktime(0, 0, 0, 1, 1, 1970);
        $time = date("Y年m月d日 H時i分", $modified_time);
        $html .=<<<HTML

<tr class="tr{$s}">
<form action="{$PHP_SELF}" method="post" enctype="multipart/form-data">
<input type="hidden" name="id" value="{$id}">
<td style="text-align:center">{$id}</td>
<td>{$file[0]} ({$basename})</td>
<td>{$time}</td>
<td style="text-align:center"><input type="submit" name="mode[download]" value="ダウンロード"class="imgbutton90"></td>
<td style="text-align:center"><input type="file" name="file"><input type="submit" name="mode[upload]" value="アップロード"class="imgbutton90"></td>
</form>
</tr>

HTML;
    }
    $ext = implode(',', $allow_ext);
    $html .=<<<HTML
</table>

<div class="sub_title left_margin">画像ファイルアップロード</div>
<div style="margin-left:30px;border:1px black solid;padding:10px;width:400px;">
注意:同名のファイルがある場合は上書きされます<br>
アップロードできるファイルの拡張子({$ext})
<br><br>
<form action="{$PHP_SELF}" method="post" enctype="multipart/form-data">
<input type="file" name="file"><input type="submit" name="mode[imgupload]" value="画像アップロード"class="imgbutton120">
</form>
</div>
HTML;

    $html .=<<<HTML
</table>

<div class="sub_title left_margin">デザイン・画像ファイル エクスポート/インポート</div>
<div style="margin-left:30px;border:1px black solid;padding:10px;width:400px;">
<form action="{$PHP_SELF}" method="post" enctype="multipart/form-data">
<input type="submit" name="mode[export]" value="エクスポート"class="imgbutton120">
</form>
<hr>
注意:同名のファイルがある場合は上書きされます<br>
反映できるファイルの拡張子({$ext})
<br><br>
<form action="{$PHP_SELF}" method="post" enctype="multipart/form-data">
<input type="file" name="file"><input type="submit" name="mode[import]" value="インポート"class="imgbutton120">
</form>
</div>
HTML;

    $html .=<<<HTML
</table>

<div class="sub_title left_margin">テンプレート エクスポート/インポート</div>
<div style="margin-left:30px;border:1px black solid;padding:10px;width:400px;">
<form action="{$PHP_SELF}" method="post" enctype="multipart/form-data">
<input type="submit" name="mode[tmp_export]" value="エクスポート"class="imgbutton120">
</form><br><br>
管理画面マニュアルリンク (manual.html)は出力されません
<hr>
注意:同名のファイルがある場合は上書きされます<br>
管理画面マニュアルリンク (manual.html)は反映できません<br>
反映できるファイルの拡張子(tpl)
<br><br>
<form action="{$PHP_SELF}" method="post" enctype="multipart/form-data">
<input type="file" name="file"><input type="submit" name="mode[tmp_import]" value="インポート"class="imgbutton120">
</form>
</div>
HTML;

    return $html;
}

function edit($message = '')
{
    global $PHP_SELF, $edit_files;
    $id = (int) $_POST['id'];
    $data = (file_exists($edit_files[$id][1])) ? encodeFileIn(file_get_contents($edit_files[$id][1])) : '';
    $data = transHtmlentities($data);

    $html =<<<HTML
{$message}
<form action="{$PHP_SELF}" method="post">
<input type="hidden" name="mode" value="update">
<input type="hidden" name="id" value="{$id}">
<textarea class="resizable" style=" height:400px" name="data" id="data">{$data}</textarea>
<p><input type="submit" name="submit:update" class="white button big" value="保存"></p>
</form>
HTML;

    return $html;
}

function update()
{
    global $PHP_SELF, $edit_files;
    $id = (int) $_POST['id'];
    $res = file_put_contents($edit_files[$id][1], encodeFileOut($_POST['data']));
    if(!is_false($res)) $res = syncCopy($edit_files[$id][1]);
    if(!is_false($res))
        $message[] = array(basename($edit_files[$id][1])." を更新しました", "Success");
    else
        $message[] = array(basename($edit_files[$id][1])." の更新に失敗しました", "Notice");
    $path = pathinfo($edit_files[$id][1]);
    if ($path['extension']=="css") {
        $minify = str_replace(".css", "-min.css", $edit_files[$id][1]);
        $res = file_put_contents($minify, cssCompress(encodeFileOut($_POST['data'])));
        if(!is_false($res)) syncCopy($minify);
        if(!is_false($res))
            $message[] = array(basename($minify)." を更新しました", "Success");
        else
            $message[] = array(basename($minify)." の更新に失敗しました", "Notice");
    }

    return $message;
}
function cssCompress($buffer)
{
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);

    return $buffer;
 }
