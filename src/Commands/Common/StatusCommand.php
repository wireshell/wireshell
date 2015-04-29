<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\ProcessDiagnostics\DiagnoseImagehandling;
use Wireshell\Helpers\ProcessDiagnostics\DiagnosePhp;
use Wireshell\Helpers\PwConnector;


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


        if ($input->getOption('php')) 
        {
            $phpStatus = $this->getDiagnosePHP();
            $tables[] = $this->buildTable($output, $phpStatus, 'PHP Diagnostics');
        }

        if ($input->getOption('image')) 
        {
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

        $version = wire('config')->version;
        
        $adminUrl = $this->getAdminUrl();

        $advancedMode = wire('config')->advanced ? $this->tint('On', 'error') : $this->tint('Off','info');

        $debugMode = wire('config')->debug ? $this->tint('On', 'error') : $this->tint('Off', 'info');

        $timezone = wire('config')->timezone;

        $hosts = implode(", ", wire('config')->httpHosts);

        $adminTheme = wire('config')->defaultAdminTheme;

        $dbHost = wire('config')->dbHost;
        $dbName = wire('config')->dbName;

        $dbUser = wire('config')->dbUser;
        $dbPass = wire('config')->dbPass;
        $dbPort = wire('config')->dbPort;

        $prepended = trim(wire('config')->prependTemplateFile);

        $appended = trim(wire('config')->appendTemplateFile);

        $prependedTemplateFile = $prepended != '' ? $prepended : $this->tint('None', 'info');

        $appendedTemplateFile = $appended != '' ? $appended : $this->tint('None', 'info');


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
            ['Version',  $version],
            ['Documentation', $documentation],
            ['License', "MIT"]
        ];

        return $status;
    }

    protected function buildTable(OutputInterface $output, $statusArray, $label)
    {
        $headers = [$this->tint($label, 'comment')];

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
        $admin = wire('pages')->get('template=admin');

        $url = wire('config')->urls->admin;

        if (!($admin instanceof \NullPage) && isset($admin->httpUrl)) 
        {
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

        foreach ($tables as $table) 
        {
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

        foreach ($rows as $row) 
        {
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

        foreach ($rows as $row) 
        {
            $result[] = [$row['title'], $row['value']];
        }

        return $result;
    }

    /**
    * Simple method for coloring output
    * @param $string
    * @param $type
    * @return tinted string
    */
    protected function tint($string, $type) 
    {
        return "<{$type}>{$string}</{$type}>";
    }

}




