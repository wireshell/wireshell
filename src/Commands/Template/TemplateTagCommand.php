<?php namespace Wireshell\Commands\Template;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class TemplateTagCommand
 *
 * Creating ProcessWire templates
 *
 * @package Wireshell
 * @author Tabea David
 */
class TemplateTagCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('template:tag')
      ->setDescription('Tags templates')
      ->addArgument('template', InputArgument::OPTIONAL, 'Comma separated list.')
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

    $templates = \ProcessWire\wire('templates');

    $inputTemplates = $tools->ask($input->getArgument('template'), 'Template name(s), comma-separated', null, false, null, 'required');
    $tag = $tools->ask($input->getOption('tag'), 'Tag name', null, false, null, 'required');

    foreach (explode(',', $inputTemplates) as $template) {
      $templateToTag = $templates->get($template);
      $tools->nl();

      if (is_null($templateToTag)) {
        $tools->writeError(" > Template '{$template}' does not exist.");
        continue;
      }

      try {
        $templateToTag->tags = $tag;
        $templateToTag->save();
        $tools->writeSuccess(" > Template '{$template}' edited successfully.");
      } catch (\WireException $e) {
        $tools->writeError(" > {$e->getMessage()}");
      }
    }
  }
}
