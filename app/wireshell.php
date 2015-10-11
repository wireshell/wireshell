<?php
use Symfony\Component\Console\Application;
use Symfony\Component\Finder\Finder;

if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}

$app = new Application('wireshell - An extendable ProcessWire CLI', '1.0.0alpha1');

/**
 * Get Command files in provided directory
 * @param $dir
 * @return Finder
 */
function getCommandFilesInDir($dir) {

    $finder = new Finder();

    if(is_dir($dir)) {

        $finder->files()->name('*Command.php')->in($dir);
    }

    return $finder;
}

/**
 * New up Command class from provided file
 * @param $file
 * @return ReflectionClass
 */
function newUpCommandClassFromFile($file, $namespace) {

    return new \ReflectionClass($namespace .
        $file->getRelativePath() . '\\'.
        $file->getBasename('.php')
    );
}

/**
 * Check if Symfony Command
 * @param $command
 * @return bool
 */
function isASymphonyCommand($command) {

    return ($command->isSubclassOf('Symfony\\Component\\Console\\Command\\Command')
        && !$command->isAbstract()
        && !$command->getConstructor()->getNumberOfRequiredParameters());
}

/**
 *
 * @param $command
 * @param $app
 * @return mixed
 */
function registerCommand($command, $app) {

    if (isASymphonyCommand($command)) {

        $app->add($command->newInstance());
    }

    return $app;
}

/**
 * @param $files
 * @param $app
 * @return mixed
 */
function loadCommandsInFilesForApp($files, $app, $namespace) {

    foreach ($files as $file) {

        $command = newUpCommandClassFromFile($file, $namespace);

        $app = registerCommand($command, $app);
    }

    return $app;
}

/**
 * Initialize core Commands
 */

$srcDir = __DIR__ . '/../src/Commands';

$coreFiles = getCommandFilesInDir($srcDir);

$app = loadCommandsInFilesForApp($coreFiles, $app, '\\Wireshell\\Commands\\');



/**
 * Initialize module provided commands
 */

$moduleDir = getcwd() . '/site/modules';
//
$moduleFiles = getCommandFilesInDir($moduleDir);
//
$app = loadCommandsInFilesForApp($moduleFiles, $app, '');

$app->run();
