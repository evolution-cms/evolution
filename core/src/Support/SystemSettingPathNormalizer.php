<?php

namespace EvolutionCMS\Support;

final class SystemSettingPathNormalizer
{
    private const BASE_PATH_PLACEHOLDER = '[(base_path)]';

    public static function normalizeStorageValue(string $settingName, string $value, string $basePath): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $value = self::collapseBasePathPlaceholder($value, $basePath);

        if ($settingName === 'filemanager_path' && rtrim($value, '/') === self::BASE_PATH_PLACEHOLDER) {
            return self::BASE_PATH_PLACEHOLDER;
        }

        if ($settingName === 'rb_base_dir' && rtrim($value, '/') === self::BASE_PATH_PLACEHOLDER . 'assets') {
            return self::BASE_PATH_PLACEHOLDER . 'assets/';
        }

        return rtrim($value, '/') . '/';
    }

    public static function collapseBasePathPlaceholder(string $value, string $basePath): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $normalizedValue = self::normalizeDirectory($value);
        $normalizedBasePath = self::normalizeDirectory($basePath);

        if ($normalizedBasePath === '') {
            return $value;
        }

        $compareValue = self::comparisonPath($normalizedValue);
        $compareBasePath = self::comparisonPath($normalizedBasePath);

        if ($compareValue === rtrim($compareBasePath, '/')) {
            return self::BASE_PATH_PLACEHOLDER;
        }

        if (strpos($compareValue, $compareBasePath) !== 0) {
            return $value;
        }

        $suffix = ltrim(substr($normalizedValue, strlen($normalizedBasePath)), '/');

        if ($suffix === '') {
            return self::BASE_PATH_PLACEHOLDER;
        }

        return self::BASE_PATH_PLACEHOLDER . $suffix;
    }

    private static function normalizeDirectory(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        return rtrim($path, '/') . '/';
    }

    private static function comparisonPath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:\//', $path) === 1) {
            return strtolower($path);
        }

        return $path;
    }
}
