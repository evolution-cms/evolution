<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!EvolutionCMS()->hasPermission('save_template')) {
    EvolutionCMS()->webAlertAndQuit($_lang["error_no_privileges"]);
}

$id = (int)$_POST['id'];
$template = $_POST['post'];
$templatename = trim($_POST['templatename']);
$templatealias = trim($_POST['templatealias']);
$description = $_POST['description'];
$locked = isset($_POST['locked']) && $_POST['locked'] == 'on' ? 1 : 0;
$selectable = $id == EvolutionCMS()->config['default_template'] ? 1 :    // Force selectable
    (isset($_POST['selectable']) && $_POST['selectable'] == 'on' ? 1 : 0);
$currentdate = time() + EvolutionCMS()->config['server_offset_time'];

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $categoryid = (int)$_POST['categoryid'];
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $categoryid = 0;
} else {
    include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
    $categoryid = checkCategory($_POST['newcategory']);
    if (!$categoryid) {
        $categoryid = newCategory($_POST['newcategory']);
    }
}

if ($templatename == "") {
    $templatename = "Untitled template";
}

/**
 * Scaffold the file a template alias resolves to, for one of the engines the
 * manager offers. The extension arrives from the form, and an extension is half
 * of a filename under the web root, so it is checked against the registry
 * rather than trusted - TemplateFileEngines::filename() returns null for
 * anything it does not recognise, and for an alias that cannot be a filename.
 */
function createTemplateFile($templatealias, $extension = null)
{
    $engines = \EvolutionCMS\Support\TemplateFileEngines::make();
    $extension = $extension ?? $engines->defaultExtension(evo()->getConfig('chunk_processor'));
    if ($extension === null) {
        return;
    }

    $filename = $engines->filename((string) $templatealias, (string) $extension);
    if ($filename === null) {
        return;
    }

    $views = $engines->scaffoldPath();

    if (!file_exists($views . '/' . $filename)) {
        if (!is_dir($views)) {
            mkdir($views, 0777, true);
        }

        if (is_writeable($views)) {
            file_put_contents($views . '/' . $filename, '');
        }
    }
}

/**
 * @deprecated since 3.5.9, use createTemplateFile()
 * @todo [remove@3.7]
 */
function createBladeFile($templatealias)
{
    createTemplateFile($templatealias, 'blade.php');
}

/**
 * The engine this template renders with, as picked in the form.
 *
 * Stored on the template so that the file it names is the one that renders,
 * rather than whichever extension a plugin registered last. An empty string
 * means the form offered no choice (an older theme, or a third party posting
 * here), and the template keeps whatever it had.
 */
function selectedTemplateFileExtension()
{
    $extension = get_by_key($_POST, 'templatefileextension');

    if (\EvolutionCMS\Support\TemplateFileEngines::make()->isRegistered($extension)) {
        return (string) $extension;
    }

    // Older themes post only the blade checkbox, which is a choice too.
    return !empty($_POST['createbladefile']) ? 'blade.php' : '';
}

/**
 * Whether an older theme asked for a file the way it used to be asked for: a
 * checkbox next to a database-backed template. The current form says it by
 * choosing where the code lives instead.
 */
function wantsTemplateFileCreated()
{
    return !empty($_POST['createtemplatefile']) || !empty($_POST['createbladefile']);
}

/**
 * Where the form says this template's code lives, or '' when it did not say -
 * an older theme, or anything else posting here, which must not be read as a
 * decision to move a template's code.
 */
function selectedTemplateSource()
{
    $source = (string) get_by_key($_POST, 'templatesource', '');

    return in_array($source, [
        \EvolutionCMS\TemplateProcessor::SOURCE_DATABASE,
        \EvolutionCMS\TemplateProcessor::SOURCE_FILE,
    ], true) ? $source : '';
}

/**
 * Stop rather than lose the edit.
 *
 * A template that keeps its code in a file does not write that code to the
 * column as well, so a file that cannot be written means the editor's contents
 * have nowhere to go. The form values are put back in the session first, which
 * is how this processor already handles a rejected save.
 */
