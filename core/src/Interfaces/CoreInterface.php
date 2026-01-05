<?php namespace EvolutionCMS\Interfaces;
use EvolutionCMS\Database;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;

interface CoreInterface extends ApplicationContract
{
    /**
     * @param $type
     * @param $scanPath
     * @param array $ext
     *
     * @return array
     *
     * @throws \Exception
     */
    public function findElements($type, $scanPath, array $ext);

    /**
     * @param string $name
     * @param string $phpCode
     * @param string $namespace
     * @param array defaultParams
     */
    public function addSnippet($name, $phpCode, $namespace = '#', array $defaultParams = []);

    /**
     * @param string $name
     * @param string $text
     * @param string $namespace
     */
    public function addChunk($name, $text, $namespace = '#');

    /**
     * @return Database
     */
    public function getDatabase() : Database;

    /**
     * Returns an entry from the config
     *
     * Note: most code accesses the config array directly and we will continue to support this.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getConfig($name = '', $default = null);
}
