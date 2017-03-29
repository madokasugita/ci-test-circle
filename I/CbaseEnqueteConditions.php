<?php
require_once 'CbaseEnquete.php';
require_once '360_EnqueteConditions.php';
require_once 'QuestionType.php';

$GLOBAL_ERROR_SEID = array();
/**
 * エラー条件の追加はここで記述する
 */
class EnqueteErrorConditions
{
    /**
     * ◆static
     * 必須チェック条件を設定する
     * @param  array $subevent セット先の質問
     * @return array EnqueteErrorConditionの配列
     */
    function &getNecessary($se)
    {
        /*
         * necessaryConditionは、常にチェックするエラーを記述。
         * 一般的には必須チェックがこれにあたる
         * $errorConditionは、necessaryConditionに引っかからなかった場合に詳細なチェックを行うもの
         *
         */
        //現状は旧形式にあわせて全て常にチェック
        //TODO:将来的には、必須チェック以外をgetに移したい

        //チェックする順番に入る
        $res = array();

        if($se['hissu']) $res[] =& new NecessaryCondition($se);
        if($se['cond2']) $res[] =& new Cond2Condition($se, $se['cond2']);

        if ($se['cond360']) {
            foreach (explode(',',($se['cond360'])) as $cond) {
                $cond.='Condition';
                $res[] =& new $cond($se);
            }
        }

        $formcond = QuestionType::getCondition($se);
        if($formcond)$res[] = $formcond;

        //$res[] =& new ExternalFontCondition($se);
        //TODO:ここでcondを改行で分割してforeachで回せば複数行指定できます。
        //理想的には、Cond4Condition::setConditionでパースして、子Cond4に今のCond4Condition機能を持たせるようにしてください
        //さらに理想的には、当関数をパース関数として、cond4とか3といった括りを廃止し、maxとかminとか目的毎にクラスを作る
        if($se['cond3']) $res[] =& new Cond3Condition($se, $se['cond3']);

        return $res;
    }

    /**
     * ◆static
     * getNecessary条件を通過した際にのみ判定される条件をセットする
     * @param  array $subevent セット先の質問
     * @return array EnqueteErrorConditionの配列
     */
    function &get($subevent)
    {
        $res = array();
        $se = $subevent;
        if($se['cond4']) $res[] =& new Cond4Condition($se, $se['cond4']);

        return $res;
    }

}

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
//▼エラー条件の基底クラス

/**
 * エラー発生のための条件クラス
 */
class EnqueteErrorCondition
{
    public function EnqueteErrorCondition(&$subevent)
    {
        $this->subevent =& $subevent;
    }

    /**
     * ◆abstruct
     * エラー文章を取得する。
     * @param  array $answer すべての回答の配列
     * @return array 発生したエラーメッセージの配列。エラー無しならarray()
     */
    public function getError($answer) {}

    //ここで指定することで、条件ごとに出力方法が変えられるという利点あり
    /**
     * エラー文章を整形して返す
     * @param  string $message エラーメッセージ
     * @return string エラーとして表示される文章
     */
    public function makeErrorMessage($message)
    {
        global $GLOBAL_ERROR_SEID;
        $GLOBAL_ERROR_SEID[$this->subevent['seid']] = true;

        if($this->subevent['title']=='メッセージ')
            $this->subevent['title'] = '####enq_error_message####';

        $title = mb_strimwidth(strip_tags($this->subevent['title']), 0, ERRORMESSAGE_TITLE_WIDTH, '...', INTERNAL_ENCODE);
        $num_ext = (is_good($this->subevent['num_ext']) && $this->subevent['num_ext']>0)? $this->subevent['num_ext'] : "";
        $message = str_replace('%%%%title%%%%', $title, $message);
        $message = str_replace('%%%%num_ext%%%%', $num_ext, $message);

        //TODO:タグの指定はできれば「デザイン」でやりたいところ
        return "%%%%error_target%%%%".$message;

    }
}

/**
 * コマンド付きエラー条件（Cond2ｸA靴覆匹濃藩僉・ */
class WithCommandCondition extends EnqueteErrorCondition
{
    public function WithCommandCondition(&$subevent, $command='')
    {
        parent::EnqueteErrorCondition($subevent);
        if($command) $this->setCondition($command);
    }

    /**
     * ◆abstract
     * コマンド（スクリプト）をパースしてセットする
     */
    public function setCondition($cond) {}

    /**
     * ◆abstract
     * 条件データをコマンドにして返す
     */
    public function getCondition() {}
}

//-----------------------------------------------------------------------------
//▽エラー条件の定義

/**
 * 必須条件
 */
class NecessaryCondition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        global $GLOBAL_NAME;
        if ($this->isBlank($answer)) {
            return '<span class="hissuerror">'.$this->makeErrorMessage(FError::get("HISSU_NOTHING")).'</span>';
        }

        return false;
    }

    public function isBlank($answer)
    {
        return QuestionType::isBlank ($this->subevent, $answer);
    }
}

/**
 * 旧Researchのcond2を処理する
 */
class Cond2Condition extends WithCommandCondition
{
    public function setCondition($cond)
    {
        $this->cond2 = unserialize($cond);
    }

