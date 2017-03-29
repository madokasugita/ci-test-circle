<?php
require_once 'ResearchDesign.php';

/*
 * CbaseSortList
 * 2007/12/18
 * 2008/01/10 2.0
 * 2008/01/17 2.0.1 デフォルトソート条件を追加
 * 2008/01/18 2.0.2 デフォルト表示数を追加
 * 2008/03/28 2.0.3 input nameの配列に対応
 * 2008/04/01 2.0.4 バージョン統合
 * 2008/04/08 2.0.5 sortViewにcolGroupを追加
 *
 * 今後の課題
 * safe htmlのチェックと対応（safe htmlは洗浄された値が入ることを期待する）
 *
 */

/*
 * ◆使い方
 *
 * CondTableAdapter
 * SortTableAdapter
 * 以上を継承し、適宜関数を上書きする（abstruct=必須、virtual=必要に応じて）
 *
 * useSessionの場合、op=backをurlに付与して呼ぶと直前の検索条件を利用する
 *
 */

//リストの同時最大表示数を指定するフォームの名前。名称重複などの場合に変える。
define('GET_LIMIT_NAME', 'get_limit');

//条件をセットして中身を表示するクラス
class SortTable
{
    public $adapter;
    public $view;

    /**
     * @param object SortTableAdapter $adapter
     */
    public function SortTable(&$adapter, &$view, $useSession = false)
    {
//		if($view===null) $view =& new SortTableView();
        $this->view =& $view;
        $this->useSession = $useSession;
        $this->adapter =& $adapter;
        $this->setColumns($this->adapter->getColumns());
        $this->noSortColumns = $this->adapter->getNoSortColumns();
    }

    public $result;
    public $limit = 20;
    public $count = 0;
    public $order = '';
    public $desc = false;
    public $offset = 0;
    public $cond;
    public $useSession = false;
    public $isSetResult = false;

    //有効なカラム
    public $columns;
    //有効なカラムのうち、ソートボタンを表示しないカラム(任意)
    public $noSortColumns = array();

    /**
     * 条件とPOSTされた追加条件から結果を取得してセットする
     * @param array $cond 検索条件の配列
     * @param array $post 追加で利用する条件の配列
     */
    public function setResult ($cond=array(), $post=array())
    {
        $this->cond =& $cond;
        if(!$this->cond) $this->cond = array();
        $where = implode(' AND ', $this->makeCond($cond));
        $where = $where? ' WHERE '.$where: '';
        $this->count = $this->adapter->getCount($where);
        $limit = $this->getOrder($post).' LIMIT '.$this->limit.$this->getOffset($post);
        $this->result = $this->adapter->getResult($where.$limit);

        $this->isSetResult = true;
    }

    public function executeDownLoadCsv()
    {
        // 完了(ダウンロード)処理
        unset($_SESSION['csvdownload_hash']);
        $this->CbaseCSV->header_download($_POST['file'], $_POST['filename']);
        ob_start();
        readfile($_POST['file']);
        ob_end_flush();
        unlink($_POST['file']);
        syncDelete($_POST['file']);
    }

    public function makeDownLoadCsv($cond, $post)
    {
        $filename = $this->adapter->getCsvFilename();
        $filename = replaceMessage($filename);

        $this->cond =& $cond;
        if(!$this->cond) $this->cond = array();
        $where = implode(' AND ', $this->makeCond($cond));
        $where = $where? ' WHERE '.$where: '';

        $file = tempnam(TMP_CSVFILE, "csv");
        $fp = fopen($file, "a");

        $max = $this->adapter->getCount($where);
        $n   = round($max/100);
        $i   = 0;
        $percentStr = function ($p,$i,$m) {
            return "<script>p({$p},{$i},{$m});</script>";
        };

        // ヘッダ出力
        $header = $this->adapter->getCsvColumns();
        $csv = array($header);
        $csv_str = $this->CbaseCSV->convert_utf8($csv);
        $csv = array();
        fwrite($fp, $csv_str);

        // 非バッファリングの前にdiv.ccache作成
        getDivListAll();

        // 非バッファリング
        global $con;
        $rs = $this->adapter->getResult($where);

        $methods = get_class_methods($this->adapter);
        // HTML出力、CSV出力開始
        if (ob_get_level() > 0) {
            while(ob_end_clean());
        }
        print encodeWebOut(SortTableView::getDownloadHtmlStart());
        ob_flush();
        flush();
        foreach ($rs as $data) {
            if (in_array('afterGetResult', $methods)) {
                $data = $this->adapter->afterGetResult($data);
            }
            $line = array();
            foreach ($header as $k=>$v) {
                $line[] = html_unescape($this->adapter->getCsvColumnValue($data ,$k));
            }
            $csv[] = $line;
            $csv_str = $this->CbaseCSV->convert_utf8($csv, true, false);
            fwrite($fp, $csv_str);
            $csv = array();
            ++$i;
            if ($n == 0 || $i % $n==0) {
                echo $percentStr(round($i/$max*100), $i, $max);
                ob_flush();
                flush();
                usleep(DLCSV_USLEEP_TIME);
            }
        }
        fclose($fp);
        echo $percentStr(100, $i, $max);
        ob_flush();
        flush();

        // 完了処理
        $con->options['result_buffering'] = true;
        $_SESSION['csvdownload_hash'] = md5(date('YmdHis'));
        $_SESSION['csvdownload_file'] = $file;
        $params = (!empty($this->csvdownload_additional_params)) ? $this->csvdownload_additional_params : array();
        $params['file'] = $file;
        $params['filename'] = $filename;
        unset($this->csvdownload_additional_params);
        print encodeWebOut(SortTableView::getDownloadHtmlEnd($params));
        ob_flush();
        flush();
        syncCopy($file);
    }

