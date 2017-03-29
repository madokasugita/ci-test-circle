<?php
/**
 * データベース接続ライブラリ
 * 内容：
 * 	データベースアクセスクラス定義
 * 	PEAR::DBへの接続
 *
 * 依存：DB.php
 * 		crm_define.php
 * 		CbaseFGeneral.php(getUniqueId使用時)
 *
 * データベースアクセス用クラス集。インスタンス化せずに使う。
 * データベースに接続し、$conにDBオブジェクトを入れておく。
 * @package Cbase.Research.Lib
 * @author	Cbase akama
 * 			Cbase kido
 * @version 1.3
 *
 * 更新履歴
 * 2007/07/17	ver1.1 	INTERNAL_ENCODEがcrm_define.phpで宣言されていないときは、EUC-JPを使うように変更
 * 2007/07/25	ver1.2 	DBエラーの時は logs/sql.logに必ずエラーを残すように
 * 2007/07/25	ver1.3	DB_STRICT_MODEを追加 (デフォルトOFF) ONの場合：対応できない文字コードをエスケープ or DBに投げる
 * 						したときにエラーを出してexitする。
 * 2008/08/08   Ver1.31 DEBUGモードの際に、呼び出し元ファイル名：行数を表示するように
 * 2008/10/01   Ver1.4  ログ出力方法の変更、getNextValのエラー出力に対応
 */

require_once 'MDB2.php';
require_once 'CbaseCommon.php';
require_once 'CbaseFileLock.php';
require_once 'CbaseTmplLib.php';

if(!defined('DEBUG'))
    define('DEBUG', 0);
define('DB_ENCODE', "UTF-8"); //ver1.3/
define('DB_STRICT_MODE', 0); //ver1.3/
define('SQL_BACKTRACE', 1);
class FDB
{
    /**
     * 使い方： $db =& FDB::getInstanceで取得、その際接続が無ければ自動で接続する
     * ※現在getInstanceがFDBを返さず、本来のInstanceの取得は果たせていない
     * ただし「接続用」として使ってしまっている箇所もあるため、関数そのものはそのままの名前で残す。
     *
     * @author Cbase akama
     */
    function & getInstance()
    {
        global $con;
        //globalにすることで旧FDBとの互換を保つ
        //（互換で使ってしまうとあまりクラス化の意味は無いのですが）
        //またstaticでは参照を保持しないらしいのでその点も配慮
        if (is_null($con)) {
            //接続準備
            $dsn = DB_TYPE."://".DB_USER.":".DB_PASSWD."@".DB_HOST.":".DB_PORT."/".DB_NAME;
            $op  = array("persistent"=>DB_PERSISTENT, "use_transactions"=>true);

            //データベースに接続
            for ($con_times=0; $con_times<6; ++$con_times) {
                if($con_times>0)
                    sleep(5);

                $con = MDB2::connect($dsn, $op);

                if (PEAR::isError($con)) {
                    FDB::outputLog($con->getDebugInfo());
                    continue;
                }
                break;
            }

            //接続に失敗したらエラー表示して終了
            if (PEAR::isError($con)) {
                if(DEBUG)
                    echo $con->getMessage();
                else
                    echo tmplGetError2_('ADMIN_ERROR_901');
                exit;
            }
            //失敗したらエラー表示して終了
            if (is_false(FDB::transSetTimeZone())) {
                if(DEBUG)
                    echo $con->getMessage();
                else
                    echo tmplGetError2_("ADMIN_ERROR_902");
                FDB::outputLog($con->getDebugInfo());
                exit;
            }
        }

        return $con;
    }

