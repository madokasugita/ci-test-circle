<?php

/**
 * メール雛形マスタ系
 * @package Cbase.Research.Lib
 *
 * 2007/11/16 MailFormatの更新にFDBを使うように kido
 * 2007/12/13 UPDATE時にmuidを上書きしないように kido
 */
require_once 'CbaseFGeneral.php';

$mail_format_cols = array (
    'mfid',
    'name',
    'title',
    'body',
    'file',
    'title_1',
    'body_1',
    'file_1',
    'title_2',
    'body_2',
    'file_2',
    'title_3',
    'body_3',
    'file_3',
    'title_4',
    'body_4',
    'file_4',
    'header',
    'footer',
    'cdate',
    'udate',
    'muid'
);

/**
 * メール雛形の削除
 * @param int $version メール雛形ID
 * @return bool 削除結果
 */
function Delete_MailFormat($version = "")
{
    global $con;

    if (!$version)
        return false;

    $count = FDB::getAssoc("select count(*) as cnt from mail_rsv where mfid = " . FDB::escape($version));
    if ($count[0]["cnt"] > 0) {
        return false;
    }
    $sql = "delete";
    $sql .= " from " . T_MAIL_FORM;
    $sql .= " where mfid = " . FDB::escape($version);
    $rs = $con->query($sql);
    if (PEAR::isError($rs))
        return false;
    return true;
}
//メールひな型の内容を取得
/**
 * メール雛形の取得
 * @param int $version メール雛形ID
 * @param string $orderk ソート列
 * @param sting $orderflg ソート方法
 * @param int muid
 * @return array メール雛形データ
 */
function Get_MailFormat($version = "-1", $orderk = "", $orderflg = "", $muid="", $unbuffered = false)
{
    global $con;

    //データ取得
    $sql = "select *";
    $sql .= " from " . T_MAIL_FORM;
    if ($version <> -1)
        $sql .= " where mfid = " . FDB::escape($version);

    if ($orderk)
        $sql .= " order by " . $orderk;
    if ($orderk && $orderflg == "desc")
        $sql .= " desc";
    $rs = $con->query($sql);
    if (PEAR::isError($rs))
        return false;

    //データを配列に展開
    $array = array ();
    $row = '';

    if ($unbuffered) {
        return $rs;
    } else {
        //while ($rs->fetchInto($row, DB_FETCHMODE_ASSOC))
        while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
            $array[] = $row;
        }

        return $array;
    }
}

/**
 *メールひな型の内容を取得(idからnameのみ抽出)
 */
function Get_MailFormat_name($version = "")
{
    global $con;

    //データ取得
    $sql = "select
            (select name from ".T_MAIL_FORM." where title in (select title from ".T_MAIL_FORM." where mfid = '" . $version . "') order by mfid asc limit 1)
            as name
            from ".T_MAIL_FORM." where mfid = '" . $version . "' ";
    $rs = $con->query($sql);
//	if (DB::isError($rs)) return false;
    if (FDB :: isError($rs))
        return false;

    //データを配列に展開
    $array = array ();
    $row = '';
    //while ($rs->fetchInto($row, DB_FETCHMODE_ASSOC))
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $array[] = $row;
    }

    return $array;
}

function Sort_MailFormat($mfids)
{
    FDB::begin();
    //FDB::lock(T_MAIL_FORM, 'SHARE MODE');
    FDB::lock(T_MAIL_FORM, 'WRITE');//追加2012-05-14

    $array = Get_MailFormat(-1, "", "", $_SESSION['muid']);
    if (is_void($array)) {
        FDB::rollback();

        return false;
    }

    $plusminus = array();
    if(is_array($array))
    foreach ($array as $key => $value) {
        $pm = array_search($value['mfid'], $mfids);
        if (is_false($pm) || !is_numeric($pm)) {
            FDB::rollback();

            return false;
        }
        $pm = $pm+1-$value['mfodr'];
        if($pm!=0)
            $plusminus[$pm][] = $value['mfid'];
    }

    if(is_array($plusminus))
    foreach ($plusminus as $pm => $ids) {
        $res = FDB::update(
            T_MAIL_FORM
            ,array('mfodr' => 'mfodr'.(($pm>0)? '+':'-').abs($pm))
            ,'WHERE mfid IN ('.implode(",", FDB::escapeArray($ids)).')'
        );
        if (is_false($res)) {
            FDB::rollback();

            return false;
        }
    }

    FDB::commit();

    return true;
}

/**
 * メール雛形の複製
 * @param int $mfid メール雛形ID
 * @return int メール雛形ID
 */
