<?php

/**
 * 依存クラス：なし(将来的にはタグ作成部をFFormに渡したい)
 * バージョン：2.0(2008/1/18)
 * 2.0.1(2008/4/8) htmlエスケープを追加
 * 2.0.2(2008/11/17) CbaseCommon依存を解消
 */

class DataEditor
{
    public $data;
    public $design;
    public $target;
    public $useSession = true;
    public $sessionName = 'dataediterusevalue';
    /** newSessionをtrueにすると、二画面目移行で新しくセッション開始 */
    public $newSession = false;

    /**
     * 引数には下にあるインターフェースと同じメソッドを持つクラスを指定する
     *
     * @param object DataEditAdapter $data   編集データの定義クラス
     * @param object IDesign         $design デザインクラス
     */
    public function DataEditor(& $data, & $design)
    {
        $this->data = & $data;
        if ($data->data)
            $this->setTarget($data->data);
        $this->design = & $design;
        $this->design->setAdapter($this->data);
    }

    /**
     * 状態に応じて処理を行い、表示画面を返す
     * @return string html
     */
    public function run()
    {
        foreach ($this->getPost() as $k => $v) {
            if (strpos($k, 'data_editor_mode') !== false) {
                $modes = explode(':', $k);
                $mode = $modes[1];
                break;
            }
        }
        if (!$mode) {
            $modes = $this->getCommand($this->getPost());
            $mode = array_shift($modes);
        }
        $this->setMode($mode);

        return $this->main($this->setNowViewByMode($mode), $modes);
    }

    public function setMode($mode)
    {
        $this->data->mode = $mode;
    }

    public function getMode()
    {
        return $this->data->mode;
    }

    public function setNowViewByMode($mode)
    {
        switch ($mode) {
            case 'confirm' :
                $now = 'confirm';
                break;
            case 'complete' :
                $now = 'complete';
                break;
            case 'top' :
            default : //最初に開いた時の処理
                $now = 'top';
                break;
        }
        $this->data->now = $now;

        return $now;
    }

    public function getNowView()
    {
        return $this->data->now;
    }

    public function main($mode, $values)
    {
        switch ($mode) {
            case 'confirm' :
                return $this->runConfirmView();
            case 'complete' :
                return $this->runCompleteView();
            case 'top' :
                return $this->runEditView();
        }
    }

    //-------------------------------------------------------------------------

    public function getCommand($post)
    {
        if ($post['data_editor_mode']) {
            $c = array_keys($post['data_editor_mode']);

            return explode(':', $c[0]);
        }

        return array ();
    }

    public function isPosted()
    {
        $post = $this->getPost();

        return ($post['data_editor_mode']);
    }

    public function getSubmitName($command)
    {
        return 'data_editor_mode[' . $command . ']';
    }

    /**
     * 編集画面の処理と表示を行う
     * @return string html
     */
    public function runEditView($def = array (), $error = array ())
    {
        $this->setMode('top');
        if (!$def) {
            $def = $this->getPost();
            if ($this->target)
                $def = $def ? $def : $this->target;
        }
        $form = $this->data->getForm($def);
        $form['next'] = $this->getConfirmButton() .
        $this->getSessionHidden();

        return $this->design->getEditView($form, $error);
    }

    /**
     * 確認画面の処理と表示を行う
     * @return string html
     */
    public function runConfirmView()
    {
        $post = $this->getPost();
        $error = $this->data->validate($post);
        if ($error) {
            $error = $this->formatErrorMessages($error);

            return $this->runEditView($post, $error);
        } else {
            $message = $this->formatNoticeMessages($this->data->getNotice($post));

            $show = $this->data->getFormatValue($this->arrayEscape($post));

            $show['previous'] = $this->getPreviousButton();
            $show['next'] = $this->getRegisterButton() . $this->getSessionHidden();

            return $this->design->getConfirmView($show, $message);
        }
    }

    public function getPreviousButton($backcommand = 'top')
    {
        return $this->design->getPreviousButton($this->getSubmitName($backcommand));
    }

    public function getConfirmButton()
    {
        return $this->design->getConfirmButton($this->getSubmitName('confirm'));
    }

    public function getRegisterButton()
    {
        return $this->design->getRegisterButton($this->getSubmitName('complete'));
    }

