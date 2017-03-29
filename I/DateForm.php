<?php
//TimeForm DateTimeForm ext

abstract
class SelectSet
{
    public $deftxt = '---';
    public $ext = array();
    public $fix = array();
    abstract public function getChoices ($key);
    abstract public function getMyValue ();
    abstract public function validate ($val, $mode=0);

    public $cancel = false;
    public $cancel_button = false;
    /**
     * 文言を設定すると設定キャンセルボタンが出る。falseに類する値で無効
     */
    public function setCancelButton($bool = '指定しない')
    {
        $this->cancel_button = $bool;
    }

    public function getCancelMessage()
    {
        return $this->cancel_button;
    }

    public function isCanceled()
    {
        return $this->cancel;
    }

    public function setCanceled($bool)
    {
        $this->cancel = $bool;
    }

    public function getForm($name)
    {
        $res = array();
        if ($this->cancel_button) {
            $res['cancel'] = FForm::replaceChecked(
                FForm::checkbox($name.'[cancel]', 1, $this->cancel_button),
                $this->cancel);
        }

        $ext = implode(' ', $this->ext);
        foreach ($this->getMyValue() as $k => $v) {
            if ($this->fix[$k]) {
                $res[$v] = FForm::hidden($name.'['.$v.']', $this->fix[$k]);
            } else
                $res[$v] = FForm::selectDef($name.'['.$v.']', $this->getChoices($k), $this->$k, $ext);
        }

        return $res;
    }

    /**
     * getFormの内容を簡易なhtmlにして返す
     */
    public function getSimpleForm($name)
    {
        $f = $this->getForm ($name);
        $c = '';
        if ($this->cancel_button) {
            $c = array_shift($f).'<br />';
        }

        return $c.implode('', $f);
    }

    public function setValue($val, $mode=0)
    {
        $this->clearValue();
        if ($this->cancel_button && $val['cancel']) {
            $this->cancel = true;
            //エラーチェックは取り急ぎなし
            return true;
        }
        $res = $this->getInnerValue ($val, $mode);
        $keys = $this->getMyValue();
        foreach ($keys as $k => $v) {
            $this->$k = $res[$v];
        }

        return $this->validate ($res, $mode);
    }

    //全部の値が揃っているか
    public function isSetAllValue($val)
    {
        if($this->cancel_button && $val['cancel']) return true;
        foreach ($this->getMyValue() as $k => $v) {
            if((is_null($val[$v]) || $val[$v] === "") && !$this->fix[$k]) return false;
        }

        return true;
    }

    /**
     * 入力がひとつでもあればtrueを返す
     */
    public function isSetAnyValue($val)
    {
        if($this->cancel_button && $val['cancel']) return true;
        foreach ($this->getMyValue() as $k => $v) {
            if(isset($val[$v]) && $val[$v] !== "" && !$this->fix[$k]) return true;
        }

        return false;
    }

    public function clearValue()
    {
        foreach ($this->getMyValue() as $k => $v) {
            $this->$k = null;
        }
    }

    /**
     * 引数に文字列を渡すと、その文字列をキーとして値を返せます
     * 例：getValue('a', 'b')
     * return array('a'=>2008, 'b'=>12, 'd'=>29); <getMyValueの順に割り当てられ、指定の無い箇所は規定値になる。
     *
     */
    public function getValue()
    {
        $args = func_get_args();
        $i = 0;
        $res = array();
        foreach ($this->getMyValue() as $k => $v) {
            $key = ($args[$i])? ($args[$i]): $v;
            $res[$key] = $this->$k;
            $i++;
        }

        return $res;
    }

    public function getDefaultChoices()
    {
        $res = array();
        if ($this->deftxt) {
            $res[''] = $this->deftxt;
        }

        return $res;
    }

}

class DateForm extends SelectSet
{
    public $year;
    public $month;
    public $day;

//	function setDate ($val, $mode=0)
//	{
//		return validate ($val, $mode=0);
//	}
//
    public function validate($val, $mode=0)
    {
        $res = $this->getInnerValue ($val, $mode);

        return $this->isSetAllValue($res) && checkdate($res['m'], $res['d'], $res{'y'});

    }

