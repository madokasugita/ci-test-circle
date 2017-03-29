<?php
//新規：2012-05-18
class Encryption
{
    /**
     *  Set any difficult Key for guessing
     *
     *  @access public
     */
    public $skey = BLOWFISH_SECRET_KEY;
    /**
     *  This makes url or cookie value safe after encrypting any value
     *
     *  @access public
     */
    /**
     * @access private
     * @var Object
     */
    private static $_instance;
    /**
     * Constructor
     * 初期制限
     */
    private function __construct()
    {
    }
    /**
     * インスタンスチェックしてなければ,
     * 初期します。
     * 存在したら前のインスタンスを返す。
     */
    public static function getInstance()
    {
        if (!isset(self::$_instance)) {
            $className = __CLASS__;
            self::$_instance = new $className;
        }

        return self::$_instance;
    }
    public function safe_b64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);

        return $data;
    }
    /**
     *  This makes url or cookie value safe after decrypting any value
     *
     *  @access public
     */
    public function safe_b64decode($string)
    {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return base64_decode($data);
    }
    /**
     *  mcrypt libreary required
     *
     *  @access public
     */
    public function enc($value)
    {
        if (!$value) {
            return false;
        }
        $text = $value;
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $crypttext = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->skey, $text, MCRYPT_MODE_ECB, $iv);

        return trim($this->safe_b64encode($crypttext));
    }
    /**
     *  mcrypt libreary required
     *
     *  @access public
     */
    public function dec($value)
    {
        if (!$value) {
            return false;
        }
        $crypttext = $this->safe_b64decode($value);
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $decrypttext = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->skey, $crypttext, MCRYPT_MODE_ECB, $iv);

        return trim($decrypttext);
    }
    /**
     * Clone禁止
     */
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    /**
     * Unserializing禁止
     */
    public function __wakeup()
    {
        trigger_error('Unserializing is not allowed.', E_USER_ERROR);
    }
}
