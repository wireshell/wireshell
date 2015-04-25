<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwConnector;

/**
 * Class ModuleEnableCommand
 *
 * Enables provided module(s)
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class ModuleEnableCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:enable')
            ->setAliases(['m:e'])
            ->setDescription('Enables provided module(s)')
            ->addArgument('modules', InputOption::VALUE_REQUIRED, 'Provide one or more module class name, comma separated: Foo,Bar');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $modules = explode(",", $input->getArgument('modules'));

        foreach ($modules as $module) {
            $this->checkIfModuleExistsLocally($module, $output);
            if(wire('modules')->get($module)) $output->writeln("<info>Module {$module} installed successfully.</info>");
        }

    }

    private function checkIfModuleExistsLocally($module, $output)
    {
        if (!wire("modules")->get("{$module}")) {
            $output->writeln("<comment>Cannot find '{$module}' locally, trying to download...</comment>");

            $this->passOnToModuleDownloadCommand($module, $output);
        }

    }

    private function passOnToModuleDownloadCommand($module, $output)
    {
        $command = $this->getApplication()->find('mod:download');

        $arguments = array(
            'command' => 'mod:download',
            'modules'    => $module
        );

        $input = new ArrayInput($arguments);

        $command->run($input, $output);
    }
}
