<?php

require_once 'vendor/autoload.php';

if (file_exists(__DIR__.'/../../autoload.php')) {
    $loader = require __DIR__.'/../../autoload.php';
} else {
    $loader = require __DIR__.'/vendor/autoload.php';
}

$loader->setPsr4('Wireshell\\Tests\\', __DIR__ . '/Tests');

return $loader;
