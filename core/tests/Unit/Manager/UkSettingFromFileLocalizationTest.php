<?php

it('uses Ukrainian text for manager setting notices', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/uk/global.php';

    expect($_lang['setting_from_file'])
        ->toContain('Значення параметра задано')
        ->not->toContain('Значение параметра задано')
        ->and($_lang['database_overhead'])
        ->toContain('Примітка:</b> надлишок')
        ->toContain('невикористаний простір')
        ->not->toContain('перевитрата')
        ->not->toContain('неиспользуемое')
        ->and($_lang['database_table_overhead'])
        ->toBe('Надлишок')
        ->and($_lang['friendly_alias_message'])
        ->toContain('буде наступною')
        ->toContain('ID ресурсу')
        ->not->toContain('следующий')
        ->not->toContain('ID ресурса')
        ->and($_lang['rb_webuser_message'])
        ->toContain('<b>Попередження:</b>')
        ->not->toContain('<B> Попередження: </b>')
        ->and($_lang['settings_strip_image_paths_title'])
        ->toBe('Обрізати шляхи до зображень')
        ->and($_lang['settings_strip_image_paths_message'])
        ->toContain('Якщо цей параметр увімкнено')
        ->not->toContain('Если')
        ->and($_lang['snippet_management_msg'])
        ->toContain('Сніпети дозволяють')
        ->toContain('виконання сніпета')
        ->not->toContain('Сніппети')
        ->not->toContain('сниппета')
        ->and($_lang['use_alias_path_message'])
        ->toContain('<b>Увага:</b>')
        ->not->toContain('< / b>')
        ->and($_lang['enable_bindings_message'])
        ->toContain('виводу')
        ->toContain('показано')
        ->not->toContain('воводу')
        ->not->toContain('показно')
        ->and($_lang['setting_resource_tree_node_name_desc_add'])
        ->toContain('за &quot;замовчуванням&quot;')
        ->not->toContain('за& &quot;')
        ->and($_lang['aliaslistingfolder_title'])
        ->toBe('Використовувати AliasListing тільки для тек')
        ->and($_lang['error_double_action'])
        ->toContain('GET &amp; POST')
        ->not->toContain('GET & POST')
        ->and($_lang['email_sender_method_message'])
        ->toContain('адреса, на яку буде відправлено відмову')
        ->not->toContain('якиу')
        ->and($_lang['websignupemail_message'])
        ->toContain('[+uid+] та [+pwd+]')
        ->not->toContain('[+ pwd +]')
        ->and($_lang['rb_message'])
        ->toContain('Виберіть \'Так\', щоб включити браузер файлів')
        ->not->toContain('\\ `Так \\');
});

it('keeps SQL backup overhead translations unique and avoids stale mixed-language wording', function () {
    $languageFiles = glob(dirname(__DIR__, 3) . '/lang/*/global.php') ?: [];

    foreach ($languageFiles as $languageFile) {
        $source = (string) file_get_contents($languageFile);

        expect(substr_count($source, '$_lang["database_overhead"]'))->toBe(1);
        expect(substr_count($source, '$_lang["database_table_overhead"]'))->toBe(1);
    }

    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/uk/global.php';

    expect($_lang['database_overhead'])
        ->not->toContain('перевитрата')
        ->not->toContain('неиспользуемое');

    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/ru/global.php';

    expect($_lang['database_overhead'])
        ->not->toContain('перерасход');
});
