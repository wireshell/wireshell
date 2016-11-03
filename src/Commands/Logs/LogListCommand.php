<?php namespace Wireshell\Commands\Logs;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;
use Wireshell\Helpers\WsTables as Tables;

/**
 * Class LogListCommand
 *
 * Log Output
 *
 * @package Wireshell
 * @author Tabea David
 */
class LogListCommand extends PwConnector {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('log:list')
            ->setDescription('List available log files');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);

        $tools = new Tools($output);
        $logs = \ProcessWire\wire('log')->getLogs();
        $tools->writeBlockCommand($this->getName());

        $data = array();
        foreach ($logs as $log) {
            $data[] = array(
                $log['name'],
                \ProcessWire\wireRelativeTimeStr($log['modified']),
                \ProcessWire\wire('log')->getTotalEntries($log['name']),
                \ProcessWire\wireBytesStr($log['size'])
            );
        }

        $headers = array('Name', 'Modified', 'Entries', 'Size');
        $tables = new Tables($output);
        $logTables = array($tables->buildTable($data, $headers));
        $tables->renderTables($logTables, false);
        $count = count($logs);
        $tools->writeCount($count);
    }
}
