<?php namespace Wireshell\Commands\Common;

use Distill\Distill;
use Distill\Exception\IO\Input\FileCorruptedException;
use Distill\Exception\IO\Input\FileEmptyException;
use Distill\Exception\IO\Output\TargetDirectoryNotWritableException;
use Distill\Strategy\MinimumSize;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Progress\Progress;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Wireshell\Helpers\PwConnector;

/**
 * Class UpgradeCommand
 *
 * @package Wireshell
 * @link http://php.net/manual/en/function.passthru.php
 * @author Marcus Herrmann
 * @author Ryan Cramer https://github.com/ryancramerdesign/ProcessWireUpgrade
 * @author Tabea David <info@justonestep.de>
 */
class UpgradeCommand extends PwConnector
{

    /**
     * @var OutputInterface
     */
    private $output;

    private $fs;

    protected $indexHashes = array(
        '2.4.0' => 'ae121ccc9c14a2cd5fa57e8786bdbb3f',
        '2.5.0' => '9b20ce2898be505608d54a1e0dd81215',
        '2.6.0' => '8890078f9d233b038e5110c1caee5a95',
        '2.7.0' => '756b8a5685ce2b6b6e92062dcf040973',
        '3.0.0' => '3a29d29b4ff7b2273b0739a82305ff71',
    );

    protected $htaccessHashes = array(
        '2.4.0' => '5114479740cb1e79a8004f3eddeecb54',
        '2.5.0' => 'f8229ef5e26221226844d461e1a4d8d2',
        '2.6.0' => '31a04ba76f50c94bcf1f848d334d62c5',
        '2.7.0' => 'd659abbf6c035b462b735743c007b17a',
        '3.0.0' => 'd659abbf6c035b462b735743c007b17a',
    );

    protected $filesToReplace = array('wire', 'htaccess.txt', 'index.php');

    function __construct(Filesystem $fs)
    {
        $this->fs = $fs;
        parent::__construct();
    }


    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('upgrade')
            ->setDescription('Checks for core upgrades.')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Download dev branch')
            ->addOption('devns', null, InputOption::VALUE_NONE, 'Download devns branch (dev with namespace support)')
            ->addOption('sha', null, InputOption::VALUE_REQUIRED, 'Download specific commit')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Just check for core upgrades.')
            ->addOption('download', null, InputOption::VALUE_NONE, 'Just download core upgrades.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $check = parent::checkForCoreUpgrades($output, $input);
        $this->output = $output;
        $this->root = \ProcessWire\wire('config')->paths->root;

        if ($check['upgrade'] && $input->getOption('check') === false) {
            if (!extension_loaded('pdo_mysql')) {
                $this->output->writeln("<error>Your PHP is not compiled with PDO support. PDO is required by ProcessWire 2.4+.</error>");
            }  elseif (!class_exists('ZipArchive')) {
                $this->output->writeln(
                    "<error>Your PHP does not have ZipArchive support. This is required to install core or module upgrades with this tool.</error>"
                );
            } elseif (!is_writable($this->root)) {
                $this->output->writeln("<error>Your file system is not writable.</error>");
            } else {
                $this->fs = new Filesystem();
                $this->projectDir = $this->fs->isAbsolutePath($this->root) ? $this->root : getcwd() . DIRECTORY_SEPARATOR . $this->root;
                $this->branch = $check['branch'];

                try {
                    $this
                        ->download()
                        ->extract()
                        ->move()
                        ->cleanup()
                        ->replace($input->getOption('download'), $input, $output);
                } catch (Exception $e) {
                }
            }
        }
    }

