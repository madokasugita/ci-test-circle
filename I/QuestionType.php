<?php

/**
 *
 * ・enq_subeventにてmatrix関連の設定有り（定数1r,2c使用のため変更の際はチェックのこと）
 * ・enq_subeventにてjavascript部分の設定有り。
 * 　これはどうにもならんので汎用的な設定にするなどした上逐一対処のこと
 * ・回答時のチェック条件を追加の場合はCbaseEnqueteConditionにて対応のこと
 *
 */

//原則static
class QuestionType
{
    /**
     * @return array DB格納値から内部値へ
     */
    public function getValueByDB($answer, $enquete, $reload = false)
    {
        $res = array();
        foreach ($answer as $v) {
            $seid = $v["seid"];
            $sd = $this->enquete->getBySeid($seid, $reload);
            switch ($sd['type2']) {
                //既存より。おそらく集計用などと思われるが不明のためコメントアウト
                //				case "seid":
                //					$array[$tid]["o"]=$row["other"];
                //					$array[$tid]["c"]=$row["choice"];
                case "t" :
                    $res["T_" . $seid] = $v["other"];
                    break;
                case "p" :
                    $res["P_" . $seid] = $v["choice"];
                    $res["E_" . $seid] = $v["other"];
                    break;
                case "r" :
                case "c" :
                    $res["P_" . $seid][] = $v["choice"];
                    $res["E_" . $seid] = $v["other"];
                    break;
            }
        }

        return $res;
    }

    /**
     * @return array 回答画面のPOST値から内部値へ
     */
    public function getValueByPost($questions, $post)
    {
        $answers = array();
        $unsets = array();
        $seids = array();
        $r = $post;
        foreach ($questions as $v) {
            $seid = $v['seid'];
            //フリースペースは回答が無いので除外する
            if ($v['type2'] != 'n') {
                $seids[] = $seid;
            }

            if (!is_null($r[$seid])) { //preg /E_[0-9]+/$ver
                if ($r[$seid] === 'ng') {
                    //プルダウン表示されたけど、回答しなかったとき
                    $answers['P_' . $seid] = 9998; //["P_1234"]形式
                } else {
                    $answers['P_' . $seid] = $r[$seid]; //['P_1234']形式
                }
            } elseif (!is_null($r['DM_' . $seid])) {
                $answers['P_' . $seid] = array (
                    9998
                ); //["P_1234"]形式
            } else {
                $unsets[] = 'P_' . $seid;
            }

            //テキスト回答の処理
            $tid = 'T_' . $seid;
            if (!is_null($r[$tid])) {
                //〒郵便番号形式
                switch ($v['type1']) {
                    case 5:
                        //どうせ書式エラーが出るので、表示用だけに変換してしまう
                        $r[$tid][0] = str_replace('-', '&#045;', $r[$tid][0]);
                        $r[$tid][1] = str_replace('-', '&#045;', $r[$tid][1]);
                        $answers[$tid] = $r[$tid][0].'-'.$r[$tid][1];
                        if($answers[$tid] == '-')
                            $answers[$tid]='';
                        continue;
                    case 6:
                    case 7:
                    case 8:
                        $answers[$tid] = implode('/',$r['T_' . $seid]);
                        if($answers[$tid] == '0/0' || $answers[$tid] =='0/0/0')
                            $answers[$tid]='';
                        continue;
                    default:
                        $answers[$tid] = $r[$tid]; //["T_1234"]形式
                }
                //記入欄回答
            } else {
                $unsets[] = $tid;
            }

            if (!is_null($r['E_' . $seid])) {
                $answers['E_' . $seid] = $r['E_' . $seid]; //["T_1234"]形式
                //記入欄回答
            } else {
                $unsets[] = 'E_' . $seid;
            }
        }

        return array (
            'answers' => $answers,
            'unsets' =>$unsets,
            'seids' => $seids,
        );
    }

