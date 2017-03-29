<?php

/**
 * アンケート操作ライブラリ
 * 作成日：2006/09/12
 * @package Cbase.Research.Lib
 * @version 1.1 2007/06/27 設問を削除したときに、eventのlastpageも更新するように
 * @version 1.1.1 2007/06/27 設問を削除したときに、eventのlastpageも更新するように( 設問が0個になった場合に対応)
 *
 * 2007/07/25 ver1.2 correctPageNumber() 関数実装 Delete_Subevent内で実行
 * 2007/07/25 ver1.3 質問追加時にページが1になってしまうバグを修正
 * 2007/09/04 ver1.4 管理画面ではキャッシュを消さない・読まない・作らない　ように
 * 2007/11/22 ver1.5 save_enquete save_subenqueteをFDBを使うように
 * 2007/12/18 ver1.6 Get_Enquete時の権限による絞込みを追加
 **/
//IEの画像submitの判断には["main_x"]
//rowsの更新で0をしていしても反映されない

require_once 'CbaseFDB.php';
include_once 'CbaseFDBClass.php';
include_once 'CbaseFGeneral.php';
include_once 'CbaseEnquete.php';
include_once 'CbaseFEnqueteCache.php';
include_once 'CbaseAuthSet.php';

/***********************************************************************************************************************/
$DEFINE_COLS_EVENT = array (
    'evid',
    'rid',
    'name',
    'type',
    'flgs',
    'flgl',
    'flgo',
    'limitc',
    'point',
    'mfid',
    'htmlh',
    'htmlm',
    'htmlf',
    'url',
    'setting',
    'sdate',
    'edate',
    'cdate',
    'udate',
    'muid',
    'htmls',
    'htmls2',
    'lastpage',
    'randomize',
    'mailaddress',
    'mailname',
    'id',
    'pw'
);

$DEFINE_COLS_SUBEVENT = array (
    'seid',
    'evid',
    'title',
    'type1',
    'type2',
    'choice',
    'hissu',
    'width',
    'rows',
    'word_limit',
    'cond',
    'page',
    'other',
    'html1',
    'html2',
    'cond2',
    'cond3',
    'cond4',
    'cond5',
    'ext',
    'fel',
    'chtable',
    'matrix',
    'randomize'
);

/***********************************************************************************************************************/

function fatal_error($message)
{
    print $message;
    exit;
}

/**
 * アンケート(event)を保存
 * @param string $mode new or update
 * @param array $array アンケート内容
 * @return int Evid
 */
function Save_Enquete($mode = "new", $array)
{
    global $DEFINE_COLS_EVENT;
    foreach ($DEFINE_COLS_EVENT as $col) {
        if (isset ($array[$col]))
            $data[$col] = $array[$col];
    }
    if ($mode == "new") {
        /*** ↓デフォルト値のセット↓ ***/
        $data['rid'] = Get_RandID(8);
        //$data['evid'] = FDB :: getNextVal('evid');
        $data['evid'] = null;
        $data['type'] = isset ($data['type']) ? $data['type'] : 1;
        $data['flgs'] = isset ($data['flgs']) ? $data['flgs'] : 0;
        $data['flgo'] = isset ($data['flgo']) ? $data['flgo'] : 0;
        $data['flgl'] = isset ($data['flgl']) ? $data['flgl'] : 0;
        $data['point'] = isset ($data['point']) ? $data['point'] : 0;
        $data['mfid'] = isset ($data['mfid']) ? $data['mfid'] : 0;
        $data['cdate'] = date("Y-m-d H:i:s");
        $data['udate'] = date("Y-m-d H:i:s");
        $data['muid'] = $_SESSION['muid'];
        $data['lastpage'] = $data['lastpage'] ? $data['lastpage'] : 1;
        $data['randomize'] = $data['randomize'] ? $data['randomize'] : null;
        /*** ↑デフォルト値のセット↑ ***/

        //if (!is_numeric($data['evid']))
        //	fatal_error('save_enquete:evid_error');

        if (is_false(FDB :: insert(T_EVENT, FDB :: escapeArray($data))))
            fatal_error('save_enquete:insert_error');
        $data['evid'] = FDB::getLastInsertedId();

    } elseif ($mode == "update") {
        $data['udate'] = date("Y-m-d H:i:s");
        unset ($data['muid']); //update時にmuidは書き換えない
        if (!ereg("^[0-9]{1,3}$", $data['lastpage']))
            unset ($data['lastpage']);

        if (!$array['sdate'])
            $data['sdate'] = null;
        if (!$array['edate'])
            $data['edate'] = null;

        if (is_false(FDB :: update(T_EVENT, FDB :: escapeArray($data), 'where evid = ' . FDB :: escape($data['evid']))))
            fatal_error('save_enquete:update_error');
    }

    return $data['evid'];
}

/**
 * 質問をDBに登録
 * @param string $mode new or update
 * @param array $array 質問内容
 * @return int Seid
 */
function Save_SubEnquete($mode = "new", $array)
{
    global $DEFINE_COLS_SUBEVENT;
    foreach ($DEFINE_COLS_SUBEVENT as $col) {
        if (isset ($array[$col]))
            $data[$col] = $array[$col];
    }
    if ($mode == "new") {
        ///*** ↓デフォルト値のセット↓ ***/
        //if(!is_numeric($data['seid']))
        //$data['seid'] = FDB :: getNextVal('seid');
        $seid = FDB::select1(T_EVENT_SUB, 'max(seid)+1 as seid', "WHERE floor(seid/1000) = ".FDB::escape($array['evid']));
        if(is_false($seid)) fatal_error('save_subenquete:seid_error');
        $data['seid'] = (is_good($seid['seid']))? $seid['seid']:$array['evid']."001";

        $data["other"] = $data["other"] ? 1 : 0;
        $data["page"] = $data["page"] ? $data["page"] : 1;
        /*** ↑デフォルト値のセット↑ ***/
        //if (!is_numeric($data['seid']))
        //	fatal_error('save_subenquete:seid_error');

        if (is_false(FDB :: insert(T_EVENT_SUB, FDB :: escapeArray($data))))
            fatal_error('save_subenquete:insert_error');
        //$data['seid'] = FDB::getLastInsertedId();
    } elseif ($mode == "update") {
        if (!is_numeric($data['seid']))
            fatal_error('save_subenquete:invalid_seid');

        if (is_false(FDB :: update(T_EVENT_SUB, FDB :: escapeArray($data), 'where seid = ' . FDB :: escape($data['seid']))))
            fatal_error('save_subenquete:update_error');
    }

    return $data['seid'];
}

/**
 * subevent_dataテーブルから指定の条件に一致するデータを取得する
 * 		とりあえずの使い方は、arrayのcountでデータが存在しているかの重複チェック
 * @param int $prmSeid seid
 * @param string $prmData data    (条件:カラムの値)
 * @param string $prmColumn column (subevent_dataのカラム指定)
 * @return array Array
 */
