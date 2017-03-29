(function(b){b.ex=b.ex||{};var a=b.extend({},b.ex);a.defineExPlugin=function(d,c,e){b.fn[d]=function(g,l){var k=this,f=[];p=e||{eachTarget:true};if(p.eachTarget){k.each(function(i){f.push(new c(k.eq(i),g))})}else{f.push(new c(k,g))}var j=b(f);for(var h in c.prototype){(function(m){if(m.slice(0,1)!="_"){j[m]=function(){return j[0][m].apply(j[0],arguments)}}})(h)}j.target=function(){return k};k["get"+d.substr(0,1).toUpperCase()+d.substr(1)]=function(){return j};if(typeof l=="function"){j.each(l)}return this}};a.scrollEvent=function(e,d){var f=this;if(typeof d=="function"){d={callback:d}}var g=f.config=b.extend({},a.scrollEvent.defaults,d,{target:e});g.status=0;g.scroll=f.getPos();g.target.scroll(function(c){if(f.isMove()){g.status=(g.status==0?1:(g.status==1?2:g.status));g.callback(c,g)}if(g.tm){clearTimeout(g.tm)}g.tm=setTimeout(function(){f.isMove();g.status=0;g.callback(c,g)},g.delay)})};b.extend(a.scrollEvent.prototype,{isMove:function(){var f=this,h=f.config;var g=f.getPos();var d=(g.top!=h.scroll.top);var e=(g.left!=h.scroll.left);if(d||e){h.scrollY=d;h.scrollX=e;h.prevScroll=h.scroll;h.scroll=g;return true}return false},getPos:function(){var d=this,e=d.config;return{top:e.target.scrollTop(),left:e.target.scrollLeft()}}});a.scrollEvent.defaults={delay:100};b.ex.fixed=function(g,e){var h=this;var i=h.config=b.extend({},b.ex.fixed.defaults,e,{target:g,logicSize:{},rowSize:{},currentStyle:"",style:"",window:b(window),staticFixed:false,oldBrowser:b.browser.msie&&(b.browser.version<7||!b.boxModel)});if(i.baseNode){i.baseNode=$(i.baseNode)}var f=h._cleanSize(i);h._eachSizeSet(function(c,k,j){i.staticFixed=i.staticFixed||(f[k.pos1]==undefined&&f[k.pos2]==undefined)});if(i.oldBrowser){h._padPos(f,h._cleanSize(i.target[0].currentStyle))}else{if(i.staticFixed){return}}i.container=b.boxModel?b("html"):b("body");i.container.height();i.target.css("position",i.oldBrowser?"absolute":"fixed");if(i.oldBrowser&&!/hidden|scroll/i.test(i.target.css("overflow"))){i.target.css("overflow","hidden")}h._smoothPatch();h._fixed(f);i.window.resize(function(){if(i.oldBrowser||i.baseNode){h._fixed()}});if(!(i.fixedX&&i.fixedY)){if(i.oldBrowser){var d;i.window.scroll(function(){if(d){clearTimeout(d)}d=setTimeout(function(){h._fixed()},0)})}else{new a.scrollEvent(i.window,function(c,j){if((j.scrollX&&!i.fixedX)||(j.scrollY&&!i.fixedY)){if(j.status==1){h._fixed(i.logicSize,{unfixed:true})}else{if(j.status==0){h._fixed()}}}})}}};b.ex.fixed.config={smoothPatched:false};b.ex.fixed.defaults={baseNode:"",baseX:true,baseY:true,fixedX:true,fixedY:true};b.extend(b.ex.fixed.prototype,{_attn:[{size:"height",pos1:"top",pos2:"bottom"},{size:"width",pos1:"left",pos2:"right"}],_camel:[{size:"Height",pos1:"Top",pos2:"Bottom"},{size:"Width",pos1:"Left",pos2:"Right"}],_moveFixedFront:function(){var f=this,g=f.config;var d=g.target.parents();var e=d.filter(function(c){var h=d.eq(c);return !(/HTML|BODY/i.test(h[0].tagName))&&d.eq(c).css("position")!="static"});if(e.size()){e.eq(e.size()-1).after(g.target)}return f},_smoothPatch:function(){var e=this,f=e.config;e._moveFixedFront();if(!f.oldBrowser){return e}b.ex.fixed.config.smoothPatched=true;var d=b("html");if(d.css("background-image")=="none"){d.css({"background-image":"url(null)"})}d.css({"background-attachment":"fixed"});return e},_eachSize:function(k){var l=this,m=l.config;for(var h=0;h<l._attn.length;h++){var g=l._attn[h];for(var e in g){var d=g[e];k({idx:h,name:d,camel:d.slice(0,1).toUpperCase()+d.slice(1)})}}},_eachSizeSet:function(e){var g=this,h=g.config;for(var d=0;d<g._attn.length;d++){e(d,g._attn[d],g._camel[d],g._attn[1-d],g._camel[1-d])}},_parseSize:function(g,d){var f=this,h=f.config;if(g=="auto"){return undefined}if((g+"").indexOf("%")<0){return parseInt(g)||0}var e=h.container.attr(d?"clientWidth":"clientHeight");return Math.round(e*parseInt(g)/100)},_parseIntSize:function(f,d){var e=this,g=e.config;return parseInt(e._parseSize(f,d))||0},_cleanSize:function(e){var f=this,g=f.config;var d={};f._eachSize(function(c){if(/undefined|auto/i.test(e[c.name])){try{delete e[c.name]}catch(h){}}else{d[c.name]=e[c.name]}});return d},_padPos:function(d,e){var f=this,h=f.config;var g;f._eachSizeSet(function(c,j,i){if(d[j.pos1]==undefined&&d[j.pos2]==undefined){if((g=e[j.pos1])!=undefined){d[j.pos1]=g}else{if((g=e[j.pos2])!=undefined){d[j.pos2]=g}else{d[j.pos1]=0}}}if(d[j.size]==undefined){if((d[j.size]=e[j.size])==undefined){d[j.size]=h.target[j.size]()}}});return d},_calcRowSize:function(h,g){var i=this,j=i.config;var g=b.extend({abs:false,base:j.baseNode,unfixed:false},g);var f={};i._eachSize(function(c){var k=h[c.name];if(!(/undefined/i.test(k))){f[c.name]=i._parseIntSize(k,/width|left|right/i.test(c.name));if(g.abs&&/top|left/i.test(c.name)){f[c.name]+=j.window["scroll"+c.camel]()}}});if(g.base){var e=j.baseNode.offset();i._eachSizeSet(function(k,l,c){e[l.pos2]=j.container.attr("client"+c.size)-(e[l.pos1]+j.baseNode["outer"+c.size]())});i._eachSize(function(k){if(!(/height|width/i.test(k.name))&&f[k.name]==undefined&&((!k.idx&&j.baseY)||(k.idx&&j.baseX))){var c=k.name=="top"?"bottom":k.name=="bottom"?"top":k.name=="left"?"right":"left";f[c]+=e[c]}})}var d=g.unfixed&&!j.fixedX?-1:1;if(d==-1||(!g.unfixed&&!j.fixedY)){if(f.top!=undefined){f.top-=(j.window.scrollTop()*d)}if(f.bottom!=undefined){f.bottom+=(j.window.scrollTop()*d)}}var d=!g.unfixed&&!j.fixedX?-1:1;if(d==-1||(g.unfixed&&!j.fixedY)){if(f.left!=undefined){f.left+=(j.window.scrollLeft()*d)}if(f.right!=undefined){f.right-=(j.window.scrollLeft()*d)}}return f},_fixed:function(g,f){var h=this,i=h.config;var f=b.extend({unfixed:false},f);if(g){i.logicSize=h._padPos(h._cleanSize(g),i.logicSize)}if(!i.oldBrowser){i.target.css($.extend(i.baseNode||!(i.fixedX&&i.fixedY)?h._calcRowSize(i.logicSize,f):i.logicSize,{position:f.unfixed?"absolute":"fixed"}))}else{var e=h._calcRowSize(i.logicSize);var d=false;if(i.target.is(":hidden")){d=true;i.target.show()}h._eachSizeSet(function(j,l,c){i.target.css(l.size,e[l.size]);var k=e[l.pos1];if(k==undefined){k=i.container.attr("client"+c.size)-e[l.pos2]-i.target["outer"+c.size]()}var m=(k+i.target["outer"+c.size]())-i.container.attr("client"+c.size);if(m>0){m=i.target[l.size]()-m;if(m>0){i.target[l.size](m)}else{d=true}}if(!d){i.target[0].style.setExpression(l.pos1,k+((!j&&!i.fixedY)||(j&&!i.fixedX)?i.window["scroll"+c.pos1]():"+eval(document.body.scroll"+c.pos1+"||document.documentElement.scroll"+c.pos1+")"))}});if(d){i.target.hide()}}},target:function(){return this.config.target},fixedOpen:function(d){var e=this,g=e.config;if(g.staticFixed){return}if(g.oldBrowser){g.target[0].style.removeExpression("top");g.target[0].style.removeExpression("left")}if(d){setTimeout(function(){if(g.oldBrowser){g.target.css({top:"auto",left:"auto"});g.target.css(e._calcRowSize(g.logicSize,{abs:true}))}d()},100)}return e},fixedClose:function(d){var e=this,f=e.config;if(f.staticFixed){return}e._fixed(d);return e},fixedSize:function(d){var e=this,f=e.config;return e._calcRowSize(e._padPos(d,f.logicSize),{abs:f.oldBrowser})},resize:function(d){var e=this,f=e.config;e.fixedOpen(function(){e.fixedClose(d)});return e}});a.defineExPlugin("exFixed",b.ex.fixed)})(jQuery);