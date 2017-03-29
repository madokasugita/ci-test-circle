<?php
require_once (DIR_LIB.'PrimaryKeyDAO.php');

class AccessLogDAO extends DAO
{

    // インスタンス
    protected static $_instance = null;

    public function setupTable()
    {
        return T_ACCESS_LOG;
    }

    public function setupColumns()
    {
        return array(
            'access_log_id' => 'ID',
            'script_name'   => 'URL',
            'cdate'         => '作成日時',
            'uid'           => 'ユーザマスタID',
            'muid'          => '管理者ユーザID',
            'proxy_flg'     => '代理ログインフラグ',
        );
    }

    /**
     * プライマリIDとなるカラムのキーを設定
     */
    public function setupPrimaryKey()
    {
        return 'access_log_id';
    }

    public function insert($data)
    {
        $now = date("Y-m-d H:i:s");
        $data['cdate'] = $now;
        unset($data['date']);

        return FDB::insert(T_ACCESS_LOG, FDB::escapeArray($data));
    }

    public static function instance()
    {
        if (self::$_instance === null) {
            $class = get_class();
            self::$_instance = new $class();
        }

        return self::$_instance;
    }
}
