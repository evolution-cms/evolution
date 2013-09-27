<?php 
if(!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE != 'true') exit();
unset($_SESSION['itemname']); // clear this, because it's only set for logging purposes
?>

<h1><?php echo $_lang['search_criteria']; ?></h1>

<div id="actions">
  <ul class="actionButtons">
      <li id="Button5"><a href="#" onclick="documentDirty=false;document.location.href='index.php?a=2';"><img alt="icons_cancel" src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel']?></a></li>
  </ul>
</div>


<div class="section">
<div class="sectionBody">
<form action="index.php?a=71" method="post" name="searchform" enctype="multipart/form-data">
<table width="100%" border="0">
  <tr>
    <td width="120"><?php echo $_lang['search_criteria_id']; ?></td>
    <td width="20">&nbsp;</td>
    <td width="120"><input name="searchid" type="text" /></td>
	<td><?php echo $_lang['search_criteria_id_msg']; ?></td>
  </tr>
  <tr>
    <td><?php echo $_lang['search_criteria_title']; ?></td>
    <td>&nbsp;</td>
    <td><input name="pagetitle" type="text" /></td>
	<td><?php echo $_lang['search_criteria_title_msg']; ?></td>
  </tr>
  <tr>
    <td><?php echo $_lang['search_criteria_longtitle']; ?></td>
    <td>&nbsp;</td>
    <td><input name="longtitle" type="text" /></td>
	<td><?php echo $_lang['search_criteria_longtitle_msg']; ?></td>
  </tr>
  <tr>
    <td>Alias</td>
    <td>&nbsp;</td>
    <td><input name="alias" type="text" /></td>
    <td></td>
  </tr>
  <tr>
    <td>URL</td>
    <td>&nbsp;</td>
    <td><input name="url" type="text" size="50" /></td>
    <td></td>
  </tr>
  <tr>
    <td><?php echo $_lang['search_criteria_content']; ?></td>
    <td>&nbsp;</td>
    <td><input name="content" type="text" /></td>
	<td><?php echo $_lang['search_criteria_content_msg']; ?></td>
  </tr>
  <tr>
  	<td colspan="4">
		<ul class="actionButtons">
		    <li><a class="default" href="#" onclick="document.searchform.submitok.click();"><img src="<?php echo $_style["icons_save"] ?>" /> <?php echo $_lang['search'] ?></a></li>
		    <li><a href="index.php?a=2"><img src="<?php echo $_style["icons_cancel"] ?>" /> <?php echo $_lang['cancel'] ?></a></li>
		</ul>
	</td>
  </tr>
</table>

<input type="submit" value="Search" name="submitok" style="display:none" />
</form>
</div>
</div>

