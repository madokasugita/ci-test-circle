<?php namespace SmartReview\Admin\EnqUpdateAll;

class ThisImportDesign extends \ImportDesign360
{
    public function getFirstViewMessage()
    {
        global $_360_user_type;
        $action = basename($_SERVER["PHP_SELF"]).'?csvdownload=1&'.getSID();
        $button = PAGE_TITLE.'をダウンロード';
        $sheet_type = getHtmlSheetTypeCheck();
        // 評価者タイプチェックボックス
        foreach ($_360_user_type as $k => $v) {
            if($k>INPUTER_COUNT)
                break;
            $options1 .= '<input type="checkbox" name="user_type[]" value="'.$k.'" checked>'.$v;
        }

        return
<<<__HTML__
    <form action="{$action}" method="POST">
        <div class="sub_title" style="width:750px;">エクスポート</div>
        <table class="cont" border="0" cellspacing="1" cellpadding="3" bgcolor="#000000" style="width:auto;margin:10px">
            <tr>
                <th bgcolor="#eeeeee" align="right" width="150px">シート</th>
                <td bgcolor="#ffffff" width="600px">{$sheet_type}</td>
            </tr>
            <tr>
                <th bgcolor="#eeeeee" align="right">評価者タイプ</th>
                <td bgcolor="#ffffff">{$options1}</td>
            </tr>
            <tr>
                <th bgcolor="#eeeeee" align="right"></th>
                <td bgcolor="#ffffff"><input type="submit" name="next" value="{$button}"></td>
            </tr>
        </table>
    </form>
__HTML__;

    }

    /**
     * フォームを返す
     * 既定値はfile, next,back,submit,error_end
     */
    public function getFormCallback($name, $default=null)
    {
        $model = new \ImportModel360();
        switch ($name) {
            case 'file':
                return \FForm::file($name,null,null);
            case 'next':
                return $this->getNextButton($name);
            case 'back':
                return $this->getBackButton($name);
            case 'submit':
                return $this->getSubmitButton($name);
            case 'udate':
                return $model->getUdateImportFile ($model->getExecFile());
            case 'last_file':
                return $model->getLastFileName ($model->getExecFile());
            case 'error_end':
                return \FForm::radio($name, 0, 'エラー行を無視して続行').
                    '<br>'.\FForm::radio($name, 1, '処理を中断する', 'checked');
            default:
                if(isset($default))
                    return $this->getHidden($name, $default);
                break;
        }
    }

    public function getFirstViewTable($line, $forms)
    {
        return <<<__HTML__
<div class="sub_title" style="width:750px;">インポート</div>
<table class="cont"border="0" cellspacing="1" cellpadding="3" bgcolor="#000000"style="width:auto;margin:10px">
{$line}
</table>
__HTML__;
    }

    public function getFirstViewLine($subject, $form)
    {
        return <<<__HTML__
<tr>
  <th bgcolor="#eeeeee" align="right" width="150px">{$subject}</th>
  <td bgcolor="#ffffff" width="600px">{$form}</td>
</tr>
__HTML__;
    }

}