    /**
     * @return string typeからformを作る
     */
    public function getForm($subevent, $now, $choices)
    {
//		$seid = $this->subevent["seid"];
        //個別対応
        switch ($subevent["type2"]) {
            case "t":
                switch ($subevent['type1']) {
                    case 5:
                        $form = new ZipFormMaker($subevent, $now, $choices);
                        break;
                    case 6:
                        $form = new Date1FormMaker($subevent, $now, $choices);
                        break;
                    case 7:
                        $form = new Date2FormMaker($subevent, $now, $choices);
                        break;
                    case 8:
                        $form = new Date3FormMaker($subevent, $now, $choices);
                        break;
                    default:
                        $form = new TextFormMaker($subevent, $now, $choices);
                        break;
                }

                break;
            case "p":
                $form = new SelectFormMaker($subevent, $now, $choices);
                break;
            case "c" :
                $form = new CheckboxFormMaker($subevent, $now, $choices);
                break;
            case "r" :
                $form = new RadioFormMaker($subevent, $now, $choices);
                break;

        }

        return $form;
    }

    /**
     * @return string typeとデザインからhtml2を作る
     */
    public function buildForm($subevent, $design)
    {
        switch ($subevent["type2"]) {
            case "r" :
            case "p" :
            case "c" :
                return buildChoiseList($design, $subevent["type2"], $subevent["choice"], $subevent["fel"], $subevent["other"]);
            case "t" :
                return buildFreeForm($design, $subevent["width"], $subevent["rows"]);
//type2=m はenq_subeventのみの特殊仕様のためサポートしない

            case "n" :
                return $subevent["html2"];
        }
    }

    /**
     * @return bool タイプ別に回答が空白かを調べ空白ならtrue(hissutyekkudesiyou)
     */
    public function isBlank($subevent, $answer)
    {
        $sd = $subevent['seid'];
        switch ($subevent['type2']) {
            case 't':
                $ta = $answer["T_" . $sd];
                //TODO:typeが増えるたびにこのあたりの設定を書き換えるのは面倒だと思う
                //〒郵便番号などの空白文字に対応する
                switch ($subevent['type1']) {
                    //郵便番号
                    case 5:
                        $ta = str_replace('-','',$ta);
                        break;
                    //年月日
                    case 6:
                    case 7:
                    case 8:
                        $ta = str_replace('0/0/0', '', $ta);
                        $ta = str_replace('0/0', '', $ta);
                        break;
                }

                return FCheck::isBlank($ta);
                break;
            case 'c':
            case 'r':
            case 'p':

                //表示されなかった、
                //または表示されたにもかかわらず、答えていない場合
                $pa = $answer["P_" . $sd];

                return is_null ($pa) || (($pa == 9998) || ($pa[0] == 9998));
                break;
            default:
                break;
        }

        return false;
    }

    /**
     * @return object Condition type依存で作成される条件クラスを返します
     */
    public function getCondition($subevent)
    {
        switch ($subevent['type1']) {
            case 3:
                return new NumericalCondition($subevent);
                break;
            case 5: //〒郵便番号

                return  new ZipCondition($subevent);
                break;
            case 6:
                return new Date1Condition($subevent);
                break;
            case 7:
                return new Date2Condition($subevent);
                break;
            case 8:
                return new Date3Condition($subevent);
                break;
            case 10:
                return new EmailCondition($subevent);
                break;
        }
    }

    /**
     * @return array 設定できるタイプ一覧を返します
     */
    public function getTypes()
    {
        return array(
            '1r' => '単数回答/ラジオボタン',
            '1p' => '単数回答/プルダウン',
            '2c' => '複数回答/チェックボックス',
            '3t' => '数値回答/テキストボックス',
            '4t' => '自由記入/テキスト入力',
            '10t' => 'Eメール回答/テキスト入力',
            '5t' => '郵便番号',
            '6t' => '日付(年/月/日)',
            '7t' => '日付(年/月)',
            '8t' => '日付(月/日)',
            '0n' => 'フリースペース',
        );
    }

    /**
     * @return array type1の一覧を返します
     */
    public function getType1()
    {
        return array(
            0 => 'フリースペース',
            1 => '単数回答',
            2 => '複数回答',
            3 => '数値回答',
            4 => '記入回答',
            5 => '郵便番号回答',
            6 => '日付回答',
            7 => '日付回答',
            8 => '日付回答',
            10 => 'Eメール回答',
        );
    }

