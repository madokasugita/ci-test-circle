<?php
define('PROJECT_NAME','lenovo');

/** ログアウト画面をSSO用にするなら1 */
define('SSO_LOGOUT_FLAG',0);

/** ログインしていないユーザを抽出 */
define('OPTION_LOGIN_FLAG', 1);

/** ユーザ用ログインページ */
define('LOGIN_URL',DOMAIN . DIR_MAIN . "360_login.php");

/** ユーザ用認証済みログインページ */
define('LOGIN_URL_S',DOMAIN . DIR_MAIN . "360_login_s.php");

$_360_menu_type['select'] = '####mypage_menu1####';
$_360_menu_type['admit'] = '####mypage_menu5####';
$_360_menu_type['input'] = '####mypage_menu2####';
//$_360_menu_type['view'] = '####mypage_menu3####';
$_360_menu_type['fb'] = '####mypage_menu4####';
$_360_menu_type['review'] = '####mypage_menu6####';

$GLOBALS['user_type_utype'] = array(
        0 => "本人",
        1 => "他者",
        2 => "承認者",
        3 => "参照者"
);

$INPUTER_COUNT=0;
foreach (getUserTypes() as $user_type) {
    if($user_type['utype']==2)//承認者
        define('ADMIT_USER_TYPE', $user_type['user_type_id']);
    else if($user_type['utype']==3)//参照者
        define('VIEWER_USER_TYPE', $user_type['user_type_id']);
    else if($user_type['utype']==1)//入力者(他者)
        $INPUTER_COUNT++;

    $GLOBALS['_360_user_type'][$user_type['user_type_id']] = $user_type['name'];
}

define('INPUTER_COUNT', $INPUTER_COUNT);

$GLOBALS['_360_select_status'][0] = '####select_status1####';//選択中
$GLOBALS['_360_select_status'][1] = '####select_status2####';//承認依頼中
$GLOBALS['_360_select_status'][2] = '####select_status3####';//承認済み

$GLOBALS['aryComparisons'] = array('=','!=','<','>','<=','>=','LIKE','NOT LIKE');

$GLOBALS['userMasterTable'] = array(
'uid'=>'ユーザーＩＤ',
'sheet_type'=>'シートタイプ',
'mflag'=>'本人フラグ',
'name'=>'氏名',
'div1'=>'所属コード(大)',
'div2'=>'所属コード(中)',
'div3'=>'所属コード(小)',
'mail'=>'メールアドレス',
'select_status'=>'回答者選択状況'
);

$GLOBALS['aryColumn'][] = array('usr','email','メールアドレス');
$GLOBALS['aryColumn'][] = array('usr','name','名前');
$GLOBALS['aryColumn'][] = array('usr','pw','パスワード');
$GLOBALS['aryColumn'][] = array('other','mypage','マイページへのアクセス');
$GLOBALS['aryColumn'][] = array('other','answer','回答データ');
$GLOBALS['aryColumn'][] = array('other','user_delete','ユーザ削除');
$GLOBALS['aryColumn'][] = array('other','enq_delete','回答削除');

/* 利用設定は360_define.csv　*/
//$_360_language[-1] = 'テスト';
$langs['_360_language'][0] = '日本語';
$langs['_360_language'][1] = 'English';
$langs['_360_language'][2] = '繁体字';
$langs['_360_language'][3] = '簡体字';
$langs['_360_language'][4] = '韓国語';

$langs['_360_language_org'][0] = '日本語';
$langs['_360_language_org'][1] = 'English';
$langs['_360_language_org'][2] = '繁體字';
$langs['_360_language_org'][3] = '简体字';
$langs['_360_language_org'][4] = '한국어';

//evid 100 = 経営職 本人シート evid 302= 事務職 部下シート

//組織マスタ
define('T_DIV','div');

//ユーザ関連付け
define('T_USER_RELATION','usr_relation');

define('T_AUTH_SET_DIV','auth_set_div');

define('T_MESSAGE','message');

/** コピーライト*/
define('MSG_CBASE_COPY_RIGHT', '####cbase####');
/** ログイン画面タイトル */
define('MSG_LOGIN_PAGE_TITLE',RESEARCH_TITLE.' ####login_title####');

/** お知らせ画面タイトル */
define('MSG_NEWS_TITLE',RESEARCH_TITLE.' ####news_title####');

/** マイページタイトル */
define('MSG_MENU_TITLE',RESEARCH_TITLE.' ####mypage_title####');

/** 文言:ログアウトしたとき */
define('MSG_LOGOUT', '####login_logouted####');

/** 文言:idが未入力の時 */
define('MSG_ERROR_LOGIN_1', '####login_error_id_empty####');

/** idの正規表現 */
define('EREG_LOGIN_ID', '^[-0-9a-zA-Z\.@_\+\-]+$');

/** 文言:idの書式がおかしいとき */
define('MSG_ERROR_LOGIN_2', '####login_error_id_invalid####');

