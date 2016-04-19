<?php namespace Wireshell\Commands\Logs;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools;
use Wireshell\Helpers\WsTables;

/**
 * Class LogTailCommand
 *
 * Log Output
 *
 * @package Wireshell
 * @author Tabea David
 */
class LogTailCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('log:tail')
            ->setDescription('Log Output')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Specify number of lines. Default: 10. (int)')
            ->addOption('text', null, InputOption::VALUE_REQUIRED, 'Text to find. (string)')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Oldest date to match entries. (int|string)')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Newest date to match entries. (int|string)');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $log = \ProcessWire\wire('log');
        $availableLogs = $log->getLogs();
        $availableLogsString = implode(array_keys($availableLogs), ', ');

        $name = $input->getArgument('name');
        if (!array_key_exists($name, $availableLogs)) {
            $output->writeln("<error>Log '{$name}' does not exist, choose one of `$availableLogsString`</error>");
            return;
        }

        $output->writeln(WsTools::tint("Log $name", 'comment'));

        $options = array(
            'limit' => $input->getOption('limit') ? $input->getOption('limit') : 10,
            'dateTo' => $input->getOption('to') ? $input->getOption('to') : 'now',
            'dateFrom' => $input->getOption('from') ? $input->getOption('from') : '-10years',
            'text' => $input->getOption('text') ? $input->getOption('text') : ''
        );

        $headers = array('Date', 'User', 'URL', 'Message');
        $data = $log->getEntries($name, $options);

        $tables = array(WsTables::buildTable($output, $data, $headers));
        WsTables::renderTables($output, $tables, false);

        $count = count($data);
        $total = $log->getTotalEntries($name);
        $output->writeln(WsTools::tint("($count in set, total: $total)", 'comment'));
    }
}
