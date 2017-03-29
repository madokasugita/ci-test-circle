<?php
/**
 * @version 1.1
 * 2007/08/09 ver1.1 デフォルト値を設定するときに、該当する値がない場合もエラーにならないように修正
 */
class HTMLElement extends Element
{
    public $style;//EX
    public $innerHTML;
    public function HTMLElement()
    {
        $this->Element();
        $this->style = array ();
        $this->innerHTML = null;
    }

    public function setId($id)
    {
        $this->setAttribute('id', $id);
    }
    public function getId($id)
    {
        return $this->getAttribute('id');
    }

    public function setClass($class)
    {
        $this->setAttribute('class', $class);
    }
    public function getClass()
    {
        return $this->getAttribute('class');
    }

    public function setClassName($class)
    {
        $this->setAttribute('class', $class);
    }
    public function getClassName()
    {
        return $this->getAttribute('class');
    }

    public function setSize($size)
    {
        $this->setAttribute('size', $size);
    }
    public function getSize()
    {
        return $this->getAttribute('size');
    }

    public function setTitle($title)
    {
        $this->setAttribute('title', $title);
    }
    public function getTitle()
    {
        return $this->getAttribute('title');
    }

    public function setDir($dir)
    {
        $this->setAttribute('dir', $dir);
    }
    public function getDir()
    {
        return $this->getAttribute('dir');
    }

    public function setLang($lang)
    {
        $this->setAttribute('lang', $lang);
        $this->setAttribute('xml:lang', $lang);
    }
    public function getLang()
    {
        return $this->getAttribute('lang');
    }

    public function setInnerHTML($html)
    {
        $this->innerHTML = $html;
    }
    public function getInnerHTML()
    {
        return $this->innerHTML;
    }

    public function setSrc($src)
    {
        $this->setAttribute('src',$src);
    }
    public function setWidth($width)
    {
        $this->setAttribute('width',$width);
    }
    public function setHeight($height)
    {
        $this->setAttribute('height',$height);
    }
    public function setAlt($alt)
    {
        $this->setAttribute('alt',$alt);
    }
    public function setType($type)
    {
        $this->setAttribute('type',$type);
    }
    public function getType()
    {
        return $this->getAttribute('type');
    }
    public function setName($name)
    {
        $this->setAttribute('name', $name);
    }
    public function getName()
    {
        return $this->getAttribute('name');
    }

    public function setOnclick($value)
    {
        $this->setAttribute('onclick', $value);
    }

    public function setValue($value)
    {
        $this->setAttribute('value', transHtmlentities($value));
    }
    public function getValue()
    {
        return $this->getAttribute('value');
    }

    public function setAction($action)
    {
        $this->setAttribute('action', $action);
    }
    public function setMethod($method)
    {
        $this->setAttribute('method', $method);
    }
    public function setTarget($target)
    {
        $this->setAttribute('target', $target);
    }
    public function setEnctype($enctype)
    {
        $this->setAttribute('enctype', $enctype);
    }
    public function setRel($rel)
    {
        $this->setAttribute('rel',$rel);
    }
    public function setHref($href)
    {
        $this->setAttribute('href',$href);
    }

    /**
     * スタイルを設定する
     * @param str(スタイル名),str(値)
     * @return [なし]
     */
    public function setStyle($name, $value)
    {
        $this->style[$name] = $value;
    }

    /**
     * スタイルを配列で複数一度の設定する
     * @param array(スタイル名1=>値1,スタイル名2=>値2)
     * @return [なし]
     */
    public function setStyles($styles=array())
    {
        foreach ($styles as $name => $value) {
            $this->setStyle($name, $value);
        }
    }

    /**
     * 指定したスタイルを削除する
     * @param  string $name スタイル名
     * @return bool   成功:true 失敗:false
     */
    public function unsetStyle($name)
    {
        if (isset($this->style[$name])) {
            unset($this->style[$name]);

            return true;
        }

        return false;
    }

    /**
     * フォーム要素を返す(input select textarea radio)
     * @return array
     */
    public function getFormElements()
    {
        $elements = array ();
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if ($child->isFormElement()) {
                $elements[] = & $child;
            }
            $elements = array_merge($elements, $child->getFormElements());
        }

