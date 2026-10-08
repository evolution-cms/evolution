<?php

/*
| main.css styles ".warning" as a ~100%-width inline-block whenever it sits as a direct child of
| td:first-child in these forms, for a label whose whole text is the warning. The email row only
| wraps the "*" in .warning, so that rule stretched the lone asterisk across the cell and pushed
| the label text out to the right behind it. The label is wrapped in an outer span so .warning is
| no longer a direct child of the td and the rule stops matching.
*/

it('marks the web user email label as required in the form, with the asterisk wrapped', function () {
    $formPath = dirname(__DIR__, 4) . '/manager/actions/mutate_web_user.dynamic.php';
    $form = file_get_contents($formPath);

    expect($form)->toContain('<span><span class="warning">*</span> <?php echo $_lang[\'user_email\']; ?>:</span>')
        // the bare, unwrapped form is what main.css's td:first-child > .warning rule stretches
        // to ~100% width, pushing the label text off to the right behind it
        ->and($form)->not->toContain('<span class="warning">*</span> <?php echo $_lang[\'user_email\']; ?>:</td>');
});
