function ajax(url,data,method) {

	var aflag = false;
	if (window.XMLHttpRequest) { 
		aflag = true;
		xmlhttp = new XMLHttpRequest();
		if (xmlhttp.overrideMimeType) {
			xmlhttp.overrideMimeType("text/xml");
		}
	} else if (window.ActiveXObject) { 
		try {
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			} catch (e) {alert("error");}
		}
	}
	
	if (xmlhttp) {
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4) {
				if (xmlhttp.status == 200) {
					eval(xmlhttp.responseText);
				} else {
					alert("error");
				}
			}
		}
		data = encodeURI(data);
		if(method == "POST"){
			xmlhttp.open("POST", url,aflag);
			xmlhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
			xmlhttp.setRequestHeader("Content-Length",data.length);
			xmlhttp.send(data);
		}else{
			xmlhttp.open("GET", url+"?"+data,aflag);
		}
	}
	return false;
}

function cbaseEnvAlert(){
	if(location.hostname.indexOf('192.168.11.2') == -1 &&
		location.hostname.indexOf('ct2.') == -1 &&
		location.hostname.indexOf('lgw.') == -1 )
		return false;
	
	var bar = document.createElement('div');
	bar.className = "cbase_env_alert";
	window.document.body.appendChild(bar);
}
setTimeout('cbaseEnvAlert()', 100);


$(function() {
    if (typeof comment_max_length !== 'undefined' && comment_max_length > 0 && $('span[id^="comment_length_"]').size() > 0) {
        $(':submit:not(#menuButton)').click(function(event) {
            $submit = $(this);
            $('span[id^="comment_length_"]').each(function() {
                if (parseInt($(this).text(), 10) > comment_max_length) {
                    event.preventDefault();
                    alert(comment_max_length + '文字以下で回答してください');
                    $submit.attr('disabled', false)
                }
            });
        });
    }
});