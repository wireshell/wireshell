<?php namespace Wireshell\Helpers;

use ProcessWire\WireHttp;
use Distill\Distill;
use Distill\Exception\IO\Input\FileCorruptedException;
use Distill\Exception\IO\Input\FileEmptyException;
use Distill\Exception\IO\Output\TargetDirectoryNotWritableException;
use Distill\Strategy\MinimumSize;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Progress\Progress;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Wireshell\Helpers\WsTools as Tools;
use Wireshell\Helpers\Downloader;

/**
 * PwModuleTools
 *
 * Reusable methods for module generation, download, activation
 *
 * @package Wireshell
 * @author Tabea David <td@kf-interactive.com>
 * @author Marcus Herrmann
 */
class PwModuleTools extends PwConnector {
  /**
   * @var Filesystem
   */
  private $fs;
  protected $tools;

  const timeout = 4.5;

  /**
   * Construct PwModuleTools
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

    $this->tools = parent::setTools();
  }

  /**
   * Set module
   *
   * @param string $module
   */
  public function setModule($module) {
    $this->module = $module;
    $this->downloader = new Downloader($this->output, \ProcessWire\wire('config')->paths->siteModules, $module);
  }

  /**
   * check if a module already exists
   *
   * @param string $module
   * @return boolean
   */
  public function checkIfModuleExists($module) {
    $moduleDir = \ProcessWire\wire('config')->paths->siteModules . $module;
    $options = array(
      'noPermissionCheck' => true,
      'noInit' => true,
      'noInstall' => true
    );
    if (\ProcessWire\wire('modules')->getModule($module, $options)) {
      $return = true;
    }

    if (is_dir($moduleDir) && !$this->isEmptyDirectory($moduleDir)) $return = true;

    return (isset($return)) ? $return : false;
  }

  /**
   * Checks whether the given directory is empty or not.
   *
   * @param  string $dir the path of the directory to check
   * @return bool
   */
  public function isEmptyDirectory($dir) {
    // glob() cannot be used because it doesn't take into account hidden files
    // scandir() returns '.'  and '..'  for an empty dir
    return 2 === count(scandir($dir . '/'));
  }

  /**
   * Check all site modules for newer versions from the directory
   *
   * @param bool $onlyNew Only return array of modules with new versions available
   * @return array of array(
   *  'ModuleName' => array(
   *    'title' => 'Module Title',
   *    'local' => '1.2.3', // current installed version
   *     'remote' => '1.2.4', // directory version available, or boolean false if not found in directory
   *     'new' => true|false, // true if newer version available, false if not
   *     'requiresVersions' => array('ModuleName' => array('>', '1.2.3')), // module requirements
   *   )
   * )
   * @throws WireException
   *
   */
  public function getModuleVersions($onlyNew = false) {
    $url = \ProcessWire\wire('config')->moduleServiceURL .
      '?apikey=' . \ProcessWire\wire('config')->moduleServiceKey .
      '&limit=100' .
      '&field=module_version,version,requires_versions' .
      '&class_name=';

    $names = array();
    $versions = array();

    foreach (\ProcessWire\wire('modules') as $module) {
      $name = $module->className();
      $info = \ProcessWire\wire('modules')->getModuleInfoVerbose($name);
      if ($info['core']) continue;
      $names[] = $name;
      $versions[$name] = array(
        'title' => $info['title'],
        'local' => \ProcessWire\wire('modules')->formatVersion($info['version']),
        'remote' => false,
        'new' => 0,
        'requiresVersions' => $info['requiresVersions']
      );
    }

    if (!count($names)) return array();
    $url .= implode(',', $names);

    $http = new WireHttp();
    $http->setTimeout(self::timeout);
    $data = $http->getJSON($url);

    if (!is_array($data)) {
      $error = $http->getError();
      if (!$error) $error = 'Error retrieving modules directory data';
      $this->tools->writeError("$error");
      return array();
    }

    foreach ($data['items'] as $item) {
      $name = $item['class_name'];
      $versions[$name]['remote'] = $item['module_version'];
      $new = version_compare($versions[$name]['remote'], $versions[$name]['local']);
      $versions[$name]['new'] = $new;
      if ($new <= 0) {
        // local is up-to-date or newer than remote
        if ($onlyNew) unset($versions[$name]);
      } else {
        // remote is newer than local
        $versions[$name]['requiresVersions'] = $item['requires_versions'];
      }
    }

    if ($onlyNew) foreach($versions as $name => $data) {
      if($data['remote'] === false) unset($versions[$name]);
    }

    return $versions;
  }