    /**
     * @return array type2の一覧を返します
     */
    public function getType2()
    {
        return array(
            'r' => 'ラジオボタン',
            'p' => 'プルダウン',
            'c' => 'チェックボックス',
            't' => 'テキストボックス',
            'n' => 'フリースペース',
        );
    }

    /**
     * @return string type1と2をひとつにまとめたもの
     */
    public function createTypeString($type1, $type2)
    {
        return $type1.$type2;
    }

    /**
     * @return array type１と2に分解。listで受け取れる
     */
    public function resolveTypeString($typestring)
    {
        return array(
            substr($typestring, 0, -1),
            substr($typestring, -1, 1),
        );
    }

    /**
     * @return bool 回答ができる質問であるかどうか
     */
    public function isAnswerable($subevent)
    {
        //現状はフリースペースであれば回答がないということの判定に使う
        return ($subevent['type2'] != 'n');
    }

    /**
     * @return bool otherが設定できるタイプならtrue
     */
    public function isSettableOther($subevent)
    {
        return (in_array($subevent['type2'], array('r', 'p', 'c')));
    }

    /**
     * @return bool choiceが設定できるタイプならtrue
     */
    public function isSettableChoice($subevent)
    {
        return (in_array($subevent['type2'], array('r', 'p', 'c')));
    }

    /**
     * @return bool 必須が設定できるタイプならtrue
     */
    public function isSettableHissu($subevent)
    {
        //フリースペース以外
        return ($subevent['type2'] !== 'n');
    }

}

class FormMaker
{
    public $subevent;
    public $now;
    public $choices;

    public function FormMaker($subevent, $now, $choices)
    {

        $this->subevent = $subevent;
        $this->now = $now;
        $this->choices = $choices;
    }

    public function getVisibleChoices($answers)
    {
        //Questionクラスのcond判定で普通はok
        $q =& getQuestion($this->subevent);

        return $q->getVisibleChoices($answers);
    }

    public function get($answers)
    {

    }
}

class SelectFormMaker extends FormMaker
{
    public function get($answers)
    {
        $seid = $this->subevent['seid'];

        //optionを取得
        $opts = "";
        $answer = $answers["P_".$seid];
        $aryTmpCa = $this->getVisibleChoices($answers);
        if (count($aryTmpCa) == 0) {
            //$sel =  (!is_null($answer) && '9999' == $answer);
            $sel = $this->isSameAnswer($answer, '9999');
            $opts .= $this->getOptionForm('9999', '該当なし', $sel);
        } else {
            $ca = $this->choices['value'];
            foreach ($this->choices['key'] as $k => $v) {
                if(!in_array($v, $aryTmpCa)) continue;
                //$sel =  (!is_null($answer) && $v == $answer);
                $sel = $this->isSameAnswer($answer, $v);
                $choice = ($v == 9999)? "該当なし": $ca[$k];
                $opts .= $this->getOptionForm($v, $choice, $sel);
            }
        }

        //フォーム名
        $parts = 'name="'.$seid.'"';

        //スタイルシート/JavaScript
        if ($this->subevent["ext"]) $parts .= ' '.$this->subevent["ext"];

        //selectを整形
        $tag.= '<select '.$parts.'>';
        $tag.= '<option value="ng">'.TEXT_PULLDOWN_DEFAULT.'</option>';
        $tag.= $opts.'</select>';

        return $tag;
    }

    public function isSameAnswer($answer, $value)
    {
        return (!is_null($answer) && $value == $answer);
    }

    public function getOptionForm($value, $choice, $selected=false, $ext='')
    {
        $sel = ($selected)? ' selected': '';
        if($ext) $ext = ' '.$ext;

        return '<option value="'.$value.'"'.$sel.$ext.'>'.$choice.'</option>';
    }
}

