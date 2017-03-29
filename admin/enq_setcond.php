<?php

//subeventのデータを更新する

define('DIR_ROOT', "../");
require_once(DIR_ROOT."crm_define.php");
require_once(DIR_LIB."CbaseFEnquete.php");
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

if (is_void($_GET['evid'])) {
    $_GET['evid'] = $_POST['evid'];
    $_GET['seid'] = $_POST['seid'];
}

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));
$evid = Check_AuthMngEvid($_GET['evid']);
$seid = $_GET['seid'];

//アンケートデータ取得
$array1 = Get_Enquete("id",$evid,"","");

if (!$seid) $seid = $array1[0][0]["seid"];

$self = getPHP_SELF();
for ($i=0;$i<count($array1[0]);++$i) {
    if ($array1[0][$i]["seid"]==$seid||$i==(count($array1[0]))-1) {
        $html0.= '<div align="center">';
        if ($i==0) {
        } elseif ($seid=="new") {
        $html0.= '|| ';
        } else {
        $html0.= '<a href="'.$self.'?seid='.$array1[0][$i-1]["seid"]. '&'.getSID().'&evid='.$evid.'"><< </a>';
        }
        $html0.= '<a href="'.$self.'?seid='.$seid.'&'.getSID().'&evid='.$evid.'"> [Refresh] </a>';
        if ($array1[0][$i+1]["seid"]) {
        $html0.= '<a href="'.$self.'?seid='.$array1[0][$i+1]["seid"].'&'.getSID().'&evid='.$evid.'">>> </a>';
        } else {
            $html0.= '<i> ||</i>';
        }
        $html0.= '</div>';
        break;
    }
}

//更新
if ($_POST["main"]) {
    unset($arr);
    unset($array);
    $arr = Get_SubEnquete("id",$seid,$evid);
    $array=$arr[0];
//セットされた条件を抽出してserialyze
    //cond
    $array["cond"] = Build_CondForm($_POST);
    //cond2
    $array["cond2"]= Build_Cond2Form($_POST);
    //cond3
    $array["cond3"]= $_POST["cond3"];
    //cond4
    $array["cond4"]= $_POST["cond4"];
    //cond5
    $array["cond5"]= rtrim(ereg_replace("(\r\n|\n)",",",$_POST["cond5"]),"\r\n");//改行コードをカンマに変更
    Save_SubEnquete("update",$array);
    unset($array);
    unset($array1);
    $array1 = Get_Enquete("id",$evid,"","");
unlink(DIR_DATA.$array1[-1]["rid"].".enqarray");
}

