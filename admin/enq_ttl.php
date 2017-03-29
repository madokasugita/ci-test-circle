<?php
define('NOT_USE_CHACHE',1);//キャッシュを読まない

define('DIR_ROOT', '../');
require_once 'cbase/crm_define3.php';
require_once (DIR_ROOT.'crm_define.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFEvent.php');
require_once (DIR_LIB . 'CbaseFDBClass.php');
require_once (DIR_LIB . 'CbaseFError.php');
require_once (DIR_LIB . 'CbaseFNoDuplication.php');
require_once (DIR_LIB . 'CbaseFUser.php');
require_once (DIR_LIB . 'D.php');
require_once (DIR_LIB . 'CbaseFCheckModule.php');
require_once (DIR_LIB . 'CbaseDAO.php');
require_once (DIR_LIB . 'CbaseFEnqueteCache.php');
require_once (DIR_LIB . 'CbaseFEnquete.php');
require_once (DIR_LIB . 'CbaseEnquete.php');
require_once (DIR_LIB . 'CbaseEnqueteAnswer.php');
require_once (DIR_LIB . 'CbaseEnqueteViewer.php');
require_once (DIR_LIB . 'CbaseEnqueteControler.php');
require_once (DIR_LIB . 'CbaseEncoding.php');

class EnqueteTotalControler extends EnqueteControler
{
    public function show()
    {
        $this->setEnquete(Enquete :: fromQuery($_GET['rid']));
        $event = & $this->enquete->getEvent();
        $this->viewer->initialize($this->enquete, $this->getAnswers());

        return $this->viewer->show(0);
    }
}

/**
 * 全質問を無条件に表示するViewer
 */
class EnqueteAllViewer extends EnqueteViewer
{
    /**
     * 指定したページに存在し、表示可能な条件を満たした質問を抜き出す
     * @param  array $subevents 全質問の配列
     * @param  int   $page      指定のページ
     * @return array 抜き出された質問
     */
    public function getVisibleQuestions($subevents, $page)
    {
        //無条件に全ての質問を表示
        return $this->getQuestions($subevents, $page);
    }

    public function getBodyParts($page, $render)
    {
        $ev = $this->enquete->getEvent();
        $res = '';
        for ($i = 1; $i <= $ev['lastpage']; $i++) {
            $res .= parent::getBodyParts ($i, $render);
        }

        return $res;
    }

    public function showError()
    {
        //エラーは出さない
        return '';
    }
}

class EnqueteTotalRender extends EnqueteRender
{
    /**
     * formタグで囲む
     * @param  string $body formで囲まれるべき本文
     * @return string html
     */
    public function getFormArea($body)
    {
        //集計では囲まない
        return $body;
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
        $htmlD = '<div style="width:' . WIDTH_BACKUP . 'px;text-align:right;padding:3px;margin:1em 0px">';
        $htmlD .= '[' . $page . '/' . $maxPage . 'ページ]';
        $htmlD .= '</div>';

        return $htmlD;
    }

    /**
     * 途中保存ボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @return string html
     */
    public function getSaveButton()
    {
        return '';
    }

    /**
     * 戻るボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @param  array  $event 表示対象のイベント（htmlmを持つため）
     * @return string html
     */
    public function getBackButton($event)
    {
        return '';
    }

    /**
     * 次へボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @param  array  $event 表示対象のイベント（htmlsを持つため）
     * @return string html
     */
    public function getNextButton($event)
    {
        return '';
    }

    /**
     * 送信ボタンのhtmlを取得
     * なおimageボタンでもsubmitボタンでも動作する
     * @param  array  $event 表示対象のイベント（htmlsを持つため）
     * @return string html
     */
    public function getSubmitButton($event)
    {
        return '<br>';
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
        return new EnqueteTotalFormBuilder($enquete, $subevent, $answers);
    }
}

class EnqueteTotalFormBuilder extends EnquetePrevFormBuilder
{

    /**
     * 選択肢のvalue文字列を作成する
     * @param  array  $choiceValues 選択肢の配列
     * @return string 表示する文字列
     */
    public function makeChoice($no, $choices)
    {
        return parent::makeChoice($no, $this->resortChoice($choices));
    }

