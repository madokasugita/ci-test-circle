<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="ja">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=shift-jis">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Script-Type" content="text/javascript">
<title>スマートレビュー　結果シート　{ $self.name }</title>
<style>
{literal}
#container {
	width:950px; /* 全体幅を指定 */
	margin:50px auto;
	padding:0;
	text-align:left;
	display:table; /* FF 印刷用 */
}

.main {
	margin:0;
	padding:0;
	display:table-cell; /* FF 印刷用 */
	float:none;         /* FF 印刷用 */
	vertical-align:top; /* FF 印刷用 */
	/zoom:1;            /* IE6,7 印刷用 */
}


body {
	color: #000000;
	margin:0;
	padding:0;
	text-align:center;
	font-family: メイリオ, Meiryo, 'ＭＳ Ｐゴシック';
	background-color:#ffffff;
	font-size:12px;
	/zoom:0.7; /* IE6,7 印刷用 */
}

a {
color:#33cc66;
text-decoration: none;
}



h1 {
	font-size:18px;
	padding:10px;
	background-color:#CFE2F3;
	color:#3D85C6;
	text-align:left;
        border:solid 1px #3D85C6;
}


h2 {
	font-size:16px;
	margin:15px 0px;
	padding:5px;
	background-color:#ffffff;
	color:#3D85C6;
	text-align:left;
	border-bottom:1px solid #3D85C6;
}



h3 {
	font-size:14px;
	margin:20px 0 0 10px;
	padding:0;
	background-color:#ffffff;
	color:#3D85C6;
	text-align:left;
}

table {
width:100%;
border-collapse:collapse;
}

td {
padding:5px;
border:1px solid #3D85C6;
font-size:12px;
text-align:left;
}

td.category{
    	color:#000000;
}

td.cont-q{
    	color:#000000;
}


table.vTable td{
    padding:0 5px 0 5px;
    /*text-align:center;*/
}
td .fix-q{
height:35px;
width:350px;
overflow: hidden;
text-overflow: ellipsis;
-webkit-text-overflow: ellipsis;
-o-text-overflow: ellipsis;
}

td.check{
    text-align:center;
}

th {
padding:5px;
border:1px solid #3D85C6;
background-color:#EDEEF2;
text-align:center;
font-weight:normal;
font-size:12px;
	color:#3D85C6;
}

.exfix{
    width:100%;
    top:0;
    left:0;
    position:fixed;
    text-align:center;
    z-index:100;
}

.exfix #bar{
    height:auto;
    width:950px;
    padding:5px 0 5px 0;
    margin: 0 auto;
    text-align: left;
    border-bottom:#478bcc 1px solid;
    background-color:rgb(250, 250, 250);
    
    -webkit-box-shadow: 0 5px 6px -4px rgba(000,000,000,0.1);
        -moz-box-shadow: 0 5px 6px -4px rgba(000,000,000,0.1);
        box-shadow: 0 5px 6px -4px rgba(000,000,000,0.1);
}

.btn {
    
    background: #478bcc;
	background: -moz-linear-gradient(top,#0099CC 0%,#006699);
	background: -webkit-gradient(linear, left top, left bottom, from(#0099CC), to(#006699));
	border: 2px solid #FFF;
	color: #FFF;
	border-radius: 4px;
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
	-moz-box-shadow: 1px 1px 1px rgba(000,000,000,0.3);
	-webkit-box-shadow: 1px 1px 1px rgba(000,000,000,0.3);
	text-shadow: 0px 0px 3px rgba(0,0,0,0.5);
	padding: 10px 0;
    background-color: #478bcc;
    background-repeat:repeat-x;
    cursor:pointer;
}

.btn:hover,
.btn:focus {
  color: #333333;
  text-decoration: none;
  background-position: 0 -15px;
  -webkit-transition: background-position 0.1s linear;
     -moz-transition: background-position 0.1s linear;
       -o-transition: background-position 0.1s linear;
          transition: background-position 0.1s linear;
}

.btn:focus {
  outline: thin dotted #333;
  outline: 5px auto -webkit-focus-ring-color;
  outline-offset: -2px;
}

.btn.active,
.btn:active {
  background-image: none;
  outline: 0;
  -webkit-box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.05);
     -moz-box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.05);
          box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.15), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.btn.disabled,
.btn[disabled] {
  cursor: default;
  background-image: none;
  opacity: 0.65;
  filter: alpha(opacity=65);
  -webkit-box-shadow: none;
     -moz-box-shadow: none;
          box-shadow: none;
}

.btn:hover, .btn:focus, .btn:active, .btn.active, .btn.disabled, .btn[disabled] {
    color: #ffffff;
    background-color: #006699;
}

.button_div .btn{
    width:250px;
}

.exfix .btn{
    padding:5px;
}

/* ここから個別指定　*/

#canvas2 {
    width:200px;
    overflow:hidden;
    text-align:center;
    margin:1px;
}