    public function getCondition()
    {
        if(!$this->cond2) return '';

        return serialize($this->cond2);
    }

    public $cond2;
    public function getError($answer)
    {
        $error = array();
        $cd2 = $this->cond2;
        $sd = $this->subevent['seid'];
        switch ($this->subevent['type2']) {
            case 'c':
            case 'r':
                if ($cd2["maxcount"]) {
                    if ($cd2["maxcount"] < count($answer["P_" . $sd])) {
                        $error[] = $this->makeErrorMessage(FError::get("CHOICE_OVER"));
                    }
                }
                if ($cd2["equalcount"]) {
                    if (count($answer["P_" . $sd]) != $cd2["equalcount"]) {
                        $error[] = $this->makeErrorMessage(ereg_replace("NNNN", mb_convert_kana($cd2["equalcount"], "N"), FError::get("CHOICE_NEED")));
                    }
                }
                if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                    if (in_array($cd2["other"], $answer["P_" . $sd]) && empty ($answer["E_" . $sd])) {
                        $error[] = $this->makeErrorMessage(FError::get("OTHER_NOTHING"));
                    }
                }
                break;
            case 'p':
                if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                    if ($cd2["other"] == $answer["P_" . $sd] && empty ($answer["E_" . $sd])) {
                        $error[] = $this->makeErrorMessage(FError::get("OTHER_NOTHING"));
                    }
                }
                break;
            default:
                break;
        }

        return $error;
    }
    /**
     * 条件を文として返す
     * @return string 条件文
     */
    public function toString($subevents = array())
    {
        $res = '';
        $cd2 = $this->cond2;
        $sd = $this->subevent['seid'];
        $me = getQuestion($this->subevent);
        switch ($this->subevent['type2']) {
            case 'c':
            case 'r':
                if ($cd2["maxcount"]) {
                    $res .= '[実行内容]'.$cd2["maxcount"].'個より多い選択でエラーを表示<br>';
                }
                if ($cd2["equalcount"]) {
                    $res .= '[実行内容]'.$cd2["equalcount"].'個選択しないとエラーを表示<br>';
                }
                if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                    $choices = getPlainText ($me->getChoices());
                    $choice = ($cd2['other'] + 1).'.'.$choices[$cd2['other']];
                    $res .= '[実行内容]'.$choice.'を選択の時は、同僚（その他）欄に回答しないとエラーを表示<br>';
                }
                break;
            case 'p':
                if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                    $choices = getPlainText ($me->getChoices());
                    $choice = ($cd2['other'] + 1).'.'.$choices[$cd2['other']];
                    $res .= '[実行内容]'.$choice.'を選択の時は、同僚（その他）欄に回答しないとエラーを表示<br>';
                }
                break;
            default:
                break;
        }

        return $res;
    }

    public function toStringShort($subevents = array())
    {
        $res = array();
        $cd2 = $this->cond2;
        $sd = $this->subevent['seid'];
        $me = getQuestion($this->subevent);
        switch ($this->subevent['type2']) {
            case 'c':
            case 'r':
                if ($cd2["maxcount"]) {
                    $res[] = $cd2["maxcount"].'個より多く選択した';
                }
                if ($cd2["equalcount"]) {
                    $res[] = $cd2["equalcount"].'個選択しない';
                }
                if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                    $choices = getPlainText ($me->getChoices());
                    $choice = ($cd2['other'] + 1).'.'.$choices[$cd2['other']];
                    $res[] = $choice.'を選択し、同僚（その他）欄に回答しない';
                }
                break;
            case 'p':
                if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                    $choices = getPlainText ($me->getChoices());
                    $choice = ($cd2['other'] + 1).'.'.$choices[$cd2['other']];
                    $res[] = $choice.'を選択し、同僚（その他）欄に回答しない';
                }
                break;
            default:
                break;
        }

        return implode(',', $res).'時、エラー表示';
    }
}

//cond3とcond4の違いがわからない
//cond3→他（または自分）の問題と質問番号を指定しての条件処理
//cond4→コマンドと条件数値のセットでの条件処理
//…とする（コマンドのフォーマット上そうすべきと思われる）
//ただし下位互換のため2008/1/16段階でどちらのフォーマットも受け付けるようにする
//（無理やり呼び出すので、当然正しく指定したほうが高速です）
//※今後コマンドを追加する際は、どちらかにしか追加しない（正しい運用のため）
//cond4::bothなど

