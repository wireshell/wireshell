<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwModuleTools;

/**
 * Class ModuleUpgradesCommand
 *
 * Upgrade module(s)
 *
 * @package Wireshell
 * @author Tabea David
 */
class ModuleUpgradesCommand extends PwModuleTools {

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('module:upgrades')
            ->setDescription('Checks for installed module upgrades.') ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        if (!wire('config')->moduleServiceKey) {
            $output->writeln("<error>No module service key was found.</error>");
            exit(1);
        }

        wire('modules')->resetCache();
        if ($moduleVersions = parent::getModuleVersions(true, $output)) {
            $output->writeln("<info>An upgrade is available for:</info>");
            foreach ($moduleVersions as $name => $info) {
                $output->writeln("  - $name: {$info['remote']}");
            }
        } else {
            $output->writeln("<info>Your modules are up-to-date.</info>");
        }

    }

}
