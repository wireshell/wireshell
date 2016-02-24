<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwModuleTools;

/**
 * Class ModuleUpgradeCommand
 *
 * Upgrade module(s)
 *
 * @package Wireshell
 * @author Tabea David
 */
class ModuleUpgradeCommand extends PwModuleTools {

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('module:upgrade')
            ->setDescription('Upgrades a given module')
            ->addArgument('modules', InputOption::VALUE_REQUIRED,
                'Provide one or more module class name, comma separated: Foo,Bar');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);
        if (!wire('config')->moduleServiceKey) $output->writeln("<error>No module service key was found.</error>");

        $modules = explode(",", $input->getArgument('modules'));

        foreach ($modules as $module) {
            // check whether module exists/is installed
            if (!$this->checkIfModuleExists($module)) {
                $output->writeln("<error>Module `$module` does not exist.</error>");
                continue;
            }

            $moduleVersions = $this->getModuleVersions(true, $output);
            // all modules are up-to-date
            if ($info = $this->getModuleVersion(true, $output, $module)) {
                $output->writeln("<info>An upgrade for $module is available: {$info['remote']}</info>");
            } else {
                $output->writeln("<info>The module `$module` is up-to-date.</info>");
                continue;
            }

            // update url available?
            if (!$info['download_url']) {
                $output->writeln("<error>No download URL specified for module `$module`.</error>");
                continue;
            }

            // update module
            $destinationDir = wire('config')->paths->siteModules . $module . '/';
            wire('modules')->resetCache();
            $this->output = $output;

            try {
                $this
                    ->downloadModule($info['download_url'], $module, $output)
                    ->extractModule($module, $output)
                    ->cleanUpTmp($module, $output);
            } catch (Exception $e) {
                $this->output->writeln(" <error> Could not download module $module. Please try again later. </error>\n");
            }

            $output->writeln("<info>Module `$module` was updated successfully.</info>");
        }
    }
}
