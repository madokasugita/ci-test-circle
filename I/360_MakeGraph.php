<?php
define('MBTTF_DIR', 'JpGraph/truetype/');
define('GOTHIC_TTF_FONT','sazanami-gothic.ttf');
// 使用するグラフを読み込む
require_once 'JpGraph/jpgraph.php';
require_once 'JpGraph/jpgraph_radar.php';
require_once 'JpGraph/jpgraph_line.php';

class MakeGraph
{
    public $width;
    public $height;
    public $font;

    /**
     * コンストラクタ
     */
    public function MakeGraph($width, $height)
    {
        $this->width = $width;
        $this->height = $height;
        $this->setFont();
    }

    /**
     * フォント設定
     */
    public function setFont($family = FF_GOTHIC, $style = FS_NORMAL, $size = 9)
    {
        $this->font = array (
            $family,
            $style,
            $size
        );
    }

    /**
     * 画像表示
     */
    public function showImage($aryTitle, $aryData)
    {
        $this->makeImage($aryTitle, $aryData, null);
    }

    /**
     * 画像作成
     */
    public function makeImage($aryTitle, $aryData, $imageName)
    {
        // 継承先で実装
    }

    /**
     * 色取得
     */
    public $color_cnt;
    public function getColor()
    {
        switch ($this->color_cnt++) {
            case 0 :
                return "#ff0099";
            case 1 :
                return "#33cc99";
            case 2 :
                return "#008800";
            case 3 :
                return "#008888";
            case 4 :
                return "#888800";
            default :
                return "#888888";
        }
    }

    /**
     * マーク収録
     */
    public $mark_cnt;
    public function getMark()
    {
        switch ($this->mark_cnt++ % 5) {
            case 0 :
                return MARK_FILLEDCIRCLE;
            case 2 :
                return MARK_UTRIANGLE;
            case 3 :
                return MARK_DTRIANGLE;
            default :
                return MARK_SQUARE;
        }
    }

    /**
     * 文字数を丸める
     */
    public function roundStrlen($string, $length = 17)
    {
        return mb_strimwidth($string, 0, 1000, "…");
    }

    /**
     * グラフを逆回転表示
     */
    public function reverseEle($aryEle)
    {
        array_push($aryEle, array_shift($aryEle));

        return array_reverse($aryEle);
    }
}

/**
 * レーダーチャート
 */
class MakeRadarGraph extends MakeGraph
{
    public $scale;

    /**
     * コンストラクタ
     */
    public function MakeRadarGraph($width, $height)
    {
        parent :: MakeGraph($width, $height);
        $this->setScale();
    }

    /**
     * スケール設定
     */
    public function setScale($min = 0, $max = 5, $interval = 1)
    {
        $this->scale = array (
            'min' => $min,
            'max' => $max,
            'interval' => $interval
        );
    }
    /**
     * 画像作成
     */
    public function makeImage($aryTitle, $aryData, $imageName)
    {
        $graph = & new RadarGraph($this->width, $this->height);
        $graph->img->SetAntiAliasing();

        $graph->setFrame(false);
        $graph->SetColor("#ffffff"); // バックカラー
        $graph->SetCenter(0.5, 0.5); // レーダーチャートの位置

        // グラフの最大数設定　lin, minpos, maxpos
        $graph->SetScale('lin', $this->scale['min'], $this->scale['max']);
        $graph->yscale->ticks->Set($this->scale['interval']); // グラフのメモリ (刻み)

        // 軸の日本語化と色設定
        $graph->axis->SetFont($this->font[0], $this->font[1], $this->font[2]);
        $graph->axis->title->SetFont($this->font[0], $this->font[1], $this->font[2]);
        $graph->axis->title->SetColor("#838B8B");
        $graph->axis->SetColor("#838B8B");
        $graph->axis->SetWeight(1); // 中心から放射状に伸びる線の太さ

        // ラインの設定
        $graph->grid->SetColor("#838B8B");
        $graph->grid->Show();
        $graph->HideTickMarks();

        $aryTitle = array_reflex($aryTitle, array (
            $this,
            'roundStrlen'
        ));
        $graph->setTitles($this->reverseEle(array_reflex($aryTitle, 'encodeJpGraph')));
        $graph->legend->SetFont($this->font[0], $this->font[1], $this->font[2]);
        //$graph->legend->SetLayout(LEGEND_HOR);
        $graph->legend->SetFillColor("#ffffff");
        $graph->legend->SetColor("#838B8B");
        $graph->legend->SetShadow(false);
        $graph->legend->SetPos(0.01, 0.85);
        $graph->legend->SetLineSpacing(4);
        $graph->legend->SetFrameWeight(0);
        $count = 0;
        foreach ($aryData as $legend => $aryPlot) {
            $color = $this->getColor();
            $plot = new RadarPlot($this->reverseEle($aryPlot));
            $plot->SetLegend(encodeJpGraph($legend . "     "));
            $plot->SetColor($color, $color);
            $plot->SetFill(false);
            $plot->SetLineWeight(2);
            $plot->mark->SetType($this->getMark());
            $plot->mark->SetColor($color);
            $plot->mark->SetFillColor($color);
            $graph->Add($plot);
        }

        (is_void($imageName)) ? $graph->Stroke() : $graph->Stroke($imageName); // 描画
    }
}

