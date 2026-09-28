<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A model without $table makes Eloquent derive the name by pluralising the class name,
 * which loads Doctrine Inflector (about 20 files) on the first query of every request.
 */
test('every core model declares its table name', function () {
    $missing = [];
    foreach (glob(dirname(__DIR__, 3) . '/src/Models/*.php') as $file) {
        $class = new ReflectionClass('EvolutionCMS\\Models\\' . basename($file, '.php'));
        if ($class->isAbstract() || !$class->isSubclassOf(Model::class)) {
            continue;
        }
        $declaring = $class->getProperty('table')->getDeclaringClass()->getName();
        $table = $class->newInstanceWithoutConstructor()->getTable();
        if ($declaring === Model::class || $table === '') {
            $missing[] = $class->getShortName();
        }
    }

    expect($missing)->toBe([]);
});

test('models that used to derive their table name keep that exact name', function () {
    $models = [
        'ActiveUser', 'ActiveUserLock', 'ActiveUserSession', 'Category', 'DocumentGroup',
        'DocumentgroupName', 'FileGroup', 'MemberGroup', 'MembergroupName', 'Permissions',
        'PermissionsGroups', 'RolePermissions', 'SiteHtmlsnippet', 'SiteModule', 'SitePlugin',
        'SitePluginEvent', 'SiteSnippet', 'SiteTemplate', 'SiteTmplvar', 'SiteTmplvarContentvalue',
        'SiteTmplvarTemplate', 'SystemEventname', 'SystemSetting', 'User', 'UserAttribute',
        'UserRole', 'UserRoleVar', 'UserSetting', 'UserValue',
    ];

    foreach ($models as $model) {
        $class = new ReflectionClass('EvolutionCMS\\Models\\' . $model);
        // The rule Model::getTable() applies when $table is not set.
        $derived = Str::snake(Str::pluralStudly($model));

        expect($class->newInstanceWithoutConstructor()->getTable())->toBe($derived, $model);
    }
});
