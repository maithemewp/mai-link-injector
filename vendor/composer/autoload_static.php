<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8b2b4a5b2c978dfc98d1096cf2fc07ac
{
    public static $files = array (
        '45a16669595eb3c0a9e2994e57fc3188' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/load-v5p3.php',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit8b2b4a5b2c978dfc98d1096cf2fc07ac::$classMap;

        }, null, ClassLoader::class);
    }
}