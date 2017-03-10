<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class ModuleDisableCommand
 *
 * Disables provided module(s)
 *
 * @package Wireshell
 * @author Tabea David <info@justonestep.de>
 */
class ModuleDisableCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('module:disable')
      ->setDescription('Disable provided module(s)')
      ->addArgument('modules', InputArgument::IS_ARRAY, 'Module classname (separate multiple names with a space)')
      ->addOption('rm', null, InputOption::VALUE_NONE, 'Remove module');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $this->tools = new Tools($output);
    $this->tools
      ->setInput($input)
      ->setHelper($this->getHelper('question'))
      ->writeBlockCommand($this->getName());

    $modules = $this->tools->ask(
      $input->getArgument('modules'),
      $this->getDefinition()->getArgument('modules')->getDescription(),
      null,
      false,
      null,
      'required'
    );

    if (!is_array($modules)) $modules = explode(" ", $modules);
    $remove = $input->getOption('rm') === true ? true : false;

    foreach ($modules as $module) {
      $this->checkIfModuleExists($module, $remove);

      if (\ProcessWire\wire('modules')->uninstall($module)) {
        $this->tools->writeSuccess("Module `{$module}` uninstalled successfully.");
      }

      // remove module
      if ($remove === true && is_dir(\ProcessWire\wire('config')->paths->$module)) {
        $this->tools->nl();
        if ($this->recurseRmdir(\ProcessWire\wire('config')->paths->$module)) {
          $this->tools->writeInfo("Module `{$module}` was <comment>removed</comment> successfully.");
        } else {
          $this->tools->writeError("Module `{$module}` could not be removed <fg=red>could not be removed</fg=red>.");
        }
      }
    }

  }

  private function checkIfModuleExists($module, $remove) {
    if (!is_dir(\ProcessWire\wire('config')->paths->siteModules . $module)) {
      $this->tools->writeError("Module `{$module}` does not exist.");
      exit(1);
    }

    if (!\ProcessWire\wire('modules')->getModule($module, array('noPermissionCheck' => true, 'noInit' => true)) && $remove === false) {
      $this->tools->writeError("Module `{$module}` is not installed.");
      exit(1);
    }
  }

  /**
   * remove uninstalled module recursive
   *
   * @param string $dir
   * @return boolean
   */
  private function recurseRmdir($dir) {
    if (is_dir($dir)) {
      chmod($dir, 0775);
      $files = array_diff(scandir($dir), array('.', '..'));
      foreach ($files as $file) {
        (is_dir("$dir/$file")) ? $this->recurseRmdir("$dir/$file") : unlink("$dir/$file");
      }
      $removed = rmdir($dir);
    } else {
      $removed = false;
    }

    return $removed;
  }
}
