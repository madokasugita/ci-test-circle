<?php

require_once 'CbaseFgetcsv.php';

function tmplCheckId($tmpl_id)
{
    if (preg_match("/^[0-9a-zA-Z]+$/", $tmpl_id)!=1) {
        error_exit('ERROR 4.0.0');
        exit;
    }
}

function tmplGetSettings()
{
    if (!$GLOBALS['tmplGetSetting']) {
        tmplGetSetting();
    }

    return $GLOBALS['tmplGetSetting'];
}

function tmplSaveEvent($data, $evid)
{
    if(is_void($data) || !is_array($data))

        return false;
    foreach ($data as $key => $value) {
        if(is_good($value))
            continue;
        $data[$key] = null;
    }

    return (!is_false(FDB::setData("update", T_EVENT, $data, "WHERE evid=".FDB::escape($evid))));
}

function tmplSave($key, $data, $tmpl_id="")
{
    if(is_void($tmpl_id))
        $tmpl_id = $_SESSION['tmpl_id'];

    if (preg_match("/^[0-9a-zA-Z]+$/", $tmpl_id)!=1) {
        echo "ERROR: ".basename(__FILE__)." ".__LINE__;
        exit;
    }
    if (is_array($data)) {
        foreach ($data as $key2 => $value2) {
            $data[$key2] = rtrim(str_replace("\r", "\n", str_replace("\r\n", "\n", $value2)));
        }
    } else {
        $data = rtrim(str_replace("\r", "\n", str_replace("\r\n", "\n", $data)));
    }

    $filename = DIR_TMPL."template_{$tmpl_id}.cdat";
    $tmpl = tmplGet($tmpl_id);
    $tmpl[$key] = $data;

    return s_write($filename, serialize($tmpl));
}

function tmplGet($tmpl_id="", $key="")
{
    if(preg_match("/^[0-9a-zA-Z]+$/", $tmpl_id)!=1)

        return array();
    $filename = DIR_TMPL."template_{$tmpl_id}.cdat";
    if(!file_exists($filename))

        return array();
    $tmpl = unserialize(file_get_contents($filename));
    if(is_void($key))

        return $tmpl;
    return $tmpl[$key];
}

function tmplGetSetting($tmpl_id="")
{
    if (!$GLOBALS[__FUNCTION__]) {
        //テンプレートの名前を読み込む
        $fp = fopen(FILE_TMPL_SETTING, "r");
        if(is_false($fp))

            return false;
        $data = CbaseFgetcsv($fp, 1024, ",", "\"", "UTF-8");
        while (!feof($fp)) {
            $data = CbaseFgetcsv($fp, 1024, ",", "\"", "UTF-8");
            if(is_void($data))
                break;
            $GLOBALS[__FUNCTION__][$data[0]] = $data;
        }
        fclose($fp);
    }
    if(is_void($tmpl_id))

        return $GLOBALS[__FUNCTION__];
    return $GLOBALS[__FUNCTION__][$tmpl_id];
}

function tmplGetError($tmpl_id="", $event="")
{
    if(is_void($tmpl_id))
        $tmpl_id = $_SESSION['tmpl_id'];

    if (isRid($tmpl_id)) {
        if (is_void($event)) {
            if(is_object($GLOBALS['controler']) && (get_class($GLOBALS['controler'])=='EnqueteControler' || get_class($GLOBALS['controler'])=='EnqueteControlerTest'))
                $event = array('error_message' => $GLOBALS['controler']->enquete->getEvent('error_message'));
            else
                $event = FDB::select1(T_EVENT, "error_message", "WHERE rid=".FDB::escape($tmpl_id));
        }

        return unserialize($event['error_message']);
    }

    $data = tmplGet($tmpl_id, 'error');

    return $data;
}

function tmplGetError2($tmpl_id="")
{
    $file = ($tmpl_id=='default')? DIR_TMPL."error_default.cdat":DIR_TMPL."error.cdat";

    return unserialize(file_get_contents($file));
}
function tmplGetError2_($key)
{
    if (!$GLOBALS[__FUNCTION__]) {
        $GLOBALS[__FUNCTION__] = tmplGetError2();
    }

    return $GLOBALS[__FUNCTION__][$key];
}

/**
 * フォーム設定で使う項目のうち、DBに保存するものを返す
 * @return array DB列名=>編集画面上列名の配列
 */