    public function downLoadCsv($cond=array(), $post=array())
    {
        $this->CbaseCSV = _getCbaseCSV('UTF-8');
        if (isset($_POST['csvdownload_hash']) && file_exists($_POST['file'])) {
            if ($_POST['csvdownload_hash'] === $_SESSION['csvdownload_hash'] && $_POST['file'] === $_SESSION['csvdownload_file']) {
                // 完了処理
                $this->executeDownLoadCsv();
            } else {
                $html = <<<HTML
不正な処理が確認されたためエラーが発生しました。<br>
再度作成からやり直してください。
<br>
<br>
<button onclick="window.close()">閉じる</button>
HTML;
                $objHtml =& new ResearchAdminHtml("ダウンロードエラー");
                echo $objHtml->getMainHtml($html);
            }
        } elseif (isset($_POST['csvdownload_hash']) && !file_exists($_POST['file'])) {
            // 2度目のアクセスエラー処理
            encodeWebOutAll();
            $html = <<<HTML
一度しかダウンロードできません。<br>
再度作成からやり直してください。
<br>
<br>
<button onclick="window.close()">閉じる</button>
HTML;
            $objHtml =& new ResearchAdminHtml("ダウンロードエラー");
            echo $objHtml->getMainHtml($html);
        } else {
            // 作成処理
            $this->makeDownLoadCsv($cond, $post);
        }
        exit;
    }

    /**
     * where句を作成する
     * @param  array  $cond 検索条件の配列
     * @return string where節
     */
    public function makeCond($cond)
    {
        $where = array();
        if ($cond) {
            foreach ($cond as $k => $v) {
                if ($k === GET_LIMIT_NAME) {
                    if ((string)$cond[$k] === (string)(int)$cond[$k]) {
                        $this->limit = $cond[$k];
                    } else {
                        $this->limit = 0;
                    }
                } elseif ($w = $this->adapter->makeCond($cond, $k)) {
                    $where[] = $w;
                }
            }
        }
        if($dc = $this->adapter->getDefaultCond()) $where[] = $dc;

        return $where;
    }

    /**
     * 有効なカラム名を取得
     * initializeで設定してください
     * @return array カラム名=>説明の連想配列
     */
    public function getColumns() {return $this->columns;}

    /**
     * VisibleColumnに従い列をセットする
     */
    public function setColumns($cols)
    {
        foreach ($cols as $k => $v) {
            if(!$this->isVisibleColumn($k)) continue;
            $this->columns[$k] = $v;
        }
    }

    /**
     * あるカラムが存在し、ソート可能かどうかを返す
     * @param  string $col カラム名
     * @return bool   ソート可能であればtrue
     */
    public function isEnableSortColumn($col)
    {
        $colms = $this->getColumns();
        if(is_null($colms[$col])) return false;
        foreach ($this->noSortColumns as $v) {
            if($v == $col) return false;
        }

        return true;
    }

    /**
     * 現在の検索条件のリンクを取得する
     * @return string リンク文字列
     */
    public function getLink()
    {
        $self = getPHP_SELF();
        $cond = array('op=search');
        $addValue = $this->adapter->setHiddenValue($this->cond);
        foreach ((array) $addValue as $k => $v) {
            $cond[] = $this->getLinkParam($k, $v);
        }
        if ($this->useSession) {
            $cond[] = $this->getSID();
        }
        $cond = implode('&', $cond);
        if ($cond) {
            $cond = '?'.$cond;
        }

        return $self.$cond;
    }

    /**
     * @return string セッション有効の場合にSIDを取得する
     */
    public function getSID()
    {
        return getSID();
    }

    /**
     * @return string リンク時に使うパラメータ部分の値を取得する
     */
    public function getLinkParam($key, $val)
    {
        if (is_array($val)) {
            $res = array();
            foreach ($val as $k => $v) {
                $res[] = $this->getLinkParam($key.'['.$k.']', $v);
            }

            return implode('&', $res);
        }
        $val = mb_convert_encoding($val,ENCODE_WEB_IN,ENCODE_INTERNAL);//20090121追加

        return $key.'='.urlencode($val);
    }

