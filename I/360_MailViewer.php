<?php
class MailLogViewer
{
    public $log;
    public $rsv;
    public $enable=false;
    public $sender;
    public $bodyTable;
    public $logedit;
    public $data;
    public function __construct($mail_from, $id="", $logedit=null)
    {
        $global_mail_cond_dao = getAllMailReceived ();
//		if ($mail_from && $id)
//		{
//			$this->data = $global_mail_cond_dao->getByFromAndId($mail_from, $id);
//		}
//		else {
            $this->data = $global_mail_cond_dao->getByFrom($mail_from);
//         }

        $this->bodyTable = new MailBodyTable($this->data);
        $this->enable = true;
    }

    public function toHtml()
    {
        return $this->getDesign();
    }

    public function onNoAddressError()
    {
//		outputAdminError('アドレスがありません');
    }
    public function onNotEnableError()
    {
        outputAdminError('有効な状態ではありません');
    }

    public function getBackUrl()
    {
        return '';
    }

    public function getBackButton()
    {
        $backpage = $this->getBackUrl();
        $sid = getSID();
        $DIR_IMG = DIR_IMG;

        return <<<__HTML__
<a href="{$backpage}">
<img src="{$DIR_IMG}back.gif" width="61" height="12" border=0></a>
__HTML__;
    }


    public function getDesign()
    {
        $data = implode("", $this->bodyTable->getDesign($this->data));

        return $this->getBody($data);
    }



    public function getBody($table)
    {
        $back = $this->getBackButton ();

        return /*getSeparateDesign(*/<<<__HTML__
        <div id="main-left">
        <div class="mail-view">

          {$edit}
{$back}
{$table}
{$res}
{$sub}
</div>
</div>
__HTML__
/*,$link)*/;


    }
}

class IdMailLogViewer extends MailLogViewer
{
    public $uid;
    public $uidname;

    public function __construct($id, $idname, $uid, $logedit=null)
    {
        parent::__construct ($id, $logedit);
        $this->uid = $uid;
        $this->uidname = $idname;
    }


    public function getIdParam()
    {
        return array($this->uid, $this->uidname);
    }
}


class MailBodyTable
{
    public function __construct($log_row)
    {

    }

    public $sender;
    public $receiver;
    public $rollinfo;

    public function getDesignParam($aryData)
    {
        $res = array();
        foreach ($aryData as $data) {
        //表示内容
        $res[] = array(
            'id' => $data['mail_received_id'],
            '送信日時' => $data['rdate'],
            '送り先' => $data['mail_to'],//($to? $to.'<br />': '').$receiver,//$log['email'],
            '差出人' => $data['mail_from'],//($from? $from.'<br />': '').$sender,//$log['sender_id'],
            '件名' => $data['title'],
//			'cc' => $cc,
            '送信内容' => "<pre>".$data['body']."</pre>",
//			'添付ファイル' => $log['mail_attach_ids'],
        );
        }

        return $res;
    }

    public function getDesign($aryData)
    {
        $aryData = $this->getDesignParam($aryData);
        $dir_css = DIR_CSS;
//		$table[] = <<<__HTML__
//<link rel="stylesheet" type="text/css" href="{$dir_css}mail_style.css">
//__HTML__;

        foreach ($aryData as $data) {
            $head = $this->getHeaderContentsDesign($data);
            $id = $data['id'];
            $table[] = <<<__HTML__
<link rel="stylesheet" type="text/css" href="{$dir_css}mail_style.css">
<a name="mail-rid-{$id}">
<div id="mail-rid-{$id}">
<div class="mail-{$iro}fixed">
    <h2>{$data['件名']}</h2>
{$head}
    <div class="mail-text">
{$data['送信内容']}
    </div>
</div>
</div>
</a>
__HTML__;
        }

        return $table;
    }

    public function getHeaderContentsDesign($data)
    {
        if ($data['cc']) {
            $cc = <<<__HTML__
    <li><table><tr><td valign="top"><strong>Cc:</strong></td><td>{$data['cc']}</td></tr></table></li>
__HTML__;
        }
        $img = DIR_MNG_IMG;

        return <<<__HTML__
<ul class="mail-meta">
    <li><table><tr><td valign="top"><strong>From:</strong></td><td>{$data['差出人']}</td></tr></table></li>
    <li><table><tr><td valign="top"><strong>To:</strong></td><td>{$data['送り先']}</td></tr></table></li>
{$cc}
    <li><table><tr><td valign="top"><strong>日付:</strong></td><td>{$data['送信日時']}</td></tr></table></li>
</ul>
__HTML__;
    }
}

class ParentMailBodyTable extends MailBodyTable
{
    public function getDesign($rsv, $log)
    {
        //urlの確定
        $pp = $_GET;
        $pp['id'] = $this->log_row['mail_log_id'];
        $prm = array();
        foreach ($pp as $k => $v) {
            $prm[] = escapeHtml($k).'='.escapeHtml($v);
        }
        $prm = implode('&',$prm);

        $data = $this->getDesignParam($rsv, $log);

        $head = $this->getHeaderContentsDesign($data);
        $phpself = CbaseCommon::getPhpSelf();
        $table = <<<__HTML__


<div style="font-size:150%;font-weight:bold;padding:5px 0px;">
<a href="{$phpself}?{$prm}">
{$data['件名']}
</a>
</div>
{$head}
<div style="line-height:150%;padding:10px;text-align:left;">
{$data['送信内容']}
</div>
__HTML__;

        return $table;
    }
}

class MailLinkAllUserCond extends CondTableAdapter
{
    public $uid;
    public $idname;
    public function __construct($mid, $id, $idName='uid')
    {
        $this->id = $id;
        $this->idname=$idName;
        $this->mail_log = $mid;
        $this->mid = $this->mail_log['mail_log_id'];
    }

    public function getColumns()
    {
        return array(

            "name" => "氏名",
            "email" => "email",
        );
    }

    public function getColumnForms($def, $key)
    {
        switch ($key) {
            default:
                    return FForm::text($key, $def[$key]);
        }
    }

    public function getColumnValues($post, $key)
    {
        switch ($key) {
            default:
                return $post[$key];
        }
    }

    public function setHiddenValue($array)
    {
        $array = parent::setHiddenValue($array);
        $array['id'] = (int) $this->mid;
        $array['link'] = 'other';

        return getMailViewerParam($array);
    }
}

class MailLinkAdminCond extends MailLinkAllUserCond
{
    public function setHiddenValue($array)
    {
        $array = parent::setHiddenValue($array);
        $array['link'] = 'other_admin';

        return $array;
    }
}

function getMailDicisionDirectHash($cid, $mailtype, $sender_id, $group_id, $url, $agent_id='')
{
    return sha1($cid.SYSTEM_RANDOM_STRING.$mailtype.$sender_id.$group_id.$url.$agent_id);
}

/**
 * arrayにmailviewerが使うGETパラメータを足す
 */
function getMailViewerParam($array)
{
        if (!$array['back']) {
            $back = $_GET['back']?$_GET['back']: $_POST['back'];
            if($back) $array['back'] = $back;
        }

        return $array;
}