    /**
     * データベースの切り替えに利用。$conのアクセス先を変更するのみ
     *
     * @param $params = ['DB_TYPE', 'DB_USER', 'DB_PASSWD', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_PERSISTENT']
     * @author Cbase yamazaki
     */
    public static function setInstance($params)
    {
        global $con;

        FDB::outputLog('データベース接続処理開始 : データベース名＝' . $params['DB_NAME']);

        //接続準備
        $dsn = $params['DB_TYPE']."://".$params['DB_USER'].":".$params['DB_PASSWD']."@".$params['DB_HOST'].":".$params['DB_PORT']."/".$params['DB_NAME'];
        $op  = array("persistent"=>$params['DB_PERSISTENT'], "use_transactions"=>true);

        //データベースに接続
        for ($con_times=0; $con_times<6; ++$con_times) {
            if($con_times>0)
                sleep(5);

            $con = MDB2::connect($dsn, $op);

            if (PEAR::isError($con)) {
                FDB::outputLog($con->getDebugInfo());
                continue;
            }
            break;
        }

        //接続に失敗したらエラー表示して終了
        if (PEAR::isError($con)) {
            if(DEBUG)
                echo $con->getMessage();
            else
                echo tmplGetError2_('ADMIN_ERROR_901');
            exit;
        }
        //失敗したらエラー表示して終了
        if (is_false(FDB::transSetTimeZone())) {
            if(DEBUG)
                echo $con->getMessage();
            else
                echo tmplGetError2_("ADMIN_ERROR_902");
            FDB::outputLog($con->getDebugInfo());
            exit;
        }

        FDB::outputLog('データベース接続処理完了 : データベース名＝' . $params['DB_NAME']);

        return $con;
    }

