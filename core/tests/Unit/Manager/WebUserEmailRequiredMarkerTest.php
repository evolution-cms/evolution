<?php

it('marks the web user email label as required in the form', function () {
    $formPath = dirname(__DIR__, 4) . '/manager/actions/mutate_web_user.dynamic.php';
    $form = file_get_contents($formPath);

    expect($form)->toContain('<span class="warning">*</span> <?php echo $_lang[\'user_email\']; ?>:');
});
