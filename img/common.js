var global_toggle_checkbox = [];
function toggleCheckbox(name, sender)
{
	var checked = !(global_toggle_checkbox[name]);
	setAllChecked(name, checked)
	global_toggle_checkbox[name] = checked;
	if(sender.type == 'button')
	{
		sender.value = (checked) ? "全解除": "全選択";
	}
	else if(sender.type == 'checkbox')
	{
		sender.checked = checked;
	}
}

function setAllChecked(name, checked)
{
	var forms = document.getElementsByName(name);
	for(var i=0; i<forms.length; ++i)
	{
		forms[i].checked = checked;
		if(forms[i].onclick) forms[i].onclick();
	}
}

function TabHeader() 
{
	this.initialize.apply(this, arguments);
}
TabHeader.prototype = 
{
	initialize: function(name, heads) 
	{
		this.name = name;
		this.headers = heads;
		//以下はrecruit用のデザイン設定
		this.class_on = 'tabmenu_on';
		this.class_off = 'tabmenu';
	}
,
	updateTabHead: function(index) 
	{
		var table = document.getElementById(this.name);
		var cells = table.getElementsByTagName('td');
		for(var i=0; i<cells.length;i++)
		{
			if(i == index)
			{
				this.setActiveTabStyle(cells[i], i);
			}
			else
				this.setTabStyle(cells[i], i);
		}
		this.onUpdate(index);
	}
,
	setActiveTabStyle: function(elm, index)
	{
		//abstract
		elm.setAttribute('class' , this.class_on);
		elm.style.textAlign='center';
		elm.setAttribute('className' , this.class_on); // for IE
		elm.innerHTML = headers[index];
	}
,
	setTabStyle: function (elm, index)
	{
		//abstract
		elm.setAttribute('class' , this.class_off);
		elm.setAttribute('className' , this.class_off); // for IE
		elm.innerHTML = '<a href="javascript:'+this.target+'.updateTabHead(' + index + ')">'+headers[index]+'</a>';
	}
,
	onUpdate: function(index){}
}


function TabDiv() 
{
	this.initialize.apply(this, arguments);
}
TabDiv.prototype = 
{
	initialize: function(name, max) 
	{
		this.name = name;
		this.max = max;
	}
,
	call: function(no)
	{
		for(var i=0; i<this.max; ++i) (i == no)? this.show(i): this.hide(i);
	}
,
	calls: function()
	{
		var nos = [], d = "%", f = function(s){return d + s + d};
		for(var i = 0;i < arguments.length; ++i) nos.push(arguments[i]);
		nos = f(nos.join(d));
		for(var i=0; i<this.max; ++i)
			(0 <= nos.indexOf(f(i)))? this.show(i): this.hide(i);
	}
,
	getElement: function(no)
	{
		return document.getElementById(this.name + "_" + no);
	}
,
	setElementStyle: function(style, display, visibility)
	{
		style.display = display;
		style.visibility = visibility;
	}
,
	show: function(no)
	{
		var e = this.getElement(no);
		if(e) this.setElementStyle(e.style, 'block', "visible");
	}
,
	hide: function(no)
	{
		var e = this.getElement(no);
		if(e) this.setElementStyle(e.style, 'none', "hidden");
	}
,
	toggle: function(no)
	{
		this.isVisible(no)? this.show(no): this.hide(no);
	}
,
	isVisible: function(no)
	{
		var e = this.getElement(no);
		if(e) return (e.style.display == 'none');
		return false;
	}
}

function noEnter(e)
{
	return (e.keyCode!=13);
}

function clearTextValue(obj, default_value)
{
	if(obj.value==default_value)
	{
		obj.value = '';
	}
	return;
}


//TrSelector-------------------------------------------------------------------
/*
 * checkboxで選択したtrの色（スタイルクラス）を変更する
 */

function toggleTr(input, onclass, offclass)
{
	var now = input;
	while(now.parentNode)
	{
		now = now.parentNode;
		if(now.tagName == 'TR')
		{
			now.className = input.checked? onclass: offclass;
			return;
		}
		else if(now.tagName == 'BODY') return;
	}
}

