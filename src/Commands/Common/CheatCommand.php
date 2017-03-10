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
 * Class CheatCommand
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
class CheatCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('cheat')
      ->setDescription('Displays styles.');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools->writeBlockCommand($this->getName());

    $tools->writeBlockBasic(array(
      '  use Wireshell\Helpers\WsTools as Tools;',
      '  $tools = new Tools($output);',
      '  $tools->writeInfo(\'This is how it should be used!\')'
    ));

    $tools->writeSuccess('Write Success!');
    $tools->writeInfo('Write Info!');
    $tools->writeComment('Write Comment!');
    $tools->writeLink('Write Link!');
    $tools->writeBlock('Write Block!');
    $tools->writeError('Write Error!');
    $tools->writeHeader('Write Header!');
    $tools->writeMark('Write Mark!');
    $tools->writeSection('Write Section!', 'Write Section Part Two.');
    $tools->writeBlockBasic('Write Block Basic!');
    $tools->writeBlockCommand('Write Block Command!');
    $tools->writeInfo('nl:');
    $tools->nl();
    $output->writeln($tools->getQuestion('Get Question', 'default'));

    $output->write($tools->writeInfo('writeCount(5, 10) ', false));
    $tools->writeCount(5, 10);
    $tools->nl();
    $tools->writeDfList('Write Df List', 'Write Df List Part Two.');
    $tools->writeDfList('Write Df List 2', 'Write Df List Part Two 2.');
    $tools->nl();
    $tools->writeError('Write Error and exit!');
  }

}
