<?php
//define('DEBUG', 1);
define('NOT_CONVERT',1);
/** path */
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
include_once (DIR_LIB . 'CbaseFForm.php');
include_once (DIR_LIB . 'CbaseFManage.php');
include_once (DIR_LIB . 'CbaseMVC.php');
include_once (DIR_LIB . 'CbaseFEnquete.php');
include_once (DIR_LIB . 'CbaseFCheckModule.php');
include_once (DIR_LIB . 'CbaseEnqueteConditions.php');
include_once (DIR_LIB . 'QuestionType.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . '360_EnqueteRelace.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
Check_AuthMng(basename(__FILE__));
define('PHP_SELF', getPHP_SELF()."?".getSID());

//POST時、もしPOSTからモードが取れればそのモード実行
//seidはmodeの１を取る。なければモード名を取る。それもなければエラー
//mode
// 表示上件:display
// 自問:self
// 連携：relation
//絞込み：filter
//条件編集：それぞれ条件に応じたコマンドをつけておく
//条件削除：delete チェックボックスの引数はcond5とかで
//次へ：next　プルダウンからコマンドを取り以下同じ

/*
 * subeventをつっこむと以下を返す関数
 * seidひとつの場合
 * 　置換項目を表示する
 * 　設定内容を作る
 * seid複数の場合
 * 　リストを表示する
 * 各コマンドの中身を作る（STEP2のウィンドウはクラス）
 * 表示（デフォルトつき）・POSTされたときの処理
 */

//TODO:MVCの使い方が適当なのでいずれリファクタリングすること
class SetCondPage extends Controller
{
    public $mode;
    public $seids;
    public function setup($post, $get)
    {
        $this->post = $post;
        list($mode, $seids) = $this->getMode($post);
        if (!$mode) {
            $mode = 'first';
            $seids = array ($get['seid']);
            $post['evid'] = $get['evid'];

        } elseif (!$seids || in_array($seids, array ('_x', '_y'))) {
            $seids = $post['seids'] ? $post['seids'] : $post[$mode];
        } else {
            $seids = array ($seids);
        }
        $this->mode = $mode;
        if ($mode === 'back') {
            $this->jumpCondList($post['evid']);
        }
        if (!$seids || !$post['evid']) {
            $this->onNoSeid($post['evid']);
            //raise error
            return false;
        }
        $this->seids = $seids;
        $this->setEnquete($post['evid']);

        return true;
    }

    public function update($post, $get)
    {
        if (!$this->setup($post, $get)) return;

        //STEP２
        $step2 = $this->getStepTwo();

        //seidに応じてヘッダのメニューを出す
        $header = $this->getHeader();

        //STEP１
        $step1 = $this->getStepOne();

        $seids = $this->getSeidsTag();

        $action = PHP_SELF;

        return<<<__HTML__
<form action="{$action}" method="post">
{$header}
      <br> {$step1}
{$step2}
      <br> <br> <br />{$seids}
</form>
__HTML__;
    }

    public function jumpCondList($evid)
    {
        header('location: cond_list.php?' . getSID() . '&evid=' . $evid);
        exit;
    }

    public function setEnquete($evid)
    {
        $this->evid = $evid;
        $this->model->loadEnquete($evid);
    }



    public function isFirst()
    {
        return in_array($this->mode, array ('first', 'delete'));
    }

    public function getHeader()
    {
        $page = $this->isFirst() ? 1 : 2;
        $main = count($this->seids) == 1 ? $this->getSingularHeader() : $this->getPluralHeader();

        return $this->view->getMainDiv(array(
                '質問条件分岐設定ウィザード（'.$page.'/2）',
                $main
            ),
            array(
                array('tr'=> 'class="tr1"', 'td'=>'height="40"'),
                array('tr'=> 'class="titlebg"')
            )
        );
    }

    /**
     * ターゲットのseを取得する
     */
    public function getSubevents()
    {
        $res = array ();
        foreach ($this->model->subevents as $v) {
            if (in_array($v['seid'], $this->seids)) {
                $res[] = $v;
            }
        }

        return $res;
    }

    public function formatQuestionTitle($subevent)
    {
        return mb_strimwidth(getPlainText($subevent['title']), 0, 44, '...');
    }

    public function getSingularHeader()
    {
        $se = $this->getSubevents();
        $se = $se[0];
        $e = $this->model->event;
        $type = $this->getSubeventType($se);
        $title = $this->formatQuestionTitle($se);
        $cond = $this->getConditionArea();
        if ($cond) {
            $input =<<<__SUBMIT__
<br><input type="submit" name="mode:delete:" value="　チェックした設定条件を削除する　" onclick="return confirm('チェックした設定条件を削除します')">
__SUBMIT__;
        } else {
            $cond = 'この質問に設定されている条件はありません';
            $input = '';
        }
        $src = DIR_IMG;

        return<<<__HTML__
<table width="580" border="0" cellpadding="6" cellspacing="0">
              <tr>
                <td width="80" nowrap>質問ID<img src="{$src}cond_1.gif" width="13" height="15" class="cond1"><strong>{$se['seid']}</strong></td>
                <td>頁設定<span align="left"><img src="{$src}cond_1.gif" width="13" height="15" class="cond1"><strong style="font-size:14px;color:#ee0000;">{$se['page']}</strong>/{$e['lastpage']}
                  page</span></td>
                <td align="right">質問属性<img src="{$src}cond_1.gif" width="13" height="15" class="cond1"><span style="color:#ee0000;">{$type}</span></td>
              </tr>
              <tr>
                <td colspan="3">質問内容<img src="{$src}cond_1.gif" width="13" height="15" class="cond1"><strong style="font-size:16px;">{$title}</strong></td>
              </tr>
            </table></td>
        </tr>
        <tr class="odd">
          <td> <table width="580" border="0" cellpadding="6" cellspacing="0">
              <tr>
                <td width="70" valign="top" nowrap>設定内容<img src="{$src}cond_1.gif" width="13" height="15" class="cond1"></td>
                <td>
                  {$cond}
                  <br> <div align="right">
                    {$input}
                  </div></td>
              </tr>
            </table>
__HTML__;
    }

    /**
     * 上半分の条件表示と編集ボタン・削除ボタンの箇所を取得
     */
    public function getConditionArea()
    {
        $se = $this->getSubevents();
        $se = $se[0];
        $seid =  $se['seid'];


        $list = array(
            'cond'  => 'display',
            'cond2' => 'self',
            'cond3' => 'cond3',
            'cond4' => 'self',
            'cond5' => 'filter',
        );

        $str = array ();
        foreach ($list as $k => $v) {
            if(!$se[$k]) continue;

            $c = $this->getConditionClass($k, $se, $se[$k]);
            //modeが分岐するなど特殊処理の場合はここに記述
            if ($v === 'cond3') {
                $v = ($c->command == 'and' || $c->command == 'or') ? 'relation' : 'self';
            }
            $str[] = $this->getDelCond($k)
                .$c->toStringShort($this->model->subevents).$this->getEditButton ($v, $seid);
        }

        return implode('<br />', $str);
    }

    public function getConditionClass($condname, $se, $cond)
    {
        switch ($condname) {
            case 'cond':
                return new Cond1Condition($se, $cond);
            case 'cond2':
                return new Cond2Condition($se, $cond);
            case 'cond3':
                return new Cond3Condition($se, $cond);
            case 'cond4':
                return new Cond4Condition($se, $cond);
            case 'cond5':
                return new Cond5Condition($se, $cond);
            default:
                echo '不正なcond指定です';
                exit;
        }
    }

    public function getDelCond($cond)
    {
        return '<input type="checkbox" name="delcond[]" value="'.$cond.'">　';
    }

    public function getEditButton($mode, $seid)
    {
        return '　<input type="submit" name="mode:'.$mode.':' . $seid . ':" value="編集">';
    }

    //TODO:他の画面でも使えるかも
    public function getSubeventType($se)
    {
        $type = array ();
        //※必須回答を表示する場合はコメントをはずす
//		if ($se['hissu'])
//			$type[] = '必須回答';
        $list = QuestionType::getType1();
        $type[] = ($list[$se['type1']])? $list[$se['type1']]: '%%%%エラー%%%%';

        if ($se['other'])
            $type[] = '記入回答欄有';

        return implode('/', $type);

    }

    public function getPluralHeader()
    {
        $src = DIR_IMG;

        $line = array ();
        $e = $this->model->event;

        foreach ($this->getSubevents() as $v) {
            $title = $this->formatQuestionTitle($v);
            $type = $this->getSubeventType($v);

            $line[] =<<<__HTML__
              <tr>
                <td nowrap><strong>{$v['seid']}</strong></td>
                <td><span align="left"><strong style="font-size:14px;color:#ee0000;">{$v['page']}</strong>/{$e['lastpage']}
                  page</span></td>
                <td><span style="color:#ee0000;">{$type}</span></td>
                <td><strong style="font-size:12px;">{$title}</strong></td>
              </tr>
__HTML__;
        }

        $line = implode('', $line);

        return<<<__HTML__
            <table width="580" border="0" cellpadding="6" cellspacing="0">
              <tr>
                <td nowrap><img src="{$src}caution_mark.gif" width="32" height="32"></td>
                <td><strong>【注意】 以下の質問に対して一括設定を実行します</strong>。<br> <br>
                  一括設定を実行した場合、既存設定されている条件分岐設定は上書きされてしまいますので<br>
                  今一度設定内容をご確認ください。特に問題がなければSTEP1より設定へ進んでください。<br> <br> <div align="right">
                    <input type="submit" name="mode:back:" value="条件設定一覧画面へ戻る">
                  </div></td>
              </tr>
            </table>
            <HR size="1" color="#3366aa"style="border-style:dotted">
            <table width="580" border="0" cellpadding="6" cellspacing="0">
              <tr>
                <td width="60" nowrap>質問 ID<img src="{$src}cond_1.gif" width="13" height="15" class="cond1"><strong></strong></td>
                <td width="60">頁設定<span align="left"><img src="{$src}cond_1.gif" width="13" height="15" class="cond1"><strong style="font-size:14px;color:#ee0000;"></strong></span></td>
                <td width="70">質問属性<img src="{$src}cond_1.gif" width="13" height="15" class="cond1"><span style="color:#ee0000;"></span></td>
                <td>質問内容<img src="{$src}cond_1.gif" width="13" height="15" class="cond1"></td>
              </tr>
 {$line}
            </table>
__HTML__;
    }

    public function getStepOne()
    {
        $mode = array (
            '' => '----------- 未設定 ----------',
            'display' => '設問に対する設問表示条件設定(条件分岐)',
            'self' => '設問の回答のみで判断する入力エラー発生条件設定(論理チェック、入力制御)',
            'relation' => '設問の回答と関連設問の回答を合わせて入力エラーの条件とする設定(回答の排他制御、他)',
            'filter' => '選択肢の絞込み表示設定',

        );

        //モード別に現在の表示モードを取得
        switch ($this->mode) {
            case 'next':
                $m = $this->post['mode'];
                break;
            case 'submit':
                $m = $this->post['submit_mode'];
                break;
            default:
                $m = $this->mode;
                break;
        }

        if ($m && $mode[$m]) {
            $form = $mode[$m];
            $active = false;
        } else {
            $active = true;
            //対象質問と直前までのイベントを元に判定する
            $ses = $this->getSubevents();
            $befores = $this->getBeforeSubevents();
            $list = array(
                'display' =>  ShowSetting :: isSelectable($ses, $befores),
                'self' =>     SelfSetting :: isSelectable($ses, $befores),
                'relation' => RelationSetting :: isSelectable($ses, $befores),
                'filter' =>   FilterSetting :: isSelectable($ses, $befores),
            );
            foreach ($list as $k => $v) {
                if(!$v) unset($mode[$k]);
            }

            if (1 < count($mode)) {
                $form = FForm :: select('mode', $mode) .
                    FForm :: hidden('comefrom', 'top') .
                    Fform :: submit('mode:next', ' 次 へ ');
            } else {
                $form = 'この設問に設定可能な条件はありません';
            }
        }

        return $this->view->getMainDiv(array($this->view->getStepTitle('step1',
            '条件設定の範囲及びタイプ [ 質問自体の表示・非表示/エラー表示 ] を選択してください。',
            $active,
            '<br> <div align="right">'.$form.'</div>'
        )));
    }

    public function getStepTwo()
    {
        if ($this->mode === 'delete') {
            $this->deleteCond();

            return '';
        }

        if ($this->isFirst())
            return '';
        //mode別に子ウィンドウのアップデート

        //子ウィンドウをゲット
        $child = $this->getChild($this->mode);

        $subtitle = $this->view->getStepTitle('step2',
            '設定後、「条件を設定する」を実行し、条件設定を完了してください。');


        //mode＝submitなら登録
        if ($this->mode === 'submit') {
            if ($error = $child->getError($this->post)) {
                $main = $this->getStepTwoEditMain($subtitle, $child, $error);
            } else {
                $child->submit($this->post);
                $this->model->loadEnquete($this->evid);
                $main = $this->view->getMainDiv(array(
                    $subtitle,
                    <<<__HTML__
            <div align="center">
            <br>登録を完了しました。<br><br>
            続けて同じ質問への条件を設定される場合は「この質問への条件設定を続ける」を選択ください。<br><br>
            <input type="submit" name="mode:back:" value="条件設定一覧画面へ戻る"><br><br>
            <input type="submit" name="mode:first:" value="この質問への条件設定を続ける"><br><br>
            </div>
__HTML__
                ));
            }
        } else {
            $main = $this->getStepTwoEditMain($subtitle, $child);
        }
        if (!$main) return '';
        $src = DIR_IMG;

        return <<<__HTML__
      <div align="center" style="width:600px;"><img src="{$src}cond_cursor.gif"></div>
      <br> <div style="width:600px;">
      {$main}
      </div>
__HTML__;
    }

    public function getStepTwoEditMain($subtitle, $child, $error='')
    {
            $main = $child->show($error);
            if (!$main) return '';

            //アップデート後、表示が有効なら表示
            $backmode = ($this->post['comefrom'] === 'top') ? 'first' : 'back';
            $back = FForm :: submit('mode:' . $backmode, ' 戻 る ');
            if ($child->isEnable()) {
                $submit = FForm :: submit('mode:submit', '条件を設定する');
            }
            $subtitle = $this->view->getMainDiv(array($subtitle));
            $cp = $this->view->getMainDiv(array(<<<__HTML__
<div align="center">
    <table width="590" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td align="right">{$back}{$submit}</td>
        </tr>
    </table>
</div>
__HTML__
), array(
            array('tr'=> 'class="even"'),
        ));

            return <<<__HTML__
{$subtitle}<br>
{$cp}<br>
{$main}<br>
{$cp}
__HTML__;
    }

    public function getChild($mode)
    {
        switch ($mode) {
            case 'next' :
                return $this->getChild($this->post['mode']);
                break;
            case 'submit' :
                return $this->getChild($this->post['submit_mode']);
                break;
            case 'display' :
                return new ShowSetting($this->getSubevents(), $this->getBeforeSubevents(), $this->view);
                break;
            case 'filter' :
                return new FilterSetting($this->getSubevents(), $this->getBeforeSubevents(), $this->view);
                break;
            case 'relation' :
                return new RelationSetting($this->getSubevents(), $this->getBeforeSubevents(), $this->view);
                break;
            case 'self' :
                return new SelfSetting($this->getSubevents(), $this->getBeforeSubevents(), $this->view);
                break;
            default :
                return new NullSetting();
                break;
        }
    }

    public function deleteCond()
    {
        $cond = $this->post['delcond'];
        if ($cond <= 0)
            return;
        $ok = array (
            'cond',
            'cond2',
            'cond3',
            'cond4',
            'cond5'
        );
        //一応チェックしておきましょう
        foreach ($cond as $dv) {
            if (!in_array($dv, $ok)) {
                echo "条件以外への削除命令が呼ばれました";
                exit;
            }
        }

        //delcondの利用は一質問の時だけ
        $subs = $this->getSubevents();
        if (1 < count($subs)) {
            echo "複数質問の選択で削除が呼ばれました";
            exit;
        }

        $seid = array ();
        foreach ($subs as $v) {
            foreach ($cond as $dv) {
                $v[$dv] = '';
            }

            Save_SubEnquete('update', $v);
        }
        //リフレッシュ
        $this->model->loadEnquete($this->evid);
    }

    public function getSeidsTag()
    {
        $res = '';
        foreach ($this->seids as $v) {
            $res .= FForm :: hidden('seids[]', $v);
        }
        $res .= FForm :: hidden('evid', $this->evid);

        return $res;
    }

    public function onNoSeid($evid = '')
    {
        //$this->modeを見ればモード別に処理もできる
        $action = PHP_SELF;
        if ($evid) {
            $modoru =<<<__HTML__
<input type="hidden" name="evid" value="{$evid}">
<input type="submit" name="mode:back:" value="戻る">
__HTML__;
        }
        echo<<<__HTML__
<form action="{$action}" method="post">
<p>
対象質問が選択されていません。再度やり直してください。
</p>
<p>
{$modoru}
</p>
</form>
__HTML__;
        exit;
    }

    //TODO:適当にリファクタリングしてください
    //: getBeforeSubeventsと処理統一、順番をresearchライブラリに任せるなど
    public function getMySubevents()
    {
        //一番若いseidのsubevent
        $s = $this->seids;
        sort($s);
        $seid = $s[0];
        //頭からそのseidが出てくるまでを追加
        $res = array ();
        foreach ($this->model->subevents as $v) {
            if ($v['seid'] == $seid) return $v;
        }
        echo 'getMySubeventsの戻り値がありません';
        exit;
    }

    /**
     * ターゲットより前のseを取得する
     */
    public function getBeforeSubevents()
    {
        //一番若いseidを取得
        $s = $this->seids;
        sort($s);
        $seid = $s[0];
        //頭からそのseidが出てくるまでを追加
        $res = array ();
        foreach ($this->model->subevents as $v) {
            if ($v['seid'] == $seid) break;
            $res[] = $v;
        }

        return $res;
    }

    //postを外部取得でないようにすると確実
    public function getMode($post=array())
    {
        foreach ($post as $key => $value) {
            if (ereg("^mode:(.*)$", $key, $match)) {
                return explode(':', $match[1]);
            }
        }

        return false;
    }
}

//テンプレートは使わないため、デザインライブラリとして機能させている
class SetCondView
{
    public function getStepTitle($img, $msg, $active=true, $add='')
    {
            $class = $active?'': ' style="background:#f2f2f2;color:#aaaaaa"';
            $src = DIR_IMG;

            return<<<__HTML__
<table width="590" border="0" cellpadding="6" cellspacing="0" class="sub_title">
              <tr{$class}>
                <td width="55"><img src="{$src}{$img}.gif" width="52" height="30"></td>
                <td>{$msg}</td>
              </tr>
            </table>{$add}
__HTML__;
    }

    public function getMainDiv ($trs, $style=array())
    {
            $s = '';
            foreach ($trs as $k => $v) {
                $tr = ($style[$k]['tr'])? ' '.$style[$k]['tr']: ' class="odd"';
                $td = ($style[$k]['td'])? ' '.$style[$k]['td']: '';
                $s .= <<<__HTML__
        <tr{$tr}><td{$td}>{$v}</td></tr>
__HTML__;
            }

            return<<<__HTML__
<table width="600" border=0 cellpadding=5 cellspacing=1 class="line">
{$s}
</table>
__HTML__;
    }

    public function getSubTitle($msg, $help)
    {
        $src = DIR_IMG;

        return <<<__HTML__
<table width="590" border="0" cellpadding="6" cellspacing="0" class="sub_title">
    <tr>
        <td>{$msg}</td>
        <td width="60"> <span onMouseOver="showHelp('<img src={$src}box_top.gif><div class=helpbox-text><img src={$src}box_tale.gif class=helpbox-tale>{$help}</div><img src={$src}box_bottom.gif>',this,event)" >
            <img onMouseOver="this.src='{$src}hint2.gif'" onMouseOut="this.src='{$src}hint1.gif'" value="submit" src="{$src}hint1.gif" />
        </span> </td>
    </tr>
</table>
__HTML__;
    }

    public function getChildBox($submitvalue, $title, $body, $error='')
    {
        $tr = array($title);
        if($error) $tr[] = $error;
        foreach ($body as $v) {
            $tr[] = $v;
        }
        $main = $this->getMainDiv($tr,array(
            array('tr'=> 'class="tr1"', 'td'=>'height="35"'),
        ));

        return<<<__HTML__
{$main}
        <br><br>
        <input type="hidden" name="submit_mode" value="{$submitvalue}">
__HTML__;
    }

    public function getChildNotice($msg)
    {
        return '<div align="center">'.$msg.'</div>';
    }
}


class SettingChild
{
    public function SettingChild($targets, $choices, $view)
    {
        $this->subevents = $choices;
        $this->targets = $targets;
        $this->view = $view;
    }

    public function save($data)
    {
        $seid = array ();
        foreach ($this->targets as $v) {
            //TODO:一括でSaveSubenqueteする仕組みがほしい
            $data['seid'] = $v['seid'];
            $data = $this->onBeforeSave($data);
            Save_SubEnquete('update', $data);
        }
    }

    public $enable = false;
    public function isEnable()
    {
        return $this->enable;
    }

    public function show($error)
    {
    }

    public function getError($post)
    {
        return "";
    }

    public function getErrorMessage($str)
    {
        return '<span style="color:#FF0000">'.$str.'</span><br>';
    }

    public function submit()
    {
    }

    public function onBeforeSave($data)
    {
        return $data;
    }
}

class NullSetting extends SettingChild
{
    public function NullSetting()
    {
    }
}

class ShowSetting extends SettingChild
{
    /**
     * @return bool この質問が選択可能ならtrue
     */
    public function isSelectable($targets, $befores)
    {
        //2ページ目以降かつ対象が存在する
        return 1 < $targets[0]['page'] && ShowSetting :: getSettable($befores);
    }

    public function getSettable($ses)
    {
        $res = array ();
        foreach ($ses as $v) {
            if (QuestionType::isSettableChoice($v)) {
                $res[] = $v;
            }
        }

        return $res;
    }

    public function getConditionClass()
    {
        if ($this->targets[0]['cond']) {
            return new Cond1Condition($this->targets[0], $this->targets[0]['cond']);
        }

        return null;
    }

    public function show($error)
    {
        $ses = ShowSetting :: getSettable($this->subevents);
        $selectShowCondQ = getSelectSeid($ses);
        $cls = $this->getConditionClass();
        //デフォルトの表示
        if ($cls) {
            //一個しか入っていないはずだが、今後の拡張の場合は変更のこと
            //こういうループもクラスでなんとかしたい
            foreach ($cls->cond as $v) {
                foreach ($v as $kseid => $vchoice) {
                    $selectShowCondQ = str_replace('value="' .
                    $kseid . '"', 'value="' .
                    $kseid . '" selected', $selectShowCondQ);
                }
            }
        }

        $selectShowCondA = "";
        foreach ($ses as $subEvent) {
            $c = getSelectChoice($subEvent);
            //デフォルトの表示
            if ($cls && !$error) {
                foreach ($cls->getCondBySeid($subEvent['seid']) as $v) {
                    $c = str_replace('value="'.$v.'"', 'value="'.$v.'" selected', $c);
                }
            }
            $selectShowCondA .= $c;
        }

        $div = array();
        if ($this->targets[0]['page'] < 2) {
            $body[] = $this->view->getChildNotice('1ページ目の質問では、表示条件の設定はできません。');
        } elseif (!$ses) {
            $body[] = $this->view->getChildNotice('以前に選択型設問が存在しないため、表示条件の設定はできません。');
        } else {
            $st = $this->view->getSubTitle('質問表示設定／前問の選択肢より本質問の表示・非表示を制御します。',
                '<strong>表示条件元質問</strong><br>現在編集している質問の表示制御元となります。<br>'
                .'尚、ここで設定できる質問は、本質問より前の質問に限定されます。<strong><br></strong>'
                .'<HR size=1 color=#660066 style=border-style:dotted><strong>表示条件元選択肢</strong><br>'
                .'ここで設定した表示条件元質問の選択肢を選択した時のみ、本質問が表示されるように設定されます。'
            );

            $body[] =<<<__HTML__
{$st}
              <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="290"> <div align="right">表示条件元質問：</div></td>
                   <td> {$selectShowCondQ}</td>
                </tr>
                <tr>
                  <td valign="top"> <div align="right">表示条件元選択肢： </div></td>
                   <td height="65" bgcolor="#DADADA">{$selectShowCondA}
                   <script>setShowCond_Seid();</script>
                    </td>
                </tr>
                <tr>
                  <td><br></td>
                  <td>
                      <font color="#FF6600">※ctrl+クリックで複数選択が可能です</font></td>
                </tr>
              </table>
__HTML__;
            $this->enable = true;
        }

        return $this->view->getChildBox('display', '設問に対する設問表示条件設定(条件分岐)', $body, $error);
    }

    public function getError($post)
    {
        if (!$post['showcond_choice'] || !$post['showcond_seid']) {
            return $this->getErrorMessage('条件を選択してください');
        }
    }

    public function submit($post)
    {

        $res = array ();
        foreach ($post['showcond_choice'] as $v) {
            $res[] = array (
                $post['showcond_seid'] => $v
            );

        }
        //condクラスに入れてパースする
        $cls = new Cond1Condition($this->targets[0]);
        $cls->cond = $res;

        //登録
        $this->save(array (
        'cond' => $cls->getCondition()));
    }

}
//-----------------------------------------------------------------------------
class SelfSetting extends SettingChild
{
    /**
     * @return bool この質問が選択可能ならtrue
     */
    public function isSelectable($targets, $befores)
    {
        /*
         * other = 1
         * type 2-4
         *
         */

        return ( 0 < $targets[0]['other']) || (in_array($targets[0]['type1'], array(2,3,4)));
    }

    public function getSettable($ses)
    {
        return $ses;
    }

    public function getCondition2Class()
    {
        if ($this->targets[0]['cond2']) {
            return new Cond2Condition($this->targets[0], $this->targets[0]['cond2']);
        }

        return null;
    }

    public function getCondition3Class()
    {
        if ($this->targets[0]['cond3']) {
            return new Cond3Condition($this->targets[0], $this->targets[0]['cond3']);
        }

        return null;
    }

    public function getCondition4Class()
    {
        if ($this->targets[0]['cond4']) {
            return new Cond4Condition($this->targets[0], $this->targets[0]['cond4']);
        }

        return null;
    }
    public function show($error)
    {
        $formother = '';
        $c31 = '';
        $c32 = '';
        foreach (explode(',', $this->targets[0]['choice']) as $k => $v) {
            $formother .= FForm :: radio('other', $k, $v) . '<br>';
            $c31 .= FForm :: radio('lca1', $k, $v) . '<br>';
            $c32 .= FForm :: radio('lca2', $k, $v) . '<br>';
        }
        $c32 .= FForm :: radio('lca2', -1, 'いずれかの選択肢') . '<br>';
        $src = DIR_IMG;
        $this->enable = true;
        $body = array();
        if ($this->targets[0]['type1'] < 3 && 0 < $this->targets[0]['other']) {

            $cls = $this->getCondition2Class();
            if ($cls) {
                $formother = str_replace('value="'.$cls->cond2['other'].'"',
                    'value="'.$cls->cond2['other'].'" checked', $formother);
            }
            $st = $this->view->getSubTitle('その他記入欄が必須となる選択肢を指定します',
                '<strong>その他記入欄が必須となる選択肢の指定</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'指定の選択肢を回答者が選択した場合に、設定されている記入回答欄<font color=red>※</font>も入力していなければ、回答エラーを表示します。<br><br>'
                .'※ アンケート質問設定画面にてメインhtml上で<b>%%%%other%%%%</b>として設定されているテキストエリアを指します。'
            );
            $body[] =<<<__HTML__
{$st}
              <br> <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td>{$formother}</td>
                </tr>
              </table>
__HTML__;
        }

        if ($this->targets[0]['type1'] == 2) { //複数選択の時のみ
            $cls = $this->getCondition2Class();
            $sel_cond = FForm :: select('cond2_val', array(
                '' => '---未設定---',
                'maxcount' => '上限数を設定する',
                'equalcount' => '選択数を固定する'
            ));

            if ($cls) foreach (array('equalcount', 'maxcount') as $v) {
                if ($cls->cond2[$v]) {
                    $sel_cond = FForm::replaceSelected($sel_cond, $v);
                    $value = $cls->cond2[$v];
                    break;
                }
            }

            $st = $this->view->getSubTitle('指定の複数選択肢の選択上限数を設定、または選択数を固定化します。<br>'
                .'（※設定した条件に合わない場合、エラーが表示されます)',
                '<strong>選択上限数の設定</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'「上限○個まで選択可能」という形式で制御します。※該当の質問が必須回答として設定してある場合には、1個以上○個以下の範囲での選択を強制し、範囲外の選択を行った場合にエラーを表示します。<br><br>'
                .'<strong>選択数の固定化</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'「必ず○個選択」という形式で制御します。よって、設定の選択数と同数を回答者が選択しなければエラーを表示します。'
            );
            $body[] =<<<__HTML__
{$st}
              <br> <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td><div align="right"><img src="{$src}check_g.gif">
                      <input name="cond2_num" type="text" size="5" value="{$value}">
                      個に
                      {$sel_cond}
                    </div></td>
                </tr>
              </table>
__HTML__;

            $cls = $this->getCondition3Class();
            $defval = '回答が矛盾しています';
            if ($cls) {
                if ($cls->command === 'lcacond3') {
                    $v = explode(':', $cls->values[0]);
                    $c31 = str_replace('value="'.$v[1].'"', 'value="'.$v[1].'" checked', $c31);
                    $v = explode(':', $cls->values[1]);
                    $c32 = str_replace('value="'.$v[1].'"', 'value="'.$v[1].'" checked', $c32);
                    $defval = $cls->message;
                }
            }
            $defval = transHtmlentities($defval);

            $st2 = $this->view->getSubTitle('同時チェックが出来ない選択肢の組み合わせを設定します。<br>'
                .'（※チェックした選択肢を回答者が選択するとエラーが表示されます)',
                '<strong>同時チェックが出来ない選択肢の組み合わせを設定</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'設定画面に表示されている右列と左列のラジオボタンよりそれぞれ選択した組み合わせを回答者が選択した場合にエラーを表示します。'
            );
            $body[] =<<<__HTML__
           <div align=center>{$st2}
              <br> <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="295">{$c31}</td>
                  <td width="295">{$c32}</td>
                </tr>
                </table>
              <br> <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td><div align="right">上記のチェックした選択肢を回答者が選ぶ場合、回答者が他の選択肢も選択したら次のエラーを表示
                    </div></td>
                </tr>
                <tr>
                  <td><div align="right">エラーメッセージ：
                      <input name="lca_mes" type="text" value="{$defval}" size="80">
                    </div></td>
                </tr>
              </table>
          </div>
__HTML__;
        } elseif ($this->targets[0]['type1'] == 3) {
            $cls = $this->getCondition4Class();
            $maxval = '';
            $minval = '';
            $defval = '指定の範囲内で回答してください';
            $sel_cond = FForm :: select('c4_bool', array(
                '' => '---未設定---',
                'false' => 'ある',
                'true' => 'ない'
            ));
            if ($cls && in_array($cls->command, array ('both', 'min', 'max'))) {
                switch ($cls->command) {
                    case 'max':
                        $maxval = $cls->value;
                        break;
                    case 'min':
                        $minval = $cls->value;
                        break;
                    case 'both':
                        list ($minval, $maxval) = explode("-", $cls->value);
                        //bothの時はbolが逆転する
                        $cls->isNot = !$cls->isNot;
                        break;
                }
                $sel_cond = FForm::replaceSelected($sel_cond, $cls->isNot? 'false': 'true');
                $defval = $cls->message;
            }
            $defval = transHtmlentities($defval);

            $st = $this->view->getSubTitle('数値回答(FA)に対する入力範囲を設定します。',
                '<strong>数値回答(FA)に対する入力範囲の設定</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'最小値及び、最大値を入力した場合に、回答者がその設定範囲内の数値を越えた数値を入力を行うと回答エラーを表示します。<br><br>'
                .'例）あなたの一日の業務時間のうち、事務作業に対するウェイトをお答えください。(全体を100%とします)<br>　　最小値->0 最大値->100'
            );

            $body[] =<<<__HTML__
{$st}
              <br> <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td width="230"> <div align="right"> 最小値
                      <input name="c4_min" type="text" size="5" value="{$minval}">
                    </div></td>
                  <td> <div align="center">
                      ≦　入力値　≦
                      <br>
                    </div></td>
                  <td width="230">最大値
                    <input name="c4_max" type="text" size="5" value="{$maxval}">
                  </td>
                </tr>
              </table>
              <br /> <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td><div align="right">上記の範囲内で
                      {$sel_cond}
                      場合、次のエラーを表示 </div></td>
                </tr>
                <tr>
                  <td><div align="right">エラーメッセージ：
                      <input name="c4_mes" type="text" value="{$defval}" size="80">
                    </div></td>
                </tr>
              </table>
__HTML__;
        } elseif ($this->targets[0]['type1'] == 4) {
            $cls = $this->getCondition4Class();
            $val = '';
            $defval = '指定の範囲内で回答してください';

            $sel_cond = FForm :: select('c4_bool', array(
                '' => '---未設定---',
                'true' => 'より大きい',
                'false' => '以内である'
            ));
            if ($cls && $cls->command === 'len') {
                $val = $cls->value;
                $defval = $cls->message;
                $sel_cond = FForm::replaceSelected($sel_cond, $cls->isNot? 'false': 'true');
            }
            $defval = transHtmlentities($defval);

            $st = $this->view->getSubTitle('記入回答(FA)に対する入力文字数を設定します。',
                '<strong>記入回答(FA)に対する入力文字数の設定</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'記入回答欄に対する入力文字数の下限・上限(**文字以内/**文字以上)のどちらかを設定し、条件を満たさない場合には回答エラーを表示します。'
            );

            $body[] =<<<__HTML__
{$st}
              <br /> <table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td><div align="right"><input name="c4_count" type="text" size="5" value="{$val}">文字
                      {$sel_cond}
                      場合、次のエラーを表示 </div></td>
                </tr>
                <tr>
                  <td><div align="right">エラーメッセージ：
                      <input name="c4_mes" type="text" value="{$defval}" size="80">
                    </div></td>
                </tr>
              </table>
__HTML__;
        }

        if (!$body) {
            $body[] =<<<__HTML__
        <div align="center">この質問のタイプに設定できる自問条件はありません。</div>
__HTML__;
            $this->enable = false;
        }

        return $this->view->getChildBox('self',
            '設問の回答のみで判断する入力エラー発生条件設定(論理チェック、入力制御)', $body, $error);
    }

    public function getError($post)
    {
        $set = array();
        $error = array();

        //その他回答
        if (isset($post['other'])) {
            $set[] = 'その他必須設定';
        }

        //回答上限
        //cond2_valはデフォルトで""なのでnullにはならない
        if ($post['cond2_num'] || $post['cond2_val']) {
            $set[] = '選択数設定';
            //必須チェック
            if (!$post['cond2_num'] || !$post['cond2_val']) {
                $error[] = '選択数設定は全ての条件を設定ください';
            } elseif (!FCheck::isNumber($post['cond2_num'])) {
                $error[] = '選択数設定は数字で入力ください';
            }
        }

        //同時チェック設定
        if (isset($post['lca1']) || isset($post['lca2'])) {
            $set[] = '同時チェック設定';
            if (is_null ($post['lca1']) || is_null ($post['lca2'])) {
                $error[] = '同時チェック設定は全ての条件を設定ください';
            }
            if (!$post['lca_mes']) {
                $error[] = '同時チェック設定のエラーメッセージを入力ください';
            }
        }

        //範囲指定
        if ($post['c4_min'] || $post['c4_max']) {
            $set[] = '入力範囲指定';
            if ((!$post['c4_min'] && !$post['c4_max']) || $post['c4_bool']==='') {
                $error[] = '入力範囲指定の条件を設定下さい';
            } elseif (($post['c4_min'] && !FCheck::isNumber($post['c4_min'])) || ($post['c4_max'] && !FCheck::isNumber($post['c4_max']))) {
                $error[] = '入力範囲指定は数字で入力ください';
            }
            if (!$post['c4_mes']) {
                $error[] = '入力範囲指定のエラーメッセージを入力ください';
            }
        }

        //文字数制限
        if ($post['c4_count']) {
            $set[] = '文字数制限';
            if (!FCheck::isNumber($post['c4_count'])) {
                $error[] = '文字数制限は数字で入力ください';
            }
            if (!$post['c4_mes']) {
                $error[] = '文字数制限のエラーメッセージを入力ください';
            }
        }

        //どれも設定されていない、またはどれか一つ間違いがあればエラーを返す
        if (!$set) {
            $error[] = '条件が設定されていません';
        }
        if ($error) {
            return $this->getErrorMessage(implode('<br>', $error));
        }
    }

    public function submit($post)
    {
        $data = array ();

        $c = new Cond2Condition($this->target[0]);
        $c->cond2 = array ();
        if ($post['cond2_num']) $c->cond2[$post['cond2_val']] = $post['cond2_num'];
        if (isset ($post['other'])) $c->cond2['other'] = $post['other'];
        $data['cond2'] = $c->getCondition();

        if (isset ($post['lca1']) && isset ($post['lca2'])) {
            $c = new Cond3Condition($this->target[0]);
            $c->command = 'lcacond3';
            $c->isNot = false; //よくわからないがこの値は使ってないのでダミー
            $c->message = $post['lca_mes'];
            $c->values = array (
                '%%%%id%%%%:' . $post['lca1'] . ',%%%%id%%%%:' . $post['lca2']
            );
            $data['cond3'] = $c->getCondition();
        }

        if ($post['c4_bool']) {
            $c = new Cond4Condition($this->target[0]);
            if (ctype_digit($post['c4_min']) && ctype_digit($post['c4_max'])) {
                $c->command = 'both';
                $c->value = $post['c4_min'] . '-' . $post['c4_max'];
            } elseif (ctype_digit($post['c4_min'])) {
                $c->command = 'min';
                $c->value = $post['c4_min'];
            } elseif (ctype_digit($post['c4_max'])) {
                $c->command = 'max';
                $c->value = $post['c4_max'];
            } elseif (ctype_digit($post['c4_count'])) {
                $c->command = 'len';
                $c->value = $post['c4_count'];
            } else {
                $no = true;
            }

            $c->isNot = ($post['c4_bool'] === 'false');
            if ($c->command === 'both') {
                //bothの時はbolが逆転する
                $c->isNot = !$c->isNot;
            }
            $c->message = $post['c4_mes'];
            if (!$no)
                $data['cond4'] = $c->getCondition();

        }

        $this->save($data);
    }

    public function onBeforeSave($data)
    {
        //TODO:理想的にはここでcond3がなければlca関係を消す処理
        if ($data['cond3'])
            $data['cond3'] = str_replace("%%%%id%%%%", $data['seid'], $data['cond3']);

        return $data;
    }

}
//-----------------------------------------------------------------------------

class RelationSetting extends SettingChild
{
    /**
     * @return bool この質問が選択可能ならtrue
     */
    public function isSelectable($targets, $befores)
    {
        return ($targets[0]['type1'] < 3 && RelationSetting :: getSettable($befores));
    }

    public function getSettable($ses)
    {
        $res = array ();
        foreach ($ses as $v) {
            if ($v['type1'] < 3) {
                $res[] = $v;
            }
        }

        return $res;
    }
    public function getCondition3Class()
    {
        if ($this->targets[0]['cond3']) {
            return new Cond3Condition($this->targets[0], $this->targets[0]['cond3']);
        }

        return null;
    }
    public function show($error)
    {
        $opt = array (
            '' => '----未設定----'
        );
        $ses = $this->getSettable($this->subevents);
        foreach ($ses as $v) {
            $opt[$v['seid']] = getPlainText($v['title']);
        }
        $sel = FForm :: select('relation_seid', $opt);

        $cond = array(
            '' => '---未設定---',
            'true' => '一致している',
            'false' => '一致していない'
        );
        $sel_cond = FForm :: select('relation_bool', $cond);

        $mesdef='回答が重複しています';
        $cls = $this->getCondition3Class();
        if ($cls && in_array($cls->command, array("and", "or"))) {
            //一個しか設定できないので
            list($tseid) = explode(':', $cls->values[0]);
            $sel = FForm::replaceSelected($sel, $tseid);
            $mesdef = $cls->message;
            $sel_cond = FForm::replaceSelected($sel_cond, $cls->isNot? 'false': 'true');
        }
        $mesdef = transHtmlentities($mesdef);

        $body = array();
        if (!$ses) {
            $body[] = $this->view->getChildNotice('以前に選択型質問が存在しないため、他問条件の設定はできません。');
        } elseif (3 <= $this->targets[0]["type1"]) {
            $body[] = $this->view->getChildNotice('この質問のタイプに設定できる他問条件はありません。');
        } else {
            $st = $this->view->getSubTitle('前後の質問の選択肢番号と比較し回答エラー表示を制御します。',
                '<strong>前後の質問の選択肢番号と比較</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'優先順位を問う質問のように、選択肢が同一でかつ設定上複数問にわたる質問間の比較制御を行います。<br>'
                .'※同じ選択肢を選んだ場合、または選ばなかった場合にエラーを表示します。'
            );

            $body[] =<<<__HTML__
{$st}
<table width="590" border="0" cellpadding="0" cellspacing="0">
                <tr>
                  <td><div align="right">
                      {$sel}
                      と比較し選択肢番号が
                      {$sel_cond}
                      場合、次のエラーを表示
                  </div></td>
                </tr>
                <tr>
                  <td> <div align="right">エラーメッセージ：
                    <input name="relation_mes" type="text" value="{$mesdef}" size="80">
                  </div></td>
                </tr>
              </table>
__HTML__;
            $this->enable = true;
        }

        return $this->view->getChildBox('relation',
            '設問の回答と関連設問の回答を合わせて入力エラーの条件とする設定(回答の排他制御、他)',
            $body, $error);
    }

    public function getError($post)
    {
        $error = array();
        if (!$post['relation_seid'] || !$post['relation_bool']) {
            $error[] = '条件を選択してください';
        }
        if (!$post['relation_mes']) {
            $error[] = 'エラーメッセージを入力ください';
        }
            if($error) return $this->getErrorMessage(implode('<br>', $error));
    }

    public function submit($post)
    {
        $c = new Cond3Condition($this->target[0]);
        //TODO:seidの複数選択ができないため、意味が無いのでandにしている
        $c->command = 'and';
        $c->isAnd = ($c->command === 'and');
        $c->isNot = ($post['relation_bool'] === 'false');
        $c->message = $post['relation_mes'];
        $c->values = array (
            $post['relation_seid'] . ':a'
        );
        $this->save(array (
        'cond3' => $c->getCondition()));
    }

    public function onBeforeSave($data)
    {
        $data['cond3'] = str_replace("%%%%id%%%%", $data['seid'], $data['cond3']);

        return $data;
    }

}
//-----------------------------------------------------------------------------
class FilterSetting extends SettingChild
{
    /**
     * @return bool この質問が選択可能ならtrue
     */
    public function isSelectable($targets, $befores)
    {
        return ($targets[0]['type2'] === 'p' && FilterSetting :: getSettable($befores));
    }

    public function getSettable($ses)
    {
        $res = array ();
        foreach ($ses as $v) {
            if ($v['type2'] === 'p') {
                $res[] = $v;
            }
        }

        return $res;
    }

    public function show($error)
    {
        $ses = $this->getSettable($this->subevents);
        $selectCond5Seid = getSelectCond5Seid($ses);
        $selectCond5Choice1 = "";
        foreach ($ses as $subEvent) {
            $selectCond5Choice1 .= getSelectCond5Choice1($subEvent);
        }
        $target = $this->targets[0];
        $selectCond5Choice2 = getSelectCond5Choice2(getSubEvent($target['seid']));
        $cond = str_replace(',', "\n", $target['cond5']);
        $src = DIR_JS;
        $body = array();
        if (!$ses) {
            $body[] = $this->view->getChildNotice('以前にプルダウン設問が存在しないため、選択肢の絞込みはできません。');
        } elseif (3 <= $this->targets[0]["type1"]) {
            $body[] = $this->view->getChildNotice('選択肢の無い設問のため、選択肢の絞込みはできません。');
        } elseif ($this->targets[0]["type2"] !== 'p') {
            $body[] = $this->view->getChildNotice('選択肢絞り込みはプルダウンのみ有効となっています。');
        } else {
            $st = $this->view->getSubTitle('選択肢表示設定／前問の選択肢より本質問の選択肢を絞り込み、表示を制御します。<br>'
                .'ドラッグ＆ドロップを行い、絞込み条件として設定値に追加してください',
                '<strong>表示条件元質問</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'本質問の選択肢が絞り込まれる条件元となる質問です。<br><br>'
                .'<strong>表示条件元選択肢</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'本質問の選択肢が絞り込まれる条件元となる質問の選択肢です。<br><br>'
                .'<strong>表示選択肢</strong><HR size=1 color=#660066 style=border-style:dotted>'
                .'絞り込まれる本質問の選択肢です。<br><br>'
                .'【設定方法】<br>１．<b>表示条件元質問</b>を選択します。<br><br>２．１．の選択によって表示される<b>表示条件元選択肢</b>を一つ選択します。<br><br>'
                .'３．<b>shift+クリック</b>にて、２.の選択肢を回答者が選択した場合に表示される、本設問選択肢を選択します<br><br>４．<b>選択した組み合わせを設定値に追加する</b>を実行します。<br><br>'
                .'５．設定値に追加されますので、複数絞込み条件を設定する場合には、'
            );
            $sname = SESSIONID;
            $sid = html_escape(session_id());

            $body[] =<<<__HTML__
{$st}
              <br> {$error}<table width="590" border="0" cellpadding="4" cellspacing="1">
                <tr>
                  <td colspan="2"> 表示条件元質問：
                    {$selectCond5Seid}</td>
                </tr>
                <tr>
                  <td width="290"> 表示条件元選択肢： <br /> </td>
                  <td width="290">表示選択肢： </td>
                </tr>
                <tr>
                  <td valign="top"> <table width="250" height="150" border="0" cellpadding="0" cellspacing="0" bgcolor="#DADADA">
                      <tr>
                        <td>{$selectCond5Choice1}</td>
                      </tr>
                    </table></td>
                  <td> <table width="250" height="150" border="0" cellpadding="0" cellspacing="0" bgcolor="#DADADA">
                      <tr>
                        <td>{$selectCond5Choice2}</td>
                      </tr>
                    </table></td>
                </tr>
                <tr>
                  <td height="30" colspan="2" valign="top"> <button type="button" onClick="setCond5();getCond5Message();">選択した組み合わせを設定値に追加する</button>
                  </td>
                </tr>
                <tr>
                  <td colspan="2" valign="top">設定値 [ ローカルに保存する場合にはコピー&amp;ペーストを行ってください
                    ] <br> <textarea name="cond5" id="cond5" style="width:580px;" cols="80" rows="5" onchange="getCond5Message();">{$cond}</textarea></td>
                </tr>
                <tr>
                  <td colspan="2" valign="top">現在の設定内容：<br><div id="cond5_message">12</div>
                  <script src="{$src}ajax.js" type="text/javascript"></script>
                  <script>
function getCond5Message()
{
        var obj = new AjaxObject();
        obj.onLoad = function (xmlhttp) {
            document.getElementById("cond5_message").innerHTML = xmlhttp.responseText;
        }
        obj.data = {
            "evid":{$target['evid']},
            "seid":{$target['seid']},
            "cond5": document.getElementById("cond5").value.replace(/\\n/g, ","),
            "{$sname}": "{$sid}"
        };
        obj.post('ajax_get_cond5_message.php');
}
getCond5Message();
                  </script>


                  </td>
                </tr>
              </table>
__HTML__;
            $this->enable = true;
        }

        return $this->view->getChildBox('filter', '選択肢の絞込み表示設定', 	$body, $error);
    }

    public function getError($post)
    {
        if (!$post['cond5']) {
            return $this->getErrorMessage('条件を選択してください');
        }
    }

    public function submit($post)
    {
        //改行は,に置き換える
        if ($post['cond5']) {
            $cls = new Cond5Condition($this->targets[0], str_replace("\n", ",", $post['cond5']));
            $command = $cls->getCondition();
        } else {
            $command = '';
        }
        //登録
        $this->save(array (
            'cond5' => $command
        ));
    }
}

//=============================================================================
function getJavaScript()
{
    return<<<__JAVA__
function setShowCond_Seid()
{
    var aryOpt = document.getElementById('showcond_seid').options;
    var numOpt = aryOpt.length;

    for (var i=0; i<numOpt; i++) {
        if (!aryOpt[i].value.match(/[^0-9]+/)) {
            var d = document.getElementById('showcond_choice_' + aryOpt[i].value);
            if (aryOpt[i].selected) {
                d.style.display = '';
                d.disabled = false;
            } else {
                d.style.display = 'none';
                d.disabled = true;
            }
        }
    }
}

function setCond5Seid()
{
    var aryOpt = document.getElementById('cond5_seid').options;
    var numOpt = aryOpt.length;

    for (var i=0; i<numOpt; i++) {
        if (!aryOpt[i].value.match(/[^0-9]+/)) {
            var d = document.getElementById('cond5_choice1_' + aryOpt[i].value);
            if (aryOpt[i].selected) {
                d.style.display = '';
                d.disabled = false;
            } else {
                d.style.display = 'none';
                d.disabled = true;
            }
        }
    }
}


function setCond5()
{
    var seid = document.getElementById('cond5_seid');
    var numSeid = seid.length;
    for (var i=0; i<numSeid; i++) {
        if (seid[i].selected) {
            seid = seid[i].value;
            break;
        }
    }

    if (!seid.match(/[^0-9]+/)) {
        var choice2 = document.getElementById('cond5_choice2');
        var numChoice2 = choice2.length;

        var cond5 = '';
        for (var i=0; i<numChoice2; i++) {
            if (choice2[i].selected) {
                if(cond5!='')	cond5 += '.';
                cond5 += choice2[i].value;
            }
        }

        if (cond5!='') {
            var choice1 = document.getElementById('cond5_choice1_' + seid);
            var numChoice1 = choice1.length;

            var listCond5 = document.getElementById('cond5').value;
            for (var i=0; i<numChoice1; i++) {
                if (choice1[i].selected) {
                    listCond5 +=  "\\n" + seid + ':' + choice1[i].value + ':' + cond5;
                }
            }
            document.getElementById('cond5').value = listCond5;
        }
    }
}
__JAVA__;
}

function getSelectSeid($arySubEvent)
{
    $option = getOptionSeid($arySubEvent);

    return<<<__HTML__
<select name="showcond_seid" id="showcond_seid" style="width:300px;" onChange="setShowCond_Seid();">
<option value="none">-------未設定-------</option>
{$option}
</select>
__HTML__;
}

function getSelectCond5Seid($arySubEvent)
{
    $option = getOptionSeid($arySubEvent);

    return<<<__HTML__
<select id="cond5_seid" style="width:300px" onChange="setCond5Seid();">
<option value="none">-------未設定-------</option>
{$option}
</select>
__HTML__;
}

function getOptionSeid($arySubEvent)
{
    $option = "";
    foreach ($arySubEvent as $subEvent) {
        $subEvent['seid'] = html_escape($subEvent['seid']);
        $subEvent['title'] = getPlainText($subEvent['title']);
        $option .=<<<__HTML__
<option value="{$subEvent['seid']}">{$subEvent['title']}</option>\n
__HTML__;
    }

    return $option;
}

function getSelectChoice($subEvent)
{
    $option = getOptionChoice($subEvent);
    $subEvent['seid'] = html_escape($subEvent['seid']);

    return<<<__HTML__
<select name="showcond_choice[]" id="showcond_choice_{$subEvent['seid']}" style="display:none;width:300px;height:100%;" multiple size="4">
{$option}
</select>
__HTML__;
}

function getSelectCond5Choice1($subEvent)
{
    $option = getOptionChoice($subEvent);
    $subEvent['seid'] = html_escape($subEvent['seid']);

    return<<<__HTML__
<select id="cond5_choice1_{$subEvent['seid']}" style="display:none;width:250px;height:100%;" multiple size="10">
{$option}
</select>
__HTML__;
}

function getSelectCond5Choice2($subEvent)
{
    $option = getOptionChoice($subEvent);

    return<<<__HTML__
<select id="cond5_choice2" style="width:250px;height:100%;" multiple size="10">
{$option}
</select>
__HTML__;
}

function getOptionChoice($subEvent)
{
    $q = getQuestion($subEvent);
//	$subEvent['choice'] = html_escape($subEvent['choice']);
//	$aryChoice = getPlainText($q->getChoices());
    $aryChoice = $q->getChoices();

    $option = "";
    foreach ($aryChoice as $key => $choice) {
        $key = html_escape($key);
        $choice = getPlainText($choice);
        $option .=<<<__HTML__
<option value="{$key}">{$choice}</option>\n
__HTML__;
    }

    return $option;
}

function getSubEvent($seid)
{
    return FDB :: select1(T_EVENT_SUB, "*", "where seid=" . FDB :: escape($seid));
}

function getArySubEvent($arySeid, $selfSeid)
{
    sort($arySeid);

    $where = array ();
    $where[] = "seid<" . FDB :: escape($selfSeid);
    $where[] = "(type1='1' or type1='2')";
    $where[] = sprintf("seid in (%s)", implode(",", FDB :: escapeArray($arySeid)));

    return FDB :: select(T_EVENT_SUB, "*", "where " . implode(" and ", $where));
}

define('ENQ_RID', getRidByEvid($_REQUEST['evid']));

$page = new SetCondPage(new Model(), new SetCondView());
$main = $page->update($_POST, $_GET);
$javascript = getJavaScript();
$evid = html_escape($page->evid);
$sid = getSID();
$src = DIR_IMG;
$html = <<<__HTML__
<table border="0" cellspacing="5" cellpadding="0">
  <tr>
    <td width="600" valign="top">
{$main}</td>
    <td width="230" valign="top"> <table width="230" border=0 cellpadding=5 cellspacing=1 class="line">
        <tr class="odd">
          <td align=left> <table width="190" border="0" cellpadding="2" cellspacing="2">
              <tr>
                <td><div align="right">
                    <p>
                      <form action="enq_event.php?{$sid}&evid={$evid}" method="post" style="display:inline">
                          <input type="submit" name="Submit222233222" value="基本設定画面へ">
                      </form>
                      <form action="enq_list.php?{$sid}&evid={$evid}" method="post" style="display:inline">
                          <input type="submit" name="Submit22223322" value="ページ設定一覧画面へ">
                          <br>
                      </form>
                      <form action="cond_list.php?{$sid}&evid={$evid}" method="post" style="display:inline">
                          <input type="submit" name="Submit2222332222" value="条件設定一覧画面へ">
                      </form>
                    </p>
                  </div></td>
              </tr>
            </table></td>
        </tr>
      </table></td>
  </tr>
</table>
__HTML__;

$css = <<<__CSS__
body {margin:10px; padding:0; color:#3366aa;}
img {border:0; }
strong {color:#3366aa; }
input,select {font-size:11px;}
td {font-size:12px; }
input,select {font-size:1.0em; }
.hissu {color:#ff0000; text-align:center; }
.title {color:#888888; font-size:12px; }
.sub_title{background:#ccddee;font-size:12px;color:#3366aa; margin-left:0px; }
.tr1 td {color:#ffffff; font-size:14px; background:#3366aa; text-align:left; }
.line {background:#ccddee; }
.titlebg {background:#ffffdd;text-align:left;}
.odd {background:#ffffff;text-align:left;}
.even {background:#f5faff; }
.condsublist {background:#dddddd;}
.tr2 td {font-size:10px; background:#ffffff; text-align:left; height:20px;}
.tr3 td {font-size:12px; background:#ffffff; text-align:left; height:25px;}
.cursor {text-align:center;background-image:url({$src}cond_cursor.gif);background-repeat:norepeat;height:100px;}
.cond1 {margin:0 5px -3px 3px; }

.helpbox-text {
background:url({$src}box_bg.gif) repeat-y;
padding:0 8px 3px 30px;
color:#880000;
font-size:11px;
line-height:1.4em;
}
.helpbox-text strong {color:#cc0000; font-size:12px;}
.helpbox-tale {display:absolute; margin:-5px 10px 0 -30px;}
__CSS__;

$objHtml =& new ResearchAdminHtml("条件設定");
$objHtml->addFileJs(DIR_JS."floatinghelp.js");
$objHtml->setSrcJs($javascript);
$objHtml->setSrcCss($css);

echo replaceEnq($objHtml->getMainHtml($html));
exit;
//1852
