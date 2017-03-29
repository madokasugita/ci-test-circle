<?php

class testCondition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        global $GLOBAL_NAME;
        ereg('(.)-([0-9]+)',$this->subevent['title'],$match);
        $id = $match[1].'_'.$match[2];

        return "<style>#{$id}{background-color:#ffdddd}</style>".'<span class="hissuerror">'.$GLOBAL_NAME.$this->makeErrorMessage("TestError!").'</span>';
    }
}

/**
 * 条件
 */
class EncodingCondition extends EnqueteErrorCondition
{
    public function getError($answer)
    {
        $text = $answer["T_" . $this->subevent['seid']];
        if(!$text);
            $text = $answer["E_" . $this->subevent['seid']];
        if (mb_convert_encoding(mb_convert_encoding($text,'SJIS','EUC-JP'),'EUC-JP','SJIS') != $text) {
            return $this->makeErrorMessage("「%%%%title%%%%」に対応できない文字を含んでいます。");
        }

        return false;
    }
}

/**
 * 360度用カスタマイズ
 */
class OtherCondition extends EnqueteErrorCondition
{
    public function getError($answer)
    {

        $text = $answer["E_" . $this->subevent['seid']];
        $maxlength=200;
        $text_ = $text;
        $text_ = str_replace("\n",'',$text_);
        $text_ = str_replace("\r",'',$text_);
        $text_ = str_replace('&amp;','&',$text_);
        if (mb_strlen($text_,'UTF-8')>$maxlength) {
            return $this->makeErrorMessage("<span style='color:blue'>「%%%%title%%%%」の記入回答は{$maxlength}文字までです。</span>");
        }
        if (mb_strlen($text_,'UTF-8')==0) {
            return $this->makeErrorMessage("<span style='color:blue'>「%%%%title%%%%」の記入回答は必須です。</span>");
        }

        return false;
    }
}
