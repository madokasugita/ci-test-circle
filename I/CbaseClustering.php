<?php
/*
 * Created on 2007/08/20
 *
 *		@author	suzuki.cbase
 *
 *	前提 ★重要★
 *		各サーバからpwなしでssh通信できること。
 *			→keychainなどを利用する
 *			但し、ssh/scpはプロトコルバージョン1である必要がある
 *			また、ssh-keyenではパスフレーズを用いてはならない
 *
 *		$USE_HOST_IPの設定がある
 *
 * Option
 * 		CC_DEBUG ->デバック用のファイルとして使用する場合に1とする  (廃止)
 * 		CC_LOG ->コマンドログ保存の為
 *
 */

define("CC_DOEXC",0);//0=>実行なし, 1=>実行
define("CC_SHOWCMD",0);//0=>なし, 1=>CMD画面出力
define("CC_LOG",1);//0=>ログなし, 1=>ログ記録

define("DIR_CCLOG","/work/www/");
define("FILE_CCLOG","clusterLog".date("Ym").".txt");
define("FILE_CCNGLOG","clusterLog".date("Ym")."_NG.txt");

//テスト時有効にしてwebrootにおき実行
//$USE_HOST_IP[] = "202.218.112.209";
//$cc=new CbaseClustering($USE_HOST_IP);
//$cc->doExec("/work/www/", "copyFile", "softbankIP.txt");

/**
 * ファイルを他サーバにコピーする
 *
 *  使い方
 * 		$cc=new CbaseClustering($USE_HOST_IP);
 * 		$cc->doExec("/path/to/file/", "copyFile", "filename.file");
 * 		//$cc->doExec("/path/to/file/", "deleteFile", "filename.file");
 */
class CbaseClustering
{
    public $ips,$myhost,$module,$noIp;

    /**
     * コンストラクタ
     *		@param array ipaddresses
     */
    public function CbaseClustering($notuseIP)
    {
        $aryIP[] = "202.218.112.207";
        $aryIP[] = "202.218.112.208";
        $aryIP[] = "202.218.112.209";
        $this->setCurrentIP();
        $this->ips = array();
        foreach ($aryIP as $ip) {
            if ($ip == $this->myhost) continue;
            $this->ips[] = $ip;
        }
        $this->module =& new CbaseClusteringSub();
    }

    /**
     * 自ホスト取得
     */
    public function setCurrentIP()
    {
        $this->myhost = $_SERVER['SERVER_ADDR'];
    }

    /**
     * 	全ての他サーバに対し実行する
     * 		@param string DIR(フルパス,最後にスラッシュ付き)
     * 		@param string 実行するCbaseClusteringSubクラスmethod名
     * 		@param string FileName
     * 		@param bool 非同期フラグ trueの場合は処理完了を待たない
     */
    public function doExec($dir, $func, $filename="",$async=false)
    {
        if (CC_SHOWCMD) echo $this->myhost.'<br>';
        if ($dir=="/") {
                error_log(date("Ymd")."\t".date("His")."\t".$func."\t".$_SERVER["SCRIPT_FILENAME"]."\n",3,DIR_CCLOG.FILE_CCNGLOG);

                return;
        }
        foreach ($this->ips as $ip) {
            $this->module->$func($dir, $filename, $ip,'www',$async);
        }
    }

}

class CbaseClusteringSub
{
    public function command($cmd)
    {
        if (CC_DOEXC)
            exec($cmd);//このログはシステムエラー時の復旧shellスクリプトにも使用するのでログ詳細は取得しない
        if (CC_SHOWCMD)
            echo $cmd.'<br>';
        if (CC_LOG)
            error_log($cmd."\n",3,DIR_CCLOG.FILE_CCLOG);
    }

    /**
     * ファイルを他サーバにコピーする
     *
     * 		@param string DIR(フルパス,最後にスラッシュ付き)
     * 		@param string FileName
     * 		@param string CopyTo(IPAddress)
     * 		@param string scp User
     * 		@param bool 非同期フラグ trueの場合は処理完了を待たない
     *
     */
    public function copyFile($dir, $filename, $ip, $user="www",$async=false)
    {

        $cmd = "scp -1 $dir$filename $user@$ip:$dir 2>&1 >/dev/null";
        if($async)
            $cmd .=' &';
        $this->command($cmd);
    }

    /**
     * ファイルを他サーバにコピーする
     *
     * 		@param string DIR(フルパス,最後にスラッシュ付き)
     * 		@param string FileName
     * 		@param string CopyFrom(IPAddress)
     * 		@param string scp User
     * 		@param bool 非同期フラグ trueの場合は処理完了を待たない
     */
    public function gatherFile($dir, $filename, $ip, $user="www",$async=false)
    {
        $tmpIp=explode(".", $ip);
        $cmd="scp -1 $user@$ip:$dir$filename $dir$filename".$tmpIp[3]."  2>&1 >/dev/null";

        if($async)
            $cmd .=' &';

        $this->command($cmd);
    }

    /**
     * 他サーバのファイルを削除する
     *
     * 		@param string DIR(フルパス,最後にスラッシュ付き)
     * 		@param string FileName
     * 		@param string host(IPAddress)
     * 		@param string ssh User
     * 		@param bool 非同期フラグ trueの場合は処理完了を待たない
     */
    public function deleteFile($dir, $filename, $ip, $user="www",$async=false)
    {
        $cmd = "ssh -1 $user@$ip rm -f $dir$filename";

        if($async)
            $cmd .='  > /dev/null &';

        $this->command($cmd);
    }

    //他サーバにディレクトリをコピーする
    //他サーバのディレクトリを削除する

}
