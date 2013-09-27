<?php if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

    $theme = $manager_theme ? "$manager_theme/":"";
    $modx_textdir = isset($modx_textdir) ? $modx_textdir : null;
    function constructLink($action, $img, $text, $allowed) {
        if($allowed==1) { ?>
            <div class="menuLink" onclick="menuHandler(<?php echo $action ; ?>); hideMenu();">
        <?php } else { ?>
            <div class="menuLinkDisabled">
        <?php } ?>
                <img src="<?php echo $img; ?>" /><?php echo $text; ?>
            </div>
        <?php
    }
    $mxla = $modx_lang_attribute ? $modx_lang_attribute : 'en';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html <?php echo ($modx_textdir ? 'dir="rtl" lang="' : 'lang="').$mxla.'" xml:lang="'.$mxla.'"'; ?>>
<head>
    <title>Document Tree</title>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $modx_manager_charset; ?>" />
    <link rel="stylesheet" type="text/css" href="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>style.css" />
    <script src="media/script/mootools/mootools.js" type="text/javascript"></script>
    <script src="media/script/mootools/moodx.js" type="text/javascript"></script>
    <script type="text/javascript">
    window.addEvent('load', function(){
        resizeTree();
        restoreTree();
        window.addEvent('resize', resizeTree);
    });

    // preload images
    var i = new Image(18,18);
    i.src="<?php echo $_style["tree_page"]?>";
    i = new Image(18,18);
    i.src="<?php echo isset($_style["tree_globe"]) ? $_style["tree_globe"] : $_style["tree_page"]; ?>";
    i = new Image(18,18);
    i.src="<?php echo $_style["tree_minusnode"]?>";
    i = new Image(18,18);
    i.src="<?php echo $_style["tree_plusnode"]?>";
    i = new Image(18,18);
    i.src="<?php echo isset($_style["tree_folderopen"]) ? $_style["tree_folderopen"] : $_style["tree_page"]; ?>";
    i = new Image(18,18);
    i.src="<?php echo isset($_style["tree_folder"]) ? $_style["tree_folder"] : $_style["tree_page"]; ?>";


    var rpcNode = null;
    var ca = "open";
    var selectedObject = 0;
    var selectedObjectDeleted = 0;
    var selectedObjectName = "";
    var _rc = 0; // added to fix onclick body event from closing ctx menu

<?php
    echo  "var openedArray = new Array();\n";
    if (isset($_SESSION['openedArray'])) {
            $opened = explode("|", $_SESSION['openedArray']);

            foreach ($opened as $item) {
                 printf("openedArray[%d] = 1;\n", $item);
            }
    }
