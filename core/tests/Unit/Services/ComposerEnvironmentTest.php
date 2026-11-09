<?php

use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Util\Platform;
use EvolutionCMS\Console\SiteUpdateCommand;
use EvolutionCMS\Services\ComposerEnvironment;

beforeEach(function () {
    $this->workspace = sys_get_temp_dir() . '/evo-composer-auth-' . bin2hex(random_bytes(8));
    $this->core = $this->workspace . '/site/core';
    $this->archive = $this->workspace . '/unpacked/core';
    $this->home = $this->workspace . '/global';
    foreach ([$this->core . '/composer', $this->archive, $this->home] as $directory) {
        mkdir($directory, 0777, true);
    }
    foreach ([$this->core, $this->archive] as $directory) {
        file_put_contents($directory . '/composer.json', '{"name":"test/update","require":{}}');
    }

    $this->environment = [];
    foreach (['COMPOSER_HOME', 'COMPOSER_AUTH', 'HOME', 'APPDATA', 'XDG_CONFIG_HOME', 'XDG_CACHE_HOME', 'XDG_DATA_HOME'] as $name) {
        $this->environment[$name] = [getenv($name), array_intersect_key($_SERVER, [$name => true]), array_intersect_key($_ENV, [$name => true])];
    }
    Platform::putEnv('COMPOSER_HOME', $this->home);
    Platform::clearEnv('COMPOSER_AUTH');
});

afterEach(function () {
    foreach ($this->environment as $name => [$value, $server, $env]) {
        $value === false ? putenv($name) : putenv($name . '=' . $value);
        unset($_SERVER[$name], $_ENV[$name]);
        $_SERVER += $server;
        $_ENV += $env;
    }
    SiteUpdateCommand::rmdirs($this->workspace);
});

test('the unpacked core and the installed core receive the same global project and environment credentials', function () {
    file_put_contents($this->home . '/auth.json', json_encode(['http-basic' => [
        'global.example' => ['username' => 'global', 'password' => 'global-secret'],
        'shared.example' => ['username' => 'old', 'password' => 'old-secret'],
    ]]));
    $project = json_encode(['http-basic' => [
        'project.example' => ['username' => 'project', 'password' => 'project-secret'],
        'shared.example' => ['username' => 'project', 'password' => 'project-secret'],
    ], 'custom-headers' => ['headers.example' => ['X-Token: header-secret']]]);
    file_put_contents($this->core . '/auth.json', $project);
    $original = json_encode(['http-basic' => [
        'shared.example' => ['username' => 'environment', 'password' => 'environment-secret'],
    ], 'bearer' => ['token.example' => 'environment-token']]);
    Platform::putEnv('COMPOSER_AUTH', $original);

    $result = (new ComposerEnvironment($this->core))->run(function () {
        $configs = [];
        foreach ([$this->archive, $this->core] as $directory) {
            // Use Composer's own project loader, with plugins/scripts disabled and no dependency resolution.
            $configs[] = (new Factory())->createComposer(new NullIO(), $directory . '/composer.json', true, $directory, false)->getConfig();
        }
        foreach ($configs as $config) {
            expect($config->get('home'))->toBe($this->home)
                ->and($config->get('http-basic'))->toBe([
                    'global.example' => ['username' => 'global', 'password' => 'global-secret'],
                    'shared.example' => ['username' => 'environment', 'password' => 'environment-secret'],
                    'project.example' => ['username' => 'project', 'password' => 'project-secret'],
                ])
                ->and($config->get('bearer'))->toBe(['token.example' => 'environment-token'])
                ->and($config->get('custom-headers'))->toBe(['headers.example' => ['X-Token: header-secret']]);
        }
        expect(file_exists($this->archive . '/auth.json'))->toBeFalse();

        return 'update-result';
    });

    expect($result)->toBe('update-result')
        ->and(getenv('COMPOSER_HOME'))->toBe($this->home)
        ->and(getenv('COMPOSER_AUTH'))->toBe($original)
        ->and($_SERVER['COMPOSER_AUTH'])->toBe($original)
        ->and(file_get_contents($this->core . '/auth.json'))->toBe($project)
        ->and(file_exists($this->archive . '/auth.json'))->toBeFalse();
});

