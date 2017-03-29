<?php
/**
 * AJAX用共通ファンクション
 * @package
 */
/******************************************************************************************************/

/** JSONライブラリ */
require_once 'JSON.php';

/**
 * JSON化したデータを返す
 * @param mixed $mixid 対象
 * @param string JSON
 */
function ajax_json($mixid)
{
    global $json_obj;
    if (!is_object($json_obj)) {
        $json_obj = new Services_JSON();
    }

    return $json_obj->encode($mixid);
}

/**
 * JSコードを出力
 * @param string $js
 */
function ajax_print($js)
{
    print $js;
    if (DEBUG) {
        global $GLOBAL_STRING_SQL;
        if(INTERNAL_ENCODE != DB_ENCODE)
            $sql = mb_convert_encoding($GLOBAL_STRING_SQL,INTERNAL_ENCODE,DB_ENCODE);
        else
            $sql = $GLOBAL_STRING_SQL;

        $js = addslashes($sql."\n".$js);
        $js = str_replace("\r\n","\n",$js);
        $js = str_replace("\r","\n",$js);
        $js = str_replace("\n",'\n',$js);
        $js ='alert("'.$js.'");';
        print $js;
    }
}
