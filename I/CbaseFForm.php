<?php
/*
 * フォーム関係を扱うクラス
 * 日　付：2006/09/04
 * 作成者：Cbase akama
 *
 * 2008/11/19 escape機能を追加
 */

/**
 * 過去のバージョンではエスケープしていなかったので、整合性のため切り替え式とする。1でescape実行
 */
define('FFORM_ESCAPE', 1);

// 年月日出力の開始年と終了年
define("FFORM_YEAR_START"	, -50);
define("FFORM_YEAR_END"		, 0);

/**
 * フォーム関係を扱うクラス
 * 日　付：2006/09/04
 * 作成者：Cbase akama
 * @package Cbase.Research.Lib
 */
class FForm
{
    /* ========================================================================
     *
     * 　基本フォーム作成
     *
     * ========================================================================
     */

    /**
     * textareaのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function textarea($prmName, $prmValue="", $prmExt="")
    {
        if ($prmExt) {
            $prmExt = " ".$prmExt;
        }
        $sValue = FForm_escape($prmValue);
        $sName = FForm_escape($prmName);

        return <<<HTML
<textarea name="{$sName}"{$prmExt}>{$sValue}</textarea>\n
HTML;
    }

    /**
     * inputタグを出力
     * @param  string $prmType  type
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmText  表示text
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function input($prmType, $prmName, $prmValue, $prmText="", $prmExt="", $prmId="")
    {
        $sValue = FForm_escape($prmValue);
        $sName = FForm_escape($prmName);

        $prmId = ($prmId)? FForm_escape($prmId): $sName;

        if ($prmExt) {
            $prmExt = " ".$prmExt;
        }

        if ($prmText) {
            $prmText = <<<HTML
<label for="{$prmId}">$prmText</label>\n
HTML;
        }

        return <<<HTML
<input type="{$prmType}" id="{$prmId}" name="{$sName}" value="{$sValue}"{$prmExt} />
{$prmText}
HTML;
    }

    /**
     * inputタグをグループで出力（checkbox,radio用）
     * @param  string $prmType  type
     * @param  string $prmName  name
     * @param  array  $prmValue value => 表示テキストの配列
     * @param  string $prmExt   styleなど
     * @return array  htmlの配列
     */
    public function inputlist($prmType, $prmName, $prmValue, $prmExt="")
    {
        $aryHtml = array();
        foreach ($prmValue as $key => $val) {
            $aryHtml[] = FForm::input($prmType, $prmName, $key, $val, $prmExt, $prmName.$key);
        }

        return $aryHtml;
    }

    /**
     * submitのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function submit($prmName, $prmValue, $prmExt="")
    {
        return FForm::input("submit", $prmName, $prmValue, "", $prmExt);
    }

    /**
     * buttonのタグを出力
     * @param	name
     *			value
     *			styleなど
     * @return html
     */
    public function button($prmName, $prmValue, $prmExt="")
    {
        return FForm::input("button", $prmName, $prmValue, "", $prmExt);
    }

    /**
     * hiddenのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmText  表示text
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function hidden($prmName, $prmValue, $prmText="", $prmExt="")
    {
        return FForm::input("hidden", $prmName, $prmValue, $prmText, $prmExt);
    }

    /**
     * textのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmText  表示text
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function text($prmName, $prmValue="", $prmText="", $prmExt="")
    {
        return FForm::input("text", $prmName, $prmValue, $prmText, $prmExt);
    }

    /**
     * passwordのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmText  表示text
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function password($prmName, $prmValue="", $prmText="", $prmExt="")
    {
        return FForm::input("password", $prmName, $prmValue, $prmText, $prmExt);
    }

    /**
     * fileのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmText  表示text
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function file($prmName, $prmValue="", $prmText="", $prmExt="")
    {
        return FForm::input("file", $prmName, $prmValue, $prmText, $prmExt);
    }

    /**
     * radioのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmText  表示text
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function radio($prmName, $prmValue, $prmText="", $prmExt="")
    {
        return FForm::input("radio", $prmName, $prmValue, $prmText, $prmExt, $prmName.$prmValue);
    }

    /**
     * radioタグをグループで出力
     * @param  string $prmName  name
     * @param  array  $prmValue value => 表示テキストの配列
     * @param  string $prmExt   styleなど
     * @return array  htmlの配列
     */
    public function radiolist($prmName, $prmValue, $prmExt="")
    {
        return FForm::inputlist("radio", $prmName, $prmValue, $prmExt);
    }