test('legacy site credentials remain available without overriding the normal global or project credentials', function () {
    file_put_contents($this->core . '/composer/auth.json', json_encode(['http-basic' => [
        'legacy.example' => ['username' => 'legacy', 'password' => 'legacy-secret'],
        'shared.example' => ['username' => 'legacy', 'password' => 'legacy-secret'],
    ]]));
    file_put_contents($this->home . '/auth.json', json_encode(['http-basic' => [
        'shared.example' => ['username' => 'global', 'password' => 'global-secret'],
    ]]));

    (new ComposerEnvironment($this->core))->run(function () {
        $auth = json_decode(getenv('COMPOSER_AUTH'), true, flags: JSON_THROW_ON_ERROR);
        expect($auth['http-basic'])->toBe([
            'legacy.example' => ['username' => 'legacy', 'password' => 'legacy-secret'],
            'shared.example' => ['username' => 'global', 'password' => 'global-secret'],
        ])->and(getenv('COMPOSER_HOME'))->toBe($this->home);
    });
});

test('project authentication overrides global authentication when there is no environment override', function () {
    file_put_contents($this->home . '/config.json', '{"config":{"bearer":{"shared.example":"global-config-token"}}}');
    file_put_contents($this->home . '/auth.json', '{"bearer":{"shared.example":"global-auth-token","global.example":"global-token"}}');
    file_put_contents($this->core . '/auth.json', '{"bearer":{"shared.example":"project-token"}}');

    (new ComposerEnvironment($this->core))->run(function () {
        expect(json_decode(getenv('COMPOSER_AUTH'), true)['bearer'])->toBe([
            'shared.example' => 'project-token',
            'global.example' => 'global-token',
        ]);
    });
});

test('startUpdate scopes authentication even with an authoritative classmap that predates the helper', function () {
    file_put_contents($this->core . '/auth.json', '{"bearer":{"project.example":"project-token"}}');
    $probe = $this->workspace . '/update.php';
    file_put_contents($probe, '<?php $loader = require ' . var_export(dirname(__DIR__, 3) . '/vendor/autoload.php', true) . ';'
        . ' $map = $loader->getClassMap(); unset($map[\\EvolutionCMS\\Services\\ComposerEnvironment::class]);'
        . ' $loader->unregister(); $oldLoader = new \\Composer\\Autoload\\ClassLoader();'
        . ' $oldLoader->addClassMap($map); $oldLoader->setClassMapAuthoritative(true); $oldLoader->register();'
        . ' define("EVO_CORE_PATH", ' . var_export($this->core . '/', true) . ');'
        . ' $command = new class extends \\EvolutionCMS\\Console\\SiteUpdateCommand {'
        . ' protected function applyUpdate() {'
        . ' $auth = json_decode(getenv("COMPOSER_AUTH"), true);'
        . ' if (($auth["bearer"]["project.example"] ?? null) !== "project-token") { throw new \\RuntimeException("Missing project auth"); }'
        . ' return false; } };'
        . ' $result = $command->startUpdate();'
        . ' echo json_encode([$result, getenv("COMPOSER_AUTH"), getenv("COMPOSER_HOME")]);');

    expect(json_decode(evoRunPhp($probe), true, flags: JSON_THROW_ON_ERROR))
        ->toBe([false, false, $this->home]);
});

test('implicit Composer home is resolved by Composer rather than replaced by core composer', function () {
    Platform::clearEnv('COMPOSER_HOME');
    Platform::putEnv(Platform::isWindows() ? 'APPDATA' : 'HOME', $this->workspace . '/user');
    Platform::putEnv('XDG_CONFIG_HOME', $this->workspace . '/xdg');
    $home = Factory::createConfig(new NullIO())->get('home');
    file_put_contents($home . '/auth.json', '{"bearer":{"implicit.example":"implicit-token"}}');

    (new ComposerEnvironment($this->core))->run(function () use ($home) {
        expect(getenv('COMPOSER_HOME'))->toBe($home)
            ->and(json_decode(getenv('COMPOSER_AUTH'), true)['bearer'])->toBe(['implicit.example' => 'implicit-token']);
    });

    expect(getenv('COMPOSER_HOME'))->toBeFalse();
});

