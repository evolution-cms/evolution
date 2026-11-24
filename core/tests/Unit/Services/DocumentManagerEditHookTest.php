<?php

/*
 * document-manager 1.1.0 read the resource before OnBeforeDocFormSave: a plugin that wrote the same
 * row there won over a submitted value equal to the one before the hook, because Eloquent saw nothing
 * to save. 1.1.1 reads it again after the hook, inside the transaction 1.1.0 introduced.
 */

function documentEditWithHook(): array
{
    static $result;
    if ($result === null) {
        $output = evoRunPhp(dirname(__DIR__, 2) . '/Mocks/document_edit_hook_worker.php', [], $exitCode);
        expect($exitCode)->toBe(0, $output);
        $result = json_decode($output, true);
        expect($result)->toBeArray($output);
    }

    return $result;
}

test('a submitted value is saved though it equals the value before the hook changed it', function () {
    $result = documentEditWithHook();

    expect($result['edited']['pagetitle'])->toBe('Manager title')
        ->and($result['returned'])->toBe('Manager title');
});

test('what a plugin changes in the hook survives when the manager did not submit it', function () {
    expect(documentEditWithHook()['edited']['description'])->toBe('Plugin description');
});

test('a hook that stops the save leaves the resource as it was', function () {
    $result = documentEditWithHook();

    expect($result['stopped'])->toBe('Stop the save')
        ->and($result['refused'])->toBe(['pagetitle' => 'Original title', 'description' => 'Original description']);
});
