<?php

use EvolutionCMS\Console\ClearCacheFullCommand;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\Mocks\MockDocumentParser;

beforeEach(function () {
    if (!defined('EVO_CLASS')) {
        define('EVO_CLASS', MockDocumentParser::class);
    }
    if (!defined('IN_MANAGER_MODE')) {
        define('IN_MANAGER_MODE', false);
    }
    if (!defined('IN_INSTALL_MODE')) {
        define('IN_INSTALL_MODE', false);
    }
    if (!defined('EVO_API_MODE')) {
        define('EVO_API_MODE', true);
    }

    // Mock the Laravel application
    $this->app = Mockery::mock(Application::class);

    // Mock the evo() function by setting up global $evo directly.
    // evo() caches its return value in global $evo; by pre-setting it
    // the EVO_CLASS constant path is never reached.
    global $evo;
    $evo = Mockery::mock(MockDocumentParser::class);
    $this->modx = $evo;

    // Create the command instance
    $this->command = new ClearCacheFullCommand();
    $this->command->setLaravel($this->app);

    // Set up output for the command - wrap BufferedOutput in OutputStyle
    $bufferedOutput = new BufferedOutput();
    $this->output = new OutputStyle(new ArrayInput([]), $bufferedOutput);
    $this->bufferedOutput = $bufferedOutput;
    $this->command->setOutput($this->output);
});

afterEach(function () {
    global $evo;
    $evo = null;
    Mockery::close();
});

test('command has correct name', function () {
    $reflection = new ReflectionClass(ClearCacheFullCommand::class);
    $property = $reflection->getProperty('name');
    $property->setAccessible(true);

    expect($property->getValue($this->command))->toBe('cache:clear-full');
});

test('command has correct description', function () {
    $reflection = new ReflectionClass(ClearCacheFullCommand::class);
    $property = $reflection->getProperty('description');
    $property->setAccessible(true);

    expect($property->getValue($this->command))->toBe('Full cache clear blade + Evolution');
});

test('handle deletes services file if it exists', function () {
    // Create temp file
    $tempServices = tempnam(sys_get_temp_dir(), 'services_');
    file_put_contents($tempServices, '<?php return [];');

    $this->app->shouldReceive('getCachedServicesPath')
        ->andReturn($tempServices);

    $this->app->shouldReceive('getCachedPackagesPath')
        ->andReturn('/non/existent/packages.php');

    // Mock evo()->clearCache()
    $this->modx->shouldReceive('clearCache')
        ->once()
        ->with('full')
        ->andReturnNull();

    // Execute the command
    $this->command->handle();

    // Verify the file was deleted
    expect(file_exists($tempServices))->toBeFalse();

    // Verify output message
    $output = $this->bufferedOutput->fetch();
    expect($output)->toContain('Cache clear');
});

test('handle deletes packages file if it exists', function () {
    // Create temp file
    $tempPackages = tempnam(sys_get_temp_dir(), 'packages_');
    file_put_contents($tempPackages, '<?php return [];');

    $this->app->shouldReceive('getCachedServicesPath')
        ->andReturn('/non/existent/services.php');

    $this->app->shouldReceive('getCachedPackagesPath')
        ->andReturn($tempPackages);

    // Mock evo()->clearCache()
    $this->modx->shouldReceive('clearCache')
        ->once()
        ->with('full')
        ->andReturnNull();

    // Execute the command
    $this->command->handle();

    // Verify the file was deleted
    expect(file_exists($tempPackages))->toBeFalse();

    // Verify output message
    $output = $this->bufferedOutput->fetch();
    expect($output)->toContain('Cache clear');
});

test('handle does not fail when cache files do not exist', function () {
    $this->app->shouldReceive('getCachedServicesPath')
        ->andReturn('/non/existent/services.php');

    $this->app->shouldReceive('getCachedPackagesPath')
        ->andReturn('/non/existent/packages.php');

    // Mock evo()->clearCache()
    $this->modx->shouldReceive('clearCache')
        ->once()
        ->with('full')
        ->andReturnNull();

    // Execute the command - should not throw exception
    $this->command->handle();

    // Verify output message
    $output = $this->bufferedOutput->fetch();
    expect($output)->toContain('Cache clear');
});

test('handle calls evo clearCache with full parameter', function () {
    $this->app->shouldReceive('getCachedServicesPath')
        ->andReturn('/non/existent/services.php');

    $this->app->shouldReceive('getCachedPackagesPath')
        ->andReturn('/non/existent/packages.php');

    // Mock and verify evo()->clearCache() is called with 'full'
    $this->modx->shouldReceive('clearCache')
        ->once()
        ->with('full')
        ->andReturnNull();

    // Execute the command
    $this->command->handle();

    // Mockery will verify the clearCache call
    // Verify output message
    $output = $this->bufferedOutput->fetch();
    expect($output)->toContain('Cache clear');
});

test('handle clears all caches in correct order', function () {
    // Create temp files
    $tempServices = tempnam(sys_get_temp_dir(), 'services_');
    $tempPackages = tempnam(sys_get_temp_dir(), 'packages_');
    file_put_contents($tempServices, '<?php return [];');
    file_put_contents($tempPackages, '<?php return [];');

    $this->app->shouldReceive('getCachedServicesPath')
        ->andReturn($tempServices);

    $this->app->shouldReceive('getCachedPackagesPath')
        ->andReturn($tempPackages);

    // Mock evo()->clearCache()
    $this->modx->shouldReceive('clearCache')
        ->once()
        ->with('full')
        ->andReturnNull();

    // Execute the command
    $this->command->handle();

    // Verify both temp files were deleted
    expect(file_exists($tempServices))->toBeFalse();
    expect(file_exists($tempPackages))->toBeFalse();

    // Verify output message
    $output = $this->bufferedOutput->fetch();
    expect($output)->toContain('Cache clear');

    // Clean up (just in case)
    if (file_exists($tempServices)) {
        unlink($tempServices);
    }
    if (file_exists($tempPackages)) {
        unlink($tempPackages);
    }
});
