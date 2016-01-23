<?php namespace Wireshell\Commands\Template;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;

/**
 * Class TemplateDeleteCommand
 *
 * Deletes ProcessWire templates
 *
 * @package Wireshell
 * @author Tabea David
 */
class TemplateDeleteCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('template:delete')
            ->setDescription('Deletes ProcessWire template(s)')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('nofile', null, InputOption::VALUE_NONE, 'Prevents template file deletion');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $names = explode(',', $input->getArgument('name'));
        $templates = wire('templates');
        $fieldgroups = wire('fieldgroups');

        foreach ($names as $name) {
            $template = $templates->get($name);
            if ($template->id) {
                // try to delete depending file?
                if (!$input->getOption('nofile') && file_exists($template->filename)) {
                    unlink($template->filename);
                }

                $template->flags = \Template::flagSystemOverride;
                $template->flags = 0; // all flags now removed, can be deleted
                $templates->delete($template);

                // delete depending fieldgroups
                $fg = $fieldgroups->get($name);
                if ($fg->id) $fieldgroups->delete($fg);
                $output->writeln("<info>Template '{$name}' deleted successfully!</info>");
            } else {
                $output->writeln("<error>Template '{$name}' doesn't exist!</error>");
            }
        }
    }

}