    /**
     * ページを変更する時のためのソート条件なども含めたリンクを取得する
     * @return string リンク文字列
     */
    public function getChangePageLink()
    {
        $link = $this->getLink();
        $cond = array();
        if ($this->order) {
            $keys = array_flip(array_keys($this->getColumns()));
            $desc = $this->desc? '&desc=1': '';
            $link .= '&sort='.$keys[$this->order].$desc;
        }

        return $link;
    }

    /**
     * 編集後の並び維持のためのソート条件、ページなども含めたリンクを取得する
     * @return string リンク文字列
     */
    public function getFullPageLink()
    {
        $link = $this->getChangePageLink();
        if($this->offset)
            $link .= '&offset='.$this->offset;

        return $link;
    }

    /**
     * htmlを作成して返す
     * @return string html
     */
    public function show()
    {
        $view = $this->view;
        if ($this->isSetResult) {
            if ($this->result) {
                $head = $this->getHeader();
                $body = '';
                foreach ($this->result as $row) {
                    $body .= $this->getRow($row);
                }

                return $view->getBox($this, $head.$body);
            }

            return $view->getEmptyHtml();
        }

        return $view->getTopHtml();
    }

    /**
     * ヘッダhtmlを取得
     * @return string html
     */
    public function getHeader()
    {
        $view = $this->view;
        $col = '';
        $link = $this->getLink();
        $keys = array_flip(array_keys($this->getColumns()));
        foreach ($this->getColumns() as $k => $v) {
            $sort = '';
            if ($this->isEnableSortColumn($k)) {
                $sortlink = $link.'&sort='.$keys[$k];
                if ($k === $this->order) {
                    $state = $this->desc? 'desc': 'asc';
                } else {
                    $state = '';
                }
                $sort = $view->getSortButton($sortlink, $sortlink.'&desc=1', $state);
            }
            $col .= $view->getHeaderTd($v,$sort,$k);
        }

        return $view->getHeaderTr($col);
    }

    /**
     * ページ遷移のためのリンクを取得
     * @param  string $link ページ遷移のためのリンク文字列（この後ろに情報が足される）
     * @return array  リンクの配列
     */
    public function getPageNavigateLink($link)
    {
        $view = $this->view;
        $max = $this->count / $this->limit;
        $pages = array();
        for ($i = 0; $i < $max; $i++) {
            $limit = ($this->limit * $i);
            $pages[] = $link.'&offset='.($limit);
            if($limit == $this->offset) $nowpage = $i;
        }

        return $view->getPageNavigateLink($pages, $nowpage);
    }

    /**
     * 次のページのリンクを取得
     * @param  string $link ページ遷移のためのリンク文字列（この後ろに情報が足される）
     * @return string リンク文字列
     */
    public function getNextPageLink($link)
    {
        $view = $this->view;
        $offset = $this->offset + $this->limit;
        if($this->count <= $offset) return $view->getNoNextPageLink();
        $href = $link.'&offset='.($offset);
        $limit = min($this->count - $offset, $this->limit);

        return $view->getNextPageLink($href, $limit);
    }

    /**
     * 前のページのリンクを取得
     * @param  string $link ページ遷移のためのリンク文字列（この後ろに情報が足される）
     * @return string リンク文字列
     */
    public function getPreviousPageLink($link)
    {
        $view = $this->view;
        $offset = $this->offset - $this->limit;
        if($offset < 0) return $view->getNoPreviousPageLink();
        $href = $link.'&offset='.($offset);

        return $view->getPreviousPageLink($href, $this->limit);
    }

    /**
     * 検索結果の一行を取得する
     * @param  array  $data 検索結果の実データ
     * @return string html
     */
    public function getRow($data)
    {
        $view = $this->view;
        $col = '';
        foreach ($this->getColumns() as $k => $v) {
            $val = $this->adapter->getColumnValue($data, $k);
            $col .= $view->getListTd($val, $k);
        }

        return $view->getListTr($col);
    }

    public function isVisibleColumn($columnName)
    {
        return  $this->adapter->isVisibleColumn($columnName);
    }

    /**
     * postからorderを取得
     * @param  array $post 追加で利用する条件の配列
     * @return int   order
     */
    public function getOrder($post)
    {
        if (is_numeric($post['sort']) || ctype_digit($post['sort'])) {
            $keys = array_keys($this->getColumns());
            $sort = $keys[$post['sort']];
            if ($this->isEnableSortColumn($sort)) {
                $order = $this->adapter->formatOrder($sort);
                $this->order = $sort;
                if ($post['desc']) {
                    $order .= ' DESC';
                    $this->desc = true;
                } else {
                    $this->desc = false;
                }
                $so =$this->adapter->getSecondOrder();
                if(is_good($so)) $order .= ','.$so;
            }
        } else {
            $order = $this->adapter->getDefaultOrder();
        }

        return $order? ' ORDER BY '.$order: '';
    }

