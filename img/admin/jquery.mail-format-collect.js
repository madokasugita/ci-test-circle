;(function (window, $) {
	var document = window.document;
	$(document).ready(function () {
		var editors = {};

		// Rich text editor開始
		var initWysywig = function() {
			setting = {
				height: '500px',
				width: '585px',
				handleSubmit: true,
				dompath: true,
				animate: true,
				buttonType: 'advanced',
				toolbar: {
					buttons: [{
						group: 'textstyle', label: ' ',
						buttons: [
							{ type: 'spin', label: '13', value: 'fontsize', range: [ 9, 75 ], disabled: true },
							{ type: 'separator' },
							{ type: 'color', label: '文字色', value: 'forecolor', disabled: true },
							{ type: 'color', label: '背景色', value: 'backcolor', disabled: true },
							{ type: 'separator' },
							{ type: 'push', label: '太字', value: 'bold' },
							{ type: 'push', label: '斜体', value: 'italic' },
							{ type: 'push', label: '下線', value: 'underline' },
							{ type: 'separator' },
							{ type: 'push', label: 'リンク', value: 'createlink' },
							{ type: 'separator' },
							{ type: 'push', label: '左揃え', value: 'justifyleft' },
							{ type: 'push', label: '中央揃え', value: 'justifycenter' },
							{ type: 'push', label: '右揃え', value: 'justifyright' },
							{ type: 'push', label: '両端揃え', value: 'justifyfull' }
						]
					}]
				}
			}
			$(".body").each(function(){
				var editor = new YAHOO.widget.Editor(this.id, setting);
				var bkey = $(this).closest('div').data('bkey');
				editors[bkey] = editor;
				editor.render();
			});
		};

		// オブジェクトへの差し込み
		var insertTextArea = function(txt, obj) {
			obj.focus();
			if(jQuery.browser.msie)
			{
				var pos = document.selection.createRange();
				pos.text = txt;
				pos.select();
			}
			else
			{
				var s = obj.value;
				var pos = obj.selectionStart;
				var newPos = pos + txt.length;
				obj.value = s.substr(0, pos) + txt + s.substr(pos);
				obj.setSelectionRange(newPos, newPos);
			}
		};

		// 差込ボタンクリックイベント
		$('.collect_word').click(function() {
			var word = $(this).closest('span').find(':input[name="collect_word"]').val();
			var body = $(this).closest('div').find('.body');
			var bkey = $(this).closest('div').data('bkey');

			if (htmlMail == '0')
			{
				insertTextArea(word, body[0]);
			}
			else if(htmlMail == '1' && typeof editors[bkey] != 'undefined')
			{
				editors[bkey].execCommand('inserthtml', word);
			}
		});

		// 実行
		if (htmlMail == '1') {
			initWysywig();
		}
	});
}(this, jQuery));