//▼旧設定のサンプル（このうちcond4のor,trueｸA靴・ond3用となるいめーじです　）
/*
サンプル
cond3 設問間制御

    【順位制御】
        第一位に入れるの設問のcond3へ
        or,false,順位が重複してます,147:a,148:a

        第二位が必須ならばこちらにも入れる
        or,false,順位が重複してます,146:a,148:a

        ※or,flaseは固定,エラー時メッセージ,自分以外のseid:a,自分以外のseid:a,･･･

    【設問間論理チェック】
        潤ｵある設問のある選択肢を選んでいて、
        　かつ自問の指定以外の選択だった場合にエラー(return false)とする

(合致していたときに何を返すか、で第二引数を決める)

###現在 まっとうには対応していない		or,false,選択が矛盾しております,142:1,140:0
        lcacond3,false,選択が矛盾しております,142:1,140:0
            # lcacond3,false,選択が…,自問seid:選択肢, ターゲット問seid:選択肢

cond4 自問制御

    ※142は自分のseid例

    【論理チェック】
        or,true,回答が正しくありません,142:0,142:1
            ※142は自分のseid例

              orはor条件,and条件のときはandとする
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　自分のseid:(選択肢番号),自分のseid:(選択肢番号),

    【最小値】
        min,true,○○以上の値を入れてください,1

              min
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　最小値 (回答が、ここで指定する最小値以上だったら正しい)

    【最大値】
        max,true,○○以下の値を入れてください,100

              max
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　最大値 (回答が、ここで指定する最大値以下だったら正しい)

    【長制限】
        len,false,○○文字以内で記述ください,200

              max
            　trueは固定
　　	　　　メッセージは自由、但しカンマは入れない
            　最大文字数 (回答が、ここで指定する最大文字数以下だったら正しい)
                            (2byteも一文字としてカウント)
*/

/**
 * 旧Researchのcond3を処理する
 */
class Cond3Condition extends WithCommandCondition
{
    public $cond;
    public $command;
    public $isAnd; //全て一致の場合のみエラー
    public $isNot; //一致しない時にエラー
    public $message;
    public $values; //3以降の配列

    public function setCondition($cond)
    {
        /*
        cond3の配列
            0	and/or	一致条件 またはコマンド
            1	true/false	一致時にtrueを返すかfalseを返すか
            2	エラーメッセージ
            3	(seid):(choice number 0始まり)
            4-	3同様
        */
        $this->values = explode(',', $cond);
        $this->command = array_shift($this->values);
        $this->isAnd =  ($this->command === 'and');
        $this->isNot =  (array_shift($this->values) === 'false');
        $this->message =  array_shift($this->values);
    }

    public function getCondition()
    {
        if(!$this->values) return '';
        $data = array(
            $this->command,
            $this->isNot? 'false': 'true',
            $this->message
        );
        foreach ($this->values as $v) {
            $data[] = $v;
        }

        return implode(',', $data);
    }

    public function getError($answer)
    {
        $error = array();
        $sd = $this->subevent['seid'];
        //この質問に対する回答が有効(ダミーでない回答またはテキスト回答がある)な時
        $pa = $answer["P_" . $sd];
        if ((isset($pa) && ($pa <> 9998 && $pa[0] <> 9998)) || $answer["T_" .$sd]) {
            $tmpAnswer = $this->subevent["type2"] == "t" ? $answer["T_" . $sd] : $pa;
            if ($this->isNot ^ $this->isError($answer, $tmpAnswer)) { //指定条件に合致しない(cond3に設定した選択し番号が回答にあった)
                $error[] = $this->makeErrorMessage($this->message);
            }
        }

        return $error;
    }

    /**
     * エラー条件に一致したらtrue
     */
    public function isError($answers, $reply)
    {
        //$reply = 選択番号、選択番号の配列、テキスト回答

        switch ($this->command) {
            case "min":
            case "max":
            case "len":
                //下位互換のためcond4を呼ぶ
                $cls =& new Cond4Condition($this->subevent);
                $cls->setCondition($this->getCondition());

                return $cls->isError($answers, $reply);
            case 'lcacond3':
                //選択の矛盾チェック(特定の選択肢同士を選択できなくする)
                list($seid1, $choice1) = explode(":", $this->values[0]);
                list($seid2, $choice2) = explode(":", $this->values[1]);

                if($choice2 == '-1')

                    return (in_array($choice1, $reply) && count($answers['P_'.$seid2])>1);
                //自問:選択肢が回答されており
                //他問：選択肢も回答されている
                return (in_array($choice1, $reply)
                    && in_array($choice2, $answers['P_'.$seid2]));
                #	lcacond3,false,選択が矛盾しております,142:1,140:0
                # 自問:選択肢, ターゲット問:選択肢
            case 'and':
            case 'or':
                //この条件はMA,SAにしか設定してはいけない
                foreach ($this->values as $v) {
                    list($seid, $choice) = explode(":", $v);
                    $pa = $answers["P_".$seid];
                    //TODO:pulldownのせいで$paに対してis_arrayが多いのでpulldownも配列にすることを推奨
                    if (
                        //自問がMAかつ対象の回答と完全一致
                        ($choice === 'a'
                            && (is_array($reply) && !array_diff($reply, $pa)))

                        //自問がSAかつ対象の回答と完全一致
                        || ($choice === 'a' && $reply == $pa)

                        //自問がMAかつ対象の特定選択肢と完全一致
                        //Aは必ず選択しなきゃだめみたいな使い方ができる
                        //選択肢毎の必須チェックのイメージ(用途は不明)
                        //cond4用の、回答クリア機能
                        || (!ereg("[^0-9]", $choice)
                            && (is_array($reply) && in_array($choice, $reply)))
                        )
                    {
                        //回答モードcond3で使用 c,r
                        //OR条件の場合は一致したらすぐに抜ける
                        if (!$this->isAnd) return true;
                    } else {
                        //AND条件の場合は一度でも不一致ならばすぐに抜ける
                        if ($this->isAnd) return false;
                    }
                }

                return $this->isAnd? true: false;
            default:
                echo '不正なcondコマンド';
                exit;
        }//switch
    }

