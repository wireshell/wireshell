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
 * Class FieldEditCommand
 *
 * Clones a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldEditCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:edit')
      ->setDescription('Edit a field')
      ->addArgument('field', InputArgument::OPTIONAL)
      ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Change field name')
      ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Change field description')
      ->addOption('notes', null, InputOption::VALUE_OPTIONAL, 'Change field description')
      ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Change label');
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

    $fields = \ProcessWire\wire('fields');

    $field = $tools->ask($input->getArgument('field'), 'Field name', null, false, null, 'required');
    $fieldToEdit = $fields->get($field);

    if (is_null($fieldToEdit)) {
      $tools->writeError("Field '{$field}' does not exist.");
      exit(1);
    }

    $name = $tools->ask($input->getOption('name'), 'New field name', $fieldToEdit->name);
    $label = $tools->ask($input->getOption('label'), 'New field label', $fieldToEdit->label);
    $description = $input->getOption('description') ? $input->getOption('description') : null;
    $notes = $input->getOption('notes') ? $input->getOption('notes') : null;

    if ($name && $name !== $fieldToEdit->name) $fieldToEdit->name = $name;
    if ($label && $label !== $fieldToEdit->label) $fieldToEdit->label = ucfirst($label);
    if ($description !== $fieldToEdit->description) $fieldToEdit->description = $description;
    if ($notes !== $fieldToEdit->notes) $fieldToEdit->notes = $notes;

    $fieldToEdit->save();

    $tools->writeSuccess("Field '{$field}' edited successfully.");
  }

}
