<?php
class node
{
    public $nodeName;
    public $attributes;
    public $childNodes;
    public $firstChild;
    public $lastChild;
    public $previousSibling;
    public $nextSibling;
    public $parentNode;
    public $nodeType;

    public function Node()
    {
        $this->_id = ++ $GLOBALS['GLOBAL_CLASS_NODE_ID'];
        $this->attributes = array ();
        $this->childNodes = array ();
        $this->firstChild = null;
        $this->lastChild = null;
        $this->previousSibling = null;
        $this->nextSibling = null;
        $this->nodeName = null;
        $this->parentNode = null;
        $this->nodeType = XML_TYPE_NODE;
    }

    public function setNodeName($nodeName)
    {
        $this->nodeName = $nodeName;
    }
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function unsetAttribute($name)
    {
        unset ($this->attributes[$name]);
    }
    public function getAttribute($name)
    {
        return $this->attributes[$name];
    }

    function & appendChild(& $child) {
        if ($this->_id == $child->_id) {
            print "can't append self node!";
            exit;
        }
        $this->innerHTML = null;
        $child->parentNode = & $this;
        $this->childNodes[] = & $child;

        if ($child->nodeType == XML_TYPE_NODE) {
            $child->previousSibling = & $this->lastChild;
            if (!is_null($this->lastChild)) {
                $this->lastChild->nextSibling = & $child;
            }
            $this->firstChild = & $this->childNodes[0];
            $this->lastChild = & $child;
        }

        return $child;
    }

    /**
     * 子ノードを最初に追加する。
     * @param object node $child
     */
    public function appendFirstChild(& $child)
    {
        if ($this->hasChildNodes()) {
            return $this->insertBefore($child, $this->firstChild);
        } else {
            return $this->appendChild($child);
        }
    }

    /**
     * 子ノードをもっているかどうか
     * @return bool(子ノードあり:true 子ノードなし:false)
     */
    public function hasChildNodes()
    {
        return !is_null($this->firstChild);
    }

    /**
     * 属性をもっているかどうか
     * @return bool(属性あり:true 属性なし:false)
     */
    public function hasAttributes()
    {
        return (count($this->attributes) > 0);
    }
    /**
     * 指定したノードの直前に子要素を追加する。
     * @param object Node $newChild
     * @param object Node $refChild
     */
    function & insertBefore(& $newChild, $refChild = null) {
        if (is_null($refChild)) {
            return $this->appendChild($newChild);
        }
        $newChild->parentNode = & $this;
        $len = count($this->childNodes);
        $newList = array ();
        for ($i = 0; $i < $len; $i++) {
            $child = & $this->childNodes[$i];
            if ($child->_id == $refChild->_id) {
                if ($i == 0) {
                    $this->firstChild = & $newChild;
                    $newChild->previousSibling = null;

                } else {
                    $prevChild = & $this->childNodes[$i -1];
                    $prevChild->nextSibling = & $newChild;
                    $newChild->previousSibling = & $prevChild;

                }
                $newChild->nextSibling = & $child;
                $child->previousSibling = & $newChild;
                $newList[] = & $newChild;

            }
            $newList[] = & $child;

        }
        $this->childNodes = $newList;

        return $newChild;

    }
    /**
     * 指定したノードの直後に子要素を追加する。
     * @param object Node $newChild
     * @param object Node $refChild
     */
    function & insertAfter(& $newChild, & $refChild) {
        if ($refChild->_id == $this->lastChild->_id) {
            return $this->appendChild($newChild);
        }

        return $this->insertBefore($newChild, $refChild->nextSibling);
    }

    /**
     * 自分の深さを返す
     * @return int 深さ
     */
    public function getDepth()
    {
        if (is_null($this->parentNode)) {
            return 0;
        } else {
            return $this->parentNode->getDepth() + 1;
        }
    }

    /**
     * 子要素を削除する。
     * @param object Node 削除する子要素
     * @return mixed 削除成功時:削除した子要素オブジェクト 失敗時:false
     */
    function & removeChild(& $oldChild) {
        for ($i = 0; $i < count($this->childNodes); $i++) {
            $child = & $this->childNodes[$i];
            if ($child->_id == $oldChild->_id) {
                $prevChild = & $child->previousSibling;
                $nextChild = & $child->nextSibling;
                if (is_null($prevChild) && !is_null($nextChild)) {
                    $this->firstChild = & $nextChild;
                    $nextChild->previousSibling = null;
                } elseif (!is_null($prevChild) && !is_null($nextChild)) {
                    $prevChild->nextSibling = & $nextChild;
                    $nextChild->previousSibling = & $prevChild;
                } elseif (!is_null($prevChild) && is_null($nextChild)) {
                    $this->lastChild = & $prevChild;
                    $prevChild->nextSibling = null;
                } else {
                    $this->firstChild = null;
                    $this->lastChild = null;
                }
                //unset ($this->childNodes[$i]);
                array_splice($this->childNodes, $i, 1);

                return $oldChild;
            }
        }

        return false;
    }
    /**
     * 自身のコピーを返す。
     *
     * @param bool 指定されたノードにあるサブツリーを再帰的に複製するかどうか。
     * @return 複製されたノード
     */
    public function cloneNode($deep = false)
    {
        $clone = $this;
        $clone->_id = ++ $GLOBALS['GLOBAL_CLASS_NODE_ID'];

        return $clone;
    }
    /**
     * oldChildを newChildで置き換え、oldChildノードを返します。
     * @param  object Node $oldChild
     * @param  object Node $newChild
     * @return $oldChild
     */
    public function replaceChild($newChild, $oldChild)
    {
        //未実装
    }

    /**
     * 自ノードを削除する。
     */
    public function deleteSelf()
    {
        $this->parentNode->removeChild($this);
    }

    /**
     * 属性を一度に複数セットする。
     * @param $attibutes (array($name=>$value,$name=>$value)
     */
    public function setAttributes($attributes = array ())
    {
        foreach ($attributes as $name => $value)
            $this->setAttribute($name, $value);
    }

    public function str2node($str,$target=null,$depth=1)
    {
        if (is_null($target)) {
            $target[0] = & $this;
        }
        if (preg_match('|^<(\w+)(.*?)>(.*)|s',$str,$match)) {
            $tagName = $match[1];
            $attr = $match[2];
            $str = ltrim($match[3]);
            $attrs = perseAttribute($attr);

            $node = $target[$depth-1]->appendChild(new $tagName());
            $node->setAttributes($attrs);
            $target[$depth] = &$node;

            $depth++;

        } elseif (preg_match('|^</(\w+)>(.*)|s',$str,$match)) {
            $depth--;
            $str = ltrim($match[2]);
        } else {
            $node = $target[$depth-1]->appendChild(new text($str));
        }

        if($str)
            $this->str2node($str,$target,$depth);
    }

}
