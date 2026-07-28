<?php

use EvolutionCMS\Support\MailTestMailer;

it('maps raw SMTP failures to sanitized feedback keys', function (string $error, string $expected) {
    $mailer = new MailTestMailer();
    $mailer->isSMTP();
    $mailer->SetError($error);

    expect($mailer->failureMessageKey())->toBe($expected);
})->with([
    'authentication' => ['SMTP Error: Could not authenticate. 535', 'mail_test_error_authentication'],
    'connection' => ['SMTP connect() failed: connection timed out', 'mail_test_error_connection'],
    'encryption' => ['TLS certificate verification failed', 'mail_test_error_encryption'],
    'recipient' => ['SMTP Error: recipient address rejected 550', 'mail_test_error_recipient'],
    'unknown' => ['Unexpected transport failure', 'mail_test_error'],
]);

it('keeps credentials out of the user-facing failure classification', function () {
    $mailer = new MailTestMailer();
    $mailer->isSMTP();
    $mailer->Username = 'smtp-user@example.test';
    $mailer->Password = 'smtp-secret-password';
    $mailer->SetError('SMTP Error: Could not authenticate.');

    $feedbackKey = $mailer->failureMessageKey();

    expect($feedbackKey)
        ->toBe('mail_test_error_authentication')
        ->not->toContain($mailer->Username)
        ->not->toContain($mailer->Password);
});

it('maps PHP mail failures to generic feedback', function () {
    $mailer = new MailTestMailer();
    $mailer->isMail();
    $mailer->SetError('Could not instantiate mail function.');

    expect($mailer->failureMessageKey())->toBe('mail_test_error');
});

it('registers a native one-time Manager mail action', function () {
    $root = dirname(__DIR__, 4);
    $viewPath = $root . '/manager/views/page/system_settings/mail_templates.blade.php';
    $view = file_get_contents($viewPath);
    $settingsView = file_get_contents(dirname($viewPath) . '/../system_settings.blade.php');
    $controller = file_get_contents($root . '/core/src/Controllers/MailTest.php');
    $managerTheme = file_get_contents($root . '/core/src/ManagerTheme.php');
    $managerRoutes = file_get_contents($root . '/manager/routes.php');
    $actionList = require $root . '/core/factory/actionlist.php';

    expect($view)
        ->toContain('id="mailTestDestination"')
        ->toContain('class="row form-row form-element-input" id="mailTestPanel"')
        ->toContain('data-endpoint="index.php"')
        ->toContain('data-action="{{ \EvolutionCMS\Controllers\MailTest::ACTION_ID }}"')
        ->toContain('class="control-label col-5 col-md-3 col-lg-2"')
        ->toContain('class="col-7 col-md-9 col-lg-10"')
        ->toContain('form="mailTestForm"')
        ->toContain('type="button" id="mailTestSend" form="mailTestForm"')
        ->toContain("body.set('a', panel.dataset.action)")
        ->toContain("body.set('_token', panel.dataset.token)")
        ->toContain('payload.success !== true')
        ->not->toContain('<form');

    expect($settingsView)->toContain('<form id="mailTestForm" onsubmit="return false;"></form>');

    expect($controller)
        ->toContain('public const ACTION_ID = 201;')
        ->toContain('implements ManagerThemeContract\PageControllerInterface')
        ->toContain("\$request->isMethod('post')")
        ->toContain("in_array(\$method, ['mail', 'smtp'], true)")
        ->toContain('new MailTestMailer()')
        ->toContain("'destination' => \$destination")
        ->toContain('isHTML(true)')
        ->toContain('renderHtmlMessage(')
        ->toContain("getVersionData('version')")
        ->not->toContain('AltBody')
        ->not->toContain("getConfig('email_method') !== 'smtp'");

    expect($managerTheme)
        ->toContain('201 => Controllers\MailTest::class')
        ->and($actionList[201] ?? null)->toBe('Sending a test mail message')
        ->and($managerRoutes)->not->toContain("Route::post('mail/test'");
});

it('provides the HTML test-mail copy in every bundled locale', function () {
    $root = dirname(__DIR__, 4);
    $languageFiles = glob($root . '/core/lang/*/global.php');

    expect($languageFiles)->toHaveCount(22);
    foreach ($languageFiles as $languageFile) {
        $language = file_get_contents($languageFile);

        expect($language)
            ->toContain("\$_lang['mail_test_subject']")
            ->toContain(':destination')
            ->toContain("\$_lang['mail_test_automated_note']");
    }
});