//対象のデータ取得
if ($seid<>"new") {
    foreach ($array1[0] as $ar) {
        if ($ar["seid"]==$seid) {
                $array=$ar;
                break;
        }
    }
}
//フォーム表示
$html0 .= '<form action="'.getPHP_SELF().'?seid='.$seid.'&'.getSID().'&evid='.$evid.'" method="post">';
$html0 .= '<input type="submit" name="main" value="　　保　　存　　"><br>';
foreach ($array as $key=>$val) {
    if ($key=="evid"||$key=="seid"||$key=="page") {
        continue;
    }
    if ($key=="title") {
        $html2.=<<<HTML
  <table width="700" border="0" cellpadding="6" cellspacing="1" bgcolor="#aaccaa" style="font-size:14px;">
    <tr>
      <td bgcolor="#ddeedd" width=120>[タイトル]</td>
      <td bgcolor="#ffffff">{$val}</td>
    </tr>
  </table>
  <br>
HTML;
        continue;
    }
    if ($key=="cond") {
        $html41 .= Create_CondForm($val,$array1[0],$seid,$array["page"]);
    } elseif ($key=="cond2") {
        $html4 .= '<tr><td bgcolor="#ddeedd">';
        $html4 .= '['.$key.'] : この設問に対する回答制御</td></tr>';
        $html4 .= '<tr><td bgcolor="#ffffff">';
        $html4 .= Create_Cond2Form($val,$array1[0],$seid);
        $html4 .= '</td></tr>';
    } elseif ($key=="cond3") {
        $html4 .= '<tr><td bgcolor="#ddeedd">';
        $html4 .= '['.$key.'] : 複数の設問にわたる条件</td></tr>';
        $html4 .= '<tr><td bgcolor="#ffffff">';
        $html4 .= '<input type="text" name="cond3" value="'.$val.'" size=80>';
        $html4 .= '<br>';
        $html4 .= '<font color="#d2d2d2">フォーマット:and/or,一致がtrue/false,message,seid:number,seid:number.......</font>';
        $html4 .= '<br>';
        $html4 .= '<font color="#d2d2d2">～numberではなく当人の回答にしたい場合は、numberを"a"として入力</font>';
        $html4 .= '</td></tr>';
    } elseif ($key=="cond4") {
        $html4 .= '<tr><td bgcolor="#ddeedd">';
        $html4 .= '['.$key.'] : 論理チェック(自問への対応)</td></tr>';
        $html4 .= '<tr><td bgcolor="#ffffff">';
        $html4 .= '<input type="text" name="cond4" value="'.$val.'" size=80>';
        $html4 .= '<br>';
        $html4 .= '<font color="#d2d2d2">フォーマット:and/or,true,message,seid:number,seid:number.......</font>';
        $html4 .= '<br>';
        $html4 .= '<font color="#d2d2d2">～指定した回答numberがあれば、指定以外の自問回答をクリアする</font>';
        $html4 .= '</td></tr>';
    } elseif ($key=="cond5") {
        $html4 .= '<tr><td bgcolor="#ddeedd">';
        $html4 .= '['.$key.'] : 選択肢絞込み</td></tr>';
        $html4 .= '<tr><td bgcolor="#ffffff">';
        $html4 .= '<textarea name="cond5" cols=40 rows=10>'.ereg_replace(",","\n",$val).'</textarea>';
        $html4 .= '<br>';
        $html4 .= "<font color=\"#d2d2d2\">フォーマット:[seid]:[seid's choice]:[choice.choice....],[seid......</font>";
        $html4 .= '<br>';
        $html4 .= '<font color="#d2d2d2">xA兼eidは一つだけ対応、そのseidは必須であることが前提</font>';
        $html4 .= '<font color="#d2d2d2">xA刑能蕕縫泪奪舛靴疹魴錣里發里覗・鮖菘験・/font>';
        $html4 .= '</td></tr>';
    } else {
        continue;
    }
}
//html4 header
$html4h='<table width="700" border="0" cellpadding="6" cellspacing="1" bgcolor="#aaccaa" style="font-size:14px;">';
//html4 footer
$html4f='</table>';
//submit
$html5 .= '<input type="hidden" name="evid" value="'.$evid.'">';
$html5 .= '<input type="hidden" name="seid" value="'.$seid.'">';
$html5 .= '<input type="submit" name="main" value="　　保　　存　　">';
$html5 .= '</form>';

$html = $html0.$html2.$html.$html41.$html4h.$html4.$html4f.$html5;

$objHtml =& new CbaseHtml("");
echo $objHtml->getMainHtml($html);
exit;

