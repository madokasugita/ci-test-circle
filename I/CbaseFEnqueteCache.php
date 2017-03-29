<?php

define('T_UNIQUE_RESTORE', 'uniqrestore');

/**
 * アンケートのキャッシュを扱う
 * @package Cbase.Research.Lib
 * 更新履歴
 * 2007/10/02 ver1.1 CbaseClustering.phpをrequire していなかったようなのでするように kido
 */
class FEnqueteCache
{
    /**
     * 保守用。データを格納形式にする
     * @param  array $paramData データ配列
     * @return array 格納形式に変換された配列
     */
    public function encode($prmData)
    {
        $prmData[-1]["htmlh"] = ereg_replace("\n|\r\n|\t", "", $prmData[-1]["htmlh"]);
        $prmData[-1]["htmls"] = ereg_replace("\n|\r\n|\t", "", $prmData[-1]["htmls"]);
        $prmData[-1]["htmls2"] = ereg_replace("\n|\r\n|\t", "", $prmData[-1]["htmls2"]);
        $prmData[-1]["htmlm"] = ereg_replace("\n|\r\n|\t", "", $prmData[-1]["htmlm"]);
        $prmData[-1]["htmlf"] = ereg_replace("\n|\r\n|\t", "", $prmData[-1]["htmlf"]);
        for ($i = 0; $i < count($prmData[0]); ++ $i) {
            $prmData[0][$i]["html1"] = ereg_replace("\n|\r\n|\t", "", $prmData[0][$i]["html1"]);
            $prmData[0][$i]["html2"] = ereg_replace("\n|\r\n|\t", "", $prmData[0][$i]["html2"]);
        }

        return serialize($prmData);
    }

    /**
     * キャッシュデータをデコードする
     * @param  array $prmData データ配列
     * @return array デコード後データ配列
     */
    public function decode($prmData)
    {
        return unserialize($prmData);
    }

    /**
     * 指定したキャッシュファイルを読み込んでデコードして返す
     * @param  string $file ファイル名
     * @return array  データ配列
     */
    public function load($file)
    {
        return FEnqueteCache::decode(file_get_contents($file));
    }

    /**
     * キャッシュ保存
     * @param  array  $prmData 保存するファイル
     * @param  string $prmFile 保存ファイル名（規格に沿うこと）
     * @return bool   成功すればtrue
     */
    public function save($prmData, $prmFile)
    {
        if (!is_array($prmData) || !$prmFile)
            return false;
        $strPath =  DIR_CACHE. $prmFile;
        $zp = fopen($strPath, "w");
        fwrite($zp, FEnqueteCache::encode($prmData));
        fclose($zp);

        return true;
    }

    /**
     * 最新キャッシュ（キャッシュIDなし）を保存します
     * @param  array  $prmData 保存内容
     * @param  string $prmRid  RID
     * @return void
     */
    public function saveLatest($prmData, $prmRid)
    {
        return FEnqueteCache :: saveWithId($prmData, $prmRid);
    }

    /**
     * IDつきキャッシュを保存します
     * @param  array  $prmData    保存内容
     * @param  string $prmRid     RID
     * @param  int    $prmCacheId キャッシュID
     * @return void
     */
    public function saveWithId($prmData, $prmRid, $prmCacheId)
    {
        $fileName = FEnqueteCache::getFilePathWithID($prmRid, $prmCacheId);

        //IDつきキャッシュを作成
        $ptIDCache = fopen($fileName, "w");
        stream_set_write_buffer($ptIDCache, 0);
        flock($ptIDCache, LOCK_EX);
        FEnqueteCache :: writeFile($ptIDCache, $prmData);
        flock($ptIDCache, LOCK_UN);
        fclose($ptIDCache);

        return;
    }

    /**
     * 管理人用キャッシュ作成
     * @param  string $prmRid RID
     * @return bool   成功すればtrue
     */
    public function createAdmin($prmRid)//未使用みたい
    {
        if (!$prmRid)
            return false;
        $strPath = $prmRid . EXT_CACHE_M;

        return FEnqueteCache :: save(Get_Enquete("rid", $prmRid, "", ""), $strPath);
    }

