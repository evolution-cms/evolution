<?php namespace EvolutionCMS\Console\Packages;

class RemovePackageRequireCommand extends InstallPackageRequireCommand
{
    /**
     * @var string
     */
    protected $signature = 'package:removerequire {key} {composer_run=1}';

    /**
     * @var string
     */
    protected $description = 'Remove composer package from custom composer requirements';

    public function updateArray()
    {
        if (!isset($this->composerArray['require']) || !is_array($this->composerArray['require'])) {
            $this->composerArray['require'] = [];
        }

        $target = strtolower(trim((string) $this->argument('key')));
        if ($target === '') {
            return;
        }

        foreach (array_keys($this->composerArray['require']) as $requireKey) {
            if (strtolower(trim((string) $requireKey)) === $target) {
                unset($this->composerArray['require'][$requireKey]);
            }
        }
    }
}
