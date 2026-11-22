<?php namespace EvolutionCMS\Support\Formatter;

class CSSMinify
{
    private const REBASED_CACHE_MARKER = '/* Evolution CMS: SVG manager icons; rebased CSS URLs v3 */';

    private $cssPath = [];

    public function __construct($cssFilesPath = [])
    {
        if (is_array($cssFilesPath) && !empty($cssFilesPath)) {
            $this->cssPath = $cssFilesPath;
        }
    }

    /**
     * Archive extraction and portable copies may preserve or reverse mtimes.
     * Compare the actual ordered sources, including their URL-rebasing paths.
     */
    public static function isStale(string $target, array $files): bool
    {
        if (!is_file($target)) {
            return true;
        }
        $handle = @fopen($target, 'rb');
        if ($handle === false) {
            return true;
        }
        $marker = fgets($handle);
        $fingerprint = fgets($handle);
        fclose($handle);

        return trim((string) $marker) !== self::REBASED_CACHE_MARKER
            || trim((string) $fingerprint) !== self::sourceMarker($files);
    }

    private static function sourceMarker(array $files): string
    {
        $digest = hash_init('sha256');
        foreach ($files as $file) {
            if (is_string($file) && is_file($file)) {
                hash_update($digest, $file . "\0" . hash_file('sha256', $file) . "\n");
            }
        }
        return '/* Source SHA-256: ' . hash_final($digest) . ' */';
    }

    /**
     * Map the whole ordered source list to browser URLs, or nothing if any source cannot
     * be represented. Restrict paths to the caller's browser-visible asset roots.
     *
     * @param array<string, string> $publicRoots Filesystem roots keyed to their URL roots
     * @return list<string>|null
     */
    public static function publicSourceUrls(array $files, array $publicRoots, string $revision): ?array
    {
        $urls = [];
        foreach ($files as $file) {
            if (!is_string($file) || !is_file($file) || !is_readable($file) || pathinfo($file, PATHINFO_EXTENSION) !== 'css') {
                return null;
            }

            $path = str_replace('\\', '/', $file);
            $url = null;
            foreach ($publicRoots as $root => $urlRoot) {
                $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
                if (!str_starts_with($path, $root)) {
                    continue;
                }

                $segments = explode('/', substr($path, strlen($root)));
                if (array_intersect($segments, ['', '.', '..']) !== []) {
                    continue;
                }

                $url = rtrim($urlRoot, '/') . '/' . implode('/', array_map('rawurlencode', $segments));
                break;
            }

            $digest = $url === null ? false : @hash_file('sha256', $file);
            if ($digest === false) {
                return null;
            }

            $urls[] = $url . $revision . '.' . $digest;
        }

        return $urls;
    }

    public function addFile($cssFilePath)
    {
        $this->cssPath[] = $cssFilePath;
    }

    public function minify(?string $outputFile = null)
    {
        $allCss = [];
        if (empty($this->cssPath)) {
            echo "No CSS was added";
            exit;
        }

        $sourceMarker = $outputFile === null ? '' : self::sourceMarker($this->cssPath);

        foreach ($this->cssPath as $css) {
            $bits = explode(".", $css);
            $ext = $bits[count($bits) - 1];
            if ($ext !== "css") {
                echo "Only CSS allowed";
                exit;
            }
            $filename = basename($css);
            $sourceDirectory = dirname($css);
            $css = file_get_contents($css);
            if ($outputFile !== null) {
                $css = $this->rebaseUrls($css, $sourceDirectory, dirname($outputFile));
            }
            $css = preg_replace("/\s{2,}/", "", $css);
            $css = str_replace(["\n", ', ', ': ', '; ', ' > ', ' }', '} ', ';}', '{ ', ' {'], ['', ',', ':', ';', '>', '}', '}', '}', '{', '{'], $css);
            $css = preg_replace('/\/\*.*?\*\//s', '', $css);

            $allCss[] = '/* ' . $filename . ' */' . "\n" . $css;
        }

        $prefix = $outputFile === null ? '' : self::REBASED_CACHE_MARKER . "\n"
            . $sourceMarker . "\n";

        return $prefix . implode("\n", $allCss);
    }

    /**
     * Publish only a complete bundle. A failed write must not leave a file with a
     * valid marker and source fingerprint but an incomplete CSS body.
     */
    public function writeBundle(string $target): bool
    {
        $css = $this->minify($target);
        $directory = dirname($target);
        $temp = @tempnam($directory, '.evo-css-');
        if ($temp === false) {
            return false;
        }

        try {
            // tempnam() may fall back to the system temp directory.
            if (realpath(dirname($temp)) !== realpath($directory)) {
                return false;
            }
            $permissions = is_file($target) ? @fileperms($target) : false;
            $mode = $permissions === false ? (0666 & ~umask()) : ($permissions & 0777);
            if (@file_put_contents($temp, $css) !== strlen($css) || !@chmod($temp, $mode)
                || !@rename($temp, $target)
            ) {
                return false;
            }
            clearstatcache(true, $target);

            return true;
        } finally {
            if (is_file($temp)) {
                @unlink($temp);
            }
        }
    }

    /** Rebuild old bundles, including bundles that still contain icon font definitions. */
    public static function isCacheCurrent(string $file): bool
    {
        if (!is_file($file)) {
            return false;
        }
        $handle = @fopen($file, 'rb');
        if ($handle === false) {
            return false;
        }

        $firstLine = fgets($handle);
        fclose($handle);

        return is_string($firstLine) && trim($firstLine) === self::REBASED_CACHE_MARKER;
    }

    private function rebaseUrls(string $css, string $sourceDirectory, string $outputDirectory): string
    {
        $sourceDirectory = str_replace('\\', '/', realpath($sourceDirectory) ?: $sourceDirectory);
        $outputDirectory = str_replace('\\', '/', realpath($outputDirectory) ?: $outputDirectory);

        return preg_replace_callback('~url\(\s*(?:([\"\x27])(.*?)\1|([^\s)]+))\s*\)~is', function (array $match) use ($sourceDirectory, $outputDirectory) {
            $quote = $match[1];
            $url = $quote !== '' ? $match[2] : $match[3];
            if ($url === '' || preg_match('~^(?:[a-z][a-z0-9+.-]*:|/|\#|\?)~i', $url)) {
                return $match[0];
            }

            preg_match('~^([^?\#]*)(.*)$~s', $url, $parts);
            $source = $this->pathSegments($sourceDirectory . '/' . $parts[1]);
            $output = $this->pathSegments($outputDirectory);
            while ($source !== [] && $output !== [] && $source[0] === $output[0]) {
                array_shift($source);
                array_shift($output);
            }
            $relative = implode('/', array_merge(array_fill(0, count($output), '..'), $source));

            return 'url(' . $quote . $relative . $parts[2] . $quote . ')';
        }, $css);
    }

    private function pathSegments(string $path): array
    {
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
            } else {
                $segments[] = $segment;
            }
        }

        return $segments;
    }
}
