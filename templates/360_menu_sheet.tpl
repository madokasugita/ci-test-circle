{if $menu[$sheet_type] != ''}
<div class="sheet_type">{$sheet_name[$sheet_type]}</div>

{*お知らせ(お知らせがあればタグも表示)*}
{if $infomation[$sheet_type] != ''}<pre class="infomation">{$infomation[$sheet_type]}</pre>{/if}

{$menu[$sheet_type]}
{/if}