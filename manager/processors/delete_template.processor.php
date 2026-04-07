<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if(!EvolutionCMS()->hasPermission('delete_template')) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id == 0) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_id"]);
}

// delete the template, but first check it doesn't have any documents using it
$siteContents = EvolutionCMS\Models\SiteContent::select('id', 'pagetitle','introtext')->where('template',$id)->where('deleted',0)->get();
$limit = $siteContents->count();
if($limit > 0) {
    include EVO_MANAGER_PATH . "includes/header.inc.php";
    ?>

    <h1><?php echo $_lang['manage_templates']; ?></h1>

    <div class="tab-page">
        <div class="container container-body">

            <p>This template is in use.<br/>
                Please set the documents using the template to another template.<br/>
                Documents using this template:</p>
            <ul>
                <?php
                foreach ($siteContents as $row){
                    echo '<li><span style="width: 200px"><a href="index.php?id=' . $row->id . '&a=27">' . $row->pagetitle . '</a></span>' . ($row->introtext != '' ? ' - ' . $row->introtext : '') . '</li>';
                }
                ?>
            </ul>
        </div>
    </div>
    <?php
    include_once EVO_MANAGER_PATH . "includes/footer.inc.php";
    exit;
}
$default_template = $modx->getConfig('default_template');
if($id == $default_template) {
    EvolutionCMS()->webAlertAndQuit("This template is set as the default template. Please choose a different default template in the MODX configuration before deleting this template.");
}

// Set the item name for logger
$name = EvolutionCMS\Models\SiteTemplate::where('id',$id)->first()->templatename;
$_SESSION['itemname'] = $name;

// invoke OnBeforeTempFormDelete event
EvolutionCMS()->invokeEvent("OnBeforeTempFormDelete", [
    "id" => $id
]);

// delete the document.
EvolutionCMS\Models\SiteTemplate::where('id', $id)->delete();

EvolutionCMS\Models\SiteTmplvarTemplate::where('templateid',$id)->delete();
// invoke OnTempFormDelete event
EvolutionCMS()->invokeEvent("OnTempFormDelete", [
    "id" => $id
]);

// empty cache
EvolutionCMS()->clearCache('full');

// finished emptying cache - redirect
$header = "Location: index.php?a=76&r=2";
header($header);