    /**
     * checkboxのタグを出力
     * @param  string $prmName  name
     * @param  string $prmValue value
     * @param  string $prmText  表示text
     * @param  string $prmExt   styleなど
     * @return string html
     */
    public function checkbox($prmName, $prmValue, $prmText="", $prmExt="")
    {
        return FForm::input("checkbox", $prmName, $prmValue, $prmText, $prmExt, $prmName.$prmValue);
    }

    /**
     * checkboxタグをグループで出力
     * @param  string $prmName  name
     * @param  array  $prmValue value => 表示テキストの配列
     * @param  string $prmExt   styleなど
     * @return array  htmlの配列
     */
    public function checkboxlist($prmName, $prmValue, $prmExt="")
    {
        return FForm::inputlist("checkbox", $prmName, $prmValue, $prmExt);
    }

    /**
     * checkboxタグをグループで出力
     * @param  string $prmName  name
     * @param  array  $prmValue value => 表示テキストの配列
     * @param  string $prmExt   styleなど
     * @return array  htmlの配列
     */
    public function checkboxlistDef($prmName, $prmValue, $defaults, $prmExt="")
    {
        $res = FForm::inputlist("checkbox", $prmName, $prmValue, $prmExt);
        if (is_array($defaults)) {
            foreach ($defaults as $v) {
                $res = FForm::replaceArrayChecked($res, $v);
            }
        }

        return $res;
    }

    /**
     * optionのタグを出力
     * @param  array  $prmValue value => 表示テキストの配列
     * @return string html
     */
    public function option($prmValue)
    {
        $strHtml = "";
        foreach ($prmValue as $key => $val) {
            $key = FForm_escape($key);
            $strHtml .= <<<HTML
<option value="{$key}">{$val}</option>\n
HTML;
        }

        return $strHtml;
    }

    /**
     * optionのタグを出力
     * @param  array  $prmValue valueの配列
     * @return string html
     */
    public function option2($prmValue)
    {
        $strHtml = "";
        foreach ($prmValue as $val) {
            $val = html_escape($val);
            $strHtml .= <<<HTML
<option value="{$val}">{$val}</option>\n
HTML;
        }

        return $strHtml;
    }

    /**
     * selectタグを出力
     * @param  string  $prmName   name
     * @param  array   $prmValue  value => 表示テキストの配列
     * @param  string  $prmExt    styleなど
     * @param  boolean $prmOption option() or option2()
     * @return string  html
     */
    public function select($prmName, $prmValue, $prmExt="", $prmOption=true)
    {
        if ($prmExt) {
            $prmExt = " ".$prmExt;
        }
        $optionHtml = ($prmOption)? FForm::option($prmValue) : FForm::option2($prmValue);
        $prmName = FForm_escape($prmName);

        return <<<HTML
<select name="{$prmName}"{$prmExt}>{$optionHtml}</select>\n
HTML;
    }

    /**
     * selectタグを出力
     * @param  string  $prmName   name
     * @param  array   $prmValue  value => 表示テキストの配列
     * @param  string  $default   デフォルト
     * @param  string  $prmExt    styleなど
     * @param  boolean $prmOption option() or option2()
     * @return string  html
     */
    public function selectDef($prmName, $prmValue, $default, $prmExt="", $prmOption=true)
    {
        $sel = FForm::select($prmName, $prmValue, $prmExt, $prmOption);

        return FForm::replaceSelected($sel, $default);
    }



    /**
     * データをフォームタグで囲む
     * @param  string $prmValue  囲むデータ
     * @param  string $prmAction action
     * @param  string $prmExt    その他データ
     * @param  string $prmMethod method
     * @return string html
     */
    public function form($prmValue, $prmAction, $prmExt="", $prmMethod="POST")
    {
        if ($prmExt) {
            $prmExt = " ".$prmExt;
        }

        return <<<HTML
<form action="{$prmAction}" method="{$prmMethod}"{$prmExt}>{$prmValue}</form>
HTML;
    }

