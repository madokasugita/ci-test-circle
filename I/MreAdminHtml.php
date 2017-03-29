<?php

require_once (DIR_LIB . '360Design.php');

/**
 * HTMLクラス
 */
class CbaseAdminHtml
{
    public $head_title;	// ヘッダタイトル
    public $aryFileCss;	// CSSファイル配列
    public $aryFileJs;		// JavaScriptファイル配列
    public $srcStyleCss;	// CSSソース
    public $srcScriptJs;	// JavaScriptソース
    public $sideContents;	// サイドコンテンツ
    public $refresh;	//更新ボタン
    public $standAlone;	//新規ウィンドウ
    public $message;	//フラッシュメッセージ

    /**
     * コンストラクタ
     * @param string $head_title
     */
    public function CbaseAdminHtml($head_title="")
    {
        $this->head_title = html_escape($head_title);

        $this->aryFileCss = array();
        $this->aryFileJs = array();
        $this->sideContents = array();
        $this->setSrcCss();
        $this->setSrcJs();
    }

    /**
     * メインHTML取得
     * @param html $body
     */
    public function getMainHtml($body)
    {
        $main = "";
        if($this->refresh)
            $main .= D360::getRefreshBar();

        $main .= "<h1>".$this->head_title.'<span class="title-comment">'.$this->titleExp."</span></h1>";
        if(is_good($this->message))
            $main .= '<div class="flush_message">'.$this->message.'</div>';
        $body = $main.'<div id="main_contents">'.$body.'</div>';
        if(is_array($this->sideContents))
            $body.= '<div id="side_contents">'.implode("<br>", $this->sideContents).'</div><div style="clear:both"></div>';

        return $this->getHeaderHtml().$body.$this->getFooterHtml();
    }

    /**
     * サイドHTML設定
     * @param html $body
     */
    public function setSideHtml($body)
    {
        $this->sideContents[] = $body;
    }

    /**
     * ヘッダHTML取得
     */
    public function getHeaderHtml()
    {
        $fileJs = implode("", array_reflex($this->aryFileJs, array($this, 'getFileJsHtml')));
        $fileCss = implode("", array_reflex($this->aryFileCss, array($this, 'getFileCssHtml')));
        $styleCss = $this->getStyleHtml($this->srcStyleCss);

        $bodyHeader = $this->getBodyHeaderHtml();

        return <<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=EUC-JP">
<meta http-equiv="Content-Style-Type" content="text/css">
<meta http-equiv="Content-Script-Type" content="text/javascript">
{$fileCss}{$fileJs}{$styleCss}<title>{$this->head_title}</title>
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
        $scriptJs = $this->getScriptHtml($this->srcScriptJs);

        return <<<__HTML__
{$scriptJs}
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
class MreAdminHtml extends CbaseAdminHtml
{
    public $title;			// タイトル
    public $titleExp;		// タイトル説明
    public $aryTitleHelp;	// タイトルヘルプ

    /**
     * コンストラクタ
     * @param html $title
     * @param html $titleExp
     */
    public function MreAdminHtml($title="", $titleExp="", $refresh = true, $standAlone = false, $blank = true)
    {
        parent::CbaseAdminHtml($title);

        $this->setTitle($title);
        $this->setTitleExp($titleExp);
        $this->aryTitleHelp = array();
        $this->refresh = $refresh;
        $this->standAlone = $standAlone;

        $this->addFileCss(DIR_IMG."360_adminpage.css");
        $this->addFileCss(DIR_IMG."redmond/jquery-ui-1.10.0.custom.min.css");

        $this->addFileJs(DIR_JS."ajax.js");
        $this->addFileJs(DIR_JS."common.js");
        $this->addFileJs(DIR_JS."jquery-1.7.1.min.js");
        $this->addFileJs(DIR_IMG."jquery-ui-1.10.0.min.js");
        $this->addFileJs(DIR_JS ."myconfirm.js");
        $this->addFileJs(DIR_JS."jquery/jquery.toastmessage.js");
        $this->setCsvdownloadWindow($blank);

    }

     public function setCsvdownloadWindow($blank)
    {
        if ($blank)
        {
            $this->addFileJs(DIR_JS."admin/jquery.csvdownload-window.js");
        }
    }

    public function setTextAreaResizer()
    {
        $this->addFileJs(DIR_JS."jquery.textarearesizer.compressed.js");
        $this->addFileJs(DIR_JS."textarearesizer.js");
        $this->addFileCss(DIR_IMG."textarearesizer.css");
    }

    public function setTools()
    {
        $this->addFileJs(DIR_JS."jquery.tools.min.js");
        $this->addFileJs(DIR_JS."tools.common.js");
    }

    public function setExFix()
    {
        $this->addFileJs(DIR_JS."jquery.exfixed-1.3.2.js");
        $this->addFileJs(DIR_JS."scrollmenu.js");
    }

    /**
     * bodyヘッダHTML取得
     */
    public function getBodyHeaderHtml()
    {
        if($this->standAlone)

        return <<<__HTML__
<body class="yui-skin-sam wrapPageBody">
<div id="container-iframe" class="wrapPage">
<div id="main-iframe" class="wrapPageInner">
__HTML__;

        return <<<__HTML__
<body class="yui-skin-sam" >
<div id="container-iframe">
<div id="main-iframe">
__HTML__;
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
     * フラッシュメッセージ設定
     * @param html $titleExp
     */
    public function setMessage($message="")
    {
        $this->message = $message;
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
        $DIR_IMG = DIR_IMG;
        $tmp=array();
        foreach ($advices as $key=>$val) {
            $tmp[] = $key.'<br>'.$val;
        }
        $tmp = implode('<br><br>',$tmp);

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

    public function getCaption($title,$comment)
    {
        $DIR_IMG = DIR_IMG;

        return<<<__HTML__
<div class="sub_title">
{$title}
<span style="padding-left:97px;color:#999999;font-weight:normal;font-size:0.9em">{$comment}</span>
</div>
__HTML__;
    }

}