function getSubeventData($prmSeid, $prmData, $prmColumn = "other")
{
    global $con;
    $aryTmp = array ();
    //パラメータチェック
    if (!$prmSeid)
        return $aryTmp;
    if (!$prmData)
        return $aryTmp;

    //sql発行
    $sql = "select * from " . T_EVENT_SUB_DATA . " ";
    $sql .= "where seid = " . FDB::quoteSmart($prmSeid) . " ";
    $sql .= "and " . $prmColumn . " = " . sql_escape(trim($prmData));

    return FDB :: getAssoc($sql);
}

/**
 * 指定した質問を削除
 * @param int $seid 質問ID
 */
function Delete_Subevent($seid, $evid = null)
{
    global $con;
    if (!$seid)
        return false;
    //対象データを削除
    $sql = "delete from " . T_EVENT_SUB . " ";
    $sql .= "where seid = " . $seid;
    //SQL実行
    $con->query($sql);

    //ver1.1
    if (!$evid) {
        $subevent = FDB :: select1(T_EVENT_SUB, 'evid', 'where seid = ' . FDB :: escape($seid));
        $evid = $subevent['evid'];
    }
    correctPageNumber($evid); //連番になるようにする //ver1.2/
    $max = FDB :: select(T_EVENT_SUB, 'max(page) as lastpage', 'where evid = ' . FDB :: escape($evid));
    $data = $max[0];
    if (!$data['lastpage']) {
        $data['lastpage'] = 1;
    } //ver1.1.1
    FDB :: update(T_EVENT, $data, 'where evid = ' . FDB :: escape($evid));
    //ver1.1 ここまで

    //clearCacheBySeid ($seid);//ver1.4/
}
//$seidの直前にインサートする
/**
 * 指定した質問の直前に質問を追加する
 * @param int $evid アンケートID
 * @param int $seid 質問ID
 * @param array $overwrite 上書き用配列
 */
function Insert_Subevent($evid, $seid, $overwrite = array ())
{
    global $con;
    if (!$evid || !$seid)
        return false;
    $array = Get_Enquete("id", $evid, "", "");

    //挿入後移動されるデータを配列にセット
    $i = 0;
    $del = array ();
    foreach ($array[0] as $val) {
        if ($val["seid"] == $seid) {
            $i = 1;
            $lastpage = $val["page"];
        }
        $newseid[$val["seid"]] = $val["seid"];
        //		if ($val["seid"]==$seid) $i=1;
        if ($i == 0)
            continue;
        $new[] = $val; //インサート点後の質問データを配列に格納
        $del[] = $val["seid"]; //$newの中にある質問データseidを格納
    }
    if (!$new)
        return false;

    FDB::begin();

    //インサート点で移動する質問データを残しておく
    //$sql = "update " . T_EVENT_SUB . " ";
    //$sql .= "set evid = 0 ";
    //$sql .= "where evid = " . $evid . " ";
    //$sql .= "and   seid >= " . $del[0] . " ";
    if ($del) {
        //$rs = $con->query($sql);
        $rs = FDB :: delete(T_EVENT_SUB, "WHERE evid = ". FDB::escape($evid)." AND seid >= ". FDB::escape($del[0]));
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        }
    }

    //新レコード挿入
    $insertdata = array (
        "seid" => "new",
        "evid" => $evid,
        "title" => "追加",
        "type1" => 0,
        "type2" => "r",
        "choice" => "",
        "hissu" => 1,
        "width" => 50,
        "rows" => 1,
        "word_limit" => "",
        "cond" => "",
        "page" => $lastpage,
        "other" => 0,
        "html1" => "",
        "html2" => ""
    );
    foreach ($overwrite as $key => $val)
        $insertdata[$key] = $val;

    $new_seid = Save_SubEnquete("new", $insertdata);
    if (!$new_seid) {
        FDB::rollback();

        return false;
    }

    //移動する質問データを登録
    foreach ($new as $val) {
        //condのseid置換
        if ($val["cond"]) {
            //古いseid指定を新しいseidに置き換える
            $newcond = array ();
            $cond = unserialize($val["cond"]);
            foreach ($cond as $v) {
                $tmpk = key($v);
                $newcond[] = array (
                    $newseid[$tmpk] => current($v
                ));
            }
            $val["cond"] = serialize($newcond);
        }
        ///////////////////////////
        //cond3対応。seidの差し替え
        //cond4対応。seidの差し替え
        //cond5対応。seidの差し替え
        //////////////////////////

        $val["html1"] = $val["html1"];
        $val["html2"] = $val["html2"];
        $nid = Save_SubEnquete("new", $val);
        //複製の場合に新規のseidを配列で保有し、condのseid置換に利用する
        $newseid[$val["seid"]] = $nid;
    }

    FDB::commit();

    return $new_seid;
}

/**
 * 指定したアンケートをコピーする
 * @param int $evid アンケートID
 */
/**
 * 指定したアンケートをコピーする
 * @param int $evid アンケートID
 */
function Duplicate_Enquete($evid)
{
    //新cond設定用 新seid配列
    $newseid = array ();
    if (!$evid)
        return false;
    $array = Get_Enquete("id", $evid, "", "");
    if (!$array)
        return false;
    $array[-1]["name"] = getCopyName($array[-1]["name"]);
    $array[-1]["htmlh"] = $array[-1]["htmlh"];
    $array[-1]["htmlm"] = $array[-1]["htmlm"];
    $array[-1]["htmls"] = $array[-1]["htmls"];
    $array[-1]["htmlf"] = $array[-1]["htmlf"];

    $newid = Save_Enquete("new", $array[-1]);

    Duplicate_Subevent ($newid, $array[0]);

    return $newid;
}

function replaceNewId($match, $newId)
{
    return '%%%%ID'.$newId[$match[1]].'%%%%';
}

function replaceNewOther($match, $newId)
{
    return '%%%%other:'.$newId[$match[1]].'%%%%';
}

