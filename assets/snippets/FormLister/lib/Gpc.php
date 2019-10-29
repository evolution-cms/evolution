<?php namespace Helpers;

/**
 * Class Gpc
 * @package Helpers
 */
class Gpc {
    private $gpc_seed = '';
    private $fields = array();

    /**
     * Gpc constructor.
     * @param array $fields
     */
    public function __construct (array $fields = array())
    {
        $this->setGpcSeed();
        $this->fields = $fields;
    }

    /**
     * @return string
     */
    private function setGpcSeed ()
    {
        $this->gpc_seed = 'sanitize_seed_' . base_convert(md5(realpath(MODX_MANAGER_PATH . 'includes/protect.inc.php')),
                16, 36);

        return;
    }

    /**
     * Remove fucking modX_sanitize_gpc
     *
     * @param $target
     * @param int $count
     * @return mixed
     */
    public function removeGpc (&$target, $count = 0)
    {
        if (empty($this->gpc_seed) || empty($this->fields)) return;

        foreach ($target as $key => $value) {
            if (!in_array($key, $this->fields)) {
                continue;
            }
            if (is_array($value)) {
                $count++;
                if (10 < $count) {
                    break;
                }
                $this->removeGpc($value, $count);
                $count--;
            } else {
                $value = str_replace($this->gpc_seed, '', $value);
                $value = str_replace('sanitized_by_modx<s cript', '<script', $value);
                $value = str_replace('sanitized_by_modx& ', '&', $value);
                $target[$key] = $value;
            }
        }

        return;
    }

    /**
     * @return array
     */
    public function getFields() {
        return $this->fields;
    }
}
