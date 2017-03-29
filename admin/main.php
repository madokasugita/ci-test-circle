<?php
/**
 * ようこそページ
 * お知らせページ
 */
//define('DEBUG', 0);
define('DIR_ROOT', "../");
require_once(DIR_ROOT.'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once(DIR_LIB.'MreAdminHtml.php');
require_once(DIR_LIB.'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFEventMail.php');
encodeWebAll();

$ADMIN_NEWS = getAdminNews();
$right = 30;
if (!empty($ADMIN_NEWS['mail_events'])) {
    foreach ($ADMIN_NEWS['mail_events'] as $me) {
        $date = date("Y年m月d日 h時i分", strtotime($me['mdate']));
        $cnd = mb_strimwidth($me['cndname'], 0, 35, "...",'utf8');
        $maillist .= <<<__JS__
    <table style="margin-right:{$right}px">
        <tr>
            <td class="td1">{$date}</td><td class="td1 cnd">{$cnd}</td>
        </tr>
        <tr>
            <td class="td2" colspan=2>　{$me['mfname']}</td>
        </tr>
    </table>
__JS__;
        $right -= 10;
    }
}
$right = 30;
foreach ($ADMIN_NEWS['term'] as $tm) {
    $date = date("Y年m月d日 h時i分", strtotime($tm['date']));
    $name = $tm['name'];
    $termlist .= <<<__JS__
    <table style="margin-right:{$right}px">
        <tr>
            <td class="td1">{$date}</td><td class="cnd"></td>
        </tr>
        <tr>
            <td class="td2" colspan="2">　{$name}</td>
        </tr>
    </table>
__JS__;
    $right -= 10;
}

$objHtml = new CbaseHtml(RESEARCH_TITLE);
$objHtml->addFileJs(DIR_IMG.'excanvas.min.js');
// $objHtml->addFileJs(DIR_IMG.'canvas.text.js');
$objHtml->addFileJs(DIR_IMG.'jquery-1.7.1.min.js');
//$objHtml->addFileJs(DIR_IMG.'jquery.Pngfix.js');
$objHtml->srcStyleCss = <<<__CSS__
    body{
        font-family:'ヒラギノ角ゴ Pro W3','Hiragino Kaku Gothic Pro','メイリオ',Meiryo,'ＭＳ Ｐゴシック',sans-serif;
        color:#778899;
    }
    h2{
        font-size:30px;
        color: #dadbdd;
        font-weight: normal;
        text-align:left;
        width: 600px;
        height: 30px;
        float: left;
        margin:0;
    }
    #title{
        -webkit-box-reflect: below -1em -webkit-linear-gradient(transparent 50%,rgba(0,0,0,0.4));
        width:100%;
        margin:200px 0 0 0;
        text-align:center;
        font-size:32px;
    }

    #admin_news_area{
        background-color:#f2f5f8;
        background-color:#f8fafc;

        margin-top:10px;
        padding:20px 0 0 0;
        text-align:center;
    }

    #admin_news_main_container{
        margin:0 auto;
        width:600px;
        height:350px;
        text-align:center;
        overflow:hidden;
        position:relative;
    }

    #admin_news_main_wrap{
        height:350px;
        width:1800px;
        margin:0;
        padding:0;
        left:0;
        top:0;
        position:relative;
    }

    .admin_news_main{
        width:600px;
        height:350px;
        text-align:center;
        float:left;
    }

    table{
        width:500px;
        float:right;
        border-collapse:separate;
        border-spacing:5px;
    }
    table td{
        font-size:14px;
        margin:0.8px;
        padding:2px 0 2px 5px;
        text-align:left;
        color: #CECECE;
    }
    table td.td1{
        background:#4088C0;
        color:white;
        border-radius: 3px 3px 0 0;
    }
    table td.td2{
        font-size:17px;

        color:#778899;

        background: rgb(255,255,255); /* Old browsers */
        background: -moz-linear-gradient(top,  rgba(255,255,255,1) 0%, rgba(246,246,246,1) 47%, rgba(240,240,240,1) 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,255,255,1)), color-stop(47%,rgba(246,246,246,1)), color-stop(100%,rgba(240,240,240,1))); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(top,  rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(240,240,240,1) 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(top,  rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(240,240,240,1) 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(top,  rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(240,240,240,1) 100%); /* IE10+ */
        background: linear-gradient(top,  rgba(255,255,255,1) 0%,rgba(246,246,246,1) 47%,rgba(240,240,240,1) 100%); /* W3C */
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ededed',GradientType=0 ); /* IE6-9 */

        -webkit-box-shadow: 0px 0px 3px 0px rgba(10, 10, 10, 0.2); /* Safari, Chrome用 */
        -moz-box-shadow: 0px 0px 3px 0px rgba(10, 10, 10, 0.2); /* Firefox用 */
        box-shadow: 0px 0px 3px 0px rgba(10, 10, 10, 0.2); /* CSS3 */

        border-radius: 3px;
    }
    table td.cnd{
        width:270px;
    }

    /* メニュー */
    #admin_news_menu_area{
        margin: 10px auto;
        width: 700px;
        height: 100px;
        z-index:1;
    }
    #admin_news_menu_area div.menu{
        width: 64px;
        height: 64px;
        margin-left: 140px;
        margin-top: 5px;
        float: left;
        cursor:pointer;

        opacity:0.2;
        filter: alpha(opacity=20);        /* ie lt 8 */
        -ms-filter: "alpha(opacity=20)";  /* ie 8 */
        -moz-opacity:0.2;                 /* FF lt 1.5, Netscape */
        -khtml-opacity: 0.2;              /* Safari 1.x */
        zoom:1;
    }

    #answer_rate_table{
        width:200px;
        position:absolute;
        bottom:50px;
        left:380px;
    }
    #answer_rate_table td{
        font-size:18px;
    }
    #answer_rate_table td.percent{
        font-size:24px;
        font-weight:bold;
        text-align:right;
    }

    .red{
        background:rgba(192, 80, 77, 0.6);
        background-color:#c2504d;
    }
    .green{
        background:rgba(155, 187, 89, 0.6);
        background-color:#86d6b0;
    }
    .purple{
        background:rgba(128, 100, 162, 0.6);
        background-color:#9760c9;
    }

    #admin_news_menu_area div.cursor{
        width: 74px;
        height: 74px;
        margin-left: 134px;

        position:absolute;

        background:gray;

        border-radius:5px;

        opacity:0.2;
        filter: alpha(opacity=20);        /* ie lt 8 */
        -ms-filter: "alpha(opacity=20)";  /* ie 8 */
        -moz-opacity:0.2;                 /* FF lt 1.5, Netscape */
        -khtml-opacity: 0.2;              /* Safari 1.x */
        zoom:1;
    }
