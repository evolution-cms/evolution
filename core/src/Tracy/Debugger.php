<?php namespace EvolutionCMS\Tracy;

use Tracy\Debugger as BaseDebugger;

class Debugger extends BaseDebugger
{
    public static function enable($mode = null, string|null $logDirectory = null, $email = null): void
    {
        parent::enable($mode, $logDirectory, $email);

        set_error_handler([__CLASS__, 'errorHandler']);
    }

    public static function errorHandler(int $severity, string $message, string $file, int $line, ?array $context = []): bool
    {
        $evo = evo();
        if (!empty($evo->currentSnippet)) {
            $file = "Snippet: $evo->currentSnippet";
        }

        if (!empty($evo->event->activePlugin)) {
            $file = "Plugin: {$evo->event->name}[{$evo->event->activePlugin}]";
        }

        return parent::errorHandler($severity, $message, $file, $line, $context);
    }
}
