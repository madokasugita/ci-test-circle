<?php

$designDefault[] = array("30","600","マトリクスタイトル","#d2d2d2","#000000","　","12","　","欠番","600","30","#d2d2d2","#000000","#000000","12","30","#ffffff","#000000","11","20","500");
$design[] = array(	"type"=>"matrix",
                    "name"=>"マトリクス形式",
                    "image"=>"F1.gif",
                    "parameter"=>
                    array(	"P01"=>"タイトル：高さ",
                            "P02"=>"タイトル：テーブル幅",
                            "P03"=>"タイトル：タイトル文章",
                            "P04"=>"タイトル：背景色",
                            "P05"=>"タイトル：文字色",
                            "P06"=>"タイトル：？",
                            "P07"=>"タイトル：文字サイズ指定",
                            "P08"=>"ヘッダー：ヘッダー文章",
                            "P10"=>"ヘッダー：マトリックス幅",
                            "P11"=>"ヘッダー：高さ(height)指定",
                            "P12"=>"ヘッダー：背景色指定",
                            "P13"=>"ヘッダー：文字色指定",
                            "P14"=>"ヘッダー：テーブル枠色指定",
                            "P15"=>"ヘッダー：選択肢文字サイズ",
                            "P16"=>"質問項目：高さ(height)指定",
                            "P17"=>"質問項目：背景色指定",
                            "P18"=>"質問項目：文字色指定",
                            "P19"=>"質問項目：文字サイズ指定",
                            "P20"=>'ヘッダー：選択肢部 幅',
                            "P21"=>'ヘッダー：非選択肢部 幅'
                            ),
                    "html1"=>'<table width="%%%%P02%%%%" border="0" cellpadding="0" cellspacing="0">
                                    <TR align=left bgColor="%%%%P04%%%%">
                                    <td width="2" valign="top">&nbsp;</td>
                                    <td height="%%%%P01%%%%" STYLE="font-size:%%%%P07%%%%px;">
                                    <font color="%%%%P05%%%%">%%%%P03%%%%</font></TD>
                                    </TR>
                                    </table><br>
                                    <!-- ヘッダーの行 開始 -->
                                    <table width="%%%%P10%%%%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                    <td bgcolor="%%%%P12%%%%"><table width="%%%%P10%%%%" border="0" cellpadding="0" cellspacing="1">
                                    <TR valign="bottom" bgcolor="%%%%P12%%%%">
                                    <TD width="%%%%P21%%%%" height="%%%%P11%%%%" STYLE="font-size:%%%%P15%%%%px;"> <div align="left"><font color="%%%%P13%%%%">&nbsp;%%%%P08%%%%<br>
                               　　 </div></TD>
                        　          %%%%%%%%<TD Width="%%%%P20%%%%" height="%%%%P11%%%%" STYLE="font-size:%%%%P15%%%%px; line-height:%%%%P15%%%%px;"><CENTER>%%%%choice%%%%</center></TD>%%%%%%%%
                                    </tr>',
                    "html2_header"=>'',
                    "html2_main"=>  '<tr bgcolor="%%%%P17%%%%" align="middle">
                                    <TD align=left nowrap>
                                    <font color="%%%%P18%%%%" style="font-size:%%%%P19%%%%pt;">&nbsp;%%%%title%%%%</font></TD>
                                    %%%%<TD valign="center">%%%%form%%%%</TD>%%%%
                                    </tr>',
                    "html2_footer"=>'</table></td></tr></table>'
                    );
