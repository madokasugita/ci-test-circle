<?php
//define('DEBUG', 1);
define('DIR_ROOT', '../');
define('ENCODE_WEB_OUT'	, 'UTF-8');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
include_once (DIR_LIB . 'CbaseFForm.php');
include_once (DIR_LIB . 'CbaseFManage.php');
include_once (DIR_LIB . 'CbaseMVC.php');
include_once (DIR_LIB . 'CbaseFEnquete.php');
include_once (DIR_LIB . 'CbaseFCheckModule.php');
include_once (DIR_LIB . 'CbaseEnqueteConditions.php');
include_once (DIR_LIB . 'QuestionType.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
Check_AuthMng('set_cond.php');

$post = $_POST;
$seid =$post['seid'];
$evid = $post['evid'];
$cond5 = $post['cond5'];

if (!$seid || !$evid || !$cond5) {
    exit;
}

$se = Get_SubEnquete('id', $seid, $evid);
$se = $se[0];
$seid =  $se['seid'];

$data = Get_Enquete_Main('id', $evid, '', '', 1);//$_SESSION['muid']);

        $str = array ();

        $c = getConditionClass('cond5', $se, $cond5);
        //modeが分岐するなど特殊処理の場合はここに記述
        $str[] = $c->toStringShort($data[0]);

        echo implode('', $str);

    function getConditionClass($condname, $se, $cond)
    {
        switch ($condname) {
            case 'cond':
                return new Cond1Condition($se, $cond);
            case 'cond2':
                return new Cond2Condition($se, $cond);
            case 'cond3':
                return new Cond3Condition($se, $cond);
            case 'cond4':
                return new Cond4Condition($se, $cond);
            case 'cond5':
                return new Cond5Condition($se, $cond);
            default:
                echo '不正なcond指定です';
                exit;
        }
    }
