<?php
class TestOfDemo extends WebTestCase
{
    public function testDemoUserCreate()
    {
        $params = array("name"=>"ユニットテスト_".rand(0, 9999), "email"=>"imai+u@cbase.co.jp", "mode:demo"=>"送信する");
        $this->post(DOMAIN.DIR_MAIN.'360_demo.php', $params);
        $this->assertText("メールを送信しました");
        $user = FDB::select1(T_USER_MST, "*", "WHERE name = ".FDB::escape($params["name"]));
        $this->assertNotEqual($user, false);
    }
}
