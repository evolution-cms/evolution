<?php

use EvolutionCMS\Traits\Settings;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;

/**
 * The factory defaults file, read the way a front-end request reads it, with a
 * translator that counts what it is asked: the core's own language files.
 */
beforeEach(function () {
    defined('EVO_CORE_PATH') || define('EVO_CORE_PATH', dirname(__DIR__, 2) . '/');
    defined('EVO_CLASS') || define('EVO_CLASS', Container::class);
    defined('IN_MANAGER_MODE') || define('IN_MANAGER_MODE', false);
    defined('IN_INSTALL_MODE') || define('IN_INSTALL_MODE', false);
    defined('EVO_API_MODE') || define('EVO_API_MODE', true);

    $this->settings = new class extends Container {
        use Settings;

        public array $translated = [];

        public function isBackend(): bool
        {
            return false;
        }

        public function setLocale($locale): void
        {
        }
    };
    $this->settings->instance('config', new Repository([]));
    $translator = new class(new FileLoader(new Filesystem(), EVO_CORE_PATH . 'lang'), 'en') extends Translator {
        public array $asked = [];

        public function get($key, array $replace = [], $locale = null, $fallback = true)
        {
            $this->asked[] = $key;

            return parent::get($key, $replace, $locale, $fallback);
        }
    };
    $this->settings->instance('translator', $translator);
    $this->translator = $translator;

    // __() reaches the translator through app() and evo().
    global $evo;
    $evo = $this->settings;
});

afterEach(function () {
    global $evo;
    $evo = null;
});

test('without known values every default text is translated', function () {
    $defaults = $this->settings->getFactorySettings();

    expect($this->translator->asked)->toHaveCount(8)
        ->and($defaults['emailsubject'])->toBe(__('global.emailsubject_default'))
        ->and($defaults['site_start'])->toBe(1);
});

test('a default the caller already has a value for is not translated', function () {
    $defaults = $this->settings->getFactorySettings(['emailsubject' => 'Hello', 'captcha_words' => 'a,b']);

    expect($defaults['emailsubject'])->toBe('Hello')
        ->and($defaults['captcha_words'])->toBe('a,b')
        ->and($this->translator->asked)->not->toContain('global.emailsubject_default', 'global.captcha_words_default')
        ->and($this->translator->asked)->toHaveCount(6);
});

test('the site cache gets the string defaults the stored settings lack', function () {
    $defaults = $this->settings->getFactoryTextDefaults(['emailsubject' => 'Stored', 'site_name' => 'Mine']);

    expect($defaults)->not->toHaveKeys(['emailsubject', 'site_name'])
        // Not a string: getFactorySettings() keeps the type of these per request.
        ->and($defaults)->not->toHaveKey('site_start')
        ->and($defaults['signupemail_message'])->toBe(__('global.system_email_signup', [], 'en'))
        ->and($defaults['custom_contenttype'])->toBeString()
        ->and(array_filter($defaults, 'is_string'))->toBe($defaults);
});

test('the site cache translates into the stored manager language', function () {
    $german = $this->settings->getFactoryTextDefaults(['manager_language' => 'de']);
    $unknown = $this->settings->getFactoryTextDefaults(['manager_language' => 'xx']);

    expect($german['emailsubject'])->toBe(__('global.emailsubject_default', [], 'de'))
        ->and($unknown['emailsubject'])->toBe(__('global.emailsubject_default', [], 'en'));
});

test('a manager language set in the configuration files wins over the stored one', function () {
    $this->settings['config']->set('cms.settings.manager_language', 'de');

    $defaults = $this->settings->getFactoryTextDefaults(['manager_language' => 'en']);

    expect($defaults['emailsubject'])->toBe(__('global.emailsubject_default', [], 'de'));
});
