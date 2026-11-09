<?php

/*
|--------------------------------------------------------------------------
| Manager actions that used to check less than the page that leads to them
|--------------------------------------------------------------------------
|
| The move form (a=51) enforces the per-document ACL, but the move action (a=52) it submits to
| only checked the global save_document right, so the form was the only thing standing between a
| user and a document outside their groups. Likewise phpinfo and the system info page expose the
| server environment and the database layout, which is settings-level detail, but they were open
| to anyone holding the much more common logs right.
|
*/

function controllerSource(string $class): string
{
    return (string)file_get_contents(dirname(__DIR__, 3) . '/src/Controllers/' . $class . '.php');
}

it('checks the source document ACL in the move action, not only in the move form', function () {
    $source = controllerSource('MoveDocument');

    $handle = strpos($source, 'protected function handle()');
    $display = strpos($source, 'protected function processDisplay()');
    $check = strpos($source, "\$this->checkDocumentPermission(\$document->getKey(), 'access_permission_denied');", $handle);

    expect($handle)->not->toBeFalse()
        ->and($check)->not->toBeFalse()
        ->and($check)->toBeLessThan($display)
        ->and($check)->toBeLessThan(strpos($source, '$document->save();', $handle));

    // the form keeps its own check
    expect(strpos($source, "\$this->checkDocumentPermission(\$document->getKey(), 'access_permission_denied');", $display))
        ->not->toBeFalse();
});

it('gates the phpinfo and system info pages on the settings right', function (string $class) {
    $source = controllerSource($class);

    expect($source)
        ->toContain("hasPermission('settings')")
        ->and($source)->not->toContain("hasPermission('logs')");
})->with(['Phpinfo', 'SystemInfo']);

it('caps role save at the actor reach and checks role deletion rights', function () {
    $source = controllerSource('UserRoles/UserRole');

    // save: subset guard before writing, and only grantable permissions stored
    expect($source)->toContain('$this->guardRoleReach($id, $actorRole, $actorPermissions);')
        ->and($source)->toContain('RoleGuard::grantablePermissions(')
        ->and($source)->not->toContain('foreach (array_keys($selectedPermissions) as $key) {');

    // delete: needs delete_role and the same reach guard
    $delete = strpos($source, "\$_GET['action'] == 'delete'");
    expect(strpos($source, "hasPermission('delete_role')", $delete))->not->toBeFalse()
        ->and(strpos($source, '$this->guardRoleReach($id)', $delete))->not->toBeFalse();
});

it('assigns a user role only within the actor reach, not just below admin', function () {
    $source = controllerSource('Users/EditOrNewUser');

    expect($source)->toContain('RoleGuard::mayManageRole($actorRole, $actorPermissions, $newRole, $newRolePermissions)')
        ->and($source)->not->toContain('AdminAccountGuard::mayAssignRole(');
});

function processorSource(string $file): string
{
    return (string)file_get_contents(dirname(__DIR__, 3) . '/../manager/processors/' . $file);
}

it('gates the full-site refresh on the empty cache right, not on nothing', function () {
    $source = controllerSource('RefreshSite');

    $canView = strpos($source, 'public function canView');
    expect($canView)->not->toBeFalse()
        ->and(strpos($source, "hasPermission('empty_cache')", $canView))->not->toBeFalse()
        // the old unconditional allow is gone
        ->and(substr($source, $canView, 120))->not->toContain('return true;');
});

it('gates permission-definition save and delete on the role rights', function () {
    $source = controllerSource('UserRoles/Permission');

    $process = strpos($source, 'public function process');
    $delete = strpos($source, "\$_GET['action'] == 'delete'", $process);
    $post = strpos($source, "isset(\$_POST['a'])", $process);

    expect(strpos($source, "hasPermission('delete_role')", $delete))->not->toBeFalse()
        ->and(strpos($source, "hasPermission('delete_role')", $delete))->toBeLessThan(
            strpos($source, 'Models\Permissions::query()->where', $delete)
        )
        ->and(strpos($source, "hasPermission('save_role')", $post))->not->toBeFalse()
        ->and(strpos($source, "hasPermission('save_role')", $post))->toBeLessThan(
            strpos($source, '$this->updateOrCreate();', $post)
        );
});

it('sets user groups through UserGroupGuard, gated by manage_groups', function () {
    $source = controllerSource('Users/EditOrNewUser');

    expect($source)->toContain('UserGroupGuard::authorizedGroups(')
        ->and($source)->toContain("hasPermission('manage_groups')")
        // the old unconditional setGroups of the posted array is gone
        ->and($source)->not->toContain("\UserManager::setGroups(['id' => \$user->getKey(), 'groups' => \$userData['user_groups']])");
});

it('shows group checkboxes outside the actor reach as read-only in the user form', function () {
    $source = (string) file_get_contents(
        dirname(__DIR__, 3) . '/../manager/actions/mutate_web_user.dynamic.php'
    );

    // the form derives the same reach the save guard uses, and disables what the actor cannot change
    expect($source)->toContain("hasPermission('manage_groups')")
        ->and($source)->toContain('$canChangeGroup')
        ->and($source)->toContain("' disabled=\"disabled\"'")
        ->and($source)->toContain('access_permissions_user_group_locked');
});

it('checks the matching permission in the delete processors', function (string $file, string $permission, string $gone) {
    $source = processorSource($file);

    expect($source)->toContain("hasPermission('{$permission}')")
        ->and($source)->not->toContain("hasPermission('{$gone}')");
})->with([
    ['delete_htmlsnippet.processor.php', 'delete_chunk', 'delete_snippet'],
    ['delete_category.processor.php', 'category_manager', 'save_plugin'],
    ['remove_content.processor.php', 'empty_trash', 'delete_document'],
]);
