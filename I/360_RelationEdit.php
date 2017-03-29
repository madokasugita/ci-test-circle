<?php
class RelationEdit
{
    public $user, $edit, $self, $edit_url;

    public function __construct($user, $edit = false)
    {
        global $_360_user_type;

        $this->user = $user;
        $this->edit = $edit;
        $this->self = PHP_SELF ;
        $this->edit_url = "360_user_relation_view_u_wrapper.php?".getSID();

        $T_USER_MST = T_USER_MST;
        $T_USER_RELATION = T_USER_RELATION;
        $UID_ESCAPED = FDB :: escape($user['uid']);
        $tmp = FDB :: getAssoc("select * from {$T_USER_RELATION} a left join {$T_USER_MST} b on a.uid_b = b.uid where a.uid_a = {$UID_ESCAPED} order by div1,div2,div3,uid;");
        $users = array ();
        foreach ($tmp as $user) {
            if (!$user['user_type'] || $user['user_type']>INPUTER_COUNT)
                continue;
            $users[$user['add_type']][$user['user_type']][] = $user;
        }
        $this->users = $users;
        foreach ($_360_user_type as $k => $v) {
            $GLOBALS['RELATION_EDIT_COUNT'][$k] += count($users[0][$k]) + count($users[1][$k]);
        }
    }

    public function setSelf($url)
    {
        $this->self = $url;
    }