    /**
     * Chooses the best compressed file format to download (ZIP or TGZ) depending upon the
     * available operating system uncompressing commands and the enabled PHP extensions
     * and it downloads the file.
     *
     * @throws \RuntimeException if the ProcessWire archive could not be downloaded
     */
    private function download()
    {
        $this->output->writeln("\n  Downloading ProcessWire Version " . $this->branch['version'] . "...");

        $distill = new Distill();
        $pwArchiveFile = $distill
            ->getChooser()
            ->setStrategy(new MinimumSize())
            ->addFile($this->branch['zipURL'])
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
        $this->compressedFilePath = getcwd() . DIRECTORY_SEPARATOR . '.' . uniqid(time()) . DIRECTORY_SEPARATOR . 'pw.' . pathinfo($pwArchiveFile,
            PATHINFO_EXTENSION);

        try {
            $response = $client->get($pwArchiveFile);
        } catch (ClientException $e) {
            if ($e->getCode() === 403 || $e->getCode() === 404) {
                throw new \RuntimeException(sprintf(
                    "The selected version (%s) cannot be installed because it does not exist.\n" .
                    "Try the special \"latest\" version to install the latest stable ProcessWire release:\n" .
                    '%s %s %s latest',
                    $this->version,
                    $_SERVER['PHP_SELF'],
                    $this->getName(),
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

        if (null !== $progressBar) {
            $progressBar->finish();
            $this->output->writeln("\n");
        }

        return $this;
    }

    /**
     * Utility method to show the number of bytes in a readable format.
     *
     * @param int $bytes The number of bytes to format
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

        return number_format($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Extracts the compressed Symfony file (ZIP or TGZ) using the
     * native operating system commands if available or PHP code otherwise.
     *
     * @throws \RuntimeException if the downloaded archive could not be extracted
     */
    private function extract()
    {
        $this->output->writeln("  Preparing new core version...\n");
        $this->uncompressedFilePath = dirname($this->compressedFilePath);

        try {
            $distill = new Distill();
            $extractionSucceeded = $distill->extractWithoutRootDirectory($this->compressedFilePath, $this->uncompressedFilePath);
        } catch (FileCorruptedException $e) {
            throw new \RuntimeException(sprintf(
                "ProcessWire can't be installed because the downloaded package is corrupted.\n" .
                "To solve this issue, try installing ProcessWire again.\n%s",
                $this->getExecutedCommand()
            ));
        } catch (FileEmptyException $e) {
            throw new \RuntimeException(sprintf(
                "ProcessWire can't be installed because the downloaded package is empty.\n" .
                "To solve this issue, try installing ProcessWire again.\n%s",
                $this->getExecutedCommand()
            ));
        } catch (TargetDirectoryNotWritableException $e) {
            throw new \RuntimeException(sprintf(
                "ProcessWire can't be installed because the installer doesn't have enough\n" .
                "permissions to uncompress and rename the package contents.\n" .
                "To solve this issue, check the permissions of the %s directory and\n" .
                "try installing ProcessWire again.\n%s",
                getcwd(), $this->getExecutedCommand()
            ));
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                "ProcessWire can't be installed because the downloaded package is corrupted\n" .
                "or because the installer doesn't have enough permissions to uncompress and\n" .
                "rename the package contents.\n" .
                "To solve this issue, check the permissions of the %s directory and\n" .
                "try installing ProcessWire again.\n%s",
                getcwd(), $this->getExecutedCommand()
            ));
        }

        if (!$extractionSucceeded) {
            throw new \RuntimeException(
                "ProcessWire can't be installed because the downloaded package is corrupted\n" .
                "or because the uncompress commands of your operating system didn't work."
            );
        }

        return $this;
    }

    /**
     * Moves the uncompressed files
     */
    private function move() {
        foreach ($this->filesToReplace as $rename) {
            $old = $this->uncompressedFilePath . '/' . $rename;
            $new = $this->root . $rename . '-' . $this->branch['version'];

            if (file_exists($new)) \ProcessWire\wireRmdir($new, true);
            rename($old, $new);
        }

        return $this;
    }

    /**
     * Cleanup
     */
    private function cleanup() {
        \ProcessWire\wireRmdir($this->uncompressedFilePath, true);
        $this->output->writeln('  Upgrade files copied.');
        return $this;
    }

    /**
     * Replace Core Files
     *
     * @param boolean $justDownload
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function replace($justDownload, InputInterface $input, OutputInterface $output) {
        if ($justDownload === true) {
            $this->output->writeln("
  We have prepared copies of upgrade files for installation.
  At this point, you may install them yourself by replacing
  the existing <fg=cyan;options=bold>wire/</fg=cyan;options=bold> directory, and optionally <fg=cyan;options=bold>index.php</fg=cyan;options=bold>
  and <fg=cyan;options=bold>.htaccess</fg=cyan;options=bold> files with the new versions indicated.
            ");
        } else {
            $this->output->writeln("  Installing new core files.");
            $replace = array('wire');

            $indexVersion = array_search(md5(file_get_contents($this->root . 'index.php')), $this->indexHashes);
            $htaccessVersion = array_search(md5(file_get_contents($this->root . '.htaccess')), $this->htaccessHashes);

            if ($indexVersion) {
                $this->output->writeln("
  Your <fg=cyan;options=bold>index.php</fg=cyan;options=bold> file is confirmed identical to the one included
  with ProcessWire version $indexVersion so it should be safe to replace
  without further analysis.
                ");
                $replace[] = 'index.php';
            } else {
                $this->output->writeln("
  We have detected that your <fg=cyan;options=bold>index.php</fg=cyan;options=bold> file may contain site-specific
  customizations. Please double check before replacing it.
                ");
            }

            if ($htaccessVersion) {
                $this->output->writeln("
  Your <fg=cyan;options=bold>.htaccess</fg=cyan;options=bold> file is confirmed identical to the one included
  with ProcessWire version $htaccessVersion so it should be safe to replace
  without further analysis.
                ");
                $replace[] = 'htaccess.txt';
            } else {
                $this->output->writeln("
  We have detected that your <fg=cyan;options=bold>.htaccess</fg=cyan;options=bold> file may contain site-specific
  customizations. Please double check before replacing it.
                ");
            }

            // do replacement
            $manually = array_flip($this->filesToReplace);
            foreach ($replace as $rename) {
                $old = $this->root . $rename . '-' . $this->branch['version'];
                $new = $this->root . $rename;

                if (file_exists($new)) \ProcessWire\wireRmdir($new, true);
                rename($old, $new);

                if (array_key_exists($rename, $manually)) unset($manually[$rename]);
            }

            $this->output->writeln("<info>  Upgrade completed.</info>");
            $this->checkPermissions($input, $output);
            $this->output->writeln("  Now double check that everything works.");

            $files = is_array($manually) ? implode(', ', array_flip($manually)) : '';
            if ($files) {
                $this->output->writeln("  You have to replace <fg=cyan;options=bold>$files</fg=cyan;options=bold> manually.");
            }

        }
    }

    /**
     * Check of permissions of updated files, chmod them on user's input via prompt
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function checkPermissions(InputInterface $input, OutputInterface $output)
    {
        $wireFolder = getcwd() . '/wire';
        $indexFile = getcwd() . '/index.php';

        $wireDirPermissions = substr(sprintf('%o', fileperms($wireFolder)), -4);
        $indexFilePermissions = substr(sprintf('%o', fileperms($indexFile)), -4);

        if (($wireDirPermissions == '0700') && ($indexFilePermissions == '0644')) {

            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('  Change permissions on updated files and folders? Type y or n: ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }

            $this->fs->chmod($indexFile, 0755);
            $this->fs->chmod($wireFolder, 0755, 0000, true);

            $this->output->writeln("<info>  Permissions changed.</info>");

        }

    }
}
