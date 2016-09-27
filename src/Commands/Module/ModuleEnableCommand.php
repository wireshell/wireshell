<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwModuleTools;

/**
 * Class ModuleEnableCommand
 *
 * Enables provided module(s)
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */
class ModuleEnableCommand extends PwModuleTools {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('module:enable')
            ->setDescription('Enables provided module(s)')
            ->addArgument('modules', InputOption::VALUE_REQUIRED,
                'Provide one or more module class name, comma separated: Foo,Bar')
            ->addOption('github', null, InputOption::VALUE_OPTIONAL,
                'Download module via github. Use this option if the module isn\'t added to the ProcessWire module directory.')
            ->addOption('branch', null, InputOption::VALUE_OPTIONAL,
                'Optional. Define specific branch to download from.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);
        $modules = explode(",", $input->getArgument('modules'));

        foreach ($modules as $module) {
            // if module doesn't exist, download the module
            if (!$this->checkIfModuleExists($module)) {
                $output->writeln("<comment>Cannot find '{$module}' locally, trying to download...</comment>");
                $this->passOnToModuleDownloadCommand($module, $output, $input);
            }

            // check whether module is already installed
            if (\ProcessWire\wire('modules')->isInstalled($module)) {
                $output->writeln("<info>Module `{$module}` is already installed.</info>");
                exit(1);
            }

            // install module
            if (\ProcessWire\wire('modules')->getModule($module, array('noPermissionCheck' => true, 'noInit' => true))) {
                $output->writeln("<info>Module `{$module}` installed successfully.</info>");
            } else {
                $output->writeln("<error>Module `{$module}` does not exist.</error>");
            }
        }

    }

    private function checkIfModuleExistsLocally($module, $output, $input) {
        if (!$this->checkIfModuleExists($module)) {
            $output->writeln("<comment>Cannot find '{$module}' locally, trying to download...</comment>");
            $this->passOnToModuleDownloadCommand($module, $output, $input);
        }

    }

    private function passOnToModuleDownloadCommand($module, $output, $input) {
        $command = $this->getApplication()->find('mod:download');

        $arguments = array(
            'command' => 'mod:download',
            'modules' => $module,
            '--github' => $input->getOption('github'),
            '--branch' => $input->getOption('branch')
        );

        $passOnInput = new ArrayInput($arguments);
        $command->run($passOnInput, $output);
    }
}
