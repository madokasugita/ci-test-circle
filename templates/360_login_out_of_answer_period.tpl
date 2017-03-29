{include file="header.tpl"}
<body id="page_login" onload="document.getElementById('id').focus();">
{*言語切り替えバー*}
{$languagebar}

{*ロゴ*}
<div id="top"><img src="{$dir_user}logo.png" alt="logoimage"></div>

{*メインタイトルの表示*}
<h1>{$text.main_title} {$text.login_title}</h1>

{*お知らせがあればお知らせを表示*}
{if $text.login_infomation }
<pre class="infomation">{$text.login_infomation}</pre>
{/if}

{*エラーがあればエラー表示*}
{if $errors }
<ul class="errors">
{foreach from=$errors item=error}
    <li class="error">{$error}</li>
{/foreach}
</ul>
{/if}

{*期間外*}
<div>{ $text.out_of_answer_period }</div>

{*パスワードを忘れた人用リンク*}
<!--<div id="passwd_reissue"><a href="passwd_reissue.php" target="_blank">{$text.login_forgotpw}</a></div>-->




{include file="footer.tpl"}