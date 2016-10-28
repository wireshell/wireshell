<?php namespace Wireshell\Commands\Logs;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;
use Wireshell\Helpers\WsTables as Tables;

/**
 * Class LogTailCommand
 *
 * Log Output
 *
 * @package Wireshell
 * @author Tabea David
 */
class LogTailCommand extends PwConnector {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('log:tail')
            ->setDescription('Log Output')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the log file.')
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
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);

        $tools = new Tools($output);
        $helper = $this->getHelper('question');
        $formatter = $this->getHelper('formatter');
        $log = \ProcessWire\wire('log');
        $availableLogs = $log->getLogs();
        $tools->writeBlockCommand($this->getName());

        $question = new ChoiceQuestion(
            $tools->getQuestion('Please choose one of', key($availableLogs)),
            array_keys($availableLogs),
            0
        );

        $name = $input->getArgument('name');
        if (!$name) {
            $name = $helper->ask($input, $output, $question);
        } else if (!array_key_exists($name, $availableLogs)) {
            $tools->writeError("<error> Log '{$name}' does not exist.</error>");
            $tools->nl();
            $name = $helper->ask($input, $output, $question);
        }

        $tools->nl();
        $tools->writeHeader('Log ' . ucfirst($name));
        $tools->nl();

        $options = array(
            'limit' => $input->getOption('limit') ? $input->getOption('limit') : 10,
            'dateTo' => $input->getOption('to') ? $input->getOption('to') : 'now',
            'dateFrom' => $input->getOption('from') ? $input->getOption('from') : '-10years',
            'text' => $input->getOption('text') ? $input->getOption('text') : ''
        );

        $headers = array('Date', 'User', 'URL', 'Message');
        $data = $log->getEntries($name, $options);

        $tables = new Tables();
        $logTables = array($tables->buildTable($output, $data, $headers));
        $tables->renderTables($output, $logTables, false);

        $count = count($data);
        $total = $log->getTotalEntries($name);
        $tools->writeCount($count, $total);
    }
}
