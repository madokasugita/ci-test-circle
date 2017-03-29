<?php
/**
 * HTMLクラス
 */
class CbaseHtml
{
    public $head_title;	// ヘッダタイトル
    public $aryFileCss;	// CSSファイル配列
    public $aryFileJs;		// JavaScriptファイル配列
    public $srcStyleCss;	// CSSソース
    public $srcScriptJs;	// JavaScriptソース

    /**
     * コンストラクタ
     * @param string $head_title
     */
    public function CbaseHtml($head_title="")
    {
        $this->head_title = html_escape($head_title);

        $this->aryFileCss = array();
        $this->aryFileJs = array();
        $this->setSrcCss();
        $this->setSrcJs();
    }

    /**
     * メインHTML取得
     * @param html $body
     */
    public function getMainHtml($body)
    {
        return $this->getHeaderHtml().$body.$this->getFooterHtml();
    }

    /**
     * ヘッダHTML取得
     */
    public function getHeaderHtml()
    {
        global $GDF;

        $fileCss = implode("", array_reflex($this->aryFileCss, array($this, 'getFileCssHtml')));
        $fileJs = implode("", array_reflex($this->aryFileJs, array($this, 'getFileJsHtml')));
        $styleCss = $this->getStyleHtml($this->srcStyleCss);
        $scriptJs = $this->getScriptHtml($this->srcScriptJs);

        $bodyHeader = $this->getBodyHeaderHtml();

        return <<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Script-Type" content="text/javascript">
{$fileCss}{$fileJs}{$styleCss}{$scriptJs}<title>{$this->head_title}</title>
<link rel="shortcut icon" href="{$GDF->get('DIR_IMG')}favicon.ico">
<link rel="apple-touch-icon-precomposed" href="{$GDF->get('DIR_IMG')}favicon.png">
</head>
{$bodyHeader}\n
__HTML__;
    }

    /**
     * フッタHTML取得
     */
    public function getFooterHtml()
    {
        $bodyFooter = $this->getBodyFooterHtml();

        return <<<__HTML__
\n{$bodyFooter}
</html>
__HTML__;
    }

    /**
     * bodyヘッダHTML取得(継承先でオーバーライド)
     */
    public function getBodyHeaderHtml()
    {
        $id = str_replace('.php','',basename(getPHP_SELF()));

        return <<<__HTML__
<body id="{$id}">
__HTML__;
    }

    /**
     * bodyフッタHTML取得(継承先でオーバーライド)
     */
    public function getBodyFooterHtml()
    {
        return <<<__HTML__
</body>
__HTML__;
    }


    /**
     * CSSファイルHTML取得
     */
    public function getFileCssHtml($fileCss)
    {
        return <<<__HTML__
<link rel="stylesheet" href="{$fileCss}" type="text/css">\n
__HTML__;
    }

    /**
     * JavaScriptファイルHTML取得
     */
    public function getFileJsHtml($fileJs)
    {
        return <<<__HTML__
<script src="{$fileJs}" type="text/javascript"></script>\n
__HTML__;
    }

    /**
     * CSSファイル追加
     * @param  string  $fileCss
     * @return boolean
     */
    public function addFileCss($fileCss)
    {
        // 存在しなければ
        if (!in_array($fileCss, $this->aryFileCss)) {
            $this->aryFileCss[] = $fileCss;

            return true;
        }

        return false;
    }

    /**
     * CSSファイル削除
     * @param  string  $fileCss
     * @return boolean
     */
    public function removeFileCss($fileCss)
    {
        $key = array_search($fileCss, $this->aryFileCss);

        // 存在すれば
        if (is_good($key)) {
            unset($this->aryFileCss[$key]);

            return true;
        }

        return false;
    }

    /**
     * JavaScriptファイル追加
     * @param  string  $fileJs
     * @return boolean
     */
    public function addFileJs($fileJs)
    {
        // 存在しなければ
        if (!in_array($fileJs, $this->aryFileJs)) {
            $this->aryFileJs[] = $fileJs;

            return true;
        }

        return false;
    }

    /**
     * JavaScriptファイル削除
     * @param  string  $fileJs
     * @return boolean
     */
    public function removeFileJs($fileJs)
    {
        $key = array_search($fileJs, $this->aryFileJs);

        // 存在すれば
        if (is_good($key)) {
            unset($this->aryFileJs[$key]);

            return true;
        }

        return false;
    }


