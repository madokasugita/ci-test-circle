<?php

if (!defined("DIR_ROOT")) {
    echo "no DIR_ROOT error";
    exit;
}
define("ERROR_RESOURCE", DIR_DATA."error.txt");

/**
 * エラー表示用関数ライブラリ
 *
 * 依存：defineでDIR_ROOT、ERROR_RESOURCEを設定しておくこと
 * 2007/02/05 1.0 Cbase akama
 * @package Cbase.Research.Lib
 * @version 1.0
 */
class FError
{
    /**
     * エラー内容の定数を定義する
     * @author Cbase akama
     */
    public function getErrorConst()
    {
        /*
         * フォーマット
         *  [name] ＝＞
         *    [comment] => エラーの解説
         *    [prefix] => エラー文の前につく文字の解説 　○○は[ 設定可能箇所 ]
         *    [suffix] => エラー文の後ろにつく文字の解説　[ 設定可能箇所 ]によるエラー
         */

        $result = array(
            "IN_INDEX" => array(
                            "comment" => "回答ページにおける予期せぬエラー全般"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"IN_PREV" => array(
                            "comment" => "プレビュー時における予期せぬエラー全般"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"HISSU_NOTHING" => array(
                            "comment" => "必須回答に回答していない場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"OTHER_NOTHING" => array(
                            "comment" => "記入欄への回答が無い場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"CHOICE_OVER" => array(
                            "comment" => "最大選択数をオーバーして選択した場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"NO_NUMBER" => array(
                            "comment" => "数字以外が記入された場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"CHOICE_NEED" => array(
                            "comment" => "最低選択数に満たない場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"COND5" => array(
                            "comment" => "条件５の設定エラー"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"RANDOMIZE_INVALID" => array(
                            "comment" => "ランダマイズフォーマットが不正な設定の場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"RANDOMIZE_REPEAT" => array(
                            "comment" => "ランダマイズ範囲が重複する設定の場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"RANDOMIZE_STEP_PAGE" => array(
                            "comment" => "ランダマイズ範囲がページをまたぐ設定の場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        )
            ,"RANDOMIZE_REVERSE" => array(
                            "comment" => "ランダマイズ終了位置が開始位置より手前の場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        ),
            "AUTH_ERROR" => array(
                            "comment" => "アンケート認証画面でID・PWの入力が無いか間違っている場合"
                            ,"prefix" => ""
                            ,"suffix" => ""
                        ),
        );

        return $result;
    }

    /**
     * エラー文字列を取得
     * @param  string $errorName エラーの名称。エラーファイルの内容と対応
     * @return string エラー文字列
     * @author Cbase akama
     */
    public function get($errorName)
    {
        $a = FError::getErrorArray();

        return $a[$errorName]?$a[$errorName]: "%NO_ERROR_NAME%";
    }

    /**
     * エラー文字配列を返す
     * @return array エラー文字配列
     * @author Cbase akama
     */
    public function getErrorArray()
    {
        global $ErrorArray;
        if (!$ErrorArray) {
            $ErrorArray = FError::loadErrorArray();
        }

        return $ErrorArray;
    }

    /**
     * エラー文字ファイルを読み込む
     *
     * @param  resource $filePath fopenで開いたファイルへのパス
     * @return array    エラー文字配列
     * @author Cbase akama
     */
    public function loadErrorArray()
    {
        $filePath = fopen(ERROR_RESOURCE, "r");
        $result = array ();
        while ($line = fgets($filePath, 1000000)) {
            if (strlen($line) < 3) continue;

            //BOM削除
            if (empty($result)) {
                if (ord($line{0}) == 0xef && ord($line{1}) == 0xbb && ord($line{2}) == 0xbf) {
                    $line = substr($line, 3);
                }
            }
            //$line=html_escape($line);

            preg_match('|([^=]+)=(.+)|i',$line,$match);

            $result[trim($match[1])] = trim($match[2]);

        }

        return $result;
    }

    /**
     * Cbaseエラーオブジェクトかどうかを判定
     * @param  object $obj 任意のオブジェクト
     * @return mixed  エラーオブジェクトならエラークラス名、違えばfalse
     * @author Cbase akama
     */
    public function is($obj)
    {
        $res = false;
        if (is_a($obj, "CbaseException")) {
            $res = get_class($obj);
        }

        return $res;
    }
}

class CbaseException
{
    public $errorMessage;

    /**
     * コンストラクタ
     * @access public
     * @param string $msg エラーメッセージ
     * @author Cbase akama
     */
    public function CbaseException($msg)
    {
        $this->errorMessage = $msg;
    }

    /**
     * エラーメッセージ等の情報を取得
     * @access public
     * @return string エラー情報の文字列
     * @author Cbase akama
     */
    public function getInfo()
    {
        return $this->errorMessage;
    }
}
