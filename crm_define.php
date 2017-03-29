<?php

/**
 *
 *
 * @version 1.1
 * 2007/08/21 ver1.1 外字対策 169行目をコメントアウトすれば完全に以前のバージョンと同等の動きをします。
 * 2007/08/30 ver1.2 セッション寿命延命用の定義を追加
 */
//設置サーバによって変更が必要な定義は切り分ける
require_once 'crm_define2.php';

require_once 'cbase/svr_define.php';
//echo DB_USER.':'.DB_HOST.':'.DB_NAME.':'.DB_PORT;exit;

//廃止フラグ。1にすると、使用廃止の関数を仕様の場合に警告表示(デバッグ用)
define('SHOW_ABOLITION', 0);

define("MAIL_MANAGER", "360_support@cbase.co.jp");
define("LENGTH_QS", 23); ///無駄(1),user(8),属性(1),event type(1),event(8),無駄(4)
$GLOBALS['SUPER_MUIDS'] = array(1,2);//全て(のアンケート)を見られるユーザ

//Cookie重複制御
define("HTML_ALREADYENTRY", "already_entry.html"); //defineクッキーoff時のリダイレクト先html

//キャッシュファイルの拡張子
define("EXT_CACHE_M", ".ccacheM");
define("EXT_CACHE_U", ".ccache");

//page
define("PAGE_LOGIN", "index.php");
define("PAGE_BACKUP", "backup.php");
define("PAGE_OPENBACKUP", "openbackup.php");
define("PAGE_RESTORE", "");
define("HTML_BACKUP", "backup.html");
define("PG_PREVIEW", "prev.php");

//■■■ユーザマスタ一覧表示の設定
/** セッション情報 */
define('SHOW_USER_MST_SKEY', "showUserMst_skey");
/** 1ページ表示件数 */
define('PAGE_SHOW_NUM', 100);

//■■■リサーチ動作の設定
/** アンケート回答後にセッションを 0->消さない 1->消す (回答内容送信機能を使うなら 0 ) */
define('CLEAR_ENQUETE_SESSION', 0);

/** キャッシュを使うかどうか(1で使用)<onにしないと途中保存機能は使えません */
define('USE_CACHE', 1);

/** セッション自動延命機能 0->使わない 1->使う */
define('USE_SESSION_LIFE_TIME_RESET', 1);

/** 何分ごとに延命を行なうか */
define('SESSION_RESET_MINUTES', 15);

/** 2度押し禁止コード onclickに設定して使う */
define('JSCODE_ANTI_DOUBLE_CLICK', 'var anti_double_click=this;setTimeout(function () {anti_double_click.disabled=true;},0);');
define('BUTTON_PB_ONCLICK'	,JSCODE_ANTI_DOUBLE_CLICK);	/** 前へ ボタンのonclickにセットするJSコード*/
define('BUTTON_MAIN_ONCLICK'	,JSCODE_ANTI_DOUBLE_CLICK);	/** 次へ ボタンのonclickにセットするJSコード*/
define('BUTTON_MAIN2_ONCLICK',JSCODE_ANTI_DOUBLE_CLICK);	/** 送信 ボタンのonclickにセットするJSコード*/
define('BUTTON_SS_ONCLICK'	,JSCODE_ANTI_DOUBLE_CLICK);	/** 途中保存 ボタンのonclickにセットするJSコード*/

/** アンケートの回答データ削除の方式 0->delete文で 1->statusを-10にする (crm_enq_data_delete.php) */
define('ENQ_DATA_DELETE_MODE', 0);

/** 認証ページの引数 0->Create_QueryStringで得られる文字 1->rid 2->evid(キャッシュが読めないので、少し負荷が高い) */
define('AUTH_QUERY_STRING',1);

/** アンケートの時間切れまでの時間(秒) 0->時間切れなし */
define('ENQ_TIMEOUT', 0);

