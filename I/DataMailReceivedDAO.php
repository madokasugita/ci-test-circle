<?php

class DataAllMailReceivedDAO extends PrimaryKeyDAO
{
    public function setupTable()
    {
        return T_MAIL_RECEIVED;
    }

    public function setupColumns()
    {
        //user_id, admin_idはreceiver_idとしてまとめてもよさそう
        return array(
            'mail_received_id' => 'ID',
            'mail_to' => '送り先email',
            'mail_from' => '送り元email',
            'title' => '件名',
            'body' => '本文',
            'rdate' => '送受信日時',
            'cdate' => '作成日時(送信日時)',
        );
    }

    /**
     * プライマリIDとなるカラムのキーを設定
     */
    public function setupPrimaryKey()
    {
        return 'mail_received_id';
    }

    /**
     * プライマリIDとなるカラムのシーケンスを設定
     */
    public function setupPrimarySeq()
    {
        return 'mail_received_mail_received_id';
    }

    public function insert($data)
    {
        $now = date("Y-m-d H:i:s");
        $data['cdate'] = $now;
        if(!$data['rdate']) $data['rdate'] = $data['cdate'];

        return parent::insert($data);
    }

    public function update($data, $cond='')
    {
        unset($data['cdate']);

        return parent::update($data, $cond);
    }

//	//rsvとmail(送り先ユーザ情報)からメールのタイプを判断する
//	function getMailType ($rsv, $mail)
//	{
//		if($rsv['user_id'])
//		{
//			if ($mail['user_id'])
//			{
//				return -1;
//			}
//			elseif($mail['admin_id'])
//			{
//				//user -> admin
//				return 2;
//			}
//		}
//		elseif($rsv['admin_id'])
//		{
//			if ($mail['user_id'])
//			{
//				//admin->user
//				return 1;
//			}
//			elseif($mail['admin_id'])
//			{
//				//admin -> admin
//				return 0;
//			}
//		}
//		return -1;
//	}

    public function updateReadStatus($data, $status=1)
    {
        if($data['read_status'] == $status) return true;
        $save = array(
            $this->getPrimaryKey() => $data[$this->getPrimaryKey()],
            'read_status' => $status,
        );

        return $this->updateById($save);
    }

    public function getPriorityString($priority)
    {
        return getMailPriorityString($priority);
    }

