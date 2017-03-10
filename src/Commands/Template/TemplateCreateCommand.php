<?php namespace Wireshell\Commands\Template;

use ProcessWire\Template;
use ProcessWire\Fieldgroup;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class TemplateCreateCommand
 *
 * Creating ProcessWire templates
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class TemplateCreateCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('template:create')
      ->setDescription('Creates a ProcessWire template')
      ->addArgument('name', InputArgument::OPTIONAL, 'Name of template')
      ->addOption('fields', null, InputOption::VALUE_REQUIRED,
        'Attach existing fields to template, comma separated')
        ->addOption('nofile', null, InputOption::VALUE_NONE, 'Prevents template file creation');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $this->tools = new Tools($output);
    $this->tools
      ->setHelper($this->getHelper('question'))
      ->setInput($input);

    $this->tools->writeBlockCommand($this->getName());

    $name = '';
    while (!$name) {
      $name = $this->tools->ask($input->getArgument('name'), 'Name for new template');
    }

    $availableFields = array();
    foreach (\ProcessWire\wire('fields') as $field) {
      if ($field->name !== 'title') $availableFields[] = $field->name;
    }

    $fields = $this->tools->askChoice(
      $input->getOption('fields'),
      "Select fields which should be assigned to template $name:",
      array_merge(array('none'), $availableFields),
      0,
      true
    );

    if (\ProcessWire\wire('templates')->get($name)) {
      $this->tools->writeError("Template '{$name}' already exists!");
      exit(1);
    }

    $fieldgroup = new Fieldgroup();
    $fieldgroup->name = $name;
    $fieldgroup->add('title');

    $addFields = !is_array($fields) ? explode(',', $fields) : $fields;
    foreach ($addFields as $field) {
      if ($field === 'none') continue;
      $this->checkIfFieldExists($field, $output);
      $fieldgroup->add($field);
    }

    $fieldgroup->save();

    $template = new Template();
    $template->name = $name;
    $template->fieldgroup = $fieldgroup;
    $template->save();

    if (!$input->getOption('nofile')) $this->createTemplateFile($name);

    $this->tools->nl();
    $this->tools->writeSuccess("Template '{$name}' created successfully!");
  }

  /**
   * @param $name
   */
  private function createTemplateFile($name) {
    if ($templateFile = fopen('site/templates/' . $name . '.php', 'w')) {
      $content = "<?php namespace ProcessWire; \n/* Template {$name} */\n";

      fwrite($templateFile, $content, 1024);
      fclose($templateFile);
    }
  }

  /**
   * @param $field
   * @param $output
   * @return bool
   */
  private function checkIfFieldExists($field, $output) {
    if (!\ProcessWire\wire('fields')->get($field)) {
      $this->tools->writeComment("Field '{$field}' does not exist!");
      $this->tools->nl();

      return false;
    }
  }

}