?>

    // return window dimensions in array
    function getWindowDimension() {
        var width  = 0;
        var height = 0;

        if ( typeof( window.innerWidth ) == 'number' ){
            width  = window.innerWidth;
            height = window.innerHeight;
        }else if ( document.documentElement &&
                 ( document.documentElement.clientWidth ||
                   document.documentElement.clientHeight ) ){
            width  = document.documentElement.clientWidth;
            height = document.documentElement.clientHeight;
        }
        else if ( document.body &&
                ( document.body.clientWidth || document.body.clientHeight ) ){
            width  = document.body.clientWidth;
            height = document.body.clientHeight;
        }

        return {'width':width,'height':height};
    }

    function resizeTree() {

        // get window width/height
        var win = getWindowDimension();

        // set tree height
        var tree = $('treeHolder');
        var tmnu = $('treeMenu');
        tree.style.width = (win['width']-20)+'px';
        tree.style.height = (win['height']-tree.offsetTop-6)+'px';
        tree.style.overflow = 'auto';
    }

    function getScrollY() {
      var scrOfY = 0;
      if( typeof( window.pageYOffset ) == 'number' ) {
        //Netscape compliant
        scrOfY = window.pageYOffset;
      } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
        //DOM compliant
        scrOfY = document.body.scrollTop;
      } else if( document.documentElement &&
          (document.documentElement.scrollTop ) ) {
        //IE6 standards compliant mode
        scrOfY = document.documentElement.scrollTop;
      }
      return scrOfY;
    }

    function showPopup(id,title,e){
        var x,y
        var mnu = $('mx_contextmenu');
        var bodyHeight = parseInt(document.body.offsetHeight);
        x = e.clientX>0 ? e.clientX:e.pageX;
        y = e.clientY>0 ? e.clientY:e.pageY;
        y = getScrollY()+(y/2);
        if (y+mnu.offsetHeight > bodyHeight) {
            // make sure context menu is within frame
            y = y - ((y+mnu.offsetHeight)-bodyHeight+5);
        }
        itemToChange=id;
        selectedObjectName= title;
        dopopup(x+5,y);
        e.cancelBubble=true;
        return false;
    };

    function dopopup(x,y) {
        if(selectedObjectName.length>20) {
            selectedObjectName = selectedObjectName.substr(0, 20) + "...";
        }
        var h,context = $('mx_contextmenu');
        context.style.left= x<?php echo $modx_textdir ? '-190' : '';?>+"px"; //offset menu to the left if rtl is selected
        context.style.top = y+"px";
        var elm = $("nameHolder");
        elm.innerHTML = selectedObjectName;

        context.style.visibility = 'visible';
        _rc = 1;
        setTimeout("_rc = 0;",100);
    }

    function hideMenu() {
        if (_rc) return false;
        $('mx_contextmenu').style.visibility = 'hidden';
    }

    function toggleNode(node,indent,parent,expandAll,privatenode) {
        privatenode = (!privatenode || privatenode == '0') ? privatenode = '0' : privatenode = '1';
        rpcNode = $(node.parentNode.lastChild);

        var rpcNodeText;
        var loadText = "<?php echo $_lang['loading_doc_tree'];?>";

        var signImg = document.getElementById("s"+parent);
        var folderImg = document.getElementById("f"+parent);

        if (rpcNode.style.display != 'block') {
            // expand
            if(signImg && signImg.src.indexOf('media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/tree/plusnode.gif')>-1) {
                signImg.src = '<?php echo $_style["tree_minusnode"]; ?>';
                folderImg.src = (privatenode == '0') ? '<?php echo $_style["tree_folderopen"]; ?>' :'<?php echo $_style["tree_folderopen_secure"]; ?>';
            }

            rpcNodeText = rpcNode.innerHTML;

            if (rpcNodeText=="" || rpcNodeText.indexOf(loadText)>0) {
                var i, spacer='';
                for(i=0;i<=indent+1;i++) spacer+='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                rpcNode.style.display = 'block';
                //Jeroen set opened
                openedArray[parent] = 1 ;
                //Raymond:added getFolderState()
                var folderState = getFolderState();
                rpcNode.innerHTML = "<span class='emptyNode' style='white-space:nowrap;'>"+spacer+"&nbsp;&nbsp;&nbsp;"+loadText+"...<\/span>";
                new Ajax('index.php?a=1&f=nodes&indent='+indent+'&parent='+parent+'&expandAll='+expandAll+folderState, {method: 'get',onComplete:rpcLoadData}).request();
            } else {
                rpcNode.style.display = 'block';
                //Jeroen set opened
                openedArray[parent] = 1 ;
            }
        }
        else {
            // collapse
            if(signImg && signImg.src.indexOf('media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/tree/minusnode.gif')>-1) {
                signImg.src = '<?php echo $_style["tree_plusnode"]; ?>';
                folderImg.src = (privatenode == '0') ? '<?php echo $_style["tree_folder"]; ?>' : '<?php echo $_style["tree_folder_secure"]; ?>';
            }
            //rpcNode.innerHTML = '';
            rpcNode.style.display = 'none';
            openedArray[parent] = 0 ;
        }
    }

    function rpcLoadData(response) {
        if(rpcNode != null){
            rpcNode.innerHTML = typeof response=='object' ? response.responseText : response ;
            rpcNode.style.display = 'block';
            rpcNode.loaded = true;
            var elm = top.mainMenu.$("buildText");
            if (elm) {
                elm.innerHTML = "";
                elm.style.display = 'none';
            }
            // check if bin is full
            if(rpcNode.id=='treeRoot') {
                var e = $('binFull');
                if(e) showBinFull();
                else showBinEmpty();
            }

            // check if our payload contains the login form :)
            e = $('mx_loginbox');
            if(e) {
                // yep! the seession has timed out
                rpcNode.innerHTML = '';
                top.location = 'index.php';
            }
        }
    }

    function expandTree() {
        rpcNode = $('treeRoot');
        new Ajax('index.php?a=1&f=nodes&indent=1&parent=0&expandAll=1', {method: 'get',onComplete:rpcLoadData}).request();
    }

    function collapseTree() {
        rpcNode = $('treeRoot');
        new Ajax('index.php?a=1&f=nodes&indent=1&parent=0&expandAll=0', {method: 'get',onComplete:rpcLoadData}).request();
    }

    // new function used in body onload
    function restoreTree() {
        rpcNode = $('treeRoot');
        new Ajax('index.php?a=1&f=nodes&indent=1&parent=0&expandAll=2', {method: 'get',onComplete:rpcLoadData}).request();
    }

    function setSelected(elSel) {
        var all = document.getElementsByTagName( "SPAN" );
        var l = all.length;

        for ( var i = 0; i < l; i++ ) {
            el = all[i]
            cn = el.className;
            if (cn=="treeNodeSelected") {
                el.className="treeNode";
            }
        }
        elSel.className="treeNodeSelected";
    };

    function setHoverClass(el, dir) {
        if(el.className!="treeNodeSelected") {
            if(dir==1) {
                el.className="treeNodeHover";
            } else {
                el.className="treeNode";
            }
        }
    };

    // set Context Node State
    function setCNS(n, b) {
        if(b==1) {
            n.style.backgroundColor="beige";
        } else {
            n.style.backgroundColor="";
        }
    };

    function updateTree() {
        rpcNode = $('treeRoot');
        treeParams = 'a=1&f=nodes&indent=1&parent=0&expandAll=2&dt=' + document.sortFrm.dt.value + '&tree_sortby=' + document.sortFrm.sortby.value + '&tree_sortdir=' + document.sortFrm.sortdir.value;
        new Ajax('index.php?'+treeParams, {method: 'get',onComplete:rpcLoadData}).request();
    }

    function emptyTrash() {
        if(confirm("<?php echo $_lang['confirm_empty_trash']; ?>")==true) {
            top.main.document.location.href="index.php?a=64";
        }
    }

    currSorterState="none";
    function showSorter() {
        if(currSorterState=="none") {
            currSorterState="block";
            document.getElementById('floater').style.display=currSorterState;
        } else {
            currSorterState="none";
            document.getElementById('floater').style.display=currSorterState;
        }
    }

    function treeAction(id, name, treedisp_children) {
        if(ca=="move") {
            try {
                parent.main.setMoveValue(id, name);
            } catch(oException) {
                alert('<?php echo $_lang['unable_set_parent']; ?>');
            }
        }
        if(ca=="open" || ca=="") {
            if(id==0) {
                // do nothing?
                parent.main.location.href="index.php?a=2";
            } else {
                // parent.main.location.href="index.php?a=3&id=" + id + getFolderState(); //just added the getvar &opened=
                if(treedisp_children==0) {
					parent.main.location.href="index.php?a=3&id=" + id + getFolderState();
				} else {
					parent.main.location.href="index.php?a=<?php echo (!empty($modx->config['tree_page_click']) ? $modx->config['tree_page_click'] : '27'); ?>&id=" + id; // edit as default action
				}
            }
        }
        if(ca=="parent") {
            try {
                parent.main.setParent(id, name);
            } catch(oException) {
                alert('<?php echo $_lang['unable_set_parent']; ?>');
            }
        }
        if(ca=="link") {
            try {
                parent.main.setLink(id);
            } catch(oException) {
                alert('<?php echo $_lang['unable_set_link']; ?>');
            }
        }
    }

    //Raymond: added getFolderState,saveFolderState
    function getFolderState(){
        if (openedArray != [0]) {
                oarray = "&opened=";
                for (key in openedArray) {
                   if (openedArray[key] == 1) {
                      oarray += key+"|";
                   }
                }
        } else {
                oarray = "&opened=";
        }
        return oarray;
    }
    function saveFolderState() {
        var folderState = getFolderState();
        new Ajax('index.php?a=1&f=nodes&savestateonly=1'+folderState, {method: 'get'}).request();
    }

    // show state of recycle bin
    function showBinFull() {
        var a = $('Button10');
        var title = '<?php echo $_lang['empty_recycle_bin']; ?>';
        if (a) {
            if(!a.setAttribute) a.title = title;
        else a.setAttribute('title',title);
        a.innerHTML = '<?php echo $_style['empty_recycle_bin']; ?>';
        a.className = 'treeButton';
        a.onclick = emptyTrash;
    }
    }

    function showBinEmpty() {
        var a = $('Button10');
        var title = '<?php echo addslashes($_lang['empty_recycle_bin_empty']); ?>';
        if (a) {
            if(!a.setAttribute) a.title = title;
        else a.setAttribute('title',title);
        a.innerHTML = '<?php echo $_style['empty_recycle_bin_empty']; ?>';
        a.className = 'treeButtonDisabled';
        a.onclick = '';
    }
    }

