<?php
/**
 * 印刷ページ
 */
define('_360_ANSWER_TIMEOUT',100000*1000000);
define('MODE_PRINT','1');
require_once 'index.php';

//動作確認モード　と左上に表示
print<<<HTML
<div id="already_f">####enq_already_finished####</div>
<style>

.delete_print_page
{
    display:none;
    visibility:hidden;
}

</style>
HTML;
exit;
