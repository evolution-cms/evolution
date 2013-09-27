/*
 * AjaxSearch 1.9.3 - package AjaxSearch 1 - Mootools 1.2.4
 * author: Coroico - www.evo.wangba.fr - 26/09/2012
 *
 * Licensed under the GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// set the folder location to the correct location of ajaxSearch.php
var _base='assets/snippets/ajaxSearch/';

// set the loading and the close image to the correct location for you
var _close=_base + 'images/cross.png';  // close image
var _closeAlt='close search';
var _load=_base + 'images/indicator.white.gif'; // loading image
var _loadAlt='loading';

var _version='1.9.3';var _opacity=1;var _liveSearch=0;var _minChars=3;var _init='none';var _pagingType=1;function activateSearch(){for(ias=0;ias<asvar.length;ias++){var asv=eval('('+asvar[ias]+')');activateAsInstance(asv);}}
function activateAsInstance(asv){var as=Array();as['vsn']=asv.vsn;as['adv']=asv.adv;as['sub']=asv.sub;as['bxt']=asv.bxt;as['cfg']=asv.cfg;as['lt']=null;as['is']=false;if(as['vsn']!=_version){alert("AjaxSearch version obsolete. Empty your browser cache and check the version of AjaxSearch-jQuery.js file");return;}
var res=as['cfg'].match(/&pagingType=`([^`]*)`/);as['pgt']=_pagingType;if(res!==null)as['pgt']=res[1];res=as['cfg'].match(/&opacity=`([^`]*)`/);as['opc']=_opacity;if(res!==null)as['opc']=parseFloat(res[1]);res=as['cfg'].match(/&init=`([^`]*)`/);as['ini']=_init;if(res!==null)as['ini']=res[1];res=as['cfg'].match(/&liveSearch=`([^`]*)`/);as['lvs']=_liveSearch;if(res!==null)as['lvs']=parseInt(res[1]);res=as['cfg'].match(/&minChars=`([^`]*)`/);as['mch']=_minChars;if(res!==null)as['mch']=parseInt(res[1]);res=as['cfg'].match(/&asId=`([^`]*)`/);as['px']='';if(res!==null)as['px']=res[1]+'_';var p=as['px'];sf=$(p+'ajaxSearch_form');if(sf){as['so']=$(p+'ajaxSearch_output');as['so'].setStyle('opacity','0');as['so'].removeClass('init');var c=new Element('img');c.setProperties({src:_close,alt:_closeAlt,id:p+'searchClose'});c.setStyle('opacity','0');sf.appendChild(c);as['sc']=c;var l=new Element('img');l.setProperties({src:_load,alt:_loadAlt,id:p+'indicator'});l.setStyle('opacity','0');sf.appendChild(l);as['sl']=l;var n=new Element('div');n.setProperty('id','current-search-results');n.setStyle('opacity','1');as['so'].appendChild(n);as['sr']=n;as['si']=$(p+'ajaxSearch_input');as['se']=$(p+'ajaxSearch_select');if(!as['lvs'])as['ss']=$(p+'ajaxSearch_submit');as['sc'].addEvent('click',function(){closeSearch(as);});if(!as['lvs'])sf.addEvent('submit',function(e){new Event(e).stop();doSearch(as);});else as['si'].addEvent('keyup',function(){doLiveSearch(as);});if(as['si'])as['si'].addEvent('keydown',function(e){var keyCode=e.keyCode||e.which;if(keyCode==13){doSearch(as);}});doSearch(as);}}
function doLiveSearch(as){if(as['lt']){window.clearTimeout(as['lt']);}
as['lt']=setTimeout(function(){doSearch(as)},400);}
function doSearch(as){if(!as['lvs']&&as['is'])return false;if(as['si'])s=as['si'].value;else if(as['se']){sl=new Array();for(var i=0;i<as['se'].options.length;i++)if(as['se'].options[i].selected)sl.push(as['se'].options[i].value);s=sl.join(" ");}
else s='';if(s==as['bxt'])s='';as['s']=s;if(as['si']&&(s.length!=0)&&as['lvs']&&(s.length<as['mch']))return false;if((s.length==0)&&(as['ini']=='none'))return false;as['is']=true;if(!as['lvs'])as['ss'].disabled=true;var rbl=new Array("allwords","exactphrase","nowords");adv=as['adv'];for(var x=0;x<3;x++){if(rb=$(as['px']+'radio_'+rbl[x])){if(rb.checked==true)adv=rb.value;}}
as['adv']=adv;var pars={q:_base+'ajaxSearchPopup.php',search:as['s'],as_version:as['vsn'],advsearch:encodeURI(as['adv']),subsearch:encodeURI(as['sub']),ucfg:as['cfg']};as['sc'].setStyle('opacity','0');as['sl'].setStyle('opacity','1');asr=new Request({url:'index-ajax.php',method:'post',data:pars,onComplete:function(text){doSearchResp(text,as);}});asr.send();}
function doSearchResp(text,as){var out=eval('('+text+')');if(out.ctgnm)ctgnm=eval('('+out.ctgnm+')');else ctgnm=new Array();if(out.ctgnb)ctgnb=eval('('+out.ctgnb+')');else ctgnb=new Array();as['so'].setStyle('opacity','0');as['sr'].set('html',out.res);if(as['pgt']==2)initMoreButtons(ctgnm,as);else initNextLinks(ctgnm,as);as['so'].setStyle('opacity',as['opc']);as['sl'].setStyle('opacity','0');as['sc'].setStyle('opacity','1');as['is']=false;if(!as['lvs'])as['ss'].disabled=false;}
function closeSearch(as){as['sl'].setStyle('opacity','0');as['sc'].setStyle('opacity','0');as['so'].setStyle('opacity','0');as['sr'].set('html','');if(as['si'])as['si'].value=as['bxt'];if(!as['lvs'])as['ss'].disabled=false;}
function cleanId(idnm){idn=idnm.replace(/\s+\|\|\s+/g,"_");idn=idn.replace(/\s+/g,"_");return idn;}
function updateNbResDisplayed(nb,asr){var msg=asr.getElement('span.AS_ajax_resultsDisplayed');if(msg){msgd=msg.innerHTML;msgp=msgd.match(/(^[^0-9]*)([0-9]*)(.*)$/);asnbrd=parseInt(msgp[2]);asnbrd=asnbrd+nb;nmsg=msgp[1]+asnbrd+msgp[3];msg.set('html',nmsg);}}
function initNextLinks(grnm,as){if(grnm){var p=as['px'];for(i=0,m=grnm.length;i<m;i++){var gr=as['sr'].getElementById(p+'grpResult_'+cleanId(grnm[i]));var pgn=i+',0,1';var grn=$(gr).getElementById(p+'next_'+cleanId(grnm[i]));var q=$(grn);if(q)initNextLink(q,gr,grnm[i],as,pgn);}}}
function initNextLink(q,gr,grnm,as,pgn){q.addEvent('click',function(){nextResults(gr,grnm,as,pgn);})}
function nextResults(gr,grnm,as,pgn){var pars={q:_base+'ajaxSearchPopup.php',search:as['s'],as_version:as['vsn'],advsearch:encodeURI(as['adv']),subsearch:encodeURI(as['sub']),ucfg:as['cfg'],pgn:pgn};asr=new Request({url:'index-ajax.php',method:'post',data:pars,onComplete:function(text){nextResultsResp(text,gr,grnm,as);}});asr.send();}
function nextResultsResp(text,gr,grnm,as){var out=eval('('+text+')');var c=$(gr);var p=as['px'];if(out.res){nextResDisplayed(c,out.res,as['sr']);gr.set('html',out.res);}
if(out.pgn){pprev=$(c.getElementById(p+'prev_'+cleanId(grnm)));if(pprev)pprev.addEvent('click',function(){nextResults(gr,grnm,as,out.pgn+',-1');});pnext=$(c.getElementById(p+'next_'+cleanId(grnm)));if(pnext)pnext.addEvent('click',function(){nextResults(gr,grnm,as,out.pgn+',1');});}}
function nextResDisplayed(gr,html,asr){nbrd=0;gr.getElements('.AS_ajax_result').each(function(){nbrd-=1;});var copy=new Element('div');copy.set('html',html);copy.getElements('.AS_ajax_result').each(function(){nbrd+=1;});updateNbResDisplayed(nbrd,asr);}
function initMoreButtons(grnm,as){if(grnm){var p=as['px'];for(i=0,m=grnm.length;i<m;i++){var gr=as['sr'].getElementById(p+'grpResult_'+cleanId(grnm[i]));var pgn=i+',0,1';var grn=$(gr).getElementById(p+'more_'+cleanId(grnm[i]));var q=$(grn);if(q)initMoreButton(q,gr,grnm[i],as,pgn);}}}
function initMoreButton(q,gr,grnm,as,pgn){q.addEvent('click',function(){moreResults(gr,grnm,as,pgn);})}
function moreResults(gr,grnm,as,pgn){var pars={q:_base+'ajaxSearchPopup.php',search:as['s'],as_version:as['vsn'],advSearch:encodeURI(as['adv']),subSearch:encodeURI(as['sub']),ucfg:as['cfg'],pgn:pgn};asr=new Request({url:'index-ajax.php',method:'post',data:pars,onComplete:function(text){moreResultsResp(text,gr,grnm,as);}});asr.send();}
function moreResultsResp(text,gr,grnm,as){var out=eval('('+text+')');var c=$(gr);var p=as['px'];if(out.hdr){var copy=new Element('div');copy.set('html',out.hdr);nhdr=copy.getElement('.AS_ajax_grpResultName');hdr=c.getElement('.AS_ajax_grpResultName');nhdr.replaces(hdr);}
var ftr=c.getElement('.paging2');if(out.res){moreResDisplayed(out.res,as['sr']);var copy=new Element('div');copy.set('html',out.res);copy.getElements('.AS_ajax_result').each(function(elt){$(elt).injectBefore(ftr);});}
if(out.ftr){var copy=new Element('div');copy.set('html',out.ftr);nftr=copy.getElement('.paging2');nftr.replaces(ftr);}
if(out.pgn){pmore=c.getElementById(p+'more_'+cleanId(grnm));if(pmore)$(pmore).addEvent('click',function(){moreResults(gr,grnm,as,out.pgn+',1');});}}
function moreResDisplayed(html,asr){nbrd=0;var copy=new Element('div');copy.set('html',html);copy.getElements('.AS_ajax_result').each(function(){nbrd+=1;});updateNbResDisplayed(nbrd,asr);}
window.addEvent('domready',function(){activateSearch();})