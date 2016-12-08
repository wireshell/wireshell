<?php namespace Wireshell\Helpers;

use Distill\Distill;
use Distill\Exception\IO\Input\FileCorruptedException;
use Distill\Exception\IO\Input\FileEmptyException;
use Distill\Exception\IO\Output\TargetDirectoryNotWritableException;
use Distill\Strategy\MinimumSize;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class Downloader
 *
 * @package Wireshell
 * @author Tabea David <info@justonestep.de>
 */
class Downloader {

  private $fs;
  private $projectDir;
  private $version;
  private $output;

  /**
   * Construct Downloader
   *
   * @param OutputInterface $output
   * @param string $projectDir
   * @param string $version
   */
  public function __construct($output, $projectDir, $version) {
    $this->fs = new Filesystem();
    $this->output = $output;
    $this->projectDir = $projectDir;
    $this->version = $version;
  }

  /**
   * Chooses the best compressed file format to download (ZIP or TGZ) depending upon the
   * available operating system uncompressing commands and the enabled PHP extensions
   * and it downloads the file.
   *
   * @param string $uri
   * @throws \RuntimeException if the ProcessWire archive could not be downloaded
   */
  public function download($uri) {
    $distill = new Distill();
    $pwArchiveFile = $distill
      ->getChooser()
      ->setStrategy(new MinimumSize())
      ->addFile($uri)
      ->getPreferredFile();

    $client = new Client();

    // store the file in a temporary hidden directory with a random name
    $tmpFolder = '.' . uniqid(time());
    $archiveName = 'pw.' . pathinfo($pwArchiveFile, PATHINFO_EXTENSION); 
    $this->compressedFilePath = $this->projectDir . DIRECTORY_SEPARATOR . $tmpFolder . DIRECTORY_SEPARATOR . $archiveName; 
    $this->fs->mkdir($this->projectDir . DIRECTORY_SEPARATOR . $tmpFolder);

    try {
      $response = $client->request('GET', $uri, [
        'sink' => $this->compressedFilePath,
        'progress' => function ($size, $downloaded) use (&$progressBar) {
          if (is_null($progressBar) && $size) {
            ProgressBar::setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
              return $this->formatSize($bar->getMaxSteps());
            });
            ProgressBar::setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
              return str_pad($this->formatSize($bar->getStep()), 11, ' ', STR_PAD_LEFT);
            });

            $progressBar = new ProgressBar($this->output, $size);
            $progressBar->setFormat('%current%/%max% %bar%  %percent:3s%%');
            $progressBar->setRedrawFrequency(max(1, floor($size / 1000)));
            $progressBar->setBarWidth(60);

            if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
              $progressBar->setEmptyBarCharacter('░'); // light shade character \u2591
              $progressBar->setProgressCharacter('');
              $progressBar->setBarCharacter('▓'); // dark shade character \u2593
            }

            $progressBar->start();
          }

          if ($progressBar) $progressBar->setProgress($downloaded);
        }
      ]);
    } catch (ClientException $e) {
      if ($e->getCode() === 403 || $e->getCode() === 404) {
        throw new \RuntimeException(sprintf(
          "The selected version (%s) cannot be installed because it does not exist.\n" .
          "Try the special \"latest\" version to install the latest stable ProcessWire release:\n" .
          '%s %s latest',
          $this->version,
          $_SERVER['PHP_SELF'],
          $this->projectDir
        ));
      } else {
        throw new \RuntimeException(sprintf(
          "The selected version (%s) couldn't be downloaded because of the following error:\n%s",
          $this->version,
          $e->getMessage()
        ));
      }
    }

    $this->fs->dumpFile($this->compressedFilePath, $response->getBody());
    $progressBar->finish();

    return $this->compressedFilePath;
  }

  /**
   * Extract archive
   *
   * @param string $from
   * @param string $to
   * @param string $name
   */
  public function extract($from, $to, $name) {
    try {
      $distill = new Distill();
      $extractionSucceeded = $distill->extractWithoutRootDirectory($from, $to);
    } catch (FileCorruptedException $e) {
      throw new \RuntimeException(sprintf(
        "ProcessWire can't be installed because the downloaded package is corrupted.\n" .
        "To solve this issue, try installing ProcessWire again.\n%s",
        $name
      ));
    } catch (FileEmptyException $e) {
      throw new \RuntimeException(sprintf(
        "ProcessWire can't be installed because the downloaded package is empty.\n" .
        "To solve this issue, try installing ProcessWire again.\n%s",
        $name
      ));
    } catch (TargetDirectoryNotWritableException $e) {
      throw new \RuntimeException(sprintf(
        "ProcessWire can't be installed because the installer doesn't have enough\n" .
        "permissions to uncompress and rename the package contents.\n" .
        "To solve this issue, check the permissions of the %s directory and\n" .
        "try installing ProcessWire again.\n%s",
        getcwd(), $name
      ));
    } catch (\Exception $e) {
      throw new \RuntimeException(sprintf(
        "ProcessWire can't be installed because the downloaded package is corrupted\n" .
        "or because the installer doesn't have enough permissions to uncompress and\n" .
        "rename the package contents.\n" .
        "To solve this issue, check the permissions of the %s directory and\n" .
        "try installing ProcessWire again.\n%s",
        getcwd(), $name
      ));
    }

    return $this;
  }

  /**
   * Utility method to show the number of bytes in a readable format
   *
   * @param int $bytes The number of bytes to format
   * @return string The human readable string of bytes (e.g. 4.32MB)
   */
  private function formatSize($bytes) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = $bytes ? floor(log($bytes, 1024)) : 0;
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return number_format($bytes, 2) . ' ' . $units[$pow];
  }

}