    /**
     * cond3とcond4が混同されているので、分別用の関数
     */
    public function isCond3($command)
    {
        return (
               $command === 'and'
            || $command === 'or'
            || $command === 'lcacond3'
        );
    }

    /**
     * seidを置換する
     * 既存のcondに一致するseidが一つでも足りなければ、空白となる
     * @param  array $seids search=>replaceの連想配列
     * @return bool  変更があればtrue
     */
    public function replaceSeid($seids)
    {
        //cond3でなければ何もしない
        if (!$this->isCond3($this->command)) return false;

        $newbody = array();
        foreach ($this->values as $v) {
            list($seid, $choice) = explode(":", $v);
            //範囲外の場合は問答無用で削除して終了
            if (!$seids[$seid]) {
                $this->values = array();

                return true;
            }
            $newbody[] = $seids[$seid].":".$choice;
        }
        $this->values = $newbody;

        return true;
    }
    /**
     * 条件を文として返す
     * @return string 条件文
     */
    public function toString($subevents)
    {
        $ses = array();
        foreach ($subevents as $v) {
            $ses[$v['seid']] = $v;
        }

        $iru = $this->isNot? 'いない': 'いる';
        $res = '';
        switch ($this->command) {
            //TODO:$replyが未記入だった場合の動作に対応していない（0扱いになりエラーが発生）
            //回避するにはNessesaryをやめる
            case "min":
            case "max":
            case "len":
                //下位互換のためcond4を呼ぶ
                $cls =& new Cond4Condition($this->subevent);
                $cls->setCondition($this->getCondition());

                return $cls->toString($subevents);
            case "lcacond3":
                list($seid1, $choice1) = explode(":", $this->values[0]);
                list($seid2, $choice2) = explode(":", $this->values[1]);

                $res .= $this->getTitleString ($ses[$seid1]);
                $res .= $this->getTargetString($choice1, $ses[$seid1]);
                $res .= $this->getTitleString ($ses[$seid2]);
                $res .= $this->getTargetString($choice2, $ses[$seid2]);

                return $res.'[実行内容]'.'同時に選択されて'.$iru.'時、エラーを表示';
            case "and":
                foreach ($this->values as $v) {
                    list($seid, $choice) = explode(":", $v);
                    $res .= $this->getTitleString ($ses[$seid]);
                    $res .= $this->getTargetString($choice, $ses[$seid]);
                }

                return $res.'[実行内容]'.'選択番号がすべて一致して'.$iru.'時、エラーを表示';
            case "or":
                foreach ($this->values as $v) {
                    list($seid, $choice) = explode(":", $v);
                    $res .= $this->getTitleString ($ses[$seid]);
                    $res .= $this->getTargetString($choice, $ses[$seid]);
                }

                return $res.'[実行内容]'.'選択番号がどれかと一致して'.$iru.'時、エラーを表示';
            default:
                return '[不正なcondコマンド]';
        }//switch
    }

    public function getTitleString($subevent)
    {
        return '[質問ID：'.$subevent['seid'].']'.getPlainText ($subevent['title']).'<br>';
    }

    public function getTitleStringShort($subevent)
    {
        return getPlainText ($subevent['title']).'(ID:'.$subevent['seid'].')';
    }

    public function getTargetString($choice, $subevent)
    {
        return '[実行対象選択肢]'.$this->getTargetStringShort ($choice, $subevent).'<br>';
    }

    public function getTargetStringShort($choice, $subevent)
    {
        if ($choice === "a") {
            return '選択されたもの';
        } elseif ($choice == "-1") {
            return 'いずれかの選択肢';
        } else {
            $q = getQuestion($subevent);
            $c = getPlainText ($q->getChoices());

            return ($choice + 1).'.'.$c[$choice];
        }
    }

    public function toStringShort($subevents)
    {
        $ses = array();
        foreach ($subevents as $v) {
            $ses[$v['seid']] = $v;
        }

        $iru = $this->isNot? 'いない': 'いる';
        $res = '';
        switch ($this->command) {
            //TODO:$replyが未記入だった場合の動作に対応していない（0扱いになりエラーが発生）
            //回避するにはNessesaryをやめる
            case "min":
            case "max":
            case "len":
                //下位互換のためcond4を呼ぶ
                $cls =& new Cond4Condition($this->subevent);
                $cls->setCondition($this->getCondition());

                return $cls->toStringShort($subevents);
            case "lcacond3":
                list($seid1, $choice1) = explode(":", $this->values[0]);
                list($seid2, $choice2) = explode(":", $this->values[1]);

                $res .= $this->getTitleStringShort($ses[$seid1]).'の';
                $res .= $this->getTargetStringShort($choice1, $ses[$seid1]).'と';
                $res .= $this->getTitleStringShort($ses[$seid2]).'の';
                $res .= $this->getTargetStringShort($choice2, $ses[$seid2]);

                return $res.'が同時に選択されて'.$iru.'時、エラーを表示';
            case "and":
                $res = array();
                foreach ($this->values as $v) {
                    list($seid, $choice) = explode(":", $v);
                    $res[] = $this->getTitleStringShort($ses[$seid]).'の'
                    .$this->getTargetStringShort($choice, $ses[$seid]);
                }
                $suru = $this->isNot? 'しない': 'する';
                if(count($res) == 1 )

                    return implode('', $res).'と選択番号が一致'.$suru.'時、エラーを表示';
                return implode('、', $res).'、全てと選択番号が一致'.$suru.'時、エラーを表示';
            case "or":
                foreach ($this->values as $v) {
                    list($seid, $choice) = explode(":", $v);
                    $res[] = $this->getTitleStringShort($ses[$seid]).'の'
                    .$this->getTargetStringShort($choice, $ses[$seid]);
                }
                $suru = $this->isNot? 'しない': 'する';
                if(count($res) == 1 )

                    return implode('', $res).'と選択番号が一致'.$suru.'時、エラーを表示';
                return implode('、または', $res).'、どれかと選択番号が一致'.$suru.'時、エラーを表示';
            default:
                return '[不正なcondコマンド]';
        }//switch
    }
}

