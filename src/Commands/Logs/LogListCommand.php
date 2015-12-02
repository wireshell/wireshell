<?php namespace Wireshell\Commands\Logs;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools;
use Wireshell\Helpers\WsTables;

/**
 * Class LogListCommand
 *
 * Log Output
 *
 * @package Wireshell
 * @author Tabea David
 */
class LogListCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('log:list')
            ->setDescription('List available log files');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $logs = wire('log')->getLogs();
        $output->writeln(WsTools::tint(count($logs) . ' logs', 'comment'));

        $data = array();
        foreach ($logs as $log) {
            $data[] = array(
                $log['name'],
                wireRelativeTimeStr($log['modified']),
                wire('log')->getTotalEntries($log['name']),
                wireBytesStr($log['size'])
            );
        }

        $headers = array('Name', 'Modified', 'Entries', 'Size');
        $tables = array(WsTables::buildTable($output, $data, $headers));
        WsTables::renderTables($output, $tables, false);
    }
}
