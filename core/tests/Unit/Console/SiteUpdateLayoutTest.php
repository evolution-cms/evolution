<?php

use EvolutionCMS\Console\SiteUpdateCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;

beforeEach(function () {
    $this->workspace = sys_get_temp_dir() . '/evo-update-layout-' . bin2hex(random_bytes(8));
    $this->archive = $this->workspace . '/unpacked';
    $this->site = $this->workspace . '/site';
    mkdir($this->archive . '/manager/views/page', 0777, true);
    mkdir($this->archive . '/manager/includes', 0777, true);
    mkdir($this->archive . '/assets/manager', 0777, true);
    mkdir($this->site . '/core', 0777, true);
    file_put_contents($this->archive . '/index.php', 'new front controller');
    file_put_contents($this->archive . '/manager/index.php', 'new manager controller');
    file_put_contents($this->archive . '/manager/views/page/system_settings.blade.php',
        file_get_contents(dirname(__DIR__, 4) . '/manager/views/page/system_settings.blade.php'));
    file_put_contents($this->archive . '/assets/manager/example.js', 'ordinary asset');

    $this->composerEnvironment = [];
    foreach (['COMPOSER_HOME', 'COMPOSER_AUTH'] as $name) {
        $this->composerEnvironment[$name] = getenv($name);
        putenv($name);
    }
});

afterEach(function () {
    foreach ($this->composerEnvironment as $name => $value) {
        $value === false ? putenv($name) : putenv($name . '=' . $value);
    }
    SiteUpdateCommand::rmdirs($this->workspace);
});

test('update overlays the configured Manager and delivers the current CSRF form', function (string $directory) {
    $manager = $this->site . '/' . $directory;
    mkdir($manager . '/views/page', 0777, true);
    file_put_contents($manager . '/views/page/system_settings.blade.php', '<form name="settings"></form>');
    file_put_contents($manager . '/local-plugin.php', 'keep local extension');

    SiteUpdateCommand::moveFiles($this->archive, $this->site, $manager . '/');

    $template = file_get_contents($manager . '/views/page/system_settings.blade.php');
    $compiler = new BladeCompiler(new Filesystem(), $this->workspace . '/compiled');
    expect($template)->toContain('@csrf')
        ->and($compiler->compileString($template))->toContain('csrf_field()')
        ->and(file_get_contents($manager . '/index.php'))->toBe('new manager controller')
        ->and(file_get_contents($manager . '/local-plugin.php'))->toBe('keep local extension')
        ->and(file_get_contents($this->site . '/index.php'))->toBe('new front controller')
        ->and(file_get_contents($this->site . '/assets/manager/example.js'))->toBe('ordinary asset');
    if ($directory !== 'manager') {
        expect(is_dir($this->site . '/manager'))->toBeFalse();
    }
})->with(['manager', 'points', 'private/control-panel']);

test('renamed Manager updates do not write to a leftover default Manager', function () {
    mkdir($this->site . '/points', 0777, true);
    mkdir($this->site . '/manager', 0777, true);
    file_put_contents($this->site . '/manager/index.php', 'leftover default manager');

    SiteUpdateCommand::moveFiles($this->archive, $this->site, $this->site . '/points');

    expect(file_get_contents($this->site . '/points/index.php'))->toBe('new manager controller')
        ->and(file_get_contents($this->site . '/manager/index.php'))->toBe('leftover default manager')
        ->and(is_dir($this->site . '/manager/views'))->toBeFalse();
});

test('Manager paths outside the public site root follow EVO_MANAGER_PATH too', function () {
    $manager = $this->workspace . '/private-manager';
    mkdir($manager, 0777, true);

    SiteUpdateCommand::moveFiles($this->archive, $this->site, $manager);

    expect(file_get_contents($manager . '/index.php'))->toBe('new manager controller')
        ->and(is_dir($this->site . '/manager'))->toBeFalse();
});

test('Manager overlay preserves existing configuration and access-control files', function (string $directory) {
    $manager = $this->site . '/' . $directory;
    mkdir($manager . '/includes', 0777, true);
    foreach (['includes/config.inc.php', '.htaccess', '.user.ini'] as $file) {
        file_put_contents($this->archive . '/manager/' . $file, 'archive default');
        file_put_contents($manager . '/' . $file, 'local configuration');
    }

    SiteUpdateCommand::moveFiles($this->archive, $this->site, $manager);

    foreach (['includes/config.inc.php', '.htaccess', '.user.ini'] as $file) {
        expect(file_get_contents($manager . '/' . $file))->toBe('local configuration');
    }
})->with(['manager', 'points']);

