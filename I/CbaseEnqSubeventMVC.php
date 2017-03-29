<?php
require_once 'CbaseMVC.php';
require_once 'CbaseFEnquete.php';
require_once 'CbaseFEventDesign.php';
require_once 'CbaseFCheckModule.php';
require_once 'CbaseFError.php';
require_once 'CbaseFForm.php';
require_once 'QuestionType.php';

class EnqSubeventModel extends Model
{
    public function delete(& $request)
    {
        Delete_Subevent($request['seid'], $request['evid']);

        $flag = false;
        foreach ($this->subevents as $seid => $subevent) {
            if ($request['seid'] != $seid)
                $nextid = $seid;
            if ($flag)
                break;
            if ($request['seid'] == $seid)
                $flag = true;

        }
        $request['seid'] = $nextid;

    }
    public function getMatrixArray($subevent)
    {
        $aryDefData = Get_Design("id", 0);
        $aryDefData = $aryDefData[0];
        $matrix_array = array ();
        $matrix_array['title'] = $subevent['title'];
        $matrix_array['hissu'] = $subevent['hissu'];
        $matrix_array['type1'] = $subevent['type1'];
        $matrix_array['type2'] = $subevent['type2'];
        if ($subevent['matrix'] == 5) {
            $matrix_array['html2'] = buildmatrix($aryDefData, 1, $subevent['choice']);
        } else {
            $matrix_array['html2'] = $subevent['html2'];
        }
        $matrix_array['choice'] = $subevent['choice'];
        $matrix_array['matrix'] = 6;
        $matrix_array['other'] = $subevent['other'];
        $matrix_array['width'] = $subevent['width'];
        $matrix_array['rows'] = $subevent['rows'];
        $matrix_array['word_limit'] = $subevent['word_limit'];
        $matrix_array['page'] = $subevent['page'];
        $matrix_array['chtable'] = $subevent['chtable'];
        $matrix_array['randomize'] = $subevent['randomize'];
        $matrix_array['ext'] = $subevent['ext'];

        return $matrix_array;
    }

    public function insertBefore(& $request)
    {
        //1個まえの設問がマトリックスだったときの処理
        $subevent = $this->subevents[$this->subevents[$request['seid']]['pre_seid']];
        $matrix_array = array ();
        if ($subevent['matrix'] == 5 || $subevent['matrix'] == 6) {
            $matrix_array = $this->getMatrixArray($subevent);
        }
        $new_seid = Insert_Subevent($request['evid'], $request['seid'], $matrix_array);
        $request['seid'] = $new_seid;
    }

    public function insertAfter(& $request)
    {
        //1個まえの設問がマトリックスだったときの処理
        $subevent = $this->subevents[$request['seid']];
        $matrix_array = array ();
        if ($subevent['matrix'] == 5 || $subevent['matrix'] == 6) {
            $matrix_array = $this->getMatrixArray($subevent);
        }

        //もしも最後の問題だったら別処理
        if ($this->event['last_seid'] == $request['seid']) {
            $insertdata = array (
                "seid" => "new",
                "evid" => $this->event['evid'],
                "title" => "追加",
                "type1" => 0,
                "type2" => "r",
                "choice" => "",
                "hissu" => 1,
                "width" => 50,
                "rows" => 1,
                "word_limit" => "",
                "cond" => "",
                "page" => $this->event['lastpage'],
                "other" => 0,
                "html1" => "",
                "html2" => ""
            );
            foreach ($matrix_array as $key => $val)
                $insertdata[$key] = $val;

            $new_seid = Save_SubEnquete("new", $insertdata);
            $request['seid'] = $new_seid;
        } else {
            $new_seid = Insert_Subevent($request['evid'], $this->subevents[$request['seid']]['next_seid'], $matrix_array);
            $request['seid'] = $new_seid;
        }
    }