__CSS__;

$objHtml->srcScriptJs = <<<__JS__
    var draw = function (callback) {

        var x = 165, y = 160;

        var canvas = document.getElementById("answerGraph");

        var cx = canvas.getContext("2d");
        var g = cx.createLinearGradient(100,100,200,400);
        // 始点に白、終点に黒を指定
        g.addColorStop(0,"white");
        g.addColorStop(1,"gray");
        // 塗りつぶしのスタイルとしてグラデーションをセット
        cx.fillStyle = g;

        var red = 'rgba(192, 80, 77, 0.7)'; //赤
        var green = 'rgba(155, 187, 89, 0.7)'; //緑
        var purple = 'rgba(128, 100, 162, 0.7)';//紫
        var white = 'rgba(255, 255, 255, 0.8)';// 白

        cx.shadowBlur = 3;
        cx.shadowColor = "gray";

        cx.beginPath();
        cx.arc(x, y, 130, 0, 360 * Math.PI / 180, false);
        cx.closePath();
        cx.fill();

        cx.shadowBlur = 0;
        cx.shadowColor = "transparent";

        /* 回答済み */
        if (answer_rate.done > 0) {

            cx.beginPath();
            cx.fillStyle = red;
            cx.moveTo(x, y);
            cx.arc(x, y, 130, -90 * Math.PI /180, (360*answer_rate.done/100 - 90) * Math.PI / 180, false);
            cx.closePath();
            cx.fill();

        }

        /* 回答中 */
        if (answer_rate.ing > 0) {

            cx.beginPath();
            cx.fillStyle = green;
            cx.moveTo(x, y);
            cx.arc(x, y, 130, (360*answer_rate.done/100 - 90) * Math.PI / 180, (360*(answer_rate.ing + answer_rate.done)/100 - 90) * Math.PI / 180, false);
            cx.closePath();
            cx.fill();

        }

        /* 未回答 */
        if (answer_rate.not > 0) {

            cx.beginPath();
            cx.fillStyle = purple;
            cx.moveTo(x, y);
            if(answer_rate.not == 100)
                cx.arc(x, y, 130, 0, 360 * Math.PI / 180, false);
            else
                cx.arc(x, y, 130, (360*(answer_rate.ing + answer_rate.done)/100 - 90) * Math.PI / 180, -90 * Math.PI / 180, false);
            cx.closePath();
            cx.fill();

        }

        if(callback) callback();
    }

    var answer_rate = {
        not: {$ADMIN_NEWS['answer_percent20']},
        ing: {$ADMIN_NEWS['answer_percent10']},
        done: {$ADMIN_NEWS['answer_percent0']}
    }

    var wrap;
    var menus;
    var cursor = 0;
    $(function () {
        wrap = $("#admin_news_main_wrap");
        menus = $("#admin_news_menu_area div.menu");

        //IE6以下には表示しない
        if (typeof document.documentElement.style.maxHeight == "undefined") return;

        $("#title").animate({'margin-top': '15px'}, 500, function () {
            $("#admin_news_area").fadeIn(500, function () {
                $(".cursor").css('left', $(menus[cursor]).position().left).show();
            });
            draw();
        });

        $(window).resize(function () {
            $(".cursor").css('left', $(menus[cursor]).position().left);
        });

        $(menus).each(function (i,menu) {
            $(menu).click(function () {
                $(wrap).stop().animate({left: '-' + i*600 + 'px'}, 'fast', 'linear');
                $(".cursor").animate({left:$(this).position().left});
                cursor = i;
            });
        });

    });