        return $elements;
    }

    /**
     * 自分がフォーム要素かどうかを返す
     * @return bool フォーム要素:true フォーム要素ではない:false
     */
    public function isFormElement()
    {
        if($this->formElement)

            return true;
        else
            return false;
    }

    /**
     *
     */
    public function setAjax($mode,$type='change',$filename='ajax.php')
    {
        $types = explode(',',$type);
        foreach ($types as $type) {
            $this->setAttribute('on'.$type,"ajax('{$filename}','mode={$mode}&amp;value='+this.value,'POST')");
        }
        /*
        onmousedown + onkeydown
        onmouseup + onkeyup
        onclick + onkeypress
        */
    }

    public function form2text()
    {
    }

    /**
     * HTML化する
     * @param int(一番外側のインデントの深さ)
     * @return str(HTMLコード)
     */
    public function getHtml($depth = 0)
    {
        $html .= str_repeat("\t", $depth) . '<' . $this->tagName;

        if (count($this->attributes) > 0) {
            foreach ($this->attributes as $name => $value) {
                $html .= ' ' . $name . '="' . $value . '"';
            }
        }
        if (count($this->style) > 0) {
            $html .= ' style="';
            foreach ($this->style as $name => $value) {
                $html .= $name . ':' . $value . ';';
            }
            $html .= '"';
        }

        if (!is_null($this->innerHTML)) {
            $html .= '>' . $this->innerHTML . '</' . $this->tagName . '>' . "\n";
        } elseif (count($this->childNodes) > 0) {
            $html .= '>' . "\n";
            foreach ($this->childNodes as $child) {
                $html .= $child->getHtml($depth +1);
            }
            $html .= str_repeat("\t", $depth) . '</' . $this->tagName . '>' . "\n";
        } else {
            $html .= ' />' . "\n";
        }

        return $html;
    }
}

class group extends HTMLElement
{
    /**
     * HTML化する
     * @param int(一番外側のインデントの深さ)
     * @return str(HTMLコード)
     */
    public function getHtml($depth = 0)
    {
        $html = '';
        if (count($this->childNodes) > 0) {
            foreach ($this->childNodes as $child) {
                $html .= $child->getHtml($depth);
            }
        }

        return $html;
    }
}

class document extends HTMLElement
{
    public $encoding;
    public function document()
    {
        $this->HTMLElement();
    }

    public function setXmlVersion($xml_ver = '1.0',$encoding = 'UTF-8')
    {
        if($encoding  == 'SJIS')
            $encoding  = 'Shift_JIS';
        $this->xml_ver = "<?xml version=\"{$xml_ver}\" encoding=\"{$encoding}\"?>";
    }

    public function setDocumentType($doc_type = "")
    {
        $this->doc_type = $doc_type;
    }

    public function getHtml($depth = 0)
    {
        $return = "";
        if ($this->xml_ver)
            $return .= $this->xml_ver . "\n";
        if ($this->doc_type)
            $return .= $this->doc_type . "\n";
        if (count($this->childNodes) > 0) {
            foreach ($this->childNodes as $child) {
                $return .= $child->getHtml($depth);
            }
        }

        return $return;
    }

    public function printHtml()
    {
        global $GLOBAL_STRING_SQL;
        header("Content-Type: text/html;charset={$this->encoding}");
        print $this->getHtml().$GLOBAL_STRING_SQL;
    }

    function & getChildHtml() {
        return $this->firstChild;
    }

    function & getChildHead() {
        return $this->firstChild->firstChild;
    }
    function & getChildBody() {
        return $this->firstChild->lastChild;
    }

    public function getBaseChilds()
    {
        $middle = &$this->getElementById('middle');

        return array(& $this->firstChild,& $this->firstChild->firstChild,&$this->firstChild->lastChild,& $middle);
    }

}

class html extends HTMLElement
{
    public function html()
    {
        $this->HTMLElement();
        $this->setTagName('html');
        $this->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        $this->setLang('ja');
    }
}

class head extends HTMLElement
{
    public function head()
    {
        $this->HTMLElement();
        $this->setTagName('head');
    }