    public function update(& $request)
    {

        //type分割(ひとつのプルダウンで二つの要素を指定している)
        list($request["type1"], $request["type2"]) = QuestionType::resolveTypeString($request["typeA"]);
        //POST["html2"]が空→タイプ別にデフォルトデザインをセット
        if ((ereg_replace(" |　", "", trim($request["html2"])) == "" || $_POST["main"] == "フォーム再作成") && FCheck :: isNumber($request["fel"])) {
            $aryDefData = Get_Design("id", 0);
            $aryDefData = $aryDefData[0];

            //html2に入れる
            if ($request["type2"] === "m") {
                switch ($request["type1"]) {
                    case "5" :
                        $request["html2"] =  buildmatrix($aryDefData, 0, $request["choice"], $request["title"]);
                        break;
                    case "6" :
                        $request["html2"] =  buildmatrix($aryDefData, 1, $request["choice"]);
                        break;
                    case "7" :
                        $request["html2"] =  buildmatrix($aryDefData, 2, $request["choice"]);
                        break;
                }
            } else {
                $request["html2"] = QuestionType::buildForm($request, $aryDefData);
            }

        } else {
            $request["html2"] = $request["html2"];
        }

        if ($request["type2"] === "m") {
            $request['matrix'] = $request["type1"];
            $request['randomize'] = '';
            list($request["type1"], $request["type2"]) = QuestionType::resolveTypeString($request["typeM"]);
        } else
            $request['matrix'] = 0;

        if ($request["type2"] === "c" || $request["type2"] === "t" || $request["type2"] === "n") {
            $request["chtable"] = "";
        }
        if (!QuestionType::isSettableHissu($request)) {
            $request["hissu"] = 0;
        }

        //typeによる、width,rows,choiceの制御
        //width
        if (QuestionType::isSettableOther($request) && $request["other"] == 0) {
            $request["width"] = 50;
            $request["rows"] = 1;
            $request["word_limit"] = 0;
        }

        //chice
        if (!QuestionType::isSettableChoice($request))
            unset ($request["choice"]);
        if (!QuestionType::isSettableOther($request))
            $request["other"] = 0;

        $request['page'] = $this->subevents[$request['seid']]['page'];

        $postError = $this->checkError($request);
        if (!$postError) {
            $request["width"] = (int) $request["width"];
            $request["rows"] = (int) $request["rows"];
            $request["word_limit"] = (int) $request["word_limit"];
            //DBへデータ挿入・更新
            if ($request['seid'] == "new") {
                $seid = Save_SubEnquete("new", $request);
                $request['seid'] = $seid;
            } else {
                Save_SubEnquete("update", $request);
            }
        } else {
            $this->error = $postError;
        }

    }
    public function checkError($request)
    {
        $result = array ();

        if ($request['fel'] && (!is_numeric($request['fel']) || $request['fel'] < 0)) {
            $result['fel'] = '<br>半角数字のみで入力して下さい。';
        } elseif (!preg_match("/^[0-9]+$/", $request['fel'])) {
            $result['fel'] = '<br>整数で入力してください。<br>';
        } elseif ($request['fel'] > count(explode(",", $request["choice"]))) {
            $result['fel'] = '<br>選択肢の数以下の値を入力して下さい。';
        }

        if ($request['width'] && (!is_numeric($request['width']) || $request['width'] < 0)) {
            $result['width'] = '半角数字のみで入力してください。<br>';
        } elseif ($request['width'] && !preg_match("/^[0-9]+$/", $request['width'])) {
            $result['width'] = '整数で入力してください。<br>';
        }

        if ($request['rows'] && (!is_numeric($request['rows']) || $request['rows'] < 0)) {
            $result['rows'] = '半角数字のみで入力してください。<br>';
        } elseif ($result['rows'] && !preg_match("/^[0-9]+$/", $request['rows'])) {
            $result['rows'] = '整数で入力してください。<br>';
        }

        if ($request['word_limit'] && (!is_numeric($request['word_limit']) || $request['word_limit'] < 0)) {
            $result['word_limit'] = '半角数字のみで入力してください。<br>';
        } elseif ($result['word_limit'] && !preg_match("/^[0-9]+$/", $request['word_limit'])) {
            $result['word_limit'] = '整数で入力してください。<br>';
        }

        if ($request['width'] > 1000) {
            $result['width'] = '値が大きすぎます。';
        }
        if ($request['rows'] > 1000) {
            $result['rows'] = '値が大きすぎます。';
        }
        if ($request['word_limit'] > 10000) {
            $result['word_limit'] = '値が大きすぎます。';
        }
        if (($request['type2'] =='r' || $request['type2'] =='c' || $request['type2'] =='p') && $request['chtable'] !== '' && count(explode(",", $request["choice"])) != count(explode(",", $request["chtable"]))) {
            $result['chtable'] = '<br>選択肢と同数入力して下さい';
        }

        if ($request['choice'] === '' && in_array($request['typeA'],array('1r','1p','2c','5m','6m','7m'))) {
            $result['choice'] = '<br>選択肢が空欄です。';
        }

        $ras = randomArraySort(array (), $request["randomize"]);
        if (FError :: is($ras)) {
            $result["randomize"] = '<br>書式が不正です。';
            $this->data["randomize_style"] = 'display:block;';
        }

        if ($result)
            $result["error"] = "入力内容にエラーがあります<br>";

        return $result;
    }
}

