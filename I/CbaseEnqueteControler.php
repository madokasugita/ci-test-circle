<?php

require_once 'QuestionType.php';

//TODO:$this->enquete->respondentに関わる処理をここではなくEnqueteでできるだろうか
/**
 * このクラス、またはその継承を読むことでアンケート処理を行える
 */
class EnqueteControler
{
    public $enquete;
    public $viewer;
    public $basedata;
    public function EnqueteControler(& $viewer)
    {
        $this->viewer = & $viewer;
    }

    /**
     * アンケートをセットアップし、表示結果を返す
     * @return string html
     */
    public function show()
    {
        $this->basedata = $this->viewer->getHiddenValue($this->getRequest());

        if ($this->basedata) {
            $this->timeCheck(); //時間切れになっていないかどうかのチェック
            $this->setEnqueteFromBasedata($this->basedata);
            if ($_SESSION) {
                //アンケート回答中(次へボタン、送信ボタンを押したとき)
                $this->viewer->initialize($this->enquete, $this->getAnswers());
                $page =& $this->getNextPageClass();
            } else {
                //アンケート回答中(次へボタン、送信ボタンを押したが、セッション切れだったとき)
                $this->viewer->initialize($this->enquete, $this->getAnswers());
                $page = & $this->getRestorationPageClass ();
            }
        } else {

            $this->setEnqueteFromQuery($this->getQuery());
            $event = & $this->enquete->getEvent();
            //初回オープン時でオープンアンケートの場合はuidは個別に設定される
            if ($this->enquete->isOpenEnquete()) {
                $this->enquete->respondent["uid"] = $this->getUniqueId();
            }
            $this->viewer->initialize($this->enquete, $this->getAnswers());
            //アンケート画面を最初に開いた場合
            $page = & $this->getFirstPageClass();
            $this->enquete->addBasedata($this->getDefaultBasedata());
        }

        $this->authCheck(); //回答権限チェック
        $page->setAnswers($this->getAnswers());
        $page->request = & $this->getRequest();

        return $page->show($this->basedata['page']);
    }

    public function setEnqueteFromQuery($query)
    {
        $this->setEnquete(Enquete :: fromQuery($query));
    }

    public function setEnqueteFromBasedata($basedata)
    {
        $this->setEnquete(Enquete :: fromBasedata($basedata));
    }

    /**
     * @return object EnqueteFirstPage ページを最初に開いた時の処理クラスを返す
     *                最初に開いたページとは「その回答者がアンケートシステムで最初に開いたページ」であり
     *                1ページ目のことではない。（途中保存から再開の場合などは途中ページになる）
     */
    function &getFirstPageClass ()
    {
        return new EnqueteFirstPage($this->enquete, $this->viewer);
    }

    /**
     * @return object EnqueteRestorationPage アンケート再接続時の処理クラスを返す
     *                主にセッションが切れた時の処理を行う
     */
    function &getRestorationPageClass ()
    {
        return new EnqueteRestorationPage($this->enquete, $this->viewer);
    }

    /**
     * @return object EnqueteNextPage アンケート初回ページ以降の処理クラスを返す
     */
    function &getNextPageClass ()
    {
        return new EnqueteNextPage($this->enquete, $this->viewer);
    }

    /**
     * アンケートの回答権限があるかどうかチェックし、無ければ終了処理を行う
     */
    public function authCheck()
    {
        if ($this->enquete->isNeedAuth() && !$this->enquete->basedata['auth_flag']) {
            header('Location: ' . getAuthPageURL($this->enquete->getEvent()));
            exit;
        }

    }

    /**
     * 時間切れになっていないかどうかチェックし、無ければ終了処理を行う
     */
    public function timeCheck()
    {
        if (ENQ_TIMEOUT && time() - $this->basedata['time'] > ENQ_TIMEOUT) {
            print FError :: get("IN_INDEX"); //timeOutだった場合
            exit;
        }
    }

    /**
     * 初回オープン時に引継ぎデータとして設定する項目をセットする
     */
    public function getDefaultBasedata()
    {
        $return = array ();

        //auth.phpから認証フラグ引継ぎ
        if ($_GET['c']) {
            $event = & $this->enquete->getEvent();
            //30秒以内にauth.phpから引き継げなければエラーに。
            if (substr(decrypt($_GET['c']), 0, 8) === $event['rid'] && substr(decrypt($_GET['c']), 8) + 30 > time()) {
                $return['auth_flag'] = 1;
            }
        }
        $return['time'] = time();

        return $return;
    }

