<?php

/**
 * PGNAME:subevent編集
 * DATE  :2007/11/22
 * AUTHOR:cbase Kido
 * @version 1.0
 */
/****************************************************************************************************/

define('NOT_CONVERT',1);

/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'ResearchDesign.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

define('PHP_SELF', getPHP_SELF()."?".getSID());

if (!$_REQUEST['mode']) {
    main();
}

/****************************************************************************************************/

function main()
{
    $body .= '<h1>質問設定</h1>';
    $objHtml = & new ResearchAdminHtml('質問設定');
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.core.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.widget.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.mouse.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.sortable.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.button.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.draggable.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.position.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.resizable.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.ui.dialog.js");
    $objHtml->addFileJs(DIR_JS."jquery/jquery.upload.js");

    print $objHtml->getMainHtml($body);
}
