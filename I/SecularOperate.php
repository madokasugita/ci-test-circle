<?php
require_once('SecularApp.php');

class SecularOperate extends SecularApp
{

    public $hashCounter = 0;

    public $usesStatuses = array(
        SECULAR_USES_STATUS_UNUSED   => 'データ未作成',
        SECULAR_USES_STATUS_CREATED  => 'データ作成済み',
        SECULAR_USES_STATUS_IMPORTED => 'データインポート済み',
        SECULAR_USES_STATUS_DISPOSAL => '-',
    );

    /**
     * ステータスの文言を返却
     */
    public function getUsesStatusString()
    {
        if (is_void($this->secular) || is_void($this->usesStatuses[$this->secular['uses_status']])) {
            return '未発番です。発番実施をお願いいたします。';
        }
        return $this->usesStatuses[$this->secular['uses_status']];
    }

    /**
     * 最終更新者名を取得
     */
    public function getLastModifiedUserName()
    {
        $musr = FDB::select1(T_MUSR, '*', 'where muid = ' . FDB::escape($this->secular['muid']));
        if (is_void($musr)) {
            return '';
        }
        return $musr['name'];
    }

    /**
     * ハッシュ発番メイン処理
     */
    public function rehashExecute()
    {
        $this->connectSecularDatabase();
        $this->setSecularData();
        FDB::begin();
        if (is_good($this->secular)) {
            $this->updateUsesStatusToDisposal();
        }
        if ($this->resultStatus) {
            $save = array(
                'ymd'         => date('Ymd'),
                'hash'        => $this->makeHash(),
                'uses_status' => 1,
                'modified_at' => date('Y-m-d H:i:s'),
                'muid'        => $_SESSION['muid'],
            );
            if (FDB::insert(T_SECULARS, FDB::escapeArray($save)) === false) {
                $this->errorLog('SecularOperate::rehashExecute', 'SQL実行エラー');
            }
        }
        if ($this->resultStatus) {
            FDB::commit();
        } else {
            FDB::rollback();
        }
        $this->connectDefaultDatabase();
        return $this;
    }

    /**
     * ステータスを廃棄に更新
     */
    public function updateUsesStatusToDisposal()
    {
        $this->secular['uses_status'] = SECULAR_USES_STATUS_DISPOSAL;
        $this->secular['muid'] = $_SESSION['muid'];
        $this->secular['modified_at'] = date('Y-m-d H:i:s');
        if (FDB::update(T_SECULARS, FDB::escapeArray($this->secular), 'WHERE id = '.FDB::escape($this->secular['id'])) === false) {
            $this->errorLog('SecularOperate::updateUsesStatusToDisposal', 'SQL実行エラー');
        }
        return $this;
    }

    /**
     * Hashが登録済みかを確認。
     */
    public function hashExists($hash)
    {
        $count = FDB::select1(T_SECULARS, 'count(*) as count', 'where hash = ' . FDB::escape($hash));
        return ($count['count'] > 0);
    }

    /**
     * ユニークHashを作成
     */
    public function makeHash()
    {
        $hash = $this->getNewHash($this->hashCounter);
        if ($this->hashExists($hash)) {
            $this->hashCounter++;
            $this->makeHash();
        }
        return $hash;
    }

    /**
     * Hash生成
     */
    public function getNewHash($prefix = '') {
        return substr(md5(uniqid($prefix)), 0, 12);
    }
}
