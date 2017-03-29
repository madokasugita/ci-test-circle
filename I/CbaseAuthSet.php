<?php
define('T_AUTH_SET', 'auth_set');
include_once($path.'CbaseFDBClass.php');
include_once($path.'CbaseDAO.php');
class AuthSetDAO extends DAO
{
    public function constructor()
    {
        parent::constructor();
        $this->table = T_AUTH_SET;
        $this->columns = array(
            'evid' => '対象evid(nullですべて)',
            'muid' => '権限所持者',
            'page' => '対象ページ',
            'auth_set_id' => 'id'
        );
    }

    public function getByMuid($muid, $other='')
    {
        return $this->getByCond('WHERE muid='.FDB::escape($muid).' '.$other);
    }

    public function getByEvid($evid, $other='')
    {
        $where = (is_null($evid))? 'WHERE evid IS NULL': 'evid='.FDB::escape($evid);

        return $this->getByCond($where.' '.$other);
    }

    public function getByPageName($name, $other='')
    {
        return $this->getByCond('WHERE page='.FDB::escape($name).' '.$other);
    }

    public function save($data)
    {
        if ($data['auth_set_id']) {
            return $this->update($data, 'WHERE auth_set_id = '.FDB::escape($data['auth_set_id']));
        } else {
            return $this->insert($data);
        }

    }

    public function deleteById($id)
    {
        return $this->delete('WHERE auth_set_id = '.FDB::escape($id));
    }

    /**
     * AuthContentsから保存・削除を行う
     * @param  array $okValue  登録するページの値。カンマ区切り文字列の配列
     * @param  array $allValue 編集対象ページ。ここにあってokValueにないと削除される。カンマ区切り文字列の配列
     * @param  int   $muid     対象ユーザのID
     * @param  int   $evid     省略の場合nullになる。対象のイベント。
     * @return bool  失敗すればfalse。トランザクションのため失敗の場合はDBに影響なし
     */
    public function saveFromAuthContents($okValue, $allValue, $muid, $evid=null)
    {
        $newPages = array();
        foreach ($okValue as $v) {
            foreach (explode(',', $v) as $va) {
                $newPages[$va] = 1;
            }
        }
        //postされたものを全部もらう
        $inputValues = array();
        foreach ($allValue as $v) {
            foreach (explode(',', $v) as $va) {
                $inputValues[$va] = 1;
            }
        }

        //eventがあればうけとる
        FDB::begin();
        $authset = $this->getByMuid($muid, 'ORDER BY evid');
        foreach ($authset as $k => $v) {
            //evid, page完全一致があれば除外
            if ($evid == $v['evid'] && $newPages[$v['page']]) {
                $newPages[$v['page']] = 2;
            } else {
                //編集対象なのに完全一致しないものは削除
                if ($evid == $v['evid'] && $inputValues[$v['page']]) {
                    if (!$this->deleteById($v['auth_set_id'])) {
                        FDB::rollback();

                        return false;
                    }
                }
            }
        }

        foreach ($newPages as $k => $v) {
            //insertのみ
            if ($v == 1) {
                $save = array(
                    'evid' => $evid
                    ,'muid' => $muid
                    ,'page' => $k
                );
                if (!$this->save($save)) {
                    FDB::rollback();

                    return false;
                }
            }
        }
        FDB::commit();

        return true;
    }
}

/*
 * Authデータ汎用
 */
class AuthChecker
{
    public $muid;
    function &fromMuid ($muid)
    {
        $obj =& AuthChecker::fromArray(AuthSet::getByMuid($muid));
        $obj->muid = $muid;

        return $obj;
    }

    //ログインデータから作成
    function &fromMusr ($musr)
    {
        $obj =& AuthChecker::fromArray($musr['auth_set']);
        $obj->muid = $musr['muid'];

        return $obj;
    }

    /**
     * 読み込み済みの配列データから作成
     */
    function &fromArray($array)
    {
        $obj =& new AuthChecker();
        $obj->setData($array);

        return $obj;
    }

    public $data;
    public function getData()
    {
        return $this->data;
    }
    public function setData($array)
    {
        $res = array();
        foreach ($array as $v) {
            $res[$v['page']][] = $v;
        }
        $this->data = $res;
    }

    public function isAuth($pageName)
    {
        if ($this->data[$pageName]) {
            return true;
        }
/*		else {
            //部分一致も一応やる
            foreach ($this->data as $v) {
                if(strstr($v['page'], $pageName)) return true;
            }
        }*/

        return false;
    }

    public function isAuthByManage($pageName, $evid = null)
    {
//		var_dump($evid);
        if ($evid) {
            return ($this->isAuthWithEvent($pageName, array('evid'=>$evid)));
        } else {
            return ($this->isAuth($pageName));
        }
    }

    public function isAuthWithEvent($pageName, $event)
    {
        //自分の作成したアンケートであれば全権限を持つ（暫定仕様）
        if(!is_null($this->muid) && $event['muid'] == $this->muid) return true;
        //権限で分岐
        if ($this->data[$pageName]) {
            foreach ($this->data[$pageName] as $v) {
                if ($event['evid'] == $v['evid'] || is_null($v['evid'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 指定のページの権限を持つeventのみを取得
     * $enqueteはGet_Enquete結果によること
     */
    public function pickAuthFromEnquete($pageName, $enquete)
    {
        if ($this->isAuthWithEvent($pageName, $enquete[-1])) {
            return $enquete;
        }

        return array();
    }

    public function pickAuthFromEvents($pageName, $events)
    {
        $res = array();
        foreach ($events as $v) {
            if ($this->isAuthWithEvent($pageName, $v)) {
                $res[] = $v;
            }
        }

        return $res;
    }
}

/**
 * crm_defineのメニュー設定値からAuthSet処理用のコンテンツを得る
 */
function getMenuContentsForAuthSet($menu=null)
{
    if (is_null($menu)) {
        global $arMenu;
        $menu = $arMenu;
    }
    $contents = array();
    foreach ($menu as $v) {
        foreach (explode(',', $v[1]) as $vPage) {
            //将来的には、各ページごとに名前を管理できるとよい
            $contents[$v[0]][$v[2]][] = array(
                'name' => $v[2],
                'page' => $vPage
            );
        }
    }

    return $contents;
}

function getAuthSetContents($list=null, $menu=null)
{
    if (is_null($list)) {
        global $GLOBAL_AUTHSET_CONTENTS;
        $list = $GLOBAL_AUTHSET_CONTENTS;
    }

    $contents = getMenuContentsForAuthSet ($menu);
    $columns = array();
    foreach ($list as $kDiv => $v) {
        foreach ($v as $kPage => $vv) {
            foreach ($contents[$kPage] as $kName => $vvv) {
                if ($vv !== 'all' && !in_array($kName, $vv)) {
                    continue;
                }
                $columns[$kDiv][$kName] = $vvv;
            }
        }
    }

    return $columns;
}
