<?php
/**
 * PG名称：アンケートシステム用デザインテンプレート取得関数
 * 日付　：2006/04/19
 * 作成者：cbase Akama
 * @package Cbase.Research.Lib
 */

/**
 * htmlのヘッダーを取得。
 * @param string $prmTitle タイトル
 * @param stginr $prmJS JavaScriptなど含める文字列
 * @return string html
 */
function getDlHtmlHeader($prmTitle,$prmJS="")
{
    $strHtml = '
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=EUC-JP">
<title>'.$prmTitle.'</title>
'.$prmJS.'
</head>
<body>
';

    return $strHtml;
}

/**
 * htmlのフッターを取得。ヘッダーと対応。
 * @return string html
 */
function getDlHtmlFooter()
{
    $strHtml = '
</body>
</html>';

    return $strHtml;
}

/**
 * 全体を囲むタグ開始のテンプレを取得
 * @return string html
 */
function getDlHtmlBegin()
{
    $strResult = '
<table border="0" cellspacing="0" cellpadding="0">
    <tr>
    <td width="431" align="center">
';

    return $strResult;
}

/**
 * 全体を囲むタグ終了のテンプレを取得
 * @return string html
 */
function getDlHtmlEnd()
{
    $strResult = '
    </td>
    <td width="10">　</td>
    </tr>
</table>
';

    return $strResult;
}

/**
 * タイトル表示部のテンプレを取得
 * @param string $prmImg タイトル画像
 * @param string $prmMsg コメント
 * @return string html
 */
function getDlHtmlTop($prmImg,$prmMsg)
{
    //タイトル部分
    $strResult = '
        <table width="400" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="260" height="13"></td>
            <td width="70" align="right">　</td>
            <td width="70" align="right">　</td>
            </tr>
        </table>
        <table width="430" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
            </tr>
        </table>
        <br>
        <table width="450" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="150"><img src="'.DIR_IMG.$prmImg.'" width="148" height="78"></td>
            <td width="300" valign="middle"><font size="2">'.$prmMsg.'</font></td>
            </tr>
        </table>
        <br>

';

    return $strResult;
}

/**
 * 見出しのテンプレを取得
 * @param string $prmTitle 見出しのタイトル
 * @param string $prmMsg 見出し横のコメント
 * @return string html
 */
function getDlHtmlSubject($prmTitle,$prmMsg)
{
    $strHtml.='
        <table width="430" border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td width="13" valign="middle" align="center">
                <img src="'.DIR_IMG.'icon_inf.gif" width="13" height="13">
            </td>
            <td width="107" valign="middle"><font size="2">'.$prmTitle.'</font></td>
            <td width="287" valign="middle"><font color="#999999" size="2">'.$prmMsg.'</font></td>
            </tr>
            <tr valign="top">
            <td height="2" colspan="3">
            <table width="430" border="0" cellspacing="0" cellpadding="0">
                <tr>
                <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
                </tr>
            </table>
            </td>
            </tr>
        </table>
        <br>
';

    return $strHtml;
}

/**
 * 結果表示テーブルの中身を取得。
 * @param string $prmMsg1 文章
 * @param string $prmMsg2 中身
 * @return string html
 */
function getDlHtmlTableItem($prmMsg1,$prmMsg2)
{
    $strResult ='
                        <tr>
                        <td width="16"> </td>
                        <td width="10"></td>
                        <td width="85"><font size="2">'.$prmMsg1.'</font></td>
                        <td width="20"><font size="2"><img src="'.DIR_IMG.'arrow_r.gif" width="16" height="16" align="absmiddle"></font></td>
                        <td width="215"><font size="2">'.$prmMsg2.'</font></td>
                        </tr>
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="5"></td>
                        </tr>
';

    return $strResult;
}

/**
 * 結果表示テーブルの中身を取得。
 * @param string $prmMsg 文章
 * @return string html
 */
function getDlHtmlTableBegin($prmMsg)
{
    $strResult ='
    <table width="380" border="0" cellpadding="0" cellspacing="0" bgcolor="4c4c4c">
        <tr>
        <td width="270" valign="bottom">
            <table width="380" border="0" cellpadding="0" cellspacing="1">
                <tr>
                <td width="380" bgcolor="#f6f6f6" align="center">
                    <font size="2"> </font>
                    <table width="380" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
                        <tr>
                        <td colspan="5" align="center">
                            <font size="2">'.$prmMsg.'</font>
                        </td>
                        </tr>
                        <tr>
                        <td colspan="5"><img src="'.DIR_IMG.'spacer.gif" width="1" height="8"></td>
                        </tr>
';

    return $strResult;
}

