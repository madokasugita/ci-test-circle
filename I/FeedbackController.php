<?php
class FeedbackController
{
    public $smarty = null;

    public $total = null;

    public $secularFeedbackController = null;

    public $parse = null;

    public $data = null;

    public $secularDatas = array();

    public $listHeaderSpansForSecular = array(
        '<span style="color:#FF0000;font-weight:bold;">○</span>',
        '<span style="color:#4AACC5;font-weight:bold;">◇</span>',
        '<span style="color:#04B052;font-weight:bold;">□</span>',
        '<span style="color:#00AFEF;font-weight:bold;">△</span>',
        '<span style="color:#FFC000;font-weight:bold;">×</span>',
    );

    public $listHeaderSpans = array(
        '<span style="color:#FF0000;font-weight:bold;">○</span>',
        '<span style="color:#04B052;font-weight:bold;">□</span>',
        '<span style="color:#00AFEF;font-weight:bold;">△</span>',
        '<span style="color:#FFC000;font-weight:bold;">×</span>',
    );

    /**
     * 事前処理。DLアクションの記載
     */
    public function init()
    {
        parse_str($_SERVER['QUERY_STRING'], $this->parse);
        if (is_good($this->parse['dl'])) {
            $this->parse['no_animate'] = $this->parse['dl'];
            unset($this->parse['dl']);
            $filepath = str_replace(".tmp", ".pdf", new_temp_filename($this->parse['q']));
            $cmd = "/usr/local/bin/rasterizejs 'http://localhost".DIR_MAIN."review_ajax.php?".http_build_query($this->parse)."' '".$filepath."' A4";
            exec($cmd, $output, $result);

            if (file_exists($filepath)) {
                $filename = encodeDownloadFilename($_SESSION['login']['uid']."_".$_SESSION['login']['name'].".pdf");

                header("Pragma: private");
                header("Cache-Control: private");
                header("Content-disposition: attachment; filename=\"{$filename}\"");
                header("Content-type: application/octet-stream; name=\"{$filename}\"");
                readfile($filepath);
                exit;
            } else {
                print <<<__HTML__
<h1 class='noprint' style='padding:10px;width:100%;background:red;color:white;'>
PDFの生成に失敗しました。時間をおいて再度お試しください。
</h1>
__HTML__;
            }
        }
    }

    /**
     * メイン処理
     */
    public function main()
    {
        $this->resolveQueryStrings();
        $this->prepareSmarty();

        if ($this->isSecularTemplate()) {
            $this->setSecularTotalCal();
        } else {
            $this->setTotalCal();
            $this->setCommentToRand();
        }

        $this->assignSmartyParameters();
        $this->display();
    }

