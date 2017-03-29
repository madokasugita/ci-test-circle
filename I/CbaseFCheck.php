<?php
/**
 * フォーム入力値チェック
 * @package Cbase.Research.Lib
 */
/*
 * PG名称：一般的な入力チェック関数
 * 日　付：2005.08.22
 * 作成者：cbase kaga
 *
 */
define('C_MIME_TYPE', "text/plain");	// 許可するMIMEタイプ

//携帯アドレスの@以降配列
$mobile_domain = array("@docomo.ne.jp",
                       "@jp-d.ne.jp",
                       "@jp-h.ne.jp",
                       "@jp-t.ne.jp",
                       "@jp-c.ne.jp",
                       "@jp-r.ne.jp",
                       "@jp-k.ne.jp",
                       "@jp-n.ne.jp",
                       "@jp-s.ne.jp",
                       "@jp-q.ne.jp",
                       "@d.vodafone.ne.jp",
                       "@h.vodafone.ne.jp",
                       "@t.vodafone.ne.jp",
                       "@c.vodafone.ne.jp",
                       "@r.vodafone.ne.jp",
                       "@k.vodafone.ne.jp",
                       "@n.vodafone.ne.jp",
                       "@s.vodafone.ne.jp",
                       "@q.vodafone.ne.jp",
                       "@ezweb.ne.jp",
                       "@sky.tkk.ne.jp",
                       "@sky.tkc.ne.jp",
                       "@sky.tu-ka.ne.jp",
                       "@pdx.ne.jp");

/**
 * 1.文字列が""(空っぽ)かチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkRequireCharacter($prmVal)
{
    $flgErr = FALSE;
    if ($prmVal == "") {
        $flgErr = TRUE;
    }

    return $flgErr;
}

/**
 * 2.文字列が空白文字列のみかチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkBlankCharacter($prmVal)
{
    $strTemp = @str_replace("　", "", $prmVal);
    $strTemp = @str_replace(" ", "", $strTemp);

    $flgErr = FALSE;
    if (checkRequireCharacter($strTemp)) {
        $flgErr = TRUE;
    }

    return $flgErr;
}

/**
 * 3.英数字以外に入力されているかチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkAlphanumeric($prmVal)
{
    $flgErr = FALSE;
    if (!@preg_match("/^(\w+)$/", $prmVal)) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * 4.数字のみ入力されているかチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkAllNumber($prmVal)
{
    $flgErr = FALSE;
    $strTemp = @str_replace("-", "", $prmVal);
    if (!@preg_match("/^(\d+)$/", $strTemp)) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * 5.全角カタカナが入力されているかチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkEmSizeKatakana($prmVal)
{
    $flgErr = FALSE;
    if (!@mb_ereg("^[ァ-ヶー－ 　]+$", $prmVal)) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * 6.ひらがなが入力されているかチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkEmSizeHiragana($prmVal)
{
    $flgErr = FALSE;
    if (!@mb_ereg("^[ぁ-んー－ 　]+$", $prmVal)) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * 7.メールアドレスが不正な書式かチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkEmailAddress($prmVal)
{
    if (SHOW_ABOLITION) {
        echo 'checkEmailAddressは廃止関数です。FCheckを使用してください<hr>';
    }
    $flgErr = FALSE;
    //2008.4.16 許可文字列に + を追加
    if (!@preg_match("/^[a-zA-Z0-9_+\.\-]+?@[A-Za-z0-9_\.\-]+$/", $prmVal) || !@preg_match("/\.([a-zA-Z]+)$/", $prmVal)) {
        $flgErr = TRUE;
    }

    return $flgErr;
}

/**
 * 8.携帯メールアドレスが入力されていないかをチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkPortableAddress($prmVal)
{
    global $mobile_domain;

    $flgErr = FALSE;
    list($strAccount, $strDomain) = explode('@', $prmVal);
    if (@in_array(sprintf("@%s", $strDomain), $mobile_domain)) {
        $flgErr = TRUE;
    }

    return $flgErr;
}

/**
 * 9.無効なメールアドレスかチェック
 * @param  string $prmVal メールアドレス
 * @return bool
 */
function checkCorrectEmailAddress($prmVal)
{
    $flgErr = FALSE;

    // メールドメイン部分をチェックします。
    list($strAccount, $strDomain) = explode('@', $prmVal);
    if (!checkdnsrr($strDomain, 'MX')) {				// メールサーバーをチェックします。
        if (!checkdnsrr($strDomain, 'A')) {			// アドレスをチェックします。
            if (!checkdnsrr($strDomain, 'CNAME')) {	// エイリアスをチェックします。
                $flgErr = TRUE;
            }
        }
    }

    return $flgErr;
}

