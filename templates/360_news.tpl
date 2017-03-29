{include file="header.tpl"}
<body id="page_mypage">
{*言語切り替えバー*}
{$languagebar}

{*ロゴ*}
<div id="top"><img src="{$dir_user}logo.png" border="0"></div>

{*ログインユーザの情報*}
<div id="userinfo">{$userinfo.uid} : {$userinfo.name}</div>

{*ログアウトボタン*}
<div id="logout"><a href="{$link.logout}">{$text.mypage_logout}</a></div>

{*メインタイトルの表示*}
<h1>{$text.main_title} {$text.news_title}</h1>
<div style="background-color:#f5f5f5;width:900px;padding:15px;margin:auto;text-align:center;">
<pre style="width:600px;text-align:left;margin:auto;font-size:15px;">{$text.news_body}</pre>

<div style="background-color:#e6e6e6;border:1px solid black;width:600px;text-align:left;margin:auto;">
<form action="" method="POST" style="display:inline;">
<input type="radio" name="news_flag" value="0" id="news_flag0"{if !$userinfo.news_flag} checked{/if}>
<label for="news_flag0">{$text.news_flag0}</label><br />
<input type="radio" name="news_flag" value="1" id="news_flag1"{if $userinfo.news_flag} checked{/if}>
<label for="news_flag1">{$text.news_flag1}</label><br/>
<div style="color:#6e6e6e;">{$text.news_flag_notes}</div>
<div style="text-align:center"><input type="submit" name="mypage" value="マイページへ移動する"></div>
</form>
</div>
</div>
{include file="footer.tpl"}