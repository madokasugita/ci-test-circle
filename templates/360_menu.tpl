{include file="header.tpl"}
<body id="page_mypage">
{*言語切り替えバー*}
{$languagebar}

{*ロゴ*}
<div id="top"><img src="{$dir_user}logo.png" border="0">

{*ログインユーザの情報とログアウトボタン*}
<div id="userinfo">{$userinfo.uid} : {$userinfo.name}({$userinfo.name_})　<a href="{$link.logout}">{$text.mypage_logout}</a></div>

</div>

{*メインタイトルの表示*}
<h1>{$text.main_title}</h1>

{*お知らせページへのリンク*}
<!-- <div id="news"><a href="{$link.news}">{$text.news_title}</a></div> -->

{*最新の情報に更新ボタン*}
<div id="refresh">
<form action="{$link.refresh}" style="display:inline" method="post">
<input type="hidden" name="reflesh" value="1">
<input type="submit" value="{$text.mypage_button_refresh}"></form>
</div>

{*お知らせ(お知らせがあればタグも表示)*}
{if $text.mypage_infomation != ''}<pre class="infomation">{$text.mypage_infomation}</pre>{/if}

{*代理ログインの場合に表示*}
{if $admin_flag}
<div style="padding:5px;border:dotted 2px black;margin:10px auto;width:930px;background-color:#f2f2f2;color:red;">
代理ログイン中です。<br>
表示期間設定に関わらず、常に全てのリンクが表示されています。
</div>

{/if}


<table id="main_table">
<tr>
<td align=middle bgcolor="#ffffff">
{include file="360_menu_sheet.tpl" sheet_type='self'}
{include file="360_menu_sheet.tpl" sheet_type='admit'}
{include file="360_menu_sheet.tpl" sheet_type='select'}
{include file="360_menu_sheet.tpl" sheet_type=1}
{include file="360_menu_sheet.tpl" sheet_type=2}
{include file="360_menu_sheet.tpl" sheet_type=3}
{include file="360_menu_sheet.tpl" sheet_type='review'}
{include file="360_menu_sheet.tpl" sheet_type='fb'}
</td>
</tr>
</table>
{include file="footer.tpl"}