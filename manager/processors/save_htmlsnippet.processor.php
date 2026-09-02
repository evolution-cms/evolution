<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}
if (!evo()->hasPermission('save_chunk')) {
    evo()->webAlertAndQuit($_lang["error_no_privileges"]);
}

if (isset($_GET['disabled'])) {
    $disabled = $_GET['disabled'] == 1 ? 1 : 0;
    $id = (int)($_REQUEST['id'] ?? 0);
    // Set the item name for logger
    try {
        $chunk = EvolutionCMS\Models\SiteHtmlsnippet::findOrFail($id);
        // invoke OnBeforeChunkFormSave event
        evo()->invokeEvent("OnBeforeChunkFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
        $_SESSION['itemname'] = $chunk->name;
        $chunk->update(['disabled' => $disabled]);
        // invoke OnChunkFormSave event
        evo()->invokeEvent("OnChunkFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        evo()->webAlertAndQuit($_lang["error_no_id"]);
    }
    // empty cache
    evo()->clearCache('full');

    // finished emptying cache - redirect
    $header="Location: index.php?a=76&tab=2&r=2";
    header($header);
    exit;
}

$id = (int)$_POST['id'];
$snippet = $_POST['post'];
$name = trim($_POST['name']);
$description = $_POST['description'];
$locked = isset($_POST['locked']) && $_POST['locked'] == 'on' ? 1 : 0;
$disabled = isset($_POST['disabled']) && $_POST['disabled'] == "on" ? '1' : '0';
$createdon = $editedon = time() + evo()->config['server_offset_time'];

//Kyle Jaebker - added category support
if (empty($_POST['newcategory']) && $_POST['categoryid'] > 0) {
    $category = (int)$_POST['categoryid'];
} elseif (empty($_POST['newcategory']) && $_POST['categoryid'] <= 0) {
    $category = 0;
} else {
    include_once(EVO_MANAGER_PATH . 'includes/categories.inc.php');
    $category = checkCategory($_POST['newcategory']);
    if (!$category) {
        $category = newCategory($_POST['newcategory']);
    }
}

if ($name == "" || $name == 'null') {
    $name = "Untitled chunk";
}

$editor_type = $_POST['which_editor'] != 'none' ? 1 : 2;
$editor_name = $_POST['which_editor'] != 'none' ? $_POST['which_editor'] : 'none';

/**
 * Stop rather than lose the edit.
 *
 * A chunk that keeps its code in a file does not write that code to the column
 * as well, so a file that cannot be written means the editor's contents have
 * nowhere to go. The form values go back into the session first, which is how
 * this processor already handles a rejected save.
 */
function chunkFileWriteFailed($name, $extension, $action, $id = null)
{
    global $_lang;

    evo()->getManagerApi()->saveFormValues($action);
    evo()->webAlertAndQuit(
        sprintf(
            get_by_key($_lang, 'chunk_file_not_writable', 'The chunk file %s could not be written, so nothing was saved. Check the name and the permissions of the chunk directory.'),
            (string) $name . '.' . ltrim((string) $extension, '.')
        ),
        'index.php?a=' . $action . ($id !== null ? '&id=' . $id : '')
    );
}

/**
 * Stop before a name is asked to be a file it cannot be.
 *
 * Almost every name can: the hazards are encoded and decoded back, so a chunk
 * moved into a file is never renamed. What is left over is three things no
 * encoding fixes - and each has a different fix, so each says so.
 */
function chunkNameRefused($name, $extension, $action, $id = null)
{
    global $_lang;

    $store = \EvolutionCMS\Support\ChunkFileStore::make();
    $reason = $store->refuseReason((string) $name, (string) $extension);

    if ($reason === null) {
        // The name is fine on its own; the question is whether another chunk
        // already answers to the same file. A case-insensitive filesystem -
        // which is to say Windows and macOS - cannot keep the two apart.
        $others = \EvolutionCMS\Models\SiteHtmlsnippet::query()
            ->when($id !== null, fn ($q) => $q->where('id', '!=', $id))
            ->pluck('name')
            ->all();

        $clash = $store->collidingName((string) $name, (string) $extension, $others);

        if ($clash === null) {
            return;
        }

        $message = sprintf(
            get_by_key($_lang, 'chunk_name_collides', 'This name and the chunk "%s" would share one file on Windows and macOS, which tell them apart only by case. Rename one of them.'),
            $clash
        );
    } else {
        $messages = [
            'empty' => get_by_key($_lang, 'chunk_name_empty', 'A chunk kept in a file needs a name.'),
            'not_utf8' => get_by_key($_lang, 'chunk_name_not_utf8', 'This name is not valid UTF-8, so it cannot become a filename.'),
            'not_nfc' => get_by_key($_lang, 'chunk_name_not_nfc', 'This name uses combining characters. Retype it, or macOS will treat it as the same file as another spelling of the same word.'),
            'too_long' => get_by_key($_lang, 'chunk_name_too_long', 'This name is too long to be a filename once its special characters are escaped.'),
        ];
        $message = get_by_key($messages, $reason, 'This name cannot become a filename.');
    }

    evo()->getManagerApi()->saveFormValues($action);
    evo()->webAlertAndQuit($message, 'index.php?a=' . $action . ($id !== null ? '&id=' . $id : ''));
}

/**
 * The file this chunk is written to: the format it already uses, or the one the
 * form posted, or the default.
 *
 * A chunk is a file. The row is still written - $snippet goes into it with
 * everything else - but only so the manager's search keeps working, because
 * search reads the database. Nothing renders from the column once the file is
 * there.
 *
 * @deprecated since 3.5.8 writing a chunk's code to the database
 * @todo [remove@3.7] Remove in Evolution CMS 3.7 - drop 'snippet' from both
 *       writes below once search reads the files.
 */
$chunkStore = \EvolutionCMS\Support\ChunkFileStore::make();
$posted = get_by_key($_POST, 'chunkfileextension');
$chunkFormat = $chunkStore->isRegistered($posted)
    ? (string) $posted
    : (string) $chunkStore->writeExtension($name);

switch ($_POST['mode']) {
    case '77':

        // invoke OnBeforeChunkFormSave event
        evo()->invokeEvent("OnBeforeChunkFormSave", [
            "mode" => "new",
            "id" => $id
        ]);

        // disallow duplicate names for new chunks
        if (EvolutionCMS\Models\SiteHtmlsnippet::where('name','=',$name)->first()) {
            evo()->getManagerApi()->saveFormValues(77);
            evo()->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['chunk'], $name), "index.php?a=77");
        }

        // The file goes first: a row that survived a failed write would claim
        // a save that did not happen. A file already sitting at this name
        // belongs to whoever put it there and is adopted, not overwritten by a
        // chunk that has only just been named.
        chunkNameRefused($name, $chunkFormat, 77);

        if ($chunkStore->pathFor($name, $chunkFormat) === null
            && !$chunkStore->write($name, $chunkFormat, $snippet)) {
            chunkFileWriteFailed($name, $chunkFormat, 77);
        }

        //do stuff to save the new doc
        $id = EvolutionCMS\Models\SiteHtmlsnippet::create(compact('name', 'description','snippet','locked','category','editor_type','editor_name','disabled','createdon','editedon'))->getKey();

        // invoke OnChunkFormSave event
        evo()->invokeEvent("OnChunkFormSave", [
            "mode" => "new",
            "id" => $id
        ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        evo()->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "78&id=$id" : "77";
            $header = "Location: index.php?a=" . $a . "&tab=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            $header = "Location: index.php?a=76&tab=2";
            header($header);
        }
        break;
    case '78':
        // invoke OnBeforeChunkFormSave event
        evo()->invokeEvent("OnBeforeChunkFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);

        // disallow duplicate names for chunks
        if (EvolutionCMS\Models\SiteHtmlsnippet::where('id','!=',$id)->where('name','=',$name)->first()) {
            evo()->getManagerApi()->saveFormValues(78);
            evo()->webAlertAndQuit(sprintf($_lang['duplicate_name_found_general'], $_lang['chunk'], $name), "index.php?a=78&id={$id}");
        }

        //do stuff to save the edited doc
        $chunk = EvolutionCMS\Models\SiteHtmlsnippet::find($id);

        $updates = compact('name', 'description','snippet','locked','category','editor_type','editor_name','disabled','editedon');

        chunkNameRefused($name, $chunkFormat, 78, $id);

        // Renaming a chunk leaves its old file behind, and the next chunk
        // named that would adopt it. The code moves with the name.
        $renamedFrom = (string) $chunk->name;

        if (!$chunkStore->write($name, $chunkFormat, $snippet)) {
            chunkFileWriteFailed($name, $chunkFormat, 78, $id);
        }

        if ($renamedFrom !== '' && $renamedFrom !== $name) {
            $chunkStore->forget($renamedFrom);
        }

        $chunk->update($updates);

        // invoke OnChunkFormSave event
        evo()->invokeEvent("OnChunkFormSave", [
            "mode" => "upd",
            "id" => $id
        ]);

        // Set the item name for logger
        $_SESSION['itemname'] = $name;

        // empty cache
        evo()->clearCache('full');

        // finished emptying cache - redirect
        if ($_POST['stay'] != '') {
            $a = ($_POST['stay'] == '2') ? "78&id=$id" : "77";
            $header = "Location: index.php?a=" . $a . "&r=2&stay=" . $_POST['stay'];
            header($header);
        } else {
            evo()->unlockElement(3, $id);
            $header = "Location: index.php?a=76&r=2";
            header($header);
        }
        break;
    default:
        evo()->webAlertAndQuit("No operation set in request.");
}