    /**
     * serial_noを新規に取得する
     * @return string ユーザID(serial_no)
     */
    public function getUniqueId()
    {
        if (!defined("T_UNIQUE_SERIAL")) {
            echo "IDテーブルが設定されていません";
            exit;
        }

        return getUniqueIdWithTable(T_UNIQUE_SERIAL, "serial_no", 8);

    }

    /**
     * 処理するアンケートをセットする
     * @param object Enquete $enquete
     */
    public function setEnquete(& $enquete)
    {
        if (!$enquete) {
            echo FError :: get("IN_INDEX");
            if (DEBUG)
                print __FILE__ . ':' . __LINE__;
            $GLOBALS['AuthSession']->sessionReset();
            exit;
        }
        $this->enquete = & $enquete;
    }

    /*
     * 以下、グローバル変数の取得箇所は分割している
     * これを別の定数や、メンバ変数を返すようにすることにより、
     * リクエストなどを自由に操作できる
     */

    /**
     * ◆virtual
     * リサーチへのリクエスト（POST,GETなど）を取得する
     * ここをoverrideすることでDBから読み出したデータやメンバ変数など入力値を自由に設定できる
     * @return array リクエスト内容の連想配列
     */
    function & getRequest()
    {
        return $_POST;
    }

    /**
     * ◆virtual
     * 回答データの一時格納先を指定する。
     * セッションであることが前提なので、
     * それ以外を設定の際はshowメソッドの最後に回答を保存するなど同等の動作をさせなければならない
     * @return array 回答データが一時格納される場所への参照
     */
    function & getAnswers()
    {
        if (!$_SESSION['answer'])
            $_SESSION['answer'] = array ();

        return $_SESSION['answer'];
    }

    /**
     * ◆virtual
     * クエリを取得する。
     * 変更目的としては、クエリがURLに関わらず常に一致の場合など。
     * 今のところこの値はリサーチのクエリ定義に沿っていなければならない
     * @return string クエリ文字列
     */
    public function getQuery()
    {
        $query = $_SERVER['QUERY_STRING'];
        $query = ereg_replace('&.*', '', $query); //他に引数を渡すので&以降はけずる

        return $query;
    }

}

/**
 * Researchに限らずどこでも使える関数についてはとりあえず切り出してある
 * このクラスを継承するのではなく、このクラスをメンバとして使うのが正解
 */
class BasePage
{
    public $sid;
    public function getSID()
    {
        if (!$this->sid) {
            $this->sid = getSID();
        }

        return $this->sid;
    }

    public function location($url)
    {
        header('Location: ' . $url);
        exit;
    }
}

class EnqueteAnswerControler
{

    //Enquete用
    public $enquete;
    public $answers = array ();
    public $cache;

    public function EnqueteAnswerControler(& $enquete)
    {
        $this->enquete = & $enquete;
        $this->cache = & new AnswerCacheControler($enquete);
    }

    /**
     * 回答データをセットする。プロパティ。
     * @param array $answers アンケート用回答データの配列
     */
    public function setAnswers(& $answers)
    {
        $this->answers = & $answers;
    }

    function &loadAnswerClass ()
    {
        $event = $this->enquete->getEvent();
        $cls =  EnqueteAnswer :: load($event['evid'], $this->enquete->respondent['uid']);

        return $cls;
    }

    //回答完了済みのデータを読み込んだ場合はtrueになる。ただし読み込んだ初回のみ。
    public $reopen = false;

    public function getAnswerState($answerCls)
    {
        return $answer->getAnswerState();
    }

