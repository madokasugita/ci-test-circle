<?php
require_once 'CbaseMVC.php';
require_once 'CbaseFEnquete.php';
require_once 'CbaseFEventDesign.php';
require_once 'CbaseFCheckModule.php';
require_once 'CbaseFError.php';
class EnqListModel extends Model
{
    public function pageChange(& $request)
    {
        $evid = (int) $request['evid'];
        $seid = (int) $request['seid'];

        $data = array ();

        if ($request['pagenew_x'] || $request['pagenew']) {
            $data['page'] = 'page + 1';
            FDB :: update(T_EVENT_SUB, $data, "where evid = {$evid} and seid >= {$seid}");
        }
        if ($request['pagedel']) {
            $data['page'] = 'page - 1';
            FDB :: update(T_EVENT_SUB, $data, "where evid = {$evid} and seid >= {$seid}");
        }
        if ($request['pageup_x'] || $request['pageup']) {
            $data['page'] = 'page - 1';
            FDB :: update(T_EVENT_SUB, $data, "where seid = {$seid}");
        }
        if ($request['pagedown_x'] || $request['pagedown']) {
            $data['page'] = 'page + 1';
            FDB :: update(T_EVENT_SUB, $data, "where seid = {$seid}");
        }
        correctPageNumber($evid);

        //最大ページ数更新処理(event.lastpage)
        $sql = "UPDATE event set lastpage = (select max(page) from subevent where evid = {$evid}) where evid = {$evid}";
        FDB :: sql($sql,true);
    }

}
/*********************************************************************************************/
class EnqListView extends View
{
    public function EnqListView()
    {
        $this->View('./enq_list_template.html');
    }

    /**
    * テンプレート内の %%%%hoge%%%%を適切なものに置き換えます。
    */
    public function ReplaceHTMLCallBack($match)
    {
        $keys = explode(':', $match[1]);

        if ($keys[0] == 'SUBEVENT_LIST') {
            return $this->getHtmlSubeventList();
        }
        if ($keys[0] == 'SET_RANDOMIZE') {
            return $this->getHtmlSetRandomize();
        }

        $base['PHP_SELF'] = PHP_SELF;
        $base['evid'] = html_escape(EVENT_ID);

        $base['basic_button'] = '<input type="button" name="basic_button" onclick="location.href=\'enq_event.php?evid='.$base['evid'].'&'.getSID().'\'" value="基本設定画面へ" >';

        if(OPTION_ENQ_COND !=1)
            $disabled = ' disabled';
        else
            $disabled = '';

        $money_mark = getMoneyMark();

        $base['condition_button'] = '<input type="button" name="condition_button" onclick="location.href=\'cond_list.php?evid='.$base['evid'].'&'.getSID().'\'" value="条件設定一覧画面へ" '.$disabled.'>'.$money_mark;

        if (count($keys) <=  2)
            return $base[$keys[0]];

        if ($keys[0] == 'error') {
            return '<span style="color:red;font-size:10px">' . $this->model->error[$keys[1]] . '</span>';
        } elseif ($keys[0] == 'event') {
            $val = $this->model->subevents[$keys[1]][$keys[2]];
        } else {
            $val = $this->model-> $keys[0][$keys[1]];
        }

        return  transHtmlentities($val);
    }

