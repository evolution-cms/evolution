<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>[+lang.DM_update_title+]</title>
    <link rel="stylesheet" type="text/css" href="media/style[+theme+]/style.css" />
    <script type="text/javascript" src="media/script/mootools/mootools.js"></script>
    <script type="text/javascript">
      function reset()
      {
        document.getElementById('backform').submit();
      }
    </script>
    <style type="text/css">
        .topdiv {
            border: 0;
            }
        .subdiv {
            border: 0;
            }
        ul, li {
            list-style: none;
            }
    </style>
    <script type="text/javascript">parent.tree.updateTree();</script>
</head>
<body>
<script>if ( [(manager_theme_mode)] == '4') {document.body.className='darkness';}</script>

<h1>[+lang.DM_module_title+]</h1>

<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="btn btn-success" href="javascript:;" onclick="window.location.href='index.php?a=106';"><i class="fa fa-times-circle"></i> [+lang.DM_close+]</a>
        <a id="Button4" class="btn btn-secondary" href="javascript:;" onclick="reset();"><i class="fa fa-times-circle'"></i> [+lang.DM_cancel+]</a>
    </div>
</div>

<div class="tab-page">
    <div class="tab-body">
        <b>[+lang.DM_update_title+]</b>
        <p>[+update.message+]</p>
        <form id="backform" method="post" style="display: none;" action="">
            <input type="submit" name="back" value="[+lang.DM_process_back+]" />
        </form>
    </div>
</div>

</body>
</html>