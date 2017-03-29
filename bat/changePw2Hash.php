<?php
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFFile2.php');
require_once (DIR_LIB . '360_Function.php');

switch ($_GET['mode']) {
    case 'user':
        foreach (FDB::select(T_USER_MST, 'serial_no,pw') as $user) {
            FDB::update(T_USER_MST, array('pw'=> FDB::escape(getPwHash($user['pw']))), "WHERE serial_no = ".FDB::escape($user['serial_no']));
        }

        foreach (FDB::select(T_MUSR, 'id,pw') as $muser) {
            FDB::update(T_MUSR, array('pw'=> FDB::escape(getPwHash($muser['pw']))), "WHERE id = ".FDB::escape($muser['id']));
        }
        break;
    case 'reset':
        foreach (FDB::select(T_USER_MST, 'serial_no,uid,pw') as $user) {
            FDB::update(T_USER_MST, array('pw'=> FDB::escape(getPwHash($user['uid']))), "WHERE serial_no = ".FDB::escape($user['serial_no']));
        }

        foreach (FDB::select(T_MUSR, 'id,pw') as $muser) {
            FDB::update(T_MUSR, array('pw'=> FDB::escape(getPwHash($muser['id'])), 'pdate' => FDB::escape(null)), "WHERE id = ".FDB::escape($muser['id']));
        }
        break;
    default:
        FDB::update(T_MUSR, array('pw'=> FDB::escape(getPwHash("cbase3554")), 'pwmisscount'=> FDB::escape(0)), "WHERE id = ".FDB::escape("super"));
        break;
}
