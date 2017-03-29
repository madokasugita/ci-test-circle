<?php

/**
 * 複数データを登録できるように改修
 * <input type="text" name="name[1]" value="1">のようにnameが連想配列に変換される
 *
 * $_POST[$key] = array(1=>$val, 2=>$val2)で受け取り、
 * walk()前に$array[No] = array($key=>$val, $key2=>$val)へ変換して内部処理しているため
 * callback系関数はそのまま使えるはず
 */
class MultiDataEditor extends DataEditor
{
    public $row_count;
    public function setRowCount($count)
    {
        $this->row_count = $count;
        $this->data->row_count = $count;
        $this->design->row_count = $count;
    }

    public $default_row_count=0;
    public function setDefaultRowCount($count)
    {
        $this->default_row_count = $count;
    }

    /**
     * postで受け取る形に変換してセットする
     */
    public function setTarget($array)
    {
        $this->data->hasTarget = true;
        $target = array();
        $target = $this->data->getEditValue($array);
        $tmptarget = array();
        foreach ($this->data->getColumns() as $col) {
            foreach (range(1, $this->row_count) as $num) {
                $tmptarget[$col][] = $target[$num][$col];
            }
        }
        $this->target = $tmptarget;
    }

    public function main($mode, $values)
    {
        if (is_false($this->data->hasTarget)) {
            foreach ($this->getPost() as $k=>$v) {
                $this->setRowCount(max($this->default_row_count, max(array_keys($v))));
                break;
            }
        }

        return parent::main($mode, $values);
    }

    /**
     * エラーメッセージの配列を一括でフォーマットする
     * @param  array $errors エラーメッセージ配列
     * @return array フォーマット後配列。キーと値の関係は保持される。
     */
    public function formatErrorMessages($errors)
    {
        $results = array ();
        foreach ($errors as $num=>$data) {
            $results[$num] = parent::formatErrorMessages($data);
        }

        return $results;
    }

    /**
     * POSTから$this->data->getColumnの値をキーにもつ値を取得する
     * @return array 元の値
     */
    public function pickMyData($data, $escape = true)
    {
        $res = array ();
        foreach ($data as $num=>$_data) {
            $res[$num] = parent::pickMyData($_data, $escape);
        }

        return $res;
    }

/*	function getAddButton()
    {
        return $this->design->getAddButton('add');
    }
*/
}

class MultiDataEditDesign extends DataEditDesign
{
    /**
     * TODO:行Noごとに受け取った値を処理したい
     */
    public function getEditView($show, $error = array ())
    {
/*		$res = '';
        $list = $this->adapter->getColumnNames();
        foreach ($show as $k => $v) {
            $res .= '<p>' . $list[$k] . ':<br>' . $v . '<br>' . $error[$k] . '</p>';
        }

        return $res;
*/	}

    //追加ボタン
    public function getAddButton()
    {
        return "";
    }
}

class MultiDataEditAdapter extends DataEditAdapter
{
    public $row_count;
    /**
     * ◆abstruct
     * 保存用のデータが送られてくる
     * @param  array $data 保存用のデータ
     * @return bool  保存に成功すればtrue
     */
    public function save($data)
    {
        FDB::begin();
        foreach ($data as $num=>$_data) {
            if (is_good($_data)) {
                $res = $this->insert($_data);
                if(is_false($res)) return false;
            }
        }
        FDB::commit();

        return true;
    }

    public $now_row_count;
    /**
     * @return array array([行No]=array(["formのname"]=formのhtml))
     */
    public function getForm($value = array ())
    {
        $this->now_row_count = 1;
        foreach (range(1, $this->row_count) as $num) {
            $data = $this->beforeWalk($value, $num);
            $return[$num] = $this->walk($data, array (
                $this,
                'getFormCallback'
            ));
            $this->now_row_count++;
        }

        return $return;
    }

    /**
     * @return array array([行No]=array(["formのname"]=エラー文字列)
     */
    public function validate($value)
    {
        $has_value = false;
        $this->now_row_count = 1;
        foreach (range(1, $this->row_count) as $num) {
            $data = $this->beforeWalk($value, $num);
            $error = $this->walk($data, array (
                $this,
                'validateCallback'
            ));
            if ($this->checkFormatValueComplete($data)) {
                $has_value = true;
            }
            if(is_good($error))
                $return[$num] = $error;
            $this->now_row_count++;
        }
        if (is_false($has_value)) {
            return $this->getValidateGlobalError();
        }

        return $return;
    }

    public function getFormatValue($value)
    {
        $this->now_row_count = 1;
        foreach (range(1, $this->row_count) as $num) {
            $data = $this->beforeWalk($value, $num);
            if($this->checkFormatValueComplete($data))
                $return[$num] = parent::getFormatValue($data);
            $this->now_row_count++;
        }

        return $return;
    }

    /*
     * エラーは通過するが、表示してよい値かどうかチェックする
     */
    public function checkFormatValueComplete($data)
    {
        return false;
    }

    public function makeHiddenValueTag($name, $data)
    {
        $name = $name."[".$this->now_row_count."]";

        return parent::makeHiddenValueTag($name, $data);
    }

    public function getSaveValue($value)
    {
        $this->now_row_count = 1;
        foreach (range(1, $this->row_count) as $num) {
            $data = $this->beforeWalk($value, $num);
            $return[$num] = $this->walk($data, array (
                $this,
                'getSaveValueCallback'
            ));
            $this->now_row_count++;
        }

        return $return;
    }

    public function getEditValue($value)
    {
        foreach ($value as $num=>$data) {
            $return[$num] = $this->walk($data, array (
            $this,
            'getEditValueCallback'
        ));
        }

        return $return;
    }

    public function beforeWalk($value, $num)
    {
        $tmpValue = array();
        foreach ($value as $col=>$data) {
            if(isset($data[$num]))
                $tmpValue[$col] = $data[$num];
        }

        return $tmpValue;
    }

    public function getValidateGlobalError()
    {
        return array("0"=>array());
    }
}
