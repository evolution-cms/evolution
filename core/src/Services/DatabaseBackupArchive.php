<?php namespace EvolutionCMS\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Compressed snapshots use the same SQL generator as synchronous CMS backups.
 * @since 3.6.0
 */
class DatabaseBackupArchive extends DatabaseBackupService
{
    /** @var string Export backend recorded for troubleshooting slow backups. */
    protected string $dumpBackend = 'php';

    /** Build a locked gzip snapshot without publishing an incomplete archive. @since 3.6.0 */
    public function create(string $description = '', ?callable $progress = null): array
    {
        $description = self::headerValue($description);
        if ($description === '') { $description = 'Backup'; }
        $directory = $this->resolveSnapshotPath();
        $this->prepareSnapshotPath($directory);
        $lock = fopen($directory . '.database-backup.lock', 'c+');
        if ($lock === false) {
            throw new \RuntimeException('Unable to open database backup lock.');
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            throw new \RuntimeException('A database backup is already running.');
        }
        try {
            if (!function_exists('gzopen')) {
                throw new \RuntimeException('Database backup compression requires zlib.');
            }
            $this->setProgressCallback($progress === null ? null : static function ($processed, $total) use ($progress): void {
                $progress('dump', $processed, $total);
            });
            $dumpStartedAt = microtime(true);
            $snapshot = $this->createSnapshot($description);
            $dumpSeconds = microtime(true) - $dumpStartedAt;
            $header = self::metadata($snapshot['path']);
            if (empty($header['Evolution CMS Version']) || empty($header['Database'])) {
                $quarantine = dirname($snapshot['path']) . '/.invalid-' . bin2hex(random_bytes(4)) . '-' . basename($snapshot['path']);
                if (!rename($snapshot['path'], $quarantine)) {
                    throw new \RuntimeException('Unable to quarantine the incomplete snapshot.');
                }
                throw new \RuntimeException('The backup header is missing; the snapshot may be incomplete.');
            }
            $snapshot['original_size'] = filesize($snapshot['path']);
            $compressionStartedAt = microtime(true);
            $snapshot['path'] = $this->compress($snapshot['path'], $progress);
            $snapshot['filename'] = basename($snapshot['path']);
            $snapshot['size'] = filesize($snapshot['path']);
            $snapshot['metrics'] = ['backend' => $this->dumpBackend, 'dump_seconds' => round($dumpSeconds, 3),
                'compression_seconds' => round(microtime(true) - $compressionStartedAt, 3)];
            return $snapshot;
        } finally {
            $this->setProgressCallback(null);
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** Prefer the streaming native client for asynchronous MySQL snapshots. @since 3.6.0 */
    protected function createDriverSnapshot($driver, $database, $filePath)
    {
        if (!in_array((string) $driver, ['mysql', 'mariadb'], true)) {
            return parent::createDriverSnapshot($driver, $database, $filePath);
        }
        $finder = new ExecutableFinder();
        $binary = $finder->find('mariadb-dump') ?: $finder->find('mysqldump');
        if (!$binary) { return parent::createDriverSnapshot($driver, $database, $filePath); }
        $api = evo()->getDatabase();
        $config = (array) $api->getConfig();
        $prefix = (string) ($config['prefix'] ?? '');
        $quotedDatabase = str_replace('`', '``', (string) $database);
        $tables = $api->getColumn('Name', $api->query("SHOW TABLE STATUS FROM `{$quotedDatabase}` LIKE '" . $api->escape($prefix) . "%'"));
        if (!$tables) { return parent::createDriverSnapshot($driver, $database, $filePath); }
        $estimate = 0;
        $result = $api->queryWithBindings('SELECT COALESCE(SUM(data_length), 0) FROM information_schema.TABLES WHERE table_schema = ? AND table_name LIKE ?',
            [(string) $database, $prefix . '%']);
        if ($result) { $estimate = (int) $api->getValue($result); }
        $this->dumpBackend = basename($binary);
        return $this->writeMySqlSnapshot($binary, $config, (string) $database, $tables, (string) $filePath,
            $this->buildSqlHeader('--', (string) $database, (string) ($config['host'] ?? '')), $estimate);
    }

    /**
     * Stream CLI SQL into a private file, preserving the CMS header and limiting memory usage.
     * The flags keep the same table-only scope as the PHP exporter; credentials never enter argv.
     * @since 3.6.0
     */
    protected function writeMySqlSnapshot(string $binary, array $config, string $database, array $tables,
        string $filePath, string $header, int $estimate): bool
    {
        $credentials = tempnam(sys_get_temp_dir(), 'evo-db-');
        $temporary = tempnam(dirname($filePath), '.database-backup-');
        $handle = null;
        $process = null;
        try {
            if ($credentials === false || $temporary === false) {
                throw new \RuntimeException('Unable to create private dump files.');
            }
            if (!chmod($credentials, 0600)) { throw new \RuntimeException('Unable to protect database client options.'); }
            $options = ['[client]'];
            foreach (['host' => 'host', 'port' => 'port', 'unix_socket' => 'socket', 'username' => 'user', 'password' => 'password'] as $key => $option) {
                if (!isset($config[$key]) || $config[$key] === '') { continue; }
                $value = str_replace(["\\", '"', "\n", "\r"], ["\\\\", '\\"', '\\n', '\\r'], (string) $config[$key]);
                $options[] = $option . '="' . $value . '"';
            }
            if (file_put_contents($credentials, implode("\n", $options) . "\n") === false) {
                throw new \RuntimeException('Unable to write database client options.');
            }
            $handle = fopen($temporary, 'wb');
            if ($handle === false || fwrite($handle, $header) !== strlen($header)) {
                throw new \RuntimeException('Unable to write the backup header.');
            }
            $arguments = [$binary, '--defaults-extra-file=' . $credentials, '--single-transaction', '--quick',
                '--skip-lock-tables', '--skip-add-locks', '--skip-disable-keys', '--skip-comments', '--skip-triggers',
                '--hex-blob', '--default-character-set=utf8mb4', '--no-tablespaces', '--', $database];
            $process = new Process(array_merge($arguments, array_values($tables)));
            $process->setTimeout(null);
            $processed = 0;
            $error = '';
            if ($this->progressCallback !== null) { ($this->progressCallback)(0, $estimate); }
            $process->run(function ($type, $chunk) use ($handle, $process, $estimate, &$processed, &$error): void {
                if ($type === Process::ERR) {
                    $error = substr($error . $chunk, -4096);
                    $process->clearErrorOutput();
                    return;
                }
                if (fwrite($handle, $chunk) !== strlen($chunk)) { throw new \RuntimeException('Unable to write dump output.'); }
                $processed += strlen($chunk);
                $process->clearOutput();
                if ($this->progressCallback !== null) {
                    ($this->progressCallback)($estimate > 0 ? min($processed, $estimate * 0.99) : 0, $estimate);
                }
            });
            if (!$process->isSuccessful() || $processed === 0) {
                $error = str_replace((string) ($config['password'] ?? ''), '[redacted]', $error);
                throw new \RuntimeException('Database client dump failed: ' . trim($error));
            }
            fclose($handle);
            $handle = null;
            if (!rename($temporary, $filePath)) { throw new \RuntimeException('Unable to publish dump output.'); }
            if ($this->progressCallback !== null) { ($this->progressCallback)(max(1, $estimate), max(1, $estimate)); }
            return true;
        } catch (\Throwable $error) {
            if ($process instanceof Process && $process->isRunning()) { $process->stop(3); }
            throw $error;
        } finally {
            if (is_resource($handle)) { fclose($handle); }
            foreach ([$credentials, $temporary] as $path) {
                if (is_string($path) && is_file($path)) { unlink($path); }
            }
        }
    }

    /** Stream SQL to gzip; keep the source snapshot if compression fails. @since 3.6.0 */
    public function compress(string $source, ?callable $progress = null): string
    {
        $target = $source . '.gz';
        if (is_file($target)) {
            throw new \RuntimeException('Compressed snapshot already exists.');
        }
        $temporary = tempnam(dirname($source), '.backup-');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create compressed snapshot.');
        }
        $input = $output = null;
        try {
            $input = fopen($source, 'rb');
            $output = gzopen($temporary, 'wb6');
            if ($input === false || $output === false) {
                throw new \RuntimeException('Unable to open snapshot streams.');
            }
            $processed = 0;
            $total = filesize($source);
            if ($progress !== null) { $progress('compress', 0, $total); }
            while (!feof($input)) {
                $chunk = fread($input, 1048576);
                if ($chunk === false || ($chunk === '' && !feof($input))) {
                    throw new \RuntimeException('Unable to read snapshot.');
                }
                if (gzwrite($output, $chunk) !== strlen($chunk)) {
                    throw new \RuntimeException('Unable to write compressed snapshot.');
                }
                $processed += strlen($chunk);
                if ($progress !== null) { $progress('compress', $processed, $total); }
            }
            fclose($input);
            $input = null;
            $closed = gzclose($output);
            $output = null;
            if (!$closed || !rename($temporary, $target)) {
                throw new \RuntimeException('Unable to publish compressed snapshot.');
            }
            Cache::forever('database-backup:original-size:' . self::fileIdentity($target), $total);
            unlink($source);
            return $target;
        } finally {
            if (is_resource($input)) { fclose($input); }
            if (is_resource($output)) { gzclose($output); }
            if (is_file($temporary)) { unlink($temporary); }
        }
    }

    /** Open plain or gzip SQL with the same stream API. @since 3.6.0 */
    public static function open(string $path)
    {
        return fopen(str_ends_with(strtolower($path), '.gz') ? 'compress.zlib://' . $path : $path, 'rb');
    }

    /**
     * Read a bounded header from native CMS SQL or mysql/mariadb-dump, plain or gzip.
     * Legacy CLI dumps can carry the saved CMS version in their system_settings data.
     * @since 3.6.0
     */
    public static function metadata(string $path): array
    {
        $stream = self::open($path);
        if ($stream === false) { return []; }
        $details = [];
        $legacyDump = false;
        $bytes = 0;
        $labels = ['Evolution CMS Version', 'Generation Time', 'Server version', 'PHP Version', 'Database', 'Description'];
        try {
            for ($index = 0; $index < 64 && $bytes < 65536; $index++) {
                $line = fgets($stream, min(8192, 65536 - $bytes + 1));
                if ($line === false) { break; }
                $bytes += strlen($line);
                if (!preg_match('/^\s*(?:#|--)\s*(.*)$/', $line, $comment)) { continue; }
                $text = trim($comment[1]);
                if (str_ends_with($text, ' Database Dump')) { $details['Title'] = $text; }
                if (preg_match('/^(?:MariaDB|MySQL) dump\b/', $text)) { $legacyDump = true; }
                if (preg_match('/^Host:\s*(.*?)\s+Database:\s*(.*?)\s*$/', $text, $match)) {
                    $details['Host'] = $match[1];
                    $details['Database'] = trim($match[2], '`');
                    continue;
                }
                if (str_starts_with($text, 'Host:')) {
                    $details['Host'] = trim(substr($text, 5));
                    continue;
                }
                foreach ($labels as $label) {
                    if (preg_match('/^' . preg_quote($label, '/') . '(?:\s*:\s*|\s+)(.*)$/', $text, $match)) {
                        $details[$label] = trim($match[1], " \t\r\n`");
                        break;
                    }
                }
            }
        } finally {
            fclose($stream);
        }
        if ($legacyDump && empty($details['Evolution CMS Version'])) {
            $version = self::legacyVersion($path);
            if ($version !== '') { $details['Evolution CMS Version'] = $version; }
        }
        if (empty($details['Description'])) { $details['Description'] = 'Backup'; }
        return $details;
    }

    /**
     * Recover the version saved in a legacy dump, caching by path, size and modification time.
     * Only inspect system_settings INSERT data; never use the currently installed CMS version.
     * @since 3.6.0
     */
    public static function legacyVersion(string $path): string
    {
        $identity = self::fileIdentity($path);
        return Cache::rememberForever('database-backup:metadata:' . $identity,
            static fn (): string => self::readLegacyVersion($path));
    }

    /** Identify cache entries by the actual backup file, not only its name. @since 3.6.0 */
    private static function fileIdentity(string $path): string
    {
        clearstatcache(true, $path);
        return hash('sha256', (realpath($path) ?: $path) . ':' . filesize($path) . ':' . filemtime($path));
    }

    /**
     * Return the full uncompressed byte count, including archives larger than gzip's 4 GiB trailer limit.
     * New archives cache the known source size; older archives are streamed once after a cache miss.
     * @since 3.6.0
     */
    public static function originalSize(string $path): int
    {
        if (!str_ends_with(strtolower($path), '.gz')) { return (int) filesize($path); }
        return (int) Cache::rememberForever('database-backup:original-size:' . self::fileIdentity($path),
            static function () use ($path): int {
                $stream = self::open($path);
                if ($stream === false) { throw new \RuntimeException('Unable to read backup size.'); }
                $size = 0;
                try {
                    while (!feof($stream)) {
                        $chunk = fread($stream, 1048576);
                        if ($chunk === false) { throw new \RuntimeException('Unable to read backup size.'); }
                        $size += strlen($chunk);
                    }
                } finally { fclose($stream); }
                return $size;
            });
    }

    /** Stream the saved version on a cache miss without loading the dump into memory. @since 3.6.0 */
    private static function readLegacyVersion(string $path): string
    {
        $stream = self::open($path);
        if ($stream === false) { return ''; }
        $version = '';
        $inSettings = false;
        $tail = '';
        try {
            while (($line = fgets($stream, 65536)) !== false) {
                if (preg_match('/^-- (?:Table structure|Dumping data) for table `([^`]+)`/', $line, $table)) {
                    $inSettings = str_ends_with($table[1], 'system_settings');
                    $tail = '';
                }
                if (!$inSettings) { continue; }
                $text = $tail . $line;
                if (preg_match("/'settings_version'\\s*,\\s*'(\\d+\\.\\d+\\.\\d+(?:[-+][A-Za-z0-9.-]+)?)'/", $text, $match)) {
                    $version = $match[1];
                    break;
                }
                $tail = substr($text, -256);
            }
        } finally {
            fclose($stream);
        }
        return $version;
    }

    /** Restore gzip SQL through the existing importer and always remove the private temp file. @since 3.6.0 */
    public static function restore(string $path, callable $restore): void
    {
        if (!str_ends_with(strtolower($path), '.sql.gz')) {
            $restore($path);
            return;
        }
        $temporary = tempnam(sys_get_temp_dir(), 'evo-restore-');
        if ($temporary === false) {
            throw new \RuntimeException('Unable to create restore temporary file.');
        }
        $input = $output = null;
        try {
            $input = fopen($path, 'rb');
            $output = fopen($temporary, 'wb');
            if ($input === false || $output === false) {
                throw new \RuntimeException('Unable to decompress snapshot.');
            }
            $inflater = inflate_init(ZLIB_ENCODING_GZIP);
            if ($inflater === false) {
                throw new \RuntimeException('Unable to initialize snapshot decompression.');
            }
            while (!feof($input)) {
                $chunk = fread($input, 65536);
                if ($chunk === false) {
                    throw new \RuntimeException('Unable to read compressed snapshot.');
                }
                if ($chunk === '') { break; }
                $decoded = @inflate_add($inflater, $chunk, ZLIB_SYNC_FLUSH);
                if ($decoded === false || fwrite($output, $decoded) !== strlen($decoded)) {
                    throw new \RuntimeException('Invalid or unreadable compressed snapshot.');
                }
            }
            if (inflate_get_status($inflater) !== ZLIB_STREAM_END || inflate_get_read_len($inflater) !== filesize($path)) {
                throw new \RuntimeException('Compressed snapshot is truncated or contains trailing data.');
            }
            fclose($input);
            $input = null;
            fclose($output);
            $output = null;
            $restore($temporary);
        } finally {
            if (is_resource($input)) { fclose($input); }
            if (is_resource($output)) { fclose($output); }
            unlink($temporary);
        }
    }

    /** Apply the core retention limit to both plain and compressed snapshots. @since 3.6.0 */
    protected function rotateSnapshots($snapshotPath)
    {
        $directory = rtrim((string) $snapshotPath, '/\\') . '/';
        $files = array_merge(glob($directory . '*.sql') ?: [], glob($directory . '*.sql.gz') ?: []);
        usort($files, static fn ($left, $right) => filemtime($right) <=> filemtime($left));
        foreach (array_slice($files, 10) as $file) {
            unlink($file);
        }
    }
}
