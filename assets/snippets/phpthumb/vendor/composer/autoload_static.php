<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit59b4f21b3db48af42f3ca10324cc4c65
{
    public static $files = array (
        '532945a4b12d830ff3e086cc36a64375' => __DIR__ . '/..' . '/james-heinrich/phpthumb/phpthumb.class.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit59b4f21b3db48af42f3ca10324cc4c65::$classMap;

        }, null, ClassLoader::class);
    }
}
