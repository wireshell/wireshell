<?php namespace Wireshell\Commands\Template;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;

/**
 * Class TemplateFieldsCommand
 *
 * Assign given fields to a given template
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class TemplateFieldsCommand extends PwConnector {

    /**
     * Configures the current command.
     */
    protected function configure() {
        $this
            ->setName('template:fields')
            ->setDescription('Assign given fields to a given template')
            ->addArgument('template', InputArgument::REQUIRED, 'Name of the template')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Supply fields to assign to template');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        parent::bootstrapProcessWire($output);

        $template = \ProcessWire\wire('templates')->get($input->getArgument('template'));
        $fields = explode(",", $input->getOption('fields'));

        if (!$input->getOption('fields')) {
            $output->writeln("<error>Please supply field(s) via --fields!</error>");
            exit(1);
        }

        if (!\ProcessWire\wire('templates')->get($template)) {
            $output->writeln("<error>Template {$template} cannot be found!</error>");
            exit(1);
        }

        $this->assignFieldsToTemplate($fields, $template, $output);

        $output->writeln("<info>Field(s) added to '{$template}' successfully!</info>");
    }

    /**
     * @param $fields
     * @param $template
     * @param $output
     */
    private function assignFieldsToTemplate($fields, $template, $output) {
        foreach ($fields as $field) {
            $this->checkIfFieldExists($field, $output);

            $template->fields->add($field);
            $template->fields->save();
        }

        return $template;
    }

    /**
     * @param $field
     * @param $output
     * @return bool
     */
    private function checkIfFieldExists($field, $output) {
        if (!\ProcessWire\wire("fields")->get("{$field}")) {
            $output->writeln("<error>Field '{$field}' does not exist!</error>");

            return false;
        }
    }
}
