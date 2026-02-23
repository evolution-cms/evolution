<?php

namespace EvolutionCMS\Console\Lists;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DeprecatedCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'deprecated:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all @deprecated and optional @todo KEYWORD@version tags with exact file:line';

    /**
     * The command signature (modern Laravel style).
     */
    protected $signature = 'deprecated:list
        {--min= : Minimum version (inclusive)}
        {--max= : Maximum version (inclusive)}
        {--todo= : Also search for @todo KEYWORD@version (example: --todo=remove)}
        {--dir= : Start directory (default: current working directory)}
        {--ext=php,js,html,htaccess : Comma-separated file extensions}';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $minVer      = $this->option('min') ?: null;
        $maxVer      = $this->option('max') ?: null;
        $todoKeyword = $this->option('todo') ?: null;
        $startDir    = $this->option('dir') ?: getcwd();
        $extList     = $this->option('ext');
        $extensions  = array_map('trim', explode(',', strtolower($extList)));

        $excludeDirs = ['vendor', 'node_modules', '.git', 'tmp', 'storage'];

        $results = $this->scan($startDir, $extensions, $excludeDirs, $minVer, $maxVer, $todoKeyword);

        if (empty($results)) {
            $this->info('No matching @deprecated or @todo tags found.');
            return;
        }

        foreach ($results as $line) {
            $this->line($line);
        }
    }

    /**
     * Core scanner (same logic as deprecate.php).
     */
    private function scan(
        string $startDir,
        array $extensions,
        array $excludeDirs,
        ?string $minVer,
        ?string $maxVer,
        ?string $todoKeyword
    ): array {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($startDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $results = [];

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $path = $fileInfo->getPathname();
            $ext  = strtolower($fileInfo->getExtension());

            if (!in_array($ext, $extensions, true)) {
                continue;
            }

            $relative = str_replace($startDir . DIRECTORY_SEPARATOR, '', $path);
            foreach ($excludeDirs as $ex) {
                if (stripos($relative, DIRECTORY_SEPARATOR . $ex . DIRECTORY_SEPARATOR) !== false) {
                    continue 2;
                }
            }

            $handle = @fopen($path, 'rb');
            if ($handle === false) {
                continue;
            }

            $lineNum = 0;
            while (($line = fgets($handle)) !== false) {
                $lineNum++;

                $hasDeprecated = stripos($line, '@deprecated') !== false;
                $hasTodoMatch  = $todoKeyword !== null
                    && stripos($line, '@todo') !== false
                    && stripos($line, $todoKeyword) !== false;

                if ($hasDeprecated || $hasTodoMatch) {
                    $ver = $this->extractVersion($line, $todoKeyword);
                    if ($this->versionInRange($ver, $minVer, $maxVer)) {
                        $realPath = realpath($path) ?: $path;
                        $results[] = $realPath . ':' . $lineNum;
                    }
                }
            }
            fclose($handle);
        }

        sort($results);
        return $results;
    }

    private function extractVersion(string $line, ?string $todoKeyword = null): ?string
    {
        if (preg_match('/@deprecated\b.*?(\d+\.\d+(?:\.\d+)?(?:-[^ \s]+)?)/i', $line, $m)) {
            return $m[1];
        }

        if ($todoKeyword !== null) {
            $kw = preg_quote($todoKeyword, '/');
            if (preg_match('/@todo\b.*?' . $kw . '.*?@?(\d+\.\d+(?:\.\d+)?(?:-[^ \s]+)?)/i', $line, $m)) {
                return $m[1];
            }
        }

        if (preg_match('/@todo\b.*?(\d+\.\d+(?:\.\d+)?(?:-[^ \s]+)?)/i', $line, $m)) {
            return $m[1];
        }

        return null;
    }

    private function versionInRange(?string $ver, ?string $min, ?string $max): bool
    {
        if ($ver === null) {
            return $min === null && $max === null;
        }
        if ($min !== null && version_compare($ver, $min, '<')) {
            return false;
        }
        if ($max !== null && version_compare($ver, $max, '>')) {
            return false;
        }
        return true;
    }
}