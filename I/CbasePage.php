<?php
require_once 'CbaseCommon.php';
class CbasePage
{

    public function __construct($pagename = '')
    {
        $this->pagename = $pagename;
    }
    public function getOperation()
    {
        foreach ($_REQUEST as $key => $val) {
            $op = explode(":", $key);
            if ($op[0] == "op")
                return $op;
        }
        if ($_REQUEST['op'])
            return $_REQUEST['op'];

        if ($_REQUEST['mode'])
            return $_REQUEST['mode'];

        return false;
    }

    public function addErrorMessage($error)
    {
        $this->error_message[] = $error;
    }
    public function getErrorMessage($width = 600)
    {
        $DIR_IMG = DIR_IMG;
        if (!count($this->error_message)) {
            return "";
        }
        $html = "<table style=\"width:{$width}px\" class=\"errors\">\n";
        foreach ($this->error_message as $error) {
            $html .= "<tr><td width=\"24\"><img src=\"{$DIR_IMG}caution.gif\"></td><td class=\"error\">{$error}</td></tr>\n";
        }
        $html .= "</table>\n";

        return $html;
    }
    public function getPhpSelf()
    {
        return getPHP_SELF() . "?" . getSID();
    }
}

class CbasePageToast extends CbasePage
{
    public function getErrorMessage($width = 600)
    {
        $DIR_IMG = DIR_IMG;
        if (!count($this->error_message)) {
            return "";
        }
        $html = "";
        foreach ($this->error_message as $error) {
            $html .= "<script>$().toastmessage('showWarningToast', '".$error."');</script>";
        }

        return $html;
    }
}
