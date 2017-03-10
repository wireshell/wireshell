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
use Wireshell\Helpers\WsTools as Tools;

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
  private $tools;

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
    $this->projectDir = substr($projectDir, -1) === '/' ? substr($projectDir, 0, -1) : $projectDir;
    $this->version = $version;
    $this->tools = new Tools($output);
  }

  /**
   * Chooses the best compressed file format to download (ZIP or TGZ) depending upon the
   * available operating system uncompressing commands and the enabled PHP extensions
   * and it downloads the file.
   *
   * @param string $uri
   * @param string $prefix
   * @throws \RuntimeException if the ProcessWire archive could not be downloaded
   */
  public function download($uri, $prefix = 'pw') {
    $distill = new Distill();
    $pwArchiveFile = $distill
      ->getChooser()
      ->setStrategy(new MinimumSize())
      ->addFile($uri)
      ->getPreferredFile();

    $client = new Client();

    // store the file in a temporary hidden directory with a random name
    $tmpFolder = '.' . uniqid(time());
    $archiveName = $prefix . '.' . pathinfo($pwArchiveFile, PATHINFO_EXTENSION); 
    $progressBar = null;
    $this->compressedFilePath = $this->projectDir . DIRECTORY_SEPARATOR. $tmpFolder . DIRECTORY_SEPARATOR . $archiveName; 
    $this->fs->mkdir($this->projectDir . DIRECTORY_SEPARATOR . $tmpFolder);

    try {
      $response = $client->request('GET', $uri, [
        'sink' => $this->compressedFilePath,
        'progress' => function ($size, $downloaded) use (&$progressBar) {
          if (!$progressBar && $size) {
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

          if ($progressBar)  {
            $progressBar->setProgress($downloaded);
          } elseif (!$progressBar && $downloaded) {
            // Move the cursor to the beginning of the line
            $this->output->write("\x0D");

            // Erase the line
            $this->output->write("\x1B[2K");
            $this->output->write('  ' . $this->formatSize($downloaded));
          }
        }
      ]);
    } catch (ClientException $e) {
      if ($e->getCode() === 403 || $e->getCode() === 404) {
        if ($prefix === 'pw') {
          throw new \RuntimeException(sprintf(
            "The selected version (%s) cannot be installed because it does not exist.\n" .
            "Try the special \"latest\" version to install the latest stable ProcessWire release:\n" .
            '%s %s latest',
            $this->version,
            $_SERVER['PHP_SELF'],
            $this->projectDir
          ));
        } else {
          throw new \RuntimeException(
            "The selected module {$this->version} cannot be downloaded because it does not exist.\n"
          );
        }
      } else {
        if ($prefix === 'pw') {
          throw new \RuntimeException(sprintf(
            "The selected version (%s) couldn't be downloaded because of the following error:\n%s",
            $this->version,
            $e->getMessage()
          ));
        } else {
          throw new \RuntimeException(sprintf(
            "The selected module (%s) couldn't be downloaded because of the following error:\n%s",
            $this->version,
            $e->getMessage()
          ));
        }
      }
    }

    $this->fs->dumpFile($this->compressedFilePath, $response->getBody());

    if ($progressBar) $progressBar->finish();

    return $this->compressedFilePath;
  }

  /**
   * Extract archive
   *
   * @param string $from
   * @param string $to
   * @param string $name
   */
  public function extract($from, $to, $name = '') {
    $source = $name ? 'ProcessWire' : 'the module';

    try {
      $distill = new Distill();
      $extractionSucceeded = $distill->extractWithoutRootDirectory($from, $to);
    } catch (FileCorruptedException $e) {
      throw new \RuntimeException(sprintf(
        "%s can't be installed because the downloaded package is corrupted.\n" .
        "To solve this issue, try installing %s again.\n%s",
        ucfirst($source), $source, $name
      ));
    } catch (FileEmptyException $e) {
      throw new \RuntimeException(sprintf(
        "%s can't be installed because the downloaded package is empty.\n" .
        "To solve this issue, try installing %s again.\n%s",
        ucfirst($source), $source, $name
      ));
    } catch (TargetDirectoryNotWritableException $e) {
      throw new \RuntimeException(sprintf(
        "%s can't be installed because the installer doesn't have enough\n" .
        "permissions to uncompress and rename the package contents.\n" .
        "To solve this issue, check the permissions of the %s directory and\n" .
        "try installing %s again.\n%s",
        ucfirst($source), getcwd(), $source, $name
      ));
    } catch (\Exception $e) {
      throw new \RuntimeException(sprintf(
        "%s can't be installed because the downloaded package is corrupted\n" .
        "or because the installer doesn't have enough permissions to uncompress and\n" .
        "rename the package contents.\n" .
        "To solve this issue, check the permissions of the %s directory and\n" .
        "try installing %s again.\n%s",
        ucfirst($source), getcwd(), $source, $name
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