//TODO:CbaseEnquete等に移動したい
//subeventsをnewidでコピー
function Duplicate_Subevent($evid, $subevents)
{
    include_once 'CbaseEnqueteConditions.php';

    //末尾のページを取る
    $newSeids = array();
    foreach ($subevents as $vs) {
        $vs["evid"] = $evid;

        //条件は全て置換
        //前から順番に処理しているので、$newSubEventsに該当がない場合は削除してよいとみなす
        if ($vs['cond']) {
            $cond =& new Cond1Condition($vs, $vs['cond']);
            $cond->replaceSeid($newSeids);
            $vs['cond'] =$cond->getCondition();
        }
        //cond2は自問条件のためそのままコピー　$vs["cond2"] = "";

        if ($vs['cond5']) {
            $cond =& new Cond5Condition($vs, $vs['cond5']);
            $cond->replaceSeid($newSeids);
            $vs['cond5'] =$cond->getCondition();
        }

        $vs['html1'] = PregReplaceCallback::run(
            '/%%%%ID([0-9]+)%%%%/i',
            'replaceNewId',
            $newSeids,
            $vs['html1']);

        $vs['html1'] = PregReplaceCallback::run(
            '/%%%%other:([0-9]+)%%%%/i',
            'replaceNewOther',
            $newSeids,
            $vs['html1']);

        $vs['html2'] = PregReplaceCallback::run(
            '/%%%%ID([0-9]+)%%%%/i',
            'replaceNewId',
            $newSeids,
            $vs['html2']);

        $vs['html2'] = PregReplaceCallback::run(
            '/%%%%other:([0-9]+)%%%%/i',
            'replaceNewOther',
            $newSeids,
            $vs['html2']);

        $newid = Save_SubEnquete("new", $vs );
        if (!$newid) {
            echo "質問のコピーエラー。".$vs["seid"]."番以降と条件はコピーされません";
            exit;
        }
        $newSeids[$vs["seid"]] = $newid;

        //cond3-4は自問を条件に取るためインサート後にアップデートにする
        $changed = false;
        if ($vs['cond3']) {
            $cond =& new Cond3Condition($vs, $vs['cond3']);
            if ($cond->replaceSeid($newSeids)) {
                $vs['cond3'] =$cond->getCondition();
                $changed = true;
            }
        }
        if ($vs['cond4']) {
            $cond =& new Cond4Condition($vs, $vs['cond4']);
            if ($cond->replaceSeid($newSeids)) {
                $vs['cond4'] =$cond->getCondition();
                $changed = true;
            }
        }
        if ($changed) {
            $vs['seid'] = $newid;
            Save_SubEnquete("update", $vs );
        }

    }

    return true;

}

//TODO:とりあえずduplicateで使うのでここにおきます。適宜移動してください
class PregReplaceCallback
{
    /**
     * preg_replace_callbackを引数付きで実行できるstaticメソッド
     * PregReplaceCallback::runで使用可能
     * @param  string   $regex        正規表現
     * @param  callback $callback     コールバック関数
     * @param  mixied   $callbackArgs コールバックに送る引数。適宜配列など利用のこと
     * @param  string   $subject      変換対象の文字列
     * @return string   変換後の文字列
     */
    public function run($regex, $callback, $callbackArgs, $subject)
    {
        $self =& new PregReplaceCallback();
        $self->args = $callbackArgs;
        $self->callback = $callback;

        return preg_replace_callback($regex, array($self, 'wrap'), $subject);
    }

    //以下はクラス内で使う
    public $args;
    public $callback;
    public function wrap($match)
    {
        return  call_user_func_array($this->callback, array($match, $this->args));
    }
}

/**
 * ファイルからアンケートを読み込む
 * @param string $file ファイル名
 */
function Get_EnqueteFromFile($file)
{
    if (SHOW_ABOLITION) {
        echo 'Get_EnqueteFromFileは廃止関数です。FEnqueteCache::loadを使用してください<hr>';
    }
    include_once 'CbaseFEnqueteCache.php';

    return FEnqueteCache::load($file);
}

/**
 * アンケートのキャッシュをファイルに作成する (DBアクセス減少のため)
 * @param array $array アンケート内容
 * @param string $file ファイル名
 */
function Make_CacheFile($array, $file)
{
    $array[-1]["htmlh"] = ereg_replace("\n|\r\n|\t", "", $array[-1]["htmlh"]);
    $array[-1]["htmls"] = ereg_replace("\n|\r\n|\t", "", $array[-1]["htmls"]);
    $array[-1]["htmls2"] = ereg_replace("\n|\r\n|\t", "", $array[-1]["htmls2"]);
    $array[-1]["htmlm"] = ereg_replace("\n|\r\n|\t", "", $array[-1]["htmlm"]);
    $array[-1]["htmlf"] = ereg_replace("\n|\r\n|\t", "", $array[-1]["htmlf"]);
    for ($i = 0; $i < count($array[0]); ++ $i) {
        $array[0][$i]["html1"] = ereg_replace("\n|\r\n|\t", "", $array[0][$i]["html1"]);
        $array[0][$i]["html2"] = ereg_replace("\n|\r\n|\t", "", $array[0][$i]["html2"]);
    }

    $cont = serialize($array);
    $zp = fopen($file, "w");
    fwrite($zp, $cont);
    fclose($zp);
}

//$mode	all(limit 30),rid,id
/**
 * アンケートを取得 (キャッシュファイルがある場合はDBにアクセスしない)
 * @param mixid $mode rid,idどちらで検索するか
 * @param mixid	 $value rid or id の値
 * @param string $orderk ソートのキー
 * @param string $orderflg descを指定した場合逆順ソート
 * @param int $muid 管理者id
 */

//管理画面でevent取得するときに、$_GET["evid"]などの値をmuidで絞る(チェックする)機構が必要

function Get_Enquete($mode = -1, $value, $orderk, $orderflg, $muid = "")
{
    //	global $con; //Pear::DBconnect
    //	$ext = ".enqarray";
    global $Setting;

    if ($Setting->sheetModeCollect()) {
        if ($mode == 'rid') {
            $evid_org = $evid = getEvidByRid($value);
            $rid_org = $value;
            if($evid%100 > 1)
                $evid = (round($evid/100)*100)+1;

            $value= getRidByEvid($evid);
        } else {
            $evid_org = $evid = $value;
            $rid_org = $value;
            if($evid%100==4)
                $evid = $evid-3;
            if($evid%100==3)
                $evid = $evid-2;
            if($evid%100==2)
                $evid = $evid-1;
            $value = $evid;
        }
    }

    //管理画面はキャッシュ読まない//ver1.4/
    if (NOT_USE_CHACHE == 1 || ereg(DIR_MNG, $_SERVER["SCRIPT_NAME"]) || ereg('test_index.php', $_SERVER["SCRIPT_NAME"])) {
        if (DEBUG) {
            print "<hr>キャッシュを読まずにDBを読みました";
        }

        return Get_Enquete_Main($mode, $value, $orderk, $orderflg, (int) $muid);
    }

    if ($value && $mode == "rid") {

        if (DEBUG) { //ver1.4/
            print "<hr>キャッシュ読みました";
        }
        if (is_file(DIR_CACHE . $value . EXT_CACHE_U)) {
            //IDなしキャッシュファイルがある場合は、そちらからリードする
            $array = FEnqueteCache::load(DIR_CACHE . $value . EXT_CACHE_U);
        } else {
            //無い場合はつくる
            $array = FEnqueteCache :: create($value);
        }
    } else {
        if (DEBUG) { //ver1.4/
            print "<hr>キャッシュを読まずにDBを読みました";
        }
        $array = Get_Enquete_Main($mode, $value, $orderk, $orderflg, (int) $muid);
    }
    if ($Setting->sheetModeCollect()) {
        $array[-1]['rid'] = $rid_org;
        $array[-1]['evid'] = $evid_org;
        if (is_array($array[0])) {
            foreach ($array[0] as &$subevent) {
                $subevent['evid'] = $evid_org;
                if($evid<>$evid_org)
                    $subevent['seid'] = str_replace("@{$evid}",$evid_org,"@".$subevent['seid']);

                $subevent['html2'] = ereg_replace("%%%%id{$evid}","%%%%id{$evid_org}",$subevent['html2']);
                $subevent['html2'] = ereg_replace("%%%%messageid{$evid}","%%%%messageid{$evid_org}",$subevent['html2']);
                $subevent['html2'] = ereg_replace("%%%%messageid_div{$evid}","%%%%messageid_div{$evid_org}",$subevent['html2']);

                $subevent['html2'] = ereg_replace("%%%%num_ext:id{$evid}","%%%%num_ext:id{$evid_org}",$subevent['html2']);
                $subevent['html2'] = ereg_replace("%%%%title:id{$evid}","%%%%title:id{$evid_org}",$subevent['html2']);
                $subevent['html2'] = ereg_replace("%%%%category1:id{$evid}","%%%%category1:id{$evid_org}",$subevent['html2']);
                $subevent['html2'] = ereg_replace("%%%%category2:id{$evid}","%%%%category2:id{$evid_org}",$subevent['html2']);
                /* 条件置き換え */
                foreach (range(1,5) as $i) {
                    $ii = ($i == 1)? "":$i;
                    if ($subevent['cond'.$ii]) {
                        $subevent['cond'.$ii] = preg_replace("/[0-9]{3}([0-9]{3}):/",$to."$1:",$subevent['cond'.$ii]);
                    }
                }
            }
        }
    }

    return $array;
}

