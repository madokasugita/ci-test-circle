<?php
function _360_resolveQueryPost($post)
{
    if (!$post['answer_mode']) {
        echo '複数対象選択モードではありません';
        exit;
    }

    /*
     * 送られてくるパラメータ
     * targets -> :で分割、_360_getEnqueteHash_LumpModedeで検証
     * answer_mode
     * position -> usertype
     * self_id -> serial_no
     */

    $sa["type"] = 1;
    $sa["flg"] = 1;
    $user_type = $post['position'];
    $uid = $post['self_id'];
    $diag = $post['diagnosis'];
    $sa["rid"] = getRidByDinognosisAndUserType($diag, $user_type);
    $sa["uid"] = $uid;
    if (!isset($post['targets']) || is_void($post['targets'])) {
        _360_error(2,1);
    }
    foreach ($post['targets'] as $v) {
        $ids = explode(':', $v);
        if (_360_getEnqueteHash_LumpMode($uid, $ids[0]) !== $ids[1]) {
            _360_error(1);
        }
        $sa["target"][] = $ids[0];
    }
    if (count($sa["target"]) > 5) {
        _360_error(3, 1);
    }

    return $sa;
}

/**
 * 360度用アンケートクラス
 */
class _360_Enquete extends Enquete
{

    //-------------------コンストラクタ
    //元々の定義が悪く、継承が難しいため、全てコピーしてクラス名を書き換えている

    /**
     * ◆static
     * クエリから読み込み作成
     * @param  int    $query 有効なクエリ
     * @return object Enquete アンケートクラス
     */
    function & fromQuery($query)
    {
        //TODO:Resolve_QueryStringはEnqueteクラスの仕事にしてもよい
        $data = _360_resolveQueryString($query); //queryStringから　type,rid,uid,flgのを取得し、$_SESSIONに登録

        if ($data === false)
            return false;

        $enq = & _360_Enquete :: fromRid($data['rid']);
        define('ENQ_RID', $data["rid"]);
        if ($enq) {
            //Queryの時のみ読み込むのは、以後はbasedataで持ちまわるため
            //flgoのあまりが0のときユーザを読む
            $ev = $enq->getEvent();
            if (!$enq->isOpenEnquete()) {
                $data = $enq->loadTargetsInfo($data);
            }
            $enq->setHiddenValue($data);
        }

        if (!$_SESSION['login']) {
            $result = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($data['uid']));
            setSessionLoginData360($result);
        }

        return $enq;
    }

    function & fromPost($post)
    {
        //TODO:Resolve_QueryStringはEnqueteクラスの仕事にしてもよい
        $data = _360_resolveQueryPost($post); //queryStringから　type,rid,uid,flgのを取得し、$_SESSIONに登録
        if ($data === false)
            return false;

        $enq = & _360_Enquete :: fromRid($data['rid']);
        define('ENQ_RID', $data["rid"]);
        if ($enq) {
            //Queryの時のみ読み込むのは、以後はbasedataで持ちまわるため
            //flgoのあまりが0のときユーザを読む
            $ev = $enq->getEvent();
            if (!$enq->isOpenEnquete()) {
                $data = $enq->loadTargetsInfo($data);
            }
            $enq->setHiddenValue($data);
        }

        return $enq;
    }

    /**
     * ◆static
     * POST情報から読み込み作成
     * @param  array  $hiddenValue POSTされたアンケート情報
     * @return object Enquete アンケートクラス
     */
    function & fromBasedata($hiddenValue)
    {
        $enq = & _360_Enquete :: fromRid($hiddenValue['rid']);
        define('ENQ_RID', $hiddenValue["rid"]);
        if ($enq) {
            $enq->setHiddenValue($hiddenValue);
        }

        return $enq;
    }

    /**
     * ◆static
     * ridから読み込み作成
     * @param  int    $rid       Get_Enqueteと同じ
     * @param  string $orderk    Get_Enqueteと同じ
     * @param  string $orderflag Get_Enqueteと同じ
     * @param  int    $muid      Get_Enqueteと同じ
     * @return object Enquete アンケートクラス
     */
    function & fromRid($rid, $orderk = '', $orderflag = '', $muid = '')
    {
        $enq = & new _360_Enquete();
        $enq->enquete = & Get_Enquete('rid', $rid, $orderk, $orderflag, $muid);
        define('ENQ_RID', $rid);
        if (!$enq->enquete)
            return false;
        return $enq;
    }
    //-------------------コンストラクタここまで
    /**
     * ユーザ情報を読み込み、hiddenvalueに混ぜて返す
     */
    public function loadTargetsInfo($querydata)
    {
        $querydata['targets'] = array ();
        if (is_array($querydata['target'])) {
            foreach ($querydata['target'] as $v) {
                $querydata['targets'][$v] = $this->loadTargetRun($v);
            }
        } else {
            $querydata['targets'][$querydata['target']] = $this->loadTargetRun($querydata['target']);
        }
        if (TEST_MODE===1) {
            unset($querydata['targets']['']);
            $querydata['targets']['dummy001'] = array(
                'target'=>'dummy001'
                ,'name'=>'TEST'
                ,'user_type'=>getUserTypeByRid($querydata['rid'])
            );
        }

        return $querydata;
    }

    public function loadTargetRun($targetid)
    {
        global $serial2name;
        if (TEST_MODE === 1) {
            $_SESSION['answer'] = array ();

            return FDB :: select1(T_USER_MST, 'serial_no as target,name', 'where serial_no = ' . FDB :: escape($targetid));
        }
        $ev = $this->getEvent();
        $user_type = getUserTypeByRid($ev['rid']);
        $type = getTypeByEvid($ev['evid']);
        $set = $_SESSION['login'][$type][$user_type];

        $res = array('target' => $targetid);
        if (is_array($_SESSION['login'][$type])) {
            foreach ($_SESSION['login'][$type] as $user_type => $set) {
                /* 回答者以外を飛ばす */
                if($user_type > INPUTER_COUNT) continue;

                foreach ($set as $v) {
                    if ($v['serial_no'] === $targetid) {
                        $res['name'] = $v['name'];
                        $res['name_'] = $v['name_'];
                        $res['user_type'] = $user_type;
                        $serial2name[$v['serial_no']] = "####mypage_ones0####".getUserName($v)."####mypage_ones####";
                    }
                    if(is_good($res['name'])) break(2);
                }
            }
        }

        if (!$res['name']) {
            //(部長版なのに1じゃない) 場合にここにくる
            _360_error(5,1);
            exit;
        }

        return $res;
    }

    public $respondent;
    /**
     * ユーザ情報をセットする
     * @param array $data ユーザ情報の配列
     */
    public function setRespondent($data)
    {
        global $serial2name;
        parent :: setRespondent($data);
        $this->respondent['target'] = $data['target'];

        $this->respondent['targets'] = array ();
        foreach ($data['targets'] as $v) {
            $res = array();
            $res['event_data_id'] = $v['event_data_id'];
            $res['target'] = $v['target'];

            //--以下任意値。ここは自由に追加できるようにしたい ★hiddenValueの種類追加

            $res['name'] = $v['name'];
            $res['name_'] = $v['name_'];
            $res['user_type'] = $v['user_type'];
            $serial2name[$v['target']] = "####mypage_ones0####".getUserName($v)."####mypage_ones####";
            $this->respondent['targets'][$v['target']] = $res;
        }
    }

    /**
     * researchのhiddenで渡すパラメータをセットして返す
     * @param  array $data 他クラスのhidden値。ここにこのクラスのパラメータを追加していく
     * @return array $dataに必要データを足したもの
     */
    public function getHiddenValue($data)
    {
        $data['rid'] = $this->enquete[-1]['rid'];
        //		$data['event_data_id'] = $this->respondent['event_data_id'];

        foreach ($this->respondent['targets'] as $v) {
            $user = array ();
            $user['target'] = $v['target'];
            $user['event_data_id'] = $v['event_data_id'];
            $user['name'] = $v['name'];
            $user['name_'] = $v['name_'];
            $user['user_type'] = $v['user_type'];

            $data['targets'][$v['target']] = $user;

        }

        $data['type'] = $this->respondent['type'];
        $data['flg'] = $this->respondent['flg'];
        $data['cacheid'] = $this->cache['cacheid'];
        $data['bdid'] = $this->cache['bdid'];
        $data['start_page'] = $this->startPage;
        //既にセットされており、ここでセットしないものを読み出す
        foreach ($this->basedata as $k => $v) {
            if (!isset ($data[$k])) {
                $data[$k] = $v;
            }
        }
        //--以下任意値。ここは自由に追加できるようにしたい ★hiddenValueの種類追加
        $data['div1'] = $this->respondent['div1'];
        $data['name'] = $this->respondent['name'];
        $data['name2'] = $this->respondent['name2'];

        return $data;
    }

}

