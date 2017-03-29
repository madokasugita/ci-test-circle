<?php
define('DIR_ROOT', '../');
require_once (DIR_ROOT . 'crm_define.php');
require_once (DIR_LIB . 'CbaseFDB.php');
require_once (DIR_LIB . 'CbaseFManage.php');
require_once (DIR_LIB . 'CbaseEncoding.php');
session_start();
Check_AuthMng('360_message_view.php');

$msgid = $_POST["msgid"];
$mode = $_POST["mode"];

if ($mode=="read") {
    $TABLE = T_MESSAGE;
    $where = "WHERE msgid =".FDB::escape($msgid);
    $value = FDB :: getAssoc("SELECT * FROM {$TABLE} {$where}");
    $json = array(
        "msgid"=>$value[0]["msgid"],
        "mkey" =>$value[0]["key"],
        "place1"=>$value[0]["place1"],
        "place2"=>$value[0]["place2"],
        "type"=>$value[0]["type"],
        "name"=>$value[0]["name"],
        "ja"=>$value[0]["body_0"],
        "en"=>$value[0]["body_1"]
    );
    header('Content-type: application/json');
    echo json_encode($json);
} elseif ($mode=="write") {
    $data = array("msgid"=>$msgid,
                    "mkey"=>$_POST["key"],
                    "place1"=>$_POST["place1"],
                    "place2"=>$_POST["place2"],
                    "type"=>$_POST["type"],
                    "name"=>$_POST["name"],
                    "body_0"=>$_POST["body_0"],
                    "body_1"=>$_POST["body_1"]);
    update($data);

} elseif ($mode=="delete") {

}

function setupColumns()
    {
        global $_360_language;
        $array = array();
        $array['msgid'] = "";
        $array['mkey'] = "キー";

        $array['place1'] = "場所1";
        $array['place2'] = "場所2";
        $array['type'] = "種類";
        $array['name'] = "名称";
        //$array['memo'] = "説明";
        foreach ($_360_language as $k => $v) {
            $array['body_'.$k] = "内容({$v})";
        }

        return $array;
    }
function update($data)
    {
        FDB::begin();
        $array = array();
        foreach (setupColumns() as $key => $val) {
            $array[$key] = FDB::escape($data[$key]);
        }
            echo "<pre>";
                print_r($array);
            echo "</pre>";
        $rs = FDB::update(T_MESSAGE,$array,'WHERE msgid = '.$array['msgid']);
        if (is_false($rs)) {
            FDB::rollback();

            return false;
        } else {
            clearMessageCache();
            FDB::commit();

            return true;
        }

    }
