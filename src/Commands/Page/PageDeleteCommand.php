<?php namespace Wireshell\Commands\Page;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class PageDeleteCommand
 *
 * Creating ProcessWire pages
 *
 * @package Wireshell
 * @author Tabea David <info@justonestep.de>
 */
class PageDeleteCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  public function configure() {
    $this
      ->setName('page:delete')
      ->setDescription('Deletes ProcessWire pages')
      ->addArgument('selector', InputArgument::OPTIONAL)
      ->addOption('rm', null, InputOption::VALUE_NONE, 'Force deletion, do not move page to trash');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output) {
    parent::setOutput($output)::setInput($input)::bootstrapProcessWire();

    $this->tools = new Tools($output);
    $this->tools
      ->setHelper($this->getHelper('question'))
      ->setInput($input)
      ->writeBlockCommand($this->getName());

    $this->pages = \ProcessWire\wire('pages');
    $this->config = \ProcessWire\wire('config');
    $forceDeletion = $input->getOption('rm') === true ? true : false;

    $this->preservedIds = array(
      $this->config->adminRootPageID,
      $this->config->trashPageID,
      $this->config->rootPageID,
      $this->config->http404PageID
    );

    $options = array('include' => 'all');
    $limitSelect = array(
      "has_parent!={$this->config->adminRootPageID}",
      'id!=' . implode('|', $this->preservedIds),
      'status<' . \processWire\Page::statusTrash
    );

    // ask for selector
    $selectorString = $input->getArgument('selector') ? $input->getArgument('selector') : null;
    if (!$selectorString) {
      $selectorString = $this->tools->ask(null, 'Provide selector or page id');
    }

    foreach (explode(',', $selectorString) as $selector) {
      $select = (is_numeric($selector)) ? (int)$selector : $selector;
      $pagesToBeDeleted = $this->pages->find($select, $options);

      // no matches? exit
      if ($pagesToBeDeleted->count() === 0) {
        $this->tools->writeError("No pages were found using selector `{$selector}`.");
        exit(1);
      }

      $this->deletePages($pagesToBeDeleted, $forceDeletion);
    }
  }

  /**
   * Delete pages
   *
   * @param \ProcessWire\PagesArray $pages
   * @param boolean $forceDeletion
   */
  private function deletePages($pagesToBeDeleted, $forceDeletion) {
    foreach ($pagesToBeDeleted as $p) {
      $this->tools->nl();

      if ($p instanceof \ProcessWire\NullPage) {
        $this->tools->writeError("Page `{$selector}` doesn't exist.");
      } else {
        $title = $p->get('title|name'); // remember title

        // check whether the page is allowed to be deleted
        if (in_array($p->id, $this->preservedIds)) {
          $this->tools->writeError("Page `{$title}` may not be deleted.");
          continue;
        } elseif ($p->parents("id={$this->config->adminRootPageID}")->count()) {
          $this->tools->writeError("Page `{$title}` (admin page) may not be deleted.");
          continue;
        }

        // and delete it / move it to the trash
        if ($forceDeletion) {
          $this->pages->delete($p, true);
          $this->tools->writeSuccess("Page `{$title}` was successfully deleted.");
        } else {
          $this->pages->trash($p, true);
          $this->tools->writeSuccess("Page `{$title}` has been successfully moved to the trash.");
        }
      }
    }
  }

}