  /**
   * Check all site modules for newer versions from the directory
   *
   * @param bool $onlyNew Only return array of modules with new versions available
   * @return array of array(
   *  'ModuleName' => array(
   *    'title' => 'Module Title',
   *    'local' => '1.2.3', // current installed version
   *     'remote' => '1.2.4', // directory version available, or boolean false if not found in directory
   *     'new' => true|false, // true if newer version available, false if not
   *     'requiresVersions' => array('ModuleName' => array('>', '1.2.3')), // module requirements
   *   )
   * )
   * @throws WireException
   */
  public function getModuleVersion($onlyNew = false, $module) {
    // get current module data
    $info = \ProcessWire\wire('modules')->getModuleInfoVerbose($module);
    $versions = array(
      'title' => $info['title'],
      'local' => \ProcessWire\wire('modules')->formatVersion($info['version']),
      'remote' => false,
      'new' => 0,
      'requiresVersions' => $info['requiresVersions']
    );

    // get latest module data
    $url = trim(\ProcessWire\wire('config')->moduleServiceURL, '/');
    $url .= "/$module/?apikey=" . \ProcessWire\wire('sanitizer')->name(\ProcessWire\wire('config')->moduleServiceKey);
    $http = new WireHttp();
    $data = $http->getJSON($url);

    if (!$data || !is_array($data)) {
      $this->tools->writeError("Error retrieving data from web service URL - `{$http->getError()}`.");
      return array();
    }

    if ($data['status'] !== 'success') {
      $error = \ProcessWire\wire('sanitizer')->entities($data['error']);
      $this->tools->writeError("Error reported by web service: `$error`");
      return array();
    }

    // yeah, received data sucessfully!
    // get versions and compare them
    $versions['remote'] = $data['module_version'];
    $new = version_compare($versions['remote'], $versions['local']);
    $versions['new'] = $new;
    $versions['download_url'] = $data['project_url'] . '/archive/master.zip';

    // local is up-to-date or newer than remote
    if ($new <= 0) {
      if ($onlyNew) $versions = array();
    } else {
      // remote is newer than local
      $versions['requiresVersions'] = $data['requires_versions'];
    }

    if ($onlyNew && !isset($versions['remote'])) $versions = array();

    return $versions;
  }

  /**
   * Removes all the temporary files and directories created to
   * download the project and removes ProcessWire-related files that don't make
   * sense in a proprietary project.
   *
   * @return NewCommand
   */
  public function cleanUpTmp() {
    $fs = new Filesystem();
    $fs->remove(dirname($this->compressedFilePath));
    $this->tools->writeSuccess(" Module `{$this->module}` downloaded successfully.");
  }

  /**
   * Extracts the compressed Symfony file (ZIP or TGZ) using the
   * native operating system commands if available or PHP code otherwise.
   *
   * @throws \RuntimeException if the downloaded archive could not be extracted
   */
  public function extractModule() {
    $this->tools->nl();
    $this->tools->writeComment(" Extracting module...\n");
    $dir = \ProcessWire\wire('config')->paths->siteModules . $this->module;
    if (is_dir($dir)) chmod($dir, 0755);

    $this->downloader->extract($this->compressedFilePath, \ProcessWire\wire('config')->paths->siteModules . $this->module);

    return $this;
  }

  /**
   * Chooses the best compressed file format to download (ZIP or TGZ) depending upon the
   * available operating system uncompressing commands and the enabled PHP extensions
   * and it downloads the file.
   *
   * @param string $url
   * @param string $module
   * @return NewCommand
   *
   * @throws \RuntimeException if the ProcessWire archive could not be downloaded
   */
  public function downloadModule($url) {
    $this->tools->writeComment(" Downloading module {$this->module}...");
    $this->compressedFilePath = $this->downloader->download($url, $this->module);
    $this->tools->nl();

    return $this;
  }

}
