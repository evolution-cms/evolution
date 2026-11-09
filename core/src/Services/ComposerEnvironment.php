<?php declare(strict_types=1);

namespace EvolutionCMS\Services;

use Composer\Config;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Util\Platform;

/**
 * Keep the site's Composer authentication available when an update checks another core directory.
 *
 * Credentials travel only in the child process environment, never in shell arguments or
 * the unpacked archive. Composer's normal home remains in use; the old core/composer
 * auth file supplies fallback credentials for sites configured by earlier updaters.
 *
 * @since 3.6.0
 */
final class ComposerEnvironment
{
    /** Authentication sections understood by Composer, each keyed by repository host. */
    private const AUTH_METHODS = [
        'http-basic', 'bearer', 'github-oauth', 'gitlab-oauth', 'gitlab-token',
        'bitbucket-oauth', 'custom-headers', 'client-certificate', 'forgejo-token',
    ];

    /**
     * Bind authentication lookup to the installed core, rather than the update archive.
     *
     * @param string $corePath Installed core directory containing composer.json and optional auth.json.
     * @since 3.6.0
     */
    public function __construct(private string $corePath)
    {
        $this->corePath = rtrim($corePath, '/\\') . '/';
    }

    /**
     * Run an update with the same credentials for its preflight and dependency restore.
     *
     * Precedence is legacy auth, normal global config/auth, project auth, then COMPOSER_AUTH.
     * Restore the original process environment even when the update fails or throws.
     * No credential file is written or copied into the update archive.
     *
     * @param callable(): mixed $update Update work whose Composer subprocesses inherit this environment.
     * @return mixed The update's result, unchanged.
     * @since 3.6.0
     */
    public function run(callable $update): mixed
    {
        $previous = [];
        foreach (['COMPOSER_HOME', 'COMPOSER_AUTH'] as $name) {
            $previous[$name] = [getenv($name), array_intersect_key($_SERVER, [$name => true]), array_intersect_key($_ENV, [$name => true])];
        }

        try {
            $home = Platform::getEnv('COMPOSER_HOME');
            if ($home) {
                Platform::putEnv('COMPOSER_HOME', $home);
            } elseif (!Platform::getEnv(Platform::isWindows() ? 'APPDATA' : 'HOME')) {
                // FPM/cron may have no user home; keep the existing site-local fallback there.
                Platform::putEnv('COMPOSER_HOME', $this->corePath . 'composer');
            }

            $config = new Config();
            $config->merge(['config' => $this->readAuthentication($this->corePath . 'composer/auth.json')]);
            try {
                $globalConfig = Factory::createConfig(new NullIO(), $this->corePath);
                $global = $globalConfig->raw()['config'];
            } catch (\Throwable $exception) {
                // Composer's JSON diagnostics may contain a line of credentials.
                throw new \RuntimeException('Could not load the existing Composer configuration and authentication.');
            }
            // Resolve implicit/XDG/Windows homes once so every child sees the same directory.
            Platform::putEnv('COMPOSER_HOME', $globalConfig->get('home'));
            $config->merge(['config' => array_intersect_key($global, array_flip(self::AUTH_METHODS))]);
            $config->merge(['config' => $this->readAuthentication($this->corePath . 'auth.json')]);

            $auth = Platform::getEnv('COMPOSER_AUTH');
            if ($auth !== false && $auth !== '') {
                try {
                    $data = JsonFile::parseJson($auth);
                    JsonFile::validateJsonSchema('COMPOSER_AUTH', $data, JsonFile::AUTH_SCHEMA);
                } catch (\Throwable $exception) {
                    throw new \RuntimeException('Invalid Composer authentication in COMPOSER_AUTH.');
                }
                $config->merge(['config' => $data]);
            }

            $authentication = array_filter(array_intersect_key($config->raw()['config'], array_flip(self::AUTH_METHODS)));
            Platform::putEnv('COMPOSER_AUTH', json_encode((object) $authentication, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

            return $update();
        } finally {
            foreach ($previous as $name => [$value, $server, $env]) {
                $value === false ? putenv($name) : putenv($name . '=' . $value);
                unset($_SERVER[$name], $_ENV[$name]);
                $_SERVER += $server;
                $_ENV += $env;
            }
        }
    }

    /**
     * Read an optional auth file without exposing its contents in parse errors.
     *
     * Composer validates the schema and merges host entries, so one host's username
     * and password are replaced together rather than recursively mixed with another source.
     *
     * @param string $path Installed site's authentication file; an absent file contributes nothing.
     * @return array<string, mixed> Composer authentication sections.
     * @since 3.6.0
     */
    private function readAuthentication(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        try {
            $file = new JsonFile($path);
            $file->validateSchema(JsonFile::AUTH_SCHEMA);

            return $file->read();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('Could not read Composer authentication from ' . $path . '.');
        }
    }
}