.profile {
width:300px;
margin:80px auto 80px auto;
}


.skill th{
text-align:left;
}

.skill td{
width:60%;
}

.explanation {
}

.explanation th{
width:15%;
}

.data {
}

.data tr{
}


.data th{
}

.data td{
line-height: 10px;
}

.data .data1 {
width:15%;
}

.data .data2 {
width:25%;
}

.data .data3 {
width:60%;
}

.comment_table tr{
    min-height:0px;
}

.comment1{
background-color:#CFE2F3;
    color:#3D85C6;
}

.small-table{

}

.small-table th{

    background-color:#EDEEF2;
    
}

.h2_advice1{
    color:#6AA84F;
    border-bottom:1px solid #6AA84F;
}

.h3_adviece1{
    color:#6AA84F;    
}

.advice1{
    border:0px;
}

.advice1 th{   
}

.advice1 tr{   
}

.advice1 td{
    border:0px;
    background-color:#D9EAD3;
    font-size:14px;
    }

.advice2{
    border:0px;
}

.advice2 th{   
}

.advice2 tr{   
}

.advice2 td{
    padding:0px 10px ;
    border:0px;
    }

.advice2 td .span1 {
    font-weight:bold;
    font-size:14px;
}


.criteria{
}

.criteria th{   
    background-color:#D9EAD3;
    border:1px solid #6AA84F;
    color:#6AA84F;

}

.criteria tr{   
}

.criteria td{
    border:1px solid #6AA84F;
}

.ca_title{
    width:150px;
    height:40px;
}
.ca_value,
.num{
    text-align:center;
}

.onlyprint{
display:none;
visibility:hidden;
}

