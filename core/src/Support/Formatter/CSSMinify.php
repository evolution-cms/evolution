<?php namespace EvolutionCMS\Support\Formatter;

class CSSMinify
{
    private $cssPath = [];

    public function __construct($cssFilesPath = [])
    {
        if (is_array($cssFilesPath) && !empty($cssFilesPath)) {
            $this->cssPath = $cssFilesPath;
        }
    }

    /**
     * True when the bundle is missing or older than one of its sources, so a changed
     * theme stylesheet reaches sites whose bundle was built before an update.
     */
    public static function isStale(string $target, array $files): bool
    {
        if (!is_file($target)) {
            return true;
        }

        $built = filemtime($target);
        foreach ($files as $file) {
            if (is_string($file) && is_file($file) && filemtime($file) > $built) {
                return true;
            }
        }

        return false;
    }

    public function addFile($cssFilePath)
    {
        $this->cssPath[] = $cssFilePath;
    }

    public function minify()
    {
        $allCss = [];
        if (empty($this->cssPath)) {
            echo "No CSS was added";
            exit;
        }

        foreach ($this->cssPath as $css) {
            $bits = explode(".", $css);
            $ext = $bits[count($bits) - 1];
            if ($ext !== "css") {
                echo "Only CSS allowed";
                exit;
            }
            $filename = basename($css);
            $css = file_get_contents($css);
            $css = preg_replace("/\s{2,}/", "", $css);
            $css = str_replace(["\n", ', ', ': ', '; ', ' > ', ' }', '} ', ';}', '{ ', ' {'], ['', ',', ':', ';', '>', '}', '}', '}', '{', '{'], $css);
            $css = preg_replace('/\/\*.*?\*\//s', '', $css);

            $allCss[] = '/* ' . $filename . ' */' . "\n" . $css;
        }

        return implode("\n", $allCss);
    }
}
