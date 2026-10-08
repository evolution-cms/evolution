(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var noOptions={};var nonWS=/[^\s\u00a0]/;var Pos=CodeMirror.Pos;function firstNonWS(str){var found=str.search(nonWS);return found==-1?0:found;}
CodeMirror.commands.toggleComment=function(cm){cm.toggleComment();};CodeMirror.defineExtension("toggleComment",function(options){if(!options)options=noOptions;var cm=this;var minLine=Infinity,ranges=this.listSelections(),mode=null;for(var i=ranges.length-1;i>=0;i--){var from=ranges[i].from(),to=ranges[i].to();if(from.line>=minLine)continue;if(to.line>=minLine)to=Pos(minLine,0);minLine=from.line;if(mode==null){if(cm.uncomment(from,to,options))mode="un";else{cm.lineComment(from,to,options);mode="line";}}else if(mode=="un"){cm.uncomment(from,to,options);}else{cm.lineComment(from,to,options);}}});function probablyInsideString(cm,pos,line){return /\bstring\b/.test(cm.getTokenTypeAt(Pos(pos.line,0)))&&!/^[\'\"\`]/.test(line)}
function getMode(cm,pos){var mode=cm.getMode()
return mode.useInnerComments===false||!mode.innerMode?mode:cm.getModeAt(pos)}
CodeMirror.defineExtension("lineComment",function(from,to,options){if(!options)options=noOptions;var self=this,mode=getMode(self,from);var firstLine=self.getLine(from.line);if(firstLine==null||probablyInsideString(self,from,firstLine))return;var commentString=options.lineComment||mode.lineComment;if(!commentString){if(options.blockCommentStart||mode.blockCommentStart){options.fullLines=true;self.blockComment(from,to,options);}
return;}
var end=Math.min(to.ch!=0||to.line==from.line?to.line+1:to.line,self.lastLine()+1);var pad=options.padding==null?" ":options.padding;var blankLines=options.commentBlankLines||from.line==to.line;self.operation(function(){if(options.indent){var baseString=null;for(var i=from.line;i<end;++i){var line=self.getLine(i);var whitespace=line.slice(0,firstNonWS(line));if(baseString==null||baseString.length>whitespace.length){baseString=whitespace;}}
for(var i=from.line;i<end;++i){var line=self.getLine(i),cut=baseString.length;if(!blankLines&&!nonWS.test(line))continue;if(line.slice(0,cut)!=baseString)cut=firstNonWS(line);self.replaceRange(baseString+commentString+pad,Pos(i,0),Pos(i,cut));}}else{for(var i=from.line;i<end;++i){if(blankLines||nonWS.test(self.getLine(i)))
self.replaceRange(commentString+pad,Pos(i,0));}}});});CodeMirror.defineExtension("blockComment",function(from,to,options){if(!options)options=noOptions;var self=this,mode=getMode(self,from);var startString=options.blockCommentStart||mode.blockCommentStart;var endString=options.blockCommentEnd||mode.blockCommentEnd;if(!startString||!endString){if((options.lineComment||mode.lineComment)&&options.fullLines!=false)
self.lineComment(from,to,options);return;}
if(/\bcomment\b/.test(self.getTokenTypeAt(Pos(from.line,0))))return
var end=Math.min(to.line,self.lastLine());if(end!=from.line&&to.ch==0&&nonWS.test(self.getLine(end)))--end;var pad=options.padding==null?" ":options.padding;if(from.line>end)return;self.operation(function(){if(options.fullLines!=false){var lastLineHasText=nonWS.test(self.getLine(end));self.replaceRange(pad+endString,Pos(end));self.replaceRange(startString+pad,Pos(from.line,0));var lead=options.blockCommentLead||mode.blockCommentLead;if(lead!=null)for(var i=from.line+1;i<=end;++i)
if(i!=end||lastLineHasText)
self.replaceRange(lead+pad,Pos(i,0));}else{self.replaceRange(endString,to);self.replaceRange(startString,from);}});});CodeMirror.defineExtension("uncomment",function(from,to,options){if(!options)options=noOptions;var self=this,mode=getMode(self,from);var end=Math.min(to.ch!=0||to.line==from.line?to.line:to.line-1,self.lastLine()),start=Math.min(from.line,end);var lineString=options.lineComment||mode.lineComment,lines=[];var pad=options.padding==null?" ":options.padding,didSomething;lineComment:{if(!lineString)break lineComment;for(var i=start;i<=end;++i){var line=self.getLine(i);var found=line.indexOf(lineString);if(found>-1&&!/comment/.test(self.getTokenTypeAt(Pos(i,found+1))))found=-1;if(found==-1&&nonWS.test(line))break lineComment;if(found>-1&&nonWS.test(line.slice(0,found)))break lineComment;lines.push(line);}
self.operation(function(){for(var i=start;i<=end;++i){var line=lines[i-start];var pos=line.indexOf(lineString),endPos=pos+lineString.length;if(pos<0)continue;if(line.slice(endPos,endPos+pad.length)==pad)endPos+=pad.length;didSomething=true;self.replaceRange("",Pos(i,pos),Pos(i,endPos));}});if(didSomething)return true;}
var startString=options.blockCommentStart||mode.blockCommentStart;var endString=options.blockCommentEnd||mode.blockCommentEnd;if(!startString||!endString)return false;var lead=options.blockCommentLead||mode.blockCommentLead;var startLine=self.getLine(start),open=startLine.indexOf(startString)
if(open==-1)return false
var endLine=end==start?startLine:self.getLine(end)
var close=endLine.indexOf(endString,end==start?open+startString.length:0);var insideStart=Pos(start,open+1),insideEnd=Pos(end,close+1)
if(close==-1||!/comment/.test(self.getTokenTypeAt(insideStart))||!/comment/.test(self.getTokenTypeAt(insideEnd))||self.getRange(insideStart,insideEnd,"\n").indexOf(endString)>-1)
return false;var lastStart=startLine.lastIndexOf(startString,from.ch);var firstEnd=lastStart==-1?-1:startLine.slice(0,from.ch).indexOf(endString,lastStart+startString.length);if(lastStart!=-1&&firstEnd!=-1&&firstEnd+endString.length!=from.ch)return false;firstEnd=endLine.indexOf(endString,to.ch);var almostLastStart=endLine.slice(to.ch).lastIndexOf(startString,firstEnd-to.ch);lastStart=(firstEnd==-1||almostLastStart==-1)?-1:to.ch+almostLastStart;if(firstEnd!=-1&&lastStart!=-1&&lastStart!=to.ch)return false;self.operation(function(){self.replaceRange("",Pos(end,close-(pad&&endLine.slice(close-pad.length,close)==pad?pad.length:0)),Pos(end,close+endString.length));var openEnd=open+startString.length;if(pad&&startLine.slice(openEnd,openEnd+pad.length)==pad)openEnd+=pad.length;self.replaceRange("",Pos(start,open),Pos(start,openEnd));if(lead)for(var i=start+1;i<=end;++i){var line=self.getLine(i),found=line.indexOf(lead);if(found==-1||nonWS.test(line.slice(0,found)))continue;var foundEnd=found+lead.length;if(pad&&line.slice(foundEnd,foundEnd+pad.length)==pad)foundEnd+=pad.length;self.replaceRange("",Pos(i,found),Pos(i,foundEnd));}});return true;});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){function continueComment(cm){if(cm.getOption("disableInput"))return CodeMirror.Pass;var ranges=cm.listSelections(),mode,inserts=[];for(var i=0;i<ranges.length;i++){var pos=ranges[i].head
if(!/\bcomment\b/.test(cm.getTokenTypeAt(pos)))return CodeMirror.Pass;var modeHere=cm.getModeAt(pos)
if(!mode)mode=modeHere;else if(mode!=modeHere)return CodeMirror.Pass;var insert=null;if(mode.blockCommentStart&&mode.blockCommentContinue){var line=cm.getLine(pos.line).slice(0,pos.ch)
var end=line.lastIndexOf(mode.blockCommentEnd),found
if(end!=-1&&end==pos.ch-mode.blockCommentEnd.length){}else if((found=line.lastIndexOf(mode.blockCommentStart))>-1&&found>end){insert=line.slice(0,found)
if(/\S/.test(insert)){insert=""
for(var j=0;j<found;++j)insert+=" "}}else if((found=line.indexOf(mode.blockCommentContinue))>-1&&!/\S/.test(line.slice(0,found))){insert=line.slice(0,found)}
if(insert!=null)insert+=mode.blockCommentContinue}
if(insert==null&&mode.lineComment&&continueLineCommentEnabled(cm)){var line=cm.getLine(pos.line),found=line.indexOf(mode.lineComment);if(found>-1){insert=line.slice(0,found);if(/\S/.test(insert))insert=null;else insert+=mode.lineComment+line.slice(found+mode.lineComment.length).match(/^\s*/)[0];}}
if(insert==null)return CodeMirror.Pass;inserts[i]="\n"+insert;}
cm.operation(function(){for(var i=ranges.length-1;i>=0;i--)
cm.replaceRange(inserts[i],ranges[i].from(),ranges[i].to(),"+insert");});}
function continueLineCommentEnabled(cm){var opt=cm.getOption("continueComments");if(opt&&typeof opt=="object")
return opt.continueLineComment!==false;return true;}
CodeMirror.defineOption("continueComments",null,function(cm,val,prev){if(prev&&prev!=CodeMirror.Init)
cm.removeKeyMap("continueComment");if(val){var key="Enter";if(typeof val=="string")
key=val;else if(typeof val=="object"&&val.key)
key=val.key;var map={name:"continueComment"};map[key]=continueComment;cm.addKeyMap(map);}});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){function dialogDiv(cm,template,bottom){var wrap=cm.getWrapperElement();var dialog;dialog=wrap.appendChild(document.createElement("div"));if(bottom)
dialog.className="CodeMirror-dialog CodeMirror-dialog-bottom";else
dialog.className="CodeMirror-dialog CodeMirror-dialog-top";if(typeof template=="string"){dialog.innerHTML=template;}else{dialog.appendChild(template);}
return dialog;}
function closeNotification(cm,newVal){if(cm.state.currentNotificationClose)
cm.state.currentNotificationClose();cm.state.currentNotificationClose=newVal;}
CodeMirror.defineExtension("openDialog",function(template,callback,options){if(!options)options={};closeNotification(this,null);var dialog=dialogDiv(this,template,options.bottom);var closed=false,me=this;function close(newVal){if(typeof newVal=='string'){inp.value=newVal;}else{if(closed)return;closed=true;dialog.parentNode.removeChild(dialog);me.focus();if(options.onClose)options.onClose(dialog);}}
var inp=dialog.getElementsByTagName("input")[0],button;if(inp){inp.focus();if(options.value){inp.value=options.value;if(options.selectValueOnOpen!==false){inp.select();}}
if(options.onInput)
CodeMirror.on(inp,"input",function(e){options.onInput(e,inp.value,close);});if(options.onKeyUp)
CodeMirror.on(inp,"keyup",function(e){options.onKeyUp(e,inp.value,close);});CodeMirror.on(inp,"keydown",function(e){if(options&&options.onKeyDown&&options.onKeyDown(e,inp.value,close)){return;}
if(e.keyCode==27||(options.closeOnEnter!==false&&e.keyCode==13)){inp.blur();CodeMirror.e_stop(e);close();}
if(e.keyCode==13)callback(inp.value,e);});if(options.closeOnBlur!==false)CodeMirror.on(inp,"blur",close);}else if(button=dialog.getElementsByTagName("button")[0]){CodeMirror.on(button,"click",function(){close();me.focus();});if(options.closeOnBlur!==false)CodeMirror.on(button,"blur",close);button.focus();}
return close;});CodeMirror.defineExtension("openConfirm",function(template,callbacks,options){closeNotification(this,null);var dialog=dialogDiv(this,template,options&&options.bottom);var buttons=dialog.getElementsByTagName("button");var closed=false,me=this,blurring=1;function close(){if(closed)return;closed=true;dialog.parentNode.removeChild(dialog);me.focus();}
buttons[0].focus();for(var i=0;i<buttons.length;++i){var b=buttons[i];(function(callback){CodeMirror.on(b,"click",function(e){CodeMirror.e_preventDefault(e);close();if(callback)callback(me);});})(callbacks[i]);CodeMirror.on(b,"blur",function(){--blurring;setTimeout(function(){if(blurring<=0)close();},200);});CodeMirror.on(b,"focus",function(){++blurring;});}});CodeMirror.defineExtension("openNotification",function(template,options){closeNotification(this,close);var dialog=dialogDiv(this,template,options&&options.bottom);var closed=false,doneTimer;var duration=options&&typeof options.duration!=="undefined"?options.duration:5000;function close(){if(closed)return;closed=true;clearTimeout(doneTimer);dialog.parentNode.removeChild(dialog);}
CodeMirror.on(dialog,'click',function(e){CodeMirror.e_preventDefault(e);close();});if(duration)
doneTimer=setTimeout(close,duration);return close;});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"))
else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod)
else
mod(CodeMirror)})(function(CodeMirror){"use strict"
CodeMirror.defineOption("autoRefresh",false,function(cm,val){if(cm.state.autoRefresh){stopListening(cm,cm.state.autoRefresh)
cm.state.autoRefresh=null}
if(val&&cm.display.wrapper.offsetHeight==0)
startListening(cm,cm.state.autoRefresh={delay:val.delay||250})})
function startListening(cm,state){function check(){if(cm.display.wrapper.offsetHeight){stopListening(cm,state)
if(cm.display.lastWrapHeight!=cm.display.wrapper.clientHeight)
cm.refresh()}else{state.timeout=setTimeout(check,state.delay)}}
state.timeout=setTimeout(check,state.delay)
state.hurry=function(){clearTimeout(state.timeout)
state.timeout=setTimeout(check,50)}
CodeMirror.on(window,"mouseup",state.hurry)
CodeMirror.on(window,"keyup",state.hurry)}
function stopListening(_cm,state){clearTimeout(state.timeout)
CodeMirror.off(window,"mouseup",state.hurry)
CodeMirror.off(window,"keyup",state.hurry)}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineOption("fullScreen",false,function(cm,val,old){if(old==CodeMirror.Init)old=false;if(!old==!val)return;if(val)setFullscreen(cm);else setNormal(cm);});function setFullscreen(cm){var wrap=cm.getWrapperElement();cm.state.fullScreenRestore={scrollTop:window.pageYOffset,scrollLeft:window.pageXOffset,width:wrap.style.width,height:wrap.style.height};wrap.style.width="";wrap.style.height="auto";wrap.className+=" CodeMirror-fullscreen";document.documentElement.style.overflow="hidden";cm.refresh();}
function setNormal(cm){var wrap=cm.getWrapperElement();wrap.className=wrap.className.replace(/\s*CodeMirror-fullscreen\b/,"");document.documentElement.style.overflow="";var info=cm.state.fullScreenRestore;wrap.style.width=info.width;wrap.style.height=info.height;window.scrollTo(info.scrollLeft,info.scrollTop);cm.refresh();}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){CodeMirror.defineExtension("addPanel",function(node,options){options=options||{};if(!this.state.panels)initPanels(this);var info=this.state.panels;var wrapper=info.wrapper;var cmWrapper=this.getWrapperElement();if(options.after instanceof Panel&&!options.after.cleared){wrapper.insertBefore(node,options.before.node.nextSibling);}else if(options.before instanceof Panel&&!options.before.cleared){wrapper.insertBefore(node,options.before.node);}else if(options.replace instanceof Panel&&!options.replace.cleared){wrapper.insertBefore(node,options.replace.node);options.replace.clear();}else if(options.position=="bottom"){wrapper.appendChild(node);}else if(options.position=="before-bottom"){wrapper.insertBefore(node,cmWrapper.nextSibling);}else if(options.position=="after-top"){wrapper.insertBefore(node,cmWrapper);}else{wrapper.insertBefore(node,wrapper.firstChild);}
var height=(options&&options.height)||node.offsetHeight;this._setSize(null,info.heightLeft-=height);info.panels++;if(options.stable&&isAtTop(this,node))
this.scrollTo(null,this.getScrollInfo().top+height)
return new Panel(this,node,options,height);});function Panel(cm,node,options,height){this.cm=cm;this.node=node;this.options=options;this.height=height;this.cleared=false;}
Panel.prototype.clear=function(){if(this.cleared)return;this.cleared=true;var info=this.cm.state.panels;this.cm._setSize(null,info.heightLeft+=this.height);if(this.options.stable&&isAtTop(this.cm,this.node))
this.cm.scrollTo(null,this.cm.getScrollInfo().top-this.height)
info.wrapper.removeChild(this.node);if(--info.panels==0)removePanels(this.cm);};Panel.prototype.changed=function(height){var newHeight=height==null?this.node.offsetHeight:height;var info=this.cm.state.panels;this.cm._setSize(null,info.heightLeft-=(newHeight-this.height));this.height=newHeight;};function initPanels(cm){var wrap=cm.getWrapperElement();var style=window.getComputedStyle?window.getComputedStyle(wrap):wrap.currentStyle;var height=parseInt(style.height);var info=cm.state.panels={setHeight:wrap.style.height,heightLeft:height,panels:0,wrapper:document.createElement("div")};wrap.parentNode.insertBefore(info.wrapper,wrap);var hasFocus=cm.hasFocus();info.wrapper.appendChild(wrap);if(hasFocus)cm.focus();cm._setSize=cm.setSize;if(height!=null)cm.setSize=function(width,newHeight){if(newHeight==null)return this._setSize(width,newHeight);info.setHeight=newHeight;if(typeof newHeight!="number"){var px=/^(\d+\.?\d*)px$/.exec(newHeight);if(px){newHeight=Number(px[1]);}else{info.wrapper.style.height=newHeight;newHeight=info.wrapper.offsetHeight;info.wrapper.style.height="";}}
cm._setSize(width,info.heightLeft+=(newHeight-height));height=newHeight;};}
function removePanels(cm){var info=cm.state.panels;cm.state.panels=null;var wrap=cm.getWrapperElement();info.wrapper.parentNode.replaceChild(wrap,info.wrapper);wrap.style.height=info.setHeight;cm.setSize=cm._setSize;cm.setSize();}
function isAtTop(cm,dom){for(var sibling=dom.nextSibling;sibling;sibling=sibling.nextSibling)
if(sibling==cm.getWrapperElement())return true
return false}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){CodeMirror.defineOption("placeholder","",function(cm,val,old){var prev=old&&old!=CodeMirror.Init;if(val&&!prev){cm.on("blur",onBlur);cm.on("change",onChange);cm.on("swapDoc",onChange);onChange(cm);}else if(!val&&prev){cm.off("blur",onBlur);cm.off("change",onChange);cm.off("swapDoc",onChange);clearPlaceholder(cm);var wrapper=cm.getWrapperElement();wrapper.className=wrapper.className.replace(" CodeMirror-empty","");}
if(val&&!cm.hasFocus())onBlur(cm);});function clearPlaceholder(cm){if(cm.state.placeholder){cm.state.placeholder.parentNode.removeChild(cm.state.placeholder);cm.state.placeholder=null;}}
function setPlaceholder(cm){clearPlaceholder(cm);var elt=cm.state.placeholder=document.createElement("pre");elt.style.cssText="height: 0; overflow: visible";elt.className="CodeMirror-placeholder";var placeHolder=cm.getOption("placeholder")
if(typeof placeHolder=="string")placeHolder=document.createTextNode(placeHolder)
elt.appendChild(placeHolder)
cm.display.lineSpace.insertBefore(elt,cm.display.lineSpace.firstChild);}
function onBlur(cm){if(isEmpty(cm))setPlaceholder(cm);}
function onChange(cm){var wrapper=cm.getWrapperElement(),empty=isEmpty(cm);wrapper.className=wrapper.className.replace(" CodeMirror-empty","")+(empty?" CodeMirror-empty":"");if(empty)setPlaceholder(cm);else clearPlaceholder(cm);}
function isEmpty(cm){return(cm.lineCount()===1)&&(cm.getLine(0)==="");}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineOption("rulers",false,function(cm,val){if(cm.state.rulerDiv){cm.state.rulerDiv.parentElement.removeChild(cm.state.rulerDiv)
cm.state.rulerDiv=null
cm.off("refresh",drawRulers)}
if(val&&val.length){cm.state.rulerDiv=cm.display.lineSpace.parentElement.insertBefore(document.createElement("div"),cm.display.lineSpace)
cm.state.rulerDiv.className="CodeMirror-rulers"
drawRulers(cm)
cm.on("refresh",drawRulers)}});function drawRulers(cm){cm.state.rulerDiv.textContent=""
var val=cm.getOption("rulers");var cw=cm.defaultCharWidth();var left=cm.charCoords(CodeMirror.Pos(cm.firstLine(),0),"div").left;cm.state.rulerDiv.style.minHeight=(cm.display.scroller.offsetHeight+30)+"px";for(var i=0;i<val.length;i++){var elt=document.createElement("div");elt.className="CodeMirror-ruler";var col,conf=val[i];if(typeof conf=="number"){col=conf;}else{col=conf.column;if(conf.className)elt.className+=" "+conf.className;if(conf.color)elt.style.borderColor=conf.color;if(conf.lineStyle)elt.style.borderLeftStyle=conf.lineStyle;if(conf.width)elt.style.borderLeftWidth=conf.width;}
elt.style.left=(left+col*cw)+"px";cm.state.rulerDiv.appendChild(elt)}}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){var defaults={pairs:"()[]{}''\"\"",triples:"",explode:"[]{}"};var Pos=CodeMirror.Pos;CodeMirror.defineOption("autoCloseBrackets",false,function(cm,val,old){if(old&&old!=CodeMirror.Init){cm.removeKeyMap(keyMap);cm.state.closeBrackets=null;}
if(val){ensureBound(getOption(val,"pairs"))
cm.state.closeBrackets=val;cm.addKeyMap(keyMap);}});function getOption(conf,name){if(name=="pairs"&&typeof conf=="string")return conf;if(typeof conf=="object"&&conf[name]!=null)return conf[name];return defaults[name];}
var keyMap={Backspace:handleBackspace,Enter:handleEnter};function ensureBound(chars){for(var i=0;i<chars.length;i++){var ch=chars.charAt(i),key="'"+ch+"'"
if(!keyMap[key])keyMap[key]=handler(ch)}}
ensureBound(defaults.pairs+"`")
function handler(ch){return function(cm){return handleChar(cm,ch);};}
function getConfig(cm){var deflt=cm.state.closeBrackets;if(!deflt||deflt.override)return deflt;var mode=cm.getModeAt(cm.getCursor());return mode.closeBrackets||deflt;}
function handleBackspace(cm){var conf=getConfig(cm);if(!conf||cm.getOption("disableInput"))return CodeMirror.Pass;var pairs=getOption(conf,"pairs");var ranges=cm.listSelections();for(var i=0;i<ranges.length;i++){if(!ranges[i].empty())return CodeMirror.Pass;var around=charsAround(cm,ranges[i].head);if(!around||pairs.indexOf(around)%2!=0)return CodeMirror.Pass;}
for(var i=ranges.length-1;i>=0;i--){var cur=ranges[i].head;cm.replaceRange("",Pos(cur.line,cur.ch-1),Pos(cur.line,cur.ch+1),"+delete");}}
function handleEnter(cm){var conf=getConfig(cm);var explode=conf&&getOption(conf,"explode");if(!explode||cm.getOption("disableInput"))return CodeMirror.Pass;var ranges=cm.listSelections();for(var i=0;i<ranges.length;i++){if(!ranges[i].empty())return CodeMirror.Pass;var around=charsAround(cm,ranges[i].head);if(!around||explode.indexOf(around)%2!=0)return CodeMirror.Pass;}
cm.operation(function(){var linesep=cm.lineSeparator()||"\n";cm.replaceSelection(linesep+linesep,null);cm.execCommand("goCharLeft");ranges=cm.listSelections();for(var i=0;i<ranges.length;i++){var line=ranges[i].head.line;cm.indentLine(line,null,true);cm.indentLine(line+1,null,true);}});}
function contractSelection(sel){var inverted=CodeMirror.cmpPos(sel.anchor,sel.head)>0;return{anchor:new Pos(sel.anchor.line,sel.anchor.ch+(inverted?-1:1)),head:new Pos(sel.head.line,sel.head.ch+(inverted?1:-1))};}
function handleChar(cm,ch){var conf=getConfig(cm);if(!conf||cm.getOption("disableInput"))return CodeMirror.Pass;var pairs=getOption(conf,"pairs");var pos=pairs.indexOf(ch);if(pos==-1)return CodeMirror.Pass;var triples=getOption(conf,"triples");var identical=pairs.charAt(pos+1)==ch;var ranges=cm.listSelections();var opening=pos%2==0;var type;for(var i=0;i<ranges.length;i++){var range=ranges[i],cur=range.head,curType;var next=cm.getRange(cur,Pos(cur.line,cur.ch+1));if(opening&&!range.empty()){curType="surround";}else if((identical||!opening)&&next==ch){if(identical&&stringStartsAfter(cm,cur))
curType="both";else if(triples.indexOf(ch)>=0&&cm.getRange(cur,Pos(cur.line,cur.ch+3))==ch+ch+ch)
curType="skipThree";else
curType="skip";}else if(identical&&cur.ch>1&&triples.indexOf(ch)>=0&&cm.getRange(Pos(cur.line,cur.ch-2),cur)==ch+ch&&(cur.ch<=2||cm.getRange(Pos(cur.line,cur.ch-3),Pos(cur.line,cur.ch-2))!=ch)){curType="addFour";}else if(identical){var prev=cur.ch==0?" ":cm.getRange(Pos(cur.line,cur.ch-1),cur)
if(!CodeMirror.isWordChar(next)&&prev!=ch&&!CodeMirror.isWordChar(prev))curType="both";else return CodeMirror.Pass;}else if(opening&&(cm.getLine(cur.line).length==cur.ch||isClosingBracket(next,pairs)||/\s/.test(next))){curType="both";}else{return CodeMirror.Pass;}
if(!type)type=curType;else if(type!=curType)return CodeMirror.Pass;}
var left=pos%2?pairs.charAt(pos-1):ch;var right=pos%2?ch:pairs.charAt(pos+1);cm.operation(function(){if(type=="skip"){cm.execCommand("goCharRight");}else if(type=="skipThree"){for(var i=0;i<3;i++)
cm.execCommand("goCharRight");}else if(type=="surround"){var sels=cm.getSelections();for(var i=0;i<sels.length;i++)
sels[i]=left+sels[i]+right;cm.replaceSelections(sels,"around");sels=cm.listSelections().slice();for(var i=0;i<sels.length;i++)
sels[i]=contractSelection(sels[i]);cm.setSelections(sels);}else if(type=="both"){cm.replaceSelection(left+right,null);cm.triggerElectric(left+right);cm.execCommand("goCharLeft");}else if(type=="addFour"){cm.replaceSelection(left+left+left+left,"before");cm.execCommand("goCharRight");}});}
function isClosingBracket(ch,pairs){var pos=pairs.lastIndexOf(ch);return pos>-1&&pos%2==1;}
function charsAround(cm,pos){var str=cm.getRange(Pos(pos.line,pos.ch-1),Pos(pos.line,pos.ch+1));return str.length==2?str:null;}
function stringStartsAfter(cm,pos){var token=cm.getTokenAt(Pos(pos.line,pos.ch+1))
return /\bstring/.test(token.type)&&token.start==pos.ch&&(pos.ch==0||!/\bstring/.test(cm.getTokenTypeAt(pos)))}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("../fold/xml-fold"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","../fold/xml-fold"],mod);else
mod(CodeMirror);})(function(CodeMirror){CodeMirror.defineOption("autoCloseTags",false,function(cm,val,old){if(old!=CodeMirror.Init&&old)
cm.removeKeyMap("autoCloseTags");if(!val)return;var map={name:"autoCloseTags"};if(typeof val!="object"||val.whenClosing)
map["'/'"]=function(cm){return autoCloseSlash(cm);};if(typeof val!="object"||val.whenOpening)
map["'>'"]=function(cm){return autoCloseGT(cm);};cm.addKeyMap(map);});var htmlDontClose=["area","base","br","col","command","embed","hr","img","input","keygen","link","meta","param","source","track","wbr"];var htmlIndent=["applet","blockquote","body","button","div","dl","fieldset","form","frameset","h1","h2","h3","h4","h5","h6","head","html","iframe","layer","legend","object","ol","p","select","table","ul"];function autoCloseGT(cm){if(cm.getOption("disableInput"))return CodeMirror.Pass;var ranges=cm.listSelections(),replacements=[];var opt=cm.getOption("autoCloseTags");for(var i=0;i<ranges.length;i++){if(!ranges[i].empty())return CodeMirror.Pass;var pos=ranges[i].head,tok=cm.getTokenAt(pos);var inner=CodeMirror.innerMode(cm.getMode(),tok.state),state=inner.state;if(inner.mode.name!="xml"||!state.tagName)return CodeMirror.Pass;var html=inner.mode.configuration=="html";var dontCloseTags=(typeof opt=="object"&&opt.dontCloseTags)||(html&&htmlDontClose);var indentTags=(typeof opt=="object"&&opt.indentTags)||(html&&htmlIndent);var tagName=state.tagName;if(tok.end>pos.ch)tagName=tagName.slice(0,tagName.length-tok.end+pos.ch);var lowerTagName=tagName.toLowerCase();if(!tagName||tok.type=="string"&&(tok.end!=pos.ch||!/[\"\']/.test(tok.string.charAt(tok.string.length-1))||tok.string.length==1)||tok.type=="tag"&&state.type=="closeTag"||tok.string.indexOf("/")==(tok.string.length-1)||dontCloseTags&&indexOf(dontCloseTags,lowerTagName)>-1||closingTagExists(cm,tagName,pos,state,true))
return CodeMirror.Pass;var indent=indentTags&&indexOf(indentTags,lowerTagName)>-1;replacements[i]={indent:indent,text:">"+(indent?"\n\n":"")+"</"+tagName+">",newPos:indent?CodeMirror.Pos(pos.line+1,0):CodeMirror.Pos(pos.line,pos.ch+1)};}
var dontIndentOnAutoClose=(typeof opt=="object"&&opt.dontIndentOnAutoClose);for(var i=ranges.length-1;i>=0;i--){var info=replacements[i];cm.replaceRange(info.text,ranges[i].head,ranges[i].anchor,"+insert");var sel=cm.listSelections().slice(0);sel[i]={head:info.newPos,anchor:info.newPos};cm.setSelections(sel);if(!dontIndentOnAutoClose&&info.indent){cm.indentLine(info.newPos.line,null,true);cm.indentLine(info.newPos.line+1,null,true);}}}
function autoCloseCurrent(cm,typingSlash){var ranges=cm.listSelections(),replacements=[];var head=typingSlash?"/":"</";var opt=cm.getOption("autoCloseTags");var dontIndentOnAutoClose=(typeof opt=="object"&&opt.dontIndentOnSlash);for(var i=0;i<ranges.length;i++){if(!ranges[i].empty())return CodeMirror.Pass;var pos=ranges[i].head,tok=cm.getTokenAt(pos);var inner=CodeMirror.innerMode(cm.getMode(),tok.state),state=inner.state;if(typingSlash&&(tok.type=="string"||tok.string.charAt(0)!="<"||tok.start!=pos.ch-1))
return CodeMirror.Pass;var replacement;if(inner.mode.name!="xml"){if(cm.getMode().name=="htmlmixed"&&inner.mode.name=="javascript")
replacement=head+"script";else if(cm.getMode().name=="htmlmixed"&&inner.mode.name=="css")
replacement=head+"style";else
return CodeMirror.Pass;}else{if(!state.context||!state.context.tagName||closingTagExists(cm,state.context.tagName,pos,state))
return CodeMirror.Pass;replacement=head+state.context.tagName;}
if(cm.getLine(pos.line).charAt(tok.end)!=">")replacement+=">";replacements[i]=replacement;}
cm.replaceSelections(replacements);ranges=cm.listSelections();if(!dontIndentOnAutoClose){for(var i=0;i<ranges.length;i++)
if(i==ranges.length-1||ranges[i].head.line<ranges[i+1].head.line)
cm.indentLine(ranges[i].head.line);}}
function autoCloseSlash(cm){if(cm.getOption("disableInput"))return CodeMirror.Pass;return autoCloseCurrent(cm,true);}
CodeMirror.commands.closeTag=function(cm){return autoCloseCurrent(cm);};function indexOf(collection,elt){if(collection.indexOf)return collection.indexOf(elt);for(var i=0,e=collection.length;i<e;++i)
if(collection[i]==elt)return i;return-1;}
function closingTagExists(cm,tagName,pos,state,newTag){if(!CodeMirror.scanForClosingTag)return false;var end=Math.min(cm.lastLine()+1,pos.line+500);var nextClose=CodeMirror.scanForClosingTag(cm,pos,null,end);if(!nextClose||nextClose.tag!=tagName)return false;var cx=state.context;for(var onCx=newTag?1:0;cx&&cx.tagName==tagName;cx=cx.prev)++onCx;pos=nextClose.to;for(var i=1;i<onCx;i++){var next=CodeMirror.scanForClosingTag(cm,pos,null,end);if(!next||next.tag!=tagName)return false;pos=next.to;}
return true;}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var listRE=/^(\s*)(>[> ]*|[*+-] \[[x ]\]\s|[*+-]\s|(\d+)([.)]))(\s*)/,emptyListRE=/^(\s*)(>[> ]*|[*+-] \[[x ]\]|[*+-]|(\d+)[.)])(\s*)$/,unorderedListRE=/[*+-]\s/;CodeMirror.commands.newlineAndIndentContinueMarkdownList=function(cm){if(cm.getOption("disableInput"))return CodeMirror.Pass;var ranges=cm.listSelections(),replacements=[];for(var i=0;i<ranges.length;i++){var pos=ranges[i].head;var eolState=cm.getStateAfter(pos.line);var inList=eolState.list!==false;var inQuote=eolState.quote!==0;var line=cm.getLine(pos.line),match=listRE.exec(line);var cursorBeforeBullet=/^\s*$/.test(line.slice(0,pos.ch));if(!ranges[i].empty()||(!inList&&!inQuote)||!match||cursorBeforeBullet){cm.execCommand("newlineAndIndent");return;}
if(emptyListRE.test(line)){if(!/>\s*$/.test(line))cm.replaceRange("",{line:pos.line,ch:0},{line:pos.line,ch:pos.ch+1});replacements[i]="\n";}else{var indent=match[1],after=match[5];var numbered=!(unorderedListRE.test(match[2])||match[2].indexOf(">")>=0);var bullet=numbered?(parseInt(match[3],10)+1)+match[4]:match[2].replace("x"," ");replacements[i]="\n"+indent+bullet+after;if(numbered)incrementRemainingMarkdownListNumbers(cm,pos);}}
cm.replaceSelections(replacements);};function incrementRemainingMarkdownListNumbers(cm,pos){var startLine=pos.line,lookAhead=0,skipCount=0;var startItem=listRE.exec(cm.getLine(startLine)),startIndent=startItem[1];do{lookAhead+=1;var nextLineNumber=startLine+lookAhead;var nextLine=cm.getLine(nextLineNumber),nextItem=listRE.exec(nextLine);if(nextItem){var nextIndent=nextItem[1];var newNumber=(parseInt(startItem[3],10)+lookAhead-skipCount);var nextNumber=(parseInt(nextItem[3],10)),itemNumber=nextNumber;if(startIndent===nextIndent){if(newNumber===nextNumber)itemNumber=nextNumber+1;if(newNumber>nextNumber)itemNumber=newNumber+1;cm.replaceRange(nextLine.replace(listRE,nextIndent+itemNumber+nextItem[4]+nextItem[5]),{line:nextLineNumber,ch:0},{line:nextLineNumber,ch:nextLine.length});}else{if(startIndent.length>nextIndent.length)return;if((startIndent.length<nextIndent.length)&&(lookAhead===1))return;skipCount+=1;}}}while(nextItem);}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){var ie_lt8=/MSIE \d/.test(navigator.userAgent)&&(document.documentMode==null||document.documentMode<8);var Pos=CodeMirror.Pos;var matching={"(":")>",")":"(<","[":"]>","]":"[<","{":"}>","}":"{<"};function findMatchingBracket(cm,where,config){var line=cm.getLineHandle(where.line),pos=where.ch-1;var afterCursor=config&&config.afterCursor
if(afterCursor==null)
afterCursor=/(^| )cm-fat-cursor($| )/.test(cm.getWrapperElement().className)
var match=(!afterCursor&&pos>=0&&matching[line.text.charAt(pos)])||matching[line.text.charAt(++pos)];if(!match)return null;var dir=match.charAt(1)==">"?1:-1;if(config&&config.strict&&(dir>0)!=(pos==where.ch))return null;var style=cm.getTokenTypeAt(Pos(where.line,pos+1));var found=scanForBracket(cm,Pos(where.line,pos+(dir>0?1:0)),dir,style||null,config);if(found==null)return null;return{from:Pos(where.line,pos),to:found&&found.pos,match:found&&found.ch==match.charAt(0),forward:dir>0};}
function scanForBracket(cm,where,dir,style,config){var maxScanLen=(config&&config.maxScanLineLength)||10000;var maxScanLines=(config&&config.maxScanLines)||1000;var stack=[];var re=config&&config.bracketRegex?config.bracketRegex:/[(){}[\]]/;var lineEnd=dir>0?Math.min(where.line+maxScanLines,cm.lastLine()+1):Math.max(cm.firstLine()-1,where.line-maxScanLines);for(var lineNo=where.line;lineNo!=lineEnd;lineNo+=dir){var line=cm.getLine(lineNo);if(!line)continue;var pos=dir>0?0:line.length-1,end=dir>0?line.length:-1;if(line.length>maxScanLen)continue;if(lineNo==where.line)pos=where.ch-(dir<0?1:0);for(;pos!=end;pos+=dir){var ch=line.charAt(pos);if(re.test(ch)&&(style===undefined||cm.getTokenTypeAt(Pos(lineNo,pos+1))==style)){var match=matching[ch];if((match.charAt(1)==">")==(dir>0))stack.push(ch);else if(!stack.length)return{pos:Pos(lineNo,pos),ch:ch};else stack.pop();}}}
return lineNo-dir==(dir>0?cm.lastLine():cm.firstLine())?false:null;}
function matchBrackets(cm,autoclear,config){var maxHighlightLen=cm.state.matchBrackets.maxHighlightLineLength||1000;var marks=[],ranges=cm.listSelections();for(var i=0;i<ranges.length;i++){var match=ranges[i].empty()&&findMatchingBracket(cm,ranges[i].head,config);if(match&&cm.getLine(match.from.line).length<=maxHighlightLen){var style=match.match?"CodeMirror-matchingbracket":"CodeMirror-nonmatchingbracket";marks.push(cm.markText(match.from,Pos(match.from.line,match.from.ch+1),{className:style}));if(match.to&&cm.getLine(match.to.line).length<=maxHighlightLen)
marks.push(cm.markText(match.to,Pos(match.to.line,match.to.ch+1),{className:style}));}}
if(marks.length){if(ie_lt8&&cm.state.focused)cm.focus();var clear=function(){cm.operation(function(){for(var i=0;i<marks.length;i++)marks[i].clear();});};if(autoclear)setTimeout(clear,800);else return clear;}}
var currentlyHighlighted=null;function doMatchBrackets(cm){cm.operation(function(){if(currentlyHighlighted){currentlyHighlighted();currentlyHighlighted=null;}
currentlyHighlighted=matchBrackets(cm,false,cm.state.matchBrackets);});}
CodeMirror.defineOption("matchBrackets",false,function(cm,val,old){if(old&&old!=CodeMirror.Init){cm.off("cursorActivity",doMatchBrackets);if(currentlyHighlighted){currentlyHighlighted();currentlyHighlighted=null;}}
if(val){cm.state.matchBrackets=typeof val=="object"?val:{};cm.on("cursorActivity",doMatchBrackets);}});CodeMirror.defineExtension("matchBrackets",function(){matchBrackets(this,true);});CodeMirror.defineExtension("findMatchingBracket",function(pos,config,oldConfig){if(oldConfig||typeof config=="boolean"){if(!oldConfig){config=config?{strict:true}:null}else{oldConfig.strict=config
config=oldConfig}}
return findMatchingBracket(this,pos,config)});CodeMirror.defineExtension("scanForBracket",function(pos,dir,style,config){return scanForBracket(this,pos,dir,style,config);});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("../fold/xml-fold"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","../fold/xml-fold"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineOption("matchTags",false,function(cm,val,old){if(old&&old!=CodeMirror.Init){cm.off("cursorActivity",doMatchTags);cm.off("viewportChange",maybeUpdateMatch);clear(cm);}
if(val){cm.state.matchBothTags=typeof val=="object"&&val.bothTags;cm.on("cursorActivity",doMatchTags);cm.on("viewportChange",maybeUpdateMatch);doMatchTags(cm);}});function clear(cm){if(cm.state.tagHit)cm.state.tagHit.clear();if(cm.state.tagOther)cm.state.tagOther.clear();cm.state.tagHit=cm.state.tagOther=null;}
function doMatchTags(cm){cm.state.failedTagMatch=false;cm.operation(function(){clear(cm);if(cm.somethingSelected())return;var cur=cm.getCursor(),range=cm.getViewport();range.from=Math.min(range.from,cur.line);range.to=Math.max(cur.line+1,range.to);var match=CodeMirror.findMatchingTag(cm,cur,range);if(!match)return;if(cm.state.matchBothTags){var hit=match.at=="open"?match.open:match.close;if(hit)cm.state.tagHit=cm.markText(hit.from,hit.to,{className:"CodeMirror-matchingtag"});}
var other=match.at=="close"?match.open:match.close;if(other)
cm.state.tagOther=cm.markText(other.from,other.to,{className:"CodeMirror-matchingtag"});else
cm.state.failedTagMatch=true;});}
function maybeUpdateMatch(cm){if(cm.state.failedTagMatch)doMatchTags(cm);}
CodeMirror.commands.toMatchingTag=function(cm){var found=CodeMirror.findMatchingTag(cm,cm.getCursor());if(found){var other=found.at=="close"?found.open:found.close;if(other)cm.extendSelection(other.to,other.from);}};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){CodeMirror.defineOption("showTrailingSpace",false,function(cm,val,prev){if(prev==CodeMirror.Init)prev=false;if(prev&&!val)
cm.removeOverlay("trailingspace");else if(!prev&&val)
cm.addOverlay({token:function(stream){for(var l=stream.string.length,i=l;i&&/\s/.test(stream.string.charAt(i-1));--i){}
if(i>stream.pos){stream.pos=i;return null;}
stream.pos=l;return"trailingspace";},name:"trailingspace"});});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.registerHelper("fold","brace",function(cm,start){var line=start.line,lineText=cm.getLine(line);var tokenType;function findOpening(openCh){for(var at=start.ch,pass=0;;){var found=at<=0?-1:lineText.lastIndexOf(openCh,at-1);if(found==-1){if(pass==1)break;pass=1;at=lineText.length;continue;}
if(pass==1&&found<start.ch)break;tokenType=cm.getTokenTypeAt(CodeMirror.Pos(line,found+1));if(!/^(comment|string)/.test(tokenType))return found+1;at=found-1;}}
var startToken="{",endToken="}",startCh=findOpening("{");if(startCh==null){startToken="[",endToken="]";startCh=findOpening("[");}
if(startCh==null)return;var count=1,lastLine=cm.lastLine(),end,endCh;outer:for(var i=line;i<=lastLine;++i){var text=cm.getLine(i),pos=i==line?startCh:0;for(;;){var nextOpen=text.indexOf(startToken,pos),nextClose=text.indexOf(endToken,pos);if(nextOpen<0)nextOpen=text.length;if(nextClose<0)nextClose=text.length;pos=Math.min(nextOpen,nextClose);if(pos==text.length)break;if(cm.getTokenTypeAt(CodeMirror.Pos(i,pos+1))==tokenType){if(pos==nextOpen)++count;else if(!--count){end=i;endCh=pos;break outer;}}
++pos;}}
if(end==null||line==end&&endCh==startCh)return;return{from:CodeMirror.Pos(line,startCh),to:CodeMirror.Pos(end,endCh)};});CodeMirror.registerHelper("fold","import",function(cm,start){function hasImport(line){if(line<cm.firstLine()||line>cm.lastLine())return null;var start=cm.getTokenAt(CodeMirror.Pos(line,1));if(!/\S/.test(start.string))start=cm.getTokenAt(CodeMirror.Pos(line,start.end+1));if(start.type!="keyword"||start.string!="import")return null;for(var i=line,e=Math.min(cm.lastLine(),line+10);i<=e;++i){var text=cm.getLine(i),semi=text.indexOf(";");if(semi!=-1)return{startCh:start.end,end:CodeMirror.Pos(i,semi)};}}
var startLine=start.line,has=hasImport(startLine),prev;if(!has||hasImport(startLine-1)||((prev=hasImport(startLine-2))&&prev.end.line==startLine-1))
return null;for(var end=has.end;;){var next=hasImport(end.line+1);if(next==null)break;end=next.end;}
return{from:cm.clipPos(CodeMirror.Pos(startLine,has.startCh+1)),to:end};});CodeMirror.registerHelper("fold","include",function(cm,start){function hasInclude(line){if(line<cm.firstLine()||line>cm.lastLine())return null;var start=cm.getTokenAt(CodeMirror.Pos(line,1));if(!/\S/.test(start.string))start=cm.getTokenAt(CodeMirror.Pos(line,start.end+1));if(start.type=="meta"&&start.string.slice(0,8)=="#include")return start.start+8;}
var startLine=start.line,has=hasInclude(startLine);if(has==null||hasInclude(startLine-1)!=null)return null;for(var end=startLine;;){var next=hasInclude(end+1);if(next==null)break;++end;}
return{from:CodeMirror.Pos(startLine,has+1),to:cm.clipPos(CodeMirror.Pos(end))};});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.registerGlobalHelper("fold","comment",function(mode){return mode.blockCommentStart&&mode.blockCommentEnd;},function(cm,start){var mode=cm.getModeAt(start),startToken=mode.blockCommentStart,endToken=mode.blockCommentEnd;if(!startToken||!endToken)return;var line=start.line,lineText=cm.getLine(line);var startCh;for(var at=start.ch,pass=0;;){var found=at<=0?-1:lineText.lastIndexOf(startToken,at-1);if(found==-1){if(pass==1)return;pass=1;at=lineText.length;continue;}
if(pass==1&&found<start.ch)return;if(/comment/.test(cm.getTokenTypeAt(CodeMirror.Pos(line,found+1)))&&(found==0||lineText.slice(found-endToken.length,found)==endToken||!/comment/.test(cm.getTokenTypeAt(CodeMirror.Pos(line,found))))){startCh=found+startToken.length;break;}
at=found-1;}
var depth=1,lastLine=cm.lastLine(),end,endCh;outer:for(var i=line;i<=lastLine;++i){var text=cm.getLine(i),pos=i==line?startCh:0;for(;;){var nextOpen=text.indexOf(startToken,pos),nextClose=text.indexOf(endToken,pos);if(nextOpen<0)nextOpen=text.length;if(nextClose<0)nextClose=text.length;pos=Math.min(nextOpen,nextClose);if(pos==text.length)break;if(pos==nextOpen)++depth;else if(!--depth){end=i;endCh=pos;break outer;}
++pos;}}
if(end==null||line==end&&endCh==startCh)return;return{from:CodeMirror.Pos(line,startCh),to:CodeMirror.Pos(end,endCh)};});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";function doFold(cm,pos,options,force){if(options&&options.call){var finder=options;options=null;}else{var finder=getOption(cm,options,"rangeFinder");}
if(typeof pos=="number")pos=CodeMirror.Pos(pos,0);var minSize=getOption(cm,options,"minFoldSize");function getRange(allowFolded){var range=finder(cm,pos);if(!range||range.to.line-range.from.line<minSize)return null;var marks=cm.findMarksAt(range.from);for(var i=0;i<marks.length;++i){if(marks[i].__isFold&&force!=="fold"){if(!allowFolded)return null;range.cleared=true;marks[i].clear();}}
return range;}
var range=getRange(true);if(getOption(cm,options,"scanUp"))while(!range&&pos.line>cm.firstLine()){pos=CodeMirror.Pos(pos.line-1,0);range=getRange(false);}
if(!range||range.cleared||force==="unfold")return;var myWidget=makeWidget(cm,options);CodeMirror.on(myWidget,"mousedown",function(e){myRange.clear();CodeMirror.e_preventDefault(e);});var myRange=cm.markText(range.from,range.to,{replacedWith:myWidget,clearOnEnter:getOption(cm,options,"clearOnEnter"),__isFold:true});myRange.on("clear",function(from,to){CodeMirror.signal(cm,"unfold",cm,from,to);});CodeMirror.signal(cm,"fold",cm,range.from,range.to);}
function makeWidget(cm,options){var widget=getOption(cm,options,"widget");if(typeof widget=="string"){var text=document.createTextNode(widget);widget=document.createElement("span");widget.appendChild(text);widget.className="CodeMirror-foldmarker";}else if(widget){widget=widget.cloneNode(true)}
return widget;}
CodeMirror.newFoldFunction=function(rangeFinder,widget){return function(cm,pos){doFold(cm,pos,{rangeFinder:rangeFinder,widget:widget});};};CodeMirror.defineExtension("foldCode",function(pos,options,force){doFold(this,pos,options,force);});CodeMirror.defineExtension("isFolded",function(pos){var marks=this.findMarksAt(pos);for(var i=0;i<marks.length;++i)
if(marks[i].__isFold)return true;});CodeMirror.commands.toggleFold=function(cm){cm.foldCode(cm.getCursor());};CodeMirror.commands.fold=function(cm){cm.foldCode(cm.getCursor(),null,"fold");};CodeMirror.commands.unfold=function(cm){cm.foldCode(cm.getCursor(),null,"unfold");};CodeMirror.commands.foldAll=function(cm){cm.operation(function(){for(var i=cm.firstLine(),e=cm.lastLine();i<=e;i++)
cm.foldCode(CodeMirror.Pos(i,0),null,"fold");});};CodeMirror.commands.unfoldAll=function(cm){cm.operation(function(){for(var i=cm.firstLine(),e=cm.lastLine();i<=e;i++)
cm.foldCode(CodeMirror.Pos(i,0),null,"unfold");});};CodeMirror.registerHelper("fold","combine",function(){var funcs=Array.prototype.slice.call(arguments,0);return function(cm,start){for(var i=0;i<funcs.length;++i){var found=funcs[i](cm,start);if(found)return found;}};});CodeMirror.registerHelper("fold","auto",function(cm,start){var helpers=cm.getHelpers(start,"fold");for(var i=0;i<helpers.length;i++){var cur=helpers[i](cm,start);if(cur)return cur;}});var defaultOptions={rangeFinder:CodeMirror.fold.auto,widget:"\u2194",minFoldSize:0,scanUp:false,clearOnEnter:true};CodeMirror.defineOption("foldOptions",null);function getOption(cm,options,name){if(options&&options[name]!==undefined)
return options[name];var editorOptions=cm.options.foldOptions;if(editorOptions&&editorOptions[name]!==undefined)
return editorOptions[name];return defaultOptions[name];}
CodeMirror.defineExtension("foldOption",function(options,name){return getOption(this,options,name);});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("./foldcode"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","./foldcode"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineOption("foldGutter",false,function(cm,val,old){if(old&&old!=CodeMirror.Init){cm.clearGutter(cm.state.foldGutter.options.gutter);cm.state.foldGutter=null;cm.off("gutterClick",onGutterClick);cm.off("change",onChange);cm.off("viewportChange",onViewportChange);cm.off("fold",onFold);cm.off("unfold",onFold);cm.off("swapDoc",onChange);}
if(val){cm.state.foldGutter=new State(parseOptions(val));updateInViewport(cm);cm.on("gutterClick",onGutterClick);cm.on("change",onChange);cm.on("viewportChange",onViewportChange);cm.on("fold",onFold);cm.on("unfold",onFold);cm.on("swapDoc",onChange);}});var Pos=CodeMirror.Pos;function State(options){this.options=options;this.from=this.to=0;}
function parseOptions(opts){if(opts===true)opts={};if(opts.gutter==null)opts.gutter="CodeMirror-foldgutter";if(opts.indicatorOpen==null)opts.indicatorOpen="CodeMirror-foldgutter-open";if(opts.indicatorFolded==null)opts.indicatorFolded="CodeMirror-foldgutter-folded";return opts;}
function isFolded(cm,line){var marks=cm.findMarks(Pos(line,0),Pos(line+1,0));for(var i=0;i<marks.length;++i)
if(marks[i].__isFold&&marks[i].find().from.line==line)return marks[i];}
function marker(spec){if(typeof spec=="string"){var elt=document.createElement("div");elt.className=spec+" CodeMirror-guttermarker-subtle";return elt;}else{return spec.cloneNode(true);}}
function updateFoldInfo(cm,from,to){var opts=cm.state.foldGutter.options,cur=from;var minSize=cm.foldOption(opts,"minFoldSize");var func=cm.foldOption(opts,"rangeFinder");cm.eachLine(from,to,function(line){var mark=null;if(isFolded(cm,cur)){mark=marker(opts.indicatorFolded);}else{var pos=Pos(cur,0);var range=func&&func(cm,pos);if(range&&range.to.line-range.from.line>=minSize)
mark=marker(opts.indicatorOpen);}
cm.setGutterMarker(line,opts.gutter,mark);++cur;});}
function updateInViewport(cm){var vp=cm.getViewport(),state=cm.state.foldGutter;if(!state)return;cm.operation(function(){updateFoldInfo(cm,vp.from,vp.to);});state.from=vp.from;state.to=vp.to;}
function onGutterClick(cm,line,gutter){var state=cm.state.foldGutter;if(!state)return;var opts=state.options;if(gutter!=opts.gutter)return;var folded=isFolded(cm,line);if(folded)folded.clear();else cm.foldCode(Pos(line,0),opts.rangeFinder);}
function onChange(cm){var state=cm.state.foldGutter;if(!state)return;var opts=state.options;state.from=state.to=0;clearTimeout(state.changeUpdate);state.changeUpdate=setTimeout(function(){updateInViewport(cm);},opts.foldOnChangeTimeSpan||600);}
function onViewportChange(cm){var state=cm.state.foldGutter;if(!state)return;var opts=state.options;clearTimeout(state.changeUpdate);state.changeUpdate=setTimeout(function(){var vp=cm.getViewport();if(state.from==state.to||vp.from-state.to>20||state.from-vp.to>20){updateInViewport(cm);}else{cm.operation(function(){if(vp.from<state.from){updateFoldInfo(cm,vp.from,state.from);state.from=vp.from;}
if(vp.to>state.to){updateFoldInfo(cm,state.to,vp.to);state.to=vp.to;}});}},opts.updateViewportTimeSpan||400);}
function onFold(cm,from){var state=cm.state.foldGutter;if(!state)return;var line=from.line;if(line>=state.from&&line<state.to)
updateFoldInfo(cm,line,line+1);}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";function lineIndent(cm,lineNo){var text=cm.getLine(lineNo)
var spaceTo=text.search(/\S/)
if(spaceTo==-1||/\bcomment\b/.test(cm.getTokenTypeAt(CodeMirror.Pos(lineNo,spaceTo+1))))
return-1
return CodeMirror.countColumn(text,null,cm.getOption("tabSize"))}
CodeMirror.registerHelper("fold","indent",function(cm,start){var myIndent=lineIndent(cm,start.line)
if(myIndent<0)return
var lastLineInFold=null
for(var i=start.line+1,end=cm.lastLine();i<=end;++i){var indent=lineIndent(cm,i)
if(indent==-1){}else if(indent>myIndent){lastLineInFold=i;}else{break;}}
if(lastLineInFold)return{from:CodeMirror.Pos(start.line,cm.getLine(start.line).length),to:CodeMirror.Pos(lastLineInFold,cm.getLine(lastLineInFold).length)};});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.registerHelper("fold","markdown",function(cm,start){var maxDepth=100;function isHeader(lineNo){var tokentype=cm.getTokenTypeAt(CodeMirror.Pos(lineNo,0));return tokentype&&/\bheader\b/.test(tokentype);}
function headerLevel(lineNo,line,nextLine){var match=line&&line.match(/^#+/);if(match&&isHeader(lineNo))return match[0].length;match=nextLine&&nextLine.match(/^[=\-]+\s*$/);if(match&&isHeader(lineNo+1))return nextLine[0]=="="?1:2;return maxDepth;}
var firstLine=cm.getLine(start.line),nextLine=cm.getLine(start.line+1);var level=headerLevel(start.line,firstLine,nextLine);if(level===maxDepth)return undefined;var lastLineNo=cm.lastLine();var end=start.line,nextNextLine=cm.getLine(end+2);while(end<lastLineNo){if(headerLevel(end+1,nextLine,nextNextLine)<=level)break;++end;nextLine=nextNextLine;nextNextLine=cm.getLine(end+2);}
return{from:CodeMirror.Pos(start.line,firstLine.length),to:CodeMirror.Pos(end,cm.getLine(end).length)};});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var Pos=CodeMirror.Pos;function cmp(a,b){return a.line-b.line||a.ch-b.ch;}
var nameStartChar="A-Z_a-z\\u00C0-\\u00D6\\u00D8-\\u00F6\\u00F8-\\u02FF\\u0370-\\u037D\\u037F-\\u1FFF\\u200C-\\u200D\\u2070-\\u218F\\u2C00-\\u2FEF\\u3001-\\uD7FF\\uF900-\\uFDCF\\uFDF0-\\uFFFD";var nameChar=nameStartChar+"\-\:\.0-9\\u00B7\\u0300-\\u036F\\u203F-\\u2040";var xmlTagStart=new RegExp("<(/?)(["+nameStartChar+"]["+nameChar+"]*)","g");function Iter(cm,line,ch,range){this.line=line;this.ch=ch;this.cm=cm;this.text=cm.getLine(line);this.min=range?Math.max(range.from,cm.firstLine()):cm.firstLine();this.max=range?Math.min(range.to-1,cm.lastLine()):cm.lastLine();}
function tagAt(iter,ch){var type=iter.cm.getTokenTypeAt(Pos(iter.line,ch));return type&&/\btag\b/.test(type);}
function nextLine(iter){if(iter.line>=iter.max)return;iter.ch=0;iter.text=iter.cm.getLine(++iter.line);return true;}
function prevLine(iter){if(iter.line<=iter.min)return;iter.text=iter.cm.getLine(--iter.line);iter.ch=iter.text.length;return true;}
function toTagEnd(iter){for(;;){var gt=iter.text.indexOf(">",iter.ch);if(gt==-1){if(nextLine(iter))continue;else return;}
if(!tagAt(iter,gt+1)){iter.ch=gt+1;continue;}
var lastSlash=iter.text.lastIndexOf("/",gt);var selfClose=lastSlash>-1&&!/\S/.test(iter.text.slice(lastSlash+1,gt));iter.ch=gt+1;return selfClose?"selfClose":"regular";}}
function toTagStart(iter){for(;;){var lt=iter.ch?iter.text.lastIndexOf("<",iter.ch-1):-1;if(lt==-1){if(prevLine(iter))continue;else return;}
if(!tagAt(iter,lt+1)){iter.ch=lt;continue;}
xmlTagStart.lastIndex=lt;iter.ch=lt;var match=xmlTagStart.exec(iter.text);if(match&&match.index==lt)return match;}}
function toNextTag(iter){for(;;){xmlTagStart.lastIndex=iter.ch;var found=xmlTagStart.exec(iter.text);if(!found){if(nextLine(iter))continue;else return;}
if(!tagAt(iter,found.index+1)){iter.ch=found.index+1;continue;}
iter.ch=found.index+found[0].length;return found;}}
function toPrevTag(iter){for(;;){var gt=iter.ch?iter.text.lastIndexOf(">",iter.ch-1):-1;if(gt==-1){if(prevLine(iter))continue;else return;}
if(!tagAt(iter,gt+1)){iter.ch=gt;continue;}
var lastSlash=iter.text.lastIndexOf("/",gt);var selfClose=lastSlash>-1&&!/\S/.test(iter.text.slice(lastSlash+1,gt));iter.ch=gt+1;return selfClose?"selfClose":"regular";}}
function findMatchingClose(iter,tag){var stack=[];for(;;){var next=toNextTag(iter),end,startLine=iter.line,startCh=iter.ch-(next?next[0].length:0);if(!next||!(end=toTagEnd(iter)))return;if(end=="selfClose")continue;if(next[1]){for(var i=stack.length-1;i>=0;--i)if(stack[i]==next[2]){stack.length=i;break;}
if(i<0&&(!tag||tag==next[2]))return{tag:next[2],from:Pos(startLine,startCh),to:Pos(iter.line,iter.ch)};}else{stack.push(next[2]);}}}
function findMatchingOpen(iter,tag){var stack=[];for(;;){var prev=toPrevTag(iter);if(!prev)return;if(prev=="selfClose"){toTagStart(iter);continue;}
var endLine=iter.line,endCh=iter.ch;var start=toTagStart(iter);if(!start)return;if(start[1]){stack.push(start[2]);}else{for(var i=stack.length-1;i>=0;--i)if(stack[i]==start[2]){stack.length=i;break;}
if(i<0&&(!tag||tag==start[2]))return{tag:start[2],from:Pos(iter.line,iter.ch),to:Pos(endLine,endCh)};}}}
CodeMirror.registerHelper("fold","xml",function(cm,start){var iter=new Iter(cm,start.line,0);for(;;){var openTag=toNextTag(iter),end;if(!openTag||iter.line!=start.line||!(end=toTagEnd(iter)))return;if(!openTag[1]&&end!="selfClose"){var startPos=Pos(iter.line,iter.ch);var endPos=findMatchingClose(iter,openTag[2]);return endPos&&{from:startPos,to:endPos.from};}}});CodeMirror.findMatchingTag=function(cm,pos,range){var iter=new Iter(cm,pos.line,pos.ch,range);if(iter.text.indexOf(">")==-1&&iter.text.indexOf("<")==-1)return;var end=toTagEnd(iter),to=end&&Pos(iter.line,iter.ch);var start=end&&toTagStart(iter);if(!end||!start||cmp(iter,pos)>0)return;var here={from:Pos(iter.line,iter.ch),to:to,tag:start[2]};if(end=="selfClose")return{open:here,close:null,at:"open"};if(start[1]){return{open:findMatchingOpen(iter,start[2]),close:here,at:"close"};}else{iter=new Iter(cm,to.line,to.ch,range);return{open:here,close:findMatchingClose(iter,start[2]),at:"open"};}};CodeMirror.findEnclosingTag=function(cm,pos,range,tag){var iter=new Iter(cm,pos.line,pos.ch,range);for(;;){var open=findMatchingOpen(iter,tag);if(!open)break;var forward=new Iter(cm,pos.line,pos.ch,range);var close=findMatchingClose(forward,open.tag);if(close)return{open:open,close:close};}};CodeMirror.scanForClosingTag=function(cm,pos,name,end){var iter=new Iter(cm,pos.line,pos.ch,end?{from:0,to:end}:null);return findMatchingClose(iter,name);};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var WORD=/[\w$]+/,RANGE=500;CodeMirror.registerHelper("hint","anyword",function(editor,options){var word=options&&options.word||WORD;var range=options&&options.range||RANGE;var cur=editor.getCursor(),curLine=editor.getLine(cur.line);var end=cur.ch,start=end;while(start&&word.test(curLine.charAt(start-1)))--start;var curWord=start!=end&&curLine.slice(start,end);var list=options&&options.list||[],seen={};var re=new RegExp(word.source,"g");for(var dir=-1;dir<=1;dir+=2){var line=cur.line,endLine=Math.min(Math.max(line+dir*range,editor.firstLine()),editor.lastLine())+dir;for(;line!=endLine;line+=dir){var text=editor.getLine(line),m;while(m=re.exec(text)){if(line==cur.line&&m[0]===curWord)continue;if((!curWord||m[0].lastIndexOf(curWord,0)==0)&&!Object.prototype.hasOwnProperty.call(seen,m[0])){seen[m[0]]=true;list.push(m[0]);}}}}
return{list:list,from:CodeMirror.Pos(cur.line,start),to:CodeMirror.Pos(cur.line,end)};});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("../../mode/css/css"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","../../mode/css/css"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var pseudoClasses={link:1,visited:1,active:1,hover:1,focus:1,"first-letter":1,"first-line":1,"first-child":1,before:1,after:1,lang:1};CodeMirror.registerHelper("hint","css",function(cm){var cur=cm.getCursor(),token=cm.getTokenAt(cur);var inner=CodeMirror.innerMode(cm.getMode(),token.state);if(inner.mode.name!="css")return;if(token.type=="keyword"&&"!important".indexOf(token.string)==0)
return{list:["!important"],from:CodeMirror.Pos(cur.line,token.start),to:CodeMirror.Pos(cur.line,token.end)};var start=token.start,end=cur.ch,word=token.string.slice(0,end-start);if(/[^\w$_-]/.test(word)){word="";start=end=cur.ch;}
var spec=CodeMirror.resolveMode("text/css");var result=[];function add(keywords){for(var name in keywords)
if(!word||name.lastIndexOf(word,0)==0)
result.push(name);}
var st=inner.state.state;if(st=="pseudo"||token.type=="variable-3"){add(pseudoClasses);}else if(st=="block"||st=="maybeprop"){add(spec.propertyKeywords);}else if(st=="prop"||st=="parens"||st=="at"||st=="params"){add(spec.valueKeywords);add(spec.colorKeywords);}else if(st=="media"||st=="media_parens"){add(spec.mediaTypes);add(spec.mediaFeatures);}
if(result.length)return{list:result,from:CodeMirror.Pos(cur.line,start),to:CodeMirror.Pos(cur.line,end)};});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("./xml-hint"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","./xml-hint"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var langs="ab aa af ak sq am ar an hy as av ae ay az bm ba eu be bn bh bi bs br bg my ca ch ce ny zh cv kw co cr hr cs da dv nl dz en eo et ee fo fj fi fr ff gl ka de el gn gu ht ha he hz hi ho hu ia id ie ga ig ik io is it iu ja jv kl kn kr ks kk km ki rw ky kv kg ko ku kj la lb lg li ln lo lt lu lv gv mk mg ms ml mt mi mr mh mn na nv nb nd ne ng nn no ii nr oc oj cu om or os pa pi fa pl ps pt qu rm rn ro ru sa sc sd se sm sg sr gd sn si sk sl so st es su sw ss sv ta te tg th ti bo tk tl tn to tr ts tt tw ty ug uk ur uz ve vi vo wa cy wo fy xh yi yo za zu".split(" ");var targets=["_blank","_self","_top","_parent"];var charsets=["ascii","utf-8","utf-16","latin1","latin1"];var methods=["get","post","put","delete"];var encs=["application/x-www-form-urlencoded","multipart/form-data","text/plain"];var media=["all","screen","print","embossed","braille","handheld","print","projection","screen","tty","tv","speech","3d-glasses","resolution [>][<][=] [X]","device-aspect-ratio: X/Y","orientation:portrait","orientation:landscape","device-height: [X]","device-width: [X]"];var s={attrs:{}};var data={a:{attrs:{href:null,ping:null,type:null,media:media,target:targets,hreflang:langs}},abbr:s,acronym:s,address:s,applet:s,area:{attrs:{alt:null,coords:null,href:null,target:null,ping:null,media:media,hreflang:langs,type:null,shape:["default","rect","circle","poly"]}},article:s,aside:s,audio:{attrs:{src:null,mediagroup:null,crossorigin:["anonymous","use-credentials"],preload:["none","metadata","auto"],autoplay:["","autoplay"],loop:["","loop"],controls:["","controls"]}},b:s,base:{attrs:{href:null,target:targets}},basefont:s,bdi:s,bdo:s,big:s,blockquote:{attrs:{cite:null}},body:s,br:s,button:{attrs:{form:null,formaction:null,name:null,value:null,autofocus:["","autofocus"],disabled:["","autofocus"],formenctype:encs,formmethod:methods,formnovalidate:["","novalidate"],formtarget:targets,type:["submit","reset","button"]}},canvas:{attrs:{width:null,height:null}},caption:s,center:s,cite:s,code:s,col:{attrs:{span:null}},colgroup:{attrs:{span:null}},command:{attrs:{type:["command","checkbox","radio"],label:null,icon:null,radiogroup:null,command:null,title:null,disabled:["","disabled"],checked:["","checked"]}},data:{attrs:{value:null}},datagrid:{attrs:{disabled:["","disabled"],multiple:["","multiple"]}},datalist:{attrs:{data:null}},dd:s,del:{attrs:{cite:null,datetime:null}},details:{attrs:{open:["","open"]}},dfn:s,dir:s,div:s,dl:s,dt:s,em:s,embed:{attrs:{src:null,type:null,width:null,height:null}},eventsource:{attrs:{src:null}},fieldset:{attrs:{disabled:["","disabled"],form:null,name:null}},figcaption:s,figure:s,font:s,footer:s,form:{attrs:{action:null,name:null,"accept-charset":charsets,autocomplete:["on","off"],enctype:encs,method:methods,novalidate:["","novalidate"],target:targets}},frame:s,frameset:s,h1:s,h2:s,h3:s,h4:s,h5:s,h6:s,head:{attrs:{},children:["title","base","link","style","meta","script","noscript","command"]},header:s,hgroup:s,hr:s,html:{attrs:{manifest:null},children:["head","body"]},i:s,iframe:{attrs:{src:null,srcdoc:null,name:null,width:null,height:null,sandbox:["allow-top-navigation","allow-same-origin","allow-forms","allow-scripts"],seamless:["","seamless"]}},img:{attrs:{alt:null,src:null,ismap:null,usemap:null,width:null,height:null,crossorigin:["anonymous","use-credentials"]}},input:{attrs:{alt:null,dirname:null,form:null,formaction:null,height:null,list:null,max:null,maxlength:null,min:null,name:null,pattern:null,placeholder:null,size:null,src:null,step:null,value:null,width:null,accept:["audio/*","video/*","image/*"],autocomplete:["on","off"],autofocus:["","autofocus"],checked:["","checked"],disabled:["","disabled"],formenctype:encs,formmethod:methods,formnovalidate:["","novalidate"],formtarget:targets,multiple:["","multiple"],readonly:["","readonly"],required:["","required"],type:["hidden","text","search","tel","url","email","password","datetime","date","month","week","time","datetime-local","number","range","color","checkbox","radio","file","submit","image","reset","button"]}},ins:{attrs:{cite:null,datetime:null}},kbd:s,keygen:{attrs:{challenge:null,form:null,name:null,autofocus:["","autofocus"],disabled:["","disabled"],keytype:["RSA"]}},label:{attrs:{"for":null,form:null}},legend:s,li:{attrs:{value:null}},link:{attrs:{href:null,type:null,hreflang:langs,media:media,sizes:["all","16x16","16x16 32x32","16x16 32x32 64x64"]}},map:{attrs:{name:null}},mark:s,menu:{attrs:{label:null,type:["list","context","toolbar"]}},meta:{attrs:{content:null,charset:charsets,name:["viewport","application-name","author","description","generator","keywords"],"http-equiv":["content-language","content-type","default-style","refresh"]}},meter:{attrs:{value:null,min:null,low:null,high:null,max:null,optimum:null}},nav:s,noframes:s,noscript:s,object:{attrs:{data:null,type:null,name:null,usemap:null,form:null,width:null,height:null,typemustmatch:["","typemustmatch"]}},ol:{attrs:{reversed:["","reversed"],start:null,type:["1","a","A","i","I"]}},optgroup:{attrs:{disabled:["","disabled"],label:null}},option:{attrs:{disabled:["","disabled"],label:null,selected:["","selected"],value:null}},output:{attrs:{"for":null,form:null,name:null}},p:s,param:{attrs:{name:null,value:null}},pre:s,progress:{attrs:{value:null,max:null}},q:{attrs:{cite:null}},rp:s,rt:s,ruby:s,s:s,samp:s,script:{attrs:{type:["text/javascript"],src:null,async:["","async"],defer:["","defer"],charset:charsets}},section:s,select:{attrs:{form:null,name:null,size:null,autofocus:["","autofocus"],disabled:["","disabled"],multiple:["","multiple"]}},small:s,source:{attrs:{src:null,type:null,media:null}},span:s,strike:s,strong:s,style:{attrs:{type:["text/css"],media:media,scoped:null}},sub:s,summary:s,sup:s,table:s,tbody:s,td:{attrs:{colspan:null,rowspan:null,headers:null}},textarea:{attrs:{dirname:null,form:null,maxlength:null,name:null,placeholder:null,rows:null,cols:null,autofocus:["","autofocus"],disabled:["","disabled"],readonly:["","readonly"],required:["","required"],wrap:["soft","hard"]}},tfoot:s,th:{attrs:{colspan:null,rowspan:null,headers:null,scope:["row","col","rowgroup","colgroup"]}},thead:s,time:{attrs:{datetime:null}},title:s,tr:s,track:{attrs:{src:null,label:null,"default":null,kind:["subtitles","captions","descriptions","chapters","metadata"],srclang:langs}},tt:s,u:s,ul:s,"var":s,video:{attrs:{src:null,poster:null,width:null,height:null,crossorigin:["anonymous","use-credentials"],preload:["auto","metadata","none"],autoplay:["","autoplay"],mediagroup:["movie"],muted:["","muted"],controls:["","controls"]}},wbr:s};var globalAttrs={accesskey:["a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z","0","1","2","3","4","5","6","7","8","9"],"class":null,contenteditable:["true","false"],contextmenu:null,dir:["ltr","rtl","auto"],draggable:["true","false","auto"],dropzone:["copy","move","link","string:","file:"],hidden:["hidden"],id:null,inert:["inert"],itemid:null,itemprop:null,itemref:null,itemscope:["itemscope"],itemtype:null,lang:["en","es"],spellcheck:["true","false"],style:null,tabindex:["1","2","3","4","5","6","7","8","9"],title:null,translate:["yes","no"],onclick:null,rel:["stylesheet","alternate","author","bookmark","help","license","next","nofollow","noreferrer","prefetch","prev","search","tag"]};function populate(obj){for(var attr in globalAttrs)if(globalAttrs.hasOwnProperty(attr))
obj.attrs[attr]=globalAttrs[attr];}
populate(s);for(var tag in data)if(data.hasOwnProperty(tag)&&data[tag]!=s)
populate(data[tag]);CodeMirror.htmlSchema=data;function htmlHint(cm,options){var local={schemaInfo:data};if(options)for(var opt in options)local[opt]=options[opt];return CodeMirror.hint.xml(cm,local);}
CodeMirror.registerHelper("hint","html",htmlHint);});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){var Pos=CodeMirror.Pos;function forEach(arr,f){for(var i=0,e=arr.length;i<e;++i)f(arr[i]);}
function arrayContains(arr,item){if(!Array.prototype.indexOf){var i=arr.length;while(i--){if(arr[i]===item){return true;}}
return false;}
return arr.indexOf(item)!=-1;}
function scriptHint(editor,keywords,getToken,options){var cur=editor.getCursor(),token=getToken(editor,cur);if(/\b(?:string|comment)\b/.test(token.type))return;token.state=CodeMirror.innerMode(editor.getMode(),token.state).state;if(!/^[\w$_]*$/.test(token.string)){token={start:cur.ch,end:cur.ch,string:"",state:token.state,type:token.string=="."?"property":null};}else if(token.end>cur.ch){token.end=cur.ch;token.string=token.string.slice(0,cur.ch-token.start);}
var tprop=token;while(tprop.type=="property"){tprop=getToken(editor,Pos(cur.line,tprop.start));if(tprop.string!=".")return;tprop=getToken(editor,Pos(cur.line,tprop.start));if(!context)var context=[];context.push(tprop);}
return{list:getCompletions(token,context,keywords,options),from:Pos(cur.line,token.start),to:Pos(cur.line,token.end)};}
function javascriptHint(editor,options){return scriptHint(editor,javascriptKeywords,function(e,cur){return e.getTokenAt(cur);},options);};CodeMirror.registerHelper("hint","javascript",javascriptHint);function getCoffeeScriptToken(editor,cur){var token=editor.getTokenAt(cur);if(cur.ch==token.start+1&&token.string.charAt(0)=='.'){token.end=token.start;token.string='.';token.type="property";}
else if(/^\.[\w$_]*$/.test(token.string)){token.type="property";token.start++;token.string=token.string.replace(/\./,'');}
return token;}
function coffeescriptHint(editor,options){return scriptHint(editor,coffeescriptKeywords,getCoffeeScriptToken,options);}
CodeMirror.registerHelper("hint","coffeescript",coffeescriptHint);var stringProps=("charAt charCodeAt indexOf lastIndexOf substring substr slice trim trimLeft trimRight "+"toUpperCase toLowerCase split concat match replace search").split(" ");var arrayProps=("length concat join splice push pop shift unshift slice reverse sort indexOf "+"lastIndexOf every some filter forEach map reduce reduceRight ").split(" ");var funcProps="prototype apply call bind".split(" ");var javascriptKeywords=("break case catch continue debugger default delete do else false finally for function "+"if in instanceof new null return switch throw true try typeof var void while with").split(" ");var coffeescriptKeywords=("and break catch class continue delete do else extends false finally for "+"if in instanceof isnt new no not null of off on or return switch then throw true try typeof until void while with yes").split(" ");function forAllProps(obj,callback){if(!Object.getOwnPropertyNames||!Object.getPrototypeOf){for(var name in obj)callback(name)}else{for(var o=obj;o;o=Object.getPrototypeOf(o))
Object.getOwnPropertyNames(o).forEach(callback)}}
function getCompletions(token,context,keywords,options){var found=[],start=token.string,global=options&&options.globalScope||window;function maybeAdd(str){if(str.lastIndexOf(start,0)==0&&!arrayContains(found,str))found.push(str);}
function gatherCompletions(obj){if(typeof obj=="string")forEach(stringProps,maybeAdd);else if(obj instanceof Array)forEach(arrayProps,maybeAdd);else if(obj instanceof Function)forEach(funcProps,maybeAdd);forAllProps(obj,maybeAdd)}
if(context&&context.length){var obj=context.pop(),base;if(obj.type&&obj.type.indexOf("variable")===0){if(options&&options.additionalContext)
base=options.additionalContext[obj.string];if(!options||options.useGlobalScope!==false)
base=base||global[obj.string];}else if(obj.type=="string"){base="";}else if(obj.type=="atom"){base=1;}else if(obj.type=="function"){if(global.jQuery!=null&&(obj.string=='$'||obj.string=='jQuery')&&(typeof global.jQuery=='function'))
base=global.jQuery();else if(global._!=null&&(obj.string=='_')&&(typeof global._=='function'))
base=global._();}
while(base!=null&&context.length)
base=base[context.pop().string];if(base!=null)gatherCompletions(base);}else{for(var v=token.state.localVars;v;v=v.next)maybeAdd(v.name);for(var v=token.state.globalVars;v;v=v.next)maybeAdd(v.name);if(!options||options.useGlobalScope!==false)
gatherCompletions(global);forEach(keywords,maybeAdd);}
return found;}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var HINT_ELEMENT_CLASS="CodeMirror-hint";var ACTIVE_HINT_ELEMENT_CLASS="CodeMirror-hint-active";CodeMirror.showHint=function(cm,getHints,options){if(!getHints)return cm.showHint(options);if(options&&options.async)getHints.async=true;var newOpts={hint:getHints};if(options)for(var prop in options)newOpts[prop]=options[prop];return cm.showHint(newOpts);};CodeMirror.defineExtension("showHint",function(options){options=parseOptions(this,this.getCursor("start"),options);var selections=this.listSelections()
if(selections.length>1)return;if(this.somethingSelected()){if(!options.hint.supportsSelection)return;for(var i=0;i<selections.length;i++)
if(selections[i].head.line!=selections[i].anchor.line)return;}
if(this.state.completionActive)this.state.completionActive.close();var completion=this.state.completionActive=new Completion(this,options);if(!completion.options.hint)return;CodeMirror.signal(this,"startCompletion",this);completion.update(true);});function Completion(cm,options){this.cm=cm;this.options=options;this.widget=null;this.debounce=0;this.tick=0;this.startPos=this.cm.getCursor("start");this.startLen=this.cm.getLine(this.startPos.line).length-this.cm.getSelection().length;var self=this;cm.on("cursorActivity",this.activityFunc=function(){self.cursorActivity();});}
var requestAnimationFrame=window.requestAnimationFrame||function(fn){return setTimeout(fn,1000/60);};var cancelAnimationFrame=window.cancelAnimationFrame||clearTimeout;Completion.prototype={close:function(){if(!this.active())return;this.cm.state.completionActive=null;this.tick=null;this.cm.off("cursorActivity",this.activityFunc);if(this.widget&&this.data)CodeMirror.signal(this.data,"close");if(this.widget)this.widget.close();CodeMirror.signal(this.cm,"endCompletion",this.cm);},active:function(){return this.cm.state.completionActive==this;},pick:function(data,i){var completion=data.list[i];if(completion.hint)completion.hint(this.cm,data,completion);else this.cm.replaceRange(getText(completion),completion.from||data.from,completion.to||data.to,"complete");CodeMirror.signal(data,"pick",completion);this.close();},cursorActivity:function(){if(this.debounce){cancelAnimationFrame(this.debounce);this.debounce=0;}
var pos=this.cm.getCursor(),line=this.cm.getLine(pos.line);if(pos.line!=this.startPos.line||line.length-pos.ch!=this.startLen-this.startPos.ch||pos.ch<this.startPos.ch||this.cm.somethingSelected()||(pos.ch&&this.options.closeCharacters.test(line.charAt(pos.ch-1)))){this.close();}else{var self=this;this.debounce=requestAnimationFrame(function(){self.update();});if(this.widget)this.widget.disable();}},update:function(first){if(this.tick==null)return
var self=this,myTick=++this.tick
fetchHints(this.options.hint,this.cm,this.options,function(data){if(self.tick==myTick)self.finishUpdate(data,first)})},finishUpdate:function(data,first){if(this.data)CodeMirror.signal(this.data,"update");var picked=(this.widget&&this.widget.picked)||(first&&this.options.completeSingle);if(this.widget)this.widget.close();this.data=data;if(data&&data.list.length){if(picked&&data.list.length==1){this.pick(data,0);}else{this.widget=new Widget(this,data);CodeMirror.signal(data,"shown");}}}};function parseOptions(cm,pos,options){var editor=cm.options.hintOptions;var out={};for(var prop in defaultOptions)out[prop]=defaultOptions[prop];if(editor)for(var prop in editor)
if(editor[prop]!==undefined)out[prop]=editor[prop];if(options)for(var prop in options)
if(options[prop]!==undefined)out[prop]=options[prop];if(out.hint.resolve)out.hint=out.hint.resolve(cm,pos)
return out;}
function getText(completion){if(typeof completion=="string")return completion;else return completion.text;}
function buildKeyMap(completion,handle){var baseMap={Up:function(){handle.moveFocus(-1);},Down:function(){handle.moveFocus(1);},PageUp:function(){handle.moveFocus(-handle.menuSize()+1,true);},PageDown:function(){handle.moveFocus(handle.menuSize()-1,true);},Home:function(){handle.setFocus(0);},End:function(){handle.setFocus(handle.length-1);},Enter:handle.pick,Tab:handle.pick,Esc:handle.close};var custom=completion.options.customKeys;var ourMap=custom?{}:baseMap;function addBinding(key,val){var bound;if(typeof val!="string")
bound=function(cm){return val(cm,handle);};else if(baseMap.hasOwnProperty(val))
bound=baseMap[val];else
bound=val;ourMap[key]=bound;}
if(custom)
for(var key in custom)if(custom.hasOwnProperty(key))
addBinding(key,custom[key]);var extra=completion.options.extraKeys;if(extra)
for(var key in extra)if(extra.hasOwnProperty(key))
addBinding(key,extra[key]);return ourMap;}
function getHintElement(hintsElement,el){while(el&&el!=hintsElement){if(el.nodeName.toUpperCase()==="LI"&&el.parentNode==hintsElement)return el;el=el.parentNode;}}
function Widget(completion,data){this.completion=completion;this.data=data;this.picked=false;var widget=this,cm=completion.cm;var hints=this.hints=document.createElement("ul");hints.className="CodeMirror-hints";this.selectedHint=data.selectedHint||0;var completions=data.list;for(var i=0;i<completions.length;++i){var elt=hints.appendChild(document.createElement("li")),cur=completions[i];var className=HINT_ELEMENT_CLASS+(i!=this.selectedHint?"":" "+ACTIVE_HINT_ELEMENT_CLASS);if(cur.className!=null)className=cur.className+" "+className;elt.className=className;if(cur.render)cur.render(elt,data,cur);else elt.appendChild(document.createTextNode(cur.displayText||getText(cur)));elt.hintId=i;}
var pos=cm.cursorCoords(completion.options.alignWithWord?data.from:null);var left=pos.left,top=pos.bottom,below=true;hints.style.left=left+"px";hints.style.top=top+"px";var winW=window.innerWidth||Math.max(document.body.offsetWidth,document.documentElement.offsetWidth);var winH=window.innerHeight||Math.max(document.body.offsetHeight,document.documentElement.offsetHeight);(completion.options.container||document.body).appendChild(hints);var box=hints.getBoundingClientRect(),overlapY=box.bottom-winH;var scrolls=hints.scrollHeight>hints.clientHeight+1
var startScroll=cm.getScrollInfo();if(overlapY>0){var height=box.bottom-box.top,curTop=pos.top-(pos.bottom-box.top);if(curTop-height>0){hints.style.top=(top=pos.top-height)+"px";below=false;}else if(height>winH){hints.style.height=(winH-5)+"px";hints.style.top=(top=pos.bottom-box.top)+"px";var cursor=cm.getCursor();if(data.from.ch!=cursor.ch){pos=cm.cursorCoords(cursor);hints.style.left=(left=pos.left)+"px";box=hints.getBoundingClientRect();}}}
var overlapX=box.right-winW;if(overlapX>0){if(box.right-box.left>winW){hints.style.width=(winW-5)+"px";overlapX-=(box.right-box.left)-winW;}
hints.style.left=(left=pos.left-overlapX)+"px";}
if(scrolls)for(var node=hints.firstChild;node;node=node.nextSibling)
node.style.paddingRight=cm.display.nativeBarWidth+"px"
cm.addKeyMap(this.keyMap=buildKeyMap(completion,{moveFocus:function(n,avoidWrap){widget.changeActive(widget.selectedHint+n,avoidWrap);},setFocus:function(n){widget.changeActive(n);},menuSize:function(){return widget.screenAmount();},length:completions.length,close:function(){completion.close();},pick:function(){widget.pick();},data:data}));if(completion.options.closeOnUnfocus){var closingOnBlur;cm.on("blur",this.onBlur=function(){closingOnBlur=setTimeout(function(){completion.close();},100);});cm.on("focus",this.onFocus=function(){clearTimeout(closingOnBlur);});}
cm.on("scroll",this.onScroll=function(){var curScroll=cm.getScrollInfo(),editor=cm.getWrapperElement().getBoundingClientRect();var newTop=top+startScroll.top-curScroll.top;var point=newTop-(window.pageYOffset||(document.documentElement||document.body).scrollTop);if(!below)point+=hints.offsetHeight;if(point<=editor.top||point>=editor.bottom)return completion.close();hints.style.top=newTop+"px";hints.style.left=(left+startScroll.left-curScroll.left)+"px";});CodeMirror.on(hints,"dblclick",function(e){var t=getHintElement(hints,e.target||e.srcElement);if(t&&t.hintId!=null){widget.changeActive(t.hintId);widget.pick();}});CodeMirror.on(hints,"click",function(e){var t=getHintElement(hints,e.target||e.srcElement);if(t&&t.hintId!=null){widget.changeActive(t.hintId);if(completion.options.completeOnSingleClick)widget.pick();}});CodeMirror.on(hints,"mousedown",function(){setTimeout(function(){cm.focus();},20);});CodeMirror.signal(data,"select",completions[this.selectedHint],hints.childNodes[this.selectedHint]);return true;}
Widget.prototype={close:function(){if(this.completion.widget!=this)return;this.completion.widget=null;this.hints.parentNode.removeChild(this.hints);this.completion.cm.removeKeyMap(this.keyMap);var cm=this.completion.cm;if(this.completion.options.closeOnUnfocus){cm.off("blur",this.onBlur);cm.off("focus",this.onFocus);}
cm.off("scroll",this.onScroll);},disable:function(){this.completion.cm.removeKeyMap(this.keyMap);var widget=this;this.keyMap={Enter:function(){widget.picked=true;}};this.completion.cm.addKeyMap(this.keyMap);},pick:function(){this.completion.pick(this.data,this.selectedHint);},changeActive:function(i,avoidWrap){if(i>=this.data.list.length)
i=avoidWrap?this.data.list.length-1:0;else if(i<0)
i=avoidWrap?0:this.data.list.length-1;if(this.selectedHint==i)return;var node=this.hints.childNodes[this.selectedHint];node.className=node.className.replace(" "+ACTIVE_HINT_ELEMENT_CLASS,"");node=this.hints.childNodes[this.selectedHint=i];node.className+=" "+ACTIVE_HINT_ELEMENT_CLASS;if(node.offsetTop<this.hints.scrollTop)
this.hints.scrollTop=node.offsetTop-3;else if(node.offsetTop+node.offsetHeight>this.hints.scrollTop+this.hints.clientHeight)
this.hints.scrollTop=node.offsetTop+node.offsetHeight-this.hints.clientHeight+3;CodeMirror.signal(this.data,"select",this.data.list[this.selectedHint],node);},screenAmount:function(){return Math.floor(this.hints.clientHeight / this.hints.firstChild.offsetHeight)||1;}};function applicableHelpers(cm,helpers){if(!cm.somethingSelected())return helpers
var result=[]
for(var i=0;i<helpers.length;i++)
if(helpers[i].supportsSelection)result.push(helpers[i])
return result}
function fetchHints(hint,cm,options,callback){if(hint.async){hint(cm,callback,options)}else{var result=hint(cm,options)
if(result&&result.then)result.then(callback)
else callback(result)}}
function resolveAutoHints(cm,pos){var helpers=cm.getHelpers(pos,"hint"),words
if(helpers.length){var resolved=function(cm,callback,options){var app=applicableHelpers(cm,helpers);function run(i){if(i==app.length)return callback(null)
fetchHints(app[i],cm,options,function(result){if(result&&result.list.length>0)callback(result)
else run(i+1)})}
run(0)}
resolved.async=true
resolved.supportsSelection=true
return resolved}else if(words=cm.getHelper(cm.getCursor(),"hintWords")){return function(cm){return CodeMirror.hint.fromList(cm,{words:words})}}else if(CodeMirror.hint.anyword){return function(cm,options){return CodeMirror.hint.anyword(cm,options)}}else{return function(){}}}
CodeMirror.registerHelper("hint","auto",{resolve:resolveAutoHints});CodeMirror.registerHelper("hint","fromList",function(cm,options){var cur=cm.getCursor(),token=cm.getTokenAt(cur);var to=CodeMirror.Pos(cur.line,token.end);if(token.string&&/\w/.test(token.string[token.string.length-1])){var term=token.string,from=CodeMirror.Pos(cur.line,token.start);}else{var term="",from=to;}
var found=[];for(var i=0;i<options.words.length;i++){var word=options.words[i];if(word.slice(0,term.length)==term)
found.push(word);}
if(found.length)return{list:found,from:from,to:to};});CodeMirror.commands.autocomplete=CodeMirror.showHint;var defaultOptions={hint:CodeMirror.hint.auto,completeSingle:true,alignWithWord:true,closeCharacters:/[\s()\[\]{};:>,]/,closeOnUnfocus:true,completeOnSingleClick:true,container:null,customKeys:null,extraKeys:null};CodeMirror.defineOption("hintOptions",null);});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("../../mode/sql/sql"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","../../mode/sql/sql"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var tables;var defaultTable;var keywords;var identifierQuote;var CONS={QUERY_DIV:";",ALIAS_KEYWORD:"AS"};var Pos=CodeMirror.Pos,cmpPos=CodeMirror.cmpPos;function isArray(val){return Object.prototype.toString.call(val)=="[object Array]"}
function getKeywords(editor){var mode=editor.doc.modeOption;if(mode==="sql")mode="text/x-sql";return CodeMirror.resolveMode(mode).keywords;}
function getIdentifierQuote(editor){var mode=editor.doc.modeOption;if(mode==="sql")mode="text/x-sql";return CodeMirror.resolveMode(mode).identifierQuote||"`";}
function getText(item){return typeof item=="string"?item:item.text;}
function wrapTable(name,value){if(isArray(value))value={columns:value}
if(!value.text)value.text=name
return value}
function parseTables(input){var result={}
if(isArray(input)){for(var i=input.length-1;i>=0;i--){var item=input[i]
result[getText(item).toUpperCase()]=wrapTable(getText(item),item)}}else if(input){for(var name in input)
result[name.toUpperCase()]=wrapTable(name,input[name])}
return result}
function getTable(name){return tables[name.toUpperCase()]}
function shallowClone(object){var result={};for(var key in object)if(object.hasOwnProperty(key))
result[key]=object[key];return result;}
function match(string,word){var len=string.length;var sub=getText(word).substr(0,len);return string.toUpperCase()===sub.toUpperCase();}
function addMatches(result,search,wordlist,formatter){if(isArray(wordlist)){for(var i=0;i<wordlist.length;i++)
if(match(search,wordlist[i]))result.push(formatter(wordlist[i]))}else{for(var word in wordlist)if(wordlist.hasOwnProperty(word)){var val=wordlist[word]
if(!val||val===true)
val=word
else
val=val.displayText?{text:val.text,displayText:val.displayText}:val.text
if(match(search,val))result.push(formatter(val))}}}
function cleanName(name){if(name.charAt(0)=="."){name=name.substr(1);}
var nameParts=name.split(identifierQuote+identifierQuote);for(var i=0;i<nameParts.length;i++)
nameParts[i]=nameParts[i].replace(new RegExp(identifierQuote,"g"),"");return nameParts.join(identifierQuote);}
function insertIdentifierQuotes(name){var nameParts=getText(name).split(".");for(var i=0;i<nameParts.length;i++)
nameParts[i]=identifierQuote+
nameParts[i].replace(new RegExp(identifierQuote,"g"),identifierQuote+identifierQuote)+
identifierQuote;var escaped=nameParts.join(".");if(typeof name=="string")return escaped;name=shallowClone(name);name.text=escaped;return name;}
function nameCompletion(cur,token,result,editor){var useIdentifierQuotes=false;var nameParts=[];var start=token.start;var cont=true;while(cont){cont=(token.string.charAt(0)==".");useIdentifierQuotes=useIdentifierQuotes||(token.string.charAt(0)==identifierQuote);start=token.start;nameParts.unshift(cleanName(token.string));token=editor.getTokenAt(Pos(cur.line,token.start));if(token.string=="."){cont=true;token=editor.getTokenAt(Pos(cur.line,token.start));}}
var string=nameParts.join(".");addMatches(result,string,tables,function(w){return useIdentifierQuotes?insertIdentifierQuotes(w):w;});addMatches(result,string,defaultTable,function(w){return useIdentifierQuotes?insertIdentifierQuotes(w):w;});string=nameParts.pop();var table=nameParts.join(".");var alias=false;var aliasTable=table;if(!getTable(table)){var oldTable=table;table=findTableByAlias(table,editor);if(table!==oldTable)alias=true;}
var columns=getTable(table);if(columns&&columns.columns)
columns=columns.columns;if(columns){addMatches(result,string,columns,function(w){var tableInsert=table;if(alias==true)tableInsert=aliasTable;if(typeof w=="string"){w=tableInsert+"."+w;}else{w=shallowClone(w);w.text=tableInsert+"."+w.text;}
return useIdentifierQuotes?insertIdentifierQuotes(w):w;});}
return start;}
function eachWord(lineText,f){var words=lineText.split(/\s+/)
for(var i=0;i<words.length;i++)
if(words[i])f(words[i].replace(/[,;]/g,''))}
function findTableByAlias(alias,editor){var doc=editor.doc;var fullQuery=doc.getValue();var aliasUpperCase=alias.toUpperCase();var previousWord="";var table="";var separator=[];var validRange={start:Pos(0,0),end:Pos(editor.lastLine(),editor.getLineHandle(editor.lastLine()).length)};var indexOfSeparator=fullQuery.indexOf(CONS.QUERY_DIV);while(indexOfSeparator!=-1){separator.push(doc.posFromIndex(indexOfSeparator));indexOfSeparator=fullQuery.indexOf(CONS.QUERY_DIV,indexOfSeparator+1);}
separator.unshift(Pos(0,0));separator.push(Pos(editor.lastLine(),editor.getLineHandle(editor.lastLine()).text.length));var prevItem=null;var current=editor.getCursor()
for(var i=0;i<separator.length;i++){if((prevItem==null||cmpPos(current,prevItem)>0)&&cmpPos(current,separator[i])<=0){validRange={start:prevItem,end:separator[i]};break;}
prevItem=separator[i];}
var query=doc.getRange(validRange.start,validRange.end,false);for(var i=0;i<query.length;i++){var lineText=query[i];eachWord(lineText,function(word){var wordUpperCase=word.toUpperCase();if(wordUpperCase===aliasUpperCase&&getTable(previousWord))
table=previousWord;if(wordUpperCase!==CONS.ALIAS_KEYWORD)
previousWord=word;});if(table)break;}
return table;}
CodeMirror.registerHelper("hint","sql",function(editor,options){tables=parseTables(options&&options.tables)
var defaultTableName=options&&options.defaultTable;var disableKeywords=options&&options.disableKeywords;defaultTable=defaultTableName&&getTable(defaultTableName);keywords=getKeywords(editor);identifierQuote=getIdentifierQuote(editor);if(defaultTableName&&!defaultTable)
defaultTable=findTableByAlias(defaultTableName,editor);defaultTable=defaultTable||[];if(defaultTable.columns)
defaultTable=defaultTable.columns;var cur=editor.getCursor();var result=[];var token=editor.getTokenAt(cur),start,end,search;if(token.end>cur.ch){token.end=cur.ch;token.string=token.string.slice(0,cur.ch-token.start);}
if(token.string.match(/^[.`"\w@]\w*$/)){search=token.string;start=token.start;end=token.end;}else{start=end=cur.ch;search="";}
if(search.charAt(0)=="."||search.charAt(0)==identifierQuote){start=nameCompletion(cur,token,result,editor);}else{addMatches(result,search,tables,function(w){return w;});addMatches(result,search,defaultTable,function(w){return w;});if(!disableKeywords)
addMatches(result,search,keywords,function(w){return w.toUpperCase();});}
return{list:result,from:Pos(cur.line,start),to:Pos(cur.line,end)};});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var Pos=CodeMirror.Pos;function getHints(cm,options){var tags=options&&options.schemaInfo;var quote=(options&&options.quoteChar)||'"';if(!tags)return;var cur=cm.getCursor(),token=cm.getTokenAt(cur);if(token.end>cur.ch){token.end=cur.ch;token.string=token.string.slice(0,cur.ch-token.start);}
var inner=CodeMirror.innerMode(cm.getMode(),token.state);if(inner.mode.name!="xml")return;var result=[],replaceToken=false,prefix;var tag=/\btag\b/.test(token.type)&&!/>$/.test(token.string);var tagName=tag&&/^\w/.test(token.string),tagStart;if(tagName){var before=cm.getLine(cur.line).slice(Math.max(0,token.start-2),token.start);var tagType=/<\/$/.test(before)?"close":/<$/.test(before)?"open":null;if(tagType)tagStart=token.start-(tagType=="close"?2:1);}else if(tag&&token.string=="<"){tagType="open";}else if(tag&&token.string=="</"){tagType="close";}
if(!tag&&!inner.state.tagName||tagType){if(tagName)
prefix=token.string;replaceToken=tagType;var cx=inner.state.context,curTag=cx&&tags[cx.tagName];var childList=cx?curTag&&curTag.children:tags["!top"];if(childList&&tagType!="close"){for(var i=0;i<childList.length;++i)if(!prefix||childList[i].lastIndexOf(prefix,0)==0)
result.push("<"+childList[i]);}else if(tagType!="close"){for(var name in tags)
if(tags.hasOwnProperty(name)&&name!="!top"&&name!="!attrs"&&(!prefix||name.lastIndexOf(prefix,0)==0))
result.push("<"+name);}
if(cx&&(!prefix||tagType=="close"&&cx.tagName.lastIndexOf(prefix,0)==0))
result.push("</"+cx.tagName+">");}else{var curTag=tags[inner.state.tagName],attrs=curTag&&curTag.attrs;var globalAttrs=tags["!attrs"];if(!attrs&&!globalAttrs)return;if(!attrs){attrs=globalAttrs;}else if(globalAttrs){var set={};for(var nm in globalAttrs)if(globalAttrs.hasOwnProperty(nm))set[nm]=globalAttrs[nm];for(var nm in attrs)if(attrs.hasOwnProperty(nm))set[nm]=attrs[nm];attrs=set;}
if(token.type=="string"||token.string=="="){var before=cm.getRange(Pos(cur.line,Math.max(0,cur.ch-60)),Pos(cur.line,token.type=="string"?token.start:token.end));var atName=before.match(/([^\s\u00a0=<>\"\']+)=$/),atValues;if(!atName||!attrs.hasOwnProperty(atName[1])||!(atValues=attrs[atName[1]]))return;if(typeof atValues=='function')atValues=atValues.call(this,cm);if(token.type=="string"){prefix=token.string;var n=0;if(/['"]/.test(token.string.charAt(0))){quote=token.string.charAt(0);prefix=token.string.slice(1);n++;}
var len=token.string.length;if(/['"]/.test(token.string.charAt(len-1))){quote=token.string.charAt(len-1);prefix=token.string.substr(n,len-2);}
replaceToken=true;}
for(var i=0;i<atValues.length;++i)if(!prefix||atValues[i].lastIndexOf(prefix,0)==0)
result.push(quote+atValues[i]+quote);}else{if(token.type=="attribute"){prefix=token.string;replaceToken=true;}
for(var attr in attrs)if(attrs.hasOwnProperty(attr)&&(!prefix||attr.lastIndexOf(prefix,0)==0))
result.push(attr);}}
return{list:result,from:replaceToken?Pos(cur.line,tagStart==null?token.start:tagStart):cur,to:replaceToken?Pos(cur.line,token.end):cur};}
CodeMirror.registerHelper("hint","xml",getHints);});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.registerHelper("lint","coffeescript",function(text){var found=[];if(!window.coffeelint){if(window.console){window.console.error("Error: window.coffeelint not defined, CodeMirror CoffeeScript linting cannot run.");}
return found;}
var parseError=function(err){var loc=err.lineNumber;found.push({from:CodeMirror.Pos(loc-1,0),to:CodeMirror.Pos(loc,0),severity:err.level,message:err.message});};try{var res=coffeelint.lint(text);for(var i=0;i<res.length;i++){parseError(res[i]);}}catch(e){found.push({from:CodeMirror.Pos(e.location.first_line,0),to:CodeMirror.Pos(e.location.last_line,e.location.last_column),severity:'error',message:e.message});}
return found;});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.registerHelper("lint","css",function(text,options){var found=[];if(!window.CSSLint){if(window.console){window.console.error("Error: window.CSSLint not defined, CodeMirror CSS linting cannot run.");}
return found;}
var results=CSSLint.verify(text,options),messages=results.messages,message=null;for(var i=0;i<messages.length;i++){message=messages[i];var startLine=message.line-1,endLine=message.line-1,startCol=message.col-1,endCol=message.col;found.push({from:CodeMirror.Pos(startLine,startCol),to:CodeMirror.Pos(endLine,endCol),message:message.message,severity:message.type});}
return found;});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("htmlhint"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","htmlhint"],mod);else
mod(CodeMirror,window.HTMLHint);})(function(CodeMirror,HTMLHint){"use strict";var defaultRules={"tagname-lowercase":true,"attr-lowercase":true,"attr-value-double-quotes":true,"doctype-first":false,"tag-pair":true,"spec-char-escape":true,"id-unique":true,"src-not-empty":true,"attr-no-duplication":true};CodeMirror.registerHelper("lint","html",function(text,options){var found=[];if(HTMLHint&&!HTMLHint.verify)HTMLHint=HTMLHint.HTMLHint;if(!HTMLHint)HTMLHint=window.HTMLHint;if(!HTMLHint){if(window.console){window.console.error("Error: HTMLHint not found, not defined on window, or not available through define/require, CodeMirror HTML linting cannot run.");}
return found;}
var messages=HTMLHint.verify(text,options&&options.rules||defaultRules);for(var i=0;i<messages.length;i++){var message=messages[i];var startLine=message.line-1,endLine=message.line-1,startCol=message.col-1,endCol=message.col;found.push({from:CodeMirror.Pos(startLine,startCol),to:CodeMirror.Pos(endLine,endCol),message:message.message,severity:message.type});}
return found;});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var bogus=["Dangerous comment"];var warnings=[["Expected '{'","Statement body should be inside '{ }' braces."]];var errors=["Missing semicolon","Extra comma","Missing property name","Unmatched "," and instead saw"," is not defined","Unclosed string","Stopping, unable to continue"];function validator(text,options){if(!window.JSHINT){if(window.console){window.console.error("Error: window.JSHINT not defined, CodeMirror JavaScript linting cannot run.");}
return[];}
JSHINT(text,options,options.globals);var errors=JSHINT.data().errors,result=[];if(errors)parseErrors(errors,result);return result;}
CodeMirror.registerHelper("lint","javascript",validator);function cleanup(error){fixWith(error,warnings,"warning",true);fixWith(error,errors,"error");return isBogus(error)?null:error;}
function fixWith(error,fixes,severity,force){var description,fix,find,replace,found;description=error.description;for(var i=0;i<fixes.length;i++){fix=fixes[i];find=(typeof fix==="string"?fix:fix[0]);replace=(typeof fix==="string"?null:fix[1]);found=description.indexOf(find)!==-1;if(force||found){error.severity=severity;}
if(found&&replace){error.description=replace;}}}
function isBogus(error){var description=error.description;for(var i=0;i<bogus.length;i++){if(description.indexOf(bogus[i])!==-1){return true;}}
return false;}
function parseErrors(errors,output){for(var i=0;i<errors.length;i++){var error=errors[i];if(error){var linetabpositions,index;linetabpositions=[];if(error.evidence){var tabpositions=linetabpositions[error.line];if(!tabpositions){var evidence=error.evidence;tabpositions=[];Array.prototype.forEach.call(evidence,function(item,index){if(item==='\t'){tabpositions.push(index+1);}});linetabpositions[error.line]=tabpositions;}
if(tabpositions.length>0){var pos=error.character;tabpositions.forEach(function(tabposition){if(pos>tabposition)pos-=1;});error.character=pos;}}
var start=error.character-1,end=start+1;if(error.evidence){index=error.evidence.substring(start).search(/.\b/);if(index>-1){end+=index;}}
error.description=error.reason;error.start=error.character;error.end=end;error=cleanup(error);if(error)
output.push({message:error.description,severity:error.severity,from:CodeMirror.Pos(error.line-1,start),to:CodeMirror.Pos(error.line-1,end)});}}}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.registerHelper("lint","json",function(text){var found=[];if(!window.jsonlint){if(window.console){window.console.error("Error: window.jsonlint not defined, CodeMirror JSON linting cannot run.");}
return found;}
jsonlint.parseError=function(str,hash){var loc=hash.loc;found.push({from:CodeMirror.Pos(loc.first_line-1,loc.first_column),to:CodeMirror.Pos(loc.last_line-1,loc.last_column),message:str});};try{jsonlint.parse(text);}
catch(e){}
return found;});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var GUTTER_ID="CodeMirror-lint-markers";function showTooltip(e,content){var tt=document.createElement("div");tt.className="CodeMirror-lint-tooltip";tt.appendChild(content.cloneNode(true));document.body.appendChild(tt);function position(e){if(!tt.parentNode)return CodeMirror.off(document,"mousemove",position);tt.style.top=Math.max(0,e.clientY-tt.offsetHeight-5)+"px";tt.style.left=(e.clientX+5)+"px";}
CodeMirror.on(document,"mousemove",position);position(e);if(tt.style.opacity!=null)tt.style.opacity=1;return tt;}
function rm(elt){if(elt.parentNode)elt.parentNode.removeChild(elt);}
function hideTooltip(tt){if(!tt.parentNode)return;if(tt.style.opacity==null)rm(tt);tt.style.opacity=0;setTimeout(function(){rm(tt);},600);}
function showTooltipFor(e,content,node){var tooltip=showTooltip(e,content);function hide(){CodeMirror.off(node,"mouseout",hide);if(tooltip){hideTooltip(tooltip);tooltip=null;}}
var poll=setInterval(function(){if(tooltip)for(var n=node;;n=n.parentNode){if(n&&n.nodeType==11)n=n.host;if(n==document.body)return;if(!n){hide();break;}}
if(!tooltip)return clearInterval(poll);},400);CodeMirror.on(node,"mouseout",hide);}
function LintState(cm,options,hasGutter){this.marked=[];this.options=options;this.timeout=null;this.hasGutter=hasGutter;this.onMouseOver=function(e){onMouseOver(cm,e);};this.waitingFor=0}
function parseOptions(_cm,options){if(options instanceof Function)return{getAnnotations:options};if(!options||options===true)options={};return options;}
function clearMarks(cm){var state=cm.state.lint;if(state.hasGutter)cm.clearGutter(GUTTER_ID);for(var i=0;i<state.marked.length;++i)
state.marked[i].clear();state.marked.length=0;}
function makeMarker(labels,severity,multiple,tooltips){var marker=document.createElement("div"),inner=marker;marker.className="CodeMirror-lint-marker-"+severity;if(multiple){inner=marker.appendChild(document.createElement("div"));inner.className="CodeMirror-lint-marker-multiple";}
if(tooltips!=false)CodeMirror.on(inner,"mouseover",function(e){showTooltipFor(e,labels,inner);});return marker;}
function getMaxSeverity(a,b){if(a=="error")return a;else return b;}
function groupByLine(annotations){var lines=[];for(var i=0;i<annotations.length;++i){var ann=annotations[i],line=ann.from.line;(lines[line]||(lines[line]=[])).push(ann);}
return lines;}
function annotationTooltip(ann){var severity=ann.severity;if(!severity)severity="error";var tip=document.createElement("div");tip.className="CodeMirror-lint-message-"+severity;if(typeof ann.messageHTML!='undefined'){tip.innerHTML=ann.messageHTML;}else{tip.appendChild(document.createTextNode(ann.message));}
return tip;}
function lintAsync(cm,getAnnotations,passOptions){var state=cm.state.lint
var id=++state.waitingFor
function abort(){id=-1
cm.off("change",abort)}
cm.on("change",abort)
getAnnotations(cm.getValue(),function(annotations,arg2){cm.off("change",abort)
if(state.waitingFor!=id)return
if(arg2&&annotations instanceof CodeMirror)annotations=arg2
cm.operation(function(){updateLinting(cm,annotations)})},passOptions,cm);}
function startLinting(cm){var state=cm.state.lint,options=state.options;var passOptions=options.options||options;var getAnnotations=options.getAnnotations||cm.getHelper(CodeMirror.Pos(0,0),"lint");if(!getAnnotations)return;if(options.async||getAnnotations.async){lintAsync(cm,getAnnotations,passOptions)}else{var annotations=getAnnotations(cm.getValue(),passOptions,cm);if(!annotations)return;if(annotations.then)annotations.then(function(issues){cm.operation(function(){updateLinting(cm,issues)})});else cm.operation(function(){updateLinting(cm,annotations)})}}
function updateLinting(cm,annotationsNotSorted){clearMarks(cm);var state=cm.state.lint,options=state.options;var annotations=groupByLine(annotationsNotSorted);for(var line=0;line<annotations.length;++line){var anns=annotations[line];if(!anns)continue;var maxSeverity=null;var tipLabel=state.hasGutter&&document.createDocumentFragment();for(var i=0;i<anns.length;++i){var ann=anns[i];var severity=ann.severity;if(!severity)severity="error";maxSeverity=getMaxSeverity(maxSeverity,severity);if(options.formatAnnotation)ann=options.formatAnnotation(ann);if(state.hasGutter)tipLabel.appendChild(annotationTooltip(ann));if(ann.to)state.marked.push(cm.markText(ann.from,ann.to,{className:"CodeMirror-lint-mark-"+severity,__annotation:ann}));}
if(state.hasGutter)
cm.setGutterMarker(line,GUTTER_ID,makeMarker(tipLabel,maxSeverity,anns.length>1,state.options.tooltips));}
if(options.onUpdateLinting)options.onUpdateLinting(annotationsNotSorted,annotations,cm);}
function onChange(cm){var state=cm.state.lint;if(!state)return;clearTimeout(state.timeout);state.timeout=setTimeout(function(){startLinting(cm);},state.options.delay||500);}
function popupTooltips(annotations,e){var target=e.target||e.srcElement;var tooltip=document.createDocumentFragment();for(var i=0;i<annotations.length;i++){var ann=annotations[i];tooltip.appendChild(annotationTooltip(ann));}
showTooltipFor(e,tooltip,target);}
function onMouseOver(cm,e){var target=e.target||e.srcElement;if(!/\bCodeMirror-lint-mark-/.test(target.className))return;var box=target.getBoundingClientRect(),x=(box.left+box.right)/ 2,y=(box.top+box.bottom)/ 2;var spans=cm.findMarksAt(cm.coordsChar({left:x,top:y},"client"));var annotations=[];for(var i=0;i<spans.length;++i){var ann=spans[i].__annotation;if(ann)annotations.push(ann);}
if(annotations.length)popupTooltips(annotations,e);}
CodeMirror.defineOption("lint",false,function(cm,val,old){if(old&&old!=CodeMirror.Init){clearMarks(cm);if(cm.state.lint.options.lintOnChange!==false)
cm.off("change",onChange);CodeMirror.off(cm.getWrapperElement(),"mouseover",cm.state.lint.onMouseOver);clearTimeout(cm.state.lint.timeout);delete cm.state.lint;}
if(val){var gutters=cm.getOption("gutters"),hasLintGutter=false;for(var i=0;i<gutters.length;++i)if(gutters[i]==GUTTER_ID)hasLintGutter=true;var state=cm.state.lint=new LintState(cm,parseOptions(cm,val),hasLintGutter);if(state.options.lintOnChange!==false)
cm.on("change",onChange);if(state.options.tooltips!=false&&state.options.tooltips!="gutter")
CodeMirror.on(cm.getWrapperElement(),"mouseover",state.onMouseOver);startLinting(cm);}});CodeMirror.defineExtension("performLint",function(){if(this.state.lint)startLinting(this);});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.registerHelper("lint","yaml",function(text){var found=[];if(!window.jsyaml){if(window.console){window.console.error("Error: window.jsyaml not defined, CodeMirror YAML linting cannot run.");}
return found;}
try{jsyaml.load(text);}
catch(e){var loc=e.mark,from=loc?CodeMirror.Pos(loc.line,loc.column):CodeMirror.Pos(0,0),to=from;found.push({from:from,to:to,message:e.message});}
return found;});});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","diff_match_patch"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var Pos=CodeMirror.Pos;var svgNS="http://www.w3.org/2000/svg";function DiffView(mv,type){this.mv=mv;this.type=type;this.classes=type=="left"?{chunk:"CodeMirror-merge-l-chunk",start:"CodeMirror-merge-l-chunk-start",end:"CodeMirror-merge-l-chunk-end",insert:"CodeMirror-merge-l-inserted",del:"CodeMirror-merge-l-deleted",connect:"CodeMirror-merge-l-connect"}:{chunk:"CodeMirror-merge-r-chunk",start:"CodeMirror-merge-r-chunk-start",end:"CodeMirror-merge-r-chunk-end",insert:"CodeMirror-merge-r-inserted",del:"CodeMirror-merge-r-deleted",connect:"CodeMirror-merge-r-connect"};}
DiffView.prototype={constructor:DiffView,init:function(pane,orig,options){this.edit=this.mv.edit;;(this.edit.state.diffViews||(this.edit.state.diffViews=[])).push(this);this.orig=CodeMirror(pane,copyObj({value:orig,readOnly:!this.mv.options.allowEditingOriginals},copyObj(options)));if(this.mv.options.connect=="align"){if(!this.edit.state.trackAlignable)this.edit.state.trackAlignable=new TrackAlignable(this.edit)
this.orig.state.trackAlignable=new TrackAlignable(this.orig)}
this.orig.state.diffViews=[this];var classLocation=options.chunkClassLocation||"background";if(Object.prototype.toString.call(classLocation)!="[object Array]")classLocation=[classLocation]
this.classes.classLocation=classLocation
this.diff=getDiff(asString(orig),asString(options.value),this.mv.options.ignoreWhitespace);this.chunks=getChunks(this.diff);this.diffOutOfDate=this.dealigned=false;this.needsScrollSync=null
this.showDifferences=options.showDifferences!==false;},registerEvents:function(otherDv){this.forceUpdate=registerUpdate(this);setScrollLock(this,true,false);registerScroll(this,otherDv);},setShowDifferences:function(val){val=val!==false;if(val!=this.showDifferences){this.showDifferences=val;this.forceUpdate("full");}}};function ensureDiff(dv){if(dv.diffOutOfDate){dv.diff=getDiff(dv.orig.getValue(),dv.edit.getValue(),dv.mv.options.ignoreWhitespace);dv.chunks=getChunks(dv.diff);dv.diffOutOfDate=false;CodeMirror.signal(dv.edit,"updateDiff",dv.diff);}}
var updating=false;function registerUpdate(dv){var edit={from:0,to:0,marked:[]};var orig={from:0,to:0,marked:[]};var debounceChange,updatingFast=false;function update(mode){updating=true;updatingFast=false;if(mode=="full"){if(dv.svg)clear(dv.svg);if(dv.copyButtons)clear(dv.copyButtons);clearMarks(dv.edit,edit.marked,dv.classes);clearMarks(dv.orig,orig.marked,dv.classes);edit.from=edit.to=orig.from=orig.to=0;}
ensureDiff(dv);if(dv.showDifferences){updateMarks(dv.edit,dv.diff,edit,DIFF_INSERT,dv.classes);updateMarks(dv.orig,dv.diff,orig,DIFF_DELETE,dv.classes);}
if(dv.mv.options.connect=="align")
alignChunks(dv);makeConnections(dv);if(dv.needsScrollSync!=null)syncScroll(dv,dv.needsScrollSync)
updating=false;}
function setDealign(fast){if(updating)return;dv.dealigned=true;set(fast);}
function set(fast){if(updating||updatingFast)return;clearTimeout(debounceChange);if(fast===true)updatingFast=true;debounceChange=setTimeout(update,fast===true?20:250);}
function change(_cm,change){if(!dv.diffOutOfDate){dv.diffOutOfDate=true;edit.from=edit.to=orig.from=orig.to=0;}
setDealign(change.text.length-1!=change.to.line-change.from.line);}
function swapDoc(){dv.diffOutOfDate=true;dv.dealigned=true;update("full");}
dv.edit.on("change",change);dv.orig.on("change",change);dv.edit.on("swapDoc",swapDoc);dv.orig.on("swapDoc",swapDoc);if(dv.mv.options.connect=="align"){CodeMirror.on(dv.edit.state.trackAlignable,"realign",setDealign)
CodeMirror.on(dv.orig.state.trackAlignable,"realign",setDealign)}
dv.edit.on("viewportChange",function(){set(false);});dv.orig.on("viewportChange",function(){set(false);});update();return update;}
function registerScroll(dv,otherDv){dv.edit.on("scroll",function(){syncScroll(dv,true)&&makeConnections(dv);});dv.orig.on("scroll",function(){syncScroll(dv,false)&&makeConnections(dv);if(otherDv)syncScroll(otherDv,true)&&makeConnections(otherDv);});}
function syncScroll(dv,toOrig){if(dv.diffOutOfDate){if(dv.lockScroll&&dv.needsScrollSync==null)dv.needsScrollSync=toOrig
return false}
dv.needsScrollSync=null
if(!dv.lockScroll)return true;var editor,other,now=+new Date;if(toOrig){editor=dv.edit;other=dv.orig;}
else{editor=dv.orig;other=dv.edit;}
if(editor.state.scrollSetBy==dv&&(editor.state.scrollSetAt||0)+250>now)return false;var sInfo=editor.getScrollInfo();if(dv.mv.options.connect=="align"){targetPos=sInfo.top;}else{var halfScreen=.5*sInfo.clientHeight,midY=sInfo.top+halfScreen;var mid=editor.lineAtHeight(midY,"local");var around=chunkBoundariesAround(dv.chunks,mid,toOrig);var off=getOffsets(editor,toOrig?around.edit:around.orig);var offOther=getOffsets(other,toOrig?around.orig:around.edit);var ratio=(midY-off.top)/(off.bot-off.top);var targetPos=(offOther.top-halfScreen)+ratio*(offOther.bot-offOther.top);var botDist,mix;if(targetPos>sInfo.top&&(mix=sInfo.top / halfScreen)<1){targetPos=targetPos*mix+sInfo.top*(1-mix);}else if((botDist=sInfo.height-sInfo.clientHeight-sInfo.top)<halfScreen){var otherInfo=other.getScrollInfo();var botDistOther=otherInfo.height-otherInfo.clientHeight-targetPos;if(botDistOther>botDist&&(mix=botDist / halfScreen)<1)
targetPos=targetPos*mix+(otherInfo.height-otherInfo.clientHeight-botDist)*(1-mix);}}
other.scrollTo(sInfo.left,targetPos);other.state.scrollSetAt=now;other.state.scrollSetBy=dv;return true;}
function getOffsets(editor,around){var bot=around.after;if(bot==null)bot=editor.lastLine()+1;return{top:editor.heightAtLine(around.before||0,"local"),bot:editor.heightAtLine(bot,"local")};}
function setScrollLock(dv,val,action){dv.lockScroll=val;if(val&&action!=false)syncScroll(dv,DIFF_INSERT)&&makeConnections(dv);dv.lockButton.innerHTML=val?"\u21db\u21da":"\u21db&nbsp;&nbsp;\u21da";}
function removeClass(editor,line,classes){var locs=classes.classLocation
for(var i=0;i<locs.length;i++){editor.removeLineClass(line,locs[i],classes.chunk);editor.removeLineClass(line,locs[i],classes.start);editor.removeLineClass(line,locs[i],classes.end);}}
function clearMarks(editor,arr,classes){for(var i=0;i<arr.length;++i){var mark=arr[i];if(mark instanceof CodeMirror.TextMarker)
mark.clear();else if(mark.parent)
removeClass(editor,mark,classes);}
arr.length=0;}
function updateMarks(editor,diff,state,type,classes){var vp=editor.getViewport();editor.operation(function(){if(state.from==state.to||vp.from-state.to>20||state.from-vp.to>20){clearMarks(editor,state.marked,classes);markChanges(editor,diff,type,state.marked,vp.from,vp.to,classes);state.from=vp.from;state.to=vp.to;}else{if(vp.from<state.from){markChanges(editor,diff,type,state.marked,vp.from,state.from,classes);state.from=vp.from;}
if(vp.to>state.to){markChanges(editor,diff,type,state.marked,state.to,vp.to,classes);state.to=vp.to;}}});}
function addClass(editor,lineNr,classes,main,start,end){var locs=classes.classLocation,line=editor.getLineHandle(lineNr);for(var i=0;i<locs.length;i++){if(main)editor.addLineClass(line,locs[i],classes.chunk);if(start)editor.addLineClass(line,locs[i],classes.start);if(end)editor.addLineClass(line,locs[i],classes.end);}
return line;}
function markChanges(editor,diff,type,marks,from,to,classes){var pos=Pos(0,0);var top=Pos(from,0),bot=editor.clipPos(Pos(to-1));var cls=type==DIFF_DELETE?classes.del:classes.insert;function markChunk(start,end){var bfrom=Math.max(from,start),bto=Math.min(to,end);for(var i=bfrom;i<bto;++i)
marks.push(addClass(editor,i,classes,true,i==start,i==end-1));if(start==end&&bfrom==end&&bto==end){if(bfrom)
marks.push(addClass(editor,bfrom-1,classes,false,false,true));else
marks.push(addClass(editor,bfrom,classes,false,true,false));}}
var chunkStart=0,pending=false;for(var i=0;i<diff.length;++i){var part=diff[i],tp=part[0],str=part[1];if(tp==DIFF_EQUAL){var cleanFrom=pos.line+(startOfLineClean(diff,i)?0:1);moveOver(pos,str);var cleanTo=pos.line+(endOfLineClean(diff,i)?1:0);if(cleanTo>cleanFrom){if(pending){markChunk(chunkStart,cleanFrom);pending=false}
chunkStart=cleanTo;}}else{pending=true
if(tp==type){var end=moveOver(pos,str,true);var a=posMax(top,pos),b=posMin(bot,end);if(!posEq(a,b))
marks.push(editor.markText(a,b,{className:cls}));pos=end;}}}
if(pending)markChunk(chunkStart,pos.line+1);}
function makeConnections(dv){if(!dv.showDifferences)return;if(dv.svg){clear(dv.svg);var w=dv.gap.offsetWidth;attrs(dv.svg,"width",w,"height",dv.gap.offsetHeight);}
if(dv.copyButtons)clear(dv.copyButtons);var vpEdit=dv.edit.getViewport(),vpOrig=dv.orig.getViewport();var outerTop=dv.mv.wrap.getBoundingClientRect().top
var sTopEdit=outerTop-dv.edit.getScrollerElement().getBoundingClientRect().top+dv.edit.getScrollInfo().top
var sTopOrig=outerTop-dv.orig.getScrollerElement().getBoundingClientRect().top+dv.orig.getScrollInfo().top;for(var i=0;i<dv.chunks.length;i++){var ch=dv.chunks[i];if(ch.editFrom<=vpEdit.to&&ch.editTo>=vpEdit.from&&ch.origFrom<=vpOrig.to&&ch.origTo>=vpOrig.from)
drawConnectorsForChunk(dv,ch,sTopOrig,sTopEdit,w);}}
function getMatchingOrigLine(editLine,chunks){var editStart=0,origStart=0;for(var i=0;i<chunks.length;i++){var chunk=chunks[i];if(chunk.editTo>editLine&&chunk.editFrom<=editLine)return null;if(chunk.editFrom>editLine)break;editStart=chunk.editTo;origStart=chunk.origTo;}
return origStart+(editLine-editStart);}
function alignableFor(cm,chunks,isOrig){var tracker=cm.state.trackAlignable
var start=cm.firstLine(),trackI=0
var result=[]
for(var i=0;;i++){var chunk=chunks[i]
var chunkStart=!chunk?1e9:isOrig?chunk.origFrom:chunk.editFrom
for(;trackI<tracker.alignable.length;trackI+=2){var n=tracker.alignable[trackI]+1
if(n<=start)continue
if(n<=chunkStart)result.push(n)
else break}
if(!chunk)break
result.push(start=isOrig?chunk.origTo:chunk.editTo)}
return result}
function mergeAlignable(result,origAlignable,chunks,setIndex){var rI=0,origI=0,chunkI=0,diff=0
outer:for(;;rI++){var nextR=result[rI],nextO=origAlignable[origI]
if(!nextR&&nextO==null)break
var rLine=nextR?nextR[0]:1e9,oLine=nextO==null?1e9:nextO
while(chunkI<chunks.length){var chunk=chunks[chunkI]
if(chunk.origFrom<=oLine&&chunk.origTo>oLine){origI++
rI--
continue outer;}
if(chunk.editTo>rLine){if(chunk.editFrom<=rLine)continue outer;break}
diff+=(chunk.origTo-chunk.origFrom)-(chunk.editTo-chunk.editFrom)
chunkI++}
if(rLine==oLine-diff){nextR[setIndex]=oLine
origI++}else if(rLine<oLine-diff){nextR[setIndex]=rLine+diff}else{var record=[oLine-diff,null,null]
record[setIndex]=oLine
result.splice(rI,0,record)
origI++}}}
function findAlignedLines(dv,other){var alignable=alignableFor(dv.edit,dv.chunks,false),result=[]
if(other)for(var i=0,j=0;i<other.chunks.length;i++){var n=other.chunks[i].editTo
while(j<alignable.length&&alignable[j]<n)j++
if(j==alignable.length||alignable[j]!=n)alignable.splice(j++,0,n)}
for(var i=0;i<alignable.length;i++)
result.push([alignable[i],null,null])
mergeAlignable(result,alignableFor(dv.orig,dv.chunks,true),dv.chunks,1)
if(other)
mergeAlignable(result,alignableFor(other.orig,other.chunks,true),other.chunks,2)
return result}
function alignChunks(dv,force){if(!dv.dealigned&&!force)return;if(!dv.orig.curOp)return dv.orig.operation(function(){alignChunks(dv,force);});dv.dealigned=false;var other=dv.mv.left==dv?dv.mv.right:dv.mv.left;if(other){ensureDiff(other);other.dealigned=false;}
var linesToAlign=findAlignedLines(dv,other);var aligners=dv.mv.aligners;for(var i=0;i<aligners.length;i++)
aligners[i].clear();aligners.length=0;var cm=[dv.edit,dv.orig],scroll=[];if(other)cm.push(other.orig);for(var i=0;i<cm.length;i++)
scroll.push(cm[i].getScrollInfo().top);for(var ln=0;ln<linesToAlign.length;ln++)
alignLines(cm,linesToAlign[ln],aligners);for(var i=0;i<cm.length;i++)
cm[i].scrollTo(null,scroll[i]);}
function alignLines(cm,lines,aligners){var maxOffset=0,offset=[];for(var i=0;i<cm.length;i++)if(lines[i]!=null){var off=cm[i].heightAtLine(lines[i],"local");offset[i]=off;maxOffset=Math.max(maxOffset,off);}
for(var i=0;i<cm.length;i++)if(lines[i]!=null){var diff=maxOffset-offset[i];if(diff>1)
aligners.push(padAbove(cm[i],lines[i],diff));}}
function padAbove(cm,line,size){var above=true;if(line>cm.lastLine()){line--;above=false;}
var elt=document.createElement("div");elt.className="CodeMirror-merge-spacer";elt.style.height=size+"px";elt.style.minWidth="1px";return cm.addLineWidget(line,elt,{height:size,above:above,mergeSpacer:true,handleMouseEvents:true});}
function drawConnectorsForChunk(dv,chunk,sTopOrig,sTopEdit,w){var flip=dv.type=="left";var top=dv.orig.heightAtLine(chunk.origFrom,"local",true)-sTopOrig;if(dv.svg){var topLpx=top;var topRpx=dv.edit.heightAtLine(chunk.editFrom,"local",true)-sTopEdit;if(flip){var tmp=topLpx;topLpx=topRpx;topRpx=tmp;}
var botLpx=dv.orig.heightAtLine(chunk.origTo,"local",true)-sTopOrig;var botRpx=dv.edit.heightAtLine(chunk.editTo,"local",true)-sTopEdit;if(flip){var tmp=botLpx;botLpx=botRpx;botRpx=tmp;}
var curveTop=" C "+w/2+" "+topRpx+" "+w/2+" "+topLpx+" "+(w+2)+" "+topLpx;var curveBot=" C "+w/2+" "+botLpx+" "+w/2+" "+botRpx+" -1 "+botRpx;attrs(dv.svg.appendChild(document.createElementNS(svgNS,"path")),"d","M -1 "+topRpx+curveTop+" L "+(w+2)+" "+botLpx+curveBot+" z","class",dv.classes.connect);}
if(dv.copyButtons){var copy=dv.copyButtons.appendChild(elt("div",dv.type=="left"?"\u21dd":"\u21dc","CodeMirror-merge-copy"));var editOriginals=dv.mv.options.allowEditingOriginals;copy.title=editOriginals?"Push to left":"Revert chunk";copy.chunk=chunk;copy.style.top=(chunk.origTo>chunk.origFrom?top:dv.edit.heightAtLine(chunk.editFrom,"local")-sTopEdit)+"px";if(editOriginals){var topReverse=dv.edit.heightAtLine(chunk.editFrom,"local")-sTopEdit;var copyReverse=dv.copyButtons.appendChild(elt("div",dv.type=="right"?"\u21dd":"\u21dc","CodeMirror-merge-copy-reverse"));copyReverse.title="Push to right";copyReverse.chunk={editFrom:chunk.origFrom,editTo:chunk.origTo,origFrom:chunk.editFrom,origTo:chunk.editTo};copyReverse.style.top=topReverse+"px";dv.type=="right"?copyReverse.style.left="2px":copyReverse.style.right="2px";}}}
function copyChunk(dv,to,from,chunk){if(dv.diffOutOfDate)return;var origStart=chunk.origTo>from.lastLine()?Pos(chunk.origFrom-1):Pos(chunk.origFrom,0)
var origEnd=Pos(chunk.origTo,0)
var editStart=chunk.editTo>to.lastLine()?Pos(chunk.editFrom-1):Pos(chunk.editFrom,0)
var editEnd=Pos(chunk.editTo,0)
var handler=dv.mv.options.revertChunk
if(handler)
handler(dv.mv,from,origStart,origEnd,to,editStart,editEnd)
else
to.replaceRange(from.getRange(origStart,origEnd),editStart,editEnd)}
var MergeView=CodeMirror.MergeView=function(node,options){if(!(this instanceof MergeView))return new MergeView(node,options);this.options=options;var origLeft=options.origLeft,origRight=options.origRight==null?options.orig:options.origRight;var hasLeft=origLeft!=null,hasRight=origRight!=null;var panes=1+(hasLeft?1:0)+(hasRight?1:0);var wrap=[],left=this.left=null,right=this.right=null;var self=this;if(hasLeft){left=this.left=new DiffView(this,"left");var leftPane=elt("div",null,"CodeMirror-merge-pane CodeMirror-merge-left");wrap.push(leftPane);wrap.push(buildGap(left));}
var editPane=elt("div",null,"CodeMirror-merge-pane CodeMirror-merge-editor");wrap.push(editPane);if(hasRight){right=this.right=new DiffView(this,"right");wrap.push(buildGap(right));var rightPane=elt("div",null,"CodeMirror-merge-pane CodeMirror-merge-right");wrap.push(rightPane);}
(hasRight?rightPane:editPane).className+=" CodeMirror-merge-pane-rightmost";wrap.push(elt("div",null,null,"height: 0; clear: both;"));var wrapElt=this.wrap=node.appendChild(elt("div",wrap,"CodeMirror-merge CodeMirror-merge-"+panes+"pane"));this.edit=CodeMirror(editPane,copyObj(options));if(left)left.init(leftPane,origLeft,options);if(right)right.init(rightPane,origRight,options);if(options.collapseIdentical)
this.editor().operation(function(){collapseIdenticalStretches(self,options.collapseIdentical);});if(options.connect=="align"){this.aligners=[];alignChunks(this.left||this.right,true);}
if(left)left.registerEvents(right)
if(right)right.registerEvents(left)
var onResize=function(){if(left)makeConnections(left);if(right)makeConnections(right);};CodeMirror.on(window,"resize",onResize);var resizeInterval=setInterval(function(){for(var p=wrapElt.parentNode;p&&p!=document.body;p=p.parentNode){}
if(!p){clearInterval(resizeInterval);CodeMirror.off(window,"resize",onResize);}},5000);};function buildGap(dv){var lock=dv.lockButton=elt("div",null,"CodeMirror-merge-scrolllock");lock.title="Toggle locked scrolling";var lockWrap=elt("div",[lock],"CodeMirror-merge-scrolllock-wrap");CodeMirror.on(lock,"click",function(){setScrollLock(dv,!dv.lockScroll);});var gapElts=[lockWrap];if(dv.mv.options.revertButtons!==false){dv.copyButtons=elt("div",null,"CodeMirror-merge-copybuttons-"+dv.type);CodeMirror.on(dv.copyButtons,"click",function(e){var node=e.target||e.srcElement;if(!node.chunk)return;if(node.className=="CodeMirror-merge-copy-reverse"){copyChunk(dv,dv.orig,dv.edit,node.chunk);return;}
copyChunk(dv,dv.edit,dv.orig,node.chunk);});gapElts.unshift(dv.copyButtons);}
if(dv.mv.options.connect!="align"){var svg=document.createElementNS&&document.createElementNS(svgNS,"svg");if(svg&&!svg.createSVGRect)svg=null;dv.svg=svg;if(svg)gapElts.push(svg);}
return dv.gap=elt("div",gapElts,"CodeMirror-merge-gap");}
MergeView.prototype={constructor:MergeView,editor:function(){return this.edit;},rightOriginal:function(){return this.right&&this.right.orig;},leftOriginal:function(){return this.left&&this.left.orig;},setShowDifferences:function(val){if(this.right)this.right.setShowDifferences(val);if(this.left)this.left.setShowDifferences(val);},rightChunks:function(){if(this.right){ensureDiff(this.right);return this.right.chunks;}},leftChunks:function(){if(this.left){ensureDiff(this.left);return this.left.chunks;}}};function asString(obj){if(typeof obj=="string")return obj;else return obj.getValue();}
var dmp;function getDiff(a,b,ignoreWhitespace){if(!dmp)dmp=new diff_match_patch();var diff=dmp.diff_main(a,b);for(var i=0;i<diff.length;++i){var part=diff[i];if(ignoreWhitespace?!/[^ \t]/.test(part[1]):!part[1]){diff.splice(i--,1);}else if(i&&diff[i-1][0]==part[0]){diff.splice(i--,1);diff[i][1]+=part[1];}}
return diff;}
function getChunks(diff){var chunks=[];var startEdit=0,startOrig=0;var edit=Pos(0,0),orig=Pos(0,0);for(var i=0;i<diff.length;++i){var part=diff[i],tp=part[0];if(tp==DIFF_EQUAL){var startOff=!startOfLineClean(diff,i)||edit.line<startEdit||orig.line<startOrig?1:0;var cleanFromEdit=edit.line+startOff,cleanFromOrig=orig.line+startOff;moveOver(edit,part[1],null,orig);var endOff=endOfLineClean(diff,i)?1:0;var cleanToEdit=edit.line+endOff,cleanToOrig=orig.line+endOff;if(cleanToEdit>cleanFromEdit){if(i)chunks.push({origFrom:startOrig,origTo:cleanFromOrig,editFrom:startEdit,editTo:cleanFromEdit});startEdit=cleanToEdit;startOrig=cleanToOrig;}}else{moveOver(tp==DIFF_INSERT?edit:orig,part[1]);}}
if(startEdit<=edit.line||startOrig<=orig.line)
chunks.push({origFrom:startOrig,origTo:orig.line+1,editFrom:startEdit,editTo:edit.line+1});return chunks;}
function endOfLineClean(diff,i){if(i==diff.length-1)return true;var next=diff[i+1][1];if((next.length==1&&i<diff.length-2)||next.charCodeAt(0)!=10)return false;if(i==diff.length-2)return true;next=diff[i+2][1];return(next.length>1||i==diff.length-3)&&next.charCodeAt(0)==10;}
function startOfLineClean(diff,i){if(i==0)return true;var last=diff[i-1][1];if(last.charCodeAt(last.length-1)!=10)return false;if(i==1)return true;last=diff[i-2][1];return last.charCodeAt(last.length-1)==10;}
function chunkBoundariesAround(chunks,n,nInEdit){var beforeE,afterE,beforeO,afterO;for(var i=0;i<chunks.length;i++){var chunk=chunks[i];var fromLocal=nInEdit?chunk.editFrom:chunk.origFrom;var toLocal=nInEdit?chunk.editTo:chunk.origTo;if(afterE==null){if(fromLocal>n){afterE=chunk.editFrom;afterO=chunk.origFrom;}
else if(toLocal>n){afterE=chunk.editTo;afterO=chunk.origTo;}}
if(toLocal<=n){beforeE=chunk.editTo;beforeO=chunk.origTo;}
else if(fromLocal<=n){beforeE=chunk.editFrom;beforeO=chunk.origFrom;}}
return{edit:{before:beforeE,after:afterE},orig:{before:beforeO,after:afterO}};}
function collapseSingle(cm,from,to){cm.addLineClass(from,"wrap","CodeMirror-merge-collapsed-line");var widget=document.createElement("span");widget.className="CodeMirror-merge-collapsed-widget";widget.title="Identical text collapsed. Click to expand.";var mark=cm.markText(Pos(from,0),Pos(to-1),{inclusiveLeft:true,inclusiveRight:true,replacedWith:widget,clearOnEnter:true});function clear(){mark.clear();cm.removeLineClass(from,"wrap","CodeMirror-merge-collapsed-line");}
if(mark.explicitlyCleared)clear();CodeMirror.on(widget,"click",clear);mark.on("clear",clear);CodeMirror.on(widget,"click",clear);return{mark:mark,clear:clear};}
function collapseStretch(size,editors){var marks=[];function clear(){for(var i=0;i<marks.length;i++)marks[i].clear();}
for(var i=0;i<editors.length;i++){var editor=editors[i];var mark=collapseSingle(editor.cm,editor.line,editor.line+size);marks.push(mark);mark.mark.on("clear",clear);}
return marks[0].mark;}
function unclearNearChunks(dv,margin,off,clear){for(var i=0;i<dv.chunks.length;i++){var chunk=dv.chunks[i];for(var l=chunk.editFrom-margin;l<chunk.editTo+margin;l++){var pos=l+off;if(pos>=0&&pos<clear.length)clear[pos]=false;}}}
function collapseIdenticalStretches(mv,margin){if(typeof margin!="number")margin=2;var clear=[],edit=mv.editor(),off=edit.firstLine();for(var l=off,e=edit.lastLine();l<=e;l++)clear.push(true);if(mv.left)unclearNearChunks(mv.left,margin,off,clear);if(mv.right)unclearNearChunks(mv.right,margin,off,clear);for(var i=0;i<clear.length;i++){if(clear[i]){var line=i+off;for(var size=1;i<clear.length-1&&clear[i+1];i++,size++){}
if(size>margin){var editors=[{line:line,cm:edit}];if(mv.left)editors.push({line:getMatchingOrigLine(line,mv.left.chunks),cm:mv.left.orig});if(mv.right)editors.push({line:getMatchingOrigLine(line,mv.right.chunks),cm:mv.right.orig});var mark=collapseStretch(size,editors);if(mv.options.onCollapse)mv.options.onCollapse(mv,line,size,mark);}}}}
function elt(tag,content,className,style){var e=document.createElement(tag);if(className)e.className=className;if(style)e.style.cssText=style;if(typeof content=="string")e.appendChild(document.createTextNode(content));else if(content)for(var i=0;i<content.length;++i)e.appendChild(content[i]);return e;}
function clear(node){for(var count=node.childNodes.length;count>0;--count)
node.removeChild(node.firstChild);}
function attrs(elt){for(var i=1;i<arguments.length;i+=2)
elt.setAttribute(arguments[i],arguments[i+1]);}
function copyObj(obj,target){if(!target)target={};for(var prop in obj)if(obj.hasOwnProperty(prop))target[prop]=obj[prop];return target;}
function moveOver(pos,str,copy,other){var out=copy?Pos(pos.line,pos.ch):pos,at=0;for(;;){var nl=str.indexOf("\n",at);if(nl==-1)break;++out.line;if(other)++other.line;at=nl+1;}
out.ch=(at?0:out.ch)+(str.length-at);if(other)other.ch=(at?0:other.ch)+(str.length-at);return out;}
var F_WIDGET=1,F_WIDGET_BELOW=2,F_MARKER=4
function TrackAlignable(cm){this.cm=cm
this.alignable=[]
this.height=cm.doc.height
var self=this
cm.on("markerAdded",function(_,marker){if(!marker.collapsed)return
var found=marker.find(1)
if(found!=null)self.set(found.line,F_MARKER)})
cm.on("markerCleared",function(_,marker,_min,max){if(max!=null&&marker.collapsed)
self.check(max,F_MARKER,self.hasMarker)})
cm.on("markerChanged",this.signal.bind(this))
cm.on("lineWidgetAdded",function(_,widget,lineNo){if(widget.mergeSpacer)return
if(widget.above)self.set(lineNo-1,F_WIDGET_BELOW)
else self.set(lineNo,F_WIDGET)})
cm.on("lineWidgetCleared",function(_,widget,lineNo){if(widget.mergeSpacer)return
if(widget.above)self.check(lineNo-1,F_WIDGET_BELOW,self.hasWidgetBelow)
else self.check(lineNo,F_WIDGET,self.hasWidget)})
cm.on("lineWidgetChanged",this.signal.bind(this))
cm.on("change",function(_,change){var start=change.from.line,nBefore=change.to.line-change.from.line
var nAfter=change.text.length-1,end=start+nAfter
if(nBefore||nAfter)self.map(start,nBefore,nAfter)
self.check(end,F_MARKER,self.hasMarker)
if(nBefore||nAfter)self.check(change.from.line,F_MARKER,self.hasMarker)})
cm.on("viewportChange",function(){if(self.cm.doc.height!=self.height)self.signal()})}
TrackAlignable.prototype={signal:function(){CodeMirror.signal(this,"realign")
this.height=this.cm.doc.height},set:function(n,flags){var pos=-1
for(;pos<this.alignable.length;pos+=2){var diff=this.alignable[pos]-n
if(diff==0){if((this.alignable[pos+1]&flags)==flags)return
this.alignable[pos+1]|=flags
this.signal()
return}
if(diff>0)break}
this.signal()
this.alignable.splice(pos,0,n,flags)},find:function(n){for(var i=0;i<this.alignable.length;i+=2)
if(this.alignable[i]==n)return i
return-1},check:function(n,flag,pred){var found=this.find(n)
if(found==-1||!(this.alignable[found+1]&flag))return
if(!pred.call(this,n)){this.signal()
var flags=this.alignable[found+1]&~flag
if(flags)this.alignable[found+1]=flags
else this.alignable.splice(found,2)}},hasMarker:function(n){var handle=this.cm.getLineHandle(n)
if(handle.markedSpans)for(var i=0;i<handle.markedSpans.length;i++)
if(handle.markedSpans[i].mark.collapsed&&handle.markedSpans[i].to!=null)
return true
return false},hasWidget:function(n){var handle=this.cm.getLineHandle(n)
if(handle.widgets)for(var i=0;i<handle.widgets.length;i++)
if(!handle.widgets[i].above&&!handle.widgets[i].mergeSpacer)return true
return false},hasWidgetBelow:function(n){if(n==this.cm.lastLine())return false
var handle=this.cm.getLineHandle(n+1)
if(handle.widgets)for(var i=0;i<handle.widgets.length;i++)
if(handle.widgets[i].above&&!handle.widgets[i].mergeSpacer)return true
return false},map:function(from,nBefore,nAfter){var diff=nAfter-nBefore,to=from+nBefore,widgetFrom=-1,widgetTo=-1
for(var i=0;i<this.alignable.length;i+=2){var n=this.alignable[i]
if(n==from&&(this.alignable[i+1]&F_WIDGET_BELOW))widgetFrom=i
if(n==to&&(this.alignable[i+1]&F_WIDGET_BELOW))widgetTo=i
if(n<=from)continue
else if(n<to)this.alignable.splice(i--,2)
else this.alignable[i]+=diff}
if(widgetFrom>-1){var flags=this.alignable[widgetFrom+1]
if(flags==F_WIDGET_BELOW)this.alignable.splice(widgetFrom,2)
else this.alignable[widgetFrom+1]=flags&~F_WIDGET_BELOW}
if(widgetTo>-1&&nAfter)
this.set(from+nAfter,F_WIDGET_BELOW)}}
function posMin(a,b){return(a.line-b.line||a.ch-b.ch)<0?a:b;}
function posMax(a,b){return(a.line-b.line||a.ch-b.ch)>0?a:b;}
function posEq(a,b){return a.line==b.line&&a.ch==b.ch;}
function findPrevDiff(chunks,start,isOrig){for(var i=chunks.length-1;i>=0;i--){var chunk=chunks[i];var to=(isOrig?chunk.origTo:chunk.editTo)-1;if(to<start)return to;}}
function findNextDiff(chunks,start,isOrig){for(var i=0;i<chunks.length;i++){var chunk=chunks[i];var from=(isOrig?chunk.origFrom:chunk.editFrom);if(from>start)return from;}}
function goNearbyDiff(cm,dir){var found=null,views=cm.state.diffViews,line=cm.getCursor().line;if(views)for(var i=0;i<views.length;i++){var dv=views[i],isOrig=cm==dv.orig;ensureDiff(dv);var pos=dir<0?findPrevDiff(dv.chunks,line,isOrig):findNextDiff(dv.chunks,line,isOrig);if(pos!=null&&(found==null||(dir<0?pos>found:pos<found)))
found=pos;}
if(found!=null)
cm.setCursor(found,0);else
return CodeMirror.Pass;}
CodeMirror.commands.goNextDiff=function(cm){return goNearbyDiff(cm,1);};CodeMirror.commands.goPrevDiff=function(cm){return goNearbyDiff(cm,-1);};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),"cjs");else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],function(CM){mod(CM,"amd");});else
mod(CodeMirror,"plain");})(function(CodeMirror,env){if(!CodeMirror.modeURL)CodeMirror.modeURL="../mode/%N/%N.js";var loading={};function splitCallback(cont,n){var countDown=n;return function(){if(--countDown==0)cont();};}
function ensureDeps(mode,cont){var deps=CodeMirror.modes[mode].dependencies;if(!deps)return cont();var missing=[];for(var i=0;i<deps.length;++i){if(!CodeMirror.modes.hasOwnProperty(deps[i]))
missing.push(deps[i]);}
if(!missing.length)return cont();var split=splitCallback(cont,missing.length);for(var i=0;i<missing.length;++i)
CodeMirror.requireMode(missing[i],split);}
CodeMirror.requireMode=function(mode,cont){if(typeof mode!="string")mode=mode.name;if(CodeMirror.modes.hasOwnProperty(mode))return ensureDeps(mode,cont);if(loading.hasOwnProperty(mode))return loading[mode].push(cont);var file=CodeMirror.modeURL.replace(/%N/g,mode);if(env=="plain"){var script=document.createElement("script");script.src=file;var others=document.getElementsByTagName("script")[0];var list=loading[mode]=[cont];CodeMirror.on(script,"load",function(){ensureDeps(mode,function(){for(var i=0;i<list.length;++i)list[i]();});});others.parentNode.insertBefore(script,others);}else if(env=="cjs"){require(file);cont();}else if(env=="amd"){requirejs([file],cont);}};CodeMirror.autoLoadMode=function(instance,mode){if(!CodeMirror.modes.hasOwnProperty(mode))
CodeMirror.requireMode(mode,function(){instance.setOption("mode",instance.getOption("mode"));});};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.multiplexingMode=function(outer){var others=Array.prototype.slice.call(arguments,1);function indexOf(string,pattern,from,returnEnd){if(typeof pattern=="string"){var found=string.indexOf(pattern,from);return returnEnd&&found>-1?found+pattern.length:found;}
var m=pattern.exec(from?string.slice(from):string);return m?m.index+from+(returnEnd?m[0].length:0):-1;}
return{startState:function(){return{outer:CodeMirror.startState(outer),innerActive:null,inner:null};},copyState:function(state){return{outer:CodeMirror.copyState(outer,state.outer),innerActive:state.innerActive,inner:state.innerActive&&CodeMirror.copyState(state.innerActive.mode,state.inner)};},token:function(stream,state){if(!state.innerActive){var cutOff=Infinity,oldContent=stream.string;for(var i=0;i<others.length;++i){var other=others[i];var found=indexOf(oldContent,other.open,stream.pos);if(found==stream.pos){if(!other.parseDelimiters)stream.match(other.open);state.innerActive=other;state.inner=CodeMirror.startState(other.mode,outer.indent?outer.indent(state.outer,""):0);return other.delimStyle&&(other.delimStyle+" "+other.delimStyle+"-open");}else if(found!=-1&&found<cutOff){cutOff=found;}}
if(cutOff!=Infinity)stream.string=oldContent.slice(0,cutOff);var outerToken=outer.token(stream,state.outer);if(cutOff!=Infinity)stream.string=oldContent;return outerToken;}else{var curInner=state.innerActive,oldContent=stream.string;if(!curInner.close&&stream.sol()){state.innerActive=state.inner=null;return this.token(stream,state);}
var found=curInner.close?indexOf(oldContent,curInner.close,stream.pos,curInner.parseDelimiters):-1;if(found==stream.pos&&!curInner.parseDelimiters){stream.match(curInner.close);state.innerActive=state.inner=null;return curInner.delimStyle&&(curInner.delimStyle+" "+curInner.delimStyle+"-close");}
if(found>-1)stream.string=oldContent.slice(0,found);var innerToken=curInner.mode.token(stream,state.inner);if(found>-1)stream.string=oldContent;if(found==stream.pos&&curInner.parseDelimiters)
state.innerActive=state.inner=null;if(curInner.innerStyle){if(innerToken)innerToken=innerToken+" "+curInner.innerStyle;else innerToken=curInner.innerStyle;}
return innerToken;}},indent:function(state,textAfter){var mode=state.innerActive?state.innerActive.mode:outer;if(!mode.indent)return CodeMirror.Pass;return mode.indent(state.innerActive?state.inner:state.outer,textAfter);},blankLine:function(state){var mode=state.innerActive?state.innerActive.mode:outer;if(mode.blankLine){mode.blankLine(state.innerActive?state.inner:state.outer);}
if(!state.innerActive){for(var i=0;i<others.length;++i){var other=others[i];if(other.open==="\n"){state.innerActive=other;state.inner=CodeMirror.startState(other.mode,mode.indent?mode.indent(state.outer,""):0);}}}else if(state.innerActive.close==="\n"){state.innerActive=state.inner=null;}},electricChars:outer.electricChars,innerMode:function(state){return state.inner?{state:state.inner,mode:state.innerActive.mode}:{state:state.outer,mode:outer};}};};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.overlayMode=function(base,overlay,combine){return{startState:function(){return{base:CodeMirror.startState(base),overlay:CodeMirror.startState(overlay),basePos:0,baseCur:null,overlayPos:0,overlayCur:null,streamSeen:null};},copyState:function(state){return{base:CodeMirror.copyState(base,state.base),overlay:CodeMirror.copyState(overlay,state.overlay),basePos:state.basePos,baseCur:null,overlayPos:state.overlayPos,overlayCur:null};},token:function(stream,state){if(stream!=state.streamSeen||Math.min(state.basePos,state.overlayPos)<stream.start){state.streamSeen=stream;state.basePos=state.overlayPos=stream.start;}
if(stream.start==state.basePos){state.baseCur=base.token(stream,state.base);state.basePos=stream.pos;}
if(stream.start==state.overlayPos){stream.pos=stream.start;state.overlayCur=overlay.token(stream,state.overlay);state.overlayPos=stream.pos;}
stream.pos=Math.min(state.basePos,state.overlayPos);if(state.overlayCur==null)return state.baseCur;else if(state.baseCur!=null&&state.overlay.combineTokens||combine&&state.overlay.combineTokens==null)
return state.baseCur+" "+state.overlayCur;else return state.overlayCur;},indent:base.indent&&function(state,textAfter){return base.indent(state.base,textAfter);},electricChars:base.electricChars,innerMode:function(state){return{state:state.base,mode:base};},blankLine:function(state){var baseToken,overlayToken;if(base.blankLine)baseToken=base.blankLine(state.base);if(overlay.blankLine)overlayToken=overlay.blankLine(state.overlay);return overlayToken==null?baseToken:(combine&&baseToken!=null?baseToken+" "+overlayToken:overlayToken);}};};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineSimpleMode=function(name,states){CodeMirror.defineMode(name,function(config){return CodeMirror.simpleMode(config,states);});};CodeMirror.simpleMode=function(config,states){ensureState(states,"start");var states_={},meta=states.meta||{},hasIndentation=false;for(var state in states)if(state!=meta&&states.hasOwnProperty(state)){var list=states_[state]=[],orig=states[state];for(var i=0;i<orig.length;i++){var data=orig[i];list.push(new Rule(data,states));if(data.indent||data.dedent)hasIndentation=true;}}
var mode={startState:function(){return{state:"start",pending:null,local:null,localState:null,indent:hasIndentation?[]:null};},copyState:function(state){var s={state:state.state,pending:state.pending,local:state.local,localState:null,indent:state.indent&&state.indent.slice(0)};if(state.localState)
s.localState=CodeMirror.copyState(state.local.mode,state.localState);if(state.stack)
s.stack=state.stack.slice(0);for(var pers=state.persistentStates;pers;pers=pers.next)
s.persistentStates={mode:pers.mode,spec:pers.spec,state:pers.state==state.localState?s.localState:CodeMirror.copyState(pers.mode,pers.state),next:s.persistentStates};return s;},token:tokenFunction(states_,config),innerMode:function(state){return state.local&&{mode:state.local.mode,state:state.localState};},indent:indentFunction(states_,meta)};if(meta)for(var prop in meta)if(meta.hasOwnProperty(prop))
mode[prop]=meta[prop];return mode;};function ensureState(states,name){if(!states.hasOwnProperty(name))
throw new Error("Undefined state "+name+" in simple mode");}
function toRegex(val,caret){if(!val)return /(?:)/;var flags="";if(val instanceof RegExp){if(val.ignoreCase)flags="i";val=val.source;}else{val=String(val);}
return new RegExp((caret===false?"":"^")+"(?:"+val+")",flags);}
function asToken(val){if(!val)return null;if(val.apply)return val
if(typeof val=="string")return val.replace(/\./g," ");var result=[];for(var i=0;i<val.length;i++)
result.push(val[i]&&val[i].replace(/\./g," "));return result;}
function Rule(data,states){if(data.next||data.push)ensureState(states,data.next||data.push);this.regex=toRegex(data.regex);this.token=asToken(data.token);this.data=data;}
function tokenFunction(states,config){return function(stream,state){if(state.pending){var pend=state.pending.shift();if(state.pending.length==0)state.pending=null;stream.pos+=pend.text.length;return pend.token;}
if(state.local){if(state.local.end&&stream.match(state.local.end)){var tok=state.local.endToken||null;state.local=state.localState=null;return tok;}else{var tok=state.local.mode.token(stream,state.localState),m;if(state.local.endScan&&(m=state.local.endScan.exec(stream.current())))
stream.pos=stream.start+m.index;return tok;}}
var curState=states[state.state];for(var i=0;i<curState.length;i++){var rule=curState[i];var matches=(!rule.data.sol||stream.sol())&&stream.match(rule.regex);if(matches){if(rule.data.next){state.state=rule.data.next;}else if(rule.data.push){(state.stack||(state.stack=[])).push(state.state);state.state=rule.data.push;}else if(rule.data.pop&&state.stack&&state.stack.length){state.state=state.stack.pop();}
if(rule.data.mode)
enterLocalMode(config,state,rule.data.mode,rule.token);if(rule.data.indent)
state.indent.push(stream.indentation()+config.indentUnit);if(rule.data.dedent)
state.indent.pop();var token=rule.token
if(token&&token.apply)token=token(matches)
if(matches.length>2&&rule.token&&typeof rule.token!="string"){state.pending=[];for(var j=2;j<matches.length;j++)
if(matches[j])
state.pending.push({text:matches[j],token:rule.token[j-1]});stream.backUp(matches[0].length-(matches[1]?matches[1].length:0));return token[0];}else if(token&&token.join){return token[0];}else{return token;}}}
stream.next();return null;};}
function cmp(a,b){if(a===b)return true;if(!a||typeof a!="object"||!b||typeof b!="object")return false;var props=0;for(var prop in a)if(a.hasOwnProperty(prop)){if(!b.hasOwnProperty(prop)||!cmp(a[prop],b[prop]))return false;props++;}
for(var prop in b)if(b.hasOwnProperty(prop))props--;return props==0;}
function enterLocalMode(config,state,spec,token){var pers;if(spec.persistent)for(var p=state.persistentStates;p&&!pers;p=p.next)
if(spec.spec?cmp(spec.spec,p.spec):spec.mode==p.mode)pers=p;var mode=pers?pers.mode:spec.mode||CodeMirror.getMode(config,spec.spec);var lState=pers?pers.state:CodeMirror.startState(mode);if(spec.persistent&&!pers)
state.persistentStates={mode:mode,spec:spec.spec,state:lState,next:state.persistentStates};state.localState=lState;state.local={mode:mode,end:spec.end&&toRegex(spec.end),endScan:spec.end&&spec.forceEnd!==false&&toRegex(spec.end,false),endToken:token&&token.join?token[token.length-1]:token};}
function indexOf(val,arr){for(var i=0;i<arr.length;i++)if(arr[i]===val)return true;}
function indentFunction(states,meta){return function(state,textAfter,line){if(state.local&&state.local.mode.indent)
return state.local.mode.indent(state.localState,textAfter,line);if(state.indent==null||state.local||meta.dontIndentStates&&indexOf(state.state,meta.dontIndentStates)>-1)
return CodeMirror.Pass;var pos=state.indent.length-1,rules=states[state.state];scan:for(;;){for(var i=0;i<rules.length;i++){var rule=rules[i];if(rule.data.dedent&&rule.data.dedentIfLineStart!==false){var m=rule.regex.exec(textAfter);if(m&&m[0]){pos--;if(rule.next||rule.push)rules=states[rule.next||rule.push];textAfter=textAfter.slice(m[0].length);continue scan;}}}
break;}
return pos<0?0:state.indent[pos];};}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"),require("./runmode"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror","./runmode"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var isBlock=/^(p|li|div|h\\d|pre|blockquote|td)$/;function textContent(node,out){if(node.nodeType==3)return out.push(node.nodeValue);for(var ch=node.firstChild;ch;ch=ch.nextSibling){textContent(ch,out);if(isBlock.test(node.nodeType))out.push("\n");}}
CodeMirror.colorize=function(collection,defaultMode){if(!collection)collection=document.body.getElementsByTagName("pre");for(var i=0;i<collection.length;++i){var node=collection[i];var mode=node.getAttribute("data-lang")||defaultMode;if(!mode)continue;var text=[];textContent(node,text);node.innerHTML="";CodeMirror.runMode(text.join(""),mode,node);node.className+=" cm-s-default";}};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.runMode=function(string,modespec,callback,options){var mode=CodeMirror.getMode(CodeMirror.defaults,modespec);var ie=/MSIE \d/.test(navigator.userAgent);var ie_lt9=ie&&(document.documentMode==null||document.documentMode<9);if(callback.appendChild){var tabSize=(options&&options.tabSize)||CodeMirror.defaults.tabSize;var node=callback,col=0;node.innerHTML="";callback=function(text,style){if(text=="\n"){node.appendChild(document.createTextNode(ie_lt9?'\r':text));col=0;return;}
var content="";for(var pos=0;;){var idx=text.indexOf("\t",pos);if(idx==-1){content+=text.slice(pos);col+=text.length-pos;break;}else{col+=idx-pos;content+=text.slice(pos,idx);var size=tabSize-col%tabSize;col+=size;for(var i=0;i<size;++i)content+=" ";pos=idx+1;}}
if(style){var sp=node.appendChild(document.createElement("span"));sp.className="cm-"+style.replace(/ +/g," cm-");sp.appendChild(document.createTextNode(content));}else{node.appendChild(document.createTextNode(content));}};}
var lines=CodeMirror.splitLines(string),state=(options&&options.state)||CodeMirror.startState(mode);for(var i=0,e=lines.length;i<e;++i){if(i)callback("\n");var stream=new CodeMirror.StringStream(lines[i]);if(!stream.string&&mode.blankLine)mode.blankLine(state);while(!stream.eol()){var style=mode.token(stream,state);callback(stream.current(),style,i,stream.start,state);stream.start=stream.pos;}}};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineExtension("annotateScrollbar",function(options){if(typeof options=="string")options={className:options};return new Annotation(this,options);});CodeMirror.defineOption("scrollButtonHeight",0);function Annotation(cm,options){this.cm=cm;this.options=options;this.buttonHeight=options.scrollButtonHeight||cm.getOption("scrollButtonHeight");this.annotations=[];this.doRedraw=this.doUpdate=null;this.div=cm.getWrapperElement().appendChild(document.createElement("div"));this.div.style.cssText="position: absolute; right: 0; top: 0; z-index: 7; pointer-events: none";this.computeScale();function scheduleRedraw(delay){clearTimeout(self.doRedraw);self.doRedraw=setTimeout(function(){self.redraw();},delay);}
var self=this;cm.on("refresh",this.resizeHandler=function(){clearTimeout(self.doUpdate);self.doUpdate=setTimeout(function(){if(self.computeScale())scheduleRedraw(20);},100);});cm.on("markerAdded",this.resizeHandler);cm.on("markerCleared",this.resizeHandler);if(options.listenForChanges!==false)
cm.on("change",this.changeHandler=function(){scheduleRedraw(250);});}
Annotation.prototype.computeScale=function(){var cm=this.cm;var hScale=(cm.getWrapperElement().clientHeight-cm.display.barHeight-this.buttonHeight*2)/
cm.getScrollerElement().scrollHeight
if(hScale!=this.hScale){this.hScale=hScale;return true;}};Annotation.prototype.update=function(annotations){this.annotations=annotations;this.redraw();};Annotation.prototype.redraw=function(compute){if(compute!==false)this.computeScale();var cm=this.cm,hScale=this.hScale;var frag=document.createDocumentFragment(),anns=this.annotations;var wrapping=cm.getOption("lineWrapping");var singleLineH=wrapping&&cm.defaultTextHeight()*1.5;var curLine=null,curLineObj=null;function getY(pos,top){if(curLine!=pos.line){curLine=pos.line;curLineObj=cm.getLineHandle(curLine);}
if((curLineObj.widgets&&curLineObj.widgets.length)||(wrapping&&curLineObj.height>singleLineH))
return cm.charCoords(pos,"local")[top?"top":"bottom"];var topY=cm.heightAtLine(curLineObj,"local");return topY+(top?0:curLineObj.height);}
var lastLine=cm.lastLine()
if(cm.display.barWidth)for(var i=0,nextTop;i<anns.length;i++){var ann=anns[i];if(ann.to.line>lastLine)continue;var top=nextTop||getY(ann.from,true)*hScale;var bottom=getY(ann.to,false)*hScale;while(i<anns.length-1){if(anns[i+1].to.line>lastLine)break;nextTop=getY(anns[i+1].from,true)*hScale;if(nextTop>bottom+.9)break;ann=anns[++i];bottom=getY(ann.to,false)*hScale;}
if(bottom==top)continue;var height=Math.max(bottom-top,3);var elt=frag.appendChild(document.createElement("div"));elt.style.cssText="position: absolute; right: 0px; width: "+Math.max(cm.display.barWidth-1,2)+"px; top: "
+(top+this.buttonHeight)+"px; height: "+height+"px";elt.className=this.options.className;if(ann.id){elt.setAttribute("annotation-id",ann.id);}}
this.div.textContent="";this.div.appendChild(frag);};Annotation.prototype.clear=function(){this.cm.off("refresh",this.resizeHandler);this.cm.off("markerAdded",this.resizeHandler);this.cm.off("markerCleared",this.resizeHandler);if(this.changeHandler)this.cm.off("change",this.changeHandler);this.div.parentNode.removeChild(this.div);};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineOption("scrollPastEnd",false,function(cm,val,old){if(old&&old!=CodeMirror.Init){cm.off("change",onChange);cm.off("refresh",updateBottomMargin);cm.display.lineSpace.parentNode.style.paddingBottom="";cm.state.scrollPastEndPadding=null;}
if(val){cm.on("change",onChange);cm.on("refresh",updateBottomMargin);updateBottomMargin(cm);}});function onChange(cm,change){if(CodeMirror.changeEnd(change).line==cm.lastLine())
updateBottomMargin(cm);}
function updateBottomMargin(cm){var padding="";if(cm.lineCount()>1){var totalH=cm.display.scroller.clientHeight-30,lastLineH=cm.getLineHandle(cm.lastLine()).height;padding=(totalH-lastLineH)+"px";}
if(cm.state.scrollPastEndPadding!=padding){cm.state.scrollPastEndPadding=padding;cm.display.lineSpace.parentNode.style.paddingBottom=padding;cm.off("refresh",updateBottomMargin);cm.setSize();cm.on("refresh",updateBottomMargin);}}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";function Bar(cls,orientation,scroll){this.orientation=orientation;this.scroll=scroll;this.screen=this.total=this.size=1;this.pos=0;this.node=document.createElement("div");this.node.className=cls+"-"+orientation;this.inner=this.node.appendChild(document.createElement("div"));var self=this;CodeMirror.on(this.inner,"mousedown",function(e){if(e.which!=1)return;CodeMirror.e_preventDefault(e);var axis=self.orientation=="horizontal"?"pageX":"pageY";var start=e[axis],startpos=self.pos;function done(){CodeMirror.off(document,"mousemove",move);CodeMirror.off(document,"mouseup",done);}
function move(e){if(e.which!=1)return done();self.moveTo(startpos+(e[axis]-start)*(self.total / self.size));}
CodeMirror.on(document,"mousemove",move);CodeMirror.on(document,"mouseup",done);});CodeMirror.on(this.node,"click",function(e){CodeMirror.e_preventDefault(e);var innerBox=self.inner.getBoundingClientRect(),where;if(self.orientation=="horizontal")
where=e.clientX<innerBox.left?-1:e.clientX>innerBox.right?1:0;else
where=e.clientY<innerBox.top?-1:e.clientY>innerBox.bottom?1:0;self.moveTo(self.pos+where*self.screen);});function onWheel(e){var moved=CodeMirror.wheelEventPixels(e)[self.orientation=="horizontal"?"x":"y"];var oldPos=self.pos;self.moveTo(self.pos+moved);if(self.pos!=oldPos)CodeMirror.e_preventDefault(e);}
CodeMirror.on(this.node,"mousewheel",onWheel);CodeMirror.on(this.node,"DOMMouseScroll",onWheel);}
Bar.prototype.setPos=function(pos,force){if(pos<0)pos=0;if(pos>this.total-this.screen)pos=this.total-this.screen;if(!force&&pos==this.pos)return false;this.pos=pos;this.inner.style[this.orientation=="horizontal"?"left":"top"]=(pos*(this.size / this.total))+"px";return true};Bar.prototype.moveTo=function(pos){if(this.setPos(pos))this.scroll(pos,this.orientation);}
var minButtonSize=10;Bar.prototype.update=function(scrollSize,clientSize,barSize){var sizeChanged=this.screen!=clientSize||this.total!=scrollSize||this.size!=barSize
if(sizeChanged){this.screen=clientSize;this.total=scrollSize;this.size=barSize;}
var buttonSize=this.screen*(this.size / this.total);if(buttonSize<minButtonSize){this.size-=minButtonSize-buttonSize;buttonSize=minButtonSize;}
this.inner.style[this.orientation=="horizontal"?"width":"height"]=buttonSize+"px";this.setPos(this.pos,sizeChanged);};function SimpleScrollbars(cls,place,scroll){this.addClass=cls;this.horiz=new Bar(cls,"horizontal",scroll);place(this.horiz.node);this.vert=new Bar(cls,"vertical",scroll);place(this.vert.node);this.width=null;}
SimpleScrollbars.prototype.update=function(measure){if(this.width==null){var style=window.getComputedStyle?window.getComputedStyle(this.horiz.node):this.horiz.node.currentStyle;if(style)this.width=parseInt(style.height);}
var width=this.width||0;var needsH=measure.scrollWidth>measure.clientWidth+1;var needsV=measure.scrollHeight>measure.clientHeight+1;this.vert.node.style.display=needsV?"block":"none";this.horiz.node.style.display=needsH?"block":"none";if(needsV){this.vert.update(measure.scrollHeight,measure.clientHeight,measure.viewHeight-(needsH?width:0));this.vert.node.style.bottom=needsH?width+"px":"0";}
if(needsH){this.horiz.update(measure.scrollWidth,measure.clientWidth,measure.viewWidth-(needsV?width:0)-measure.barLeft);this.horiz.node.style.right=needsV?width+"px":"0";this.horiz.node.style.left=measure.barLeft+"px";}
return{right:needsV?width:0,bottom:needsH?width:0};};SimpleScrollbars.prototype.setScrollTop=function(pos){this.vert.setPos(pos);};SimpleScrollbars.prototype.setScrollLeft=function(pos){this.horiz.setPos(pos);};SimpleScrollbars.prototype.clear=function(){var parent=this.horiz.node.parentNode;parent.removeChild(this.horiz.node);parent.removeChild(this.vert.node);};CodeMirror.scrollbarModel.simple=function(place,scroll){return new SimpleScrollbars("CodeMirror-simplescroll",place,scroll);};CodeMirror.scrollbarModel.overlay=function(place,scroll){return new SimpleScrollbars("CodeMirror-overlayscroll",place,scroll);};});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var WRAP_CLASS="CodeMirror-activeline";var BACK_CLASS="CodeMirror-activeline-background";var GUTT_CLASS="CodeMirror-activeline-gutter";CodeMirror.defineOption("styleActiveLine",false,function(cm,val,old){var prev=old==CodeMirror.Init?false:old;if(val==prev)return
if(prev){cm.off("beforeSelectionChange",selectionChange);clearActiveLines(cm);delete cm.state.activeLines;}
if(val){cm.state.activeLines=[];updateActiveLines(cm,cm.listSelections());cm.on("beforeSelectionChange",selectionChange);}});function clearActiveLines(cm){for(var i=0;i<cm.state.activeLines.length;i++){cm.removeLineClass(cm.state.activeLines[i],"wrap",WRAP_CLASS);cm.removeLineClass(cm.state.activeLines[i],"background",BACK_CLASS);cm.removeLineClass(cm.state.activeLines[i],"gutter",GUTT_CLASS);}}
function sameArray(a,b){if(a.length!=b.length)return false;for(var i=0;i<a.length;i++)
if(a[i]!=b[i])return false;return true;}
function updateActiveLines(cm,ranges){var active=[];for(var i=0;i<ranges.length;i++){var range=ranges[i];var option=cm.getOption("styleActiveLine");if(typeof option=="object"&&option.nonEmpty?range.anchor.line!=range.head.line:!range.empty())
continue
var line=cm.getLineHandleVisualStart(range.head.line);if(active[active.length-1]!=line)active.push(line);}
if(sameArray(cm.state.activeLines,active))return;cm.operation(function(){clearActiveLines(cm);for(var i=0;i<active.length;i++){cm.addLineClass(active[i],"wrap",WRAP_CLASS);cm.addLineClass(active[i],"background",BACK_CLASS);cm.addLineClass(active[i],"gutter",GUTT_CLASS);}
cm.state.activeLines=active;});}
function selectionChange(cm,sel){updateActiveLines(cm,sel.ranges);}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineOption("styleSelectedText",false,function(cm,val,old){var prev=old&&old!=CodeMirror.Init;if(val&&!prev){cm.state.markedSelection=[];cm.state.markedSelectionStyle=typeof val=="string"?val:"CodeMirror-selectedtext";reset(cm);cm.on("cursorActivity",onCursorActivity);cm.on("change",onChange);}else if(!val&&prev){cm.off("cursorActivity",onCursorActivity);cm.off("change",onChange);clear(cm);cm.state.markedSelection=cm.state.markedSelectionStyle=null;}});function onCursorActivity(cm){if(cm.state.markedSelection)
cm.operation(function(){update(cm);});}
function onChange(cm){if(cm.state.markedSelection&&cm.state.markedSelection.length)
cm.operation(function(){clear(cm);});}
var CHUNK_SIZE=8;var Pos=CodeMirror.Pos;var cmp=CodeMirror.cmpPos;function coverRange(cm,from,to,addAt){if(cmp(from,to)==0)return;var array=cm.state.markedSelection;var cls=cm.state.markedSelectionStyle;for(var line=from.line;;){var start=line==from.line?from:Pos(line,0);var endLine=line+CHUNK_SIZE,atEnd=endLine>=to.line;var end=atEnd?to:Pos(endLine,0);var mark=cm.markText(start,end,{className:cls});if(addAt==null)array.push(mark);else array.splice(addAt++,0,mark);if(atEnd)break;line=endLine;}}
function clear(cm){var array=cm.state.markedSelection;for(var i=0;i<array.length;++i)array[i].clear();array.length=0;}
function reset(cm){clear(cm);var ranges=cm.listSelections();for(var i=0;i<ranges.length;i++)
coverRange(cm,ranges[i].from(),ranges[i].to());}
function update(cm){if(!cm.somethingSelected())return clear(cm);if(cm.listSelections().length>1)return reset(cm);var from=cm.getCursor("start"),to=cm.getCursor("end");var array=cm.state.markedSelection;if(!array.length)return coverRange(cm,from,to);var coverStart=array[0].find(),coverEnd=array[array.length-1].find();if(!coverStart||!coverEnd||to.line-from.line<=CHUNK_SIZE||cmp(from,coverEnd.to)>=0||cmp(to,coverStart.from)<=0)
return reset(cm);while(cmp(from,coverStart.from)>0){array.shift().clear();coverStart=array[0].find();}
if(cmp(from,coverStart.from)<0){if(coverStart.to.line-from.line<CHUNK_SIZE){array.shift().clear();coverRange(cm,from,coverStart.to,0);}else{coverRange(cm,from,coverStart.from,0);}}
while(cmp(to,coverEnd.to)<0){array.pop().clear();coverEnd=array[array.length-1].find();}
if(cmp(to,coverEnd.to)>0){if(to.line-coverEnd.from.line<CHUNK_SIZE){array.pop().clear();coverRange(cm,coverEnd.from,to);}else{coverRange(cm,coverEnd.to,to);}}}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.defineOption("selectionPointer",false,function(cm,val){var data=cm.state.selectionPointer;if(data){CodeMirror.off(cm.getWrapperElement(),"mousemove",data.mousemove);CodeMirror.off(cm.getWrapperElement(),"mouseout",data.mouseout);CodeMirror.off(window,"scroll",data.windowScroll);cm.off("cursorActivity",reset);cm.off("scroll",reset);cm.state.selectionPointer=null;cm.display.lineDiv.style.cursor="";}
if(val){data=cm.state.selectionPointer={value:typeof val=="string"?val:"default",mousemove:function(event){mousemove(cm,event);},mouseout:function(event){mouseout(cm,event);},windowScroll:function(){reset(cm);},rects:null,mouseX:null,mouseY:null,willUpdate:false};CodeMirror.on(cm.getWrapperElement(),"mousemove",data.mousemove);CodeMirror.on(cm.getWrapperElement(),"mouseout",data.mouseout);CodeMirror.on(window,"scroll",data.windowScroll);cm.on("cursorActivity",reset);cm.on("scroll",reset);}});function mousemove(cm,event){var data=cm.state.selectionPointer;if(event.buttons==null?event.which:event.buttons){data.mouseX=data.mouseY=null;}else{data.mouseX=event.clientX;data.mouseY=event.clientY;}
scheduleUpdate(cm);}
function mouseout(cm,event){if(!cm.getWrapperElement().contains(event.relatedTarget)){var data=cm.state.selectionPointer;data.mouseX=data.mouseY=null;scheduleUpdate(cm);}}
function reset(cm){cm.state.selectionPointer.rects=null;scheduleUpdate(cm);}
function scheduleUpdate(cm){if(!cm.state.selectionPointer.willUpdate){cm.state.selectionPointer.willUpdate=true;setTimeout(function(){update(cm);cm.state.selectionPointer.willUpdate=false;},50);}}
function update(cm){var data=cm.state.selectionPointer;if(!data)return;if(data.rects==null&&data.mouseX!=null){data.rects=[];if(cm.somethingSelected()){for(var sel=cm.display.selectionDiv.firstChild;sel;sel=sel.nextSibling)
data.rects.push(sel.getBoundingClientRect());}}
var inside=false;if(data.mouseX!=null)for(var i=0;i<data.rects.length;i++){var rect=data.rects[i];if(rect.left<=data.mouseX&&rect.right>=data.mouseX&&rect.top<=data.mouseY&&rect.bottom>=data.mouseY)
inside=true;}
var cursor=inside?data.value:"";if(cm.display.lineDiv.style.cursor!=cursor)
cm.display.lineDiv.style.cursor=cursor;}});(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";CodeMirror.TernServer=function(options){var self=this;this.options=options||{};var plugins=this.options.plugins||(this.options.plugins={});if(!plugins.doc_comment)plugins.doc_comment=true;this.docs=Object.create(null);if(this.options.useWorker){this.server=new WorkerServer(this);}else{this.server=new tern.Server({getFile:function(name,c){return getFile(self,name,c);},async:true,defs:this.options.defs||[],plugins:plugins});}
this.trackChange=function(doc,change){trackChange(self,doc,change);};this.cachedArgHints=null;this.activeArgHints=null;this.jumpStack=[];this.getHint=function(cm,c){return hint(self,cm,c);};this.getHint.async=true;};CodeMirror.TernServer.prototype={addDoc:function(name,doc){var data={doc:doc,name:name,changed:null};this.server.addFile(name,docValue(this,data));CodeMirror.on(doc,"change",this.trackChange);return this.docs[name]=data;},delDoc:function(id){var found=resolveDoc(this,id);if(!found)return;CodeMirror.off(found.doc,"change",this.trackChange);delete this.docs[found.name];this.server.delFile(found.name);},hideDoc:function(id){closeArgHints(this);var found=resolveDoc(this,id);if(found&&found.changed)sendDoc(this,found);},complete:function(cm){cm.showHint({hint:this.getHint});},showType:function(cm,pos,c){showContextInfo(this,cm,pos,"type",c);},showDocs:function(cm,pos,c){showContextInfo(this,cm,pos,"documentation",c);},updateArgHints:function(cm){updateArgHints(this,cm);},jumpToDef:function(cm){jumpToDef(this,cm);},jumpBack:function(cm){jumpBack(this,cm);},rename:function(cm){rename(this,cm);},selectName:function(cm){selectName(this,cm);},request:function(cm,query,c,pos){var self=this;var doc=findDoc(this,cm.getDoc());var request=buildRequest(this,doc,query,pos);var extraOptions=request.query&&this.options.queryOptions&&this.options.queryOptions[request.query.type]
if(extraOptions)for(var prop in extraOptions)request.query[prop]=extraOptions[prop];this.server.request(request,function(error,data){if(!error&&self.options.responseFilter)
data=self.options.responseFilter(doc,query,request,error,data);c(error,data);});},destroy:function(){closeArgHints(this)
if(this.worker){this.worker.terminate();this.worker=null;}}};var Pos=CodeMirror.Pos;var cls="CodeMirror-Tern-";var bigDoc=250;function getFile(ts,name,c){var buf=ts.docs[name];if(buf)
c(docValue(ts,buf));else if(ts.options.getFile)
ts.options.getFile(name,c);else
c(null);}
function findDoc(ts,doc,name){for(var n in ts.docs){var cur=ts.docs[n];if(cur.doc==doc)return cur;}
if(!name)for(var i=0;;++i){n="[doc"+(i||"")+"]";if(!ts.docs[n]){name=n;break;}}
return ts.addDoc(name,doc);}
function resolveDoc(ts,id){if(typeof id=="string")return ts.docs[id];if(id instanceof CodeMirror)id=id.getDoc();if(id instanceof CodeMirror.Doc)return findDoc(ts,id);}
function trackChange(ts,doc,change){var data=findDoc(ts,doc);var argHints=ts.cachedArgHints;if(argHints&&argHints.doc==doc&&cmpPos(argHints.start,change.to)>=0)
ts.cachedArgHints=null;var changed=data.changed;if(changed==null)
data.changed=changed={from:change.from.line,to:change.from.line};var end=change.from.line+(change.text.length-1);if(change.from.line<changed.to)changed.to=changed.to-(change.to.line-end);if(end>=changed.to)changed.to=end+1;if(changed.from>change.from.line)changed.from=change.from.line;if(doc.lineCount()>bigDoc&&change.to-changed.from>100)setTimeout(function(){if(data.changed&&data.changed.to-data.changed.from>100)sendDoc(ts,data);},200);}
function sendDoc(ts,doc){ts.server.request({files:[{type:"full",name:doc.name,text:docValue(ts,doc)}]},function(error){if(error)window.console.error(error);else doc.changed=null;});}
function hint(ts,cm,c){ts.request(cm,{type:"completions",types:true,docs:true,urls:true},function(error,data){if(error)return showError(ts,cm,error);var completions=[],after="";var from=data.start,to=data.end;if(cm.getRange(Pos(from.line,from.ch-2),from)=="[\""&&cm.getRange(to,Pos(to.line,to.ch+2))!="\"]")
after="\"]";for(var i=0;i<data.completions.length;++i){var completion=data.completions[i],className=typeToIcon(completion.type);if(data.guess)className+=" "+cls+"guess";completions.push({text:completion.name+after,displayText:completion.displayName||completion.name,className:className,data:completion});}
var obj={from:from,to:to,list:completions};var tooltip=null;CodeMirror.on(obj,"close",function(){remove(tooltip);});CodeMirror.on(obj,"update",function(){remove(tooltip);});CodeMirror.on(obj,"select",function(cur,node){remove(tooltip);var content=ts.options.completionTip?ts.options.completionTip(cur.data):cur.data.doc;if(content){tooltip=makeTooltip(node.parentNode.getBoundingClientRect().right+window.pageXOffset,node.getBoundingClientRect().top+window.pageYOffset,content);tooltip.className+=" "+cls+"hint-doc";}});c(obj);});}
function typeToIcon(type){var suffix;if(type=="?")suffix="unknown";else if(type=="number"||type=="string"||type=="bool")suffix=type;else if(/^fn\(/.test(type))suffix="fn";else if(/^\[/.test(type))suffix="array";else suffix="object";return cls+"completion "+cls+"completion-"+suffix;}
function showContextInfo(ts,cm,pos,queryName,c){ts.request(cm,queryName,function(error,data){if(error)return showError(ts,cm,error);if(ts.options.typeTip){var tip=ts.options.typeTip(data);}else{var tip=elt("span",null,elt("strong",null,data.type||"not found"));if(data.doc)
tip.appendChild(document.createTextNode(" — "+data.doc));if(data.url){tip.appendChild(document.createTextNode(" "));var child=tip.appendChild(elt("a",null,"[docs]"));child.href=data.url;child.target="_blank";}}
tempTooltip(cm,tip,ts);if(c)c();},pos);}
function updateArgHints(ts,cm){closeArgHints(ts);if(cm.somethingSelected())return;var state=cm.getTokenAt(cm.getCursor()).state;var inner=CodeMirror.innerMode(cm.getMode(),state);if(inner.mode.name!="javascript")return;var lex=inner.state.lexical;if(lex.info!="call")return;var ch,argPos=lex.pos||0,tabSize=cm.getOption("tabSize");for(var line=cm.getCursor().line,e=Math.max(0,line-9),found=false;line>=e;--line){var str=cm.getLine(line),extra=0;for(var pos=0;;){var tab=str.indexOf("\t",pos);if(tab==-1)break;extra+=tabSize-(tab+extra)%tabSize-1;pos=tab+1;}
ch=lex.column-extra;if(str.charAt(ch)=="("){found=true;break;}}
if(!found)return;var start=Pos(line,ch);var cache=ts.cachedArgHints;if(cache&&cache.doc==cm.getDoc()&&cmpPos(start,cache.start)==0)
return showArgHints(ts,cm,argPos);ts.request(cm,{type:"type",preferFunction:true,end:start},function(error,data){if(error||!data.type||!(/^fn\(/).test(data.type))return;ts.cachedArgHints={start:start,type:parseFnType(data.type),name:data.exprName||data.name||"fn",guess:data.guess,doc:cm.getDoc()};showArgHints(ts,cm,argPos);});}
function showArgHints(ts,cm,pos){closeArgHints(ts);var cache=ts.cachedArgHints,tp=cache.type;var tip=elt("span",cache.guess?cls+"fhint-guess":null,elt("span",cls+"fname",cache.name),"(");for(var i=0;i<tp.args.length;++i){if(i)tip.appendChild(document.createTextNode(", "));var arg=tp.args[i];tip.appendChild(elt("span",cls+"farg"+(i==pos?" "+cls+"farg-current":""),arg.name||"?"));if(arg.type!="?"){tip.appendChild(document.createTextNode(":\u00a0"));tip.appendChild(elt("span",cls+"type",arg.type));}}
tip.appendChild(document.createTextNode(tp.rettype?") ->\u00a0":")"));if(tp.rettype)tip.appendChild(elt("span",cls+"type",tp.rettype));var place=cm.cursorCoords(null,"page");var tooltip=ts.activeArgHints=makeTooltip(place.right+1,place.bottom,tip)
setTimeout(function(){tooltip.clear=onEditorActivity(cm,function(){if(ts.activeArgHints==tooltip)closeArgHints(ts)})},20)}
function parseFnType(text){var args=[],pos=3;function skipMatching(upto){var depth=0,start=pos;for(;;){var next=text.charAt(pos);if(upto.test(next)&&!depth)return text.slice(start,pos);if(/[{\[\(]/.test(next))++depth;else if(/[}\]\)]/.test(next))--depth;++pos;}}
if(text.charAt(pos)!=")")for(;;){var name=text.slice(pos).match(/^([^, \(\[\{]+): /);if(name){pos+=name[0].length;name=name[1];}
args.push({name:name,type:skipMatching(/[\),]/)});if(text.charAt(pos)==")")break;pos+=2;}
var rettype=text.slice(pos).match(/^\) -> (.*)$/);return{args:args,rettype:rettype&&rettype[1]};}
function jumpToDef(ts,cm){function inner(varName){var req={type:"definition",variable:varName||null};var doc=findDoc(ts,cm.getDoc());ts.server.request(buildRequest(ts,doc,req),function(error,data){if(error)return showError(ts,cm,error);if(!data.file&&data.url){window.open(data.url);return;}
if(data.file){var localDoc=ts.docs[data.file],found;if(localDoc&&(found=findContext(localDoc.doc,data))){ts.jumpStack.push({file:doc.name,start:cm.getCursor("from"),end:cm.getCursor("to")});moveTo(ts,doc,localDoc,found.start,found.end);return;}}
showError(ts,cm,"Could not find a definition.");});}
if(!atInterestingExpression(cm))
dialog(cm,"Jump to variable",function(name){if(name)inner(name);});else
inner();}
function jumpBack(ts,cm){var pos=ts.jumpStack.pop(),doc=pos&&ts.docs[pos.file];if(!doc)return;moveTo(ts,findDoc(ts,cm.getDoc()),doc,pos.start,pos.end);}
function moveTo(ts,curDoc,doc,start,end){doc.doc.setSelection(start,end);if(curDoc!=doc&&ts.options.switchToDoc){closeArgHints(ts);ts.options.switchToDoc(doc.name,doc.doc);}}
function findContext(doc,data){var before=data.context.slice(0,data.contextOffset).split("\n");var startLine=data.start.line-(before.length-1);var start=Pos(startLine,(before.length==1?data.start.ch:doc.getLine(startLine).length)-before[0].length);var text=doc.getLine(startLine).slice(start.ch);for(var cur=startLine+1;cur<doc.lineCount()&&text.length<data.context.length;++cur)
text+="\n"+doc.getLine(cur);if(text.slice(0,data.context.length)==data.context)return data;var cursor=doc.getSearchCursor(data.context,0,false);var nearest,nearestDist=Infinity;while(cursor.findNext()){var from=cursor.from(),dist=Math.abs(from.line-start.line)*10000;if(!dist)dist=Math.abs(from.ch-start.ch);if(dist<nearestDist){nearest=from;nearestDist=dist;}}
if(!nearest)return null;if(before.length==1)
nearest.ch+=before[0].length;else
nearest=Pos(nearest.line+(before.length-1),before[before.length-1].length);if(data.start.line==data.end.line)
var end=Pos(nearest.line,nearest.ch+(data.end.ch-data.start.ch));else
var end=Pos(nearest.line+(data.end.line-data.start.line),data.end.ch);return{start:nearest,end:end};}
function atInterestingExpression(cm){var pos=cm.getCursor("end"),tok=cm.getTokenAt(pos);if(tok.start<pos.ch&&tok.type=="comment")return false;return /[\w)\]]/.test(cm.getLine(pos.line).slice(Math.max(pos.ch-1,0),pos.ch+1));}
function rename(ts,cm){var token=cm.getTokenAt(cm.getCursor());if(!/\w/.test(token.string))return showError(ts,cm,"Not at a variable");dialog(cm,"New name for "+token.string,function(newName){ts.request(cm,{type:"rename",newName:newName,fullDocs:true},function(error,data){if(error)return showError(ts,cm,error);applyChanges(ts,data.changes);});});}
function selectName(ts,cm){var name=findDoc(ts,cm.doc).name;ts.request(cm,{type:"refs"},function(error,data){if(error)return showError(ts,cm,error);var ranges=[],cur=0;var curPos=cm.getCursor();for(var i=0;i<data.refs.length;i++){var ref=data.refs[i];if(ref.file==name){ranges.push({anchor:ref.start,head:ref.end});if(cmpPos(curPos,ref.start)>=0&&cmpPos(curPos,ref.end)<=0)
cur=ranges.length-1;}}
cm.setSelections(ranges,cur);});}
var nextChangeOrig=0;function applyChanges(ts,changes){var perFile=Object.create(null);for(var i=0;i<changes.length;++i){var ch=changes[i];(perFile[ch.file]||(perFile[ch.file]=[])).push(ch);}
for(var file in perFile){var known=ts.docs[file],chs=perFile[file];;if(!known)continue;chs.sort(function(a,b){return cmpPos(b.start,a.start);});var origin="*rename"+(++nextChangeOrig);for(var i=0;i<chs.length;++i){var ch=chs[i];known.doc.replaceRange(ch.text,ch.start,ch.end,origin);}}}
function buildRequest(ts,doc,query,pos){var files=[],offsetLines=0,allowFragments=!query.fullDocs;if(!allowFragments)delete query.fullDocs;if(typeof query=="string")query={type:query};query.lineCharPositions=true;if(query.end==null){query.end=pos||doc.doc.getCursor("end");if(doc.doc.somethingSelected())
query.start=doc.doc.getCursor("start");}
var startPos=query.start||query.end;if(doc.changed){if(doc.doc.lineCount()>bigDoc&&allowFragments!==false&&doc.changed.to-doc.changed.from<100&&doc.changed.from<=startPos.line&&doc.changed.to>query.end.line){files.push(getFragmentAround(doc,startPos,query.end));query.file="#0";var offsetLines=files[0].offsetLines;if(query.start!=null)query.start=Pos(query.start.line- -offsetLines,query.start.ch);query.end=Pos(query.end.line-offsetLines,query.end.ch);}else{files.push({type:"full",name:doc.name,text:docValue(ts,doc)});query.file=doc.name;doc.changed=null;}}else{query.file=doc.name;}
for(var name in ts.docs){var cur=ts.docs[name];if(cur.changed&&cur!=doc){files.push({type:"full",name:cur.name,text:docValue(ts,cur)});cur.changed=null;}}
return{query:query,files:files};}
function getFragmentAround(data,start,end){var doc=data.doc;var minIndent=null,minLine=null,endLine,tabSize=4;for(var p=start.line-1,min=Math.max(0,p-50);p>=min;--p){var line=doc.getLine(p),fn=line.search(/\bfunction\b/);if(fn<0)continue;var indent=CodeMirror.countColumn(line,null,tabSize);if(minIndent!=null&&minIndent<=indent)continue;minIndent=indent;minLine=p;}
if(minLine==null)minLine=min;var max=Math.min(doc.lastLine(),end.line+20);if(minIndent==null||minIndent==CodeMirror.countColumn(doc.getLine(start.line),null,tabSize))
endLine=max;else for(endLine=end.line+1;endLine<max;++endLine){var indent=CodeMirror.countColumn(doc.getLine(endLine),null,tabSize);if(indent<=minIndent)break;}
var from=Pos(minLine,0);return{type:"part",name:data.name,offsetLines:from.line,text:doc.getRange(from,Pos(endLine,end.line==endLine?null:0))};}
var cmpPos=CodeMirror.cmpPos;function elt(tagname,cls){var e=document.createElement(tagname);if(cls)e.className=cls;for(var i=2;i<arguments.length;++i){var elt=arguments[i];if(typeof elt=="string")elt=document.createTextNode(elt);e.appendChild(elt);}
return e;}
function dialog(cm,text,f){if(cm.openDialog)
cm.openDialog(text+": <input type=text>",f);else
f(prompt(text,""));}
function tempTooltip(cm,content,ts){if(cm.state.ternTooltip)remove(cm.state.ternTooltip);var where=cm.cursorCoords();var tip=cm.state.ternTooltip=makeTooltip(where.right+1,where.bottom,content);function maybeClear(){old=true;if(!mouseOnTip)clear();}
function clear(){cm.state.ternTooltip=null;if(tip.parentNode)fadeOut(tip)
clearActivity()}
var mouseOnTip=false,old=false;CodeMirror.on(tip,"mousemove",function(){mouseOnTip=true;});CodeMirror.on(tip,"mouseout",function(e){var related=e.relatedTarget||e.toElement
if(!related||!CodeMirror.contains(tip,related)){if(old)clear();else mouseOnTip=false;}});setTimeout(maybeClear,ts.options.hintDelay?ts.options.hintDelay:1700);var clearActivity=onEditorActivity(cm,clear)}
function onEditorActivity(cm,f){cm.on("cursorActivity",f)
cm.on("blur",f)
cm.on("scroll",f)
cm.on("setDoc",f)
return function(){cm.off("cursorActivity",f)
cm.off("blur",f)
cm.off("scroll",f)
cm.off("setDoc",f)}}
function makeTooltip(x,y,content){var node=elt("div",cls+"tooltip",content);node.style.left=x+"px";node.style.top=y+"px";document.body.appendChild(node);return node;}
function remove(node){var p=node&&node.parentNode;if(p)p.removeChild(node);}
function fadeOut(tooltip){tooltip.style.opacity="0";setTimeout(function(){remove(tooltip);},1100);}
function showError(ts,cm,msg){if(ts.options.showError)
ts.options.showError(cm,msg);else
tempTooltip(cm,String(msg),ts);}
function closeArgHints(ts){if(ts.activeArgHints){if(ts.activeArgHints.clear)ts.activeArgHints.clear()
remove(ts.activeArgHints)
ts.activeArgHints=null}}
function docValue(ts,doc){var val=doc.doc.getValue();if(ts.options.fileFilter)val=ts.options.fileFilter(val,doc.name,doc.doc);return val;}
function WorkerServer(ts){var worker=ts.worker=new Worker(ts.options.workerScript);worker.postMessage({type:"init",defs:ts.options.defs,plugins:ts.options.plugins,scripts:ts.options.workerDeps});var msgId=0,pending={};function send(data,c){if(c){data.id=++msgId;pending[msgId]=c;}
worker.postMessage(data);}
worker.onmessage=function(e){var data=e.data;if(data.type=="getFile"){getFile(ts,data.name,function(err,text){send({type:"getFile",err:String(err),text:text,id:data.id});});}else if(data.type=="debug"){window.console.log(data.message);}else if(data.id&&pending[data.id]){pending[data.id](data.err,data.body);delete pending[data.id];}};worker.onerror=function(e){for(var id in pending)pending[id](e);pending={};};this.addFile=function(name,text){send({type:"add",name:name,text:text});};this.delFile=function(name){send({type:"del",name:name});};this.request=function(body,c){send({type:"req",body:body},c);};}});/* Tern worker code, must not run in the main thread: var server;this.onmessage=function(e){var data=e.data;switch(data.type){case"init":return startServer(data.defs,data.plugins,data.scripts);case"add":return server.addFile(data.name,data.text);case"del":return server.delFile(data.name);case"req":return server.request(data.body,function(err,reqData){postMessage({id:data.id,body:reqData,err:err&&String(err)});});case"getFile":var c=pending[data.id];delete pending[data.id];return c(data.err,data.text);default:throw new Error("Unknown message type: "+data.type);}};var nextId=0,pending={};function getFile(file,c){postMessage({type:"getFile",name:file,id:++nextId});pending[nextId]=c;}
function startServer(defs,plugins,scripts){if(scripts)importScripts.apply(null,scripts);server=new tern.Server({getFile:getFile,async:true,defs:defs,plugins:plugins});}
if(typeof importScripts==="function")this.console={log:function(v){postMessage({type:"debug",message:v});}}; */(function(mod){if(typeof exports=="object"&&typeof module=="object")
mod(require("../../lib/codemirror"));else if(typeof define=="function"&&define.amd)
define(["../../lib/codemirror"],mod);else
mod(CodeMirror);})(function(CodeMirror){"use strict";var Pos=CodeMirror.Pos;function findParagraph(cm,pos,options){var startRE=options.paragraphStart||cm.getHelper(pos,"paragraphStart");for(var start=pos.line,first=cm.firstLine();start>first;--start){var line=cm.getLine(start);if(startRE&&startRE.test(line))break;if(!/\S/.test(line)){++start;break;}}
var endRE=options.paragraphEnd||cm.getHelper(pos,"paragraphEnd");for(var end=pos.line+1,last=cm.lastLine();end<=last;++end){var line=cm.getLine(end);if(endRE&&endRE.test(line)){++end;break;}
if(!/\S/.test(line))break;}
return{from:start,to:end};}
function findBreakPoint(text,column,wrapOn,killTrailingSpace){var at=column
while(at<text.length&&text.charAt(at)==" ")at++
for(;at>0;--at)
if(wrapOn.test(text.slice(at-1,at+1)))break;for(var first=true;;first=false){var endOfText=at;if(killTrailingSpace)
while(text.charAt(endOfText-1)==" ")--endOfText;if(endOfText==0&&first)at=column;else return{from:endOfText,to:at};}}
function wrapRange(cm,from,to,options){from=cm.clipPos(from);to=cm.clipPos(to);var column=options.column||80;var wrapOn=options.wrapOn||/\s\S|-[^\.\d]/;var killTrailing=options.killTrailingSpace!==false;var changes=[],curLine="",curNo=from.line;var lines=cm.getRange(from,to,false);if(!lines.length)return null;var leadingSpace=lines[0].match(/^[ \t]*/)[0];for(var i=0;i<lines.length;++i){var text=lines[i],oldLen=curLine.length,spaceInserted=0;if(curLine&&text&&!wrapOn.test(curLine.charAt(curLine.length-1)+text.charAt(0))){curLine+=" ";spaceInserted=1;}
var spaceTrimmed="";if(i){spaceTrimmed=text.match(/^\s*/)[0];text=text.slice(spaceTrimmed.length);}
curLine+=text;if(i){var firstBreak=curLine.length>column&&leadingSpace==spaceTrimmed&&findBreakPoint(curLine,column,wrapOn,killTrailing);if(!firstBreak||firstBreak.from!=oldLen||firstBreak.to!=oldLen+spaceInserted){changes.push({text:[spaceInserted?" ":""],from:Pos(curNo,oldLen),to:Pos(curNo+1,spaceTrimmed.length)});}else{curLine=leadingSpace+text;++curNo;}}
while(curLine.length>column){var bp=findBreakPoint(curLine,column,wrapOn,killTrailing);changes.push({text:["",leadingSpace],from:Pos(curNo,bp.from),to:Pos(curNo,bp.to)});curLine=leadingSpace+curLine.slice(bp.to);++curNo;}}
if(changes.length)cm.operation(function(){for(var i=0;i<changes.length;++i){var change=changes[i];if(change.text||CodeMirror.cmpPos(change.from,change.to))
cm.replaceRange(change.text,change.from,change.to);}});return changes.length?{from:changes[0].from,to:CodeMirror.changeEnd(changes[changes.length-1])}:null;}
CodeMirror.defineExtension("wrapParagraph",function(pos,options){options=options||{};if(!pos)pos=this.getCursor();var para=findParagraph(this,pos,options);return wrapRange(this,Pos(para.from,0),Pos(para.to-1),options);});CodeMirror.commands.wrapLines=function(cm){cm.operation(function(){var ranges=cm.listSelections(),at=cm.lastLine()+1;for(var i=ranges.length-1;i>=0;i--){var range=ranges[i],span;if(range.empty()){var para=findParagraph(cm,range.head,{});span={from:Pos(para.from,0),to:Pos(para.to-1)};}else{span={from:range.from(),to:range.to()};}
if(span.to.line>=at)continue;at=span.from.line;wrapRange(cm,span.from,span.to,{});}});};CodeMirror.defineExtension("wrapRange",function(from,to,options){return wrapRange(this,from,to,options||{});});CodeMirror.defineExtension("wrapParagraphsInRange",function(from,to,options){options=options||{};var cm=this,paras=[];for(var line=from.line;line<=to.line;){var para=findParagraph(cm,Pos(line,0),options);paras.push(para);line=para.to;}
var madeChange=false;if(paras.length)cm.operation(function(){for(var i=paras.length-1;i>=0;--i)
madeChange=madeChange||wrapRange(cm,Pos(paras[i].from,0),Pos(paras[i].to-1),options);});return madeChange;});});