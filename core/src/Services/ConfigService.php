<?php namespace EvolutionCMS\Services;


class ConfigService
{
    public function get($config = '', $default = null)
    {
        return evo()->getConfig($config, $default);
    }
    public function set($name, $value)
    {
        evo()->setConfig($name, $value);
    }

}
