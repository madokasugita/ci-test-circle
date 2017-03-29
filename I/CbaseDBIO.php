<?php
/*
 * DBからのインプットアウトプット
 *
 */

class AbstructDBIO
{
    //各行のデータ区切り文字
    public $separater = "\t";

    //改行文字
    public $newLine = "\n";

    public $adapter;

    public function AbstructDBIO($adpt)
    {
        $this->adapter = $adpt;
    }

    /*
     * インポートデータのエラーチェック(列数チェック)
     */
    public function isColumnCount($head)
    {
        if (count(explode($this->separater, $head)) != count($this->adapter->getFileFormat())) {
            return false;
        }

        return true;
    }
}
class Exporter extends AbstructDBIO
{

    /*
     * エクスポート結果を取得する
     */
    public function run()
    {
        $line[] = $this->getHeader();
        foreach ($this->adapter->getAllLine() as $v) {
            $line[] = $this->makeOutputLine($v);
        }

        return implode($this->newLine, $line);
    }

    /*
     * エクスポートデータのヘッダを取得
     */
    public function getHeader()
    {
        $line = array_keys($this->adapter->getFileFormat());

        return implode($this->separater, $line);
    }

    /*
     * エクスポートデータの一行を作成
     */
    public function makeOutputLine($data)
    {
        $format = $this->adapter->getFileFormat();
        $line = array();
        foreach ($format as $v) {
            $line[$v] = $this->adapter->getExportValue($data, $v);
        }

        return implode($this->separater, $line);
    }

    /**
     * 処理結果を直接ファイルとしてダウンロード
     */
    public function export($filename, $encode='SJIS')
    {
        //TODO:output_encodeが有効のためそちらの文字コードで出力されてしまう問題あり。
        //現状はSJISなので問題ないこととする
        $encode = INTERNAL_ENCODE;
        FFile::download(mb_convert_encoding($this->run(),$encode, INTERNAL_ENCODE), $filename);
        exit;
    }
}

class Importer extends AbstructDBIO
{

    /*
     * クラスへのインポートを実行する
     */
    public function run($texts)
    {
        $texts = explode($this->newLine, $texts);
        if (!$this->isColumnCount(array_shift($texts))) {
            return false;
        }
        $result = array();
        foreach ($texts as $v) {
            if (trim($v) != "") {
                $result[] = $this->getLine(trim($v));
            }
        }

        return $this->adapter->setAllLine($result);
    }

    /*
     * インポートデータの一行を配列に変換
     */
    public function getLine($data)
    {
        $data = explode($this->separater, $data);
        $format = $this->adapter->getFileFormat();
        $line = array();
        $i = 0;
        foreach ($format as $v) {
            $value = $this->adapter->getImportValue($v, $data[$i++]);
            if($value) $line[$v] = $value;
        }

        return $line;
    }
}

class DBIOAdapter
{
    //出力する全行を取得する
    public function getAllLine()
    {
        //ここでデータクラスから取得し変換する
        return array();
    }

    public function getExportValue($data, $key)
    {
        return $data[$key];
    }

    //入力する全行が送られてくる
    public function setAllLine($data)
    {
        //ここでデータ変換してデータクラスのsaveへ送る
    }

    public function getImportValue($key, $value)
    {
        return $value;
    }

    public function getFileFormat()
    {
        return array(
            "キー" => "カラム名"
        );
    }
}
