<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
?>

<div class="sectionHeader"><?php echo $_lang['about_title']; ?></div><div class="sectionBody">
<?php echo $_lang['about_msg']; ?>
</div>

<div class="sectionHeader"><?php echo $_lang['help_title']; ?></div><div class="sectionBody">
<?php echo $_lang['help_msg']; ?>
</div>

<div class="sectionHeader"><?php echo $_lang['credits']; ?></div><div class="sectionBody">
<table width="500"  border="0" cellspacing="0" cellpadding="0">
  <tr height="70">
    <td align="center"><a href="http://www.php.net" target="_blank"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/credits/php.gif" border="0"></a></td>
    <td align="left"><?php echo $_lang['credits_php']; ?></td>
  </tr>
  <tr height="70">
    <td align="center"><a href="http://www.mysql.com" target="_blank"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/credits/mysql.gif" border="0"></a></td>
    <td align="left"><?php echo $_lang['credits_mysql']; ?></td>
  </tr>
  <tr height="70">
    <td align="center"><a href="http://www.destroydrop.com" target="_blank"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/credits/dtree.gif" border="0"></a></td>
    <td align="left"><?php echo $_lang['credits_dTree']; ?></td>
  </tr>
  <tr height="70">
    <td align="center"><a href="http://www.everaldo.com" target="_blank"><img src="media/style/<?php echo $manager_theme ? "$manager_theme/":""; ?>images/credits/penguin.gif" border="0"></a></td>
    <td align="left"><?php echo $_lang['credits_everaldo']; ?></td>
  </tr>
  <tr>
    <td colspan="2">&nbsp;</td>
  </tr>
  <tr>
    <td align="center" valign="top"><?php echo $_lang['credits_shouts_title']; ?></td>
	<td align="left"><?php echo $_lang['credits_shouts_msg']; ?></td>
  </tr>
</table>

</div>

