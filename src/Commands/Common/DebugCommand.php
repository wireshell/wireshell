<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class DebugCommand
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */
class DebugCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('debug')
      ->setDescription('Change ProcessWire debug mode.')
      ->addOption('on', null, InputOption::VALUE_NONE, 'Turn debug mode on.')
      ->addOption('off', null, InputOption::VALUE_NONE, 'Turn debig mode off.');
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

    // get new state
    $state = false;
    if ($input->getOption('on')) {
      $state = true;
    } elseif (!$input->getOption('off')) {
      // if none provided, ask!
      $state = $tools->askConfirmation(null, 'Turn debug mode on (type `y` or `n`)?');
      $tools->nl();
    }

    $conf = \ProcessWire\wire('config')->paths->site . 'config.php';
    $newMode = '$config->debug = ' . var_export($state, true) . ";\n";
    $result = '';
    $hasBeenFound = false;
    foreach (file($conf) as $line) {
      if (!$hasBeenFound && substr($line, 0, 14) == '$config->debug') {
        $result .= $newMode;
        $hasBeenFound = true;
      } else {
        $result .= $line;
      }
    }

    // no debug statement was found
    // add it to the bottom of the file
    if (!$hasBeenFound) $result .= $newMode;

    file_put_contents($conf, $result);

    $tools->writeSuccess(sprintf('Debug mode has been turned %s.', $state ? 'on' : 'off'));
  }
}
