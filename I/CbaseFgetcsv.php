<?php
/**
 * CSVデータ取得
 */

function CbaseFgetcsv($handle, $delimiter=",", $enclosure="\"", $encoding="SJIS-win", $delete_bom=false)
{
    if ($delimiter=="" || $enclosure=="" || $encoding=="") {
        return false;
    }
    $line = CbaseFgetcsvClass::getLine($handle, $delimiter, $enclosure, $encoding);
    if($delete_bom)
        $line = delete_bom($line);

    return CbaseFgetcsvClass::getData($line, $delimiter, $enclosure);
}

class CbaseFgetcsvClass
{
    public function getLine($handle, $delimiter, $enclosure, $encoding)
    {
        $line = null;
        do {
            if (feof($handle)) {
                break;
            }
            $line .= mb_convert_encoding(fgets($handle), INTERNAL_ENCODE, $encoding);
        } while (!CbaseFgetcsvClass::isCompleteLine($line, $delimiter, $enclosure));
        if (is_null($line)) {
            return false;
        }

        return CbaseFgetcsvClass::trimLine($line);
    }

    public function getData($line, $delimiter, $enclosure)
    {
        if ($line===false) {
            return false;
        }
        $aryData = array();
        $aryTmp = explode($delimiter, $line);
        if (is_array($aryTmp) && !empty($aryTmp)) {
            $data = null;
            foreach ($aryTmp as $tmp) {
                $data .= (is_null($data))? $tmp:$delimiter.$tmp;
                if (!CbaseFgetcsvClass::isCompleteData($data, $enclosure)) {
                    continue;
                }
                $aryData[] = CbaseFgetcsvClass::trimData($data, $enclosure);
                $data = null;
            }
        }
        if (!is_null($data)) {
            $aryData[] = CbaseFgetcsvClass::ltrimData($data, $enclosure);
        }

        return $aryData;
    }

    public function isCompleteLine($string, $delimiter, $enclosure)
    {
        return (substr_count($string, $enclosure)%2==0);
    }

    public function isCompleteData($string, $enclosure)
    {
        return (substr_count($string, $enclosure)%2==0);
    }

    public function trimLine($line)
    {
        return rtrim(str_replace("\r", "\n", str_replace("\r\n", "\n", $line)), "\n");
    }

    public function trimData($data, $enclosure)
    {
        $e = preg_quote($enclosure);
        if (preg_match("/^{$e}.*{$e}$/s", trim($data))==0) {
            return $data;
        }
        $data = preg_replace("/^{$e}/", "" , preg_replace("/{$e}$/", "", trim($data)));

        return str_replace(str_repeat($enclosure, 2), $enclosure, $data);
    }

    public function ltrimData($data, $enclosure)
    {
        $e = preg_quote($enclosure);
        if (preg_match("/^{$e}/", ltrim($data))==0) {
            return $data;
        }
        $data = preg_replace("/^{$e}/", "" , ltrim($data));

        return str_replace(str_repeat($enclosure, 2), $enclosure, $data);
    }
}