    /**
     * Smarty変数のアサイン
     */
    public function assignSmartyParameters()
    {
        // 共通
        $this->smarty->assign('subevents_choice',  $this->getSubeventsChoice());
        $this->smarty->assign('subevents_other',   $this->total->comments);
        $this->smarty->assign('self',              $this->total->target);
        $this->smarty->assign('userTypesWithSpan', $this->getUserTypesWithSpan());
        $this->smarty->assign('userTypes',         $this->getUserTypes());
        $this->smarty->assign('displayUserTypes',  $this->getDisplayUserTypes());
        $tmpParse = $this->parse;
        unset($tmpParse['secularYmd']);
        unset($tmpParse['template']);
        unset($tmpParse['user_type']);
        $this->smarty->assign('current_url', "./review.php?".http_build_query($tmpParse));
        $this->smarty->assign('dl_url_1', "./review.php?".http_build_query($this->parse)."&dl=1");
        $this->smarty->assign('dl_url_2', "./review.php?".http_build_query($this->parse)."&dl=2");

        if ($this->isSecularTemplate()) {
            // 経年ページのみ対象
            $this->smarty->assign('secularDatas',          $this->secularDatas);
//             $this->smarty->assign('average',               $this->total->average);
//             $this->smarty->assign('average_json',          $this->getAverageJson());
            $this->smarty->assign('category',              $this->total->ary_category);
//             $this->smarty->assign('category_json',         $this->getCategoryJson());

            $this->smarty->assign('currentUserType',       is_void($this->parse['user_type']) ? null : $this->parse['user_type']);
            $this->smarty->assign('feedbackNames',         $this->getSecularNames());
            $this->smarty->assign('feedbackNamesWithSpan', $this->getSecularNamesWithSpan());

            $feedbackTargets = array_merge($this->getCurrentSecularData(), $this->secularFeedbackController->feedbackTargets);
            sort($feedbackTargets);
            $this->smarty->assign('feedbackTargets', $feedbackTargets);

            $javascript = '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/raphael-min.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/smartGraphForSecular.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/jquery-1.7.1.min.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/currentButton.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/pageRefresh.js").'</script>';
            $this->smarty->assign('require_javascript', $javascript);
        } else {
            $this->smarty->assign('average',           $this->total->average);
            $this->smarty->assign('average_json',      $this->getAverageJson());
            $this->smarty->assign('category',          $this->total->ary_category);
            $this->smarty->assign('category_json',     $this->getCategoryJson());
            $this->smarty->assign('currentUserType',   is_void($this->parse['user_type']) ? null : $this->parse['user_type']);
            $this->smarty->assign('feedbackTargets',   $this->secularFeedbackController->feedbackTargets);

            // 経年非対象
            $this->smarty->assign('MaxData',           $this->total->MaxData);
            $this->smarty->assign('MinData',           $this->total->MinData);
            $this->smarty->assign('StdevData',         $this->total->StdevData);
            $this->smarty->assign('comments',          $this->total->ary_comment);
            $this->smarty->assign('count',             $this->total->count);

            $javascript = '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/raphael-min.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/smartGraph.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/jquery-1.7.1.min.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/currentButton.js").'</script>';
            $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/pageRefresh.js").'</script>';
            $this->smarty->assign('require_javascript', $javascript);
        }

        return $this;
    }

    /**
     * 平均値JSON取得
     */
    public function getAverageJson()
    {
        $average_json = array();
        foreach($this->total->average as $type => $values)
            $average_json[$type] = json_encode(array_values($values));

        return $average_json;
    }

    /**
     * 選択肢取得
     */
    public function getSubeventsChoice()
    {
        $subevents_choice = array();
        $category1 = '';
        $category2 = '';
        foreach ($this->total->SheetData as $num=>$title) {
            if(!$title) continue;
            if($category1 != $this->total->subevents[$this->total->header_num2seid[$num]]['category1']) {
                $row1 = 1;
                $category1_num = $num;
            } else {
                $rowspan1[$category1_num] = ++$row1;
            }
            if($category2 != $this->total->subevents[$this->total->header_num2seid[$num]]['category2']) {
                $row2 = 1;
                $category2_num = $num;
            } else {
                $rowspan2[$category2_num] = ++$row2;
            }
            $category1 = $this->total->subevents[$this->total->header_num2seid[$num]]['category1'];
            $category2 = $this->total->subevents[$this->total->header_num2seid[$num]]['category2'];
            $num_ext = $this->total->subevents[$this->total->header_num2seid[$num]]['num_ext'];
            $subevents_choice[$num] = array("title"=>$title, "category1"=>$category1, "category2"=>$category2, "num_ext"=>$num_ext);
        }
        // カテゴリー rowspan挿入
        foreach ($rowspan1 as $num => $rowspan)
            $subevents_choice[$num]['rowspan1'] = $rowspan;
        foreach ($rowspan2 as $num => $rowspan)
            $subevents_choice[$num]['rowspan2'] = $rowspan;

        return $subevents_choice;
    }