function TrSelector() 
{
	this.initialize.apply(this, arguments);
}
TrSelector.prototype = 
{
	//nameにはチェックボックスのnameを指定
	initialize: function(name, onclass, offclass) 
	{
		this.inputs = document.getElementsByName(name);
		for(var i=0;i< this.inputs.length;i++)
		{
			if(this.inputs[i].type == 'checkbox')
				this.setupEvent(this.inputs[i], onclass, offclass)
		}
	}
,
	setupEvent: function(target, onclass, offclass)
	{
		var old = target.onclick;
		toggleTr(target, onclass, offclass);
		target.onclick = (old)?
			function(){old();toggleTr(target, onclass, offclass)}:
			function(){toggleTr(target, onclass, offclass)};
	}
}

//TrSelector-----------------------------------------------------------------//

//-----------------------------------------------------------------------------
//基本DOM関数

function isIE6(){   
    return (typeof document.body.style.maxHeight == "undefined");   
}

var isIE = (function(){
    var undef, v = 3, div = document.createElement('div');
   
    while (
        div.innerHTML = '<!--[if gt IE '+(++v)+']><i></i><![endif]-->',
        div.getElementsByTagName('i')[0]
    );
   
    return v> 4 ? v : undef;
}());
 
//[A]レイヤの位置（X軸方向）の取得 
function getDivLeft(div){
   return document.layers?div.left:(div.offsetLeft||div.style.pixelLeft||0);
}
 
//[A]レイヤの位置（Y軸方向）の取得 
function getDivTop(div){
   return document.layers?div.top:(div.offsetTop||div.style.pixelTop||0);
}

//[A]レイヤのサイズ（幅）の取得 
function getDivWidth (div){ 
  return document.layers?
         div.clip.width:(div.offsetWidth||div.style.pixelWidth||0);
}

//[A]レイヤのサイズ（高さ）の取得 
function getDivHeight(div){
  return document.layers?
         div.clip.height:(div.offsetHeight||div.style.pixelHeight||0);
}

//-----------------------------------------------------------------------------

//-----------------------------------------------------------------------------
//IE6プルダウン問題解決用ライブラリ(汎用)
//show, hideを利用する。対象のz-index指定は必須

var ie6_iframe = {};

function createIe6Iframe(obj)
{
    if(isIE6()){   
		if(ie6_iframe[obj]){   
			return;   
		}   
  
        // iFrameでプルダウンを隠す   
        var e = document.createElement("iframe");   
        e.setAttribute("src", "javascript:false;");   
//        e.setAttribute("id", "hideSelect");   
        e.style.cssText = "z-index: " + (obj.style.zIndex - 1) + ";"+   
                                "position: absolute;"+   
                                "background-color: #fff;"+   
                                "border: none;"+   
                                "filter: alpha(opacity=0);"+   
                                "-moz-opacity: 0;"+   
                                "opacity: 0;"
                                "";   
        document.getElementsByTagName("body").item(0).appendChild(e);
        ie6_iframe[obj] = e;
      }   

}



function showIe6Iframe(obj, x, y, w, h)
{
	if(!ie6_iframe[obj]){
		createIe6Iframe(obj);
	}
	if(!x) x = 0;
	if(!y) y = 0;
	if(!w) w = 0;
	if(!h) h = 0;
	
	if(ie6_iframe[obj])
	{
		var s = ie6_iframe[obj].style;
		s.left = (getDivLeft(obj) + x) + 'px';
		s.top = (getDivTop(obj) + y) + 'px';
		s.width = (getDivWidth(obj) + w) + 'px';
		s.height = (getDivHeight(obj) + h) + 'px';
		s.display = '';
		s.visibility = 'visible';
	}
}


function hideIe6Iframe(obj)
{
	if(ie6_iframe[obj])
	{
		var s = ie6_iframe[obj].style;
		s.display = 'none';
		s.visibility = 'hidden';
	}
}

