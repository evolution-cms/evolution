<?php

/*
|--------------------------------------------------------------------------
| The manager update widget follows the line a site runs
|--------------------------------------------------------------------------
|
| make:site notes the branch, moving tag or fork in factory/version.php. The
| widget used to compare releases only: a 3.5.x site never got its branch
| offered, a newer release pulled it off the branch, and a fork site was
| updated from the official repository.
|
*/

$root = dirname(__DIR__, 3);

test('a site on a branch is offered that branch once it has moved', function () use ($root) {
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');

    expect($source)
        ->toContain("\$installedRef = trim((string)(\$currentVersion['ref'] ?? ''));")
        ->toContain("\$installedCommit = trim((string)(\$currentVersion['commit'] ?? ''));")
        ->toContain('$head = updaterFetchRefHead($version, $branch);')
        // Shown only while the branch head differs from the commit the site runs.
        ->toContain("(!\$isLineMode || (\$git['head'] !== '' && \$git['head'] !== \$installedCommit))")
        ->toContain("'/compare/' . \$installedCommit . '...' . \$git['head']");
});

test('a pinned commit or a release is no line to follow', function () use ($root) {
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');

    expect($source)
        ->toContain("&& !preg_match('/^[0-9a-f]{7,40}\$/i', \$installedRef)")
        ->toContain("&& !preg_match('/^\\d+\\.\\d+\\.\\d+(?:[-+][A-Za-z0-9.-]+)?\$/', \$installedRef);");
});

test('a fork site is updated from its fork, the button included', function () use ($root) {
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');
    $line = substr($source, strpos($source, '$installedRepository = trim('), 600);

    expect($line)->toContain("preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+\$~', \$installedRepository)")
        ->and($line)->toContain('$version = $installedRepository;')
        // The live update button and the CLI hint take the repository from $version.
        ->and($source)->toContain("'repository' => \$version,");
});

test('the branch head is looked up once an hour, also when GitHub does not answer', function () use ($root) {
    $source = (string) file_get_contents($root . '/assets/plugins/updater/plugin.updater.php');
    $helper = substr($source, strpos($source, 'function updaterFetchRefHead('), 1600);

    expect($helper)->toContain("date('YmdH')")
        ->and($helper)->toContain('/commits?per_page=1&sha=')
        ->and($helper)->toContain("preg_match('/^[0-9a-f]{40}\$/', (string)\$commits[0]['sha'])")
        ->and($helper)->toContain('@file_put_contents($cacheFile, json_encode($head));');
});

test('the line notice has an English source string', function () use ($root) {
    $en = (string) file_get_contents($root . '/assets/plugins/updater/lang/en.php');

    expect($en)->toContain("\$_lang['updater_line_mode_notice'] = 'This site follows %s")
        ->and($en)->toContain("\$_lang['updater_action_compare'] = 'View changes';");
});