    public function setEditUrl($url)
    {
        $this->edit_url = $url;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function getHtmlUserInfo()
    {
        $user = $this->user;
        $user['name_'] = html_escape($user['name_']);
        $user['name'] = html_escape($user['name']);
        $user['uid'] = html_escape($user['uid']);
        $user['email'] = html_escape($user['email']);
        $user['div1'] = getDiv1NameById($user['div1'], getMyLanguage());
        $user['div2'] = getDiv2NameById($user['div2'], getMyLanguage());
        $user['div3'] = getDiv3NameById($user['div3'], getMyLanguage());
        $user['sheet_type'] = getSheetTypeNameById($user['sheet_type']);
        if($user['select_status'])
            $user['select_status'] = '<span style="color:blue;font-weight:bold">'.getSelectStatusName($user['select_status']).'</span>';
        else
            $user['select_status'] = '<span style="color:red;font-weight:bold">'.getSelectStatusName($user['select_status']).'</span>';

        return<<<HTML
<div style="margin-bottom:20px;background-color:#f0f0f0;padding:10px">
[####target_Info####]
<table class="admintable2" style="background-color:#ffffff;">
<tr class="admintable_header">
<td>####selection_status####</td>
<td>####sheet_type####</td>
<td>####mypage_fb_uid####</td>
<td>####mypage_fb_name####</td>
<td>####mypage_name_####</td>
<td>####div_name_1####</td>
<td>####div_name_2####</td>
<td>####div_name_3####</td>

<td>####mail####</td>
</tr>
<tr>
<td style="text-align:center">{$user['select_status']}</td>
<td style="text-align:center">{$user['sheet_type']}</td>
<td style="text-align:center">{$user['uid']}</td>
<td>{$user['name']}</td>
<td>{$user['name_']}</td>
<td>{$user['div1']}</td>
<td>{$user['div2']}</td>
<td>{$user['div3']}</td>

<td>{$user['email']}</td>
</tr>
</table>
</div>

HTML;
    }

    public function getHtmlRelationInfo()
    {
        global $_360_user_type, $_360_language_org; ;

        $target = $user = $this->user;
        $target_hash = getHash360($target['serial_no']);
        $users = $this->users[0];

        $count ="";
        foreach ($_360_user_type as $k => $v) {
            if (!$k || $k>INPUTER_COUNT)
                continue;
            $count.=$v." : ".count($users[$k])."####person####　";
        }

        $buttonTD = ($this->edit)? '<td style="width:120px"></td>':'';

        $html =<<<HTML
    <div>{$count}</div>
    <table class="admintable2" style="background-color:#ffffff;">
    <tr class="admintable_header">
    <td style="width:100px">####Respondent####<br>####Type####</td>
    <td>####mypage_fb_name####</td>
    <td>####mypage_name_####</td>
    <td style="width:100px">####mail####</td>
    <td>####language####</td>
    {$buttonTD}
    </tr>
HTML;

        $disabled = ($target['select_status']>0)? ' disabled':'';
        foreach ($_360_user_type as $k => $v) {
            if (!$k || $k>INPUTER_COUNT)
                continue;
            if ($k > 1) {
                $html .=<<<HTML
    <tr>
    <td style="height:5px;background-color:#444444" colspan="9"></td>
    </tr>
HTML;
            }

            $count = count($users[$k]);
            if (!$count) {
                $colspan = ($this->edit)? 8:7;
                $html .=<<<HTML

    <tr>
    <td style="text-align:center;font-weight:bold;">{$v}</td>
    <td style="text-align:center" colspan="{$colspan}"> ####NO_Setting#### </td>
    </tr>
HTML;
                continue;
            }
            $user_type =<<<HTML
    <td rowspan="{$count}" style="text-align:center;font-weight:bold;">{$v}</td>
HTML;
            foreach ($users[$k] as $user) {
                $user['name'] = html_escape($user['name']);
                $user['name_'] = html_escape($user['name_']);
                $user['uid'] = html_escape($user['uid']);
                $user['email'] = html_escape($user['email']);
                $user['div'] = getUserDiv($user);
                $user['div1'] = getDiv1NameById($user['div1']);
                $user['div2'] = getDiv2NameById($user['div2']);
                $user['div3'] = getDiv3NameById($user['div3']);
                $user['lang_type'] = $_360_language_org[$user['lang_type']];
                $hash = getHash360($user['serial_no']);

                if($this->edit)
                    $button = <<<__HTML__
    <td style="text-align:center;width:120px">
    <form action="{$this->self}&serial_no={$target['serial_no']}&hash={$target_hash}" method="post" style="display:inline;margin:0px;">
        <input type="submit" onClick='return confirm("####remove_user_alert####\\n\\n{$user['email']}")' value="####delete####"{$disabled}>
        <input type="hidden" value="delete_relation" name="mode">
        <input type="hidden" value="{$user['serial_no']}" name="respondent_serial_no">
        <input type="hidden" value="{$hash}" name="respondent_hash">
    </form>
    </td>
__HTML__;
                $html .=<<<HTML
    <tr>
    {$user_type}
    <td>{$user['name']}</td>
    <td>{$user['name_']}</td>
    <td>{$user['email']}</td>
    <td>{$user['lang_type']}</td>

<!--
    <td align="center">{$user['class']}</td>
    <td>{$user['div']}</td>
-->

    {$button}
    </tr>
HTML;
                $user_type = '';
            }

        }
        $html.="</table>";

        return $html;
    }

    public function getHtmlRelationInfo2()
    {
        global $_360_user_type, $_360_language_org;

        $target = $user = $this->user;
        $target_hash = getHash360($target['serial_no']);
        $users = $this->users[1];

        $count ="";
        foreach ($_360_user_type as $k => $v) {
            if (!$k || $k>INPUTER_COUNT)
                continue;
            $count.=$v." : ".count($users[$k])."####person####　";
        }

        $html =<<<HTML
    <div>{$count}</div>
    <table class="admintable2" style="background-color:#ffffff;">
    <tr class="admintable_header">
    <td style="width:100px">####respondent_type####</td>
    <td style="width:300px">####name####</td>

    <td>####mail####</td>
    <td style="width:150px">####language####</td>

<!--
    <td style="width:100px">####Post####</td>
    <td>####div####</td>
-->

    <td style="width:120px"></td>
    </tr>
HTML;

        $disabled = ($target['select_status']>0)? ' disabled':'';
        foreach ($_360_user_type as $k => $v) {
            if (!$k || $k>INPUTER_COUNT)
                continue;

            if ($k > 1) {
                $html .=<<<HTML
    <tr>
    <td style="height:5px;background-color:#444444" colspan="9"></td>
    </tr>
HTML;
            }

            $count = count($users[$k]);
            if (!$count) {
                $html .=<<<HTML
    <tr>
    <td style="text-align:center;font-weight:bold;">{$v}</td>
    <td style="text-align:center" colspan="7"> ####NO_Setting#### </td>
    </tr>
HTML;
                continue;
            }
            $user_type =<<<HTML
    <td rowspan="{$count}" style="text-align:center;font-weight:bold;">{$v}</td>
HTML;
            foreach ($users[$k] as $user) {
                $user['name'] = getUserName_($user, "");
                $user['uid'] = html_escape($user['uid']);
                $user['email'] = html_escape($user['email']);
                $user['div'] = getUserDiv($user);
                $user['div1'] = getDiv1NameById($user['div1']);
                $user['div2'] = getDiv2NameById($user['div2']);
                $user['div3'] = getDiv3NameById($user['div3']);
                $user['lang_type'] = $_360_language_org[$user['lang_type']];
                $hash = getHash360($user['serial_no']);

                $pre_disabled = $disabled;

                $button = <<<__HTML__
<form action="{$this->edit_url}&target_serial_no={$target['serial_no']}&hash={$target_hash}&serial_no={$target['serial_no']}" method="post" style="display:inline;margin:0px;">
    <input type="submit" value="####edit####"{$disabled}>
    <input type="hidden" value="edit" name="mode">
    <input type="hidden" value="{$user['serial_no']}" name="respondent_serial_no">
    <input type="hidden" value="{$hash}" name="respondent_hash">
    <input type="hidden" value="{$k}" name="respondent_type">
</form>

<form action="{$this->self}&serial_no={$target['serial_no']}&hash={$target_hash}" method="post" style="display:inline;margin:0px;">
    <input type="submit" onClick='return confirm("####remove_user_alert####\\n\\n{$user['email']}")' value="####delete####"{$disabled}>
    <input type="hidden" value="delete" name="mode">
    <input type="hidden" value="{$user['serial_no']}" name="respondent_serial_no">
    <input type="hidden" value="{$hash}" name="respondent_hash">
</form>
__HTML__;
                $html .=<<<HTML
<tr>
{$user_type}
<td>{$user['name']}</td>
<td>{$user['email']}</td>
<td>{$user['lang_type']}</td>

<!--
<td align="center">{$user['class']}</td>
<td>{$user['div']}</td>
-->

<td style="text-align:center;width:120px">{$button}</td>
</tr>
HTML;
                $user_type = '';
                $disabled = $pre_disabled;
            }

        }
        $html.="</table>";

        return $html;
    }

    public function getHtmlAddButton1()
    {
        $user = $this->user;
        $serial_no = $user['serial_no'];

        if($user['select_status'])
            $disabled = ' disabled';
        else
            $disabled = '';

        $hash = getHash360($serial_no);
        $SID = getSID();

        return<<<HTML
<form action="360_user_relation_edit_u.php?{$SID}&target_serial_no={$serial_no}&hash={$hash}" method="post" style="display:inline;margin:0px;">
<input type="hidden" name="serial_no" value="{$serial_no}"><input type="hidden" name="hash" value="{$hash}"><input type="hidden" name="mode" value="edit">
<input type="submit" value="####user_relation_view_button_1####" class="btn large"{$disabled}>
</form>
HTML;
    }

    public function getHtmlAddButton2()
    {
        $user = $this->user;
        $serial_no = $user['serial_no'];

        if($user['select_status'])
            $disabled = ' disabled';
        else
            $disabled = '';

        $hash = getHash360($serial_no);
        $SID = getSID();

        return<<<HTML
<form action="360_user_respondent_new_u.php?{$SID}&target_serial_no={$serial_no}&hash={$hash}&mode=new" method="post" style="display:inline;margin:0px;">
<input type="submit" value="####user_relation_view_button_3####" class="btn large"{$disabled}>
</form>
HTML;
        }

    public function getHtmlRelationArea1()
    {
        $relation_info2 = $this->getHtmlRelationInfo();
        $add_button2 = $this->getHtmlAddButton1();

        return <<<__HTML__
<div style="margin-bottom:20px;background-color:#f0f0f0;padding:10px">
    <div style="font-weight:bold;font-size:17px;background-color:#ffffff;margin:10px 0px;padding:5px;border-bottom:solid 3px #333399">####user_relation_view_1####</div>
    <div style="margin:auto;width:800px;text-align:left">
    <pre>####user_relation_view_message_1####</pre>
    </div>

    {$add_button2}
    {$relation_info2}

</div>
__HTML__;
    }

    public function getHtmlRelationArea2()
    {
        $relation_info1 = $this->getHtmlRelationInfo2();
        $add_button1 = $this->getHtmlAddButton2();

        return <<<__HTML__
<div style="margin-bottom:20px;background-color:#f0f0f0;padding:10px">
    <div style="font-weight:bold;font-size:17px;background-color:#ffffff;margin:10px 0px;padding:5px;border-bottom:solid 3px #333399">####user_relation_view_3####</div>
    <div style="margin:auto;width:800px;text-align:left">
    <pre>####user_relation_view_message_3####</pre>
    </div>

    {$add_button1}
    {$relation_info1}

</div>
__HTML__;
    }

    public function getHtmlRelationView()
    {
        $relation_area = "";
        switch (RESPONDENT_MODE) {
            case 2:
                $relation_area .= $this->getHtmlRelationArea2();
                break;
            case 3:
                $relation_area .= $this->getHtmlRelationArea1();
                $relation_area .= $this->getHtmlRelationArea2();
                break;
            default:
                $relation_area .= $this->getHtmlRelationArea1();
                break;
        }

        return $relation_area;
    }

}
