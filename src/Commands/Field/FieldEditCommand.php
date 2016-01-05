<?php namespace Wireshell\Commands\Field;

use Field;
use Fieldgroup;
use InputfieldText;
use InputfieldWrapper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;

/**
 * Class FieldEditCommand
 *
 * Clones a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldEditCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('field:edit')
            ->setDescription('Edit a field')
            ->addArgument('field', InputArgument::REQUIRED)
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Change field name')
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Change label');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $field = $input->getArgument('field');
        $fields = wire('fields');
        $fieldToEdit = $fields->get($field);

        if (is_null($fieldToEdit)) {
            $output->writeln("<error>Field '{$field}' does not exist!</error>");
            return;
        }

        if ($input->getOption('name')) $fieldToEdit->name = $input->getOption('name');
        if ($input->getOption('label')) $fieldToEdit->label = ucfirst($input->getOption('label'));

        $fieldToEdit->save();

        $output->writeln("<info>Field '{$field}' edited successfully!</info>");
    }

}

