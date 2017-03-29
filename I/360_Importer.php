<?php
require_once 'CbaseImporter.php';

class Importer360 extends Importer
{
    /**
     * @return string 二番目の画面でエラーチェック処理を行い、結果のhtmlを取得
     */
    public function confirm($post)
    {

        //アップロードエラーのチェックを行う

        $errors = $this->design->getUploadErrorMessage ($_FILES['file']['error']);
        if ($errors) {
            return $this->showError(array($errors), $post);
        }
        $temp_id  = temp_rename($_FILES['file']['tmp_name']);
        $_SESSION['upload_file_name'] = $_FILES['file']['name'];

        $result = $this->importConfirmExec(temp_file_path($temp_id), $post);
        if($this->showError)

            return $result;

        list($ng_line, $total, $ok_count) = $result;
        $errors = array();

        $ngcode = implode(':', $ng_line);
        $hash = $this->getHash ($ngcode, $temp_id);
        $hidden = $this->design->getHidden('ng_line', $ngcode).
        $this->design->getHidden('import_id', $hash).
        $this->design->getHidden('total', $total).
        $this->design->getHidden('import', 'submit').
        $this->design->getHidden('temp_id', $temp_id).
        $this->getHiddenSID ().
        $this->getHiddenFromPost ($post);

        $forms = array('back', 'submit');

        return $this->design->getConfirmView($hidden, $this->design->getForms($forms),
            $this->design->getFormatValues($this->model->getFormKeys(), $post), $ok_count, $errors);
    }
}

class ImportDesign360 extends ImportDesign
{

    public function getProgressBar()
    {
        return<<<HTML
<style>
.graph {
    position: relative;
    width: 400px;
    border: 1px solid #005aa5;
    padding: 2px;
}
.graph .bar {
    height: 20px;
    display: block;
    position: relative;
    background: #005aa5;
}
#percent{
    width:100px;
}
</style>
<script>
function p(p,i,m)
{
    if (p>100) {
        p = 100;
    }
    if (i>m) {
        i = m;
    }
    document.getElementById('table1').style.display="";
    document.getElementById('percent').innerHTML = p + '%' + '('+i+'/'+m+')';
    document.getElementById('bar').style.width = p + '%';
}
</script>
<table style="margin-left:20px;display:none" id="table1">
<tr><td class="graph"><span class="bar" style="width: 0%;" id="bar"><br></span></td><td id="percent"></td></tr>
</table>
HTML;
    }
    public function getHeader()
    {
        if (!isset($this->additionalJs)) {
            $this->additionalJs = "";
        }
        $DIR_IMG = DIR_IMG;
        $PAGE_TITLE = PAGE_TITLE;

        return <<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<link rel="stylesheet" href="{$DIR_IMG}360_adminpage.css" type="text/css">
<script src="{$DIR_IMG}common.js" type="text/javascript"></script>
<script src="{$DIR_IMG}myconfirm.js" type="text/javascript"></script>
{$this->additionalJs}
<title>{$PAGE_TITLE}</title>
</head>
<body>
<div id="container-iframe">
<div id="main-iframe">
<h1>{$PAGE_TITLE}</h1>
<div align="center">
__HTML__;
    }

    /**
     * JavaScriptの追加
     *
     * @param  String $name JSファイル名 e.g., common.js
     * @param  String $path JSファイルディレクトリ名
     * @return Object $this
     */
    public function setAdditionalJs($name, $path = DIR_IMG)
    {
        if (!isset($this->additionalJs)) {
            $this->additionalJs = "";
        }
        $this->additionalJs .= '<script src="' . $path . $name . '" type="text/javascript"></script>' . "\n";

        return $this;
    }
}

class ImportModel360 extends ImportModel
{
    public function getExecFile ()
    {
        $debug = end(debug_backtrace());
        $path_parts = pathinfo($debug['file']);
        return $path_parts['basename'];
    }

    public function getUdateImportFile ($exec_file)
    {
        $where = 'WHERE exec_file = '. FDB :: escape($exec_file);
        $res = FDB :: select(T_IMPORT_FILE, 'date_format(udate, "%Y/%m/%d %H:%i:%s") AS udate', $where);

        return $res[0]['udate'];
    }
    public function getLastFileName ($exec_file)
    {
        $where = 'WHERE exec_file = '. FDB :: escape($exec_file);
        $res = FDB :: select(T_IMPORT_FILE, 'last_file', $where);

        return $res[0]['last_file'];
    }

    public function upsertImportFile ($muid, $exec_file)
    {
        $data['udate'] = 'NOW()';
        $data['muid'] = $muid;

        if (!$this->existsImportFile($exec_file)) {
            $data['cdate'] = 'NOW()';
            $data['exec_file'] = $exec_file;
            return $this->insertImportFile($data);
        } else {
            return $this->updateImportFile($data, $exec_file);
        }
    }

    private function getUploadFileName ()
    {
        return ($_SESSION['upload_file_name']) ? $_SESSION['upload_file_name'] : '';
    }

    private function existsImportFile ($exec_file)
    {
        $where = 'WHERE exec_file = '. FDB :: escape($exec_file);
        $res = FDB :: select(T_IMPORT_FILE, 'COUNT(*) AS count', $where);

        return ($res[0]['count'] > 0) ? true : false;
    }

    private function updateImportFile ($data, $exec_file)
    {
        $data['muid'] = FDB :: escape($data['muid']);
        $data['last_file'] = FDB :: escape($this->getUploadFileName());
        FDB :: begin();
        $res = FDB :: update(T_IMPORT_FILE, $data, 'WHERE exec_file = '.FDB :: escape($exec_file));
        FDB :: commit();

        return $res;
    }

    private function insertImportFile($data)
    {
        $data['muid'] = FDB :: escape($data['muid']);
        $data['exec_file'] = FDB :: escape($data['exec_file']);
        $data['last_file'] = FDB :: escape($this->getUploadFileName());

        FDB :: begin();
        $res = FDB :: insert(T_IMPORT_FILE, $data);
        FDB :: commit();

        return $res;
    }

}