function templateFileWriteFailed($templatealias, $extension, $action, $id = null)
{
    global $_lang;

    EvolutionCMS()->getManagerApi()->saveFormValues($action);
    EvolutionCMS()->webAlertAndQuit(
        sprintf(
            get_by_key($_lang, 'template_file_not_writable', 'The template file %s could not be written, so nothing was saved. Check the alias and the permissions of the views directory.'),
            $templatealias . '.' . ltrim((string) $extension, '.')
        ),
        'index.php?a=' . $action . ($id !== null ? '&id=' . $id : '')
    );
}

/**
 * Write the editor's contents to the template's file.
 *
 * The path is never taken from the request: it is rebuilt from the alias and
 * an extension the registry recognises, so nothing outside a configured view
 * path and nothing with an extension no engine renders can be written.
 *
 * @return bool whether the file now holds the posted content
 */
function writeTemplateFile($templatealias, $extension, $content, $mayCreate)
{
    $engines = \EvolutionCMS\Support\TemplateFileEngines::make();
    $path = $engines->pathFor((string) $templatealias, (string) $extension);

    if ($path === null) {
        if (!$mayCreate) {
            return false;
        }

        createTemplateFile($templatealias, $extension);
        $path = $engines->pathFor((string) $templatealias, (string) $extension);

        if ($path === null) {
            return false;
        }
    }

    return is_writable($path) && file_put_contents($path, (string) $content) !== false;
}

