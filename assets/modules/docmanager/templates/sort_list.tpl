<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>[+lang.DM_module_title+]</title>
    <link rel="stylesheet" type="text/css" href="media/style[+theme+]/style.css" /> 
    <script type="text/javascript" src="media/script/mootools/mootools.js"></script>
    <script type="text/javascript" src="media/script/mootools/moodx.js"></script> 
    <script type="text/javascript" src="../assets/modules/docmanager/js/docmanager.js"></script>
    <script type="text/javascript">
	    function save() { 
		    //populateHiddenVars(); 
		    setTimeout("document.sortableListForm.submit()",1000); 
	    }
	    
	    function reset() {
	       document.resetform.submit();
	    }
	    
	    window.addEvent('domready', function() {
	       new Sortables($('sortlist'), {
	           onComplete: function() {
	               var list = '';
	               $$('li.sort').each(function(el, i) {
	                   list += el.id + ';';
	               });
	               $('list').value = list;
	           }
	       });
	       
	       if ([+sort.disable_tree_select+] == true) {
	           parent.tree.ca = '';
	       }
	    });
	    
	    parent.tree.updateTree();
    </script>
    <style type="text/css">        
        li {
            cursor: move;
            border: 1px solid #CCCCCC;
            background: #eee no-repeat 2px center;
            margin: 2px 0;
            list-style: none;
            padding: 1px 4px 1px 24px;
            min-height: 20px;
        }
        li.noChildren {
            background-image: url(media/style[+theme+]/images/tree/page.gif);
        }
        li.hasChildren {
            background-image: url(media/style[+theme+]/images/tree/folder.gif);
        }
    </style>
</head>
<body>
    <h1>[+lang.DM_module_title+]</h1>
    <form action="" method="post" name="resetform" style="display: none;">
        <input name="actionkey" type="hidden" value="0" />
    </form>
    <div id="actions">
        <ul class="actionButtons">
            <li id="Button1"><a href="#" onclick="reset();"><img src="media/style[+theme+]/images/icons/stop.png" align="absmiddle"> [+lang.DM_close+]</a></li>
            <li id="Button2" style="display:[+sort.save+]"><a href="#" onclick="save();"><img src="media/style[+theme+]/images/icons/save.png" align="absmiddle"> [+lang.DM_save+]</a></li>
            <li id="Button4"><a href="#" onclick="reset();"><img src="media/style[+theme+]/images/icons/cancel.png" align="absmiddle"> [+lang.DM_cancel+]</a></li>
        </ul>
    </div>
    
    <div class="section">
    <div class="sectionHeader">[+lang.DM_sort_title+]</div>
    <div class="sectionBody">
        [+sort.message+]
        <ul id="sortlist" class="sortableList">
            [+sort.options+]
        </ul>
	    <form action="" method="post" name="sortableListForm" style="display: none;">
            <input type="hidden" name="tabAction" value="sortList" />
            <input type="text" id="list" name="list" value="" />
        </form>
    </div>
    </div>
</body>
</html>