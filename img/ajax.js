function AjaxObject()
{
	this.initialize.apply(this, arguments);
}
AjaxObject.prototype = 
{
	initialize: function() 
	{
		this.aflag = false;
	}
,

	getXmlObject: function ()
	{
		if (window.XMLHttpRequest) 
		{
			this.aflag = true;
			xmlhttp = new XMLHttpRequest();
			if (xmlhttp.overrideMimeType) xmlhttp.overrideMimeType("text/xml");
		}
		else if (window.ActiveXObject) 
		{ 
			try 
			{
				xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
			} 
			catch (e) 
			{
				try 
				{
					xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
				} 
				catch (e) 
				{
					alert("getXML error");
				}
			}
		}
		return xmlhttp;
	}
,
	encodeData: function()
	{
		var res = new Array();
		for(key in this.data) res.push( key + "=" + this.data[key]);
		return res.join("&");
	}
,
	post: function(url)
	{
		this.ajaxCorrespond(url, "POST");
	}
,
	send: function(url)
	{
		this.ajaxCorrespond(url, "GET");
	}
,
	ajaxCorrespond: function(url, method) 
	{
		var xmlhttp = this.getXmlObject();
		if (!xmlhttp) return false;
		var doEvent = this.onLoad,
			doError = this.onError;
		xmlhttp.onreadystatechange = function() 
		{
			if (xmlhttp.readyState == 4) 
			{
				if (xmlhttp.status == 200) 
					doEvent(xmlhttp);
				else
					doError(xmlhttp);
			}
		}		
		var senddata = encodeURI(this.encodeData());
		if(method == "POST")
		{
			xmlhttp.open("POST", url, this.aflag);
			xmlhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
			xmlhttp.setRequestHeader("Content-Length",senddata.length);
			xmlhttp.send(senddata);
		}
		else
		{
			xmlhttp.open("GET", url+"?"+senddata, this.aflag);
		}
		return true;
	}
,
	onLoad: function(xmlhttp)
	{
		eval(xmlhttp.responseText);
	}
,
	onError: function(xmlhttp)
	{
		alert("error2");
	}
};