    //mode=1 入力が無ければ自動代入：月初
    //mode=2 入力が無ければ自動代入：月末
    public function getInnerValue($val, $mode=0)
    {
        $y = $val['y'];
        $m = $val['m'];
        $d = $val['d'];
        $res = array();

        $res['y'] = $y;
        $res['m'] = $m;
        $res['d'] = $d;
        if (0 < $mode && $y) {
            $res['y'] = $y;
            $res['m'] = $m? $m: ($d? 0: 1);
            $res['d'] = $d? $d: 1;

            if ($mode == 2 && !$d) {
                if ($m) {
                    $res['m'] += 1;
                } else {
                    $res['y'] += 1;
                    $res['m'] = 1;
                }
                $res['d'] = 0;
                $time = mktime(0, 0, 0,$res['m'], $res['d'], $res['y']);
                $res['y'] =  date('Y', $time);
                $res['m'] = date('n', $time);
                $res['d']  = date('j', $time);
            }
        }

        return $res;
    }

    public function getChoices($key)
    {
        switch ($key) {
            case 'year':
                return $this->getYears();
            case 'month':
                return $this->getDefineMonth();
            case 'day':
                return $this->getDefineDay();
            default:
                return array();
        }
    }

    public function setFromStr($str)
    {
        $delimit = strpos($str, '-')===false? '/': '-';
        $a = explode(' ', $str);
        list($y, $m, $d)= explode($delimit, $a[0]);

        return $this->setValue(array(
            'y'=>(int) $y, 'm'=>(int) $m, 'd'=>(int) $d
        ));
    }

    public function getMyValue()
    {
        return array(
            'year' => 'y',
            'month' => 'm',
            'day' => 'd',
        );
    }

    public function getTime()
    {
        return mktime(0, 0, 0,(int) $this->month, (int) $this->day, (int) $this->year);
    }

    public function format($format)
    {
        return date($format, $this->getTime());
    }

    public function getText()
    {
        return $this->format("Y年m月d日");
    }
    public function getTimeStamp()
    {
        return $this->format("Y-m-d 0:0:0");
    }

    public $syear;
    public $eyear;
    public function getYears()
    {
        $res = $this->getDefaultChoices ();
        foreach ($this->getYearRange () as $v) {
            $res[$v] = $v.'年';
        }

        return $res;
    }

    public function getYearRange()
    {
        $a = $this->syear? $this->syear: (date('Y') - 5);
        $b = $this->eyear? $this->eyear: (date('Y'));

        return range($a, $b);
    }

    public function setYearRange($syear, $eyear)
    {
        $this->syear = $syear;
        $this->eyear = $eyear;
    }

    public function getDefineMonth()
    {
        $res = $this->getDefaultChoices ();
        foreach (range(1, 12) as $v) {
            $res[$v] = $v.'月';
        }

        return $res;
    }

    public function getDefineDay()
    {
        $res = $this->getDefaultChoices ();
        foreach (range(1, 31) as $v) {
            $res[$v] = $v.'日';
        }

        return $res;
    }
}

class DateYmForm extends DateForm
{
    public $fix = array('day'=> 1);
//	function __construct ()
//	{
//		$this->day = 1;
//	}
//
//	function setValue ($val, $mode=0)
//	{
//		$this->day = 1;
//		if($val['y'] && $val['d'] && $mode == 2)
//		{
//			$this->day = date("d", strtotime(sprintf("%d-%d-01 -1 day", $val['y'], $val['m'] + 1)));
//		}
//		return parent::setValue ($val, $mode);
//	}
//
//	function validate ($val, $mode=0)
//	{
//		$res = $this->getInnerValue ($val, $mode);
//		return $this->isSetAllValue($res) && checkdate($res['m'], 1, $res{'y'});
//
//	}
//
//	function getMyValue ()
//	{
//		return array(
//			'year' => 'y',
//			'month' => 'm',
//		);
//	}
}

class TimeForm extends DateTimeForm
{
    public function getForm($name)
    {
        $ext = implode(' ', $this->ext);
        foreach ($this->getMyValue() as $k => $v) {
            if($k!='h' && $k!='i')
                continue;
            if ($this->fix[$k]) {
                $res[$v] = FForm::hidden($name.'['.$v.']', $this->fix[$k]);
            } else
                $res[$v] = FForm::selectDef($name.'['.$v.']', $this->getChoices($k), $this->$k, $ext);
        }

        return $res;
    }
}

class DateTimeForm extends DateForm
{

    public $h;
    public $i;

