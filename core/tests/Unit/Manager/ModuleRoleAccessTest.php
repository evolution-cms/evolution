<?php

use EvolutionCMS\Support\ModuleAccess;

it('leaves unrestricted modules available to every role', function () {
    expect(ModuleAccess::isAllowedForRole(2, []))->toBeTrue()
        ->and(ModuleAccess::isAllowedForRole(3, []))->toBeTrue()
        ->and(ModuleAccess::isAllowedForRole(0, []))->toBeTrue();
});

it('only lets the listed roles run a restricted module', function () {
    expect(ModuleAccess::isAllowedForRole(2, [2, 3]))->toBeTrue()
        ->and(ModuleAccess::isAllowedForRole(3, [2, 3]))->toBeTrue()
        ->and(ModuleAccess::isAllowedForRole(4, [2, 3]))->toBeFalse();
});

it('always lets the administrator role run a restricted module', function () {
    expect(ModuleAccess::isAllowedForRole(ModuleAccess::ADMIN_ROLE, [2]))->toBeTrue();
});

it('compares role ids numerically, not by string shape', function () {
    // ids arrive from $_POST and from the database as strings
    expect(ModuleAccess::isAllowedForRole(2, ['2']))->toBeTrue()
        ->and(ModuleAccess::isAllowedForRole(2, ['02']))->toBeTrue()
        ->and(ModuleAccess::isAllowedForRole(20, ['2']))->toBeFalse();
});

it('drops junk and duplicates when normalizing role ids', function () {
    expect(ModuleAccess::normalizeRoleIds(['3', 3, 0, -1, 'x', null, [], 4]))->toBe([3, 4]);
});

it('restricts the bundled Extras module to the administrator role by default', function () {
    expect(ModuleAccess::defaultRolesFor('Extras'))->toBe([ModuleAccess::ADMIN_ROLE])
        ->and(ModuleAccess::isAllowedForRole(2, ModuleAccess::defaultRolesFor('Extras')))->toBeFalse()
        ->and(ModuleAccess::isAllowedForRole(3, ModuleAccess::defaultRolesFor('Extras')))->toBeFalse()
        ->and(ModuleAccess::isAllowedForRole(1, ModuleAccess::defaultRolesFor('Extras')))->toBeTrue();
});

it('leaves modules without a shipped default unrestricted', function () {
    expect(ModuleAccess::defaultRolesFor('Some Third Party Module'))->toBe([]);
});

it('keeps file based modules unrestricted unless they declare roles', function () {
    $module = ['id' => 'abc', 'name' => 'Report', 'file' => 'report.php', 'properties' => []];

    expect(ModuleAccess::fileModuleAllowedRoles($module))->toBe([])
        ->and(ModuleAccess::canRunFileModule(2, $module))->toBeTrue()
        ->and(ModuleAccess::canRunFileModule(0, $module))->toBeTrue();
});

it('enforces the roles a file based module declares', function () {
    $module = [
        'id' => 'abc',
        'name' => 'Report',
        'file' => 'report.php',
        'properties' => ['roles' => [1, 3]],
    ];

    expect(ModuleAccess::canRunFileModule(3, $module))->toBeTrue()
        ->and(ModuleAccess::canRunFileModule(2, $module))->toBeFalse()
        ->and(ModuleAccess::canRunFileModule(ModuleAccess::ADMIN_ROLE, $module))->toBeTrue();
});

it('accepts a comma separated role list from a file based module', function () {
    $module = ['properties' => ['roles' => '1, 3']];

    expect(ModuleAccess::fileModuleAllowedRoles($module))->toBe([1, 3])
        ->and(ModuleAccess::canRunFileModule(2, $module))->toBeFalse();
});

it('treats an unusable roles declaration as unrestricted rather than locking everyone out', function () {
    expect(ModuleAccess::fileModuleAllowedRoles(['properties' => ['roles' => 5]]))->toBe([])
        ->and(ModuleAccess::fileModuleAllowedRoles(['properties' => 'nonsense']))->toBe([])
        ->and(ModuleAccess::fileModuleAllowedRoles([]))->toBe([]);
});
