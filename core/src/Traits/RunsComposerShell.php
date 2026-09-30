<?php namespace EvolutionCMS\Traits;

use ExecWithFallback\ExecWithFallback;

/**
 * Find a Composer executable and run shell commands from the core directory.
 *
 * Shared by the core updater and the package installer, so both run Composer
 * as a separate process through ExecWithFallback and find it the same way.
 */
trait RunsComposerShell
{
    /**
     * Resolve a Composer executable command for shell calls.
     *
     * Some shared-hosting environments expose Composer only as a shell alias such
     * as ~/.composer/composer. PHP executes update commands through /bin/sh, where
     * interactive bash aliases are not available, so we need a real executable path.
     *
     * @since 3.5.7
     * @return string Composer command safe for shell usage.
     */
    protected function composerBinaryCommand(): string
    {
        return $this->resolveComposerBinaryCommand() ?? 'composer';
    }

    /**
     * Find a Composer executable without guessing.
     *
     * Unlike composerBinaryCommand(), which falls back to a bare "composer" and lets
     * the shell report the failure, this answers null when nothing was found, so a
     * caller with another way to run Composer can take it instead.
     *
     * @since 3.5.9
     * @return string|null Composer command safe for shell usage, or null.
     */
    protected function resolveComposerBinaryCommand(): ?string
    {
        foreach (['COMPOSER_BINARY', 'COMPOSER_BIN'] as $envName) {
            $configured = trim((string) getenv($envName));
            if ($configured !== '') {
                return escapeshellarg($configured);
            }
        }

        if ($this->shellCommandExists('composer')) {
            return 'composer';
        }

        foreach ($this->composerBinaryCandidates() as $candidate) {
            if ($this->isExecutableFile($candidate)) {
                return escapeshellarg($candidate);
            }
        }

        return null;
    }

    /**
     * Check whether a path is something the shell can run.
     *
     * On Windows is_executable() answers false even for a genuine
     * composer.bat — it does not consult PATHEXT the way the shell does — so
     * every candidate would be rejected no matter which paths were offered.
     * There the file existing is the only signal available.
     *
     * @since 3.5.8
     * @param string $path Absolute path to test.
     * @return bool
     */
    protected function isExecutableFile(string $path): bool
    {
        if (!is_file($path)) {
            return false;
        }

        return windows_os() ? true : is_executable($path);
    }

    /**
     * Build fallback Composer executable candidates.
     *
     * @since 3.5.7
     * @return array<int, string>
     */
    protected function composerBinaryCandidates(): array
    {
        $candidates = [
            '/usr/local/bin/composer',
            '/usr/bin/composer',
        ];

        foreach ($this->homeDirectories() as $home) {
            $candidates[] = $home . '/.composer/composer';
        }

        // Appended rather than switched on the platform. Every candidate is
        // filtered by isExecutableFile() anyway, so an entry that cannot exist
        // here costs one is_file() call, while a platform branch would be a
        // new way to guess wrong — under WSL, or wherever the environment does
        // not match what PHP_OS_FAMILY suggests.
        $candidates = array_merge($candidates, $this->windowsComposerBinaryCandidates());

        return array_values(array_unique($candidates));
    }

