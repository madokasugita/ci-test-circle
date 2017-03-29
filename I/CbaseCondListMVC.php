<?php
require_once 'CbaseMVC.php';
require_once 'CbaseFEnquete.php';
require_once 'CbaseFEventDesign.php';
require_once 'CbaseFCheckModule.php';
require_once 'CbaseFError.php';
require_once 'CbaseEnqueteConditions.php';

class CondListModel extends Model
{

}
/*********************************************************************************************/
class CondListView extends View
{
    public function CondListView()
    {
        $this->View('./cond_list_template.html');
    }
    /**
    * テンプレート内の %%%%hoge%%%%を適切なものに置き換えます。
    */
    public function ReplaceHTMLCallBack($match)
    {

        if ($match[1] == 'SUBEVENT_LIST') {
            return $this->getHtmlSubeventList();
        }
        $base['img_path'] = DIR_IMG;
        $base['PHP_SELF'] = PHP_SELF;
        $base['SID'] = getSID();
        $base['evid'] = $this->model->event['evid'];
        $base['rid'] = $this->model->event['rid'];
        $query = Create_QueryString(Get_RandID(8), $this->model->event['rid'], 1, "A");

        $base['prev_url'] = DOMAIN . DIR_MAIN . PG_PREVIEW . '?rid=' . $query;
        $base['test_url'] = DOMAIN . DIR_MAIN . 'test_index.php' . '?' . $query;

        return $base[$match[1]];
    }

