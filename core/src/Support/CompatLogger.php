<?php

namespace EvolutionCMS\Support;

use Illuminate\Support\Facades\Log;

class CompatLogger
{
    public static function log(array $data): void
    {
        // Prevent secrets from ever reaching logs.
        unset($data['password'], $data['token'], $data['csrf']);

        $payload = array_merge([
            'ts' => now()->toISOString(),
            'ip' => request()->ip(),
            'ua' => substr((string) request()->userAgent(), 0, 255),
        ], $data);

        // key=value format, single line
        $line = collect($payload)
            ->map(fn ($v, $k) => $k . '=' . self::sanitize($v))
            ->implode(' ');

        Log::channel('manager_compat')->warning($line);
    }

    protected static function sanitize($value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return str_replace(["\n", "\r", ' '], ['_', '', '_'], (string) $value);
        }

        return 'complex';
    }
}
