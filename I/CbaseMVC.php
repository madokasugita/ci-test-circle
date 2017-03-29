<?php
class Model
{
    public function Model()
    {

    }
    /**
     * アンケートをメンバ変数に読み込む
     * @param int $evid
     */
    public function loadEnquete($evid)
    {
        $data = Get_Enquete_Main('id', $evid, '', '', $_SESSION['muid']);
        $this->event = $data[-1];
        $this->event['first_seid'] = $data[0][0]['seid'];
        $this->subevents = array ();
        $num = 0;
        $pre_seid = null;
        foreach ($data[0] as $subevent) {
            $num++;
            $subevent['num'] = $num;
            $subevent['pre_seid'] = $pre_seid;
            if ($pre_seid)
                $this->subevents[$pre_seid]['next_seid'] = $subevent['seid'];
            $this->subevents[$subevent['seid']] = $subevent;
            $pre_seid = $subevent['seid'];
        }
        $this->event['last_seid'] = $subevent['seid'];
        $this->event['lastnum'] = $num;
    }
    public function location($url)
    {
        header('Location: ' . $url);
        exit;
    }
}

class View
{
    public function View($template = '')
    {
        if ($template)
            $this->HTML = mb_convert_encoding(file_get_contents($template), INTERNAL_ENCODE, 'UTF-8');
    }
    public function getHtml(& $request, & $model)
    {
        //差し替え
        $this->model = &$model;
        $this->HTML = preg_replace_callback('/%%%%([a-zA-Z0-9:_]+)%%%%/', array (
            $this,
            'ReplaceHtmlCallBack'
        ), $this->HTML);
        if (DEBUG)
            return $this->HTML . $this->debugHtml();
        return $this->HTML;
    }

    public function ReplaceHTMLCallBack($match)
    {
        $keys = explode(':', $match[1]);

        $base['img_path'] = DIR_IMG;
        $base['PHP_SELF'] = PHP_SELF;

        if (count($keys) == 1)
            return $base[$keys[0]];
        return $this->model->$keys[0][$keys[1]];
    }

    public function debugHtml()
    {

        $session = var_export($_SESSION, true);
        $post = var_export($_POST, true);
        $get = var_export($_GET, true);
        $files = (count($_FILES) == 0) ? '' : var_export($_FILES, true);
        $session_size = html_escape(strlen($session));

        $session = str_replace("\n", "<br>", $session);
        $session = str_replace(",", ",<WBR>", $session);
        $post = str_replace("\n", "<br>", $post);
        $get = str_replace("\n", "<br>", $get);
        $files = str_replace("\n", "<br>", $files);

        return<<<__HTML__
<table width="850" border="1" cellpadding="0" cellspacing="0">
  <tr>
    <td width="290">\$_SESSION - <span class="alert">{$session_size} byte</span></td>
    <td width="280">\$_POST</td>
    <td width="280">\$_GET</td>
  </tr>
  <tr>
    <td valign="top" width="290">{$session}</td>
    <td valign="top">{$post}{$files}</td>
    <td valign="top">{$get}</td>
  </tr>
</table>\n
__HTML__;
    }

}

class Controller
{
    public $mode;
    public $view;
    public $request;
    public $model;

    public function Controller(& $model, & $view)
    {
        $this->mode = $this->getMode();
        $this->setModel($model);
        $this->setView($view);
        $this->request = $this->getRequest();
    }
    /**
    * POST,GETなどモードを判別
    * @param array $array  $_POST,$_GETなどを渡す
    * @return string mode
    */
    public function getMode($array = null)
    {
        if ($array === null) {
            $array = & $this->getRequest();
        }

        foreach ($array as $key => $value) {
            $key = str_replace('_x', '', $key);
            $key = str_replace('_y', '', $key);
            if (ereg("^mode:(.*)$", $key, $match)) {
                return $match[1];
            }
        }

        return $array['mode'];

    }
    /**
     * リクエスト（POST,GETなど）を取得する
     * ここをoverrideすることでDBから読み出したデータやメンバ変数など入力値を自由に設定できる
     * @return array リクエスト内容の連想配列
     */
    function & getRequest()
    {
        return $_REQUEST;
    }

    public function setModel(& $model)
    {
        $this->model = & $model;
    }
    public function setView(& $view)
    {
        $this->view = & $view;
    }
    public function show()
    {
        return $this->view->getHtml($this->request, $this->model);
    }
}
