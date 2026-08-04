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
            $this->error('Package requirement name is empty.');
            return false;
        }

        foreach (array_keys($this->composerArray['require']) as $requireKey) {
            if ($this->matchesRequirementKey((string) $requireKey, $target)) {
                unset($this->composerArray['require'][$requireKey]);
                $this->info('Removed package requirement: ' . $requireKey);
                return true;
            }
        }

        $this->error('Package requirement not found: ' . $this->argument('key'));
        return false;
    }

    protected function matchesRequirementKey(string $requireKey, string $target): bool
    {
        $requireKey = strtolower(trim($requireKey));
        if ($requireKey === $target) {
            return true;
        }

        $packageName = basename(str_replace('\\', '/', $requireKey));

        return $packageName === $target;
    }
}
