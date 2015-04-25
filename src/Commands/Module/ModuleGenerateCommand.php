<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Wireshell\PwConnector;

/**
 * Class ModuleGenerateCommand
 *
 * modules.pw
 *
 * @package Wireshell
 * @author Nico
 * @author Marcus Herrmann
 */

class ModuleGenerateCommand extends PwConnector
{

    protected $defaults = [
        'name' => 'My Module name',
        'author' => 'Alexis Awesome',
        'type' => 'Other',
        'version' => 10,
        'autoload' => 'false',
        'permanent' => 'false',
        'singular' => 'false'
    ];


    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:generate')
            ->setAliases(['m:g'])
            ->setDescription('Generates a boilerplate module')
            ->addArgument('name', InputOption::VALUE_REQUIRED, 'Provide a class name for the module')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Module title')
            ->addOption('author', null, InputOption::VALUE_REQUIRED, 'Module author')
            ->addOption('link', null, InputOption::VALUE_REQUIRED, 'Module link')
            ->addOption('summary', null, InputOption::VALUE_REQUIRED, 'Module summary')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Module type')
            ->addOption('version', null, InputOption::VALUE_REQUIRED, 'Module version')
            ->addOption('extends', null, InputOption::VALUE_REQUIRED, 'Module extends')
            ->addOption('implements', null, InputOption::VALUE_REQUIRED, 'Module implements (Interface)')
            ->addOption('permissions', null, InputOption::VALUE_REQUIRED, 'Module permissions')
            ->addOption('require-pw', null, InputOption::VALUE_NONE, 'Module\'s ProcessWire version compatibility')
            ->addOption('require-php', null, InputOption::VALUE_NONE, 'Module\'s PHP version compatibility')
            ->addOption('is-autoload', null, InputOption::VALUE_NONE, 'autoload = true')
            ->addOption('is-singular', null, InputOption::VALUE_NONE, 'singular = true')
            ->addOption('is-permanent', null, InputOption::VALUE_NONE, 'permanent = true')
            ->addOption('with-external-json', null, InputOption::VALUE_NONE, 'Generates external json config file')
            ->addOption('with-copyright', null, InputOption::VALUE_NONE, 'Module author')
            ->addOption('with-uninstall', null, InputOption::VALUE_NONE, 'Module author')
            ->addOption('with-sample-code', null, InputOption::VALUE_NONE, 'Module author')
            ->addOption('with-config-page', null, InputOption::VALUE_NONE, 'Module author');

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $modName = wire('sanitizer')->name($input->getArgument('name'));

        $modDir = $this->createModuleDirectory($output, $modName);
        $this->createModuleFiles($output, $modDir, $modName);

    }

    /**
     * @return string
     */
    protected function createModuleDirectory($output, $modName)
    {
        $pwModsDir = parent::getModuleDirectory();

        $fs = new Filesystem();

        $path = getcwd() . $pwModsDir . $modName;

        try {

            $fs->mkdir($path);

        } catch (IOExceptionInterface $e) {

            $output->writeln("<error>An error occurred while creating your directory at {$e->getPath()}</error>");
            exit(1);
        }

        $output->writeln("<comment>Path {$path} created sucessfully</comment>");

    }

    private function createModuleFiles($output, $input, $modDir)
    {
        // @Todo: implement
    }

}