    /**
     * 途中回答のデータがあれば読み込む
     * @return int 読み込んだ結果最新のページを返す
     */
    public function loadMidstAnswers($event)
    {
        /*
         * キャッシュとREOPENの違い
         * ・キャッシュIDを考慮する
         * 　REOPENは「質問が増えたので再回答してください」にも対応するが、
         * 　キャッシュは質問が増えても、常に開いた時のまま表示
         *
         */
        /*
         * そのユーザの途中保存があれば読み込む→最終ページ
         * 途中保存データがあればページを指定
         * どっちもなく、REOPENなら読み込む→1ページ
         * どれもない→1ページ
         */
        $page = 0;
        $answer = & $this->loadAnswerClass();
        if ($answer) {
            switch ($this->getAnswerState ($answer)) {
                case 'midst' :
                    $this->loadAnswer($answer);
                    //最後の回答があるページを取得
                    $page = $this->getLastPage();
                    if (0 < $event['flgs'] && $cache = $this->cache->load()) {
                        $this->enquete->setCache($cache->data);
                        $this->enquete->loadCache();
                        $page = $this->getStartPage($event['lastpage'], $page, $cache->data['page']);
                    } else {
                        $page = $this->getStartPage($event['lastpage'], $page);
                    }

                    break;
                case 'completed' :
                    if (!REOPEN) { //flgo:0=固有URL回答,1=オープンアンケート
                        //固有URLかつ再開機能がオンなら回答済みデータを復帰
                        $page = 1;
                        $this->loadAnswer($answer);
                        define('REOPENED_FLAG',1);//360度用カスタマイズ
                    }
                    $this->reopen = true;
                    break;
                case 'deleted' :
                    $page = 1;
                    break;
                default :
                    return 1;
            }
        }

        return $page ? $page : 1;
    }

    /**
     * ページ番号を管理する３つの要素から初期表示すべきページ番号を判断する
     * @param  int $lastpage  イベントに設定された最終ページ
     * @param  int $nowpage   現在いるページ（通常はこの次のページを表示する）
     * @param  int $cachepage 途中保存されたページ番号
     * @return int 表示すべきページ番号
     */
    public function getStartPage($lastpage, $nowpage, $cachepage = 0)
    {
        /*
         * 現在の処理
         * バックアップされたページ番号がある(0の場合は無し)＝最優先でそのページ番号に復帰
         * （1>2>3途中保存3>2>1とした場合3から始まってしまう不具合あり）→画面遷移時はbackup_Dataをリセットすることで回避
         * それ以外は最初のページから
         */

        /*
         * ページ番号が0→一度も途中保存ボタンをおしていないが、途中回答はある
         * それ以降→途中ボタンを押してからの再開か、
         * 　　　　　または途中ボタンを押して再開後、ボタンを押さずに中断してからの再開
         */
        if (0 < $cachepage) {
            $this->enquete->startPage = $cachepage;

            return $cachepage;
        } else {
            return 1;
        }
        //以下の仕様を再現するためには、上のelseを削り下を復帰する
        /*
             * 最終ページと最終回答のページが同じ（にもかかわらず回答途中）＝最終ページから復帰
         * それ以外＝最終回答のページの次のページから再開
         * （最終回答が戻るによるものだった場合、回答を飛び越す不具合あり）
         */
        //		elseif($lastpage == $nowpage)
        //		{
        //			return $lastpage;
        //		}
        //		return $this->viewer->nextPage($nowpage);
    }

    /**
     * 回答データクラスから回答を読み込みアンケート用回答データに変換する
     * @param object EnqueteAnswer $answerCls
     */
    public function loadAnswer($answerCls)
    {
        //TODO:この処理汚いのでrespondentに$answerClsを持たせるなどする
        $this->enquete->respondent['event_data_id'] = $answerCls->info['event_data_id'];

        //TODO:別に$this->answer = QuestionTYpeｸA靴任いい隼廚Δ・
        foreach (QuestionType::getValueByDB($answerCls->answer, $this->enquete) as $k => $v) {
            $this->answers[$k] = $v;
        }
        //TODO:answerClsの側でQuestionType読んでもいいかも
    }

    /**
     * 回答された中での最後のページ番号を取得する
     * @return int 回答された中で最後のページ番号
     */
    public function getLastPage()
    {
        $page = 1;
        foreach ($this->answers as $k => $v) {
            //seidを取る
            $seid = preg_replace('/(^P_)|(^T_)|(^E_)/', '', $k);

            $se = $this->enquete->getBySeid($seid);
            $page = max($page, $se['page']);
        }

        return $page;
    }
}

//=============================================================================

/**
 * 途中回答は無視するコントローラ
 */
class IgnoreMidstAnswerControler extends EnqueteAnswerControler
{

    /**
     * 途中回答のデータがあれば読み込む
     * @return int 読み込んだ結果最新のページを返す
     */
    public function loadMidstAnswers($event)
    {
        $page = 0;
        $answer = & EnqueteAnswer :: load($event['evid'], $this->enquete->respondent['uid']);
        if ($answer) {
            switch ($answer->getAnswerState()) {
                case 'midst' :
                    $this->cache->delete($answer->info['event_data_id']);
                    $answer->deleteInfo ();
                    $page = 1;
                    break;
                default :
                    return parent::loadMidstAnswers($event);
            }
        }

        return $page ? $page : 1;
    }

}
//=============================================================================
/**
 * アンケートのページを示す基底クラス。整備はいまいち
 */
