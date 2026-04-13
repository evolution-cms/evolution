<?php namespace EvolutionCMS\Services\Store;

class RemoteTransportService
{
    protected array $allowedHosts = [
        'evo.im',
        'extras.evo.im',
        'github.com',
        'codeload.github.com',
        'raw.githubusercontent.com',
    ];

    public function fetchBody($url)
    {
        $url = $this->normalizeAndValidateUrl($url);
        if ($url === '') {
            return '';
        }

        try {
            if (ini_get('allow_url_fopen') == true) {
                $context = $this->createStreamContext(20);
                $content = @file_get_contents($url, false, $context);
                if (is_string($content) && $content !== '') {
                    return $content;
                }
            }

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Evolution CMS Store');
                $content = curl_exec($ch);
                curl_close($ch);
                if (is_string($content) && $content !== '') {
                    return $content;
                }
            }
        } catch (\Throwable $exception) {
        }

        return '';
    }

    public function downloadFile($url, $path)
    {
        $url = $this->normalizeAndValidateUrl($url);
        if ($url === '') {
            return false;
        }

        try {
            if (ini_get('allow_url_fopen') == true) {
                $read = @fopen($url, 'rb', false, $this->createStreamContext(60));
                if (!$read) {
                    throw new \RuntimeException('Could not open remote file.');
                }

                $write = fopen($path, 'wb');
                if (!$write) {
                    fclose($read);
                    throw new \RuntimeException('Could not open local file for writing.');
                }

                while (!feof($read)) {
                    fwrite($write, fread($read, 1024 * 8), 1024 * 8);
                }

                fclose($read);
                fclose($write);
                return true;
            }

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Evolution CMS Store');
                $content = curl_exec($ch);
                curl_close($ch);

                if (!is_string($content) || $content === '') {
                    return false;
                }

                return file_put_contents($path, $content) !== false;
            }
        } catch (\Throwable $exception) {
        }

        return false;
    }

    public function normalizeAndValidateUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            return '';
        }

        if (!in_array($host, $this->allowedHosts, true)) {
            return '';
        }

        return $url;
    }

    protected function createStreamContext(int $timeoutSeconds)
    {
        return stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Evolution CMS Store\r\n",
                'timeout' => max(1, $timeoutSeconds),
                'follow_location' => 1,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false,
            ],
        ]);
    }
}