    /**
     * postからoffsetを取得
     * @param  array $post 追加で利用する条件の配列
     * @return int   offset
     */
    public function getOffset($post)
    {
        $offset = '';
        if ($post['offset'] && ctype_digit($post['offset'])) {
            $offset= $post['offset'];
            $this->offset = $offset;
            $offset = ' OFFSET '.$offset;
        }

        return $offset;
    }
}

/**
 * SortTableに関する情報を設定するクラス。継承して使う
 */
class SortTableAdapter
{
    /**
     * 有効なカラム名を取得
     * @return array カラム名=>説明の連想配列
     */
    public function getColumns() {return array();}
    public function getCsvColumns()
    {
        $a = $this->getColumns ();
        unset($a['button']);

        return $a;
    }
    public function getCsvFileName()
    {
        return date('Ymd').'.csv';
    }

    /**
     * 有効なカラム名のうちソートボタンを表示しないカラム名を取得
     * @return array カラム名の配列
     */
    public function getNoSortColumns() {return array();}

    /**
     * ◆abstract
     * where句の一行分作成する
     * 継承先ではdeafaultでparentを呼んでください
     * @param  array  $values 検索条件の配列
     * @param  string $key    作成する条件のキー
     * @return string where節
     */
    public function makeCond($values, $key)
    {
        echo '不正な条件が含まれています';
        exit;
    }

    /**
     * ◆virtual
     * 検索時に追加される固定の条件(ユーザIDなど)があればここに書く
     * @return string where節に追加される条件部分のSQL
     */
    public function getDefaultCond() {return null;}

    /**
     * ◆virtual
     * 検索時に追加される固定のソート条件があればここに書く
     * @return string order by節に追加される条件部分のSQL
     */
    public function getDefaultOrder() {return null;}

    public function getSecondOrder() {return null;}

    /**
     * ◆abstract
     * DB等から結果を取得して返す
     * @param  string $where 検索条件WHERE句
     * @return array  検索結果
     */
    public function getResult($where) {return array();}

    /**
     * ◆abstract
     * DB等から結果のカウントを取得して返す
     * @param  string $where 検索条件WHERE句（LIMITは無し）
     * @return int    カウント
     */
    public function getCount($where) {return 0;}

    /**
     * ◆virtual
     * DB等から取得した値を表示用に変換する
     * @param  array  $data DB等から取得した値の配列
     * @param  string $key  処理対象のキー
     * @return array  取得した値
     */
    public function getColumnValue($data, $key) {return $data[$key];}
    public function getCsvColumnValue($data, $key)
    {
        if ($key=='div1') {
            return getDiv1NameById($data[$key]);
        }
        if ($key=='div2') {
            return getDiv2NameById($data[$key]);
        }
        if ($key=='div3') {
            return getDiv3NameById($data[$key]);
        }

        return $data[$key];
    }
    /**
     * ◆virtual
     * @return bool 特定の列を非表示にしたい場合、ここでfalseを返す
     */
    public function isVisibleColumn($columnName)
    {
        return  true;
    }

    /**
     * ◆virtual
     * keyにテーブル名など付く場合はここで編集する
     * 例えばJOINしたテーブルでnameをcompany.nameとしたい場合など
     * @param string $key 処理対象のキー
     */
    public function formatOrder($key) {return $key;}

    /**
     * ◆virtual
     * $arrayに[]で追加すると、hiddenを出力する。name=>value
     */
    public function setHiddenValue($array)
    {
        return $array;
    }
}

/**
 * SortTableに関するhtml出力部を扱うクラス。継承して使う
 */
class SortTableView
{
    public function __construct()
    {
        $this->RDTable = new RDTable();
    }
    /**
     * override可
     * ソート用のボタン（昇り順・降順のセット）を返す
     * @param  string $asc   昇り順に並べ替えるためのリンク先
     * @param  string $desc  降り順に並べ替えるためのリンク先
     * @param  string $state ''/ desc/ asc 現在どれが選択されているか
     * @return string html
     */
    public function getSortButton($asc, $desc, $state='')
    {
        $asct = $this->getSortButtonTag($asc, '昇順に並べる', '▲', ($state==='asc'));
        $desct = $this->getSortButtonTag($desc, '降順に並べる', '▼', ($state==='desc'));

        return  <<<__HTML__
            <span class="sort">
                {$asct}
                {$desct}
            </span>
__HTML__;
    }

