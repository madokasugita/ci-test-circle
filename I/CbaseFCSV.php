<?php
/**
 * CSV管理ライブラリ
 * 内容：
 * 依存：
 * 作成日：2006/09/08
 * @package Cbase.Research.Lib
 * @author	Cbase akama
 * @author Cbase kido
 */

class FCSV
{
    /**
     * 配列からCVS形式の文字列を取得
     * @param  array  $prmAry       対象配列
     * @param  string $prmSeparator セパレータ
     * @param  string $prmCode      出力データの文字コード
     * @return string CSV形式文字列
     */
    public function getCSV($prmAry, $prmSeparator=",", $prmCode="SJIS")
    {
        $strFile="";
        foreach ($prmAry as $valRow) {
            $strFile .=implode($prmSeparator,$valRow)."\r\n";
        }

        return mb_convert_encoding($strFile,$prmCode,"EUC-JP");
    }

    /**
     * CSVの一行を変換して取得する
     * @param  string $prmStr       CSV形式の文字列
     * @param  array  $prmKeys      キー配列
     * @param  string $prmSeparator セパレータ
     * @param  string $prmCode      出力データの文字コード
     * @return array  配列
     */
    public function getRow($prmStr, $prmKeys=NULL, $prmSeparator=",", $prmCode="SJIS")
    {
        $aryData = mb_convert_encoding($prmStr, "EUC-JP", $prmCode);
        $aryData = explode($prmSeparator, $aryData);
        if ($prmKeys) $aryData = array_combine($prmKeys, $aryData);
        return $aryData;
    }

    /**
     * CSV形式文字列からarrayを取得
     * @param  string $prmStr       CSV形式の文字列
     * @param  bool   $prmIndex     インデックス行を含むかどうか
     * @param  array  $prmKeys      キー配列
     * @param  string $prmSeparator セパレータ
     * @param  string $prmCode      出力データの文字コード
     * @return array  2次元配列
     */
    public function getArray($prmStr, $prmIndex=false, $prmKeys=NULL, $prmSeparator=",", $prmCode="SJIS")
    {
        //改行コード統一
        $strStr = $prmStr;
        $strStr = ereg_replace("\r\n","\r",$strStr);
        $strStr = ereg_replace("\r","\n",$strStr);

        //分割
        $strStr = explode("\n", $prmStr);
        foreach ($strStr as $key => $valStr) {
            if (strlen($valStr) < 3 || ($prmIndex && $key == 0)) continue;
            $aryResult[] = FCSV::getRow($valStr, $prmKeys, $prmSeparator, $prmCode);
        }

        return $aryResult;
    }

    /**
     * ファイルへのパスからarrayを取得(オープン処理は別でやってください)
     * @param  string $prmFilePath  ファイルへのパス
     * @param  bool   $prmIndex     インデックス行を含むかどうか
     * @param  array  $prmKeys      キー配列
     * @param  string $prmSeparator セパレータ
     * @param  string $prmCode      出力データの文字コード
     * @return 配列
     */
    public function getArrayFromFile($prmFilePath, $prmIndex=false, $prmKeys=NULL, $prmSeparator=",", $prmCode="SJIS")
    {
        $aryReturn = array ();
        $globalAryRowCount = array();
        $i = 0;
        while ($strLine = fgets($prmFilePath, 1000000)) {
            if (strlen($strLine) < 3 || ($prmIndex && $i == 0)) continue;
            $aryResult[] = FCSV::getRow($strLine, $prmKeys, $prmSeparator, $prmCode);
            $i++;
        }

        return $aryReturn;
    }
}

/**
 * 一方の配列をキーとして、もう一方の配列を値として、ひとつの配列を生成する
 *  (PHP5ではデフォルトで定義済み)
 * @param array $keys キー一覧
 * @param array $vals 値一覧
 * @param int $max 作成する配列の最大の大きさ
 * @return array 作成された配列
 */
 function array_combine($keys, $vals, $max=0)
 {
    $keys = array_values((array) $keys);
    $vals = array_values((array) $vals);
//	$n = max(count($keys), count($vals));
    if($max) $n = $max;
    $r = array ();
    for ($i = 0; $i < $n; $i ++) {
        $r[$keys[$i]] = $vals[$i];
    }

    return $r;
}
