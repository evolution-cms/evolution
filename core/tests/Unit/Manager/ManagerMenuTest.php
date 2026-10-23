<?php

use EvolutionCMS\Support\ManagerMenu;

function managerMenuRow(string $id, string $parent = 'main', int $index = 0, string $permission = '', string $class = ''): array
{
    return [$id, $parent, '<svg></svg>' . $id, 'index.php?a=2', $id, '', $permission, 'main', 0, $index, $class];
}

test('plugin-added items retain parents, stable positions, labels and handlers', function () {
    $menu = [
        managerMenuRow('tools', 'main', 30),
        managerMenuRow('first', 'tools', 5),
        managerMenuRow('second', 'tools', 5),
        managerMenuRow('group', 'tools', 2),
        managerMenuRow('nested', 'group', 0),
    ];
    // Model a plugin moving/removing stock entries and appending its own root item.
    unset($menu[1]);
    $menu[2][1] = 'group';
    $menu[2][5] = 'window.pluginAction(event); return false;';
    $menu[] = managerMenuRow('plugin', 'main', 1);
    $tree = (new ManagerMenu())->tree($menu, fn () => true);

    expect(array_column($tree, 'id'))->toBe(['plugin', 'tools'])
        ->and(array_column($tree[1]['children'], 'id'))->toBe(['group'])
        ->and(array_column($tree[1]['children'][0]['children'], 'id'))->toBe(['nested', 'second'])
        ->and($tree[1]['children'][0]['children'][1]['onclick'])->toBe('window.pluginAction(event); return false;')
        ->and((string) $tree[0]['label'])->toBe('<svg></svg>plugin');
});

test('equal menu positions retain extension insertion order', function () {
    $tree = (new ManagerMenu())->tree([
        managerMenuRow('z'), managerMenuRow('a'), managerMenuRow('m'),
    ], fn () => true);
    expect(array_column($tree, 'id'))->toBe(['z', 'a', 'm']);
});

test('the shipped sidebar toggle sentinel is not interpreted as a permission', function () {
    $row = managerMenuRow('bars', 'main', 10, ' return false;');
    $row[5] = 'evo.resizer.toggle(); return false;';
    $tree = (new ManagerMenu())->tree([$row], fn () => false);
    expect($tree[0]['id'])->toBe('bars')->and($tree[0]['onclick'])->toBe($row[5]);
});

test('menu permissions accept an available operation and hide denied branches', function () {
    $tree = (new ManagerMenu())->tree([
        managerMenuRow('edit', 'main', 0, 'new_chunk, edit_chunk'),
        managerMenuRow('denied', 'main', 0, 'settings'),
        managerMenuRow('child', 'denied'),
        managerMenuRow('open'),
    ], fn ($permission) => $permission === 'edit_chunk');
    expect(array_column($tree, 'id'))->toBe(['edit', 'open']);
});

test('static plugin children take precedence over an AJAX submenu and keep optional controls', function () {
    $row = managerMenuRow('parent', 'main', 0, '', 'dropdown-toggle custom-plugin');
    $row[11] = ['extra' => ['a', 'javascript:;', 'extra-class', 'extra(event)', 'Extra', '<svg></svg>']];
    $tree = (new ManagerMenu())->tree([$row, managerMenuRow('child', 'parent')], fn () => true);
    expect($tree[0]['lazy'])->toBeFalse()
        ->and($tree[0]['class'])->toBe('dropdown-toggle custom-plugin')
        ->and($tree[0]['optional'][0]['onclick'])->toBe('extra(event)')
        ->and((string) $tree[0]['optional'][0]['label'])->toBe('<svg></svg>');

    $tree = (new ManagerMenu())->tree([$row], fn () => true);
    expect($tree[0]['lazy'])->toBeTrue();
});

test('single optional buttons, separators and external targets survive adaptation', function () {
    $row = managerMenuRow('external');
    $row[7] = '_blank';
    $row[8] = 1;
    $row[11] = ['button', '', 'my-button', 'extra(event)', 'Extra', 'Run'];
    $tree = (new ManagerMenu())->tree([$row], fn () => true);
    expect($tree[0]['target'])->toBe('_blank')
        ->and($tree[0]['divider'])->toBeTrue()
        ->and($tree[0]['optional'][0]['tag'])->toBe('button');
});

test('already escaped module captions remain readable accessible labels', function () {
    $row = managerMenuRow('module');
    $row[2] = 'A &amp; B &lt;module&gt;';
    $row[4] = 'A &amp; B &lt;module&gt;';
    $tree = (new ManagerMenu())->tree([$row], fn () => true);
    expect($tree[0]['title'])->toBe('A & B <module>')
        ->and((string) $tree[0]['label'])->toBe('A &amp; B &lt;module&gt;');
});

test('malformed rows and cyclic plugin parents cannot break menu construction', function () {
    $tree = (new ManagerMenu())->tree([
        null, [], managerMenuRow('main'), managerMenuRow('loop', 'loop'), managerMenuRow('good'),
    ], fn () => true);
    expect(array_column($tree, 'id'))->toBe(['good']);
});
