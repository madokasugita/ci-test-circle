<?php
/**
 * C.php
 * @package Cbase.Research.Lib
 */
/*
サンプル
cond3 設問間制御

    【順位制御】
        第一位に入れるの設問のcond3へ
        or,false,順位が重複してます,147:a,148:a

        第二位が必須ならばこちらにも入れる
        or,false,順位が重複してます,146:a,148:a

        ※or,flaseは固定,エラー時メッセージ,自分以外のseid:a,自分以外のseid:a,･･･

    【設問間論理チェック】
        潤ｵある設問のある選択肢を選んでいて、
        　かつ自問の指定以外の選択だった場合にエラー(return false)とする

(合致していたときに何を返すか、で第二引数を決める)

###現在 まっとうには対応していない		or,false,選択が矛盾しております,142:1,140:0
        lcacond3,false,選択が矛盾しております,142:1,140:0
            # lcacond3,false,選択が…,自問seid:選択肢, ターゲット問seid:選択肢

cond4 自問制御

    ※142は自分のseid例

    【論理チェック】
        or,true,回答が正しくありません,142:0,142:1
            ※142は自分のseid例

              orはor条件,and条件のときはandとする
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　自分のseid:(選択肢番号),自分のseid:(選択肢番号),

    【最小値】
        min,true,○○以上の値を入れてください,1

              min
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　最小値 (回答が、ここで指定する最小値以上だったら正しい)

    【最大値】
        max,true,○○以下の値を入れてください,100

              max
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　最大値 (回答が、ここで指定する最大値以下だったら正しい)

    【長制限】
        len,false,○○文字以内で記述ください,200

              max
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　最大文字数 (回答が、ここで指定する最大文字数以下だったら正しい)
                            (2byteも一文字としてカウント)
*/

/**
 * cond3,4用 条件チェック
 * @param array $prmSession セッション
 * @param array $prmCond 条件
 * @param array $prmAnser 条件質問に対する回答
 * @return array 結果とエラーメッセージ
 */
