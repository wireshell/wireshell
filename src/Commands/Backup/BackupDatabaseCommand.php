<?php namespace Wireshell\Commands\Backup;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Rah\Danpu\Dump;
use Rah\Danpu\Export;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class BackupDatabaseCommand
 *
 * Performs database dump
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class BackupDatabaseCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('backup:db')
      ->setDescription('Performs database dump')
      ->addOption('filename', null, InputOption::VALUE_REQUIRED, 'Provide a file name for the dump')
      ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Provide a file path for the dump (relative to ProcessWire root directory or absolute)');
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|null|void
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools->writeBlockCommand($this->getName());

    $config = \ProcessWire\wire('config');
    $database = $config->dbName;
    $host = $config->dbHost;
    $user = $config->dbUser;
    $pass = $config->dbPass;

    $inFilename = $input->getOption('filename');
    $filename = $inFilename ? "{$inFilename}.sql" : 'dump-' . date("Y-m-d-H-i-s") . '.sql';
    $target = $input->getOption('target') ? $input->getOption('target') : '';
    if ($target && !preg_match('/$\//', $target)) $target = "$target/";

    try {
      $dump = new Dump;
      $dump
        ->file($target . $filename)
        ->dsn("mysql:dbname={$database};host={$host}")
        ->user($user)
        ->pass($pass)
        ->tmp(getcwd() . 'site/assets/tmp');

      new Export($dump);
    } catch (Exception $e) {
      $tools->writeBlockError(array(
        "Export failed with message: `{$e->getMessage()}`.",
        'Please make sure that the provided target exists.'
      ));
      exit(1);
    }

    $tools->writeSuccess("Dumped database into `{$target}{$filename}` successfully.");
  }
}
