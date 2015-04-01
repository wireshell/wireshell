<?php namespace Wireshell\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\ClientInterface;
use ZipArchive;
use DirectoryIterator;

/**
 * Class NewCommand
 *
 * Downloads ProcessWire in current or in specified folder
 * Methods and approach based on T. Otwell's Laravel installer script: https://github.com/laravel/installer
 * Methods based on P. Urlich's ProcessWire online Installer script: https://github.com/somatonic/PWOnlineInstaller
 *
 * @package Wireshell
 * @author Taylor Otwell
 * @author Philipp Urlich
 * @author Marcus Herrmann
 *
 */

class NewCommand extends Command
{

    private $master = 'https://github.com/ryancramerdesign/ProcessWire/archive/master.zip';
    private $dev = 'https://github.com/ryancramerdesign/ProcessWire/archive/dev.zip';
    private $client;

    /**
     * @param ClientInterface $client
     */
    function __construct(ClientInterface $client)
    {
        $this->client = $client;
        parent::__construct();
    }

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new ProcessWire installation.')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the new PW installation')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Download dev branch');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $directory = getcwd() . '/' . $name;

        $branch = ($input->getOption('dev')) ? $this->dev : $this->master;

        $this->assertInstallationDoesntAlreadyExist($name, $directory, $output);

        $this
            ->download($branch, $zipFile = $this->makeFileName(), $output)
            ->extract($zipFile, $directory, $output)
            ->cleanUp($zipFile);

        $output->writeln("<comment>ProcessWire installation ready at {$directory}!</comment>");

    }

    /**
     * @param $name
     * @param $directory
     * @param OutputInterface $output
     */
    private function assertInstallationDoesntAlreadyExist($name, $directory, OutputInterface $output)
    {
        if (($name && is_dir($directory)) OR (!$name && is_dir(getcwd() . '/wire'))) {

            $output->writeln("<error>ProcessWire installation already exists in {$directory}.</error>");
            exit(1);
        }

    }

    /**
     * @param $branch
     * @param $tempZip
     * @param $output
     * @return $this
     */
    private function download($branch, $tempZip, $output)
    {
        $output->writeln("downloading {$branch}...");

        $response = $this->client->get($branch)->getBody();

        file_put_contents($tempZip, $response);

        return $this;
    }


    /**
     * @return string
     */
    private function makeFileName()
    {
        return getcwd() . '/processwire_' . md5(time() . uniqid()) . '.zip';
    }


    /**
     * @param $tempZip
     * @param $directory
     * @param $output
     * @return $this
     */
    private function extract($tempZip, $directory, $output)
    {
        $output->writeln("extracting...");

        $archive = new ZipArchive;

        $archive->open($tempZip);
        $archive->extractTo($directory);

        $this->recursiveMove($directory . "/" . $archive->getNameIndex(0), $directory);
        $this->removeDir($directory . "/" . $archive->getNameIndex(0), true);

        $archive->close();

        return $this;
    }


    /**
     * @param $src
     * @param $dest
     * @return bool
     */
    private function recursiveMove($src, $dest)
    {
        if (!is_dir($src))
            return false;

        if (!is_dir($dest)) {

            if (!mkdir($dest)) {
                return false;
            }
        }

        $i = new DirectoryIterator($src);

        foreach ($i as $f) {

            if ($f->isFile()) {
                rename($f->getRealPath(), "$dest/" . $f->getFilename());
            } else if (!$f->isDot() && $f->isDir()) {
                $this->recursiveMove($f->getRealPath(), "$dest/$f");
            }
        }
    }

    /**
     * @param $dir
     * @param $deleteDir
     */
    private function removeDir($dir, $deleteDir)
    {
        if (!$dh = @opendir($dir))
            return;

        while (($obj = readdir($dh))) {
            if ($obj == '.' || $obj == '..')
                continue;

            if (!@unlink($dir . '/' . $obj))
                $this->removeDir($dir . '/' . $obj, true);
        }
        if ($deleteDir) {
            closedir($dh);
            @rmdir($dir);
        }
    }

    /**
     * @param $zipFile
     * @return $this
     */
    private function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);

        return $this;
    }
}
