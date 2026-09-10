<?php namespace EvolutionCMS\Support;

use Illuminate\Support\Facades\Log;

/**
 * A chunk's code as a file under views/chunks/.
 *
 * Not under assets/: that is served, and chunks hold extras' configuration.
 * Unlike a template file, a chunk file is read rather than rendered, so its
 * extension picks no engine.
 *
 * @since 3.5.8
 */
class ChunkFileStore
{
    /** Illegal on Windows, plus '%' - the escape character itself. */
    private const UNSAFE_BYTES = "%<>:\"/\\|?*";

    /** Refused rather than truncated: a truncated name would not decode back. */
    private const MAX_FILENAME_BYTES = 255;

    /** Refused by Windows whatever extension follows: CON.html is uncreatable. */
    private const RESERVED_DEVICE_NAMES = [
        'CON', 'PRN', 'AUX', 'NUL',
        'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9',
        'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9',
    ];

    /** @var array<string, string> extension => label */
    private array $formats;

    /** Absolute, without a trailing separator. */
    private string $directory;

    /**
     * @param array<string, mixed> $formats extension => label
     */
    public function __construct(array $formats, string $directory)
    {
        $normalised = [];
        foreach ($formats as $extension => $label) {
            $extension = ltrim((string) $extension, '.');
            if ($extension === '' || $this->sanitise($extension) !== $extension) {
                continue;
            }

            $normalised[$extension] = (string) (is_scalar($label) ? $label : $extension);
        }

        $this->formats = $normalised;
        $this->directory = rtrim(str_replace(chr(92), '/', $directory), '/');
    }

    public static function make(): self
    {
        $formats = [];
        $directory = EVO_BASE_PATH . 'views/chunks/';

        if (function_exists('config')) {
            try {
                $formats = (array) config('view.chunk_formats', []);
                $directory = (string) config('view.chunk_path', $directory);
            } catch (\Throwable) {
                $formats = [];
            }
        }

        return new self($formats, $directory);
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->formats;
    }

    public function isRegistered(?string $extension): bool
    {
        return $extension !== null && isset($this->formats[ltrim($extension, '.')]);
    }

    /**
     * The extension the form preselects when a chunk has not recorded one.
     */
    public function defaultExtension(): ?string
    {
        return $this->formats === [] ? null : (string) array_key_first($this->formats);
    }

    /**
     * The file a chunk name is written to, or null when it cannot be one.
     *
     * Names are prose, so hazardous bytes are percent-encoded rather than the
     * name refused; nameFor() reverses it. Non-ASCII is left readable, which
     * costs the NFC rule in refuseReason().
     */
    public function filename(string $name, string $extension): ?string
    {
        if (!$this->isRegistered($extension) || $this->refuseReason($name, $extension) !== null) {
            return null;
        }

        return $this->encode($name) . '.' . ltrim($extension, '.');
    }

    /** The inverse of filename(), or null if this store did not write it. */
    public function nameFor(string $filename, string $extension): ?string
    {
        $suffix = '.' . ltrim($extension, '.');

        if (!str_ends_with($filename, $suffix)) {
            return null;
        }

        $encoded = substr($filename, 0, -strlen($suffix));

        // Strict: a bare %, or one not followed by two hex digits, was never
        // produced here and must not decode to something that looks close.
        if ($encoded === '' || preg_match('/%(?![0-9A-Fa-f]{2})/', $encoded)) {
            return null;
        }

        $name = preg_replace_callback(
            '/%([0-9A-Fa-f]{2})/',
            static fn (array $m): string => chr((int) hexdec($m[1])),
            $encoded
        );

        // Round trip or nothing: %2e and %2E both decode to '.', but only one
        // of them is what this store writes, and only that one may be read as
        // the chunk's file.
        return $this->encode((string) $name) === $encoded ? $name : null;
    }