    public function getInnerValue($val, $mode=0)
    {
        $y = $val['y'];
        $m = $val['m'];
        $d = $val['d'];
        $h = $val['h'];
        $i = $val['i'];

        if(is_int($i))$i = ceil($i/5)*5;

        if($this->isSetAllValue(array(
            'y' => $y,
            'm' => $m,
            'd' => $d,
            'h' => $h,
            'i' => $i
        )))
        {
            $time = mktime(
                (int) $h,
                (int) $i, 0,
                (int) $m,
                (int) $d,
                (int) $y);

            $res = parent::getInnerValue(
                array('y' =>date('Y', $time), 'm'=>date('n', $time), 'd'=>date('j', $time)), $mode);

            $res['h'] = date('G', $time);
            $res['i'] = (int) date('i', $time);
        } else {
            $res = parent::getInnerValue($val, $mode);

            $res['h'] = $h;
            $res['i'] = $i == 60? 0: $i;

        }

        return $res;
    }

    public function validate($val, $mode=0)
    {

        $res = $this->getInnerValue ($val, $mode);

        return $this->isSetAllValue($res) && $this->checkDateTime($res['y'], $res['m'], $res{'d'}, $res['h'], $res['i']);

    }

    public function getChoices($key)
    {
        switch ($key) {
            case 'h':
                return $this->getDefineH();
            case 'i':
                return $this->getDefineI();
            default:
                return parent::getChoices ($key);
        }
    }

    public function checkDateTime($y, $m, $d, $h, $i)
    {
        return checkdate($m, $d, $y) && (0 <= $h && $h < 24) && (0 <= $i && $i < 60) ;
    }

//	function getForm ($name)
//	{
//		$ext = implode(' ', $this->ext);
//
//		$res = parent::getForm($name);
//		$res['h'] = FForm::selectDef($name.'[h]', $this->getDefineH(), $this->h, $ext);
//		$res['i'] = FForm::selectDef($name.'[i]', $this->getDefineI(), $this->i, $ext);
//		return $res;
//	}
//
    public function getText()
    {
        return $this->format("Y年m月d日 H時i分");
    }
    public function getTimeStamp()
    {
        return $this->format("Y-m-d H:i:0");
//		return "{$this->year}-{$this->month}-{$this->day}- {$this->h}:{$this->i}:0";
    }

    public function setFromStr($str)
    {
        $a = explode(' ', $str);
        list($y, $m, $d)= explode('-', $a[0]);
        list($h, $i, $s)= explode(':', $a[1]);

        return $this->setValue(array(
            'y'=>(int) $y, 'm'=>(int) $m, 'd'=>(int) $d, 'h'=>(int) $h, 'i'=>(int) $i
        ));
    }

//	function setValue ($val, $mode=0)
//	{
//		$this->clearValue();
//		return $this->setDateTime ($val['y'], $val['m'], $val['d'], $val['h'], $val['i'], $mode);
//	}

    public function setValueText($val)
    {
        $time = strtotime($val);

        return $this->setValue(array(
            'y'=>date('Y',$time), 'm'=>date('n',$time), 'd'=>date('j',$time), 'h'=>date('G',$time), 'i'=>date('i',$time)
        ));

    //	return $this->setDateTime (date('Y',$time), date('n',$time),date('j',$time),date('G',$time),date('i',$time),$mode);
    }

//	function getValue ($y='y', $m='m', $d='d', $h='h', $i='i')
//	{
//		return array(
//			$y => $this->year,
//			$m => $this->month,
//			$d => $this->day,
//			$h => $this->h,
//			$i => $this->i,
//		);
//	}

//	function clearValue ()
//	{
//		parent::clearValue();
//		$this->h = null;
//		$this->i = null;
//	}

    public function getMyValue()
    {
        return array(
            'year' => 'y',
            'month' => 'm',
            'day' => 'd',
            'h' => 'h',
            'i' => 'i',
        );
    }

    public function getTime()
    {
        return mktime(
            (int) $this->h,
            (int) $this->i, 0,
            (int) $this->month,
            (int) $this->day,
            (int) $this->year);
    }

    public $shour=null;
    public $ehou=null;

    public function getHourRange()
    {
        $a = isset($this->shour)? $this->shour: (FROM_HOUR);
        $b = isset($this->ehour)? $this->ehour: (TO_HOUR+1);

        return range($a, $b);
    }

    public function setHourRange($shour, $ehour)
    {
        $this->shour = max(0, min($shour, 23));
        $this->ehour = max(0, min($ehour, 23));
    }

    public function getDefineH()
    {
        $res = array('' => $this->deftxt);
        foreach ($this->getHourRange () as $v) {
            $res[$v] = $v.'時';
        }

        return $res;
    }

    public function getDefineI()
    {
        $res = array('' => $this->deftxt);
        foreach (array(0,5,10,15,20,25,30,35,40,45,50,55) as $v) {
            $res[$v] = $v.'分';
        }

        return $res;
    }

}
