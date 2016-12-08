<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Wireshell\Helpers\Downloader;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class UpgradeCommand
 *
 * @package Wireshell
 * @link http://php.net/manual/en/function.passthru.php
 * @author Marcus Herrmann
 * @author Ryan Cramer https://github.com/ryancramerdesign/ProcessWireUpgrade
 * @author Tabea David <info@justonestep.de>
 */
class UpgradeCommand extends PwConnector {

    private $fs;

    protected $indexHashes = array(
        '3.0.0' => '3a29d29b4ff7b2273b0739a82305ff71',
        '3.0.34' => 'b4f5308a9a3d53409393d875344c914b',
    );

    protected $htaccessHashes = array(
        '3.0.0' => 'd659abbf6c035b462b735743c007b17a', 
        '3.0.34' => '22aee112acd42dfc8d89ac246848457e',
    );

    protected $filesToReplace = array('wire', 'htaccess.txt', 'index.php');

    function __construct(Filesystem $fs) {
        $this->fs = $fs;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('upgrade')
            ->setDescription('Checks for core upgrades.')
            ->addOption('sha', null, InputOption::VALUE_REQUIRED, 'Download specific commit')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Just check for core upgrades.')
            ->addOption('download', null, InputOption::VALUE_NONE, 'Just download core upgrades.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::setOutput($output)::setInput($input)::bootstrapProcessWire();

        $this->tools = new Tools($output);
        $this->tools
            ->setInput($input)
            ->setHelper($this->getHelper('question'))
            ->writeBlockCommand($this->getName());

        $check = parent::checkForCoreUpgrades();
        $this->branch = $check['branch'];
        $this->output = $output;
        $this->root = \ProcessWire\wire('config')->paths->root;

        if ($check['upgrade'] && $input->getOption('check') === false) {
            if (!extension_loaded('pdo_mysql')) {
                $this->tools->writeError('Your PHP is not compiled with PDO support. PDO is required by ProcessWire.');
            }  elseif (!class_exists('ZipArchive')) {
                $this->tools->writeError('Your PHP does\'nt have ZipArchive support. This is required to install core or module upgrades.');
            } elseif (!is_writable($this->root)) {
                $this->tools->writeError('Your file system is not writable.');
            } else {
                $this->fs = new Filesystem();
                $this->projectDir = $this->fs->isAbsolutePath($this->root) ? $this->root : getcwd() . DIRECTORY_SEPARATOR . $this->root;

                try {
                    $this->downloader = new Downloader($this->output, $this->projectDir, $this->branch['version']);

                    $this
                        ->download()
                        ->extract()
                        ->move()
                        ->cleanup()
                        ->replace($input->getOption('download'), $input, $output);
                } catch (Exception $e) {
                    $this->tools->writeError('Sorry, ProcessWire could not be updated.');
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
    private function download() {
        $this->tools->writeInfo("- Downloading ProcessWire version " . $this->branch['version'] . "...");
        $this->compressedFilePath = $this->downloader->download($this->branch['zipURL']);
        $this->tools->nl();

        return $this;
    }

    /**
     * Extracts the compressed Symfony file (ZIP or TGZ) using the
     * native operating system commands if available or PHP code otherwise.
     *
     * @throws \RuntimeException if the downloaded archive could not be extracted
     */
    private function extract() {
        $this->tools->writeBlockBasic('- Preparing new core version...');
        $this->uncompressedFilePath = dirname($this->compressedFilePath);
        $this->downloader->extract($this->compressedFilePath, $this->uncompressedFilePath, $this->getName());

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
        $this->tools->writeInfo('- Upgrade files copied.');

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
            $this->tools->write(sprintf('
    For installation we\'ve prepared copies of upgrade files.
    At this point, you may install them yourself by replacing
    the existing %s directory, and optionally %s
    and %s files with the new versions indicated.
                ',
                $this->tools->writeComment('`wire/`', false),
                $this->tools->writeComment('`index.php`', false), 
                $this->tools->writeComment('`.htaccess`', false)
            ), false);
        } else {
            $this->tools->writeBlockBasic('- Installing new core files.');
            $replace = array('wire');

            $indexVersion = array_search(md5(file_get_contents($this->root . 'index.php')), $this->indexHashes);
            if (file_exists($this->root . '.htaccess')) {
              $htaccessVersion = array_search(md5(file_get_contents($this->root . '.htaccess')), $this->htaccessHashes);
            } else {
              $htaccessVersion = false;
            }

            if ($indexVersion) {
                $replace[] = 'index.php';

                $this->tools->write(sprintf('
    Your %s file is confirmed identical to the one included
    with ProcessWire version %s so it should be safe to replace
    without further analysis.
                    ',
                    $this->tools->writeComment('`index.php`', false), 
                    $this->tools->writeComment("`$indexVersion`", false)
                ), false);
            } else {
                $this->tools->write(sprintf('
    We\'ve detected that your %s file may contain site-specific
    customizations. Please double check before replacing it.
                    ',
                    $this->tools->writeComment('`index.php`', false)
                ), false);
            }

            if ($htaccessVersion) {
                $replace[] = 'htaccess.txt';

                $this->tools->write(sprintf('
    Your %s file is confirmed identical to the one included
    with ProcessWire version %s so it should be safe to replace
    without further analysis.
                    ',
                    $this->tools->writeComment('`.htaccess`', false), 
                    $this->tools->writeComment("`$htaccessVersion`", false)
                ), false);
            } else {
                $this->tools->write(sprintf('
    We\'ve detected that your %s file may contain site-specific
    customizations. Please double check before replacing it.
                    ',
                    $this->tools->writeComment('`.htaccess`', false)
                ), false);
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

            $this->tools->writeSuccess('Upgrade completed.');
            $this->tools->nl();
            $this->checkPermissions($input, $output);
            $this->tools->writeMark(' Now double check that everything works. ');

            $files = is_array($manually) ? implode(', ', array_flip($manually)) : '';
            if ($files) {
                $this->tools->write(sprintf('
    You have to replace %s manually.
                    ',
                    $this->tools->writeComment("`$files`", false)
                ), false);
            }
        }
    }

    /**
     * Check of permissions of updated files, chmod them on user's input via prompt
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function checkPermissions(InputInterface $input, OutputInterface $output) {
        $wireFolder = getcwd() . '/wire';
        $indexFile = getcwd() . '/index.php';

        $wireDirPermissions = substr(sprintf('%o', fileperms($wireFolder)), -4);
        $indexFilePermissions = substr(sprintf('%o', fileperms($indexFile)), -4);

        if (($wireDirPermissions == '0700') && ($indexFilePermissions == '0644')) {
            if (!$this->tools->askConfirmation(null, '- Change permissions on updated files and folders (type `y` or `n`)?'));

            $this->fs->chmod($indexFile, 0755);
            $this->fs->chmod($wireFolder, 0755, 0000, true);

            $this->tools->writeComment('- Permissions changed');
        }
    }
}
