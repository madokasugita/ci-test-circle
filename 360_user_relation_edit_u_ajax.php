<?php

/**
 * PGNAME:ユーザ回答者関連付け
 * DATE  :2008/11/10
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

/** path */
define('DIR_ROOT', '');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseFunction.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFUser.php');
session_start();
checkAuthUsr360();
/****************************************************************************************************/
define('PHP_SELF', getPHP_SELF() . '?' . getSID());
/****************************************************************************************************/
function main()
{
    global $return_url;
    $SID = getSID();
    $serial_no = $_REQUEST['serial_no'];
    $hash = getHash360($serial_no);
    $return_url = "360_user_relation_view_u.php?{$SID}&serial_no={$serial_no}&hash={$hash}";
    if ($hash != $_REQUEST['hash']) {
        print "invalid hash!";
        exit;
    }
    $target_user = FDB :: select1(T_USER_MST, '*', 'where serial_no = ' . FDB :: escape($serial_no) . ' and ' . getDivWhere());
    if (!$target_user) {
        print "error ユーザが見つかりません";
        exit;
    }
    define('TARGET_USR_UID', $target_user['uid']);

    if ($_REQUEST['mode'] == 'edit_2') {
        print relationEdit($target_user);
        exit;
//		$_POST = $_REQUEST = $_SESSION['__FILE__']['post'];
    }
    print false;
    exit;
}
/****************************************************************************************************/

function relationEdit($user)
{
    if (getHash360($_POST['target_uid']) != $_POST['target_uid_hash']) {
        print "invalid hash";
        exit;
    }
    $type = $_POST['relation'];
    $where = 'where user_type in ('.implode(',', range(1,INPUTER_COUNT)).') and uid_a = ' . FDB :: escape($user['uid']) . ' and uid_b = ' . FDB :: escape($_POST['target_uid']);
    $relation = FDB :: select1(T_USER_RELATION, 'user_type', $where);
    if ($relation) {
            $where2 = 'where answer_state in(10,0) and target = (select serial_no from usr where uid = '.FDB::escape($user['uid']).') and serial_no = (select serial_no from usr where uid = '.FDB::escape($_POST['target_uid']).')';
            $result = FDB::select(T_EVENT_DATA,'answer_state',$where2);
            if($result && $result[0]['answer_state']==0)

                return "既に回答が完了しているため選定内容を変更できませんでした。";
            if($result && $result[0]['answer_state']==10)

                return "既に回答を始めているため選定状況を変更できませんでした。";
    }

    if ($type) {
        $data = array ();
        $data['user_type'] = (int) $type;
        $data['uid_a'] = FDB :: escape($user['uid']);
        $data['uid_b'] = FDB :: escape($_POST['target_uid']);
        if ($relation) {
            $res = FDB :: update(T_USER_RELATION, $data, $where);
        } else {
            $res = FDB :: insert(T_USER_RELATION, $data, $where);
        }
        if(is_false($res))

            return false;
        return $data['user_type'];
    } else {
        $res = FDB :: delete(T_USER_RELATION, $where);
        if(is_false($res))

            return false;
        return 0;
    }

    return false;
}
/****************************************************************************************************/
main();