/** オープンアンケートの途中回答を許可するかどうか。1にするとオープン+途中保存の指定が可能になる。 */
define('ENQ_OPEN_RESTORE', 1);

/** アンケート再開ログインのためのページ名 */
define('ENQ_RESTORE_PAGE', 'login.php');

/**
 * 自分のアンケートは無条件に全件を与えるかどうか。
 * 1にするとONになり、認証データをいちいち作らなくなるため負荷は軽減するが、権限の微調整がきかなくなる。
 * 0にするとsuper以外許可されたアンケートしか開けないが、新規作成時に自動で権限付与を行う。
 */
define('AUTH_MY_ENQUETE', 0);

//■■■リサーチのデザインに関する設定

/** 途中保存ボタン表示部分のテーブル幅 */
define("WIDTH_BACKUP", 600);

/** エラーメッセージで表示されるタイトル部分の表示文字数(バイト数) */
define('ERRORMESSAGE_TITLE_WIDTH', 50);

/** エラーメッセージで枠の幅(pixel) */
define('ERRORMESSAGE_TABLE_WIDTH', 700);

define('HISSU_MARK', '<span style="color:#FF0000">*必須</span>');

//■■■ 結果DLの挙動
/** 0=>9999というデータを出さずにnullとして出力する */
define('NO_9999', 1);

/** 回答の状態(完了,途中) をデータに加えるフラグ 0->表示しない 1->表示する */
define('SHOW_STATUS', 1);

/** CSVかTSVか csv or tsv を指定 */
define('CSV_TSV', 'csv');

/** 0->改行タブを取り除く 1->改行タブを残す(csvモードのときのみ有効) */
define('OTHER_NOESCAPE_MODE', 1);

/** MAを分割する？ 0->しない(1つのセルにカンマ区切り)  1->する */
define('MA_EXPLODE_MODE', 1);

//■■■ メール配信設定
/** 配信許可時間帯*/
$mailrsv_hh = range(5, 23);//5時04分から23時59分まで

/** 経年比較のタイプ定義 */
define('SECULAR_USES_TYPE_DUMP',   1); // DBダンプ
define('SECULAR_USES_TYPE_RAW',  2); // RAWデータ
/** 経年比較のステータス定義 */
define('SECULAR_USES_STATUS_UNUSED',   1); // 未使用
define('SECULAR_USES_STATUS_CREATED',  2); // データ作成済み
define('SECULAR_USES_STATUS_IMPORTED', 3); // データインポート済み
define('SECULAR_USES_STATUS_DISPOSAL', 9); // 廃棄
/** 経年比較のS3アクセス情報定義 */
define('SECULAR_AWS_KEY', 'AKIAJQYNMPVTYMPXMZ7Q');
define('SECULAR_AWS_SECRET_KEY', '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');
define('SECULAR_AWS_S3_BUCKET', 's3sc-mrejapan');
define('SECULAR_AWS_S3_PREFIX', 'mre_demo_keinen/');
/** 経年比較対象に出来る限界数 */
define('SECULAR_TARGET_LIMIT_COUNT', 4);

