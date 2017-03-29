<?php
//define('DEBUG', 1);
define('DIR_ROOT', '../');
require_once(DIR_ROOT."crm_define.php");

$lib = DIR_LIB;
require_once($lib."CbaseCommon.php");
require_once($lib."CbaseFDB.php");
require_once($lib."CbaseFForm.php");

require_once($lib."CbaseDataEditor.php");
require_once($lib."CbaseSortList.php");

require_once($lib."PrimaryKeyDAO.php");
require_once($lib."DataMailReceivedDAO.php");
require_once($lib."CbaseEncoding.php");

require_once($lib."360Design.php");

encodeWebAll();
session_start();

$mail_from = $_POST['mail_from']? $_POST['mail_from']:$_GET['mail_from'];

require_once($lib."360_MailViewer.php");

class MyMailLogViewer extends MailLogViewer
{
    public function getBackButton()
    {
        return ;
    }

}

$back = D360::getBackBar ('crm_mail_received.php?op=back&'.getSID());

$view = new MyMailLogViewer($mail_from);
$html = $view->toHtml();

$html =<<<HTML
{$back}
{$html}
HTML;

$objHtml = & new MreAdminHtml("メール詳細");
print $objHtml->getMainHtml($html);
