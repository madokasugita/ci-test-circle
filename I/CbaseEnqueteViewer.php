<?php
/**
 * 2008/03/28 %%%%BASEDATA:****%%%% で basedata(hiddenValue)の値を表示できるように
 */
require_once 'CbaseFCrypt.php';
require_once 'dom.php';

/**
 * アンケートの表示を行うためのクラス
 */
class EnqueteViewer
{

    //アンケートデータの配列(0=>event 1=>array subevent)
    public $enquete = array();
    //回答データの配列([P_1004]みたいなやつ)
    public $answers = array();
    /**
     * 指定のアンケートと回答で初期化します
     * @param object Enquete $enq 表示するアンケートのクラス
     * @param array          $ans アンケートへ回答の配列。形式はkey=T_seid,P_seidのもの
     */
    public function initialize(&$enq, &$ans)
    {
        $this->enquete =& $enq;
        $this->answers =& $ans;
    }

    public $render;
    /**
     * renderをセットする。デフォルトとは異なるrenderを用いる場合に使う
     * （※使用例はindex.phpやprev.phpを参照）
     * @param object EnqueteRender $render 割り当てるRenderオブジェクト
     */
    public function setRender(&$render)
    {
        $this->render =& $render;
    }

    /**
     * render未設定時はデフォルトを取得するためrender使用時は必ずここから取得する
     * @return object EnqueteRender render
     */
    function &getRender ()
    {
        //setRenderされていないときはデフォルトをセット
        if ($this->render === null) {
            $this->render =& new EnqueteRender();
        }

        return $this->render;
    }

    public $error;
    /**
     * 入力エラーなどを表示したい場合この関数を通してセットする
     * @param array $error 現仕様では配列であればよい。
     *                     ※将来の拡張箇所としては、seid=>エラーとすることで表示位置を質問直下にするなど
     */
    public function setError(&$error)
    {
        $this->error =& $error;
    }

    //TODO:EnquetePageに移動したい
    /**
     * 指定したページの次に質問の現れるページを取得する
     * @param  int   $page     基準となるページ
     * @param  mixed $callback 2ページ移行、移動するたびに呼ばれるコールバック関数
     * @return int   次に表示されることになるページ
     */
    public function nextPage($page, $callback=null)
    {
        ++$page;
        foreach ($this->enquete->getSubEvents() as $vse) {
            if ($page < $vse["page"]) {
                //質問のページが目標のページを上回った時はページを合わせる
                if($callback) call_user_func_array($callback, array($page));
                ++$page;
            }

            if ($page == $vse["page"] && $this->isVisible($vse)) {
                //表示可能質問が一件でもある最初のページを返す
                return $page;
            }
        }

        //次のページが取得できない時の処理
        return $this->onNoNextPage($this->enquete->getEvent(), $page);
    }

    //TODO:EnquetePageに移動したい
    /**
     * 次のページが取得できない場合に呼び出される
     * 基本的には不具合でなければユーザの不正操作（POST改ざんなど）が原因
     * @param array $event 対象イベント
     * @param int   $page  処理を行おうとしたページ番号
     */
    public function onNoNextPage(&$event, $page)
    {
        //TODO:既存では終了扱い(=lastpage)にしていた
//		echo "条件分岐により、表示する質問がひとつもありません。<br>確認ページを作成してください。";
//		exit;
        return false;//$event["lastpage"];
    }

    //TODO:EnquetePageに移動したい
    /**
     * 指定したページの前に質問の現れるページを取得する
     * @param  int   $page     基準となるページ
     * @param  mixed $callback 2ページ移行、移動するたびに呼ばれるコールバック関数
     * @return int   前に表示されることになるページ
     */
    public function previousPage($page, $callback=null)
    {
        $subevents = $this->enquete->getSubEvents();
        while (0 < $page) {
            --$page;
            foreach ($subevents as $vse) {
                if ($page == $vse["page"] && $this->isVisible($vse)) {
                    //表示可能質問が一件でもある最初のページを返す
                    return $page;
                }
            }
            if($callback) call_user_func_array($callback, array($page));
        }

        //前のページが取得できない時の処理
        return $this->onNoPreviousPage($this->enquete->getEvent(), $page);
    }

    //TODO:EnquetePageに移動したい
    /**
     * 前のページが取得できない場合に呼び出される
     * 基本的には不具合でなければユーザの不正操作（POST改ざんなど）が原因
     * @param array $event 対象イベント
     * @param int   $page  処理を行おうとしたページ番号
     */
    public function onNoPreviousPage($event, $page)
    {
        //TODO:一応最初のページに戻すべき？
        echo "前のページが取得できません";
        exit;
//		return 1;
    }

