<?php namespace Wireshell\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwConnector;

/**
 * Class ModuleDisableCommand
 *
 * Disables provided module(s)
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class ModuleDisableCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('mod:disable')
            ->setAliases(['m:d'])
            ->setDescription('Disable provided module(s)')
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
            $this->checkIfModuleExists($module, $output);
            if(wire('modules')->uninstall($module)) $output->writeln("Module {$module} <comment>uninstalled</comment> successfully.");
        }

    }

    private function checkIfModuleExists($module, $output)
    {
        if (!wire("modules")->get("{$module}")) {
            $output->writeln("<error>Module '{$module}' does not exist!</error>");
            return false;
        }

    }
}
