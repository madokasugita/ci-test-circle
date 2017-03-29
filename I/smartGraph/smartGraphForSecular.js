var smartGraph = (function(){
	param = GetQueryString();
	var paper;
	var size;
	var animate = (location.search.indexOf('no_animate')==-1)? true:false;
	var pdf_mode = (param["no_animate"]==2)? 2:1;
	
	function smartGraph(){};
	
	var init = function(dom){
		paper = Raphael(dom);
		size = {x:dom.offsetWidth, y:dom.offsetHeight};
	};
	smartGraph.init = init;
	var label_tag = ["本人", "他者"];
	var label_attr = [
		{stroke:"#ff0099", fill:"#ff0099", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"-"},
		{stroke:"#33cc99", fill:"#33cc99", "fill-opacity":0.2, "stroke-width":2},
		{stroke:"#54af62", fill:"#54af62", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"--"},
		{stroke:"#ffb000", fill:"#ffb000", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"-."},
		{stroke:"#088da5", fill:"#088da5", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"- "},
		{stroke:"#ae4ccd", fill:"#ae4ccd", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"."},
		{stroke:"#76766e", fill:"#76766e", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"-.."},
		{stroke:"#ed4bb2", fill:"#ed4bb2", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":". "},
		{stroke:"#5bdb5d", fill:"#5bdb5d", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"- ."},
		{stroke:"#bc0840", fill:"#bc0840", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"--."},
		{stroke:"#f0590a", fill:"#f0590a", "fill-opacity":0.2,"stroke-width":2, "stroke-dasharray":"--.."}
	];
	var gantt_attr = [{"stroke-width":0, fill:"#DBEDF4", stroke:"#DBEDF4"}];
	var gantt_attr_underestimate = [{"stroke-width":0, fill:"#DBEDF4", stroke:"#DBEDF4"}];
	var gantt_attr_overestimate  = [{"stroke-width":0, fill:"#ffdddd", stroke:"#ffdddd"}];
	var label_color = "gray";
	var legend_color = "gray";
	var font_family = "'Hiragino Kaku Gothic ProN', Meiryo, 'MS PGothic', sans-serif";
	
	var config = function(args){
		var args = args||{};
		label_tag = args.label_tag||label_tag;
		for(var i in label_attr)
			label_attr[i] = args.label_attr && args.label_attr[i]||label_attr[i];
		for(var i in gantt_attr)
			gantt_attr[i] = args.gantt_attr && args.gantt_attr[i]||gantt_attr[i];
		for(var i in gantt_attr_underestimate)
			gantt_attr_underestimate[i] = args.gantt_attr_underestimate && args.gantt_attr_underestimate[i]||gantt_attr_underestimate[i];
		for(var i in gantt_attr_overestimate)
			gantt_attr_overestimate[i] = args.gantt_attr_overestimate && args.gantt_attr_overestimate[i]||gantt_attr_overestimate[i];
		
		label_color = args.label_color || label_color;
		legend_color = args.legend_color || legend_color;
	}
	smartGraph.config = config;
	
	var glow_attr={width:2};

	var textbyte = function(str){
		count=0;
		for(i=0;i<str.length;i++)
		(str.charAt(i).match(/[ｱ-ﾝ]/) || escape(str.charAt(i)).length< 4)?count++:count+=2;
		return count;
	}
	
	var clone = function(o)
	{
	    var f = {};
	    f.prototype = o;
	    return new f();
	}
	
	var markerDraw = {
		/* 丸 */
		0: function(x, y){
			return paper.circle(x, y, 5);
		},
		/* 菱型 */
		1: function(x, y){
			var pathdata = "10,0 0,10 10,20 20,10";
			pathdata = pathdata.split(" ");
			for(var i in pathdata){
				var tmp;
				tmp = pathdata[i].split(",");
				tmp[0] = tmp[0]*0.65+x-7;
				tmp[1] = tmp[1]*0.65+y-7;
				pathdata[i] = tmp.join(",");
			}
			pathdata = pathdata.join(" ");
			return paper.path("M"+pathdata+"z");
		},
		/* 四角 */
		2: function(x, y){
			return paper.rect(x-5, y-5, 10, 10);
		},
		/* 三角 */
		3:function(x, y){
			var pathdata = [];
			x = x-6;
			y = y-6;
			pathdata.push([6+x, y].join(","));
			pathdata.push([12+x, 11+y].join(","));
			pathdata.push([x, 11+y].join(","));
			return paper.path("M"+pathdata.join(" ")+"z");
		},
		/* エックス */
		4:function(x, y){
			var pathdata = "24.778,21.419 19.276,15.917 24.777,10.415 21.949,7.585 16.447,13.087 10.945,7.585 8.117,10.415 13.618,15.917 8.116,21.419 10.946,24.248 16.447,18.746 21.948,24.248";
			pathdata = pathdata.split(" ");
			for(var i in pathdata){
				var tmp;
				tmp = pathdata[i].split(",");
				tmp[0] = tmp[0]*0.6+x-9.5;
				tmp[1] = tmp[1]*0.6+y-9.5;
				pathdata[i] = tmp.join(",");
			}
			pathdata = pathdata.join(" ");
			var path = paper.path("M"+pathdata+"z");
			return path;
		},
		/* 十字 */
		5:function(x, y){
			var pathdata = "25.979,12.896 19.312,12.896 19.312,6.229 12.647,6.229 12.647,12.896 5.979,12.896 5.979,19.562 12.647,19.562 12.647,26.229 19.312,26.229 19.312,19.562 25.979,19.562";
			pathdata = pathdata.split(" ");
			for(var i in pathdata){
				var tmp;
				tmp = pathdata[i].split(",");
				tmp[0] = tmp[0]*0.6+x-9;
				tmp[1] = tmp[1]*0.6+y-9;
				pathdata[i] = tmp.join(",");
			}
			pathdata = pathdata.join(" ");
			var path = paper.path("M"+pathdata+"z");
			return path;
		}
	}
	
	var polygon = function(args){
		var args = args||{};
		var radius = args.radius||100;
		var vertex = args.vertex||6;
		var size = args.size||{x:600, y:600}
		var center = args.center||{x:size.x/2, y:size.y/2}
		var path="";
		var radian = 0;
		var point = {x:0, y:0}

		for(var i=0;i < vertex;i++){
			radian = i*(360/vertex)-90;
			point.x = radius * Math.cos(radian*Math.PI/180) + center.x;
			point.y = radius * Math.sin(radian*Math.PI/180) + center.y;
			path += (i==0)? "M":"L";
			path += point.x+","+point.y;
		}

		return line = paper.path(path+"Z");
	}

	var polygon_line = function(args){
		var args = args||{};
		var radius = args.radius||100;
		var vertex = args.vertex||6;
		var size = args.size||{x:600, y:600}
		var center = args.center||{x:size.x/2, y:size.y/2}
		var radian = 0;
		var point = {x:0, y:0}
		
		var set = paper.set();
		
		for(var i=0;i < vertex;i++){
			var path="";
		
			radian = i*(360/vertex)-90;
			point.x = radius * Math.cos(radian*Math.PI/180) + center.x;
			point.y = radius * Math.sin(radian*Math.PI/180) + center.y;

			set.push(paper.path("M"+point.x+","+point.y+"L"+center.x+","+center.y+"Z"));
		}
		
		return set;
	}

	var polygon_label = function(args){
		var args = args||{};
		var data = args.data||[10,80,20,160,130,200];
		var radius = args.radius||100;
		var vertex = data.length;
		var size = args.size||{x:600, y:600};
		var center = args.center||{x:size.x/2, y:size.y/2};
		var path="";
		var radian = 0;
		var point = {x:0, y:0};
		
		var set = paper.set();
		
		var rectsize = {x:100, y:20};
		radius += 10;

		for(var i=0;i < vertex;i++){
			radian = i*(360/vertex)-90;
			point.x = radius * Math.cos(radian*Math.PI/180) + center.x;
			point.y = radius * Math.sin(radian*Math.PI/180) + center.y;
			
			rectsize.x = textbyte(data[i])*7;
			if(i != 0 && vertex/2 > i) point.x += rectsize.x/3;
			if(i != 0 && vertex/2 < i) point.x -= rectsize.x/3;
			var label = paper.rect(point.x-rectsize.x/2, point.y-rectsize.y/2, rectsize.x, rectsize.y);
			label.attr({"stroke-width":0, fill:"white", stroke:"white", "fill-opacity":0.5})
			
			var t = paper.text(point.x, point.y, data[i]).attr({
				"font-weight": "bold",
				"font-size": 14,
				"font-family":font_family,
				"fill": label_color
			})
			set.push(t);
		}
	}

	var polygon_data = function(args){
		var args = args||{};
		var radius = args.radius||100;
		var data = args.data||[3,3,3,3,3,3];
		var vertex = data.length;
		var step = args.step||5;
		var size = args.size||{x:600, y:600};
		var center = args.center||{x:size.x/2, y:size.y/2};
		var path="";
		var radian = 0;
		var point = {x:0, y:0}
		var marker = args.marker||0;
		var attr = args.attr||label_attr[0];
		var markAttr = {"stroke-width":attr["stroke-width"],
				"stroke":attr["stroke"],
				"fill":attr["fill"],
				"fill-opacity":attr["fill-opacity"]};
		
		var set = paper.set();
		
		for(var i=0;i < vertex;i++){
			radian = i*(360/vertex)-90;
			var r = radius/step*data[i];
			point.x = r * Math.cos(radian*Math.PI/180) + center.x;
			point.y = r * Math.sin(radian*Math.PI/180) + center.y;
			set.push(markerDraw[marker](point.x, point.y).attr(markAttr));
			
			path += (i==0)? "M":"L";
			path += point.x+","+point.y;
		}

		set.push(line = paper.path(path+"Z").attr(attr));
		
		return set;
	}

	smartGraph.rader = function(args){
		var base = paper.set();
		
		var args = args||{};
		var padding = args.padding||15;
		var radius = args.radius||size.x/4 - padding*0.5;
		var center = args.center||{x:size.x*2/5, y:size.y/2};
		var step = args.step||5;
		var data = args.data||[[0,0,0,0,0,0], [0,0,0,0,0,0]];
		var label = args.label||["カテゴリー1", "カテゴリー2", "カテゴリー3", "カテゴリー4", "カテゴリー5","カテゴリー6"];
		var vertex = args.vertex||label.length;
		
		for(var i=0; i<step; i++){
			base.push(polygon({radius:radius-(radius/step*i), center:center, vertex:vertex}));
		}
		
		base.push(polygon_line({radius:radius, center:center, vertex:vertex}));
		
		base.attr({stroke:"#d6d6d6", fill:"#efefef"});
		
		for (var i in data) {
			var tmp = polygon_data({radius:radius, center:center, step:step, data:data[i], attr:label_attr[i], "marker":i});
			i = parseInt(i, 10) + 1;
			eval("var c" + i + "=tmp;");
		}
		
		var labels = polygon_label({radius:radius, center:center, data:label});
		
		var label_start = {x:center.x + radius + padding*5, y:size.y - padding*4 - 20};
		
		var legends = {};
		// チャート右下の表示を削除
// 		for(var i=0; i < 5; i++){
// 			legends[i] = paper.set();
// 			var t = paper.text(label_start.x, label_start.y, label_tag[i]).attr({
// 				//"font-weight": "bold",
// 				"font-size": 14,
// 				"font-family":font_family,
// 				"fill": legend_color
// 			});
// 			legends[i].push(t);
// 			var p = paper.path("M"+(label_start.x+30)+","+label_start.y+" H"+(label_start.x+80)).attr(label_attr[i]);
// 			legends[i].push(p);
//
// 			label_start.y += 20 + padding;
// 		}
		
		for(var i=0; i < step; i++){
			var y = center.y - (radius/step)*(step-i) + 5;
			paper.text(center.x, y, step - i).attr({
				//"font-weight": "bold",
				"stroke-width":0,
				"font-size": 11,
				//"stroke": "white",
				"fill": "#666666"
			});
		}
		
		/* アニメーションは表示時のみなので処理をまとめる */
		if(animate){
			if (typeof c5 !== "undefined") {
				c5.attr({transform:"s0.6", opacity:0});
				c5.hover(function(){
					this.g = this.glow(glow_attr);
				},function(){
					this.g.remove();
				});
				var c5func = function() {
					c5.animate({transform:"s1", opacity:1}, 200, "-elastic", function(){});
				};
			}
			if (typeof c4 !== "undefined") {
				c4.attr({transform:"s0.6", opacity:0});
				c4.hover(function(){
					this.g = this.glow(glow_attr);
				},function(){
					this.g.remove();
				});
				var c4func = function() {
					c4.animate({transform:"s1", opacity:1}, 200, "-elastic", c5func);
				};
			}
			if (typeof c3 !== "undefined") {
				c3.attr({transform:"s0.6", opacity:0});
				c3.hover(function(){
					this.g = this.glow(glow_attr);
				},function(){
					this.g.remove();
				});
				var c3func = function() {
					c3.animate({transform:"s1", opacity:1}, 200, "-elastic", c4func);
				};
			}
			if (typeof c2 !== "undefined") {
				c2.attr({transform:"s0.6", opacity:0});
				c2.hover(function(){
					this.g = this.glow(glow_attr);
				},function(){
					this.g.remove();
				});
				var c2func = function() {
					c2.animate({transform:"s1", opacity:1}, 200, "-elastic", c3func);
				};
			}
			if (typeof c1 !== "undefined") {
				c1.attr({transform:"s0.6", opacity:0});
				c1.hover(function(){
					this.g = this.glow(glow_attr);
				},function(){
					this.g.remove();
				});
				c1.animate({transform:"s1", opacity:1}, 200, "-elastic", c2func);
			}
		}
		
		return base;
	}
	
	var preGanttObj = {};
	var preLineSet;
	var preMarkSet;
	var drawVerticalData = function(args){
		var args = args||{};
		var data = args.data||[[],[]];
		var lineHeight = args.lineHeight||5;
		var padding = args.padding||5;
		var term = args.term||5;
		var mode = args.mode||1;
		
		var vDataSet = paper.set();
		/* 最大最小の差 */
		var top = 0;
		for(var i=0; i < data[0].length; i++){
			var max=null, maxdata=null, min=null, mindata=null;
			for(var d = 0; d < data.length; d++){
				if(!data[d][i]) continue;
				
				if(!maxdata || maxdata < data[d][i]){
					maxdata = data[d][i];
					max = d;
				}
				if(!mindata || mindata > data[d][i]){
					mindata = data[d][i];
					min = d;
				}
			}
			
			if(!maxdata || !mindata) continue;
			
			top = i*lineHeight;
			
			if (mode == 1)
				gantt_attr_color = gantt_attr;

			if (mode == 2)
				gantt_attr_color = (min == 1)? gantt_attr_overestimate:gantt_attr_underestimate;

			if(preGanttObj[i]){
				gantt_attr_color[0] = $.extend(gantt_attr_color[0], {x:padding + data[min][i]*term, width:(data[max][i]-data[min][i])*term});
				preGanttObj[i].animate(gantt_attr_color[0], 500, "elastic");
			}else{
				preGanttObj[i] = paper.rect(padding + data[min][i]*term, top+lineHeight/4, (data[max][i]-data[min][i])*term, lineHeight/2)
				.attr(gantt_attr_color[0]);
				vDataSet.push(preGanttObj[i]);
			}
		}
		
		if(preLineSet) preLineSet.remove();
		if(preMarkSet) preMarkSet.remove();
		preLineSet = paper.set();
		preMarkSet = paper.set();
		
		/* 縦グラフ */
		for(var i=0; i < data.length; i++){
			top = 0;
			var path = "";
			var set = paper.set();
			
			for(var d=0; d < data[i].length; d++){
				top = d*lineHeight;
				
				var x = padding + data[i][d]*term;
				var y = top+lineHeight/2;
				
				/* 自身か一つ前が存在しなければ線を引かず、移動のみ */
				path += (!data[i][d] || !data[i][d-1])? "M":"L";
				path += x+","+y;
				
				if(data[i][d]){
					var tmpAttr = {"stroke-width":label_attr[i]["stroke-width"],
							"stroke":label_attr[i]["stroke"],
							"fill":label_attr[i]["fill"],
							"fill-opacity":label_attr[i]["fill-opacity"]};
					var mark = markerDraw[i](x, y).attr(tmpAttr);
					preMarkSet.push(mark);
					set.push(mark);
				}
			}
			var line = paper.path(path).attr(label_attr[i]);
			preLineSet.push(line);
			set.push(line);
			set.attr({"fill-opacity":0});
			vDataSet.push(set);
		}
		
		return vDataSet;
	}
	
	smartGraph.verticalLine = function(args){
		var args = args||{};
		var step = args.step||5;
		var padding = args.padding||2;
		var term = (size.x-padding*2)/(step+1);
		var lineHeight = args.lineHeight||30;
		var dataSet = {};
		dataSet[1] = args.data||[[3,3,3,3,3], [4,4,4,4,4]];
		dataSet[2] = args.data2||dataSet[1];
		var odd = "#f9f9f9";
		
		/* 背景 */
		var top = 0;
		for(var i=0; i < data[0].length; i++){
			top = i*lineHeight;
			
			var r = paper.rect(0, top, size.x, lineHeight).attr({"stroke-width":0, fill:"white", stroke:"white"});
			if(i%2==0) r.attr({fill:odd, stroke:odd});
		}
		/* 罫線 */
		for(var i=1; i <= step; i++){
			paper.path("M"+(padding + i*term)+",0 V"+(lineHeight*data[0].length))
				.attr({stroke:"lightgray"});
		}
		
		/*
		for(var i=1; i <= step; i++){
			paper.text(padding + i*term, 5, i);
		}
		*/
		
		paper.setSize(size.x, data[0].length*lineHeight);
		
		var r = new Object();
		r.mode = pdf_mode;
		
		var d = drawVerticalData({data:dataSet[r.mode], lineHeight:lineHeight, padding:padding, term:term, mode:r.mode});
		r.toggle = function(){
			r.mode = r.mode==1? 2:1;
			d = drawVerticalData({data:dataSet[r.mode], lineHeight:lineHeight, padding:padding, term:term, mode:r.mode});
		}
		
		return r;
	}
	
	return smartGraph;
})();

/**
 * パラメータを取得する
 */
function GetQueryString()
{
	var result = {};
	if(1 < window.location.search.length)
	{
		// 最初の1文字 (?記号) を除いた文字列を取得する
		var query = window.location.search.substring(1);
		// クエリの区切り記号 (&) で文字列を配列に分割する
		var parameters = query.split('&');

		for(var i = 0; i < parameters.length; i++)
		{
			// パラメータ名とパラメータ値に分割する
			var element = parameters[ i ].split('=');
			var paramName = decodeURIComponent(element[0]);
			var paramValue = decodeURIComponent(element[1]);
			// パラメータ名をキーとして連想配列に追加する
			result[ paramName ] = paramValue;
		}
	}
	return result;
}
