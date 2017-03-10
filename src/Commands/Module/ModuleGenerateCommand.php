<?php namespace Wireshell\Commands\Module;

use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\PwModuleTools;
use Wireshell\Helpers\WsTools as Tools;
use ZipArchive;

/**
 * Class ModuleGenerateCommand
 *
 * modules.pw
 *
 * @package Wireshell
 * @author Nico
 * @author Marcus Herrmann
 * @author Tabea David
 */
class ModuleGenerateCommand extends PwModuleTools {
  const API = 'http://modules.pw/api.php';
  protected $client;

  /**
   * @param ClientInterface $client
   */
  function __construct(ClientInterface $client) {
    $this->client = $client;
    parent::__construct();
  }

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('module:generate')
      ->setDescription('Generates a boilerplate module')
      ->addArgument('name', InputOption::VALUE_REQUIRED, 'Provide a class name for the module')
      ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Module title')
      ->addOption('mod-version', null, InputOption::VALUE_REQUIRED, 'Module version')
      ->addOption('author', null, InputOption::VALUE_REQUIRED, 'Module author')
      ->addOption('link', null, InputOption::VALUE_REQUIRED, 'Module link')
      ->addOption('summary', null, InputOption::VALUE_REQUIRED, 'Module summary')
      ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Module type')
      ->addOption('extends', null, InputOption::VALUE_REQUIRED, 'Module extends')
      ->addOption('implements', null, InputOption::VALUE_REQUIRED, 'Module implements (Interface)')
      ->addOption('require-pw', null, InputOption::VALUE_REQUIRED, 'Module\'s ProcessWire version compatibility')
      ->addOption('require-php', null, InputOption::VALUE_REQUIRED, 'Module\'s PHP version compatibility')
      ->addOption('is-autoload', null, InputOption::VALUE_NONE, 'autoload = true')
      ->addOption('is-singular', null, InputOption::VALUE_NONE, 'singular = true')
      ->addOption('is-permanent', null, InputOption::VALUE_NONE, 'permanent = true')
      ->addOption('with-external-json', null, InputOption::VALUE_NONE, 'Generates external json config file')
      ->addOption('with-copyright', null, InputOption::VALUE_NONE, 'Adds copyright in comments')
      ->addOption('with-uninstall', null, InputOption::VALUE_NONE, 'Adds uninstall method')
      ->addOption('with-sample-code', null, InputOption::VALUE_NONE, 'Adds sample code')
      ->addOption('with-config-page', null, InputOption::VALUE_NONE, 'Adds config page');
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

    $modName = \ProcessWire\wire('sanitizer')->name($input->getArgument('name'));
    if (!$modName) $this->tools->writeErrorAndExit('Please provide a class name for the module.');

    $request = $this->createRequest($modName, $output, $input);
    $modDir = getcwd() . parent::getModuleDirectory();

    $this->download($request, $modDir)
        ->extract($modDir)
        ->cleanUp($modDir, $modName);
  }

  protected function getDefaults() {
    return array(
      'version' => '0.0.1',
      'requirePw' => \ProcessWire\wire('config')->version,
      'requirePhp' => PHP_VERSION
    );
  }

  /**
   * @param $modName
   * @return string
   */
  private function createRequest($modName, OutputInterface $output, InputInterface $input) {
    if ($this->checkIfModuleExists($modName)) {
      $this->tools->writeErrorAndExit("Module '{$modName}' already exists!");
    }

    $defaults = $this->getDefaults();
    $request = self::API . "?name={$modName}";
    $this->tools->write('Generating module at `modules.pw` ..');

    $params = array(
      'title' => $input->getOption('title'),
      'version' => $input->getOption('mod-version') ? $input->getOption('mod-version') : $defaults['version'],
      'author' => $input->getOption('author'),
      'link' => $input->getOption('link'),
      'summary' => $input->getOption('summary'),
      'type' => $input->getOption('type'),
      'extends' => $input->getOption('extends'),
      'implements' => $input->getOption('implements'),
      'require-pw' => $input->getOption('require-pw') ? $input->getOption('require-pw') : $defaults['requirePw'],
      'require-php' => $input->getOption('require-php') ? $input->getOption('require-php') : $defaults['requirePhp'],
    );

    $paramsBool = array(
      'is-autoload' => $input->getOption('is-autoload'),
      'is-singular' => $input->getOption('is-singular'),
      'is-permanent' => $input->getOption('is-permanent'),
      'with-external-json' => $input->getOption('with-external-json'),
      'with-copyright' => $input->getOption('with-copyright'),
      'with-uninstall' => $input->getOption('with-uninstall'),
      'with-config-page' => $input->getOption('with-config-page')
    );

    foreach ($params as $key => $param) {
      if ($param) $request .= "&{$key}={$param}";
    }

    foreach ($paramsBool as $key => $param) {
      if ($param) $request .= "&{$key}=true";
    }

    return $request;
  }

  /**
   * @param $request
   * @param $modDir
   * @return $this
   */
  private function download($request, $modDir) {
    $this->tools->write("Downloading ... - {$request}");

    $response = $this->client->get($request)->getBody();
    file_put_contents("$modDir/temp.zip", $response);

    return $this;
  }

  /**
   * @param $modDir
   * @return $this
   */
  private function extract($modDir) {
    $this->tools->write('Extracting ...');

    $archive = new ZipArchive;
    $archive->open($modDir . '/temp.zip');
    $archive->extractTo($modDir);
    $archive->close();

    return $this;
  }

  /**
   * @param $modDir
   * @return $this
   */
  private function cleanUp($modDir, $modName) {
    @chmod($modDir . '/temp.zip', 0777);
    @unlink($modDir . '/temp.zip');

    $this->tools->writeSuccess("Module {$modName} created successfully!");

    return $this;
  }

}