class SelectInputFormMaker extends FormMaker
{
    public function getSelectInput($type, $answers)
    {
        $no = $this->now;
        $choiceKeys = $this->choices['key'];
        $seid = $this->subevent['seid'];

        $key = $choiceKeys[$no];

        if(!$key && $key !== 0) return;

        //フォーム名
        $parts[] = ' name="'.$seid.'[]"';
        $parts[] = ' id="'.$seid.'_'.$key.'"';

        //スタイルシート/JavaScript
        if ($this->subevent["ext"]) {
            $parts[] = $this->subevent["ext"];
        }

        //タイプ
        $parts[] = 'type="'.$type.'"';

        //選択肢の展開
        //一致のときにchecked(radio)等をつける
        $sel = "";
        $answer = $answers["P_".$seid];
        if (is_array($answer) && in_array($key, $answer)) {
            $sel = ' checked';
        }

        $tag = '<input '.implode(" ", $parts).' value="'.$choiceKeys[$no].'"'.$sel.'>';

        //選択肢の先頭ならばダミー文字列入れ
        if ($no == 0) {
            $tag = '<input type="hidden" name="DM_'.$seid.'" value="dum">'.$tag;
        }

        return $tag;
    }
}

class CheckboxFormMaker extends SelectInputFormMaker
{
    public function get($answers)
    {
        return $this->getSelectInput('checkbox', $answers);
    }
}

class RadioFormMaker extends SelectInputFormMaker
{
    public function get($answers)
    {
        return $this->getSelectInput('radio', $answers);
    }
}

class TextFormMaker extends FormMaker
{
    public function get($answers)
    {
        //TODO:上位でhtmlspecialcharなどをつかっているため、安全が保証されない
        //フォーム名
        $answerKey = 'T_'.$this->subevent['seid'];
        $addParams = array($this->subevent['ext']);
        $parts = array('name="'.$answerKey.'"');
        //スタイルシート
        //if ($array[$fn]["style"]) $parts[] = " style=".$array[$fn]["style"];
        foreach ($addParams as $v) {
            $parts[] = $v;
        }

        if ($this->subevent["rows"] <= 1) {
            $parts[] = 'type="text"';
            $parts[] = 'size="'.$this->subevent["width"].'"';
            $parts[] = 'value="'.transHtmlentities($answers[$answerKey]).'"';

            //タグ展開
            $tag = '<input '.implode(" ", $parts).'>';
        } else {
            $parts[] = 'cols="'.$this->subevent["width"].'"';
            $parts[] = 'rows="'.$this->subevent["rows"].'"';
            $tag = '<textarea '.implode(" ", $parts).'>'
                .transHtmlentities($answers[$answerKey])
                .'</textarea>';
        }

        return $tag;
    }
}

class ZipFormMaker extends FormMaker
{
    public function get($answers)
    {
        $answerKey = 'T_'.$this->subevent['seid'];
        list($zip1, $zip2)=explode('-', $answers[$answerKey]);

        return<<<HTML
<input name="{$answerKey}[]" value="{$zip1}" size="3" maxlength="3" style="ime-mode:disabled">
-
<input name="{$answerKey}[]" value="{$zip2}" size="4" maxlength="4" style="ime-mode:disabled">
HTML;
    }
}

class DateFormMaker extends FormMaker
{
    public function getSelect($name, $values, $def, $ext="")
    {
        $opts = "";
        if(is_array($values))
        foreach ($values as $key => $value) {
            $opts .= $this->getOptionForm($key, $value, $this->isSameAnswer($def, $value));
        }

        return <<<__HTML__
<select name="{$name}"{$ext}>
{$opts}
</select>
__HTML__;
    }

    public function isSameAnswer($answer, $value)
    {
        return (is_good($answer) && $value==$answer);
    }

    public function getOptionForm($value, $choice, $selected=false, $ext="")
    {
        $selected = ($selected)? " selected":"";
        $ext = (is_good($ext))? " {$ext}":"";

        return <<<__HTML__
<option value="{$value}"{$selected}{$ext}>{$choice}</option>
__HTML__;
    }
}

