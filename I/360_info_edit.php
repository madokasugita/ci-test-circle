<?php
function info_edit_main()
{
    global $page_name;
    switch (getMode()) {
        case "write" :
            $html = write_term();
            break;
        default :
            $html = getHtmlForm();
    }

    echo HTML_header($page_name, "", "bgcolor='{$type_color[$type]}' style='text-align:left;padding:10px;'") . $html . HTML_footer();
    exit;
}
function write_term()
{
    global $type, $ERROR;
    s_write(FILE_INFO_EDIT, $_POST['infomation']);

    return getHtmlForm();
}

function getHtmlForm()
{
    global $term_names, $page_name, $PHP_SELF, $ERROR;
    $type = TYPE;
    $infomation = file_get_contents(FILE_INFO_EDIT);

    $error = $ERROR->show();
    $html =<<<HTML

<h1>{$page_name}</h1>

&lt;!--to2008/09/01--&gt; と入力すると、2008年9月1日までの日数が表示されます。(2008年8月31日時点で、"2"と表示されます)

{$error}

<form action="{$PHP_SELF}" method="POST">
<textarea name="infomation" style="width:645px;height:200px">{$infomation}</textarea>

<br>
<input type="submit" name="mode:write" value="更新">




HTML;
    $infomation = preg_replace('|<!--to([^-]+)-->|e', "dateCal('\\1')", $infomation);

    $html .=<<<HTML
<br><br><br>
プレビュー
<pre style="width:645px;text-align:left;padding:10px;border-width:1px;border-color:#333333;border-style:dotted;line-height:1.5">
{$infomation}
</pre>
HTML;

    return $html;
}