    //デフォルト表示用の関数
    /**
     * ソートボタンのリンクタグを取得する
     * @param  string $href      リンク先
     * @param  string $title     title属性の文字列
     * @param  string $body      リンク対象の文字列
     * @param  bool   $isChoiced 現在選択されているかどうか
     * @return string html
     */
    public function getSortButtonTag($href, $title, $body, $isChoiced)
    {
        $style = '';
        if ($isChoiced) {
            $style = ' style="color:#999999"';
        }

        return <<<__HTML__
            <a href="{$href}" title="{$title}"{$style}>{$body}</a>
__HTML__;
    }

    /**
     * 最初に開いた時の表示を返す
     * @return string html
     */
    public function getTopHtml()
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
     * 検索結果及び付属の情報の表示を返す
     * @param  object SortTable $sortTable 呼び出し元データへのアクセス
     * @param  string           $body      検索結果の本文
     * @return string           html
     */
    public function getBox(&$sortTable, $body)
    {
        $link = $sortTable->getChangePageLink();
        $next = $sortTable->getNextPageLink($link);
        $prv = $sortTable->getPreviousPageLink($link);
        $navi = $sortTable->getPageNavigateLink($link);

        return <<<__HTML__
<table border>
{$body}
</table>
全{$sortTable->count}件
<br>{$next}
<br>{$navi}
<br>{$prv}
__HTML__;
    }

    /**
     * ページジャンプのリンク表示を返す
     * @param  array  $pages   リンク先の配列。0から順にページ順に並ぶ。
     * @param  int    $nowpage 現在のページ番号が入る
     * @return string html
     */
    public function getPageNavigateLink($pages, $nowpage)
    {
        //ページが1ページしかなければ、表示の必要は無い
        if(count($pages) < 2) return '';
        $navi = new PageNavigater($this->getPageNavigateItem($pages, $nowpage));

        return $navi->toHtml($nowpage);
    }


    /**
     * @return array ページジャンプ用のリンクhtmlの配列
     */
    public function getPageNavigateItem($pages, $nowpage)
    {
        $html = array();
        $no = 0;
        foreach ($pages as $v) {
            $style = '';
            if ($no == $nowpage) {
                $style = ' style="color:#999999"';
            }
            ++$no;
            $html[] = <<<__HTML__
<a href="{$v}#page"{$style}>$no</a>
__HTML__;
        }

        return $html;
    }

    /**
     * 次のページへジャンプするリンク表示を返す
     * @param  string $href  次のページのリンク先
     * @param  int    $limit 次のページの表示数
     * @return string html
     */
    public function getNextPageLink($href, $limit)
    {
        return <<<__HTML__
<a href="{$href}">####next####</a>
__HTML__;
    }

    /**
     * 前のページへジャンプするリンク表示を返す
     * @param  string $href  前のページのリンク先
     * @param  int    $limit 前のページの表示数
     * @return string html
     */
    public function getPreviousPageLink($href, $limit)
    {
        return <<<__HTML__
<a href="{$href}">####prev####</a>
__HTML__;
    }

    /**
     * 次のページが無い時のリンク表示部分への表示
     * @return string html
     */
    public function getNoNextPageLink()
    {
        return '';
    }

    /**
     * 前のページが無い時のリンク表示部分への表示
     * @return string html
     */
    public function getNoPreviousPageLink()
    {
        return '';
    }

    /**
     * 検索結果のヘッダー部分の一行を装飾して返す
     * @param  string $body 検索結果一行分の情報
     * @return string html
     */
    public function getHeaderTr($body)
    {
        return <<<__HTML__
<tr>
{$body}
</tr>
__HTML__;
    }

    /**
     * 検索結果のヘッダー部分の一セルを装飾して返す
     * @param  string $body 検索結果一セル分の情報
     * @return string html
     */
    public function getHeaderTd($body)
    {
        return <<<__HTML__
    <td>{$body}</td>
__HTML__;
    }

    /**
     * 検索結果のデータ部分の一行を装飾して返す
     * @param  string $body 検索結果一行分の情報
     * @return string html
     */
    public function getListTr($body)
    {
        return <<<__HTML__
<tr>
{$body}
</tr>
__HTML__;
    }

    public $colGroup;
    public function setColStyle($name, $param)
    {
        $this->colGroup[$name] = $param;
    }

    public function getColStyle($colname)
    {
        return ($this->colGroup[$colname])? ' '.$this->colGroup[$colname]: '';
    }

    /**
     * 検索結果のデータ部分の一セルを装飾して返す
     * @param  string $body 検索結果一セル分の情報
     * @return string html
     */
    public function getListTd($body, $colname)
    {
        $style = $this->getColStyle($colname);

        return <<<__HTML__
    <td{$style}>{$body}</td>
__HTML__;
    }

