<style>
  #tabDoc {
    overflow: hidden;
  }

  #tabDoc::before {
    position: absolute;
    content: "";
    right: 0;
    top: 0;
    bottom: 0;
    width: 30px;
    background: -moz-linear-gradient(left, rgba(255,255,255,0) 0%, rgba(255,255,255,1) 90%, rgba(255,255,255,1) 100%);
    background: -webkit-linear-gradient(left, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 90%,rgba(255,255,255,1) 100%);
    background: linear-gradient(to right, rgba(255,255,255,0) 0%,rgba(255,255,255,1) 90%,rgba(255,255,255,1) 100%);
  }

  #treePane .tab-page ul {
    margin: 5px 0;
    padding: 0;
  }

  #treePane .tab-page ul li {
    list-style: none;
  }

  #treePane .tab-page ul li li {
    list-style: none;
    padding-left: 5px;
    line-height: 1.6;
  }

  #treePane .tab-page ul li a {
    text-decoration: none;
  }

  #treePane .tab-page ul li a:hover {
    text-decoration: none;
  }

  #treePane .tab {
    padding-left: 7px;
    padding-right: 7px;
  }

  #treePane .tab > span > .fa {
    margin: 0;
  }

  #treePane .tab.selected {
    padding-bottom: 6px;
  }

  #treePane .tab-row .tab span {
    font-size: 14px;
  }

  /* Clearfix to avoid .tab-row height() = 0 */
  #treePane .tab-row:after {
    content: " ";
    clear: both;
    display: block;
    background-color: #cfd2d6;
  }

  #treePane .ext-ico {
    text-decoration:none!important;
    color: #5CB85C !important;
  }

  #treePane ul > li > strong > a.catname
  {
    color: #444;
  }

  #treePane .fade {
    opacity: 0;
    -webkit-transition: opacity .15s linear;
    -o-transition: opacity .15s linear;
    transition: opacity .15s linear;
  }

  #treePane .fade.in {
    opacity: 1;
  }

  #treePane .collapse {
    display: none;
  }

  #treePane .collapse.in {
    display: block;
  }

  #treePane tr.collapse.in {
    display: table-row;
  }

  #treePane tbody.collapse.in {
    display: table-row-group;
  }

  #treePane .collapsing {
    position: relative;
    height: 0;
    overflow: hidden;
    -webkit-transition-timing-function: ease;
    -o-transition-timing-function: ease;
    transition-timing-function: ease;
    -webkit-transition-duration: .35s;
    -o-transition-duration: .35s;
    transition-duration: .35s;
    -webkit-transition-property: height;
    -o-transition-property: height;
    transition-property: height;
  }

  #treePane.no-transition .collapsing {
    -webkit-transition: none;
    -o-transition: none;
    transition: none;
  }

  #treePane .panel-heading {
    border-top: 1px solid #ddd;
    border-bottom: 1px solid #ddd;
    background-color: #F2F2F2;
    margin-top: -1px;
  }

  #treePane .panel {
    padding-top: 1px;
  }

  #treePane .panel-heading:hover {
    background-color: #eaeaea;
  }

  #treePane .panel-title a:hover {
    background-color: transparent;
  }

  .dark #treePane .panel-heading,
  .darkness #treePane .panel-heading {
    border-top: 1px solid #3e4144;
    border-bottom: 1px solid #3e4144;
    background-color: #2f323a;
  }

  .dark #treePane .panel-heading:hover,
  .darkness #treePane .panel-heading:hover {
    background-color: #363942;
  }

  #treePane .panel-collapse {
  }

  #treePane .panel-title a{
    display: block;
    padding: 4px 0 4px 1.9rem;
    color: #657587;
    font-weight: bold;
  }

  #treePane .panel-title a:hover {
    text-decoration: none;
  }

  #treePane .panel-title a:focus {
    text-decoration: none;
  }

  #treePane .panel-title > a::before {
    content: "\f107"; /* fa-angle-down */
    font-family: "FontAwesome";
    font-size: 14px;
    margin-left:-17px;
    width: 10px;
    display: inline-block;
  }
  #treePane .panel-title > a.collapsed::before {
    content: "\f105"; /* fa-angle-right */
    padding:0 2px;
  }
  #treePane .panel-title > a[aria-expanded="true"] {
    color: #657587;
  }

  .filterElements-form--eit {
    width: 200px;
    width: calc(100% - 70px);
  }

  .actionButtons--eit {
    position: absolute;
    top: 25px;
    right: 10px;
  }

  .actionButtons--eit li {
    margin-right: 5px;
    padding-left: 0 !important;
  }

  .actionButtons--eit a {
    padding: 5px 8px;
    font-size: 14px;
  }

  #tabTemp li.eltree:before {content: "\f1ea";}
  #tabCH   li.eltree:before {content: "\f009";}
  #tabSN   li.eltree:before {content: "\f121";}
  #tabTV   li.eltree:before {content: "\f022";}
  #tabPL   li.eltree:before {content: "\f1e6";}
  #tabMD   li.eltree:before {content: "\f1b2";}

  .tab-page { margin-bottom:0; }

  /* ElementsInTree main styles */

  @media (max-width: 840px) {

    .ElementsInTree #tree {
      max-width: 345px;
    }

  }

  .ElementsInTree #tree a {
    color: #404040;
  }
  
  .ElementsInTree #tree .disabledPlugin, 
  .ElementsInTree #tree .disabledPlugin a {
    color: #B68282;
  }

  .ElementsInTree #tree .treeframebody {
    background-color: #fafafa !important;
    border-right: 1px solid #cfd2d6
  }

  .ElementsInTree #tree #treeHolder {
    height: 100%;
    max-height: 100%;
    overflow: hidden;
    padding: 0
  }

  .ElementsInTree #tree .actionButtons--eit {
    top: 1.95rem;
    right: 0.8rem;
  }

  .ElementsInTree #tree .actionButtons--eit li {
    float: left;
    margin: 0 0 0 5px !important;
  }

  .ElementsInTree #tree .actionButtons--eit li a {
    padding: 0.5rem;
    color: #888;
  }

  .ElementsInTree #tree .actionButtons--eit li a:hover {
    color: #404040;
  }

  .dark.ElementsInTree #tree .actionButtons--eit li a:hover,
  .darkness.ElementsInTree #tree .actionButtons--eit li a:hover {
    color: #bfbfbf;
  }

  .ElementsInTree #tree .tab-page {
    padding: 0 !important;
    background-color: #fafafa;
    border-width: 1px 0;
    box-shadow: none;
    min-height: 55px;
  }

  .ElementsInTree #tree .tab-page .panel-group .panel,
  .ElementsInTree #tree #tabDoc.tab-page>div {
    overflow: auto;
  }
  
  .ElementsInTree #tree #tabDoc.tab-page>div {
    max-height: calc(100vh - 10rem) !important;
  }

  .ElementsInTree #tree.has-treemenu-intab #tabDoc.tab-page>div {
    max-height: calc(100vh - 10.3rem) !important;
  }

  .ElementsInTree #tree .tab-page .panel-group .panel {
    max-height: calc(100vh - 9rem) !important;
  }

  .ElementsInTree #tree.has-treemenu-intab .tab-page .panel-group .panel {
    max-height: calc(100vh - 7.125rem) !important;
  }

  .ElementsInTree #tree .tab-page .panel-group {
    overflow: visible !important;
    max-height: none !important;
    box-sizing: border-box !important;
    padding-top: 0;
    border-top: 1px solid #ddd;
  }

  .ElementsInTree #tree .tab-row {
    padding: 0;
    display: table;
    width: 100%;
    table-layout: fixed;
    background-color: #f2f2f2;
  }

  .ElementsInTree #tree.has-treemenu-intab .tab-row {
    background-color: #DFDFDF;
  }

  .ElementsInTree #tree .tab-row .tab {
    height: 1.875rem;
    line-height: 1.75rem;
    background-color: transparent;
    border-color: #cfd2d6;
    border-width: 0 1px 0 0;
    display: table-cell;
    text-align: center;
    vertical-align: middle;
    width: 100%;
    z-index: 2;
  }

  .ElementsInTree #tree .tab-row .tab:last-child {
    border-width: 0;
  }

  .ElementsInTree #tree .tab-row .tab:hover {
    background-color: rgba(255, 255, 255, 0.5);
  }

  .ElementsInTree #tree .tab-row .tab.selected {
    padding-bottom: 0;
    background-color: #fafafa;
  }

  .ElementsInTree #tree .eltree {
    line-height: 1.5;
    padding-top: 1px;
    padding-bottom: 1px;
    padding-left: 1.7rem;
    background-color: rgba(33, 150, 243, 0);
    -webkit-transition-duration: 0.15s;
    transition-duration: 0.15s;
  }

  .ElementsInTree #tree .eltree:before {
    font-family: "FontAwesome";
    font-size: 14px;
    padding:0 5px 0 0;
    margin-right:2px;
    color: #657587;
  }

  .ElementsInTree #tree .eltree:hover, 
  .ElementsInTree #tree .eltree.current {
    background-color: rgba(33, 150, 243, 0.1);
  }

  .dark.ElementsInTree #tree .eltree:hover, 
  .dark.ElementsInTree #tree .eltree.current,
  .darkness.ElementsInTree #tree .eltree:hover, 
  .darkness.ElementsInTree #tree .eltree.current {
    background-color: rgba(255, 255, 255, 0.15);
  }

  .ElementsInTree #tree .eltree img {
    width: 1em;
    height: 1em;
    margin-top: -3px;
  }

  .ElementsInTree #tree #tabDoc {
    padding-top: 0.5rem !important;
    padding-bottom: 0.8rem !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
  }

  .ElementsInTree #tree #tabDoc::before {
    display: none
  }

  .ElementsInTree #treeRoot a {
    padding-left: 2em;
  }

  .ElementsInTree #treeMenu.is-intab {
    height: 1.875rem;
    background-color: transparent !important;
  }

  .ElementsInTree.treeframebody {
    -webkit-box-shadow: none;
    box-shadow: none
  }

  .ElementsInTree .filterElements-form--eit {
    width: 200px !important;
    width: calc(100% - 85px) !important;
  }

  .ElementsInTree .filterElements-form--eit .form-control {
    padding: 0.25rem;
    padding-left: 0.5rem;
    height: 2rem;
    font-size: 0.8rem;
    border-width: 0;
    background-color: #fafafa;
  }

  .dark.ElementsInTree #tree .treeframebody,
  .darkness.ElementsInTree #tree .treeframebody {
    background-color: #202329 !important;
    color: #828282;
    border-color: #2a2d33
  }

  .dark.ElementsInTree #tree .tab-row .tab,
  .darkness.ElementsInTree #tree .tab-row .tab {
    color: #7b7b7b;
    border-color: #2a2d33;
    background-color: #1a1c21;
  }

  .dark.ElementsInTree #tree .tab-row .tab:hover,
  .darkness.ElementsInTree #tree .tab-row .tab:hover {
    background-color: #2d3033;
  }

  .dark.ElementsInTree #tree .tab-row .tab.selected,
  .darkness.ElementsInTree #tree .tab-row .tab.selected {
    background-color: #202329;
    color: #bfbfbf;
  }

  .dark.ElementsInTree #tree .tab-row .tab span,
  .darkness.ElementsInTree #tree .tab-row .tab span {
    background-color: transparent;
  }

  .dark.ElementsInTree #tree .tab-page,
  .darkness.ElementsInTree #tree .tab-page {
    background-color: #202329;
  }

  .dark.ElementsInTree #tree .tab-page .panel-group,
  .darkness.ElementsInTree #tree .tab-page .panel-group {
    border-color: #3e4144;
  }

  .dark.ElementsInTree #tree .form-control,
  .darkness.ElementsInTree #tree .form-control {
    background-color: transparent;
    color: #c7c7c7;
  }

  .dark.ElementsInTree #tree a,
  .darkness.ElementsInTree #tree a {
    color: #b7b7b7;
  }

  .dark.ElementsInTree #tree .disabledPlugin, 
  .dark.ElementsInTree #tree .disabledPlugin a,
  .darkness.ElementsInTree #tree .disabledPlugin, 
  .darkness.ElementsInTree #tree .disabledPlugin a {
    color: #B68282;
  }

  .dark.ElementsInTree #tree a:hover,
  .darkness.ElementsInTree #tree a:hover {
    color: #dbdbdb;
  }

  .dark.ElementsInTree #tree .disabledPlugin a,
  .darkness.ElementsInTree #tree .disabledPlugin a {
    color: #b68282;
  }

  .dark.ElementsInTree #treeMenu,
  .darkness.ElementsInTree #treeMenu {
    background-color: rgba(0, 0, 0, 0.2) !important;
  }

  .dark.ElementsInTree #treeMenu.is-intab,
  .darkness.ElementsInTree #treeMenu.is-intab {
    background-color: transparent !important;
  }
  
  #treePane .panel-title {
    font-size: 0.8125rem;
  }

  #treePane .panel-title a {
    padding: 4px 0 6px 0.6rem !important;
  }

  #treePane .panel-title > a:before {
    display: none !important;
  }

  #treePane .tab-page ul {
    margin: 7px 0 !important;
  }

  .ElementsInTree #tree .eltree {
    padding-left: 1rem !important;
    font-size: 13px;
  }

  .dark #treePane .panel-heading,
  .darkness #treePane .panel-heading {
    border-top-color: #3e4144 !important;
    border-bottom-color: transparent !important;
  }


  #treePane .panel-heading:first-child,
  .dark #treePane .panel-heading:first-child,
  .darkness #treePane .panel-heading:first-child {
    border-top-color: transparent !important;
  }


  /* Unify frames */

  body,
  div.treeframebody {
    background-color: #f2f2f2 !important;
  }

  div.treeframebody {
    background-color: transparent !important;
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
  }

  [+treeButtonsInTab_css+]

