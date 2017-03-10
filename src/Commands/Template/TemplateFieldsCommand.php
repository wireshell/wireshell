<?php namespace Wireshell\Commands\Template;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class TemplateFieldsCommand
 *
 * Assign given fields to a given template
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class TemplateFieldsCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('template:fields')
      ->setDescription('Assign given fields to a given template')
      ->addArgument('template', InputArgument::OPTIONAL, 'Name of the template')
      ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Supply fields to assign to template');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $this->errorCount = 0;
    $this->tools = new Tools($output);
    $this->tools
      ->setHelper($this->getHelper('question'))
      ->setInput($input);

    $this->tools->writeBlockCommand($this->getName());

    // check if the template exists
    $availableTemplates = $this->getAvailableTemplates();
    $tmpl = $input->getArgument('template');
    if ($tmpl && !in_array($tmpl, $availableTemplates)) {
      $tools->writeError("Template `$tmpl` does not exist.");
      $tools->nl();
      $tmpl = null;
    }

    // ask for template
    $template = $this->tools->askChoice($tmpl, 'Select template', $availableTemplates, 0);
    $this->tools->nl();

    // get the fields
    $availableFields = array();
    foreach (\ProcessWire\wire('fields') as $field) {
      $availableFields[] = $field->name;
    }

    $fields = $input->getOption('fields') ? explode(",", $input->getOption('fields')) : '';
    $fields = $this->tools->askChoice($fields, 'Select fields', $availableFields, 0, true);
    $this->assignFieldsToTemplate($fields, $template, $output);

    $this->tools->nl();

    if ($this->errorCount === count($fields)) {
      $this->tools->writeError('Field(s) could not be assigned.');
    } elseif ($this->errorCount > 0) {
      $this->tools->writeSuccess("Field(s) added to '{$template}' successfully except '{$this->errorCount}'.");
    } else {
      $this->tools->writeSuccess("Field(s) added to '{$template}' successfully.");
    }
  }

  /**
   * Assign fields to template
   *
   * @param $fields
   * @param $template
   * @param $output
   */
  private function assignFieldsToTemplate($fields, $template, $output) {
    $pwTemplate = \ProcessWire\wire('templates')->get($template);
    foreach ($fields as $field) {
      if ($this->checkIfFieldExists($field)) {
        $pwTemplate->fields->add($field);
        $pwTemplate->fields->save();
      } 
    }

    return $template;
  }

  /**
   * Check if field exists
   *
   * @param $field
   * @param $output
   * @return bool
   */
  private function checkIfFieldExists($field) {
    if (!\ProcessWire\wire('fields')->get($field)) {
      $this->tools->writeError("- Field '{$field}' does not exist!</error>");
      $doesNotExist = false;
      $this->errorCount++;
    }

    return isset($doesNotExist) ? false : true;
  }
}