    public static function getDownloadHtmlStart()
    {
        $dir_js = DIR_JS;

        return <<<__HTML__
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
    <head>
        <meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <title>ダウンロード</title>
        <meta http-equiv="content-script-type" content="text/javascript">
        <meta http-equiv="content-style-type" content="text/css">
        <script src="{$dir_js}jquery-1.7.1.min.js" type="text/javascript"></script>
        <style>
            a{color:blue}
        </style>
    </head>
    <body bgcolor='#fff0f0'>ダウンロード用データを生成します。しばらくお待ちください。<br>
    <br><br>
    準備が完了しますと、ダウンロードが開始されます。<br>
        <style>
            .graph {
                position: relative;
                width: 400px;
                border: 1px solid #F17662;
                padding: 2px;
            }
            .graph .bar {
                display: block;
                position: relative;
                background: #F17662;
            }
        </style>
        <script>
            function p(p,i,m)
            {
                document.getElementById('table1').style.display="";
                document.getElementById('percent').innerHTML = p + '%' + '('+i+'/'+m+')';
                document.getElementById('bar').style.width = p + '%';
            }
        </script>
        <table style="margin-left:50px;display:none" id="table1">
            <tr><td class="graph"><span class="bar" style="width: 0%;" id="bar"><br></span></td><td id="percent"></td></tr>
        </table>
__HTML__;
    }

    public static function getDownloadHtmlEnd ($additional_params = array())
    {
        $session = array('key' => SESSIONID, 'val' => html_escape(session_id()));
        $params = array(
            'csvdownload_hash' => $_SESSION['csvdownload_hash'],
            $session['key']    => $session['val'],
            'csvdownload'      => '結果をダウンロード',
            'file'             => $file,
            'filename'         => $filename,
            'op'               => 'search',
        );
        if (!empty($additional_params)) {
            $params = array_merge($params, $additional_params);
        }
        $js = '';
        foreach ($params as $name => $val) {
            $js.= ".append($('<input/>', {type: 'hidden', name: '".$name."', value: '".$val."'}))\n";
            $html.= "<input type=\"hidden\" name=\"{$name}\" value=\"{$val}\">";
        }

        return <<<__HTML__
            <br><br>
            ダウンロードの準備が完了致しました。
            <br>
            <form method="post">
                {$html}
                <input type="submit" value="ダウンロードする" name="csvdownload">
            </form>
__HTML__;
//             <script>
//                 window.opener.$('<form/>', {method: 'post', name: 'csv_temp_form'})
//                     {$js}
//                     .appendTo(window.opener.document.body);
//                 window.opener.$('form[name="csv_temp_form"]').submit();
//                 window.opener.$('form[name="csv_temp_form"]').remove();
//                 window.close();
//             </script>
    }
}

/**
 * 0,1,2,3,4,5,6...のstring配列を、現在のページに応じて抜粋したりする
 */
class PageNavigater
{
    public $items;
    /**
     * @param array $items stringの配列。連想配列は×
     */
    public function PageNavigater($items)
    {
        $this->items = $items;
    }

    //表示するページナビの数（現在のページも含む）
    public $totalNum = 11;
    //両端に常に表示する数（片端分）
    public $alwaysShowNum = 2;
    /**
     * @param int $no 中心にする番号
     */
    public function toHtml($no)
    {
        $count = $this->getItemCount();
        $total = $this->totalNum;

        //11ページ以内なら、特に抽出する必要なし
        if($count <= $total) return implode(' ', $this->items);

        /*
         * 現在のページから左右に3ページ分表示するが、
         * 1,2および最終ページ-1、最終ページは常に表示
         * 常に表示する部分にぶつかったら、あまり分を反対側に伸ばす
         * 伸ばした結果5以上になる場合、常に表示する部分を増やす
         *
         */
        $defval = $this->alwaysShowNum;
        list($lsft, $rsft) = $this->getShiftValue ($no);

        //5以上の判断
        $lastno = $count-1;
        $def = array();
        for ($i = 0; $i < $defval; $i++) {
            $def[] = $i;
            $def[] = $lastno - $i;
        }

        //デフォルトによる補正
        $limitreach = 5;
        if ($limitreach < $no - $lsft) {
            $imax = $no - $lsft - $limitreach;
            for ($i = 0; $i < $imax; ++$i) {
                $def[] = $i + $defval;
            }
            $lsft = $no - $limitreach;
        }

        if ($limitreach < $rsft - $no) {
            $imax = $rsft - $no - $limitreach;
            for ($i = 0; $i < $imax; ++$i) {
                $def[] = $lastno - ($i + $defval);
            }
            $rsft = $no + $limitreach;
        }

        $res = array();
        foreach ($this->items as $k => $v) {
            if (in_array($k, $def) || ($lsft <= $k && $k <= $rsft)) {
                $res[$k] = $v;
            }
        }

        return $this->myImplode($res);
    }

