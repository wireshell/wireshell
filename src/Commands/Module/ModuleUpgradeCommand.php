<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Wireshell\Helpers\PwModuleTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class ModuleUpgradeCommand
 *
 * Upgrade module(s)
 *
 * @package Wireshell
 * @author Tabea David
 */
class ModuleUpgradeCommand extends PwModuleTools {

  /**
   * @var OutputInterface
   */
  protected $output;

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('module:upgrade')
      ->setDescription('Upgrades given module(s)')
      ->addArgument('modules', InputArgument::IS_ARRAY, 'Module classname (separate multiple names with a space)')
      ->addOption('check', null, InputOption::VALUE_NONE, 'Just check for module upgrades.');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    parent::init($output, $input);
    parent::bootstrapProcessWire($output);

    if (!\ProcessWire\wire('config')->moduleServiceKey) throw new \RuntimeException('No module service key was found.');

    $this->tools = new Tools($output);
    $this->tools->setHelper($this->getHelper('question'))
      ->setInput($input)
      ->writeBlockCommand($this->getName());

    $modules = $input->getArgument('modules');

    if ($modules && !$input->getOption('check')) {

      // upgrade specific modules
      if (!is_array($modules)) $modules = explode(" ", $modules);
      if ($modules) $this->upgradeModules($modules, $output);

    } else {

      // check for module upgrades
      \ProcessWire\wire('modules')->resetCache();

      if ($moduleVersions = parent::getModuleVersions(true, $output)) {
        $this->tools->writeInfo('An upgrade is available for:');
        foreach ($moduleVersions as $name => $info) {
          $this->tools->writeDfList($name, "{$info['local']} -> {$info['remote']}");
        }

        // aks which module should be updated
        if (!$input->getOption('check')) {
          $this->tools->nl();
          $modules = $this->tools->askChoice(
            $modules,
            'Please select which module(s) should be updated',
            array_merge(array('None'), array_keys($moduleVersions)),
            '0',
            true
          );

          $this->tools->nl();
          $this->tools->writeSection('You\'ve selected', implode(', ', $modules));

          // if not `None` was selected, update modules
          if (!in_array('None', $modules) && $modules) $this->upgradeModules($modules, $output);
        }
      } else {
        $this->tools->write('Your modules are up-to-date.');
      }
    }
  }

  /**
   * Upgrade modules
   *
   * @param array $modules
   * @param OutputInterface $output
   */
  private function upgradeModules($modules, $output) {
    foreach ($modules as $module) {
      // check whether module exists/is installed
      if (!$this->checkIfModuleExists($module)) {
        $this->tools->writeError("Module `$module` does not exist.");
        continue;
      }

      $moduleVersions = $this->getModuleVersions(true);
      // all modules are up-to-date
      if ($info = $this->getModuleVersion(true, $module)) {
        $this->tools->writeBlockBasic("Upgrading `$module` to version {$info['remote']}.");
      } else {
        $this->tools->write("The module `$module` is up-to-date.");
        continue;
      }

      // update url available?
      if (!$info['download_url']) {
        $this->tools->writeError("No download URL specified for module `$module`.");
        continue;
      }

      // update module
      $destinationDir = \ProcessWire\wire('config')->paths->siteModules . $module . '/';
      \ProcessWire\wire('modules')->resetCache();
      parent::setModule($module);

      try {
        $this
          ->downloadModule($info['download_url'], $module, $output)
          ->extractModule($module, $output)
          ->cleanUpTmp($module, $output);
      } catch (Exception $e) {
        $this->tools->writeError("Could not download module `$module`. Please try again later.");
      }

      $this->tools->nl();
      $this->tools->writeSuccess("Module `$module` was updated successfully.");
    }
  }
}
