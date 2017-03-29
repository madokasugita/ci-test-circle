$(function() {
	if ($(':input[name="file"]').size() > 0) {
		$('form[name="message_upload_form"]').submit(function() {
			var file = $(':input[name="file"]').prop('files')[0];
			if (typeof file != "undefined") {
				var path = location.pathname;
				var paths = new Array();
				paths = path.split("/");
				if(paths[paths.length-1] != ""){
					paths[paths.length-1] = "";
					path = paths.join("/");
				}
				$.cookie('temporary_message_upload_file', file.name);
			}
		});
		if (typeof $.cookie('temporary_message_upload_file') != "undefined") {
			var file_tr = $(':input[name="file"]').parent().parent();
			var filename_tr = file_tr.clone(true);
			filename_tr.find('th').text('最後に選択したファイル');
			filename_tr.find('td').text($.cookie('temporary_message_upload_file'));
			file_tr.after(filename_tr);
		}
	}
});
