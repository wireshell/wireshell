<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwModuleTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class ModuleDownloadCommand
 *
 * Downloads module(s)
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David <td@kf-interactive.com>
 */
class ModuleDownloadCommand extends PwModuleTools {

  /**
   * @var OutputInterface
   */
  protected $output;

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('module:download')
      ->setDescription('Downloads ProcessWire module(s).')
      ->addArgument('modules', InputArgument::OPTIONAL, 'Provide one or more module class name, comma separated: Foo,Bar')
      ->addOption('github', null, InputOption::VALUE_OPTIONAL, 'Download module via github. Use this option if the module isn\'t added to the ProcessWire module directory.')
      ->addOption('branch', null, InputOption::VALUE_OPTIONAL, 'Define specific branch to download from.');
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

    $this->checkPermissions();
    $this->output = $output;

    $modules = $this->tools->ask($input->getArgument('modules'), 'Modules', null, false, null, 'required');
    $github = $input->getOption('github');
    $branch = $input->getOption('branch') ? $input->getOption('branch') : 'master';

    if ($github) $github = "https://github.com/{$input->getOption('github')}/archive/{$branch}.zip";

    if (!is_array($modules)) $modules = explode(',', $modules);
    foreach ($modules as $module) {
      $this->tools->writeBlockBasic(" - Module `$module`: ");

      if ($this->checkIfModuleExists($module)) {
        $this->tools->writeError("Module '{$module}' already exists.");
      } else {
        parent::setModule($module);
        // reset PW modules cache
        \ProcessWire\wire('modules')->resetCache();

        if (isset($github)) {
          $this->downloadModuleByUrl($module, $github);
        } else {
          $this->downloadModuleIfExists($module);
        }
      }
    }

    \ProcessWire\wire('modules')->resetCache();
  }

  /**
    * Check Permissions
   */
  private function checkPermissions() {
    if (!ini_get('allow_url_fopen')) {
      // check if we have the rights to download files from other domains
      // using copy or file_get_contents
      $this->tools->writeError('The php config `allow_url_fopen` is disabled on the server. Enable it then try again.');
      exit(1);
    }

    if (!is_writable(\ProcessWire\wire('config')->paths->siteModules)) {
      // check if module directory is writeable
      $this->tools->writeError('Make sure your `/site/modules` directory is writeable by PHP.');
      exit(1);
    }
  }

  /**
   * check if a module exists in processwire module directory
   *
   * @param string $module
   */
  public function downloadModuleIfExists($module) {
    $contents = file_get_contents(
      \ProcessWire\wire('config')->moduleServiceURL .
      '?apikey=' . \ProcessWire\wire('config')->moduleServiceKey .
      '&limit=1' . '&class_name=' . $module
    );

    $result = json_decode($contents);

    if ($result->status === 'error') {
      $this->tools->writeError("A module with the class `$module` does not exist.");
    } else {
      // yeah! module exists
      $item = $result->items[0];
      $moduleUrl = "{$item->project_url}/archive/master.zip";
      $this->downloadModuleByUrl($module, $moduleUrl);
    }
  }

  /**
   * download module
   *
   * @param string $module
   */
  public function downloadModuleByUrl($module, $moduleUrl) {
    try {
      $this
        ->downloadModule($moduleUrl)
        ->extractModule()
        ->cleanUpTmp();
    } catch (Exception $e) {
      $this->tools->writeError("Could not download module `$module`. Please try again later.");
    }
  }

  /**
   * get the config either default or overwritten by user config
   * @param  string $key name of the option
   * @return mixed      return requested option value
   */
  public function getConfig($key) {
    return self::$defaults[$key];
  }
}
