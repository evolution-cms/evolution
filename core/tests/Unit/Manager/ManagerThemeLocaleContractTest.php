<?php

it('applies the ISO locale provided by the selected manager language file', function () {
    $managerTheme = file_get_contents(dirname(__DIR__, 3) . '/src/ManagerTheme.php');

    $selectedLanguagePosition = strpos(
        $managerTheme,
        "include EVO_CORE_PATH . 'lang/' . \$lang . '/global.php';"
    );
    $localePosition = strpos($managerTheme, 'app()->setLocale($modx_lang_attribute);');

    expect($selectedLanguagePosition)
        ->not->toBeFalse()
        ->and($localePosition)
        ->not->toBeFalse()
        ->and($selectedLanguagePosition)
        ->toBeLessThan($localePosition)
        ->and($managerTheme)
        ->toContain('$this->lang = $modx_lang_attribute;')
        ->not->toContain('$evo_lang_attribute = $this->getLang();');
});