    /* ========================================================================
     *
     * 　汎用関数
     *
     * ========================================================================
     */

     /**
     * $prmHtmlに含まれるvalue=$prmValueの時$prmStatusを付与
     * @param  string $prmHtml   html
     * @param  string $prmValue  value
     * @param  string $prmStatus checked or selected
     * @return string html
     */
    public function replaceStatus($prmHtml, $prmValue, $prmStatus)
    {
        $prmValue = str_replace(")", "\)", str_replace("(", "\(", $prmValue));

        return isset($prmValue)? ereg_replace('(value="'.$prmValue.'")', "\\1 ".$prmStatus, $prmHtml):$prmHtml;
    }

    /**
     * $prmHtmlに含まれるvalue=$prmValueの時checkedを付与
     * @param  string $prmHtml  html
     * @param  string $prmValue value
     * @return string html
     */
    public function replaceChecked($prmHtml, $prmValue)
    {
        return FForm::replaceStatus($prmHtml, $prmValue, "checked");
    }

    /**
     * $prmArrayに含まれるvalue=$prmValueの時checkedを付与
     * @param  array  $prmArray array of html
     * @param  string $prmValue value
     * @return string html
     */
    public function replaceArrayChecked($prmArray, $prmValue)
    {
        foreach ($prmArray as $key => $val) {
            $prmArray[$key] = FForm::replaceChecked($val, $prmValue);
        }

        return $prmArray;
    }

    /**
     * $prmHtmlに含まれるvalue=$prmValueの時selectedを付与
     * @param  string $prmHtml  html
     * @param  string $prmValue value
     * @return string html
     */
    public function replaceSelected($prmHtml, $prmValue)
    {
        return FForm::replaceStatus($prmHtml, $prmValue, "selected");
    }

    /**
     * $prmArrayに含まれるvalueが一致するselectに全てselectedを付与
     * @param  string $prmHtml  html
     * @param  string $prmArray value
     * @return string html
     */
    public function replaceArraySelected($prmHtml, $prmArray)
    {
        foreach ($prmArray as $key => $val) {
            $prmHtml = FForm::replaceStatus($prmHtml, $val, "selected");
        }

        return $prmHtml;
    }

    /**
     * $prmHtmlに含まれるform要素を無効化
     * @param  string $prmHtml html
     * @return string html
     */
    public function replaceDisabled($prmHtml)
    {
        return ereg_replace("<(input|select|textarea)", "<\\1 disabled", $prmHtml);
    }

    /* ========================================================================
     *
     * 　汎用フォーム作成
     *
     * ========================================================================
     */

    /**
     * 性別のラジオボタンを取得(今回に関してはあまり意味が無い)
     * @param  string $prmName    name
     * @param  string $prmDefault デフォルト値
     * @return array  htmlの配列
     */
    public function getFormSex($prmName, $prmDefault)
    {
        $aryValue = array(
            "男性" => "男性",
            "女性" => "女性"
        );

        return FForm::replaceArrayChecked(FForm::radiolist($prmName, $aryValue), $prmDefault);
    }

    /**
     * 都道府県のセレクトボックスを取得
     * @param  string $prmName    name
     * @param  string $prmDefault デフォルト値
     * @return string html
     */
    public function getFormPref($prmName, $prmDefault)
    {
        global $pref;

        return FForm::replaceSelected(FForm::select($prmName, $pref, "", false), $prmDefault);
    }

    /**
     * 誕生日のセレクトボックスを取得
     * @param  array  $nameAry    array of name
     * @param  array  $defaultAry array of default
     * @return string html
     */
    public function getFormBirth($aryName, $aryDefault)
    {
        global $yyyy, $mm, $dd;
        if(!$aryDefault["yyyy"])		$aryDefault["yyyy"] = $yyyy[count($yyyy)-1];
        $strHtml = FForm::replaceSelected(FForm::select($aryName."[yyyy]", $yyyy, "", false), $aryDefault["yyyy"])."年";
        $strHtml .= FForm::replaceSelected(FForm::select($aryName."[mm]", $mm, "", false), $aryDefault["mm"])."月";
        $strHtml .= FForm::replaceSelected(FForm::select($aryName."[dd]", $dd, "", false), $aryDefault["dd"])."日";

        return $strHtml;
    }

