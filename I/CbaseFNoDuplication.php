<?php
/**
 * 重複回答制御
 * Created on 2007/03/01
 * 作成者：cbase suzuki
 *
 *  重複回答された場合にはHTML_ALREADYENTRYに飛ばす
 */

define("CLASS_ERRORMSG_ND","設定エラーです。");//クラスに必要な定数が設定されていないとき

//--ローカル-------------------------------------
define("ND_COOKIE_LIFE",30);//30=>30日間有効のクッキーとなる(=30日後には未回答扱い)
define("COOKIE_ND_NAME_PRI","Rese_");//クッキーのキー名称の接頭語、この後にridが付く
//cookie offかonの未回答かは一回のhttpでは判断できない。
//	依って実装する場合は、リダイレクト処理をはさんで二度このクラスを通し、フラグ用のcookieを設置
//	>>>しかしcookie offの場合無限ループになるかもしれないので、$_GETでリダイレクトがわかるようにする
//				→そうするとQUERY_STRINGが変更されてしまう為、注意が必要
//define("ND_COOKIE_OFF",0);//0=>CookieOFF時はエラー画面にリダイレクトさせる (=Cookie必須)
//define("HTML_ND_COOKIE_OFF","nocookie.html");//defineクッキーoff時のリダイレクト先html
//----------------------------------------------

//index.php
    //$objNd=new Noduplication($rid, HTML_ALREADYENTRY);
    //$objNd->DoCheck();

//index.php
    //$objNd=new Noduplication($rid, HTML_ALREADYENTRY);
    //$objNd->recodeCookie();

/**
 * クッキーによる重複回答制御
 * Created on 2007/03/01
 * 作成者：cbase suzuki
 *
 *  重複回答された場合にはHTML_ALREADYENTRYに飛ばす
 */
class Noduplication
{
    //クラス変数宣言
    public $rid;
    public $ngredirect;
    public $duplicate;

    /**
     * コンストラクタ
     * @param string $rid        アンケートキー
     * @param string $ngredirect クッキーオフ時のリダイレクト先
     */
     function Noduplication($rid, $redirecthtml)
    {
        $this->rid			=$rid;
        $this->ngredirect	=$redirecthtml;
        $this->duplicate	=true;
        $this->checkVar();
    }

    /**
     * 定数/変数の確認
     *	@return mixed
     */
    public function checkVar()
    {
        $error=array();
        if (!$this->rid)						$error++;
        if (!$this->ngredirect)			$error++;
        if (!defined("ND_COOKIE_LIFE"))			$error++;
        if (!defined("COOKIE_ND_NAME_PRI"))	$error++;
        if ($error) {
            echo "ERROR: ".CLASS_ERRORMSG_ND;
            exit;
        }

        return;
    }

    /**
     * 重複チェックの実行
     *	@return mixed
     */
    public function DoCheck()
    {
        if (NODUPLICATION!=0) return true;
        $this->checkCookie();
        if ($this->duplicate==true) $this->redirect();
        return;
    }

    /**
     * 重複回答のクッキー取得
     *	@return mixed
     */
     function checkCookie()
     {
        //クッキー取得
        $cookie="";
        $cookie=$_COOKIE[COOKIE_ND_NAME_PRI.$this->rid];
        if ($cookie) {
            //取れる=重複回答
            return;
        } else {
            //取れない
                //cookie on = 未回答, //cookie off= 不明(双方ありうる)
            $this->duplicate=false;

            return;
        }
     }

    /**
     * リダイレクト処理
     */
    public function redirect()
    {
        $url = DOMAIN.DIR_MAIN.$this->ngredirect;
        header("Location: ".$url);
        exit;
    }

    /**
     * 回答済みというクッキーを記録する
     */
    public function recodeCookie()
    {
        setcookie(COOKIE_ND_NAME_PRI.$this->rid, "dummyvalue", time()+60*60*24*ND_COOKIE_LIFE);
    }
}

//--ローカル-------------------------------------

//----------------------------------------------

/**
 * 同一IPによる指定時間内重複回答制御
 * Created on 2007/08/27
 * 作成者：cbase suzuki
 *
 *  重複回答された場合には指定されたファイルに飛ばす
 */
//$objNd=new Noduplication2($evid, HTML_ALREADYENTRY, $intv=60);
//$objNd->DoCheck();
class Noduplication2
{
    //クラス変数宣言
    public $evid;
    public $ngredirect;
    public $intv;
    public $duplicate;

    /**
     * コンストラクタ
     * @param string $evid       アンケートid
     * @param string $ngredirect クッキーオフ時のリダイレクト先
     * @param string $intv       指定分以内の同一IPからの回答を拒否
     */
     function Noduplication2($rid, $redirecthtml, $intv=60)
    {
        //$this->evid			=$evid;
        $this->ngredirect	=$redirecthtml;
        $this->intv			=$intv;
        $this->duplicate	=true;
        $this->checkVar();
    }

    /**
     * 定数/変数の確認
     *	@return mixed
     */
    public function checkVar()
    {
        $error=array();
        if (!$this->rid)						$error++;
        if (!$this->ngredirect)			$error++;
        if (!defined("INTV_TIME"))			$error++;
        if ($error) {
            echo "ERROR: ".CLASS_ERRORMSG_ND;
            exit;
        }

        return;
    }

    /**
     * 重複チェックの実行
     *	@return mixed
     */
    public function DoCheck()
    {
        global $con;
        if (NODUPLICATION2!=0) return true;
        $this->checkData($con, array_shift(explode(',', (!is_null($_SERVER['HTTP_X_FORWARDED_FOR']))? $_SERVER['HTTP_X_FORWARDED_FOR']:$_SERVER['REMOTE_ADDR'])));
        if ($this->duplicate==true) $this->redirect();
        return;
    }

    /**
     * 重複回答のクッキー取得
     * @param object CbaseFDB.phpのオブジェクト
     * @param string IPAddress
     *	@return mixed
     */
     function checkData($con, $ip)
     {
        $limit=date("Y-m-d H:i:s",time()-60*$this->intv);
        $sql = "select evid from ".T_EVENT_DATA." ";
        $sql.= "where addr = ".FDB::quoteSmart($ip)." ";
        $sql.= "and cdate >= ".FDB::quoteSmart($limit)." ";
        $sql.= "limit 1 ";
        $rs=$con->query($sql);
        //DBエラーまたは該当のデータはあり
        if (FDB::isError($rs)||$rs->numRows()) return;

        //取れない
        $this->duplicate=false;

        return;
     }

    /**
     * リダイレクト処理
     */
    public function redirect()
    {
        $url = DOMAIN.DIR_MAIN.$this->ngredirect;
        header("Location: ".$url);
        exit;
    }

}
