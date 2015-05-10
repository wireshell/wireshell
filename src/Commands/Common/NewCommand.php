<?php namespace Wireshell\Commands\Common;

/*
 * This file is part of the Symfony Installer package.
 *
 * https://github.com/symfony/symfony-installer
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Wireshell\Helpers\Installer;

/**
 * Class NewCommand
 *
 * Downloads ProcessWire in current or in specified folder
 * Methods and approach based on T. Otwell's Laravel installer script: https://github.com/laravel/installer
 * Methods based on P. Urlich's ProcessWire online Installer script: https://github.com/somatonic/PWOnlineInstaller
 *
 * @package Wireshell
 * @author Taylor Otwell
 * @author Fabien Potencier
 * @author Philipp Urlich
 * @author Marcus Herrmann
 * @author Hari KT
 *
 */
class NewCommand extends Command
{

    /**
     * @var Filesystem
     */
    private $fs;

    private $projectName;

    private $projectDir;

    private $version;

    private $compressedFilePath;

    private $requirementsErrors = array();

    private $installer;

    private $master = 'https://github.com/ryancramerdesign/ProcessWire/archive/master.zip';

    private $dev = 'https://github.com/ryancramerdesign/ProcessWire/archive/dev.zip';

    /**
     * @var OutputInterface
     */
    private $output;

    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Creates a new ProcessWire project.')
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory where the new project will be created.')
            ->addOption('dbUser', null, InputOption::VALUE_REQUIRED, 'Database user.')
            ->addOption('dbName', null, InputOption::VALUE_REQUIRED, 'Database name.')
            ->addOption('dbPass', null, InputOption::VALUE_REQUIRED, 'Database password.')
            ->addOption('dbHost', null, InputOption::VALUE_REQUIRED, 'Database host.')
            ->addOption('dbPort', null, InputOption::VALUE_REQUIRED, 'Database port.')
            ->addOption('dbEngine', null, InputOption::VALUE_REQUIRED, 'Database engine.')
            ->addOption('dbCharset', null, InputOption::VALUE_REQUIRED, 'Database characterset.')
            ->addOption('timezone', null, InputOption::VALUE_REQUIRED, 'Timezone.')
            ->addOption('chmodDir', null, InputOption::VALUE_REQUIRED, 'Directory mode. Defaults 755.')
            ->addOption('chmodFile', null, InputOption::VALUE_REQUIRED, 'File mode. Defaults 644')
            ->addOption('httpHosts', null, InputOption::VALUE_REQUIRED, 'Hostname without www part.')
            ->addOption('adminUrl', null, InputOption::VALUE_REQUIRED, 'Admin url.')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, 'Admin username.')
            ->addOption('userpass', null, InputOption::VALUE_REQUIRED, 'Admin password.')
            ->addOption('useremail', null, InputOption::VALUE_REQUIRED, 'Admin email address.')
            ->addOption('profile', null, InputOption::VALUE_REQUIRED, 'Default site profile.')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Download dev branch')
            ->addOption('no-install', null, InputOption::VALUE_NONE, 'Disable installation');;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $directory = rtrim(trim($input->getArgument('directory')), DIRECTORY_SEPARATOR);
        $this->projectDir = $this->fs->isAbsolutePath($directory) ? $directory : getcwd() . DIRECTORY_SEPARATOR . $directory;
        $this->projectName = basename($directory);

        $logger = new Logger('name');
        $logger->pushHandler(new StreamHandler("php://output"));
        $this->installer = new Installer($logger, $this->projectDir);

        $this->version = '2.4.0';
        $this->output = $output;

        $profile = $input->getOption('profile');
        $branch = ($input->getOption('dev')) ? $this->dev : $this->master;

        try {
            $this
                ->checkProjectName()
                ->download($branch)
                ->extract()
                ->cleanUp();
        } catch (Exception $e) {
        }

        try {
            $install = ($input->getOption('no-install')) ? false : true;
            if ($install) {
                $this
                    ->extractProfile($profile)
                    ->checkProcessWireRequirements();

                $helper = $this->getHelper('question');

                $post = array(
                    'dbUser' => '',
                    'dbName' => '',
                    'dbPass' => '',
                    'dbHost' => 'localhost',
                    'dbPort' => '3306',
                    'dbEngine' => 'MyISAM',
                    'dbCharset' => 'utf8',
                    'timezone' => "366",
                    'chmodDir' => '755',
                    'chmodFile' => '644',
                    'httpHosts' => ''
                );
                $dbUser = $input->getOption('dbUser');
                if (!$dbUser) {
                    $question = new Question('Please enter the database user name : ', 'dbUser');
                    $dbUser = $helper->ask($input, $output, $question);
                }
                $post['dbUser'] = $dbUser;

                $dbName = $input->getOption('dbName');
                if (!$dbName) {
                    $question = new Question('Please enter the database name : ', 'dbName');
                    $dbName = $helper->ask($input, $output, $question);
                }
                $post['dbName'] = $dbName;

                $dbPass = $input->getOption('dbPass');
                if (!$dbPass) {
                    $question = new Question('Please enter the database password : ', 'dbPass');
                    $question->setHidden(true);
                    $question->setHiddenFallback(false);
                    $dbPass = $helper->ask($input, $output, $question);
                }
                $post['dbPass'] = $dbPass;

                $dbHost = $input->getOption('dbHost');
                if ($dbHost) {
                    $post['dbHost'] = $dbHost;
                }

                $dbPort = $input->getOption('dbPort');
                if ($dbPort) {
                    $post['dbPort'] = $dbPort;
                }

                $dbEngine = $input->getOption('dbEngine');
                if ($dbEngine) {
                    $post['dbEngine'] = $dbEngine;
                }

                $dbCharset = $input->getOption('dbCharset');
                if ($dbCharset) {
                    $post['dbCharset'] = $dbCharset;
                }

                $timezone = $input->getOption('timezone');
                if ($timezone) {
                    $post['timezone'] = $timezone;
                }

                $chmodDir = $input->getOption('chmodDir');
                if ($chmodDir) {
                    $post['chmodDir'] = $chmodDir;
                }

                $chmodFile = $input->getOption('chmodFile');
                if ($chmodFile) {
                    $post['chmodFile'] = $chmodFile;
                }

                $httpHosts = $input->getOption('httpHosts');
                if (!$httpHosts) {
                    $question = new Question('Please enter the hostname without www. Eg: pw.dev : ', 'httpHosts');
                    $httpHosts = $helper->ask($input, $output, $question);
                }
                $post['httpHosts'] = $httpHosts . "\n" . "www." . $httpHosts;

                $accountInfo = array(
                    'admin_name' => 'processwire',
                    'username' => '',
                    'userpass' => '',
                    'userpass_confirm' => '',
                    'useremail' => '',
                    'color' => 'classic',
                );

                $adminUrl = $input->getOption('adminUrl');
                if ($adminUrl) {
                    $accountInfo['admin_name'] = $adminUrl;
                }

                $username = $input->getOption('username');
                if (!$username) {
                    $question = new Question('Please enter admin user name : ', 'username');
                    $username = $helper->ask($input, $output, $question);
                }
                $accountInfo['username'] = $username;

                $userpass = $input->getOption('userpass');
                if (!$userpass) {
                    $question = new Question('Please enter admin password : ', 'password');
                    $question->setHidden(true);
                    $question->setHiddenFallback(false);
                    $userpass = $helper->ask($input, $output, $question);
                }
                $accountInfo['userpass'] = $userpass;
                $accountInfo['userpass_confirm'] = $userpass;

                $useremail = $input->getOption('useremail');
                if (!$useremail) {
                    $question = new Question('Please enter admin email address : ', 'useremail');
                    $useremail = $helper->ask($input, $output, $question);
                }
                $accountInfo['useremail'] = $useremail;
                $this
                    ->installProcessWire($post, $accountInfo);
            }
        } catch (\Exception $e) {
            $this->cleanUp();
            throw $e;
        }
    }

    /**
     * Checks whether it's safe to create a new project for the given name in the
     * given directory.
     *
     * @return NewCommand
     *
     * @throws \RuntimeException if a project with the same does already exist
     */
    private function checkProjectName()
    {
        if (is_dir($this->projectDir) && !$this->isEmptyDirectory($this->projectDir)) {
            throw new \RuntimeException(sprintf(
                "There is already a '%s' project in this directory (%s).\n" .
                "Change your project name or create it in another directory.",
                $this->projectName, $this->projectDir
            ));
        }

        return $this;
    }

    /**
     * Chooses the best compressed file format to download (ZIP or TGZ) depending upon the
     * available operating system uncompressing commands and the enabled PHP extensions
     * and it downloads the file.
     *
     * @return NewCommand
     *
     * @throws \RuntimeException if the ProcessWire archive could not be downloaded
     */
    private function download($branch)
    {
        $this->output->writeln("\n Downloading ProcessWire...");

        $distill = new Distill();
        $pwArchiveFile = $distill
            ->getChooser()
            ->setStrategy(new MinimumSize())
            ->addFile($branch)
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
     * Extracts the compressed Symfony file (ZIP or TGZ) using the
     * native operating system commands if available or PHP code otherwise.
     *
     * @return NewCommand
     *
     * @throws \RuntimeException if the downloaded archive could not be extracted
     */
    private function extract()
    {
        $this->output->writeln(" Preparing project...\n");

        try {
            $distill = new Distill();
            $extractionSucceeded = $distill->extractWithoutRootDirectory($this->compressedFilePath, $this->projectDir);
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
     * Removes all the temporary files and directories created to
     * download the project and removes ProcessWire-related files that don't make
     * sense in a proprietary project.
     *
     * @return NewCommand
     */
    private function cleanUp()
    {
        $this->fs->remove(dirname($this->compressedFilePath));

        try {
            $licenseFile = array($this->projectDir . '/LICENSE');
            $upgradeFiles = glob($this->projectDir . '/UPGRADE*.md');
            $changelogFiles = glob($this->projectDir . '/CHANGELOG*.md');

            $filesToRemove = array_merge($licenseFile, $upgradeFiles, $changelogFiles);
            $this->fs->remove($filesToRemove);

            $readmeContents = sprintf("%s\n%s\n\nA ProcessWire project created on %s.\n", $this->projectName,
                str_repeat('=', strlen($this->projectName)), date('F j, Y, g:i a'));
            $this->fs->dumpFile($this->projectDir . '/README.md', $readmeContents);
        } catch (\Exception $e) {
            // don't throw an exception in case any of the ProcessWire-related files cannot
            // be removed, because this is just an enhancement, not something mandatory
            // for the project
        }

        return $this;
    }

    /**
     * Checks if environment meets ProcessWire requirements
     *
     * @return OneclickCommand
     */
    private function checkProcessWireRequirements()
    {
        $this->installer->compatibilityCheck();

        return $this;
    }

    private function installProcessWire($post, $accountInfo)
    {
        $this->installer->dbSaveConfig($post, $accountInfo);

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
     * Formats the error message contained in the given Requirement item
     * using the optional line length provided.
     *
     * @param \Requirement $requirement The ProcessWire requirements
     * @param int $lineSize The maximum line length
     *
     * @return string
     */
    private function getErrorMessage(\Requirement $requirement, $lineSize = 70)
    {
        if ($requirement->isFulfilled()) {
            return;
        }

        $errorMessage = wordwrap($requirement->getTestMessage(), $lineSize - 3, PHP_EOL . '   ') . PHP_EOL;
        $errorMessage .= '   > ' . wordwrap($requirement->getHelpText(), $lineSize - 5, PHP_EOL . '   > ') . PHP_EOL;

        return $errorMessage;
    }

    /**
     * Returns the executed command.
     *
     * @return string
     */
    private function getExecutedCommand()
    {
        $version = '';
        if ('latest' !== $this->version) {
            $version = $this->version;
        }

        $pathDirs = explode(PATH_SEPARATOR, $_SERVER['PATH']);
        $executedCommand = $_SERVER['PHP_SELF'];
        $executedCommandDir = dirname($executedCommand);

        if (in_array($executedCommandDir, $pathDirs)) {
            $executedCommand = basename($executedCommand);
        }

        return sprintf('%s new %s %s', $executedCommand, $this->projectName, $version);
    }

    /**
     * Checks whether the given directory is empty or not.
     *
     * @param  string $dir the path of the directory to check
     * @return bool
     */
    private function isEmptyDirectory($dir)
    {
        // glob() cannot be used because it doesn't take into account hidden files
        // scandir() returns '.'  and '..'  for an empty dir
        return 2 === count(scandir($dir . '/'));
    }

    private function extractProfile($profile)
    {
        if (!$profile) {
            return $this;
        }
        $this->output->writeln(" Extracting profile...\n");

        try {
            $distill = new Distill();
            $extractPath = getcwd() . DIRECTORY_SEPARATOR . '.' . uniqid(time()) . DIRECTORY_SEPARATOR . 'pwprofile';
            $extractionSucceeded = $distill->extractWithoutRootDirectory($profile, $extractPath);
            if ($extractionSucceeded) {
                try {
                    $this->fs->mirror($extractPath, $this->projectDir . '/');
                } catch (\Exception $e) {
                }
                // cleanup
                $this->fs->remove($extractPath);
                try {
                    $process = new Process("cd $this->projectDir; composer install");
                    $process->run(function ($type, $buffer) {
                        if (Process::ERR === $type) {
                            echo ' ' . $buffer;
                        } else {
                            echo ' ' . $buffer;
                        }
                    });
                } catch (\Exception $e) {
                }
            }
        } catch (FileCorruptedException $e) {
            throw new \RuntimeException(
                "The profile can't be installed because the downloaded package is corrupted.\n"
            );
        } catch (FileEmptyException $e) {
            throw new \RuntimeException(
                "The profile can't be installed because the downloaded package is empty.\n"
            );
        } catch (TargetDirectoryNotWritableException $e) {
            throw new \RuntimeException(
                "The profile can't be installed because the installer doesn't have enough\n" .
                "permissions to uncompress and rename the package contents.\n"
            );
        } catch (\Exception $e) {
            throw new \RuntimeException(
                "The profile can't be installed because the downloaded package is corrupted\n" .
                "or because the installer doesn't have enough permissions to uncompress and\n" .
                "rename the package contents.\n" .
                $e->getMessage()
            );
        }

        if (!$extractionSucceeded) {
            throw new \RuntimeException(
                "The profile can't be installed because the downloaded package is corrupted\n" .
                "or because the uncompress commands of your operating system didn't work."
            );
        }

        return $this;
    }
}