</script>

<!--[if lt IE 7]>
    <style type="text/css">
      body { behavior: url(media/script/forIE/htcmime.php?file=csshover.htc) }
      img { behavior: url(media/script/forIE/htcmime.php?file=pngbehavior.htc); }
    </style>
<![endif]-->


</head>
<body onClick="hideMenu(1);" class="<?php echo $modx_textdir ? ' rtl':''?>">

<?php
    // invoke OnTreePrerender event
    $evtOut = $modx->invokeEvent('OnManagerTreeInit',$_REQUEST);
    if (is_array($evtOut))
        echo implode("\n", $evtOut);
?>

<div class="treeframebody">
<div id="treeSplitter"></div>

<table id="treeMenu" width="100%"  border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td>
        <table cellpadding="0" cellspacing="0" border="0">
            <tr>
            <td><a href="#" class="treeButton" id="Button1" onClick="expandTree();" title="<?php echo $_lang['expand_tree']; ?>"><?php echo $_style['expand_tree']; ?></a></td>
            <td><a href="#" class="treeButton" id="Button2" onClick="collapseTree();" title="<?php echo $_lang['collapse_tree']; ?>"><?php echo $_style['collapse_tree']; ?></a></td>
            <?php if ($modx->hasPermission('new_document')) { ?>
                <td><a href="#" class="treeButton" id="Button3a" onClick="top.main.document.location.href='index.php?a=4';" title="<?php echo $_lang['add_resource']; ?>"><?php echo $_style['add_doc_tree']; ?></a></td>
                <td><a href="#" class="treeButton" id="Button3c" onClick="top.main.document.location.href='index.php?a=72';" title="<?php echo $_lang['add_weblink']; ?>"><?php echo $_style['add_weblink_tree']; ?></a></td>
            <?php } ?>
            <td><a href="#" class="treeButton" id="Button4" onClick="top.mainMenu.reloadtree();" title="<?php echo $_lang['refresh_tree']; ?>"><?php echo $_style['refresh_tree']; ?></a></td>
            <td><a href="#" class="treeButton" id="Button5" onClick="showSorter();" title="<?php echo $_lang['sort_tree']; ?>"><?php echo $_style['sort_tree']; ?></a></td>
            <?php if ($modx->hasPermission('empty_trash')) { ?>
                <td><a href="#" id="Button10" class="treeButtonDisabled" title="<?php echo $_lang['empty_recycle_bin_empty'] ; ?>"><?php echo $_style['empty_recycle_bin_empty'] ; ?></a></td>
            <?php } ?>
            </tr>
        </table>
    </td>
    <td align="right">
        <table cellpadding="0" cellspacing="0" border="0">
            <tr>
            <td><a href="#" class="treeButton" id="Button6" onClick="top.mainMenu.hideTreeFrame();" title="<?php echo $_lang['hide_tree']; ?>"><?php echo $_style['hide_tree']; ?></a></td>
            </tr>
        </table>
    </td>
  </tr>
