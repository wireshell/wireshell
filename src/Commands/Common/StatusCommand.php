<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwConnector;

/**
 * Class StatusCommand
 *
 * Returns versions, paths and environment info
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Camilo Castro
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
            ->setDescription('Returns versions, paths and environment info');
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

        $envStatus = $this->getEnvStatus();

        $wsStatus = $this->getWsStatus();

        $tablePW = $this->buildTable($output, $pwStatus, 'ProcessWire');

        $tableEnv = $this->buildTable($output, $envStatus, 'Environment');

        $tableWs = $this->buildTable($output, $wsStatus, 'wireshell');

        $this->renderTables($output, $tablePW, $tableEnv, $tableWs);

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
    protected function getEnvStatus()
    {

        $status = [
            ['PHP version', PHP_VERSION],
            ['PHP binary', PHP_BINDIR],
            ['MySQL version', $this->getMySQLVersion()]
        ];

        return $status;
    }

    /**
     * @return array
     */
    protected function getWsStatus()
    {
        $version = $this->getApplication()->getVersion();

        $forumLink = 'https://processwire.com/talk/topic/9494-wireshell-an-extendable-processwire-command-line-interface/';

        $githubLink = 'https://github.com/marcus-herrmann/wireshell';

        $status = [
            ['Version',  $version],
            ['Forum', $forumLink],
            ['Source code', $githubLink],
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

        if (!($admin instanceof \NullPage) && isset($admin->httpUrl)) {

            $url = $admin->httpUrl;
        }

        return $url;
    }

    /**
     * @return mixed
     * @info http://stackoverflow.com/questions/10414530/how-to-get-server-mysql-version-in-php-without-connecting
     */
    function getMySQLVersion() {

        $output = shell_exec('mysql -V');
        preg_match('@[0-9]+\.[0-9]+\.[0-9]+@', $output, $version);
        return $version[0];

    }

    /**
     * @param OutputInterface $output
     * @param $tablePW
     * @param $tableEnv
     * @param $tableWs
     */
    protected function renderTables(OutputInterface $output, $tablePW, $tableEnv, $tableWs)
    {
        $tablePW->render();
        $output->writeln("\n");
        $tableEnv->render();
        $output->writeln("\n");
        $tableWs->render();
    }

    /**
    * @param $string
    * @param $type
    * @return tinted string
    */
    protected function tint($string, $type) 
    {
        return "<{$type}>{$string}</{$type}>";
    }

}