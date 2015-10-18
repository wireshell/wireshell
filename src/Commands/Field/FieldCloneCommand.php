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
 * Class FieldCloneCommand
 *
 * Clones a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldCloneCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('field:clone')
            ->setDescription('Clones a field')
            ->addArgument('field', InputArgument::REQUIRED)
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Name');
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
        $fieldToClone = $fields->get($field);

        if (is_null($fieldToClone)) {
            $output->writeln("<error>Field '{$field}' does not exist!</error>");
            return;
        }

        $clone = $fields->clone($fieldToClone);

        if ($input->getOption('name')) {
            $clone->name = $input->getOption('name');
            $clone->label = ucfirst($input->getOption('name'));
            $clone->save();
        }

        $name = $input->getOption('name') !== "" ? $input->getOption('name') : $field . 'cloned';

        $output->writeln("<info>Field '{$field}' cloned successfully!</info>");
    }

}