    public function getLatestCache($rid)
    {
        if (is_file(DIR_CACHE  . $rid . EXT_CACHE_M)) {
            $array = FEnqueteCache::load(DIR_CACHE  . $rid . EXT_CACHE_M);
        } elseif (is_file(DIR_CACHE  . $rid . EXT_CACHE_U)) {
            //IDなしキャッシュファイルがある場合は、そちらからリードする
            $array = FEnqueteCache::load(DIR_CACHE  . $rid . EXT_CACHE_U);
        } else {
            //無い場合はつくる
            $array = FEnqueteCache :: create($rid);
        }

        return $array;
    }

    /**
     * キャッシュを作成しながらアンケートを読み込む
     * rid.cacheが無かったときに呼ばれる
     * @param string $prmRid RID
     * @param array 読み込んだアンケートデータ
     */
    public function create($prmRid)
    {
        if (!$prmRid)
            return false;

        if (!USE_CACHE) {
            return Get_Enquete_Main("rid", $prmRid, "", "");
        }

        //まずはローカルの制御:RID.cacheロック
        $ptLatest = fopen(FEnqueteCache::getFilePath($prmRid), "w");
        stream_set_write_buffer($ptLatest, 0);
        flock($ptLatest, LOCK_EX);

        //最新のcacheIDを取得する
        $latestCache = FEnqueteCache :: getBackUpEvent($prmRid);
        $cacheData = $latestCache["arrayserial"];
        $cacheId = $latestCache["cacheid"];

        if (!$latestCache || is_file(FEnqueteCache::getFilePathWithID($prmRid, $cacheId))) {
            /* 初回 or 最新のbackup_eventのキャッシュファイルが作成済み => backup_eventを追加 */
            FEnqueteCache :: setLatestBackUpEvent($prmRid, true);
            $latestCache = FEnqueteCache :: getBackUpEvent($prmRid);
            $cacheData = $latestCache["arrayserial"];
            $cacheId = $latestCache["cacheid"];
        }

        //最新のIDつきキャッシュを作成
        FEnqueteCache ::saveWithId($cacheData, $prmRid, $cacheId);

        //IDなしキャッシュを作成
        FEnqueteCache :: writeFile($ptLatest, $cacheData);

        flock($ptLatest, LOCK_UN);
        fclose($ptLatest);

        return FEnqueteCache::decode($cacheData);
    }

    public function setLatestBackUpEvent($prmRid, $lockflag = false)
    {
        if ($lockflag) {
            FDB::begin();
            //AWS対応
            FDB::lock(array(T_BACKUP_EVENT, T_EVENT, T_EVENT_SUB, T_AUTH_SET), "WRITE"); //lock
        }
        $newData = array ();
        $enquete = Get_Enquete_Main("rid", $prmRid, "", "");
        $newData["rid"] = $prmRid;
        $cacheData = FEnqueteCache :: encode($enquete);
        $newData["arrayserial"] = $cacheData;
        $insertData = FEnqueteCache :: setBackUpEvent($newData);
        if ($lockflag) {
            FDB::commit(); //unlock
        }

        return $insertData["cacheid"];
    }

