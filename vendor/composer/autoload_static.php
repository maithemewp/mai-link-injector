<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8b2b4a5b2c978dfc98d1096cf2fc07ac
{
    public static $files = array (
        '256558b1ddf2fa4366ea7d7602798dd1' => __DIR__ . '/..' . '/yahnis-elsts/plugin-update-checker/load-v5p5.php',
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
