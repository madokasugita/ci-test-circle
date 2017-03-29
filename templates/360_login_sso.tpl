{include file="header.tpl"}
<body id="page_login" onload="document.getElementById('id').focus();">
{*言語切り替えバー*}
{$languagebar}

{*ロゴ*}
<div id="top"><img src="{$dir_user}logo.png" border="0"></div>

{*メインタイトルの表示*}
<h1>{$text.main_title} {$text.login_title}</h1>

{*お知らせ(お知らせがあればタグも表示)*}
{if $text.login_infomation != ''}<pre class="infomation">{$text.login_infomation}</pre>{/if}

{if $errors }
<ul class="errors">
{foreach from=$errors item=error}
    <li class="error">{$error}</li>
{/foreach}
</ul>
{/if}

<script>
window.close();
</script>

{include file="footer.tpl"}