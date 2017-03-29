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

<form action="360_login.php" method="POST" onSubmit="return this.flag?false:this.flag=true;">
<input type="hidden" name="mode" value="login">
	<div id="login-form">
		<table>
		<tr>
			<th>
				{$text.login_id} ：
			</th>
			<td>
				<input id="id" name="id" class="form-top-login" type="text" value="{$post_id}">
			</td>
		</tr>
		<tr>
			<th>
				{$text.login_pw} ：
			</th>
			<td>
				<input id="pw" name="pw" class="form-top-login" maxlength="15" type="password" value="{$post_pw}">
			</td>
		</tr>
		</table>
	</div>
	<div id="login-form-button"><input type="submit" name="mode:login" value="{$text.login_button}"></div>
</form>

{*パスワードを忘れた人用リンク*}
<div id="passwd_reissue"><a href="passwd_reissue.php" target="_blank">{$text.login_forgotpw}</a></div>




{include file="footer.tpl"}