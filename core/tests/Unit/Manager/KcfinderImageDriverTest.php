<?php

$mcpuk = dirname(__DIR__, 4) . '/manager/media/browser/mcpuk';
foreach (['image' => 'class_image.php', 'image_gd' => 'class_image_gd.php',
    'image_imagick' => 'class_image_imagick.php', 'image_gmagick' => 'class_image_gmagick.php'] as $class => $file) {
    if (!class_exists($class, false)) {
        require_once $mcpuk . '/lib/' . $file;
    }
}

class image_kcftest_on
{
    static function available() { return true; }
}

class image_kcftest_other
{
    static function available() { return true; }
}

class image_kcftest_off
{
    static function available() { return false; }
}

it('takes the first available driver of the configured priority', function () {
    expect(image::resolveDriver('kcftest_off  kcftest_on kcftest_other', []))->toBe('kcftest_on');
});

it('falls back to the default chain when the priority names nothing available', function (?string $priority) {
    expect(image::resolveDriver($priority, ['kcftest_off', 'kcftest_other']))->toBe('kcftest_other');
})->with([null, '', 'kcftest_off', 'missing_driver', 'bad;name']);

it('reports no driver when neither the priority nor the fallback is available', function () {
    expect(image::resolveDriver('kcftest_off', ['kcftest_off', 'missing_driver']))->toBeFalse();
});

it('tries GD, then Imagick, then Gmagick by default', function () {
    expect(image::DEFAULT_DRIVERS)->toBe(['gd', 'imagick', 'gmagick']);
});

it('picks the default driver this PHP actually supports', function () {
    $expected = function_exists('imagecreatefromjpeg') ? 'gd'
        : (class_exists('Imagick') ? 'imagick' : (class_exists('Gmagick') ? 'gmagick' : false));

    expect(image::resolveDriver(null))->toBe($expected);
});

it('selects the uploader and upload check driver through the fallback chain', function () use ($mcpuk) {
    $uploader = file_get_contents($mcpuk . '/core/uploader.php');
    $typeImg = file_get_contents($mcpuk . '/core/types/type_img.php');

    expect($uploader)->toContain("image::resolveDriver(\$this->config['imageDriversPriority'] ?? null)")
        ->and($uploader)->toContain("\$this->config['imageDriversPriority'] = \$driver;")
        ->and($typeImg)->toContain("image::resolveDriver(\$config['imageDriversPriority'] ?? null)")
        ->and($typeImg)->not->toContain(': "gd";');
});
