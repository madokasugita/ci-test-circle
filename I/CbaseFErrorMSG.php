<?php
$ERROR = new C_ERROR_MESSAGE();
class C_ERROR_MESSAGE
{
    public function C_ERROR_MESSAGE()
    {
        $this->errors = array();
    }
    public function add($error)
    {
        $this->errors[] = $error;
    }
    public function addMessage($error)
    {
        $this->errors[] = $error;
    }
    public function getErrorMessages()
    {
        return $this->errors;
    }
    public function isError()
    {
        return (count($this->errors) != 0);
    }
    public function show($width=600)
    {
        if (!$this->isError()) {
            return "";
        }
        $html = "<table class=\"errors\">\n";
        foreach ($this->errors as $error) {
            $html .= "<tr><td class=\"error\">{$error}</td></tr>\n";
        }
        $html .= "</table>\n";

        return $html;
    }
}
