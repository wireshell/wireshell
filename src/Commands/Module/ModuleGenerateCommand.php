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

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:generate')
            ->setAliases(['m:g'])
            ->setDescription('Generates a module boilerplate')
            ->addArgument('name', InputOption::VALUE_REQUIRED, 'Provide a class name for the module');
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
