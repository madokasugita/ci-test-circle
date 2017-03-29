<?php
/**
 * CSV処理クラス
 */

class CbaseCSV
{
    public $file_encoding;
    public $file_crlf;
    public $internal_encoding;

    /**
     * コンストラクタ
     * @param string $file_encoding
     * @param string $file_crlf
     */
    public function CbaseCSV($file_encoding, $file_crlf="\r\n")
    {
        $this->setFileEncoding($file_encoding, $file_crlf);
        $this->internal_encoding = INTERNAL_ENCODE;
    }

    /**
     * ファイルエンコード設定
     * @param string $file_encoding
     * @param string $file_crlf
     */
    public function setFileEncoding($file_encoding, $file_crlf="\r\n")
    {
        $this->file_encoding = $file_encoding;
        $this->file_crlf = $file_crlf;
    }

    /**
     * ファイルエンコード取得
     * @return string
     */
    public function getFileEncoding()
    {
        return $this->file_encoding;
    }

    /**
     * CSVデータ読込
     * @param  resource $handle
     * @param  int      $length
     * @param  string   $delimiter
     * @param  string   $enclosure
     * @return array
     */
    public function getCsvData($handle, $length=0, $delimiter=",", $enclosure="\"")
    {
        $line = $this->get_csv_line($handle, $length, $enclosure);

        return $this->get_csv_data($line, $delimiter, $enclosure);
    }

    /**
     * CSVデータ書込
     * @param  resource $handle
     * @param  array    $fields
     * @param  string   $delimiter
     * @param  string   $enclosure
     * @return int
     */
    public function putCsvData($handle, $fields, $delimiter=",", $enclosure="\"")
    {
        foreach ($fields as $key => $value) {
            $fields[$key] = $this->csv_escape($value, $enclosure);
        }
        $line = implode($delimiter, $fields).$this->file_crlf;

        return fwrite($handle, mb_convert_encoding($line, $this->file_encoding, $this->internal_encoding));
    }

    /**
     * private get_csv_data
     */
    public function get_csv_data($line, $delimiter, $enclosure)
    {
        $tmpData = explode($delimiter, $line);

        $aryData = array();
        $data = "";
        foreach ($tmpData as $tmp_data) {
            $data .= (is_void($data))? $tmp_data:$delimiter.$tmp_data;
            if (!$this->is_correct_csv($data, $enclosure)) {
                continue;
            }
            $aryData[] = $this->csv_unescape($data, $enclosure);
            $data = "";
        }
        if (is_good($data)) {
            $aryData[] = $this->csv_unescape($data, $enclosure);
        }

        return $aryData;
    }

    /**
     * private get_csv_line
     */
    public function get_csv_line($handle, $length, $enclosure)
    {
        $line = "";
        do {
            if (feof($handle)) {
                break;
            }
            $line .= mb_convert_encoding(fgets($handle, $length), $this->internal_encoding, $this->file_encoding);
        } while (!$this->is_correct_csv($line, $enclosure));

        return trim($line, $this->file_crlf);
    }

    /**
     * private is_correct_csv
     */
    public function is_correct_csv($data, $enclosure)
    {
        if (is_void($enclosure)) {
            return true;
        }
        preg_match_all("/".$enclosure."/", $data, $matches);

        return (count($matches[0])%2==0);
    }

    /**
     * private csv_escape
     */
    public function csv_escape($data, $enclosure)
    {
        if (is_void($enclosure)) {
            return $data;
        }

        return enclose(str_replace($enclosure, str_repeat($enclosure, 2)), $enclosure);
    }

    /**
     * private csv_unescape
     */
    public function csv_unescape($data, $enclosure)
    {
        if (is_void($enclosure)) {
            return $data;
        }

        return str_replace(str_repeat($enclosure, 2), $enclosure, trim($data, $enclosure));
    }

    public function header_download($filepath, $filename)
    {
        $filename = encodeDownloadFilename($filename);
        header("Pragma: private");
        header("Cache-Control: private");
        header("Content-Type: application/csv");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Content-length: " . filesize($filepath));
    }

    public function convert_utf8($prmAry, $convert = true, $encode = true)
    {
        $strFile = "";
        foreach ($prmAry as $strRow) {
            $strFile .= implode(OUTPUT_CSV_DELIMITER, csv_quoteArray($strRow)) . "\r\n";
        }
        if($convert)
            $strFile = strip_tags(replaceMessage($strFile));
        $strFile = mb_convert_encoding($strFile,OUTPUT_CSV_ENCODE,INTERNAL_ENCODE);
        if ($encode) {
            if(OUTPUT_CSV_ENCODE == 'UTF-16LE')//BOMを追加
                $strFile = chr(255) . chr(254).$strFile;
            if(OUTPUT_CSV_ENCODE == 'UTF-8')//BOMを追加
                $strFile = chr(0xEF).chr(0xBB).chr(0xBF).$strFile;
        }

        return $strFile;
    }

    /**
     * CSVダウンロードに必要なパラメータを取得
     */
    public function get_csv_download_utf8($prmAry, $filename = '',$convert = true, $bom = true)
    {
        if (!$filename)
            $filename = date('Y-m-d') . '.csv';
        $strFile = "";
        foreach ($prmAry as $strRow) {
            $strFile .= implode(OUTPUT_CSV_DELIMITER, csv_quoteArray($strRow)) . "\r\n";
        }
        if($convert)
            $strFile = strip_tags(replaceMessage($strFile));
        $strFile = mb_convert_encoding($strFile,OUTPUT_CSV_ENCODE,INTERNAL_ENCODE);
        if(OUTPUT_CSV_ENCODE == 'UTF-16LE' && $bom)//BOMを追加
            $strFile = chr(255) . chr(254).$strFile;
        if(OUTPUT_CSV_ENCODE == 'UTF-8' && $bom)//BOMを追加
            $strFile = chr(0xEF).chr(0xBB).chr(0xBF).$strFile;

        $filename = encodeDownloadFilename(replaceMessage($filename));
        $return = array(
            'filename' => $filename,
            'strFile' => $strFile,
        );

        return $return;
    }

    /**
     * CSVダウンロード
     */
    public function execute_csv_download($params)
    {
        header("Pragma: private");
        header("Cache-Control: private");
        header("Content-Type: application/csv");
        header("Content-Disposition: attachment; filename=\"{$params['filename']}\"");
        header("Content-length: " . strlen($params['strFile']));
        print $params['strFile'];
        exit;
    }

}
