<?php namespace EvolutionCMS\Extensions;

use Illuminate\Support\Str;
use Illuminate\View\Compilers\BladeCompiler as IlluminateBladeCompiler;

/**
 * Blade compiler that keys a compiled view on the file time of its template.
 *
 * Laravel names a compiled view after the template path alone and recompiles it
 * only when the template is newer. Replacing the code by any means - make:site,
 * an archive or Composer overlay, git - gives templates the time of their commit,
 * which is older than the compiled view, so the views of the previous version went
 * on running against the new core: switching 3.5.8 to 3.5.x left the dashboard
 * calling SimplePie, which 3.5.x no longer ships. With the file time in the name,
 * any change of that time, forward or back, is another compiled view, and the new
 * file name also keeps OPcache from serving the old one.
 *
 * @since 3.5.9
 */
class BladeCompiler extends IlluminateBladeCompiler
{
    /**
     * @param string $path
     * @return string
     */
    public function getCompiledPath($path)
    {
        $time = @filemtime($path);

        return $this->cachePath . '/' . $this->compiledName($path)
            . ($time ? '-' . $time : '') . '.' . $this->compiledExtension;
    }

    /**
     * @param string|null $path
     * @return void
     */
    public function compile($path = null)
    {
        parent::compile($path);

        $this->forgetPreviousVersions((string) $this->getPath());
    }

    /**
     * The name Laravel gives the compiled view, without the file time.
     *
     * @param string $path
     * @return string
     */
    protected function compiledName($path): string
    {
        return hash('xxh128', 'v2' . Str::after($path, $this->basePath));
    }

    /**
     * Drop the views compiled from earlier file times of the same template.
     *
     * They are never read again, and editing a template would otherwise leave one
     * behind on every save.
     *
     * @param string $path
     * @return void
     */
    protected function forgetPreviousVersions(string $path): void
    {
        if ($path === '' || is_null($this->cachePath)) {
            return;
        }

        $current = $this->getCompiledPath($path);
        $pattern = $this->cachePath . '/' . $this->compiledName($path) . '*.' . $this->compiledExtension;

        foreach (glob($pattern) ?: [] as $file) {
            if ($file !== $current) {
                @unlink($file);
            }
        }
    }
}
