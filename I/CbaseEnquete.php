<?php
// ★hiddenValueの種類追加　で検索すると、hiddenValue(持ちまわる値)を増やす箇所がすぐわかる。
/**
 * 更新履歴:
 * 2008/03/29 basedata(hiddenValue)にて　usr.name と usr.name1 も持ちまわるように変更
 */
include_once 'CbaseEnqueteConditions.php';

/**
 * アンケート構造体
 */
class Enquete
{
    public $enquete;
    //seidからインデックス検索するリファレンス（今は値コピー）
    public $seidContents = null;

    //static
    /**
     * ◆static
     * Get_Enqueteで読み込み後の配列から作成（下位互換用、将来的に廃止）
     * 将来的には、fromEvidを行った時点でDBに読みにいくような設計にしたい
     */
    function &fromArray(&$array)
    {
if (SHOW_ABOLITION) {
    echo 'Enquete::fromArrayは廃止予定関数です。<hr>';
}
        $enq =& new Enquete();
        $enq->enquete =& $array;

        return $enq;
    }

    /**
     * ◆static
     * クエリから読み込み作成
     * @param  int    $query 有効なクエリ
     * @return object Enquete アンケートクラス
     */
    function &fromQuery ($query)
    {
        //TODO:Resolve_QueryStringはEnqueteクラスの仕事にしてもよい
        $data = Resolve_QueryString($query); //queryStringから　type,rid,uid,flgのを取得し、$_SESSIONに登録
        if ($data === false) return false;

        $enq =& Enquete::fromRid($data['rid']);

        if ($enq) {
            //Queryの時のみ読み込むのは、以後はbasedataで持ちまわるため
            //flgoのあまりが0のときユーザを読む
            $ev = $enq->getEvent();
            if (!$enq->isOpenEnquete()) {
                $data = $enq->loadUserInfo ($data);
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
    function &fromBasedata ($hiddenValue)
    {
        $enq =& Enquete::fromRid($hiddenValue['rid']);
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
    function &fromRid($rid, $orderk='', $orderflag='', $muid='')
    {
        $enq =& new Enquete();
        $enq->enquete =& Get_Enquete('rid', $rid, $orderk, $orderflag, $muid);
        if(!$enq->enquete) return false;

        return $enq;
    }

    //===================================================================================
    //
    //  アンケート回答者データ入出力
    //

    public $user_exists = null;
    public function isUserExists()
    {
        if ($this->user_exists === null) {
            echo 'CbaseEnquete::isUserExistsは、loadUserInfoを使うページでのみ使用可能です';
            exit;
        }

        return $this->user_exists;
    }

    /**
     * @return array ユーザ情報からbasedataに読み込んでおくカラムを配列で設定する
     */
    public function getUserDataColumns()
    {
        return array(
            'div1',
            'name1',
            'name2',
            'email'
        );
    }

    /**
     * ユーザ情報を読み込み、hiddenvalueに混ぜて返す
     */
    public function loadUserInfo($hiddenValue)
    {
        if ($this->isNeedAuth () && $_SESSION['auth_user']) {
            $user = $_SESSION['auth_user'];
            $this->user_exists = ($user);
        } else {
            $user = Get_UserData("sp", "serial_no", $hiddenValue['uid']);
            $this->user_exists = (0 < count($user));
            $user = $user[0];
        }
        //--以下任意値。ここは自由に追加できるようにしたい ★hiddenValueの種類追加
        foreach ($this->getUserDataColumns () as $v) {
            $hiddenValue[$v] = $user[$v];
        }

        return $hiddenValue;
    }

    public $respondent;
    /**
     * ユーザ情報をセットする
     * @param array $data ユーザ情報の配列
     */
    public function setRespondent($data)
    {
        //ここでユーザ情報など読み込める
        $this->respondent['event_data_id'] =  $data['event_data_id'];
        $this->respondent['uid'] = $data['uid'];
        //このtypeは多分いらない
        $this->respondent['type'] = $data['type'];
        //flgは拡張用だっけ？
        $this->respondent['flg'] = $data['flg'];

        //--以下任意値。ここは自由に追加できるようにしたい ★hiddenValueの種類追加
        foreach ($this->getUserDataColumns () as $v) {
            $this->respondent[$v] = $data[$v];
        }
    }

    public $cache;
    /**
     * 回答途中保存の情報をセットする
     * @param array $cache キャッシュ情報の配列
     */
    public function setCache($cache)
    {
        //TODO:backup_dataは厳密にはキャッシュとは関係ないため、名称変更を提案
        //TODO:配列ではなく、途中保存回答クラスがあるのでそれを持たせてもよさそう
        $this->cache['cacheid'] = $cache['cacheid'];
        $this->cache['bdid'] = $cache['bdid'];
    }

    public $basedata = array();
    public $startPage = 0;
    /**
     * hiddenから送られてきた情報を内部にセットする
     * @param array $data 基本的にはgetHiddenValueで送ったデータの配列
     */
    public function setHiddenValue($data)
    {
        $this->setRespondent($data);
        $this->setCache($data);
        $this->setBasedata($data);
        $this->startPage = $data['start_page'];

        //内部値セットの上でアンケートの前処理を行う
        $this->refleshEnquete();
    }

    /**
     * basedataをセットする。$dataと$basedataに同じ値が合った場合は後者が優先される
     * （そのような場合はあまり無いと思われるため判定を省く）
     * @param array $data     基本的にはgetHiddenValueで送ったデータの配列
     * @param array $basedata ほかに追加でbasedataとして与える値
     */
    public function setBasedata ($data, $basedata=array())
    {
        //TODO:↑で登録したデータは省くようにするといいかもしれない
        $this->basedata = $data;
        if ($basedata) {
            $this->addBasedata($data);
        }
    }

    /**
     * basedataを追加でセットする。
     * @param array $data 追加でbasedataとして与える値
     */
    public function addBasedata($data)
    {
        foreach ($data as $k => $v) {
            $this->basedata[$k] = $v;
        }
    }

    /**
     * researchのhiddenで渡すパラメータをセットして返す
     * @param  array $data 他クラスのhidden値。ここにこのクラスのパラメータを追加していく
     * @return array $dataに必要データを足したもの
     */
    public function getHiddenValue($data)
    {
        global $aaaa;
        $data['rid'] = $this->enquete[-1]['rid'];
        $data['event_data_id'] = $this->respondent['event_data_id'];
        $data['uid'] = $this->respondent['uid'];
        $data['type'] = $this->respondent['type'];
        $data['flg'] = $this->respondent['flg'];
        $data['cacheid'] = $this->cache['cacheid'];
        $data['bdid'] = $this->cache['bdid'];
        $data['start_page'] =  $this->startPage;
        //既にセットされており、ここでセットしないものを読み出す
        foreach ($this->basedata as $k=>$v) {
            if (!isset($data[$k])) {
                $data[$k] = $v;
            }
        }
        //--以下任意値。ここは自由に追加できるようにしたい ★hiddenValueの種類追加
        foreach ($this->getUserDataColumns () as $v) {
            $data[$v] = $this->respondent[$v];
        }

        return $data;
    }

    /**
     * アンケートに対して、ロード後に何らかの処理を行う場合はここに記述する
     */
    public function refleshEnquete()
    {
        //キャッシュIDを持っている場合、アンケートをロードしなおす
        if ($this->hasCache()) {
           $cache =& AnswerCache::createByEnquete($this);
           $this->enquete = $cache->getLatestCache();
        }

    }

    //===================================================================================
    //
    //  アンケートデータの取得
    //

    /**
     * 下位互換用。
     * 既存のGet_Enqueteによって得られる配列と同等のものを返す
     * このクラス以外でthis->enquete[-1]のような取り方をしている場合は、この関数を使ってください。
     * それにより、データ保持の互換性が保証されます
     * @return array -1=>event 0=>array(subevent)の配列
     */
    public function toArray()
    {
        return array(
            -1 => $this->getEvent(),
            0 => $this->getSubEvents()
        );
    }

    /**
     * event部分を取得するプロパティ
     * @return array 1行分のevent
     */
    function &getEvent ($col=null)
    {

        if(!is_null($col))

            return $this->enquete[-1][$col];
        return $this->enquete[-1];
    }

    /**
     * subevent部分を取得するプロパティ
     * @return array subeventの配列
     */
    function &getSubEvents ()
    {
        return $this->enquete[0];
    }

    /**
     * seidからsubeventの一行分を取得する
     * @param  int   $seid
     * @return array 一行分のsubevent
     */
    function &getBySeid ($seid, $reload = false)
    {
        global $Setting;
        if (is_null($this->seidContents[$seid])) {
            $temp = array();
            //ほんとうは &$vとして、=&$vとしたい

            if ($reload == true) {
                if ($Setting->sheetModeCollect() && $seid%100 > 1) {
                    $a_seid = adjustSeidByUserType($seid, 1);
                    $result = Get_Enquete('id', floor($a_seid/1000), false, false);
                } else {
                    $result = Get_Enquete('id', floor($seid/1000), false, false);
                }
                $subevent = $result[0];
            } else {
                $subevent = $this->getSubEvents();
            }
            foreach ($subevent as $v) {
                $this->seidContents[$v['seid']] = $v;
                if($Setting->sheetModeCollect() && $v['evid']%100 == 1)
                    foreach (range(1, INPUTER_COUNT) as $user_type) {
                        if($user_type==1) continue;
                        $_temp = $v;
                        $_temp['seid'] = adjustSeidByUserType($v['seid'], $user_type);
                        $_temp['evid'] = adjustEvidByUserType($v['evid'], $user_type);
                        $this->seidContents[$_temp['seid']] = $_temp;
                    }
            }
        }

        return $this->seidContents[$seid];
    }

    /**
     * 回答途中保存データを持っているとき、
     * そのデータのcacheidからアンケートのキャッシュを取得する
     */
    public function loadCache()
    {
        //TODO:loadCacheのCacheはアンケートキャッシュで、hasCacheは$this->cacheなので混乱の可能性あり。改名を推奨。
        $this->refleshEnquete();
    }

    /**
     * 回答途中保存データを持っているかどうかの判断
     * @return bool 持っていればtrue
     */
    public function hasCache()
    {
        return !is_null($this->cache['cacheid']);
    }

    /**
     * @return bool このアンケートはオープンアンケートであればtrue
     */
    public function isOpenEnquete()
    {
        $ev = $this->getEvent();
        //オープンアンケートの定義は2で割ったあまりが1であること
        return (isOpenEnqueteByFlgo($ev['flgo']));
    }

    /**
     * @return bool このアンケートは認証が必要ならtrue。認証画面への分岐などに使用。
     */
    public function isNeedAuth()
    {
        $ev = $this->getEvent();
        //flgoが2以上なら認証が必要
        return (isNeedAuthByFlgo($ev['flgo']));

    }

    /**
     * @return bool このアンケートは途中保存が可能であればtrue
     */
    public function isSavable()
    {
        $ev = $this->getEvent();

        return 0 < $ev['flgs'] && (ENQ_OPEN_RESTORE || !$this->isOpenEnquete());
    }

    //===================================================================================
    //
    //  アンケートデータクラスの取得
    //

//	var $questions;
    /**
     * このアンケートに含まれる質問データをクラスにして取得する
     * @param  int    $seid 取り出す質問データのseid
     * @return object Question 取り出された質問データ
     */
    function &getQuestion ($seid)
    {
//		if(!$this->questions[$seid])
//		{
//			$this->questions[$seid] =& new Question($this->getBySeid($seid));
//		}
//		return $this->questions[$seid];
        return getQuestion($this->getBySeid($seid));

    }

}

    /**
     * このアンケートに含まれる質問データをクラスにして取得する
     * @param  int    $seid 取り出す質問データのseid
     * @return object Question 取り出された質問データ
     */
function &getQuestion ($subevent)
{
    global $global_questions_list;
    $seid = $subevent['seid'];
    if (!$global_questions_list[$seid]) {
        $global_questions_list[$seid] =& new Question($subevent);
    }

    return $global_questions_list[$seid];
}

/**
 * subeventから作成される質問管理クラス
 */
class Question
{
    /**
     * @param array $subevent この質問の内容を示す配列
     */
    public function Question(&$subevent)
    {
        $this->subevent =& $subevent;
        $this->setErrorCondition();
        $this->setShowCondition();
    }

    public $subevent;
    /**
     * この質問に対する入力内容にエラーがあるかどうかをチェックする
     * @param  array $answers アンケートへの回答すべて（条件分岐で別の質問への回答が関わるため）
     * @return array エラーがあれば配列が返る
     */
    public function getError($answers)
    {
        $res = $this->getErrorMain($this->necessaryCondition, $answers);
        if (!$res) {
            $res = $this->getErrorMain($this->errorCondition, $answers);
        }

        return $res;
    }

    /**
     * private
     * getErrorの実行部
     * @param  array $conds   判定するエラー条件。
     * @param  array $answers アンケートへの回答すべて（条件分岐で別の質問への回答が関わるため）
     * @return array エラーがあれば配列が返る
     */
    public function getErrorMain($conds, $answers)
    {
        //TODO:他クラスを含め全体的に変える必要があるが、$res[]=ではなく$res[$seid]などとすれば、
        //該当フォームの直後にエラーを表示するなどの方法が可能になります
        //なおその場合、「同じidに対する複数のエラー」など考慮の必要あり(is_array部分)
        $res = array();
        foreach ($conds as $v) {
            if ($error = $v->getError($answers)) {
                if (is_array($error)) {
                    foreach ($error as $va) {
                        $res[] = $va;
                    }
                } else {
                    $res[] = $error;
                }
            }
        }

        return $res;
    }

    /**
     * この質問が現在の回答状況で表示可能かをチェックする
     * @param  array $answers アンケートへの回答すべて（条件分岐で別の質問への回答が関わるため）
     * @return bool  表示できるならtrue
     */
    public function isVisible($answer)
    {
        foreach ($this->showCondition as $v) {
            if(!$v->isVisible($answer)) return false;
        }

        return true;
    }

    /**
     * この質問が現在の回答状況で表示できる選択肢番号を返す
     * @param  array $answers アンケートへの回答すべて（条件分岐で別の質問への回答が関わるため）
     * @return bool  表示できるならtrue
     */
    public function getVisibleChoices($answer)
    {
        //条件が無ければいきなり返す
        if (!$this->showChoiceCondition) {
            //TODO:選択肢の取得方法も統一関数にしたほうがいいかもしれない
            return array_keys(explode(',', $this->subevent['choice']));
        }

        $cn = array();
        foreach ($this->showChoiceCondition as $v) {
            foreach ($v->getVisible($answer) as $vc) {
                ++$cn[$vc];
            }
        }
        //全ての条件を満たしたもののみ返す
        $res = array();
        $cnt = count($this->showChoiceCondition);
        foreach ($cn as $k => $v) {
            if($cnt = $v) $res[] = $k;
        }

        return $res;
    }

    //将来拡張用として、下記メソッドでスクリプトをパースして条件を持つような感じ

    //エラー条件の取得
    public $necessaryCondition = array();
    public $errorCondition = array();
    //DBデータ→cond用データへのパーサと考える
    public function setErrorCondition()
    {
        /*
         * necessaryConditionは、常にチェックするエラーを記述。
         * 一般的には必須チェックがこれにあたる
         * $errorConditionは、necessaryConditionに引っかからなかった場合に詳細なチェックを行うもの
         *
         */
        //現状は旧形式にあわせて全て常にチェック

        $this->necessaryCondition = EnqueteErrorConditions::getNecessary($this->subevent);
        $this->errorCondition = EnqueteErrorConditions::get($this->subevent);
    }

    //表示条件の取得
    public $showCondition = array();
    public $showChoiceCondition = array();
    public function setShowCondition()
    {
        $se = $this->subevent;
        $this->showCondition =& EnqueteVisibleConditions::get($se);
        $this->showChoiceCondition =& EnqueteVisibleConditions::getChoices($se);
    }

    //----
    //プロパティ
    public function getChoices()
    {
        return explode(',', $this->subevent['choice']);
    }

    public function getTitle()
    {
        return $this->subevent['title'];
    }

}

function isOpenEnqueteByFlgo($flgo)
{
    return ($flgo%2==1);
}

function isNeedAuthByFlgo($flgo)
{
    return ($flgo>=2);
}