function escapeHtml(str) { 
    str = str.replace(/&/g,"&amp;") ;
    str = str.replace(/"/g,"&quot;") ;
    str = str.replace(/'/g,"&#039;") ;
    str = str.replace(/</g,"&lt;") ;
    str = str.replace(/>/g,"&gt;") ;
    return str ;
} 

//-----------------------------------------------------------------------------

function getFilename(file_id, post_id)
{
	document.getElementById(post_id).value = document.getElementById(file_id).value;
	return;
}

function getSelectedValue(obj)
{
	return obj.options[obj.selectedIndex].value;
}

function setDisabledInputs(obj, flg)
{
	var tags = ['input','select','textarea'];
	for(var i=0; i<tags.length; ++i)
	{
		var inputs = obj.getElementsByTagName(tags[i]);
		for(var j=0; j<inputs.length; ++j)
			inputs[j].disabled = flg;
	}
	return;
}

function switchDisp(id)
{
	var elm = document.getElementById(id);
	elm.style.display = (elm.style.display=='none')? '':'none';
	return (elm.style.display!='none');
}

function getCheckedIds(form, from_name, to_name)
{
	var ids = '';
	var elms = document.getElementsByName(from_name);
	for(var i=0; i<elms.length; ++i)
	{
		if(elms[i].checked)
			ids += ',' + elms[i].value;
	}
	ids = ids.replace(/^,/, '');
	var elms = form.getElementsByTagName('input');
	for(var i=0; i<elms.length; ++i)
	{
		if(elms[i].name==to_name)
		{
			elms[i].value = ids;
			return;
		}
	}
	alert('IDを指定できません');
	return;
}

function confirmDel(name, word)
{
	var flg = false;
	var elms = document.getElementsByName(name);
	for(var i=0; i<elms.length; ++i)
	{
		if(elms[i].checked)
		{
			flg = true;
			break;
		}
	}
	if(!flg)
	{
		alert(word + 'を選択してください');
		return false;
	}
	return confirm('選択された' + word + 'を削除してもよろしいですか？');
}

function confirmChk(name, word)
{
	var flg = false;
	var elms = document.getElementsByName(name);
	for(var i=0; i<elms.length; ++i)
	{
		if(elms[i].checked)
		{
			flg = true;
			break;
		}
	}
	if(!flg)
	{
		alert(word + 'を選択してください');
		return false;
	}
	return true;
}

function clearForm(obj)
{
	var tags = ['input','select','textarea'];
	for(var i=0; i<tags.length; ++i)
	{
		var inputs = obj.form.getElementsByTagName(tags[i]);
		switch(tags[i])
		{
			case 'input':
				for(var j=0; j<inputs.length; ++j)
				{
					switch(inputs[j].type)
					{
						case 'radio':
						case 'checkbox':
							inputs[j].checked = false;
							break;
						case 'text':
						case 'password':
							inputs[j].value = '';
							break;
						case 'hidden':
						case 'submit':
						case 'reset':
						case 'button':
						case 'image':
						case 'file':
						default:
							break;
					}
				}
				break;
			case 'select':
				for(var j=0; j<inputs.length; ++j)
					inputs[j].selectedIndex = 0;
				break;
			case 'textarea':
				for(var j=0; j<inputs.length; ++j)
					inputs[j].value = '';
				break;
			default:
				break;
		}
	}
}

function getKEYCODE(e){
	try {
		e.keyCode;
	} catch(ex) {
		e = event;  // for IE
	}
	return e.keyCode;
}

function basename(path) {
	return path.replace( /.*\//, '' );
}

function cbaseEnvAlert(){
	if(basename(location.pathname) != 'index2.php')
		return false;
	
	if(location.hostname.indexOf('192.168.11.2') == -1 &&
		location.hostname.indexOf('ct2.') == -1 &&
		location.hostname.indexOf('lgw.') == -1 )
		return false;
	
	var bar = document.createElement('div');
	bar.className = "cbase_env_alert";
	window.document.body.appendChild(bar);
}
setTimeout('cbaseEnvAlert()', 100);
//-----------------------------------------------------------------------------