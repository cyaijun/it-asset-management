<?php

// autoload.php for PhpSpreadsheet manual installation

$autoloadDir = __DIR__;
$phpspreadsheetPath = $autoloadDir . '/phpoffice/PhpSpreadsheet';
$psrCachePath = $autoloadDir . '/psr/simple-cache/src';
$composerPcrePath = $autoloadDir . '/composer/pcre/src';
$zipstreamPath = $autoloadDir . '/zipstream/src';

// 注册自动加载器
spl_autoload_register(function ($class) {
    global $autoloadDir, $phpspreadsheetPath, $psrCachePath, $composerPcrePath, $zipstreamPath;

    // 处理 ZipStream 命名空间
    $zipPrefix = 'ZipStream\\';
    $zipBaseDir = $zipstreamPath . '/';

    $zipLen = strlen($zipPrefix);
    if (strncmp($zipPrefix, $class, $zipLen) === 0) {
        $relativeClass = substr($class, $zipLen);
        $file = $zipBaseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // 处理 Composer\Pcre 命名空间
    $pcrePrefix = 'Composer\\Pcre\\';
    $pcreBaseDir = $composerPcrePath . '/';

    $pcreLen = strlen($pcrePrefix);
    if (strncmp($pcrePrefix, $class, $pcreLen) === 0) {
        $relativeClass = substr($class, $pcreLen);
        $file = $pcreBaseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // 处理 PhpOffice\PhpSpreadsheet 命名空间
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $baseDir = $phpspreadsheetPath . '/src/PhpSpreadsheet/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // 处理 Psr\SimpleCache 命名空间
    $psrPrefix = 'Psr\\SimpleCache\\';
    $psrBaseDir = $psrCachePath . '/';

    $psrLen = strlen($psrPrefix);
    if (strncmp($psrPrefix, $class, $psrLen) === 0) {
        $relativeClass = substr($class, $psrLen);
        $file = $psrBaseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});