//=============================================================================

/**
 * 360度用アンケートコントローラ
 */
class _360_EnqueteControler extends EnqueteControler
{
    public function setEnqueteFromQuery($query)
    {
        if ($_POST['answer_mode'] === 'lump')
            $this->setEnquete(_360_Enquete :: fromPost($_POST));
        else
            $this->setEnquete(_360_Enquete :: fromQuery($query));
    }

    public function setEnqueteFromBasedata($basedata)
    {
        $this->setEnquete(_360_Enquete :: fromBasedata($basedata));
    }

    /**
     * @return object EnqueteFirstPage ページを最初に開いた時の処理クラスを返す
     *                最初に開いたページとは「その回答者がアンケートシステムで最初に開いたページ」であり
     *                1ページ目のことではない。（途中保存から再開の場合などは途中ページになる）
     */
    function & getFirstPageClass()
    {
        return $this->setupAnswerControler(new EnqueteFirstPage($this->enquete, $this->viewer));
    }

    /**
     * @return object EnqueteRestorationPage アンケート再接続時の処理クラスを返す
     *                主にセッションが切れた時の処理を行う
     */
    function & getRestorationPageClass()
    {
        return $this->setupAnswerControler(new _360_EnqueteRestorationPage($this->enquete, $this->viewer));
    }

    /**
     * @return object EnqueteNextPage アンケート初回ページ以降の処理クラスを返す
     */
    function & getNextPageClass()
    {
        return $this->setupAnswerControler(new _360_EnqueteNextPage($this->enquete, $this->viewer));
    }

    public function setupAnswerControler($cls)
    {
        $cls->answerCtlr = & new _360_EnqueteAnswerControler($this->enquete);

        return $cls;
    }
}

//=============================================================================

//=============================================================================

//NextPage関連の修正はすべてここ
class _360_EnqueteNextPage extends EnqueteNextPage
{
    public function show($page)
    {
        $newpage = parent :: show($page);

        return $newpage;
    }

    //途中保存ボタンが押されたかどうかを判別
    public function isSaveButton()
    {
        return ($this->request['ss'] || $this->request['ss_x'] || $this->request['tsaveprint']);
    }

    /**
     * 回答データを保存する
     * @param  array $data 回答データ
     * @return bool  成功すればtrue
     */
    public function saveEnqueteData($data)
    {
        $answer = $this->createAnswerClass();
        foreach ($answer as $v) {
            $v->saveCompleteInfo();
        }

        return true;
    }

    public function clearTempAnswer()
    {
        //途中保存機能用のファイル削除
        $event = & $this->enquete->getEvent();
        $a = array ();
        $this->setAnswers($a);
    }

    public function setEventList($prm)
    {
        $ev = $this->enquete->getEvent();
        foreach ($this->enquete->respondent['targets'] as $v) {
            $rid = getRidByEvid(adjustEvidByUserType($ev['evid'], $v['user_type']));
            _360_setEventList($this->enquete->respondent['uid'], $v['target'], $rid, $prm);
        }
    }

    /**
     * 途中保存実行時
     */
    public function onSave($page)
    {
        $this->setEventList(1); //eventlist更新
        if ($_POST['timeout']) {
            $this->location(DOMAIN . DIR_MAIN . 'timeout.php?' . getSID());
        }
        $this->location('backup.php?' . getSID());
    }

    public function locateThanks()
    {
        $this->setEventList(2); //eventlist更新
        $this->location(DOMAIN . DIR_MAIN . 'thanks.php?' . getSID());
    }

    public function createAnswerClass()
    {
        return $this->answerCtlr->createAnswerClass();
    }

