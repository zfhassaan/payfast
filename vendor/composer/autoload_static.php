<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit93b4e8e4b837fccdc2253e02fe9f2d5e
{
    public static $prefixLengthsPsr4 = array (
        'z' => 
        array (
            'zfhassaan\\Payfast\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'zfhassaan\\Payfast\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit93b4e8e4b837fccdc2253e02fe9f2d5e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit93b4e8e4b837fccdc2253e02fe9f2d5e::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit93b4e8e4b837fccdc2253e02fe9f2d5e::$classMap;

        }, null, ClassLoader::class);
    }
}
