<?php namespace EvolutionCMS;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * @property Core $app
 */
class ServiceProvider extends BaseServiceProvider
{
    /**
     * Mass registration of virtual snippets using namespace
     *
     * @param $path
     * @param $namespace
     * @throws \Exception
     */
    protected function loadSnippetsFrom($path, $namespace = '')
    {
        $found = $this->app->findElements('snippet', $path, ['php']);
        foreach ($found as $name => $code) {
            $this->addSnippet($name, $code, $namespace);
        }
    }

    /**
     * Mass registration of virtual chunks using namespace
     *
     * @param $path
     * @param $namespace
     * @throws \Exception
     */
    protected function loadChunksFrom($path, $namespace = '')
    {
        $found = $this->app->findElements('chunk', $path, ['tpl', 'html']);
        foreach ($found as $name => $code) {
            $this->addChunk($name, $code, $namespace);
        }
    }

    /**
     * Bulk registration of virtual plugins
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
     * Registering a virtual snippet using namespace
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
     * Registering a virtual chunk using namespace
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
