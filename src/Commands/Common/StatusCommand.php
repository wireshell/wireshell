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

        $pwStatus = [
            ['Version', wire('config')->version],
            ['Admin URL', $this->getAdminUrl()],
            ['Advanced mode', wire('config')->advanced ? 'On' : 'Off'],
            ['Debug mode', wire('config')->debug ? '<error>On</error>' : '<info>Off</info>'],
            ['Timezone', wire('config')->timezone],
            ['HTTP hosts', implode(", ", wire('config')->httpHosts)],
            ['Admin theme', wire('config')->defaultAdminTheme],
            ['Database host', wire('config')->dbHost],
            ['Database name', wire('config')->dbName],
            ['Database user', wire('config')->dbUser],
            ['Database port', wire('config')->dbPort],
            ['Installation path', getcwd()]
        ];

        $envStatus = [
            ['PHP version', PHP_VERSION],
            ['PHP binary', PHP_BINDIR],
            ['MySQL version', $this->getMySQLVersion()]
        ];

        $wsStatus = [
            ['Version',  $this->getApplication()->getVersion()],
            ['Forum', 'https://processwire.com/talk/topic/9494-wireshell-an-extendable-processwire-command-line-interface/']
        ];

        $tablePW = $this->buildTable($output, $pwStatus, 'ProcessWire');

        $tableEnv = $this->buildTable($output, $envStatus, 'Environment');

        $tableWs = $this->buildTable($output, $wsStatus, 'wireshell');

        $this->renderTables($output, $tablePW, $tableEnv, $tableWs);

    }

    protected function buildTable(OutputInterface $output, $statusArray, $label)
    {

        $tablePW = new Table($output);
        $tablePW
            ->setStyle('borderless')
            ->setHeaders(["<comment>{$label}</comment>"])
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
     * @return string
     */
    function getMySQLVersion() {

        ob_start();
        phpinfo(INFO_MODULES);
        $info = ob_get_contents();
        ob_end_clean();
        $info = stristr($info, 'Client API version');
        preg_match('/[1-9].[0-9].[1-9][0-9]/', $info, $match);
        $gd = $match[0];

        return $gd;

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

}
