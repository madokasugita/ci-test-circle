<?php
require_once 'simpletest/browser.php';

class TestOfEnviewer extends UnitTestCase
{
    public function testKaonaviOfEnviwer()
    {
        $this->root = 'https://ct2.mrejapan.com/sys_demo/enviewer/admin/';
        $browser = new SimpleBrowser();
        $browser->post($this->root, array("id"=>'yasuda', "pw"=>"yasuda"));
        $offset = 0;
        $page = $browser->get($this->root.'setting_face_navi.php?op=search&pagelimit=100&offset='.$offset);
        preg_match_all("/<table.*?<\/table>/us", $page, $match);
        preg_match_all("/<tr.*?<\/tr>/us", $match[0][1], $trs);
        $trs = $trs[0];
        array_shift($trs);
        foreach ($trs as $tr) {
            preg_match_all("/<td.*?>(.*?)<\/td>/us", $tr, $tds);
            if(count($tds[1])==1) continue;

            preg_match('/href="(.*?)"/us', $tds[1][2], $naviLink);
            $navi = $browser->get($naviLink[1]);
            preg_match("/<table.*?class=\"navi-table\".*?<\/table>/us", $navi, $naviTable);

            $this->assertPattern("/<img/us", $naviTable[0], "画像がありません。");

            print $naviTable[0];
        }
    }
}
