<?php namespace Wireshell\Commands\Field;

use ProcessWire\Field;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\PwTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class FieldCreateCommand
 *
 * Creates a field
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class FieldCreateCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:create')
      ->setDescription('Creates a field')
      ->addArgument('name', InputArgument::OPTIONAL)
      ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Label')
      ->addOption('desc', null, InputOption::VALUE_REQUIRED, 'Description')
      ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Tag')
      ->addOption('type', null, InputOption::VALUE_REQUIRED,
        'Type of field: text|textarea|email|datetime|checkbox|file|float|image|integer|page|url');
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

    $name = $tools->ask($input->getArgument('name'), 'New field name', null, false, null, 'required');
    $label = $tools->ask($input->getOption('label'), 'New field label', $name);
    $suppliedType = $tools->askChoice($input->getOption('type'), 'Field type', PWTools::getAvailableFieldtypesShort(), '0');
    $tools->nl();

    $type = PwTools::getProperFieldtypeName($suppliedType);
    $check = $this->checkFieltype($type);

    if ($check === true) {
      $field = new Field();
      $field->type = \ProcessWire\wire('modules')->get($type);
      $field->name = $name;
      $field->label = $label;
      $field->description = $input->getOption('desc');
      if ($input->getOption('tag')) $field->tags = $input->getOption('tag');
      $field->save();

      // add FieldsetClose if tab / fieldset
      if (in_array($suppliedType, array('fieldset', 'tab'))) {
        $field = new Field();
        $field->type = \ProcessWire\wire('modules')->get('FieldtypeFieldsetClose');
        $field->name = "{$name}_END";
        $field->label = 'Close an open fieldset';
        $field->description = $input->getOption('desc');
        if ($input->getOption('tag')) $field->tags = $input->getOption('tag');
        $field->save();
      }

      $tools->writeSuccess("Field '{$name}' ($type) created successfully.");
    } else {
      $tools->writeError("This fieldtype `$type` does not exists.");
    }
  }

  /**
   * @param $type
   */
  protected function checkFieltype($type) {
    // get available fieldtypes
    $fieldtypes = array();
    foreach (\ProcessWire\wire('modules') as $module) {
      if (preg_match('/^Fieldtype/', $module->name)) {
        $fieldtypes[] = $module->name;
      }
    }

    // check whether fieldtype exists
    return in_array($type, $fieldtypes) ? true : false;
  }
}