    //TODO:このへん組み方次第でもう少し軽く出来るかも
    //表示用にソートされたSubEventの配列を返す
    /**
     * ランダマイズなどを考慮し表示順にソートしたsubeventの配列を取得する
     * @return array subevent配列の配列
     */
    public function getSortedSubevents()
    {
        //return $_SESSION["ed"][0];
        $event = $this->enquete->getEvent();
        $sortsubevents = randomArraySort($this->enquete->getSubEvents(), $event["randomize"], "subevent");
        if (FError::is($sortsubevents)) {
            echo $sortsubevents->getInfo();
            exit;
        }

        return $sortsubevents["value"];
    }

    /**
     * 指定したページに存在する質問を抜き出す
     * @param  array $subevents 全質問の配列
     * @param  int   $page      指定のページ
     * @return array 抜き出された質問
     */
    public function getQuestions($subevents, $page)
    {
        $res = array();
        foreach ($subevents as $vse) {
            //ページ分割表示
            if ($page == $vse["page"]) {
                $res[] = $vse;
            }
        }

        return $res;
    }

    //TODO:このあたり一括してList.Findみたいにしたい

    /**
     * 指定したページに存在し、表示可能な条件を満たした質問を抜き出す
     * @param  array $subevents 全質問の配列
     * @param  int   $page      指定のページ
     * @return array 抜き出された質問
     */
    public function getVisibleQuestions($subevents, $page)
    {
        $pc = 0; //登録でー多数
        $htmls = array();
        foreach ($this->getQuestions($subevents, $page) as $vse) {
            if ($this->isVisible($vse)) {
                $htmls[] = $vse;
            }
        }

        return $htmls;
    }

    /**
     * 表示内容をフォームに変換して取得する
     * @param  array $subevents 全質問の配列
     * @param  int   $page      指定のページ
     * @return array 抜き出された質問のフォーム配列
     */
    public function getVisibleParts($subevents, $page)
    {
        $pc = 0; //登録でー多数
        $htmls = array();
        $render =& $this->getRender();

        foreach ($this->getVisibleQuestions($subevents, $page) as $v) {
            if ($res = $render->getFormParts($this->enquete, $v, $this->answers)) {
                $htmls[] = $res;
            }
        }

        return $htmls;
    }

    //---表示用の命令

    /**
     * hiddenで渡すアンケートの基本値を取得する。
     * ここで返すのはinputのみでデザインは含めないことが望ましい
     * @param  int    $page 受け渡すページ番号(現在のページ)
     * @return string hiddenのinputhtml
     */
    public function getHiddenParts($page)
    {
        $data['page'] = $page;

        $data = $this->enquete->getHiddenValue($data);
        $data['time'] = time();

        $value = encrypt(serialize($data));
        //name="basedata"がViewer側の都合で名づけられるため、inputタグもここで指定する
        return <<<__HTML__
<input type="hidden" name="basedata" value="{$value}">
__HTML__;
    }

    /**
     * hiddenで渡された基本値を元に戻して取得する
     * getHiddenPartsと対応
     * @param  array $post hiddenを渡したデータ。普通は$_POSTになる。
     * @return array hiddenデータの配列
     */
    public function getHiddenValue($post)
    {
        if(!$post['basedata']) return false;

        return unserialize(decrypt($post['basedata']));
    }

    /**
     * 表示処理を行い表示htmlを返す
     * @param  int    $page 表示するページ
     * @return string html
     */
    public function show($page)
    {
        $render =& $this->getRender();
        $result = $this->getBodyParts ($page, $render);
        //エラーがあれば表示
        $result = $this->showError().$result;
        $html = $render->getCompleteHtml($this->enquete->getEvent(), $result);
        $html = $render->completeRender($html,$this->enquete);// %%%%hoge%%%%を置換する

        return $html;
    }

    /**
     * 表示内容の本文部分を取得する
     * @param  array                $ev     eventのデータ
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               html
     */
    public function getBodyParts($page, &$render)
    {
        $htmlmain = $render->getPartsArea($this->getVisibleParts ($this->getSortedSubevents(), $page));
        $result = "";
        //表示コンテンツがない場合=条件分岐等で以後のページの表示が必要ない場合
        //本来はエラーにすべきだが、既存がヘッダとフッタのみを返しているのでそれに習う
        //エラー表示が必要な場合はelseで対応ください
        if ($htmlmain) {
            $header = $this->getHeaderParts($page, $render);
            $hidden = $this->getHiddenParts($page);
            $footer = $this->getFooterParts($page, $render);

            $result = $render->getFormArea($header . $htmlmain . $hidden . $footer);
        }

        return $result;
    }

