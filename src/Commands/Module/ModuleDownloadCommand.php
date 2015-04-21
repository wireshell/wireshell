<?php namespace Wireshell\Commands\Module;

use Distill\Distill;
use Distill\Exception\IO\Input\FileCorruptedException;
use Distill\Exception\IO\Input\FileEmptyException;
use Distill\Exception\IO\Output\TargetDirectoryNotWritableException;
use Distill\Strategy\MinimumSize;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Progress\Progress;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Wireshell\PwConnector;

/**
 * Class ModuleDownloadCommand
 *
 * Downloads module(s)
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David <td@kf-interactive.com>
 */

class ModuleDownloadCommand extends PwConnector
{


    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('mod:download')
            ->setAliases(['m:dl'])
            ->setDescription('Downloads ProcessWire module(s).')
            ->addArgument('modules', InputOption::VALUE_REQUIRED, 'Provide one or more module class name, comma separated: Foo,Bar');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $this->fs = new Filesystem();
        $this->output = $output;

        $modules = explode(",", $input->getArgument('modules'));

        if (!ini_get('allow_url_fopen')) {
            // check if we have the rights to download files from other domains
            // using copy or file_get_contents
            $this->output->writeln('The php config `allow_url_fopen` is disabled on the server. Enable it then try again.');
        } elseif (!is_writable(wire('config')->paths->siteModules)) {
            // check if module directory is writeable
            $this->output->writeln('Make sure your /site/modules directory is writeable by PHP.');
        } else {
            foreach ($modules as $module) {
                $this->output->writeln("\n<bg=yellow;options=bold> - Module '$module': </bg=yellow;options=bold>\n");

                if ($this->checkIfModuleExists($module)) {
                    $this->output->writeln(" <error> Module '{$module}' already exists! </error>\n");
                } else {
                    // reset PW modules cache
                    wire('modules')->resetCache();
                    $this->downloadModuleIfExists($module);
                }
            }

            wire('modules')->resetCache();
        }
    }

    /**
     * check if a module already exists
     *
     * @param string $module
     * @return boolean
     */
    private function checkIfModuleExists($module)
    {
        $moduleDir = wire('config')->paths->siteModules . $module;
        if (wire("modules")->get("{$module}")) {
            $return = true;
        }

        if (is_dir($moduleDir) && !$this->isEmptyDirectory($moduleDir)) {
            $return = true;
        }

        return (isset($return)) ? $return : false;
    }

    /**
     * check if a module exists in processwire module directory
     * download it
     *
     * @param string $module
     */
    public function downloadModuleIfExists($module) {
        $contents = file_get_contents(
            wire('config')->moduleServiceURL .
            '?apikey=' . wire('config')->moduleServiceKey .
            '&limit=1' . '&class_name=' . $module
        );

        $result = json_decode($contents);

        if ($result->status === 'error') {
            $this->output->writeln(" <error> A module with the class '$module' does not exist. </error>\n");
        } else {
            // yeah! module exists
            $item = $result->items[0];
            $moduleUrl = $item->project_url . '/archive/master.zip';

            try {
                $this
                    ->download($moduleUrl, $module)
                    ->extract($module)
                    ->cleanUp($module);
            } catch (Exception $e) {
                $this->output->writeln(" <error> Could not download module $module. Please try again later. </error>\n");
            }
        }
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
    private function download($url, $module)
    {
        $this->output->writeln(" Downloading module $module...");

        $distill = new Distill();
        $pwArchiveFile = $distill
            ->getChooser()
            ->setStrategy(new MinimumSize())
            ->addFile($url)
            ->getPreferredFile();

            /** @var ProgressBar|null $progressBar */
            $progressBar = null;
            $downloadCallback = function ($size, $downloaded, $client, $request, Response $response) use (&$progressBar) {
            // Don't initialize the progress bar for redirects as the size is much smaller
            if ($response->getStatusCode() >= 300) {
                return;
            }

            if (null === $progressBar) {
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

            $progressBar->setProgress($downloaded);
        };

        $client = new Client();
        $client->getEmitter()->attach(new Progress(null, $downloadCallback));

        // store the file in a temporary hidden directory with a random name
        $this->compressedFilePath = wire('config')->paths->siteModules.'.'.uniqid(time()).DIRECTORY_SEPARATOR.$module.'.'.pathinfo($pwArchiveFile, PATHINFO_EXTENSION);

        try {
            $response = $client->get($pwArchiveFile);
        } catch (ClientException $e) {
            if ($e->getCode() === 403 || $e->getCode() === 404) {
                throw new \RuntimeException(
                    "The selected module $module cannot be downloaded because it does not exist.\n"
                );
            } else {
                throw new \RuntimeException(sprintf(
                    "The selected module (%s) couldn't be downloaded because of the following error:\n%s",
                    $module,
                    $e->getMessage()
                ));
            }
        }

        $this->fs->dumpFile($this->compressedFilePath, $response->getBody());

        if (null !== $progressBar) {
            $progressBar->finish();
            $this->output->writeln("\n");
        }

        return $this;
    }

    /**
     * Extracts the compressed Symfony file (ZIP or TGZ) using the
     * native operating system commands if available or PHP code otherwise.
     *
     * param string $module
     * @return NewCommand
     *
     * @throws \RuntimeException if the downloaded archive could not be extracted
     */
    private function extract($module)
    {
        $this->output->writeln(" Preparing module...\n");

        try {
            $distill = new Distill();
            $extractionSucceeded = $distill->extractWithoutRootDirectory($this->compressedFilePath, wire('config')->paths->siteModules . $module);
            $dir = wire('config')->paths->siteModules . $module;
            if (is_dir($dir)) chmod($dir, 0755);
        } catch (FileCorruptedException $e) {
            throw new \RuntimeException(
                "This module can't be downloaded because the downloaded package is corrupted.\n".
                "To solve this issue, try installing the module again.\n"
            );
        } catch (FileEmptyException $e) {
            throw new \RuntimeException(
                "This module can't be downloaded because the downloaded package is empty.\n".
                "To solve this issue, try installing the module again.\n"
            );
        } catch (TargetDirectoryNotWritableException $e) {
            throw new \RuntimeException(sprintf(
                "This module can't be downloaded because the installer doesn't have enough\n".
                "permissions to uncompress and rename the package contents.\n".
                "To solve this issue, check the permissions of the %s directory and\n".
                "try installing this module again.\n",
                getcwd()
            ));
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                "This module can't be downloaded because the downloaded package is corrupted\n".
                "or because the installer doesn't have enough permissions to uncompress and\n".
                "rename the package contents.\n".
                "To solve this issue, check the permissions of the %s directory and\n".
                "try installing this module again.\n",
                getcwd()
            ));
        }

        if (!$extractionSucceeded) {
            throw new \RuntimeException(
                "This module can't be downloaded because the downloaded package is corrupted\n".
                "or because the uncompress commands of your operating system didn't work."
            );
        }

        return $this;
    }


    /**
     * Utility method to show the number of bytes in a readable format.
     *
     * @param int     $bytes The number of bytes to format
     *
     * @return string The human readable string of bytes (e.g. 4.32MB)
     */
    private function formatSize($bytes)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return number_format($bytes, 2).' '.$units[$pow];
    }

    /**
     * Removes all the temporary files and directories created to
     * download the project and removes ProcessWire-related files that don't make
     * sense in a proprietary project.
     *
     * @param string $module
     * @return NewCommand
     */
    private function cleanUp($module)
    {
        $this->fs->remove(dirname($this->compressedFilePath));
        $this->output->writeln("<info> Module {$module} downloaded successfully.</info>\n");

        return $this;
    }

    /**
     * Checks whether the given directory is empty or not.
     *
     * @param  string  $dir the path of the directory to check
     * @return bool
     */
    private function isEmptyDirectory($dir)
    {
        // glob() cannot be used because it doesn't take into account hidden files
        // scandir() returns '.'  and '..'  for an empty dir
        return 2 === count(scandir($dir.'/'));
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