    /**
     * 	セッション有効の際のhidden生成処理
     */
    public function getSessionHidden()
    {
        $hidden = array ();
        if ($this->useSession) {
            $now = $this->getNowView();
            if ($this->newSession && $now === 'top')
                return '';
            $hidden[SESSIONID] = html_escape(session_id());
            if ($this->getNowView() === 'confirm') {
                $reloadId = md5(uniqid(mt_rand()));
                $_SESSION[$this->sessionName]['reload_id'] = $reloadId;
                $hidden['reload_id'] = $reloadId;
            }
        }
        foreach ($hidden as $k => $v) {
            $res .= '<input type="hidden" name="' . $this->escape($k) . '" value="' . $this->escape($v) . '" />';
        }

        return $res;
    }

    public function validateSession()
    {
        if ($this->useSession) {
            $post = $this->getPost();
            if ($post['reload_id'] !== $_SESSION[$this->sessionName]['reload_id']) {
                return array (
                    '多重投稿は禁止されています'
                );
            }
        }

        return array ();
    }

    public function clearSession()
    {
        if ($this->useSession) {
            $_SESSION[$this->sessionName] = null;
        }
    }

    /**
     * エラーメッセージの配列を一括でフォーマットする
     * @param  array $errors エラーメッセージ配列
     * @return array フォーマット後配列。キーと値の関係は保持される。
     */
    public function formatErrorMessages($errors)
    {
        $results = array ();
        foreach ($errors as $k => $v) {
            $results[$k] = $this->design->getErrorFormat($v, $k);
        }

        return $results;
    }

    /**
     * 警告メッセージの配列を一括でフォーマットする
     * @param  array $errors 警告メッセージ配列
     * @return array フォーマット後配列。キーと値の関係は保持される。
     */
    public function formatNoticeMessages($errors)
    {
        $results = array ();
        foreach ($errors as $k => $v) {
            $results[$k] = $this->design->getNoticeFormat($v, $k);
        }

        return $results;
    }

    /**
     * 登録完了画面の処理と表示を行う
     * @return string html
     */
    public function runCompleteView()
    {
        $data = $this->getPost();
        $error = $this->data->validate($data);
        if ($error || $error = $this->validateSession()) {
            $this->onRegisterError($error);

            return $this->design->getErrorView('登録に失敗しました<br>' . implode('<br>', $error));
        }
        $data = $this->data->onBeforeSave($data);
        if ($this->data->save($this->pickMyData($this->data->getSaveValue($data)))) {
            $this->onComplete($data);

            return $this->design->getCompleteView($data);
        }
        $this->onRegisterError(null);

        return $this->design->getErrorView('登録に失敗しました');
    }

    /**
     * 登録エラーの際に処理を付与できる。
     * @param array $error 発生したエラーの配列(validate依存)。DB登録に失敗の場合はnull
     */
    public function onRegisterError($error)
    {
    }

    /**
     * 完了の際に処理を付与できる。
     * @param array $data 登録されたデータ
     */
    public function onComplete($data)
    {
        $this->clearSession();
    }

    /**
     * Postを取得する。POST以外からとる場合はオーバーライドする
     */
    public function getPost()
    {
        return $_POST ? $_POST : array ();
    }

    /**
     * POSTから$this->data->getColumnの値をキーにもつ値を取得する
     * @return array 元の値
     */
    public function pickMyData($data, $escape = true)
    {
        $res = array ();
        foreach ($this->data->getColumns() as $v) {
            if (isset ($data[$v])) {
                $res[$v] = (is_a($data[$v], 'NullValue')) ? null : $this->arrayEscape($data[$v]);
            }
        }

        return $res;
    }

    /**
     * 編集対象を設定する
     */
    public function setTarget($array)
    {
        $this->data->hasTarget = true;
        $this->target = $this->data->getEditValue($array);
    }

    /**
     * POSTから取得の際にかけるエスケープ処理のループ
     */
    public function arrayEscape($strs)
    {
        if (is_array($strs)) {
            $res = array ();
            foreach ($strs as $k => $v) {
                $res[$k] = $this->arrayEscape($v);
            }
        } else {
            $res = $this->escape($strs);
        }

        return $res;
    }

    /**
     * POSTから取得の際にかけるエスケープ処理
     * 確認画面・保存時と複数回呼ばれるので、それでも問題の無い処理とすること
     */
    public function escape($str)
    {
        return trim($str);
    }
}

////■データクラスのインターフェース
/**
 * ■データクラスとの接続用基底クラス
 * このクラスを継承し、
 * データクラス<--<use>--アダプタクラス　のようにつかってください
 * 理念としては、アダプタクラスはデータクラスを知っているが、
 * データクラスはアダプタクラスを知らないように作るとよい
 *
 */
class DataEditAdapter
{
    public $hasTarget = false;
    public $now;
    public $useHtmlEscape = true;

