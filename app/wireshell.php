<?php

use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$app = new Application('wireshell - An extendable ProcessWire CLI', '1.0.0');

$coreCommandDir = __DIR__ . '/../src/Commands';

if (!is_dir($coreCommandDir)) {
    return;
}

$finderCoreCommands = new Finder();
$coreFiles = $finderCoreCommands->files()->name('*Command.php')->in($coreCommandDir);

foreach ($coreFiles as $coreFile) {

    $r = new \ReflectionClass('\\Wireshell\\Commands\\' . $coreFile->getRelativePath() . '\\'.$coreFile->getBasename('.php'));

    if ($r->isSubclassOf('Symfony\\Component\\Console\\Command\\Command')
        && !$r->isAbstract()
        && !$r->getConstructor()->getNumberOfRequiredParameters()) {

            $app->add($r->newInstance());
    }
}

$app->run();
