<?php

/**
 * 複数メール送信
 *
 * @package Cbase.Research.Lib
 *
 * 2007/05/17 メール送信ログファイルを変更(LOG_CRONMAIL3)
 */

//Pear必要
//http://pear.php.net/manual/ja/package.mail.mail.php
require_once 'Mail.php';
require_once 'Mail/mime.php';
require_once 'CbaseMailBody.php';
require_once 'CbaseFCondition.php';
require_once 'CbaseFMail.php';
require_once '360_Setting.php';
//define("MAIL_INT", 7500000); //
define("LOG_ALLMAIL", "./send" . date("Ymd") . ".clog");

//何通に一回ロックファイルにtouchするかを指定。メール送信間隔より算出する
//マイクロセカンド100000=10回/秒,80000=12.5回/秒,50000=20回/秒
define('LOCK_INT', (int) ((1000000/MAIL_INT)*60*MAIL_LOCK_REPORT_MINS/2));

/**
 * 複数メール送信
 * @param array $adds emailを含むユーザデータ
 * @param array $format 本文などのメールデータ
 * @return void なし
 */
//NO123 メール配信履歴追加 *4
function CbaseMassMailer($adds, $format, $mail_rsv,$cond ,$logmode="", $lock)
{
    global $mobile_domain; //定義
    //start log
    error_log("----Start---- at " . "\t" . date("Ymd") . "\t" . date("His") . "-------" . "\n", 3, LOG_SEND);
    $stime = date("Y/m/d H:i:s");

    error_log(date("Y/m/d H:i:s") . "\t" . 'mrid=' . $mail_rsv['mrid'] . "\t" . "start" . "\n", 3, LOG_CRONMAIL3);
    //対象数, 配信数, エラー数
    $totalcount = $sendcount = $errorcount = 0;
    touch($lock);
    foreach ($adds as $ad) {
        //あるファイルが存在すれば、メール送信を中断する。
        //メール送信を外部から強制終了させるための仕組み？
        if (existsMailForcedStopFile($mail_rsv['mrid'])) {
            //配信ステータス変更
            $mail_rsv["flgs"] = 13;
            $mail_rsv["count"] = $sendcount;
            Save_MailEvent("update", $mail_rsv);
            break;
        }

        //NO123 メール配信履歴追加 *4
        $res = Pc_Mail_Send($ad, $format, $mail_rsv, $logmode);
        //失敗ログはPc_Mail_Sendで残している。成功ログについては不要とのことで省略
        if ($res) {
            ++$sendcount;
        } else {
            ++$errorcount;
        }
        ++$totalcount;

        if (LOCK_INT!=0 && $totalcount % LOCK_INT==0) {
            touch($lock);
        }

        //配信間隔調整
        if ($ad["email"])
            error_log( date("Y/m/d H:i:s") . "\t" . $ad["email"]."\n", 3, LOG_CRONMAIL3); //edit 2007/05/17
        usleep(MAIL_INT);

        //++$sendcount;
    }
    error_log(date("Y/m/d H:i:s") . "\t" . 'mrid=' . $mail_rsv['mrid'] . "\t" . "end" . "\n", 3, LOG_CRONMAIL3);

    $etime = date("H:i:s");
    if (MAIL_END_SENDING_REPORT) {
        $headers = array();
        $headers["Content-Type"] = "text/plain; charset=ISO-2022-JP";
        $headers["Content-Transfer-Encoding"] = "7bit";
        $headers["MIME-Version"] = "1.0";
        $headers['From'] = "=?iso-2022-jp?B?" . base64_encode(mb_convert_encoding(MAIL_SENDERNAME0, "JIS", INTERNAL_ENCODE)) . "?=";
        $headers['From'] .= " <".MAIL_SENDER0.">";
        $headers['Subject'] = "=?iso-2022-jp?B?" . base64_encode(mb_convert_encoding('メール配信完了通知', "JIS", INTERNAL_ENCODE)) . "?=";
        $headers['To'] = MAIL_SENDER0;
        //処理件数、配信数は上記ループで取得したものを使用
        //主に中断の際、対象件数!=処理件数となる
        //配信数（対象件数）の計算を使う場合は以下を利用
        //$targetcount = count($adds);
        $admin_url = DOMAIN.DIR_MAIN.DIR_MNG;
        $body =<<<MAILBODY
<配信時間>
{$stime} -> {$etime}

<配信名>
{$mail_rsv['name']}

<条件>
{$cond[0]['name']}

<雛形>
{$format['name']}

<処理件数>
{$totalcount}件

<配信件数>
{$sendcount}件

<管理画面URL>
{$admin_url}
MAILBODY;
        $body = mb_convert_encoding($body, "JIS", INTERNAL_ENCODE);
        $mail_object = getMailObject();
        $res = $mail_object->send($headers['To'], $headers, $body);
        if (PEAR::isError($res)) {
            //PEARのエラーメッセージを取得
            $error_message = $res->getMessage()."(".$res->getDebugInfo().")"."(Report Mail Send Error)";
            error_log(implode("\t", array(
                date("Y/m/d H:i:s"),
                $headers['To'],
                $mail_rsv['mrid'],
                $format['mfid'],
                $error_message
            ))."\n", 3, LOG_SEND_ERROR);
        }
    }
    //end log
    error_log("----Finish--- at " . "\t" . date("Ymd") . "\t" . date("His") . "-------" . "\n", 3, LOG_SEND);
}
/**
 * メール送信
 * @param array $mail 送信先ユーザデータ
 * @param array $format 本文などメールフォーマット
 * @param array $event イベントデータ
 * @return void なし
 */