    /**
     * ファイルポインタに書き込み処理を行う
     * @param  mixed  $fp        ファイルポインタ
     * @param  string $writeData 書き込むデータ
     * @return void
     */
    public function writeFile($fp, $writeData)
    {
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $writeData);
    }

    /**
     * キャッシュ削除
     * 管理用キャッシュを削除する
     * @param string $prmRid RID
     */
    public function deleteAdmin($prmRid)
    {
        $strPath1 = FEnqueteCache::getFilePathAdmin($prmRid);
        @ unlink($strPath1);
        FEnqueteCache::delete($prmRid);
        FEnqueteCache::deleteAllHost($prmRid);
    }

    /**
     * キャッシュ削除
     * ユーザ最新(cacheidなし)キャッシュを削除する
     * @param string $prmRid RID
     */
    public function delete($prmRid)
    {
        $strPath2 = FEnqueteCache::getFilePath($prmRid);
        //echo $strPath2;
        @ unlink($strPath2);
     //   FEnqueteCache::delete();
    }

    /**
     * すべてのホスト（$USE_HOST_IP）についてユーザ用キャッシュを削除するphpを呼び出す
     * @param string $prmRid RID
     */
    public function deleteAllHost($prmRid)
    {
        require_once 'cbase/CbaseClustering.php';
        $cc=new CbaseClustering(array());
        $cc->doExec(DIR_CACHE , "deleteFile", $prmRid.EXT_CACHE_U);
    }

    ////////////////////////////////////////////////////////////////////////////////
    //
    // DB関連
    //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * 新しいbackup_eventをインサートする
     * @param  array $prmData インサートするデータ
     * @return mixed 結果Object
     */
    public function setBackUpEvent($prmData)
    {
        //$newData["cacheid"] = FDB :: getNextVal('backup_event_cacheid');
        $newData["cacheid"] = null;
        $newData["rid"] = $prmData["rid"];
        $newData["arrayserial"] = $prmData["arrayserial"];

        if(is_false(FDB :: setData("new", T_BACKUP_EVENT, $newData)))

            return false;

        $newData['cacheid'] = FDB::getLastInsertedId();

        return $newData;
    }

    /**
     * 最新のbackup_eventを取得
     * @param  string $prmRid RID
     * @return array  取得結果
     */
    public function getBackUpEvent($prmRid)
    {
        $where = FDB :: where("rid=" . FDB :: escape($prmRid)) . " ORDER BY cacheid DESC LIMIT 1";
        $result = FDB :: select(T_BACKUP_EVENT, "*", $where);

        return $result[0];
    }

    /**
     * 特定IDのbackup_eventを取得
     * @param  string $prmRid     RID
     * @param  int    $prmCacheId キャッシュID
     * @return array  取得結果
     */
    public function getBackUpEventWithID($prmRid, $prmCacheId)
    {
        $where = FDB :: where("rid=" . FDB :: escape($prmRid) ." AND cacheid=".FDB::escape($prmCacheId)) . "  LIMIT 1";
        $result = FDB :: select(T_BACKUP_EVENT, "*", $where);

        return $result[0];
    }

    ////////////////////////////////////////////////////////////////////////////////
    //
    // ファイルパス
    //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * 管理用キャッシュのファイル名を取得
     * @param  string $prmRid RID
     * @return string ファイル名
     */
    public function getFilePathAdmin($prmRid)
    {
        return DIR_CACHE  . $prmRid . EXT_CACHE_M;
    }

    /**
     * キャッシュのファイル名を取得
     * @param  string $prmRid RID
     * @return string ファイル名
     */
    public function getFilePath($prmRid)
    {
        return DIR_CACHE  . $prmRid . EXT_CACHE_U;
    }

    /**
     * IDつきキャッシュのファイル名を取得
     * @param  string $prmRid  RID
     * @param  string $cacheId キャッシュID
     * @return string ファイル名
     */
    public function getFilePathWithID($prmRid, $cacheId)
    {
        return DIR_CACHE . $prmRid . "_" . $cacheId . EXT_CACHE_U;
    }

}

/**
 * 回答中情報のキャッシュ
 */
class AnswerCache
{
    public $dao;
    public function AnswerCache()
    {
        $this->dao =& new AnswerCacheDAO();
    }

    public $data;
    /**
     * ◆static
     * Enqueteクラスから作成
     * @param  object Enquete $enquete
     * @return object         AnswerCache 生成物
     */
    function &createByEnquete ($enquete)
    {
       $res =& new AnswerCache();
       $ev = $enquete->getEvent();
        $res->data['serial_no'] = $enquete->respondent['uid'];
        $res->data['event_data_id'] = $enquete->respondent['event_data_id'];

        $res->data['rid'] = $ev['rid'];
        $res->data['evid'] = $ev['evid'];

        $res->data['cacheid'] = $enquete->cache['cacheid'];
        $res->data['bdid'] = $enquete->cache['bdid'];

        return $res;
    }

    /**
     * ◆static
     * event_data_idからAnswerCacheを取得
     * @param  integer $evdataId event_data_id
     * @return object  AnswerCache 生成物
     */
    function &createByEvdataId($evdataId)
    {
        //念のため一番新しいキャッシュIDのものを持ってくる
        /*
         * 途中保存→はじめから答える→途中保存の場合にキャッシュデータが二つになる可能性があるため
         * ただしbdidは重複しないので問題はない
         */
        $where = FDB :: where("event_data_id =".FDB::escape($evdataId) ). " ORDER BY cacheid DESC LIMIT 1";
        $res =& new AnswerCache();
        $result = $res->dao->getByCond($where);
        if ($result) {
            $res->data = $result[0];

            return $res;
        }

        return false;
    }