    /**
     * エラーがあれば表示する
     * @return string 加工されたエラー文言のhtml
     */
    public function showError()
    {
        if ($this->error) {
            $render =& $this->getRender();

            return $render->getErrorArea($this->enquete->getEvent(), $this->error);
        }

        return '';
    }

    /**
     * 表示内容の本文部分のヘッダを取得する
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               html
     */
    public function getHeaderParts($page, $render)
    {
        $header = "";
        //ページ進行の表示
        if ($GLOBALS['Setting']->useProgressDisplay()) {
            $header .= $this->getProgress($page, $render);
        }

        //途中保存ボタンの表示
        if ($GLOBALS['Setting']->buttonPositionUpper()) {
            $header .= $this->getSaveButton($page, $render);
        }

        return $header;
    }

    /**
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               表示可能であれば途中保存ボタンのhtmlを返す
     */
    public function getSaveButton($page, $render)
    {
        $res = '';
        //1ページ目に出さない時は$page=1を弾く
        if ($this->enquete->isSavable()) {
            // && strstr($_SERVER["HTTP_REFERER"], DIR_MAIN)) { {
            $res .= $render->getSaveButton($page);
        }

        return $res;
    }

    /**
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               表示可能であればページ進行表示のhtmlを返す
     */
    public function getProgress($page, $render)
    {
        $event = $this->enquete->getEvent();
        $now = $page;
        $max = $event["lastpage"];

        //フリースペースを進渉バーに含めないモードならば
        if (PROGRESS_WITHIN_FREE==2) {
            $is_only_free = array();
            foreach ($this->enquete->getSubEvents() as $se) {
                if(is_void($is_only_free[$se['page']]))
                    $is_only_free[$se['page']]=true;
                if($se['type2']!='n')
                    $is_only_free[$se['page']]=false;
            }
            if($is_only_free[$page])

                return "";

            foreach ($is_only_free as $p=>$free) {
                if($p < $page && $free==true)
                    $now -= 1;
                if($free == true)
                    $max -= 1;
            }
        }

        return $render->getProgress($now, $max);
    }

    /**
     * 表示内容の本文部分のフッタを取得する
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               html
     */
    public function getFooterParts($page, $render)
    {
        $footer = "";
        //$footer .= $this->getBackButton($page, $render);
        if ($GLOBALS['Setting']->buttonPositionLower()) {
            $footer .= $this->getSaveButton($page, $render);
        }
        $footer .= $this->getNextButton ($page, $render);

        //form(END)出力
        $footer .= getHiddenSID();

        return $footer;
    }

    /**
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               表示可能であれば戻るボタンを表示するhtmlを返す
     */
    public function getBackButton($page, $render)
    {
        //2ページ目以降なら戻るボタン
        return (1 < $page)? $render->getBackButton($this->enquete->getEvent()): '';
    }

    /**
     * @param  int                  $page   表示するページ
     * @param  object EnqueteRender $render レンダークラス
     * @return string               次へ進むボタンまたは送信ボタンを表示するhtmlを返す
     */
    public function getNextButton($page, $render)
    {
        $event = $this->enquete->getEvent();

        return ($page == $event["lastpage"])?
            //ラストページの時
            $render->getSubmitButton($page):
            $render->getNextButton($event,$page);
    }

    /**
     * ある質問が表示可能な条件を満たしているかを判定
     * @param  array $subevent 処理対象質問
     * @return bool  表示可能ならtrue
     */
    public function isVisible($subevent)
    {
        //Questionクラスのcond判定で普通はok
        $q =& $this->enquete->getQuestion($subevent['seid']);

        return $q->isVisible($this->answers);
    }
}

/**
 * アンケートの表示項目のレンダリングを行うクラス
 * このRenderを入れ替えることで、携帯対応や集計結果表示など、
 * 同じ表示処理方法で違う表示結果を出す場合に対応できる
 */
class EnqueteRender
{
    /**
     * formタグで囲む
     * @param  string $body formで囲まれるべき本文
     * @return string html
     */
    public function getFormArea($body)
    {
        $html = '<form action="' . getPHP_SELF() . '" method="post">';
        $html .= $body;
        $html .= '</form>';

        return $html;
    }

