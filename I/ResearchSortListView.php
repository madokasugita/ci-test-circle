<?php
/**
 * Research管理画面用SortListのViewクラス
 * 2008/1/17
 *
 * v2.0 imobile用修正により条件付テーブル用のデザイン追加
 */

require_once 'ResearchDesign.php';

//表示数変更のみ表示
class ResearchLimitCondTableView extends CondTableView
{
    public function getBox($row, $hidden, $action, $width="100%")
    {
        $body = $this->getBody($row);
        $submit = $hidden.$this->getSubmitButton();

        return <<<__HTML__
<form action="{$action}" method="post" style="display:inline">
<div align="left" style="text-align:right;margin:0px;background-color:#ffffff;font-size:smaller;width:{$width};">
{$body}{$submit}
</div>
</form>
__HTML__;
    }

    public function getRow($key, $value)
    {
        return <<<__HTML__
{$key}{$value}
__HTML__;
    }

    public function getSubmitButton()
    {
        return <<<__HTML__
<input type="submit" value="変更"class="button white">
<input type="submit" name="csvdownload" value="リストをダウンロード"class="button white">
__HTML__;
    }

}

//表示数変更のみ表示
class ResearchCondTableView extends CondTableView
{
    public function getBox($row, $hidden, $action, $width=450)
    {
        $body = $this->getBody($row);
        $submit = $hidden.$this->getSubmitButton();

        return <<<__HTML__
<form action="{$action}" method="post" style="display:inline">
<div align="left" style="text-align:right;margin:0px;background-color:#ffffff;font-size:smaller;width:{$width};">
{$body}{$submit}
</div>
</form>
__HTML__;
    }

    public function getRow($key, $value)
    {
        return <<<__HTML__
{$key}{$value}
__HTML__;
    }

    public function getSubmitButton()
    {
        return <<<__HTML__
<input type="submit" value="変更">
__HTML__;
    }

}

class ResearchSortTableView extends SortTableView
{
    public $tableWidth;
    //widthはResearchListからも設定できます
    public function ResearchSortTableView($width='')
    {
        $this->tableWidth = $width;
        $this->setColStyle('button', 'style="text-align:center;"');
        $this->setColStyle('checkbox', 'style="text-align:center;"');
        $this->setColStyle('name', 'style="white-space: nowrap;"');

        $this->setColStyle('mflag', 'style="text-align:center;"');
        $this->setColStyle('lang_flag', 'style="text-align:center;"');
        $this->setColStyle('test_flag', 'style="text-align:center;"');

        parent::__construct();
    }


    /**
     * 該当が無かった場合の表示を返す
     * @return string html
     */
    public function getEmptyHtml()
    {
        if($_POST)
            $message = '<br>####no_data####<br><br>';
        else
            $message = '<br>条件を指定して検索ボタンを押してください<br><br>';

        return $this->RDTable->getTBody(
            $this->RDTable->getTr(
                $this->RDTable->getTd($message)
            )
        ,$this->tableWidth);
    }

    /**
     * override可
     * ソート用のボタン（昇り順・降順のセット）を返す
     * @param  string $asc   昇り順に並べ替えるためのリンク先
     * @param  string $desc  降り順に並べ替えるためのリンク先
     * @param  string $state ''/ desc/ asc 現在どれが選択されているか
     * @return string html
     */
    public function getSortButton($asc, $desc, $state="")
    {
        $asct = $this->getSortButtonTag($asc, "昇順に並べる", "sort-ascend", ($state==="asc"));
        $desct = $this->getSortButtonTag($desc, "降順に並べる", "sort-descend", ($state==="desc"));

        return  <<<__HTML__
         <div class="sort-icon">
          {$asct}{$desct}
         </div>
__HTML__;
    }

    //デフォルト表示用の関数
    public function getSortButtonTag($href, $title, $body, $isChoiced)
    {
        if ($isChoiced) {
            $href = "#";
            $body .= "-select";
        }

        return <<<__HTML__
<a href="{$href}" title="{$title}" class="{$body}"></a>
__HTML__;
    }


    public function getBox(&$sortTable, $body)
    {
        $link = $sortTable->getChangePageLink();
        $next = $sortTable->getNextPageLink($link);
        $prv = $sortTable->getPreviousPageLink($link);
        $navi = $sortTable->getPageNavigateLink($link);
        $offset = $sortTable->offset + 1;
        $max = min($sortTable->count, $sortTable->offset + $sortTable->limit);

        $table = $this->RDTable->getTBody($body, $this->tableWidth);

        return <<<__HTML__
<div class="page">
全{$sortTable->count}件中{$offset}～{$max}件を表示　
{$prv}｜{$navi}｜{$next}
</div>
{$table}
<div class="page">
全{$sortTable->count}件中{$offset}～{$max}件を表示　
{$prv}｜{$navi}｜{$next}
</div>
__HTML__;
    }

    public function getNextPageLink($href, $limit)
    {
        return <<<__HTML__
<a href="{$href}#page">####next####&gt;&gt;</a>
__HTML__;
    }

    public function getPreviousPageLink($href, $limit)
    {
        return <<<__HTML__
<a href="{$href}#page">&lt;&lt;####prev####</a>
__HTML__;
    }

    //次のページが無い時
    public function getNoNextPageLink()
    {
        return '<span style="color:#999999;">####next####&gt;&gt;</span>';
    }
    //前のページが無い時
    public function getNoPreviousPageLink()
    {
        return '<span style="color:#999999;">&lt;&lt;####prev####</span>';
    }

    public function getHeaderTr($body)
    {
        return $this->RDTable->getHeaderTr ($body);
    }

    public function getHeaderTd($v,$sort,$colname)
    {

        $v = html_unescape($v);
        $s = $this->getColStyle($colname);
$cell=<<<HTML
<table width="100%" class="sort-title-table"><tr><td>{$v}</td><td>{$sort}</td></tr></table>
HTML;
        //デフォルト値を活かすため。
        return $s? $this->RDTable->getHeaderTd ($cell, $s): $this->RDTable->getHeaderTd ($cell, $s);
    }

    public $trcount = 0;
    public function getListTr($body)
    {
        return $this->RDTable->getTr ($body, $this->trcount++);
    }

    public function getListTd($body, $colname)
    {

        $s = $this->getColStyle($colname);
        if($width = $this->getColWidth($colname))
            $s .=' width="'.$width.'"';

        if(is_void($body)) $body = "&nbsp;";
        //デフォルト値を活かすため。
        return $s? $this->RDTable->getTd ($body, $s): $this->RDTable->getTd ($body);
    }
    public function getColWidth($colname)
    {
        return '';
    }

}

class ResearchList extends SearchList
{
    public $width;
    public function ResearchList(&$condTable, &$sortTable, $width=450)
    {
        parent::SearchList($condTable, $sortTable);
        $this->resTable->view->tableWidth = $this->width = $width;
    }

    public function getHtml($cond, $res)
    {
        return <<<__HTML__
<div style="width:{$this->width}px">
{$cond}
{$res}
</div>
__HTML__;
    }
}
