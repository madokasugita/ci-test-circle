<?php
ini_set('display_errors', 'On');
{//11.2用
    define('DOMAIN', "http://192.168.33.10/src"); //後にスラッシュなし
    define('DIR_MAIN', str_replace($_SERVER['DOCUMENT_ROOT'], "", dirname(__FILE__)) . "/"); //前後にスラッシュあり
    //define('DB_USER', "root"); //接続ユーザ名
    define('DIR_SRC',DIR_ROOT.'img/');
    //SSL
    define('SSL_ON'		,0);	//0=>SSL環境なし, 1=>SSL環境あり
    //SES
    define('SES_ON'		,0);	//1=>SES使用
    //SES登録アドレス
    define('SES_MAIL'	,'');

    if(file_exists('/work/www/src/dashboard/lib/debuglib.php'))
        require_once '/work/www/src/dashboard/lib/debuglib.php';//print_a()
    if(file_exists('/work/www/src/dashboard/lib/ChromePhp.php'))
        require_once '/work/www/src/dashboard/lib/ChromePhp.php';

        ini_set("error_reporting","E_ALL & ~E_NOTICE & ~E_DEPRECATED");
}

{
//define('DEBUG',1);
define('PROJECT_NAME','cbase_mysql');
if ($_COOKIE['DEBUG'])
    define('DEBUG', 1);
else
    define('DEBUG', 0);
//DEBUGモードだったらエラー表示ありにする
if (DEBUG && !strpos($_SERVER['PHP_SELF'], "index2.php")) {
    ini_set('display_errors', 'On');
    show_vars();
}

define('DEBUG', 0);

//ディレクトリ設定
//define('DOMAIN', "http://lgw.cbase.co.jp"); //後にスラッシュなし
define('DIR_MAIN', "/app/mre_demo/".str_replace("_", "/", PROJECT_NAME).'/'); //前後にスラッシュあり
define('DIR_MNG', "admin/"); //前にスラッシュなし, 後にスラッシュあり

//DB設定

define('DB_NAME', "mre_".PROJECT_NAME); //接続DB名

/* svr_define
define('DB_HOST', "localhost"); //接続DBホスト名
define('DB_PORT', 5432); //接続DBポート番号(5433=pgpool)
define('DB_USER', "root"); //接続ユーザ名
define('DB_PASSWD', ""); //接続パスワード
define('DB_TYPE', "mysql"); //DB種類:mysql,pgsql,mssql,odbc
define('DB_PERSISTENT', false); //パーシスタント接続

//クラスタリング設定
define('CC_DOEXC', 0); //0=>実行なし, 1=>実行
define('CC_SHOWCMD', 0); //0=>出力なし, 1=>CMD画面出力
define('CC_LOG', 0); //0=>ログなし, 1=>ログ記録

//define('SMTP_HOST',"localhost");
//define('SMTP_PORT',25);

*/

//SSL
define('SSL_ON'		,1);	//0=>SSL環境なし, 1=>SSL環境あり
//SES
define('SES_ON'		,1);	//1=>SES使用

define('DEBUG', 0); //1->デバッグモード

//暗号化設定
define('SYSTEM_RANDOM_STRING', "AAAAAAAAAAAAAAAAAAAAAAAAA"); //案件毎に設定
//トークン生成キー
define('BLOWFISH_SECRET_KEY', 'hugahuga123891');
}