    /**
     * エラー表示スペースを取得
     * @param  array  $event 対象イベント
     * @param  array  $error 発生したエラーの配列
     * @return string html
     */
    public function getErrorArea($event, $error)
    {
        global $GLOBAL_ERROR_SEID;
        //TODO:この処理はプレビューなど継承で実装すべき
        //リファラを見てエラーを非表示にする処理
        //if (0 < $event["flgs"] && !strstr($_SERVER["HTTP_REFERER"], DIR_MAIN))
        //	return "";
        $ERRORMESSAGE_TABLE_WIDTH = ERRORMESSAGE_TABLE_WIDTH;
        $errortext = implode("<br>", $error);

        foreach ($GLOBAL_ERROR_SEID as $seid => $v) {
            foreach (range(0, INPUTER_COUNT) as $i) {
                $seid = adjustSeidByUserType($seid, $i);
                $css.= "#error{$seid}{background-color:#ffdddd}\n";
            }
        }

        $DIR_IMG = DIR_IMG;

        return<<<__HTML__
<br>
<table width="{$ERRORMESSAGE_TABLE_WIDTH}" border="0" cellpadding="7" cellspacing="1" bgcolor="#FFDEAD" style="margin:0px auto" class="errorMessageTable">
<tr>
<td width="100" align="center">
    <img src="{$DIR_IMG}caution.gif" width="20" height="21">
</td>
<td style="text-align:left">
    <font size="2">{$errortext}</font>
</td>
</tr>
</table>
<style>
{$css}</style>
<br>
<script>
$(document).ready(function () {alert('####enq_error####');});
</script>
__HTML__;
    }

    /**
     * 各質問の配列をひとつにまとめる。
     * 必要があれば各質問ごとにテーブルで囲んだりなどができる
     * @param  array  $parts 整形された各質問htmlの配列
     * @return string html
     */
    public function getPartsArea($parts)
    {
        return implode('', $parts);
    }

    /**
     * フォームを生成して返す。空で返る時もある
     * @param  array  $enquete  フォームを作成するアンケート
     * @param  array  $subevent フォームを作成する質問。$enqueteの一部であり冗長
     * @param  array  $answers  回答一覧。$subeventのseidからデフォルト値を取得したりする
     * @return string html
     */
    public function getFormParts(&$enquete, $subevent, &$answers)
    {
        $builder = &$this->getFormBuilder($enquete, $subevent, $answers);
        $html = $builder->render($subevent["html1"], 0);
        $html .= $builder->render($subevent["html2"], 0);

        return $html;
    }

    /**
     * override用。EnqueteFormBuilderを作成して返す
     * （キャッシュ処理などはこの関数の呼び出し側で行う事）
     * @param  array  $enquete  フォームを作成するアンケート
     * @param  array  $subevent フォームを作成する質問。$enqueteの一部であり冗長
     * @param  array  $answers  回答一覧。$subeventのseidからデフォルト値を取得したりする
     * @return object EnqueteFormBuilder
     */
    function &getFormBuilder (&$enquete, $subevent, &$answers)
    {
        return new EnqueteFormBuilder($enquete, $subevent, $answers);
    }

    //途中保存ボタン
    //TODO:途中保存ボタンの引数は何が適切？
    /**
     * 途中保存ボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @return string html
     */
    public function getSaveButton(&$enquete)
    {
        $html0 = "<table width=" . WIDTH_BACKUP . "><tr><td align=right>";
        $html0 .= '<input type="image" src="img/saveSession.gif" name="ss" alt="途中保存">';
        $html0 .= "</td></tr></table>";
        $html0 .= "<br>";
        if (BUTTON_SS_ONCLICK) {
            $html0 = str_replace('<input','<input onclick="'.BUTTON_SS_ONCLICK.'"',$html0);
        } elseif ($enquete->isOpenEnquete()) {
            //オープン途中保存であれば、パスワードについてのアラートを出すが、
            //SS_ONCLICK設定時はそちらを優先する
            $txt = 'ボタン押下後、pwが発行されますので控えて下さい'; //とりあえず原文ママ
            $html0 = str_replace('<input','<input onclick="alert(\''.$txt.'\')"',$html0);
        }

        return $html0;
    }