//2008/04/02 条件を追加できないため$addwhere,$addotherを追加
//TODO:DAOクラスなどでなんとかしたい
/**
 * アンケートを取得のDBアクセス部分
 * @param mixid $mode rid,idどちらで検索するか
 * @param mixid	 $value rid or id の値
 * @param string $orderk ソートのキー
 * @param string $orderflg descを指定した場合逆順ソート
 * @param string $getcol 取得したい列を指定できる
 * @param array $addwhere whereに追加したい項目がある場合配列の形で足す
 * @param string $addother その他の条件を文字列で
 * @param int $muid 管理者id
 */
function Get_Enquete_Main($mode = -1, $value, $orderk, $orderflg, $muid = "", $getcol="*", $addwhere=array(), $addother="")
{
    $muid = "";
    global $con;
    //SQL文生成
    $where = $addwhere;

    $idmode = true;
    if ($mode === 'rid') {
        $where["rid"] = 'event.rid = '.FDB::escape($value);
    } elseif ($mode === 'id') {
        $where["evid"] = 'event.evid = '.FDB::escape($value);
    } else {
        $idmode = false;
    }

    //sql組み立て
    $sqlbase = "select * from " . T_EVENT . " ";
    $sql = $sqlbase;
    $i = 0;

    foreach ($where as $kWhere => $vWhere) {
        if ($i === 0) {
            $sql .= "where ";
        } elseif ($i > 0) {
            $sql .= "and ";
        }
        $sql .= $vWhere . " ";
        ++ $i;
    }

    $order = '';
    if ($orderk) {
        $order .= "order by " . $orderk . " ";
        if ($orderflg === 'desc') {
            $order .= "desc ";
        }
    }
    $order .= $addother;

    $sql .= $order;

    //v1.6 mode=rid||idの時は一件しか要らないので
    if ($idmode) {
        $sql .= ' LIMIT 1';
    }

    //SQL実行
    //v1.6 FDBに変更

    $rs = FDB::sql($sql);
    if(is_false($rs))

        return false;
    if(!$rs) return $rs;

    //データを配列に展開
    $array = array ();
    $row = '';
    //while ($rs->fetchInto($row, DB_FETCHMODE_ASSOC))
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        if ($idmode) {
            $array[-1] = $row;
        } else {
            $array[] = $row;
        }
    }

    if ($idmode && $array[-1]) {
        $array[0] = Get_SubEnquete(-1, '', $array[-1]["evid"]);
    }

    return $array;
}

/**
 * 質問を配列に取得
 * @param mixid $mode モード
 * @param int $seid 質問ID
 * @param int $evid アンケートID
 * @return array 質問リスト
 */
function Get_SubEnquete($mode = -1, $seid, $evid)
{
    global $con; //Pear::DBconnect
    $sql = "select * from " . T_EVENT_SUB . " ";
    $sql .= "where evid = " . $evid . " ";
    if ($mode == "id") {
        $sql .= "and   seid = " . $seid . " ";
    }
    $sql .= "order by seid";
    //SQL実行
    $rs = $con->query($sql);
    if (FDB :: isError($rs)) {
        if (DEBUG)
            echo $rs->getDebuginfo();

        return false;
    }
    //データを配列に展開
    $array = array ();
    $row = '';
    //while ($rs->fetchInto($row, DB_FETCHMODE_ASSOC))
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $row["html1"] = $row["html1"];
        $row["html2"] = $row["html2"];
        $array[] = $row;
    }

    return $array;
}

/**
 * insert,updateしても問題ない値かどうかをチェック（このバージョンでは未実装）
 */
function Audit_Enquete($mode = "new", $array)
{
    //insert,updateしても問題ない値かどうか。
    if ($mode == "new") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..mDate
    } elseif ($mode == "update") {
        //日付の有効性。未来であるかどうか
        //必須項目に記載があるか..mDate,sq
    }

    return true;
}

/**
 * 指定したアンケートシリアルIDを持つアンケートのキャッシュファイルを削除
 * @param string $prmRid アンケートシリアルID
 */
function transClearCache($prmRid)
{

    //管理フォルダから呼ばれる前提。それ以外の場合はDIR_ROOTを宣言しておく
    define("DIR_ROOT", "../");
    require_once (DIR_ROOT . "I/CbaseFEnqueteCache.php");
    FEnqueteCache :: deleteAdmin($prmRid);
    if (DEBUG) {
        print "<hr>キャッシュを消しました";
    }
}

/**
 * seidを指定してキャッシュをクリアする
 * @param int $seid seid
 * @author Cbase akama
 */
function clearCacheBySeid($seid)
{
    $where = "SELECT evid FROM " . T_EVENT_SUB . " " . FDB :: where("seid=" . FDB :: escape($seid));
    $res = FDB :: select(T_EVENT, "rid", FDB :: where("evid IN(" . $where . ")"));
    if (is_false($res)) {
        if (DEBUG)
            echo $rs->getDebuginfo();
        exit;
    }
    $prmRid = $res[0]["rid"];
    transClearCache($prmRid);
}

//arrayはGet_Enqueteした結果 Get_SubEnqueteのデータも含まれる
/**
 * セッションの中身を展開して、アンケートをHTML化して返す。
 */
