<?php namespace EvolutionCMS;

use Illuminate\Support\Facades\Cache;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;
use WebPConvert\WebPConvert;

class HelperProcessor
{
    /**
     * Same as the shipped assets/cache/images/.htaccess: both dialects, a bare 2.2 directive is a 500 on
     * a 2.4 server without mod_access_compat.
     */
    const CACHE_FOLDER_HTACCESS = "<IfModule mod_authz_core.c>\n    Require all granted\n</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Allow from all\n</IfModule>\n";

    /**
     * Set for a day once no WebP converter works here (a GD without WebP and no cwebp, imagick, ...),
     * so the stack is not tried, and logged, again for every image; clearing the cache retries.
     */
    const WEBP_UNAVAILABLE_KEY = 'evo.phpthumb.webp_unavailable';

    const ALLOWED_PARAMS = ['w', 'h', 'wp', 'hp', 'wl', 'hl', 'ws', 'hs', 'q', 'sx', 'sy', 'sw', 'sh',
        'zc', 'ica', 'bc', 'bg', 'bgt', 'fltr', 'xto', 'ra', 'ar', 'aoe', 'far', 'iar', 'maxb', 'dpi'];

    const ALLOWED_FORMATS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico', 'wbmp'];

    /**
     * @var Interfaces\CoreInterface
     */
    protected $core;


    public function __construct(Interfaces\CoreInterface $core)
    {
        $this->core = $core;
    }

    protected function makeFilename($pathinfo, $params)
    {
        return $pathinfo['filename'];
    }

    protected function makeFilePath($newFilename, $pathinfo, $params)
    {
        return str_replace('assets/images', '', $pathinfo['dirname']);
    }