class EnquetePage
{

    //Enquete用
    public $enquete;
    public $viewer;
    public $answers = array ();
    //委譲クラス
    public $common;
    public $answerCtlr;
    public function EnquetePage(& $enquete, & $viewer)
    {
        $this->enquete = & $enquete;
        $this->viewer = & $viewer;
        $this->common = new BasePage();
        $this->setupAnswerControler();
    }

    public function setupAnswerControler()
    {
        $this->answerCtlr = new EnqueteAnswerControler($this->enquete);
    }

    public function getCache()
    {
        return $this->answerCtlr->cache;
    }

    //委譲メソッド
    public function getSID()
    {
        return $this->common->getSID();
    }

    //委譲メソッド
    public function location($url)
    {
        return $this->common->location($url);
    }

    public $request;

    /**
     * 回答データをセットする。プロパティ。
     * @param array $answers アンケート用回答データの配列
     */
    public function setAnswers(& $answers)
    {
        return $this->answerCtlr->setAnswers( $answers);
    }

    //回答完了済みのデータを読み込んだ場合はtrueになる。ただし読み込んだ初回のみ。
    public $reopen = false;
    /**
     * 途中回答のデータがあれば読み込む
     * @return int 読み込んだ結果最新のページを返す
     */
    public function loadMidstAnswers($event)
    {
        return $this->answerCtlr->loadMidstAnswers($event);
    }

    public function isReopen()
    {
        return $this->answerCtlr->reopen;
    }

    function &getAnswers()
    {
        return $this->answerCtlr->answers;
    }

    public function createAnswerClass()
    {
        $event = & $this->enquete->getEvent();
        $user = & $this->enquete->respondent;

        return EnqueteAnswer :: create($user['event_data_id'], $event['evid'], $user['uid'], $user['flg']);
    }

    /**
     * ◆abstruct
     * 継承先で必ず実装すること。アンケートの更新処理を行いhtmlを返す
     * @param  int    $page 更新しない場合に表示したいページの番号(一つ前に開いていたページ)
     * @return string html
     */
    public function show($page)
    {
    }
}

class EnqueteFirstPage extends EnquetePage
{
    //override
    public function setupAnswerControler()
    {
        if (ENABLE_MIDST) {
            $this->answerCtlr = new EnqueteAnswerControler($this->enquete);
        } else {
            $this->answerCtlr = new IgnoreMidstAnswerControler($this->enquete);

        }
    }

    //override
    public function show($page)
    {
        $event = & $this->enquete->getEvent();
        //ユーザー存在確認
        if (CHECK_USER_EXISTS && $event["flgo"] == "0") {
            if (count(Get_UserData("sp", "serial_no", $this->enquete->respondent['uid'])) == 0) {
                $this->location(DOMAIN . DIR_MAIN . 'error.html');
            }
        }

        $page = $this->loadMidstAnswers($event);
        //ロードの仮定でenqueteが書き換わることもあるため再取得
        $this->checkShowable($this->enquete->getEvent());
        //ページ表示

        if(MODE_PRINT==1)
            $page = $this->enquete->getEvent('lastpage');

        return $this->viewer->show($page);
    }

    /**
     * クリックURLでないかどうかを判断する
     * TODO:※この機能は現状使われていないこともあり、テストも不完全。
     * 真面目に実装するのであれば、ここではなく、新たにEnquetePageを継承したクラスを作って処理すべき
     */
    public function checkModeClickUrl($event)
    {
        //TODO:このtypeはeventのtypeではないの？ →YESとのこと。ただし一応そのまま

        if ($this->enquete->respondent['type'] == '3') {
            //アクセス記録
            $ccu = Check_Data($event["evid"], $this->enquete->respondent['uid'], "");
            if ($ccu == 0) {
                $this->saveEnqueteData($this->getAnswers());
            }
            //リダイレクト
            $GLOBALS['AuthSession']->sessionReset();
            $this->location($event["url"]);
        }
    }

