<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>[+lang.DM_module_title+]</title>
    <link rel="stylesheet" type="text/css" href="media/style[+theme+]/style.css" />
    <script type="text/javascript" src="media/script/tabpane.js"></script>
    <script type="text/javascript" src="[(mgr_jquery_path)]"></script>
    <script type="text/javascript" src="media/script/mootools/mootools.js"></script>
    <script type="text/javascript" src="../assets/modules/docmanager/js/docmanager.js"></script>
    <script type="text/javascript">
      function loadTemplateVars(tplId)
      {
        document.getElementById('tvloading').style.display = 'block';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '[+ajax.endpoint+]', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded;');
        xhr.onload = function() {
          if (this.readyState === 4) {
            var a = [], b, c = /<script[^>]*>([\s\S]*?)<\/script>/gi;
            while ((b = c.exec(this.response))) {
              a.push(b[1]);
            }
            a = a.join('\n');
            if (a) {
              (window.execScript) ? window.execScript(a) : window.setTimeout(a, 0);
            }
            document.getElementById('results').innerHTML = this.response;
            document.getElementById('tvloading').style.display = 'none';
            var DatePickers = document.querySelectorAll('input.DatePicker');
            if (DatePickers) {
              for (var i = 0; i < DatePickers.length; i++) {
                new DatePicker(DatePickers[i], {
                  yearOffset: dpOffset, format: dpformat, dayNames: dpdayNames, monthNames: dpmonthNames, startDay: dpstartDay,
                });
              }
            }
          }
        };
        xhr.send('theme=[+theme+]&tplID=' + tplId);
      }

      function save()
      {
        document.newdocumentparent.submit();
      }

      function setMoveValue(pId, pName)
      {
        if (pId === 0 || checkParentChildRelation(pId, pName)) {
          document.newdocumentparent.new_parent.value = pId;
          document.getElementById('parentName').innerHTML = 'Parent: <strong>' + pId + '</strong> (' + pName + ')';
        }
      }

      function checkParentChildRelation(pId, pName)
      {
        var sp;
        var id = document.newdocumentparent.id.value;
        var tdoc = parent.tree.document;
        var pn = (tdoc.getElementById) ? tdoc.getElementById('node' + pId) : tdoc.all['node' + pId];
        if (!pn) {
          return;
        }
        while (pn.p > 0) {
          pn = (tdoc.getElementById) ? tdoc.getElementById('node' + pn.p) : tdoc.all['node' + pn.p];
          if (pn.id.substr(4) === id) {
            alert('Illegal Parent');
            return;
          }
        }

        return true;
      }
    </script>
    [+onManagerMainFrameHeaderHTMLBlock+]
</head>
<body>
<script>if ( [(manager_theme_mode)] == '4') {document.body.className='darkness';}</script>

<h1>
    <i class="fa fa-file-text"></i>[+lang.DM_module_title+]
</h1>

<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="btn btn-success" href="javascript:;" onclick="window.location.href='index.php?a=106';">
            <i class="fa fa-times-circle"></i><span>[+lang.DM_close+]</span>
        </a>
    </div>
</div>

<div class="tab-pane" id="docManagerPane">
    <script type="text/javascript">
      tpResources = new WebFXTabPane(document.getElementById('docManagerPane'));
    </script>

    <div class="tab-page" id="tabTemplates">
        <h2 class="tab"><i class="fa fa-newspaper-o"></i> [+lang.DM_change_template+]</h2>
        <script type="text/javascript">tpResources.addTabPage(document.getElementById('tabTemplates'));</script>
        <div class="tab-body">
            [+view.templates+]
        </div>
    </div>

    <div class="tab-page" id="tabTemplateVariables">
        <h2 class="tab"><i class="fa fa-list-alt"></i> [+lang.DM_template_variables+]</h2>
        <script type="text/javascript">tpResources.addTabPage(document.getElementById('tabTemplateVariables'));</script>
        <div class="tab-body">
            [+view.templatevars+]
        </div>
    </div>

    <div class="tab-page" id="tabDocPermissions">
        <h2 class="tab"><i class="fa fa-file-text"></i> [+lang.DM_doc_permissions+]</h2>
        <script type="text/javascript">tpResources.addTabPage(document.getElementById('tabDocPermissions'));</script>
        <div class="tab-body">
            [+view.documentgroups+]
        </div>
    </div>

    <div class="tab-page" id="tabOther">
        <h2 class="tab"><i class="fa fa-tasks"></i> [+lang.DM_other+]</h2>
        <script type="text/javascript">tpResources.addTabPage(document.getElementById('tabOther'));</script>
        <div class="tab-body">
            [+view.misc+]
            [+view.changeauthors+]
        </div>
    </div>
</div>

[+view.documents+]
[+view.tab+]
</body>
</html>