<?php namespace Wireshell\Helpers;

use ProcessWire\WireException;
use ProcessWire\WireHttp;
use ProcessWire\Template;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class PwConnector
 *
 * Serving as connector layer between Symfony Commands and ProcessWire
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
abstract class PwConnector extends SymfonyCommand {

  const branchesURL = 'https://api.github.com/repos/processwire/processwire/branches';
  const versionURL = 'https://raw.githubusercontent.com/processwire/processwire/{branch}/wire/core/ProcessWire.php';
  const zipURL = 'https://github.com/processwire/processwire/archive/{branch}.zip';
  const BRANCH_MASTER = 'master';
  const BRANCH_DEV = 'dev';
  const USER_PAGE_ID = '29';
  const ROLE_PAGE_ID = '30';

  public $moduleServiceURL;
  public $moduleServiceKey;
  protected $userContainer;
  protected $roleContainer;
  protected $modulePath = '/site/modules/';
  protected $output;
  protected $input;
  protected $tools;

  /**
   * Init
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @param Boolean $checkForPW
   */
  public function init(InputInterface $input, OutputInterface $output, $checkForPW = true) {
    $this
      ->setInput($input)
      ->setOutput($output);

    if ($checkForPW) {
      $this->bootstrapProcessWire();
    }
  }

  /**
   * Setter for Output
   *
   * @param OutputInterface $output
   */
  public function setOutput(OutputInterface $output) {
    $this->output = $output;
    $this->setTools();
    return $this;
  }

  /**
   * Setter for Input
   *
   * @param InputInterface $input
   */
  public function setInput(InputInterface $input) {
    $this->input = $input;
    return $this;
  }

  /**
   * Setter for Tools
   */
  public function setTools() {
    $this->tools = new Tools($this->output);
    return $this;
  }

  /**
   * Check for ProcessWire
   */
  public function checkForProcessWire() {
    if (!getcwd()) {
      $this->tools->writeErrorAndExit('Please check whether the current directory still exists.');
      exit(1);
    }

    if (!is_dir(getcwd() . '/wire')) {
      foreach (new \DirectoryIterator(getcwd()) as $fileInfo) {
        if (is_dir($fileInfo->getPathname() . '/wire')) chdir($fileInfo->getPathname());
      }

      if (!is_dir(getcwd() . '/wire')) {
        chdir('..');

        if (empty(pathinfo(getcwd())['basename'])) {
          $this->tools->writeError('No ProcessWire installation found.');
          exit(1);
        } else {
          $this->checkForProcessWire();
        }
      } else {
        $directory = $this->tools->writeInfo('`' . getcwd() . '`', false);
        $this->tools->write("Working directory changed to $directory.", false);
        $this->tools->nl();
      }
    }
  }

  /**
   * Bootstrap ProcessWire
   */
  protected function bootstrapProcessWire() {
    $this->checkForProcessWire();

    if (!function_exists('\ProcessWire\wire')) include(getcwd() . '/index.php');

    $this->userContainer = \ProcessWire\wire('pages')->get(self::USER_PAGE_ID);
    $this->roleContainer = \ProcessWire\wire('pages')->get(self::ROLE_PAGE_ID);

    $this->moduleServiceURL = \ProcessWire\wire('config')->moduleServiceURL;
    $this->moduleServiceKey = \ProcessWire\wire('config')->moduleServiceKey;
  }

  /**
   * Get module directory
   */
  protected function getModuleDirectory() {
    return $this->modulePath;
  }

  /**
   * Determine branch
   */
  public function determineBranch() {
    $branch = self::BRANCH_MASTER;
    if ($this->input->getOption('dev')) {
      $branch = self::BRANCH_DEV;
    } elseif ($this->input->getOption('sha')) {
      $branch = $this->input->getOption('sha');
    }
    return $branch;
  }

