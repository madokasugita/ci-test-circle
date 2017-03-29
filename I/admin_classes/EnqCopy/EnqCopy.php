<?php namespace SmartReview\Admin\EnqCopy;
/**
 * 評価シート管理[import/export]
 */
class EnqCopy
{
    /**
     * デフォルト画面を返す
     */
    public function getHtmlDefault($error = '',$errortype='')
    {
        $PHP_SELF = PHP_SELF;
        $html = '';
        if ($error) {
            switch ($errortype) {
            case'import':
                $ierror = "<span style=\"color:red\">{$error}</span><br>";
                break;
            case'export':
                $eerror = "<span style=\"color:red\">{$error}</span><br>";
                break;
            }
        }
        $html .=<<<HTML
<style>
#events td{
    font-size:12px;
}
</style>
<h1>import/export</h1>
HTML;

        $html .= $this->getHtmlImportForm($ierror);
        $html .= $this->getHtmlExportForm($eerror);

        return $html;
    }

    /**
     * アンケート選択フォーム
     */
    public function getHtmlExportForm($error='')
    {
        $PHP_SELF = PHP_SELF;
        $options = '';

        $html = '';
        $html .=<<<HTML
<div class="sub_title"style="margin-left:20px">エクスポート</div>
<div style="margin:10px 0 10px 30px;text-align:left">
<form action="{$PHP_SELF}" method="post" style="display:inline">
{$error}
<table class="cont"style="width:auto;"id="events" bgcolor="#222222"  border="0" cellpadding="3" cellspacing="1">
<tr bgcolor="#cccccc">
<th width="20" align="center" >ID</th>
<th width="400" align="center">名称</th>
<th align="center"></th>
</tr>
HTML;

        $i = 0;
        $color[0] = '#f6f6f6';
        $color[1] = '#ffffff';
        foreach (getSheetNames() as $evid => $name) {
            $html .=<<<__HTML__
                <tr bgcolor="{$color[++$i%2]}">
                <td width="20" align="center" >{$evid}</tDd>
                <td width="400">{$name}</td>
                <td width="40" align="center"><input type="hidden" name="mode" value="export">
                <input type="submit" value="エクスポート" name="id[{$evid}]" class="imgbutton90"></td>
                </tr>
__HTML__;
        }

        $html .= <<<HTML
</table>
</form>
</div>
HTML;

        return $html;
    }

    /**
     * $_POST['ids'](配列)のevidのアンケートをファイルに書き出す
     */
    public function getHtmlExport()
    {
        $id = array_keys($_POST['id']);
        $evid = $id[0];
        $enquete = $this->getEnqueteByEvid($evid);
        $filename = $this->getHtmlExportFilename($evid);
        mb_http_output("pass");
        ob_end_clean();
        download(serialize($enquete), $filename);
        unlink(TMP_FILE);
    }

    public function getHtmlExportFilename($evid)
    {
        $filename = date('Ymd') . "enq_copy_" . $evid . ".dat";

        return $filename;
    }

    public function getEnqueteByEvid($evid)
    {
        $enquete = Get_Enquete("id", $evid, "", "", $_SESSION["muid"]);
        foreach ($GLOBALS['_360_language'] as $lang => $tmp) {
            $filename = $lang ? "../enqcsv/{$evid}_{$lang}.csv" : "../enqcsv/{$evid}.csv";
            if (file_exists($filename)) $enquete[-2][$lang] = file_get_contents($filename);
        }

        return $enquete;
    }

    public function getHtmlImportForm($error = '')
    {
        $PHP_SELF = PHP_SELF;

        $html = '';
        $evids = array('---選択して下さい---');
        $evids +=getSheetNames(false);
        $select = \FForm::select('evid',$evids);
        $html .=<<<HTML
<div class="sub_title"style="margin-left:20px">インポート</div>
<div style="margin:10px 0 10px 30px;text-align:left">
<form action="{$PHP_SELF}" method="post" enctype="multipart/form-data">
<input type="hidden" name="mode" value="import">
{$error}
<table class="cont" style="width:400px">
<tr><th>インポートファイル</th><td><input type="file" name="file" style="width:250px"></td></tr>
<tr><th>インポート先</th><td>{$select}</td></tr>
<tr><td colspan="2" style="text-align:center"><input type="submit" value="インポート"class="imgbutton90"></td></tr>
</table>
</form>
</div>
HTML;

        return $html;
    }

    /**
     * インポート処理を行なう
     */
    public function getHtmlImport()
    {
        if (!$_FILES['file']['name']) {
            return $this->getHtmlDefault('ファイルが選択されていません。','import');
        }
        $data = unserialize(file_get_contents($_FILES['file']['tmp_name']));
        if (!$data[-1]['evid']) {
            return $this->getHtmlDefault('評価シートデータではありません。(1)','import');
        }
        if (!$_POST['evid']) {
            return $this->getHtmlDefault('インポート先評価シートが選択されていません。(2)','import');
        }

        $to = (int) $_POST['evid'];
        if(!$this->importExecTrigger($data, $to))

            return $this->getHtmlDefault('<span style="color:black">インポート:</span><span style="color:blue">失敗!</span><br>','import');
        return $this->getHtmlDefault('<span style="color:black">インポート:</span><span style="color:red">成功!</span><br>','import');
    }

    public function importExecTrigger($data, $evid)
    {
        $event = $data[-1];
        $from = $event['evid'];

        return $this->importExec($event,$data,$from,$evid);
    }

    public function importExec($event,$data,$from,$to)
    {
        global 	$seid2seid;
        if(\PEAR::isError(\FDB::begin()))

            return false;
        if(\PEAR::isError(\FDB::delete(T_EVENT,'where evid = '.$to)))

            return false;
        $event['evid'] = $to;
        $event['rid'] = 'rid00'.$to;
        if(\PEAR::isError(\FDB::insert(T_EVENT,\FDB::escapeArray($event))))

            return false;
        $subevents = $data[0];
        if(\PEAR::isError(\FDB::delete(T_EVENT_SUB,'where evid = '.$to)))

            return false;
        if(\PEAR::isError(\FDB::delete(T_EVENT_SUB,'where evid = 0')))

            return false;

        $seid2seid = array();
        foreach ($subevents as $k => $subevent) {
            $old_seid = $subevent['seid'];
            $new_seid = $to*1000+$k;
            $seid2seid[$old_seid] = $new_seid;
        }
        foreach ($subevents as $k => $subevent) {
            $subevent['evid'] = $to;
            $subevent['seid'] = $to*1000+$k;
            $subevent['html2'] = preg_replace("/%%%%id([^%]+)%%%%/e","'%%%%id'.$this->convertSeid('\\1').'%%%%'",$subevent['html2']);
            $subevent['html2'] = preg_replace("/%%%%messageid([^%]+)%%%%/e","'%%%%messageid'.$this->convertSeid('\\1').'%%%%'",$subevent['html2']);
            if(\PEAR::isError(\FDB::insert(T_EVENT_SUB,\FDB::escapeArray($subevent))))

                return false;
        }
        if(\PEAR::isError(\FDB::commit()))

            return false;

        foreach ($GLOBALS['_360_language'] as $lang => $tmp) {
            $filename = $lang ? "../enqcsv/{$to}_{$lang}.csv" : "../enqcsv/{$to}.csv";
            s_write($filename,$data[-2][$lang]);
            s_unlink($filename.'.ctmp');
        }

        return true;
    }
    public function convertSeid($seid)
    {
        global 	$seid2seid;

        return $seid2seid[$seid];
    }
}