    public function getTempAnswers($page)
    {
        $temp_answers_res = array ();
        foreach ($this->enquete->respondent['targets'] as $v) {
            $target = $v['target'];
            $questions = $this->viewer->getQuestions($this->enquete->getSubEvents(), $page);
            foreach ($questions as &$q) {
                $q['seid'] = adjustSeidByUserType($q['seid'], $v['user_type']);
                $q['evid'] = adjustEvidByUserType($q['evid'], $v['user_type']);
            }

            $temp_answers = QuestionType :: getValueByPost($questions, $this->request[$target]);
            $ans = & $this->getAnswers();
            foreach ($temp_answers['unsets'] as $v) {
                unset ($ans[$target][$v]);
            }
            foreach ($temp_answers['answers'] as $k => $v) {
                $ans[$target][$k] = $v;
            }
            $temp_answers_res[$target] = $temp_answers;
        }

        return $temp_answers_res;
    }

    public function setNewAnswer($page, $temp_answers_p)
    {
        $answerCls_p = $this->createAnswerClass();
        foreach ($this->enquete->respondent['targets'] as $v) {
            $tid = $v['target'];
            $answerCls = $answerCls_p[$tid];
            $temp_answers = $temp_answers_p[$tid];
            //既存処理
            $event = & $this->enquete->getEvent();
            $user = & $this->enquete->respondent;

            //idを差し戻しておく
            //TODO:この処理汚いので、将来的にはenquete->respondantがanswerを持つなど工夫すること
            if (!$v['event_data_id'])
                $this->enquete->respondent['targets'][$tid]['event_data_id'] = $answerCls->info['event_data_id'];
            if (0 < $this->enquete->startPage) {
                $c->initializeCache();
                $this->enquete->startPage = 0;
            }
            $answerCls->deleteList = $temp_answers['seids'];
            $answer = & $this->setEnqueteAnswer($answerCls, $temp_answers['answers']);

            if (is_false($answer->saveMidst())) {
                throw new Exception('database_error');

                return false;
            }
        }
    }
    //メンバ変数やセッションを更新する
    public function updatePage($page)
    {
        //新たな回答を格納する
        $this->setNewAnswer($page, $this->getTempAnswers($page));

        //★★★戻るボタンを押したとき **************************************************************
        if ($this->isPreviousButton()) {
            return $this->onPrevious($page);
        }
        //★★★途中保存ボタンを押したとき ***********************************************************
        if ($this->isSaveButton()) {
            return $this->onSave($page);
        }
        //エラーチェック
        if (($error = $this->isError($this->getAnswers(), $page))) {
            $this->viewer->setError($error);//エラーがあった場合はエラー内容をアンケート内容にくっつけて返す
        } elseif ($this->isLastPage($page)) {
            return $this->onFinish();
        } elseif (($page = $this->onNext($page))===false) {
            return $this->onFinish();//次のページが条件分岐などでなくなった場合
        }

        return $page;
    }
    public function isError($answers, $page)
    {
        $error = array ();
        $is_plural = (count($answers)>1);
        foreach ($answers as $k => $v) {
            $error = array_merge($error, parent :: isError($v, $page, $k, $is_plural));
        }

        return $error;
    }
}

//=============================================================================

//元の継承関係が悪いため、全上書きにする

/**
 * 360度用
 * セッション切れの際の救済用ページ
 * 途中回答からの復帰を行う
 */
class _360_EnqueteRestorationPage extends _360_EnqueteNextPage
{
    public function show($page)
    {
        $event = & $this->enquete->getEvent();
        //$pageは更新しない
        $this->loadMidstAnswers($event);

        return parent :: show($page);
    }

    public function createAnswerClass()
    {
        return $this->answerCtlr->createAnswerClass();
    }
}

//=============================================================================

