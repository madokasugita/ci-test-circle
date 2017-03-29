<?php
/*
 * DIR_LIB_MAIL
 * DIR_EML
 * DIR_MAIL_ERROR_LOG
 * が必要
 */
//chdir(dirname(__FILE__));

//define('DEBUG', 1);

define('RUN_AUTH_CHECK', 0);
define('DIR_ROOT', '../');

require_once(DIR_ROOT.'crm_define.php');
$lib = DIR_LIB;
$reclib = DIR_RECRUIT_LIB;
require_once($lib."CbaseCommon.php");
require_once($lib."CbaseFDB.php");
require_once($lib."CbaseFCheckModule.php");
require_once($lib."CbaseDataEditor.php");

require_once($lib."PrimaryKeyDAO.php");
require_once($lib."DAOEditor.php");
require_once($lib."DataMailReceivedDAO.php");
require_once($lib."ReceiveMailLib.php");

require_once(DIR_MAIL_LIB.'CbaseMailRead.php');

class MyCbaseMailRead extends CbaseMailRead
{
    public $filepath = '';
    public function setEml($eml)
    {
        $this->filepath = $eml;

        return parent::setEml($eml);
    }

    public function backup($path)
    {
        $contents = file_get_contents($this->filepath);
        s_write($path, $contents);
    }

    public function delete()
    {
        s_unlink($this->filepath);
    }
}

writeEntryMailLog ('##start##');
$locker = new SendEntryMailFileLocker('receive_mail_register');
if (!$locker->lock()) {
    writeEntryMailLog ('##lock end##');
    exit;
}

$res = array();
$CMR = new MyCbaseMailRead();

foreach (glob(DIR_MAIL_RECEIVED."*.eml") as $eml) {
    //配下のdirもチェックする？仕様不明のため保留
    if (!$CMR->setEml($eml)) {
        onCbaseMailReadError ($CMR, 'read error:'.$CMR->filepath);
        $CMR->delete($eml);
        continue;
    } elseif (!isOkHeader($CMR)) {
        onCbaseMailReadError ($CMR,
            'no email:'.$to.' from:'.$CMR->getFrom().':'.$CMR->filepath);
        $CMR->delete($eml);
        continue;
    } elseif (!($id = registerMailErrorLog ($CMR))) {
        onCbaseMailReadError ($CMR, 'register error:'.$CMR->filepath);
        $CMR->delete($eml);
        continue;
    } else {
        $res[] = $id;
    }
    $CMR->delete($eml);
}

if ($res) {
    writeEntryMailLog ('entry:'.implode(',', $res));
}

$locker->unlock();
writeEntryMailLog ('##end##');
/**
 * 	toアドレスがEメールとしておかしい場合はfalseを返す
 * 	fromアドレスがおかしい場合は携帯など有りうるので放置する
 */
function isOkHeader($CMR)
{
    //TODO:fromのエラーはログのみ記録してもよいが、今は何もしていない
    $to = $CMR->getTo();

    foreach (explode(',', $to) as $k => $v) {
        if (!FCheck::isEmail(getEmailParts($v))) {
            return false;
        }
    }

    return true;
}

/**
 * ファイルをバックアップして保存して終了する
 */
function onCbaseMailReadError($CMR, $errormessage)
{
    $CMR->backup(DIR_ERROR_MAIL_LOG);
    writeEntryMailLog ($errormessage);
}

/**
 * この関数内を案件固有の処理にする
 */
function registerMailErrorLog($CMR)
{
    $error_flag = $CMR->getErrorFlg();

    if ($error_flag == 0) {
        //エラーが0なら通常の受信メールとして処理する
        return saveOkMail($CMR);
    } else {
        //エラーメールは専用テーブルへ格納
        return 'err:'.saveErrorMail($CMR);
    }
}

function saveErrorMail($CMR)
{
    $sub = $CMR->getSubject();
    $to = $CMR->getTo();
    $from = $CMR->getFrom();
    $replyTo = $CMR->getHeaderInfo('In-Reply-To');
    $message_id = $CMR->getMessageID();

    saveMailLog($CMR);

    $mao = getAllMailReceived();
    $recent = $mao->getRecent();

    return $recent["mail_received_id"];
}

function saveMailLog($CMR)
{
    $mao = getAllMailReceived();
    $mao->insert(
        array(
            'mail_to' => $CMR->getTo(),
            'title' => $CMR->getSubject(),
            'body' => $CMR->getBody(),
//			'uncertain_flag' => 1,
            'mail_from' =>$CMR->getFrom(),
//			'header_log' => $CMR->getHeader(),
//			'admin_id' => 1, // dome only※1
//			'message_id' => $CMR->getMessageID(),
//			'mail_attach_ids' =>implode(',', $ids) ,
            'rdate' => $CMR->getDate() ,
        )
    );
}

/**
 * データを登録してデータIDを返す
 */
function saveOkMail($CMR)
{
    $sub = $CMR->getSubject();
    $to = $CMR->getTo();
    $from = $CMR->getFrom();
    $replyTo = $CMR->getHeaderInfo('In-Reply-To');
    $message_id = $CMR->getMessageID();

    saveMailLog($CMR);

    $mao = getAllMailReceived();
    $recent = $mao->getRecent();

    return $recent["mail_received_id"];
}

//function getUserFromAllGroup ($email)
//{
//	global $global_all_group;
//	if(!$global_all_group)
//	{
//		$gao = getDAO('Group');
//
//		$global_all_group = array();
//		foreach ($gao->getAll('group_id') as $v)
//		{
//			if(0 < $v['group_id'])
//				$global_all_group[] = getDAO('User', $v['group_id']);
//		}
//	}
//
//	$where = array();
//	foreach ($email as $v)
//	{
//		$where[] = 'email = '.FDB::escape($v);
//		$where[] = 'email_mobile = '.FDB::escape($v);
//	}
//
//	$w = 'WHERE '.implode(' OR ', $where);
//	$sql = array();
//	foreach ($global_all_group as $v)
//	{
//		$v->modeSQL = true;
//		$v->modeNl = false;
//		$sql[] = $v->getByCond($w, 'user_id,group_id');
//		$v->modeNl = true;
//		$v->modeSQL = false;
//	}
//
//	$sql = implode(' UNION ALL ', $sql);
//	return FDB::getAssoc($sql);
//}

//署名つきを読んでemailを返す
function getEmailParts($email)
{
    //署名無しならそのまま
    if(mb_strpos($email, '<')=== false) return $email;
    $m = array();
    mb_ereg('<([^>]*?)>', $email, $m);

    return $m[1];
}