/**
 * ラインプロット
 */
class MakeLineGraph extends MakeGraph
{
    public $scale;

    /**
     * コンストラクタ
     */
    public function MakeLineGraph($width, $height)
    {
        //if (TYPE == 'lfb3')
        //	$height = 412;
        parent :: MakeGraph($width, $height);
        $this->setScale();
    }

    /**
     * スケール設定
     */
    public function setScale($min = 0, $max = 5)
    {
        $this->scale = array (
            'min' => $min,
            'max' => $max
        );
    }

    /**
     * 画像作成
     */
    public function makeImage($aryTitle, $aryData, $imageName)
    {
        $graph = & new Graph($this->width, $this->height);
        $graph->img->SetAntiAliasing();

        $graph->setFrame(false);
        $graph->SetColor("#ffffff"); // バックカラー
        $graph->SetMarginColor("#ffffff");

        // グラフの最大数設定　lin, minpos, maxpos
        $graph->SetScale('lin', $this->scale['min'], $this->scale['max']);
        $graph->xaxis->Hide();
        $graph->yaxis->Hide();

        for ($i = $this->scale['min']; $i <= $this->scale['max']; ++ $i) {
            $plot = & new LinePlot(array_fill(0, count($aryData["本人"]), $i));
            $plot->SetColor("#888888", "#888888");
            $graph->Add($plot);
        }
        /*
        $graph->legend->SetFont($this->font[0], $this->font[1], $this->font[2]);
        $graph->legend->SetLayout(LEGEND_HOR);
        $graph->legend->SetFillColor("#ffffff");
        $graph->legend->SetShadow(false);
        $graph->legend->SetPos(0, 0);
        $graph->legend->Hide();
        */
        $count = 0;
        foreach ($aryData as $legend => $aryPlot) {
            $color = $this->getColor();
            $plot = & new LinePlot($aryPlot);
            //	$plot->SetLegend(encodeJpGraph($legend."     "));
            $plot->SetColor($color, $color);
            $plot->SetLineWeight(2);
            $plot->mark->SetType($this->getMark());
            $plot->mark->SetColor($color);
            $plot->mark->SetFillColor($color);
            $graph->Add($plot);
        }
        if (count($aryData["本人"]) == 23)
            $graph->Set90AndMargin(10, 10, 10, 10);
        else
            $graph->Set90AndMargin(10, 10, 10, -10);

        (is_void($imageName)) ? $graph->Stroke() : $graph->Stroke($imageName); // 描画
    }
}

/**
 * グラフA作成
 */
function makeGraphA($aryData, $id)
{
    switch (TYPE) {
        case "lfb1" :
        case "lfb2" :
        case "lfb3" :
            $aryTitle = array (
                "部下の成長支援",
                "部のモチベート",
                "部下との信頼関係",
                "部長の考えへの共感",
                "部・部下のリード　　　　　　",
                "部による価値創造　　　　　　",

            );
            break;
        case "lfb2" :
            $aryTitle = array (
                "部下の成長支援",
                "課のモチベート",
                "部下との信頼関係",
                "課長の考えへの共感",
                "課・部下のリード　　　　　　",
                "課による価値創造　　　　　　"
            );
            break;
    }
    $aryData = array (
        "本人" => $aryData[0],
        "他者" => $aryData[1]
    );
    $graph = & new MakeRadarGraph(300, 200);
    $graph->setScale(0, 6, 1);

    $imageName = getGraphImageName($id, 1);
    $graph->makeImage($aryTitle, $aryData, $imageName);

    return getGraphImageName($id, 1, true);
}

