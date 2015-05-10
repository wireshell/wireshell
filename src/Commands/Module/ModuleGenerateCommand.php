<?php namespace Wireshell\Commands\Module;

use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\PwModuleTools;
use ZipArchive;

/**
 * Class ModuleGenerateCommand
 *
 * modules.pw
 *
 * @package Wireshell
 * @author Nico
 * @author Marcus Herrmann
 */

class ModuleGenerateCommand extends PwModuleTools
{

    protected $api = "http://modules.pw/api.php";
    protected $client;

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
    protected function configure()
    {
        $this
            ->setName('module:generate')
            ->setAliases(['m:g'])
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $modName = wire('sanitizer')->name($input->getArgument('name'));

        $request = $this->createRequest($modName, $output, $input);

        $modDir = $this->getModuleDirectory();

        $this->download($request, $modDir, $output);

        $this->extract($modDir, $output);

        $this->cleanUp($modDir,$modName, $output);

    }

    /**
     * @return string
     */
    protected function getModuleDirectory()
    {
        $modDir = getcwd() . parent::getModuleDirectory();

        return $modDir;
    }

    protected function getDefaults()
    {
        return [
            'version' => '0.0.1',
            'requirePw' => wire('config')->version,
            'requirePhp' => PHP_VERSION
        ];
    }


    /**
     * @param $modName
     * @return string
     */
    private function createRequest($modName, OutputInterface $output, InputInterface $input)
    {
        if ($this->checkIfModuleExists($modName)) {
            $output->writeln("<error>Module '{$modName}' already exists!</error>\n");
            exit(1);
        }

        $defaults = $this->getDefaults();
        $output->writeln("<comment>Generating module at modules.pw ...</comment>");

        $title = $input->getOption('title');
        $modVersion = ($input->getOption('mod-version')) ? $input->getOption('mod-version') : $defaults['version'];
        $author = $input->getOption('author');
        $link = $input->getOption('link');
        $summary = $input->getOption('summary');
        $type = $input->getOption('type');
        $extends = $input->getOption('extends');
        $implements = $input->getOption('implements');
        $requirePw = ($input->getOption('require-pw')) ? $input->getOption('require-pw') : $defaults['requirePw'];
        $requirePhp = ($input->getOption('require-php')) ? $input->getOption('require-php') : $defaults['requirePhp'];
        $isAutoload = $input->getOption('is-autoload');
        $isSingular = $input->getOption('is-singular');
        $isPermanent = $input->getOption('is-permanent');
        $withExternalJson = $input->getOption('with-external-json');
        $withCopyright = $input->getOption('with-copyright');
        $withUninstall = $input->getOption('with-uninstall');
        $withConfigPage = $input->getOption('with-config-page');

        $request = $this->api . "?name=" . $modName;

        if ($title) $request .= "&title={$title}";
        if ($modVersion) $request .= "&version={$modVersion}";
        if ($author) $request .= "&author={$author}";
        if ($link) $request .= "&link={$link}";
        if ($summary) $request .= "&summary={$summary}";
        if ($type) $request .= "&summary={$type}";
        if ($extends) $request .= "&extends={$extends}";
        if ($implements) $request .= "&implements={$implements}";
        if ($requirePw) $request .= "&require-pw='{$requirePw}'";
        if ($requirePhp) $request .= "&require-php='{$requirePhp}'";
        if ($isAutoload) $request .= "&is-autoload=true";
        if ($isSingular) $request .= "&is-singular=true";
        if ($isPermanent) $request .= "&is-permanent=true";
        if ($withExternalJson) $request .= "&with-external-json=true";
        if ($withCopyright) $request .= "&with-copyright=true";
        if ($withUninstall) $request .= "&with-uninstall=true";
        if ($withConfigPage) $request .= "&with-config-page=true";

        return $request;

    }

    /**
     * @param $request
     * @param $modDir
     * @param OutputInterface $output
     * @return $this
     */
    private function download($request, $modDir, OutputInterface $output)
    {
        $output->writeln("<comment>Downloading ... - {$request}</comment>");

        $response = $this->client->get($request)->getBody();

        file_put_contents($modDir . "/temp.zip", $response);

        return $this;
    }

    /**
     * @param $modDir
     * @param OutputInterface $output
     * @return $this
     */
    private function extract($modDir, OutputInterface $output)
    {
        $output->writeln("<comment>Extracting...</comment>");

        $archive = new ZipArchive;

        $archive->open($modDir . "/temp.zip");
        $archive->extractTo($modDir);

        $archive->close();

        return $this;
    }

    /**
     * @param $modDir
     * @return $this
     */
    private function cleanUp($modDir, $modName, OutputInterface $output)
    {
        @chmod($modDir . "/temp.zip", 0777);
        @unlink($modDir . "/temp.zip");

        $output->writeln("<info>Module {$modName} created successfully!</info>");

        return $this;
    }



}
