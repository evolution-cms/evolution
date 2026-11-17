<?php

/*
| The "Resource overview" button of the recent resources widget is itself an <a data-toggle="collapse">,
| and its icon has pointer-events: none, so every click lands on that <a>. The toggle handler skipped
| any click on a link, so the button never opened the overview row.
*/

$root = dirname(__DIR__, 4);

test('the info button of the recent resources widget is a link that toggles the overview row', function () use ($root) {
    $recent = (string) file_get_contents($root . '/manager/media/style/default/snippets/welcome/RecentInfo.php');
    $css = (string) file_get_contents($root . '/manager/media/style/default/css/custom.css');

    expect($recent)->toContain('<a title="[%resource_overview%]" data-toggle="collapse" data-target=".collapse[+id+]">')
        ->and($css)->toMatch('/\.table\.data \.actions a \.action-icon svg[^{]*\{[^}]*pointer-events: none/');
});

test('the dashboard toggle skips only links other than the toggle itself', function () use ($root) {
    $script = (string) file_get_contents($root . '/manager/media/style/default/chunks/welcome/StartUpScript.tpl');

    expect($script)
        ->not->toContain('if(e.target.tagName === "A") return;')
        ->toContain('var link = $(e.target).closest("a");')
        ->toContain('if(link.length && !link.is(this)) return;');
});
