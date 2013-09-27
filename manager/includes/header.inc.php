<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
$mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';

// invoke OnManagerRegClientStartupHTMLBlock event
$evtOut = $modx->invokeEvent('OnManagerMainFrameHeaderHTMLBlock');
$modx_textdir = isset($modx_textdir) ? $modx_textdir : null;
$onManagerMainFrameHeaderHTMLBlock = is_array($evtOut) ? implode("\n", $evtOut) : '';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo  $mxla . '" lang="' .  $mxla . '"' . ($modx_textdir ? ' dir="rtl"' : ''); ?>>
<head>
    <title>MODX</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $modx_manager_charset; ?>" />
    <link rel="stylesheet" type="text/css" href="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>style.css" />

    <!-- OnManagerMainFrameHeaderHTMLBlock -->
    <?php echo $onManagerMainFrameHeaderHTMLBlock; ?>
    
    <script src="media/script/mootools/mootools.js" type="text/javascript"></script>
    <script src="media/script/mootools/moodx.js" type="text/javascript"></script>
    <script type="text/javascript">
		/* <![CDATA[ */
        window.addEvent('load', document_onload);
        window.addEvent('beforeunload', document_onunload);
        
        function document_onload() {
            stopWorker();
            hideLoader();
            <?php echo isset($_REQUEST['r']) ? " doRefresh(".$_REQUEST['r'].");" : "" ;?>;
        };

		function reset_path(elementName) {
	  		document.getElementById(elementName).value = document.getElementById('default_' + elementName).innerHTML;
		}

        var dontShowWorker = false;
        function document_onunload() {
            if(!dontShowWorker) {
                top.mainMenu.work();
            }
        };

        // set tree to default action.
        if (parent.tree) parent.tree.ca = "open";

		// call the updateMail function, updates mail notification in top navigation
		if (top.mainMenu) {
			if(top.mainMenu.updateMail) {
				top.mainMenu.updateMail(true);
			}
		}
		
        function stopWorker() {
            try {
                parent.mainMenu.stopWork();
            } catch(oException) {
                ww = window.setTimeout('stopWorker()',500);
            }
        }

        function doRefresh(r) {
            try {
                rr = r;
                top.mainMenu.startrefresh(rr);
            } catch(oException) {
                vv = window.setTimeout('doRefresh()',1000);
            }
        }
        var documentDirty=false;

        function checkDirt(evt) {
            if(documentDirty==true) {
				var message = "<?php echo $_lang['warning_not_saved']; ?>";
				if (typeof evt == 'undefined') {
					evt = window.event;
            }
				if (evt) {
					evt.returnValue = message;
        }
				return message;
  			}
        }

        function saveWait(fName) {
            document.getElementById("savingMessage").innerHTML = "<?php echo $_lang['saving']; ?>";
            for(i = 0; i < document.forms[fName].elements.length; i++) {
                document.forms[fName].elements[i].disabled='disabled';
            }
        }

        var managerPath = "";

        function hideLoader() {
            document.getElementById('preLoader').style.display = "none";
        }

        hideL = window.setTimeout("hideLoader()", 1500);

        // add the 'unsaved changes' warning event handler
        if( window.addEventListener ) {
			window.addEventListener('beforeunload',checkDirt,false);
		} else if ( window.attachEvent ) {
			window.attachEvent('onbeforeunload',checkDirt);
		} else {
			window.onbeforeunload = checkDirt;
		}
		/* ]]> */
    </script>
</head>
<body ondragstart="return false"<?php echo $modx_textdir ? ' class="rtl"':''?>>

<div id="preLoader"><table width="100%" border="0" cellpadding="0"><tr><td align="center"><div class="preLoaderText"><?php echo $_style['ajax_loader']; ?></div></td></tr></table></div>