//DBテーブル設定
{
define('T_USER_MST'	,"usr");		//ユーザマスタ
define('T_MAIL_RSV'	,"mail_rsv");	//メール予約
define('T_MAIL_RECEIVED'	,"mail_received");	//受信メール
define('T_MAIL_FORM'	,"mail_format");//メール雛形
define('T_COND'		,"cond");		//条件
define('T_EVENT'		,"event");		//イベント
define('T_EVENT_SUB'	,"subevent");	//サブイベント
define('T_EVENT_DATA'	,"event_data");	//イベントデータ
define('T_EVENT_SUB_DATA'	,"subevent_data");	//サブイベントデータ
define('T_EVENT_DESIGN'	,"event_design");	//イベントデザイン
define('T_MUSR'		,"musr");		//管理者マスタ
define('T_BACKUP_DATA'	,"backup_data");
define('T_BACKUP_EVENT'	,"backup_event");
define('T_UNIQUE_SERIAL'	,"uniqserial");
define('T_CHOICE'		,"choice");
define('T_MAIL_LOG'	,"mail_log");	//メール配信ログ
define('T_UNIQUE_UID','unique_uid');
define('T_PROJECT','project');
define('T_IMPORT_FILE','import_file');

//組織マスタ
define('T_DIV','divs');

//ユーザ関連付け
define('T_USER_RELATION','usr_relation');
define('T_PROJECT_RELATION','usr_project_relation');
define('T_USER_TYPE','user_type');

define('T_AUTH_SET_DIV','auth_set_div');
define('T_MESSAGE','message');

//このテーブルは回答バックアップ機能を使うときのみ使用する
define('T_EVENT_SUB_DATA_BACKUP', "subevent_data_backup");

//パスワード再設定情報持つテーブル--追加2012-05-18
define('T_REISSUE_URL', "reissue_url");
//基本設定
define('T_SETTING', "setting");
//期間設定
define('T_FROMTO', "fromto");
//アクセスログ
define('T_ACCESS_LOG', "access_log");

//経年比較
define('T_SECULARS', 'seculars');
define('T_SECULAR_CONDUCTORS', 'secular_conductors');

//経年比較の対象テーブル。追加時は配列に追加。定数保持内容はテーブル名のカンマ繋ぎ文字列
define('SECULAR_TARGET_TABLES', implode(',', array(T_USER_MST, T_USER_RELATION, T_EVENT_DATA, T_EVENT_SUB_DATA)));
define('DB_NAME_SECULAR_SUFFIX', '_secular'); //経年比較用DBのサフィックス
}

//ディレクトリ設定
{
/** crm_define.phpが置いてあるディレクトリの絶対パス */
define('DIR_SYS_ROOT', dirname(__FILE__).'/');

define('DIR_LOG'	,DIR_SYS_ROOT.'logs/');	/** ログ格納ディレクトリ */

define('DIR_DATA'	,DIR_SYS_ROOT.'data/');	/** データファイル格納ディレクトリ */

define('DIR_TMPL'	,DIR_SYS_ROOT.'tmpl/');/** テンプレート格納ディレクトリ */

define('DIR_LIB'	,DIR_SYS_ROOT.'I/');		/** ライブラリ格納ディレクトリ */

define('DIR_ADMIN_CLASSES', DIR_LIB . 'admin_classes/'); /** admin/*.phpに対応したClass格納ディレクトリ */

define('DIR_TMP'	,'/tmp/');					/** 一時ファイル格納ディレクトリ */

define('DIR_CACHE'	,DIR_SYS_ROOT.'cache/');	/** キャッシュ格納ディレクトリ */

//define('DIR_SRC'	,'https://smart-review.s3-ap-southeast-1.amazonaws.com/img/');			/** 画像/CSS/JSファイル格納ディレクトリ */
// "スマレビ"用の新しいロゴに切り替え
define('DIR_SRC'	,'https://smart-review.s3-ap-southeast-1.amazonaws.com/img_ver2/');		/** 画像/CSS/JSファイル格納ディレクトリ */
define('DIR_IMG'	,(defined('URL_IMG') && URL_IMG!="")? URL_IMG:DIR_SRC);
define('DIR_CSS'	,(defined('URL_CSS') && URL_CSS!="")? URL_CSS:DIR_SRC);
define('DIR_JS'		,(defined('URL_JS') && URL_JS!="")? URL_JS:DIR_SRC);

define('DIR_TEMPLATES'	,DIR_SYS_ROOT.'templates/');
define('DIR_TEMPLATES_COMPILE'	,DIR_SYS_ROOT.'templates_c/');

define('FILE_ABC'	,DIR_LIB.'ABC.csv');		/** ABCカナ変換ファイル */

/** 文言管理用キャッシュ */
define('FILE_MESSAGE_CACHE',DIR_DATA.'message.ccache');

/** 所属管理用キャッシュ */
define('FILE_DIV_CACHE',DIR_DATA.'div.ccache');

/** 管理画面トップニュースキャッシュ */
define('FILE_ADMIN_NEWS_CACHE',DIR_DATA.'news_admin.ccache');

/** シート用キャッシュ */
define('FILE_SHEET_CACHE',DIR_DATA.'sheet.ccache');
/** ユーザータイプキャッシュ */
define('FILE_USER_TYPE_CACHE',DIR_DATA.'user_type.ccache');
/** 設定キャッシュ */
define('FILE_SETTING_CACHE',DIR_DATA.'setting.ccache');
/** 期間キャッシュ */
define('FILE_FROMTO_CACHE',DIR_DATA.'fromto.ccache');
}

