/*
 * AjaxSearch 1.9.3 - package AjaxSearch 1 - JQuery 1.4.2
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

var _version='1.9.3';var _opacity=1.;var _liveSearch=0;var _minChars=3;var _init='none';var _pagingType=1;jQuery(function($){function activateSearch(){for(ias=0;ias<asvar.length;ias++){var asv=eval('('+asvar[ias]+')');activateAsInstance(asv);}}
function activateAsInstance(asv){var as=Array();as['vsn']=asv.vsn;as['adv']=asv.adv;as['sub']=asv.sub;as['bxt']=asv.bxt;as['cfg']=asv.cfg;as['lt']=null;as['is']=false;if(as['vsn']!=_version){alert("AjaxSearch version obsolete. Empty your browser cache and check the version of AjaxSearch-jQuery.js file");return;}
var res=as['cfg'].match(/&pagingType=`([^`]*)`/);as['pgt']=_pagingType;if(res!==null)as['pgt']=res[1];res=as['cfg'].match(/&opacity=`([^`]*)`/);as['opc']=_opacity;if(res!=null)as['opc']=parseFloat(res[1]);res=as['cfg'].match(/&init=`([^`]*)`/);as['ini']=_init;if(res!=null)as['ini']=res[1];res=as['cfg'].match(/&liveSearch=`([^`]*)`/);as['lvs']=_liveSearch;if(res!=null)as['lvs']=parseInt(res[1]);res=as['cfg'].match(/&minChars=`([^`]*)`/);as['mch']=_minChars;if(res!=null)as['mch']=parseInt(res[1]);res=as['cfg'].match(/&asId=`([^`]*)`/);as['px']='';if(res!=null)as['px']=res[1]+'_';var p=as['px'];sf=$('#'+p+'ajaxSearch_form');as['sc']=$('<img src="'+_close+'" alt="'+_closeAlt+'" id="'+p+'searchClose" />').appendTo(sf).hide();as['sl']=$('<img src="'+_load+'" alt="'+_loadAlt+'" id="'+p+'indicator" />').appendTo(sf).hide();as['sr']=$('#'+p+'ajaxSearch_output').hide().removeClass('init');as['si']=$('#'+p+'ajaxSearch_input');as['se']=$('#'+p+'ajaxSearch_select');if(!as['lvs'])as['ss']=$('#'+p+'ajaxSearch_form input:submit');as['sc'].click(function(){closeSearch(as);return false;});if(!as['lvs'])as['ss'].click(function(){doSearch(as);return false;});else as['si'].keyup(function(){doLiveSearch(as);});if(as['si'].length)as['si'].keypress(function(e){var keyCode=e.keyCode||e.which;if(keyCode==13){doSearch(as);return false;}});doSearch(as);}
function doLiveSearch(as){if(as['lt']){window.clearTimeout(as['lt']);}
as['lt']=setTimeout(function(){doSearch(as)},400);}
function doSearch(as){if(!as['lvs']&&as['is'])return false;if(as['si'].length)s=as['si'].val();else if(as['se'].length){sl=new Array();as['se'].find('option:selected').each(function(i){sl.push($(this).attr('value'));});s=sl.join(" ");}
else s='';if(s==as['bxt'])s='';as['s']=s;if(as['si'].length&&(s.length!=0)&&as['lvs']&&(s.length<as['mch']))return false;if((s.length==0)&&(as['ini']=='none'))return false;as['is']=true;if(!as['lvs'])as['ss'].attr('disabled','disabled');var rbl=new Array("allwords","exactphrase","nowords");adv=as['adv'];for(var x=0;x<3;x++){if(rb=$('#'+as['px']+'radio_'+rbl[x])){if(rb.attr('checked')==true)adv=rb.attr('value');}}
as['adv']=adv;var pars={q:_base+'ajaxSearchPopup.php',search:as['s'],as_version:as['vsn'],advsearch:encodeURI(as['adv']),subsearch:encodeURI(as['sub']),ucfg:as['cfg']};as['sc'].hide();as['sl'].show();$.post('index-ajax.php',pars,function(text){var out=eval('('+text+')');if(out.ctgnm)ctgnm=eval('('+out.ctgnm+')');else ctgnm=new Array();if(out.ctgnb)ctgnb=eval('('+out.ctgnb+')');else ctgnb=new Array();as['sr'].hide();as['sr'].html(out.res).show().css('opacity',as['opc']);if(as['pgt']==2)initMoreButtons(ctgnm,as);else initNextLinks(ctgnm,as);if(!as['lvs'])as['ss'].removeAttr('disabled');as['is']=false;as['sl'].hide();as['sc'].show();});}
function closeSearch(as){as['sr'].hide();as['sc'].hide();as['sl'].hide();if(as['si'].length)as['si'].val(as['bxt']);as['is']=false;if(!as['lvs'])as['ss'].removeAttr('disabled');}
function cleanId(idnm){idn=idnm.replace(/\s+\|\|\s+/g,"_");idn=idn.replace(/\s+/g,"_");return idn;}
function updateNbResDisplayed(nb,asr){msg=asr.find('.AS_ajax_resultsDisplayed');if(msg.length){msgd=msg.html();msgp=msgd.match(/(^[^0-9]*)([0-9]*)(.*)$/);asnbrd=parseInt(msgp[2]);asnbrd=asnbrd+nb;nmsg=msgp[1]+asnbrd+msgp[3];msg.html(nmsg);}}
function initNextLinks(grnm,as){if(grnm){var p=as['px'];for(i=0,m=grnm.length;i<m;i++){gr=as['sr'].find('#'+p+'grpResult_'+cleanId(grnm[i]));pgn=i+',0,1';q=gr.find('#'+p+'next_'+cleanId(grnm[i]));if(q)initNextLink(q,gr,grnm[i],as,pgn);}}}
function initNextLink(q,gr,grnm,as,pgn){q.click(function(){nextResults(gr,grnm,as,pgn);});}
function nextResults(gr,grnm,as,pgn){var pars={q:_base+'ajaxSearchPopup.php',search:as['s'],as_version:as['vsn'],advsearch:encodeURI(as['adv']),subsearch:encodeURI(as['sub']),ucfg:as['cfg'],pgn:pgn};$.post("index-ajax.php",pars,function(text){var out=eval('('+text+')');var p=as['px'];if(out.res){nextResDisplayed(gr,out.res,as['sr']);gr.html(out.res);}
if(out.pgn){gr.find('#'+p+'prev_'+cleanId(grnm)).click(function(){nextResults(gr,grnm,as,out.pgn+',-1');});gr.find('#'+p+'next_'+cleanId(grnm)).click(function(){nextResults(gr,grnm,as,out.pgn+',1');});}});}
function nextResDisplayed(gr,html,asr){nbrd=0;gr.find('.AS_ajax_result').each(function(){nbrd-=1;});var copy=$('<div></div>').append(html);copy.find('.AS_ajax_result').each(function(){nbrd+=1;});updateNbResDisplayed(nbrd,asr);}
function initMoreButtons(grnm,as){if(grnm){var p=as['px'];for(i=0,m=grnm.length;i<m;i++){gr=as['sr'].find('#'+p+'grpResult_'+cleanId(grnm[i]));pgn=i+',0,1';q=gr.find('#'+p+'more_'+cleanId(grnm[i]));if(q)initMoreButton(q,gr,grnm[i],as,pgn);}}}
function initMoreButton(q,gr,grnm,as,pgn){q.click(function(){moreResults(gr,grnm,as,pgn);});}
function moreResults(gr,grnm,as,pgn){var pars={q:_base+'ajaxSearchPopup.php',search:as['s'],as_version:as['vsn'],advsearch:encodeURI(as['adv']),subsearch:encodeURI(as['sub']),ucfg:as['cfg'],pgn:pgn};$.post("index-ajax.php",pars,function(text){var out=eval('('+text+')');var p=as['px'];if(out.hdr){gr.find('.AS_ajax_grpResultName').remove();gr.prepend(out.hdr);}
if(out.res){moreResDisplayed(out.res,as['sr']);gr.find('.paging2').before(out.res);}
if(out.ftr){gr.find('.paging2').remove();gr.append(out.ftr);}
if(out.pgn)gr.find('#'+p+'more_'+cleanId(grnm)).click(function(){moreResults(gr,grnm,as,out.pgn+',1');});});}
function moreResDisplayed(html,asr){var copy=$('<div></div>').append(html);nbrd=0;copy.find('.AS_ajax_result').each(function(){nbrd+=1;});updateNbResDisplayed(nbrd,asr);}
activateSearch();});