function Show_Enquete()
{
    if (SHOW_ABOLITION) {
        echo 'Show_Enqueteは廃止関数です。CbaseEnqueteViewerを使用してください<hr>';
    }
    //htmlmを「戻る」ボタン用に変更
    //		if ($_SESSION["page"]==1) {
    //1P→タイトル出力
    //		$html0 .= $_SESSION["ed"][-1]["htmlm"];
    //		}

    //form出力
    $html0 .= '<form action="' . getPHP_SELF() . '" method="post">';
    //コンテンツ表示
    //$pc→SESSIONの指定するページに表示データがあるかどうかの検査
    //なければSESSION["page"]を++する
    //戻る対応
    $htmlmain = '';
    $sortsubevents = randomArraySort($_SESSION["ed"][0], $_SESSION["ed"][-1]["randomize"], "subevent");
    if (FError :: is($sortsubevents)) {
        echo $sortsubevents->getInfo();
        exit;
    }

    $subevents = $sortsubevents["value"];
    //$subevents = $_SESSION["ed"][0];
    for ($i = 0, $pc = 0; $i < count($subevents); ++ $i) {
        //ページ分割表示
        if ($_SESSION["page"] < $subevents[$i]["page"]) {
            if ($pc == 0 && $_POST["pb"]) {
                $htmlmain = '';
                $_SESSION["page"]--;
                $i = -1;
                continue;
            } elseif ($pc == 0) {
                $htmlmain = '';
                $_SESSION["page"]++;
            } else {
                break;
            }
        }
        if ($_SESSION["page"] <> $subevents[$i]["page"])
            continue;

        //表示条件チェック
        //条件がなければスルー
        //一致してればスルー
        if (strstr($_SERVER["SCRIPT_NAME"], "index.php")) {
            $show = true;
            if ($subevents[$i]["cond"] <> NULL) {
                $show = false;
                $cd = unserialize($subevents[$i]["cond"]);
                foreach ($cd as $val) {
                    foreach ($val as $k => $v) {
                        if ($_SESSION["P_" .
                            $k] == $v || @ in_array($v, $_SESSION["P_" .
                            $k]) || $_SESSION["T_" .
                            $k])
                            $show = true;
                    }
                }
            } //cond null

            //if ($show==false) continue;
            if ($show == false) {
                $tmpseid = $subevents[$i]["seid"];
                unset ($_SESSION["P_" . $tmpseid]);
                unset ($_SESSION["T_" . $tmpseid]);
                unset ($_SESSION["E_" . $tmpseid]);
                if ($subevents[$i]["html1"])
                    $htmlmain .= $subevents[$i]["html1"];
                continue;
            }
        } //strstr

        unset ($_SESSION["tm"]);
        $_SESSION["tm"] = $subevents[$i];
        $htmlmain .= BuildForm($subevents[$i]["html1"], 0);
        $htmlmain .= BuildForm($subevents[$i]["html2"], 0);
        ++ $pc;
    }
    //表示コンテンツがない場合=条件分岐等で以後のページの表示が必要ない場合
    if (!$htmlmain)
        return false;

    //		if (BACKUP && $_SESSION["page"]>1 && strstr($_SERVER["HTTP_REFERER"],DIR_MAIN)) {
    if ($_SESSION["ed"][-1]["flgs"] > 0) {
        if (strstr($_SERVER["HTTP_REFERER"], DIR_MAIN)) {
            $html0 .= "<table width=" . WIDTH_BACKUP . "><tr><td align=right>";
            $html0 .= '<input type="image" src="img/saveSession.gif" name="ss">';
            $html0 .= "</td></tr></table>";
            $html0 .= "<br>";
        }
    }

    //tmpデータ削除
    unset ($_SESSION["tm"]);
    //保険の閉じタグ
    $html .= "</td></tr></table>";
    //戻るボタン
    if ($_SESSION["page"] <> 1) {
        $html .= $_SESSION["ed"][-1]["htmlm"];
        //$html .= $_SESSION["ed"][-1]["htmlm"];
    }
    //submitの出力
    if ($_SESSION["page"] == $_SESSION["ed"][-1]["lastpage"]) { //ラストページの時
        $html .= $_SESSION["ed"][-1]["htmls"];
    } else {
        if (is_void($_SESSION["ed"][-1]["htmls2"])) {
            $html .= ereg_replace("送信", "次へ", $_SESSION["ed"][-1]["htmls"]);
        } else {
            $html .= $_SESSION["ed"][-1]["htmls2"];
        }
    }
    //$html .= $_SESSION["ed"][-1]["htmls"];
    //form(END)出力
    $html .= getHiddenSID();
    $html .= '</form>';

    //ページ進行の表示
    if (!HIDDEN_PAGENUMBER) {
        $htmlD = "";
        $htmlD .= '<table border=0 cellspacing=0 cellpadding=0 width=' . WIDTH_BACKUP . '><tr><td align=right>';
        //			$htmlD.= '['.sprintf("%01d",($_SESSION["page"]/$_SESSION["ed"][-1]["lastpage"])*100).'%]';
        $htmlD .= '[' . $_SESSION["page"] . '/' . $_SESSION["ed"][-1]["lastpage"] . 'ページ]';
        $htmlD .= '</td></tr></table>';
        $html0 = $htmlD . $html0;
    }

    return stripslashes($html0) . stripslashes($htmlmain) . stripslashes($html);
}

/**
 * 回答データを保存する
 * @param array $data 回答データ
 * @return bool 成功すればtrue
 */
