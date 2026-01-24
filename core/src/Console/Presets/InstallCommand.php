<?php namespace EvolutionCMS\Console\Presets;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'preset:install
        {--from= : Git repo URL or local path to preset}
        {--ref= : Git branch/tag (optional)}
        {--keep : Keep cloned preset directory}
        {--path= : Target Evo install path}
        {--preset= : Preset name (optional override)}
        {--force : Run preset seeders without prompt}
        {--no-composer : Skip composer dump-autoload}
        {--delete : Delete files not present in source}
        {--dry-run : Show actions without changing files}';

    protected $description = 'Install a preset from Git or local path.';

    public function handle(): int
    {
        $from = (string) $this->option('from');
        if ($from === '') {
            $this->error('Option --from is required (git URL or local path).');
            return 1;
        }

        $args = [
            '--from' => $from,
        ];

        $ref = (string) $this->option('ref');
        if ($ref !== '') {
            $args['--ref'] = $ref;
        }

        if ((bool) $this->option('keep')) {
            $args['--keep'] = true;
        }

        $path = (string) $this->option('path');
        if ($path !== '') {
            $args['--path'] = $path;
        }

        $preset = (string) $this->option('preset');
        if ($preset !== '') {
            $args['--preset'] = $preset;
        }

        if ((bool) $this->option('force')) {
            $args['--force'] = true;
        }

        if ((bool) $this->option('no-composer')) {
            $args['--no-composer'] = true;
        }

        if ((bool) $this->option('delete')) {
            $args['--delete'] = true;
        }

        if ((bool) $this->option('dry-run')) {
            $args['--dry-run'] = true;
        }

        return $this->call('preset:apply', $args);
    }
}