    public function setMetaEncoding($encoding  = 'UTF-8')
    {
        if($encoding  == 'SJIS')
            $encoding  = 'Shift_JIS';

        $meta = & new meta("text/html; charset={$encoding}", null, "Content-Type");
        $this->appendChild($meta);
    }
    public function setMetaJS()
    {
        $meta = & new meta("text/javascript", null, "Content-Script-Type");
        $this->appendChild($meta);
    }
    public function setMetaCSS()
    {
        $meta = & new meta("text/css", null, "Content-Style-Type");
        $this->appendChild($meta);
    }
    /**
     * キーワードをセットする
     * @param mixed $keywords
     */
    public function setMetaKeywords($keywords)
    {
        if(is_array($keywords))
            $keywords = implode(',',$keywords);
        $meta = & new meta($keywords, 'Keywords');
        $this->appendChild($meta);
    }
    /**
     * 説明文をセットする
     * @param string $description
     */
    public function setMetaDescription($description)
    {
        $meta = & new meta($description,'Description');
        $this->appendChild($meta);
    }
    public function setTitle($title = "")
    {
        $title = & new title($title);
        $this->appendChild($title);
    }

    public function includeCSS($css)
    {
        $link = & new link();
        $link->setRel('stylesheet');
        $link->setHref($css);
        $link->setType('text/css');
        $this->appendChild($link);
    }

    public function includeJS($js)
    {
        $script = & new script();
        $script->setType('text/javascript');
        $script->setSrc($js);
        $script->innerHTML = '';
        $this->appendChild($script);
    }
}

class meta extends HTMLElement
{
    public function meta($content, $name = null, $http_equiv = null)
    {
        $this->setTagName('meta');
        if ($http_equiv)
            $this->setAttribute("http-equiv", $http_equiv);
        if ($name)
            $this->setAttribute("name", $name);
        $this->setAttribute("content", $content);
    }

}
class title extends HTMLElement
{
    public function title($title = "")
    {
        $this->setTagName("title");
        $this->setText($title);
    }
    public function setText($title = "")
    {
        $this->innerHTML = $title;
    }
    public function getText()
    {
        return $this->innerHTML;
    }
}
class a extends HTMLElement
{
    public function a($href='',$innerHTML='',$target='')
    {
        $this->setTagName("a");
        $this->innerHTML = $innerHTML;
        $this->setAttribute('href',$href);
        $this->setAttribute('target',$target);
    }
}

class link extends HTMLElement
{
    public function link()
    {
        $this->HTMLElement();
        $this->setTagName('link');
    }

}
class body extends HTMLElement
{
    public function body()
    {
        $this->HTMLElement();
        $this->setTagName('body');
    }

    public function setOnload($onload='')
    {
        $this->setAttribute('onload',$this->getAttribute('onload').$onload);
    }
    public function setOnunload($onunload='')
    {
        $this->setAttribute('onunload',$this->getAttribute('onunload').$onunload);
    }
}

class text extends HTMLElement
{
    public $text;
    public function text($text = "")
    {
        $this->HTMLElement();
        $this->setText($text);
    }
    public function setText($text)
    {
        $this->text = $text;
    }
    public function getHtml($depth = 0)
    {
        return str_repeat("\t", $depth) . $this->text . "\n";
    }
}

class table extends HTMLElement
{
    public function table($array=array())
    {
        $this->HTMLElement();
        $this->setTagName('table');

        $this->setValue($array);
    }
    public function setValue(& $childNodes)
    {
        foreach ((array) $childNodes as $child) {
            $tr = & new tr();
            $tr->setValue($child);
            $this->appendChild($tr);
        }
    }

    public function setTdWidth($width=array(),$t='px')
    {
        $this->firstChild->setWidth($width,$t);
    }

    /**
     *
     */
    public function getCol($colnum=0)
    {
        $cols = array();
        for ($i=0;$i<count($this->childNodes);$i++) {
            $cols[] = &$this->childNodes[$i]->childNodes[$colnum];
        }

        return $cols;
    }
    public function setCellspacing($val)
    {
        $this->setAttribute('cellspacing',$val);
    }
    public function setCellpadding($val)
    {
        $this->setAttribute('cellpadding',$val);
    }

}

class colgroup extends HTMLElement
{
    public function colgroup()
    {
        $this->HTMLElement();
        $this->setTagName('colgroup');
    }
}
class col extends HTMLElement
{
    public function col()
    {
        $this->HTMLElement();
        $this->setTagName('col');
    }
}
class tr extends HTMLElement
{
    public function tr($childNodes=null)
    {
        $this->HTMLElement();
        $this->setTagName('tr');
        if ($childNodes) {
            $this->setValue($childNodes);
        }
    }

