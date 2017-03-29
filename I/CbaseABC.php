<?php
/**
 * ABCカナ変換クラス
 */

class CbaseABC
{
    public $aryAbc;

    /**
     * コンストラクタ
     */
    public function CbaseABC()
    {
        $this->aryAbc = array();

        $fp = fopen(FILE_ABC, "r");

        if (!$fp) {
            print "No abc file!";
            exit;
        }

        while (!feof($fp)) {
            $abc = explode(",", trim(fgets($fp), "\n"));
            $this->aryAbc[$abc[0]] = trim($abc[1]);
        }
        fclose($fp);
    }

    /**
     * カナ取得(string)
     * @param  string $string
     * @return string
     */
    public function getKana($string)
    {
        $aryStr = array();
        $strlen = mb_strlen($string);
        for ($i=0; $i<$strlen; ++$i) {
            $aryStr[] = $this->getKana_char(mb_substr($string, $i, 1));
        }

        return implode("・", $aryStr);
    }

    /**
     * カナ取得(char)
     * @param  char   $char
     * @return string
     */
    public function getKana_char($char)
    {
        return (isset($this->aryAbc[$char]))? $this->aryAbc[$char]:$char;
    }
}