    /**
     * スタイルHTML取得
     */
    public function getStyleHtml($srcStyle, $type="text/css")
    {
        if (is_void($srcStyle)) {
            return "";
        }

        return <<<__HTML__
<style type="{$type}">
<!--
{$srcStyle}
//-->
</style>\n
__HTML__;
    }

    /**
     * スクリプトHTML取得
     */
    public function getScriptHtml($srcScript, $type="text/javascript")
    {
        if (is_void($srcScript)) {
            return"";
        }

        return <<<__HTML__
<script type="{$type}">
<!--
{$srcScript}
//-->
</script>\n
__HTML__;
    }

    /**
     * CSSソース設定
     * @param style $srcCss
     */
    public function setSrcCss($srcCss="")
    {
        $this->srcStyleCss = $srcCss;
    }

    /**
     * JavaScriptソース設定
     * @param script $srcJs
     */
    public function setSrcJs($srcJs="")
    {
        $this->srcScriptJs = $srcJs;
    }
}


/**
 * Research管理画面用HTMLクラス
 */
class ResearchAdminHtml extends CbaseHtml
{
    public $title;			// タイトル
    public $titleExp;		// タイトル説明
    public $aryTitleHelp;	// タイトルヘルプ

    /**
     * コンストラクタ
     * @param html $title
     * @param html $titleExp
     */
    public function ResearchAdminHtml($title="", $titleExp="")
    {
        parent::CbaseHtml($title);

        $this->setTitle($title);
        $this->setTitleExp($titleExp);
        $this->aryTitleHelp = array();

        $this->addFileCss(DIR_IMG."360_adminpage.css");
        $this->addFileCss(DIR_IMG."redmond/jquery-ui-1.10.0.custom.min.css");

        $this->addFileJs(DIR_JS."common.js");
        $this->addFileJs(DIR_JS."ajax.js");
        $this->addFileJs(DIR_JS."jquery-1.7.1.min.js");
        $this->addFileJs(DIR_IMG."jquery-ui-1.10.0.min.js");
        $this->addFileJs(DIR_JS ."myconfirm.js");
        $this->addFileJs(DIR_JS ."admin/jquery.csvdownload-window.js");
    }

    public function setTextAreaResizer()
    {
        //$this->addFileJs(DIR_JS."jquery-1.7.1.min.js");
        $this->addFileJs(DIR_JS."jquery.textarearesizer.compressed.js");
        $this->addFileJs(DIR_JS."textarearesizer.js");
        $this->addFileCss(DIR_IMG."textarearesizer.css");
    }

    /**
     * bodyヘッダHTML取得
     */
    public function getBodyHeaderHtml()
    {
        return <<<__HTML__
<body class="yui-skin-sam">
<div id="container-iframe">
<div id="main-iframe">
__HTML__;
        /*
        $titleHelp = $this->getTitleHelp();

        return <<<__HTML__
<body>
<div id="title_enq">
<h1>{$this->title}</h1>
<p>{$this->titleExp}</p>

<div style="text-align:right;margin-right:15px;">
{$titleHelp}
</div>

</div>

<div id="container">
__HTML__;
        */
    }

    /**
     * bodyフッタHTML取得
     */
    public function getBodyFooterHtml()
    {
        return <<<__HTML__
</div>
</div>
</body>
__HTML__;
    }


    /**
     * タイトル設定
     * @param html $title
     */
    public function setTitle($title="")
    {
        $this->title = $title;
    }

    /**
     * タイトル説明設定
     * @param html $titleExp
     */
    public function setTitleExp($titleExp="")
    {
        $this->titleExp = $titleExp;
    }

    /**
     * タイトルヘルプ追加
     * @param string $helpName
     * @param string $helpValue
     */
    public function addTitleHelp($helpName, $helpValue)
    {
        foreach ($this->aryTitleHelp as $help) {
            if($help['name']==$helpName) return false;
        }
        $this->aryTitleHelp[] = array(
            'name'		=> $helpName
            ,'value'	=> $helpValue
        );

        return true;
    }

    /**
     * タイトルヘルプ削除
     * @param  string  $helpName
     * @return boolean
     */
    public function removeTitleHelp($helpName)
    {
        foreach ($this->aryTitleHelp as $key => $help) {
            if ($help['name']==$helpName) {
                unset($this->aryTitleHelp[$key]);

                return true;
            }
        }

        return false;
    }

