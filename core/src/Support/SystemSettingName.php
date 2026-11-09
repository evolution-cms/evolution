<?php

namespace EvolutionCMS\Support;

/**
 * Which posted names save_settings may store as a system setting.
 *
 * MySQL and MariaDB compare `setting_name` by the column's collation (utf8mb4_general_ci by
 * default): letter case, accents, trailing spaces and ignorable characters fold away, so
 * `USE_UDPERMS` or `use_udpérms` would update the `use_udperms` row while every check in PHP
 * saw another name. A name is stored only when PHP and the database are bound to agree on it:
 * plain ASCII (letters, digits, `_`), and the spelling of an existing setting when one matches
 * it case-insensitively. Settings such as `jpegQuality` keep their mixed case.
 */
final class SystemSettingName
{
    /**
     * @param string[] $existing names of the settings already stored
     */
    public static function storable(string $name, array $existing): bool
    {
        if (preg_match('/^[A-Za-z0-9_]+$/', $name) !== 1) {
            return false;
        }

        if (in_array($name, $existing, true)) {
            return true;
        }
        foreach ($existing as $stored) {
            if (strcasecmp((string) $stored, $name) === 0) {
                return false;
            }
        }

        return true;
    }
}