    public function getHtmlSubeventList()
    {
        $DIR_IMG = DIR_IMG;
        $button_new =<<<HTML
<input onclick="return this.flag?false:this.flag=true;" name="pagenew" type="image" tabindex="1" onmouseover="this.src='{$DIR_IMG}pagechg.gif'" onmouseout="this.src='{$DIR_IMG}pagechg.gif'" value="submit" src="{$DIR_IMG}pagechg.gif" />
HTML;
        $button_del =<<<HTML
<input onclick="return this.flag?false:this.flag=true;" name="pagedel" type="submit" tabindex="1" value="削除" style="background-color:#de598f;color:white;padding:1px 4px;font-size:9px"/>
HTML;
        $button_up =<<<HTML
<input onclick="return this.flag?false:this.flag=true;" name="pageup" type="image" tabindex="1" onmouseover="this.src='{$DIR_IMG}pageup2.gif'" onmouseout="this.src='{$DIR_IMG}pageup.gif'" value="submit" src="{$DIR_IMG}pageup.gif" />
HTML;
        $button_down =<<<HTML
<input onclick="return this.flag?false:this.flag=true;" name="pagedown" type="image" tabindex="1" onmouseover="this.src='{$DIR_IMG}pagedwn2.gif'" onmouseout="this.src='{$DIR_IMG}pagedwn.gif'" value="submit" src="{$DIR_IMG}pagedwn.gif" />
HTML;

        $html = '';
        $i = 0;
        $c = 1;

        foreach ($this->model->subevents as $subevent) {
            $page = $subevent['page'];
            $next_page = (int) $this->model->subevents[$subevent['next_seid']]['page'];
            $pre_page = (int) $this->model->subevents[$subevent['pre_seid']]['page'];

            if($page!=$pre_page)
                $c = ($c+1) % 2;
            $j=$i;
            $i++;
            $action = PHP_SELF . '&evid=' . $subevent['evid'];
            $query = getSID() . '&seid=' . $subevent['seid'] . '&evid=' . $subevent['evid'];
            $title = strip_tags($subevent['title']);
            if ($title === '') {
                $title = '[空欄]';
            }
            $title = mb_strimwidth($title, 0, 80, '...', INTERNAL_ENCODE);

            if ($subevent['hissu'])
                $hissu = '〇';
            else
                $hissu = '';

            if ($page == $pre_page)
                $b1 = $button_new;
            else if($page != 1)
                $b1 = $button_del;
            else
                $b1 = '';

            if ($page != $pre_page && $pre_page)
                $b2 = $button_up;
            else
                $b2 = '';

            if ($page != $next_page && $page == $pre_page && $next_page)
                $b3 = $button_down;
            else
                $b3 = '';

            $html .=<<<HTML
        <tr class="color{$c}">
            <form action="{$action}" method="post">
            <input type="hidden" name="seid" value="{$subevent['seid']}">
            <input type="hidden" name="mode" value="pagechg">
            <td align="center">
                <strong>{$subevent['page']}</strong>
            </td>
            <td>
                {$b1}
            </td>
            <td>
                {$b2}
            </td>
            <td>
                {$b3}
            </td>
            <td align="center">
                {$j}
            </td>
            <td class="title">
                {$title}
            </td>
            <td>
                {$subevent['seid']}
            </td>
            <td class="hissu">
                {$hissu}
            </td>
            <td>
                <a href="enq_subevent.php?{$query}" target="_blank" title="質問設定画面へ"><img src="{$DIR_IMG}subevent.gif" alt="質問設定画面へ" onmouseover="this.src='{$DIR_IMG}over.gif'" onmouseout="this.src='{$DIR_IMG}subevent.gif'" /></a>
            </td>
            </form>
        </tr>
HTML;
        }

        return $html;

    }

    public function getHtmlSetRandomize()
    {
        if (OPTION_ENQ_RANDOMIZE!=1) {
                return "";
        }
        if (is_good($_POST['set_randomize'])) {
            $rs = randomArraySort($this->model->subevents, $_POST['randomize'], "subevent");
            if (FError::is($rs)) {
                $msg = '<span style="color:#ff0000;">指定が不正です。</span>';
            } else {
                $event = array();
                $event['evid'] = EVENT_ID;
                $event['randomize'] = $_POST['randomize'];
                Save_Enquete("update", $event);
                $this->model->loadEnquete(EVENT_ID);
                $msg = '<span style="color:#0000ff;">設定しました。</span>';
            }
        }

        $PHP_SELF = PHP_SELF;
        $evid = html_escape(EVENT_ID);
        $money_mark = getMoneyMark();
        $randomize = html_escape($this->model->event['randomize']);

            return <<<__HTML__
<tr>
  <td colspan="9">
  <form action="{$PHP_SELF}&evid={$evid}" method="POST" style="display:inline;">
  質問順序ランダム設定：
  <input type="text" name="randomize" value="{$randomize}" style="text-align:left;">
  <input type="submit" name="set_randomize" value="設定"> {$money_mark}
  </form>
  {$msg}
  <div style="font-size:smaller;">※開始番号-終了番号,開始番号2-終了番号2,…のフォーマットで記述</div>
  </td>
</tr>
__HTML__;
    }
}
/*********************************************************************************************/
class EnqListController extends Controller
{

    public function EnqListController(& $model, & $view)
    {
        $this->Controller($model, $view);
        $this->model->loadEnquete($this->request['evid']);
        switch ($this->mode) {
            case 'pagechg' :
                $this->model->pageChange($this->request);
                $this->model->loadEnquete($this->request['evid']);
                break;

            default :
                ;
        }
    }
}
