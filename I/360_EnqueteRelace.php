<?php
$MSG['base'] =<<<HTML
<table class="smalltable">
<tr>
<td>
####enq_rate_5####
</td>

<td>
####enq_rate_4####
</td>

<td>
####enq_rate_3####
</td>

<td>
####enq_rate_2####
</td>

<td>
####enq_rate_1####
</td>
<td>
####enq_rate_Na####
</td>
</tr>
</table>
HTML;
$files = getMessage('files');
if ($files) {
    $files = '<div class="mp">####manualqa####</div><div class="bd">'.$files.'</div>';
}
$MSG['files'] =<<<HTML
<div class="infolink" style="width:890px">


</div>
HTML;


$MSG['JS'] =<<<HTML
<script>
function checkMainComment(obj)
{
    var txt = obj.value;
    var count = countLength(txt);
    if (count>400) {
        count = '<font color="red">'+count+'</font>';
    }
    document.getElementById('comment_length'+obj.name).innerHTML = count;
}


function checkMainComment_Onblur(obj)
{
    var txt = obj.value;
    var count = countLength(txt);
    if (count>400) {
        count = '<font color="red">'+count+'</font>';
    }
    document.getElementById('comment_length'+obj.name).innerHTML = count;
    if (countLength(txt) > 400) {
        alert("####enq_errror_message_count####");
        obj.focus();

        return;
    }
}




function getByte(text)
{
    count = 0;
    for (i=0; i<text.length; i++) {
        n = escape(text.charAt(i));
        if (n.length < 4) count++; else count+=2;
    }

    return count;
}

function countLength(txt)
{
    return txt.length;
}

function allReplace(text, sText, rText)
{
    while (true) {
        dummy = text;
        text = dummy.replace(sText, rText);
        if (text == dummy) {
            break;
        }
    }

    return text;
}
</script>
HTML;

$MSG['top'] =<<<HTML

HTML;
/********************************************************************************************************************************/
function replaceEnq($html)
{
    $html = preg_replace_callback("/%%%%MSG_ENQ_(.*?)%%%%/i", "ENQ_ReplaceParts", $html);
    $html = preg_replace("/%%%%print_link%%%%/i", "./print.php?".getSID(), $html);

    if($GLOBALS['enq_page']==2)
        $html = str_replace("%%%%alert1%%%%", "<div id=\"message6\">####enq_message6####</div>", $html);
    else
        $html = str_replace("%%%%alert1%%%%", "", $html);

    //確認画面はメニューに戻るリンクを消す
    if($GLOBALS['enq_page']==2)
        $html = ereg_replace('<div id="back">[^<]+</div>','', $html);


    prepareEnqMatrix();
    $html = preg_replace_callback("/####enqmatrix(.*?)_(.*?)####/i", "ENQ_RepalaceMatrix", $html);

    if (MODE_PRINT == 1)
        $html = replacePrintEnq($html);
    if (REOPENED_FLAG === 1 && $GLOBALS['Setting']->reopenTypeEqual(1)) {
        $html = replacePrintEnq($html);
        $html = replaceReopenEnq($html);
    }
    if (MODE_PRINT != 1)
        $html = replaceNomalEnq($html);

    return $html;
}

//通常回答時
function replaceNomalEnq($html)
{
    return $html;
}

//完了後に開いたとき
function replaceReopenEnq($html)
{
    $reopen =<<<HTML
<div id="already_f">####enq_already_finished####</div>
HTML;
    $html = preg_replace('|<div id="maincontainer">|m', '<div id="maincontainer">' . $reopen, $html);

    return $html;
}