</table>

<div id="floater">
<?php
if(isset($_REQUEST['tree_sortby'])) {
    $_SESSION['tree_sortby'] = $_REQUEST['tree_sortby'];
}

if(isset($_REQUEST['tree_sortdir'])) {
    $_SESSION['tree_sortdir'] = $_REQUEST['tree_sortdir'];
}
?>
<form name="sortFrm" id="sortFrm" action="menu.php">
<table width="100%"  border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td style="padding-left: 10px;padding-top: 1px;" colspan="2">
        <select name="sortby">
            <option value="isfolder" <?php echo $_SESSION['tree_sortby']=='isfolder' ? "selected='selected'" : "" ?>><?php echo $_lang['folder']; ?></option>
            <option value="pagetitle" <?php echo $_SESSION['tree_sortby']=='pagetitle' ? "selected='selected'" : "" ?>><?php echo $_lang['pagetitle']; ?></option>
            <option value="id" <?php echo $_SESSION['tree_sortby']=='id' ? "selected='selected'" : "" ?>><?php echo $_lang['id']; ?></option>
            <option value="menuindex" <?php echo $_SESSION['tree_sortby']=='menuindex' ? "selected='selected'" : "" ?>><?php echo $_lang['resource_opt_menu_index'] ?></option>
            <option value="createdon" <?php echo $_SESSION['tree_sortby']=='createdon' ? "selected='selected'" : "" ?>><?php echo $_lang['createdon']; ?></option>
            <option value="editedon" <?php echo $_SESSION['tree_sortby']=='editedon' ? "selected='selected'" : "" ?>><?php echo $_lang['editedon']; ?></option>
        </select>
    </td>
  </tr>
  <tr>
    <td width="99%" style="padding-left: 10px;padding-top: 1px;">
        <select name="sortdir">
            <option value="DESC" <?php echo $_SESSION['tree_sortdir']=='DESC' ? "selected='selected'" : "" ?>><?php echo $_lang['sort_desc']; ?></option>
            <option value="ASC" <?php echo $_SESSION['tree_sortdir']=='ASC' ? "selected='selected'" : "" ?>><?php echo $_lang['sort_asc']; ?></option>
        </select>
        <input type='hidden' name='dt' value='<?php echo $_REQUEST['dt']; ?>' />
    </td>
    <td width="1%"><a href="#" class="treeButton" id="button7" style="text-align:right" onClick="updateTree();showSorter();" title="<?php echo $_lang['sort_tree']; ?>"><?php echo $_lang['sort_tree']; ?></a></td>
  </tr>
