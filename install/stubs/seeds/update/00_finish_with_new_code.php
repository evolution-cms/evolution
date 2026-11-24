<?php

/*
 * Not a seeder: make:site of 3.5.7 - 3.5.9 requires the files of this folder, in name order, from its
 * runUpdateSeeders(), still running the old code and the old autoloader against the vendor Composer has
 * just replaced. The first query would then load a class of the new Laravel that only the new autoloader
 * knows, a fatal error. So the new code takes over here, as the newer make:site does on its own
 * (SiteUpdateCommand::finishWithNewCode()), and the old process ends: what it has left needs the database too.
 */
if (
    !isset($this)
    || !$this instanceof \EvolutionCMS\Console\SiteUpdateCommand
    || method_exists($this, 'finishWithNewCode')
) {
    return;
}

(function (): void {
    $command = (PHP_SAPI === 'cli' && PHP_BINARY !== '' ? escapeshellarg(PHP_BINARY) : 'php') . ' artisan make:site finish --no-ansi';
    $output = [];
    $exitCode = 1;
    if (method_exists($this, 'execCoreShellCommand')) {
        $exitCode = $this->execCoreShellCommand($command, $output);
    } else {
        exec('cd ' . escapeshellarg(EVO_CORE_PATH) . ' && ' . $command . ' 2>&1', $output, $exitCode);
    }

    foreach ($output as $line) {
        $this->line((string) $line);
    }

    // SiteUpdateCommand::FINISH_MARKER of the new code, which is not the class loaded here.
    if ((int) $exitCode !== 0 || !in_array('Finish the update with the new code', array_map('trim', $output), true)) {
        throw new \RuntimeException('The new code could not finish the update (exit code ' . (int) $exitCode
            . '). Run "php artisan make:site finish" in ' . EVO_CORE_PATH . ' once the cause is fixed.');
    }

    if (method_exists($this, 'describeInstalledVersion')) {
        $version = $this->describeInstalledVersion();
    } else {
        $data = include EVO_CORE_PATH . 'factory/version.php';
        $version = is_array($data) ? (string) ($data['full_appname'] ?? '') : '';
    }
    $this->line('<fg=yellow;bg=blue>Now You use ' . $version . '</>');

    exit(0);
})->call($this);