test('an environment without a user home retains the site local Composer home fallback', function () {
    foreach (['COMPOSER_HOME', 'HOME', 'APPDATA'] as $name) {
        Platform::clearEnv($name);
    }
    file_put_contents($this->core . '/composer/auth.json', '{"bearer":{"legacy.example":"legacy-token"}}');

    (new ComposerEnvironment($this->core))->run(function () {
        expect(getenv('COMPOSER_HOME'))->toBe($this->core . '/composer')
            ->and(Factory::createConfig(new NullIO())->get('bearer'))->toBe(['legacy.example' => 'legacy-token']);
    });

    expect(getenv('COMPOSER_HOME'))->toBeFalse()
        ->and(getenv('COMPOSER_AUTH'))->toBeFalse()
        ->and(array_key_exists('COMPOSER_HOME', $_SERVER))->toBeFalse();
});

test('the environment inherited by child processes is restored after a failure', function (bool $throws) {
    // A web server or dotenv may set runtime globals without exporting them to subprocesses.
    putenv('COMPOSER_HOME');
    $_SERVER['COMPOSER_HOME'] = $this->home;
    $_ENV['COMPOSER_HOME'] = 'distinct-env-home';
    $auth = '{"bearer":{"env.example":"synthetic-token"}}';
    putenv('COMPOSER_AUTH=');
    $_SERVER['COMPOSER_AUTH'] = $auth;
    unset($_ENV['COMPOSER_AUTH']);

    try {
        $result = (new ComposerEnvironment($this->core))->run(function () use ($throws) {
            $probe = $this->workspace . '/child.php';
            file_put_contents($probe, '<?php echo json_encode([getenv("COMPOSER_HOME"), json_decode(getenv("COMPOSER_AUTH"), true)]);');
            $child = json_decode(evoRunPhp($probe), true, flags: JSON_THROW_ON_ERROR);
            expect($child)->toBe([$this->home, ['bearer' => ['env.example' => 'synthetic-token']]]);
            if ($throws) {
                throw new RuntimeException('Synthetic update failure.');
            }

            return false;
        });
        expect($throws)->toBeFalse()->and($result)->toBeFalse();
    } catch (RuntimeException $exception) {
        expect($throws)->toBeTrue()->and($exception->getMessage())->toBe('Synthetic update failure.');
    }

    expect(getenv('COMPOSER_HOME'))->toBeFalse()
        ->and($_SERVER['COMPOSER_HOME'])->toBe($this->home)
        ->and($_ENV['COMPOSER_HOME'])->toBe('distinct-env-home')
        ->and(getenv('COMPOSER_AUTH'))->toBe('')
        ->and($_SERVER['COMPOSER_AUTH'])->toBe($auth)
        ->and(array_key_exists('COMPOSER_AUTH', $_ENV))->toBeFalse();
})->with([false, true]);

test('invalid authentication is refused without printing credentials or running the update', function (string $source) {
    $secret = 'synthetic-secret-that-must-not-be-printed';
    $invalid = '{"http-basic":{"repo.example":{"password":"' . $secret . '"} BROKEN';
    if ($source === 'COMPOSER_AUTH') {
        Platform::putEnv($source, $invalid);
    } else {
        file_put_contents($source === 'global' ? $this->home . '/auth.json' : $this->core . '/' . $source, $invalid);
    }

    $ran = false;
    $before = getenv('COMPOSER_AUTH');
    try {
        (new ComposerEnvironment($this->core))->run(function () use (&$ran) {
            $ran = true;
        });
        $this->fail('Invalid authentication must stop the update.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())->toContain('Composer')->not->toContain($secret)
            ->and($exception->getPrevious())->toBeNull();
    }

    expect($ran)->toBeFalse()
        ->and(getenv('COMPOSER_AUTH'))->toBe($before)
        ->and(getenv('COMPOSER_HOME'))->toBe($this->home)
        ->and(file_exists($this->archive . '/auth.json'))->toBeFalse();
})->with(['auth.json', 'composer/auth.json', 'global', 'COMPOSER_AUTH']);
