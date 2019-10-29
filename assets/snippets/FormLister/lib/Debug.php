<?php namespace Helpers;

/**
 * Created by PhpStorm.
 * User: Pathologic
 * Date: 24.05.2016
 * Time: 12:59
 */

/**
 * Class Debug
 * @package Helpers
 */
class Debug
{
    protected $modx = null;
    private $log = array();
    private $timeStart = array();
    private $caller = 'Debug Helper';

    /**
     * Debug constructor.
     * @param \DocumentParser $modx
     * @param array $cfg
     */
    public function __construct(\DocumentParser $modx, $cfg = array())
    {
        $this->modx = $modx;
        $this->timeStart = microtime(true);
        if (isset($cfg['caller'])) {
            $this->caller = $cfg['caller'];
        }
    }

    /**
     * @param $message
     * @param array $data
     */
    public function log($message, $data = array())
    {
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $data = array_pop($data);
        }
        $this->log[] = array(
            'message' => $message,
            'data'    => $this->dumpData($data, 'pre'),
            'time'    => microtime(true) - $this->timeStart
        );
    }

    /**
     * @param $data
     * @param string $wrap
     * @param string $charset
     * @return array|mixed|string
     */
    public function dumpData($data, $wrap = '', $charset = 'UTF-8')
    {
        $out = \APIHelpers::sanitarTag(print_r($this->cleanData($data), 1), $charset);
        if (!empty($wrap) && is_string($wrap)) {
            $out = "<{$wrap}>{$out}</{$wrap}>";
        }

        return $out;
    }

    /**
     * @param $data
     * @return array
     */
    public function cleanData($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_object($value)) {
                    $data[$key] = 'Object: ' . get_class($value);
                } elseif (is_array($value)) {
                    $data[$key] = $this->cleanData($value);
                }
            }
            if (empty($data)) {
                $data = 'No data provided' . PHP_EOL;
            }
        }

        return $data;
    }

    public function saveLog()
    {
        $out = '<style>pre {font-size:14px;}</style>';
        foreach ($this->log as $entry) {
            $out .= "<h3>{$entry['message']}</h3>";
            if ($entry['data']) {
                $out .= $entry['data'];
            }
            $out .= "<p>Time: {$entry['time']}</p>";
            $out .= '<hr>';
        }
        $time = microtime(true) - $this->timeStart;
        $out .= "<p>Total time: {$time}</p>";
        if ($out) {
            $this->modx->logEvent(0, 1, $out, $this->caller);
        }
    }
}