class Date1FormMaker extends DateFormMaker
{
    public function get($answers)
    {
        global $yyyy,$mm,$dd;
        $answerKey = 'T_'.$this->subevent['seid'];

        $yyyy_ = array(0=>'年');
        foreach ($yyyy as $y) {
            $yyyy_[$y] = $y;
        }
        $mm_ = array_merge(array(-1=>'月'),$mm);
        $dd_ = array_merge(array(-1=>'日'),$dd);

        list($year,$month,$day)=explode('/', $answers[$answerKey]);
        $select_year = $this->getSelect($answerKey.'[]', $yyyy_, $year, "id=\"{$answerKey}_y\"");
        $select_month = $this->getSelect($answerKey.'[]', $mm_, $month, "id=\"{$answerKey}_m\"");
        $select_day = $this->getSelect($answerKey.'[]', $dd_, $day, "id=\"{$answerKey}_d\"");
        $y_max = end($yyyy);
        $y_min = reset($yyyy);

        return<<<HTML
{$select_year}/{$select_month}/{$select_day}
<input type="hidden" id="{$answerKey}"/>
<script type="text/javascript">
<!--
$("#{$answerKey}").datepicker({
    showOn: 'button',
    altFormat: 'yy/m/d',
    dateFormat: 'yy/m/d',
    changeMonth: true,
    changeYear: true,
    showMonthAfterYear: true,
    yearSuffix:"\u5e74",
    monthNamesShort:["1\u6708","2\u6708","3\u6708","4\u6708","5\u6708","6\u6708","7\u6708","8\u6708","9\u6708","10\u6708","11\u6708","12\u6708"],
    dayNamesMin:["\u65e5","\u6708","\u706b","\u6c34","\u6728","\u91d1","\u571f"]
});
$( "#{$answerKey}" ).datepicker( "option", "minDate", "{$y_min}/1/1" );
$( "#{$answerKey}" ).datepicker( "option", "maxDate", "{$y_max}/12/31" );
$('#{$answerKey}').live("change",function () {
    var val = $(this).val();
    var date = val.split("/");
    $('select[id="{$answerKey}_y"]').val(date[0]);
    $('select[id="{$answerKey}_m"]').val(date[1]);
    $('select[id="{$answerKey}_d"]').val(date[2]);
});
var setMailDate = function () {
    var y = $('select[id="{$answerKey}_y"]').val();
    var m = $('select[id="{$answerKey}_m"]').val();
    var d = $('select[id="{$answerKey}_d"]').val();
    $("#{$answerKey}").val(y+"/"+m+"/"+d);
}
$('select[id="{$answerKey}_y"], select[id="{$answerKey}_m"], select[id="{$answerKey}_d"]').live("change", setMailDate);
$(function () {
    setMailDate();
});
//-->
</script>
HTML;
    }
}
class Date2FormMaker extends DateFormMaker
{
    public function get($answers)
    {
        global $yyyy,$mm,$dd;
        $answerKey = 'T_'.$this->subevent['seid'];

            $yyyy_ = array(0=>'年');
            foreach ($yyyy as $y) {
                $yyyy_[$y] = $y;
            }
            $mm_ = array_merge(array(-1=>'月'),$mm);

            list($year,$month,$day)=explode('/',$answers[$answerKey]);
            $select_year = $this->getSelect($answerKey.'[]', $yyyy_, $year);
            $select_month = $this->getSelect($answerKey.'[]', $mm_, $month);

            return<<<HTML
{$select_year}/{$select_month}
HTML;
    }
}

class Date3FormMaker extends DateFormMaker
{
    public function get($answers)
    {
        global $yyyy,$mm,$dd;
        $answerKey = 'T_'.$this->subevent['seid'];

            $yyyy_ = array(0=>'年');
            foreach ($yyyy as $y) {
                $yyyy_[$y] = $y;
            }
            $mm_ = array_merge(array(-1=>'月'),$mm);
            $dd_ = array_merge(array(-1=>'日'),$dd);


            list($month,$day)=explode('/',$answers[$answerKey]);

            $select_month = $this->getSelect($answerKey.'[]', $mm_, $month);
            $select_day = $this->getSelect($answerKey.'[]', $dd_, $day);

            return<<<HTML
{$select_month}/{$select_day}
HTML;
    }
}