    //TODO:Enqueteで処理するのが適切かも
    /**
     * 重複していればエラー画面へ飛ぶ
     * @param array $event 表示しようとしているアンケートのevent
     */
    public function checkDuplicate($event)
    {
        //Cookie重複回答制御 (defineで制御しない設定の場合に記述があっても問題なし)
        if ($event['flgo'] != '0' && $event['flgo'] != '2') {
            $objNd = new Noduplication($event['rid'], HTML_ALREADYENTRY);
            $objNd->DoCheck();
        }
        //重複回答チェック
        if ($event['flgo'] == 0 || $event['flgo'] == 2) { //flgo:0=固有URL回答,1=オープンアンケート
            //TODO:会員指定での複数回答には対応していない。
            //複数回答OKの場合は条件を追加して以下を無視させること
            if (REOPEN && $this->isReopen()) {
                $this->location(DOMAIN . DIR_MAIN . HTML_ALREADYENTRY);
            }
        }
    }

    //TODO:Enqueteで処理するのが適切かも
    /**
     *
     */
    public function checkTerm($event)
    {
        $now = mktime();
        if ($event['sdate']) {
            if ($now < $this->getTime($event['sdate'])) {
                $this->location(DOMAIN . DIR_MAIN . 'closed.html');
            }
        }
        if ($event['edate']) {
            if ($this->getTime($event['edate']) <= $now) {
                $this->location(DOMAIN . DIR_MAIN . 'closed.html');
            }
        }
    }

    //TODO:→汎用クラスに移動できる
    /**
     * 「YYYY-MM-DD HH:ii:ss」の形式からunixtimeを取得する
     * @param  string $date 「YYYY-MM-DD HH:ii:ss」
     * @return int    unixtime
     */
    public function getTime($date)
    {
        $tmpar = explode(' ', $date);
        $tmpdt = explode('-', $tmpar[0]);
        $tmptm = explode(':', $tmpar[1]);

        return mktime($tmptm[0], $tmptm[1], $tmptm[2], $tmpdt[1], $tmpdt[2], $tmpdt[0]);
    }

    //TODO:Enqueteで処理するのが適切かも
    /**
     * 回答上限数に引っかかっていたらエラー画面へ
     * @param array $event 表示しようとしているアンケートのevent
     */
    public function checkLimitAnswer($event)
    {
        //回答数制限チェック
        if (0 < $event['limitc']) {
            $cdd = Check_Data($event['evid'], '', $this->enquete->respondent['flg']);
            if ($cdd != 0 && $event['limitc'] <= $cdd) {
                $this->location(DOMAIN . DIR_MAIN . 'no_entry.html');
            }
        }
    }

    /**
     * 開始条件チェックを増やしたい場合はこの関数をオーバーライドする
     * エラー時の処理は各関数で設定
     * @param array $event 表示しようとしているアンケートのevent
     */
    public function checkShowable($event)
    {
        $this->checkModeClickUrl($event);
        $this->checkTerm($event);
        $this->checkDuplicate($event);
        $this->checkLimitAnswer($event);
    }
    /*
     * メモ
     * EnqueteErrorCheckerみたいなクラスを作り、
     * そこでEnqueteクラスに対してチェックをするような処理が理想的かもしれません
     */

}

class EnqueteNextPage extends EnquetePage
{
    public $answerCls;

