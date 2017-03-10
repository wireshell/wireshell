<?php namespace Wireshell\Commands\Field;

use ProcessWire\Field;
use ProcessWire\Fieldgroup;
use ProcessWire\InputfieldText;
use InputfieldWrapper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class FieldTagCommand
 *
 * Tags a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldTagCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('field:tag')
      ->setDescription('Tags fields')
      ->addArgument('field', InputArgument::OPTIONAL, 'Comma separated list.')
      ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Tag name');
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

    $inputFields = $tools->ask($input->getArgument('field'), 'Field name(s), comma-separated', null, false, null, 'required');
    $tag = $tools->ask($input->getOption('tag'), 'Tag name', null, false, null, 'required');

    foreach (explode(',', $inputFields) as $field) {
      $fieldToTag = $fields->get($field);
      $tools->nl();

      if (is_null($fieldToTag)) {
        $tools->writeError(" > Field '{$field}' does not exist.");
        continue;
      }

      try {
        $fieldToTag->tags = $tag;
        $fieldToTag->save();
        $tools->writeSuccess(" > Field '{$field}' edited successfully.");
      } catch (\WireException $e) {
        $tools->writeError(" > {$e->getMessage()}");
      }
    }
  }

}