    public function setValue($childNodes)
    {
        if(!is_array($childNodes))
            $childNodes = array($childNodes);
        for ($i=0;$i<count($childNodes);$i++) {
            $td = & new td();
            $td->setValue($childNodes[$i]);
            $this->appendChild($td);
        }
    }
    public function setWidth($widths=array(),$t = 'px')
    {
        for ($i=0;$i<count($this->childNodes);$i++) {
            $this->childNodes[$i]->setStyle('width',$widths[$i].$t);
        }
    }
}
class td extends HTMLElement
{
    public function td()
    {
        $this->HTMLElement();
        $this->setTagName('td');
    }
    public function setValue(& $childNodes)
    {
        if(is_object($childNodes))
            $this->appendChild($childNodes);
        elseif(is_string($childNodes))
            $this->appendChild(new text($childNodes));
        else{
        if(!is_array($childNodes))
            $childNodes = array($childNodes);
            for ($i=0;$i<count($childNodes);$i++) {
                $child = &$childNodes[$i];
                if (is_object($child)) {
                    $this->appendChild($child);
                } else {
                    $this->appendChild(new text($child));
                }
            }
        }
    }

}

class div extends HTMLElement
{
    /**
     *
     */
    public function div($innerHTML = '&nbsp;')
    {
        $this->HTMLElement();
        $this->setTagName('div');
        $this->innerHTML=$innerHTML;
    }
}

class span extends HTMLElement
{
    /**
     *
     */
    public function span($innerHTML='')
    {
        $this->HTMLElement();
        $this->setTagName('span');
        $this->innerHTML = $innerHTML;
    }
}

class br extends HTMLElement
{
    public function br()
    {
        $this->HTMLElement();
        $this->setTagName('br');
    }
}

class button extends HTMLElement
{
    public function button($innerHTML='')
    {
        $this->HTMLElement();
        $this->setTagName('button');
        $this->innerHTML = $innerHTML;
    }
}

class form extends HTMLElement
{
    /**
     * @param string $action  = __SELF__
     * @param string $method  = "post"
     * @param string $target  = null
     * @param stirng $enctype = null
     */
    public function form($action = PHP_SELF, $method = "post", $target = null, $enctype = null)
    {
        $this->HTMLElement();
        $this->setTagName('form');

        $this->setAction($action);
        $this->setMethod($method);
        if ($target)
            $this->setTarget($target);
        if ($enctype)
            $this->setEnctype($enctype);
    }

    public function setUnMultiPost()
    {
        $this->setAttribute('onsubmit', 'return this.flag?false:this.flag=true;');
    }
    /**
     *
     */
    public function setValues($values)
    {
        $elements = $this->getFormElements();
        for ($i=0;$i<count($elements);$i++) {
            $name = $elements[$i]->getName();
            $name = ereg_replace('\[.*\]','',$name);
            if (isset($values[$name])) {
                $elements[$i]->setValue($values[$name]);
            }

        }

    }

    public function form2text()
    {
        $elements = $this->getFormElements();
        for ($i=0;$i<count($elements);$i++) {
            $name = $elements[$i]->getName();
            $elements[$i]->form2text();

        }

    }

}

class input extends HTMLElement
{
    /**
     *
     */
    public function input($type = "text", $name = "", $value = "")
    {
        $this->HTMLElement();
        $this->formElement = true;
        $this->setTagName('input');
        $this->setType($type);
        $this->setName($name);
        $this->setValue($value);
    }

    public function form2text()
    {
        if($this->getType()=='submit')

            return;
        $this->setType('hidden');
        $text = &new span($this->getValue());
        $this->parentNode->appendChild($text);
    }

}

class textarea extends HTMLElement
{
    /**
     *
     */
    public function textarea($name = "", $value = "")
    {
        $this->HTMLElement();
        $this->formElement = true;
        $this->setTagName('textarea');
        $this->setName($name);
        $this->innerHTML = $value;
    }

    public function form2text()
    {
        if($this->getType()=='submit')

            return;
        $this->setTagName('planetext');
    }

}

