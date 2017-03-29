<?php

class SSubeventBase
{
    public function __construct($args)
    {
        foreach($args as $k => $v)
            $this->$k = $v;
    }

    public function get()
    {
        $res = array();
        foreach($this as $k => $v)
            $res[$k] = $v;

        return $res;
    }
}

class SAnswerRateTable extends SSubeventBase
{
    public function __construct($args)
    {
        $this->type1 = 0;
        $this->type2 = "n";
        $this->title = "回答基準";
        $this->hissu = 0;
        $this->page = 1;
        $this->other = 0;
        $this->html2 = <<<__HTML__
<div class="criteria">
<strong>####criteria####</strong>

<table class="smalltable">

__HTML__;
        foreach (array_reverse(range(0,5)) as $i) {
            $this->html2 .= <<<__HTML__
<tr><td>
####enq_rate_{$i}####
</td></tr>

__HTML__;
        }

        $this->html2 .= <<<__HTML__
</table>
</div>
__HTML__;
        parent::__construct($args);
    }

    public function setRateArray($array = array())
    {
        $this->html2 = <<<__HTML__
<div class="criteria">
<strong>####criteria####</strong>

<table class="smalltable">

__HTML__;
        foreach ($array as $i) {
            $this->html2 .= <<<__HTML__
<tr><td>
{$i}
</td></tr>

__HTML__;
        }

        $this->html2 .= <<<__HTML__
</table>
</div>
__HTML__;
    }
}

class STableHeader extends SSubeventBase
{
    public function __construct($args)
    {
        $this->type1 = 0;
        $this->type2 = "n";
        $this->title = "テーブルヘッダー";
        $this->hissu = 0;
        $this->page = 1;
        $this->other = 0;
        $this->html2 = <<<__HTML__
<div class="matrix_div">
<table class="matrix_body_table">
<tr>
<td class="matrix_header_col_0 matrix_col_width_0">####category1####</td>
<td class="matrix_header_col_1 matrix_col_width_1">####category2####</td>
<td class="matrix_header_col_2 matrix_col_width_2">####num_ext####</td>
<td class="matrix_header_col_3 matrix_col_width_3">####question_title####</td>
%%%%targets%%%%
</tr>
__HTML__;
        parent::__construct($args);
    }
}

class STableFooter extends SSubeventBase
{
    public function __construct($args)
    {
        $this->type1 = 0;
        $this->type2 = "n";
        $this->title = "テーブルフッター";
        $this->hissu = 0;
        $this->page = 1;
        $this->other = 0;
        $this->html2 = <<<__HTML__
</table>
</div>
__HTML__;
        parent::__construct($args);
    }
}

class SSelectQuestion extends SSubeventBase
{
    public function __construct($args)
    {
        $this->type1 = 1;
        $this->type2 = "p";
        $this->title = "選択肢設問";
        $this->choice = "5,4,3,2,1,N/A";
        $this->chtable = "5,4,3,2,1,9";
        $this->hissu = 1;
        $this->page = 1;
        $this->other = 0;
        $this->html2 = <<<__HTML__
<tr class="matrix_body_row">
<td class="matrix_body_col category1">%%%%category1%%%%</td>
<td class="matrix_body_col category2">%%%%category2%%%%</td>
<td class="matrix_body_col_2 matrix_col_width_2 cell30_2">%%%%num_ext%%%%</td>
<td class="matrix_body_col_3 matrix_col_width_3 cell30_3">%%%%title%%%%</td>
%%%%form%%%%
</tr>
__HTML__;
        parent::__construct($args);
    }
}

class SMessageQuestion extends SSubeventBase
{
    public function __construct($args)
    {
        $this->type1 = 4;
        $this->type2 = "t";
        $this->title = "記述設問";
        $this->hissu = 1;
        $this->page = 1;
        $this->other = 0;
        $this->width = 150;
        $this->rows = 3;
        $this->html2 = <<<__HTML__
<div class="comment1">
    <b>%%%%title%%%%</b>
    %%%%message%%%%
</div>
__HTML__;
        $this->ext = <<<__HTML__
onblur="checkMainComment_Onblur(this)" onkeyup="checkMainComment(this)"
__HTML__;
        parent::__construct($args);
    }
}

class SConfirm extends SSubeventBase
{
    public function __construct($args)
    {
        $this->type1 = 0;
        $this->type2 = "n";
        $this->title = "確認画面";
        $this->hissu = 0;
        $this->page = 1;
        $this->other = 0;
        $this->html2 = "";
        parent::__construct($args);
    }

    public function setSubevents($subevents)
    {
        $confirm = "";
        foreach ($subevents as $subevent) {
            $html = $subevent['html2'];

            $html = preg_replace('/%%%%message[0-9]*%%%%/ui', '%%%%messageid'.$subevent['seid'].'%%%%', $html);
            $html = preg_replace('/%%%%form%%%%/ui', '%%%%id'.$subevent['seid'].'%%%%', $html);
            $html = preg_replace('/%%%%category1%%%%/ui', '%%%%category1:id'.$subevent['seid'].'%%%%', $html);
            $html = preg_replace('/%%%%category2%%%%/ui', '%%%%category2:id'.$subevent['seid'].'%%%%', $html);
            $html = preg_replace('/%%%%num_ext%%%%/ui', '%%%%num_ext:id'.$subevent['seid'].'%%%%', $html);
            $html = preg_replace('/%%%%title%%%%/ui', '%%%%title:id'.$subevent['seid'].'%%%%', $html);
            $confirm .= $html;
        }
        $this->html2 = $confirm;
    }
}

class SFreeSpace extends SSubeventBase
{
    public function __construct($args)
    {
        $this->type1 = 0;
        $this->type2 = "n";
        $this->title = "フリースペース";
        $this->hissu = 0;
        $this->page = 1;
        $this->other = 0;
        $this->html2 = "";
        parent::__construct($args);
    }
}
