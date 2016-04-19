<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwModuleTools;

/**
 * Class ModuleDownloadCommand
 *
 * Downloads module(s)
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David <td@kf-interactive.com>
 */
class ModuleDownloadCommand extends PwModuleTools {

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('module:download')
            ->setDescription('Downloads ProcessWire module(s).')
            ->addArgument('modules', InputOption::VALUE_REQUIRED,
                'Provide one or more module class name, comma separated: Foo,Bar')
            ->addOption('github', null, InputOption::VALUE_OPTIONAL,
                'Download module via github. Use this option if the module isn\'t added to the ProcessWire module directory.')
            ->addOption('branch', null, InputOption::VALUE_OPTIONAL,
                'Optional. Define specific branch to download from.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);
        $this->output = $output;

        $modules = explode(",", $input->getArgument('modules'));

        if (!ini_get('allow_url_fopen')) {
            // check if we have the rights to download files from other domains
            // using copy or file_get_contents
            $this->output->writeln('The php config `allow_url_fopen` is disabled on the server. Enable it then try again.');
        } elseif (!is_writable(\ProcessWire\wire('config')->paths->siteModules)) {
            // check if module directory is writeable
            $this->output->writeln('Make sure your /site/modules directory is writeable by PHP.');
        } else {
            $github = $input->getOption('github');
            $branch =$input->getOption('branch');

            if ($github) {
                $branch = ($branch) ? $input->getOption('branch') : 'master';
                $github = 'https://github.com/' . $input->getOption('github') . '/archive/' . $branch . '.zip';
            }

            foreach ($modules as $module) {
                $this->output->writeln("\n<bg=yellow;options=bold> - Module '$module': </bg=yellow;options=bold>\n");

                if ($this->checkIfModuleExists($module)) {
                    $this->output->writeln(" <error> Module '{$module}' already exists! </error>\n");
                } else {
                    // reset PW modules cache
                    \ProcessWire\wire('modules')->resetCache();
                    if (isset($github)) {
                        $this->downloadModuleByUrl($module, $github);
                    } else {
                        $this->downloadModuleIfExists($module);
                    }
                }
            }

            \ProcessWire\wire('modules')->resetCache();
        }
    }

    /**
     * check if a module exists in processwire module directory
     *
     * @param string $module
     */
    public function downloadModuleIfExists($module) {
        $contents = file_get_contents(
            \ProcessWire\wire('config')->moduleServiceURL .
            '?apikey=' . \ProcessWire\wire('config')->moduleServiceKey .
            '&limit=1' . '&class_name=' . $module
        );

        $result = json_decode($contents);

        if ($result->status === 'error') {
            $this->output->writeln(" <error> A module with the class '$module' does not exist. </error>\n");
        } else {
            // yeah! module exists
            $item = $result->items[0];
            $moduleUrl = $item->project_url . '/archive/master.zip';
            $this->downloadModuleByUrl($module, $moduleUrl);
        }
    }

    /**
     * download module
     *
     * @param string $module
     */
    public function downloadModuleByUrl($module, $moduleUrl) {
        try {
            $this
                ->downloadModule($moduleUrl, $module, $this->output)
                ->extractModule($module, $this->output)
                ->cleanUpTmp($module, $this->output);
        } catch (Exception $e) {
            $this->output->writeln(" <error> Could not download module $module. Please try again later. </error>\n");
        }
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
