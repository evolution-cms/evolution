/*
 * AjaxSearch 1.9.3 - clearDefault
 * Copyright (c) 2012 Coroico - www.modx.wangba.fr
 *
 * Licensed under the GPL license: http://www.gnu.org/copyleft/gpl.html
 */
 
// This code come from http://www.yourhtmlsource.com/forms/clearingdefaulttext.html

function addEventAS(element,eventType,lamdaFunction,useCapture){if(element.addEventListener){element.addEventListener(eventType,lamdaFunction,useCapture);return true;}else if(element.attachEvent){var r=element.attachEvent('on'+eventType,lamdaFunction);return r;}else{return false;}}
addEventAS(window,'load',clearFormFields,false);function clearFormFields(){var formInputs=document.getElementsByTagName('input');for(var i=0;i<formInputs.length;i++){var theInput=formInputs[i];if(theInput.type=='text'&&theInput.className.match(/\bcleardefault\b/)){addEventAS(theInput,'focus',clearDefaultText,false);addEventAS(theInput,'blur',replaceDefaultText,false);if(theInput.value!=''){theInput.defaultText=theInput.value;}}}}
function clearDefaultText(e){var target=window.event?window.event.srcElement:e?e.target:null;if(!target)return;if(target.value==target.defaultText){target.value='';}}
function replaceDefaultText(e){var target=window.event?window.event.srcElement:e?e.target:null;if(!target)return;if(target.value==''&&target.defaultText){target.value=target.defaultText;}}