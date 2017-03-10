<?php namespace Wireshell\Commands\Page;

use ProcessWire\Page;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wireshell\Helpers\PwUserTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class PageEmptyTrashCommand
 *
 * Creating ProcessWire pages
 *
 * @package Wireshell
 * @author Tabea David <info@justonestep.de>
 */
class PageEmptyTrashCommand extends PwUserTools {
  protected $maxItems = 1000;

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('page:emptytrash')
      ->setDescription('Empty Trash');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools
      ->setHelper($this->getHelper('question'))
      ->setInput($input)
      ->writeBlockCommand($this->getName());

    $pages = \ProcessWire\wire('pages');
    $config = \ProcessWire\wire('config');

    $trashed = "parent_id={$config->trashPageID},limit={$this->maxItems},";
    $trashed .= "status<" . Page::statusMax . ",include=all";

    $trashPages = $pages->find($trashed);

    if ($trashPages->getTotal() > 0) {
      foreach ($trashPages as $t) $pages->delete($t, true);
      $tools->writeSuccess("The trash was successfully cleared, {$trashPages->getTotal()} pages were deleted.");
    } else {
      $tools->writeInfo("The trash is already empty.");
    }
  }

}

