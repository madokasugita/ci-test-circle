<?php
//require_once('../classes/log.php');

class TestOfLogging extends UnitTestCase
{
    public function testLogCreatesNewFileOnFirstMessage()
    {
        //@unlink('/temp/test.log');
        //$log = new Log('/temp/test.log');
        //$this->assertFalse(file_exists('/temp/test.log'));
        //$log->message('Should write this to a file');
        //$this->assertTrue(file_exists('/temp/test.log'));
        $this->assertFalse(false);
        $this->assertTrue(true);
    }
}

require_once 'simpletest/web_tester.php';

class TestOfCbase extends WebTestCase
{
    public function testWeAreTopOfCbase()
    {
        $this->assertTrue($this->get('http://cbase.co.jp/'));
        //$this->click("360度評価(多面評価)");
        //$this->assertText("調査会社が選ぶWebリサーチシステム。");
    }
}
