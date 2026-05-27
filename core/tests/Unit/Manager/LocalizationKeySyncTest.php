<?php

it('keeps reported Ukrainian and Russian manager localization keys in sync', function () {
    $locales = [];
    foreach (['en', 'ru', 'uk'] as $locale) {
        $_lang = [];
        include dirname(__DIR__, 3) . '/lang/' . $locale . '/global.php';
        $locales[$locale] = $_lang;
    }

    foreach (['disable', 'enable'] as $key) {
        expect($locales['ru'])
            ->toHaveKey($key)
            ->and($locales['uk'])
            ->toHaveKey($key);
    }

    foreach (['permission_title', 'groups_permission_title', 'lang_key_desc', 'key_desc'] as $key) {
        expect($locales['uk'])->toHaveKey($key);
    }

    expect($locales['ru'])
        ->toHaveKey('chunk_processor')
        ->and($locales['uk']['permission_title'])
        ->toBe('Створити / редагувати право доступу')
        ->and($locales['ru']['disable'])
        ->toBe('Отключить')
        ->and($locales['uk']['enable'])
        ->toBe('Увімкнути');
});

it('removes Ukrainian-only SEO indexing keys that are not used by the manager', function () {
    $_lang = [];
    include dirname(__DIR__, 3) . '/lang/uk/global.php';

    foreach ([
        'site_indexing_title',
        'site_indexing_message',
        'ignore',
        'indexing_is_allowed',
        'indexing_is_prohibited',
    ] as $key) {
        expect($_lang)->not->toHaveKey($key);
    }
});