  /**
   * Check for core upgrades
   */
  protected function checkForCoreUpgrades() {
    $config = \ProcessWire\wire('config');
    $targetBranch = $this->determineBranch();
    $branches = $this->getCoreBranches($targetBranch);

    if ($targetBranch === self::BRANCH_MASTER && version_compare($config->version, $branches[$targetBranch]['version'], '>')) {
      $targetBranch = self::BRANCH_DEV;
    }

    $upgrade = false;
    $branch = $branches[$targetBranch];

    // branch does not exist - assume commit hash
    if (!array_key_exists($targetBranch, $branches)) {
      $branch = $branches['sha'];
      $upgrade = true;
    } elseif (version_compare($branches[$targetBranch]['version'], $config->version) > 0 
      && ($targetBranch === self::BRANCH_MASTER || $targetBranch === self::BRANCH_DEV)) {
      // branch is newer than current
      $upgrade = true;
    }

    $versionStr = $this->tools->writeInfo(" $branch[name] $branch[version]", false);
    if ($upgrade) {
      $this->tools->write("A ProcessWire core upgrade is available:$versionStr", false);
      $this->tools->nl();
    } else {
      $this->tools->writeSuccess("Your ProcessWire core is up-to-date:$versionStr");
    }

    return array('upgrade' => $upgrade, 'branch' => $branch);
  }

  /**
   * Get Core Branches with further informations
   *
   * @param string $targetBranch - whether branch name or commit
   */
  protected function getCoreBranches($targetBranch = 'master') {
    $branches = array();
    $http = new WireHttp();
    $http->setHeader('User-Agent', 'ProcessWireUpgrade');
    $data = $http->getJson(self::branchesURL);

    if (!$data) {
      $error = 'Error loading GitHub branches ' . self::branchesURL;
      $this->tools->writeErrorAndExit($error);
    }

    if (!$data) {
      $error = 'Error JSON decoding GitHub branches ' . self::branchesURL;
      $this->tools->writeErrorAndExit($error);
    }

    foreach ($data as $info) {
      $name = $info['name'];
      $branches[$name] = $this->getBranchInformations($name, $http);
    }

    // branch does not exist - assume sha
    if (!array_key_exists($targetBranch, $branches)) {
      $http = new WireHttp();
      $http->setHeader('User-Agent', 'ProcessWireUpgrade');
      $versionUrl = str_replace('{branch}', $targetBranch, self::versionURL);
      $json = $http->get($versionUrl);

      if (!$json) {
        $error = "Error loading sha `$targetBranch`.";
        $this->tools->writeErrorAndExit($error);
      }

      $name = $targetBranch;
      $branches['sha'] = $this->getBranchInformations($name, $http);
    }

    return $branches;
  }

  /**
   * Get branch with further informations
   *
   * @param string $name
   * @param WireHttp $http
   */
  protected function getBranchInformations($name, $http) {
    $branch = array(
      'name' => $name,
      'title' => ucfirst($name),
      'zipURL' => str_replace('{branch}', $name, self::zipURL),
      'version' => '',
      'versionURL' => str_replace('{branch}', $name, self::versionURL),
    );

    switch ($name) {
      case 'master':
        $branch['title'] = 'Stable/Master';
        break;
      case 'dev':
        $branch['title'] = 'Development';
        break;
      default:
        $branch['title'] = 'Specific commit sha';
        break;
    }

    $content = $http->get($branch['versionURL']);
    $branch['version'] = $this->getVersion($content);

    return $branch;
  }

  /**
   * Get version
   *
   * @param string $content
   */
  public static function getVersion($content = '', $branch = 'master') {
    if (!$content) {
      $ch = curl_init(str_replace('{branch}', $branch, PwConnector::versionURL));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'ProcessWireGetVersion');
      $content = curl_exec($ch);
      curl_close($ch);
    }

    if (!preg_match_all('/const\s+version(Major|Minor|Revision)\s*=\s*(\d+)/', $content, $matches)) {
      $branch['version'] = '?';
      return;
    }

    $version = array();
    foreach ($matches[1] as $key => $var) {
      $version[$var] = (int) $matches[2][$key];
    }

    return "$version[Major].$version[Minor].$version[Revision]";
  }

  /**
   * Get available templates
   *
   * @param boolean $excludeFlagged
   */
  public function getAvailableTemplates($excludeFlagged = true) {
    $availableTemplates = array();
    foreach (\ProcessWire\wire('templates') as $template) {
      if ($excludeFlagged && $template->flags & Template::flagSystem) continue;
      $availableTemplates[] = $template->name;
    }

    return $availableTemplates;
  }

}