/**
 * 旧Researchのcond4を処理する
 */
class Cond4Condition extends WithCommandCondition
{
    public $cond;
    public $command;
    public $isNot; //一致しない時にエラー
    public $message;
    public $value;
    public $values = array(); //4以降の配列(ほんとは使わないはず)

    public function setCondition($cond)
    {
        /*
        cond4の配列
            0	コマンド
            1	true/false	一致時にtrueを返すかfalseを返すか
            2	エラーメッセージ
            3	コマンドの引数
            4-	あまり
        */
        $this->values = explode(',', $cond);
        $this->command = array_shift($this->values);
        $this->isNot =  (array_shift($this->values) === 'false');
        $this->message =  array_shift($this->values);
        $this->value =  array_shift($this->values);
    }

    public function getCondition()
    {
        $data = array(
            $this->command,
            $this->isNot? 'false': 'true',
            $this->message,
            $this->value,
        );
        foreach ($this->values as $v) {
            $data[] = $v;
        }

        return implode(',', $data);
    }

    public function getError($answer)
    {
        global $GLOBAL_NAME;//○○さんに対する
        $error = array();
        $sd = $this->subevent['seid'];
//TODO:下記getCond4Clear箇所削除の際はこのrequireも不要です
//		require_once ("C.php");
        $isText = ($this->subevent['type2'] === 't');
        $tmpAnswer = $isText? $answer["T_" . $sd] : $answer["P_" . $sd];
        if ($this->isNot ^ $this->isError($answer, $tmpAnswer)) { //指定条件に合致した(cond4に設定した選択し番号が回答にあった)
            if ($isText) { //記入回答に対するmin,max,lenの条件
                $error[] = '<span class="cond4error">'.$this->makeErrorMessage($this->message).'</span>';
            } else {
/* 以下、seid:choiceの形式で書かれたchoiceと回答の一致を見ているが、
 * 機能が大幅に簡略してある上、特定の回答のみ許可するパターンはcond3で指定可
 * またisErrorがcond3を自動で呼ぶため、この条件は不要と判断
*/
//TODO:認識に相違なければ下記if部分は消してください
//TODO:下記ifが無ければ上のif($isText)も不要、大幅に記述を省略できます。
//				if ($answer["P_" . $sd] != getCond4Clear($this->getCondition(), $answer["P_" .$sd]))
//				{
                    $error[] = '<span class="cond4error">'.$this->makeErrorMessage($this->message).'</span>';
//				}
            }
        }

        return $error;
    }
    //ここで指定することで、条件ごとに出力方法が変えられるという利点あり
    /**
     * エラー文章を整形して返す
     * @param  string $message エラーメッセージ
     * @return string エラーとして表示される文章
     */
    public function makeErrorMessage($message)
    {
        global $GLOBAL_ERROR_SEID;
        $GLOBAL_ERROR_SEID[$this->subevent['seid']] = true;

        $title = mb_strimwidth(strip_tags($this->subevent['title']), 0, ERRORMESSAGE_TITLE_WIDTH, '...', INTERNAL_ENCODE);
        $num_ext = (is_good($this->subevent['num_ext']) && $this->subevent['num_ext']>0)? $this->subevent['num_ext'].". ":"";
        $message = str_replace('%%%%title%%%%',$num_ext.$title,$message);
        //TODO:タグの指定はできれば「デザイン」でやりたいところ
        return $message;

    }
    public function isError($answers, $reply)
    {
        switch ($this->command) {
            //TODO:$replyが未記入だった場合の動作に対応していない（0扱いになりエラーが発生）
            //回避するにはNessesaryをやめる
            case "min":
                //最小値制限：指定値までは入力可、未満は×
                return is_void($reply) || ($reply < $this->value);
            case "max":
                //最大値制限：指定値までは入力可、それ以上は×
                return is_void($reply) || ($this->value < $reply);
            case "len":
                //最大文字数制限：指定文字数までは入力可、それ以上は×
                return ($this->value < mb_strlen($reply));
            case 'limit':
                return (0 < preg_match('/^['.$this->value.']$/', $reply));
            case "both":
                //範囲指定：x-yの値と一致すればtrue（when x..y）
                //min,maxのチェック条件とはtrue/falseが逆になっているので注意
                list($min, $max) = explode("-", $this->value);

                return is_void($reply) || ($min <= $reply && $reply <= $max);
            case "lcacond3":
            case "and":
            case "or":
                //下位互換のためcond3を呼ぶ
                $cls =& new Cond3Condition($this->subevent);
                $cls->setCondition($this->getCondition());

                return $cls->isError($answers, $reply);
            default:
                echo '不正なcondコマンド';
                exit;
        }//switch

    }

