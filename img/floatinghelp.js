var ele_help = null;

function createHelpDiv()
{
	ele_help = document.createElement('div');
	ele_help.innerHTML = 'a';
	var style = ele_help.style;
		style.position = 'absolute';
		style.zIndex = 1;
	//	style.backgroundColor = '#fff2f2';
	//	style.backgroundImage = 'url(img/box_bg.gif)';
	//	style.color = '#ff2222';
		style.width = '250px';
	//	style.height = '200px';
		style.display = 'none';
		style.visibility = 'hidden';
	//	style.borderWidth = '1px';
	//	style.borderStyle = 'solid';
	//	style.borderColor = '#000000';
	//	style.fontSize='12px';
		style.margin = '0px'
		style.padding = '0px'
	document.getElementsByTagName("body").item(0).appendChild(ele_help);
}

function showHelp(message,obj,event)
{
	if(!ele_help){
		createHelpDiv();
	}
	ele_help.innerHTML = message;
	var style = ele_help.style;
	style.display = '';
	style.visibility = 'visible';
	if(!isNaN(event.offsetX)){
		style.left = getEventPageX(event)-event.offsetX + 55 + 'px';
	}
	else
	{
		style.left = getEventPageX(event) + 10 + 'px';
	}
	style.top = getEventPageY(event) + 0 + 'px';
	style.zIndex = 1;
	obj.onmouseout = function(){hideHelp(obj);}
}


function showHelp2(message,obj,event)
{
	if(!ele_help){
		createHelpDiv();
	}
	ele_help.innerHTML = message;
	var style = ele_help.style;
	style.display = '';
	style.visibility = 'visible';
	if(!isNaN(event.offsetX)){
		style.left = getEventPageX(event)-event.offsetX+ 255 + 'px';
	}
	else
	{
		style.left = getEventPageX(event) + 10 + 'px';
	}
	style.top = getEventPageY(event) - 5 + 'px';
	style.zIndex = 1;
	obj.onmouseout = function(){hideHelp(obj);}
}


function hideHelp(obj)
{
	var style = ele_help.style;
	style.display = 'none';
	style.visibility = 'hidden';
	obj.onmouseout = null;
}



function getEventPageX(e){
	if(!e) var e = window.event;
	if(window.opera){
		return (document.documentElement?window.pageXOffset:0)+e.clientX;
	} 
	else if(e.pageX)
		return e.pageX;
	else if(e.clientX){
		var sl=0;
		if(document.documentElement && document.documentElement.scrollLeft)
			sl=document.documentElement.scrollLeft;
		else if(document.body && document.body.scrollLeft)
			sl=document.body.scrollLeft;
		else if(window.scrollX||window.pageXOffset) 
			sl=(window.scrollX||window.pageXOffset);
		return sl+e.clientX;					
	}
	return 0;
}

function getEventPageY(e){
	if(!e) var e = window.event;
	if(window.opera){
		return (document.documentElement?window.pageYOffset:0)+e.clientY;
	} 
	else if(e.pageY) 
		return e.pageY;
	else if(e.clientY){
		var st = 0;
		if(document.documentElement && document.documentElement.scrollTop)
			st=document.documentElement.scrollTop;
		else if(document.body && document.body.scrollTop)
			st=document.body.scrollTop;
		else if(window.scrollY||window.pageYOffset)
			st=(window.scrollY||window.pageYOffset);
		return st+e.clientY;					
	}
	return 0;
}