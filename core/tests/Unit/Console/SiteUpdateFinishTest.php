<?php

use EvolutionCMS\Console\SiteUpdateCommand;

/*
 * After Composer has replaced core/vendor, the process that runs make:site still holds the old
 * code. From 3.5 to 3.6 the update seeders then loaded a class of the new Laravel that needs one
 * only the new autoloader knows, and the update died with a fatal error, install/ left behind.
 * The new code finishes the update in a process of its own ("make:site finish").
 */

/** A make:site whose new code is the given source, and whose "make:site finish" child answers as given. */
function finishCommand(string $newSource, int $exitCode, array $output): SiteUpdateCommand
{
    $file = tempnam(sys_get_temp_dir(), 'evo-finish-');
    file_put_contents($file, $newSource);

    return new class($file, $exitCode, $output) extends SiteUpdateCommand {
        public array $commands = [];
        public array $lines = [];
        public int $stepsHere = 0;

        public function __construct(private string $file, private int $exitCode, private array $answer)
        {
            parent::__construct();
        }

        public function __destruct()
        {
            @unlink($this->file);
        }

        protected function newSiteUpdateCommandFile(): string
        {
            return $this->file;
        }

        protected function execCoreShellCommand(string $command, ?array &$output = null): int
        {
            $this->commands[] = $command;
            $output = $this->answer;

            return $this->exitCode;
        }

        protected function runFinishSteps(): void
        {
            $this->stepsHere++;
        }

        public function line($string, $style = null, $verbosity = null)
        {
            $this->lines[] = (string) $string;
        }

        public function error($string, $verbosity = null)
        {
            $this->lines[] = 'ERROR ' . $string;
        }
    };
}

function finishInvoke(SiteUpdateCommand $command): bool
{
    return (new ReflectionMethod($command, 'finishWithNewCode'))->invoke($command);
}

const FINISHING_SOURCE = "class SiteUpdateCommand extends Command\n{\n    switch (\$action) {\n        case 'update':\n        case 'finish':\n    }\n}";
const LEGACY_SOURCE = "class SiteUpdateCommand extends Command\n{\n    switch (\$action) {\n        case 'pizdato':\n        case 'update':\n    }\n}";

test('the new code finishes the update in its own process', function () {
    $command = finishCommand(FINISHING_SOURCE, 0, ['Finish the update with the new code', 'Run Core Migrations', 'Apply update seeders', 'Remove Install Directory']);

    expect(finishInvoke($command))->toBeTrue()
        ->and($command->commands)->toHaveCount(1)
        ->and($command->commands[0])->toEndWith(' artisan make:site finish --no-ansi')
        ->and($command->stepsHere)->toBe(0)
        // The live updater moves its progress on these lines, so they are passed on.
        ->and($command->lines)->toContain('Run Core Migrations')
        ->and($command->lines)->toContain('Remove Install Directory');
});

test('a release whose make:site has no finish ends its update here, as before', function () {
    $command = finishCommand(LEGACY_SOURCE, 1, []);

    expect(finishInvoke($command))->toBeTrue()
        ->and($command->commands)->toBe([])
        ->and($command->stepsHere)->toBe(1);
});

test('a new code that fails before it announces itself is reported, not replaced by the old one', function () {
    // A fatal error while artisan boots: no marker, exit code 255.
    $command = finishCommand(FINISHING_SOURCE, 255, ['PHP Fatal error:  Uncaught Error: Class "Foo" not found in core/src/Bar.php:12']);

    expect(finishInvoke($command))->toBeFalse()
        ->and($command->stepsHere)->toBe(0)
        ->and($command->lines)->toContain('PHP Fatal error:  Uncaught Error: Class "Foo" not found in core/src/Bar.php:12')
        ->and(implode("\n", $command->lines))->toContain('ERROR The new code could not finish the update (exit code 255). Run "php artisan make:site finish"');
});

test('a failure of the new code after it announced itself is reported, not repeated', function () {
    $command = finishCommand(FINISHING_SOURCE, 1, ['Finish the update with the new code', 'Run Core Migrations', 'SQLSTATE[HY000]: General error']);

    expect(finishInvoke($command))->toBeFalse()
        ->and($command->stepsHere)->toBe(0)
        ->and($command->lines)->toContain('SQLSTATE[HY000]: General error')
        ->and(implode("\n", $command->lines))->toContain('ERROR The new code could not finish the update (exit code 1).');
});

test('a make:site that cannot be read is a failure, and the old code runs nothing', function (string $case) {
    $command = finishCommand('', 1, []);
    $file = (new ReflectionMethod($command, 'newSiteUpdateCommandFile'))->invoke($command);
    unlink($file);
    if ($case === 'unreadable') {
        // A folder in its place: no read gives contents, as root or not.
        mkdir($file);
    } elseif ($case === 'truncated') {
        file_put_contents($file, "<?php\nnamespace EvolutionCMS\\Console;\n");
    }

    try {
        expect(finishInvoke($command))->toBeFalse()
            ->and($command->stepsHere)->toBe(0)
            ->and($command->commands)->toBe([])
            ->and(implode("\n", $command->lines))->toContain('ERROR Could not read ' . $file);
    } finally {
        if (is_dir($file)) {
            rmdir($file);
        }
    }
})->with(['missing', 'unreadable', 'truncated']);

test('this make:site has finish, so a later update recognises it', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');

    expect($source)->toContain('class SiteUpdateCommand')
        ->and($source)->toContain("case 'finish':");
});

test('make:site finish announces itself before any step and runs every step after Composer', function () {
    $source = (string) file_get_contents(dirname(__DIR__, 3) . '/src/Console/SiteUpdateCommand.php');
    $case = substr($source, strpos($source, "case 'finish':"));
    $case = substr($case, 0, strpos($case, 'return self::SUCCESS;'));
    $steps = substr($source, strpos($source, 'protected function runFinishSteps('));
    $steps = substr($steps, 0, strpos($steps, "\n    }\n"));
    $update = substr($source, strpos($source, 'protected function applyUpdate()'));
    $update = substr($update, 0, strpos($update, "\n    }\n"));

    expect(strpos($case, '$this->line(self::FINISH_MARKER);'))->toBeLessThan(strpos($case, '$this->runFinishSteps();'))
        ->and($steps)->toContain('$this->runCoreMigrations();')
        ->and($steps)->toContain('$this->runUpdateSeeders();')
        ->and($steps)->toContain('$this->clearCompiledViews();')
        ->and($steps)->toContain('$this->updateBundledExtrasModule();')
        ->and($steps)->toContain('$this->syncSettingsVersion();')
        ->and($steps)->toContain("self::rmdirs(EVO_BASE_PATH . 'install');")
        // last: a step that fails leaves the sessions alone
        ->and(trim(substr($steps, strrpos($steps, '$this->'))))->toBe('$this->updateInstallTime();')
        // Nothing of it runs in the updating process any more, except for a release without finish.
        ->and($update)->not->toContain('$this->runCoreMigrations();')
        ->and($update)->not->toContain('$this->runUpdateSeeders();')
        ->and(strpos($update, '$applied = $this->withRollback('))->toBeLessThan(strpos($update, '$this->finishWithNewCode()'));
});