    /**
     * 現在のページ番号を示すhtmlを取得
     * バーなど様々な表現方法が考えられる
     * @param  int    $page 現在のページ
     * @param  int    $page 全ページ数
     * @return string html
     */
    public function getProgress($page, $maxPage)
    {
        //今までどおりの分数表示
        $htmlD = '<table border=0 cellspacing=0 cellpadding=0 width=' . WIDTH_BACKUP . '><tr><td align=right>';
        $htmlD .= '[' . $page . '/' . $maxPage . 'ページ]';
        $htmlD .= '</td></tr></table>';

        //バーにしてみたりとか(本来は継承してoverrideすべき)
        $barsize = 300;		$size = (int) ($barsize*($page/$maxPage));

return<<<HTML

<div id="progress_bar">
<table>
<tr>
<td nowrap>
[ {$page} / {$maxPage} ]
</td>
<td nowrap>
<div class="progress_container" style="width:{$barsize}px;"><div class="progress" style="width:{$size}px;"></div></div>
</td>
</table>
</div>

HTML;

    }

    /**
     * 戻るボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @param  array  $event 表示対象のイベント（htmlmを持つため）
     * @return string html
     */
    public function getBackButton($event)
    {
        $button = $event["htmlm"];
        if (BUTTON_PB_ONCLICK) {
            $button = str_replace('<input','<input onclick="'.BUTTON_PB_ONCLICK.'"',$button);
        }

        return $button;
    }

    /**
     * 次へボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @param  array  $event 表示対象のイベント（htmlsを持つため）
     * @return string html
     */
    public function getNextButton($event)
    {
        $button = $event["htmls2"];
        if (is_void($button)) {
            $button = str_replace("送信", "次へ",$event["htmls"]);
        }
        if (BUTTON_MAIN_ONCLICK) {
            $button = str_replace('<input','<input onclick="'.BUTTON_MAIN_ONCLICK.'"',$button);
        }

        return $button;
    }

    /**
     * 送信ボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @param  array  $event 表示対象のイベント（htmlsを持つため）
     * @return string html
     */
    public function getSubmitButton($event)
    {
        $button = $event["htmls"];
        if (BUTTON_MAIN2_ONCLICK) {
            $button = str_replace('<input','<input onclick="'.BUTTON_MAIN2_ONCLICK.'"',$button);
        }

        return $button;
    }

    public $isMobile;
    /**
     * 完成した「そのページのhtml」を取得。主にヘッダやフッタを足し、最終処理をする。
     * 完了画面の取得ではないので注意
     * @param  array  $event 処理対象のイベント
     * @param  string $body  他で作られてきた本文のhtml
     * @return string html
     */
    public function getCompleteHtml($event, $body)
    {
        $html = $event["htmlh"];
        $html.= $body;
        //セッション寿命自動延長用javascriptを追加
        if (USE_SESSION_LIFE_TIME_RESET == 1) {
            $html .= getHtmlSessionLifeTimeReset(); //ver1.1/
        }
        $html.= $event["htmlf"];
        //処理結果を携帯用に変換
        if ($this->isMobile) {
            $html = getMobileHtml($html);
        }

        return $html;
    }

    /**
     * ヘッダ.アンケート部.フッタを組み合わせた後に %%%%hoge%%%%を置換する
     * @param  string $format 変換タグの含まれる文章
     * @return string html
     */
    public function completeRender($format,&$enquete)
    {
        $this->enquete = &$enquete;
        $html = preg_replace_callback('/%%%%([a-zA-Z0-9:]+)%%%%/',
            array($this, 'replacePartsComplete'),$format);

        return $html;
    }
    public function replacePartsComplete($match)
    {
        //basedataの置換処理
        //TODO:formbuilder側でBASEDATAの除外処理をしているが、処理統一したい
        if (ereg('BASEDATA:([a-zA-Z0-9]+)',$match[1],$match_)) {
            return 	$this->enquete->basedata[$match_[1]];
        }
    }
}

/*
 * 携帯用画面
 * ※簡易携帯用変換はEnqueteRenderで対応のため、廃止の方向。
 * 携帯用で各パーツごとに表示を変更する必要がある場合は、
 * 必要に応じてRender, Builderで対応してください
 */
class EnqueteMobileRender extends EnqueteRender
{
    public function getCompleteHtml($event, $body)
    {
        //強制的にisMobileをオンにする
        $this->isMobile = true;

        return parent::getCompleteHtml($event, $body);
    }

//	function getSaveButton ()
//	{
//		//携帯版はtype=imageが無効なため
//		$html0 .= '<input type="submit" value="途中保存" name="ss">';
//		$html0 .= "<br>";
//		return $html0;
//	}

}

