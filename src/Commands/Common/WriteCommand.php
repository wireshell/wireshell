<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\ProcessDiagnostics\DiagnoseImagehandling;
use Wireshell\Helpers\ProcessDiagnostics\DiagnosePhp;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class StatusCommand
 *
 * Returns versions, paths and environment info
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Camilo Castro
 * @author Tabea David
 * @author netcarver
 * @author horst
 */
class WriteCommand extends PwConnector {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('write')
            ->setDescription('Displays styles.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->tools = new Tools($output);

        $this->tools->writeSuccess('Write Success!');
        $this->tools->writeInfo('Write Info!');
        $this->tools->writeComment('Write Comment!');
        $this->tools->writeLink('Write Link!');
        $this->tools->writeBlock('Write Block!');
        $this->tools->writeError('Write Error!');
        $this->tools->writeHeader('Write Header!');
        $this->tools->writeMark('Write Mark!');
        $this->tools->writeSection('Write Section!', 'Write Section Part Two.');
        $this->tools->writeBlockBasic('Write Block Basic!');
        $this->tools->writeBlockCommand('Write Block Command!');
        $this->tools->writeInfo('nl');
        $this->tools->nl();
        $this->tools->writeInfo('spacing');
        $this->tools->spacing();
        $output->writeln($this->tools->getQuestion('Get Question', 'default'));

        // @todo: list
        // @todo: table
    }

}
