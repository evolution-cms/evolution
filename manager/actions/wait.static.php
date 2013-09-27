<?php
if(IN_MANAGER_MODE!="true") die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
/*********************/
$sd=isset($_REQUEST['dir'])?'&dir='.$_REQUEST['dir']:'&dir=DESC';
$sb=isset($_REQUEST['sort'])?'&sort='.$_REQUEST['sort']:'&sort=createdon';
$pg=isset($_REQUEST['page'])?'&page='.(int)$_REQUEST['page']:'';
$add_path=$sd.$sb.$pg;
/**********************/
?>

<h1><?php echo $_lang['cleaningup']; ?></h1>

<div class="section">
<div class="sectionBody">
<p><?php echo $_lang['actioncomplete']; ?></p>
<script type="text/javascript">
function goHome() {
<?php if($_REQUEST['r']==10) {?>
	top.mainMenu.startrefresh(10);
<?php } elseif($_REQUEST['dv']==1 && $_REQUEST['id']!='') { ?>
//	document.location.href="index.php?a=3&id=<?php echo $_REQUEST['id']; ?>";
//возвращаем куда нужно с учетом сортировки
	document.location.href="index.php?a=3&id=<?php echo $_REQUEST['id'].$add_path; ?>";
<?php } else { ?>
	document.location.href="index.php?a=2";
<?php } ?>
}
x=window.setTimeout('goHome()',1000);
</script>
</div>
</div>