/**
 * アンケートのレンダリングから呼び出され、タグ部分の置換を担当する
 * このクラスを差し替えれば、inputではなく文字列だけを表示などが可能
 */
class EnqueteFormBuilder
{
    public $enquete;
    public $subevent;
    public $answers;

    public $nowChoice = 0;
    public $nowForm = 0;


    //編集対象のサブイベントを指定
    public function EnqueteFormBuilder(&$enquete, &$subevent, &$answers)
    {
        $this->enquete =& $enquete;
        $this->subevent =& $subevent;
        $this->answers =& $answers;
    }


    /**
     * タグを変換してフォームを作成
     * @param  string $format 変換タグの含まれる文章
     * @param  int    $type   フォームの作成タイプ
     * @return string html
     */
    public function render($format,$type=0)
    {
        $this->nowChoice = 0;
        $this->nowForm = 0;

        //　":" も置き換えられるようにする場合は注意! このreplacePartsCompleteでうまく置き換えできなくなる可能性がでてきます。
        $html = preg_replace_callback('/%%%%([a-zA-Z0-9:_]+)%%%%/',array($this, 'replaceParts'),$format);

        return $html;
    }





    /*
     * *****************************************************
        ReplaceParts()
            フォームのinputタグを構築して返す

            $match -> preg_replace_callbackからの情報
            $array -> formアイテムの定義
            $formc -> form毎の出力済み要素数
                        (どの選択肢を出力するかの判断基準)
            $error -> エラーメッセージの配列
    ******************************************************/
    /**
     * BuildFormから呼ばれて、マッチした文字列に応じて適切なものに置き換える
     * @param  string $match マッチした文字列
     * @return string 置き換え後の文字列(html)
     */
    public function replaceParts($match)
    {

        global $error;
        //pregのマッチ部分の取り出し
        $fn = $match[1];

        //エラーメッセージ出力用
        if (eregi("err",$fn)) {
            $this->makeErrorMessage();
        }

        //選択肢の配列生成
        $choices = getRandomChoices($this->subevent);

        if (!$this->seid2num) {
            $num = 0;
            foreach ($this->enquete->enquete[0] as $v) {
                if($v['seid']%1000==900)
                    continue;
                $this->num2seid[$num] = $v['seid'];
                if($v['type2'] != 'n')
                    $num++;
            }
        }

        //生成
        switch ($fn) {
            case 'title':
                return $this->makeTitle();
            case 'hissu':
                return $this->makeHissu();
            case 'choice':
                return $this->makeChoice($this->nowChoice++, $choices);
            case 'choiceV':
                return Char_ToVert($this->makeChoice($this->nowChoice++, $choices),"<br>");
            case 'other':
                return $this->makeOther();
            case 'form':
                return $this->makeForm($this->nowForm++, $choices);
            case 'seid':
                return $this->subevent['seid'];
            default:
                if (preg_match('/^NUM[0-9:]+$/i', $fn)) {
                    $num = preg_replace('/NUM/i', '', $fn);
                    $num = explode(':', $num);
                    $num[0] = $this->num2seid[$num[0]];

                    return $this->makeAnswer(implode(':', $num));
                }

                if (preg_match('/^TITLE[0-9:]+$/i', $fn)) {
                    $title = preg_replace('/TITLE/i', '', $fn);
                    $title = explode(':', $title);
                    $title[0] = $this->num2seid[$title[0]];
                    $this->getSubeventSelectTitles();

                    return $this->select_titles[$title[0]];
                }

                if (preg_match('/^ID[0-9:]+$/i', $fn)) {
                    return $this->makeAnswer(preg_replace('/ID/i', '', $fn));
                } elseif (substr($fn, 0, 4) == 'sess') {
                    return $this->makeSessionValue(substr($fn, 4));
                } elseif (substr($fn,0,6) == 'other:') {
                    list($em, $id) = explode(':', $fn);

                    return $this->makeOtherAnswer($id);
                } elseif (ereg('BASEDATA',$fn)) {//2008/07/22 BASEDATAだったらそのまま返すように

                    return "%%%%{$fn}%%%%";
                } elseif (preg_match('/^answer:num:[0-9:]+$/i', $fn) && count(explode(':', $fn)) == 4) {
                    return $this->makeAnswerNum(explode(':', $fn));
                }
                //	else if(substr($fn,0,5) == "info:")
                //	{
                //		return ReplaceInfo();
                //	} else {
                    return '<font color="red">%%%%設定エラー%%%%</font>';
                break;
        }
    }