    /**
     * Why this name cannot be a file, or null when it can.
     *
     * What the encoding cannot fix. Each reason has its own fix, so the
     * manager repeats it back rather than shrugging.
     */
    public function refuseReason(string $name, string $extension): ?string
    {
        if (trim($name) === '') {
            return 'empty';
        }

        if (preg_match('//u', $name) !== 1) {
            return 'not_utf8';
        }

        // macOS compares NFC and NFD as one file, so two chunks that differ
        // only in normal form would share it.
        if (class_exists(\Normalizer::class) && !\Normalizer::isNormalized($name, \Normalizer::FORM_C)) {
            return 'not_nfc';
        }

        // Percent-escapes cost three bytes each, and a name is up to 100
        // characters of anything.
        if (strlen($this->encode($name) . '.' . ltrim($extension, '.')) > self::MAX_FILENAME_BYTES) {
            return 'too_long';
        }

        return null;
    }

    /**
     * Key for the one clash the encoding cannot see: "MyChunk" and "mychunk"
     * are both safe, both encode to themselves, and are one file on Windows
     * and macOS.
     */
    public function caseKey(string $name, string $extension): ?string
    {
        $filename = $this->filename($name, $extension);

        return $filename === null ? null : mb_strtolower($filename, 'UTF-8');
    }

    /**
     * The other chunk this name would share a file with, or null.
     *
     * A UNIQUE index compares bytes; the filesystem folds case. Ask before
     * writing.
     *
     * @param array<int|string, string> $others names of every other chunk
     */
    public function collidingName(string $name, string $extension, array $others): ?string
    {
        $key = $this->caseKey($name, $extension);

        if ($key === null) {
            return null;
        }

        foreach ($others as $other) {
            if ((string) $other !== $name && $this->caseKey((string) $other, $extension) === $key) {
                return (string) $other;
            }
        }

        return null;
    }

    /**
     * Percent-encode every filename hazard: the illegal bytes, a leading dot
     * (hidden on unix), a trailing dot or space (dropped by Windows) and the
     * reserved device names.
     */
    private function encode(string $name): string
    {
        $out = '';
        $length = strlen($name);

        for ($i = 0; $i < $length; $i++) {
            $byte = $name[$i];

            // A list, not a character class: escaping a backslash through to
            // the regex engine already lost it once. Only ASCII is listed, so
            // multi-byte characters (>= 0x80) pass through.
            $ord = ord($byte);
            $out .= ($ord < 0x20 || $ord === 0x7F || strpos(self::UNSAFE_BYTES, $byte) !== false)
                ? '%' . strtoupper(bin2hex($byte))
                : $byte;
        }

        if (str_starts_with($out, '.')) {
            $out = '%2E' . substr($out, 1);
        }

        if (str_ends_with($out, '.') || str_ends_with($out, ' ')) {
            $out = substr($out, 0, -1) . '%' . strtoupper(bin2hex(substr($out, -1)));
        }

        if (in_array(strtoupper($out), self::RESERVED_DEVICE_NAMES, true)) {
            $out = '%' . strtoupper(bin2hex($out[0])) . substr($out, 1);
        }

        return $out;
    }

    /**
     * The path of a chunk's file, if it is there.
     */
    public function pathFor(string $name, ?string $extension): ?string
    {
        if ($extension === null) {
            return null;
        }

        $filename = $this->filename($name, ltrim($extension, '.'));
        if ($filename === null) {
            return null;
        }

        $candidate = $this->directory . '/' . $filename;

        return is_file($candidate) ? $candidate : null;
    }

    /**
     * Every format this chunk has a file for.
     *
     * @return array<string, string> extension => absolute path
     */
    public function existing(string $name): array
    {
        $found = [];
        foreach (array_keys($this->formats) as $extension) {
            $path = $this->pathFor($name, $extension);
            if ($path !== null) {
                $found[$extension] = $path;
            }
        }

        return $found;
    }

    /**
     * A chunk's code: its file, or the column it used to live in.
     *
     * No setting and nothing to migrate - a site crosses over one save at a
     * time. Unlike a template, a same-named file winning is not an accident:
     * views/chunks/ holds nothing else.
     *
     * @param string|null $stored the site_htmlsnippets column
     *      @deprecated since 3.5.8 Only so a chunk nobody has saved yet still
     *      renders. Without it, resolve() is "read the file".
     *      @todo [remove@3.7] Remove in Evolution CMS 3.7
     */
    public function resolve(string $name, ?string $stored): ?string
    {
        $extension = $this->firstExisting($name);

        if ($extension === null) {
            return $stored;
        }

        $content = $this->read($name, $extension);

        if ($content !== null) {
            return $content;
        }

        // Unreadable, not absent - permissions. The column keeps the page
        // up; the log says it may be stale.
        try {
            Log::warning('Chunk file could not be read. The database copy was used instead.', [
                'chunk' => $name,
                'extension' => $extension,
            ]);
        } catch (\Throwable) {
            // No logger yet. Not worth a page for.
        }

        return $stored;
    }