__JS__;

$IMG = DIR_IMG;
$answer_percent0 = round($ADMIN_NEWS['answer_percent0']);
$answer_percent10 = round($ADMIN_NEWS['answer_percent10']);
$answer_percent20 = round($ADMIN_NEWS['answer_percent20']);

$html = <<<__HTML__
<div id='title'>
    <img src="{$IMG}smartreview_logo2.jpg" alt="smart review" width="400px">
</div>
<div id="admin_news_area" style="display:none;">
    <div id="admin_news_main_container">
    <div id="admin_news_main_wrap">
        <div class="admin_news_main">
            <h2>回答率</h2>
            <canvas id="answerGraph" width="300" height="350" style="float:left"></canvas>
            <div style="float:left; width:300; height: 350">
                <table id="answer_rate_table">
                    <tr><td class="red"></td><td>回答済み</td><td class="percent">{$answer_percent0} <small>%</small></td></tr>
                    <tr><td class="green"></td><td>回答中</td><td class="percent">{$answer_percent10} <small>%</small></td></tr>
                    <tr><td class="purple"></td><td>未回答</td><td class="percent">{$answer_percent20} <small>%</small></td></tr>
                </table>
            </div>
        </div>
        <div class="admin_news_main">
            <h2>配信予約</h2>
            <br><br>
            {$maillist}
        </div>
        <div class="admin_news_main">
            <h2>スケジュール</h2>
            <br><br>
            {$termlist}
        </div>
    </div>
    </div>
    <div style="clear:both"></div>

    <div id="admin_news_menu_area">
        <div class="cursor" style="display:none;"></div>
        <div class="menu"><img src="{$IMG}64_pie_graph.png"></div>
        <div class="menu"><img src="{$IMG}64_email.png"></div>
        <div class="menu"><img src="{$IMG}64_map.png"></div>
    </div>

</div>
__HTML__;

echo $objHtml->getMainHtml($html);
exit;

function termCmp($a, $b)
{
    if ($a[3] == $b[3]) {
        return 0;
    }

    return ($a[3] < $b[3]) ? -1 : 1;
}

