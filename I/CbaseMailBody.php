<?php
require_once 'CbaseFCrypt.php';
require_once 'CbaseEncoding.php';
class MailBody
{
    public $bdy;
    public $usr;
    public $event;

    /**
     * コンストラクタ
     * @param string $body  本文
     * @param array  $user  ユーザ
     * @param array  $event イベント
     *
     * @author Cbase akama
     */
    public function MailBody($body, $user, $event)
    {
        $this->bdy = $body;
        $this->usr = $user;
        $this->event = $event;
    }

    /**
     * 語句の置き換え
     *
     * @author Cbase akama
     */
    public function ReplaceParts()
    {
        return preg_replace_callback("/%%%%([a-zA-Z0-9_]+)%%%%/", array (
            $this,
            "DoReplace"
        ), $this->bdy);
    }

    /**
     * 何かする
     * @param String $match マッチ文字列
     *
     * @author Cbase akama
     */
    public function DoReplace($match)
    {
        //pregのマッチ部分の取り出し
        $fn = $match[1];
        //フォーム名が存在するかどうかのチェック
        if ($fn == 'URL') {
            return LOGIN_URL;
        } elseif ($fn == 'URL_S') {
            return _360_getSloginURL($this->usr);
        } elseif ($fn == 'target_name_list') {
            if (is_void($this->usr['target_name_list'])) {
                $users = FDB::getAssoc("SELECT b.name,b.div1,b.div2,b.div3,b.class FROM ".T_USER_RELATION." a LEFT JOIN ".T_USER_MST." b ON a.uid_a = b.uid WHERE a.user_type <4 AND a.uid_b = ".FDB::escape($this->usr['uid']));
                if (is_good($users)) {
                    $res = array();
                    foreach ($users as $u) {
                        $res[] = getUserDiv($u).' '.$u['name'];
                    }
                    $this->usr['target_name_list'] = implode("\n", $res);
                } else {
                    $this->usr['target_name_list'] = " ";
                }
            }

            return $this->usr['target_name_list'];
        } elseif (!$this->usr[$fn]) {
            return '■■■■■■■';
            //return " ";
//         }
    //	elseif ($fn == "uid" || $fn == "pw") 多言語だとカタカナはおかしいので
    //	{
    //		return $this->usr[$fn] . " (" . abc2kana($this->usr[$fn]) . ")";
    //	} elseif ($fn == 'mflag') {
            return $this->usr[$fn] ? '対象者' : '非対象者';
        } elseif ($fn == 'sheet_type') {
            return replaceMessage(getSheetTypeNameById($this->usr[$fn]));
        } elseif ($fn == 'div1') {
            return getDiv1NameById($this->usr[$fn]);
        } elseif ($fn == 'div2') {
            return getDiv2NameById($this->usr[$fn]);
        } elseif ($fn == 'div3') {
            return getDiv3NameById($this->usr[$fn]);
        } elseif ($fn == 'pw') {
            if(TEST_MAIL == 1)/* テストなら生成せずに返す */

                return "パスワード";

            if (isReversiblePw()) {
                $pw = getDisplayPw($this->usr[$fn]);
            } else {
                $retry = 0;
                do {
                    $pw = get360RandomPw();
                    if(is_good($this->usr['muid']))
                        $result = FDB::update(T_MUSR, FDB::escapeArray(array('pw'=>getPwHash($pw), 'pwmisscount'=>0, 'pdate'=>null)), "WHERE muid = ".FDB::escape($this->usr['muid']));
                    else
                        $result = FDB::update(T_USER_MST, FDB::escapeArray(array('pw'=>getPwHash($pw), 'pwmisscount'=>0, 'pw_flag'=>0)), "WHERE serial_no = ".FDB::escape($this->usr['serial_no']));
                    $retry++;
                } while (is_false($result) && $retry < 3);
                $pw = (is_false($result))? "":$pw;
            }

            return $pw;
        } else {
            return $this->usr[$fn];
        }

    }
}
