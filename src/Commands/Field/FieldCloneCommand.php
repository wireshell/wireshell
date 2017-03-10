<?php namespace Wireshell\Commands\Field;

use ProcessWire\Field;
use ProcessWire\Fieldgroup;
use ProcessWire\InputfieldText;
use ProcessWire\InputfieldWrapper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class FieldCloneCommand
 *
 * Clones a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldCloneCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:clone')
      ->setDescription('Clones a field')
      ->addArgument('field', InputArgument::OPTIONAL)
      ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools
      ->setInput($input)
      ->setHelper($this->getHelper('question'))
      ->writeBlockCommand($this->getName());

    // get the fields
    $availableFields = array();
    $fields = \ProcessWire\wire('fields');
    foreach ($fields as $field) {
      $availableFields[] = $field->name;
    }

    $field = $tools->askChoice($input->getArgument('field'), 'Select field', $availableFields, 0);
    $fieldToClone = $fields->get($field);

    if (is_null($fieldToClone)) {
      $tools->writeError("Field '{$field}' does not exist!");
      exit(1);
    }

    $clone = $fields->clone($fieldToClone);

    if ($input->getOption('name')) {
      $clone->name = $input->getOption('name');
      $clone->label = ucfirst($input->getOption('name'));
      $clone->save();
    }

    $name = $input->getOption('name') !== '' ? $input->getOption('name') : $field . 'cloned';

    $tools->writeSuccess("Field '{$field}' cloned successfully.");
  }

}
