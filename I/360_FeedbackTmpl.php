<?php

class Tmpl
{
    public $tmpl;

    public function Tmpl($filename)
    {
        $this->tmpl = encodeHtmlIn(file_get_contents($filename));
    }

    public function replaceTmpl($search, $replace)
    {
        $this->tmpl = preg_replace($search, $replace, $this->tmpl);
    }

    public function getTmpl()
    {
        return $this->tmpl;
    }

    public function saveTmpl($id)
    {
        $fp = fopen(getFeedbackHtmlName($id), "w");
        if(!$fp) return false;
        fwrite($fp, encodeHtmlOut($this->tmpl));
        fclose($fp);
    }
}

class FeedbackTmpl extends Tmpl
{
    public function replaceByData($aryData, $table_num)
    {
        if ($table_num>4) {
            foreach ($aryData as $row_num => $value) {
                $row_num++;
                $search = sprintf("/\<\!\-\- *%s-%s *\-\-\>/", $table_num, $row_num);
                $this->replaceTmpl($search, str_replace("\n", "\n<br>", $value));
            }

            return;
        }

        foreach ($aryData as $col_num => $data) {
            $col_num = chr(65+$col_num);
            foreach ($data as $row_num => $value) {
                $row_num++;
                $search = sprintf("/\<\!\-\- *%s-%s-%s *\-\-\>/", $col_num, $table_num, $row_num);
                if(is_numeric($value))
                    $value = sprintf('%.1f',$value);
                $this->replaceTmpl($search, $value);
            }
        }

        return;
    }

    public function replaceImgPath($arrayImgPath)
    {
        $i=1;
        foreach ($arrayImgPath as $imgPath) {
            $imgPath = str_replace(DIR_SYS_ROOT,'',$imgPath);
            $this->replaceTmpl("|%%%%graph{$i}%%%%|", $imgPath);
            $i++;
        }

    }
}

/**
 * フィードバックHTML名取得
 */
function getFeedbackHtmlName($id)
{
    return DIR_FEEDBACK."{$id}.html";
}
