<?php
class element extends Node
{
    public $tagName;
    /**
     * Elementコンストラクタ
     */
    public function Element()
    {
        $this->Node();
    }

    /**
     * タグ名をセットする。
     * @param string $tagName タグ名
     */
    public function setTagName($tagName)
    {
        $this->tagName = $tagName;
    }

    /**
     * 属性値からオブジェクトを検索する。
     * @param  string $name  属性名
     * @param  string $value 値
     * @return object Element
     */
    function & getElementByAttribute($name, $value) {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if ($child->attributes[$name] == $value)
                return $child;
            $result = & $child->getElementByAttribute($name, $value);
            if ($result !== false)
                return $result;
        }

        return false;
    }
    /**
     * 属性値からオブジェクトを複数検索する。
     * @param  string $name  属性名
     * @param  string $value 値
     * @return array  Elementのリファレンスの配列
     */
    public function getElementsByAttribute($name, $value)
    {
        $elements = array ();
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if ($value == '*' || $child->attributes[$name] == $value) {
                $elements[] = & $child;
            }
            $elements = array_merge($elements, $child->getElementsByAttribute($name, $value));
        }

        return $elements;
    }

    /**
     * タグ名からオブジェクトを検索する。
     * @param  string $tagName タグ名
     * @return object Element
     */
    function & getElementByTagName($tagName) {
        for ($i = 0; $i < count($this->childNodes); $i++) {

            $child = & $this->childNodes[$i];

            if ($child->tagName == $tagName)
                return $child;

            $result = & $child->getElementByTagName($tagName);
            if ($result !== false)
                return $result;
        }

        return false;
    }

    /**
     * 属性値からオブジェクトを複数検索する。
     * @param  string $tagName タグ名
     * @return array  Elementのリファレンスの配列
     */
    public function getElementsByTagName($tagName)
    {
        $elements = array ();
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if ($tagName == '*' || $child->tagName == $tagName) {
                $elements[] = & $child;
            }
            $elements = array_merge($elements, $child->getElementsByTagName($tagName));
        }

        return $elements;
    }

    /**
     * 全ての子オブジェクトを再帰的に検索する。
     * @return array Elementのリファレンスの配列
     */
    public function getElements()
    {
        $elements = array ();
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            $elements[] = & $child;
            $elements = array_merge($elements, $child->getElements());
        }

        return $elements;
    }

    /**
     * IDからオブジェクトを検索する。
     * @param  string $id
     * @return object Element
     */
    function & getElementById($id) {
        return $this->getElementByAttribute("id", $id);
    }
    /**
     * Nameからオブジェクトを検索する。
     * @param  string $name
     * @return object Element
     */
    public function getElementsByName($name)
    {
        return $this->getElementsByAttribute("name", $name);
    }
    /**
     * クラス名からオブジェクトを検索する。
     * @param  string $class
     * @return object Element
     */
    public function getElementsByClassName($class)
    {
        return $this->getElementsByAttribute("class", $class);
    }
}
