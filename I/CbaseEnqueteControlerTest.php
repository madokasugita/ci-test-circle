<?php

class EnqueteControlerTest extends _360_EnqueteControler
{
    /**
     * @return object EnqueteNextPage アンケート初回ページ以降の処理クラスを返す
     */
    function &getNextPageClass ()
    {
        return new EnqueteNextPageTest($this->enquete, $this->viewer);
    }

    /**
     * @return object EnqueteFirstPage ページを最初に開いた時の処理クラスを返す
     *                最初に開いたページとは「その回答者がアンケートシステムで最初に開いたページ」であり
     *                1ページ目のことではない。（途中保存から再開の場合などは途中ページになる）
     */
    function &getFirstPageClass ()
    {
        return new EnqueteFirstPageTest($this->enquete, $this->viewer);
    }

    /*
     * テストモードでは以下を無効とする
     */

    /**
     * アンケートの回答権限があるかどうかチェックし、無ければ終了処理を行う
     */
    public function authCheck()
    {
        return;
    }

    /**
     * 時間切れになっていないかどうかチェックし、無ければ終了処理を行う
     */
    public function timeCheck()
    {
        return;
    }
}

class EnqueteFirstPageTest extends EnqueteFirstPage
{
    /**
     * 開始-終了期間内であるかどうかをチェックし、不可ならエラー画面へ
     * @param array $event 表示しようとしているアンケートのevent
     */
    public function checkTerm($event)
    {
        //なにもしない
    }
}

class EnqueteNextPageTest extends _360_EnqueteNextPage
{

    /**
     * DBに格納された回答を削除する。効率のため複数seidを配列で指定
     */
    public function clearDBAnswer($seids)
    {
        //なにもしない
    }

    public function setNewAnswer($page, $temp_answers)
    {
        //なにもしない
    }
    /**
     * 途中保存実行時
     */
    public function onSave($page)
    {
        $this->location('test_thanks.html');
        exit;
    }

    /**
     * 最終ページで送信ボタンを押したときの処理
     */
    public function onFinish()
    {
        $this->location('test_thanks.html');
        exit;
    }
    /**
     * 回答データを保存する
     * @param  array $data 回答データ
     * @return bool  成功すればtrue
     */
    public function saveEnqueteData($data)
    {
        return true;
    }
}