    //listで受け取ること
    public function getShiftValue($no)
    {
        $defval = $this->alwaysShowNum;
        $shiftval = ($this->totalNum - 1 - ($defval * 2) ) / 2;
        $lsft = $no - $shiftval;
        $rsft = $no + $shiftval;
        $lastno = $this->getItemCount ()-1;
        //シフト値の調節
        if ($lsft <= $defval) {
            $rsft += $defval - $lsft;
            $lsft = $defval;
        } elseif ($lastno - $defval <= $rsft) {
            $lsft -= $rsft - ($lastno - $defval);
            $rsft = $lastno - $defval;
        }

        return array($lsft, $rsft);
    }

    public $count;
    public function getItemCount()
    {
        if (is_null($this->count)) {
            $this->count = count($this->items);
        }

        return $this->count;
    }


    /**
     * @return string 抽出結果を結合するルールを決める
     */
    public function myImplode($items)
    {
        $res = array();
        $start = 0;
        foreach ($items as $k => $v) {
            if (1 < abs($start - $k)) {
                $res[] = '...';
            }
            $res[] = $v;
            $start = $k;
        }

        return implode(' ', $res);
    }

}

//------------------------------------------------------------------------------

/**
 * POST(GET)を解釈して条件を配列で取得、表示するクラス
 */
class CondTable
{
    public $adapter;
    public $useSession = false;
    public $view;

    public $columns;

    public $post;
    /**
     * @param object CondTableAdapter $adapter アダプタ
     * @param array                   $def     POSTが無い場合のデフォルトPOST値
     */
    public function CondTable(&$adapter, &$view, $useSession=false)
    {
        $this->useSession = $useSession;
        $this->view =& $view;
        $this->view->adapter =& $adapter;
        $this->adapter =& $adapter;
        $this->columns = $adapter->getColumns();
        $this->sessionName = 'Sort_CondTable_'.getPHP_SELF();
    }

    public $cond;
    /**
     * 値から条件を取得してセットする
     * @param array $data POSTから取得したデータ
     */
    public function setCond($data)
    {
        $res = array();
        foreach ($this->getColumns() as $k => $v) {
            $res[$k] = $data[$k];
        }
        $gln = GET_LIMIT_NAME;
        if($data[$gln]) $res[$gln] = $data[$gln];
        $this->cond = $res;
    }

    /**
     * 設定された条件を取得する
     * @return array 条件の配列
     */
    public function getCond()
    {
        return $this->cond;
    }


    public $sessionName = 'Sort_CondTable';
    /**
     * POSTとして扱う配列をPOST,GET,SESSIONから取得
     * メンバ変数の$this->postを取得するという意味ではない
     * @param array $def POSTが無かった場合のデフォルト値
     */
    public function getLikePost ($def = array())
    {
        if ($_GET['op'] === 'search')
            return $_GET;
        if (isset($_POST['op']['search']) || $_POST['op'] === 'search')
            return $_POST;
        if ($this->useSession && ($_GET['op'] === 'back' || $_POST['op'] === 'back'))
            return $_SESSION[$this->sessionName]? $_SESSION[$this->sessionName]: $def;

        return $def;
    }

    /**
     * POSTとして扱う配列をセット
     * @param array $post post
     */
    public function setPost($post)
    {
        $this->post = $post;
        if ($this->useSession) {
            $_SESSION[$this->sessionName] = $this->post;
        }
    }

    public $visible = true;
    /**
     * 表示内容を作成する
     * @return string html
     */
    public function show ($def = array())
    {
        $this->setPost($this->getLikePost($def));
        $this->setCond($this->getValues($this->post));

        if(!$this->visible) return '';
        $hidden = '<input type="hidden" name="op" value="search">';

        //ソート中はソートを保持する
        if (isset($this->post['sort'])) {
            $hidden .= '<input type="hidden" name="sort" value="'.html_escape($this->post['sort']).'">';
            $hidden .= '<input type="hidden" name="desc" value="'.html_escape($this->post['desc']).'">';
        }


        $res = $this->getForms($this->cond);
        $res['表示数'] = $this->view->getLimitSelect($this->cond[GET_LIMIT_NAME]);

        return $this->getBox($res, $hidden);
    }

    /**
     * @param  array $def フォームのデフォルト値
     * @return array テーブル上のタイトル=>フォームの連想配列
     */
    public function getForms ($def=array())
    {
        $res = array();
        $adpt =& $this->adapter;
        foreach ($this->getColumns() as $k => $v) {
            if($w = $adpt->getColumnForms($def, $k)) $res[$v] = $w;
        }

        return $res;
    }

    /**
     * POSTから値を取得する
     * カラム外のPOSTを設定したい場合は、parent（この関数）を呼んだ上で追加記述する
     * @return array カラム名=>値の連想配列
     */
    public function getValues($post)
    {
        $res = array();
        $adpt =& $this->adapter;
        foreach ($this->getColumns() as $k=>$v) {
            $res[$k] = $adpt->getColumnValues($post, $k);
        }
        $gln = GET_LIMIT_NAME;
        $res[$gln] = $post[$gln]? $post[$gln]: $this->getDefaultLimit();

        return $res;
    }

