<?php

it('fixes reported Ukrainian manager localization typos and escapes', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/uk/global.php';

    expect($_lang['configcheck_cache_msg'])
        ->toContain('<b>/assets/cache</b>')
        ->and($_lang['doc_data_title'])
        ->toBe('Огляд ресурсу')
        ->and($_lang['files_dirwritable'])
        ->toBe('Чи дозволено запис у папку?')
        ->and($_lang['files_filetype_notok'])
        ->toBe('Завантаження файлів такого типу заборонене')
        ->and($_lang['confirm_publish'])
        ->not->toContain('\\П')
        ->and($_lang['confirm_unpublish'])
        ->not->toContain('\\П')
        ->and($_lang['access_permissions_docs_collision'])
        ->toStartWith('Оскільки')
        ->and($_lang['error_sending_email'])
        ->toBe('Помилка відправки e-mail');
});

it('fixes reported Russian manager localization typos and escaping', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/ru/global.php';

    expect($_lang['duplicate_name_found_general'])
        ->toContain('Пожалуйста')
        ->and($_lang['duplicate_name_found_module'])
        ->toContain('Пожалуйста')
        ->and($_lang['files_management_no_permission'])
        ->toStartWith('У вас недостаточно прав')
        ->and($_lang['access_permissions_links_tab'])
        ->toContain('могут создавать и редактировать')
        ->and($_lang['access_permissions_resources_tab'])
        ->toContain('Также здесь')
        ->and($_lang['access_permissions_users_tab'])
        ->toContain('Также здесь')
        ->and($_lang['files_upload_permissions_error'])
        ->toContain('недоступна для записи')
        ->and($_lang['confirm_reset_sort_order'])
        ->toContain("'sort order/index'")
        ->not->toContain('\\"sort order/index\\"');
});

it('fixes reported English manager localization typos and grammar', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/en/global.php';

    expect($_lang['login_logo_message'])
        ->toStartWith('Recommended')
        ->and($_lang['login_bg_message'])
        ->toStartWith('Recommended')
        ->and($_lang['invalid_event_response'])
        ->toBe('The %s event has invalid output')
        ->and($_lang['access_permissions_resources_tab'])
        ->toContain('see its name')
        ->and($_lang['enable_sharedparams_msg'])
        ->toContain('accessing its shared parameters')
        ->and($_lang['configcheck_templateswitcher_present_msg'])
        ->toContain('only if the functionality is required');
});