/**
 * 20.不正な形式でURLが入力されているかチェック
 * @param  string $prmVal URL
 * @return bool
 */
function checkUrl($prmVal)
{
    $flgErr = FALSE;
    //if (!@preg_match("/^[-A-Za-z0-9_\~\*\:\/\+\%\?\=\@\.]*$/", $prmVal)) {
    if (!@preg_match("/(http|https)\:\/\/([-a-z0-9_]+)((\.[-a-z0-9_]+)*)\/([-a-z0-9_\+\%\?\=\+\.]*)$/", $prmVal)) {

        $flgErr = TRUE;
    }

    return $flgErr;
}

/**
 * 11.不正な文字でパスワードが入力されているかチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkPassword($prmVal)
{
    $flgErr = FALSE;
    if (!@preg_match("/^[-A-Za-z0-9_]+$/", $prmVal)) {
        $flgErr = TRUE;
    }

    return $flgErr;
}

/**
 * 12.間違った日付が入力されているかチェック
 * @param  string $prmVal 日付
 * @return bool
 */
function checkCorrectDate($prmVal)
{
    $flgErr = FALSE;
    list($strDate, $strTime) = explode(" ", $prmVal);	// 日付と時間を分割します。
    list($strYear, $strMonth, $strDay) = explode("-", $strDate);	// 年と月と日を分割します。

    if (!checkDate((int) $strMonth, (int) $strDay, (int) $strYear)) {
        $flgErr = TRUE;
    }

    return $flgErr;
}

/**
 * 13.先頭の文字が0(零)が入力されているかチェック
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkHeadZeroCharacter($prmVal)
{
    $flgErr = FALSE;
    if (@strcmp(@substr($prmVal, 0, 1), '0')) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * 14.半角カタカナが入力されていないかをチェックします。
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkNormalWidthKatakana($prmVal)
{
    $flgErr = FALSE;
    if (@mb_ereg("[ｦ-ﾟ]", $prmVal)) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * 15.HTMLタグ文字が入力されていないかをチェックします。
 * @param  string $prmVal 文字列
 * @return bool
 */
