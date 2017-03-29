{include file="header.tpl"}
<body id="asswdreissue">
<div id="container-iframe"> 
<div id="main-iframe"> <!-- top -->

<div id="top">
<img src="img/_/logo.png" border="0">
</div>

<!-- /top -->
<h1>パスワード再送信</h1>

{*お知らせがあればお知らせを表示*}
{if $text.pw_reissue }
<pre class="infomation">{$text.pw_reissue}</pre>
{/if}


{*エラーがあればエラー表示*}
{if $errors }
<ul class="errors">
{foreach from=$errors item=error}
    <li class="error">{$error}</li>
{/foreach}
</ul>
{/if}

<form action="passwd_reissue.php" method="POST" onSubmit="return this.flag?false:this.flag=true;">
	<div style="background-color:#f5f5f5;width:780px;text-align:center;margin:15px auto;padding:15px;">
		<table cellpadding="0" cellspacing="5" style="margin:0 auto">
		<tr> 
			<th>
				{$text.login_id} ：
			</th>
			<td>
				<input name="id" class="form-top-login" type="text" value="">
			</td>
		</tr>
		<tr> 
			<th>
				{$text.mail} ：
			</th>
			<td>
				<input name="email" class="form-top-login" type="text" value="">
			</td>
		</tr>
		</table>
	</div>
	<div style="width:590px;text-align:center;margin:15px auto;">
		<input type="hidden" name="mode" value="reissue">
		<input type="submit" name="mode:reissue" value="再送信" style="width:100px">
	</div>
</form>

<div style="width:780px;text-align:center;margin:15px auto;padding:15px;">
<button onclick="window.close()">{$text.reissue_close_button}</button>
</div>

{include file="footer.tpl"}