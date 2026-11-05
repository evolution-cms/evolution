<?php

use EvolutionCMS\Support\HtaccessRules;

require_once dirname(__DIR__, 3) . '/src/Support/HtaccessRules.php';

function htaccessRulesFile(string $path): string
{
    return (string) file_get_contents(dirname(__DIR__, 4) . '/' . $path);
}

test('the rules it asks for are the rules ht.access ships', function () {
    expect(HtaccessRules::missing(htaccessRulesFile('ht.access')))->toBe([]);
});

test('it names what a .htaccess of an older release lacks', function () {
    $older = implode("\n", array_slice(HtaccessRules::REQUIRED, 0, 2));

    expect(HtaccessRules::missing($older))->toBe(array_slice(HtaccessRules::REQUIRED, 2));
});

test('a commented out rule is a missing rule, and spacing does not matter', function () {
    $file = "\t" . str_replace(' ', "  ", HtaccessRules::REQUIRED[0]) . "\r\n# " . HtaccessRules::REQUIRED[1] . "\r\n";

    expect(HtaccessRules::missing($file))->toBe(array_slice(HtaccessRules::REQUIRED, 1));
});

test('the dashboard and make:site report it', function () {
    expect(htaccessRulesFile('manager/includes/config_check.inc.php'))
        ->toContain('HtaccessRules::missing(')
        ->toContain("_hide_configcheck_htaccess_rules")
        ->toContain("\$_lang['configcheck_htaccess_rules_msg']")
        ->and(htaccessRulesFile('core/lang/en/global.php'))
        ->toContain('$_lang["configcheck_htaccess_rules"]')
        ->toContain('$_lang["configcheck_htaccess_rules_msg"]')
        ->and(htaccessRulesFile('core/src/Console/SiteUpdateCommand.php'))
        ->toContain('$this->warnAboutHtaccess();')
        ->toContain('HtaccessRules::missing(');
});
