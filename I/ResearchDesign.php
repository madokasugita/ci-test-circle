<?php
/*
 * 分類のためstaticメソッドで作ること
 * ResearchDesign=RDを前置詞とする
 */

//メイン
//タイトル部分
class RD
{
    //tdを追加する必要があればaddtdへ記述
    public function getMain($body, $addtd='')
    {
        return <<<__HTML__
<table width="610" border="0" cellspacing="0" cellpadding="0">
<tr>
  <td width="431" align="left">
  {$body}
  </td>
  {$addtd}
</tr>
</table>
__HTML__;
    }

    public function getTitle($titleimg='', $comment='')
    {
        if($titleimg) $titleimg = '<img src="'.$titleimg.'">';

        return <<<__HTML__
<table width="450" border="0" cellpadding="0" cellspacing="0">
<tr>
  <td width="150">{$titleimg}</td>
  <td width="300" valign="middle"><font size="2">{$comment}</font></td>
</tr>
</table>
<br>
__HTML__;
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

    //戻るボタン
    public function getBackBar($url="", $width=430)
    {
        if(is_void($url)) $url = getPHP_SELF()."?".getSID();
        //$line = RD::getLine($width);
        $DIR_IMG = DIR_IMG;

        return 	<<<__HTML__
<div class="refresh"><a href="{$url}"><img src="{$DIR_IMG}back.gif" width="61" height="12" border="0"></a></div>
__HTML__;
    }

    //更新ボタン
    public function getRefreshBar($url="", $width=430)
    {
        if(is_void($url)) $url = getPHP_SELF()."?".getSID();
        //$line = RD::getLine($width);
        $DIR_IMG = DIR_IMG;

        return 	<<<__HTML__
<div class="refresh"><a href="{$url}"><button class="white button">更新</button></a></div>
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
}


//テーブル
//tr
//td
class RDTable
{


    /**
     * @return string テーブルの囲み枠部分
     */
    public function getTBody($body, $width=450, $add="")
    {
        if($width)
            $width = ' width="'.$width.'"';

        return <<<__HTML__
<table class="cont"{$width}{$add}>
    {$body}
</table>
__HTML__;
    }

    public function getHeaderTr($body)
    {
        return <<<__HTML__
<tr style="white-space: nowrap">
{$body}
</tr>
__HTML__;
    }

    public function getHeaderTd($body, $style="")
    {
        return <<<__HTML__
  <th {$style}>
    {$body}
  </th>
__HTML__;
    }

    public function getTr($body, $line=0)
    {
        //$color = $line%2? '#f6f6f6': '#ffffff';
        $class = $line%2? 'odd':'odd2';

        return <<<__HTML__
<tr class="{$class}">
{$body}
</tr>
__HTML__;
    }

    public function getTd($body, $style="")
    {
        $body = str_replace("\n","",$body);
        $body = str_replace("\r","",$body);

        return <<<__HTML__
<td {$style} class="break">{$body}</td>
__HTML__;
    }
}