function getCheckCond($prmSession,$prmCond,$prmAnswer)
{
if (SHOW_ABOLITION) {
    echo 'C::getCheckCondは廃止予定関数です。<hr>';
}
    //引数エラー
    /*
    if (!$prmAnswer) return true;	////////////trueで良い？
    if (!$prmCond) return true;				////////////trueで良い？
    if (count($aryCond)>=4) return true;		////////////trueで良い？
    */

    $aryCond =getParseCond($prmCond);//prmCondを配列で取得
    $strType =$aryCond[0];
    $blAndOr =$aryCond[0]=="and"? true:false;
    $blReturn=$blAndOr===true? true:false;//And条件の場合はfalseを判断するため初期true
    $blBool  =$aryCond[1]=="true"? true:false;
        //$blBool==trueの時、条件に合致していればtrueを返す
        //$blBool==falseの時、条件に合致していればfalseを返す
    $strMsg  = $aryCond[2];//エラーメッセージ

    switch ($strType) {
        case "min":
            if (!ereg("[^0-9]",$aryCond[3])
                &&
                $aryCond[3]>$prmAnswer
                ) {//最小値制限をクリアしない
                    $blReturn=true;
                //} else {//制限クリアまたは設定ミス(ereg)
                }
            break;
        case "max":
            if (!ereg("[^0-9]",$aryCond[3])
                &&
                $aryCond[3]<$prmAnswer
                ) {//最大値制限をクリアしない
                    $blReturn=true;
                //} else {//制限クリアまたは設定ミス(ereg)
                }
            break;
        case "len":
            if (!ereg("[^0-9]",$aryCond[3])
                &&
                $aryCond[3]<mb_strlen($prmAnswer)
                ) {//最大文字数制限をクリアしない
                    $blReturn=true;
                //} else {//制限クリアまたは設定ミス(ereg)
                }
            break;
        case "lcacond3":
                $aryTmp1=explode(":",$aryCond[3]);
                $aryTmp2=explode(":",$aryCond[4]);
                $strKey   ="P_".$aryTmp2[0];
                if (in_array($aryTmp1[1],$prmAnswer) //自問:選択肢が回答されてて
                    &&
                    in_array($aryTmp2[1],$prmSession[$strKey])
                    )
                    {
                    $blReturn=true;
                }
            #	lcacond3,false,選択が矛盾しております,142:1,140:0
            # 自問:選択肢, ターゲット問:選択肢
                break;
        default:
            //$i=3はand/or,true/false,messageを飛ばす為
            for ($i=3;$i<count($aryCond);++$i) {
                //処理中の条件をarray("seid","answer number")へ
                $aryTmp=array();
                $aryTmp=explode(":",$aryCond[$i]);
                //処理中の条件(seid)のセッションキーをセット
                $strKey="";
                $strKey="P_".$aryTmp[0];
                $intNum="";
                $intNum=$aryTmp[1];
                if ($intNum=="a"
                    &&
                    is_array($prmAnswer)
                    &&
                    !array_diff($prmAnswer,$prmSession[$strKey])
                    )  {//回答モードcond3で使用 c,r
                        //OR条件の場合は一致したらすぐに抜ける
                        if ($blAndOr===false) {
                            $blReturn=true;
                            break;
                        }
                } elseif ($intNum=="a"
                        &&
                            $prmAnswer==$prmSession[ $strKey ]
                            ) {//回答モードcond3で使用 c,r
                            //OR条件の場合は一致したらすぐに抜ける
                            if ($blAndOr===false) {
                                $blReturn=true;
                                break;
                            }
                } elseif (!ereg("[^0-9]",$intNum)
                            &&
                            //データが入ってなかったらfunc
                            in_array($intNum,$prmAnswer)
                            ) {
                        //OR条件の場合は一致したらすぐに抜ける
                        if ($blAndOr===false) {
                            $blReturn=true;
                            break;
                        }
                } else {
                    //AND条件の場合は一度でも不一致ならばすぐに抜ける
                    if ($blAndOr===true) {
                        $blReturn=false;
                        break;
                    }
                }
            }//for
    }//switch

    if ($blBool===true) {//条件に合致→trueを返す
        if ($blReturn===true) return array(true,$strMsg);
                              return array(false,$strMsg);
    } else {//条件に不合致→true返し
        if ($blReturn===false) return array(true,$strMsg);
                              return array(false,$strMsg);
    }

}

/**
 * 条件の設定値の分解
 * @param string $prmCond cond3/4の設定値
 * @return array 設定値をexplodeしたもの
 */
function getParseCond($prmCond)
{
if (SHOW_ABOLITION) {
    echo 'C::getParseCondは廃止予定関数です。<hr>';
}

    if ($prmCond=="") return false;
    /*
        cond3の配列
            0	and/or	一致条件
            1	true/false	一致時にtrueを返すかfalseを返すか
            2	エラーメッセージ
            3	(seid):(choice number 0始まり)
            4-	3同様
    */

    return explode(",",$prmCond);
}

//r,cのみ対応 (プルダウンは×)
/**
 * 論理チェック違反回答データのクリア
 * @param string $prmCond 条件設定値
 * @param array $prmAnswer 条件質問に対する回答
 * @return array 論理チェック違反データクリア後の回答
 */
function getCond4Clear($prmCond,$prmAnswer)
{
if (SHOW_ABOLITION) {
    echo 'C::getCond4Clearは廃止予定関数です。<hr>';
}
    $aryCond =getParseCond($prmCond);//prmCondを配列で取得
    $aryReturn=array();//論理チェック後の答え
    for ($i=3;$i<count($aryCond);++$i) {
        //処理中の条件をarray("seid","answer number")へ
        $aryTmp=array();
        $aryTmp=explode(":",$aryCond[$i]);
        //処理中の条件(seid)のセッションキーをセット
        if (in_array($aryTmp[1],$prmAnswer)) {
            $aryReturn[]=(string) $aryTmp[1];
        }
    }

    return $aryReturn;
}
