$(function(){
	var oncolor = (window.cbase_radio_mouseover_color)? window.cbase_radio_mouseover_color:'#e6f0ff';
	var selectcol = (window.cbase_radio_click_color)? window.cbase_radio_click_color:'#b0c4de';
	var stil = $('td:has(:radio:checked,:checkbox:checked):not(:has(td))');
	stil.css('font-weight','bold');
	stil.css('background-color',selectcol);
	$('td:has(:radio,:checkbox):not(:has(td))').mouseover(function(){
		var color = $(this).css('background-color');
		$(this).attr('default',color);
		$(this).css('background-color',oncolor);
	});
	$('td:has(:radio,:checkbox):not(:has(td))').mouseout(function(){
		var color =  $(this).attr('default');
		$(this).css('background-color',color);
	});
	$('td:has(:radio):not(:has(td))').click(function(){
		var radiobox = $(this).find(':radio');
		radiobox.attr('checked','checked');
		var name = $(this).find(':radio').attr('name');
		var group = $(':radio[name="'+name+'"]');
		group.parent().css('font-weight','');
		group.parent().css('background-color','');
		radiobox.parent().css('font-weight','bold');
		radiobox.parent().css('background-color',selectcol);
		radiobox.parent().attr('default',selectcol);
		radiobox.change();
	});
	$(':checkbox').click(function(){
		var check = $(this).attr('checked');
		var cell = $(this).parent();
		cell.attr('touch','true');
		if(check == true){
			cell.css('font-weight','bold');
			cell.css('background-color',selectcol);
			cell.attr('default',selectcol);
		}
		else{
			cell.css('font-weight','');
			cell.css('background-color','');
			cell.attr('default','');
		}
	});
	$('td:has(:checkbox):not(:has(td))').click(function(){
		var box = $(this).find(':checkbox');
		var check = box.attr('checked');
		var touch = $(this).attr('touch');
		if(!touch){
			if(check == false){
				box.attr('checked',true);
				$(this).css('font-weight','bold');
				$(this).css('background-color',selectcol);
				$(this).attr('default',selectcol);
			}
			else{
				box.attr('checked',false);
				$(this).css('font-weight','');
				$(this).css('background-color','');
				$(this).attr('default','');
			}
		}else{
			$(this).removeAttr('touch');
		}
		box.change();
	});
});