$designDefault[] = array("30","1","25","#dddddd","#000000","　","12","600","24","#ffffff","#000000","#ffffff","11","#ffffff");
$design[] = array(	"type"=>"multi",
                    "name"=>"単複回答",
                    "image"=>"F2.gif",
                    "parameter"=>
                    array(	"P1"=>"各行の高さ",
                            "P2"=>"改行までの選択肢数",
                            "P3"=>"フォーム部分の幅",
                            "P4"=>"色:タイトル背景色",
                            "P5"=>"色:タイトル文字",
                            "P6"=>"－－",
                            "P7"=>"タイトル：文字サイズ指定",
                            "P8"=>"質問項目：幅(width)指定",
                            "P9"=>"質問項目：高さ(height)指定",
                            "P10"=>"質問項目：背景色指定",
                            "P11"=>"質問項目：文字色指定",
                            "P12"=>"質問項目：テーブル枠色指定",
                            "P13"=>"質問項目：文字サイズ指定",
                            "P14"=>"－－"
                            ),
                    "html1"=>'<table width="%%%%P8%%%%" border="0" cellpadding="0" cellspacing="0">
                                    <TR align=left bgColor="%%%%P4%%%%">
                                    <td height="%%%%P3%%%%" style="font-size:%%%%P7%%%%;">
                                    <font color="%%%%P5%%%%">&nbsp;%%%%title%%%%</font></TD>
                                    </TR>
                                    </table>',
                    "html2_header"=>'',
                    "html2_main"=>  '<table width="%%%%P8%%%%" cellpadding=0 cellspacing=0>
                                    <TR align=left bgColor="%%%%P12%%%%">
                                    <td height="%%%%P9%%%%" valign="top" bgcolor="%%%%P10%%%%">
                                    <table width="%%%%P8%%%%" border="0" cellpadding="0" cellspacing="1">
                                    <TR bgcolor="%%%%P10%%%%">
                                    %%%%<TD width="%%%%P3%%%%" height="%%%%P9%%%%"> %%%%form%%%% </TD><TD style="font-size:%%%%P7%%%%;"> <font color="%%%%P11%%%%">&nbsp;%%%%choice%%%%</font></TD>%%%%
                                    </TR>
                                    </table></td>
                                    </TR>
                                    </table>',
                    "html2_footer"=>''
                    );
$designDefault[] = array("#d2d2d2","#000000","12","600","30","#ffffff","#ffffff");
$design[] = array(	"type"=>"textbox",
                    "name"=>"テキストボックス回答",
                    "image"=>"F3.gif",
                    "parameter"=>
                    array(	"P1"=>"色:タイトル背景色",
                            "P2"=>"色:タイトル文字",
                            "P3"=>"タイトル：文字サイズ指定",
                            "P4"=>"質問項目：幅(width)指定",
                            "P5"=>"質問項目：高さ(height)指定",
                            "P6"=>"質問項目：背景色指定",
                            "P7"=>"質問項目：テーブル枠色指定"
                            ),
                    "html1"=>'<table width="%%%%P4%%%%" border="0" cellpadding="0" cellspacing="0">
                                    <TR align=left bgColor="%%%%P1%%%%">
                                    <td height="%%%%P5%%%%">
                                    <font color="%%%%P2%%%%" style="font-size:%%%%P3%%%%;">%%%%title%%%%</font></TD>
                                    </TR>
                                    </table>',
                    "html2_header"=>'',
                    "html2_main"  =>'<table width="%%%%P4%%%%" cellpadding=0 cellspacing=0>
                                    <TR align=left bgColor="%%%%P7%%%%">
                                    <td height="%%%%P5%%%%" valign="top" bgcolor="%%%%P6%%%%">
                                        <table width="%%%%P4%%%%" border="0" cellpadding="3" cellspacing="0">
                                        <TR align="left">
                                        <TD bgcolor="%%%%P6%%%%">
                                        %%%%form%%%%
                                        </TD>
                                        </TR></table>
                                    </td></tr></table>',
                    "html2_footer"=>''
                    );
$designDefault[] = array("#d2d2d2","#000000","12","600","30","#ffffff","#ffffff");
$design[] = array(	"type"=>"pulldown",
                    "name"=>"プルダウン",
                    "image"=>"F4.gif",
                    "parameter"=>
                    array(	"P1"=>"色:タイトル背景色",
                            "P2"=>"色:タイトル文字",
                            "P3"=>"タイトル：文字サイズ指定",
                            "P4"=>"質問項目：幅(width)指定",
                            "P5"=>"質問項目：高さ(height)指定",
                            "P6"=>"質問項目：背景色指定",
                            "P7"=>"質問項目：テーブル枠色指定"
                            ),
                    "html1"=>'<table width="%%%%P4%%%%" border="0" cellpadding="0" cellspacing="0">
                                    <TR align=left bgColor="%%%%P1%%%%">
                                    <td height="%%%%P5%%%%">
                                    <font color="%%%%P2%%%%" style="font-size:%%%%P3%%%%;">%%%%title%%%%</font></TD>
                                    </TR>
                                    </table>',
                    "html2_header"=>'',
                    "html2_main"  =>'<table width="%%%%P4%%%%" cellpadding=0 cellspacing=0>
                                    <TR align=left bgColor="%%%%P7%%%%">
                                    <td height="%%%%P5%%%%" valign="top" bgcolor="%%%%P6%%%%">
                                        <table width="%%%%P4%%%%" border="0" cellpadding="3" cellspacing="0">
                                        <TR align="left">
                                        <TD bgcolor="%%%%P6%%%%">
                                        %%%%form%%%%
                                        </TD>
                                        </TR></table>
                                    </td></tr></table>',
                    "html2_footer"=>''
                    );