/**
 * 結果表示テーブルの中身を取得。
 * @return string html
 */
function getDlHtmlTableEnd()
{

    $strResult.='
                    </table>
                </td>
                </tr>
            </table>
        </td>
        </tr>
    </table>
';

    return $strResult;
}

/**
 * 画面下部に取り付けられる戻るボタンを取得。
 * @param string $prmUrl 戻り先URL
 * @return string html
 */
function getDlHtmlReturn($prmUrl)
{
    $strResult = '
        <table width="430" border="0" cellspacing="0" cellpadding="0">
            <tr>
            <td height="1" background="'.DIR_IMG.'line_r.gif"><img src="'.DIR_IMG.'spacer.gif" width="1" height="1"></td>
            </tr>
            <tr height="12"><td></td>
            </tr>
            <tr>
            <td align="center"><a href="'.$prmUrl.'"><img src="'.DIR_IMG.'m_back.gif" width="100" height="24" align="middle" border=0></a></td>
            </tr>
        </table>

';

    return $strResult;
}

class HtmlDesign
{
    /**
     * ヘッダーを取得
     * @param  string $title タイトル
     * @return string html
     */
    public function header($title="")
    {
        return<<<HTML
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
<meta http-equiv="content-type" content="text/html; charset=EUC-JP">
<title>{$title}</title>
<meta http-equiv="content-script-type" content="text/javascript">
<meta http-equiv="content-style-type" content="text/css">
</head>
<body>
HTML;
    }

    /**
     * フッターを取得
     * @return string html
     */
    public function footer()
    {
        return<<<HTML
</body>
</html>
HTML;
    }

    /**
     * 本文テーブルの中身を返す
     * @param  string $prmTitle タイトル
     * @param  string $prmBody  本文
     * @return string html
     */
    public function tr($prmTitle,$prmBody)
    {
        return<<<HTML
<TR>
    <TD bgcolor="#eeeeee" align="right"><FONT size="-1">{$prmTitle}</FONT></TD>
    <TD bgcolor="#ffffff">{$prmBody}</TD>
</TR>
HTML;
    }

    /**
     * 本文テーブルの中身を返す
     * @param  string $prmTitle  タイトル
     * @param  array  $prmBody   本文の配列
     * @param  string $prmTColor 一列目の色
     * @param  string $prmBColor 二列目以降の色
     * @return string html
     */
    public function trArray($prmTitle,$prmBody, $prmTColor="#eeeeee", $prmBColor="#ffffff")
    {
        $strHtml = <<<HTML
<TR>
    <TD bgcolor="{$prmTColor}" align="right"><FONT size="-1">{$prmTitle}</FONT></TD>
HTML;

        foreach ($prmBody as $valBody) {
            $strHtml .= '<TD bgcolor="'.$prmBColor.'">'.$valBody.'</TD>';
        }

        $strHtml .= <<<HTML
</TR>
HTML;

        return	$strHtml;
    }

/**
 * 本体TR部、SUBMIT部
 *
 * @param string $prmForm formの属性
 * @param string $prmMessage 表示するメッセージ
 * @param array $prmTr 表示する列の配列
 * @param string $prmSubmit submit部分に表示するもの（ボタンなど）
 * @param string $prmError エラーメッセージ
 * @return string html
 */
function body($prmForm,$prmMessage,$prmTr,$prmSubmit,$prmError="")
{
    $strHtml = "";
    $strHtml .= <<<HTML
<div align="center">
<FORM {$prmForm}>
<TABLE border="0" cellspacing="0" cellpadding="0">
    <TR>
        <TD align="center" height="40"><FONT size="-1">{$prmMessage}<FONT></TD>
    </TR>
HTML;

    if ($prmError) {
        $strHtml .= '
<TR>
    <TD align="center" height="40"><FONT size="-1" color="#FF0000">'.$prmError.'<FONT></TD>
</TR>
';
    }

    $strHtml .= <<<HTML
    <TR>
        <TD bgcolor="#000000">
            <TABLE border="0" cellspacing="1" cellpadding="3">
HTML;

    foreach ($prmTr as $valTr) {
        $strHtml .= $valTr;
    }

    $strHtml .= <<<HTML
            </TABLE>
        </TD>
    </TR>

    <TR>
        <TD>&nbsp;</TD>
    </TR>
    <TR>
        <TD align="center">
            <TABLE border="0" cellspacing="0" cellpadding="0">
                <TR>
                    {$prmSubmit}
                </TR>
            </TABLE>
        </TD>
    </TR>
</TABLE>
</FORM>
</div>
HTML;

    return $strHtml;
}

}
