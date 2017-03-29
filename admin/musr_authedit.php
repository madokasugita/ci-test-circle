<?php
/*
 * こんながめんにしたい
 *
 * [アンケート権限設定]
 *
 * [アンケート名]     　権限　権限　[編集]
 * [アンケート選択|▼]　権限　権限  [登録]　
 * [新規追加]
 *
 * [その他権限設定]（カテゴリ別など）
 * 権限　権限　権限
 *
 */

//define('DEBUG', 1);
define('DIR_ROOT', "../");
require_once(DIR_ROOT.'crm_define.php');
require_once(DIR_LIB.'CbaseFDB.php');
require_once(DIR_LIB.'CbaseFDBClass.php');
require_once(DIR_LIB.'CbaseFForm.php');
require_once(DIR_LIB.'CbaseAuthSet.php');
require_once (DIR_LIB . 'ResearchDesign.php');
require_once (DIR_LIB . 'CbaseHtml.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
encodeWebAll();

session_start();
require_once(DIR_LIB.'CbaseFManage.php');
Check_AuthMng(basename(__FILE__));

//この画面用の値に変換

class AuthEdit
{
    public $contents;
    public $design;
    public $events;
    public $editCategory;
    public $editEvid;
    public $mode;
    public $muid;

    public function AuthEdit($contents, &$design)
    {
        $this->contents = $contents;
        $this->design =& $design;
        $this->events = $this->getEvent();

        $this->muid = $_POST['id']? $_POST['id']: $_GET['id'];

        $this->setTarget($this->muid);

        if ($_POST) {
            $op = $this->getOperation($_POST);
            if ($op) {
                $this->mode = $op[1];
                switch ($this->mode) {
                    case 'edit':
                        $this->editCategory = $op[2];
                        $this->editEvid = $op[3];
                        break;
                    case 'submit':
                        //登録処理
                        $this->register($_POST);
                        break;
                    case 'new':
                        $this->editCategory = $op[2];
                        $this->editEvid = 'new';
                        break;
                }
            }
        }

    }

    public function register($post)
    {
        $dao =& new AuthSetDAO();
        $dao->saveFromAuthContents ($post['page'], $post['show'],
            $this->muid, ($post['event'])?$post['event']: null);

        $this->setTarget ($this->muid);
    }

    /**
     * 文字列の配列から操作を示すものを取得する
     * @param  array $params $_POSTなど
     * @return array 1=>操作 2以降=>引数
     */
    public function getOperation($params)
    {
        foreach ($params as $key => $val) {
            $op = explode(":", $key);
            if($op[0] == "op") return $op;
        }

        return false;
    }

    public function getEvent()
    {
        $ev = FDB::select(T_EVENT,'*','order by evid desc');
        $res = array();
        foreach ($ev as $v) {
            $res[$v['evid']] = $v;
        }

        return $res;
    }

    public $authset;
    public function setTarget($muid)
    {
        $this->authset = $this->getAuthSet($muid);
    }

    public function getAuthSet($muid)
    {
        $dao =& new AuthSetDAO();
        $authset = $dao->getByMuid($muid, 'ORDER BY evid');
        $res = array();
        foreach ($authset as $v) {
            if (is_null($v['evid'])) {
                $res['super'][] = $v;
            } else {
                $res[$v['evid']][] = $v;
            }
        }

        return $res;
    }

    public function show()
    {
        $body = array();
        foreach ($this->contents as $k => $v) {
            if ($k == 'enquete') {
                $body[] = $this->getEnqueteAuthArea($v);
            }
//			else
//			{
/*				$body[] = <<<__HTML__
<div>
<h2>メール権限設定</h2>
<table border>
    <tr>
        <td>権限１</td>
        <td>○</td>
    </tr>
    <tr>
        <td>権限２</td>
        <td>×</td>
    </tr>
</table>
<input type="submit" value="編集">
</div>
__HTML__;*/
//			}
        }
        $id = FForm::hidden('id', $this->muid);

        return $this->design->getCompleteHtml(implode('', $body).$id);
    }

    public function getEnqueteAuthArea($contents)
    {
        $enqauthset = $this->authset;
        $super = $enqauthset['super'];
        unset($enqauthset['super']);
        //evidごとに書く
        $lineses = array();
        foreach ($enqauthset as $k => $va) {
            $lineses[] = $this->getEnqueteAuthLine($contents, $k, $va);
        }
        if ($this->mode == 'new') {
            $lineses[] = $this->getEnqueteAuthLine($contents, 'new', array());
        }
        $body = implode('', $lineses);

        return $this->design->getEnqueteAuthTable($body, $contents, FForm::submit('op:new:enquete', '新規追加'));
    }

    //$contents 使用可能なコンテンツ
    //$evid  この列のevid
    //$enqauthset アンケート一つ分（$evid分）の権限セット
    /**
     * @return string safety html
     */
    public function getEnqueteAuthLine($contents, $evid, $enqauthset)
    {
        $pages = array();
        foreach ($enqauthset as $v) {
            $pages[$v['page']] = 1;
        }

        $lines = array();

        foreach ($contents as $v) {
            $value = $this->isEnqueteEditLine($evid)?
                $this->getAuthInput($pages, $v):
                $this->getAuthText($pages, $v);
            $lines[] = $this->design->getEnqueteAuthTd($value);
        }
        if ($this->isEnqueteEditLine($evid)) {
            $shSubmit = FForm::submit('op:submit', '更新');
            $shHead = $this->getEnqueteSelect($evid);
        } else {
            $shSubmit = FForm::submit('op:edit:enquete:'.$evid, '編集');
            $shHead =  html_escape($this->events[$evid]['name']);
        }

        return $this->design->getEnqueteAuthLine($shHead, implode('', $lines), $shSubmit);

    }

    public function isEnqueteEditLine($evid)
    {
        return (($this->mode == 'edit' || $this->mode == 'new') && $this->editCategory =='enquete' && $this->editEvid == $evid);
    }

    /**
     * @return string safety html
     */
    public function getEnqueteSelect($evid)
    {
        $values = array();
        foreach ($this->events as $v) {
            $values[$v['evid']] = mb_strimwidth($v['name'],0,50);
        }
        if ($evid === 'new') {
            return FForm::replaceSelected(FForm::select('event', $values), $evid);
        } else {
            return FForm::hidden('event', $evid).$values[$evid];
        }
    }

    //pagesは[page名]=>1の配列であること
    public function getAuthInput($pages, $groups)
    {
        //groups全てを含むチェックボックスを作成（暫定措置）
        $values = array();
        foreach ($groups as $vb) {
            if ($pages[$vb['page']]) {
                $bool = 'checked';
            }
            $values[] = $vb['page'];
        }
        $value = implode(',', $values);

        return FForm::checkbox('page[]', $value, '', $bool).FForm::hidden('show[]', $value);

    }

    //pagesは[page名]=>1の配列であること
    public function getAuthText($pages, $groups)
    {
        $i = 0;
        foreach ($groups as $vb) {
            if($pages[$vb['page']]) ++$i;
        };
        if ($i == 0) {
            return "×";
        } elseif ($i == count($groups)) {
            return "○";
        } else {
            return "△";
        }
    }

}

class Design
{
    /**
     * @return string safety html
     */
    public function getEnqueteAuthTd($shBody)
    {
        $show = '<td>'.$shBody.'</td>';

        return $show;
    }

    //tr部分を返す
    //shTdには安全が保証されたものを送ること
    /**
     * @return string safety html
     */
    public function getEnqueteAuthLine($shName, $shTd, $shSubmit)
    {
        $show =	'<tr><td>'.$shName.'</td>'.$shTd.'<td>'.$shSubmit.'</td></tr>';

        return $show;
    }

    public function getEnqueteAuthTable($shBody, $contents, $shSubmit)
    {
        //凡例を書く
        $lines = array();
        foreach ($contents as $k => $v) {
            $lines[] = '<td>'.html_escape($k).'</td>';
        }
        $head = implode('', $lines);

        $show = <<<__HTML__
<div>
<table border>
    <tr>
        <td>シート名</td>
        {$head}
        <td>編集</td>
    </tr>
{$shBody}
</table>
{$shSubmit}
</div>
__HTML__;

        return $show;
    }

    public function getCompleteHtml($body)
    {
        $self = getPHP_SELF()."?".getSID();
        $show = <<<__HTML__
<form action="{$self}" method="post">
{$body}
</form>
<br>○=アクセス可能です
<br>×=アクセス不可能です
<br>△=権限設定の変更などで機能のうち一部のみアクセス可能な状態です。更新すると直ります。
__HTML__;

        return $show;
    }
}

//2008/04/02 マスタ管理とメール配信予約も権限に追加

//一次キーはAuthEditのshowと対応
//$GLOBAL_AUTHSET_CONTENTS = array(
//	//アンケートごとに設定できる権限に含めるメニュー
//	'enquete'=>array(
//		'enquete' => 'all',
//		'mail' => array('メール配信予約'),
//		'master' => 'all',
//	)
//);

$columns = getAuthSetContents();

$editor =& new AuthEdit($columns, new Design());

$html = $editor->show();

$html = RD::getSubject("シート別権限設定").$html;

$html = RD::getTitle("").$html;
$html = RD::getBackBar("musr_list.php?op=back&".getSID()).$html;

$objHtml =& new ResearchAdminHtml("アンケート別権限設定");
echo $objHtml->getMainHtml($html);
exit;
