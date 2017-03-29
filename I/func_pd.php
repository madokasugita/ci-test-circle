<?php
/**
 * PG名称：プルダウン関数
 * 日  付：2005/04/21
 * 作成者：cbase Akama
 * @package Cbase.Research.Lib
 */

//定数
/*
 * 解説
 * 右辺文字列が有効な場合、{{{{　}}}}　で囲まれた範囲の中の
 * STRINGが右辺文字列に置き換えられる。
 * 無効の場合は{{{{ }}}}の範囲が削除される。
 *
 */
$C_OPERATOR_SET = array(
    "op0" => array(
        "show"  => "＝(等しい)",
        "value" => "={{{{ 'STRING'}}}}"
    ),
    "op1" => array(
        "show"  => "<>(等しくない)",
        "value" => "<>{{{{ 'STRING'}}}}"
    ),
    "op2" => array(
        "show"  => "≧(以上)",
        "value" => ">={{{{ 'STRING'}}}}"
    ),
    "op3" => array(
        "show"  => "＞(より大きい)",
        "value" => ">{{{{ 'STRING'}}}}"
    ),
    "op4" => array(
        "show"  => "≦(以下)",
        "value" => "<={{{{ 'STRING'}}}}"
    ),
    "op5" => array(
        "show"  => "＜(未満)",
        "value" => ">{{{{ 'STRING'}}}}"
    ),
    "op6" => array(
        "show"  => "前方一致",
        "value" => "LIKE{{{{ 'STRING%'}}}}"
    ),
    "op7" => array(
        "show"  => "後方一致",
        "value" => "LIKE{{{{ '%STRING'}}}}"
    ),
    "op8" => array(
        "show"  => "全文一致",
        "value" => "LIKE{{{{ '%STRING%'}}}}"
    )
 );

$C_OPERATOR_AFTER = array(
    "op2" => array(
        "show"  => "当日から",
        "value" => ">={{{{ 'STRING'}}}}"
    ),
    "op3" => array(
        "show"  => "翌日から",
        "value" => ">{{{{ 'STRING'}}}}"
    ),
);

$C_OPERATOR_BEFORE = array(
    "op4" => array(
        "show"  => "当日まで",
        "value" => "<={{{{ 'STRING'}}}}"
    ),
    "op5" => array(
        "show"  => "前日まで",
        "value" => ">{{{{ 'STRING'}}}}"
    ),
);

/**
 * tableからプルダウンを取得。
 * @param string $prmTable 取得するtable
 * @param string $prmName プルダウンのname
 * @param string $prmStyle styleなどを記述した文字列	※省略可能
 * @param int $prmPos 選択肢の初期位置(key)	※省略可能
 * @return string html
 */
function getSelectHtml($prmTable,$prmName,$prmStyle="",$prmPos="")
{
    $strResult = '
        <select name="'.$prmName.'"';
        if ($prmStyle <> "") {
            $strResult.= ' '.$prmStyle;
        }
    $strResult.= '>';
    foreach ($prmTable as $key => $value) {
        $strResult.= '<option value="'.$key.'"';
        if ($key == $prmPos) {
            $strResult.= ' selected';
        }
        $strResult.= '>'.$value["show"].'</option>';
    }
    $strResult.= '
        </select>
';

    return $strResult;
}

/**
 * tableのkeyを右辺文字列を含めて記号に変換。
 * @param string $prmTable 取得するtable
 * @param string $prmKey key
 * @param string $prmStr 右辺文字列	※省略可能
 * @return string html
 */
function getSelectValue($prmTable,$prmKey,$prmStr = "")
{
    if ($prmStr <>"") {
        $str = ereg_replace("({{{{)|(}}}})","",$prmTable[$prmKey]["value"]);
        $str = ereg_replace("(STRING)",$prmStr,$str);
    } else {
        $str = ereg_replace("({{{{.*}}}})","",$prmTable[$prmKey]["value"]);

    }

    return $str;
//	return ereg_replace("({{{{STRING}}}})","'".$prmStr."'",$prmTable[$prmKey]["value"]);
}

/* テストデータ　兼　サンプル

if ($_POST["main"]) {
    //getSelectValue(表示内容の配列,プルダウンで選択した番号,右辺に接続する文字列)
    echo getSelectValue($C_OPERATOR_SET,$_POST["pd"],"test");

}

echo '<form action='.getPHP_SELF().' method="post" encType=multipart/form-data>';
//	getSelectHtml(表示内容の配列,inputタグのname,style指定など（省略可）,初期位置（省略可）);
echo getSelectHtml($C_OPERATOR_SET,"pd");
echo '<input type="submit" name="main" value="OK" />';
echo '</form>';
*/
