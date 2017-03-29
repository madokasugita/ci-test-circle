<?php
/**
 * チェック関数集（インスタンス化せずに使う）
 * 内容：入力文字列に対する一般的なチェック関数をまとめたもの
 * 依存：なし
 * 作成日：2006/08/29
 * @package Cbase.Research.Lib
 * @author	Cbase Akama
 */

class FCheck
{
    /**
     * 文字列が空白かどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   空白ならtrue
     */
    public function isBlank($prmVal)
    {
        $flgErr = false;
        $strTemp = @str_replace("　", "", $prmVal);
        $strTemp = @str_replace(" ", "", $strTemp);
        if ($strTemp === "") {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 数字かどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   数字ならtrue
     */
    public function isNumber($prmVal)
    {
        $flgErr = false;
        if (@preg_match("/^(\d+)$/", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 英数字かどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   英数字ならtrue
     */
    public function isAlphanumeric($prmVal)
    {
        $flgErr = false;
        if (@preg_match("/^(\w+)$/", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 全角カナかどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   全角カナならtrue
     */
    public function isEmkana($prmVal)
    {
        $flgErr = false;
        if (@mb_ereg("^[ァ-ヶー－]+$", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 半角カナかどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   半角カナならtrue
     */
    public function isHanKana($prmVal)
    {
        $flgErr = false;
        if (@mb_ereg("[ｦ-ﾟ]", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * ひらがなかどうかをチェック
     * @param  strign $prmVal 文字列
     * @return bool   ひらがなならtrue
     */
    public function isHiragana($prmVal)
    {
        $flgErr = false;
        if (@mb_ereg("^[ぁ-んー－]+$", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    //=============================================================================
    //
    //	事務系
    //
    //=============================================================================

    /**
     * 正しい電話番号かどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   正しいならtrue
     */
    public function isTel($prmVal)
    {
        //先頭が0で数字のみであるものを正しい電話番号とする
        $flgErr = false;
        if (FCheck::isNumber($prmVal) && FCheck::isHeadZero($prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 正しいメールアドレスかどうかをチェック
     * @paramstring $prmVal 文字列
     * @return bool 正しいならtrue
     */
    public function isEmail($prmVal)
    {
        $flgErr = false;
        //2008.4.16 許可文字列に + を追加
        if (@preg_match("/^[a-zA-Z0-9_+\.\-]+?@[A-Za-z0-9_\.\-]+$/", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 許可しているドメインかどうかをチェック
     * @paramstring $prmVal 文字列
     * @return bool 正しいならtrue
     */
    public function isPermittedDomain($prmVal)
    {
        $flgErr = false;
        $array = explode(',', SES_DOMAIN);
        foreach ($array as $value)
        {
            $value = @str_replace('\*', '[A-Za-z0-9_\-]+', preg_quote($value));
            if (@preg_match("/^[a-zA-Z0-9_+\.\-]+?@".$value."$/", $prmVal))
                $flgErr = true;
        }
        if (SES_ON && defined("SES_MAIL") && SES_MAIL == $prmVal)
            $flgErr = true;

        return $flgErr;
    }

    /**
     * 正しいURLかどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   正しいならtrue
     */
    public function isUrl($prmVal)
    {
        $flgErr = false;
        if (@preg_match("/(http|https)\:\/\/([-a-z0-9_]+)((\.[-a-z0-9_]+)*)((\/[-a-z0-9_\+\%\?\=\+\.]+)*)$/", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 正しい文字でパスワードが入力されているかをチェックします。
     * @param  string $prmVal 文字列
     * @return bool   正しいならtrue
     */
    public function isPassword($prmVal)
    {
        //半角英数のみであれば正しいパスワードとする
        $flgErr = false;
        if (@preg_match("/^[-A-Za-z0-9_]+$/", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 正しい日付が入力されているかをチェックします。
     * @param  string $prmVal   文字列
     * @param  string $prmSplit 区切り文字列
     * @return bool   正しいならtrue
     */
    public function isDate($prmVal, $prmSplit = "-")
    {
        //フォーマットはyyyy-mm-dd h:i:sまたはyyyy-mm-dd
        $flgErr = false;
        list($strDate, $strTime) = explode(" ", $prmVal);	// 日付と時間を分割します。
        list($strYear, $strMonth, $strDay) = explode($prmSplit, $strDate);	// 年と月と日を分割します。

        if (!checkDate((int) $strMonth, (int) $strDay, (int) $strYear)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    //=============================================================================
    //
    //	処理系
    //
    //=============================================================================

    /**
     * タグが含まれるかどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   含まれるならtrue
     */
    public function isTag($prmVal)
    {
        $flgErr = dalse;
        if (@ereg("<([^>]*)>", $prmVal)) {
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 先頭の文字が0かどうかをチェック
     * @param  string $prmVal 文字列
     * @return bool   0ならtrue
     */
    public function isHeadZero($prmVal)
    {
        $flgErr = false;
        if (ereg("^0",$prmVal)) {  //*********変更
            $flgErr = true;
        }

        return $flgErr;
    }

    /**
     * 選択された日付が過去日付かどうかをチェックします。
     * @param  string $prmVal 日付
     * @param  string $prmVal 区切り文字(デフォルト"-")
     * @return bool   過去日付ならtrue
     */
    public function isPastDate($prmVal, $prmSplit = "-")
    {
        $flgErr = false;

        if (0 < strpos($prmVal, " ")) {
            list($strDate, $strTime) = explode(" ", $prmVal);
        } else {
            $strDate = $prmVal;
        }

        list($strYear, $strMonth, $strDay) = explode($prmSplit, $strDate);

        $strNowDateTime = date("U", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
        $strTargetDateTime = date("U", mktime(0, 0, 0, $strMonth, $strDay, $strYear));

        if ($strTargetDateTime < $strNowDateTime) {
            $flgErr = true;
        }

        return $flgErr;
    }

}
