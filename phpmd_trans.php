<?php
//$file = fopen("phpmd_result.xml", "r");
//$file = fopen("log/phpmd_result_201701241721.log", "r");
$file = fopen($argv[1], "r");

if ($file) {
    $first_flg = 0;
    $before_file = "";
    echo <<< HTML
<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<checkstyle version="2.7.1">

HTML;
    while ($line = fgets($file)) {
        if (preg_match("/<error\sfilename=\"(.*)\"\smsg=\"(.*)line:\s([0-9]*),\scol:\s([0-9]*).*/", $line, $matches)) {
            if ($before_file != $matches[1]) {
                if (!$first_flg) {
                    $first_flg = 1;
                    echo <<< HTML
<file name="{$matches[1]}">

HTML;
                }
                echo <<< HTML
</file>
<file name="{$matches[1]}">

HTML;
            }
            echo <<< HTML
 <error line="{$matches[3]}" column="{$matches[4]}" severity="error" message="{$matches[2]}" source="PHP Mess Detector"/>

HTML;
            $before_file = $matches[1];
        }
    }
    if ($first_flg) {
        echo <<< HTML
</file>

HTML;
    }
    echo <<< HTML
</checkstyle>

HTML;
}

fclose($file);
