<?php

$root = dirname(__DIR__, 3);

test('permission check reads the allow root setting and scopes the document lookup', function () use ($root) {
    $source = file_get_contents($root . '/core/src/Legacy/Permissions.php');

    // the manager stopped exporting settings as globals, so the setting has to be read back
    expect($source)->toContain("evo()->getConfig('udperms_allowroot')")
        // the document group lookup has to be limited to the document being checked
        ->and($source)->toContain("->where('site_content.id', (int) \$document)")
        ->and($source)->toContain("->orWhereIn('document_groups.document_group', \$docgrp)")
        // trashed documents stay visible, otherwise undelete and publish lose their target
        ->and($source)->toContain('SiteContent::withTrashed()->where(\'site_content.id\'')
        // intval() as a Collection callback receives the key as its base argument
        ->and($source)->not->toContain("->map('intval')");
});

test('a new resource without a requested parent falls back to the first allowed location', function () use ($root) {
    $source = file_get_contents($root . '/manager/actions/mutate_content.dynamic.php');

    expect($source)->toContain('EvolutionCMS\Legacy\Permissions::getFirstAllowedParent()')
        ->and($source)->toContain('EvolutionCMS\Legacy\Permissions::canCreateIn(')
        // a restored form keeps the parent the user picked
        ->and($source)->toContain("!isset(\$_REQUEST['pid']) && !isset(\$content['parent'])");
});

test('resource tree nodes publish whether they accept a new child', function () use ($root) {
    $source = file_get_contents($root . '/core/functions/nodes.php');

    expect($source)->toContain('ResourceParentGuard::documentIsAccessible(')
        ->and($source)->toContain('ResourceParentGuard::nodeAcceptsChild(')
        ->and($source)->toContain("'canAddChild' => \$row['canAddChild']")
        // single, folder and childless folder node templates
        ->and(substr_count($source, 'data-canaddchild="[+canAddChild+]"'))->toBe(3)
        // the protected styling and the hasAccess placeholder keep their old meaning
        ->and($source)->toContain("if (\$mgrRole == 1 || \$row['privatemgr'] == 0) {");
});

test('the site root node reports whether resources may be created in it', function () use ($root) {
    $template = file_get_contents($root . '/manager/views/frame/tree.blade.php');

    expect($template)->toContain('data-canaddchild="{{ \EvolutionCMS\Legacy\Permissions::canCreateIn(0) ? 1 : 0 }}"');
});

test('default manager theme refuses parent and move targets the user may not manage', function () use ($root) {
    $themeJs = file_get_contents($root . '/manager/media/style/default/js/evo.js');

    expect($themeJs)->toContain('isBlockedParentTarget: function (node)')
        ->and($themeJs)->toContain('w.modxTreeParentGuardHelper.isBlockedParentTarget(node)')
        // both the parent picker of the resource form and the move screen are guarded
        ->and(substr_count($themeJs, 'this.isBlockedParentTarget(el)'))->toBe(2)
        ->and($themeJs)->toContain('alert(evo.lang.access_permission_parent_denied)');
});

test('the manager frame loads the parent guard helper and its message', function () use ($root) {
    $template = file_get_contents($root . '/manager/views/frame/1.blade.php');

    expect($template)->toContain('media/script/tree-parent-guard-helper.js')
        ->and($template)->toContain("'access_permission_parent_denied' => ManagerTheme::getLexicon('access_permission_parent_denied')");
});

test('every shipped language defines the parent denied message', function () use ($root) {
    foreach (glob($root . '/core/lang/*/global.php') as $file) {
        expect(file_get_contents($file))->toContain('$_lang["access_permission_parent_denied"]');
    }
});