function Create_CondForm($cd,$array,$seid,$page)
{
    /*
        指定されたseidの表示条件を出力、設定用フォームを出す
    */
    if (!$seid) return "";
    //配列に戻す
    if ($cd) {
        $cond = unserialize($cd);
        if (!is_array($cond)) return "データ形式が壊れています。";
    }
    //前までの質問をforで回す
    $txt .= '<table width="700" border="0" cellpadding="5" cellspacing="1" bgcolor="#aaccaa" style="font-size:12px;line-height:1.6em;">';
    $txt .= '<tr><td bgcolor="#ffffff" colspan=2 align=left>表示条件 xA轡船Д奪・鯑・譴秦・鮖茲・・个譴討い觧・法・・潴笋・充┐気譴泙后・/td></tr>';
    for ($i=0;$i<count($array);++$i) {
        if ($seid==$array[$i]["seid"]) break;
//		if ($array[$i]["page"]<>($page-1)) continue;
//		if ($array[$i]["page"]==1) continue;
        $car = array();
        //設定しようとしているseidの条件データがセットされている場合...
        if ($cond) {
            foreach ($cond as $c) {
                //対象のseidの条件を配列にセット
                if (key($c)<>$array[$i]["seid"]) continue;
                //carには、処理中(ここのfor分11行上)のseid選択肢番号が入ってくる
                $car[] = current($c);
            }
        }
        //選択肢を配列にセット
        $tmp = explode(",",stripslashes($array[$i]["choice"]));
        switch ($array[$i]["type2"]) {
            //r,cの場合は出力
                //配列に該当のものがあればチェックを入れて出力
            case "p":
            case "r":
            case "c":
                    $txt.= '<tr>';
                    $txt.= '<td bgcolor="#ffffff">';
//					$txt.= $array[$i]["seid"].'/';
                    $txt.= $array[$i]["title"];
                    $txt.= '</td>';
                    $txt.= '<td bgcolor="#f5fff5">';
                    for ($m=0;$m<count($tmp);++$m) {
                        $txt.= '<input type="checkbox" name="C_'.$array[$i]["seid"].'[]"';
                        if (@in_array($m,$car)) $txt.= ' checked';
                        $txt.= ' value="'.$m.'">'.$tmp[$m].'<br>';
                    }
                    $txt.= '</td>';
                    $txt.= '</tr>';
                    break;
            case "t":
                    $txt.= '<tr>';
                    $txt.= '<td bgcolor="#ffffff">';
//					$txt.= $array[$i]["seid"].'/';
                    $txt.= $array[$i]["title"];
                    $txt.= '</td>';
                    $txt.= '<td bgcolor="#f5fff5">';
                        $txt.= '<input type="checkbox" name="C_'.$array[$i]["seid"].'[]"';
                        if ($car[0]=="500") $txt.= ' checked';
                        $txt.= ' value="500">この設問を条件として設定する<br>';
                    $txt.= '</td>';
                    $txt.= '</tr>';
                    break;
            default:
//					$txt.= "対応してません。".$array[$i]["title"];
                    break;
        }
    }
    $txt .= '</table><br>';

    return $txt;
}
function Create_Cond2Form($cd,$array,$seid)
{
    if (!$seid) return "";
    //配列に戻す
    if ($cd) {
        $cond = unserialize($cd);
        if (!is_array($cond)) return "データ形式が壊れています。";
    }
    for ($i=0;$i<count($array);++$i) {
        if ($seid<>$array[$i]["seid"]) continue;
        $ar = $array[$i];
    }

    //選択肢の最大回答数を指定
    if ($ar["type2"]=="c") {
        $txt.= '<tr>';
        $txt.= '<td>';
        $txt.= '最大選択数'.'<input type="text" name="C2_maxcount" value="'.$cond["maxcount"].'"><br>';
        $txt.= '※制限なしの場合は空欄';
        $txt.= '</td>';
        $txt.= '</tr>';
        $txt.= '<tr>';
        $txt.= '<td>';
        $txt.= '選択数制御'.'<input type="text" name="C2_equalcount" value="'.$cond["equalcount"].'"><br>';
        $txt.= '※「３ヶご選択ください。」などに使用します';
        $txt.= '</td>';
        $txt.= '</tr>';
    }
    //その他記入欄を必須にする回答番号を指定
    if (($ar["type2"]=="c"||$ar["type2"]=="r"||$ar["type2"]=="p")&& $ar["other"]==1) {
        $tmp = explode(",",stripslashes($ar["choice"]));
        $txt.= '<tr>';
        $txt.= '<td>';
        $txt.= '記入回答必須となる選択肢';
        $txt.= '<select name="C2_other">';
        $txt.= '<option value="ng">－－－</option>';
        for ($m=0;$m<count($tmp);++$m) {
            $txt.= '<option value="'.$m.'"';
            if (($cond["other"]=="0"||!empty($cond["other"]))&& $m==$cond["other"]) $txt.= ' selected';
            $txt.= '>'.$tmp[$m].'</option>';
        }
        $txt.= '</select>';
        $txt.= '</td>';
        $txt.= '</tr>';
    }
    $txt0 .= '<table border=1 bordercolor="#00A55A">';
    $txt2 .= '</table>';
    if (!$txt) return "";
    return $txt0.$txt.$txt2;
}
function Build_CondForm($post)
{
    //前までの質問をforで回す
    foreach ($post as $k=>$v) {
        if (!ereg("^C_",$k)) continue;
        $key = ereg_replace("C_","",$k);

        if ($v[0]=="ng") continue;

        foreach ($v as $value) {
            if (isset($value)) {
                $condar = array();
                $condar[$key] = $value;
                $txt[] = $condar;
            }
        }
    }
    if (!$txt) return "";
    return serialize($txt);
}
function Build_Cond2Form($post)
{
    //前までの質問をforで回す
    foreach ($post as $k=>$v) {
        if (!ereg("^C2_",$k)) continue;
        $key = ereg_replace("C2_","",$k);

        if ($v=="ng") continue;

        if ($key=="maxcount"&&!empty($v)&&!ereg("[^0-9]",$v)) $txt["maxcount"] = (int) $v;
        if ($key=="equalcount"&&!empty($v)&&!ereg("[^0-9]",$v)) {
            $txt["equalcount"] = (int) $v;
            unset($txt["maxcount"]);
        }
        if ($key=="other"&&(!empty($v)||$v=="0")) $txt["other"]	 = $v;
    }
    if (!$txt) return "";
    return serialize($txt);
}
