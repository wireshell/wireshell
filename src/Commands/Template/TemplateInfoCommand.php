<?php namespace Wireshell\Commands\Template;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTables as Tables;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class TemplateInfoCommand
 *
 * Creating ProcessWire templates
 *
 * @package Wireshell
 * @author Tabea David
 */
class TemplateInfoCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('template:info')
      ->setDescription('Displays detailed information about a specific template')
      ->addArgument('template', InputArgument::OPTIONAL, 'Template name.');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tables = new Tables($output);

    $tools = new Tools($output);
    $tools
      ->setInput($input)
      ->setHelper($this->getHelper('question'))
      ->writeBlockCommand($this->getName());

    $templates = \ProcessWire\wire('templates');
    $inputTemplate = $tools->ask($input->getArgument('template'), 'Template name', null, false, null, 'required');
    $template = $templates->get($inputTemplate);

    $tableInfo = $this->getInfoTable($template, $tables);
    $tableFields = $this->getFieldsTable($template, $tables);
    $tables->renderTables($tableInfo);
    $tables->renderTables($tableFields);
  }

  /**
   * get template info data
   * starting with some basic information
   *
   * @param Template $template
   * @param Tables $tables
   * @return array
   */
  private function getInfoTable($template, $tables) {
    $pages = \ProcessWire\wire('pages');
    $headers = array('Property', 'Value');
    $content = array(
      array('ID', $template->id),
      array('Tags', $template->tags),
      array('File Name', $template->filename),
      array('Cache Time', $template->cacheTime),
      array('Number of fields', $template->fields->count),
      array('Number of pages using this template', $pages->count("template=$template"))
    );

    return array($tables->buildTable($content, $headers));
  }

  /**
   * get template fields
   *
   * @param Template $template
   * @param Tables $tables
   * @return array
   */
  private function getFieldsTable($template, $tables) {
    $headers = array('Field', 'Label', 'Type');
    $content = array();

    foreach ($template->fields as $field) {
      $content[] = array(
        $field->name, $field->label, str_replace('Fieldtype', '', $field->type)
      );
    }

    return array($tables->buildTable($content, $headers));
  }

}
