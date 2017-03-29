var my_confirm_target;
function myconfirm(message)
{
	var _my_confirm_target = global_myconfirm_obj = (window.event||arguments.callee.caller.arguments[0]).target||(window.event||arguments.callee.caller.arguments[0]).srcElement || arguments.callee.caller.caller.arguments[0].target || arguments.callee.caller.caller.caller.arguments[0].target;
	if(my_confirm_target == _my_confirm_target) return true;
	my_confirm_target = _my_confirm_target;
	
	if($("#dialog-confirm").size() > 0) $("#dialog-confirm").remove();
	var my_confirm_modal = document.createElement('div');
	my_confirm_modal.id = 'dialog-confirm';
	my_confirm_modal.title = '確認';
	my_confirm_modal.innerHTML = '<p>'+message+'</p>';
	$('body').append(my_confirm_modal);
	$( "#dialog-confirm" ).dialog({
		resizable: false,
		height:'auto',
		width:'auto',
		modal: true,
		buttons: {
			"はい": function() {
				$(this).dialog("destroy").remove();
				if (my_confirm_target.tagName == 'FORM')
					my_confirm_target.submit();
				else
					my_confirm_target.click();
				my_confirm_target = null;
			},
			"キャンセル": function() {
				$(this).dialog("destroy").remove();
				my_confirm_target = null;
			}
		},
		beforeClose: function(event, ui){
			my_confirm_target = null;
		}
	});
	
	return false;
}