    /**
     * カテゴリJSON取得
     */
    public function getCategoryJson()
    {
        $category_json = array();
        foreach($this->total->ary_category as $type => $array)
            foreach($array as $user_type => $values)
                $category_json[$type][$user_type] = json_encode(array_values($values));

        return $category_json;
    }

    /**
     * 経年比較、利用開始準備
     */
    public function prepareSecular()
    {
        $this->secularFeedbackController = new SecularFeedbackController();
        $this->secularFeedbackController->connectSecularDatabase();
        $this->secularFeedbackController->setTargetDatas();
        $this->secularFeedbackController->connectDefaultDatabase();
        return $this;
    }

    /**
     * Smarty、利用開始準備
     */
    public function prepareSmarty()
    {
        $this->smarty = new MreSmarty();
        $this->smarty->caching = 0;
        $this->smarty->cache_lifetime = 1800;

        if ($this->smarty->is_cached("review.tpl", $this->data['uid'])) {
            if(DEBUG) print "<hr>smarty used cache.<hr>";
            $this->smarty->display('review.tpl', $this->data['uid']);
            exit;
        }
        return $this;
    }

    /**
     * 経年比較表示用
     */
    public function switchSecular()
    {
        if (is_good($this->parse['secularYmd'])) {
            if (is_good($this->secularFeedbackController->feedbackTargets[$this->parse['secularYmd']])) {
                $this->secularFeedbackController->connectSecularDatabase();
                $this->secularFeedbackController->createAccessTables($this->parse['secularYmd']);
            } else {
                return _360_error(6, 1);
            }
        }
        return $this;
    }

    /**
     * ユーザタイプの取得
     */
    public function getUserTypes()
    {
        $userTypes = array();
        foreach($GLOBALS['_360_user_type'] as $k => $v) {
            if ($k > INPUTER_COUNT) {
                continue;
            }
            $userTypes[$k] = $v;
        }
        return $userTypes;
    }

    /**
     * ユーザタイプの取得
     */
    public function getUserTypesWithSpan()
    {
        $userTypes = array();
        foreach($GLOBALS['_360_user_type'] as $k => $v) {
            if ($k > INPUTER_COUNT) {
                continue;
            }
            $userTypes[$k]['name'] = $v;
            $userTypes[$k]['span'] = $this->listHeaderSpans[$k];
        }

        return $userTypes;
    }

    /**
     * テンプレートをセット、表示
     */
    public function display()
    {
        $tmpl = 'review.tpl';
        if ($this->isSecularTemplate()) {
            if (count($this->getSecularNames()) == 1) {
                return _360_error(6, 1);
            }
            $tmpl = 'secular_review.tpl';
        }
        $this->smarty->display($tmpl, $this->data['uid']);
        return $this;
    }

    /**
     * 経年比較用のテンプレートか
     */
    public function isSecularTemplate()
    {
        if (is_good($this->parse['template']) && $this->parse['template'] == 'secular') {
            return true;
        }
        return false;
    }

    /**
     * アクセスURLをパース
     */
    public function resolveQueryStrings()
    {
        $query = $_SERVER['QUERY_STRING'];
        $query = preg_replace('/&.*/', '', $query);
        $this->data = _360_resolveQueryString($query);
        define("ENQ_RID", $this->data['rid']);
        return $this;
    }

    /**
     * コメントをランダムにセット
     */
    public function setCommentToRand()
    {
        global $Setting;

        if($Setting->randComments() && is_array($this->total->ary_comment['others']))
            foreach ($this->total->ary_comment['others'] as $key => $values)
                shuffle($this->total->ary_comment['others'][$key]);

        return $this;
    }

