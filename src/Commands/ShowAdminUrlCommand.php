<?php namespace Wireshell\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwConnector;

/**
 * Class ShowAdminUrlCommand
 *
 * Returns the url for the admin page
 *
 * @package Wireshell
 * @author Camilo Castro
 */

class ShowAdminUrlCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('show:admin')
            ->setAliases(['s:a'])
            ->setDescription('Show the url for the PW administration page');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        parent::bootstrapProcessWire($output);

        $admin = wire('pages')->get('template=admin');

        $output->writeln("Admin Url {$admin->httpUrl}");

    }
}
