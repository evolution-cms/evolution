<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <title>[+lang.DM_module_title+]</title>
        <link rel="stylesheet" type="text/css" href="media/style[+theme+]/style.css" /> 
        <script type="text/javascript" src="media/script/tabpane.js"></script>
        <script type="text/javascript" src="media/script/datefunctions.js"></script>
        <script type="text/javascript" src="media/script/mootools/mootools.js"></script>
        <script type="text/javascript" src="media/calendar/datepicker.js"></script>
        <script type="text/javascript" src="media/script/mootools/moodx.js"></script>
        <script type="text/javascript" src="../assets/modules/docmanager/js/docmanager.js"></script>
        <script type="text/javascript">
            function loadTemplateVars(tplId) {
			    $('tvloading').style.display = 'block';
			    new Ajax('[+ajax.endpoint+]', {
			        update: 'results',
			        method: 'post',
			        postBody: 'theme=[+theme+]&tplID=' + tplId,
			        evalScripts: true,
			        onComplete: function(r) {
			            $('tvloading').style.display = 'none';
			        }
			    
			    }).request();
			}
			
		    function save() {
                document.newdocumentparent.submit();
            }   

		    function setMoveValue(pId, pName) {
		      if (pId==0 || checkParentChildRelation(pId, pName)) {
		        document.newdocumentparent.new_parent.value=pId;
		        $('parentName').innerHTML = "Parent: <strong>" + pId + "</strong> (" + pName + ")";
		      }
		    }

			function checkParentChildRelation(pId, pName) {
			    var sp;
			    var id = document.newdocumentparent.id.value;
			    var tdoc = parent.tree.document;
			    var pn = (tdoc.getElementById) ? tdoc.getElementById("node"+pId) : tdoc.all["node"+pId];
			    if (!pn) return;
			        while (pn.p>0) {
			            pn = (tdoc.getElementById) ? tdoc.getElementById("node"+pn.p) : tdoc.all["node"+pn.p];
			            if (pn.id.substr(4)==id) {
			                alert("Illegal Parent");
			                return;
			            }
			        }
			    
			    return true;
			}
			
			window.addEvent('domready', function() {
			    var dpOffset = [+datepicker.offset+];
			    var dpformat = "[+datetime.format+]" + ' hh:mm:00';
			    new DatePicker($('date_pubdate'), {'yearOffset' : dpOffset, 'format' : dpformat });
			    new DatePicker($('date_unpubdate'), {'yearOffset' : dpOffset, 'format' : dpformat});
			    new DatePicker($('date_createdon'), {'yearOffset' : dpOffset, 'format' : dpformat});
			    new DatePicker($('date_editedon'), {'yearOffset' : dpOffset, 'format' : dpformat});
			});
        </script>
    </head>
    <body>
        <h1>[+lang.DM_module_title+]</h1>
        <div id="actions">
            <ul class="actionButtons">
                <li id="Button1"><a href="#" onclick="document.location.href='index.php?a=106';"><img src="media/style[+theme+]/images/icons/stop.png" /> [+lang.DM_close+]</a></li>
            </ul>
        </div>        
	    <div class="sectionBody">
	        <div class="tab-pane" id="docManagerPane"> 
	        <script type="text/javascript"> 
	            tpResources = new WebFXTabPane(document.getElementById('docManagerPane')); 
	        </script>
	        
	        <div class="tab-page" id="tabTemplates">  
	            <h2 class="tab">[+lang.DM_change_template+]</h2>  
	            <script type="text/javascript">tpResources.addTabPage(document.getElementById('tabTemplates'));</script>
	           [+view.templates+]
	        </div>
	   
	        <div class="tab-page" id="tabTemplateVariables">  
	            <h2 class="tab">[+lang.DM_template_variables+]</h2>  
	            <script type="text/javascript">tpResources.addTabPage(document.getElementById("tabTemplateVariables" ));</script> 
	           [+view.templatevars+]
	        </div>
	    
	        <div class="tab-page" id="tabDocPermissions">  
	            <h2 class="tab">[+lang.DM_doc_permissions+]</h2>  
	            <script type="text/javascript">tpResources.addTabPage(document.getElementById("tabDocPermissions"));</script> 
	           [+view.documentgroups+]
	        </div>
	      
	        <div class="tab-page" id="tabSortMenu">  
	            <h2 class="tab">[+lang.DM_sort_menu+] </h2>  
	            <script type="text/javascript">tpResources.addTabPage(document.getElementById("tabSortMenu"));</script> 
	           [+view.sort+]
	        </div>
	
	        <div class="tab-page" id="tabOther">  
	           <h2 class="tab">[+lang.DM_other+]</h2>  
	           <script type="text/javascript">tpResources.addTabPage(document.getElementById("tabOther"));</script>
	           [+view.misc+]
	           [+view.changeauthors+]
	        </div>
	    </div>
	</div>
	[+view.documents+]
    </body>
</html>