.cat1color0{background-color:#f0efe7}
.cat1color1{background-color:#f0efe7}
.cat1color2{background-color:#f0efe7}
.cat1color3{background-color:#f0efe7}
.cat1color4{background-color:#f0efe7}
.cat1color5{background-color:#fff3b8}
.cat1color6{background-color:#a1d8e2}
.cat1color7{background-color:#a3d6cc}
.cat1color8{background-color:#fff3b8}
.cat1color9{background-color:#a1d8e2}
.cat1color10{background-color:#a3d6cc}
.cat1color11{background-color:#fff3b8}
.cat1color12{background-color:#a1d8e2}
.cat1color13{background-color:#a3d6cc}
.cat1color14{background-color:#fff3b8}
.cat1color15{background-color:#a1d8e2}

.cat2color0{background-color:#ffffff}
.cat2color1{background-color:#ffffff}
.cat2color2{background-color:#ffffff}
.cat2color3{background-color:#ffffff}
.cat2color4{background-color:#ffffff}
.cat2color5{background-color:#ffffff}
.cat2color6{background-color:#ffffff}
.cat2color7{background-color:#ffffff}
.cat2color8{background-color:#ffffff}
.cat2color9{background-color:#ffffff}
.cat2color10{background-color:#ffffff}
.cat2color11{background-color:#ffffff}
.cat2color12{background-color:#ffffff}
.cat2color13{background-color:#ffffff}
.cat2color14{background-color:#ffffff}
.cat2color15{background-color:#ffffff}
.cat2color16{background-color:#f0efe7}
.cat2color17{background-color:#d8d7cf}
.cat2color18{background-color:#f0efe7}
.cat2color19{background-color:#d8d7cf}
.cat2color20{background-color:#f0efe7}
.cat2color21{background-color:#d8d7cf}
.cat2color22{background-color:#f0efe7}
.cat2color23{background-color:#d8d7cf}
.cat2color24{background-color:#f0efe7}
.cat2color25{background-color:#d8d7cf}

@media print {
.noprint { display:none }

.onlyprint{
	display:block;
	visibility:visible;
	}

.span1 {
    font-weight:bold;
        }    
}

.category1 {
    width:130px;
      }

.category2 {
    width:100px;
      }



</style>
{/literal}

{$require_javascript}
</head>
<body>

<div class="exfix" class="noprint">
<div id="bar" class="noprint">
<input type="submit" value="メニューに戻る" class="btn" onclick="location.href='{$menu_link}'; return false;">
<input type="submit" value="印刷" class="btn" onclick="window.print();return false;">
<input type="submit" value="PDF1で保存" class="btn" onclick="location.href='{$dl_url_1}'; return false;">
<input type="submit" value="PDF2で保存" class="btn" onclick="location.href='{$dl_url_2}'; return false;">
</div>
</div>

<div id="container">
<div class="main">

<h1>2013年度上期　360度評価 結果レポート</h1>

<table class="small-table">
<tr><th>ID</th><td> { $self.uid } </td><th>所属</th><td> { $self.div1 } </td><th>役職</th><td>{ $self.class }</td><th>氏名</th><td>{ $self.name }</td><th>回答人数</th><td>{$count.others}人</td></tr>
	
</table>

<h2 class="h2_advice1">結果の見方</h2>
<table class="advice1">
<tr><td>
360度サーベイは、複数人の視点から対象者を観察し、日頃の行動についてをフィードバックすることで、
対象者の成長を促進するものです。<br />
周囲からのフィードバックを前向きに受け止め、自身の行動変革に結びつけましょう。<br />
</td></tr></table>
<br />

<table class="advice2">
<tr><td>
<span class="span1">着目ポイント1：他者回答のうち、結果が高い項目・低い項目はどれですか？</span><br />
それらの項目が、周囲から見たあなたの強み・弱みです。まずは周囲から見たあなたの仕事ぶりについて認識しましょう。<br />
<br />
<span class="span1">着目ポイント2：本人回答と他者回答を比較し、ギャップが大きくでている項目はどれですか？</span><br />
そのような認識の差が出ているのはなぜだと思いますか？自身の行動について振り返ってみましょう。<br />
<br />
<span class="span1">着目ポイント3：上司回答・部下回答・同僚回答とを比較し、広く差がある項目はありませんか？</span><br />
部下回答だけ結果が高かったり、上司回答だけ回答が低かったりするような項目はありませんか？その要因について一度考えてみましょう。<br />
<br />
<span class="span1">着目ポイント4：他者が感じている、あなたの成長にとって重要と思う項目を確認しましょう。</span><br />
ご自身の認識とのずれはありませんか？周囲の方はなぜそのように感じていると思いますか？<br />
<br />
<span class="span1">着目ポイント5：フリーコメントを確認し、具体的なアクションプランを検討しましょう。</span><br />
改善要望等、なかには受け止めづらい内容も含まれているかもしれません。<br />
しかしこれらはあなたが次のステップへ進むための、周囲からの大事なメッセージです。<br />
是非前向きな気持ちで受け止め、明日からの行動改善に生かして下さい。</td></tr>

</table>

<h3 class="h3_adviece1">回答基準</h3>

<table class="criteria">
<tr><th>5</th><td>十分</td></tr>
<tr><th>4</th><td>どちらかといえば十分</td></tr>
<tr><th>3</th><td>どちらともいえない</td></tr>
<tr><th>2</th><td>どちらかといえば不十分</td></tr>
<tr><th>1</th><td>不十分</td></tr>
<tr><th>N/A</th><td>わかりません</td></tr>
</table>


<h2>I　カテゴリ別　集計結果</h2>

<div id="canvas" style="width:550px;height:350px;float:left"></div>
<script>var rdata = [{$category_json[1][0]}, {$category_json[1].others}]</script>
{literal}
<script>
var g = smartGraph;
g.config({
    label_tag : ["本人", "他者"],
    label_attr : [
        {stroke:"#FF0000", fill:"#F07070", "fill-opacity":0.2,"stroke-width":2},
        {stroke:"#4AACC5", fill:"#4AACC5", "fill-opacity":0.2, "stroke-width":2},
        {stroke:"#04B052", fill:"#04B052", "fill-opacity":0.2,"stroke-width":2},
        {stroke:"#00AFEF", fill:"#00AFEF", "fill-opacity":0.2,"stroke-width":2},
        {stroke:"#FFC000", fill:"#FFC000", "fill-opacity":0.2,"stroke-width":2},
        {stroke:"#000000", fill:"#ae4ccd", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"."},
        {stroke:"#000000", fill:"#76766e", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"-.."},
        {stroke:"#000000", fill:"#ed4bb2", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":". "},
        {stroke:"#5bdb5d", fill:"#5bdb5d", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"- ."},
        {stroke:"#bc0840", fill:"#bc0840", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"--."},
        {stroke:"#f0590a", fill:"#f0590a", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"--.."}
    ],
    gantt_attr : [{"stroke-width":0, fill:"#DBEDF4", stroke:"#DBEDF4"}],
    gantt_attr_underestimate: [{"stroke-width":0, fill:"#DBEDF4", stroke:"#DBEDF4"}],
    gantt_attr_overestimate : [{"stroke-width":0, fill:"#ffdddd", stroke:"#ffdddd"}],
    label_color: "#3D85C6",
    legend_color: "#3D85C6"
});

g.init(document.getElementById("canvas"));
g.rader({
	label:["課題思考","課題遂行","コミュニケーション","組織貢献","ベースマインド "],
	data:rdata,
	step:5
});
</script>
{/literal}

<table class="small-table" style="width:380px;float:left;">
<tr><th></th><th>本人</th><th>他者</th><th>上司</th><th>部下</th><th>同僚</th></tr>

{foreach from=$category[1][0] key=num item=ca}
<tr>
<th class="ca_title">{$num}</th>
<td class="ca_value">{if $category[1][0][$num]}{$category[1][0][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
<td class="ca_value">{if $category[1].others[$num]}{$category[1].others[$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
<td class="ca_value">{if $category[1][1][$num]}{$category[1][1][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
<td class="ca_value">{if $category[1][2][$num]}{$category[1][2][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
<td class="ca_value">{if $category[1][3][$num]}{$category[1][3][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
</tr>
{/foreach}
</table>
<div style="clear:both"></div>




<!-- 改ページここから -->
<div style="page-break-before:always;font-size:1;margin:0;border:0;"><span style="visibility: hidden;"> </span></div>
<!-- 改ページここまで -->


<div class="onlyprint">
<table class="small-table">
<tr><th>ID</th><td> { $self.uid } </td><th>所属</th><td> { $self.div1 } </td><th>役職</th><td>{ $self.class }</td><th>氏名</th><td>{ $self.name }</td><th>回答人数</th><td>{$count.others}人</td></tr>
	
</table>

</div>

<h2>II　項目別　集計結果　<span style="font-size:12px;">※グラフをクリックすることで、表示を「本人/上司/部下/同僚」⇔「本人/他者」と切り替え可能です。</span></h2>

<table class="vTable">
<tr><th colspan="4"></th>
<th width="40px;">本人<br /><span style="color:#FF0000;font-weight:bold;">○</span></th>
<th width="40px;">他者</th>
<th width="40px;">上司<br /><span style="color:#04B052;font-weight:bold;">□</span></th>
<th width="40px;">部下<br /><span style="color:#00AFEF;font-weight:bold;">△</span></th>
<th width="40px;">同僚<br /><span style="color:#FFC000;font-weight:bold;">×</span></th>
<!-- 最大値・最小値
<th width="40px;">最大</th>
<th width="40px;">最小</th>
-->
<th>1　　2　　3　　4　　5</th>
{foreach from=$subevents_choice key=num item=subevent}
    <tr>
    {if $subevent.rowspan1}<td class="category1" rowspan="{$subevent.rowspan1}">{$subevent.category1}</td>{/if}
    {if $subevent.rowspan2}<td class="category2" rowspan="{$subevent.rowspan2}">{$subevent.category2}</td>{/if}
    <td class="num">{$subevent.num_ext}</td>
    <td class="cont-q"><div class="fix-q">{$subevent.title}</div></td>
    <td class="check">{if $average[0][$num]}{$average[0][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
    <td class="check">{if $average.others[$num]}{$average.others[$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
    <td class="check">{if $average[1][$num]}{$average[1][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
    <td class="check">{if $average[2][$num]}{$average[2][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
    <td class="check">{if $average[3][$num]}{$average[3][$num]|round:2|string_format:'%.2f'}{else}-{/if}</td>
<!-- 最大値・最小値
    <td class="check">{if $MaxData.others[$num] != ""}{$MaxData.others[$num]}{else}-{/if}</td>
    <td class="check">{if $MinData.others[$num] != ""}{$MinData.others[$num]}{else}-{/if}</td>
-->

  {if $num eq 1}
    <td rowspan={$subevents_choice|@count} style="vertical-align:top;padding:0">
        <div id="canvas2"></div>
    </td>
  {/if}

  </tr>
{/foreach}
</table>

<script>
var data = [{$average_json[0]}, [], {$average_json[1]}, {$average_json[2]}, {$average_json[3]}];
var data2 = [{$average_json[0]}, {$average_json.others}, [], [], []];
</script>

{literal}
<script>
g.init(document.getElementById("canvas2"));
var a = g.verticalLine({
	data:data,
	data2:data2,
	step:5,
	lineHeight:36
});
$("#canvas2").on("click", function(){a.toggle()});

var big = 4.5;
var bigColor = "#CFE2F3";
var small = 2.5;
var smallColor = "#F4CCCC"

$(".check").each(function(){
if(!this.firstChild || !this.firstChild.nodeValue)
    return true;

if(big <= this.firstChild.nodeValue)
  this.style.background = bigColor;
if(small >= this.firstChild.nodeValue)
  this.style.background = smallColor;

});
</script>
{/literal}

<!-- 改ページここから -->
<div style="page-break-before:always;font-size:1;margin:0;border:0;"><span style="visibility: hidden;"> </span></div>
<!-- 改ページここまで -->

<div class="onlyprint">

<table class="small-table">
<tr><th>ID</th><td> { $self.uid } </td><th>所属</th><td> { $self.div1 } </td><th>役職</th><td>{ $self.class }</td><th>氏名</th><td>{ $self.name }</td><th>回答人数</th><td>{$count.others}人</td></tr>
	
</table>

</div>
<h2>III　コメント結果</h2>

{foreach from=$subevents_other key=num item=title}

{if $num == 31}
<h3>1.対象者の強みだと感じていることについて自由に記入してください。</h3>
{elseif $num == 32}
<h3>2.対象者にとって今後の課題と感じていることについて自由に記入して下さい。</h3>
{else}
<h3>{$title}</h3>
{/if}

<table class="comment_table">
    <tr><td class="comment1">本人回答</td></tr>
    <tr><td>
        {$comments[0][$num][0]|nl2br}
    </td></tr>
    <tr><td class="comment1">他者回答</td></tr>
        {foreach from=$comments.others[$num] key=key item=other_answer}
        {if $other_answer|trim != ""}
            <tr><td>
                {$other_answer|nl2br}
            </td></tr>
        {/if}
        {/foreach}
</table>

{/foreach}

</div><!-- main -->
</div><!-- container -->

<br>
<br>

<!-- セル結合js -->
{literal}
<script>
/*
//<!--

var catNum, str1, str2;
var $category1 = $(".category1");
var $category2 = $(".category2");

catNum = 0;
$category1.each(function(i, c){
    $(c).addClass("cat1color" + catNum);

    str1 = $(c).text();
    str2 = $category1.eq(i+1).text();
    
    if(str1==str2){
	$category1.eq(i+1).addClass("vanish");
    }else{
	catNum++;
    }
});

catNum = 0;
$category2.each(function(i, c){
    $(c).addClass("cat2color" + catNum);

    str1 = $(c).text();
    str2 = $category2.eq(i+1).text();

    if(str1==str2){
	$category2.eq(i+1).addClass("vanish");
    }else{
	catNum++;
    };
});

$(".vanish").css("border-top", "hidden");
$(".vanish").text("");

//-->
*/
</script>
{/literal}

</body>
</html>
