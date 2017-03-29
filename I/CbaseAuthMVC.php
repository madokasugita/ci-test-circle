<?php
/**
 * @version 1.1 2008/04/07 ID,PWのn t rを削除してしまう不具合を修正
 */
require_once 'CbaseMVC.php';
require_once 'CbaseEnquete.php';
require_once 'CbaseFEnquete.php';
require_once 'CbaseFCrypt.php';
require_once 'CbaseFError.php';
class AuthModel extends Model
{
    public function AuthModel()
    {
        if (!$this->setEnquete($_POST))//Viewでもevidを使いたいので、先にアンケートを取得しておく 2008/02/29
            $this->redirectToErrorPage();
    }
    public function setEnquete(& $request)
    {
        global $GLOBAL_EVENT;

        if (!$request['rid']) {
            switch (AUTH_QUERY_STRING) {
                case 0 :
                    $return = Resolve_QueryString($_SERVER['QUERY_STRING']);
                    $request['rid'] = $return['rid'];
                    break;
                case 1 :
                    $request['rid'] = $_SERVER['QUERY_STRING'];
                    break;
                case 2 :
                    $request['evid'] = $_SERVER['QUERY_STRING'];
                    break;
            }
        }
        //ridからgetEnqueteするほうが負荷が低いです。
        if ($request['rid']) {
            $enq = Get_Enquete('rid', $request['rid'], 'evid', '');
        } elseif ($request['evid']) {
            $enq = Get_Enquete('id', $request['evid'], 'evid', '');
        }

        if (!$enq) {
            return false;
        }

        $GLOBAL_EVENT = $enq[-1];
        if (!isNeedAuthByFlgo($GLOBAL_EVENT['flgo'])) {
            return false;
        }

        return true;
    }

    public function tryLogin(& $request)
    {
        global $GLOBAL_EVENT, $ERROR;

        $request['id'] = $request[$request['idkey']];
        $request['pw'] = $request[$request['pwkey']];

        $request['id'] = ereg_replace("[ 　:\r\n\t]",'',$request['id']);
        $request['pw'] = ereg_replace("[ 　:\r\n\t]",'',$request['pw']);

        //マスタ認証 (マスタのid,pwで認証)
        if ($GLOBAL_EVENT['flgo'] == 2) {

            $user = FDB :: select1(T_USER_MST, '*', 'where id = ' . FDB :: escape($request['id']) . ' and pw = ' . FDB :: escape($request['pw']));

            if ($user && $user['evid'] != $GLOBAL_EVENT['evid']) {
                $ERROR['message1'] = FError :: get("AUTH_ERROR");

                return false;
            }

            if ($user) {
                //アンケートへリダイレクト
                $GLOBALS['AuthSession']->sessionRestart();
                $_SESSION['auth_user'] = $user;

                $q = Create_QueryString($user['serial_no'], $GLOBAL_EVENT['rid']);
                $q .= '&c=' . encrypt($GLOBAL_EVENT['rid'] . time());
                header('Location: ' . REDIRECT_OK . '?' . $q.'&'.getSID());
                exit;
            }
        }
        //オープンアンケート認証 (eventテーブルのid,pwで認証)
        elseif ($GLOBAL_EVENT['flgo'] == 3) {
            if ($GLOBAL_EVENT['id'] === $request['id'] && $GLOBAL_EVENT['pw'] === $request['pw']) {
                $q = Create_QueryString(Get_RandID(8), $GLOBAL_EVENT['rid']);
                $q .= '&c=' . encrypt($GLOBAL_EVENT['rid'] . time());
                header('Location: ' . REDIRECT_OK . '?' . $q);
                exit;
            }
        }

        $ERROR['message1'] = FError :: get("AUTH_ERROR");

        return false;
    }

    public function redirectToErrorPage()
    {
        $this->location(ERROR_PAGE);
        exit;
    }
    /**
     * 開始-終了期間内であるかどうかをチェックし、不可ならエラー画面へ
     * @param array $event 表示しようとしているアンケートのevent
     */
    public function checkTerm()
    {
        global $GLOBAL_EVENT;
        $now = mktime();
        if ($GLOBAL_EVENT['sdate']) {
            if ($now < $this->getTime($GLOBAL_EVENT['sdate'])) {
                $this->location(DOMAIN . DIR_MAIN . 'closed.html');
            }
        }
        if ($GLOBAL_EVENT['edate']) {
            if ($this->getTime($GLOBAL_EVENT['edate']) <= $now) {
                $this->location(DOMAIN . DIR_MAIN . 'closed.html');
            }
        }
    }
    /**
     * 「YYYY-MM-DD HH:ii:ss」の形式からunixtimeを取得する
     * @param  string $date 「YYYY-MM-DD HH:ii:ss」
     * @return int    unixtime
     */
    public function getTime($date)
    {
        $tmpar = explode(' ', $date);
        $tmpdt = explode('-', $tmpar[0]);
        $tmptm = explode(':', $tmpar[1]);

        return mktime($tmptm[0], $tmptm[1], $tmptm[2], $tmpdt[1], $tmpdt[2], $tmpdt[0]);
    }
}