//閲覧モード
function replacePrintEnq($html)
{
    $html = preg_replace('|<input([^>]*?)submit([^>]*?)>|m', '', $html); //送信ボタンは消す
    //$html = preg_replace('|height:([^>]*?)px;overflow-x:hidden;overflow-y:scroll;|m','',$html);//スクロールしない
    $html = preg_replace('|<input([^>]*?)checkbox([^>]*?)>|m', '<input$1checkbox$2 disabled>', $html); //チェックボックスを変更不可に

    $html = preg_replace('|<input |m', '<input readonly ', $html); //送信ボタンは消す
    $html = preg_replace('|<input readonly readonly |m', '<input readonly ', $html); //送信ボタンは消す

    $html = preg_replace('|<!-- bottom -->.*?</body>|m', '</body>', $html); //フッタ削除
    $html = str_replace('<textarea', '<textarea readonly', $html); //テキストエリアは変更不可に
    $html = preg_replace('|<option[^s]+?>.*?<\/option>|m','',$html);	//selected の要素以外消す
    $html = str_replace('"></select>','"><option value="ng" selected>--</option></select>',$html);

    return $html;
}

function prepareEnqMatrix()
{

    global $enqmatrix;
    if(is_array($enqmatrix))

        return;
    $evid = getEvidByRid(ENQ_RID);

    $langage = $_COOKIE['lang360'] ? $_COOKIE['lang360'] : 0;

    if(isset($_SESSION['login']['lang_type']))
        $langage = $_SESSION['login']['lang_type'];

    if ($GLOBALS['Setting']->sheetModeCollect()) {
        if($evid%100 > 1)
            $evid = (round($evid/100)*100)+1;
    }

    if ($langage) {
        $file = DIR_ROOT."enqcsv/{$evid}_{$langage}.csv";
    } else {
        $file = DIR_ROOT."enqcsv/{$evid}.csv";
    }

    if (!is_file($file.'.ctmp')) {
        $tmp = file_get_contents($file);
        $mb = mb_detect_encoding($tmp);
        if($mb=='SJIS')
            $mb = 'sjis-win';
        if($mb)
            file_put_contents($file.'.ctmp',mb_convert_encoding($tmp, "UTF-8",$mb));
        else
            file_put_contents($file.'.ctmp',mb_convert_encoding($tmp, "UTF-8","Unicode"));
    }
    $file = $file.'.ctmp';
    $fp = fopen($file, 'r');

    if (!$fp) {
        print "file open error!(189)";
        exit;
    }
    $line =	fgets($fp);
    $line .=fgets($fp);
    $line .=fgets($fp);
    rewind($fp);
    if(count(explode("\t",$line)) < count(explode(",",$line)))
        $delimiter = ",";
    else
        $delimiter = "\t";

    $row = 0;
    while (!feof($fp) && ($data = CbaseFgetcsv($fp, $delimiter, "\"", "UTF-8"))) {

        $col = 0;
        foreach ($data as $d) {
            $enqmatrix[$row][$col] = nl2br($d);
            $col++;
        }
        $row++;
    }
    $enqmatrix[0][0] = str_replace('"','',$enqmatrix[0][0]);
    fclose($fp);
}
function ENQ_RepalaceMatrix($match)
{
    global $enqmatrix;

/*
    if ($match[2] == 0) {
        $enqmatrix[$match[1]][$match[2]] = preg_replace('/([a-z][a-z][a-z])/i','\\1&#8203;',$enqmatrix[$match[1]][$match[2]]);
    }
*/
    if($enqmatrix!==null)

        return $enqmatrix[$match[1]][$match[2]];
    else
        return "";
}
function ENQ_RepalaceMatrix_Strip($match)
{
    global $enqmatrix;

/*
    if ($match[2] == 0) {
        $enqmatrix[$match[1]][$match[2]] = preg_replace('/([a-z][a-z][a-z])/i','\\1&#8203;',$enqmatrix[$match[1]][$match[2]]);
    }
*/
    if($enqmatrix!==null)

        return str_replace("\n","",strip_tags($enqmatrix[$match[1]][$match[2]]));
    else
        return "";
}

function ENQ_ReplaceParts($match)
{
    global $MSG;

    return $MSG[$match[1]];
}