    /**
     * seidを置換する
     * 既存のcondに一致するseidが一つでも足りなければ、空白となる
     * @param  array $seids search=>replaceの連想配列
     * @return bool  変更があればtrue
     */
    public function replaceSeid($seids)
    {
        //cond4はseidを指定しないはずなので本来は処理不要
        if(!Cond3Condition::isCond3($this->command)) return false;

        //下位互換用。当然処理は余計に増える
        $cls =& new Cond3Condition($this->subevent);
        $cls->setCondition($this->getCondition());
        $cls->replaceSeid($seids);
        $this->setCondition($cls->getCondition());

        return true;
    }
    /**
     * 条件を文として返す
     * @return string 条件文
     */
    public function toString($subevents = array(), $res = '[実行内容]')
    {
        switch ($this->command) {
            //TODO:$replyが未記入だった場合の動作に対応していない（0扱いになりエラーが発生）
            //回避するにはNessesaryをやめる
            case "min":
                //最小値制限：指定値までは入力可、未満は×
                if ($this->isNot) {
                    return $res.$this->value.'以上の時、エラーを表示';
                } else {
                    return $res.$this->value.'未満の時、エラーを表示';
                }
            case "max":
                //最大値制限：指定値までは入力可、それ以上は×
                if ($this->isNot) {
                    return $res.$this->value.'以下の時、エラーを表示';
                } else {
                    return $res.$this->value.'より大きい時、エラーを表示';
                }
            case "len":
                //最大文字数制限：指定文字数までは入力可、それ以上は×
                if ($this->isNot) {
                    return $res.$this->value.'文字以下の時、エラーを表示';
                } else {
                    return $res.$this->value.'文字を越える時、エラーを表示';
                }
            case "both":
                //範囲指定：x-yの値と一致すればtrue（when x..y）
                list($min, $max) = explode("-", $this->value);
                $dearu = $this->isNot? 'でない': 'の';

                return $res.$min.'以上'.$max.'以下'.$dearu.'時、エラーを表示';
            case "lcacond3":
            case "and":
            case "or":
                //下位互換のためcond3を呼ぶ
                $cls =& new Cond3Condition($this->subevent);
                $cls->setCondition($this->getCondition());

                return $cls->toString($subevents);
            default:
                return '[不正なcondコマンド]';
        }//switch
    }

    public function toStringShort($subevents = array())
    {
        switch ($this->command) {
            case "min":
            case "max":
            case "len":
            case "both":
                return $this->toString($subevents = array(), '');
            case "lcacond3":
            case "and":
            case "or":
                //下位互換のためcond3を呼ぶ
                $cls =& new Cond3Condition($this->subevent);
                $cls->setCondition($this->getCondition());

                return $cls->toStringShort($subevents);
            default:
                return '[不正なcondコマンド]';
        }//switch
    }
}

/**
 * 数値条件
 */
class NumericalCondition extends EnqueteErrorCondition
{
    public $cond4;
    public function getError($answer)
    {
        if ($answer["T_" . $this->subevent["seid"]]) {
            if (ereg("[^0-9]", $answer["T_" . $this->subevent["seid"]])) {
                $error[] = $this->makeErrorMessage(FError::get("NO_NUMBER"));
            }
        }

        return $error;
    }
}

/**
 * 〒郵便番号の書式チェック
 */
class ZipCondition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        $a = $answer["T_" . $this->subevent["seid"]];
        if ($a && $a != '-' && !ereg("^[0-9][0-9][0-9]-[0-9][0-9][0-9][0-9]$",$a)) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」の書式が不正です");
        }

        return $error;
    }
}

/**
 * 重複チェック
 */
class DuplicateCondition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        $a = $answer["T_" . $this->subevent["seid"]];
        if ($a && FDB::is_exist(T_EVENT_SUB_DATA, "WHERE other=".FDB::escape($a))) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」の値は既に登録されています");
        }

        return $error;
    }
}

//TODO:Data1,2,3じゃなくてYMDConditionなど、もう少し分かりやすい名称に変えたい

/**
 * 年月日
 */
class Date1Condition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        list($y,$m,$d) = explode('/',$answer["T_" . $this->subevent["seid"]]);

        if (!($y==0 && $m==0 && $d==0) && ($y==0 ||$m==0||$d==0)) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」 年月日全て選択してください");

            return $error;
        }
        if (!($y==0 && $m==0 && $d==0) && !checkdate($m,$d,$y)) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」 存在しない日付が指定されています");

            return $error;
        }

        return $error;
    }
}

/**
 * 年月
 */