    public function setupNoEscapeColumns()
    {
        return array ();
    }

    public $noEscapeColumns;
    public function getNoEscapeColumns()
    {
        if (!$this->noEscapeColumns) {
            $this->noEscapeColumns = $this->setupNoEscapeColumns();
        }

        return $this->noEscapeColumns;
    }

    public function getNowView()
    {
        return $this->now;
    }

    /**
     * ◆abstract
     * @return array このデータで使えるカラム名=>名称のリストを返す
     */
    public function setupColumns()
    {
        return array ();
    }

    /**
     * ◆abstruct
     * 保存用のデータが送られてくる
     * @param  array $data 保存用のデータ
     * @return bool  保存に成功すればtrue
     */
    public function save($data)
    {
        return false;
    }

    /**
     * ◆abstruct
     * 列ごとに作成したフォームを返す
     * @param  array  $data 各列の初期値の配列
     * @param  string $col  処理対象列
     * @return string 作成したフォーム
     */
    public function getFormCallback($data, $col)
    {
        return $data[$col];
    }

    /**
     * ◆virtual
     * 列ごとにエラーチェックを行う(nullでエラーなし)
     * @param  array  $data 入力値の配列
     * @param  string $col  処理対象列
     * @return string この列に対するエラー文言
     */
    public function validateCallback($data, $col)
    {
        return null;
    }

    /**
     * ◆virtual
     * 列ごとに確認画面で表示する警告のチェックを行う(nullでエラーなし)
     * @param  array  $data 入力値の配列
     * @param  string $col  処理対象列
     * @return string この列に対する警告文言
     */
    public function getNoticeCallback($data, $col)
    {
        return null;
    }

    /**
     * ◆virtual
     * 列ごとに画面表示用の値への変換を行う
     * @param  array  $data 入力値の配列
     * @param  string $col  処理対象列
     * @return string この列の表示値
     */
    public function getFormatValueCallback($data, $col)
    {
        return $data[$col];
    }

    /**
     * ◆virtual
     * 確認画面にて、登録画面へと受け渡す値を返す
     * 基本的にはPOSTされる値をそのまま渡すようにしており、特殊なケースのみ書き換える
     * @param  array  $data 各列の初期値の配列
     * @param  string $col  処理対象列
     * @return string 作成したフォームのhtml
     */
    public function getHiddenValue($data, $col, $colname = '')
    {
        $colname = $colname ? $colname : $col;
        if (is_array($data[$col])) {
            $form = '';
            foreach ($data[$col] as $k => $v) {
                $form .= $this->getHiddenValue($data[$col], $k, $colname . '[' . $k . ']');
            }

            return $form;
        }

        return $this->makeHiddenValueTag($colname, $data[$col]);
    }

    /**
     * ◆virtual
     * セーブ直前に呼び出される。
     * 用途はINSERTで得られるgetSaveValueでidが必須の場合、一度INSERTを行うなど
     * @param  array $post getSaveValueに送られる予定のポスト値
     * @return array getSaveValueに送られる値
     */
    public function onBeforeSave($post)
    {
        return $post;
    }

    /**
     * ◆virtual
     * 列ごとにデータクラス保存用の値への変換を行う
     * @param  array  $data 入力値の配列
     * @param  string $col  処理対象列
     * @return string DataClass::saveで使える値
     */
    public function getSaveValueCallback($data, $col)
    {
        return $data[$col];
    }

    /**
     * ◆virtual
     * 列ごとにデータエディタ用の値への変換を行う
     * DBなどから読み込んだデータをPOSTされた後のデータと同じ形式にすると考えてください
     * @param  array  $data データクラス読み出し後の配列
     * @param  string $col  処理対象列
     * @return string フォームのデフォルト値
     */
    public function getEditValueCallback($data, $col)
    {
        return $data[$col];
    }

    /**
     * ◆virtual
     * 確認画面については入力値をそのまま表示することにより脆弱性がある
     * よって、表示用の値についてはここで変換処理を通す。
     * 不要な場合はswitchなどで除外する
     * @param  string $key   変換するデータのキー
     * @param  string $value 変換するデータの値。これを変換したものを返すこと。
     * @return string エスケープ後の値
     */
    public function escapeFormatValue($key, $value)
    {
        //html_escape
        if (is_array($value)) {
            $res = array ();
            foreach ($value as $k => $v) {
                $res[$k] = $this->escapeFormatValue($k, $v);
            }
        } else {
            $string = html_escape($value);
            $res = preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", $string);
        }

        return $res;
    }

    //----------------------------------------------------------------------------

    public $columns;

