<?php
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . '360_Function.php');

if((is_good($_POST['mode']) && $_POST['mode'] == 'edit')
        || (is_good($_POST['data_editor_mode']['confirm']))
        || (is_good($_POST['data_editor_mode']['top']))
        || (is_good($_POST['data_editor_mode']['complete']))
        )
{
    $error_message = '';
    if ($message = getEditRelationError()) {
        $error_message = $message;
        require_once '360_user_relation_view.php';
    } else {
        require_once '360_user_respondent_edit.php';
    }
} else {
    require_once '360_user_relation_view.php';
}

function getEditRelationError()
{
    if (is_void($_REQUEST['target_serial_no']) || is_void($_POST['respondent_serial_no'])) {
        return '';
    }
    $target_serial_no     = $_REQUEST['target_serial_no'];
    $respondent_serial_no = $_POST['respondent_serial_no'];
    $where = 'where answer_state in(10,0) and target = '.FDB::escape($target_serial_no).' and serial_no = '.FDB::escape($respondent_serial_no);
    $result = FDB::select(T_EVENT_DATA,'answer_state', $where);

    if(is_good($result) && $result[0]['answer_state']==0)

        return "既に回答が完了しているため選定内容を変更できませんでした。<br/>「回答状況検索(詳細)/代理入力」にて該当する回答を「未回答」状態に戻したうえで再度変更して下さい。";
    if(is_good($result) && $result[0]['answer_state']==10)

        return "既に回答を始めているため選定状況を変更できませんでした。<br/>「回答状況検索(詳細)/代理入力」にて該当する回答を「未回答」状態に戻したうえで再度変更して下さい。";

    return '';
}
