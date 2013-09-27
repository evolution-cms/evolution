<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
if(!$modx->hasPermission('export_static'))
{
	$e->setError(3);
	$e->dumpError();
}

// figure out the base of the server, so we know where to get the documents in order to export them
?>

<h1><?php echo $_lang['export_site_html']; ?></h1>

<div id="actions">
  <ul class="actionButtons">
      <li id="Button5"><a href="#" onclick="documentDirty=false;document.location.href='index.php?a=2';"><img alt="icons_cancel" src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']?></a></li>
  </ul>
</div>

<script type="text/javascript" src="media/script/tabpane.js"></script>
<script type="text/javascript">
tpExport = new WebFXTabPane(document.getElementById("exportPane"));
</script>

<div class="sectionBody">
<div class="tab-pane" id="exportPane">
<div class="tab-page" id="tabMain">
<h2 class="tab"><?php echo $_lang['export_site']?></h2>
<script type="text/javascript">tpExport.addTabPage( document.getElementById( "tabMain" ) );</script>

<?php

if(isset($_POST['export']))
{
	$rs = include_once($modx->config['base_path'] . 'manager/processors/export_site.processor.php');
	echo $rs;
}
else
{
?>

<form action="index.php" method="post" name="exportFrm">
<input type="hidden" name="export" value="export" />
<input type="hidden" name="a" value="83" />
<style type="text/css">
table.settings {width:100%;}
table.settings td.head {white-space:nowrap;vertical-align:top;padding-right:20px;font-weight:bold;}
</style>
<table class="settings" cellspacing="0" cellpadding="2">
  <tr>
    <td class="head"><?php echo $_lang['export_site_cacheable']; ?></td>
    <td><label><input type="radio" name="includenoncache" value="1" checked /><?php echo $_lang['yes'];?></label>
		<label><input type="radio" name="includenoncache" value="0" /><?php echo $_lang['no'];?></label></td>
  </tr>
  <tr>
    <td class="head"><?php echo $_lang['a83_ignore_ids_title']; ?></td>
    <td><input type="text" name="ignore_ids" value="<?php echo $ignore_ids;?>" style="width:300px;" /></td>
  </tr>
  <tr>
    <td class="head"><?php echo $_lang['export_site.static.php4']; ?></td>
    <td><input type="text" name="repl_before" value="<?php echo $repl_before; ?>" style="width:300px;" /></td>
  </tr>
  <tr>
    <td class="head"><?php echo $_lang['export_site.static.php5']; ?></td>
    <td><input type="text" name="repl_after" value="<?php echo $repl_after; ?>" style="width:300px;" /></td>
  </tr>
  <tr>
    <td class="head"><?php echo $_lang['export_site_maxtime']; ?></td>
    <td><input type="text" name="maxtime" value="60" />
		<br />
		<?php echo $_lang['export_site_maxtime_message']; ?>
	</td>
  </tr>
</table>

<ul class="actionButtons">
	<li><a href="#" class="default" onclick="document.exportFrm.submit();"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang["export_site_start"]; ?></a></li>
</ul>
</form>

<?php
}
?>


</div>
<div class="tab-page" id="tabHelp">
<h2 class="tab"><?php echo $_lang['help']?></h2>
<script type="text/javascript">tpExport.addTabPage( document.getElementById( "tabHelp" ) );</script>
<?php
	echo '<p>'.$_lang['export_site_message'].'</p>';
?>
</div>
</div>
</div>
