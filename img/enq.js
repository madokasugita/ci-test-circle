
function OnKeyUpCommentCheck(obj)
{
	var txt = obj.value;
	var count = countLength(txt);

	var txta = count+"文字";
	if(count > 200){
		var txta = "<span style='color:red'>"+count+"文字</span>";
	}
	document.getElementById('comment_length'+obj.name).innerHTML = "("+txta+"/200文字)";
}


function countLength(txt){
	txt = allReplace(txt,"\r", "");
	txt = allReplace(txt,"\n", "");
	return txt.length;
}

function allReplace(text, sText, rText) {
	while (true) {
		dummy = text;
		text = dummy.replace(sText, rText);
		if (text == dummy) {
			break;
		}
	}
	return text;
}


function TextAreaCheck()
{
	var textareas = document.getElementsByTagName('textarea');
	for(var i = 0;i<textareas.length;i++)
	{
		OnKeyUpCommentCheck(textareas[i]);
	}
}

function checkMainComment(obj){
	var txt = obj.value;
	var count = countLength(txt);
	if(count>200){
		count = '<font color="red">'+count+'</font>';
	}
	document.getElementById('comment_length'+obj.name).innerHTML = count;
}


function checkMainComment_Onblur(obj){
	var txt = obj.value;
	var count = countLength(txt);
	if(count>200){
		count = '<font color="red">'+count+'</font>';
	}
	document.getElementById('comment_length'+obj.name).innerHTML = count;
	if(countLength(txt) > 200){
		alert("200文字以内で入力してください");
		obj.focus();
		return;
	}
}