    /**
     * viewとadapterの設定値からデフォルトの表示数を取得する
     * @return int 表示数
     */
    public function getDefaultLimit()
    {
        $list = $this->view->getLimitChoices ();

        return $list[$this->adapter->getDefaultLimitNo()];
    }


    /**
     * @return array カラム名=>説明の連想配列
     */
    public function getColumns() {return$this->columns;}

    /**
     * このテーブルの表示枠を取得する
     * @param  string $body   行部分のhtml
     * @param  string $submit 登録ボタン部分のhtml
     * @return string html
     */
    public function getBox($body, $submit)
    {
        $self = getPHP_SELF();
        $hiddens = array();
        if ($this->useSession) {
            $hiddens[SESSIONID] = html_escape(session_id());
        }
        foreach ($this->adapter->setHiddenValue($hiddens) as $k => $v) {
            $submit .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
        }

        return $this->view->getBox($body, $submit, $self);
    }

}

/**
 * CondTableの設定を扱うクラス。継承して使う
 */
class CondTableAdapter
{
    public $columns = array();
    /**
     * ◆abstract
     * colmuns設定を返す
     * @return array カラム名=>説明の連想配列
     */
    public function getColumns() {return $this->columns;}


    /**
     * ◆abstract
     * @param  array $def フォームのデフォルト値
     * @return array テーブル上のタイトル=>フォームの連想配列
     */
    public function getColumnForms($def, $key) {return null;}

    /**
     * ◆virtual
     * POSTから値を取得する
     * カラム内の値はこちらで取得する
     * @return mixed 値
     */
    public function getColumnValues($post, $key) {return $post[$key];}

    /**
     * ◆virtual
     * viewで設定する表示テーブルのうち何番目をデフォルトとするかを設定
     * @return int viewの表示数テーブルのindex
     */
    public function getDefaultLimitNo() {return 0;}

    public function setHiddenValue($array) {return $array;}
}

/**
 * CondTableのhtml出力を扱うクラス。継承して使う。
 */
class CondTableView
{
    public $adapter;
    public function getBox($row, $hidden, $action)
    {
        $body = $this->getBody($row);
        $submit = $hidden.$this->getSubmitButton();

        return <<<__HTML__
<form action="{$action}" method="post">
<table border>
{$body}
</table>
{$submit}
</form>
__HTML__;
    }

    public function getBody($row)
    {
        $res = '';
        foreach ($row as $k => $v) {
            $res .= $this->getRow($k, $v);
        }

        return $res;
    }

    /**
     * @param string $key   safe html
     * @param string $value safe html
     */
    public function getRow($key, $value)
    {
        return <<<__HTML__
<tr>
    <td>{$key}</td>
    <td>{$value}</td>
</tr>
__HTML__;
    }

    public function getSubmitButton()
    {
        return <<<__HTML__
<div class="button-container float-left"><div class="button-group">
<input type="submit" name="op[search]" value="　　　検索　　　"class="button white">
</div></div>
<div class="button-container float-left" style="padding-left:15px;"><div class="button-group">
<input type="submit" name="csvdownload" value="結果をダウンロード"class="button white">
</div></div>
<div class="clear"></div>
__HTML__;
    }

    /**
     * 表示最大数選択のフォームを得る
     */
    public function getLimitSelect($def)
    {
        $s = array($def => ' selected');
        $c = '';
        foreach ($this->getLimitChoices() as $v) {
            $c .= '<option value="'.$v.'"'.$s[$v].'>'.$v.'</option>';
        }

        return '<select name="'.GET_LIMIT_NAME.'">'.$c.'</select>';
    }

    public function getLimitChoices()
    {
        return array(
            50,
            100,
            150,
            200,
        );
    }
}

//-----------------------------------------------------------------

class SearchList
{
    public $condTable;
    public $resTable;

    public function SearchList(&$condTable, &$sortTable)
    {
        $this->condTable =& $condTable;
        $this->resTable =& $sortTable;
    }

    public function show ($def = array())
    {
        $ct =& $this->condTable;
        $html = $ct->show($def);

        if ($ct->post["csvdownload"]) {
            $this->resTable->downLoadCsv($ct->getCond(), $ct->post);
        }

        if ($ct->post) {
            $this->resTable->setResult($ct->getCond(), $ct->post);
        }

        return $this->getHtml($html, $this->resTable->show());
    }

    public function setUseSession($bool=true)
    {
        $this->condTable->useSession = $bool;
        $this->resTable->useSession = $bool;
    }

    public $showCond = true;
    public $showSort = true;

    public function getHtml($cond, $res)
    {
        if(!$this->showCond) $cond = '';
        if(!$this->showSort) $res = '';

        return <<<__HTML__
{$cond}
{$res}
__HTML__;
    }
}