function tmplGetFormDbColumns()
{
    return array(
        'htmls' => 'button_submit'
        ,'htmls2' => 'button_next'
        ,'htmlm' => 'button_back'
        ,'htmlss' => 'button_save'
        ,'html_joint_begin' => 'joint_begin'
        ,'html_connect_begin' => 'connect_begin'
        ,'html_connect_end' => 'connect_end'
        ,'html_joint_end' => 'joint_end'
    );
}

function tmplGetForm($tmpl_id="", $event="")
{
    if(is_void($tmpl_id))
        $tmpl_id = $_SESSION['tmpl_id'];

    $data = tmplGet($tmpl_id, 'form');
    if (isRid($tmpl_id)) {
        $get_col = tmplGetFormDbColumns();
        if (is_void($event)) {
            $cols = implode(",", array_keys($get_col));
            $event = FDB::select1(T_EVENT, $cols, "WHERE rid=".FDB::escape($tmpl_id));
        }
        foreach ($get_col as $k => $v) {
            $data[$v] = $event[$k];
        }
    }

    return $data;
}

function tmplGetDesign($tmpl_id="", $event="")
{
    if(is_void($tmpl_id))
        $tmpl_id = $_SESSION['tmpl_id'];

    $data = tmplGet($tmpl_id, 'design');
    if (isRid($tmpl_id)) {
        if(is_void($event))
            $event = FDB::select1(T_EVENT, "htmlh,htmlf,htmlpb,htmlpp,default_select,hissu_mark,error_area", "WHERE rid=".FDB::escape($tmpl_id));
        $data['common_header'] = $event['htmlh'];
        $data['common_footer'] = $event['htmlf'];
        $data['bar_html'] = $event['htmlpb'];
        $data['page_html'] = $event['htmlpp'];
        $data['default_select'] = $event['default_select'];
        $data['hissu_mark'] = $event['hissu_mark'];
        $data['error_area'] = $event['error_area'];
    }

    return $data;
}

function tmplGetMypage($tmpl_id="", $key="")
{
    if($tmpl_id=='default')
        $fp = fopen(DIR_TMPL."mypage_default.cdat", "r");
    else
        $fp = fopen(DIR_TMPL."mypage.cdat", "r");
    if(is_false($fp))

        return false;
    $array = array();
    while (!feof($fp)) {
        $data = CbaseFgetcsv($fp, 1024, ",", "\"", "UTF-8");
        if(is_void($data))
            break;
        $array[$data[0]] = $data[1];
    }
    fclose($fp);
    if(is_void($key))

        return $array;
    return $array[$key];
}

function tmplGetCSS($tmpl_id="")
{
    return tmplGet($tmpl_id, 'css');
}

function tmplGetLogin($tmpl_id="")
{
    return tmplGet($tmpl_id, 'login');
}

class CbaseTmpl
{
    public function __construct($fh="")
    {
        if(is_file($fh))
            $this->setFile($fh);
        else
            $this->setHtml($fh);
        $this->data = array();
        $this->data['SID'] = getSID();
    }
    public function setHtml($html)
    {
        $this->html = $html;
    }
    public function setFile($file)
    {
        $this->html = file_get_contents($file);
    }
    public function exists($key)
    {
        return (!is_false(strpos($this->html, "%%%%{$key}%%%%")));
    }
    public function set($key, $data)
    {
        $this->assign($key, $data);
    }
    public function assign($key, $data)
    {
        $this->data[$key] = $data;
    }
    public function display($fh="")
    {
        if (is_good($fh)) {
            if(is_file($fh))
                $this->setFile($fh);
            else
                $this->setHtml($fh);
        }
        foreach ($this->data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key2 => $value2) {
                    $this->html = str_replace("%%%%{$key}:{$key2}%%%%", $value2, $this->html);
                }
            } else {
                $this->html = str_replace("%%%%{$key}%%%%", $value, $this->html);
            }
        }

        return $this->html;
    }
}

class ERROR_MESSAGE
{
    public function ERROR_MESSAGE()
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
        return (count($this->errors)!=0);
    }
    public function show($width=600)
    {
        if(!$this->isError())

            return "";

        $html = "";
        foreach ($this->errors as $error) {
            $html .= <<<__HTML__
<li class="error">{$error}</li>
__HTML__;
        }

        return <<<__HTML__
<ul class="errors">{$html}</ul>
__HTML__;
    }
}

function isRid($tmpl_id)
{
    return (strlen($tmpl_id)==8);
}

$GLOBALS['ERROR_MESSAGE'] = new ERROR_MESSAGE();
