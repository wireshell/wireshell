<?php namespace Wireshell\Commands\Module;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Wireshell\Helpers\PwModuleTools;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class ModuleUpgradeCommand
 *
 * Upgrade module(s)
 *
 * @package Wireshell
 * @author Tabea David
 */
class ModuleUpgradeCommand extends PwModuleTools {

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('module:upgrade')
            ->setDescription('Upgrades given module(s)')
            ->addArgument('modules', InputArgument::OPTIONAL,
              'Provide one or more module class name, comma separated: Foo,Bar')
            ->addOption('check', null, InputOption::VALUE_NONE, 'Just check for module upgrades.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);
        if (!\ProcessWire\wire('config')->moduleServiceKey) throw new \RuntimeException('No module service key was found.');

        $tools = new Tools();
        if ($input->getArgument('modules') && !$input->getOption('check')) {
            // upgrade specific modules
            $modules = explode(",", $input->getArgument('modules'));
            if ($modules) $this->upgradeModules($modules, $output);
        } else {
          \ProcessWire\wire('modules')->resetCache();
          if ($moduleVersions = parent::getModuleVersions(true, $output)) {
            $output->writeln($tools->tint('An upgrade is available for:', Tools::kTintInfo));
            foreach ($moduleVersions as $name => $info) $output->writeln("  - $name: {$info['local']} -> {$info['remote']}");

            // aks which module should be updated
            if (!$input->getOption('check')) {
                $helper = $this->getHelper('question');

                $question = new ChoiceQuestion(
                    $tools->getQuestion("\nPlease select which module(s) should be updated", 'None'),
                    array_merge(array('None'), array_keys($moduleVersions)),
                    '0'
                );

                $question->setMultiselect(true);
                $modules = $helper->ask($input, $output, $question);

                $selectedMessage = $tools->tint('You have selected:', Tools::kTintInfo);
                $selectedModules = $tools->tint(implode(', ', $modules), Tools::kTintComment);
                $output->writeln("\n$selectedMessage $selectedModules");

                // if not `None` was selected, update modules
                if (!in_array('None', $modules)) if ($modules) $this->upgradeModules($modules, $output);
            }
          } else {
              $output->writeln("<info>Your modules are up-to-date.</info>");
          }
        }
    }

    /**
     * Upgrade modules
     *
     * @param array $modules
     * @param OutputInterface $output
     */
    private function upgradeModules($modules, $output) {
        foreach ($modules as $module) {
            // check whether module exists/is installed
            if (!$this->checkIfModuleExists($module)) {
                $output->writeln("<error>Module `$module` does not exist.</error>");
                continue;
            }

            $moduleVersions = $this->getModuleVersions(true, $output);
            // all modules are up-to-date
            if ($info = $this->getModuleVersion(true, $output, $module)) {
                $output->writeln("<info>An upgrade for $module is available: {$info['remote']}</info>");
            } else {
                $output->writeln("<info>The module `$module` is up-to-date.</info>");
                continue;
            }

            // update url available?
            if (!$info['download_url']) {
                $output->writeln("<error>No download URL specified for module `$module`.</error>");
                continue;
            }

            // update module
            $destinationDir = \ProcessWire\wire('config')->paths->siteModules . $module . '/';
            \ProcessWire\wire('modules')->resetCache();
            $this->output = $output;

            try {
                $this
                    ->downloadModule($info['download_url'], $module, $output)
                    ->extractModule($module, $output)
                    ->cleanUpTmp($module, $output);
            } catch (Exception $e) {
                $this->output->writeln(" <error> Could not download module $module. Please try again later. </error>\n");
            }

            $output->writeln("<info>Module `$module` was updated successfully.</info>");
        }
    }
}