    /**
     * Build fallback Composer executable candidates for Windows layouts.
     *
     * The POSIX list finds nothing here: there is no /usr/local/bin, and a
     * per-user install puts a shim in %APPDATA%\Composer rather than in a
     * ~/.composer/composer file. Only shell-runnable shims are listed —
     * composer.phar is deliberately absent, because it needs `php` in front of
     * it and this list feeds a command that is executed directly.
     *
     * @since 3.5.8
     * @return array<int, string>
     */
    protected function windowsComposerBinaryCandidates(): array
    {
        $candidates = [];

        // Where the Composer-Setup installer puts a machine-wide install.
        $programData = trim((string) getenv('ProgramData'));
        if ($programData !== '') {
            $base = rtrim(str_replace('\\', '/', $programData), '/') . '/ComposerSetup/bin/composer';
            $candidates[] = $base . '.bat';
            $candidates[] = $base . '.exe';
        }

        // A per-user install.
        $appData = trim((string) getenv('APPDATA'));
        if ($appData !== '') {
            $base = rtrim(str_replace('\\', '/', $appData), '/') . '/Composer/composer';
            $candidates[] = $base . '.bat';
            $candidates[] = $base . '.exe';
        }

        foreach ($this->homeDirectories() as $home) {
            $candidates[] = $home . '/AppData/Roaming/Composer/composer.bat';
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * Resolve possible home directories without relying on shell "~" expansion.
     *
     * @since 3.5.7
     * @return array<int, string>
     */
    protected function homeDirectories(): array
    {
        $homes = [];

        foreach (['HOME', 'USERPROFILE'] as $envName) {
            $home = trim((string) getenv($envName));
            if ($home !== '') {
                $homes[] = rtrim(str_replace('\\', '/', $home), '/');
            }
        }

        if (function_exists('posix_getpwuid') && function_exists('posix_getuid')) {
            $user = posix_getpwuid(posix_getuid());
            if (is_array($user) && !empty($user['dir'])) {
                $homes[] = rtrim(str_replace('\\', '/', (string) $user['dir']), '/');
            }
        }

        return array_values(array_unique(array_filter($homes)));
    }

    /**
     * Check whether a command is available to the non-interactive shell.
     *
     * @since 3.5.7
     * @param string $command Command name.
     * @return bool
     */
    protected function shellCommandExists(string $command): bool
    {
        $output = [];
        $exitCode = 1;

        // `command -v` is a POSIX shell builtin and /dev/null is a POSIX
        // device; cmd.exe has neither, so on Windows this probe reported "not
        // found" for every command — including ones plainly on PATH — and the
        // resolver fell through to candidate paths that do not exist there
        // either. `where` is the native equivalent and answers 0 when found.
        $probe = windows_os()
            ? 'where ' . escapeshellarg($command) . ' >NUL 2>NUL'
            : 'command -v ' . escapeshellarg($command) . ' >/dev/null 2>&1';

        ExecWithFallback::exec($probe, $output, $exitCode);

        return (int) $exitCode === 0;
    }


    /**
     * Run a shell command from the core directory and report how it went.
     *
     * Composer and artisan calls must not rely on the current working directory:
     * updates are started from the manager, cron or a shell anywhere on the disk.
     *
     * @since 3.5.9
     * @param string $command Command to execute from EVO_CORE_PATH.
     * @param array<int, string>|null $output Receives the combined stdout and stderr lines.
     * @return int Exit code of the command.
     */
    protected function execCoreShellCommand(string $command, ?array &$output = null): int
    {
        // Plain `cd` keeps the current drive on Windows; /d switches it too.
        $cd = windows_os() ? 'cd /d ' : 'cd ';
        $fullCommand = $cd . escapeshellarg(EVO_CORE_PATH) . ' && ' . $command . ' 2>&1';

        $output = [];
        $exitCode = 1;
        ExecWithFallback::exec($fullCommand, $output, $exitCode);

        return (int) $exitCode;
    }

    /**
     * PHP executable for commands that start a PHP script.
     *
     * PHP_BINARY names the running interpreter only on the command line; under
     * PHP-FPM it is the FPM daemon, which cannot run a script, so the PHP on PATH
     * is used there instead.
     *
     * @since 3.5.9
     * @return string PHP command safe for shell usage.
     */
    protected function phpBinaryCommand(): string
    {
        if (PHP_SAPI === 'cli' && PHP_BINARY !== '') {
            return escapeshellarg(PHP_BINARY);
        }

        return 'php';
    }

    /**
     * Read the version of the Composer a command runs.
     *
     * @since 3.5.9
     * @param string $composerCommand Composer command safe for shell usage.
     * @return string|null Version such as "2.8.4", or null when it could not be read.
     */
    protected function composerVersion(string $composerCommand): ?string
    {
        $output = [];
        if ($this->execCoreShellCommand($composerCommand . ' --version --no-ansi', $output) !== 0) {
            return null;
        }

        return self::parseComposerVersion(implode("\n", $output));
    }

    /**
     * Pick the version out of `composer --version` output.
     *
     * @since 3.5.9
     * @param string $output Output of `composer --version`.
     * @return string|null
     */
    public static function parseComposerVersion(string $output): ?string
    {
        if (preg_match('~Composer (?:version )?v?(\d+\.\d+(?:\.\d+)?)~i', $output, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
