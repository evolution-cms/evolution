<?php

use EvolutionCMS\Console\SiteLinkCommand;
use Illuminate\Container\Container;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/*
 * A hosting account serves public_html and keeps its own files there; the CMS installed next to it
 * stays out of the web root, and the public folder links to its entry points.
 */

beforeEach(function () {
    $this->home = sys_get_temp_dir() . '/evo-site-link-' . bin2hex(random_bytes(6));
    $this->site = $this->home . '/evolution';
    $this->public = $this->home . '/public_html';
    mkdir($this->site . '/admin', 0777, true);
    mkdir($this->site . '/assets/images', 0777, true);
    mkdir($this->public, 0777, true);
    file_put_contents($this->site . '/index.php', 'front controller');
    file_put_contents($this->site . '/admin/index.php', 'manager');
    file_put_contents($this->site . '/.htaccess', 'rules');
    file_put_contents($this->public . '/hello.php', 'own script');
    if (!@symlink($this->site . '/index.php', $this->home . '/probe')) {
        $this->markTestSkipped('This file system does not let the test create a symlink.');
    }
    unlink($this->home . '/probe');
});

afterEach(function () {
    \EvolutionCMS\Console\SiteUpdateCommand::rmdirs($this->home);
});

/** Runs site:link against the test's site, its manager renamed to admin/. */
function siteLink(object $test, array $input, bool $linksFail = false, string $manager = 'admin'): array
{
    $command = new class($test->site, $linksFail, $manager) extends SiteLinkCommand {
        public function __construct(private string $site, private bool $linksFail, private string $manager)
        {
            parent::__construct();
        }

        protected function createLink(string $value, string $link): bool
        {
            return !$this->linksFail && parent::createLink($value, $link);
        }

        protected function basePath(): string
        {
            return $this->site . '/';
        }

        protected function managerPath(): string
        {
            return $this->site . '/' . $this->manager . '/';
        }
    };
    $command->setLaravel(new class extends Container {
        public function runningUnitTests(): bool
        {
            return true;
        }
    });
    $output = new BufferedOutput();
    $exitCode = $command->run(new ArrayInput($input), $output);

    return [$exitCode, $output->fetch()];
}

test('the entry points are linked relatively, the manager by its own name', function () {
    [$exitCode, $output] = siteLink($this, ['path' => $this->public]);

    expect($exitCode)->toBe(0)
        ->and(readlink($this->public . '/index.php'))->toBe('../evolution/index.php')
        ->and(readlink($this->public . '/admin'))->toBe('../evolution/admin')
        ->and(readlink($this->public . '/assets'))->toBe('../evolution/assets')
        ->and(file_get_contents($this->public . '/admin/index.php'))->toBe('manager')
        // install/ and update.php are there while the installer and the updater need them
        ->and(readlink($this->public . '/install'))->toBe('../evolution/install')
        ->and(readlink($this->public . '/update.php'))->toBe('../evolution/update.php')
        ->and($output)->toContain('install -> ../evolution/install (waits for install)')
        ->and(file_exists($this->public . '/.htaccess'))->toBeFalse()
        ->and(file_get_contents($this->public . '/hello.php'))->toBe('own script');
});

test('the installer folder answers through its link once it exists', function () {
    siteLink($this, ['path' => $this->public]);
    mkdir($this->site . '/install');
    file_put_contents($this->site . '/install/index.php', 'installer');

    expect(file_get_contents($this->public . '/install/index.php'))->toBe('installer');
});

test('absolute links and .htaccess on request', function () {
    [$exitCode] = siteLink($this, ['path' => $this->public, '--absolute' => true, '--htaccess' => true]);

    expect($exitCode)->toBe(0)
        ->and(readlink($this->public . '/index.php'))->toBe(str_replace('\\', '/', realpath($this->site)) . '/index.php')
        ->and(file_get_contents($this->public . '/.htaccess'))->toBe('rules');
});

test('a second run finds the links in place', function () {
    siteLink($this, ['path' => $this->public]);

    [$exitCode, $output] = siteLink($this, ['path' => $this->public]);

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('index.php -> ../evolution/index.php (already linked)')
        ->and($output)->toContain('install -> ../evolution/install (already linked)');
});

test('a file of the account is never replaced', function () {
    file_put_contents($this->public . '/index.php', 'the account\'s own index');

    [$exitCode, $output] = siteLink($this, ['path' => $this->public, '--force' => true]);

    expect($exitCode)->toBe(1)
        ->and(file_get_contents($this->public . '/index.php'))->toBe('the account\'s own index')
        ->and($output)->toContain('index.php is a file of the public folder; move it away first')
        // the other entry points are linked all the same
        ->and(is_link($this->public . '/assets'))->toBeTrue();
});

