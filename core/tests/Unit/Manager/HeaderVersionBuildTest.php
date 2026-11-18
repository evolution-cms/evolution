<?php

/*
| The header menu shows the release number on a stable site, and the branch and commit instead on a
| site that follows a branch (make:site notes ref and commit in factory/version.php): the number in a
| branch archive's version file belongs to the next release, so "3.5.9" would mislead there.
*/

test('the header version line shows the release, or the branch and commit a branch site runs', function () {
    $frame = (string) file_get_contents(dirname(__DIR__, 4) . '/manager/views/frame/1.blade.php');
    $start = strpos($frame, '$versionData = (array) evo()->getVersionData();');
    $block = substr($frame, $start, strpos($frame, '</span></li>', $start) - $start + 13);

    expect($start)->not->toBeFalse()
        // getVersionData('ref') would answer a missing key with the whole array
        ->and($block)->toContain("\$versionRef = is_string(\$versionData['ref'] ?? null) ? \$versionData['ref'] : '';")
        ->toContain("\$versionLabel = \$versionRef !== '' && preg_match('/^[0-9a-f]{40}\$/D', \$versionCommit)")
        ->toContain("? evo()->getPhpCompat()->entities(\$versionRef) . ' @' . substr(\$versionCommit, 0, 7)")
        ->toContain(": evo()->getConfig('settings_version');")
        ->toContain("evo()->getVersionData('branch') . ' ' . \$versionLabel . '</span></li>'");
});
