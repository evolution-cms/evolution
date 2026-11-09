<?php

/*
|--------------------------------------------------------------------------
| Document actions that cascade past the id they check
|--------------------------------------------------------------------------
|
| Delete and undelete check the per-document ACL on the posted id, then write to
| every descendant; the menuindex sort checks the sorted folder, then writes to a
| posted list of ids; the drag-and-drop move checked the moved document's own ACL
| only when tree_show_protected was on. Each now runs DocumentCascadeGuard (or, for
| the single moved document, the move target guard) so a private document in the
| branch is not reached through a reachable one. Search had the mirror problem: the
| TV-matched ids were re-added as a top-level orWhere, outside the group filter.
|
*/

function cascadeProcessorSource(string $file): string
{
    return (string) file_get_contents(dirname(__DIR__, 3) . '/../manager/processors/' . $file);
}

it('guards the full delete branch, not only the posted id', function () {
    $source = cascadeProcessorSource('delete_content.processor.php');

    expect($source)
        ->toContain('DocumentCascadeGuard::deniedForSession($documentDeleteIds)')
        ->toContain("webAlertAndQuit(\$_lang[\"access_permission_denied\"]");
});

it('guards the full undelete branch, not only the posted id', function () {
    $source = cascadeProcessorSource('undelete_content.processor.php');

    expect($source)
        ->toContain('DocumentCascadeGuard::deniedForSession($documentDeleteIds)')
        ->toContain("webAlertAndQuit(\$_lang[\"access_permission_denied\"]");
});

it('checks every reordered document in the menuindex sort', function () {
    $source = (string) file_get_contents(
        dirname(__DIR__, 3) . '/../manager/actions/mutate_menuindex_sort.dynamic.php'
    );

    expect($source)
        ->toContain('DocumentCascadeGuard::deniedForSession($docIds)')
        ->toContain("webAlertAndQuit(\$_lang[\"access_permission_denied\"]");
});

it('checks the moved document ACL regardless of tree_show_protected', function () {
    $source = (string) file_get_contents(
        dirname(__DIR__, 3) . '/../manager/media/style/default/ajax.php'
    );

    $move = strpos($source, "case 'movedocument'");
    expect($move)->not->toBeFalse();

    // the moved document itself is checked, not only the destination parent
    expect(strpos($source, 'MoveDocumentTargetGuard::deniedForUser($id)', $move))->not->toBeFalse();
});

it('does not leak TV-matched documents past the document-group filter in search', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/Search.php');

    // the ACL-bypassing top-level orWhere on the TV ids must be gone
    expect($source)->not->toContain('$searchQuery = $searchQuery->orWhere(function ($query) use ($contentIds)');
});
