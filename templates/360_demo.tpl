{include file="header.tpl"}
<body id="page_login" onload="document.getElementById('id').focus();">
{*言語切り替えバー*}
{$languagebar}

{*ロゴ*}
<div id="top"><img src="{$dir_user}logo.png" alt="logoimage"></div>

{*メインタイトルの表示*}
<h1>{$text.main_title} {$text.demo_title}</h1>

{*エラーがあればエラー表示*}
{if $errors }
<ul class="errors">
{foreach from=$errors item=error}
    <li class="error">{$error}</li>
{/foreach}
</ul>
{/if}

{*成功があれば成功表示*}
{if $success }
<ul class="errors">
{foreach from=$success item=s }
    <li class="error">{$s}</li>
{/foreach}
</ul>
{/if}

<form action="360_demo.php" method="POST" onSubmit="return this.flag?false:this.flag=true;">
    <div id="login-form">
        <table>
            <tr>
                <th>
                    {$text.name} ：
		</th>
		<td>
		    <input id="name" name="name" type="text" value="">
		</td>
            </tr>
            <tr>
                <th>
                    メールアドレス ：
		</th>
		<td>
		    <input id="email" name="email" class="form-top-login" type="text" value="">
		</td>
            </tr>
        </table>
    </div>
    <div id="login-form-button"><input type="submit" name="mode:demo" value="{$text.demo_button}"></div>
</form>

{include file="footer.tpl"}