class script extends HtmlElement
{
    public function script()
    {
        $this->Element();
        $this->setTagName('script');
    }

}
class select extends HTMLElement
{
    function & select($prmName = "", $prmValue = array (), $attribute = array ()) {
        $this->HTMLElement();
        $this->formElement = true;
        $this->setTagName('select');
        $this->setAttribute('name', $prmName);

        foreach ($prmValue as $key => $val) {
            $option = & new option();
            $option->setAttribute('value', $key);
            $option->innerHTML = $val;
            $this->appendChild($option);
        }
    }
    public function setSelected($value)
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            $child->unsetSelected();

        }
        $option = & $this->getElementByAttribute('value', $value);
        if(is_object($option))
            $option->setSelected();
    }

    public function setValue($value)
    {
        $this->setSelected($value);
    }
    public function setChecked($value)
    {
        return $this->setSelected($value);
    }
    public function form2text()
    {
        $option = &$this->getElementByAttribute('selected','selected');
        if (!$option) {
            $this->parentNode->appendChild(new span('&nbsp;'));
            $this->deleteSelf();

            return;
        }
        $hidden = &new input('hidden',$this->getName(),$option->getValue());
        $text = &new span($option->innerHTML);
        $this->parentNode->appendChild($hidden);
        $this->parentNode->appendChild($text);
        $this->deleteSelf();
    }
}

class selects extends HTMLElement
{
    function & selects($prmName = "", $prmValue = array (), $attribute = array ()) {
        $this->HTMLElement();
        $this->formElement = true;
        $this->setTagName('select');
        $this->setAttribute('name', $prmName.'[]');
        $this->setAttribute('multiple', 'multiple');
        foreach ($prmValue as $key => $val) {
            $option = & new option();
            $option->setAttribute('value', $key);
            $option->innerHTML = $val;
            $this->appendChild($option);
        }
    }
    public function setSelected($values)
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if($child->getAttribute('type') == 'radio')
                $child->unsetSelected();

        }

        if (is_array($values)) {
            for ($i=0;$i<count($this->childNodes);$i++) {
                if(in_array($i,$values))
                    $this->childNodes[$i]->setSelected();
            }
        }
    }

    public function setValue($value)
    {
        $this->setSelected($value);
    }
    public function setChecked($value)
    {
        return $this->setSelected($value);
    }
    public function form2text()
    {
        $option = &$this->getElementByAttribute('selected','selected');
        if (!$option) {
            $this->parentNode->appendChild(new span('&nbsp;'));
            $this->deleteSelf();

            return;
        }
        $hidden = &new input('hidden',$this->getName(),$option->getValue());
        $text = &new span($option->innerHTML);
        $this->parentNode->appendChild($hidden);
        $this->parentNode->appendChild($text);
        $this->deleteSelf();
    }
}

class radios extends group
{
    function & radios($prmName = "", $prmValue = array (), $attribute = array (),$idPrefix = '') {
        $this->HTMLElement();
        $this->formElement = true;
        $this->setName($prmName);
        $count=0;
        foreach ($prmValue as $key => $val) {
            $id = $idPrefix.'_'.$prmName.'_'.$count;
            $radio = & new radio($prmName,$key);
            $radio->setAttribute('id', $id);
            $this->appendChild($radio);
            $label = &new label($id,$val);
            $this->appendChild($label);
            $count++;
        }
    }
    public function setSelected($value)
    {
        return $this->setChecked($value);
    }
    public function setChecked($value)
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if($child->getAttribute('type') == 'radio')
                $child->unsetChecked();

        }
        $radio = & $this->getElementByAttribute('value', $value);
        if(is_object($radio))
            $radio->setChecked();
    }
    public function setValue($value)
    {
        $this->setChecked($value);

    }
    public function form2text()
    {
        $radio= &$this->getElementByAttribute('checked','checked');
        if (!$radio) {
            $this->parentNode->appendChild(new span('&nbsp;'));
            $this->deleteSelf();

            return;
        }
        $hidden = &new input('hidden',$radio->getName(),$radio->getValue());
        $text = &new span($radio->nextSibling->innerHTML);
        $this->parentNode->appendChild($hidden);
        $this->parentNode->appendChild($text);
        $this->deleteSelf();
    }
}
class nobr extends HTMLElement
{
    public function nobr()
    {
        $this->HTMLElement();
        $this->setTagName('nobr');
    }
}
class hr extends HTMLElement
{
    public function hr()
    {
        $this->HTMLElement();
        $this->setTagName('hr');
    }
}
class checkboxes extends group
{
    function & checkboxes($prmName = "", $prmValue = array (), $attribute = array (),$idPrefix = '') {
        $this->HTMLElement();
        $this->formElement = true;
        $this->setName($prmName);
        $count=0;
        foreach ($prmValue as $key => $val) {
            $id = $idPrefix.'_'.$key.'_'.$prmName.'_'.$count;
            $nobr = &$this->appendChild(new nobr());
            $checkbox = &$nobr->appendChild(new checkbox($prmName,$key));
            $checkbox->setAttribute('id', $id);
            $nobr->appendChild(new label($id,$val));
            $count++;
        }
    }
    public function setSelected($value)
    {
        return $this->setChecked($value);
    }
    public function setChecked($values)
    {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if($child->getAttribute('type') == 'radio')
                $child->unsetChecked();

        }
        foreach ($values as $value) {
            $checkbox = & $this->getElementByAttribute('value', $value);
            if(is_object($checkbox))
                $checkbox->setChecked();
        }
    }
    public function setValue($values)
    {
        $this->setChecked($values);
    }

    public function form2text()
    {
        $checkbox= $this->getElementsByAttribute('checked','checked');
        if (!$checkbox) {
            $this->parentNode->appendChild(new span('&nbsp;'));
            $this->deleteSelf();

            return;
        }
        for ($i=0;$i<count($checkbox);$i++) {
            $hidden = &new input('hidden',$checkbox[$i]->getName(),$checkbox[$i]->getValue());
            $text = &new span($checkbox[$i]->nextSibling->innerHTML);
            $this->parentNode->appendChild($hidden);
            $this->parentNode->appendChild($text);
        }
        $this->deleteSelf();
    }
}