//ログ設定
{
//メール送信ログファイル名 (CbaseFSendmail.php)
define('LOG_FILE_NAME'      ,"%s".date("Ym").".clog");
define('LOG_SEND'           ,sprintf(LOG_FILE_NAME, DIR_LOG.'send'));
define('LOG_SEND_ERROR'     ,sprintf(LOG_FILE_NAME, DIR_LOG.'send_error'));
define('LOG_IMPORT_BOUNCE'  ,sprintf(LOG_FILE_NAME, DIR_LOG.'import_bounce'));

define('LOG_CRONMAIL'       ,sprintf(LOG_FILE_NAME, DIR_LOG.'mail1'));	//CbaseCronMail.php //Cronの実行状態のログ
define('LOG_CRONMAIL2'      ,sprintf(LOG_FILE_NAME, DIR_LOG.'mail2'));	//CbaseCronMail.php //ロックが掛かりっぱなしなど異常事態のログ
define('LOG_CRONMAIL3'      ,sprintf(LOG_FILE_NAME, DIR_LOG.'mail3'));	//実際に配信したアドレスのリスト
define('LOG_CRON_ERROR'     ,sprintf(LOG_FILE_NAME, DIR_LOG.'cron_error'));

define('LOG_FILE_PHP_ERROR' ,sprintf(LOG_FILE_NAME, DIR_LOG.'phperror'));		/** PHPエラーログ **/
define('LOG_FILE_PHP'       ,sprintf(LOG_FILE_NAME, DIR_LOG.'php'));	/** PHPログ **/
define('LOG_FILE_SQL'       ,sprintf(LOG_FILE_NAME, DIR_LOG.'sql'));	/** SQLログ */
define('LOG_FILE_MAIL'      ,sprintf(LOG_FILE_NAME, DIR_LOG.'mail'));	/** メール送信ログ */
define('LOG_FILE_SQL_DELETE',sprintf(LOG_FILE_NAME, DIR_LOG.'sql_delete'));/** DELETE実行ログ */
define('LOG_MAX_SQL_TIME'   ,sprintf(LOG_FILE_NAME, DIR_LOG.'sql_maxtime'));/** 実行時間測定ログ */
define('LOG_FILE_DEMO'      ,sprintf(LOG_FILE_NAME, DIR_LOG.'demo'));	/** デモ時のアクセスログ */
define('DIR_ERROR_MAIL_LOG' ,sprintf(LOG_FILE_NAME, DIR_LOG.'error_mail'));	/** 取込失敗メール格納ディレクトリ  */

define('LOG_DELETE_ANSWER'  ,sprintf(LOG_FILE_NAME, DIR_LOG.'delete_answer'));	/** 回答削除ログ */
define('LOG_DELETE_LOGFILE' ,sprintf(LOG_FILE_NAME, DIR_LOG.'delete_logfile'));	/** ログファイル削除ログ */
define('LOG_DELETE_USER'    ,sprintf(LOG_FILE_NAME, DIR_LOG.'delete_user'));		/** ユーザ削除ログ */

define('LOG_SECULAR'        ,sprintf(LOG_FILE_NAME, DIR_LOG.'secular'));    /** 経年比較の処理ログ */
define('LOG_CHECK_ENV'      ,sprintf(LOG_FILE_NAME, DIR_LOG.'check_env'));  /** 環境チェックのアクセスログ */

/** 管理者ログインログ (DIR_MNG/index.php) */
define('LOG_LOGIN_MNG'      ,sprintf(LOG_FILE_NAME, DIR_LOG.'adminLogin'));
/** ユーザログインログ*/
define('LOG_LOGIN_USER'     ,sprintf(LOG_FILE_NAME, DIR_LOG.'userLogin'));
/** ユーザダウンロードログ*/
define('LOG_DOWNLOAD_USER'  ,sprintf(LOG_FILE_NAME, DIR_LOG.'userDownload'));
}
//メニューカテゴリ名設定
{
$nameMenu =array("sheet"=>array("評価シート管理"),
                    "user"=>array("マイページ管理"),//todo:トップ文言編集へのリンクに変える
                    "mail"=>array("メール配信"),
                    "import"=>array("マスタ取り込み"),
                    "search"=>array("各種検索"),
                    "data"=>array("データDL"),
                    "other"=>array("その他"),
                );
}
//管理ページ設定
{
$arMenu[] = array("sheet","crm_enq0_client.php","評価シート一覧","main",false);
$arMenu[] = array("sheet","crm_enq0.php,enq_event.php,enq_subevent.php,enq_subevent2.php,enq_setcond.php,enq_list.php,enq_cond.php,set_cond.php,cond_list.php","評価シート一覧(編集機能あり)","main",false);
//$arMenu[] = array("sheet","360_enq_import.php","CSVから作成/更新","main",false);
$arMenu[] = array("sheet","360_enq_import_message.php","CSVから言語別文言更新","main",false);
$arMenu[] = array("sheet","360_enq_update_all.php","一括更新(詳細)","main",false);
$arMenu[] = array("sheet","enq_copy.php","import/export","main",false);

$arMenu[] = array("user","360_message_view_client.php,360_message_edit.php","文言管理","main",false);
$arMenu[] = array("user","360_message_view.php,360_message_edit.php","文言管理(全て)","main",false);
$arMenu[] = array("user","360_message_import.php","文言一括更新","main",false);
$arMenu[] = array("user","360_file_edit.php","テンプレート編集","main",false);
$arMenu[] = array("user","360_term_edit.php","リンク表示期間設定","main",false);

$arMenu[] = array("mail","crm_mf1.php,crm_mf2.php,crm_mf_import.php","ひな型管理","main",false);
$arMenu[] = array("mail","360_make_mail_cond.php","配信条件作成","main",false);
$arMenu[] = array("mail","enq_cond.php,enq_sqlsearch.php,enq_sqls_edit.php,mail_target_list.php,360_mail_target_list_dl.php","配信条件設定","main",false);
$arMenu[] = array("mail","enq_mailrsv.php","配信予約","main",false);
$arMenu[] = array("mail","crm_mr1.php,crm_mr1.php,crm_mr2.php,mail_target_list.php,mail_log_list.php","予約一覧","main",false);

//if (OPTION_MAIL_REVEICED === 1)
//{
//	$arMenu[] = array("mail","crm_mail_received.php","受信メール一覧","main",false);
//}

$arMenu[] = array("import","360_div_import.php","組織マスタインポート","main",false);
$arMenu[] = array("import","360_user_import.php","ユーザマスタインポート","main",false);
$arMenu[] = array("import","360_relation_import.php","回答者選定インポート","main",false);
$arMenu[] = array("import","360_admit_import.php","承認者設定インポート","main",false);
$arMenu[] = array("import","360_viewer_import.php","参照者設定インポート","main",false);
$arMenu[] = array("import","360_answer_import.php","評価インポート","main",false);
$arMenu[] = array("import","360_answer_import_multiple.php","評価インポート(複数設問対応)","main",false);

//$arMenu[] = array("master","user_mail_wizard.php","回答ユーザ登録ウィザード","main",false);
//$arMenu[] = array("master","360_excel_import.php","ユーザマスタインポート(Excel)","main",false);

$arMenu[] = array("search","360_div_search.php,360_div_edit.php","組織マスタ検索","main",false);
$arMenu[] = array("search","360_user_search.php,360_user_edit.php","ユーザマスタ検索","main",false);
$arMenu[] = array("search","360_user_relation_search.php,360_user_relation_view.php,360_user_relation_edit.php,360_user_respondent_edit.php,360_user_respondent_new.php","回答者選定検索","main",false);
$arMenu[] = array("search","360_target_relation_search.php,360_target_relation_view.php,360_target_relation_edit.php","対象者選定検索","main",false);
$arMenu[] = array("search","360_admit_relation_search.php,360_admit_relation_edit.php","承認者設定検索","main",false);
$arMenu[] = array("search","360_viewer_relation_search.php,360_viewer_relation_edit.php","参照者設定検索","main",false);
$arMenu[] = array("search","360_user_evaluator_search.php","代理ログイン","main",false);
$arMenu[] = array("search","360_enq_search.php","回答状況検索","main",false);
$arMenu[] = array("search","360_enq_search_all.php,enq_search_csv.php","回答状況検索(詳細)","main",false);
$arMenu[] = array("search","360_enq_search_div.php","回答状況検索(所属別)","main",false);
$arMenu[] = array("search","360_user_pw_search.php,360_user_pw_edit.php","ログイン管理","main",false);
$arMenu[] = array("search","360_fb_search.php","対象者FB検索","main",false);

$arMenu[] = array("data","360_rawdata_dl_menu.php,DLspecial.php","評価Rawデータダウンロード","main",false);
$arMenu[] = array("data","360_export_result_total.php","集計値ダウンロード(旧)","main",false);
$arMenu[] = array("data","360_export_result_total2.php","集計値ダウンロード(ベーシック)","main",false);
$arMenu[] = array("data","360_export_result_total3.php","集計値ダウンロード(標準偏差)","main",false);
$arMenu[] = array("data","360_export_result_total4.php","集計値ダウンロード(ベーシック＋計算)","main",false);
$arMenu[] = array("data","360_export_result_total_fb.php","集計値ダウンロード(FB用)","main",false);
$arMenu[] = array("data","360_comment_menu.php,360_comment_export.php,360_comment_import.php","回答コメント訂正","main",false);
$arMenu[] = array("data","360_dlcsv_menu.php,360_dlcsv.php","各種データダウンロード","main",false);

//$arMenu[] = array("data","crm_enq_data_delete.php","回答データ削除","main",false);

$arMenu[] = array("other","mng_mst.php","管理者マスタ管理","main",false);
$arMenu[] = array("other","360_muser_import.php","管理者マスタインポート","main",false);
$arMenu[] = array("other","mng_colmun_setting.php","ユーザマスタ項目設定","main",false);
$arMenu[] = array("other","360_user_type_list.php,360_user_type_edit.php","ユーザータイプ管理","main",false);
$arMenu[] = array("other","mng_permit.php","ページ別権限管理","main",false);
$arMenu[] = array("other","mng_permit_read_only.php","ページ別権限管理(閲覧のみ)","main",false);
$arMenu[] = array("other","mng_colmun_permit.php","ユーザ情報権限管理","main",false);
$arMenu[] = array("other","360_musr_list.php,360_musr_authedit.php","所属別権限管理","main",false);
$arMenu[] = array("other","360_muser_div_import.php","所属別権限インポート","main",false);
$arMenu[] = array("other","logList.php","各種ログ確認","main",false);
$arMenu[] = array("other","error_replace.php","エラー文言編集","main",false);
$arMenu[] = array("other","360_secular_mst.php,360_secular_edit.php","経年比較マスタ管理","main",false);
$arMenu[] = array("other","360_admin_operate.php","Cbase用データ操作","main",false);

//$arMenu[] = array("edit","360_easy_install.php","かんたん設定","main",false);
$arMenu[] = array("edit","360_define_edit.php,360_define_import.php","基本設定","main",false);
$arMenu[] = array("edit","360_define_edit_client.php","運用設定","main",false);

//$arMenu[] = array("manage","musr_list.php,musr_authedit.php","シート別権限管理","main",true);

//$arMenu[] = array("master","360_dairy_report.php","進捗状況推移確認","main",false);

/*
$arMenu[] = array("inquiry","_inquiry_search.php,_inquiry_detail.php","お問い合わせ管理","main",false);
$arMenu[] = array("inquiry","_inquiry_template_search.php,_inquiry_template_edit.php","返信用テンプレート","main",false);
$arMenu[] = array("inquiry","_inquiry_filter_edit.php","対応振り分けフィルタ","main",false);
$arMenu[] = array("message","_message_search.php,_inquiry_message.php","ユーザページ文言一覧","main",false);
$arMenu[] = array("message","_message_menu.php,_message_export.php,_message_import.php","ユーザページ文言一括編集","main",false);
*/
/*
$arMenu[] = array("tool","manual.html","各種マニュアル","main",false);
$arMenu[] = array("tool","freetool.html","テキストデータTool","main",false);
*/
/**
 * 上記メニューのうち、authsetで設定可能な項目を振り分ける
 * authset内分類 => メニュー分類 => 'all'またはメニュー名の配列で指定
 */
$GLOBAL_AUTHSET_CONTENTS = array(
    //アンケートごとに設定できる権限に含めるメニュー
    'enquete'=>array(
        'enquete' => 'all',
        //'mail' => array('メール配信予約'),
        //'master' => 'all',
    )
);

/**
 * アンケート作成時に自動的に権限付与される項目
 * 設定は$GLOBAL_AUTHSET_CONTENTSと同じ。
 */
$GLOBAL_AUTHSET_AUTO_ADD = $GLOBAL_AUTHSET_CONTENTS;
}

