<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitad31f6215e50efeea301f674ab3183ec
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitad31f6215e50efeea301f674ab3183ec::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitad31f6215e50efeea301f674ab3183ec::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitad31f6215e50efeea301f674ab3183ec::$classMap;

        }, null, ClassLoader::class);
    }
}
