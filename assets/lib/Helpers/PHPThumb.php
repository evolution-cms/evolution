<?php namespace Helpers;
use Exception;
use Spatie\ImageOptimizer\OptimizerChainFactory;

include_once(MODX_BASE_PATH . 'assets/snippets/phpthumb/phpthumb.class.php');
require_once(MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');

/**
 * Class PHPThumb
 * @package Helpers
 */
class PHPThumb
{

    /** @var \phpthumb */
    private $thumb;
    /** @var FS */
    protected $fs;
    public $debugMessages = ''; //TODO refactor debug

    /**
     * PHPThumb constructor.
     */
    public function __construct()
    {
        $this->thumb = new \phpthumb();
        $this->fs = FS::getInstance();
    }

    /**
     * @param $inputFile
     * @param $outputFile
     * @param $options
     * @return bool
     */
    public function create($inputFile, $outputFile, $options)
    {
        $this->thumb->sourceFilename = $inputFile;
        $ext = explode('.', $inputFile);
        $ext = str_replace('jpeg', 'jpg', strtolower(array_pop($ext)));
        $options = 'f=' . $ext . '&' . $options;
        $this->setOptions($options);
        if ($this->thumb->GenerateThumbnail() && $this->thumb->RenderToFile($outputFile)) {
            return true;
        } else {
            if (!empty($this->thumb->debugmessages)) {
                $this->debugMessages = implode('<br>', $this->thumb->debugmessages);
            }

            return false;
        }
    }

    /**
     * @param string $type
     */
    public function optimize($file)
    {
        $file = MODX_BASE_PATH . $this->fs->relativePath($file);
        if (class_exists('Spatie\ImageOptimizer\OptimizerChain') && function_exists('proc_open')) {
            try {
                $optimizerChain = OptimizerChainFactory::create();
                $optimizerChain->optimize($file);
            } catch (Exception $e) {
                if (!empty($this->debugMessages)) {
                    $this->debugMessages .= '<br>';
                }
                $this->debugMessages .= $e->getMessage();
            };
        }
    }

    /**
     * @param $options
     */
    private function setOptions($options)
    {
        $options = strtr($options, array("," => "&", "_" => "=", '{' => '[', '}' => ']'));
        parse_str($options, $params);
        if (!is_array($params)) {
            $params = array();
        }

        foreach ($params as $key => $value) {
            $this->thumb->setParameter($key, $value);
        }
    }

    /**
     * @return string
     */
    public function getMessages() {
        return $this->debugMessages;
    }
}