</table>
</form>
</div>

<div id="treeHolder">
<?php
    // invoke OnTreeRender event
    $evtOut = $modx->invokeEvent('OnManagerTreePrerender', $_REQUEST);
    if (is_array($evtOut))
        echo implode("\n", $evtOut);
?>
    <div><?php echo $_style['tree_showtree']; ?>&nbsp;<span class="rootNode" onClick="treeAction(0, '<?php echo addslashes($site_name); ?>');"><b><?php echo $site_name; ?></b></span><div id="treeRoot"></div></div>
<?php
    // invoke OnTreeRender event
    $evtOut = $modx->invokeEvent('OnManagerTreeRender', $_REQUEST);
    if (is_array($evtOut))
        echo implode("\n", $evtOut);
?>
</div>

<script type="text/javascript">
// Set 'treeNodeSelected' class on document node when editing via Context Menu
function setActiveFromContextMenu( doc_id ){
    $$('.treeNodeSelected').removeClass('treeNodeSelected');
    $$('#node'+doc_id+' span')[0].className='treeNodeSelected';
}

// Context menu stuff
function menuHandler(action) {
    switch (action) {
        case 1 : // view
            setActiveFromContextMenu( itemToChange );
            top.main.document.location.href="index.php?a=3&id=" + itemToChange;
            break
        case 2 : // edit
            setActiveFromContextMenu( itemToChange );
            top.main.document.location.href="index.php?a=27&id=" + itemToChange;
            break
        case 3 : // new Resource
            top.main.document.location.href="index.php?a=4&pid=" + itemToChange;
            break
        case 4 : // delete
            if(selectedObjectDeleted==0) {
                if(confirm("'" + selectedObjectName + "'\n\n<?php echo $_lang['confirm_delete_resource']; ?>")==true) {
                    top.main.document.location.href="index.php?a=6&id=" + itemToChange;
                }
            } else {
                alert("'" + selectedObjectName + "' <?php echo $_lang['already_deleted']; ?>");
            }
            break
        case 5 : // move
            top.main.document.location.href="index.php?a=51&id=" + itemToChange;
            break
        case 6 : // new Weblink
            top.main.document.location.href="index.php?a=72&pid=" + itemToChange;
            break
        case 7 : // duplicate
            if(confirm("<?php echo $_lang['confirm_resource_duplicate'] ?>")==true) {
                   top.main.document.location.href="index.php?a=94&id=" + itemToChange;
               }
            break
        case 8 : // undelete
            if(selectedObjectDeleted==0) {
                alert("'" + selectedObjectName + "' <?php echo $_lang['not_deleted']; ?>");
            } else {
                if(confirm("'" + selectedObjectName + "' <?php echo $_lang['confirm_undelete']; ?>")==true) {
                    top.main.document.location.href="index.php?a=63&id=" + itemToChange;
                }
            }
            break
        case 9 : // publish
            if(confirm("'" + selectedObjectName + "' <?php echo $_lang['confirm_publish']; ?>")==true) {
                top.main.document.location.href="index.php?a=61&id=" + itemToChange;
            }
            break
        case 10 : // unpublish
            if (itemToChange != <?php echo $modx->config['site_start']?>) {
                if(confirm("'" + selectedObjectName + "' <?php echo $_lang['confirm_unpublish']; ?>")==true) {
                    top.main.document.location.href="index.php?a=62&id=" + itemToChange;
                }
            } else {
                alert('Document is linked to site_start variable and cannot be unpublished!');
            }
            break
        case 12 : // preview	
            window.open(selectedObjectUrl,'previeWin'); //re-use 'new' window
            break

        default :
            alert('Unknown operation command.');
    }
}