    //
    /*
     * ■■■■■■■■■■■■■■■■■■■■■■■■■■■■■■
     * 以下、make***が返す値はsafe htmlでなければならない
     * （但し既存は格納時にサニタイズしてるっぽいので当面は問題なし）
     *
     *
     */
    /**
     * 質問のタイトル（質問文）を作成
     * @return string 表示する文字列
     */
    public function makeTitle()
    {
// 		return $this->subevent["title"]." ".$this->makeHissu();
        return $this->subevent["title"];
    }

    public function makeHissu()
    {
        return ($this->subevent["hissu"])? HISSU_MARK: '';
    }

    /**
     * セッションの値表示を作成する
     * @param  string $key 表示させたいキー
     * @return string 表示する文字列
     */
    public function makeSessionValue($key)
    {
        return $_SESSION[$key];
    }

    /**
     * 選択肢のvalue文字列を作成する
     * @param  array  $choiceValues 選択肢の配列
     * @return string 表示する文字列
     */
    public function makeChoice($no, $choices)
    {
        $keys = $choices['key'];
        $values = $choices['value'];
        if(!$keys[$no] && $keys[$no] !== 0) return;
        $seid = $this->subevent["seid"];

        return '<label for="'.$seid.'_'.$keys[$no].'">'.$values[$no].'</label>';
    }

    /**
     * 回答表示を作成する
     * @param  int    $id 表示する回答のseid
     * @return string 表示する文字列
     */
    public function makeAnswer($id)
    {
        $key = $id;
        //TODO:正確を期すならsubevent見たほうがいいかもしれません
        //テキスト回答を返す
        if (!is_null($this->answers["T_".$key])) {
            return nl2br(transHtmlentities($this->answers["T_".$key]));
        }
        //選択肢データを取得
        $target = $this->enquete->getBySeid($key);


        //pulldown回答を返す
        //radio,checkbox回答を返す
        $strPChoice = $this->answers["P_".$key];
        if (!$strPChoice && $strPChoice != "0") return;


        $tchoice = getEnqueteChoice($target, $this->enquete->respondent);

        //回答選択肢を展開
        $tval=array();
        if (!is_array($strPChoice)) {
            $tval[] = $tchoice[$strPChoice];
        } else {
            foreach ($strPChoice as $ans) {
                $tval[] = $tchoice[$ans];
            }
        }

        return transHtmlentities(implode(",", $tval));
    }

    /**
     * 回答表示を作成する
     * @param  int    $id 表示する回答のseid
     * @return string 表示する文字列
     */
    public function makeOtherAnswer($id)
    {
        $key = $id;
        //テキスト回答を返す
        if (!is_null($this->answers["E_".$key])) {
            return nl2br(transHtmlentities($this->answers["E_".$key]));
        }
        //TODO:正確を期すならsubevent見たほうがいいかもしれません
        //otherフラグが0ならエラー返すとか
        return '';

    }

    /**
     * エラーメッセージ表示箇所を作成
     * @return string html
     */
    public function makeErrorMessage()
    {
        global $error;
        $msg = "";
        if ($error != "") {
            foreach ($error as $er) {
                $msg .= '<li>'.$er.'</li>';
            }
        }

        return $msg;
    }

    /**
     * その他欄を作成
     */
    public function makeOther()
    {
        $seid = $this->subevent["seid"];

        return $this->makeTextQuestion("E_".$seid);
    }

    public function makeForm($now, $choices)
    {
        $form = QuestionType::getForm($this->subevent, $now, $choices);

        return $form->get($this->answers);
    }

    public function getSubeventSelectTitles()
    {
        if (!empty($this->select_titles)) {
            return;
        }
        foreach ($this->enquete->enquete[0] as $v) {
            $this->select_titles[$v['seid']] = $v['title'];
        }

        return;
    }

    /**
     */
    public function getVisibleChoices()
    {
        //Questionクラスのcond判定で普通はok
        $q =& $this->enquete->getQuestion($this->subevent['seid']);

        return $q->getVisibleChoices($this->answers);
    }

