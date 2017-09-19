<?php
if(IN_MANAGER_MODE != "true") {
	die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the MODX Content Manager instead of accessing this file directly.");
}

// (un)publishing of documents, version 2!
// first, publish document waiting to be published
$ctime = $_SERVER['REQUEST_TIME'];

$where = "pub_date < {$ctime} AND pub_date!=0 AND unpub_date > {$ctime}";
$modx->db->update('published=1', '[+prefix+]site_content', $where);
$num_rows_pub = $modx->db->getAffectedRows();

$where = "unpub_date < {$ctime} AND unpub_date!=0 AND published=1";
$modx->db->update('published=0', '[+prefix+]site_content', $where);
$num_rows_unpub = $modx->db->getAffectedRows();

?>

<h1><?= $_lang['refresh_title'] ?></h1>

<div id="actions">
    <div class="btn-group">
        <a id="Button1" class="btn btn-success" href="index.php?a=26">
            <i class="fa fa-recycle"></i> <span><?php echo $_lang['refresh_site']; ?></span>
        </a>
    </div>
</div>

<div class="tab-page">
	<div class="container container-body">
		<?php if($num_rows_pub)   printf('<p>' . $_lang["refresh_published"]   . '</p>', $num_rows_pub) ?>
		<?php if($num_rows_unpub) printf('<p>' . $_lang["refresh_unpublished"] . '</p>', $num_rows_unpub) ?>
		<?php
		$modx->clearCache('full', true);
		// invoke OnSiteRefresh event
		$modx->invokeEvent("OnSiteRefresh");
		?>
	</div>
</div>