function Save_EnqueteData($data)
{
    global $con; //Pear::DB

    if (SHOW_ABOLITION) {
        echo 'Save_EnqueteDataは廃止関数です。CbaseEnqueteControlerまたはCbaseEnqueteAnswerを使用してください<hr>';
    }

    //オープンアンケートの場合
    if (isOpenEnqueteByFlgo($data["ed"][-1]["flgo"])) {
        if (!defined("T_UNIQUE_SERIAL")) {
            echo "IDテーブルが設定されていません";
            exit;
        }
        $data["uid"] = getUniqueIdWithTable(T_UNIQUE_SERIAL, "serial_no", 8);
        //IDを生成
        //$data["uid"] = sprintf("%03d", date("z")) . Get_RandID(5); //元旦からの日数を三桁で表示

        //セッションがない場合
    } elseif (!$data) {
        header("Location: " . HTML_ALREADYENTRY);
        exit;

        //CRMアンケートの場合
    } else {
        //二重回答チェック
        if (REOPEN) {
            $ccu = Check_Data($data["evid"], $data["uid"]);
            if ($ccu > 0) {
                header("Location: " . HTML_ALREADYENTRY);
                exit;
            }
        }
    }
    unset ($data["main"]);
    unset ($data[SESSIONID]);

    //Reopen用
    if (!REOPEN) {
        $sql = "update " . T_EVENT_DATA . " ";
        $sql .= "set evid = (evid+1000) ";
        $sql .= "where serial_no = '" . $data["uid"] . "' ";
        $sql .= "and evid = " . $data["evid"] . " ";
        $rs = $con->query($sql);

        $sql = "update " . T_EVENT_SUB_DATA . " ";
        $sql .= "set evid = (evid+1000) ";
        $sql .= "where serial_no = '" . $data["uid"] . "' ";
        $sql .= "and evid = " . $data["evid"] . " ";
        $rs = $con->query($sql);
    }
    /*
            if(!$_SESSION['sdate'])
                $_SESSION['sdate'] = null;
            if(!$_SESSION['stop_count'])
                $_SESSION['stop_count']=0;
    */

    //event_dataに登録
    $sql = 'insert into ' . T_EVENT_DATA . ' values(';
    $sql .= $data["evid"] . ',';
    $sql .= "'" . $data["uid"] . "',";
    $sql .= "'" . date("Y-m-d H:i:s") . "',";
    $sql .= "'" . $data["flg"] . "'";
    //		$sql.= "'".$data["flg"]."',";
    //		$sql.= FDB::quoteSmart($_SESSION['sdate']).",";
    //		$sql.= "'".date("Y-m-d H:i:s")."',";
    //		$sql.= "'".$_SESSION['stop_count']."'";
    $sql .= ')';
    $rs = $con->query($sql);
    if (FDB :: isError($rs)) {
        if (DEBUG)
            echo $rs->getDebuginfo();

        return false;
    }

    //subevent_dataに登録
    reset($data);
    $data1 = $data;
    foreach ($data1 as $key => $val) {
        //if (!ereg("^P_|^T_",$key)) continue;
        $keys = '';
        if (ereg("^P_", $key)) {
            $keys = ereg_replace("P_", "", $key);
        } elseif (ereg("^T_", $key)) {
            $keys = ereg_replace("T_", "", $key);
        } else {
            continue;
        }
        $sql = 'insert into ' . T_EVENT_SUB_DATA . ' ';
        $sql .= 'values (';
        $sql .= $data["evid"] . ",'" . $data["uid"] . "'," . $keys . ",";

        if (ereg("^P_", $key)) {
            //type2 = r,c
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    //$sqlは上のを使いまわす
                    //$sql2 = $sql.ereg_replace("[^0-9]","",$v).",".FDB::quoteSmart($data["E_".$keys]).")";
                    if (empty ($data["E_" . $keys])) {
                        $sql2 = $sql . ereg_replace("[^0-9]", "", $v) . ",NULL)";
                    } else {
                        //その他欄のデータ入れ
                        $sql2 = $sql .
                        ereg_replace("[^0-9]", "", $v) .
                        "," .
                            //FDB::quoteSmart(mb_convert_encoding($data["E_".$keys],"EUC-JP",)).
    sql_escape(stripslashes($data["E_" . $keys]), "EUC-JP", "SJIS") .
                        ")";
                    }
                    $rs = $con->query($sql2);
                }
            } else {
                //type2 = p
                if ($val == "ng")
                    continue; //pulldownのデフォルト

                if (empty ($data["E_" . $keys])) { //記入欄回答なし
                    $sql .= ereg_replace("[^0-9]", "", $val) . ",NULL)";
                } else {
                    //その他欄のデータ入れ
                    $sql .= ereg_replace("[^0-9]", "", $val) .
                    "," .
                    //FDB::quoteSmart(mb_convert_encoding($data["E_".$keys],"EUC-JP","SJIS"))
                    sql_escape(stripslashes($data["E_" . $keys]), "EUC-JP", "SJIS") .
                    ")";
                }
                $rs = $con->query($sql);
            }
        } else { //テキスト回答
            //記入欄のデータ入れ
            $sql .= "-1," .
            sql_escape(stripslashes($data["T_" . $keys]), "EUC-JP", "SJIS") .
            //FDB::quoteSmart(mb_convert_encoding($data["T_".$keys],"EUC-JP","SJIS")).
            ")";
            $rs = $con->query($sql);
        }
    }
    //途中保存機能用のファイル削除
    //		if (BACKUP) {
    if ($_SESSION["ed"][-1]["flgs"] > 0) {
        //		foreach (glob(DIR_SAVESESSION . $data["uid"] . "*") as $filename)
        //		{
        //			@ unlink($filename);
        //		}
        //		$strPath = DIR_SAVESESSION . $data["uid"] . "_" . $data["rid"];
        //		@ unlink($strPath);
        FBackUpData :: deleteBackUpData($data["uid"], $data["rid"]);
    }

    if (CLEAR_ENQUETE_SESSION) { //reseach_mail
        $GLOBALS['AuthSession']->sessionReset();
    }

    return true;
}

/**
 * アンケート回答時の入力値チェック
 * @param array $error エラー時に文字列を入れる(参照返し)
 * @param array $data 入力値
 * @param array $ed アンケートデータ
 */
