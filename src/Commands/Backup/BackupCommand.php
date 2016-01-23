<?php namespace Wireshell\Commands\Backup;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Rah\Danpu\Dump;
use Rah\Danpu\Export;
use Wireshell\Helpers\PwConnector;

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
            ->setDescription('Performs database dump')
            ->addOption('filename', null, InputOption::VALUE_REQUIRED, 'Provide a file name for the dump')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Provide a file path for the dump');
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

        $filename = $input->getOption('filename') ? $input->getOption('filename') . '.sql' : 'dump-' . date("Y-m-d-H-i-s") . '.sql';
        $target = $input->getOption('target') ? $input->getOption('target') : '';
        if ($target && !preg_match('/$\//', $target)) $target = "$target/";

        try {
            $dump = new Dump;
            $dump
                ->file($target . $filename)
                ->dsn("mysql:dbname={$database};host={$host}")
                ->user($user)
                ->pass($pass)
                ->tmp(getcwd() . 'site/assets/tmp');

            new Export($dump);
        } catch (Exception $e) {
            $output->writeln("<error>Export failed with message: {$e->getMessage()}</error>");
            exit(1);
        }

        $output->writeln("<info>Dumped database into `{$target}{$filename}` successfully.</info>");
    }
}
