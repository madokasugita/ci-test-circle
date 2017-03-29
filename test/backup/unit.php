<?php
/**
 * Unitテスト実行クラス
 */
class unit
{
    public function __construct()
    {
    }
    public static function init()
    {
        $pharFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpunit.phar';
        if (!is_file($pharFile)) {
            require($pharFile);
        }
    }
    public static function execute()
    {
        require_once './StackTest.php';
        $suite  = new PHPUnit_TestSuite("StackTest");
        $result = PHPUnit::run($suite);

        echo $result -> toString();
    }
}
Unit::init();
Unit::execute();