switch ($_POST['mode']) {
    case '19':

        // invoke OnBeforeTempFormSave event
        EvolutionCMS()->invokeEvent("OnBeforeTempFormSave", [
            "mode" => "new",
            "id" => $id
        ]);

        // disallow duplicate names for new templates
        $count = \EvolutionCMS\Models\SiteTemplate::where('templatename', $templatename)->count();
        if ($count > 0) {
            EvolutionCMS()->getManagerApi()->saveFormValues(19);
            EvolutionCMS()->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['template'], $templatename), "index.php?a=19");
        }

        if($templatealias == '')
            $templatealias = $templatename;
        $templatealias = strtolower(EvolutionCMS()->stripAlias(trim($templatealias)));

        $count = \EvolutionCMS\Models\SiteTemplate::where('templatealias', $templatealias)->count();

        if ($count > 0) {
            EvolutionCMS()->getManagerApi()->saveFormValues(19);
            EvolutionCMS()->webAlertAndQuit(sprintf($_lang["duplicate_template_alias_found"], $docid, $templatealias), "index.php?a=19");
        }
        //do stuff to save the new doc
        $source = selectedTemplateSource();
        $extension = selectedTemplateFileExtension();
        $goesToFile = $source === \EvolutionCMS\TemplateProcessor::SOURCE_FILE;

        // The file goes first. It is the only copy of the code in this mode, so
        // a row that survives a failed write would point at a file that never
        // arrived - and claim the save succeeded.
        if ($goesToFile) {
            // A file already sitting at this name belongs to whoever put it
            // there - it is adopted, not overwritten by a template that has
            // only just been named.
            $alreadyOnDisk = \EvolutionCMS\Support\TemplateFileEngines::make()
                    ->pathFor($templatealias, $extension) !== null;

            if (!$alreadyOnDisk && !writeTemplateFile($templatealias, $extension, $template, true)) {
                templateFileWriteFailed($templatealias, $extension, 19);
            }
        }

        // Code destined for a file is written to the file, not to the column -
        // one copy, in the place that renders.
        $newid = \EvolutionCMS\Models\SiteTemplate::query()->insertGetId([
            'templatename' => $templatename,
            'templatealias' => $templatealias,
            'templatesource' => $source,
            'templatefileextension' => $extension,
            'description' => $description,
            'content' => $goesToFile ? '' : $template,
            'locked' => $locked,
            'selectable' => $selectable,
            'category' => $categoryid,
            'createdon' => $currentdate,
            'editedon' => $currentdate
        ]);

        // invoke OnTempFormSave event
        EvolutionCMS()->invokeEvent("OnTempFormSave", [
            "mode" => "new",
            "id" => $newid
        ]);
        // Set new assigned Tvs
        saveTemplateAccess($newid);

        if (!$goesToFile && wantsTemplateFileCreated()) {
            createTemplateFile($templatealias, $extension);
        }

        // Set the item name for logger
        $_SESSION['itemname'] = $templatename;

        // empty cache
        EvolutionCMS()->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "16&id=$newid" : "19";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }

        break;
    case '16':

        // invoke OnBeforeTempFormSave event
        EvolutionCMS()->invokeEvent("OnBeforeTempFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);

        // disallow duplicate names for templates
        $count = \EvolutionCMS\Models\SiteTemplate::where('templatename', $templatename)->where('id', '!=', $id)->count();
        if ($count > 0) {
            EvolutionCMS()->getManagerApi()->saveFormValues(16);
            EvolutionCMS()->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['template'], $templatename), "index.php?a=16&id={$id}");
        }

        if($templatealias == '')
            $templatealias = $templatename;
        $templatealias = strtolower(EvolutionCMS()->stripAlias(trim($templatealias)));

        $count = \EvolutionCMS\Models\SiteTemplate::where('templatealias', $templatealias)->where('id', '!=', $id)->count();

        if ($count > 0) {
            EvolutionCMS()->getManagerApi()->saveFormValues(16);
            EvolutionCMS()->webAlertAndQuit(sprintf($_lang["duplicate_template_alias_found"], $docid, $templatealias), "index.php?a=16&id={$id}");
        }
        //do stuff to save the edited doc
        $updates = [
            'templatename' => $templatename,
            'templatealias' => $templatealias,
            'description' => $description,
            'content' => $template,
            'locked' => $locked,
            'selectable' => $selectable,
            'category' => $categoryid,
            'editedon' => $currentdate
        ];

        // An older theme, or anything else posting here without the engine
        // picker, must not silently re-point an existing template at a
        // different engine - so the column is only written when a choice was
        // actually made.
        $selectedExtension = selectedTemplateFileExtension();
        if ($selectedExtension !== '') {
            $updates['templatefileextension'] = $selectedExtension;
        }

        $source = selectedTemplateSource();
        if ($source !== '') {
            $updates['templatesource'] = $source;
        }

        // The editor's contents go wherever this template says its code lives,
        // and nowhere else. The form loads the file behind whichever pair of
        // selectors is chosen, so what is posted is always what was on screen -
        // including the database copy, once the source is switched back to it.
        $goesToFile = $source === \EvolutionCMS\TemplateProcessor::SOURCE_FILE;
        $saved = \EvolutionCMS\Models\SiteTemplate::whereKey($id)
            ->first(['templatesource', 'content']);
        $wasFile = (string) ($saved->templatesource ?? '')
            === \EvolutionCMS\TemplateProcessor::SOURCE_FILE;

        if ($goesToFile) {
            unset($updates['content']);
        }

        // What the editor is showing is what gets written - the form loads the
        // file behind whichever pair of selectors is chosen, so the two cannot
        // disagree. Unless the form could not do that: with scripting off the
        // editor still holds the database copy when the source is switched to
        // a file, and writing it would flatten a file nobody has looked at.
        // Recognisable precisely, because the posted code is the column,
        // character for character.
        $stalePost = $goesToFile
            && !$wasFile
            && (string) $template === (string) ($saved->content ?? '')
            && \EvolutionCMS\Support\TemplateFileEngines::make()
                ->pathFor($templatealias, $selectedExtension) !== null;

        // Same order as a new template: nothing about the template changes
        // until its code is safely on disk.
        if ($goesToFile && !$stalePost
            && !writeTemplateFile($templatealias, $selectedExtension, $template, true)) {
            templateFileWriteFailed($templatealias, $selectedExtension, 16, $id);
        }

        \EvolutionCMS\Models\SiteTemplate::find($id)->update($updates);
        // Set new assigned Tvs
        saveTemplateAccess($id);

        if (!$goesToFile && wantsTemplateFileCreated()) {
            createTemplateFile($templatealias, $selectedExtension);
        }

        // invoke OnTempFormSave event
        EvolutionCMS()->invokeEvent("OnTempFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $templatename;

        // first empty the cache
        EvolutionCMS()->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "16&id=$id" : "19";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            EvolutionCMS()->unlockElement(1, $id);
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }


        break;
    default:
        EvolutionCMS()->webAlertAndQuit("No operation set in request.");
}
