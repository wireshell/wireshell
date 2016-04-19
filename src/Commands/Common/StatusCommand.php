<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\ProcessDiagnostics\DiagnoseImagehandling;
use Wireshell\Helpers\ProcessDiagnostics\DiagnosePhp;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;


/**
 * Class StatusCommand
 *
 * Returns versions, paths and environment info
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Camilo Castro
 * @author netcarver
 * @author horst
 */
class StatusCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Returns versions, paths and environment info')
            ->addOption('image', null, InputOption::VALUE_NONE, 'get Diagnose for Imagehandling')
            ->addOption('php', null, InputOption::VALUE_NONE, 'get Diagnose for PHP');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $pwStatus = $this->getPWStatus();

        $wsStatus = $this->getWsStatus();

        $tables = [];
        $tables[] = $this->buildTable($output, $pwStatus, 'ProcessWire');
        $tables[] = $this->buildTable($output, $wsStatus, 'wireshell');


        if ($input->getOption('php')) {
            $phpStatus = $this->getDiagnosePHP();
            $tables[] = $this->buildTable($output, $phpStatus, 'PHP Diagnostics');
        }

        if ($input->getOption('image')) {
            $phpStatus = $this->getDiagnoseImagehandling();
            $tables[] = $this->buildTable($output, $phpStatus, 'Image Diagnostics');
        }


        $this->renderTables($output, $tables);
    }

    /**
     * @return array
     */
    protected function getPWStatus()
    {
        $config = \ProcessWire\wire('config');
        $on = Tools::tint('On', Tools::kTintError);
        $off = Tools::tint('Off', Tools::kTintInfo);
        $none = Tools::tint('None', Tools::kTintInfo);


        $version = $config->version;
        $adminUrl = $this->getAdminUrl();
        $advancedMode = $config->advanced ? $on : $off;
        $debugMode = $config->debug ? $on : $off;
        $timezone = $config->timezone;
        $hosts = implode(", ", $config->httpHosts);
        $adminTheme = $config->defaultAdminTheme;
        $dbHost = $config->dbHost;
        $dbName = $config->dbName;
        $dbUser = $config->dbUser;
        $dbPass = $config->dbPass;
        $dbPort = $config->dbPort;

        $prepended = trim($config->prependTemplateFile);
        $appended = trim($config->appendTemplateFile);
        $prependedTemplateFile = $prepended != '' ? $prepended : $none;
        $appendedTemplateFile = $appended != '' ? $appended : $none;

        $installPath = getcwd();

        $status = [
            ['Version', $version],
            ['Admin URL', $adminUrl],
            ['Advanced mode', $advancedMode],
            ['Debug mode', $debugMode],
            ['Timezone', $timezone],
            ['HTTP hosts', $hosts],
            ['Admin theme', $adminTheme],
            ['Prepended template file', $prependedTemplateFile],
            ['Appended template file', $appendedTemplateFile],
            ['Database host', $dbHost],
            ['Database name', $dbName],
            ['Database user', $dbUser],
            ['Database password', $dbPass],
            ['Database port', $dbPort],
            ['Installation path', $installPath]
        ];

        return $status;
    }

    /**
     * @return array
     */
    protected function getWsStatus()
    {

        $version = $this->getApplication()->getVersion();

        $documentation = 'http://wireshell.pw';

        $status = [
            ['Version', $version],
            ['Documentation', $documentation],
            ['License', "MIT"]
        ];

        return $status;
    }

    protected function buildTable(OutputInterface $output, $statusArray, $label)
    {
        $headers = [Tools::tint($label, Tools::kTintComment)];

        $tablePW = new Table($output);
        $tablePW
            ->setStyle('borderless')
            ->setHeaders($headers)
            ->setRows($statusArray);

        return $tablePW;
    }

    /**
     * @return string
     */
    protected function getAdminUrl()
    {
        $admin = \ProcessWire\wire('pages')->get('template=admin');
        $url = \ProcessWire\wire('config')->urls->admin;

        if (!($admin instanceof \ProcessWire\NullPage) && isset($admin->httpUrl)) {
            $url = $admin->httpUrl;
        }

        return $url;
    }

    /**
     * @param OutputInterface $output
     * @param $tables
     */
    protected function renderTables(OutputInterface $output, $tables)
    {
        $output->writeln("\n");

        foreach ($tables as $table) {
            $table->render();
            $output->writeln("\n");
        }
    }


    /**
     * wrapper method for the Diagnose PHP submodule from @netcarver
     */
    protected function getDiagnosePHP()
    {
        $sub = new DiagnosePhp();
        $rows = $sub->GetDiagnostics();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [$row['title'], $row['value']];
        }

        return $result;
    }


    /**
     * wrapper method for the Diagnose Imagehandling submodule from @netcarver & @horst
     */
    protected function getDiagnoseImagehandling()
    {
        $sub = new DiagnoseImagehandling();
        $rows = $sub->GetDiagnostics();
        $result = [];

        foreach ($rows as $row) {
            $result[] = [$row['title'], $row['value']];
        }

        return $result;
    }

}




