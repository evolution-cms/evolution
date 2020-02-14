<?php namespace EvolutionCMS;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * @property Core $app
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Массовая регистрация виртуальных сниппетов с использованием неймспейса
     *
     * @param $path
     * @param $namespace
     * @throws \Exception
     */
    protected function loadSnippetsFrom($path, $namespace = '')
    {
        $found = $this->app->findElements('snippet', $path, array('php'));
        foreach ($found as $name => $code) {
            $this->addSnippet($name, $code, $namespace);
        }
    }

    /**
     * Массовая регистрация виртуальных чанков с использованием неймспейса
     *
     * @param $path
     * @param $namespace
     * @throws \Exception
     */
    protected function loadChunksFrom($path, $namespace = '')
    {
        $found = $this->app->findElements('chunk', $path, array('tpl', 'html'));
        foreach ($found as $name => $code) {
            $this->addChunk($name, $code, $namespace);
        }
    }

    /**
     * Массовая регистрация виртуальных плагинов
     *
     * @param $path
     * @throws \Exception
     */
    protected function loadPluginsFrom($path)
    {
        foreach (glob($path . '*.php') as $file) {
            include $file;
        }
    }


    /**
     * Регистрация виртуального сниппета с использованием неймспейса
     *
     * @param $name
     * @param $code
     * @param $namespace
     */
    protected function addSnippet($name, $code, $namespace = '')
    {
        $this->app->addSnippet($name, $code, !empty($namespace) ? $namespace . '#' : '');
    }

    /**
     * Регистрация виртуального чанка с использованием неймспейса
     *
     * @param $name
     * @param $code
     * @param $namespace
     */
    protected function addChunk($name, $code, $namespace = '')
    {
        $this->app->addChunk($name, $code, !empty($namespace) ? $namespace . '#' : '');
    }
}
