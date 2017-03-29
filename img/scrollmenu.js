$(document).ready(function(){
	$('#scrollmenu').exFixed();
    $('#scrollmenu').hide();
	var scroll = $(window).scrollTop();
	
	$('#scrollmenu').attr('prescroll',''+scroll+'');
	if(scroll>=300){
		$('#scrollmenu').show();
	}
	$(window).scroll(function(){
		scroll = $(this).scrollTop();
		if(scroll>=300){
			if($('#scrollmenu').attr('prescroll')<300){
				$('#scrollmenu').fadeIn('fast');
			}
		}else{
			if($('#scrollmenu').attr('prescroll')>=300){
				$('#scrollmenu').fadeOut('fast');
			}
		}
		$('#scrollmenu').attr('prescroll',''+scroll+'');
	});
	$('#totop').click(function(){
		$('html,body').animate({scrollTop:0},'linear');
	});
})