function Duplicate_MailFormat($mfid)
{
    if (!$mfid)
        return false;

    $array = Get_MailFormat($mfid, "", "",$_SESSION['muid']);
    if (!$array)
        return false;
    $array[0]["name"] = getCopyName($array[0]["name"]);

    return Save_MailFormat("new", $array[0]);
}

function Save_MailFormat($mode = "new", $array)
{
    if ($mode == "new") {
        $result = insertMailFormat($array);
    } elseif ($mode == "update") {
        $result = updateMailFormat($array);
    }

    return $result;
}

function insertMailFormat($array)
{
    global $mail_format_cols;
    $data = array ();
    foreach ($mail_format_cols as $col) {
        $data[$col] = $array[$col];
    }
    //$mfid = $data['mfid'] = $data['mfodr'] = FDB :: getNextVal('mfid');
    $data['cdate'] = date('Y-m-d H:i:s');
    $data['udate'] = date('Y-m-d H:i:s');
    //追加2012-05-14↓
    $data['mfid'] = 'NULL';

    $data = FDB :: escapeArray($data);
    FDB::begin();
    $res = FDB :: insert(T_MAIL_FORM, $data);
    $insert_id = FDB :: getLastInsertedId();
    if (is_good($insert_id)) {
        $mfid = $insert_id;
        $mfodr = $mfid;
        $res = FDB :: update(T_MAIL_FORM, array(),"mfodr = '".$mfodr."' WHERE mfid='".$mfid."'");
    }
    if (is_false($res)) {
        FDB::rollback();

        return false;
    } else {
        FDB::commit();

        return $mfid;
    }
    //追加2012-05-14↑
    //削除2012-05-14↓
    /*
    $res = FDB :: insert(T_MAIL_FORM, $data);

    if (is_false($res))
        return false;
    else
        return $mfid;*/
    //削除2012-05-14↑
}
function updateMailFormat($array)
{
    global $mail_format_cols;
    $data = array ();
    foreach ($mail_format_cols as $col) {
        if(isset($array[$col]))	$data[$col] = $array[$col];
    }

    $mfid = $data['mfid'];
    unset ($data['mfid']);
    unset ($data['cdate']);
    unset ($data['muid']);
    $data['udate'] = date('Y-m-d H:i:s');

    $data = FDB :: escapeArray($data);
    $res = FDB :: update(T_MAIL_FORM, $data, 'where mfid = '. FDB :: escape($mfid));
    if (is_false($res))
        return false;
    else
        return $mfid;
}

/**
 * Get_MailFormatで得た配列からプルダウン用の選択肢配列を作成する
 * mfid => name
 * @param array $formats フォーマットの配列
 * @param array $def 元配列（この配列に追加されていきます）keyがかぶらないように注意。
 * @param int $length フォーマット名の文字数制限
 */
function getSelectableByMailFormat ($formats, $def=array(), $length=50)
{
    $mfids = $def;
    foreach ($formats as $mail) {
        if($mail['name']=="")		continue;
        if($length < strlen($mail['name']))	$mail['name'] = mb_strimwidth($mail['name'], 0, $length,'...');
        $mfids[$mail['mfid']] = sprintf("%02d", $mail['mfid']).'　'.$mail['name'];
    }

    return $mfids;
}

/**
 * 差込可能データ取得
 */
function getOkTag()
{
    $res = array();

    $res['URL'] = 'ログインページURL';
    $res['URL_S'] = '認証省略URL';
    $res['name'] = '氏名';
    $res['uid'] = 'ログインID';
    $res['pw'] = 'パスワード';
    $res['div1'] = '所属名(大)';
    $res['div2'] = '所属名(中)';
    $res['div3'] = '所属名(小)';
    $res['memo'] = 'メモ欄';
    $res['target_name_list'] = '対象者一覧(所属大 中 小 氏名)';
    $res['_separator_'] = '----';
    $res['target_name'] = '対象者名(承認依頼用)';
    $res['body'] = '差し戻し理由(差し戻し用)';

    return $res;
}

/**
 * 確認画面で差込文字をハイライトさせる
 * 正常置換できなかった%は警告色にする
 * @param $value メール本文
 */
function replaceToHighlight ($value)
{
    $t = chr(0).chr(1).chr(2).chr(3).'`~@';//チェック済み%を、警告色に変えないように適当な文字列に置換する
    foreach (getOkTag () as $k=> $v) {
        if($k == "_separator_") continue;

        $value = str_replace('%%%%'.$k.'%%%%', '<span class="replace_ok">'.$t.$k.$t.'</span>', $value);
    }
    $value = str_replace('%', '<span class="replace_ng">%</span>', $value);//正常置換できなかった%を警告色に
    return str_replace($t, '%%%%', $value);//適当な文字列から %%%%に戻す
}