    /**
     * このデータで使えるカラムを返す
     * とりあえず全部書いておけばよい
     * 入力項目を絞る場合は、このメソッドをオーバーライドして編集したい項目だけを書く
     * @return array カラム名のリスト
     */
    public function getColumns()
    {
        return array_keys($this->getColumnNames());
    }

    /**
     * @return array カラム名=>名称のリストを返す
     */
    public function getColumnNames()
    {
        if (!$this->columns) {
            $this->columns = $this->setupColumns();
        }

        return $this->columns;
    }

    public $data;
    public function DataEditAdapter($data = null)
    {
        $this->data = & $data;
    }

    /**
     * 与えられたデータのうち、自分に許可されたデータ全てに指定の関数を適用する
     * @param  array $data     走査対象データ
     * @param  array $callback 記述を固定しているため必ずarray(this, ○○)で指定のこと
     * @return array 処理後のデータ
     */
    public function walk($data, $callback)
    {
        $res = array ();

        foreach ($this->getColumns() as $col) {
            //本来は下記を使うところだが、速度を考慮して方式変更
            //$res[$col] = call_user_func_array($callback, array($data, $col));
            $v = $this-> $callback[1] ($data, $col);
            if (isset ($v)) {
                $res[$col] = $v;
            }
        }

        return $res;
    }

    /**
     * エディット画面のフォームを作成する
     * 出力はgetColumnsの項目と一致する（または含む）こと
     * @return array array["formのname"]=formのhtml
     */
    public function getForm($value = array ())
    {
        return $this->walk($value, array (
            $this,
            'getFormCallback'
        ));
    }

    /**
     * 入力値のチェックを行う。
     * 列ごとの主な処理はvalidateCallbackで。
     * @param  array $inputData 入力値
     * @return array array["formのname"]=エラー文字列
     */
    public function validate($inputData)
    {
        return $this->walk($inputData, array (
            $this,
            'validateCallback'
        ));
    }

    /**
     * エラーにはならないが、確認画面で警告表示を出したい場合に試用
     * 列ごとの主な処理はgetNoticeCallbackで。
     * @param  array $inputData 入力値
     * @return array array["formのname"]=エラー文字列
     */
    public function getNotice($inputData)
    {
        return $this->walk($inputData, array (
            $this,
            'getNoticeCallback'
        ));
    }

    /**
     * 確認画面での表示用の値をフォーマットして返す
     * @param  array  $data POSTで受け取った値
     * @return string 確認画面用データ（Design::getConfirmViewで使用）
     */
    public function getFormatValue($data)
    {
        //処理が特殊なのでwalkを使わない

        $escape = array ();
        foreach ($data as $k => $v) {
            $escape[$k] = $this->escapeFormatValue($k, $v);
        }
        $data = $escape;

        $res = array ();
        foreach ($this->getColumns() as $col) {
            if (isset ($data[$col])) {
                $v = $this->getFormatValueCallback($data, $col);
                if (isset ($v)) {
                    $res[$col] = $this->addHiddenValue($v, $data, $col);
                }
            }
        }

        return $res;
    }

    /**
     * DBの値を単に表示するだけの値に変換する
     * @param  array  $data POSTで受け取った値
     * @return string 確認画面用データ（Design::getConfirmViewで使用）
     */
    public function getFormatData($data, $escape = true)
    {
        //処理が特殊なのでwalkを使わない

        $data = $this->getEditValue($data);

        $defEscape = $this->useHtmlEscape;
        $this->useHtmlEscape = $escape;

        $escape = array ();
        foreach ($data as $k => $v) {
            $escape[$k] = $this->escapeFormatValue($k, $v);
        }
        $data = $escape;

        $res = array ();
        foreach ($this->getColumns() as $col) {
            if (isset ($data[$col])) {
                $v = $this->getFormatValueCallback($data, $col);
                if (isset ($v)) {
                    $res[$col] = $v;
                }
            }
        }
        $this->useHtmlEscape = $defEscape;

        return $res;
    }

    public function addHiddenValue($str, $data, $col)
    {
        if (is_array($str)) {
            $str['hidden'] = $this->getHiddenValue($data, $col);

            return $str;
        }

        return $str . $this->getHiddenValue($data, $col);
    }

    //TODO:CbaseFFormが完全safehtml対応したらそれを使ってください
    /**
     * hidden用のタグを作成する
     * @param  string $name タグのname
     * @param  string $data タグのvalue
     * @return string safe html
     */
    public function makeHiddenValueTag($name, $data)
    {
        $sName = html_escape($name);
        $sData = html_escape($data);

        return<<<__HTML__
<input type="hidden" name="{$sName}" value="{$sData}">
__HTML__;
    }

