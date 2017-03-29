$(function(){
	$("input[rel]").overlay({
	    mask: {
			color: 'gray',
			loadSpeed: 0,
			opacity: '0.3'
		},
		speed: 0,
		
		onBeforeLoad: function() {
			var overlay = this.getOverlay();
			overlay.css('left', (overlay.closest('body').width() - overlay.width()) / 2);
		}
	});

	$("a[rel]").overlay({
		mask: {
			color: 'gray',
			loadSpeed: 0,
			opacity: '0.3'
		},
		speed: 0,

		onBeforeLoad: function() {
			var overlay = this.getOverlay();
			overlay.css('left', (overlay.closest('body').width() - overlay.width()) / 2);
			
			var wrap = overlay.find(".contentWrap");
			wrap.attr('src', "");
			wrap.css('opacity', 0).load(function(){
				$(this).animate({"opacity": 1}, "fast");
			});
			wrap.attr('src', this.getTrigger().attr("href"));
		}
	});
});