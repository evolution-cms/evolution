<?php

use EvolutionCMS\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;

it('submits the legacy Extras installer form with a valid token to the configured Manager', function (string $managerUrl, string $moduleId, int $expectedId) {
    $fixture = dirname(__DIR__, 2) . '/Fixtures/store-installer-options.php';
    $html = evoRunPhp($fixture, [$managerUrl, $moduleId], $exitCode);
    expect($exitCode)->toBe(0);
    expect($html)->toContain('action="' . htmlspecialchars($managerUrl, ENT_QUOTES, 'UTF-8') . '?id=' . $expectedId . '&amp;a=112&amp;action=install2_step"');

    $document = new DOMDocument();
    @$document->loadHTML($html);
    $xpath = new DOMXPath($document);
    $form = $xpath->query('//form[@id="install_form"]')->item(0);
    expect($form)->not->toBeNull();
    expect(strtolower($form->getAttribute('method')))->toBe('post');
    expect($form->getAttribute('action'))->toBe($managerUrl . '?id=' . $expectedId . '&a=112&action=install2_step');

    $fields = $xpath->query('.//input[@name="_token"]', $form);
    expect($fields->length)->toBe(1);
    $token = $fields->item(0)->getAttribute('value');
    expect($token)->toBe(str_repeat('a', 40));

    $session = $_SESSION ?? null;
    $_SESSION = ['mgrValidated' => 1, '_token' => $token];
    try {
        $request = Request::create($form->getAttribute('action'), 'POST', ['_token' => $token, 'options_selected' => '1']);
        expect((new VerifyCsrfToken())->handle($request, fn () => 'installed'))->toBe('installed');

        // A missing or stale token must still fail verification: do not exempt Extras.
        $check = new ReflectionMethod(VerifyCsrfToken::class, 'tokensMatch');
        foreach ([[], ['_token' => 'stale']] as $body) {
            expect($check->invoke(new VerifyCsrfToken(), Request::create($form->getAttribute('action'), 'POST', $body)))->toBeFalse();
        }
    } finally {
        if ($session === null) {
            unset($_SESSION);
        } else {
            $_SESSION = $session;
        }
    }
})->with([
    'default manager' => ['https://example.test/manager/', '7', 7],
    'renamed manager in a subdirectory' => ['https://example.test/site/control/', '12', 12],
    'escaped Manager URL and integer module id' => ['https://example.test/site/a&b/', '12" onmouseover="alert(1)', 12],
]);
