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

test('update skips root GitHub workflows and keeps the site configuration intact', function (bool $siteHasGithub) {
    mkdir($this->archive . '/.github/workflows', 0777, true);
    file_put_contents($this->archive . '/.github/workflows/ci.yml', 'CMS workflow');
    file_put_contents($this->archive . '/.github/workflows/build.yml', 'another CMS workflow');
    mkdir($this->archive . '/assets/.github', 0777, true);
    file_put_contents($this->archive . '/assets/.github/example.txt', 'nested archive file');
    if ($siteHasGithub) {
        mkdir($this->site . '/.github/workflows', 0777, true);
        file_put_contents($this->site . '/.github/workflows/ci.yml', 'site workflow');
        file_put_contents($this->site . '/.github/workflows/deploy.yml', 'site deployment');
    }

    SiteUpdateCommand::moveFiles($this->archive, $this->site);

    expect(is_dir($this->site . '/.github'))->toBe($siteHasGithub)
        ->and(file_exists($this->site . '/.github/workflows/build.yml'))->toBeFalse()
        ->and(file_get_contents($this->site . '/assets/.github/example.txt'))->toBe('nested archive file');
    if ($siteHasGithub) {
        expect(file_get_contents($this->site . '/.github/workflows/ci.yml'))->toBe('site workflow')
            ->and(file_get_contents($this->site . '/.github/workflows/deploy.yml'))->toBe('site deployment');
    }
})->with([
    'site with its own workflows' => [true],
    'site without GitHub configuration' => [false],
]);

test('update skips development files and config examples from older archives and preserves site copies', function () {
    $excluded = [
        '.dockerignore', 'AGENTS.md', 'SECURITY.md', 'docs/notes.md', 'phpstan.neon', 'publiccode.yml',
        'tools/build-manager-icons.php', 'core/phpunit.xml', 'core/tests/LocalTest.php',
        'core/custom/.env.docker.example', 'core/custom/.env.example',
        'core/custom/define.php.example', 'core/custom/routes.php.example',
        'core/custom/config/cms/settings/ControllerNamespace.php.example',
        'core/custom/config/cms/settings.php.example',
    ];
    $included = ['assets/docs/license.txt', 'assets/AGENTS.md', 'tools/site-helper.php'];
    foreach ([$this->archive, $this->site] as $root) {
        foreach ($excluded as $path) {
            $file = $root . '/' . $path;
            if (!is_dir(dirname($file))) {
                mkdir(dirname($file), 0777, true);
            }
            file_put_contents($file, $root === $this->archive ? 'CMS file' : 'site file');
        }
    }
    foreach (array_merge($included, ['docs/cms-only.md', 'core/tests/CmsTest.php']) as $path) {
        $file = $this->archive . '/' . $path;
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, 'CMS file');
    }

    $fresh = $this->workspace . '/fresh';
    mkdir($fresh);
    SiteUpdateCommand::moveFiles($this->archive, $this->site);

    foreach ($excluded as $path) {
        expect(file_get_contents($this->site . '/' . $path))->toBe('site file')
            ->and(is_file($this->archive . '/' . $path))->toBeTrue();
    }
    foreach ($included as $path) {
        expect(file_get_contents($this->site . '/' . $path))->toBe('CMS file');
    }
    expect(is_file($this->site . '/docs/cms-only.md'))->toBeFalse()
        ->and(is_file($this->site . '/core/tests/CmsTest.php'))->toBeFalse();

    SiteUpdateCommand::moveFiles($this->archive, $fresh);
    foreach (array_merge($excluded, ['docs', 'core/tests']) as $path) {
        expect(file_exists($fresh . '/' . $path))->toBeFalse();
    }
});

test('updater passes the configured Manager path into the archive overlay', function () {
    $source = file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect($source)->toContain("self::moveFiles(\$temp_dir . '/' . \$dir, EVO_BASE_PATH, EVO_MANAGER_PATH);");
});
