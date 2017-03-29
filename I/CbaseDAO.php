<?php
/**
 * (DAO)
 * 2008.2.29現在最新
 * ・recentDataのサポート
 * ・setup系メソッドの追加
 */
class DAO
{
    public $table;
    public $columns;
    /**
     * noEscape = trueで、SQLのエスケープを行わない（計算式用）
     * この場合、使用側でエスケープ処理を確実にすること
     */
    public $noEscape = false;

    /**
     * このモードが有効だと実行せずSQLのみを返す
     */
    public $modeSQL = false;

    /**
     * 直前に更新・追加のSQLを発行したデータがここに保存される
     */
    public $recentData;

    public function DAO()
    {
        $this->table = $this->setupTable ();
        $this->columns = $this->setupColumns ();
        $this->constructor();
    }

    /**
     * ◆abstruct
     * このオブジェクトで使うテーブルを設定
     * @return array DB上の列名=>項目名の配列
     */
    public function setupTable()
    {
        return '';
    }

    /**
     * ◆abstruct
     * このオブジェクトで使うカラムを設定
     * @return array DB上の列名=>項目名の配列
     */
    public function setupColumns()
    {
        return array();
    }

    /**
     * ◆virtual
     * 初期化処理が必要になった場合はここに書く
     * 継承で利用する際は必ずparent::constructorを呼ぶこと
     */
    public function constructor() {}

    /**
     * このオブジェクトで使えるテーブルを取得
     * @return array DB上の列名=>項目名の配列
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * このオブジェクトで使えるカラムを取得
     * @return array DB上の列名=>項目名の配列
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * 最近登録されたデータ配列を返す
     */
    public function getRecent()
    {
        return $this->recentData;
    }

    /**
     * 全行を取得する
     * @return array 取得結果
     */
    public function getAll()
    {
        return $this->getByCond ($cond='');
    }

    /**
     * 条件を指定して行を取得する
     * @param  string $cond 取得条件。where,limit,order,groupなど
     * @param  string $col  取得するカラム
     * @return array  取得結果
     */
    public function getByCond($cond, $col='*')
    {
        return $this->getAssoc(FDB::getSelectSql($this->getTable(), $col,  $cond));
    }

    /**
     * データをインサートする
     * @param  string $data 保存するデータ
     * @return object DBResult 問い合わせ結果
     */
    public function insert($data)
    {
        $this->recentData = $data;

        return $this->query(FDB::getInsertSql($this->getTable(), $this->getSaveData($data)));
    }

    public function insertArray($aryData)
    {
        $count = 0;
        $tmpData = array();
        if(is_array($aryData))
        foreach ($aryData as $key => $data) {
            $this->recentData = $data;
            $tmpData[] = $this->getSaveData($data);
            if (++$count%10==0) {
                if(is_false($this->query(FDB::getInsertArraySql($this->getTable(), $tmpData))))

                    return false;
                $tmpData = array();
            }
        }
        if (is_good($tmpData)) {
            if(is_false($this->query(FDB::getInsertArraySql($this->getTable(), $tmpData))))

                return false;
        }

        return true;
    }
    /**
     * データをアップデートする
     * @param  string $data 保存するデータ
     * @param  string $cond 取得条件。where,limit,order,groupなど
     * @return object DBResult 問い合わせ結果
     */
    public function update($data, $cond='')
    {
        $this->recentData = $data;

        return $this->query(FDB::getUpdateSql($this->getTable(), $this->getSaveData($data), $cond));
    }

    /**
     * データを削除する
     * @param  string $cond 削除条件。where,limit,order,groupなど
     * @return object DBResult 問い合わせ結果
     */
    public function delete($cond='')
    {
        return $this->query(FDB::getDeleteSql($this->getTable(), $cond));
    }

    /**
     * テーブルデータ全てを削除する
     * @return object DBResult 問い合わせ結果
     */
    public function truncate()
    {
        return $this->query(FDB::getTruncateSql($this->getTable()));
    }

    /**
     * 与えられたSQLを実行するか、そのまま返す
     * ※この命令を直に使って更新するとrecentDataが更新されないため整合性注意
     */
    public function query($query)
    {
        return $this->modeSQL? $query: FDB::sql($query, true);
    }

    /**
     * 与えられたSQLを実行するか、そのまま返す
     */
    public function getAssoc($query)
    {
        return $this->modeSQL? $query: FDB::getAssoc($query);
    }

    /**
     * データを保存用に変換する。
     * 主に、データからの指定カラムのみの抜き出し及びエスケープを行う
     * @param  string $data 保存するデータ
     * @return array  整形済みデータ
     */
    public function getSaveData($data)
    {
        $col = $this->getColumns();
        $res = array();
        foreach ($data as $k => $v) {
            if ($col[$k]) {
                $res[$k] = $this->noEscape? $v: FDB::escape($v);
            }
        }

        return $res;
    }
}

//使用例
///**
// * eventDataへのデータアクセスクラス
// */
//class EventDataDAO extends DAO
//{
//	function constructor ()
//	{
//		parent::constructor();
//		$this->table = T_EVENT_DATA;
//	}
//
//	function getColumns ()
//	{
//		return array(
//			'evid'      => 'イベントID',
//			'serial_no'=> 'ユーザID',
//			'cdate'     => '作成日',
//			'flg'       => '汎用フラグ'
//		);
//	}
//
//	function insert ($data)
//	{
//		//初期値の必要がある場合は入れる
//		$data['cdate'] = date("Y-m-d H:i:s");
//		return parent::insert($data);
//	}
//}
