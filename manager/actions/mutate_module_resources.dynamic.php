<?php
if (!defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if (!EvolutionCMS()->hasPermission('edit_module')) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// initialize page view state - the $_PAGE object
EvolutionCMS()->getManagerApi()->initPageViewState();

// check to see the  editor isn't locked
$userActivity = \EvolutionCMS\Models\ActiveUser::query()->where('action', 108)->where('internalKey', '!=', EvolutionCMS()->getLoginUserID('mgr'))->first();
if (!is_null($userActivity)) {
    EvolutionCMS()->webAlertAndQuit(sprintf($_lang['lock_msg'], $userActivity->username, 'module'));
}
// end check for lock

// take action
switch ($_REQUEST['op']) {
    case 'add':
        // convert ids to numbers
        $opids = array_filter(array_map('intval', explode(',', $_REQUEST['newids'])));

        if (count($opids) > 0) {
            // 1-snips, 2-tpls, 3-tvs, 4-chunks, 5-plugins, 6-docs
            $rt = strtolower($_REQUEST["rt"]);
            if ($rt == 'chunk') {
                $type = 10;
            }
            if ($rt == 'doc') {
                $type = 20;
            }
            if ($rt == 'plug') {
                $type = 30;
            }
            if ($rt == 'snip') {
                $type = 40;
            }
            if ($rt == 'tpl') {
                $type = 50;
            }
            if ($rt == 'tv') {
                $type = 60;
            }
            \EvolutionCMS\Models\SiteModuleDepobj::query()->where('module', $id)->whereIn('resource', $opids)->where('type', $type)->delete();
            foreach ($opids as $opid) {
                \EvolutionCMS\Models\SiteModuleDepobj::create([
                    'module' => $id,
                    'resource' => $opid,
                    'type' => $type,
                ]);
            }
        }
        break;
    case 'del':
        // convert ids to numbers
        $opids = array_filter(array_map('intval', $_REQUEST['depid']));

        // get resources that needs to be removed
        $ds = \EvolutionCMS\Models\SiteModule::query()->whereIn('id', $opids)->get();
        // loop through resources and look for plugins and snippets
        $plids = [];
        $snids = [];
        foreach ($ds->toArray() as $row) {
            if ($row['type'] == '30') {
                $plids[$i] = $row['resource'];
            }
            if ($row['type'] == '40') {
                $snids[$i] = $row['resource'];
            }
        }
        // get guid
        $guid = \EvolutionCMS\Models\SiteModule::query()->find($id)->guid;
        // reset moduleguid for deleted resources
        if (($cp = count($plids)) || ($cs = count($snids))) {
            if ($cp) {
                \EvolutionCMS\Models\SitePlugin::query()->whereIn('id', $plids)->where('moduleguid', $guid)->update(['moduleguid' => '']);
            }
            if ($cs) {
                \EvolutionCMS\Models\SitePlugin::query()->whereIn('id', $plids)->where('moduleguid', $snids)->update(['moduleguid' => '']);
            }
            // reset cache
            EvolutionCMS()->clearCache('full');
        }
        \EvolutionCMS\Models\SiteModuleDepobj::query()->whereIn('id', $opids)->delete();
        break;
}

// load record
$content = \EvolutionCMS\Models\SiteModule::find($id);
if (is_null($content)) {
    EvolutionCMS()->webAlertAndQuit("Module not found for id '{$id}'.");
}
$content = $content->toArray();
$_SESSION['itemname'] = $content['name'];
if ($content['locked'] == 1 && $_SESSION['mgrRole'] != 1) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

?>
<script type="text/javascript">

    function removeDependencies() {
        if (confirm("<?php echo $_lang['confirm_delete_record']; ?>") == true) {
            documentDirty = false;
            document.mutate.op.value = "del";
            document.mutate.submit();
        }
    };

    function addSnippet() {
        openSelector("snip", "m", "setResource");
    };

    function addDocument() {
        openSelector("doc", "m", "setResource");
    };

    function addTemplate() {
        openSelector("tpl", "m", "setResource");
    };

    function addTV() {
        openSelector("tv", "m", "setResource");
    };

    function addChunk() {
        openSelector("chunk", "m", "setResource");
    };

    function addPlugin() {
        openSelector("plug", "m", "setResource");
    };

    function setResource(rt, ids) {
        if (ids.length == 0) return;
        document.mutate.op.value = "add";
        document.mutate.rt.value = rt;
        document.mutate.newids.value = ids.join(",");
        document.mutate.submit();
    };

    function openSelector(resource, mode, callback, w, h) {
        var win
        w = w ? w : 600;
        h = h ? h : 400;
        url = "index.php?a=84&sm=" + mode + "&rt=" + resource + "&cb=" + callback
        // center on parent
        if (window.screenX) {
            var x = window.screenX + (window.outerWidth - w) / 2;
            var y = window.screenY + (window.outerHeight - h) / 2;
        } else {
            var x = (screen.availWidth - w) / 2;
            var y = (screen.availHeight - h) / 2;
        }
        self.chkBoxArray = {}; //reset checkbox array;
        win = window.open(url, "resource_selector", "left=" + x + ",top=" + y + ",height=" + h + ",width=" + w + ",status=yes,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no");
        win.opener = self;
    };

    var actions = {
        close: function () {
            document.location.href = 'index.php?a=76&tab=5';
        }
    }
</script>

<form name="mutate" method="post" action="index.php">
    <input type="hidden" name="a" value="113">
    <input type="hidden" name="op" value=""/>
    <input type="hidden" name="rt" value=""/>
    <input type="hidden" name="newids" value=""/>
    <input type="hidden" name="id" value="<?php echo $content['id']; ?>"/>

    <h1>
        <i class="<?= $_style['icon_cogs'] ?>"></i><?= ($content['name'] ? $content['name'] . '<small>(' . $content['id'] . ')</small>' : $_lang['module_resource_title']) ?>
    </h1>

    <?php echo ManagerTheme::getStyle('actionbuttons.dynamic.close') ?>

    <div class="section">
        <div class="sectionHeader"><?php echo $content["name"] . " - " . $_lang['module_resource_title']; ?></div>
        <div class="sectionBody">
            <p><?php echo $_lang['module_resource_msg']; ?></p>
            <br/>
            <!-- Dependencies -->
            <table width="100%" border="0" cellspacing="1" cellpadding="2">
                <tr>
                    <td valign="top" align="left">
                        <?php
                        $depobj = \EvolutionCMS\Models\SiteModuleDepobj::query()->select('site_module_depobj.id', \DB::raw('COALESCE(NULL'), 'site_snippets.name',
                            'site_templates.templatename', 'site_tmplvars.name', 'site_htmlsnippets.name', 'site_tmplvars.name', 'site_plugins.name', 'site_content.pagetitle', \DB::raw('NULL) as name'),
                            'site_module_depobj.type')
                            ->leftJoin('site_htmlsnippets', function ($join) {
                                $join->on('site_htmlsnippets.id', '=', 'site_module_depobj.resource');
                                $join->on('site_module_depobj.type', '=', \DB::raw(10));
                            })
                            ->leftJoin('site_content', function ($join) {
                                $join->on('site_content.id', '=', 'site_module_depobj.resource');
                                $join->on('site_module_depobj.type', '=', \DB::raw(20));
                            })
                            ->leftJoin('site_plugins', function ($join) {
                                $join->on('site_plugins.id', '=', 'site_module_depobj.resource');
                                $join->on('site_module_depobj.type', '=', \DB::raw(30));
                            })
                            ->leftJoin('site_snippets', function ($join) {
                                $join->on('site_snippets.id', '=', 'site_module_depobj.resource');
                                $join->on('site_module_depobj.type', '=', \DB::raw(40));
                            })
                            ->leftJoin('site_templates', function ($join) {
                                $join->on('site_templates.id', '=', 'site_module_depobj.resource');
                                $join->on('site_module_depobj.type', '=', \DB::raw(50));
                            })
                            ->leftJoin('site_tmplvars', function ($join) {
                                $join->on('site_tmplvars.id', '=', 'site_module_depobj.resource');
                                $join->on('site_module_depobj.type', '=', \DB::raw(60));
                            })
                            ->where('site_module_depobj.module', $id)
                            ->orderBy('site_module_depobj.type')
                            ->orderBy('name');
                        $grd = new \EvolutionCMS\Support\DataGrid('', $depobj, 0); // set page size to 0 t show all items
                        $grd->noRecordMsg = $_lang["no_records_found"];
                        $grd->prepareResult = ['type' => [10 => 'Chunk', 20 => 'Document', 30 => 'Plugin', 40 => 'Snippet', 50 => 'Template', 60 => 'TV']];
                        $grd->cssClass = "grid";
                        $grd->columnHeaderClass = "gridHeader";
                        $grd->itemClass = "gridItem";
                        $grd->altItemClass = "gridAltItem";
                        $grd->columns = $_lang["element_name"] . " ," . $_lang["type"];
                        $grd->colTypes = "template:<input type='checkbox' name='depid[]' value='[+id+]'> [+value+]";
                        $grd->fields = "name,type";
                        echo $grd->render();
                        ?>
                    </td>
                    <td valign="top" style="width: 150px;">
                        <a class="btn btn-block btn-danger text-left" style="margin-bottom:10px;" href="javascript:;"
                           onclick="removeDependencies();return false;"><i
                                    class="<?php echo $_style["icon_trash"] ?>"></i> <?php echo $_lang['remove']; ?></a>
                        <div class="btn-group-vertical" style="min-width: 100%">
                            <a class="btn btn-block btn-secondary text-left" href="javascript:;"
                               onclick="addSnippet();return false;"><i
                                        class="<?php echo $_style["icon_add"] ?>"></i> <?php echo $_lang['add_snippet']; ?>
                            </a>
                            <a class="btn btn-block btn-secondary text-left" href="javascript:;"
                               onclick="addDocument();return false;"><i
                                        class="<?php echo $_style["icon_add"] ?>"></i> <?php echo $_lang['add_doc']; ?>
                            </a>
                            <a class="btn btn-block btn-secondary text-left" href="javascript:;"
                               onclick="addChunk();return false;"><i
                                        class="<?php echo $_style["icon_add"] ?>"></i> <?php echo $_lang['add_chunk']; ?>
                            </a>
                            <a class="btn btn-block btn-secondary text-left" href="javascript:;"
                               onclick="addPlugin();return false;"><i
                                        class="<?php echo $_style["icon_add"] ?>"></i> <?php echo $_lang['add_plugin']; ?>
                            </a>
                            <a class="btn btn-block btn-secondary text-left" href="javascript:;"
                               onclick="addTV();return false;"><i
                                        class="<?php echo $_style["icon_add"] ?>"></i> <?php echo $_lang['add_tv']; ?>
                            </a>
                            <a class="btn btn-block btn-secondary text-left" href="javascript:;"
                               onclick="addTemplate();return false;"><i
                                        class="<?php echo $_style["icon_add"] ?>"></i> <?php echo $_lang['add_template']; ?>
                            </a>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    <input type="submit" name="save" style="display:none">
</form>