    /**
     * 保存用の値をフォーマットして返す
     * @param  array  $data POSTで受け取った値
     * @return string 保存用データ（saveEditDataに送られる）
     */
    public function getSaveValue($data)
    {
        return $this->walk($data, array (
            $this,
            'getSaveValueCallback'
        ));
    }

    /**
     * エディット画面での表示用の値をフォーマットして返す
     * SetTargetから送られてくる
     * ここで返した値の配列が、getEditFormの$valueとして送られてくる
     * @param  array  $data POSTで受け取った値
     * @return mixied エディット画面用データ（getEditFormのvalueとして使う値）
     */
    public function getEditValue($data)
    {
        return $this->walk($data, array (
            $this,
            'getEditValueCallback'
        ));
    }
}

//デザインクラスは以下を実装すること
//実装があれば委譲や別クラスでもOK
class DataEditDesign
{
    public $adapter;
    public function setAdapter(& $adapter)
    {
        $this->adapter = & $adapter;
    }

    function & getAdapter()
    {
        if (!$this->adapter) {
            echo 'designにadapterがセットされていません';
            exit;
        }

        return $this->adapter;
    }

    /**
     * ◆virtual
     * 編集画面の表示を行う
     * @return string html
     */
    public function getEditView($show, $error = array ())
    {
        $html=<<<__HTML__
<table class="searchbox">
__HTML__;
        foreach (ThisAdapter :: setupColumns() as $colkey => $colval) {
            if (!$colval) {
                $html.=$show[$colkey];
                continue;
            }
            $html .=<<<__HTML__
<tr>
<th>{$colval}</th>
<td class="tr2">
{$show[$colkey]}{$error[$colkey]}
</td>
</tr>
__HTML__;

        }

        $html .=<<<__HTML__
</table>
{$show['previous']}
{$show['next']}
__HTML__;

        return $html;
    }

    /**
     * ◆virtual
     * 確認画面の表示を行う
     * @return string html
     */
    public function getConfirmView($show, $message = array ())
    {
        return $this->getEditView($show, $message);
    }

    /**
     * ◆virtual
     * 登録完了画面の表示を行う
     * @return string html
     */
    public function getCompleteView($show)
    {
        return<<<__HTML__
<span style="color:red;size:13px;font-weight:bold">完了しました</span>
<br><br>
<a href="#" onclick="window.close()" style="text-decoration:underline">閉じる</a>

<script>
<!--
(function () {
if(!window.opener) return;

if(window.opener.document.getElementsByName('op[search]').length > 0)
    window.opener.document.getElementsByName('op[search]')[0].click();
else
    window.opener.location.reload();
})();
//-->
</script>
__HTML__;
    }

    /**
     * ◆virtual
     * 文字列をエラー表示用タグで囲んで返す
     * @param  string $str 囲む文字列
     * @return string html
     */
    public function getErrorFormat($str, $col)
    {
        return '<span style="color:#F00">' . $str . '</span>';
    }

    /**
     * ◆virtual
     * 文字列を警告表示用タグで囲んで返す
     * @param  string $str 囲む文字列
     * @return string html
     */
    public function getNoticeFormat($str, $col)
    {
        return $str;
    }

    /**
     * ◆virtual
     * エラー画面の表示を行う
     * @param  string $message エラーメッセージ
     * @return string html
     */
    public function getErrorView($message)
    {
        return $message;
    }

    /**
     * 確認ボタンを取得する
     * @param  string $name submitタグのname部分
     * @return string html $nameを用いたsubmitを含めること
     */
    public function getConfirmButton($name)
    {
        return<<<__HTML__
<input type="submit" name="{$name}" value="確認" class="white button big">
__HTML__;
    }

    /**
     * 戻るボタンを取得する
     * @param  string $name submitタグのname部分
     * @return string html $nameを用いたsubmitを含めること
     */
    public function getPreviousButton($name)
    {
        return<<<__HTML__
<input type="submit" name="{$name}" value="戻る" class="white button big">
__HTML__;
    }

    /**
     * 登録ボタンを取得する
     * @param  string $name submitタグのname部分
     * @return string html $nameを用いたsubmitを含めること
     */
    public function getRegisterButton($name)
    {
        return<<<__HTML__
<input type="submit" name="{$name}" value="登録する" class="white button big">
__HTML__;

    }
}

/**
 * 識別用Nullクラス
 * saveValuecallbackなどでこれを返すとnullを代入できる
 */
class NullValue
{
}
