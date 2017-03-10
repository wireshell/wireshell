<?php namespace Wireshell\Commands\Field;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class FieldCreateCommand
 *
 * Lists all available fieldtypes
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldTypesCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:types')
      ->setDescription('Lists all available fieldtypes.');
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

    // get available fieldtypes
    foreach (\ProcessWire\wire('modules') as $module) {
      if (preg_match('/^Fieldtype/', $module->name)) {
        $tools->writeDfList($module->name, substr($module->name, 9));
      }
    }
  }
}
