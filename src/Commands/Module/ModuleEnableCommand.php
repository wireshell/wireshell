<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwModuleTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class ModuleEnableCommand
 *
 * Enables provided module(s)
 *
 * @package Wireshell
 * @author Tabea David <info@justonestep.de>
 */
class ModuleEnableCommand extends PwModuleTools {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('module:enable')
      ->setDescription('Enables provided module(s)')
      ->addArgument('modules', InputArgument::OPTIONAL, 'Provide one or more module class name, comma separated: Foo,Bar')
      ->addOption('github', null, InputOption::VALUE_OPTIONAL, 'Download module via github. Use this option if the module isn\'t added to the ProcessWire module directory.')
      ->addOption('branch', null, InputOption::VALUE_OPTIONAL, 'Optional. Define specific branch to download from.');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $this->tools = new Tools($output);
    $this->tools->setHelper($this->getHelper('question'))
      ->setInput($input)
      ->writeBlockCommand($this->getName());

    $modules = $this->tools->ask($input->getArgument('modules'), 'Modules', null, false, null, 'required');
    if (!is_array($modules)) $modules = explode(',', $modules);

    foreach ($modules as $module) {
      // if module doesn't exist, download the module
      if (!$this->checkIfModuleExists($module)) {
        $this->tools->writeComment("Cannot find '{$module}' locally, trying to download...");
        $this->tools->nl();
        $this->passOnToModuleDownloadCommand($module, $output, $input);
      }

      // check whether module is already installed
      if (\ProcessWire\wire('modules')->isInstalled($module)) {
        $this->tools->writeInfo(" Module `{$module}` is already installed.");
        continue;
      }

      // install module
      $options = array(
        'noPermissionCheck' => true,
        'noInit' => true
      );

      if (\ProcessWire\wire('modules')->getInstall($module, $options)) {
        $this->tools->writeSuccess(" Module `{$module}` installed successfully.");
      } else {
        $this->tools->writeError(" Module `{$module}` does not exist.");
      }
    }

  }

  private function checkIfModuleExistsLocally($module, $output, $input) {
    if (!$this->checkIfModuleExists($module)) {
      $output->writeln("<comment>Cannot find '{$module}' locally, trying to download...</comment>");
      $this->passOnToModuleDownloadCommand($module, $output, $input);
    }

  }

  private function passOnToModuleDownloadCommand($module, $output, $input) {
    $command = $this->getApplication()->find('mod:download');

    $arguments = array(
      'command' => 'mod:download',
      'modules' => $module,
      '--github' => $input->getOption('github'),
      '--branch' => $input->getOption('branch')
    );

    $passOnInput = new ArrayInput($arguments);
    $command->run($passOnInput, $output);
  }
}