class EnqSubeventView extends View
{
    public function EnqSubeventView()
    {
        $this->View('./enq_subevent_template.html');
    }
    public function getHtml(& $request, & $model)
    {
        $this->model = & $model;
        if (!$request['seid']) {
            $request['seid'] = $this->model->event['first_seid'];
        }
        if (!$request['seid']) {
            $request['seid'] = 'new';
        }
        if ($this->model->event['first_seid'] && $request['seid'] === 'new') {
            $request['seid'] = $this->model->event['first_seid'];
        }

        if ($request['seid'] == 'new') {

            $this->model->subevent = array (
                "seid" => "new",
                "evid" => $this->model->event['evid'],
                "title" => "新規質問",
                "type1" => 0,
                "type2" => "r",
                "choice" => "",
                "hissu" => 1,
                "width" => 50,
                "rows" => 1,
                "word_limit" => "",
                "cond" => "",
                "page" => $this->model->event['lastpage'],
                "other" => 0,
                "html1" => "",
                "html2" => "",
                "num" => 0
            );
        } else {
            $this->model->subevent = $this->model->subevents[$request['seid']];
        }

        if ($model->error) {
            foreach ($request as $key => $val) {
                $this->model->subevent[$key] = $val;
            }
        }
        if (!$this->model->subevent['fel'])
            $this->model->subevent['fel'] = 1;
        $this->setControllSeidPage(); //次の設問、ページなどのボタンを差し替えるためのデータを用意

        $this->model->errors = $model->error;
        //差し替え
        $this->HTML = preg_replace_callback('/%%%%([a-zA-Z0-9:_]+)%%%%/', array (
            $this,
            'ReplaceHtmlCallBack'
        ), $this->HTML);

        if (DEBUG)
            return $this->HTML . $this->debugHtml();
        return $this->HTML;
    }
    /**
     * 次の設問、ページなどのボタンを差し替えるためのデータを用意
     */
    public function setControllSeidPage()
    {
        if ($this->model->subevent['page'] == $this->model->event['lastpage']) {
            $this->model->data['next_page_hidden'] = ' style=visibility:hidden';
        } else {
            foreach ($this->model->subevents as $tmp) {
                if ($this->model->subevent['page'] + 1 == $tmp['page']) {
                    $seid = $tmp['seid'];
                    break;
                }

            }
            $this->model->data['next_page_seid'] = $seid;
        }

        if ($this->model->subevent['page'] == 1) {
            $this->model->data['pre_page_hidden'] = ' style=visibility:hidden';

        } else {
            foreach ($this->model->subevents as $tmp) {
                if ($this->model->subevent['page'] - 1 == $tmp['page']) {
                    $seid = $tmp['seid'];
                    break;
                }
            }
            $this->model->data['pre_page_seid'] = $seid;
        }

        if ($this->model->subevent['num'] == $this->model->event['lastnum']) {
            $this->model->data['next_seid_hidden'] = ' style=visibility:hidden';
        } else {
            $this->model->data['next_seid'] = $this->model->subevents[$this->model->subevent['seid']]['next_seid'];
        }

        if ($this->model->subevent['num'] == 1) {
            $this->model->data['pre_seid_hidden'] = ' style=visibility:hidden';
        } else {
            $this->model->data['pre_seid'] = $this->model->subevents[$this->model->subevent['seid']]['pre_seid'];
        }

        $this->model->data['preview'] = DIR_ROOT . PG_PREVIEW . '?rid=' . Create_QueryString('preview0', $this->model->event['rid']) . '&page=' . $this->model->subevent['page'];

    }
    /**
    * subevent編集画面テンプレート内の %%%%hoge%%%%を適切なものに置き換えます。
    */
    public function ReplaceHTMLCallBack($match)
    {
        $keys = explode(':',$match[1]);

        $key = $match[1];
        if ($key == 'img_path') {
            return DIR_IMG;
        }

        if ($key == 'PHP_SELF') {
            return PHP_SELF;
        }

        if ($key == 'event_name') {
            return replaceMessage($this->model->event['evid'].'. '.$this->model->event['name']);
        }

        if ($key == 'money_mark') {
            return getMoneyMark();
        }
        if ($key == 'OPTION_ENQ_RANDOMIZE') {
            if(OPTION_ENQ_RANDOMIZE!=1)

                return " disabled";
            return "";
        }
        if ($key == 'SUBEVENT_LIST') {
            return $this->getHtmlSubeventList();
        }

        if ($key == 'replace_word') {
            $replace_word = array('', '%%%%form%%%%', '%%%%targets%%%%', '%%%%message%%%%', '%%%%title%%%%', '%%%%num_ext%%%%', '####category1####', '####category2####');

            return FForm::option2($replace_word);
        }

        if ($key == 'subevent_typeA') {
            $type = ($this->model->subevent['matrix'])?
                $this->model->subevent['matrix'] . 'm':
                QuestionType::createTypeString(
                    $this->model->subevent['type1'],
                    $this->model->subevent['type2']);

            return $this->getOptionTag($this->getTypeAItem(), $type);
        }
        if ($key == 'subevent_typeM') {
            $type = QuestionType::createTypeString(
                $this->model->subevent['type1'],
                $this->model->subevent['type2']);

            return $this->getOptionTag($this->getTypeMItem(), $type);
        }

        if (ereg('([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)', $key, $match2)) {
            $array_name = $match2[1];
            $key = $match2[2];
        } else {
            $key = $match[1];
        }

        if ($array_name == 'subevent_hissu') {
            if ($this->model->subevent['hissu'] == $key) {
                $val = ' checked';
            } else {
                $val = '';
            }
        } elseif ($array_name == 'subevent_other') {
            if ($this->model->subevent['other'] == $key) {
                $val = ' checked';
            } else {
                $val = '';
            }
        } elseif ($array_name == 'error') {
            return '<span style="color:red;font-size:10px">' . $this->model->errors[$key] . '</span>';
        } else {
            $val = $this->model->{$keys[0]}[$keys[1]];
        }

        return  transHtmlentities($val);
    }