/**
 * 本番環境用設定
 */
{
define('PROJECT_NAME','★');

//ディレクトリ設定
//define('DOMAIN', "https://www.cbase.co.jp"); //後にスラッシュなし
define('DIR_MAIN', "/asp/★/"); //前後にスラッシュあり
define('DIR_MNG', "★★★/"); //前にスラッシュなし, 後にスラッシュあり

/* svr_define
//DB設定
define('DB_NAME', "★★★"); //接続DB名
define('DB_HOST', "localhost"); //接続DBホスト名
define('DB_PORT', 5432); //接続DBポート番号(5433=pgpool)
define('DB_USER', "www"); //接続ユーザ名
define('DB_PASSWD', ""); //接続パスワード
define('DB_TYPE', "pgsql"); //DB種類:mysql,pgsql,mssql,odbc
define('DB_PERSISTENT', false); //パーシスタント接続

//クラスタリング設定
define('CC_DOEXC', 1); //0=>実行なし, 1=>実行
define('CC_SHOWCMD', 0); //0=>出力なし, 1=>CMD画面出力
define('CC_LOG', 1); //0=>ログなし, 1=>ログ記録

define('SMTP_HOST'	,"localhost");	//SMTPホスト名
define('SMTP_PORT'	,25);				//SMTPポート番号
define('SMTP_AUTH'	,false);
define('SMTP_USERNAME',"");
define('SMTP_PASSWORD',"");
define('SMTP_PERSIST',false);			//SMTPパーシストで送るかどうか
define('SMTP_TIMEOUT',10);				//SMTPタイムアウト

*/

//SSL
define('SSL_ON'		,1);	//0=>SSL環境なし, 1=>SSL環境あり
//SES
define('SES_ON'		,0);	//1=>SES使用
//メール送信者アドレスに設定可能なドメイン
define('SES_DOMAIN'	,'cbase.co.jp,*.mailpass.jp');
//SES登録アドレス
//define('SES_MAIL'	,'');
//メール配信速度
if(SES_ON)
    define("MAIL_INT", 4000);
else
    define("MAIL_INT", 750000);

define('DEBUG', 0); //1->デバッグモード

//暗号化設定
define('SYSTEM_RANDOM_STRING', "★★★"); //案件毎に設定
//トークン生成キー
define('BLOWFISH_SECRET_KEY', '★★★');

}
/**
 * 案件データ
 */
{

    //svr_define
    //define('SSL_SEAL', 'https://siteseal.thawte.com/cgi/server/thawte_seal_generator.exe');

    define('RESEARCH_TITLE', "####main_title####"); //管理画面タイトル(ブックマーク用)
    define('RESEARCH_NEWS', ""); //管理画面お知らせ
    define('CBASE_COPYRIGHT', "Copyright 2012 Cbase Corp, Inc All rights reserved."); //弊社コピーライト

    define('MAIL_RESTORE_TITLE', "【！】アンケート再開URL"); //再開URL送信タイトル

    //CronMailが指定時間ロックが掛かりっぱなしだと管理者にメールを送る 0->送らない 1以上->送る
    define('MAIL_LOCK_REPORT_HOURS', 3);
    define('MAIL_LOCK_REPORT_MINS', 3);
    define('MAIL_LOCK_REPORT_ADDRESS', "360_error@cbase.co.jp"); //送り先

    //回答データを削除したときに管理者にメールを送る 0->送らない 1->送る
    define('ENQ_DATA_DELETE_REPORT_FLAG', 1);
    define('ENQ_DATA_DELETE_REPORT_ADDRESS', "360_report@cbase.co.jp"); //送り先

    //予約メール完了時に　From のアドレスに報告を送る 0->送らない 1->送る
    define('MAIL_END_SENDING_REPORT', 1);

    /**
     * 有料オプション機能
     */
    define('MONEY_MARK_ON', 0); //1=>有料マーク表示 0=>非表示

    /**
     * オプション機能表示設定
     * 1  => 有効
     * 0  => 無効 (リンクしない,チェック選べない)
     */
    define('OPTION_ENQ_BACKUP', 1); //途中保存機能
    define('OPTION_ENQ_RANDOMIZE', 1); //ランダマイズ機能
    define('OPTION_ENQ_COND', 1); //条件分岐
    define('OPTION_ENQ_CROSS', 1); //クロス集計機能
    define('OPTION_ENQ_MOBILE', 1); //回答画面携帯対応
    define('OPTION_ENQ_MAIL', 1); //メール配信機能
    define('OPTION_MAIL_REVEICED', 1); //メール受信機能

    /**
     * 動作オプション
     */
    define('REOPEN', 0); //0->再回答できる 1->再回答禁止
    define('HIDDEN_PAGENUMBER', 0); //0->現在のページ番号を表示(例:1/4ページ)
    define('NODUPLICATION', 1); //0=>Cookie重複制御[ON]
    define('NODUPLICATION2', 1); //0=>同一IP重複制御[ON]
    define('INTV_TIME', 60); //define分以内の同一IPからの回答拒否
    define('USE_HASHURL', 1); //通常は1で使用。0は他社システムとの連携時などに使用する
    define('CHECK_USER_EXISTS', 0); //1=>クローズドアンケートの際にユーザのマスタ存在を確認
    define('DLCSV_USLEEP_TIME', 0); //結果CSVを生成処理 1人分処理するごとに何μs 待つか

    /**
     * 実行したPHPのログ(CbaseFManage.php)
     * 0->ログを残さない
     * 1->muidとPHP名を残す
     */
    define('LOG_MODE_PHP', 1);

    /**
     * 実行したSQLのログ(CbaseFDBClass.php)
     * 0->ログを残さない
     * 1->エラー時のみ残す
     * 2->全てのSQLを残す
     * 3->muidと全てのSQLを残す
     */
    define('LOG_MODE_SQL', 2);

    /**
     * メール送信のログ(CbaseFSMTP_UTF8.php)
     * 0->ログを残さない
     * 1->あて先
     * 2->件名,あて先
     * 3->件名,本文,あて先
     */
    define('LOG_MODE_MAIL', 2);

}

// Jquery UI datepicker向け年度選択の年度幅設定
define('DATEPICKER_YEAR_RANGE_DEFAULT', 'c-2:c+10'); // 2年前から10年先まで

//AWS対応
date_default_timezone_set('Asia/Tokyo');
$_SERVER['REMOTE_ADDR'] = array_shift(explode(",", (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));

// 一時ディレクトリ
define('DIR_ZIP', 'tmp_zip/');
// Jquery UI datepicker向け年度選択の年度幅設定
define('DATEPICKER_YEAR_RANGE_DEFAULT', 'c-2:c+10'); // 2年前から10年先まで