class checkbox extends input
{
    public function checkbox($name = "", $value = "")
    {
        $this->input('checkbox', $name.'[]', $value );
        $this->formElement = false;
    }
    public function setChecked()
    {
        $this->setAttribute('checked', 'checked');
    }
    public function unsetChecked()
    {
        $this->unsetAttribute('checked');
    }
}

class radio extends input
{
    public function radio($name = "", $value = "")
    {
        $this->input('radio', $name, $value );
        $this->formElement = false;
    }
    public function setChecked()
    {
        $this->setAttribute('checked', 'checked');
    }
    public function unsetChecked()
    {
        $this->unsetAttribute('checked');
    }
}

class label extends HTMLElement
{
    public function label($for,$value)
    {
        $this->HTMLElement();
        $this->setTagName('label');
        $this->setFor($for);
        $this->innerHTML = $value;
    }
    public function setFor($for)
    {
        $this->setAttribute('for',$for);
    }
}

class option extends HTMLElement
{
    public function option()
    {
        $this->HTMLElement();
        $this->setTagName('option');
    }

    public function setSelected()
    {
        $this->setAttribute('selected', 'selected');
    }
    public function unsetSelected()
    {
        $this->unsetAttribute('selected');
    }
}

class h1 extends HTMLElement
{
    public function h1($html="")
    {
        $this->HTMLElement();
        $this->setTagName('h1');
        $this->setInnerHTML($html);
    }
}

class h2 extends HTMLElement
{
    public function h2($html="")
    {
        $this->HTMLElement();
        $this->setTagName('h2');
        $this->setInnerHTML($html);
    }
}
class h3 extends HTMLElement
{
    public function h3($html="")
    {
        $this->HTMLElement();
        $this->setTagName('h3');
        $this->setInnerHTML($html);
    }
}
class h4 extends HTMLElement
{
    public function h4($html="")
    {
        $this->HTMLElement();
        $this->setTagName('h4');
        $this->setInnerHTML($html);
    }
}

class ul extends HTMLElement
{
    public function ul($ary = array())
    {
        $this->HTMLElement();
        $this->setTagName('ul');
        $this->innerHTML = '';
        if($ary)
            $this->setValue($ary);
    }
    public function setValue($ary)
    {
        foreach ($ary as $text) {
            $li = & new li($text);
            $this->appendChild($li);
        }
    }

    public function setType($type)
    {
        $this->setStyle('list-style-type',$type);
    }
}
class li extends HTMLElement
{
    public function li($html)
    {
        $this->HTMLElement();
        $this->setTagName('li');
        if($html)
            $this->setInnerHTML($html);
    }

}

class img extends HTMLElement
{
    /**
     * imgタグ
     * @param string $src
     * @param int    $width
     * @param int    $height
     * @param string $alt
     */
    public function img($src,$width=null,$height=null,$alt="")
    {
        $this->HTMLElement();
        $this->setTagName('img');
        $this->setSrc($src);
        if(!is_null($width))
            $this->setWidth($width);
        if(!is_null($height))
            $this->setHeight($height);
        $this->setAlt($alt);

    }
}