//基礎データ設定
{
//都道府県配列
$pref = array("北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県","茨城県",
"栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県","新潟県","富山県","石川県","福井県",
"長野県","山梨県","岐阜県","静岡県","愛知県","三重県","滋賀県","京都府","大阪府","兵庫県",
"奈良県","和歌山県","鳥取県","島根県","岡山県","広島県","山口県","徳島県","香川県","愛媛県",
"高知県","福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県");

//年月日時分配列
$yyyy = range(date('Y')-5, date('Y')+5);
$mm = range(1, 12);
$dd = range(1, 31);
$hh = range(0, 23);
$ii = array(0,10,20,30,40,50);

//携帯ドメイン配列
$mobile_domain = array(
"@docomo.ne.jp",
"@jp-d.ne.jp","@jp-h.ne.jp","@jp-t.ne.jp","@jp-c.ne.jp","@jp-r.ne.jp","@jp-k.ne.jp","@jp-n.ne.jp","@jp-s.ne.jp","@jp-p.ne.jp",
"@d.vodafone.ne.jp","@h.vodafone.ne.jp","@t.vodafone.ne.jp","@c.vodafone.ne.jp","@r.vodafone.ne.jp",
"@k.vodafone.ne.jp","@n.vodafone.ne.jp","@s.vodafone.ne.jp","@q.vodafone.ne.jp",
"@softbank.ne.jp","@i.softbank.ne.jp","disney.ne.jp",
"@ezweb.ne.jp","ido.ne.jp","@sky.tkk.ne.jp","@sky.tkc.ne.jp","@sky.tu-ka.ne.jp",
"@pdx.ne.jp"
);

//Eメール正規表現
define('PREG_EMAIL', "/^[a-zA-Z0-9_\-\.]+?@([a-zA-Z0-9_\-\.]+)$/");
}

