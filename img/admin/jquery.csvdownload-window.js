var debug1, debug2;
;(function (window, $)
{
	var document = window.document;
	$(document).ready(function ()
	{
		// csvdownloadのボタン押下時別Window化
		(function()
		{
			var ele = $(':input[type="submit"]');
			var prev_target, modified_form;
			ele.click(function()
			{
				if ($(this).attr('name') === 'csvdownload')
				{
					prev_target = $(this).closest('form').attr('target');
					$(this).closest('form').attr('target', '_blank');
					modified_form = $(this).closest('form')[0];
				}
				else
				{
					if (modified_form === $(this).closest('form')[0]) {
						if (typeof prev_target === 'undefined') {
							$(this).closest('form').removeAttr('target');
						} else {
							$(this).closest('form').attr('target', prev_target);
						}
					}
				}
			});
		})();
	});
}(this, jQuery));