class Date2Condition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        list($y,$m,$d) = explode('/',$answer["T_" . $this->subevent["seid"]]);

        if (!($y==0 && $m==0) && ($y==0 ||$m==0)) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」 年月 両方を選択してください");

            return $error;
        }

        return $error;
    }
}

/**
 * 月日
 */
class Date3Condition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        list($m,$d) = explode('/',$answer["T_" . $this->subevent["seid"]]);

        if (!($d==0 && $m==0) && ($d==d ||$m==0)) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」 月日 両方を選択してください");

            return $error;
        }
        if (!($m==0 && $d==0) && !checkdate($m,$d,2000)) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」 存在しない日付が指定されています");

            return $error;
        }

        return $error;
    }
}

/**
 * email
 */
class EmailCondition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        $ans = $answer["T_" . $this->subevent["seid"]];
        require_once 'CbaseFCheckModule.php';
        //TODO:ほんとうは必須チェックを通すので、nessesaryじゃない方の条件に登録すればブランク判定要らない
        if ($ans && !FCheck::isEmail($ans)) {
            $error[] = $this->makeErrorMessage("「%%%%title%%%%」の書式が不正です");
        }

        return $error;
    }
}

/**
 * 外字条件
 */
class ExternalFontCondition extends EnqueteErrorCondition
{
    public $cond4;
    public function getError($answer)
    {
        $seid = $this->subevent['seid'];
        $text = $answer["E_{$seid}"]? $answer["E_{$seid}"]: $answer["T_{$seid}"];
        if (mb_convert_encoding($text,INTERNAL_ENCODE,INTERNAL_ENCODE) != $text) {
            $error[] = $this->makeErrorMessage("の回答は不正な文字を含んでいます。");
        }

        return $error;
    }
}

//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------
//-----------------------------------------------------------------------------

class EnqueteVisibleConditions
{
    function &get ($se)
    {
        $res = array();
        if($se['cond']) $res[] =& new Cond1Condition($se, $se['cond']);

        return $res;
    }

    /**
     * 選択肢質問の取得
     */
    function &getChoices ($se)
    {
        if($se['cond5'])
            $res[] = new Cond5Condition($se, $se['cond5']);

        return $res;
    }

}

/**
 * アンケートの表示に関わる条件はこのクラスの継承で実現する
 */
class EnqueteVisibleCondition
{
    public function EnqueteVisibleCondition(&$subevent)
    {
        $this->subevent =& $subevent;
    }

    public function isVisible($answer) {return false;}
}

/**
 * 選択肢の表示に関わる条件はこのクラスの継承で実現する
 */
class ChoiceVisibleCondition
{
    public function ChoiceVisibleCondition(&$subevent)
    {
        $this->subevent =& $subevent;
    }

    public function getVisible($answer) {return array();	}
}

class Cond1Condition extends EnqueteVisibleCondition
{
    //TODO:条件クラス追加の際は上位クラスCommandVisibleConditionあたり作ってください
    public function Cond1Condition(&$subevent, $command='')
    {
        parent::EnqueteVisibleCondition($subevent);
        if($command) $this->setCondition($command);
    }

    public $cond;
    public function setCondition($command)
    {
        /*
         * フォーマットは
         * array(seid=>条件選択肢)
         */

        $this->cond = unserialize($command);
    }

    public function getCondition()
    {
        return $this->cond? serialize($this->cond): '';
    }

    public function isVisible($answer)
    {
        //TODO:こういうのは継承で無効化するなどが適切
        if (strstr($_SERVER["SCRIPT_NAME"], "index.php")) {
            //condがあるときの条件判定
            foreach ($this->cond as $val) {
                foreach ($val as $k => $v) {
                    $pa = $answer["P_".$k];
                    if ($pa == $v
                        || (is_array($pa) && in_array($v, $pa))
                        || $answer["T_".$k])
                    {
                        return true;
                    }
                }
            }

            return false;
        }

        return true;
    }

    /**
     * あるseidに設定されている条件を全て返す
     */
    public function getCondBySeid($seid)
    {
        $res = array();
        foreach ($this->cond as $val) {
            if(isset($val[$seid])) $res[] =$val[$seid];
        }

        return $res;
    }

    /**
     * seidを置換する
     * 既存のcondに一致するseidが一つでも足りなければ、空白となる
     * @param array $seids search=>replaceの連想配列
     */
    public function replaceSeid($seids)
    {
        $newCond = array();
        foreach ($this->cond as $vLine) {
            //$val= seid=>val
            $newVal = array();
            foreach ($vLine as $k => $v) {
                if (!$seids[$k]) {
                    $this->cond = array();

                    return;
                }
                $newVal[$seids[$k]] = $v;
            }
            $newCond[] = $newVal;
        }
        $this->cond = $newCond;
    }
    /**
     * 条件を文として返す
     * @return string 条件文
     */
    public function toString($subevents)
    {
        $res = '';
        $ses = array();
        foreach ($subevents as $v) {
            $ses[$v['seid']] = $v;
        }
        $seid = -1;
        foreach ($this->cond as $val) {
            foreach ($val as $k => $v) {
                $q = getQuestion($ses[$k]);

                if ($seid != $k) {
                    $res .= '[質問ID：'.$k.']'.getPlainText($q->getTitle ()).'<br>';
                }
                $seid = $k;

                $choice =  getPlainText($q->getChoices());
                $res .= '[実行対象選択肢]'.($v + 1).'.'.$choice[$v].'<br>';
            }
        }
        $res .= '[実行内容]本設問の表示';

        return $res;
    }