//携帯設定
{
//携帯正規表現
define('EREG_MOBILE', "^DoCoMo|^J\-PHONE|^Vodafone|^SoftBank|^MOT\-[CV]|^KDDI\-|UP\.Browser|^PDXGW|DDIPOCKET|WILLCOM|ASTEL");

//携帯からのアクセスかどうかをチェックするにはこの定数を呼ぶ
require_once(DIR_LIB.'CbaseFMobile.php');
define('IS_MOBILE', (OPTION_ENQ_MOBILE && isMobile($_SERVER['HTTP_USER_AGENT'], EREG_MOBILE)));
}

//CbaseEncoding設定
{
if (IS_MOBILE) {
    define('ENCODE_WEB_IN'	,'SJIS-win');	/** ブラウザからの文字を受け取ったのエンコード */
    define('ENCODE_WEB_OUT'	,'SJIS-win');	/** ブラウザへ文字を出力するときのエンコード */
}

define('INTERNAL_ENCODE'	,'UTF-8');		/** 内部エンコード */

define('ENCODE_WEB_IN'	,'UTF-8');		/** ブラウザからの文字を受け取ったのエンコード */
define('ENCODE_WEB_OUT'	,'UTF-8');		/** ブラウザへ文字を出力するときのエンコード */

define('ENCODE_FILE_IN'	,'UTF-8');		/** 外部ファイルを読み込む時のエンコード */
define('ENCODE_FILE_OUT'	,'UTF-8');		/** 外部ファイルを書き出す時のエンコード */

define('ENCODE_HTML_IN'	,'SJIS-win');		/** 外部HTMLファイルを読み込む時のエンコード */
define('ENCODE_HTML_OUT'	,'SJIS-win');		/** 外部HTMLファイルを書き出す時のエンコード */

define('ENCODE_DB_IN'		,'UTF-8');		/** DBから文字を受け取るときのエンコード */
define('ENCODE_DB_OUT'	,'UTF-8');		/** DBへ文字を投げるときのエンコード */

define('ENCODE_UPLOAD_IN'	,'SJIS-win');		/** アップロードされたファイルのエンコード */
define('ENCODE_DOWNLOAD_OUT','SJIS-win');	/** ファイルをダウンロードするときのエンコード */

define('ENCODE_MAIL_OUT'	,'ISO-2022-JP');	/** メールの送る時のエンコード */
}

