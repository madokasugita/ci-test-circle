<?php
/**
 * 回答データ一人分を扱う。
 * 同一evid,uidへの複数回答には対応していない（researchのシステムを変えなければ不可）
 * 複数人を一括で取得する際は下のDBデータクラスで。
 */
class EnqueteAnswer
{

//-------------------------------------------------------
//	function EnqueteAnswer()
//	{
//	}

    /**
     * ◆static
     * 値を指定して新しい回答データの作成
     * @param  int    $evdataId このデータのId。指定するとそのIdを上書きする。新規作成の場合はnull
     * @param  int    $evid     このデータが示す回答先eventのID
     * @param  string $uid      このデータの回答者のID。CbaseResearchの場合はserial_no
     * @param  string $flg      予備の汎用フラグ。稀に使用される。
     * @return object EnqueteAnswer 作成したオブジェクト
     */
    function &create ($evdataId, $evid, $uid, $flg='')
    {
        $self =& new EnqueteAnswer();
        $self->setInfo($evdataId, $evid, $uid, $flg);

        return $self;
    }

    /**
     * ◆static
     * 指定したIDから回答データを読み込んで作成
     * @param  int    $evdataId 読み込むデータのId
     * @return object EnqueteAnswer 作成したオブジェクト
     */
    function &loadById ($evdataId)
    {
        $ed =& new EventDataDAO();
        $ev = $ed->getById($evdataId);
        if ($ev) {
            return EnqueteAnswer::loadFromEventData($ev[0]);
        }

        return null;
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
    function &load ($evid, $uid)
    {
        $ed =& new EventDataDAO();
        $ev = $ed->getByQuerySet($evid, $uid);
        if ($ev) {
            return EnqueteAnswer::loadFromEventData($ev[0]);
        }

        return null;
    }

    /**
     * ◆static
     * 読み込み済みのevent_data一行分から回答データを読み込んで作成
     * @param  array  $evdata event_data一行分
     * @return object EnqueteAnswer 作成したオブジェクト
     */
    function &loadFromEventData ($evdata)
    {
        $self =& new EnqueteAnswer();
        $self->setInfoFromEventData($evdata);
        $sed =& new SubEventDataDAO();
        foreach ($sed->getByEvdataId($evdata['event_data_id']) as $v) {
            $self->addAnswer ($v['seid'], $v['choice'], $v['other']);
        }

        return $self;

    }

    /**
     * 回答情報(event_data)
     */
    public $info;//eventdata

    /**
     * 回答データの配列(subevent_data)
     */
    public $answer = array();//subeventdata

    /**
     * infoが正しくセットされないと、DBに保存されるべきではない。
     * 正しくセットされた時trueにする
     */
    public $enable = false;

    /**
     * この配列にseidを追加しておくと、保存時に同時に消してくれる
     */
    public $deleteList = array(); //削除対象seidのリスト

    /**
     * infoをセットする
     * @param int    $evdataId このデータのId。指定するとそのIdを上書きする。新規作成の場合はnull
     * @param int    $evid     このデータが示す回答先eventのID
     * @param string $uid      このデータの回答者のID。CbaseResearchの場合はserial_no
     * @param string $flg      予備の汎用フラグ。稀に使用される。
     */
    public function setInfo($evdataId, $evid, $uid, $flg='')
    {
        $this->setInfoFromEventData(array(
            'event_data_id' => $evdataId,
            'evid' => $evid,
            'serial_no' => $uid,
            'flg' => $flg
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
            $ed =& new EventDataDAO();
            $evd = $ed->getByQuerySet($evdata['evid'],$evdata['serial_no']);
            if($evd) $evdata = $evd[0];
        }

        $this->info = array(
            'event_data_id' => $evdata['event_data_id'],
            'evid' => $evdata['evid'],
            'serial_no' => $evdata['serial_no'],
            'flg' => $evdata['flg'],
            'answer_state' => $evdata['answer_state']
        );

        if (!$this->info['event_data_id']) {
            $this->saveMidstInfo();
        }

        $this->enable = true;
    }

    /**
     * 回答を追加する
     * @param int    $seid   回答先の質問ID
     * @param int    $choice 回答番号。記入回答などの場合は-1が入るはず。
     * @param string $other  記入回答。無ければnull
     */
    public function addAnswer($seid, $choice, $other)
    {
        $this->answer[] = array(
            'event_data_id' => $this->info['event_data_id'],
            'evid' => $this->info['evid'],
            'serial_no' => $this->info['serial_no'],
            'seid' => $seid,
            'choice' => $choice,
            'other' => $other,
        );
    }

    //TODO:特に関数分けせずgetByQuerySetを使用して問題ないと思われます
    /**
     * ◆static
     * evidとuidから対応するevent_data_idを取得
     * @param int    $evid 回答先eventのID
     * @param string $uid  CbaseResearchの場合はserial_no
     */
    public function getEventDataIdById($evid, $uid)
    {
        $ed =& new EventDataDAO();
        $ev = $ed->getByQuerySet($evid, $uid);

        return $ev[0]['event_data_id'];
    }

    /**
     * 回答の途中保存を行う
     * @param  int $evdataId 既にevdataidを持っていれば、それを渡す（無ければDBを読む）
     * @return int 登録したevent_data_idを返す
     */
    public function saveMidst($evdataId=false)
    {
        if(!$this->enable) return false;

        $sed =& new SubEventDataDAO();
        if ($dc = $this->getDeleteCond()) {
            $sed->delete($dc);
        }
        if(is_false($sed->insertArray($this->answer)))

            return false;

        return true;
    }

    /**
     * 途中回答の不要分を削除する条件を返す
     */
    public function getDeleteCond()
    {
        if ($this->deleteList) {
            $seids = array();
            foreach ($this->deleteList as $v) {
                $seids[] = FDB::escape($v);
            }
            //DEBUG:デバッグ文書<<<--
            if (!$this->info['event_data_id']) {
                echo'getDeleteCondにてevent_data_idがありません';
                exit;
            }
            //-->>>
            $list = $this->getAnswerStateSet();

            return 'WHERE '.
                'event_data_id='.FDB::escape($this->info['event_data_id']).
                ' AND seid IN ('.implode(',', $seids).')';
        }

        return false;
    }

    /**
     * 途中保存データとしてevent_Dataを保存する
     */
    public function saveMidstInfo()
    {

        $ed =& new EventDataDAO();
        $data = $ed->getByCond('WHERE event_data_id='.FDB::escape($this->info['event_data_id']).' LIMIT 1');
        //TODO:定数を使うこと
        $this->info['answer_state'] = 10;
        if (count($data) <= 0) {
            //データが無ければインサート
            $this->info['event_data_id'] = FDB::getNextVal('event_data_event_data_id');
            if(!$ed->insert($this->info)) return false;
        } else {
            $this->info['event_data_id'] = $data[0]['event_data_id'];
        }

        return $this->info['event_data_id'];
    }

    /**
     * 途中保存データとして保存されているevent_dataのステータスを回答完了に変更する
     */
    public function saveCompleteInfo()
    {
        if(!$this->enable) return false;
        $ed =& new EventDataDAO();
        $flag = $this->getAnswerStateSet();
        $data = $ed->update(array('answer_state' => $flag['completed']),
            'WHERE event_data_id='.FDB::escape($this->info['event_data_id']));

    }

    /**
     * event_dataのステータスを削除に変更する・またはモードによっては完全に消す
     */
    public function deleteInfo()
    {
        if(!$this->enable) return false;

        $ed =& new EventDataDAO();
        switch (ENQ_DATA_DELETE_MODE) {
            case 0:
                $sed =& new SubEventDataDAO();
                $cond = 'where event_data_id='.FDB::escape($this->info['event_data_id']);
                $ed->delete($cond);
                $sed->delete($cond);
                break;
            case 1:
                $flag = $this->getAnswerStateSet();
                $data = $ed->update(array('answer_state' => $flag['deleted']),
                    'WHERE event_data_id='.FDB::escape($this->info['event_data_id']));
                break;
        }

    }

    /**
     * 回答を一括で保存する
     * 現状未使用
     */
    public function save()
    {
        echo 'EnqueteAnswer.saveはつかっていません';exit;
        if(!$this->enable) return false;
//		if (!REOPEN)
//		if (true)
//		{
//			$this->invalidate();
//		}
        $ed =& new EventDataDAO();
        $res = $ed->insert($this->info);
        if ($res) {
            $sed =& new SubEventDataDAO();
            foreach ($this->answer as $v) {
                if(!($res = $sed->insert($v))) return false;
            }
        }

        return $res;
    }

    //TODO:DAOにおくべきかも？
    /**
     * ◆static
     * イベント回答データ件数チェック
     * @param  int    $evid イベントID
     * @param  string $urid ユーザー特定キー
     * @param  string $flg  ユーザー層データ
     * @return int    データ件数
     */
    public function isDuplicate($evid, $urid="", $flg="")
    {

        //SQL文生成

        $where = "where evid = ".FDB::escape($evid)." ";
        if ($urid<>"") {
            $where.= "and serial_no = ".FDB::escape($urid)." ";
        }
        if ($flg<>"") {
            $where.= "and flg = ".FDB::escape($flg)." ";
        }

        $set = EnqueteAnswer::getAnswerStateSet();
        $where.= "and answer_state = ".FDB::escape($set['completed'])." ";
        //SQL実行
        $cls =& new EventDataDAO();
        $res = $cls->getByCond($where);

        return (0 < count($res));
    }

    /**
     * このデータのanswerstateを取得する
     * @return string getAnswerStateSetのキー文字列
     */
    public function getAnswerState()
    {
        $list = array_flip($this->getAnswerStateSet());

        return $list[$this->info['answer_state']];
    }

    /**
     * answerstateの定数を取得する
     * @return array 定数名=>数値の配列
     */
    public function getAnswerStateSet()
    {
        return array(
            /* 削除 */'deleted' => -10,
            /* 完了 */'completed' => 0,
            /* 途中 */'midst' => 10
        );
    }

    /**
     * シートタイプとnumから該当する回答を１件取得
     * @param int num 設問番号
     * @param int sheet_type シートタイプ
     * @return array 回答情報
     */
    public function getAnswerNumSubEventData($evid, $target)
    {
        $sql = "SELECT se.num, sed.* FROM ".T_EVENT_SUB." se
            LEFT JOIN ".T_EVENT_SUB_DATA." sed ON sed.seid = se.seid
            LEFT JOIN ".T_EVENT_DATA." ed ON ed.event_data_id = sed.event_data_id
            WHERE ed.evid = ".FDB::escape($evid)."
            AND ed.target = ".FDB::escape($target)."
            AND ed.serial_no != ".FDB::escape($_SESSION['login']['serial_no'])."
            AND ed.answer_state = 0";

        return FDB::getAssoc($sql);
    }
}

/**
 * eventDataへのデータアクセスクラス
 */
class EventDataDAO extends DAO
{
    public function constructor()
    {
        parent::constructor();
        $this->table = T_EVENT_DATA;
    }

    public function getColumns()
    {
        return array(
            'event_data_id'      => 'イベントデータID',
            'evid'      => 'イベントID',
            'serial_no'=> 'ユーザID',
            'cdate'     => '作成日',
            'udate'     => '最終更新日',
            'flg'       => '汎用フラグ',
            'target'       => '対象者', //360度用拡張
            'answer_state'=>'回答データの状況',
            'level'=>'レベル'
        );
    }

    public function getById($id)
    {
        return $this->select('WHERE event_data_id='.FDB::escape($id));
    }

    public function getBySetId($evid, $uid)
    {
        return $this->getByCond('WHERE evid='.FDB::escape($evid).' AND serial_no='.FDB::escape($uid));
    }

    //360度用修正
    public function getByQuerySet($evid, $uid, $target=null)
    {
        return $this->getByCond('WHERE 0 <= answer_state AND evid='.FDB::escape($evid)
            .' AND serial_no='.FDB::escape($uid)
            .' AND target='.FDB::escape($target));
    }

    public function insert($data)
    {
        //初期値の必要がある場合は入れる
        $now = date("Y-m-d H:i:s");
        $data['cdate'] = $now;
        $data['udate'] = $now;

        return parent::insert($data);
    }

    public function update($data, $cond='')
    {
        //初期値の必要がある場合は入れる
        $now = date("Y-m-d H:i:s");
        unset($data['cdate']);
        $data['udate'] = $now;

        return parent::update($data, $cond);
    }

}

/**
 * subEventへのデータアクセスクラス
 */
class SubEventDAO extends DAO
{
    public function constructor()
    {
        parent::constructor();
        $this->table = T_EVENT_SUB;
    }

    public function getColumns()
    {
        return array(
            'seid'       => 'ID',
            'evid'       => '評価シートID',
            'title'      => '質問文',
            'type1'      => '設問タイプ1',
            'type2'      => '設問タイプ2',
            'choice'     => '未使用',
            'hissu'      => '回答制御',
            'width'      => '入力欄サイズ・横幅',
            'rows'       => '入力欄サイズ・行数',
            'word_limit' => '最大文字数',
            'cond'       => 'エラーチェック条件',
            'page'       => 'ページ番号',
            'other'      => '記入回答欄',
            'html1'      => '用途不明',
            'html2'      => 'メインHTML',
            'cond2'      => 'エラーチェック条件2',
            'cond3'      => 'エラーチェック条件3',
            'cond4'      => 'エラーチェック条件4',
            'cond5'      => 'エラーチェック条件5',
            'ext'        => '追加設置',
            'fel'        => '1行に並べる選択肢数',
            'chtable'    => 'DLデータ値',
            'matrix'     => 'マトリクス',
            'randomize'  => 'ランダマイズ',
            'cond360'    => 'カスタマイズ条件',
            'num'        => '設問番号',
            'category1'  => '設問大カテゴリ',
            'category2'  => '設問中カテゴリ',
            'num_ext'    => '設問番号(表示用)',
        );
    }
}

/**
 * subEventDataへのデータアクセスクラス
 */
class SubEventDataDAO extends DAO
{
    public function constructor()
    {
        parent::constructor();
        $this->table = T_EVENT_SUB_DATA;
    }

    public function getColumns()
    {
        return array(
            'event_data_id'      => 'イベントデータID',
            'evid'      => 'イベントID',
            'serial_no'=> 'ユーザID',
            'seid'      => '質問ID',
            'choice'    => '選択番号',
            'other'     => 'その他欄',
        );
    }

    public function getByEvdataId($id)
    {
        return $this->getByCond('WHERE event_data_id='.FDB::escape($id));
    }

}