    /**
     * 連番数字 + 文字列のフォームを取得
     * @param  string $prmName    name
     * @param  int    $prmStart   開始値
     * @param  int    $prmEnd     終了値
     * @param  string $prmText    付与文字列
     * @param  string $prmDefault デフォルト値
     * @param  bool   $prmNoPoint 「指定しない」の表示
     * @param  int    $prmDigit   桁数指定（0で埋まる）
     * @return string html
     *
     */
    public function getFormNumber($prmName, $prmStart, $prmEnd, $prmText="", $prmDefault="", $prmNoPoint=false, $prmDigit=0)
    {
        if ($prmNoPoint) {
            $aryVal[0] = "-";
        }
        for ($i=$prmStart; $i<=$prmEnd; $i++) {
            $intVal = sprintf("%0".$prmDigit."d", $i);
            $aryVal[$intVal] = $i.$prmText;
        }

        return FForm::replaceSelected(FForm::select($prmName, $aryVal), $prmDefault);
    }

    /**
     * 年月日のフォームを取得
     * @param  string $prmName    name
     * @param  string $prmDefault $prmDefault デフォルト値　"now","y-m-d"またはなし
     * @param  bool   $prmNoPoint 指定なしを表示する
     * @return string html
     */
    public function getFormDate($prmName, $prmDefault="", $prmNoPoint=false)
    {
        $aryTmpDate = explode("-", $prmDefault);
        if (1 < count($aryTmpDate)) {
            $aryDate = $aryTmpDate;
        }

        $intNowYear = date("Y");
        $intStartYear = $intNowYear + FFORM_YEAR_START;
        $intEndYear = $intNowYear + FFORM_YEAR_END;
        // デフォルト値の反映
        if ($aryDate[0]) {
            $strYearDef = $aryDate[0];
        } elseif ($prmDefault=="now") {
            $strYearDef = $intNowYear;
        }
        $strYear = FForm::getFormNumber($prmName."_y", $intStartYear, $intEndYear, "年", $strYearDef, $prmNoPoint);

        // 月の出力
        // デフォルト値の反映
        if ($aryDate[1]) {
            $strMonthDef = $aryDate[1];
        } elseif ($prmDefault=="now") {
            $strMonthDef = date("m");
        }
        $strMonth = FForm::getFormNumber($prmName."_m", 1, 12, "月", $strMonthDef, $prmNoPoint, 2);

        // 日の出力
        // デフォルト値の反映
        if ($aryDate[2]) {
            $strDayDef = $aryDate[2];
        } elseif ($prmDefault == "now") {
            $strDayDef = date("d");
        }
        $strDay = FForm::getFormNumber($prmName."_d", 1, 31, "日", $strDayDef, $prmNoPoint, 2);

        $strHTML = $strYear.$strMonth.$strDay;

        return $strHTML;
    }

    /**
     * データに含まれる年月日データを取得
     * @param  array  $prmData データ（$_POSTを放り込むことを想定）
     * @param  string $prmName FORMの名前
     * @param  string $prmMode date形式(Y-m-dなど)又は"array"
     * @return mixed  mode:date形式=指定した形式の文字列 mode:array=ymdの配列
     */
    public function getValueDate($prmData, $prmName, $prmMode)
    {
        if ($prmData[$prmName."_y"] && $prmData[$prmName."_m"] && $prmData[$prmName."_d"]) {
            if ($prmMode == "array") {
                $result["y"] = $prmData[$prmName."_y"];
                $result["m"] = $prmData[$prmName."_m"];
                $result["d"] = $prmData[$prmName."_d"];
            } else {
                $result = date($prmMode, mktime(0,0,0, $prmData[$prmName."_m"], $prmData[$prmName."_d"], $prmData[$prmName."_y"]));
            }
        }

        return $result;
    }
}

function FForm_escape($value)
{
    if (FFORM_ESCAPE) {
        return html_escape($value);
    }

    return $value;
}
