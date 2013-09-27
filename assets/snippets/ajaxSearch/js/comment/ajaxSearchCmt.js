// ajaxSearchCmt.js
// Version: 1.9.3 - created by Coroico
// 26/09/2012
// Description: This code is used to post a comment about ajax search results

// AjaxSearch Snippet folder location
var _asbase = 'assets/snippets/ajaxSearch/';

var xmlHttp;function GetXmlHttpObject(){var xmlHttp=null;try{xmlHttp=new XMLHttpRequest();}
catch(e){try{xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");}
catch(e){xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");}}
return xmlHttp;}
function commentSearch(){xmlHttp=GetXmlHttpObject()
if(xmlHttp==null){alert("Your browser does not support AJAX!");return;}
xmlHttp.onreadystatechange=function(){if(xmlHttp.readyState==4){var res=xmlHttp.responseText;var msg=document.getElementById('ajaxSearch_cmtThks');err=res.split('ERROR:')[1];if(!err)msg.style.visibility="visible";else msg.style.visibility="hidden";}}
var ascmt=document.getElementById('ajaxSearch_cmtArea').value;var hfld=document.getElementById('ajaxSearch_cmtHField').value;if((ascmt!='')&&(hfld=='')){var logid=document.getElementById('ajaxSearch_logid').value;var url='index-ajax.php';var q=_asbase+"classes/ajaxSearchLog.class.inc.php";var params="q="+q+"&ascmt="+ascmt+"&logid="+logid;xmlHttp.open("POST",url,true);xmlHttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");xmlHttp.setRequestHeader("Content-length",params.length);xmlHttp.setRequestHeader("Connection","close");xmlHttp.send(params);}}
function resetThksMessage(){var msg=document.getElementById('ajaxSearch_cmtThks');if(msg.style.visibility=="visible"){msg.style.visibility="hidden";}}