function getAdminNews()
{
    //キャッシュがない、または5分以上経過していたら作成
    if (!file_exists(FILE_ADMIN_NEWS_CACHE) || (time() - filemtime(FILE_ADMIN_NEWS_CACHE) > 5 * 60)) {
        $ADMIN_NEWS = array();

        //回答率
        $user_type_count = INPUTER_COUNT + 1;
        $SQL = <<<__SQL__
SELECT count(*) as count,answer_state FROM (
SELECT * FROM
(SELECT email,serial_no,uid,name,div1,div2,div3,0 as user_type ,uid as target FROM usr WHERE mflag = 1 AND test_flag != 1) as dummy
UNION ALL
(SELECT u.email,u.serial_no,u.uid,u.name,u.div1,u.div2,u.div3,r.user_type,r.uid_a as target from usr_relation r LEFT JOIN usr u on r.uid_b = u.uid WHERE u.test_flag != 1) ) as u1
LEFT JOIN usr u2 on u1.target = u2.uid
LEFT JOIN event_data ev on ev.evid = u2.sheet_type*100+user_type and ev.serial_no = u1.serial_no and ev.target = u2.serial_no
WHERE user_type < {$user_type_count} group by answer_state
__SQL__;

        foreach (FDB :: getAssoc($SQL) as $data) {
            if ($data['answer_state'] === null || $data['answer_state']<0) {
                $data['answer_state'] = 20;
            }
            $ADMIN_NEWS['answer_state'][$data['answer_state']] = $data['count'];
        }

        //回答率計算
        $ADMIN_NEWS['answer_sum'] = $ADMIN_NEWS['answer_state'][20]+$ADMIN_NEWS['answer_state'][10]+$ADMIN_NEWS['answer_state'][0];
        $ADMIN_NEWS['answer_percent20'] = ($ADMIN_NEWS['answer_sum'])? sprintf("%01.1f", ($ADMIN_NEWS['answer_state'][20] / $ADMIN_NEWS['answer_sum'] * 100)) : 0;
        $ADMIN_NEWS['answer_percent10'] = ($ADMIN_NEWS['answer_sum'])? sprintf("%01.1f", ($ADMIN_NEWS['answer_state'][10] / $ADMIN_NEWS['answer_sum'] * 100)) : 0;
        $ADMIN_NEWS['answer_percent0']  = ($ADMIN_NEWS['answer_sum'])? sprintf("%01.1f", ($ADMIN_NEWS['answer_state'][0] / $ADMIN_NEWS['answer_sum'] * 100)) : 0;

        //メール予約
        $_mail_events = Get_MailEvent(-1, '', 'mdate', '', $_SESSION['muid']);
        foreach ($_mail_events as $mail_event) {
            if($mail_event['flgs'] != 0)
                continue;

            $ADMIN_NEWS['mail_events'][] = $mail_event;
            if(count($ADMIN_NEWS['mail_events']) > 3)
                break;
        }

        //回答期間
        $datas = array ();
        $now = strtotime(date("Y/m/d H:i:s"));
        foreach (getFromtoArray() as $term_data) {
            if($now < strtotime($term_data['sdate']))
                $datas[] = array($term_data['evid'], $term_data['type'], 's', strtotime($term_data['sdate']));
            if($now < strtotime($term_data['edate']))
                $datas[] = array($term_data['evid'], $term_data['type'], 'e', strtotime($term_data['edate']));
        }

        usort($datas, "termCmp");
        $events = getAllEventArray();

        for ($i = 0; $i < 4; $i++) {
            $name = "";
            foreach ($events as $e) {
                if($e['evid'] != $datas[$i][0] || $e['type'] != $datas[$i][1])
                    continue;

                $name = $e['name'];
                break;
            }
            $name .= ($datas[$i][2] == 's')? " 開始":" 終了";

            $ADMIN_NEWS['term'][] = array(
                'date' => date("Y/m/d H:i", $datas[$i][3]),
                'name' => $name
            );
        }

        file_put_contents(FILE_ADMIN_NEWS_CACHE, serialize($ADMIN_NEWS));
    } else {
        $ADMIN_NEWS = unserialize(file_get_contents(FILE_ADMIN_NEWS_CACHE));
    }

    return $ADMIN_NEWS;
}