    /**
     * 標準版表示内容の取得、メイン処理
     */
    public function setSecularTotalCal()
    {
        $this->total = new ResultSecularTotalCal();
        $this->total->setTargetSerial($this->data['target']);
        $this->total->setSheetType(getEvidByRid($this->data['rid'])/100);
        $this->total->setUserType($this->parse['user_type']);
        $this->total->enableFeedback();

        // 当年データ作成
        $this->total->prepare();
        $this->total->run();

        $this->secularDatas[""]['average']       = $this->total->average;
        $this->secularDatas[""]['average_json']  = $this->getAverageJson();
        $this->secularDatas[""]['category']      = $this->total->ary_category;
        $this->secularDatas[""]['category_json'] = $this->getCategoryJson();
        $this->secularDatas[""]['secular']       = $this->getCurrentSecularData();

        // 経年比較DB切り替え
        $this->prepareSecular();
        foreach ($this->secularFeedbackController->feedbackTargets as $ymd => $target) {
            $this->total = new ResultSecularTotalCal();
            $this->total->setTargetSerial($this->data['target']);
            $this->total->setSheetType(getEvidByRid($this->data['rid'])/100);
            $this->total->setUserType($this->parse['user_type']);
            $this->total->enableFeedback();
            $this->secularFeedbackController->connectSecularDatabase();
            $this->secularFeedbackController->createAccessTables($ymd);
            $this->total->prepare();
            // デフォルトDBへ戻し
            $this->secularFeedbackController->dropAccessTables($ymd);
            $this->secularFeedbackController->connectDefaultDatabase();

            $this->total->run();

            $this->secularDatas[$ymd]['average']       = $this->total->average;
            $this->secularDatas[$ymd]['average_json']  = $this->getAverageJson();
            $this->secularDatas[$ymd]['category']      = $this->total->ary_category;
            $this->secularDatas[$ymd]['category_json'] = $this->getCategoryJson();
            $this->secularDatas[$ymd]['secular']       = $target;
        }
        return $this;
    }

    /**
     * 標準版表示内容の取得、メイン処理
     */
    public function setTotalCal()
    {
        $this->total = new ResultTotalCal();
        $this->total->setTargetSerial($this->data['target']);
        $this->total->setSheetType(getEvidByRid($this->data['rid'])/100);
        $this->total->enableFeedback();

        // 経年比較DB切り替え
        $this->prepareSecular();
        $this->switchSecular();

        $this->total->prepare();

        // デフォルトDBへ戻し
        $this->secularFeedbackController->connectDefaultDatabase();

        $this->total->run();
        return $this;
    }

    public function getSecularNames()
    {
        $ret = array('当年度');
        foreach ($this->secularFeedbackController->feedbackTargets as $feedbackTarget) {
            $ret[] = $feedbackTarget['name'];
        }
        return $ret;
    }

    public function getSecularNamesWithSpan()
    {
        $ret = array(array(
            'name' => '当年度',
            'span' => $this->listHeaderSpansForSecular[0],
        ));
        $i = 1;
        foreach ($this->secularFeedbackController->feedbackTargets as $feedbackTarget) {
            $ret[$i]['name'] = $feedbackTarget['name'];
            $ret[$i]['span'] = $this->listHeaderSpansForSecular[$i];
            $i++;
        }
        return $ret;
    }

    public function getCurrentSecularData()
    {
        $ret = array(
            'name' => '当年度',
            'hash' => '',
            'tables' => array(),
        );
        return $ret;
    }

    public function displayLoadingTemplate()
    {
//         $this->resolveQueryStrings();
        $this->prepareSmarty();
        $this->smarty->assign('ajax_url', "./review_ajax.php?".http_build_query($this->parse));
        $javascript .= '<script type="text/javascript" charset="utf-8">'.file_get_contents(DIR_LIB."smartGraph/jquery-1.7.1.min.js").'</script>';
        $this->smarty->assign('require_javascript', $javascript);
        $this->smarty->display('review_loading.tpl', $this->data['uid']);
    }

    public function getDisplayUserTypes()
    {
        $userTypes = $this->getUserTypes();
        $userTypes['others'] = '他者';
        return $userTypes;
    }
}
