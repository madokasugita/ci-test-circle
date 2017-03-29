{include file="header.tpl"}
<body id="page_login" onload="document.getElementById('id').focus();">
{*言語切り替えバー*}
{$languagebar}

{*ロゴ*}
<div id="top"><img src="{$dir_user}logo.png" alt="logoimage"></div>

{*メインタイトルの表示*}
<h1>{$text.main_title}</h1>

{*お知らせがあればお知らせを表示*}
{if $text.pw_reissue_info }
<pre class="infomation">{$text.pw_reissue_info}</pre>
{/if}

{*エラーがあればエラー表示*}
{if $errors }
<ul class="errors">
{foreach from=$errors item=error}
    <li class="error">{$error}</li>
{/foreach}
</ul>
{/if}

<form action="" method="POST" onSubmit="return this.flag?false:this.flag=true;">
	<div id="login-form">
		<table>
		<tr>
			<th>
				{$text.login_pw_new} ：
			</th>
			<td>
				<input id="token" name="token" type="hidden" value="{$token}">
				<input id="pw" name="pw" class="form-top-login" maxlength="15" type="password" value="{$post_pw}">
			</td>
		</tr>
		<tr>
			<th>
				{$text.login_pw_confirm} ：
			</th>
			<td>
				<input id="confirm" name="confirm" class="form-top-login" maxlength="15" type="password" value="{$post_confirm}">
			</td>
		</tr>
		</table>
	</div>
	<div id="login-form-button"><input type="submit" name="mypage" value="{$text.pw_re_button}"></div>
</form>

{include file="footer.tpl"}