<?php namespace Wireshell\Commands\Module;

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
            ->setName('module:disable')
            ->setAliases(['m:d'])
            ->setDescription('Disable provided module(s)')
            ->addArgument('modules', InputOption::VALUE_REQUIRED, 'Provide one or more module class name, comma separated: Foo,Bar')
            ->addOption('rm', null, InputOption::VALUE_NONE, 'Remove module');
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
            if (wire('modules')->uninstall($module)) {
                $output->writeln("Module {$module} <comment>uninstalled</comment> successfully.");

                // remove module
                if ($input->getOption('rm') === true && is_dir(wire('config')->paths->MobileDetect)) {
                    if ($this->recurseRmdir(wire('config')->paths->$module)) {
                        $output->writeln("Module {$module} was <comment>removed</comment> successfully.");
                    } else {
                        $output->writeln("Module {$module} could not be removed <fg=red>could not be removed</fg=red>.");
                    }
                }
            }
        }

    }

    private function checkIfModuleExists($module, $output)
    {
        if (!wire("modules")->get("{$module}")) {
            $output->writeln("<error>Module '{$module}' does not exist!</error>");
            return false;
        }

    }

    /**
     * remove uninstalled module recursive
     *
     * @param string $dir
     * @return boolean
     */
    private function recurseRmdir($dir)
    {
        if (is_dir($dir)) {
            chmod($dir, 0775);
            $files = array_diff(scandir($dir), array('.','..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? $this->recurseRmdir("$dir/$file") : unlink("$dir/$file");
            }
            $removed = rmdir($dir);
        } else {
            $removed = false;
        }

        return $removed;
    }
}