    public function toStringShort($subevents)
    {
        $res = '';
        $ses = array();
        foreach ($subevents as $v) {
            $ses[$v['seid']] = $v;
        }
        $seid = -1;
        foreach ($this->cond as $val) {
            foreach ($val as $k => $v) {
                $q = getQuestion($ses[$k]);
                if($seid != $k) $res .= getPlainText($q->getTitle ()).'(ID：'.$k.')の';
                $seid = $k;
                $choice = getPlainText($q->getChoices());
                $res .= ($v + 1).'.'.$choice[$v].',';
            }
        }
        $res = substr($res, 0, -1).'を選択した時、本設問の表示';

        return $res;
    }
}

//cond5 seidは一つしか対応していない そのseidは必須であることが前提
//条件元 条件付けされた問題　共にプルダウンであることが条件

//cond5フォーマット
    //[seid]:[seid's choice]:[choice.choice....],[seid......
class Cond5Condition extends ChoiceVisibleCondition
{
    //TODO:条件クラス追加の際は上位クラスCommandChoiceVisibleConditionあたり作ってください
    public function Cond5Condition(&$subevent, $command='')
    {
        parent::ChoiceVisibleCondition($subevent);
        if($command) $this->setCondition($command);
    }

    public $conds;
    public function setCondition($command)
    {
        $conds = array();
        foreach (explode(',', $command) as $v) {
            $v = trim($v);
            if(!$v) continue;
            list($seid, $choice, $show) = explode(':', $v);
            $conds[] = array(
                'seid' => $seid,
                'choice' => $choice,
                'show' => explode('.', $show)
            );
        }

        $this->conds = $conds;
    }

    public function getCondition()
    {
        $conds = array();
        foreach ($this->conds as $v) {
            $conds[] = $v['seid'].':'.$v['choice'].':'.implode('.', $v['show']);
        }

        return implode(',', $conds);
    }

    public function getVisible($answer)
    {
        foreach ($this->conds as $v) {
            //対象seidの回答が指定の選択肢でなければcontinue
            //一致した条件の場合の選択肢のみが出る仕様

            //ここでinarrayにすればradio,checkboxに対応できる
            if ($answer[ "P_".$v['seid'] ] == $v['choice']) {
                return $v['show'];
            }
        }

        return array(9999);
    }

    /**
     * seidを置換する
     * 既存のcondに一致するseidが足りない行は変換を行わず空白とする
     * @param array $seids search=>replaceの連想配列
     */
    public function replaceSeid($seids)
    {
        $newCond = array();

        foreach ($this->conds as $v) {
            if (!$seids[$v['seid']]) {
                continue;
            }
            $v['seid'] = $seids[$v['seid']];
            $newCond[] = $v;
        }
        $this->conds = $newCond;
    }

    /**
     * 条件を文として返す
     * @return string 条件文
     */
    public function toString($subevents)
    {
        $ses = array();
        foreach ($subevents as $v) {
            $ses[$v['seid']] = $v;
        }
        $res = '';
        $me = getQuestion($this->subevent);
        $meChoice = getPlainText ($me->getChoices());
        foreach ($this->conds as $v) {
            $seid = $v['seid'];
            $q = getQuestion($ses[$seid]);
            $sTitle = getPlainText ($q->getTitle());
            $res .= '[質問ID：'.$seid.']'.$sTitle.'<br>';
            //$sChoice = getPlainText ($ses[$seid]['choice']);
            //$choice = explode(',', $sChoice);
            $choice = getPlainText ($q->getChoices());
            $res .= '[実行対象選択肢]'.($v['choice'] + 1).'.'.$choice[$v['choice']].'<br>';

            $show = array();
            foreach ($v['show'] as $vShow) {
                $show[] = ($vShow + 1).'.'.$meChoice[$vShow];
            }
            $res .= '[実行内容]'.implode(',', $show).'の表示<br>';

        }

        return $res;
    }

    public function toStringShort($subevents)
    {
        $ses = array();
        foreach ($subevents as $v) {
            $ses[$v['seid']] = $v;
        }
        $res = '';
        $me = getQuestion($this->subevent);
        $meChoice = getPlainText ($me->getChoices());
        foreach ($this->conds as $v) {
            $seid = $v['seid'];
            $q = getQuestion($ses[$seid]);

            $res .= '[質問ID：'.$seid.']'.getPlainText ($q->getTitle()).'(ID:'.$seid.')で';
            $choice = getPlainText ($q->getChoices());
            $res .= ($v['choice'] + 1).'.'.$choice[$v['choice']].'を選択の時、';
            $show = array();
            foreach ($v['show'] as $vShow) {
                $show[] = ($vShow + 1).'.'.$meChoice[$vShow];
            }
            $res .=implode(',', $show).'を表示<br>';

        }

        return $res;
    }
}