</style>

<div class="tab-pane no-transition" id="treePane" style="border:0;">
  <script type="text/javascript" src="media/script/tabpane.js"></script>
  <script src="media/script/bootstrap/js/bootstrap.min.js"></script>
  <script type="text/javascript" src="media/script/jquery.quicksearch.js"></script>
  <script type="text/javascript" src="media/script/jquery.nucontextmenu.js"></script>
  <script>
    function initQuicksearch(inputId, listId) {
      jQuery("#"+inputId).quicksearch("#"+listId+" ul li", {
        selector: ".elementname",
        "show": function () { jQuery(this).removeClass("hide"); },
        "hide": function () { jQuery(this).addClass("hide"); },
        "bind":"keyup",
        "onAfter": function() {
          jQuery("#"+listId).find(".panel-collapse").each( function() {
            var parentLI = jQuery(this);
            var totalLI  = jQuery(this).find("li").length;
            var hiddenLI = jQuery(this).find("li.hide").length;
            if (hiddenLI == totalLI) { parentLI.prev(".panel-heading").addClass("hide"); }
            else { parentLI.prev(".panel-heading").removeClass("hide"); }
          });
        }
      });
      jQuery(".filterElements-form").keydown(function (e) {
        if (e.keyCode == 13) {
          e.preventDefault();
        }
      });
    }

    var storageKey = "MODX_elementsInTreeParams";

    // localStorage reset :
    // localStorage.removeItem(storageKey);

    // Prepare remember collapsed categories function
    var storage = localStorage.getItem(storageKey);
    var elementsInTreeParams = {};
    var searchFieldCache = {};

    try {
      if(storage != null) {
        try {
          elementsInTreeParams = JSON.parse( storage );
        } catch(err) {
          console.log(err);
          elementsInTreeParams = { "cat_collapsed": {}, "scroll_pos": {} };
        }
      } else {
        elementsInTreeParams = { "cat_collapsed": {}, "scroll_pos": {} };
      }

      // Remember collapsed categories functions
      function setRememberCollapsedCategories(obj=null) {
        obj = obj == null ? elementsInTreeParams.cat_collapsed : obj;
        for (var type in obj) {
          if (!elementsInTreeParams.cat_collapsed.hasOwnProperty(type)) continue;
          for (var category in elementsInTreeParams.cat_collapsed[type]) {
            if (!elementsInTreeParams.cat_collapsed[type].hasOwnProperty(category)) continue;
            state = elementsInTreeParams.cat_collapsed[type][category];
            if(state == null) continue;
            var collapseItem = jQuery("#collapse" + type + category);
            var toggleItem = jQuery("#toggle" + type + category);
            if(state == 0) {
              // Collapsed
              collapseItem.collapse("hide");
              toggleItem.addClass("collapsed");
            } else {
              // Open
              collapseItem.collapse("show");
              toggleItem.removeClass("collapsed");
            } 
          }
        }
        // Avoid first category collapse-flicker on reload
        setTimeout(function() {
          jQuery("#treePane").removeClass("no-transition");
        }, 50);
      }

      function setLastCollapsedCategory(type, id, state) {
        state = state != 1 ? 1 : 0;
        if(typeof elementsInTreeParams.cat_collapsed[type] == "undefined") elementsInTreeParams.cat_collapsed[type] = {};
        elementsInTreeParams.cat_collapsed[type][id] = state;
      }
      function writeElementsInTreeParamsToStorage() {
        var jsonString = JSON.stringify(elementsInTreeParams);
        localStorage.setItem(storageKey, jsonString );
      }

      // Issue #20 - Keep HTTP_REFERER
      //        function reloadElementsInTree() {
      //            // http://stackoverflow.com/a/7917528/2354531
      //            var url = "index.php?a=1&f=tree";
      //            var a = document.createElement("a");
      //            if (a.click)
      //            {
      //                // HTML5 browsers and IE support click() on <a>, early FF does not.
      //                a.setAttribute("href", url);
      //                a.style.display = "none";
      //                document.body.appendChild(a);
      //                a.click();
      //            } else {
      //                // Early FF can, however, use this usual method where IE cannot with secure links.
      //                window.location = url;
      //            }
      //        }

      function reloadElementsInTree() {
        jQuery.ajax({
          url: 'index.php?a=1&f=tree',
          method: 'get'
        }).done(function(data) {
          savePositions();
          var div = document.createElement('div');
          div.innerHTML = data;
          var tabs = div.getElementsByClassName('tab-page');
          var el, i, p, r;
          for (i = 0; i < tabs.length; i++) {
            if (tabs[i].id !== 'tabDoc') {
              el = tabs[i].getElementsByClassName('panel-group')[0];
              if (el) {
                el.style.display = 'none';
                el.classList.add('clone');
                p = document.getElementById(tabs[i].id);
                if (p) {
                  r = p.getElementsByClassName('panel-group')[0];
                  if (r) {
                    p.insertBefore(el, r);
                  }
                }
              }
            }
          }
          setRememberCollapsedCategories();
          for (i = 0; i < tabs.length; i++) {
            if (tabs[i].id !== 'tabDoc') {
              el = document.getElementById(tabs[i].id);
              if (el) {
                el = el.getElementsByClassName('panel-group')[1];
                if (el) {
                  el.parentNode.removeChild(el);
                }
              }
              el = document.getElementById(tabs[i].id);
              if (el) {
                el = el.getElementsByClassName('panel-group')[0];
                if (el) {
                  el.classList.remove('clone');
                  el.style.display = 'block';
                }
              }
            }
          }
          loadPositions();
          for (i = 0; i < tabIds.length; i++) {
            initQuicksearch(tabIds[i] + '_search', tabIds[i]);
          }
          var at = document.querySelectorAll('#tree .accordion-toggle');
          for (i = 0; i < at.length; i++) {
            at[i].onclick = function(e) {
              e.preventDefault();
              var thisItemCollapsed = $(this).hasClass('collapsed');
              if (e.shiftKey) {
                var toggleItems = $(this).closest('.panel-group').find('> .panel .accordion-toggle');
                var collapseItems = $(this).closest('.panel-group').find('> .panel > .panel-collapse');
                if (thisItemCollapsed) {
                  toggleItems.removeClass('collapsed');
                  collapseItems.collapse('show');
                } else {
                  toggleItems.addClass('collapsed');
                  collapseItems.collapse('hide');
                }
                toggleItems.each(function() {
                  var state = $(this).hasClass('collapsed') ? 1 : 0;
                  setLastCollapsedCategory($(this).data('cattype'), $(this).data('catid'), state);
                });
                writeElementsInTreeParamsToStorage();
              } else {
                $(this).toggleClass('collapsed');
                $($(this).attr('href')).collapse('toggle');
                var state = thisItemCollapsed ? 0 : 1;
                setLastCollapsedCategory($(this).data('cattype'), $(this).data('catid'), state);
                writeElementsInTreeParamsToStorage();
              }
            }
          }
        }).fail(function(xhr, ajaxOptions, thrownError) {
          alert(thrownError + '\r\n' + xhr.statusText + '\r\n' + xhr.responseText);
        })
      }

      /////////////////////////////////////////////////////////////
      // Prepare "remember scroll-position" functions
      var tabIds = ["tree_site_templates","tree_site_tmplvars","tree_site_htmlsnippets","tree_site_snippets","tree_site_plugins","tree_site_modules"];

      function getScrollXY(tab) {
        var t = document.getElementById(tab);
        return [t.scrollLeft, t.scrollTop];
      }

      function setScrollXY(tab, pos) {
        document.getElementById(tab).scrollLeft = pos[0];
        document.getElementById(tab).scrollTop = pos[1];
      }

      // Window load
      function loadPositions() {
        for (var i = 0; i < tabIds.length; i++) {
          var tabId = tabIds[i];
          var tabEl = jQuery("#"+tabId);
          tabEl.css("box-sizing","content-box").css("overflow","auto");
          if(typeof elementsInTreeParams.scroll_pos[tabId] == "undefined" || tabEl.length < 1) continue;
          var tabPage = tabEl.closest(".tab-page");
          if(tabPage.is(":visible")) {
            setScrollXY(tabId, elementsInTreeParams.scroll_pos[tabId]);
          } else {
            tabPage.show();
            setScrollXY(tabId, elementsInTreeParams.scroll_pos[tabId]);
            tabPage.hide(); 
          }
        }
      }

      // Window unload
      function savePositions() {
        if(typeof elementsInTreeParams.scroll_pos == "undefined") { elementsInTreeParams.scroll_pos = {}; }
        for (var i = 0; i < tabIds.length; i++) {
          var tabId = tabIds[i];
          var tabEl = jQuery("#"+tabId);
          if(tabEl.length < 1) continue;
          var tabPage = tabEl.closest(".tab-page");
          if(tabPage.is(":visible")) {
            elementsInTreeParams.scroll_pos[tabId] = getScrollXY(tabId);
          } else {
            tabPage.show(); 
            elementsInTreeParams.scroll_pos[tabId] = getScrollXY(tabId);
            tabPage.hide(); 
          }

        }
        writeElementsInTreeParamsToStorage();
      }

      // Window load & resize
      var winHeight, tabsHeight, buttonsSize, themeMargins, tabHeight;

      function determineHeightValues() {
        winHeight = jQuery(window).height();
        tabsHeight = jQuery(".tab-row:first").height();
        buttonsSize = jQuery(".filterElements-form:first").getSize();
        themeMargins = 60; // All MODxRE2 top/bottom margins
        tabHeight = winHeight - tabsHeight - buttonsSize.height - themeMargins;
      }

      function setTabsHeight() {
        for (var i = 0; i < tabIds.length; i++) {
          var tabId = tabIds[i];
          var tabEl = jQuery("#"+tabId);
          tabEl.css("max-height",tabHeight+"px");
        }
      }

      jQuery(window).on("load", function() {
        determineHeightValues();
        setTabsHeight();
        // Workaround for Firefox, which sometimes does not set scrollTop, 1ms is hopefully enough 
        var initDelay = setTimeout(function(){
          loadPositions();
        }, 1);
      });
      jQuery(window).on("unload", function() {
        savePositions();
      });
      jQuery(window).on("resize", function() {
        determineHeightValues();
        setTabsHeight();
      });

      // Get size of invisible elements - http://stackoverflow.com/a/8839261/2354531
      jQuery.fn.getSize = function() {
        var $wrap = jQuery("<div />").appendTo(jQuery("body"));
        $wrap.css({
          "position":   "absolute !important",
          "visibility": "hidden !important",
          "display":    "block !important"
        });

        $clone = jQuery(this).clone().appendTo($wrap);

        sizes = {
          "width": $clone.width(),
          "height": $clone.height()
        };

        $wrap.remove();

        return sizes;
      };
      /////////////////////////////////////////////////////////////

      jQuery(document).ready(function() {

        jQuery('.filterElements-form').keydown(function(e) {
          if (e.keyCode === 13) e.preventDefault();
        });

        [+treeButtonsInTab_js+]

        // Shift-Mouseclick opens/collapsed all categories
        jQuery('.accordion-toggle').click(function(e) {
          e.preventDefault();
          var thisItemCollapsed = jQuery(this).hasClass('collapsed');
          if (e.shiftKey) {
            // Shift-key pressed
            var toggleItems = jQuery(this).closest('.panel-group').find('> .panel .accordion-toggle');
            var collapseItems = jQuery(this).closest('.panel-group').find('> .panel > .panel-collapse');
            if (thisItemCollapsed) {
              toggleItems.removeClass('collapsed');
              collapseItems.collapse('show');
            } else {
              toggleItems.addClass('collapsed');
              collapseItems.collapse('hide');
            }
            // Save states to localStorage
            toggleItems.each(function() {
              state = jQuery(this).hasClass('collapsed') ? 1 : 0;
              setLastCollapsedCategory(jQuery(this).data('cattype'), jQuery(this).data('catid'), state);
            });
            writeElementsInTreeParamsToStorage();
          } else {
            jQuery(this).toggleClass('collapsed');
            jQuery(jQuery(this).attr('href')).collapse('toggle');
            // Save state to localStorage
            state = thisItemCollapsed ? 0 : 1;
            setLastCollapsedCategory(jQuery(this).data('cattype'), jQuery(this).data('catid'), state);
            writeElementsInTreeParamsToStorage();
          }
        });

        setRememberCollapsedCategories();

      });
    } catch (err) {
      alert('document.ready error: ' + err);
    }
  </script>
  <script type="text/javascript">
    treePane = new WebFXTabPane(document.getElementById( "treePane" ),true);
  </script>
  <div class="tab-page" id="tabDoc" style="padding-left:0; padding-right:0;">
    <h2 class="tab">[+tabTreeTitle+]</h2>
    <script type="text/javascript">treePane.addTabPage( document.getElementById( "tabDoc" ) );</script>
