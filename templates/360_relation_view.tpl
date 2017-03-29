{include file="header.tpl"}
<div class="exfix" class="noprint">
<div id="bar" class="noprint">
<input type="submit" value="メニューに戻る" class="btn" onclick="location.href='{$menu_link}'; return false;">
</div>
</div>

<div id="maincontainer">
<div style="text-align:left;margin:10px auto 5px auto;border-top:dotted 1px #222222;border-bottom:dotted 1px #222222;padding:10px;">
回答者情報閲覧
</div>
<div style="text-align:left;width:880px;margin:0px auto 5px auto;">
<!--
{$message}
-->
{$user_info}

{*回答者選定*}
{if $relation_info1}
<div style="margin-bottom:20px;background-color:#f0f0f0;padding:10px">
<div style="font-weight:bold;font-size:17px;background-color:#ffffff;margin:10px 0px;padding:5px;border-bottom:solid 3px #333399">
{$text.user_relation_view_1}
</div>
<div style="margin:auto;width:800px;text-align:left">
<pre>{$text.user_relation_view_message_1}</pre>
</div>

<div style="text-align:center;margin:10px 0px; background-color:#cccccc;padding:10px">
{$delete_error}
{$add_button1}
</div>

{$relation_info1}

</div>
{/if}

{*回答者追加*}
{if $relation_info2}
<div style="margin-bottom:20px;background-color:#f0f0f0;padding:10px">
<div style="font-weight:bold;font-size:17px;background-color:#ffffff;margin:10px 0px;padding:5px;border-bottom:solid 3px #333399">{$text.user_relation_view_3}</div>
<div style="margin:auto;width:800px;text-align:left">
<pre>{$text.user_relation_view_message_3}</pre>
</div>

<div style="text-align:center;margin:10px 0px; background-color:#cccccc;padding:10px">
{$add_button2}
</div>

{$relation_info2}

</div>
{/if}

{*確定ボタン*}
{if $status_select_button}
<div style="text-align:center;background-color:#eeeeee;padding:10px">
<div style="text-align:left;font-weight:bold;font-size:17px;background-color:#ffffff;margin:10px 0px;padding:5px;border-bottom:solid 3px #333399">{$text.user_relation_view_2}</div>
<div style="margin:auto;width:800px;text-align:left">

<pre>{$text.user_relation_view_message_2}</pre>

</div>
<div style="text-align:center;background-color:#cccccc;padding:10px">
{$message}
{$status_select_button}
</div>
</div>
{/if}


</div>

</div><!-- maincontainer -->
{include file="footer.tpl"}