</script>

<!-- Contextual Menu Popup Code -->
<div id="mx_contextmenu" onselectstart="return false;">
    <div id="nameHolder">&nbsp;</div>
    <?php
    constructLink(3, $_style["icons_new_document"], $_lang["create_resource_here"], $modx->hasPermission('new_document')); // new Resource
    constructLink(2, $_style["icons_save"], $_lang["edit_resource"], $modx->hasPermission('edit_document')); // edit
    constructLink(5, $_style["icons_move_document"] , $_lang["move_resource"], $modx->hasPermission('save_document')); // move
    constructLink(7, $_style["icons_resource_duplicate"], $_lang["resource_duplicate"], $modx->hasPermission('new_document')); // duplicate
    ?>
    <div class="seperator"></div>
    <?php
    constructLink(9, $_style["icons_publish_document"], $_lang["publish_resource"], $modx->hasPermission('publish_document')); // publish
    constructLink(10, $_style["icons_unpublish_resource"], $_lang["unpublish_resource"], $modx->hasPermission('publish_document')); // unpublish
    constructLink(4, $_style["icons_delete"], $_lang["delete_resource"], $modx->hasPermission('delete_document')); // delete
    constructLink(8, $_style["icons_undelete_resource"], $_lang["undelete_resource"], $modx->hasPermission('delete_document')); // undelete
    ?>
    <div class="seperator"></div>
    <?php
    constructLink(6, $_style["icons_weblink"], $_lang["create_weblink_here"], $modx->hasPermission('new_document')); // new Weblink
    ?>
    <div class="seperator"></div>
    <?php
    constructLink(1, $_style["icons_resource_overview"], $_lang["resource_overview"], $modx->hasPermission('view_document')); // view
    constructLink(12, $_style["icons_preview_resource"], $_lang["preview_resource"], 1); // preview
    ?>
</div>
</div>

</body>
</html>