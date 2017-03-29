<?php
/* ---------------------------------------------------------
   ;;;;;;;;;;;;
   ;;;.php;;  by  ipsyste@cbase.co.jp
   ;;;;;;;;;;;;
--------------------------------------------------------- */
//変数セット
define('DIR_ROOT', "../");
require_once(DIR_ROOT."crm_define.php");
require_once(DIR_LIB."CbaseFDB.php");
require_once(DIR_LIB."CbaseFGeneral.php");
require_once(DIR_LIB."CbaseFunction.php");
require_once(DIR_LIB."MreAdminHtml.php");
require_once(DIR_LIB."CbaseEncoding.php");
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

if ($_POST["main"]=="更新") {//更新
    Set_Musr($_POST,"permit",$_POST["fMUID"]);
}

//一覧取得
$array = Get_Musr();
//デフォルト
    $html.= '<br>';
    $html.= '<table align=center border=0 cellspacing=0 cellpadding=2>';
    $html.= '<tr>';
    $html.= '<td>&nbsp;</td>';
    $c=0;
    foreach ($arMenu as $menu) {

        if($menu[4] && !DEBUG)//360カスタマイズ true の権限は編集できない
            continue;

        if ($lastcategory<>$menu[0]) ++$c;
        $style = (isIE())? ' style="writing-mode:tb-rl;"':'';
        $html.= '<td width=16 valign=top '.(($c%2)==0? "":" bgcolor=#f2f2f2").$style.'>';
        $html.= (isIE())? stringToVirtical($menu[2]," "):stringToVirtical($menu[2],"<br>");

        if ($menu[4]==true) $html.= '<font color=red>&nbsp;*</font>';
        $html.= '</td>';
        $lastcategory = $menu[0];
    }
    $html.= '<td>&nbsp;</td>';
    $html.= '</tr>';
    //一覧
    foreach ($array as $ar) {
        $html.= '<form action="'.getPHP_SELF().'?'.getSID().'" method="post">';
        $html.= '<tr>';
        $html.= '<td>'.html_escape($ar["divs"]).' '.html_escape($ar["name"]).'</td>';
        if ($ar["muid"]==$_POST["fMUID"]) {//編集対象
            foreach ($arMenu as $menu) {

                if($menu[4] && !DEBUG)//360カスタマイズ true の権限は編集できない
                    continue;
                $html.= '<td><input type="checkbox" name="ok[]" value="'.$menu[1].'" '.
                            (strstr($ar["permitted"],$menu[1])==false? "":" checked").
                            '></td>';
            }
        } else {
            $c=0;unset($lastcategory);
            foreach ($arMenu as $menu) {

                if($menu[4] && !DEBUG)//360カスタマイズ true の権限は編集できない
                    continue;

                if ($lastcategory<>$menu[0]) ++$c;
                $html.= '<td'.(($c%2)==0? "":" bgcolor=#f2f2f2").'>'.(strstr($ar["permitted"],$menu[1])==false? "×":"○").'</td>';
                $lastcategory = $menu[0];
            }
        }
/*		//ボタン
        if ($ar["muid"]==$_POST["fMUID"]) {//編集対象
        $html.= '<td><input type="submit" name="main" value="更新" class="imgbutton35"></td>';
        $html.= '<td>すべてチェック <input type="checkbox" onChange="var elms = document.getElementsByName(\'ok[]\'); for (var i=0; i<elms.length; ++i) { elms[i].checked = this.checked; }"></td>';
        } else {
        $html.= '<td><input type="submit" name="main" value="編集" class="imgbutton35"></td>';
        $html.= '<td><br></td>';
        }
*/		$html.= '<input type="hidden" name="fMUID" value="'.$ar["muid"].'">';
        //ボタン
        $html.= '</tr>';
        $html.= '</form>';
    }
    $html.= '</table>';

$objHtml = new MreAdminHtml("ページ権限管理(閲覧のみ)");
echo $objHtml->getMainHtml($html);
exit;
