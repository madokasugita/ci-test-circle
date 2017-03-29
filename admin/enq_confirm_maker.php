<?php
define("DIR_ROOT", "../");
require_once(DIR_ROOT."crm_define.php");
require_once(DIR_LIB."CbaseFForm.php");
require_once(DIR_LIB."CbaseFCheckModule.php");
require_once(DIR_LIB."CbaseFError.php");
require_once(DIR_LIB."CbaseFDBClass.php");
require_once(DIR_LIB."CbaseFEnquete.php");
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng('enq_event.php');
$evid = Check_AuthMngEvid($_GET['evid']);

/**
 * 簡易の確認画面を作成する
 * 作成：2007/02/23
 * @author Cbase akama
 */
class ConfirmMaker
{
    public $evid;

    /**
     * ファクトリーメソッド。=&で受け取ること。
     * @param  int    $evid evid
     * @return object ConfirmMaker 生成したオブジェクト
     * @author Cbase akama
     */
    function &create($evid)
    {
        $instance = new ConfirmMaker();
        if ($instance->isEvid ($evid)) {
            $instance->evid = $evid;
        } else {
            return new CbaseException("urlに引数が不足してるか、不正な引数です。");
        }

        return $instance;
    }

    /**
     * evidの妥当性をチェック
     * @param  int  $evid evid
     * @return bool エラーでなければtrue
     * @author Cbase akama
     */
    public function isEvid($evid)
    {
        if (FCheck::isNumber($evid)) {
            return true;
        }

        return false;
    }

    /**
     * 引数を確認し、エラーなら表示して終了。クラス関数でクラス外の処理すべてにつける。
     * @param  mixed $obj なんでもいい
     * @return mixed $objが返る
     * @author Cbase akama
     */
    public function run($obj)
    {
        if (FError::is($obj)) {
            echo $obj->getInfo();
            exit;
        }

        return $obj;
    }

    /**
     * メイン処理
     * @author Cbase akama
     */
    public function main($post="")
    {
        if ($post) {
            if(FError::is($res = $this->makeConfirm())) return $res;

            return $this->viewSecond();
        } else {
            return $this->viewFirst();
        }
    }

    /**
     * 確認画面を作成
     * @return bool true　またはエラーオブジェクト
     * @author Cbase akama
     */
    public function makeConfirm()
    {
        //DBオブジェクト確保。2/23現在FEnqueteがFDBを呼んでおりあまり意味は無いためコメントに
        //$db =& FDB::getInstance();
        $enquete = Get_Enquete("id", $this->evid, "", "");
        $event = &$enquete[-1];
        $subevents = array();
        foreach ($enquete[0] as $subevent) {
            if($subevent['seid']%1000==900)
                continue;
            $subevents[] = 	$subevent;
        }
        $page = $subevents[count($subevents) - 1]["page"] + 1;

        if (FError::is($html = $this->makeConfirmHtml($subevents))) return $html;

        $evid = $this->evid;
        $seid = $this->evid*1000+900;
        FDB::delete(T_EVENT_SUB,'where evid = '.FDB::escape($evid).' and seid = '.FDB::escape($seid));

        //登録データ
        $saveData = array(
            "evid"=>$evid
            ,"seid" => $seid
            ,"title" => "確認画面"
            ,"type1" => "0"
            ,"type2" => "n"
            ,"hissu" => 0
            ,"width" => 0
            ,"rows"  => 0
            ,"page"  => $page
            ,"html1" => ''
            ,"html2" => $html

        );

        Save_SubEnquete("new", $saveData);
        //eventのページを増やす
        $event["lastpage"] = $page;
        Save_Enquete("update", $event);

        return true;
    }

    /**
     * 確認画面のhtml出力
     * @param  array  $subevents サブイベントの配列
     * @return string html またはエラーオブジェクト
     * @author Cbase akama
     */
    public function makeConfirmHtml($subevents)
    {
        if(!$subevents) return new CbaseException("対象質問がありません");

        $html = "";

        $num = 0;
        foreach ($subevents as $v) {
            if($v['seid']%1000==900)
                continue;
            if (ereg('%%%%message',$v['html2'])) {
                $html .= ereg_replace('%%%%message[0-9]*%%%%','%%%%messageid'.$v['seid'].'%%%%',$v['html2']);
            } else {
                if(ereg('%%%%title%%%%',$v['html2']))
                    $v['html2'] = str_replace('%%%%title%%%%','%%%%title'.$num.'%%%%',$v['html2']);
                $html .= str_replace('%%%%form%%%%','%%%%num'.$num.'%%%%',$v['html2']);
            }

            if($v['type2'] != 'n')
                $num++;

        }

        return $html;
    }

    /**
     * 最初の画面のHTML
     * @author Cbase akama
     */
    public function viewFirst()
    {
        $formSubmit = FForm::submit("submit","作成");
        $formExt = 'action="'.getPHP_SELF().'?'.getSID().'&evid='.$this->evid.'" method="post"';
        $DIR_IMG = DIR_IMG;
        $html = <<<__HTML__
<form {$formExt}>

<table width="430" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width="13" valign="middle">
            <center>
                <img src="{$DIR_IMG}icon_inf.gif" width="13" height="13">
            </center>
        </td>
        <td width="107" valign="middle"><font size="2">確認画面簡易作成</font></td>
        <td width="287" valign="middle"><font color="#999999" size="2">※新しく確認画面を作成します。</font></td>
    </tr>
    <tr valign="top">
        <td height="13" colspan="3">
            <table width="430" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td height="1" background="{$DIR_IMG}line_r.gif"><img src="{$DIR_IMG}spacer.gif" width="1" height="1"></td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<font size="2">
<p>確認画面を作成します。この操作によって、新規ページに新規フリースペースとして確認画面が追加されます。</p>
<p>操作時点で登録されている質問に対して作成しますので、全ての質問を作成後に行ってください。</p>
<p>作成された確認画面のデザイン等は質問設定で編集可能です。確認不要項目の削除等はそちらで行えます。</p>
<p>質問が追加された場合は質問設定より「確認画面」を削除してから再度下の作成ボタンを実行してください。</p>
</font>
<table width="550" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td height="1" background="{$DIR_IMG}line_r.gif"><img src="{$DIR_IMG}spacer.gif" width="1" height="1"></td>
    </tr>
</table>

    <br>{$formSubmit}
</form>
__HTML__;

        return $html;
    }

    /**
     * 二番目の画面のHTML
     * @author Cbase akama
     */
    public function viewSecond()
    {

        $html = <<<__HTML__
    完了しました。
    <br><br>
    <input type="button" value="閉じる" onClick="window.close();">
__HTML__;

        return $html;
    }
}

ConfirmMaker::run($main =& ConfirmMaker::Create($evid));

ConfirmMaker::run($view = $main->main($_POST));

$objHtml =& new ResearchAdminHtml("確認画面作成");
echo $objHtml->getMainHtml($view);
exit;