function Check_SubmitEvent(& $error, $data, $ed)
{
    if (SHOW_ABOLITION) {
        echo 'Check_SubmitEventは廃止関数です。CbaseEnqueteControler(暫定)を使用してください<hr>';
    }
    /*
     *エラーのときに以下を記述する形式
    $error[] = '<font color="#005aa5">「' .
    mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25)
    .'...」'
    . $txterr0
    . '</font>';
     *
     */

    //テキスト欄未入力エラー時msg
    //	$txterr0 = 'は必須項目です。';
    //	$txterr1 = "の記入欄も入力ください。";
    //	$txterr2 = "の選択数が超過しております。";
    //	$txterr3 = "は半角数値入力項目です。";
    //	$txterr4 = "はNNNNつ選択ください。";
    //	$txterr5 = "ERROR cond3";

    $txterr0 = FError :: get("HISSU_NOTHING");
    $txterr1 = FError :: get("OTHER_NOTHING");
    $txterr2 = FError :: get("CHOICE_OVER");
    $txterr3 = FError :: get("NO_NUMBER");
    $txterr4 = FError :: get("CHOICE_NEED");
    $txterr5 = FError :: get("COND5");

    //必須のものをチェックする
    foreach ($ed as $key => $val) {
        //ページ分割対応...該当ページのみ処理
        if ($_SESSION["page"] < $val["page"])
            break;
        if ($_SESSION["page"] <> $val["page"])
            continue;
        //入力値チェック
        //数値回答
        if ($val["type1"] == 3 && (!empty ($data["T_" . $val["seid"]]) || $data["T_" . $val["seid"]] == "0")) {
            if (ereg("[^0-9]", $data["T_" . $val["seid"]])) {
                $error[] = '<font color="#005aa5">「' .
                mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 13) .
                '...」' . $txterr3 . '</font>';
            }
        }

        //外字対策
        $text = $_SESSION["E_{$seid}"];
        if (!$text)
            $text = $_SESSION["T_{$seid}"];
        if (mb_convert_encoding($text, INTERNAL_ENCODE, INTERNAL_ENCODE) != $text) {
            $error[] = '<font color="#005aa5">「' .
            mb_substr(ereg_replace("<br>", "", $val["title"]), 0, 25) .
            '...」' . "対応できない文字を含んでいます。" . '</font>';
        }

        //選択回答
        //数値以外はエラーindex.phpの値登録時に制御
        //テキスト入力回答
        //不正文字は除去index.phpの値登録時に制御

        //論理チェック
        if ($val["cond4"]) {
            require_once 'C.php';
            $strTmpAnswer = $val["type2"] == "t" ? $_SESSION["T_" . $val["seid"]] : $_SESSION["P_" . $val["seid"]];
            list ($blReturn, $strMsg) = getCheckCond($_SESSION, $val["cond4"], $strTmpAnswer);
            if ($blReturn === true) { //指定条件に合致した(cond4に設定した選択し番号が回答にあった)
                if ($val["type2"] == "t") { //記入回答に対するmin,max,lenの条件
                    $error[] = $strMsg;
                } else {
                    if ($_SESSION["P_" . $val["seid"]] != getCond4Clear($val["cond4"], $_SESSION["P_" .
                        $val["seid"]]))
                    {
                        $error[] = $strMsg;
                    }
                }
            }
        } //end cond4

        //必須でなく＆自問条件がない場合は、このチェック機構をパスさせる
        if ($val["hissu"] == 0 && $val["cond2"] == NULL && $val["cond3"] == NULL)
            continue;

        //条件分岐の、表示していない設問を飛ばす
        $show = 1;
        if ($val["cond"] <> NULL) {
            $show = 0;
            $cd = unserialize($val["cond"]);
            foreach ($cd as $val2) {
                foreach ($val2 as $k => $v) {
                    if ($_SESSION["P_" .
                        $k] == $v || @ in_array($v, $_SESSION["P_" .
                        $k]) || $_SESSION["T_" .
                        $k])
                        $show = 1;
                }
            }
        }
        if ($show <> 1)
            continue;

        //seidセット
        $sd = $val["seid"];

        //cond3処理 (新エラー処理)順位指定などに使用
        if ($val["cond3"]) {
            if ((isset ($data["P_" . $sd]) && $data["P_" . $sd] <> "ng") || !empty ($data["T_" .
                $sd]))
            {
                require_once 'C.php';
                $strTmpAnswer = $val["type2"] == "t" ? $_SESSION["T_" . $sd] : $_SESSION["P_" . $sd];
                list ($blReturn, $strMsg) = getCheckCond($_SESSION, $val["cond3"], $strTmpAnswer);
                //list($blReturn,$strMsg) = getCheckCond($_SESSION,$val["cond3"],$_SESSION["P_". $val["seid"] ]);
                if ($blReturn === false) { //指定条件に合致しない(cond3に設定した選択し番号が回答にあった)
                    $error[] = $strMsg;
                }
            }
        }

        //[hissu]必須入力チェック
        if ($val["type2"] == "t" && $val["type1"] == "3") { //数値回答
            if (empty ($data["T_" . $sd]) && $data["T_" . $sd] <> "0")
                $error[] = '<font color="#005aa5">「' .
                mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                '...」' . $txterr0 . '</font>';
        } elseif ($val["type2"] == "t" && $val["type1"] == "4") { //記入回答
            if (empty ($data["T_" . $sd]))
                $error[] = '<font color="#005aa5">「' .
                mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                '...」' . $txterr0 . '</font>';
        } elseif ($val["type2"] == "c" || $val["type2"] == "r") { //配列データ

            if (!isset ($data["P_" . $sd]) && $val["hissu"] == 1) {
                $error[] = '<font color="#005aa5">「' .
                mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                '...」' . $txterr0 . '</font>';
            } elseif (isset ($data["P_" . $sd])) {
                //[cond2]テキスト入力欄or複数回答数制限チェック
                if ($val["cond2"] <> NULL) {
                    $cd2 = unserialize($val["cond2"]);
                    if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                        if (in_array($cd2["other"], $data["P_" . $sd]) && empty ($data["E_" . $sd])) {
                            $error[] = '<font color="#005aa5">「' .
                            mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                            '...」' . $txterr1 . '</font>';
                        }
                    }
                    if ($cd2["maxcount"]) {
                        if (count($data["P_" . $sd]) > $cd2["maxcount"]) {
                            $error[] = '<font color="#005aa5">「' .
                            mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                            '...」' . $txterr2 . '</font>';
                        }
                    }
                    if ($cd2["equalcount"]) {
                        if (count($data["P_" . $sd]) <> $cd2["equalcount"]) {
                            $error[] = '<font color="#005aa5">「' .
                            mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                            '...」' .
                            ereg_replace("NNNN", mb_convert_kana($cd2["equalcount"], "N"), $txterr4) .
                            '</font>';
                        }
                    }
                } //cond2
            } //isset

        } elseif ($val["type2"] == "p") {
            if ($data["P_" . $sd] == "ng") {
                $error[] = '<font color="#005aa5">「' .
                mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                '...」' . $txterr0 . '</font>';
            } else {
                //[cond2]テキスト入力欄チェック
                if ($val["cond2"] <> NULL) {
                    $cd2 = unserialize($val["cond2"]);
                    if (isset ($cd2["other"]) || $cd2["other"] == "0") {
                        if ($cd2["other"] == $data["P_" . $sd] && empty ($data["E_" . $sd])) {
                            $error[] = '<font color="#005aa5">「' .
                            mb_substr(ereg_replace("<br>", "", stripslashes($val["title"])), 0, 25) .
                            '...」' . $txterr1 . '</font>';
                        }
                    }
                } //cond2
            }
            ////////////////////////////////////////////////
            ////////////////////////////////////////////////
            /////↓///その他の条件の処理は？！//////////////
            ////////////////////////////////////////////////
            ////////////////////////////////////////////////
        } else {
            echo '<font color="red">$$else$$$$$$$$$$</font><br>';
        } //type2分岐
    } //foreach

    return;
} //function

//アンケート結果出力
/**
 * アンケート結果出力
 * セッションに入ってるアンケート結果を出力する。
 */
function Show_EnqueteTotal()
{

    //ヘッダー出力
    //1P→タイトル出力
    //		$html0 .= $_SESSION["ed"][-1]["htmlm"];
    //コンテンツ表示
    //$pc→SESSIONの指定するページに表示データがあるかどうかの検査
    //なければSESSION["page"]を++する
    for ($i = 0; $i < count($_SESSION["ed"][0]); ++ $i) {
        unset ($_SESSION["tm"]);
        $_SESSION["tm"] = $_SESSION["ed"][0][$i];
        $html .= BuildForm($_SESSION["ed"][0][$i]["html1"], 2);
        $html .= BuildForm($_SESSION["ed"][0][$i]["html2"], 2);
    }
    //tmpデータ削除
    unset ($_SESSION["tm"]);
    //フッター出力
    //$html .= $_SESSION["ed"][-1]["htmlf"];
    return $html;
    //return stripslashes($html0).stripslashes($html);
}

//$arrayで返す$array[key]=count
/**
 * アンケート(質問ごと)の集計結果を返す
 * @param int $seid 質問ID
 * @return array $array[key]=count keyは選択肢
 */
function Get_TTLSubevent($seid)
{
    /*
     * この関数は取り急ぎの対応として定数などベタ打ちしている
     * 集計を作り直したら廃止予定
     */
    global $con;
    if (SHOW_ABOLITION) {
        echo 'Get_TTLSubeventは廃止関数です。enq_ttl::getTotalSubeventを使用してください<hr>';
    }
    //SQL文で group by
    $sql = 'select count(choice) as count,choice from ' . T_EVENT_SUB_DATA . ' ';
    $sql .= 'inner join ' . T_EVENT_DATA . ' on ' . T_EVENT_SUB_DATA . '.event_data_id = ' . T_EVENT_DATA . '.event_data_id ';
    $sql .= 'where seid = ' . $seid . ' ';
    $sql .= 'AND answer_state = 0 ';
    $sql .= 'group by choice ';
    $rs = $con->query($sql);
    if (FDB :: isError($rs)) {
        if (DEBUG)
            echo $rs->getMessage();
        if (DEBUG)
            echo $rs->getDebuginfo();

        return false;
    }
    $row = '';
    //while ($rs->fetchInto($row, MDB2_FETCHMODE_NUMERIC))//削除2012-06-21
    while ($row = $rs->fetchRow(MDB2_FETCHMODE_NUMERIC)) {
        $array[$row[1]] = $row[0];
    }

    return $array;
} //end//Get_TTLSubevent