    /**
     * 指定ターゲットを指定メールログの子として設定
     */
//	function decisionByMailLog($target_id, $mail_log_id)
//	{
//		//idのデータを読んでchild_idの親とする
//		//sender_id, user/admin_id, group_idをもってきて全部入ればok
//		$target = $this->getById($target_id);
//		//取れないまたは確定済みならエラー
//		if(!$target)
//		{
//			outputAdminError('error: 4687');
//		}
//
//		$parent = $this->getById($mail_log_id);
//		//取れないまたは未確定ならエラー
//		if(!$parent || $parent['uncertain_flag'])
//		{
//			outputAdminError('error: 6874');
//		}
//		$parent_roll = $this->getRollInfo($parent);
//		//送信者は親の受信者になる
//		$target['sender_id'] = $parent_roll['receiver_id'];
//		$target['group_id'] = $parent['group_id'];
//
//		//送り先は常に固定
//		//将来受信先も判断する場合はここで↑のsender_idを入れる
//		$target['admin_id'] = 1;
//		$target['agent_id'] = $parent['agent_id'];
//		$target['uncertain_flag'] = 0;
//		//mail_typeが対になるように設定
//		switch($parent['mail_type'])
//		{
//			case 0:
//				$target['mail_type'] = 0;
//				break;
//			case 1:
//				$target['mail_type'] = 2;
//				break;
//			case 2:
//				$target['mail_type'] = 1;
//				break;
//			default:
//				outputAdminError('error 53456');
//		}
//		$target['parent_id'] = $parent['mail_log_id'];
//		$target['thread_id'] = $parent['thread_id']? $parent['thread_id']: $parent['mail_log_id'];
//
//		$this->updateById($target);
//		return $target;
//	}
//	/**
//	 * 指定ターゲットを指定したデータとして設定
//	 * ただしreceiver_idは指摘できない（DOME:受取人は固定のため）
//	 */
//	function decisionDirect($target_id, $mail_type, $sender_id, $group_id=null,$uncertain=0,$agent_id=null)
//	{
//		//idのデータを読んでchild_idの親とする
//		//sender_id, user/admin_id, group_idをもってきて全部入ればok
//		$target = $this->getById($target_id);
//		//取れないまたは確定済みならエラー
//		if(!$target)
//		{
//			outputAdminError('error: 4687');
//		}
//
//
//		//送信者は親の受信者になる
//		$target['sender_id'] = $sender_id;
//		if($group_id) $target['group_id'] = $group_id;
//		$target['agent_id'] = $agent_id;
//
//		//送り先は常に固定
//		//将来受信先も判断する場合はここで↑のsender_idを入れる
//		$target['admin_id'] = 1;
//		$target['uncertain_flag'] = $uncertain;
//		//mail_typeが対になるように設定
//		$target['mail_type'] = $mail_type;
//
//		$target['parent_id'] = null;
//		$target['thread_id'] = null;
//
//		$this->updateById($target);
//		return $target;
//	}
//
//
//	/**
//	 * とりあえずエージェントを確定する。確定情報は全て消える
//	 */
//	function decisionAgent($target_id, $mail_type, $agent_id)
//	{
//		return $this->decisionDirect($target_id, $mail_type, $agent_id, null, 0, $agent_id);
////		return $this->decisionDirect($target_id, $mail_type, null, null, 1, $agent_id);
//	}
//
//	function cancelDecision($target_id)
//	{
//		$target = $this->getById($target_id);
//		//取れないまたは確定済みならエラー
//		if(!$target)
//		{
//			outputAdminError('error: 4687');
//		}
//
//		$target['sender_id'] = null;
//		$target['group_id'] = null;
//		$target['admin_id'] = null;
//		$target['uncertain_flag'] = 1;
//		$target['mail_type'] = -1;
//		$target['parent_id'] = null;
//		$target['thread_id'] = null;
//		$this->updateById($target);
//		return $target;
//	}
//
//	/**
//	 * 指定されたタイトルに関係ありそうなタイトルの配列を返す
//	 */
//	function getReplaySubject ($sub)
//	{
//		$subs = array($sub);
//		if(stripos($sub, 're:') === 0)
//		{
//			//頭三つを取る(RE:, Re:, re:)
//			$subs[] = mb_substr($sub, 3);
//		}
//		elseif(preg_match('/^re([0-9]+)/i',$sub, $match))
//		{
//			//Re3:ならre2:のように一つ前のReがある場合を考える
//			$no = $match[1] - 1;
//			$subs[] = preg_replace('/^re([0-9]+)/i', 'RE'.$no.':', $sub);
//			$subs[] = preg_replace('/^re([0-9]+)/i', 'Re'.$no.':', $sub);
//			$subs[] = preg_replace('/^re([0-9]+)/i', 're'.$no.':', $sub);
//		}
//		return $subs;
//	}

    public function setDeleteStatus($id, $flag=1)
    {
        $pkey = $this->getPrimaryKey();

        return parent::update(array('state_flag' => $flag), 'WHERE '.$pkey.'='.FDB::escape($id), true);
    }

    public function setDeleteStatusByIds($ids, $flag=1)
    {
        $res = array();
        foreach ($ids as $v) {
            $res[] = FDB::escape($v);
        }

        $pkey = $this->getPrimaryKey();

        return parent::update(array('state_flag' => $flag), 'WHERE '.$pkey.' IN ('.implode(',', $res).')', true);
    }

//	function getByFromAndId($mail_from, $id)
//	{
//		$where = sprintf("WHERE mail_from = %s AND mail_received_id = %s", FDB::escape($mail_from), FDB::escape($id));
//		return $this->getByCond ($where, '*');
//	}

    public function getByFrom($mail_from)
    {
        $where = ($mail_from == "") ? "WHERE mail_from IS NULL" :
                                sprintf("WHERE mail_from = %s", FDB::escape($mail_from));
        $where .= " ORDER BY rdate DESC";

        return $this->getByCond ($where, '*');
    }
}
class DataMailReceivedDAO extends DataAllMailReceivedDAO
{
    /**
     * 条件を指定して行を取得する
     * @param  string $cond 取得条件。where,limit,order,groupなど
     * @param  string $col  取得するカラム
     * @return array  取得結果
     */
    public function getByCond($cond, $col='*')
    {
        $cond = $this->mergeCond($cond, 'WHERE state_flag = 0');

        return parent::getByCond ($cond, $col);
    }
}

function getAllMailReceived()
{
    global $global_mailreceivedDAO;
    if (!$global_mailreceivedDAO) {
        $global_mailreceivedDAO = new DataAllMailReceivedDAO();
    }

    return $global_mailreceivedDAO;
}