    /** The format this chunk has a file in, in configured order, or null. */
    public function firstExisting(string $name): ?string
    {
        foreach (array_keys($this->formats) as $extension) {
            if ($this->pathFor($name, $extension) !== null) {
                return $extension;
            }
        }

        return null;
    }

    /**
     * The format a save writes to. Re-saving must not leave a second file
     * beside the first, which would then win.
     */
    public function writeExtension(string $name): ?string
    {
        return $this->firstExisting($name) ?? $this->defaultExtension();
    }

    /**
     * Delete every format's file, not just the winning one: one left behind
     * is adopted by the next chunk named the same.
     */
    public function forget(string $name): void
    {
        foreach ($this->existing($name) as $path) {
            @unlink($path);
        }
    }

    /**
     * What a chunk's file holds, or null when there is no such file.
     */
    public function read(string $name, ?string $extension): ?string
    {
        $path = $this->pathFor($name, $extension);

        if ($path === null || !is_readable($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return $content === false ? null : $content;
    }

    /**
     * Put a chunk's code in its file, creating the file and the directory if
     * they are not there yet.
     *
     * @return bool whether the file now holds this content
     */
    public function write(string $name, ?string $extension, string $content): bool
    {
        $filename = $extension === null ? null : $this->filename($name, ltrim($extension, '.'));
        if ($filename === null) {
            return false;
        }

        if (!$this->ensureDirectory()) {
            return false;
        }

        $path = $this->directory . '/' . $filename;

        if (is_file($path) ? !is_writable($path) : !is_writable($this->directory)) {
            return false;
        }

        return file_put_contents($path, $content) !== false;
    }

    /**
     * The directory chunk files are kept in.
     */
    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * Create the directory and its deny guards.
     *
     * views/ already denies, but the path is configurable and a parent
     * .htaccess is one deploy away from being gone. Existing guards are kept.
     */
    public function ensureDirectory(): bool
    {
        if (!is_dir($this->directory)
            && !@mkdir($this->directory, 0777, true)
            && !is_dir($this->directory)) {
            return false;
        }

        if (!is_writable($this->directory)) {
            return false;
        }

        foreach ([
            '.htaccess' => "order deny,allow\ndeny from all\n",
            'index.html' => "<h2>Unauthorized access</h2>\nYou're not allowed to access file folder",
        ] as $guard => $body) {
            if (!file_exists($this->directory . '/' . $guard)) {
                @file_put_contents($this->directory . '/' . $guard, $body);
            }
        }

        return true;
    }

    /** Relative to the installation: an absolute server path is nobody's business. */
    public function displayDirectory(): string
    {
        $base = rtrim(str_replace(chr(92), '/', EVO_BASE_PATH), '/') . '/';
        $directory = $this->directory . '/';

        return strpos($directory, $base) === 0 ? substr($directory, strlen($base)) : $directory;
    }

    /** The manager's label for a chunk's file, or null when it cannot be one. */
    public function displayPath(string $name, ?string $extension): ?string
    {
        $filename = $extension === null ? null : $this->filename($name, ltrim($extension, '.'));

        return $filename === null ? null : $this->displayDirectory() . $filename;
    }

    /**
     * What an extension may be made of. Extensions come from config, not from
     * typing, so a hazardous one is a mistake to refuse rather than encode.
     */
    private function sanitise(string $value): string
    {
        $value = (string) preg_replace('/\s+/', '', $value);
        $value = (string) preg_replace('/[^a-zA-Z0-9_.-]+/', '', $value);
        $value = (string) preg_replace('/\.{2,}/', '.', $value);

        return trim($value, '.');
    }
}