class _360_EnqueteAnswerControler extends EnqueteAnswerControler
{
    public function getAnswerState($answerCls)
    {
        return $answer[0]->getAnswerState();
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
        foreach ($answer as $va) {
            switch ($va->getAnswerState()) {
                case 'midst' :
                    $this->loadAnswer($va);
                    $page = 1;
                    break;
                case 'completed' :
                    if (!REOPEN) { //flgo:0=固有URL回答,1=オープンアンケート
                        //固有URLかつ再開機能がオンなら回答済みデータを復帰
                        $page = 1;
                        $this->loadAnswer($va);
                        define('REOPENED_FLAG', 1); //360度用カスタマイズ
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

    function & loadAnswerClass()
    {
        $event = $this->enquete->getEvent();
        $aaa = array ();
        foreach ($this->enquete->respondent['targets'] as $v) {
            $evid = adjustEvidByUserType($event['evid'], $v['user_type']);
            $a = _360_EnqueteAnswer :: load($evid, $this->enquete->respondent['uid'], $v['target']);
            if ($a)
                $aaa[$v['target']] = $a;
        }

        return $aaa;
    }

    function & createAnswerClass()
    {
        //印刷モードの時はデータを更新しない
        //		if(MODE_PRINT)
        //		{
        //			return;
        //		}
        $event = $this->enquete->getEvent();
        $user = $this->enquete->respondent;
        $aaa = array ();
        foreach ($this->enquete->respondent['targets'] as $v) {
            $evid = adjustEvidByUserType($event['evid'], $v['user_type']);
            $a = _360_EnqueteAnswer :: create($v['event_data_id'], $evid, $user['uid'], $user['flg'], $v['target']);
            if ($a)
                $aaa[$v['target']] = $a;
        }

        return $aaa;
    }

    /**
     * 回答データクラスから回答を読み込みアンケート用回答データに変換する
     * @param object EnqueteAnswer $answerCls
     */
    public function loadAnswer($answerCls)
    {
        //TODO:この処理汚いのでrespondentに$answerClsを持たせるなどする
        $this->enquete->respondent['users'][$answerCls->info['target']]['event_data_id'] = $answerCls->info['event_data_id'];

        //TODO:別に$this->answer = QuestionTYpeでいいと思うが

        foreach (QuestionType :: getValueByDB($answerCls->answer, $this->enquete) as $k => $v) {
            $this->answers[$answerCls->info['target']][$k] = $v;
        }
        //TODO:answerClsの側でQuestionType読んでもいいかも
    }

}

//=============================================================================

class _360_EnqueteAnswer extends EnqueteAnswer
{
    public $enquete;

    /**
     * ◆static
     * 値を指定して新しい回答データの作成
     * @param  int    $evdataId このデータのId。指定するとそのIdを上書きする。新規作成の場合はnull
     * @param  int    $evid     このデータが示す回答先eventのID
     * @param  string $uid      このデータの回答者のID。CbaseResearchの場合はserial_no
     * @param  string $flg      予備の汎用フラグ。稀に使用される。
     * @return object EnqueteAnswer 作成したオブジェクト
     */
    function & create($evdataId, $evid, $uid, $flg = '', $target)
    {
        $self = & new _360_EnqueteAnswer();
        $self->setInfo($evdataId, $evid, $uid, $flg, $target);

        return $self;
    }

    //TODO:複数の回答がある場合の対処なし
    //複数の回答がある場合、どの回答を最新とするかという問題あり
    //最新のevent_data_idを取るという形でも問題は無さそう？
    /**
     * ◆static
     * 指定したevid,uidから回答データを読み込んで作成
     * @param  int    $evid このデータが示す回答先eventのID
     * @param  string $uid  このデータの回答者のID。CbaseResearchの場合はserial_no
     * @return object EnqueteAnswer 作成したオブジェクト
     */
    function & load($evid, $uid, $target)
    {
        $ed = & new EventDataDAO();

        $ev = $ed->getByQuerySet($evid, $uid, $target);

        if ($ev) {
            return _360_EnqueteAnswer :: loadFromEventData($ev[0]);
        }

        return null;
    }

    /**
     * ◆static
     * 読み込み済みのevent_data一行分から回答データを読み込んで作成
     * @param  array  $evdata event_data一行分
     * @return object EnqueteAnswer 作成したオブジェクト
     */
    function & loadFromEventData($evdata)
    {
        $self = & new _360_EnqueteAnswer();
        $self->setInfoFromEventData($evdata);
        $sed = & new SubEventDataDAO();
        foreach ($sed->getByEvdataId($evdata['event_data_id']) as $v) {
            $self->addAnswer($v['seid'], $v['choice'], $v['other']);
        }

        return $self;

    }

    /**
     * infoをセットする
     * @param int    $evdataId このデータのId。指定するとそのIdを上書きする。新規作成の場合はnull
     * @param int    $evid     このデータが示す回答先eventのID
     * @param string $uid      このデータの回答者のID。CbaseResearchの場合はserial_no
     * @param string $flg      予備の汎用フラグ。稀に使用される。
     */
    public function setInfo($evdataId, $evid, $uid, $flg = '', $target)
    {
        $this->setInfoFromEventData(array (
            'event_data_id' => $evdataId,
            'evid' => $evid,
            'serial_no' => $uid,
            'flg' => $flg,
            'target' => $target
        ));
    }

    /**
     * event_data一行分から読み込んでinfoをセットする
     * @param  array  $evdata event_data一行分
     * @return object EnqueteAnswer 作成したオブジェクト
     */
    public function setInfoFromEventData($evdata)
    {
        //event_data_idがない場合でも、更新ボタンの可能性を考えて一応event_dataを調べてみる。
        if (!$evdata['event_data_id']) {
            $ed = & new EventDataDAO();
            $evd = $ed->getByQuerySet($evdata['evid'], $evdata['serial_no'], $evdata['target']);
            if ($evd)
                $evdata = $evd[0];
        }

        $this->info = array (
            'event_data_id' => $evdata['event_data_id'],
            'evid' => $evdata['evid'],
            'serial_no' => $evdata['serial_no'],
            'flg' => $evdata['flg'],
            'answer_state' => $evdata['answer_state'],
            'target' => $evdata['target']
        );

        if (!$this->info['event_data_id']) {
            if (is_false($this->saveMidstInfo())) {
                throw new Exception('database_error');

                return false;
            }
        }

        $this->enable = true;

    }

    //TODO:DAOクラスが変更になるときに対応しづらい
    //同様にinfoの情報も変更になったときに対応しづらい

    /**
     * 途中保存データとしてevent_Dataを保存する
     */
    public function saveMidstInfo()
    {
        $ed = & new EventDataDAO();
        if(is_good($this->info['event_data_id']))
            $data = $ed->getByCond('WHERE event_data_id=' . FDB :: escape($this->info['event_data_id']) . ' LIMIT 1');
        //TODO:定数を使うこと
        $this->info['answer_state'] = 10;
        if (is_void($data)) {
            //データが無ければインサート
            $this->info['event_data_id'] = null;
            $result = $ed->insert($this->info);
            if(!is_false($result)) $result = FDB :: getLastInsertedId();

            if(is_false($result))

                return false;

            $this->info['event_data_id'] = $result;
        } else {
            $this->info['event_data_id'] = $data[0]['event_data_id'];
        }

        return $this->info['event_data_id'];
    }

    public $rids;
    public function getRidByEvid($evid)
    {
        if (!$this->rids[$evid]) {
            $res = FDB :: select(T_EVENT, "rid", "WHERE evid=" . FDB :: escape($evid) . ' LIMIT 1');
            $this->rids[$evid] = $res[0]["rid"];
        }

        return $this->rids[$evid];
    }

}

class _360_EnqueteViewer extends EnqueteViewer
{
    /**
     * 表示内容の本文部分のヘッダを取得する
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               html
     */
    public function getHeaderParts($page, $render)
    {
        global $GDF;
        $header = parent :: getHeaderParts($page, $render);
        $time = _360_ANSWER_TIMEOUT;
        if ($GLOBALS['Setting']->autoSaveValid()) {
            $TIME_OUT_ACTION = <<<__JS__
$("#midst_save_button").parent().append('<input type="hidden" name="ss" value="1"><input type="hidden" name="timeout" value="1">');
$("#midst_save_button").click();
__JS__;
        } else {
            $TIME_OUT_ACTION = <<<__JS__
location.href = "./timeout.php?{$GDF->get('SID')}";
__JS__;
        }
        $DIR_IMG = DIR_IMG;
        $date_js = "";
        foreach ($this->enquete->getSubEvents() as $vse) {
            if(is_good($date_js))
                break;

            if ($vse['page']==$page && $vse['type1']=="6" && $vse['type2']=="t") {
                $date_js= <<<__HTML__
<link href="{$DIR_IMG}redmond/jquery-ui-1.10.0.custom.min.css" type="text/css" rel="stylesheet">
<script src="{$DIR_IMG}jquery-ui-1.10.0.min.js" type="text/javascript"></script>
__HTML__;
            }
        }
        //$comment_max_length = ($GLOBALS['Setting']->commentMaxLengthNotEmpty())? COMMENT_MAX_LENGTH : 0;
        $header .=<<<__HTML__
{$date_js}
<script type="text/javascript" language="javascript" src="{$DIR_IMG}timeoutcheck.js" charset="EUC-JP"></script>
<script type="text/javascript" language="javascript">
<!--

//var comment_max_length = {$comment_max_length};
var blurtime_length = 0;
var alerted = true;

window.onblur = (function() {
    alerted = false;
});

function checkMainComment(obj)
{
    if(document.getElementById('comment_length_'+obj.name)) {
        var txt = obj.value;
        //var c_maxlength = $(obj).attr("c_maxlength") || {$comment_max_length};
        var c_maxlength = $(obj).prev().attr('data_length');
        var count = countLength(txt);
        // ウィンドウのblur時の文字列を保存して比較
        if (blurtime_length != count) {
            alerted = true;
        }
        blurtime_length = count;
        if (c_maxlength > 0 && count > c_maxlength) {
            count = '<font color="red">'+count+'</font>';
        }
        document.getElementById('comment_length_'+obj.name).innerHTML = count;
    }

    if(document.getElementById('comment_lines_'+obj.name)) {
        document.getElementById('comment_lines_'+obj.name).innerHTML = countCommentLine(obj.value);
        var rows = document.getElementById('comment_lines_'+obj.name).getAttribute('data_rows');
        rows = parseInt(rows, 10);
        var line = countCommentLine(obj.value)
        if(line > rows)
            line = '<font color="red">'+line+'</font>';
        document.getElementById('comment_lines_'+obj.name).innerHTML = line;
    }
}

function countCommentLine(txt)
{
    var str_length = 66;
    var datas = txt.split("\\n");
    var line = datas.length;
    for(var i = 0; i<datas.length; i++){
        var bytes = getByte(datas[i]);
        line += parseInt((bytes-1)/(str_length*2));
    }
    return line;
}

function checkMainComment_Onblur(obj)
{
    var alertComment = [];
    if(document.getElementById('comment_length_'+obj.name)) {
        var txt = obj.value;
        var count = count_val = countLength(txt);
        //var c_maxlength = $(obj).attr("c_maxlength") || {$comment_max_length};
        var c_maxlength = $(obj).prev().attr('data_length');
        if (c_maxlength > 0 && count > c_maxlength) {
            count = '<font color="red">'+count+'</font>';
        }

        document.getElementById('comment_length_'+obj.name).innerHTML = count;
        if (c_maxlength > 0 && count_val > c_maxlength)
            alertComment.push("####enq_errror_message_count####");
    }

    if(document.getElementById('comment_lines_'+obj.name)) {
        var rows = document.getElementById('comment_lines_'+obj.name).getAttribute('data_rows');
        rows = parseInt(rows, 10);
        var line = countCommentLine(obj.value)
        if(document.getElementById('comment_lines_'+obj.name) && line > rows)
        {
            line = '<font color="red">'+line+'</font>';
            alertComment.push("####enq_error_line_count####");
        }
        document.getElementById('comment_lines_'+obj.name).innerHTML = line;
    }
    if (txt.match(/#{4,}/))
        alertComment.push("####message_error_Illegal_character####");

	// 文字制限オーバーしていると、画面上の全てのボタンが無効になる。
	var btns = document.	getElementsByClassName("btn");
	for( var i=0,l=btns.length; l>i; i++ ) {
		var btn = btns[i] ;
		btn.disabled = "";
	}
    if (alertComment.length > 0) {
        if (alerted) {
            alert(alertComment.join("\\n").replace("XXX", c_maxlength));
        }
        setTimeout(function(){obj.focus();},1)
		for( var i=0,l=btns.length; l>i; i++ ) {
			var btn = btns[i] ;
			btn.disabled = "true";
		}
        return;
    }
}

function getByte(text)
{
    count = 0;
    for (i=0; i<text.length; i++) {
        n = escape(text.charAt(i));
        if (n.length < 4) count++; else count+=2;
    }

    return count;
}

function countLength(txt)
{
    return txt.length;
}

function allReplace(text, sText, rText)
{
    while (true) {
        dummy = text;
        text = dummy.replace(sText, rText);
        if (text == dummy) {
            break;
        }
    }

    return text;
}

$(function () {
    timeout = new TimeoutChecker();
    timeout.onTimeout = function () {
        {$TIME_OUT_ACTION}
    }
    timeout.run({$time});

    function TextAreaCheck()
    {
        if ($(".comment_length").size() > 0) {
            $(".comment_length").each(function () {
                checkMainComment($("[name='" + $(this).attr("target_name") + "']")[0]);
            });
        }
    }
    TextAreaCheck();

}
);
// -->
</script>
__HTML__;

        return $header;
    }

    //	/**
    //	 * @param int $page 表示するページ
    //	 * @param object EnqueteRender $render レンダークラス
    //	 * @return string 次へ進むボタンまたは送信ボタンを表示するhtmlを返す
    //	 */
    //	function getNextButton ($page, $render)
    //	{
    //		$event = $this->enquete->getEvent();
    //		return ($page == $event["lastpage"])?
    //			//ラストページの時
    //			$render->getSubmitButton($this->enquete):
    //			$render->getNextButton($event);
    //	}
}

//=============================================================================

class _360_EnqueteRender extends EnqueteRender
{

    public function getCompleteHtml($event, $body)
    {
        $html = $body;
        //セッション寿命自動延長用javascriptを追加
        if (USE_SESSION_LIFE_TIME_RESET == 1) {
            $html .= getHtmlSessionLifeTimeReset(); //ver1.1/
        }
        //処理結果を携帯用に変換
        if ($this->isMobile) {
            $html = getMobileHtml($html);
        }

        return $html;
    }
    /**
     * override用。EnqueteFormBuilderを作成して返す
     * （キャッシュ処理などはこの関数の呼び出し側で行う事）
     * @param  array  $enquete  フォームを作成するアンケート
     * @param  array  $subevent フォームを作成する質問。$enqueteの一部であり冗長
     * @param  array  $answers  回答一覧。$subeventのseidからデフォルト値を取得したりする
     * @return object EnqueteFormBuilder
     */
    function & getFormBuilder(& $enquete, $subevent, & $answers)
    {
        return new _360_EnqueteFormBuilder($enquete, $subevent, $answers);
    }

    /**
     * 送信ボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @param  array  $event 表示対象のイベント（htmlsを持つため）
     * @return string html
     */
    public function getSaveButton($page)
    {
        global $GDF;

        return<<<HTML
<div class="button_div save">
<input class="btn" type="submit" name="ss" id="midst_save_button" value="####enq_button_ss####" onclick="{$GDF->get('BUTTON_SS_ONCLICK')}">
<span id="add_value"></span>
</div>
HTML;
    }

    public function getSubmitButton($page)
    {
        global $GDF;

        $html =<<<HTML
<div class="button_div submit">
HTML;
        if ($page != 1)
            $html .=<<<HTML
<input class="btn" type="submit" name="pb" value="####enq_button_pb####" onclick="{$GDF->get('BUTTON_PB_ONCLICK')}">
HTML;
        $html .=<<<HTML
<input class="btn" type="submit" name="main" value="####enq_button_main####" onclick="if (confirm('####enq_main_confirm####')) {{$GDF->get('BUTTON_MAIN2_ONCLICK')} return true;}return false;">
</div>
HTML;
        return $html;
    }
    public function getNextButton($event,$page)
    {
        global $GDF;
        if($page==1)

            return<<<HTML
<div class="button_div next">
<input class="btn" type="submit" name="main" value="####enq_button_next####" onclick="{$GDF->get('BUTTON_MAIN_ONCLICK')}">
</div>
HTML;
        else
            return<<<HTML
<div class="button_div next">
<input class="btn" type="submit" name="pb" value="####enq_button_pb####" onclick="{$GDF->get('BUTTON_PB_ONCLICK')}">
<input class="btn" type="submit" name="main" value="####enq_button_next####" onclick="{$GDF->get('BUTTON_MAIN_ONCLICK')}">
</div>
HTML;
    }
}

//=============================================================================

/**
 * 複数同時評価に対応
 */
class _360_EnqueteFormBuilder extends EnqueteFormBuilder
{
    public function __destruct()
    {
        define('TARGET_NAME',$this->makeTargetName());
    }
    /**
    * BuildFormから呼ばれて、マッチした文字列に応じて適切なものに置き換える
    * @param string $match マッチした文字列
    * @return string 置き換え後の文字列(html)
    */
    public function replaceParts($match)
    {
        //pregのマッチ部分の取り出し
        $fn = $match[1];
        switch ($fn) {
            case 'category1':
                return html_escape($this->subevent['category1']);
            case 'category2':
                return html_escape($this->subevent['category2']);
            case 'num_ext':
                return html_escape($this->subevent['num_ext']);
            case 'targets' :
                return $this->makeTargetsName();
            case 'targetsdiv' :
                return $this->makeTargetsDiv();
            case 'targetname' :
                return $this->makeTargetName();
            case 'targetdiv' :
                return $this->makeTargetDiv();
            case 'targetclass':
                return $this->makeTargetClass();
            case 'message2' :
                return $this->makeMessage2Form();
            case 'message' :
                return $this->makeMessageForm();
            case 'messagediv' :
                return $this->makeMessageForm("div");
            default :
                if (preg_match('/^MESSAGEID_DIV[0-9]+$/i', $fn)) {
                    return $this->makeMessageFormAnswer(preg_replace('/MESSAGEID_DIV/i', '', $fn), "div");
                } elseif (preg_match('/^MESSAGEID[0-9]+$/i', $fn)) {
                    return $this->makeMessageFormAnswer(preg_replace('/MESSAGEID/i', '', $fn));
                } elseif (preg_match('/^TITLE:ID[0-9]+$/i', $fn)) {
                    $target = $this->enquete->getBySeid(preg_replace('/TITLE:ID/i', '', $fn));

                    return html_escape($target['title']);
                } elseif (preg_match('/^CATEGORY1:ID[0-9]+$/i', $fn)) {
                    $target = $this->enquete->getBySeid(preg_replace('/CATEGORY1:ID/i', '', $fn));

                    return html_escape($target['category1']);
                } elseif (preg_match('/^CATEGORY2:ID[0-9]+$/i', $fn)) {
                    $target = $this->enquete->getBySeid(preg_replace('/CATEGORY2:ID/i', '', $fn));

                    return html_escape($target['category2']);
                } elseif (preg_match('/^NUM_EXT:ID[0-9]+$/i', $fn)) {
                    $target = $this->enquete->getBySeid(preg_replace('/NUM_EXT:ID/i', '', $fn));

                    return html_escape($target['num_ext']);
                } elseif (preg_match('/^TARGET:.+$/i', $fn)) {
                    return $this->makeTargetsAttr(preg_replace('/TARGET:/i', '', $fn));
                } elseif (preg_match('/^TARGETRELATION:.+$/i', $fn)) {
                    return $this->makeTargetRelation(preg_replace('/TARGETRELATION:/i', '', $fn));
                }

                break;
        }

        return parent :: replaceParts($match);
    }


    //orverride
    public function makeForm($now, $choices)
    {
        $html = '';
        foreach ($this->enquete->respondent['targets'] as $v) {
            $subevent = $this->subevent;

            $subevent['seid'] = adjustSeidByUserType($subevent['seid'], $v['user_type']);
            $subevent['evid'] = adjustEvidByUserType($subevent['evid'], $v['user_type']);

            $form = QuestionType :: getForm($subevent, $now, $choices);

            $seid = $subevent['seid'];

            $id = " id=\"error{$seid}\"";

            $res = $form->get($this->answers[$v['target']]);
            $res = str_replace('checked','', $res);

            $res = str_replace('value="0"','value="'.$now.'"', $res);
            $res = preg_replace('/name="(.*?)"/', 'name="' . $v['target'] . '[\1]"', $res);
            $res = str_replace('[]]"','][]"', $res);
            if(is_array($this->answers[$v['target']]['P_'.$seid])
                && in_array((string) $now,$this->answers[$v['target']]['P_'.$seid]))
            {
                $res = str_replace('>',' checked>', $res);
            }
            if($GLOBALS['Setting']->multiAnswerModeValid())
                $html .= '<td class="matrix_col_width_form" '.$id.'>' . $res . '</td>' . "\n";
            else
                $html .= $res . "\n";
        }

        return $html;
    }

    /**
     * 回答表示を作成する
     * @param  int    $id 表示する回答のseid
     * @return string 表示する文字列
     */
    public function makeAnswer($id)
    {
        $html = "";
        foreach ($this->enquete->respondent['targets'] as $v) {
            if ($GLOBALS['Setting']->multiAnswerModeValid()) {
                $seid = explode(":", $id);
                $seid[0] = adjustSeidByUserType($seid[0], $v['user_type']);
                $seid = implode(":", $seid);
                $html .= '<td class="matrix_col_width_form">' . $this->makeAnswer_child ($v['target'], $seid) . '</td>' . "\n";
            } else {
                $html .= $this->makeAnswer_child ($v['target'], $id) . "\n";
            }
        }

        return $html;
    }
    /**
     * 回答表示を作成する
     * @param  int    $id 表示する回答のseid
     * @return string 表示する文字列
     */
    public function makeAnswer2($id)
    {
        $key = $id;
        ereg('([0-9]+)_([0-9]+)',$id,$match);
        $key = $match[1];
        $num = $match[2];

        $t = array_keys($this->enquete->respondent['targets']);
        //pulldown回答を返す
        //radio,checkbox回答を返す
        $strPChoice = $this->answers[$t[0]]["P_".$key][0];

        if($strPChoice == $num)

            return "〇";
        else
         return "";
    }

    //parent::makeAnswerが、this->answersを引数で受け取るようにしたほうがいい
    public function makeAnswer_child($target, $id, $answers = null, $raw = false)
    {
        $answers = (is_null($answers)) ? $this->answers[$target] : $answers;
        $match = explode(':', $id);
        $key = $match[0];
        $num = $match[1];

        //TODO:正確を期すならsubevent見たほうがいいかもしれません
        //テキスト回答を返す
        if (!is_null($answers["T_".$key])) {
            return nl2br(transHtmlentities($answers["T_".$key]));
        }
        //pulldown回答を返す
        //radio,checkbox回答を返す
        $strPChoice = $answers["P_".$key];
        if (!$strPChoice && $strPChoice != "0") return;

        //選択肢データを取得
        $target = $this->enquete->getBySeid($key);
        $tchoice = getEnqueteChoice($target, $this->enquete->respondent);

        //選択肢単独のデータを返す
        if (is_false($raw) && is_good($num) && is_array($strPChoice)) {
            if(in_array($num, $strPChoice))

                return "◯";
            else
                return "";
        }

        if (is_false($raw) && $target['type2'] == 'r') {
            $count = (int) $this->makeAnswerCount[$key];
            $this->makeAnswerCount[$key]++;
            if($strPChoice[0] == $count)

                return "〇";
            else
                return "";
        }

        //回答選択肢を展開
        $tval=array();
        if (!is_array($strPChoice)) {
            $tval[] = $tchoice[$strPChoice];
        } else {
            foreach ($strPChoice as $ans) {
                $tval[] = $tchoice[$ans];
            }
        }

        return transHtmlentities(implode(",", $tval));
    }

    public function makeTargetsAttr($attr)
    {
        foreach ($this->enquete->respondent['targets'] as $v) {
            $user = getUserBySerial($v['target']);

            return get360Value($user, $attr);
        }
    }

    public function makeTargetsName()
    {
        $html = '';

        if ($GLOBALS['Setting']->headerModePulldown()) {
            foreach ($this->enquete->respondent['targets'] as $v) {
                $user = getUserBySerial($v['target']);
                if(is_good($user['name_']) && $GLOBALS['Setting']->nameModeIs1())
                    $name = html_escape($user['name']).'<br>( '.html_escape($user['name_']).' )';
                else {
                    $lang_type = (int) $_SESSION['login']['lang_type'];
                    if($lang_type != 0)
                        $name = html_escape($user['name_']);
                    else
                        $name = html_escape($user['name']);
                }
                //$name = stringToVirtical($v['name']);
                $html .= '<td class="matrix_col_width_form targetname">' . $name .'</td>' . "\n";
            }
        } else {
            foreach ($this->enquete->respondent['targets'] as $v) {
                $i = 0;
                foreach (explode(',',$this->enquete->enquete[0][3]['choice']) as $choice) {
                    if($i==5)
                        $html .= '<td class="line" style="width:0px;padding:0px;background-color:black"></td>' . "\n";
                    $html .= '<td class="matrix_col_width_form">' . $choice . '</td>' . "\n";
                    $i++;
                }
            }

        }

        return $html;
    }

    public function makeTargetsDiv()
    {
        $html = '';

        if ($GLOBALS['Setting']->headerModePulldown()) {
            foreach ($this->enquete->respondent['targets'] as $v) {
                $user = getUserBySerial($v['target']);
                $div = getUserDiv($user);
                $html .= '<td class="matrix_col_width_form targetname">' . $div .'</td>' . "\n";
            }
        }
/*
        else {
            foreach ($this->enquete->respondent['targets'] as $v) {
                $i = 0;
                foreach (explode(',',$this->enquete->enquete[0][3]['choice']) as $choice) {
                    if($i==5)
                        $html .= '<td class="line" style="width:0px;padding:0px;background-color:black"></td>' . "\n";
                    $html .= '<td class="matrix_col_width_form">' . $choice . '</td>' . "\n";
                    $i++;
                }
            }
        }
*/

        return $html;
    }
    public function makeTargetName()
    {
        $html = '';
        foreach ($this->enquete->respondent['targets'] as $v) {
            $user = getUserBySerial($v['target']);
            if($user['name_'] && $GLOBALS['Setting']->nameModeIs1())

                return $user['name'].' ( '.$user['name_'].' ) ';
            else {
                $lang_type = (int) $_SESSION['login']['lang_type'];
                if($lang_type != 0)

                    return $user['name_'];
                else
                    return $user['name'];
            }
        }

        return $html;
    }

    public function makeTargetDiv()
    {
        $html = '';
        foreach ($this->enquete->respondent['targets'] as $v) {
            $user = getUserBySerial($v['target']);

            return getUserDiv($user);
        }

        return $html;
    }

    public function makeTargetClass()
    {
        $html = '';
        foreach ($this->enquete->respondent['targets'] as $v) {
            $user = getUserBySerial($v['target']);

            return (is_good($user['class']))? $user['class']:"";
        }

        return $html;
    }

    public function makeTargetRelation($type)
    {
        static $makeTargetRelation;

        switch ($type) {
            case 'admit':
                $type = ADMIT_USER_TYPE;
                break;
            case 'viewer':
                $type = VIEWER_USER_TYPE;
                break;
        }

        if(is_zero($type) || !array_key_exists($type, $GLOBALS['_360_user_type'])) return "";

        if(is_good($makeTargetRelation[$type])) return $makeTargetRelation[$type];

        foreach ($this->enquete->respondent['targets'] as $v) {
            $res = array();
            $user = getUserBySerial($v['target']);
            $table = T_USER_MST." a LEFT JOIN ".T_USER_RELATION." b ON a.uid=b.uid_b";
            $where = "WHERE b.uid_a = ".FDB::escape($user['uid'])." AND user_type = ".FDB::escape($type);
            foreach(FDB::select($table, "a.name", $where) as $relation)
                $res[] = $relation['name'];

            $makeTargetRelation[$type] = (is_good($res))? implode("<br>", $res):"";

            return $makeTargetRelation[$type];
        }

        return "";
    }

    /* 通常の記述回答
     * 文字数カウントの基本設定によってカウント数表示
     */
    public function makeMessageForm($display="name")
    {
        global $GDF;
        $html = '';
        foreach ($this->enquete->respondent['targets'] as $v) {
            $subevent = $this->subevent;

            $subevent['seid'] = adjustSeidByUserType($subevent['seid'], $v['user_type']);
            $subevent['evid'] = adjustEvidByUserType($subevent['evid'], $v['user_type']);

            $form = QuestionType :: getForm($subevent, null, null);

            $res = $form->get($this->answers[$v['target']]);
            $res = preg_replace('/name="(.*?)"/', 'name="' . $v['target'] . '[\1]"', $res);
            preg_match('/name="(.*?)"/', $res, $match);
            $name = $match[1];
            $user = getUserBySerial($v['target']);
            $uname = getUserName($user);

            if($display=="div")
                $uname = getUserDiv($user);

            $uname = ($GLOBALS['Setting']->multiAnswerModeValid())? '<div class="message_name">'.$uname.'####enq_message1####</div>':"";

            $maxRows = html_escape($subevent['rows']);

            $count = null;
            if ($GLOBALS['Setting']->commentCountValid()) {
                $length = $this->getCommentLength($subevent);
                if ($length > 0) {
                    $data_length = 'data_length="'.$length.'"';
                    $count = <<<__HTML__
(####enq_message3####: <span id='comment_length_{$name}'></span>/{$length}####enq_message7####)
__HTML__;
                }
            }
            if ($maxRows > 0) {
                $count .= <<<__HTML__
(####enq_message8####: <span id='comment_lines_{$name}' data_rows="{$maxRows}"></span>/{$maxRows}####enq_message9####)####enq_message_count_comment####
__HTML__;
            }
            if (is_good($count)) {
                $count = '<div class="comment_length"'.$data_length.' target_name="'.$name.'">' . $count . '</div>';
            }
            $html .=<<<HTML
{$uname}
{$count}
{$res}
HTML;
        }

        return $html;
    }

    /* 文字数カウント記述回答
     * 文字数カウントの基本設定に関わらずカウント数表示
     */
    // TODO:この関数は、運用Gのヒアリングにより使用していないことが判明。後日削除予定。
    public function makeMessage2Form()
    {
        global $GDF;
        $html = '';
        foreach ($this->enquete->respondent['targets'] as $v) {
            $subevent = $this->subevent;

            $subevent['seid'] = adjustSeidByUserType($subevent['seid'], $v['user_type']);
            $subevent['evid'] = adjustEvidByUserType($subevent['evid'], $v['user_type']);

            $form = QuestionType :: getForm($subevent, null, null);

            $res = $form->get($this->answers[$v['target']]);
            $res = preg_replace('/name="(.*?)"/', 'name="' . $v['target'] . '[\1]"', $res);
            preg_match('/name="(.*?)"/', $res, $match);
            $name = $match[1];
            $user = getUserBySerial($v['target']);
            $uname = getUserName($user);

            $uname = ($GLOBALS['Setting']->multiAnswerModeValid())? '<div class="message_name">'.$uname.'####enq_message1####</div>':"";

            $length = $this->getCommentLength($subevent);
            $html .=<<<HTML
{$uname}
<div class="comment_length" data_length="{$length}" target_name="{$name}">
(####enq_message3####: <span id='comment_length_{$name}'></span>/{$length}####enq_message7####)
(####enq_message8####: <span id='comment_lines_{$name}'></span>/6####enq_message9####)
</div>
{$res}
HTML;
        }

        return $html;
    }

    public function makeMessageFormAnswer($id, $display="name")
    {
        $html = '';
        foreach ($this->enquete->respondent['targets'] as $v) {
            $seid = adjustSeidByUserType($id, $v['user_type']);

            $form = QuestionType :: getForm($this->enquete->getBySeid($seid), null, null);

            $answer = $this->makeAnswer_child ($v['target'], $seid);
            $answer = strip_tags($answer);
            $user = getUserBySerial($v['target']);
            $uname = getUserName($user);

            if($display == "div")
                $uname = getUserDiv($user);

            $uname = ($GLOBALS['Setting']->multiAnswerModeValid())? '<div class="message_name">'.$uname.'####enq_message1####</div>':"";

            $html .=<<<HTML
{$uname}
<textarea class="comment" readonly>
{$answer}
</textarea>
HTML;
        }

        return $html;
    }

    /**
     * コメント文字数を取得。
     */
    public function getCommentLength($subevent)
    {
        $length = 0;
        if (is_good($subevent['word_limit']) && !is_zero($subevent['word_limit'])) {
            // 設問設定で設定された値
            $length = $subevent['word_limit'];
        } else if ($GLOBALS['Setting']->commentMaxLengthNotEmpty()) {
            // 基本設定で設定された値
            $length = COMMENT_MAX_LENGTH;
        }
        return $length;
    }

}
//=============================================================================