    public function getTypeAItem()
    {
        $list = QuestionType::getTypes();
        $list['5m'] = 'マトリクス開始';
        $list['6m'] = 'マトリクス内部';
        $list['7m'] = 'マトリクス終了';

        return $list;
    }

    public function getTypeMItem()
    {
        $list = QuestionType::getTypes();

        return array(
            '1r' => $list['1r'],
            '2c' => $list['2c']
        );
    }

    public function getOptionTag($item, $type)
    {
        $html = array();
        foreach ($item as $k => $v) {
            $check = ($type == $k)? ' selected': '';
            $sk = transHtmlentities($k);
            $sv = transHtmlentities($v);
            $html[] = <<<__HTML__
            <option value="{$sk}"{$check}>{$sv}</option>
__HTML__;
        }

        return implode("\n", $html);
    }

    public function getHtmlSubeventList()
    {

        $html = '';
        $prev_page = 1;
        foreach ($this->model->subevents as $subevent) {
            $class = array();
            $href = PHP_SELF . '&seid=' . $subevent['seid'] . '&evid=' . $subevent['evid'];
            $title = strip_tags($subevent['title']);
            if ($title === '') {
                $title = '[空欄]';
            }

            if($this->model->subevent['seid'] == $subevent['seid'])
                $class[] = 'selected';

            if($subevent['page'] != $prev_page)
                $class[] = 'page_break';

            $class = (count($class)>0)? ' class="'.implode(' ', $class).'"':'';
            $html .=<<<HTML
<li{$class}><a href="{$href}" onclick="return this.flag?false:this.flag=true;">{$title}</a></li>

HTML;

            $prev_page = $subevent['page'];
        }

        return $html;

    }
}

class EnqSubeventController extends Controller
{

    public function EnqSubeventController(& $model, & $view)
    {
        $this->Controller($model, $view);
        $this->model->loadEnquete($this->request['evid']);
        switch ($this->mode) {
            case 'insertBefore' :
                $this->model->insertBefore($this->request);
                $this->model->loadEnquete($this->request['evid']);
                break;
            case 'insertAfter' :
                $this->model->insertAfter($this->request);
                $this->model->loadEnquete($this->request['evid']);
                break;
            case 'delete' :
                $this->model->delete($this->request);
                $this->model->loadEnquete($this->request['evid']);
                break;
            case 'update' :
                $this->model->update($this->request);
                if (is_good($this->request['insertAfter']) && is_null($this->model->error)) {
                    $this->model->insertAfter($this->request);
                }
                $this->model->loadEnquete($this->request['evid']);
                break;

            default :
                ;
        }
    }
}

/***************************************************************/
