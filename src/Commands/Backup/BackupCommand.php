<?php namespace Wireshell\Commands\Backup;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Rah\Danpu\Dump;
use Rah\Danpu\Export;
use Wireshell\PwConnector;

/**
 * Class BackupCommand
 *
 * Performs database dump
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class BackupCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('backup:db')
            ->setAliases(['b:db'])
            ->setDescription('Performs database dump')
            ->addOption('filename', null, InputOption::VALUE_REQUIRED, 'Provide a file name for the dump');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $database = wire('config')->dbName;
        $host = wire('config')->dbHost;
        $user = wire('config')->dbUser;
        $pass = wire('config')->dbPass;

        $filename = $input->getOption('filename') ? $input->getOption('filename') . '.sql' : 'dump-' . date("Y-m-d-H-i-s").'.sql';

        try {
            $dump = new Dump;
            $dump
                ->file($filename)
                ->dsn("mysql:dbname={$database};host={$host}")
                ->user($user)
                ->pass($pass)
                ->tmp(getcwd() . 'site/assets/tmp');

            new Export($dump);
        } catch (Exception $e) {
            echo 'Export failed with message: ' . $e->getMessage();
        }

        $output->writeln("<info>Dumped database into {$filename} successfully.</info>");


    }
}