<?php
if(isset($_REQUEST['submitok'])) {
    $searchid = ($_REQUEST['searchid']!=='') ? intval($_REQUEST['searchid']) : '0';
	$searchtitle = htmlentities($_POST['pagetitle'], ENT_QUOTES, $modx_manager_charset);
    $search_alias = $modx->db->escape($_REQUEST['alias']);
	$searchcontent = $modx->db->escape($_REQUEST['content']);
	$searchlongtitle = $modx->db->escape($_REQUEST['longtitle']);
    if(isset($_REQUEST['url']) && $_REQUEST['url']!=='') {
        $url = $modx->db->escape($_REQUEST['url']);
        $friendly_url_suffix = $modx->config['friendly_url_suffix'];
        $base_url = $modx->config['base_url'];
        $site_url = $modx->config['site_url'];
        $url = preg_replace('@' . $friendly_url_suffix . '$@', '', $url);
        if($url[0]==='/')             $url = preg_replace('@^' . $base_url . '@', '', $url);
        if(substr($url,0,4)==='http') $url = preg_replace('@^' . $site_url . '@', '', $url);
        $searchid = $modx->getIdFromAlias($url);
        if (empty($searchid)) $searchid = 'x';
    }

    $tbl_site_content = $modx->getFullTableName('site_content');
    $sqladd .= $searchid!=='0'        ? " AND id='{$searchid}' " : '';
    $sqladd .= $searchtitle!=''     ? " AND pagetitle LIKE '%{$searchtitle}%' " : '';
    $sqladd .= $searchlongtitle!='' ? " AND longtitle LIKE '%{$searchlongtitle}%' " : '';
    $sqladd .= $search_alias!='' ? " AND alias LIKE '%{$search_alias}%' " : '';
    $sqladd .= $searchcontent!=''   ? " AND content LIKE '%{$searchcontent}%' " : '';

    $fields = 'id, contenttype, pagetitle, description, deleted, published, isfolder, type';
    $where  = "1=1 {$sqladd}";
    $rs = $modx->db->select($fields,$tbl_site_content,$where,'id');
$limit = $modx->db->getRecordCount($rs);
?>
<div class="section">
<div class="sectionHeader"><?php echo $_lang['search_results']; ?></div><div class="sectionBody">
<?php
if($limit<1) {
	echo $_lang['search_empty'];
} else {
	printf('<p>'.$_lang['search_results_returned_msg'].'</p>', $limit);
?>
	<script type="text/javascript" src="media/script/tablesort.js"></script>
  <table border="0" cellpadding="2" cellspacing="0" class="sortabletable sortable-onload-2 rowstyle-even" id="table-1" width="90%"> 
    <thead> 
      <tr bgcolor="#CCCCCC"> 
		<th width="20"></th>
        <th class="sortable"><b><?php echo $_lang['search_results_returned_id']; ?></b></th> 
        <th class="sortable"><b><?php echo $_lang['search_results_returned_title']; ?></b></th> 
        <th class="sortable"><b><?php echo $_lang['search_results_returned_desc']; ?></b></th>
		<th width="20"></th>
      </tr> 
    </thead> 
    <tbody>
     <?php
    // icons by content type
    $icons = array(
        'application/rss+xml' => $_style["tree_page_rss"],
        'application/pdf' => $_style["tree_page_pdf"],
        'application/vnd.ms-word' => $_style["tree_page_word"],
        'application/vnd.ms-excel' => $_style["tree_page_excel"],
        'text/css' => $_style["tree_page_css"],
        'text/html' => $_style["tree_page_html"],
        'text/plain' => $_style["tree_page"],
        'text/xml' => $_style["tree_page_xml"],
        'text/javascript' => $_style["tree_page_js"],
        'image/gif' => $_style["tree_page_gif"],
        'image/jpg' => $_style["tree_page_jpg"],
        'image/png' => $_style["tree_page_png"]
    );

while ($row = $modx->db->getRow($rs)) {
		// figure out the icon for the document...
		$icon = "";
	if ($row['type']=='reference') {
			$icon .= $_style["tree_linkgo"];
	} elseif ($row['isfolder'] == 0) {
		$icon .= isset($icons[$row['contenttype']]) ? $icons[$row['contenttype']] : $_style["tree_page_html"];
		} else {
			$icon .= $_style['tree_folder'];
		}

		$tdClass = "";
	if($row['published'] == 0) {
			$tdClass .= ' class="unpublishedNode"';
		}
	if($row['deleted'] == 1) {
			$tdClass .= ' class="deletedNode"';
		}
?>
    <tr>
      <td align="center"><a href="index.php?a=3&id=<?php echo $row['id']; ?>" title="<?php echo $_lang['search_view_docdata']; ?>"><img src="<?php echo $_style['icons_resource_overview']; ?>" /></a></td>
      <td><?php echo $row['id']; ?></td>
<?php
		if (function_exists('mb_strlen') && function_exists('mb_substr')) {
?>
		<td<?php echo $tdClass; ?>><?php echo mb_strlen($row['pagetitle'], $modx_manager_charset)>70 ? mb_substr($row['pagetitle'], 0, 70, $modx_manager_charset)."..." : $row['pagetitle'] ; ?></td>
		<td<?php echo $tdClass; ?>><?php echo mb_strlen($row['description'], $modx_manager_charset)>70 ? mb_substr($row['description'], 0, 70, $modx_manager_charset)."..." : $row['description'] ; ?></td>
<?php
		} else {
?>
		<td<?php echo $tdClass; ?>><?php echo strlen($row['pagetitle'])>20 ? substr($row['pagetitle'], 0, 20).'...' : $row['pagetitle'] ; ?></td>
		<td<?php echo $tdClass; ?>><?php echo strlen($row['description'])>35 ? substr($row['description'], 0, 35).'...' : $row['description'] ; ?></td>
<?php
		}
?>
      <td align="center"><img src="<?php echo $icon; ?>" /></td>
    </tr>
<?php
	}
?>
    </tbody>
     </table>
<?php
}
?>
</div>
</div>
<?php
}
?>