<?php
/*
 * DataEditorを用いてDAOを編集するためのクラス群
 */
require_once 'CbaseDataEditor.php';

/**
 * CbaseDAOとEditAdapterを接続する
 */
class EditToDAOAdapter extends DataEditAdapter
{
    /**
     * ◆abstract
     * @return object CbaseDAO DAOクラスを返す
     */
    public function setupDAO()
    {
        return null;
    }

    public $dao;
    function &getDAO()
    {
        if (!$this->dao) {
            $this->dao = $this->setupDAO();
        }

        return $this->dao;
    }

    /**
     * ◆abstract
     * @return array このデータで使えるカラムを返す
     */
    public function setupColumns()
    {
        $dao =& $this->getDAO();

        return $dao->getColumns();
    }

    /**
     * 保存用のデータが送られてくる
     * @param  array $data 保存用のデータ
     * @return bool  保存に成功すればtrue
     */
    public function save($data)
    {
        $dao =& $this->getDAO();

        return $dao->save($data);
    }

}
