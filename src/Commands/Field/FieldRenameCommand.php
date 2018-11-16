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
 * Class FieldRenameCommand
 *
 * Renames a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldRenameCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:rename')
      ->setDescription('Rename a field')
      ->addArgument('field', InputArgument::OPTIONAL)
      ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Change field name')
      ->addOption('tag', null, InputOption::VALUE_OPTIONAL, 'Restrict field list by tag')
      ->addOption('camelCaseToSnakeCase', null, InputOption::VALUE_NONE, 'Change field name notation')
      ->addOption('chooseAll', null, InputOption::VALUE_NONE, 'Choose all fields by default');
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
    $fieldsRestricted = $fields;

    // restrict selection
    if ($tag = $input->getOption('tag')) $fieldsRestricted = $fields->findByTag($tag);

    // select fields
    foreach ($fieldsRestricted as $field) $availableFields[] = $field->name;
    $preselect = $input->getOption('chooseAll') ? implode(',', array_keys($availableFields)) : 0;
    $chosenFields = $tools->askChoice($input->getArgument('field'), 'Select field', $availableFields, $preselect, true);
    if (!is_array($chosenFields)) $chosenFields = array($chosenFields);

    foreach ($chosenFields as $field) {
      $fieldToEdit = $fields->get($field);

      if (is_null($fieldToEdit)) {
        $tools->writeError("Field '{$field}' does not exist.");
        exit(1);
      }

      // rename single! if name is present
      if (count($chosenFields) === 1 && $newName = $input->getOption('name')) {
        $name = $tools->ask($newName, 'New field name', $fieldToEdit->name);
        if ($name && $name !== $fieldToEdit->name) $fieldToEdit->name = $name;
      } else if ($input->getOption('camelCaseToSnakeCase')) {
        // transform fieldname from camelCase to snake_case
        $fieldToEdit->name = $this->camelCaseToSnakeCase($fieldToEdit->name);
      }

      $fieldToEdit->save();
      $tools->writeSuccess("Field '{$field}' renamed successfully to '{$fieldToEdit->name}'.");
    }
  }

  /**
   * camelCase to snake_case
   *
   * @param string $string
   * @return string
   */
  private function camelCaseToSnakeCase($string) {
    return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
  }
}
