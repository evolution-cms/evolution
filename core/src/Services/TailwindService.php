<?php namespace EvolutionCMS\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use RuntimeException;
use Throwable;

/**
 * Compile (or fetch from cache) Tailwind CSS for a package.
 * If the Tailwind CLI binary is missing, download the latest build automatically.
 */
class TailwindService
{
    /** Where we expect the binary */
    protected string $binary = EVO_BASE_PATH . 'core/vendor/bin/tailwindcss';

    /** Where to write output inside the package */
    protected string $buildDir = '';
    protected int $ttl = 31536000; // 365 days (60 × 60 × 24 × 365)

    /**
     * Build CSS or return cached file.
     *
     * @param  string $input         Relative package file of style (e.g. packages/sSeo/css/tailwind.css)
     * @param  bool   $forceRebuild  Ignore cache
     * @return string                Public URL to compiled CSS
     */
    public function compile(string $input, bool $forceRebuild = false): string
    {
        $output = str_replace('.css', '.min.css', $input);

        if (!$forceRebuild && is_file($output)) {
            $hash = Cache::get("tw:{$input}");
            $cur  = md5_file($input);
            if ($hash === $cur) {
                return str_replace(EVO_BASE_PATH, '/', $output);
            }
        }

        if (!is_file($input)) {
            throw new RuntimeException("Tailwind sources not found for «{$input}».");
        }

        $this->ensureBinary();

        if (!is_dir(dirname($output))
            && !mkdir(dirname($output), 0775, true)
            && !is_dir(dirname($output))) {
            throw new RuntimeException("Cannot create build dir for «{$input}».");
        }

        $tmpPath = EVO_CORE_PATH . 'storage/tmp';
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath, 0775, true);
        }

        $proc = new Process(
            [$this->binary, '-i', $input, '-o', $output, '--minify'],
            null,
            ['TMPDIR' => $tmpPath] + $_ENV
        );
        $proc->run();

        if (!$proc->isSuccessful()) {
            throw new RuntimeException('Tailwind build failed: ' . $proc->getErrorOutput());
        }

        $hash = md5_file($input);
        Cache::put("tw:{$input}", $hash, $this->ttl);

        return str_replace(EVO_BASE_PATH, '/', $output);
    }

    /**
     * Ensure the Tailwind CLI binary exists; download it if necessary.
     *
     * @throws RuntimeException if download fails
     */
    protected function ensureBinary(): void
    {
        if (is_file($this->binary) && filesize($this->binary) > 0) {
            return;
        }

        // Determine a platform (basic)
        [$os, $arch] = $this->detectPlatform(); // linux/macos/windows | x64/arm64
        $ext = $os === 'windows' ? '.exe' : '';
        $url = sprintf(
            'https://github.com/tailwindlabs/tailwindcss/releases/latest/download/'
            .'tailwindcss-%s-%s%s',
            $os, $arch, $ext
        );

        if (!is_dir(dirname($this->binary))) {
            mkdir(dirname($this->binary), 0775, true);
        }

        $this->download($url, $this->binary);
        if ($os !== 'windows') {
            chmod($this->binary, 0755);
        }
    }

    /**
     * Returns [os, arch] in the format needed by Tailwind.
     */
    private function detectPlatform(): array
    {
        $unameOS   = strtolower(PHP_OS_FAMILY); // 'Windows', 'Linux', 'Darwin'
        $unameArch = php_uname('m'); // x86_64, aarch64, ...

        /* ОS */
        $os = match (true) {
            str_contains($unameOS, 'windows') => 'windows',
            str_contains($unameOS, 'darwin')  => 'macos',
            default                           => 'linux',
        };

        /* Architecture */
        $arch = str_contains($unameArch, '64') ? 'x64' : 'arm64';
        if (str_contains($unameArch, 'arm') || str_contains($unameArch, 'aarch')) {
            $arch = 'arm64';
        }

        return [$os, $arch];
    }

    /**
     * Download helper – tries curl, falls back to stream_copy_to_stream.
     */
    private function download(string $url, string $dst): void
    {
        try {
            if (function_exists('curl_init')) {
                $fp = fopen($dst, 'w');
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_FILE            => $fp,
                    CURLOPT_FOLLOWLOCATION  => true,
                    CURLOPT_FAILONERROR     => true,
                    CURLOPT_CONNECTTIMEOUT  => 10,
                    CURLOPT_TIMEOUT         => 60,
                ]);
                curl_exec($ch);
                $err = curl_error($ch);
                curl_close($ch);
                fclose($fp);
                if ($err) {
                    throw new RuntimeException("Failed to download Tailwind CLI: " . $err);
                }
            } else {
                $in  = fopen($url, 'r');
                $out = fopen($dst, 'w');
                if (!$in || !$out) {
                    throw new RuntimeException("Failed to download Tailwind CLI: Unable to open streams");
                }
                if (stream_copy_to_stream($in, $out) === false) {
                    throw new RuntimeException("Failed to download Tailwind CLI: Download failed");
                }
                fclose($in);
                fclose($out);
            }
        } catch (Throwable $e) {
            @unlink($dst);
            throw $e;
        }
    }
}