    /**
     * テキスト回答のフォームを作る
     * @param  array  $addParam styleなどの追加値。OtherとTextの時、textでのみextをつかうため。
     * @return string safety html
     */
    public function makeTextQuestion ($answerKey, $addParams=array())
    {
        //TODO:上位でhtmlspecialcharなどをつかっているため、安全が保証されない
        //フォーム名
        $parts = array('name="'.$answerKey.'"');
        //スタイルシート
        //if ($array[$fn]["style"]) $parts[] = " style=".$array[$fn]["style"];
        foreach ($addParams as $v) {
            $parts[] = $v;
        }

        if ($this->subevent["rows"] <= 1) {
            $parts[] = 'type="text"';
            $parts[] = 'size="'.$this->subevent["width"].'"';
            $parts[] = 'value="'.transHtmlentities($this->answers[$answerKey]).'"';

            //タグ展開
            $tag = '<input '.implode(" ", $parts).'>';
        } else {
            $parts[] = 'cols="'.$this->subevent["width"].'"';
            $parts[] = 'rows="'.$this->subevent["rows"].'"';
            $tag = '<textarea '.implode(" ", $parts).'>'
                .transHtmlentities($this->answers[$answerKey])
                .'</textarea>';
        }

        return $tag;
    }

    /**
     * 他の回答を差し込み
     * @param  array  $answerNum %%%%answer:num:1:2%%%%の文字列を分解した配列
     * @return string safety html
     */
    public function makeAnswerNum($answerNum)
    {
        if (!$this->ea) {
            $this->ea = new EnqueteAnswer();
        }
        $str       = "";
        $userType  = $answerNum[2];
        $num       = $answerNum[3];
        $target    = $this->enquete->respondent['targets'];
        $target    = array_shift($target);
        $sheetType = getSheetTypeByEvid($this->subevent['evid']);
        $evid      = getEvidBySheetTypeAndUserType($sheetType, $userType);

        if (!isset($this->_answerNumArray[$evid])) {
            $result = $this->ea->getAnswerNumSubEventData($evid, $target['target']);
            if (is_array($result)) {
                foreach ($result as $v) {
                    $this->_answerNumArray[$evid][$v['num']][]= $v;
                }
            }
        }

        if (empty($this->_answerNumArray[$evid][$num])) {
            return '';
        }

        $str = array();
        foreach ($this->_answerNumArray[$evid][$num] as $v) {
            if (isset($event_data_id) && $event_data_id != $v['event_data_id'])
                continue;

            $str[] = _360_EnqueteFormBuilder::makeAnswer_child(
                $target['target'],
                $v['seid'],
                QuestionType::getValueByDB(array(0 => $v), $this->enquete, true),
                true
            );
            $event_data_id = $v['event_data_id'];
        }

        return implode('<br />', $str);
    }

}

/**
 * プレビュー用に置換値を文字で表示できる。prev,enq_ttlで使用
 */
class EnquetePrevFormBuilder extends EnqueteFormBuilder
{
    /**
     * セッションの値表示を作成する
     * @param  string $key 表示させたいキー
     * @return string 表示する文字列
     */
    public function makeSessionValue($key)
    {
        return '<i>[セッション"'.$key.'"の値]</i>';
    }

    /**
     * 回答表示を作成する
     * @param  int    $id 表示する回答のseid
     * @return string 表示する文字列
     */
    public function makeOtherAnswer($id)
    {
        $target = $this->enquete->getBySeid($id);
        if(!$target) return '<i>[id:'.$id.'記入欄への回答(<font color="red">エラー：存在しない質問</font>)]</i>';

        return '<i>[id:'.$id.'"'.$target['title'].'"記入欄への回答]</i>';


    }

    /**
     * 回答表示を作成する
     * @param  int    $id 表示する回答のseid
     * @return string 表示する文字列
     */
    public function makeAnswer($id)
    {
        $target = $this->enquete->getBySeid($id);
        if(!$target) return '<i>[id:'.$id.'への回答(<font color="red">エラー：存在しない質問</font>)]</i>';

        return '<i>[id:'.$id.'"'.$target['title'].'"への回答]</i>';
    }
}

/**
 * セッション寿命延長用のjavascirptを返す
 *
 */
function getHtmlSessionLifeTimeReset()
{
    //ver1.1/
    $SESSION_RESET_MINUTES = SESSION_RESET_MINUTES;
    $SID = getSID();
    $DIR_JS = DIR_JS;

    return<<<HTML
<script type="text/javascript" src="{$DIR_JS}session_life_time_reset.js"></script>
<script type="text/javascript">setSessionLifeTimeReset("{$SID}",{$SESSION_RESET_MINUTES});</script>
HTML;
}