/** 文言:パスワードが未入力の時 */
define('MSG_ERROR_LOGIN_3', '####login_error_pw_empty####');

/** 文言:パスワードに半角英数字以外の文字が含まれていたとき */
define('MSG_ERROR_LOGIN_4', '####login_error_pw_invalid####');

/** 文言:データベースにユーザーＩＤが登録されいないとき */
define('MSG_ERROR_LOGIN_5', '####login_error_id_not_found####');

/** 文言:パスワードが間違っていたとき*/
define('MSG_ERROR_LOGIN_6', '####login_error_pw_wrong####');

/*
/** 文言:データベースにユーザーＩＤが対象者外だったとき */
//define('MSG_ERROR_LOGIN_7', '対象者ではありません');

//define('MSG_NOT_FOUND_USER','メールアドレスが設定されていません。');

define('FILE_DEFINES', DIR_DATA . '360_define.csv');
$Setting = new _360_Setting();
foreach (getSetting() as $setting) {
    switch ($setting['define']) {
        case '_360_language':
            foreach (explode(",", $setting['value']) as $v) {
                $GLOBALS[$setting['define']][$v] = $langs[$setting['define']][$v];
                $GLOBALS[$setting['define'].'_org'][$v] = $langs[$setting['define'].'_org'][$v];
            }
            break;
        default:
            if (preg_match('/^(\\d+):/', $setting['value'], $match)) {
                $setting['value'] = $match[1];
            }
            define($setting['define'], $setting['value']);
            break;
    }
}

/**
 * ディレクトリ定義
 */
{

    /** システムデータ */
    define('DIR_SYSDATA', DIR_SYS_ROOT . 'data/');

    /** 画像、CSS、JSなど */
//	define('DIR_SRC', DIR_ROOT . 'img/');
//	define('DIR_IMG', DIR_SRC);
//	define('DIR_CSS', DIR_SRC);
//	define('DIR_JS', DIR_SRC);

    define('DIR_IMG_USER_LOCAL'	,DIR_ROOT.'img/_/');	/** 管理画面から編集可能なimgディレクトリ **/
    define('DIR_IMG_USER'	,($Setting->dirImgNotEmpty())? OUTER_DIR_IMG_USER:DIR_IMG_USER_LOCAL);	/** 管理画面から編集可能なimgディレクトリ **/

    /** feedbackHTMLがおいてあるディレクトリ); **/
    define('DIR_FB',DIR_SYS_ROOT.'feedback/');

    /** フィードバック出力ディレクトリ **/
    define('DIR_FEEDBACK', DIR_FB);
}
/**
 *  ファイル定義
 */
{
    define('FILE_EVENTS_CACHE', DIR_SYSDATA. 'events.ccache');

    /** JavaScript外部ファイル */
    define('FILE_JS', DIR_JS. '360_userpage.js');

    /** ユーザーページCSS外部ファイル */
    define('FILE_CSS', DIR_IMG_USER. '360_userpage-min.css');

    /** SQLログファイル */
    define('FILE_LOG_SQL', sprintf(LOG_FILE_NAME, DIR_LOGS.'sql'));

    /** 期間設定ファイル */
    //define('FILE_DAT_FROMTO',DIR_SYSDATA.'360_fromto.dat');

    /** ログインページお知らせ*/
    define('FILE_INFO_LOGIN',DIR_SYSDATA.'360_info_login.txt');

    /** アンケートエラーメッセージ集 */
    define('FILE_ERROR_MSG',DIR_SYSDATA.'error.txt');

    /** 言語別メッセージ */
    define('FILE_language',DIR_SYSDATA.'language.csv');

    define('MESSAGE_CACHE',DIR_DATA.'message.ccache');

    define('DATA_FILE_EXTENTION','.csv');
}

/**
 * カラム定義
 */
{
    /** ログインID */
    define('C_ID','uid');

    /** パスワードを間違った回数 */
    define('C_PASSWORD_MISS','pwmisscount');

    /** ログイン済みかのフラグ */
    define('C_LOGIN_FLAG', 'login_flag');

    define('C_EVENT_LIST','eventlist');

    define('C_SERIAL','serial_no');
}
{
    //管理画面用のユーザタイプ名称を読み込む
    if (ereg(DIR_MNG,$_SERVER['SCRIPT_NAME'])) {
        foreach (getUserTypes() as $user_type) {
            $GLOBALS['_360_user_type'][$user_type['user_type_id']] = $user_type['admin_name'];
        }
    }
}
if($Setting->csvTabDelimiter())
    define('OUTPUT_CSV_DELIMITER',"\t");
else
    define('OUTPUT_CSV_DELIMITER',",");

if($Setting->hideTest())
    define('TEST',0);
else
    define('TEST',1);

foreach(getSheetList() as $s)
    $GLOBALS['_360_sheet_type'][$s['sheet_type']] = "####sheet_type{$s['sheet_type']}####";

define('OFFSET_BREAK_FOR_MEMORY', 10000);
