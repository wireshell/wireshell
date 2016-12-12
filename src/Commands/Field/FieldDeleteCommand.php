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
 * Class FieldDeleteCommand
 *
 * Deletes a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldDeleteCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:delete')
      ->setDescription('Deletes fields')
      ->addArgument('field', InputArgument::OPTIONAL, 'Comma separated list.');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::setOutput($output)::setInput($input)::bootstrapProcessWire();

    $tools = new Tools($output);
    $tools
      ->setInput($input)
      ->setHelper($this->getHelper('question'))
      ->writeBlockCommand($this->getName());

    // ask
    $fields = \ProcessWire\wire('fields');
    $availableFields = array();
    foreach ($fields as $field) {
      // exclude system fields
      if ($field->flags & Field::flagSystem || $field->flags & Field::flagPermanent) continue;
      $availableFields[] = $field->name;
    }

    $inputFields = $input->getArgument('field') ? explode(',', $input->getArgument('field')) : null;
    $inputFields = $tools->askChoice($inputFields, 'Select all fields which should be deleted', $availableFields, 0, true);
    $tools->nl();

    foreach ($inputFields as $field) {
      $fieldToDelete = $fields->get($field);

      if (is_null($fieldToDelete)) {
        $tools->writeError("> Field '{$field}' does not exist.");
        $tools->nl();
        continue;
      }

      try {
        $fields->delete($fieldToDelete);
        $tools->writeSuccess(" > Field '{$field}' deleted successfully.");
        $tools->nl();
      } catch (\WireException $e) {
        $tools->writeError("> {$e->getMessage()}");
        $tools->nl();
      }
    }
  }

}
