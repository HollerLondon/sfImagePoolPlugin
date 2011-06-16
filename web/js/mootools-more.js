// MooTools: the javascript framework.
// Load this file's selection again by visiting: http://mootools.net/more/b182d2c5d96807119ec2cc0d6508a442 
// Or build this file again with packager using: packager build More/Fx.Reveal
/*
---
copyrights:
  - [MooTools](http://mootools.net)

licenses:
  - [MIT License](http://mootools.net/license.txt)
...
*/
MooTools.More={version:"1.3.0.1",build:"6dce99bed2792dffcbbbb4ddc15a1fb9a41994b5"};Element.implement({isDisplayed:function(){return this.getStyle("display")!="none";
},isVisible:function(){var a=this.offsetWidth,b=this.offsetHeight;return(a==0&&b==0)?false:(a>0&&b>0)?true:this.style.display!="none";},toggle:function(){return this[this.isDisplayed()?"hide":"show"]();
},hide:function(){var b;try{b=this.getStyle("display");}catch(a){}if(b=="none"){return this;}return this.store("element:_originalDisplay",b||"").setStyle("display","none");
},show:function(a){if(!a&&this.isDisplayed()){return this;}a=a||this.retrieve("element:_originalDisplay")||"block";return this.setStyle("display",(a=="none")?"block":a);
},swapClass:function(a,b){return this.removeClass(a).addClass(b);}});Document.implement({clearSelection:function(){if(document.selection&&document.selection.empty){document.selection.empty();
}else{if(window.getSelection){var a=window.getSelection();if(a&&a.removeAllRanges){a.removeAllRanges();}}}}});(function(){var a=function(d,c){var e=[];
Object.each(c,function(f){Object.each(f,function(g){d.each(function(h){e.push(h+"-"+g+(h=="border"?"-width":""));});});});return e;};var b=function(e,d){var c=0;
Object.each(d,function(g,f){if(f.test(e)){c=c+g.toInt();}});return c;};Element.implement({measure:function(h){var d=function(j){return !!(!j||j.offsetHeight||j.offsetWidth);
};if(d(this)){return h.apply(this);}var g=this.getParent(),i=[],e=[];while(!d(g)&&g!=document.body){e.push(g.expose());g=g.getParent();}var f=this.expose();
var c=h.apply(this);f();e.each(function(j){j();});return c;},expose:function(){if(this.getStyle("display")!="none"){return function(){};}var c=this.style.cssText;
this.setStyles({display:"block",position:"absolute",visibility:"hidden"});return function(){this.style.cssText=c;}.bind(this);},getDimensions:function(c){c=Object.merge({computeSize:false},c);
var h={x:0,y:0};var g=function(i,e){return(e.computeSize)?i.getComputedSize(e):i.getSize();};var d=this.getParent("body");if(d&&this.getStyle("display")=="none"){h=this.measure(function(){return g(this,c);
});}else{if(d){try{h=g(this,c);}catch(f){}}}return Object.append(h,(h.x||h.x===0)?{width:h.x,height:h.y}:{x:h.width,y:h.height});},getComputedSize:function(c){if(c&&c.plains){c.planes=c.plains;
}c=Object.merge({styles:["padding","border"],planes:{height:["top","bottom"],width:["left","right"]},mode:"both"},c);var e={},d={width:0,height:0};if(c.mode=="vertical"){delete d.width;
delete c.planes.width;}else{if(c.mode=="horizontal"){delete d.height;delete c.planes.height;}}a(c.styles,c.planes).each(function(f){e[f]=this.getStyle(f).toInt();
},this);Object.each(c.planes,function(g,f){var h=f.capitalize();e[f]=this.getStyle(f).toInt();d["total"+h]=e[f];g.each(function(j){var i=b(j,e);d["computed"+j.capitalize()]=i;
d["total"+h]+=i;});},this);return Object.append(d,e);}});})();Fx.Reveal=new Class({Extends:Fx.Morph,options:{link:"cancel",styles:["padding","border","margin"],transitionOpacity:!Browser.ie6,mode:"vertical",display:function(){return this.element.get("tag")!="tr"?"block":"table-row";
},opacity:1,hideInputs:Browser.ie?"select, input, textarea, object, embed":null},dissolve:function(){if(!this.hiding&&!this.showing){if(this.element.getStyle("display")!="none"){this.hiding=true;
this.showing=false;this.hidden=true;this.cssText=this.element.style.cssText;var c=this.element.getComputedSize({styles:this.options.styles,mode:this.options.mode});
if(this.options.transitionOpacity){c.opacity=this.options.opacity;}var b={};Object.each(c,function(e,d){b[d]=[e,0];});this.element.setStyles({display:Function.from(this.options.display).call(this),overflow:"hidden"});
var a=this.options.hideInputs?this.element.getElements(this.options.hideInputs):null;if(a){a.setStyle("visibility","hidden");}this.$chain.unshift(function(){if(this.hidden){this.hiding=false;
this.element.style.cssText=this.cssText;this.element.setStyle("display","none");if(a){a.setStyle("visibility","visible");}}this.fireEvent("hide",this.element);
this.callChain();}.bind(this));this.start(b);}else{this.callChain.delay(10,this);this.fireEvent("complete",this.element);this.fireEvent("hide",this.element);
}}else{if(this.options.link=="chain"){this.chain(this.dissolve.bind(this));}else{if(this.options.link=="cancel"&&!this.hiding){this.cancel();this.dissolve();
}}}return this;},reveal:function(){if(!this.showing&&!this.hiding){if(this.element.getStyle("display")=="none"){this.hiding=false;this.showing=true;this.hidden=false;
this.cssText=this.element.style.cssText;var c;this.element.measure(function(){c=this.element.getComputedSize({styles:this.options.styles,mode:this.options.mode});
}.bind(this));if(this.options.heightOverride!=null){c.height=this.options.heightOverride.toInt();}if(this.options.widthOverride!=null){c.width=this.options.widthOverride.toInt();
}if(this.options.transitionOpacity){this.element.setStyle("opacity",0);c.opacity=this.options.opacity;}var b={height:0,display:Function.from(this.options.display).call(this)};
Object.each(c,function(e,d){b[d]=0;});b.overflow="hidden";this.element.setStyles(b);var a=this.options.hideInputs?this.element.getElements(this.options.hideInputs):null;
if(a){a.setStyle("visibility","hidden");}this.$chain.unshift(function(){this.element.style.cssText=this.cssText;this.element.setStyle("display",Function.from(this.options.display).call(this));
if(!this.hidden){this.showing=false;}if(a){a.setStyle("visibility","visible");}this.callChain();this.fireEvent("show",this.element);}.bind(this));this.start(c);
}else{this.callChain();this.fireEvent("complete",this.element);this.fireEvent("show",this.element);}}else{if(this.options.link=="chain"){this.chain(this.reveal.bind(this));
}else{if(this.options.link=="cancel"&&!this.showing){this.cancel();this.reveal();}}}return this;},toggle:function(){if(this.element.getStyle("display")=="none"){this.reveal();
}else{this.dissolve();}return this;},cancel:function(){this.parent.apply(this,arguments);this.element.style.cssText=this.cssText;this.hiding=false;this.showing=false;
return this;}});Element.Properties.reveal={set:function(a){this.get("reveal").cancel().setOptions(a);return this;},get:function(){var a=this.retrieve("reveal");
if(!a){a=new Fx.Reveal(this);this.store("reveal",a);}return a;}};Element.Properties.dissolve=Element.Properties.reveal;Element.implement({reveal:function(a){this.get("reveal").setOptions(a).reveal();
return this;},dissolve:function(a){this.get("reveal").setOptions(a).dissolve();return this;},nix:function(a){var b=Array.link(arguments,{destroy:Type.isBoolean,options:Type.isObject});
this.get("reveal").setOptions(a).dissolve().chain(function(){this[b.destroy?"destroy":"dispose"]();}.bind(this));return this;},wink:function(){var b=Array.link(arguments,{duration:Type.isNumber,options:Type.isObject});
var a=this.get("reveal").setOptions(b.options);a.reveal().chain(function(){(function(){a.dissolve();}).delay(b.duration||2000);});}});