    /**
     * mysqlの扱う時間帯を指定する
     * @param string $prmZone タイムゾーン
     */
    public function transSetTimeZone($prmZone="+9:00")
    {
        $prmZone = FDB::escape($prmZone);

        return FDB::sql("SET time_zone={$prmZone};", true);
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //	実行系
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    /**
     *  渡されたSQL文を実行します
     * @param  string $prmStrSql 実行したいSQL
     * @return mixed  結果オブジェクト
     */
    public function sql($prmStrSql, $exec=false)
    {
        global $con,$FDB_affectedRows;

        //ver1.3/
        if (DB_STRICT_MODE) {
            mb_substitute_character("long");
            $prmStrSql = mb_convert_encoding($prmStrSql, DB_ENCODE, DB_ENCODE);
            if (ereg("BAD\\+[0-9A-F]+", $prmStrSql)) {
                echo DB_ENCODE . "の範囲外の文字が含まれています。";
                exit;
            }
        }

        //ver.1.4/
        //実行時間測定を追加
        $sql_time = time();
        $rs = ($exec)? $con->exec($prmStrSql):$con->query($prmStrSql);
        $sql_time = time() - $sql_time;
        if ($sql_time > MAX_SQL_TIME) {
            $prmStrSql = str_replace("\n", " ", str_replace("\r", " ", str_replace("\r\n", " ", $prmStrSql)));
            error_log(date('Y-m-d H:i:s')."\t".$sql_time."\t".$_SERVER['SCRIPT_NAME']."\t".$prmStrSql."\n", 3, LOG_MAX_SQL_TIME);
        }

        if (FDB::isError($rs, $prmStrSql)) {
            $FDB_affectedRows = null;

            return false;
        }
        //delete文が実行したら、DIR_LOG.'sql_delete'.DATE_YM.'.clog'にログを抽出
        if (preg_match('/^delete/i', trim($prmStrSql))==1) {
            error_log(date('Y-m-d H:i:s')."\t".$_SERVER['SCRIPT_NAME']."\t".$prmStrSql."\n", 3, LOG_FILE_SQL_DELETE);
        }
        $FDB_affectedRows = ($exec)? $rs:$rs->numRows();

        return $rs;
    }

    public function getAffectedRows()
    {
        global $FDB_affectedRows;

        return $FDB_affectedRows;
    }

    //ver.1.4/
    public function isError($result, $sql="", $mode=null)
    {
        if(is_null($mode))
            $mode = LOG_MODE_SQL;

        //ver1.31
        $trace = "";
        if (DEBUG && SQL_BACKTRACE===1) {
            foreach (debug_backtrace() as $debug) {
                if(basename($debug['file'])==basename(__FILE__))
                    continue;
                $trace .= "(".basename($debug['file']).":".$debug['line'].") ";
            }
            $trace .= "<br>";
        }

        if (PEAR::isError($result)) {
            if(LOG_MODE_SQL>=1)
                FDB::outputLog($result->getDebugInfo());
            if(DEBUG)
                FDB::displayLog($trace.FDB::sql_display_escape($result->getDebugInfo()));

            return true;
        }

        if(LOG_MODE_SQL>=2)
            FDB::outputLog($sql);
        if(DEBUG)
            FDB::displayLog($trace.FDB::sql_display_escape($sql));
            //FDB::displayLog($trace.html_escape(preg_replace("/B'([^']{8})[^']*'/", "B'$1...'", $sql)));//bit演算のSQLで長くなりすぎて見づらい場合はこの処理を使う
        return false;
    }

    //ver.1.4/
    public function outputLog($body)
    {
        if (LOG_MODE_SQL==3) {
            $muid = $_SESSION['muid']."\t";
        }
        $body = str_replace("\n", " ", str_replace("\r", " ", str_replace("\r\n", " ", $body)));
        error_log($muid.date('Y-m-d H:i:s')."\t".$_SERVER['SCRIPT_NAME']."\t".$body."\n", 3, LOG_FILE_SQL);
    }

    public function displayLog($body)
    {
        echo <<<__HTML__
<div style="font-size:12px;">{$body}</div><hr>
__HTML__;
    }

    public function sql_display_escape($string)
    {
        $encode = (defined('INTERNAL_CHARSET'))? INTERNAL_CHARSET:'UTF-8';
        $string = htmlentities($string, ENT_QUOTES, $encode);

        return preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", $string);
    }

    /**
     *  SQL文を投げて、select結果を配列にいれて返します。
     * @param  string $prmSql SQL文
     * @return array  結果の配列(2次元)
     */
    public function getAssoc($prmStrSQL)
    {
        global $con;
        $rs = FDB::sql($prmStrSQL);
        if($rs===false)

            return false;

        if ($con->options['result_buffering']) {
            for($aryTmp = array(); $row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC); $aryTmp[] = $row); //ver1.2/
            $rs->free();

            return $aryTmp;
        } else {
            return $rs;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //	SQL生成系
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    /**
     * SELECT文を作って返します。基本的に外部から呼び出して使うことはありません。
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmStrCol    得たいカラム名
     * @param  string $prmStrOption WHERE節、ORDER BY等の条件文
     * @return string Sql文字列
     */
    public function getSelectSql($prmStrTable, $prmStrCol="*", $prmStrOption="", $nl=";")
    {
        return "SELECT {$prmStrCol} FROM {$prmStrTable} {$prmStrOption}{$nl}";
    }

    /**
     * UPDATE文を作って返します。
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmAryData   キー:カラム バリュー:データ の連想配列
     * @param  string $prmStrOption オプション"where id = 3" "limit 5" など
     * @return string SQL
     */
    public function getUpdateSql($prmStrTable, $prmAryData, $prmStrOption="", $nl=";")
    {
        foreach ($prmAryData as $strKey => $strVal) {
            $strSet .= "{$strKey}={$strVal},";
        }
        $strSet = trim($strSet, ",");

        return "UPDATE {$prmStrTable} SET {$strSet} {$prmStrOption}{$nl}";
    }

    /**
     * INSERT文を作って返します。
     * @param  string $prmStrTable テーブル名
     * @param  string $prmAryData  配列 (連想配列だとキーがカラムも指定)
     * @return string SQL
     */
    public function getInsertSql($prmStrTable, $prmAryData, $nl=";")
    {
        $keys = array_keys($prmAryData);
        if (!is_numeric($keys[0]))
            $strKeys = "(".implode(",", array_keys($prmAryData)).")";
        $prmAryData = implode(",", $prmAryData);

        return "INSERT INTO {$prmStrTable}{$strKeys} VALUES({$prmAryData}){$nl}";
    }

    public function getInsertArraySql($prmStrTable, $prmAryData, $nl=";")
    {
        $strKeys = null;
        if(is_array($prmAryData))
        foreach ($prmAryData as $key => $value) {
            if (is_null($strKeys)) {
                $strKeys = array_keys($value);
                $strKeys = (!is_numeric($strKeys[0]))? "(".implode(",", $strKeys).")":"";
            }
            $prmAryData[$key] = "(".implode(",", $value).")";
        }
        $prmAryData = implode(",", $prmAryData);

        return "INSERT INTO {$prmStrTable}{$strKeys} VALUES{$prmAryData}{$nl}";
    }

    /**
     * DELETE文を作って返します。
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmStrOption 条件文"where id = 3" など
     * @return string SQL
     *                             SQL文
     */
    public function getDeleteSql($prmStrTable, $prmStrOption="", $nl=";")
    {
        return "DELETE FROM {$prmStrTable} {$prmStrOption}{$nl}";
    }

    /**
     * TRUNCATE文を作って返します。
     * @param  string $prmStrTable テーブル名
     * @return string SQL
     *                            SQL文
     */
    public function getTruncateSql($prmStrTable)
    {
        return "TRUNCATE TABLE {$prmStrTable}";
    }

    /**
     * WHERE節を返す。
     * @param  string $prmOption 条件文
     * @return string WHERE節
     */
    public function where($prmOption)
    {
        return " WHERE {$prmOption}";
    }

    /**
     * GROUP BY節を返す。
     * @param  string $prmOption 条件文
     * @return string BY節
     */
    public function groupby($prmOption)
    {
        return " GROUP BY {$prmOption}";
    }

    /**
     * ORDER BY節を返す。
     * @param  string $prmOption 条件文
     * @return string ORDER BY節
     */
    public function orderby($prmOption)
    {
        return " ORDER BY {$prmOption}";
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //	生成＆実行系
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    /**
     * データベースからデータをselectします。
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmStrCol    得たいカラムを「,」区切りで (例 "id,data" )
     * @param  string $prmStrOption 条件文"where id = 3" などとする(SQLインジェクション対策は関数外でお願いします)
     * @return array  結果の配列(二次元配列)
     */
    public function select($prmStrTable, $prmStrCol = "*", $prmStrOption = "")
    {
        return FDB :: getAssoc(FDB :: getSelectSql($prmStrTable, $prmStrCol, $prmStrOption));
    }

    /**
     * データベースからデータを1行selectします。
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmStrCol    得たいカラムを「,」区切りで (例 "id,data" )
     * @param  string $prmStrOption 条件文"where id = 3" などとする(SQLインジェクション対策は関数外でお願いします)
     * @return array  結果の配列(一次元配列)
     */
    public function select1($prmStrTable, $prmStrCol = "*", $prmStrOption = "")
    {
        $tmp = FDB :: getAssoc(FDB :: getSelectSql($prmStrTable, $prmStrCol, $prmStrOption . " LIMIT 1"));

        return $tmp[0];
    }

    /**
     * データベースからデータを1行の最初の1カラムだけselectします。
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmStrCol    得たいカラムを「,」区切りで (例 "id,data" )
     * @param  string $prmStrOption 条件文"where id = 3" などとする(SQLインジェクション対策は関数外でお願いします)
     * @return string 1行1カラム
     */
    public function select11($prmStrTable, $prmStrCol = "*", $prmStrOption = "")
    {
        $tmp = FDB :: getAssoc(FDB :: getSelectSql($prmStrTable, $prmStrCol, $prmStrOption . " LIMIT 1"));

        return array_shift($tmp[0]);
    }

    /**
     * データベースのデータをupdateします。
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmAryData   キー:カラム バリュー:データ の連想配列
     * @param  string $prmStrOption 条件文"where id = 3" など
     * @return mixed  結果Object
     */
    public function update($prmStrTable, $prmAryData, $prmStrOption = "")
    {
        return FDB :: sql(FDB :: getUpdateSql($prmStrTable, $prmAryData, $prmStrOption), true);
    }

    /**
     * データベースにinsertします。
     * @param  string $prmStrTable テーブル名
     * @param  string $prmAryData  キー:カラム バリュー:データ の連想配列
     * @return mixed  結果Object
     */
    public function insert($prmStrTable, $prmAryData)
    {
        return FDB :: sql(FDB :: getInsertSql($prmStrTable, $prmAryData), true);
    }

    /**
     * データベースのデータをdeleteします。
     * @param
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmStrOption 条件文"where id = 3" など
     * @return mixed  結果Object
     */
    public function delete($prmStrTable, $prmStrOption = "")
    {
        return FDB :: sql(FDB :: getDeleteSql($prmStrTable, $prmStrOption), true);
    }

    /**
     *  配列をDBにinsert
     * insertする配列はエスケープ＆エンコードされている必要があります。
     *  例: transAry2DB("dbtable",$array, false, 2,array("id","data"),100);
     * @param string $prmStrTable     テーブル名
     * @param string $prmAryData      配列
     * @param string $prmBolOutputSql trueなら実行せずにSQLを返す
     * @param int    $prmLimit        何件ずつinsertするか
     */
    public function insertArray($prmStrTable, $prmAryData)
    {
        $count = 0;
        $tmpAryData = array();
        if(is_array($prmAryData))
        foreach ($prmAryData as $valAryData) {
            $tmpAryData[] = FDB::escapeArray($valAryData);

            if (++$count%10==0) {
                if(FDB::sql(FDB::getInsertArraySql($prmStrTable, $tmpAryData), true)===false)

                    return false;
                $tmpAryData = array();
            }
        }
        if (!empty($tmpAryData)) {
            if(FDB::sql(FDB::getInsertArraySql($prmStrTable, $tmpAryData), true)===false)

                return false;
        }

        return true;
    }

    /**
     * データを登録(escapeもしてくれる)
     * @param  string $mode      new or update
     * @param  string $prmTable  対象テーブル
     * @param  string $prmData   登録するデータ
     * @param  string $prmOption whereなど（省略可能）
     * @return mixed  結果Object
     */
    public function setData($prmMode, $prmTable, $prmData, $prmOption="")
    {
        switch ($prmMode) {
            case "new":
                return (FDB::insert($prmTable, FDB::escapeArray($prmData))===false)? false:$prmData;
            case "update":
                return (FDB::update($prmTable, FDB::escapeArray($prmData), $prmOption)===false)? false:$prmData;
            default:
                return false;
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //	その他DB問い合わせ系
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    /**
     * 指定シーケンスのnextvalを取得
     * @param  string $parmSeq シーケンス名
     * @return int    値
     */
    public function getNextVal($prmSeq)
    {
        global $con;
        //ver.1.4/
        if(FDB::isError($rs = $con->nextID($prmSeq), "FDB::getNextVal({$prmSeq})"))

            return false;
        return $rs;
    }

    /**
     * 指定idのlast insert idを取得
     * @param  string $parmSeq シーケンス名
     * @return int    値
     */
    public function getLastInsertedId()
    {
        $insert_id = FDB :: getAssoc("SELECT LAST_INSERT_ID() as lsid;");
        if(isset($insert_id[0]['lsid']))

            return $insert_id[0]['lsid'];
        return false;
    }

    /**
     * 条件に一致する行が存在するかどうかを調べ、true|falseを返します
     * @param  string $prmStrTable  テーブル名
     * @param  string $prmStrOption where句(例: where uid=1)
     * @return bool   存在する:true 存在しない:false
     */
    public function is_exist($prmStrTable, $prmStrOption)
    {
        $res = FDB::sql("SELECT * FROM {$prmStrTable} {$prmStrOption} LIMIT 1");
        if($res===false)

            return false;
        $res = $res->numRows();

        return (!PEAR::isError($res) && $res>0);
    }

    /**
     * DBからtableのcolumnの名前とコメントの配列を返す。コメントがない場合はNULLが入る。
     * @param  string $prmStrTable テーブル名
     * @return array  コメント配列 array(array("column" => "カラム名","comment"=> "コメント"))
     */
    public function getComment($prmStrTable)
    {
        $cache = DIR_CACHE."FDB_getComment_{$prmStrTable}.ccache";
        if (file_exists($cache)) {
            $res = unserialize(file_get_contents($cache));
            if($res!==false)

                return $res;
        }

        switch (DB_TYPE) {
            case 'mysql':
                $table = FDB::escape($prmStrTable, false);
                $strSQL = <<<__SQL__
SHOW FULL COLUMNS FROM {$table};
__SQL__;
                $res = FDB::getAssoc($strSQL);
                if(is_array($res))
                foreach ($res as $key => $value) {
                    $res[$key] = array(
                        'column' => $value['field']
                        ,'comment' => $value['comment']
                    );
                }
                break;
            case 'pgsql':
            default:
                $table = FDB::escape($prmStrTable);
                $strSQL = <<<__SQL__
SELECT att.attname AS column,com.description AS comment
 FROM pg_stat_user_tables sut,pg_attribute att LEFT OUTER JOIN pg_description com ON att.attrelid=com.objoid AND att.attnum=com.objsubid
 WHERE att.attrelid=sut.relid AND att.attnum>0 AND sut.relname={$table} AND att.attname NOT LIKE '........pg.%'
 ORDER BY att.attnum;
__SQL__;
                //....pg.はカラムを消した時などに残るバックアップのゴミを消すための対処
                $res = FDB::getAssoc($strSQL);
                break;
        }

        $lock = new CbaseFileLock($cache);
        $lock->filePutContents(serialize($res));

        return $res;
    }

    /**
     * 指定したカラムに対してユニークなIDを発行する
     * （ここで返ったidを処理側でDB登録するまでの間に別人が同idを取得する危険有り。
     * 　登録直前に使うこと）
     * @param  string $prmStrTable テーブル名
     * @param  string $prmStrTable カラム名
     * @return string 指定したテーブル、カラムにおいてユニークなID
     */
    public function getUniqueId($prmStrTable, $prmStrColumn)
    {
        $aryData = FDB :: select($prmStrTable, $prmStrColumn);
        foreach ($aryData as $valData) {
            $aryCol[] = $valData[$prmStrColumn];
        }
        $bolFlag = true;
        while ($bolFlag) {
            $strRandomId = Get_RandID(8);
            $bolFlag = in_array($strRandomId, $aryCol);
        }

        return $strRandomId;
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    //
    //	補助処理系
    //
    ///////////////////////////////////////////////////////////////////////////////////////

    /**
     * SQLインジェクション対策で文字コードをクリーニングします
     * @param string	SQLに投げる文字列
     * @param string	クォーテーションをつけるかどうか
     * @return string SQLに投げる文字列
     */
    public function escape($prmStr, $prmQuote = true)
    {
        return sql_escape($prmStr, $prmQuote);
    }

    /**
     * SQLインジェクション対策で文字コードをクリーニングします
     * @param string	SQLに投げる文字列の配列
     * @param string	クォーテーションをつけるかどうか
     * @return array SQLに投げる文字列の配列
     */
    public function escapeArray($prmAry, $prmQuote = true)
    {
        foreach ($prmAry as $key => $val) {
            $prmAry[$key] = FDB :: escape($val, $prmQuote);
        }

        return $prmAry;
    }

    /**
     * 文字列連結用の文字列を返します
     * @param array	連結する文字列の配列
     * @return string 文字列連結用の文字列
     */
    public function concat($prmAry)
    {
        switch (DB_TYPE) {
            case 'mysql':
                return "concat(".implode(",", $prmAry).")";
            case 'pgsql';
            default:
                return implode("||", $prmAry);
        }
    }

    /**
     * 特定のテーブルをロックする
     * @param  string $prmTable テーブル名
     * @param string ロックモード 指定できるモードに関して
     *                          :http://www.postgresql.jp/document/pg702doc/user/sql-lock.htm
     * @return bool   成功したらtrue
     *
     * 特定行を更新する際にselectまでロックを掛けるにはEXCLUSIVE MODEを使用する
     *
     * DB全体にロックを掛けるときにはACCESS EXCLUSIVE MODEを使用する
     *
     *
     */
    public function lock($prmTable, $prmMode="")
    {
        switch (DB_TYPE) {
            case 'mysql':
                $prmMode = ($prmMode=="")? "READ":$prmMode;
                if(!is_array($prmTable))
                    $prmTable = array($prmTable);
                foreach ($prmTable as $key => $value) {
                    $prmTable[$key] = "{$value} {$prmMode}";
                }
                $prmTable = implode(",", $prmTable);
                $strSql = "LOCK TABLES {$prmTable};";
                break;
            case 'pgsql':
            default:
                $prmMode =  ($prmMode=="")? "ACCESS EXCLUSIVE MODE":$prmMode;
                $strSql = "LOCK TABLE {$prmTable} IN {$prmMode};";
                break;
        }
        global $FDB_lock;
        $FDB_lock = (!is_false(FDB::sql($strSql, true)));

        return $FDB_lock;
    }

    public function unlock()
    {
        global $FDB_lock;
        if(is_null($FDB_lock) || is_false($FDB_lock))

            return true;
        switch (DB_TYPE) {
            case 'mysql':
                $res = (!is_false(FDB::sql("UNLOCK TABLES;", true)));
                break;
            case 'pgsql';
            default:
                $res = true;
                break;
        }
        if(is_true($res))
            $FDB_lock = false;

        return $res;
    }

    /**
     * トランザクション開始
     */
    public function begin()
    {
        global $con;

        return (!FDB::isError($con->beginTransaction(), "FDB::begin()"));
    }

    /**
     * トランザクション終了
     */
    public function commit()
    {
        global $con;
        if (is_false(FDB::unlock())) {
            FDB::rollback();

            return false;
        }

        return (!FDB::isError($con->commit(), "FDB::commit()"));
    }

    /**
     * ロールバックする
     */
    public function rollback()
    {
        global $con;
        FDB::unlock();

        return (!FDB::isError($con->rollback(), "FDB::rollback()"));
    }
    //追加2012-05-14↓
    /**
     * 更新件数を取得
     */
    public function getUpdateNum()
    {
        global $con;

        return $con->affectedRows();
    }
    public function quoteSmart($param)
    {
        global $con;

        return $con->quote($param);
    }
    //追加2012-05-14↑
}

//接続準備
$dsn = DB_TYPE."://".DB_USER.":".DB_PASSWD."@".DB_HOST.":".DB_PORT."/".DB_NAME;
$op  = array("persistent"=>DB_PERSISTENT, "use_transactions"=>true);

//データベースに接続
for ($con_times=0; $con_times<6; ++$con_times) {
    if($con_times>0)
        sleep(5);

    $con = MDB2::connect($dsn, $op);

    if (PEAR::isError($con)) {
        FDB::outputLog($con->getDebugInfo());
        continue;
    }
    break;
}

//接続に失敗したらエラー表示して終了
if (PEAR::isError($con)) {
    if(DEBUG)
        echo $con->getMessage();
    else
        echo tmplGetError2_("ADMIN_ERROR_901");
    exit;
}
//失敗したらエラー表示して終了
if (is_false(FDB::transSetTimeZone())) {
    if(DEBUG)
        echo $con->getMessage();
    else
        echo tmplGetError2_("ADMIN_ERROR_902");
    FDB::outputLog($con->getDebugInfo());
    exit;
}
