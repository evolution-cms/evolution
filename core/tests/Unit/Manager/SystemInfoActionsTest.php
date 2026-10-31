<?php

use EvolutionCMS\Controllers\SystemInfo;
use Symfony\Component\HttpKernel\Exception\HttpException;

function systemInfoResetFixture(bool $allowed = true, bool $available = true, bool $success = true): SystemInfo
{
    $core = Mockery::mock(\EvolutionCMS\Interfaces\CoreInterface::class);
    $core->shouldReceive('getDatabase')->andReturn(Mockery::mock(\EvolutionCMS\Database::class));
    $theme = Mockery::mock(\EvolutionCMS\Interfaces\ManagerThemeInterface::class);
    $theme->shouldReceive('getCore')->andReturn($core);
    $controller = new class($theme) extends SystemInfo {
        public int $resets = 0;

        public bool $allowed = true;
        public bool $available = true;
        public bool $success = true;
        public function canView(): bool { return $this->allowed; }
        protected function isOpcacheEnabled(): bool { return true; }
        protected function canUseOpcacheApi(): bool { return $this->available; }
        protected function performOpcacheReset(): bool
        {
            $this->resets++;
            return $this->success;
        }
    };
    $controller->allowed = $allowed;
    $controller->available = $available;
    $controller->success = $success;
    return $controller;
}

test('OPcache reset refuses a manager without the settings right', function () {
    $controller = systemInfoResetFixture(false);

    expect(fn () => $controller->resetOpcache())->toThrow(HttpException::class)
        ->and($controller->resets)->toBe(0);
});

test('OPcache reset reports unavailable without calling the restricted API', function () {
    $controller = systemInfoResetFixture(true, false);

    expect($controller->resetOpcache())->toBe('opcache_reset_unavailable')
        ->and($controller->resets)->toBe(0);
});

test('OPcache reset returns the actual API result for button feedback', function (bool $success, string $key) {
    if (!function_exists('opcache_reset')) {
        \PHPUnit\Framework\Assert::markTestSkipped('OPcache is not installed on this runtime');
    }
    $controller = systemInfoResetFixture(true, true, $success);

    expect($controller->resetOpcache())->toBe($key)
        ->and($controller->resets)->toBe(1);
})->with([
    [true, 'opcache_reset_complete'],
    [false, 'opcache_reset_failed'],
]);

test('opening and closing PHP Info does not reset OPcache', function () {
    $controller = systemInfoResetFixture();
    $component = new class($controller) extends \EvolutionCMS\Livewire\SystemInfo {
        public function __construct(private SystemInfo $fixture) {}
        protected function controller(): SystemInfo { return $this->fixture; }
    };

    expect($component->phpInfoOpen)->toBeFalse();
    $component->openPhpInfo('evolightness');
    expect($component->phpInfoOpen)->toBeTrue()
        ->and($component->phpInfoTheme)->toBe('evolightness');
    $component->closeModal();
    expect($component->phpInfoOpen)->toBeFalse()
        ->and($controller->resets)->toBe(0);
});

function systemInfoWorkerFixture(string $sapi, array $environment): SystemInfo
{
    $core = Mockery::mock(\EvolutionCMS\Interfaces\CoreInterface::class);
    $core->shouldReceive('getDatabase')->andReturn(Mockery::mock(\EvolutionCMS\Database::class));
    $theme = Mockery::mock(\EvolutionCMS\Interfaces\ManagerThemeInterface::class);
    $theme->shouldReceive('getCore')->andReturn($core);
    $theme->shouldReceive('getLexicon')->andReturnUsing(fn (string $key) => $key === 'sysinfo_worker_recycle' ? '%s requests' : $key);
    $controller = new class($theme) extends SystemInfo {
        public string $sapi = 'cli';
        public array $environment = [];
        protected function serverApi(): string { return $this->sapi; }
        protected function environment(string $name): string|false { return $this->environment[$name] ?? false; }
        public function row(): array { return $this->workerModeRow(); }
    };
    $controller->sapi = $sapi;
    $controller->environment = $environment;
    return $controller;
}

test('worker mode hint follows the server API and the worker environment', function (string $sapi, array $environment, bool $enabled, array $hints) {
    expect(systemInfoWorkerFixture($sapi, $environment)->workerModeInfo())
        ->toBe(['enabled' => $enabled, 'hints' => $hints]);
})->with([
    'apache' => ['apache2handler', [], false, [['sysinfo_worker_unavailable']]],
    'fpm with the variable set' => ['fpm-fcgi', ['EVO_WORKER_MODE' => '1', 'EVO_WORKER_ORIGIN' => 'http://a'], false, [['sysinfo_worker_ignored']]],
    'frankenphp, not requested' => ['frankenphp', ['EVO_WORKER_MODE' => '0'], false, [['sysinfo_worker_available']]],
    'frankenphp without origin' => ['frankenphp', ['EVO_WORKER_MODE' => '1', 'EVO_WORKER_ORIGIN' => ' '], false, [['sysinfo_worker_no_origin']]],
    'frankenphp, new application' => ['frankenphp', ['EVO_WORKER_MODE' => '1', 'EVO_WORKER_ORIGIN' => 'http://a'], true,
        [['sysinfo_worker_new_app'], ['sysinfo_worker_recycle', '500']]],
    'frankenphp, kept application' => ['frankenphp', ['EVO_WORKER_MODE' => '1', 'EVO_WORKER_ORIGIN' => 'http://a',
        'EVO_WORKER_KEEP_APP' => '1', 'EVO_WORKER_MAX_REQUESTS' => '20'], true,
        [['sysinfo_worker_kept_app'], ['sysinfo_worker_recycle', '20']]],
]);

test('worker mode row renders the status with its hints for both manager themes', function () {
    $row = systemInfoWorkerFixture('frankenphp', ['EVO_WORKER_MODE' => '1', 'EVO_WORKER_ORIGIN' => 'http://a',
        'EVO_WORKER_KEEP_APP' => '1', 'EVO_WORKER_MAX_REQUESTS' => '20'])->row();

    expect($row)->toBe([
        'is_lexicon' => true,
        'data' => 'enabled (sysinfo_worker_kept_app, 20 requests)',
        'status' => true,
        'details' => '(sysinfo_worker_kept_app, 20 requests)',
    ]);
});
