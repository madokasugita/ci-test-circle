<?php namespace SmartReview\Admin\EnqUpdateAll;
require_once (DIR_LIB . '360_Importer.php');
use FDB;
class ThisImportModel extends \ImportModel360
{
    public function importLine($line_no, $data)
    {
        $data = array_combine($this->header,$data);
        $data = $this->getSavedata($data);
        $seid = $data['now_seid'];
        unset($data['now_seid']);
        FDB::update(T_EVENT_SUB,FDB::escapeArray($data),'where seid = '.FDB::escape($seid));

        return '';
    }

    public function getSavedata($data)
    {
        if(is_void($data['category1'])) $data['category1'] = 0;
        if(is_void($data['category2'])) $data['category2'] = 0;
        if(is_void($data['num_ext'])) $data['num_ext'] = 0;

        return $data;
    }

    /**
     * @return array エラーチェックを行い、エラーがあればエラー文言の配列を返す。
     */
    public function getErrors($line_no, $data)
    {
        $error = array();
        $num = $line_no-1;
        foreach (array_combine($this->header,$data) as $k=>$v) {
            switch ($k) {
                //case 'category1':
                //case 'category2':
            case 'num_ext':
                if (ctype_digit($v)) {
                    break;
                } elseif (is_void($v)) {
                    break;
                } else {
                    $error[] = $num."行目：".$k."は数値で入力してください";
                }
                break;
            default:
                break;
            }
        }

        return $error;
    }

    /**
     * インポート処理直前、トランザクション処理開始後に呼び出されるメソッド
     */
    public function onBeforeImport()
    {
    }

    public function onAfterImport()
    {
        if (!$this->upsertImportFile($_SESSION["muid"], $this->getExecFile())) {
            // エラー発生時
        }
    }
    public function getFormNames()
    {
        return array(
            'last_file' => '前回取り込みファイル',
            'udate' => '更新日時',
            'error_end' => 'エラー検出時の動作',
        );
    }

}
