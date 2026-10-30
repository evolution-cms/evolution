<?php

use EvolutionCMS\Support\ManagerLanguage;
use Illuminate\Filesystem\Filesystem;

/**
 * Any locale but English may be deleted: a setting that still names it reads as English.
 */
beforeEach(function () {
    $this->langPath = sys_get_temp_dir() . '/evo-manager-language-' . getmypid() . '-' . uniqid();
    foreach (['en', 'de', 'uk'] as $locale) {
        mkdir($this->langPath . '/' . $locale, 0777, true);
        file_put_contents($this->langPath . '/' . $locale . '/global.php', "<?php\n");
    }
    // a folder emptied by hand is not a locale
    mkdir($this->langPath . '/fr');
    file_put_contents($this->langPath . '/README.md', '');
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->langPath);
});

test('only folders with a lexicon are offered, sorted', function () {
    expect(ManagerLanguage::available($this->langPath))->toBe(['de', 'en', 'uk']);
});

test('an installed locale is kept', function () {
    expect(ManagerLanguage::exists('uk', $this->langPath))->toBeTrue()
        ->and(ManagerLanguage::resolve('uk', $this->langPath))->toBe('uk');
});

test('a deleted locale falls back to English', function (mixed $language) {
    expect(ManagerLanguage::exists($language, $this->langPath))->toBeFalse()
        ->and(ManagerLanguage::resolve($language, $this->langPath))->toBe('en');
})->with([
    'deleted' => 'fr',
    'never shipped' => 'xx',
    'legacy name' => 'english',
    'empty' => '',
    'null' => null,
    'not a string' => [['de']],
]);

test('a value that walks out of the language folder is refused', function (string $language) {
    file_put_contents($this->langPath . '/global.php', "<?php\n");

    expect(ManagerLanguage::exists($language, $this->langPath . '/de'))->toBeFalse()
        ->and(ManagerLanguage::resolve($language, $this->langPath . '/de'))->toBe('en');
})->with(['..', '../de', '../../de', 'de/../uk', 'de\\..\\uk']);

test('a deleted locale is listed again once it is restored', function () {
    (new Filesystem())->deleteDirectory($this->langPath . '/de');
    expect(ManagerLanguage::resolve('de', $this->langPath))->toBe('en');

    mkdir($this->langPath . '/de');
    file_put_contents($this->langPath . '/de/global.php', "<?php\n");
    expect(ManagerLanguage::resolve('de', $this->langPath))->toBe('de');
});

test('the core language folder is read by default and holds English', function () {
    expect(ManagerLanguage::available())->toContain('en')
        ->and(ManagerLanguage::exists('en'))->toBeTrue();
});

test('a locale name is checked without the filesystem', function (mixed $language, bool $valid) {
    expect(ManagerLanguage::isName($language))->toBe($valid);
})->with([
    'locale' => ['de', true],
    'deleted locale' => ['xx', true],
    'with region' => ['pt_BR', true],
    'empty' => ['', false],
    'parent' => ['..', false],
    'path' => ['../de', false],
    'backslash path' => ['de\\..\\uk', false],
    'not a string' => [null, false],
]);

test('the manager lexicon falls back to English only when the locale is gone', function (string $language, string $name, string $lang) {
    defined('EVO_CORE_PATH') || define('EVO_CORE_PATH', dirname(__DIR__, 2) . '/');
    defined('MGR_DIR') || define('MGR_DIR', 'manager');
    defined('EVO_CLASS') || define('EVO_CLASS', \Illuminate\Container\Container::class);
    defined('IN_MANAGER_MODE') || define('IN_MANAGER_MODE', false);
    defined('IN_INSTALL_MODE') || define('IN_INSTALL_MODE', false);
    defined('EVO_API_MODE') || define('EVO_API_MODE', true);
    global $evo;
    $previous = $evo;
    $evo = new class {
        public string $locale = '';

        public function setLocale(string $locale): void
        {
            $this->locale = $locale;
        }
    };
    $config = [];
    $core = Mockery::mock(\EvolutionCMS\Core::class);
    $core->shouldReceive('setConfig')->andReturnUsing(function ($key, $value) use (&$config) {
        $config[$key] = $value;
    });
    $theme = (new ReflectionClass(\EvolutionCMS\ManagerTheme::class))->newInstanceWithoutConstructor();
    Closure::bind(function () use ($core) {
        $this->core = $core;
    }, $theme, \EvolutionCMS\ManagerTheme::class)();

    try {
        $loaded = $theme->reloadLang($language);
        expect($loaded)->toBe($name)
            ->and($theme->getLangName())->toBe($name)
            ->and($theme->getLang())->toBe($lang)
            ->and($evo->locale)->toBe($lang)
            ->and($config)->toMatchArray(['manager_language' => $name, 'lang_code' => $lang])
            ->and($theme->getLexicon('yes'))->not->toBe('');
    } finally {
        $evo = $previous;
        Mockery::close();
    }
})->with([
    'installed' => ['de', 'de', 'de'],
    'deleted' => ['xx', 'en', 'en'],
    'legacy name' => ['english', 'en', 'en'],
    'path' => ['../lang/de', 'en', 'en'],
]);

test('a request with an installed locale makes no filesystem check before the include', function () {
    $root = dirname(__DIR__, 3);
    $theme = (string) file_get_contents($root . '/core/src/ManagerTheme.php');
    $loadLang = substr($theme, (int) strpos($theme, 'protected function loadLang'));
    $loadLang = substr($loadLang, 0, (int) strpos($loadLang, "\n    }"));
    $settings = (string) file_get_contents($root . '/core/src/Traits/Settings.php');
    $factory = substr($settings, (int) strpos($settings, 'public function getFactorySettings'));
    $factory = substr($factory, 0, (int) strpos($factory, "\n    }"));

    foreach ([$loadLang, $factory] as $source) {
        expect($source)->toContain('ManagerLanguage::isName(')
            ->and($source)->not->toContain('is_file(')
            ->and($source)->not->toContain('file_exists(')
            ->and($source)->not->toContain('ManagerLanguage::exists(')
            ->and($source)->not->toContain('ManagerLanguage::resolve(')
            ->and($source)->not->toContain('ManagerLanguage::available(');
    }
});

test('every place that reads or offers the manager language resolves it', function () {
    $root = dirname(__DIR__, 3);
    $files = [
        'core/src/ManagerTheme.php' => 'ManagerLanguage::isName($lang)',
        'core/src/Traits/Settings.php' => 'ManagerLanguage::isName($factoryLocale)',
        'core/src/Controllers/SystemSettings.php' => "ManagerLanguage::resolve(\$out['manager_language']",
        'manager/processors/save_settings.processor.php' => 'ManagerLanguage::resolve($v)',
        'manager/actions/mutate_web_user.dynamic.php' => "ManagerLanguage::resolve(\$usersettings['manager_language'])",
    ];

    foreach ($files as $file => $call) {
        $source = (string) file_get_contents($root . '/' . $file);
        expect($source)->toContain($call)
            // the pre-3.0 name is no locale folder: it left manager_language unmatched by any option
            ->and($source)->not->toContain("'english'");
    }
});