    /**
     * バックアップデータのアクセスurlエンコード
     * @param  string $serialNo シリアルナンバー
     * @param  string $bdid     バックアップデータID
     * @return string urlのクエリ文字列
     */
    public function getUrl($serialNo, $bdid)
    {
        return $serialNo.$bdid;
    }

    /**
     * backup_dataを発行
     * @param  array $prmData 保存データ
     * @return mixed 結果オブジェクト
     */
    public function save()
    {
        $save =& $this->data;
        //バックアップidが設定されていれば読み出されたデータ（更新の必要あり）とみなす

       if ($save["bdid"]) {
            $option = FDB::where("bdid=".FDB::escape($save["bdid"]));

            return $this->dao->update($save, $option);
        } else {
            //最新のcacheidを取得
            //$latestCache = $this->dao->getByRid($save["rid"]);
            $latestCache = FEnqueteCache::getBackUpEvent($save["rid"]);
            $save["cacheid"] = $latestCache["cacheid"];
            //$res = false;
            $i = 0;
            do {
                if (1000000 < ++$i) {
                    echo "登録エラー";
                    exit;
                }
                $save["bdid"] = Get_RandID(24);
                $res = $this->dao->insert($save);
            } while (!$res);

            return $res;
        }
    }

//eventdataがidを持つに伴い廃止。ただしユーザ単位で削除したい場合などのためコメントアウトにて保存
//    /**
//	 * serial_noとridからbackup_dataを削除
//	 * @param string $serial serial_no
//	 * @param string $rid rid
//	 * @return array 取得したデータ（一件）
//	 */
//	function delete($serial, $rid)
//	{
//		$where = FDB :: where("rid=" . FDB :: escape($rid)
//			. " AND serial_no=" . FDB :: escape($serial));
//		return $this->dao->delete($where);
//	}

    /**
     * event_data_idからbackup_dataを削除
     * @param  string $evdataId event_data_id
     * @return array  取得したデータ（一件）
     */
    public function delete($evdataId)
    {
        $where = FDB :: where("event_data_id=" . FDB :: escape($evdataId));

        return $this->dao->delete($where);
    }

    public function getLatestCache()
    {
        $path = FEnqueteCache::getFilePathWithID($this->data["rid"], $this->data["cacheid"]);

        if (is_file($path)) {
            $useCache = FEnqueteCache::load($path);
        } else {
            $useCache = FEnqueteCache::getBackUpEventWithID($this->data["rid"], $this->data["cacheid"]);
            FEnqueteCache ::saveWithId($useCache["arrayserial"], $this->data["rid"], $this->data["cacheid"]);

        }
        $latestCache = FEnqueteCache::getLatestCache($this->data["rid"]);

        return $this->isSameCache($useCache, $latestCache)? $latestCache: $useCache;
    }

    /**
     * 二つのキャッシュが同じならtrue
     * この関数の精度で最新キャッシュ取得置換を調整できる
     * @author Cbase akama
     */
    public function isSameCache($cache1, $cache2)
    {
        //質問数が同じならtrue
//		return false;
        return (count($cache1[0]) == count($cache2[0]));
    }
}

if (class_exists('DAO')) {
/**
 * 回答中情報のキャッシュ
 */
class AnswerCacheDAO extends DAO
{
    public function constructor()
    {
        parent::constructor();
        $this->table = T_BACKUP_DATA;
    }

    public function getColumns()
    {
        return array(
            'serial_no'=> 'ユーザID',
            'cdate'    => '作成日時',
            'cacheid'      => 'キャッシュID',
            'rid'      => 'イベントID',
            'bdid'      => 'イベントID',
            'udate'     => '更新日時',
            'evid'      => 'イベントID',
            'page' => 'ページ番号',
            'event_data_id'      => 'イベントデータID',
            'restore_id' =>'再開用ID',
        );
    }

    public function getByRid($rid)
    {
        return $this->getByCond('WHERE rid='.FDB::escape($rid));

    }

    public function insert($data)
    {
        //初期値の必要がある場合は入れる
        $now = date("Y-m-d H:i:s");
        $data['cdate'] = $now;
        $data['udate'] = $now;
        if (ENQ_OPEN_RESTORE) {
            $data['restore_id'] = getUniqueRestoreId();
        }

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
}

function getUniqueRestoreId()
{
    return getUniqueIdWithTable(T_UNIQUE_RESTORE, 'restore_id', 6, 6);
}
