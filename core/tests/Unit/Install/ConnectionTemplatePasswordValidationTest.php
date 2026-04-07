<?php

namespace Tests\Unit\Install;

use InvalidArgumentException;
use Tests\TestCase;

require_once dirname(__DIR__, 4) . '/install/src/functions.php';

final class ConnectionTemplatePasswordValidationTest extends TestCase
{
    public function testShortAdminPasswordsUseDedicatedMinLengthMessage(): void
    {
        $template = (string) file_get_contents(dirname(__DIR__, 4) . '/install/src/template/actions/connection.tpl');

        self::assertStringContainsString('data-minlength="[+adminPasswordMinLengthMessage+]"', $template);
        self::assertStringContainsString("return setFieldErrorMessage(form.cmspassword, 'minlength');", $template);
        self::assertStringContainsString("return setFieldErrorMessage(form.cmspasswordconfirm, 'minlength');", $template);
        self::assertStringContainsString("return setFieldErrorMessage(form.cmspasswordconfirm, 'default');", $template);
    }

    public function testPasswordValidatorSharesTheSameMinLengthContract(): void
    {
        self::assertSame('Admin password should have at least 8 characters', adminPasswordMinLengthMessage());

        try {
            validateAdminPassword('1234567');
            self::fail('Expected validateAdminPassword() to reject short passwords.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(adminPasswordMinLengthMessage(), $exception->getMessage());
        }
    }
}
