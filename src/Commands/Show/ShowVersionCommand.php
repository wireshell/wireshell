<?php namespace Wireshell\Commands\Show;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwConnector;

/**
 * Class ShowVersionCommand
 *
 * Returns the version of the current PW installation
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class ShowVersionCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('show:version')
            ->setAliases(['s:v'])
            ->setDescription('Show version of current PW installation');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        parent::bootstrapProcessWire($output);

        $version = wire('config')->version;

        $output->writeln("ProcessWire {$version}");

    }
}