/**
 * グラフB作成
 */
function makeGraphB($aryData, $id)
{
    switch (TYPE) {
        case "lfb1" :
        case "lfb2" :
        case "lfb3" :
            $aryTitle = array (
                "全社の経営計画への関与",
                "\n\n　　全社の経営への\n　　　　　フォロワーシップ",
                "\n部の事業計画\nの立案",
                "部の組織運営\n計画の立案",
                "目標設定",
                "プロセス管理",
                "評価・\nフィードバック",
                "社内外との連携　　　　",
                "成長目標の共有　　　　　",
                "部下への権限委譲　　　　",
                "チームの活性化"
            );
            break;
        case "lfb2" :
            $aryTitle = array (
                "部の経営計画への関与",
                "\n\n　　部の組織運営への\n　　　　　フォロワーシップ",
                "\n課の事業計画立案",
                "課の組織運営\n　　　　　計画の立案",
                "目標設定",
                "プロセス管理",
                "評価・\nフィードバック",
                "社内外との連携　　　　",
                "成長目標の共有　　　　　　　",
                "部下への権限委譲　　　　　　",
                "チームの活性化"
            );
            break;
    }

    $aryUnset = array (
        0,
        3,
        6,
        11,
        14
    );
    foreach ($aryUnset as $unset) {
        unset ($aryData[0][$unset]);
        unset ($aryData[1][$unset]);
    }
    $aryData = array (
        "本人" => $aryData[0],
        "他者" => $aryData[1]
    );
    $graph = & new MakeRadarGraph(300, 200);
    $graph->setScale(0, 6, 1);

    $imageName = getGraphImageName($id, 2);
    $graph->makeImage($aryTitle, $aryData, $imageName);

    return getGraphImageName($id, 2, true);
}

/**
 * グラフC作成
 */
function makeGraphC($aryData, $id)
{

    $aryTitle = array (
        "ビジョン・価値観を示す",
        "変革する",
        "グローバルな視野\nを持つ",
        "\n決断し前に\n進める",
        "専門軸を持つ",
        "信頼関係を築く\n\n　",
        "人の成長を重視する",
        "\n顧客にとっての価値にこだわる",
        "他者を尊重する",
        "誠実に行動する　　　　　",
        "自己を律する　　　　　",
        "\nチャレンジマインド　　　　　\nを持つ",
        "人生を楽しむ"
    );

    $aryData = array (
        "本人" => $aryData[0],
        "他者" => $aryData[1]
    );
    $graph = & new MakeRadarGraph(300, 200);
    $graph->setScale(0, 6, 1);

    $imageName = getGraphImageName($id, 3);
    $graph->makeImage($aryTitle, $aryData, $imageName);

    return getGraphImageName($id, 3, true);
}

/**
 * グラフD作成
 */
function makeGraphD($aryData, $id)
{
    $aryData = array (
        "本人" => $aryData[0],
        "他者" => $aryData[1]
            //		"上司" => $aryData[2],
        //		"部下" => $aryData[3],
        //		"同僚" => $aryData[4]

    );
    $graph = & new MakeLineGraph(105, 970);
    $graph->setScale(0, 6);

    $imageName = getGraphImageName($id, 4);
    $graph->makeImage(array (), $aryData, $imageName);

    return getGraphImageName($id, 4, true);
}

/**
 * グラフ画像名取得
 */
function getGraphImageName($id, $graph, $no_dir = false)
{
    if ($no_dir == true) {
        return DIR_SYS_ROOT . "feedbackimg/graph_{$id}_{$graph}_" . md5(SYSTEM_RANDOM_STRING . $id . $graph) . ".png";
    }

    return DIR_SYS_ROOT . "feedbackimg/graph_{$id}_{$graph}_" . md5(SYSTEM_RANDOM_STRING . $id . $graph) . ".png";
}