    /**
     * アンケート回答中 ($_SESSION['page']が1以上の場合)の表示
     */
    public function show($page)
    {
        $newpage = $this->updatePage($page);
        $GLOBALS['enq_page'] = $newpage;
        if ($newpage) {
            if (1 < abs($newpage - $page)) {
                $this->clearOldAnswer($newpage);
            }

            $html = $this->viewer->show($newpage);
            //回答条件などの都合で、アンケート内容がない場合は強制的にアンケート完了処理
            if (!$html) {
                $this->onFinish();

                return false;
            }

            return $html;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function clearOldAnswer($page)
    {

        $beforepage = $page -1;
        $delSeids = array();
        while (!$this->viewer->getVisibleParts($this->enquete->getSubEvents(), $beforepage)) {
            //このページの質問を全部削除
            foreach ($this->viewer->getQuestions($this->enquete->getSubEvents(), $beforepage) as $v) {
                $seid = $v['seid'];
                $this->clearSessionAnswer($seid);
                //フリースペースは回答が無いので除外する
                if (QuestionType::isAnswerable($v)) {
                    $delSeids[] = $seid;
                }
            }

            if (--$beforepage <= 0) break;
        }
        $this->clearDBAnswer($delSeids);
    }

    /**
     * DBに格納された回答を削除する。効率のため複数seidを配列で指定
     */
    public function clearDBAnswer($seids)
    {
        if ($seids) {
            $answerCls = $this->createAnswerClass ();
            $answerCls->deleteList = $seids;
            $answerCls->saveMidst();
        }
    }

    /**
     * セッションに格納された回答を削除する。DBからは消さない。
     */
    public function clearSessionAnswer($seid)
    {
        //変数なのであるなしにかかわらずunsetしてしまう
        $ans =& $this->getAnswers();
        unset ($ans['P_' . $seid]);
        unset ($ans['T_' . $seid]);
        unset ($ans['E_' . $seid]);
        unset ($ans['DM_' . $seid]);
    }

    public function setNewAnswer($page, $temp_answers)
    {
        $answerCls = $this->createAnswerClass ();

        $event = & $this->enquete->getEvent();
        $user = & $this->enquete->respondent;

        //idを差し戻しておく
        //TODO:この処理汚いので、将来的にはenquete->respondantがanswerを持つなど工夫すること
        if (!$user['event_data_id'])
            $user['event_data_id'] = $answerCls->info['event_data_id'];
        //初回答なら回答途中保存情報をセット
        $c =&  $this->getCache();
        if (!$this->enquete->hasCache()) {
            if (0 < $event['flgs'] && $cache = $c->load()) {
                $this->enquete->setCache($cache->data);
            } elseif (0 < $event['flgs'] && $cache = $c->makeCache()) {
                $this->enquete->setCache($cache->data);
            }
        } elseif (0 < $this->enquete->startPage) {
            $c->initializeCache();
            $this->enquete->startPage = 0;
        }

        $answerCls->deleteList = $temp_answers['seids'];
        $answer = & $this->setEnqueteAnswer($answerCls, $temp_answers['answers']);

        $answer->saveMidst();

    }

    /*
     * エラー処理について
     *
     * POST投稿→セッションに登録
     * 　エラーあり→エラー表示
     * 　エラーなし→DBに登録
     *
     * エラーの場合、DBとセッションに相違が出るが、セッションは次のPOSTの際に上書きされるので特に問題はない
     *
     */

    public function getTempAnswers($page)
    {
        $temp_answers = QuestionType::getValueByPost(
            $this->viewer->getQuestions($this->enquete->getSubEvents(), $page),
            $this->request
        );
        $ans =& $this->getAnswers();
        foreach ($temp_answers['unsets'] as $v) {
            unset ($ans[$v]);
        }
        foreach ($temp_answers['answers'] as $k => $v) {
            $ans[$k] = $v;
        }

        return $temp_answers;
    }

    //戻るボタンが押されたかどうかを判別
    public function isPreviousButton()
    {
        return ($this->request['pb'] || $this->request['pb_x']);
    }

    //途中保存ボタンが押されたかどうかを判別
    public function isSaveButton()
    {
        return ($this->request['ss'] || $this->request['ss_x']);
    }

    //メンバ変数やセッションを更新する
    public function updatePage($page)
    {
        //新たな回答を格納する
        $temp_answers = $this->getTempAnswers($page);

        //★★★戻るボタンを押したとき **************************************************************
        if ($this->isPreviousButton()) {
            $this->setNewAnswer($page, $temp_answers);

            return $this->onPrevious($page);
        }
        //★★★途中保存ボタンを押したとき ***********************************************************
        if ($this->isSaveButton()) {
            $this->setNewAnswer($page, $temp_answers);

            return $this->onSave($page);
        }

        //エラーチェック
        $error = $this->isError($this->getAnswers(), $page);

        //エラーがあった場合はエラー内容をアンケート内容にくっつけて返す
        if ($error) {
            $this->viewer->setError($error);
        } else {
            $this->setNewAnswer($page, $temp_answers);
            //			//集計用ダミーデータ入れtype2=c/rのみ
            //			$this->setDummyAnswer();
            if ($this->isLastPage($page)) {
                //★★★送信ボタンを押したとき ****************************************************************
                return $this->onFinish();
            } else {
                //★★★次へボタンを押したとき ****************************************************************
                $page = $this->onNext($page);
                if ($page === false) {
                    //★★★送信ボタンを押したとき ****************************************************************
                    return $this->onFinish();
                }
            }
        }

        return $page;
    }

    public function isLastPage($page)
    {
        $event = & $this->enquete->getEvent();

        return ($page == $event['lastpage']);
    }

    /**
     * 戻る実行時
     */
    public function onPrevious($page)
    {
        return $this->viewer->previousPage($page);
    }

    /**
     * 進む実行時
     */
    public function onNext($page)
    {
        return $this->viewer->nextPage($page);
    }

    /**
     * 途中保存実行時
     */
    public function onSave($page)
    {
        //		$this->cache->data['page'] = $page;
        $c =& $this->getCache();
        $c->save($page);
        //		exit;
        $ev = $this->enquete->getEvent();
        //TODO:適当な値を渡す。backup.phpは基本的に何もしないので表示用の値だけでよい
        $id = Create_QueryString($this->enquete->respondent['uid'], $ev['rid'], $this->enquete->respondent['type'], $this->enquete->respondent['flg']);
        $this->location(DOMAIN . DIR_MAIN . PAGE_BACKUP . '?id=' . $id . '&' . $this->getSID());
    }

    /**
     * 最終ページで送信ボタンを押したときの処理
     */
    public function onFinish()
    {
        //保存
        //		echo '回答終了(デバッグ用に登録処理をスルーしています)';
        //		exit;

        $this->saveEnqueteData($this->getAnswers()); //中で$_SESSIONを展開して判断
        $this->clearTempAnswer();
        $event = & $this->enquete->getEvent();

        if ($event['flgo'] != '0' && $event['flgo'] != '2') {
            //重複回答用フラグをcookieに記録 (defineで制御しない設定の場合に記述があっても問題なし)
            $objNd = new Noduplication($event['rid'], HTML_ALREADYENTRY);
            $objNd->recodeCookie();
        }
        //サンクスページ表示
        $this->locateThanks();
        //		exit;
    }

    public function locateThanks()
    {
        $event = & $this->enquete->getEvent();
        //サンクスページ表示
        if ($event['url']) {
            $SID = (!CLEAR_ENQUETE_SESSION) ? '?' .
            $this->getSID() : '';
            $this->location($event['url'] . $SID);
        } else {
            $this->location(DOMAIN . DIR_MAIN . 'thanks.html');
        }
    }

    public function clearTempAnswer()
    {
        //途中保存機能用のファイル削除
        //		if (BACKUP) {
        $event = & $this->enquete->getEvent();
        if (0 < $event["flgs"]) {
            //		foreach (glob(DIR_SAVESESSION . $data["uid"] . "*") as $filename)
            //		{
            //			@ unlink($filename);
            //		}
            //		$strPath = DIR_SAVESESSION . $data["uid"] . "_" . $data["rid"];
            //		@ unlink($strPath);
            //FBackUpData::deleteBackUpData($this->enquete->respondent["uid"], $event["rid"]);

            //DEBUG:デバッグ文書
            if (!$this->enquete->respondent["event_data_id"]) {
                echo 'clearTempAnswerevent_data_idないよ';
                exit;
            }
            $c =&  $this->getCache();
            $c->delete($this->enquete->respondent["event_data_id"]);
        }

        $a = array ();
        $this->setAnswers($a);
        if (CLEAR_ENQUETE_SESSION) { //reseach_mail
            $GLOBALS['AuthSession']->sessionReset();
        }
    }

    /**
     * アンケート回答時の入力値チェック
     * @param array $error エラー時に文字列を入れる(参照返し)
     * @param array $data  入力値
     * @param array $ed    アンケートデータ
     */
    public function isError($answers, $page,$target=null, $is_plural=false)
    {
        global $serial2name;
        global $GLOBAL_NAME;
        $GLOBAL_NAME = $serial2name[$target];
        $error = array ();
        $error_target = ($is_plural)? $GLOBAL_NAME:"";
        $target_user = $this->enquete->respondent['targets'][$target];

        //必須のものをチェックする
        foreach ($this->viewer->getVisibleQuestions($this->enquete->getSubEvents(), $page) as $key => $val) {
            $val['seid'] = adjustSeidByUserType($val['seid'], $target_user['user_type']);
            $q = & $this->enquete->getQuestion($val['seid']);
            if ($e = $q->getError($answers)) {
                foreach ($e as $vvv) {
                    $error[] = str_replace("%%%%error_target%%%%", $error_target, $vvv);
                }
            }

        } //foreach

        return $error;
    }

    /**
     * 回答データを保存する
     * @param  array $data 回答データ
     * @return bool  成功すればtrue
     */
    public function saveEnqueteData($data)
    {
        global $con; //Pear::DB

        $event = $this->enquete->getEvent();
        $user = $this->enquete->respondent;
        if (!$data) {
            //echo '1111';
            //$this->location(DOMAIN . DIR_MAIN . HTML_ALREADYENTRY);

            //CRMアンケートの場合
        }

        //$answer = & EnqueteAnswer :: create($user['event_data_id'], $event['evid'], $user['uid'], $user['flg']);
        $answer = $this->createAnswerClass();
        $answer->saveCompleteInfo();

        return true;
    }

    function & setEnqueteAnswer(& $answer, $data)
    {
        foreach ($data as $key => $val) {
            if (ereg("^P_", $key)) {
                $keys = str_replace("P_", "", $key);
                if (is_array($val)) {
                    //type2 = r,c
                    foreach ($val as $k => $v) {
                        $choice = ereg_replace("[^0-9]", "", $v);
                        $other = (empty ($data["E_" . $keys])) ? null : $data["E_" . $keys];
                        $answer->addAnswer($keys, $choice, $other);
                    }
                } else {
                    //type2 = p
                    if ($val == "ng")
                        continue; //pulldownのデフォルト
                    $choice = ereg_replace("[^0-9]", "", $val);
                    $other = (empty ($data["E_" . $keys])) ? null : $data["E_" . $keys];
                    $answer->addAnswer($keys, $choice, $other);
                }
            } elseif (ereg("^T_", $key)) { //テキスト回答
                $keys = str_replace("T_", "", $key);
                //記入欄のデータ入れ
                $answer->addAnswer($keys, '-1', $data["T_" . $keys]);
            }
        }

        return $answer;
    }

    public function getSeidFromAnswerKey($answerKey)
    {
        if (ereg("^P_", $answerKey)) {
            $repStr = "P_";
        } elseif (ereg("^T_", $answerKey)) {
            $repStr = "T_";
        } else {
            return false;
        }

        return str_replace($repStr, '', $answerKey);
    }

}

/**
 * セッション切れの際の救済用ページ
 * 途中回答からの復帰を行う
 */
class EnqueteRestorationPage extends EnqueteNextPage
{
    public function show($page)
    {
        $event = & $this->enquete->getEvent();
        //$pageは更新しない
        $this->loadMidstAnswers($event);

        return parent :: show($page);
    }
}

class AnswerCacheControler
{
    public $enquete;
    public function AnswerCacheControler(& $enquete)
    {
        $this->enquete = & $enquete;

    }

    function & load()
    {
        $ev = $this->enquete->getEvent();
        $evdid  = $this->enquete->respondent['event_data_id'];
        if (0 < $ev['flgs']) {
            return AnswerCache :: createByEvdataId($evdid);
        }

        return false;
    }

    public function makeCache()
    {
        $ev = $this->enquete->getEvent();
        if (0 < $ev['flgs']) {
            $cache = & $this->saveCache();
        }

        return $cache;
    }

    /**
     * 途中保存→回答再開時に、途中保存情報を初期化する
     */
    public function initializeCache()
    {
        $cache = & $this->saveCache();
    }

    function & saveCache($page = 0)
    {
        $cache = & AnswerCache :: createByEnquete($this->enquete);
        $cache->data['page'] = $page;
        $cache->save();

        return $cache;
    }

    public function delete($evdid)
    {
        $cache = & AnswerCache :: createByEnquete($this->enquete);
        $cache->delete($evdid);
    }

    public function save($page)
    {
        $ev = $this->enquete->getEvent();

        switch ($ev['flgs']) {
            case 1 :
                $cache = & $this->saveCache($page);
                break;
            case 2 :
                $cache = & $this->saveCache($page);
                break;
            default :
                echo '途中保存フラグエラー';
                exit;
        } //switch

        return $cache;

    }

    public function getRestoreMailBody()
    {
        return<<<EOF
「%%%%enq_name%%%%」をご回答いただいている方へ

---------------------------------------------------------------

以下URLより、ご回答中のアンケートを前回セーブされた設問から
引き続きご回答いただけます。

[アンケート再開URL]
%%%%URL%%%%


※回答期限内にご回答いただけますようご協力の程宜しくお願い致します。


---------------------------------------------------------------
■「%%%%enq_name%%%%」
　 アンケート管理事務局
EOF;
    }
}
