var SID;
function setSessionLifeTimeReset(SID_,minutes){
	SID=SID_;
	setInterval("setSessionLifeTimeReset_()", minutes*1000*60) ;
}

function setSessionLifeTimeReset_(){
	ajax('session_life_time_reset.php',SID,'POST');
}

function ajax(url,data,method) {
	if (window.XMLHttpRequest) { 
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
			xmlhttp.open("POST", url, true);
			xmlhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
			xmlhttp.setRequestHeader("Content-Length",data.length);
			xmlhttp.send(data);
		}else{
			xmlhttp.open("GET", url+"?"+data, true);
		}
	}
}