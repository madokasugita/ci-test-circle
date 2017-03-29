<?php

//event処理用

define('DIR_ROOT', '../');

require_once (DIR_ROOT . "crm_define.php");
require_once (DIR_LIB . "CbaseFGeneral.php");
require_once (DIR_LIB . "CbaseFEnquete.php");
require_once (DIR_LIB . "CbaseFEvent.php");
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));
$evid = Check_AuthMngEvid($_GET['evid']);

$_SESSION["ed"] = Get_Enquete("id", $evid, "", "",$_SESSION['muid']);

$_SESSION["evid"] = $_SESSION["ed"][-1]["evid"];

$html .= '<table>';

foreach ($_SESSION["ed"][0] as $v) {
    //確認画面は表示しない
    if ($v["type2"] == "t" or $v["type1"] == "0")
        continue;

    $html .= '<tr>';
    $html .= "<td colspan=2>";
    $html .= '【' . $v["title"] . '】';
    $html .= "</td>";
    $html .= '</tr>';

    if (!is_void($v["choice"])) {
        $ca = explode(",", $v["choice"]);
        for ($i = 0; $i < count($ca); $i++) {
            $html .= '<tr>';
            $html .= "<td>";
            $html .= ($i +1);
            $html .= "</td>";
            $html .= "<td>";
            $html .= $ca[$i];
            $html .= "</td>";
            $html .= '</tr>';
        }
    }
}
$html .= '</table>';

$objHtml =& new CbaseHtml("選択肢一覧");
echo $objHtml->getMainHtml($html);
exit;
