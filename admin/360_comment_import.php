<?php

//デバッグ用。登録せずにSQLを表示してくれる。
define('THISPAGE_NO_INSERT', 0);
if (THISPAGE_NO_INSERT) {
    define('DEBUG', 1);
}
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . '360_Importer.php');
require_once (DIR_LIB . 'CbaseFGeneral.php');
require_once (DIR_LIB . 'CbaseFForm.php');

session_start();
Check_AuthMng(basename(__FILE__));
/****************************************************************************************************************************/
define('PAGE_TITLE', 'コメントインポート');
/****************************************************************************************************************************/

define('COLNUM_COMMENTID',0);
define('COLNUM_FLAG',14);

class CommentImportModel extends ImportModel360
{
    public function importLine($line_no, $data)
    {
        if (!$data[COLNUM_FLAG])
            return;

        list($comennt_id,$seids) = explode('/',$data[COLNUM_COMMENTID]);
        list ($evdid, $evid) = resolveCommentId($comennt_id);
        $seids = explode(':',$seids);
        foreach ($seids as $k => $seid) {
            $array['other'] = FDB :: escape($data[COLNUM_FLAG+1+$k]);
            FDB :: update(T_EVENT_SUB_DATA, $array, 'where event_data_id = ' . FDB :: escape($evdid) . ' and seid = '.FDB::escape($seid));
        }
        $array = array ();
        $array['udate'] = FDB :: escape(date('Y-m-d H:i:s'));
        $array['ucount'] = 'ucount + 1';
        FDB :: update(T_EVENT_DATA, $array, 'where event_data_id = ' . FDB :: escape($evdid));

        return '';
    }
    /**
     * @return array インポートファイルの列フォーマットを指定。
     */
    public function getRows()
    {
        return array (
            "コメントID",
            "状況",
            "シートタイプ",
            "入力者区分",
            "対象者ID",
            "対象者氏名",
            "対象者所属",
            "修正チェック",
            "更新日時"
        );
    }
    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data) //override
    {
        global $countUpdate,$countnoUpdate,$comment_seids;

        if (!is_array($comment_seids)) {
            $comment_seids = array();
            foreach(FDB::select(T_EVENT_SUB, "evid,seid", "WHERE type2 = 't' GROUP BY evid,seid") as $se)
                $comment_seids[$se['evid']][] = $se['seid'];
        }

        list($comennt_id,$seids) = explode('/',$data[COLNUM_COMMENTID]);
        list ($evdid, $evid) = resolveCommentId($comennt_id);

        if($data[COLNUM_FLAG])
            $countUpdate++;
        else
            $countnoUpdate++;

        if ($comennt_id !== getCommentId($evdid, $evid)) {
            $error[] = "{$line_no}行目:コメントIDが不正です。";
        }

        $seids = explode(':',$seids);
        foreach ($seids as $k => $seid) {
            if(!in_array($seid, $comment_seids[$evid]))
                $error[] = "{$line_no}行目:コメント設問ではない設問が含まれています。(質問ID:".$seid.")";
        }

//		if (!$this->isSameRowCount($data))
//		{
//			$error[] = "{$line_no}行目:列数が不正です。" . count($data) . '列になっています。';
//		}
/*
        if ($data[COLNUM_FLAG] && mb_strlen($data[COLNUM_COMMENT1])>200) {
            $error[] = "{$line_no}行目:コメント1が200字を超えています。(".mb_strlen($data[COLNUM_COMMENT1])."字)";
        }
        if ($data[COLNUM_FLAG] && mb_strlen($data[COLNUM_COMMENT2])>200) {
            $error[] = "{$line_no}行目:コメント2が200字を超えています。(".mb_strlen($data[COLNUM_COMMENT2])."字)";
        }
*/

        return $error;
    }
}
function getCommentId($evdid, $evid)
{
    return $evdid . '_' . $evid . '_' . substr(sha1($evdid . $evid . SECRET_KEY), 0, 8);
}

function resolveCommentId($cid)
{
    $c = explode('_', $cid);
    $evdid = $c[0];
    $evid = $c[1];

    return array (
        $evdid,
        $evid
    );
}
class CommentImportDesign extends ImportDesign360
{
    /**
     * @param string $hidden     hiddenの値。formタグ内のどこかに含めてください
     * @param array  $forms      フォームセット。backとsubmitのみ
     * @param array  $values     表示用の値セット。model依存
     * @param int    $line_count 取り込み可能な行数
     * @param array  $errors     エラーが発生していれば、その内容の配列
     */
    public function getConfirmView($hidden, $forms, $values, $line_count, $errors = array ())
    {
        global $countUpdate,$countnoUpdate;
        if(!$countnoUpdate)
        $countnoUpdate=0;
        if(!$countUpdate)
        $countUpdate=0;

        $action = $this->getAction();
        $html = $this->getFormArea(<<<HTML
{$hidden}
{$line_count}行をインポートしますか？<br><br><br><br> (修正あり:<b>{$countUpdate}</b>件 修正なし:<b>{$countnoUpdate}</b>件)<br><br><br><br>
{$forms['back']}
{$forms['submit']}
</form>
HTML
);
        if (0 < count($errors)) {
            $html .=<<<HTML
<br><br>
以下のエラーがありました。<br>
このままインポートしますと、正常な行のみ取り込まれます。
<br><br>
HTML;
            $html .= $this->getErrorShow($errors);
        }

        return $html;
    }
}

$main = new Importer360(new CommentImportModel(), new CommentImportDesign());
$main->useSession = true;
$body = $main->run($_POST);
encodeWebAll();
print $body;
