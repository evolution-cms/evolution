<?php namespace EvolutionCMS\Interfaces;

interface CoreInterface
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
    public function addSnippet($name, $phpCode, $namespace = '#', array $defaultParams = array());

    /**
     * @param string $name
     * @param string $text
     * @param string $namespace
     */
    public function addChunk($name, $text, $namespace = '#');
}
