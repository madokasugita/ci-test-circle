<?php
require_once('aws/sdk.class.php');

class SecularApp
{

    public $targetTables = null;

    public $targetDate = null;

    public $tablePrefix = null;

    public $s3 = null;

    public $resultStatus = true;

    public static function getDeafultDBSetting()
    {
        return array(
            'DB_TYPE'       => DB_TYPE,
            'DB_USER'       => DB_USER,
            'DB_PASSWD'     => DB_PASSWD,
            'DB_HOST'       => DB_HOST,
            'DB_PORT'       => DB_PORT,
            'DB_NAME'       => DB_NAME,
            'DB_PERSISTENT' => DB_PERSISTENT,
        );
    }

    /**
     * フラグを確認してからの代理実行
     */
    public function proxyExecution($func)
    {
        if ($this->resultStatus) {
            $this->{$func}();
        }
        return $this;
    }

    /**
     * 経年比較用データベースへ接続
     */
    public function connectSecularDatabase()
    {
        $params = self::getDeafultDBSetting();
        $params['DB_NAME'] = $params['DB_NAME'] . DB_NAME_SECULAR_SUFFIX;
        FDB::setInstance($params);
        return $this;
    }

    /**
     * デフォルトデータベースへ接続
     */
    public function connectDefaultDatabase()
    {
        FDB::setInstance(self::getDeafultDBSetting());
        return $this;
    }

    /**
     * S3インスタンスをメンバ変数にセット
     */
    public function setS3Instance()
    {
        $this->s3 = new AmazonS3(array(
            'key'    => SECULAR_AWS_KEY,
            'secret' => SECULAR_AWS_SECRET_KEY,
        ));
        return $this;
    }

    /**
     * 再帰的なディレクトリ削除
     */
    public function rrmdir()
    {
        foreach (glob($this->tmpDir . '/*') as $file) {
            if (is_dir($file)) {
                $this->rrmdir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($this->tmpDir);
    }

    public function isOK()
    {
        return $this->resultStatus;
    }

    public function setSecularData($where = '')
    {
        if ($where == '') {
            $where = 'where uses_status != '.SECULAR_USES_STATUS_DISPOSAL.' order by id desc';
        }
        $this->secular = FDB::select1(T_SECULARS, '*', $where);
        return $this;
    }

    public function errorLog($action, $message)
    {
        $this->resultStatus = false;
        $ip = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
        error_log(date('Y-m-d H:i:s')."\t".$ip."\t".$_SESSION['muid']."\t".$_SERVER['SCRIPT_NAME']."\tfunction={$action}"."\n".$message."\n", 3, LOG_SECULAR);
    }

    public function alertSendMailToAdministrator($body)
    {
        $headers = array();
        $headers["Content-Type"] = "text/plain; charset=ISO-2022-JP";
        $headers["Content-Transfer-Encoding"] = "7bit";
        $headers["MIME-Version"] = "1.0";
        $headers['From'] = "=?iso-2022-jp?B?" . base64_encode(mb_convert_encoding(MAIL_SENDERNAME0, "JIS", INTERNAL_ENCODE)) . "?=";
        $headers['From'] .= " <".MAIL_SENDER0.">"; // TODO 合ってる？
        $headers['Subject'] = "=?iso-2022-jp?B?" . base64_encode(mb_convert_encoding('[SmartReview] 経年比較RAWデータインポートにてエラーが発生しました。', "JIS", INTERNAL_ENCODE)) . "?=";
        $headers['To'] = MAIL_SENDER0; // TODO 合ってる？
        $admin_url = DOMAIN.DIR_MAIN.DIR_MNG;
        $body = mb_convert_encoding($body, "JIS", INTERNAL_ENCODE);
        $mail_object = getMailObject();
        $res = $mail_object->send($headers['To'], $headers, $body);
        if (PEAR::isError($res)) {
            //PEARのエラーメッセージを取得
            $error_message = $res->getMessage()."(".$res->getDebugInfo().")"."(Report Mail Send Error)";
            error_log(implode("\t", array(
                date("Y/m/d H:i:s"),
                $headers['To'],
                $error_message
            ))."\n", 3, LOG_SEND_ERROR);
        }
    }

}