test('Manager overlay installs shipped access-control files when they are absent', function () {
    $manager = $this->site . '/points';
    mkdir($manager, 0777, true);
    file_put_contents($this->archive . '/manager/.htaccess', 'archive access control');

    SiteUpdateCommand::moveFiles($this->archive, $this->site, $manager);

    expect(file_get_contents($manager . '/.htaccess'))->toBe('archive access control');
});

test('repeated Manager overlays update the same configured directory', function () {
    $manager = $this->site . '/points';
    mkdir($manager, 0777, true);
    SiteUpdateCommand::moveFiles($this->archive, $this->site, $manager);
    file_put_contents($this->archive . '/manager/index.php', 'next manager controller');

    SiteUpdateCommand::moveFiles($this->archive, $this->site, $manager);

    expect(file_get_contents($manager . '/index.php'))->toBe('next manager controller')
        ->and(is_dir($this->site . '/manager'))->toBeFalse();
});

test('a missing configured Manager fails before any site file is overwritten', function () {
    file_put_contents($this->site . '/index.php', 'old front controller');

    expect(fn () => SiteUpdateCommand::moveFiles($this->archive, $this->site, $this->site . '/missing'))
        ->toThrow(RuntimeException::class)
        ->and(file_get_contents($this->site . '/index.php'))->toBe('old front controller');
});

test('two-argument moveFiles keeps the ordinary archive layout', function () {
    SiteUpdateCommand::moveFiles($this->archive, $this->site);

    expect(file_get_contents($this->site . '/manager/index.php'))->toBe('new manager controller');
});

test('updater passes the configured Manager path into the archive overlay', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect($source)->toContain("self::moveFiles(\$temp_dir . '/' . \$dir, EVO_BASE_PATH, EVO_MANAGER_PATH);");
});

test('Composer keeps global and explicit authentication while retaining legacy site auth', function (
    bool $legacyAuth,
    string|false $home,
    string|false $auth,
    string|false $expectedHome
) {
    $legacy = $this->workspace . '/legacy-composer';
    mkdir($legacy, 0777, true);
    if ($legacyAuth) {
        file_put_contents($legacy . '/auth.json', '{}');
    }
    if ($home !== false) {
        putenv('COMPOSER_HOME=' . $home);
    }
    if ($auth !== false) {
        putenv('COMPOSER_AUTH=' . $auth);
    }
    $method = new ReflectionMethod(SiteUpdateCommand::class, 'configureComposerHome');

    $method->invoke(new SiteUpdateCommand(), $legacy);

    expect(getenv('COMPOSER_HOME'))->toBe($expectedHome === 'legacy' ? $legacy : $expectedHome)
        ->and(getenv('COMPOSER_AUTH'))->toBe($auth)
        ->and(is_file($legacy . '/auth.json'))->toBe($legacyAuth);
})->with([
    'normal global/XDG lookup' => [false, false, false, false],
    'legacy site auth' => [true, false, false, 'legacy'],
    'explicit home without legacy auth' => [false, '/operator/composer', false, '/operator/composer'],
    'explicit home wins over legacy auth' => [true, '/operator/composer', false, '/operator/composer'],
    'inline auth without legacy auth' => [false, false, '{}', false],
    'inline auth wins over legacy home' => [true, false, '{}', false],
]);

test('a headless worker still has a Composer home without hiding inline auth', function (bool $inlineAuth) {
    $name = windows_os() ? 'APPDATA' : 'HOME';
    $home = getenv($name);
    $legacy = $this->workspace . '/worker-composer';
    putenv($name);
    if ($inlineAuth) {
        putenv('COMPOSER_AUTH={}');
    }

    try {
        (new ReflectionMethod(SiteUpdateCommand::class, 'configureComposerHome'))
            ->invoke(new SiteUpdateCommand(), $legacy);

        expect(getenv('COMPOSER_HOME'))->toBe($legacy)
            ->and(getenv('COMPOSER_AUTH'))->toBe($inlineAuth ? '{}' : false);
    } finally {
        $home === false ? putenv($name) : putenv($name . '=' . $home);
    }
})->with([false, true]);