//内部エンコードの設定
mb_internal_encoding(INTERNAL_ENCODE);

//その他(廃止?)
define("TEXT_PULLDOWN_DEFAULT", '####enq_message4####');

/** 空っぽorDefineなしならログなし */
if (defined('LOG_FILE_DEMO') && LOG_FILE_DEMO) {
    $logdata=array();
    $logdata[] = date('Y-m-d H:i:s');
    $logdata[] = array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR']));
    $logdata[] = $_SERVER['SCRIPT_NAME'];
    error_log(implode("\t",$logdata)."\n", 3, LOG_FILE_DEMO);
}

require_once (DIR_LIB."360_Function.php");
require_once '360_define.php';
require_once 'cbase/common_define.php';

/* ヒアドキュメント内で定数を呼び出すためのクラス {$GDF->get('define')} */
global $GDF;
require_once(DIR_LIB.'DefConst.php');
$GDF = DefConst::getInstance();

ini_set('log_errors', 1);
ini_set('error_log', LOG_FILE_PHP_ERROR);

//AWS対応
//セッション
require_once(DIR_LIB."CbaseFDB.php");
require_once 'HTTP/Session2.php';
require_once(DIR_LIB."360_AuthSession.php");
HTTP_Session2::useTransSID(false);
HTTP_Session2::useCookies(($GLOBALS['Setting']->sessionModeCookie())? true:false);
HTTP_Session2::setIdle(time() + 60*30);
HTTP_Session2::setContainer('MDB2', array('dsn' => &$con, 'table' => 'sessiondata'));
$AuthSession = new _360_AuthSession();
$AuthSession->setProxySessionName();
define("SESSIONID", session_name());
$AuthSession->setCookieParams();
