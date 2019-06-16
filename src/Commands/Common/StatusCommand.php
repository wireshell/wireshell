<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\ProcessDiagnostics\DiagnoseImagehandling;
use Wireshell\Helpers\ProcessDiagnostics\DiagnosePhp;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTables as Tables;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class StatusCommand
 *
 * Returns versions, paths and environment info
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Camilo Castro
 * @author Tabea David
 * @author netcarver
 * @author horst
 */
class StatusCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('status')
      ->setDescription('Returns versions, paths and environment info')
      ->addOption('image', null, InputOption::VALUE_NONE, 'get Diagnose for Imagehandling')
      ->addOption('php', null, InputOption::VALUE_NONE, 'get Diagnose for PHP')
      ->addOption('pass', null, InputOption::VALUE_NONE, 'display database password');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $this->tools = new Tools($output);
    $tables = new Tables($output);
    $stTables = array();

    $this->tools->writeBlockCommand($this->getName());

    $pwStatus = $this->getPWStatus($input->getOption('pass'));
    $wsStatus = $this->getWsStatus();

    $stTables[] = $tables->buildTable($pwStatus, 'ProcessWire');
    $stTables[] = $tables->buildTable($wsStatus, 'wireshell');

    if ($input->getOption('php')) {
      $phpStatus = $this->getDiagnosePHP();
      $stTables[] = $tables->buildTable($phpStatus, 'PHP Diagnostics');
    }

    if ($input->getOption('image')) {
      $phpStatus = $this->getDiagnoseImagehandling();
      $stTables[] = $tables->buildTable($phpStatus, 'Image Diagnostics');
    }

    $tables->renderTables($stTables);
  }

  /**
   * @return array
   */
  protected function getPWStatus($showPass) {
    $config = \ProcessWire\wire('config');
    $on = $this->tools->writeInfo('On', false);
    $off = $this->tools->writeComment('Off', false);
    $none = $this->tools->writeComment('None', false);
    $version = $config->version;
    $latestVersion = parent::getVersion(); // master

    if (version_compare($version, $latestVersion, '>')) {
      $latestVersion = parent::getVersion('', self::BRANCH_DEV); // dev
      $version .= ' ' . self::BRANCH_DEV;
    }

    if ($version !== $latestVersion) {
      $version .= ' ' . $this->tools->writeMark("(upgrade available: $latestVersion)", false);
    }

    $adminUrl = $this->tools->writeLink($this->getAdminUrl(), false);
    $advancedMode = $config->advanced ? $on : $off;
    $debugMode = $config->debug ? $on : $off;
    $timezone = $config->timezone;
    $hosts = implode(", ", $config->httpHosts);
    $adminTheme = $config->defaultAdminTheme;
    $dbHost = $config->dbHost;
    $dbName = $config->dbName;
    $dbUser = $config->dbUser;
    $dbPass = $showPass ? $config->dbPass : '*****';
    $dbPort = $config->dbPort;

    $prepended = trim($config->prependTemplateFile);
    $appended = trim($config->appendTemplateFile);
    $prependedTemplateFile = $prepended != '' ? $prepended : $none;
    $appendedTemplateFile = $appended != '' ? $appended : $none;

    $installPath = getcwd();

    $status = [
      ['Version', $version],
      ['Admin URL', $adminUrl],
      ['Advanced mode', $advancedMode],
      ['Debug mode', $debugMode],
      ['Timezone', $timezone],
      ['HTTP hosts', $hosts],
      ['Admin theme', $adminTheme],
      ['Prepended template file', $prependedTemplateFile],
      ['Appended template file', $appendedTemplateFile],
      ['Database host', $dbHost],
      ['Database name', $dbName],
      ['Database user', $dbUser],
      ['Database password', $dbPass],
      ['Database port', $dbPort],
      ['Installation path', $installPath]
    ];

    return $status;
  }

  /**
   * @return array
   */
  protected function getWsStatus() {
    return array(
      array('Version', $this->getApplication()->getVersion()),
      array('Documentation', $this->tools->writeLink('https://docs.wireshell.pw', false)),
      array('License', 'MIT')
    );
  }

  /**
   * @return string
   */
  protected function getAdminUrl() {
    $admin = \ProcessWire\wire('pages')->get('template=admin');
    $url = \ProcessWire\wire('config')->urls->admin;

    if (!($admin instanceof \ProcessWire\NullPage) && isset($admin->httpUrl)) {
      $url = $admin->httpUrl;
    }

    return $url;
  }

  /**
   * wrapper method for the Diagnose PHP submodule from @netcarver
   */
  protected function getDiagnosePHP() {
    $sub = new DiagnosePhp();
    $rows = $sub->GetDiagnostics();
    $result = [];

    foreach ($rows as $row) {
      $result[] = [$row['title'], $row['value']];
    }

    return $result;
  }

  /**
   * wrapper method for the Diagnose Imagehandling submodule from @netcarver & @horst
   */
  protected function getDiagnoseImagehandling() {
    $sub = new DiagnoseImagehandling();
    $rows = $sub->GetDiagnostics();
    $result = [];

    foreach ($rows as $row) {
      $result[] = [$row['title'], $row['value']];
    }

    return $result;
  }

}
