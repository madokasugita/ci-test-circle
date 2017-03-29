<?php
require_once 'CbaseDAO.php';

class PrimaryKeyDAO extends DAO
{
    public function PrimaryKeyDAO()
    {

        parent::DAO();
    }

    public $primaryKey;
    public $primarySeq;
    public function constructor()
    {
        parent::constructor();
        $this->primaryKey = $this->setupPrimaryKey();
        $this->primarySeq = $this->setupPrimarySeq();
    }

    /**
     * プライマリIDとなるカラムのキーを設定
     */
    public function setupPrimaryKey()
    {
        return '';
    }

    /**
     * プライマリIDとなるカラムのシーケンスを設定
     */
    public function setupPrimarySeq()
    {
        return $this->setupTable().'_'.$this->setupPrimaryKey();
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getPrimarySeq()
    {
        return $this->primarySeq;
    }

    public $idCache = true;
    /**
     * idからデータを取得する
     * @return SELECT結果(必ず一件)
     */
    public function getById($id, $other="", $col='*', $useCache=true)
    {
        if ($this->idCache && $useCache) {
            global $global_groupdao_ids;
            $me = get_class($this);
            if (is_null($global_groupdao_ids[$me][$id])) {

                $res = $this->getByCond('WHERE '.$this->getPrimaryKey().'='.FDB::escape($id).$other.' LIMIT 1', $col);
                $global_groupdao_ids[$me][$id] = $res[0]? $res[0]: array();
            }

            return $global_groupdao_ids[$me][$id];
        } else

            return $this->getByCond('WHERE '.$this->getPrimaryKey().'='.FDB::escape($id).$other.' LIMIT 1', $col);
    }

    /**
     * @param array  $ids
     * @param string $other
     */
    public function getByIds($ids, $other="")
    {
        $s = array();
        foreach ($ids as $v) {
            $s[] = FDB::escape($v);
        }
        $res = $this->getByCond('WHERE '.$this->getPrimaryKey().' IN('.implode(',', $s).')'.$other);

        return $res;
    }

    public function getCount($cond)
    {
        $res = $this->getByCond($cond, "count(".$this->getPrimaryKey().") as count");

        return $res[0]['count'];
    }

    /**
     * idでinsert/updateを判断してデータをDBに保存する
     * @return mixied 成功すれば登録ID、失敗すればfalseを返す
     */
    public function save($data)
    {
        $pkey = $this->getPrimaryKey();
        if ($data[$pkey] !== 0 && !$data[$pkey]) {
            $rs = $this->insert($data);
            //idを取得する
            $data = $this->getRecent();
        } else {
            $rs = $this->updateById($data);
        }

        return ($rs==false)? false: $data[$pkey];
    }

    public function deleteById($id, $other='')
    {
        $pkey = $this->getPrimaryKey();

        return $this->delete('WHERE '.$pkey.'='.FDB::escape($id).' '.$other);

    }

    public function insert($data, $idupdate=true)
    {
        if($idupdate) $data[$this->getPrimaryKey()] = FDB::getNextVal($this->getPrimarySeq());

        return parent::insert($data);

    }

    public function updateById($data, $other='')
    {
        $pkey = $this->getPrimaryKey();

        return $this->update($data, 'WHERE '.$pkey.'='.FDB::escape($data[$pkey]).' '.$other);

    }

    public function update($data, $cond='', $idupdate=false)
    {
        //idはupdateしない
        if(!$idupdate) unset($data[$this->getPrimaryKey()]);

        return parent::update($data, $cond);
    }

    public function duplicate($id)
    {
        if (!$id) return false;
        return $this->duplicateFromData ($this->getById($id));
    }

    /**
     * 一行分のデータから複製
     */
    public function duplicateFromData($data)
    {
        if (!$data) return false;
        return $this->insert($this->setupDuplicateData ($data));
    }

    /**
     * @return array 重複時のデータ修正などを行う
     */
    public function setupDuplicateData($data)
    {
        return $data;
    }
}