//NO123 メール配信履歴追加 *4
function Pc_Mail_Send($user, $format, $mail_rsv, $logmode="")
{
    global $Setting;
    if (is_void($user['email'])) {
        error_log(implode("\t", array(
            date("Y/m/d H:i:s"),
            $user['uid'],
            $mail_rsv['mrid'],
            NULL,
            'mail emailaddress missing'
        ))."\n", 3, LOG_SEND_ERROR);
        if ($logmode == "addlog") {
            Save_MailLog($mail_rsv['mrid'], $user, false);
        }

        return false;
    }
    if($user["send_mail_flag"])
    {
        error_log(implode("\t", array(
            date("Y/m/d H:i:s"),
            $user['uid'],
            $mail_rsv['mrid'],
            NULL,
            'send_mail_flag is active'
        ))."\n", 3, LOG_SEND_ERROR);
        if ($logmode == "addlog") {
            Save_MailLog($mail_rsv['mrid'], $user, false);
        }

        return false;
    }
    if (is_void($format['mfid'])) {
        error_log(implode("\t", array(
            date("Y/m/d H:i:s"),
            $user["email"],
            $mail_rsv['mrid'],
            NULL,
            'mail format missing'
        ))."\n", 3, LOG_SEND_ERROR);
        if ($logmode == "addlog") {
            Save_MailLog($mail_rsv['mrid'], $user, false);
        }

        return false;
    }
    $encode = ($Setting->mailEncodeJis())?"iso-2022-jp":"utf-8";
    if ($user['lang_type']) {
        $body = $format["body_".$user['lang_type']];
        $title = mb_convert_encoding($format["title_".$user['lang_type']],$encode,INTERNAL_ENCODE);
        $file = $format["file_".$user['lang_type']];
    } else {
        $body = $format["body"];
        $title = mb_convert_encoding($format["title"],$encode,INTERNAL_ENCODE);
        $file = $format["file"];
    }

    list($MAIL_SENDERNAME,$MAIL_SENDER) = getMailSender($user);	$MAIL_SENDERNAME = mb_convert_encoding($MAIL_SENDERNAME,$encode,INTERNAL_ENCODE);
    $headers['Content-Type'] = "text/plain; charset={$encode}";
    $headers['Content-Transfer-Encoding'] = "base64";
    $headers['MIME-Version'] = "1.0";
    $headers['To'] = $user['email'];
    $headers['Subject'] = "=?{$encode}?B?" . base64_encode($title) . "?=";
    $headers['From'] = "=?{$encode}?B?" . base64_encode($MAIL_SENDERNAME) . "?=";
    $headers['From'] .= " <$MAIL_SENDER>";
    /*******************************************************************************/

    $replace1 = new MailBody($body, $user, null);
    $body = $format["header"];
    $body .= $replace1->ReplaceParts() . "\n";
    $body .= $format["footer"];
    unset ($replace1);
    $mime = new Mail_mime("\r\n");
    $txt_body = mb_convert_encoding(strip_tags(str_replace('<br>',"\n",$body)),$encode,INTERNAL_ENCODE);
    $mime->setTXTBody($txt_body);
    if ($Setting->htmlMail()) {
        $mime->setHTMLBody(mb_convert_encoding($body,$encode,INTERNAL_ENCODE));
    }

    if (is_good($file)) {
        $attach = unserialize($file);
        if(is_array($attach))
        foreach ($attach as $file => $filename) {
            $filename = $filename['name'];
            $filename = mb_convert_encoding($filename,$encode,INTERNAL_ENCODE);
            if(!file_exists(DIR_DATA.$file))
                continue;
            switch (PEAR_MAIL_MIME_VER) {
                case '1.3.1':
                    $mime->addAttachment(DIR_DATA.$file, 'application/octet-stream', "=?{$encode}?B?" . base64_encode($filename) . "?=");
                    break;
                default:
                    $mime->addAttachment(DIR_DATA.$file, 'application/octet-stream', mb_convert_kana($filename, 'KV'), true, 'base64', 'attachment', $encode, 'ja', '', 'base64', 'base64');
                    break;
            }
        }
    }

    //送信設定
    $charsets = array(
        "html_charset" => $encode,
        'head_charset'	=> $encode
        ,'text_charset'	=> $encode
        ,'text_encoding'=> 'base64'
    );
    $body = $mime->get($charsets);
    $header = $mime->headers($headers);
    $mail_object = getMailObject();
    $res = $mail_object->send($user["email"], $header, $body);
    $isError = PEAR::isError($res);
    if ($isError) {
        //PEARのエラーメッセージを取得
        $error_message = $res->getMessage()."(".$res->getDebugInfo().")";
        error_log(implode("\t", array(
            date("Y/m/d H:i:s"),
            $user["email"],
            $mail_rsv['mrid'],
            $format['mfid'],
            $error_message
        ))."\n", 3, LOG_SEND_ERROR);
    }

    //NO123 メール配信履歴追加 *4
    if ($logmode == "addlog") {
        $isSend = $isError ? false : true;
        Save_MailLog($mail_rsv['mrid'], $user, $isSend);
    }

    return (!$isError);
}
