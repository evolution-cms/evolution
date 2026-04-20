<?php

use EvolutionCMS\Support\SystemSettingPathNormalizer;

test('it preserves the base path placeholder for portable storage values', function () {
    $basePath = 'D:/OSPanel/domains/test2.loc/';

    expect(SystemSettingPathNormalizer::normalizeStorageValue('filemanager_path', $basePath, $basePath))
        ->toBe('[(base_path)]');

    expect(SystemSettingPathNormalizer::normalizeStorageValue('filemanager_path', '[(base_path)]', $basePath))
        ->toBe('[(base_path)]');

    expect(SystemSettingPathNormalizer::normalizeStorageValue('rb_base_dir', $basePath . 'assets/', $basePath))
        ->toBe('[(base_path)]assets/');

    expect(SystemSettingPathNormalizer::normalizeStorageValue('rb_base_dir', '[(base_path)]assets/', $basePath))
        ->toBe('[(base_path)]assets/');
});

test('it keeps external paths untouched', function () {
    $basePath = 'D:/OSPanel/domains/test2.loc/';
    $externalPath = 'D:/shared/uploads/';

    expect(SystemSettingPathNormalizer::normalizeStorageValue('filemanager_path', $externalPath, $basePath))
        ->toBe($externalPath);

    expect(SystemSettingPathNormalizer::normalizeStorageValue('rb_base_dir', $externalPath, $basePath))
        ->toBe($externalPath);
});
