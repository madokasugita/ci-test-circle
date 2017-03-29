<?php
class D360
{
    public function getHelpIcon($title, $help)
    {
    //文字列をjs用にする
    $help = preg_replace("/[\r\n]/", '', $help);
    $help = str_replace('"', '\\\'', $help);
    $src = DIR_IMG;

    return <<<__HTML__
 <span class="helpbox"onMouseOver="showHelp('<img src={$src}box_top.gif><div class=helpbox-text><img src={$src}box_tale.gif class=helpbox-tale>{$help}</div><img src={$src}box_bottom.gif>',this,event)" >？</span>
__HTML__;
    }

    public function getTitle($title='',$message='',$type='')
    {
        switch ($type) {
            case('help'):
                return '<h1>'.$title.D360::getHelpIcon($title,$message).'</h1>';
            case('comment'):
                return '<h1>'.$title.'<span class="title-comment">'.$message.'</span></h1>';
            default:
                return '<h1>'.$title.'</h1>';
        }
    }

    public function getSubject($title, $comment='', $width=600, $commentcolor='#999999',$style="")
    {
        return <<<__HTML__
<div class="sub_title"style="width:{$width};{$style}">
{$title}
<span style="padding-left:97px;color:{$commentcolor};font-weight:normal;font-size:0.9em">{$comment}</span>
</div>
__HTML__;
    }

    public function getLine($width = 430)
    {
        $DIR_IMG = DIR_IMG;

        return 	<<<__HTML__
<table width="{$width}" border="0" cellspacing="0" cellpadding="0">
<tr>
  <td height="1" background="{$DIR_IMG}line_r.gif"><img src="{$DIR_IMG}spacer.gif" width="1" height="1"></td>
</tr>
</table>
__HTML__;
    }

    public function getAdvice($title,$advices)
    {
        $tmp=array();
        foreach ($advices as $key=>$val) {
            $tmp[] = $key.'<br>'.$val;
        }
        $tmp = implode('<br><br>',$tmp);
        $DIR_IMG = DIR_IMG;

        return <<<__HTML__
<div class="advice_area">
<img src="{$DIR_IMG}overview.gif" width="100" height="16"><br><br>
<img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle">{$title}<br><br>

{$tmp}
</div>

__HTML__;
    }

    public function getSideBox($title,$main)
    {
        $DIR_IMG = DIR_IMG;

        return <<<__HTML__
<div class="tool_area">
<img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle">{$title}<br><br>
{$main}
</div>

__HTML__;
    }

    //戻るボタン
    public function getBackBar($url="", $width=430)
    {
        if(is_void($url)) $url = getPHP_SELF()."?".getSID();
        //$line = RD::getLine($width);
        $icon = D360::getIconButton("button", "refresh", "ui-icon-arrowreturnthick-1-w", "戻る", " onclick=\"location.href='".$url."'\"");
        $DIR_IMG = DIR_IMG;

        return 	<<<__HTML__
<div class="refresh">{$icon}</div>
__HTML__;
    }

    //更新ボタン
    public function getRefreshBar($url="", $width=430)
    {
        if(is_void($url)) $url = getPHP_SELF()."?".getSID();
        $icon = D360::getIconButton("button", "refresh", "ui-icon-refresh", "更新", " onclick=\"location.href='".$url."'\"");
        $DIR_IMG = DIR_IMG;

        return 	<<<__HTML__
<div class="refresh">{$icon}</div>
__HTML__;
    }

    //更新促進メッセージ
    public function getUpdateInfo($width=430)
    {
        return 	<<<__HTML__
<table width="{$width}" border="0" cellpadding="0" cellspacing="0">
<tr>
  <td valign="middle" align="center">
    <font color="#999999" size="2">※最新情報に更新されない時は、「更新」を行ってください。</font>
  </td>
</tr>
</table>
__HTML__;
    }

    public function getIconButton($type, $name, $icon, $message, $ext="")
    {
        if($ext) " ".$ext;
        if(!$message) $icon_only = " ui-button-icon-only";

        return <<<__HTML__
<button type="{$type}" name="{$name}" class="white button ui-button ui-button-text-icon-primary{$icon_only}"{$ext} value="1">
    <span class="ui-icon {$icon}"></span>
    <span class="ui-button-text">$message</span>
</button>
__HTML__;
    }
    public function getIconMiniButton($type, $name, $icon, $message, $ext="")
    {
        if($ext) " ".$ext;

        return <<<__HTML__
<button type="{$type}" name="{$name}" class="white button_s ui-button ui-button-text-icon-primary"{$ext} value="1">
    <span class="ui-icon {$icon}"></span>
    <span class="ui-button-text">$message</span>
</button>
__HTML__;
    }
}
