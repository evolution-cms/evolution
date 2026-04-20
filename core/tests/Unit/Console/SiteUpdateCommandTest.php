<?php use EvolutionCMS\Console\SiteUpdateCommand;

beforeEach(function () {
    $this->command = new SiteUpdateCommand();
});

function invokeSiteUpdateMethod(SiteUpdateCommand $command, string $method, array $args = [])
{
    $reflection = new ReflectionClass($command);
    $instanceMethod = $reflection->getMethod($method);
    $instanceMethod->setAccessible(true);

    return $instanceMethod->invokeArgs($command, $args);
}

test('buildArchiveUrl uses tag archive path for semantic versions', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '3.5.4']);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/tags/3.5.4');
});

test('buildArchiveUrl uses branch archive path for branch refs', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '3.5.x']);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/refs/heads/3.5.x');
});

test('buildArchiveUrl preserves nested branch paths', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['vendor/repo', 'feature/test-branch']);

    expect($url)->toBe('https://codeload.github.com/vendor/repo/zip/refs/heads/feature/test-branch');
});

test('buildArchiveUrl uses commit archive path for commit hashes', function () {
    $url = invokeSiteUpdateMethod($this->command, 'buildArchiveUrl', ['evolution-cms/evolution', '922ece66071acecaea9afb8486791738acc6de5e']);

    expect($url)->toBe('https://codeload.github.com/evolution-cms/evolution/zip/922ece66071acecaea9afb8486791738acc6de5e');
});

test('normalizeRequestedVersion keeps explicit refs and normalizes empty input', function () {
    expect(invokeSiteUpdateMethod($this->command, 'normalizeRequestedVersion', ['3.5.x']))->toBe('3.5.x');
    expect(invokeSiteUpdateMethod($this->command, 'normalizeRequestedVersion', ['']))->toBe('null');
    expect(invokeSiteUpdateMethod($this->command, 'normalizeRequestedVersion', [null]))->toBe('null');
});
