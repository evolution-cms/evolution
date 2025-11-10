<?php namespace EvolutionCMS\Console;

use EvolutionCMS\AliasLoader;
use EvolutionCMS\Services\TailwindService;
use Illuminate\Console\Command;
use ReflectionClass;

class TailwindBuildCommand extends Command
{
    protected $signature   = 'tailwind:build {package? : Package name or * for all} {--force : Rebuild even if cached}';
    protected $description = 'Compile Tailwind CSS for one or all packages.';

    public function handle(): int
    {
        $packageArg = $this->argument('package');
        $force = $this->option('force');
        [$labels, $map] = $this->discoverPackages();

        if (empty($packageArg)) {
            $packageArg = $this->choice(
                'Select package to build (Package name or * for all)',
                array_merge(['*'], $labels),
                0
            );
        }

        $packages = ($packageArg === '*') ? array_values($map) : [$map[strtolower($packageArg)] ?? null];

        $compiler = app(TailwindService::class);

        foreach ($packages as $pkg) {
            $styles = glob(EVO_BASE_PATH . $pkg . '/*tailwind.css');
            foreach ($styles as $style) {
                try {
                    $url = $compiler->compile($style, $force);
                    $this->info("✔ {$pkg}: built → {$url}");
                } catch (\Throwable $e) {
                    $this->error("✖ {$pkg}: " . $e->getMessage());
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array [labels, map]
     */
    private function discoverPackages(): array
    {
        $labels = [];
        $map    = [];

        /* -------------------- assets/<name> -------------------- */
        $root = EVO_BASE_PATH . 'assets';
        if (is_dir($root)) {
            foreach (scandir($root) as $dir) {
                if ($dir[0] === '.') continue;

                $base = "{$root}/{$dir}/";
                if (is_file($base . 'tailwind.css')) {
                    $pretty = $this->guessPrettyName(realpath($base));
                    $labels[] = $pretty;
                    $map[strtolower($pretty)] = "assets/{$dir}";
                }
            }
        }

        /* -------------------- core/vendor/<vendor>/<pkg>/css -------------------- */
        $root = EVO_CORE_PATH . 'vendor';
        if (is_dir($root) && ($vendors = scandir($root))) {
            foreach ($vendors as $vendor) {
                if ($vendor[0] === '.') continue;
                $vendorPath = "{$root}/{$vendor}";
                if (!is_dir($vendorPath)) continue;
                $packages = scandir($vendorPath);
                if (!is_array($packages)) continue;
                foreach ($packages as $pkg) {
                    if ($pkg[0] === '.') continue;
                    $base = "{$vendorPath}/{$pkg}/";

                    if (is_file($base.'css/tailwind.css')) {
                        $pretty = $this->guessPrettyName(realpath($base));
                        $key = strtolower($pretty);

                        if (isset($map[$key])) {
                            $pretty = "{$vendor}/{$pretty}";
                        }

                        $labels[] = $pretty;
                        $map[strtolower($pretty)] = "core/vendor/{$vendor}/{$pkg}/css";
                    }
                }
            }
        }

        /* -------------------- manager/media/style/<theme>/css -------------------- */
        $root = EVO_MANAGER_PATH . 'media/style';
        if (is_dir($root)) {
            foreach (scandir($root) as $theme) {
                if ($theme[0] === '.') continue;

                $base = "{$root}/{$theme}/";
                if (is_file($base.'css/tailwind.css')) {
                    $pretty = "theme:{$theme}";
                    $labels[]  = $pretty;
                    $map[strtolower($pretty)] = "manager/media/style/{$theme}/css";
                }
            }
        }

        /* -------------------- css/ (frontend) -------------------- */
        $styles = glob(EVO_BASE_PATH . 'css/*tailwind.css');
        if (count($styles)) {
            $labels[] = 'frontend';
            $map['frontend'] = 'css';
        }

        sort($labels, SORT_NATURAL | SORT_FLAG_CASE);
        return [$labels, $map];
    }

    /**
     * Повертає “красиве” ім’я пакета, якщо у Facade-аліасів знайдено клас
     * всередині переданої директорії. Інакше – basename($path).
     *
     * @param  string $absPath  Абсолютний шлях до пакета
     * @return string
     */
    private function guessPrettyName(string $absPath): string
    {
        /** @var AliasLoader $loader */
        $loader = AliasLoader::getInstance();
        foreach ($loader->getAliases() as $alias => $class) {
            try {
                $ref = new ReflectionClass($class);
                $file = $ref->getFileName();
                if ($file && str_starts_with($file, $absPath)) {
                    return $alias;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return basename($absPath);
    }
}