class AuthView extends View
{
    public function AuthView()
    {
        global $CONTROLL,$GLOBAL_EVENT;
        $evid = $GLOBAL_EVENT['evid'];

        $this->PHP_SELF = getPHP_SELF(); //."?".getSID(); セッションはログイン成功まで使わないため削除

        $header = encodeFileIn(file_get_contents(TEMPLATE_HEADER));
        $form = encodeFileIn(file_get_contents(TEMPLATE_FORM));
        $footer = encodeFileIn(file_get_contents(TEMPLATE_FOOTER));

        /*
        evidごとにログインフォームを分ける場合以下のように変更。
        if(is_exsist()) でなければデフォルト　というようにするとよいかもしれないです。

        $header = encodeFileIn(file_get_contents(DIR_TMPL.$evid.'header.txt'));
        $form = encodeFileIn(file_get_contents(DIR_TMPL.$evid.'form.txt'));
        $footer = encodeFileIn(file_get_contents(DIR_TMPL.$evid.'footer.txt'));

        注意:
        formタグ(閉じタグも)はどのテンプレも含んではいけない (以下で自動挿入するため)

        テンプレートに記述する内容:
        %%%%name%%%% ->アンケート名に置換
        %%%%sdate%%%% -> 開始日に置換
        %%%%edate%%%% -> 終了日に置換
        %%%%ERROR:message1%%%% -> エラーメッセージに置換

        IDのフォーム
        <input class="inputform" type="text" name="%%%%CONTROLL:idkey%%%%" value="%%%%POST:id%%%%">

        PWのフォーム
        <input class="inputform" type="password" name="%%%%CONTROLL:pwkey%%%%" value="%%%%POST:pw%%%%">
        */

        $CONTROLL['idkey'] = 'id' . time();
        $CONTROLL['pwkey'] = 'pw' . time();
        $this->HTML =<<<HTML
{$header}
<br>
<form action="{$this->PHP_SELF}" method="post" style="display:inline" autocomplete="off">
{$form}
<input type="hidden" name="idkey" value="%%%%CONTROLL:idkey%%%%">
<input type="hidden" name="pwkey" value="%%%%CONTROLL:pwkey%%%%">
<input type="hidden" name="rid" value="%%%%rid%%%%">
<input type="hidden" name="mode" value="tryLogin">
</form>


<script language="javascript">
<!--
window.onload=function () {document.getElementsByName('%%%%CONTROLL:idkey%%%%')[0].focus();}
//-->
</script>


{$footer}
HTML;
    }
    /**
    * subevent編集画面テンプレート内の %%%%hoge%%%%を適切なものに置き換えます。
    */
    public function ReplaceHTMLCallBack($match)
    {
        global $CONTROLL, $ERROR, $GLOBAL_EVENT;
        $key = $match[1];
        if ($key == 'name') {
            return $GLOBAL_EVENT['name'];
        }
        if ($key == 'sdate') {
            if (!$GLOBAL_EVENT['sdate'])
                return "未設定";
            return date(DATE_FORMAT, strtotime($GLOBAL_EVENT['sdate']));
        }
        if ($key == 'edate') {
            if (!$GLOBAL_EVENT['edate'])
                return "未設定";
            return date(DATE_FORMAT, strtotime($GLOBAL_EVENT['edate']));
        }
        if ($key == 'rid') {
            return $GLOBAL_EVENT['rid'];
        }

        if (ereg('([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)', $key, $match2)) {
            $array_name = $match2[1];
            $key = $match2[2];
        } else {
            $key = $match[1];
        }

        if ($array_name == 'CONTROLL') {

            $val = $CONTROLL[$key];
        } elseif ($array_name == 'ERROR') {
            $val = $ERROR[$key];
        }

        return transHtmlentities($val);
    }

}

class AuthController extends Controller
{

    public function AuthController(& $model, & $view)
    {
        $this->Controller($model, $view);
        switch ($this->mode) {
            case 'tryLogin' :
                $this->model->tryLogin($this->request);
                break;

            default :
                $this->model->checkTerm();
        }
    }
}

/***************************************************************/
