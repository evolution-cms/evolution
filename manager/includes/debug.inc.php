<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");

// show debug information
if(isset($enable_debug) && $enable_debug==true) {
	?>
	<script language="javascript">
	//document.onload = removeDebug();
	
	function removeDebug() {
		xyz = window.setTimeout('removeDebugDiv()', 6000);	
	}
	
	function removeDebugDiv() {
		document.getElementById('debug').style.display="none";
	}
	</script>
	<style>
	.debug {
		position:absolute;
		top: 60px;
		right: 40px;
		border: 1px solid #003399;
		padding: 3px;
		background-color: #ffffff;
		filter:progid:DXImageTransform.Microsoft.Shadow(color='#666666', Direction=135, Strength=2);
		z-index:50;
		cursor: pointer;
	}
	.debug TD {
		font-size: 9px;
		font-family: verdana;
	}
	</style>
	<?php
	$mtime = microtime(); $mtime = explode(" ",$mtime); $mtime = $mtime[1] + $mtime[0]; $tend = $mtime; $totaltime = ($tend - $tstart);
	?>
	<div class='debug' id='debug' name='debug' onClick="removeDebugDiv();">
		<table border="0" cellspacing="0" cellpadding="0">
		  <tr>
			<td colspan="2"><b>Debug information</b> (click to hide)</td>
		  </tr>
		  <tr>
			<td width="70">Time taken</td>
			<td width="80"><?php echo printf ("%6.5f s", $totaltime); ?></td>
		  </tr>
		</table>
	</div>
<?php
}

?>