    /**
     * ランダムソートを無効にする
     */
    public function resortChoice($choices)
    {
        $keys = array_flip($choices['key']);
        $c = array();
        foreach ($keys as $no) {
            $c['key'][$no] = $no;
            $c['value'][$no] = $choices['value'][$keys[$no]];
        }

        return $c;
    }

    /**
     * その他欄を作成
     */
    public function makeOther()
    {
        return "記入回答";
    }

    //フォームの作成
    public function makeForm($now, $choices)
    {
        $choices = $this->resortChoice($choices);
        $choiceKeys = array_flip($choices['key']);
        $ca = $choices['value'];
        $seid = $this->subevent["seid"];

        //////////////////結果取得
        if (QuestionType::isSettableChoice($this->subevent)) {
            //$seid;
            $dar = getTotalSubevent($this->subevent);
        }

        $tc = $dar ? array_sum($dar) : 0;

        //個別対応
        switch ($this->subevent["type2"]) {
            case "t":
                $SID = getSID();
                $tag .=<<<HTML
<a href="enq_ttl_fa_view.php?{$SID}&seid={$seid}" target="_blank">内容を見る</a>
HTML;
                break;
            case "p":
                //フォーム名
                $parts = array('name="'.$seid.'"');

                //スタイルシート/JavaScript
                if ($this->subevent["ext"]) {
                    $parts[] = $this->subevent["ext"];
                }

                //optionを取得
                $opts = "";
                foreach ($choiceKeys as $v) {
                    $val = $dar[$v]? $dar[$v]: 0;
                    //$sel =  (!is_null($answer) && $choiceKeys[$v] == $answer)? ' selected': '';
                    $choice = ($v == 9999)? "該当なし": $ca[$v];
                    $tmprate = $this->calcPercent($val, $tc);
                    $opts .= '<option value="'.$choiceKeys[$v].'">'.$choice.'(' . $val. '回答:'.$tmprate.'%)'.'</option>';
                }

                //selectを整形
                $tag.= '<select '.implode(" ", $parts).'>';
                $tag.= '<option value="ng">'.TEXT_PULLDOWN_DEFAULT.'</option>';
                $tag.= $opts.'</select>';
                break;
            case "c" :
            case "r" :
                //$tag .= $this->makeSelectableQuestion('checkbox', $seid, $choiceKeys);
                $v = $choiceKeys[$now];
                $val = $dar[$v]? $dar[$v]: 0;
                $tmprate = $this->calcPercent($val, $tc);
                $bgc = transGetBgColor($tmprate);
                $tag .= '<font size=2>' . $val . '</font><br>';
                $tag .= '<font size=2 color="' . $bgc . '">' .$tmprate. '%</font>';
                break;

        }

        //リターン
        return '<font color=green>' . $tag . '</font>';
    }

    public function calcPercent($num, $max, $format="%01.1f")
    {
        return sprintf($format, (($max ? $num / $max : 0) * 100));
    }

    /**
     */
    public function getVisibleChoices()
    {
        //常に全部返す
        return array_keys(explode(',', $this->subevent['choice']));
    }

}

//TODO:Enqueteクラスか、集計クラス？などに統合してください
function getTotalSubevent($subevent)
{
    global $total_subevent_values;
    if (is_null($total_subevent_values)) {
        $res = FDB::getAssoc("
SELECT seid, choice, COUNT(choice) as count
FROM ".T_EVENT_SUB_DATA." a
    INNER JOIN ".T_EVENT_DATA." b ON a.event_data_id = b.event_data_id
where b.evid = ".FDB::escape($subevent['evid'])." and answer_state = 0 group by seid, choice");
        $set = array();
        foreach ($res as $v) {
            $set[$v['seid']][$v['choice']] = $v['count'];
        }
        $total_subevent_values = $set;
    }

    return $total_subevent_values[$subevent['seid']];

}

//◆クラスとmainを同時記述しているための回避処理
if (!defined('TESTCASE')) {

encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

$arySess = $_SESSION;

/************************************************************************************************************/

$viewer = & new EnqueteAllViewer();
$viewer->setRender(new EnqueteTotalRender());

//$controler = & new EnquetePrevControler($viewer);
$controler = & new EnqueteTotalControler($viewer);
$html = $controler->show();

echo $html;
exit;

/************************************************************************************************************/

$_SESSION = $arySess;

} //◆クラスとmainを同時記述しているための回避処理