    public function phpThumb($input = '', $options = '', $webp = true)
    {
        if (!empty($input) && strtolower(substr($input, -4)) == '.svg') {
            return $input;
        }

        $newFolderAccessMode = $this->core->getConfig('new_folder_permissions');
        $newFolderAccessMode = empty($new) ? 0777 : octdec($newFolderAccessMode);

        $defaultCacheFolder = 'assets/cache/';
        $cacheFolder = $defaultCacheFolder . 'images';
        $phpThumbNoImagePath = 'assets/images/';

        /**
         * @see: https://github.com/kalessil/phpinspectionsea/blob/master/docs/probable-bugs.md#mkdir-race-condition
         */
        $path = EVO_BASE_PATH . $cacheFolder;
        if (!file_exists($path) && mkdir($path) && is_dir($path)) {
            chmod($path, $newFolderAccessMode);
        }

        if (!empty($input)) {
            $input = rawurldecode($input);
        }

        if (empty($input) || !file_exists(EVO_BASE_PATH . $input)) {
            $input = $phpThumbNoImagePath . 'noimage.png';
        }

        /**
         * allow read in phpthumb cache folder
         */
        if (!file_exists(EVO_BASE_PATH . $cacheFolder . '/.htaccess') &&
            $cacheFolder !== $defaultCacheFolder &&
            strpos($cacheFolder, $defaultCacheFolder) === 0
        ) {
            file_put_contents(EVO_BASE_PATH . $cacheFolder . '/.htaccess', self::CACHE_FOLDER_HTACCESS);
        }

        $path_parts = pathinfo($input);
        $ext = strtolower($path_parts['extension']);
        $defaultFormat = in_array($ext, ['png', 'gif', 'jpeg'], true) ? $ext : 'jpg';
        $options = 'f=' . ($defaultFormat === 'jpg' ? 'jpg&q=85' : $defaultFormat) . '&' .
            strtr($options, [',' => '&', '_' => '=', '{' => '[', '}' => ']']);

        parse_str($options, $params);
        $params = $this->sanitizeParams($params, $defaultFormat);

        $fName = $this->makeFilename($path_parts, $params);

        $tmpImagesFolder = $this->makeFilePath($fName, $path_parts, $params);
        $tmpImagesFolder = explode('/', $tmpImagesFolder);

        foreach ($tmpImagesFolder as $folder) {
            if (!empty($folder) && $folder !== '.' && $folder !== '..') {
                $cacheFolder .= '/' . $folder;
                $path = EVO_BASE_PATH . $cacheFolder;
                if (!file_exists($path) && mkdir($path) && is_dir($path)) {
                    chmod($path, $newFolderAccessMode);
                }
            }
        }

        $fmtime = '';
        if(isset($filemtime)){
            $fmtime = filemtime(EVO_BASE_PATH . $input);
        }

        $fNamePref = rtrim($cacheFolder, '/') . '/';
        $fNameSuf = '-' .
            (isset($params['w']) ? $params['w'] : '') . 'x' . (isset($params['h']) ? $params['h'] : '') . '-' .
            substr(md5(serialize($params) . $fmtime), 0, 3) .
            '.' . $params['f'];

        $fNameSuf = str_replace("ad", "at", $fNameSuf);

        $outputFilename = EVO_BASE_PATH . $fNamePref . $fName . $fNameSuf;
        if (!file_exists($outputFilename)) {
            $phpThumb = new \phpthumb();
            $phpThumb->config_cache_directory = EVO_BASE_PATH . $defaultCacheFolder;
            $phpThumb->config_temp_directory = $defaultCacheFolder;
            $phpThumb->config_document_root = EVO_BASE_PATH;
            $phpThumb->setSourceFilename(EVO_BASE_PATH . $input);
            foreach ($params as $key => $value) {
                $phpThumb->setParameter($key, $value);
            }
            if ($phpThumb->GenerateThumbnail()) {
                $phpThumb->RenderToFile($outputFilename);
            } else {
                $this->core->logEvent(0, 3, implode('<br/>', $phpThumb->debugmessages), 'phpthumb');
            }
        }

        if ($webp && class_exists('\WebPConvert\WebPConvert')) {
            if( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false && pathinfo($outputFilename, PATHINFO_EXTENSION) != 'gif') {
                if ($this->makeWebp($outputFilename)) {
                    $fNameSuf .= '.webp';
                }
            }
        }

        return $fNamePref . rawurlencode($fName) . $fNameSuf;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function sanitizeParams(array $params, string $defaultFormat): array
    {
        $format = isset($params['f']) && is_string($params['f']) ? strtolower($params['f']) : $defaultFormat;
        if (!in_array($format, self::ALLOWED_FORMATS, true)) {
            $format = $defaultFormat;
        }

        $safe = [];
        foreach (self::ALLOWED_PARAMS as $key) {
            if (array_key_exists($key, $params)) {
                $safe[$key] = $params[$key];
            }
        }
        $safe['f'] = $format;

        return $safe;
    }

    /**
     * Writes $filename.webp next to the thumbnail unless it is there already.
     *
     * @return bool false when there is no WebP copy to link: the caller keeps the thumbnail itself
     */
    protected function makeWebp(string $filename): bool
    {
        if (file_exists($filename . '.webp')) {
            return true;
        }
        if (!file_exists($filename) || $this->rememberedWebpUnavailable()) {
            return false;
        }
        try {
            $this->convertToWebp($filename, $filename . '.webp');
        } catch (\Throwable $e) {
            if ($e instanceof ConverterNotOperationalException) {
                $this->rememberWebpUnavailable();
            }
            $this->core->logEvent(0, 2, 'WebP copy of ' . e(basename($filename)) . ' not made, the thumbnail is served as it is: '
                . e($e->getMessage()), 'phpthumb');

            return false;
        }

        return file_exists($filename . '.webp');
    }

    protected function convertToWebp(string $source, string $destination): void
    {
        WebPConvert::convert($source, $destination);
    }

    protected function rememberedWebpUnavailable(): bool
    {
        try {
            return (bool) Cache::get(self::WEBP_UNAVAILABLE_KEY, false);
        } catch (\Throwable $e) {
            return false; // no cache store here: try every time
        }
    }

    protected function rememberWebpUnavailable(): void
    {
        try {
            Cache::put(self::WEBP_UNAVAILABLE_KEY, true, 86400);
        } catch (\Throwable $e) {
        }
    }
}