/**
 * seidを条件にSubEventのseidを更新する
 * @param int $prmWhereId 条件ID
 * @param int $prmNewId 書き換え後ID
 * @return int 書き換え後ID　失敗したらfalse
 */
function Update_SubEnqueteId($prmWhereId, $prmNewId)
{
    global $con; //Pear::DBconnect

    $strSql = "UPDATE " .
    T_EVENT_SUB . " " .
    "SET seid='" . $prmNewId . "' " .
    "WHERE seid=" . $prmWhereId;

    $rs = $con->query($strSql);
    if (FDB :: isError($rs)) {
        if (DEBUG)
            echo $rs->getDebuginfo();
        $rs->rollback();

        return false;
    }

    return $prmNewId;
}

/**
 * 指定されたイベントに属するサブイベントのページ設定を、抜け番が無くなる様に更新する
 * @param int $evid
 */
function correctPageNumber($evid)
{
    $subevents = FDB :: select(T_EVENT_SUB, "page,seid", "where evid =" . FDB :: escape($evid) . " order by seid");
    $page = 1;
    $first_loop_flag = true;
    foreach ($subevents as $subevent) {
        $data = array ();

        if ($first_loop_flag && $subevent['page'] != 1) {
            $data['page'] = 1;
        } elseif ($subevent['page'] != $page) {
            $page++;
            if ($subevent['page'] != $page) {
                $data['page'] = $page;
            }
        }
        if ($data) {
            FDB :: update(T_EVENT_SUB, $data, "where seid = {$subevent['seid']}");
        }
        $first_loop_flag = false;
    }
}

/**
 * アンケートをグローバル変数に読み込む
 * @param int $evid
 */
function loadEnquete($evid)
{
    global $GLOBAL_EVENT, $GLOBAL_SUBEVENTS;
    $data = Get_Enquete_Main('id', $evid, '', '', $_SESSION['muid']);
    $GLOBAL_EVENT = $data[-1];
    $GLOBAL_EVENT['first_seid'] = $data[0][0]['seid'];
    $GLOBAL_SUBEVENTS = array ();
    $num = 0;
    $pre_seid = null;
    foreach ($data[0] as $subevent) {
        $num++;
        $subevent['num'] = $num;
        $subevent['pre_seid'] = $pre_seid;
        if ($pre_seid)
            $GLOBAL_SUBEVENTS[$pre_seid]['next_seid'] = $subevent['seid'];
        $GLOBAL_SUBEVENTS[$subevent['seid']] = $subevent;
        $pre_seid = $subevent['seid'];
    }
    $GLOBAL_EVENT['last_seid'] = $subevent['seid'];
    $GLOBAL_EVENT['lastnum'] = $num;
}

function enqIsOpen($event)
{
    return (isOpenEnqueteByFlgo($event['flgo']));
}

class EnqueteChoiceCache
{

    /***
     * public 選択肢を取得する
     */
    public function get ($subevent, $user =array())
    {
        $seid = $subevent['seid'];
        if ($this->exists($seid)) {
            //キャッシュがあればキャッシュを読む
            if (DEBUG) {
                print "<hr>read choice from cache.<br>";
            }
            $choices = $this->load($seid);
        } else {
            //キャッシュが無ければ読んで保存
            if (DEBUG) {
                print "<hr>read choice from DB.<br>";
            }
            $choices = $this->getDBContents($seid);
            $this->save($seid, $choices);
        }
        //userがあれば絞込み
        if ($user) {
            $choices = $this->extractByUser($choices, $user);
        }

        return $this->format($choices);
    }

    /**
     * キャッシュファイルの存在確認
     */
    public function exists($seid)
    {
        return file_exists( $this->getFileName($seid));
    }

    //定数
    /**
     * 保存するファイル名の取得
     */
    public function getFileName($seid)
    {
        return DIR_CACHE.'choices_'.$seid.'.ccache';
    }

    /**
     * キャッシュファイルの保存
     */
    public function save($seid, $choices)
    {
        $this->saveFile(serialize($choices), $this->getFileName($seid));
    }

    /**
     * キャッシュファイルの読み込み
     */
    public function load($seid)
    {
        return unserialize($this->loadFile($this->getFileName($seid)));
    }

    /**
     * キャッシュファイルの削除
     */
    public function delete($seid)
    {
        return $this->deleteFile($this->getFileName($seid));
    }

    /**
     * DBからの読み込み
     */
    public function getDBContents($seid)
    {
        $data = FDB::select(T_CHOICE, 'num, choice, div',
            'WHERE seid='.FDB::escape($seid));

        return $data;
    }

    /**
     * データを選択肢で使えるデータに変換
     */
    public function format($dbContents)
    {
        $a = array();
        foreach ($dbContents as $v) {
            $a[$v['num']] = $v['choice'];
        }

        return $a;
    }

    /**
     * ユーザで抽出
     */
    public function extractByUser($choices, $user)
    {
        $a = array();
        foreach ($choices as $v) {
            if ($this->isExtract($v, $user)) {
                $a[] = $v;
            }
        }

        return $a;
    }

    /**
     * 抽出条件。trueで抽出
     */
    public function isExtract($choice, $user)
    {
        return ($choice['div'] == $user['div1'] || is_null($choice['div']));
    }

    //TODO:以下CbaseEnqueteCacheよりコピペして加工。委譲にできる
    /**
     * キャッシュ保存
     * @param  array  $prmData 保存するファイル
     * @param  string $prmFile 保存ファイル名（規格に沿うこと）
     * @return bool   成功すればtrue
     */
    public function saveFile($prmData, $prmFile)
    {

        if (!$prmFile)
            return false;
        $strPath =  $prmFile;
        $zp = fopen($strPath, "w");
        fwrite($zp, $prmData);
        fclose($zp);

        return true;
    }

    public function loadFile($file)
    {
        $fp = fopen($file, "r");
        $dt = fgets($fp);
        fclose($fp);

        return $dt;
    }

    public function deleteFile($filePath)
    {
        if (file_exists($filePath)) {
            unlink(file_exists);

            return true;
        }

        return false;
    }
}

/**
 * 質問データとユーザデータから選択肢を取得して返す
 * @param array $subevent 質問データ
 * @param array $user ユーザデータ、無い場合はユーザを考慮しない
 */
function getEnqueteChoice ($subevent, $user =array())
{
    if ($subevent['choice_mode']) {
        $cls = new EnqueteChoiceCache();

        return $cls->get($subevent, $user);
    } else {
        return explode(',', $subevent['choice']);
    }
}
