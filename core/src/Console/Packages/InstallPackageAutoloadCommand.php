<?php namespace EvolutionCMS\Console\Packages;


class InstallPackageAutoloadCommand extends InstallPackageRequireCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:installautoload {key} {value} {composer_run=1}';


    public function updateArray()
    {
        $this->composerArray['autoload']['psr-4'][$this->argument('key')] = $this->argument('value');
    }

    /**
     * An autoload mapping changes no package, so rebuilding the autoloader is enough.
     *
     * Without this the inherited arguments fell back to a bare `update` of every
     * dependency of the site, just to register one namespace.
     *
     * @return array<string,mixed>
     */
    public function buildComposerArguments(bool $minimalChanges = false): array
    {
        return ['command' => 'dump-autoload'];
    }
}
