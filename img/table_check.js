function resettarget()
{
	var eles =  document.getElementsByTagName('input');
	for (var i = 0; i < eles.length; i++) {
		if(eles[i].name.match(/^mail_serial\[[0-9]*\]/i)){
			eles[i].checked = false;
			check(eles[i]);
		}
	}
//	CookieWrite('{$MAILTARGET_NAME}', '', 1);
}
function reverse() {
	var eles =  document.getElementsByTagName('input');
	for (var i = 0; i < eles.length; i++) {
		if(eles[i].name.match(/^mail_serial\[[0-9]*\]/i)){
			eles[i].checked = !(eles[i].checked);
			check(eles[i]);
		}
	}
	return false;
}
function check(obj)
{
	var tr = $(obj).closest("tr");
	if(obj.checked){
		var color = $(tr).css("background-color");
		$(tr).attr("default_bg", color);
		$(tr).css("background-color", "#FFC");
	}else{
		var color = $(tr).attr("default_bg");
		$(tr).css("background-color", color);
	}
}
$(function(){
	$("table.cont :checkbox").each(function(){
		var chk = this;
		var tr = $(this).closest("tr");
		var td = $(this).closest("td");
		
		$(chk).css("cursor", "pointer");
		$(td).css("cursor", "pointer");
		
		$(td).click(function(event){
			if(event.target == chk)
				return;
			
			chk.checked = !chk.checked;
			check(chk);
		})
		
		if(this.checked){
			$(tr).attr("default_bg", $(tr).css("background-color"));
			$(tr).css("background-color", "#FFC");
		}
	});
});