function checkHtmlTagCharacter($prmVal)
{
    $flgErr = FALSE;
    if (@ereg("<([^>]*)>", $prmVal)) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * 16.選択された日付が過去日付ではないかをチェックします。
 * @param  string $prmVal 日付
 * @param  string $prmSplit セパレータ
 * @return bool
 */
function checkPastDate($prmVal, $prmSplit)
{
    $flgErr = FALSE;

    if (strpos($prmVal, " ") > 0) {
        list($strDate, $strTime) = explode(" ", $prmVal);
    } else {
        $strDate = $prmVal;
    }

    list($strYear, $strMonth, $strDay) = explode($prmSplit, $strDate);

    $strNowDateTime = date("U", mktime(0, 0, 0, date("m"), date("d"), date("Y")));
    $strTargetDateTime = date("U", mktime(0, 0, 0, $strMonth, $strDay, $strYear));

    if ($strTargetDateTime < $strNowDateTime) {
        $flgErr = TRUE;	// エラー
    }

    return $flgErr;
}

/**
 * POST値をチェック定義で指定されたチェック項目でチェックします。
 * 番号はそれぞれ本モジュールの関数につけられたコメントの頭の数字に対応
 * @param  string $prmVal 文字列
 * @return 配列データ(エラーカウント値, エラーメッセージ)
 */
function checkBatchPostData($prmPOST)
{
    global $input_check, $field_name;

    $intErrCount = 0;

    foreach ($input_check as $input_key => $check_val) {
        $aryCheckNumber = array();
        $aryCheckOption = array();
        $aryErrMessage[$input_key] = array();

        // 入力チェック内容を取得します。
        $aryCheckList1 = explode('&', $check_val);

        foreach ($aryCheckList1 as $check_line) {
            $aryCheckList2 = explode('=', $check_line);
            array_push($aryCheckNumber, $aryCheckList2[0]);

            if (isset($aryCheckList2[1])) {
                $aryCheckOption[$aryCheckList2[0]] = $aryCheckList2[1];
            }
        }
        unset($aryCheckList1);
        unset($aryCheckList2);

        // 必須項目に入力されているかをチェックします。
        if (in_array(1, $aryCheckNumber)) {
            if (checkRequireCharacter($prmPOST[$input_key])) {
                if (isset($aryCheckOption[1]) && $aryCheckOption[1] == 1) {
                    array_push($aryErrMessage[$input_key], sprintf("「%s」を選択して下さい。", $field_name[$input_key]));
                } else {
                    array_push($aryErrMessage[$input_key], sprintf("「%s」を入力して下さい。", $field_name[$input_key]));
                }
                $intErrCount++;
            }
        }

        // 空白のみ入力されているかをチェックします。
        if (in_array(2, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkBlankCharacter($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」を入力して下さい。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 英数字のみ入力されているかをチェックします。
        if (in_array(3, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkAlphanumeric($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」は英数字で入力して下さい。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 数字のみ入力されているかをチェックします。
        if (in_array(4, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkAllNumber($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」は数字で入力して下さい。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // カタカナで入力されているかをチェックします。
        if (in_array(5, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkEmSizeKatakana($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」は全角カタカナで入力して下さい。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // ひらがなで入力されているかをチェックします。
        if (in_array(6, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkEmSizeHiragana($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」はひらがなで入力して下さい。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 正しい形式でメールアドレスが入力されているかをチェックします。
        if (in_array(7, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkEmailAddress($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」の入力が誤りです。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 携帯メールアドレスが入力されていないかをチェックします。
        if (in_array(8, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkPortableAddress($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」は携帯メールアドレスは入力できません。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 有効なメールアドレスが入力されているかをチェックします。
        if (in_array(9, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkCorrectEmailAddress($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」は有効なメールアドレスではありません。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 正しい形式でURLが入力されているかをチェックします。
        if (in_array(10, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkUrl($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」の入力が誤りです。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 正しい文字でパスワードが入力されているかをチェックします。
        if (in_array(11, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkPassword($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」の入力が誤りです。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 正しい日付が選択されているかをチェックします。
        if (in_array(12, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkCorrectDate($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」の入力が誤りです。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 先頭に数字の零(0)が入力されているかをチェックします。
        if (in_array(13, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkHeadZeroCharacter($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」は0から始まる数字で入力して下さい。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 半角カタカナが入力されていないかをチェックします。
        if (in_array(14, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkNormalWidthKatakana($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」に半角カタカナは入力できません。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // タグ文字が入力されていないかをチェックします。
        if (in_array(15, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkHtmlTagCharacter($prmPOST[$input_key])) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」にタグ文字は入力できません。", $field_name[$input_key]));
                $intErrCount++;
            }
        }

        // 過去日付が選択されていないかをチェックします。
        if (in_array(16, $aryCheckNumber) && $prmPOST[$input_key] != "") {
            if (checkPastDate($prmPOST[$input_key], "-")) {
                array_push($aryErrMessage[$input_key], sprintf("「%s」に過去日付は選択できません。", $field_name[$input_key]));
                $intErrCount++;
            }
        }
    }

    return array($intErrCount, $aryErrMessage);
}

/**
 * 送信されたファイルがただしいかどうか
 * @param string $prmFileName ファイル名
 * @param string $prmMimeType MIMEタイプ
 * @return bool
 */
function checkUploadFile($prmFileName, $prmMimeType)
{
    global $mime_type;

    $flgErr = FALSE;
    if (!checkRequireCharacter($prmFileName)) {	// 送信ファイルが選択されているかをチェックします。
        if (array_search($prmMimeType, $mime_type) === FALSE) {	// 送信ファイルのMIMEタイプをチェックします。
            $flgErr = TRUE;
        }
    } else {
        $flgErr = TRUE;
    }

    return $flgErr;
}
/**
 * パスワードのセキュリティレベルをチェックする(0 - ∞)　5くらいセキュリティレベルがあれば安全
 * @param string $pw パスワード
 * @return int セキュリティレベル
 */
function getPasswordLevel($pw)
{
    $point = -2;
    //4文字以下だったら問題外
    if (strlen($pw) < 4)
        return 0;

    //パスワードに含まれる文字を配列にいれて重複を取り除く
    $array = array ();
    for ($i = 0; $i < strlen($pw); $i++)
        $array[] = substr($pw, $i, 1);
    $array = array_unique($array);

    //パスワードの長さ/3  を点数に足す
    $point += strlen($pw) / 3;

    //パスワードに使われてる文字の種類/2 を点数に足す
    $point += count($array) / 2;

    //↓文字配列準備//
    $array_n = range(0, 9);
    $array_a = range('a', 'z');
    $array_A = range('A', 'Z');
    $array_o = array_merge_recursive($array_n, $array_a, $array_A);
    //↑文字配列準備//

    //数字が使われていたら+1点
    if (count(array_diff($array, $array_n)) != count($array))
        $point += 1;

    //小文字アルファベットが使われていたら+1点
    if (count(array_diff($array, $array_a)) != count($array))
        $point += 1;

    //大文字アルファベットが使われていたら+1点
    if (count(array_diff($array, $array_A)) != count($array))
        $point += 1;

    //数字でもアルファベットでもないものが使われていたら+2点
    if (array_diff($array, $array_o))
        $point += 2;

    return round($point);
}