test('a link elsewhere is replaced only with --force', function () {
    mkdir($this->home . '/old-release/assets', 0777, true);
    symlink('../old-release/assets', $this->public . '/assets');

    [$refused, $output] = siteLink($this, ['path' => $this->public]);
    expect($refused)->toBe(1)
        ->and(readlink($this->public . '/assets'))->toBe('../old-release/assets')
        ->and($output)->toContain('assets links to ../old-release/assets; --force replaces the link.');

    [$forced] = siteLink($this, ['path' => $this->public, '--force' => true]);
    expect($forced)->toBe(0)
        ->and(readlink($this->public . '/assets'))->toBe('../evolution/assets');
});

test('a replacement that cannot be made leaves the old link in place', function () {
    mkdir($this->home . '/old-release/assets', 0777, true);
    symlink('../old-release/assets', $this->public . '/assets');

    [$exitCode, $output] = siteLink($this, ['path' => $this->public, '--force' => true], true);

    expect($exitCode)->toBe(1)
        ->and(readlink($this->public . '/assets'))->toBe('../old-release/assets')
        ->and($output)->toContain('Could not replace the link assets -> ../evolution/assets; it still links to ../old-release/assets.')
        ->and(glob($this->public . '/.assets.site-link-*'))->toBe([]);
});

test('a renamed manager gets its link, and the old one goes', function () {
    siteLink($this, ['path' => $this->public]);
    rename($this->site . '/admin', $this->site . '/panel');

    [$exitCode, $output] = siteLink($this, ['path' => $this->public], false, 'panel');

    expect($exitCode)->toBe(0)
        ->and(readlink($this->public . '/panel'))->toBe('../evolution/panel')
        ->and(is_link($this->public . '/admin'))->toBeFalse()
        ->and($output)->toContain('admin -> ../evolution/admin removed: the site has no admin any more')
        // install/ and update.php lead nowhere too, but they are entry points
        ->and(is_link($this->public . '/install'))->toBeTrue();
});

test('only a dead link into the top of the site is removed', function () {
    mkdir($this->home . '/elsewhere');
    symlink('../evolution/gone', $this->public . '/old-manager');
    symlink($this->site . '/also-gone', $this->public . '/old-absolute');
    symlink('../evolution/assets/gone', $this->public . '/deeper');
    symlink('../elsewhere/gone', $this->public . '/outside');
    symlink('../elsewhere', $this->public . '/working');

    siteLink($this, ['path' => $this->public]);

    expect(is_link($this->public . '/old-manager'))->toBeFalse()
        ->and(is_link($this->public . '/old-absolute'))->toBeFalse()
        ->and(is_link($this->public . '/deeper'))->toBeTrue()
        ->and(is_link($this->public . '/outside'))->toBeTrue()
        ->and(is_link($this->public . '/working'))->toBeTrue()
        ->and(file_get_contents($this->public . '/hello.php'))->toBe('own script');
});

test('a public folder inside the site, or around it, is refused', function () {
    mkdir($this->site . '/public');

    [$inside, $insideOutput] = siteLink($this, ['path' => $this->site . '/public']);
    [$around, $aroundOutput] = siteLink($this, ['path' => $this->home]);
    [$missing, $missingOutput] = siteLink($this, ['path' => $this->home . '/absent']);

    expect($inside)->toBe(1)
        ->and($insideOutput)->toContain('The public folder is inside the site')
        ->and(glob($this->site . '/public/*'))->toBe([])
        ->and($around)->toBe(1)
        ->and($aroundOutput)->toContain('The site is inside the public folder already')
        ->and($missing)->toBe(1)
        ->and($missingOutput)->toContain('does not exist');
});

test('relative paths climb to the common folder', function () {
    expect(SiteLinkCommand::relativePath('/home/user/public_html', '/home/user/evolution/index.php'))->toBe('../evolution/index.php')
        ->and(SiteLinkCommand::relativePath('/srv/www/site/public', '/srv/evo/assets'))->toBe('../../../evo/assets')
        ->and(SiteLinkCommand::relativePath('C:/sites/public', 'D:/evo/index.php'))->toBe('D:/evo/index.php');
});

test('artisan knows site:link', function () {
    $provider = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Providers/ArtisanServiceProvider.php');

    expect($provider)->toContain("'SiteLink' => 'command.sitelink',")
        ->and($provider)->toContain('return new Console\SiteLinkCommand();');
});

test('ng.inx carries the lines for a site outside the web root', function () {
    $nginx = (string) file_get_contents(dirname(__DIR__, 4) . '/ng.inx');

    expect($nginx)->toContain('php core/artisan site:link /home/user/public_html')
        ->and($nginx)->toContain('    # root /{public_path};')
        ->and($nginx)->toContain('    # location ~ ^/(manager|install)/.*\.php$ { access_log off; return 404; }')
        ->and($nginx)->toContain("    # Outside the web root: replaces the location above, comment that one out;\n    # two locations of one prefix are a configuration error.\n    # location ^~ /.well-known/acme-challenge/ { root /{public_path}; }")
        ->and($nginx)->toContain("    # Outside the web root: replaces the line above, comment that one out.\n    # root /{public_path};")
        ->and($nginx)->toContain('-e "s|{public_path}|${PUBLIC_PATH}|g"');
});