    public function getHtmlSubeventList()
    {
        $DIR_IMG = DIR_IMG;
        $html = '';
        $i = 0;
        $c = 1;
        $class_name[0] = "odd";
        $class_name[1] = "even";
        foreach ($this->model->subevents as $subevent) {
            $page = $subevent['page'];
            $next_page = (int) $this->model->subevents[$subevent['next_seid']]['page'];
            $pre_page = (int) $this->model->subevents[$subevent['pre_seid']]['page'];

            if ($page != $pre_page)
                $c = ($c +1) % 2;

            $i++;
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

            if ($subevent['cond'])
                $cond1 = & new Cond1Condition($subevent, $subevent['cond']);
            if ($subevent['cond2'])
                $cond2 = & new Cond2Condition($subevent, $subevent['cond2']);
            if ($subevent['cond3'])
                $cond3 = & new Cond3Condition($subevent, $subevent['cond3']);
            if ($subevent['cond4'])
                $cond4 = & new Cond4Condition($subevent, $subevent['cond4']);
            if ($subevent['cond5'])
                $cond5 = & new Cond5Condition($subevent, $subevent['cond5']);

            $cond3_3 = substr($subevent['cond3'], 0, 3);
            $cond4_3 = substr($subevent['cond4'], 0, 3);

            $is_set_display = ($subevent['cond']);
            $is_set_self = ($subevent['cond2'] || ($cond3_3 && $cond3_3 != 'and' && $cond3_3 != 'or,') || ($cond4_3 && $cond4_3 != 'and' && $cond4_3 != 'or,'));
            $is_set_relation = ($cond3_3 == 'and' || $cond3_3 == 'or,' || $cond4_3 == 'and' || $cond4_3 == 'or,');
            $is_set_filter = ($subevent['cond5']);

            if ($is_set_display) {
                $condstring = $cond1->toString($this->model->subevents);
                $condstring = str_replace('&#39;','&amp;#39;',$condstring);
                $v_display =<<<HTML
              <span onMouseOver="showHelp('<img src={$DIR_IMG}box_top.gif><div class=helpbox-text><img src={$DIR_IMG}box_tale.gif class=helpbox-tale><strong>表示条件設定内容</strong><br><HR size=1 color=#660066 style=border-style:dotted>$condstring<br><br>上記内容を変更する場合には「<img src={$DIR_IMG}check_g.gif>」をクリックしてください。</div><img src={$DIR_IMG}box_bottom.gif>',this,event)" >
              <input name="mode:display:{$subevent['seid']}:" type="image" tabindex="1" value="submit" src="{$DIR_IMG}check_g.gif" />
              </span>
HTML;
            } else {
                $v_display = "";
            }

            $b_display =<<<HTML
                <input name="display[]" type="checkbox" value="{$subevent['seid']}">
HTML;
            //1ページ目はdisplay条件は使えない
            if ($page==1) {
                $b_display = "";
            }






            if ($is_set_self) {
                $condstring = "";
                if ($subevent['cond2'])
                    $condstring .= $cond2->toString($this->model->subevents);
                if ($cond3_3 && $cond3_3 != 'and' && $cond3_3 != 'or,')
                    $condstring .= $cond3->toString($this->model->subevents);
                if ($cond4_3 && $cond4_3 != 'and' && $cond4_3 != 'or,')
                    $condstring .= $cond4->toString($this->model->subevents);

                $condstring = str_replace('&#39;','&amp;#39;',$condstring);
                $v_self =<<<HTML
              <span onMouseOver="showHelp('<img src={$DIR_IMG}box_top.gif><div class=helpbox-text><img src={$DIR_IMG}box_tale.gif class=helpbox-tale><strong>表示条件設定内容</strong><br><HR size=1 color=#660066 style=border-style:dotted>$condstring<br><br>上記内容を変更する場合には「<img src={$DIR_IMG}check_g.gif>」をクリックしてください。</div><img src={$DIR_IMG}box_bottom.gif>',this,event)" >
              <input name="mode:self:{$subevent['seid']}:" type="image" tabindex="1" value="submit" src="{$DIR_IMG}check_g.gif" />
              </span>
HTML;
            } else {
                $v_self = "";
            }




            $b_self =<<<HTML
                <input name="self[]" type="checkbox" value="{$subevent['seid']}">
HTML;
            if (!in_array($subevent['type1'],array(2,3,4)) && $subevent['other'] == 0) {
                $b_self="";
            }





            if ($is_set_relation) {
                $condstring = "";
                if ($cond3_3 == 'and' || $cond3_3 == 'or,')
                    $condstring .= $cond3->toString($this->model->subevents);
                if ($cond4_3 == 'and' || $cond4_3 == 'or,')
                    $condstring .= $cond4->toString($this->model->subevents);
                $condstring = str_replace('&#39;','&amp;#39;',$condstring);
                $v_relation =<<<HTML
              <span onMouseOver="showHelp('<img src={$DIR_IMG}box_top.gif><div class=helpbox-text><img src={$DIR_IMG}box_tale.gif class=helpbox-tale><strong>表示条件設定内容</strong><br><HR size=1 color=#660066 style=border-style:dotted>$condstring<br><br>上記内容を変更する場合には「<img src={$DIR_IMG}check_g.gif>」をクリックしてください。</div><img src={$DIR_IMG}box_bottom.gif>',this,event)" >
              <input name="mode:relation:{$subevent['seid']}:" type="image" tabindex="1" value="submit" src="{$DIR_IMG}check_g.gif" />
              </span>
HTML;
            } else {
                $v_relation = "";
            }
            $b_relation =<<<HTML
                <input name="relation[]" type="checkbox" value="{$subevent['seid']}">
HTML;
            //一問目はrelation条件は使えない
            if ($i == 1) {
                $b_relation = "";
            }
            if (!in_array($subevent['type1'],array(1,2))) {
                $b_relation="";
            }


            if ($is_set_filter) {
                $condstring = $cond5->toString($this->model->subevents);
                $condstring = str_replace('&#39;','&amp;#39;',$condstring);
                $v_filter =<<<HTML
              <span onMouseOver="showHelp('<img src={$DIR_IMG}box_top.gif><div class=helpbox-text><img src={$DIR_IMG}box_tale.gif class=helpbox-tale><strong>表示条件設定内容</strong><br><HR size=1 color=#660066 style=border-style:dotted>$condstring<br><br>上記内容を変更する場合には「<img src={$DIR_IMG}check_g.gif>」をクリックしてください。</div><img src={$DIR_IMG}box_bottom.gif>',this,event)" >
              <input name="mode:filter:{$subevent['seid']}:" type="image" tabindex="1" value="submit" src="{$DIR_IMG}check_g.gif" />
              </span>
HTML;
            } else {
                $v_filter =<<<HTML
<input type="image" name="mode:filter:{$subevent['seid']}:" src="{$DIR_IMG}misettei1.gif" alt="条件設定画面へ" onmouseover="this.src='{$DIR_IMG}misettei2.gif'" onmouseout="this.src='{$DIR_IMG}misettei1.gif'" />
HTML;
            }

            //一問目はfilter条件は使えない
            if ($i == 1) {
                $v_filter = "";
            }
            if ($subevent['type2'] !== 'p') {
                $v_filter="";
            }





            $html .=<<<HTML
          <tr class="{$class_name[$c]}">
            <td align="center"><strong>{$subevent['page']}</strong></td>
            <td class="title"><a href="set_cond.php?{$query}">{$title}</a></td>
            <td align="center">{$subevent['seid']}</td>
            <td class="hissu">{$hissu}</td>
            <td><a href="enq_subevent.php?{$query}" target="_blank" title="質問設定画面へ"><img src="{$DIR_IMG}subevent.gif" alt="質問設定画面へ" onmouseover="this.src='{$DIR_IMG}over.gif'" onmouseout="this.src='{$DIR_IMG}subevent.gif'" /></a></td>
            <td>{$b_display}{$v_display}</td>
            <td>{$b_self}{$v_self}</td>
            <td>{$b_relation}{$v_relation}</td>
            <td align="center">{$v_filter}</td>
          </tr>

HTML;
        }

        return $html;

    }
}
/*********************************************************************************************/
class CondListController extends Controller
{

    public function CondListController(& $model, & $view)
    {
        $this->Controller($model, $view);
        $this->model->loadEnquete($this->request['evid']);
        switch ($this->mode) {
            default :
                ;
        }
    }
}