    /**
     * タイトルヘルプ取得
     */
    public function getTitleHelp()
    {
        if (empty($this->aryTitleHelp)) {
            return "";
        }
        $titleHelp = array();
        foreach ($this->aryTitleHelp as $help) {
            $titleHelp[] = <<<__HTML__
<strong>{$help['name']}</strong><br>{$help['value']}<br>
__HTML__;
        }

        return getHelpBalloon('titleHelp', implode("<br>\n", $titleHelp));
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
<div style="width:160px;padding:5px;background-color:f6f6f6;border:solid 1px #666666;">
<img src="{$DIR_IMG}overview.gif" width="100" height="16"><br><br>
<img src="{$DIR_IMG}arrow_r.gif" width="16" height="16" align="absmiddle">{$title}<br><br>

{$tmp}
</div>

__HTML__;
    }


    public function getTopHtml($img,$text,$width='450')
    {
        $DIR_IMG = DIR_IMG;
        $PHP_SELF = PHP_SELF;

    return<<<__HTML__
<table width="{$width}" border="0" cellpadding="0" cellspacing="0">
<tr>
<td align="right" colspan="2"><a href="{$PHP_SELF}"><img src="{$DIR_IMG}change.gif" width="61" height="12" border="0"></a></td>
</tr>
<tr><td colspan="2" height="2"><img src="{$DIR_IMG}spacer.gif" width="1" height="1"></td></tr>
<tr>
  <td height="1" background="{$DIR_IMG}line_r.gif" colspan="2"><img src="{$DIR_IMG}spacer.gif" width="1" height="1"></td>
</tr>
<tr><td colspan="2" height="10"><img src="{$DIR_IMG}spacer.gif" width="1" height="1"></td></tr>
<tr>
  <td width="150"><img src="{$DIR_IMG}{$img}"></td>
  <td valign="middle"><font size="2">{$text}</font></td>
</tr>
</table>
__HTML__;
}

    public function getCaption($title,$comment,$width='100%')
    {
        $DIR_IMG = DIR_IMG;

        return<<<__HTML__
<div class="sub_title"style="width:{$width}">
{$title}
<span style="padding-left:97px;color:#999999;font-weight:normal;font-size:0.9em">{$comment}</span>
</div>
__HTML__;
    }

}


/**
 * ヘルプバルーン取得
 * @param string $uniqId
 * @param html $helpHtml
 */
function getHelpBalloon($uniqId, $helpHtml="")
{
    global $HB_assocUniqId;
    if(isset($HB_assocUniqId[$uniqId])) return "";
    $HB_assocUniqId[$uniqId] = 1;

    $DIR_IMG = DIR_IMG;

    return <<<__HTML__
<!-- ticker start -->
<dl class="ticker">
<dt class="icon">
<script type="text/javascript">
<!--
document.write('<a href="#" onClick="ticker(\'help-{$uniqId}\',\'{$DIR_IMG}\');return false;"><img src="{$DIR_IMG}hatena1.gif" alt="ヘルプ" width="16" height="16" class="inline m-l" name="ticker-{$uniqId}" /></a>');
//-->
</script>
</dt>
<dd class="ticker">
  <div class="ticker" id="help-{$uniqId}" style="display:none;">
    <div class="inner">
      <div class="close-box">
        <a href="#" onClick="ticker('help-{$uniqId}','{$DIR_IMG}');return false;"><img src="{$DIR_IMG}btn_ticker_close.gif" alt="閉じる" width="13" height="13" /></a><br>
      </div>
      <div class="text-box">
{$helpHtml}
      </div>
    </div>
  </div>
</dd>
</dl>
<!-- ticker end -->
__HTML__;
}


/**
 * サブタイトル取得
 * @param html $title
 * @param html $titleExp
 * @param int $width
 */
function getSubTitle($title="", $titleExp="", $width="500")
{
    return <<<__HTML__
<table width="{$width}" border="0" cellpadding="5" cellspacing="0" align="center" class="main_index">
<tr>
  <td class="main_title">{$title}</td>
  <td>{$titleExp}</td>
</tr>
</table>
__HTML__;
}


/**
 * 注意画像取得
 */
function getImgCaution($sytle="")
{
    $DIR_IMG = DIR_IMG;

    return <<<__HTML__
<img src="{$DIR_IMG}caution.gif" alt="[注意]" style="{$style}">
<span style="color:#ff0000;font-size:larger;font-weight:bold;">[注意]</span>
__HTML__;
}

/**
 * 警告画像取得
 */
function getImgWarning($style="")
{
    $DIR_IMG = DIR_IMG;

    return <<<__HTML__
<img src="{$DIR_IMG}warning.gif" alt="[警告]" style="{$style}">
<span style="color:#ff0000;font-size:larger;font-weight:bold;">[警告]</span>
__HTML__;
}
