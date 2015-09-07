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
 * Class FieldTagCommand
 *
 * Tags a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldTagCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('field:tag')
            ->setDescription('Tags fields')
            ->addArgument('field', InputArgument::REQUIRED, 'Comma separated list.')
            ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Tag name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        parent::bootstrapProcessWire($output);

        $inputFields = explode(',', $input->getArgument('field'));
        $fields = wire('fields');

        if (!empty($input->getOption('tag'))) {
            $tag = $input->getOption('tag');
        } else {
            $output->writeln("\n<error> Please provide a tag name (`--tag=tagname`).</error>");
            return;
        }


        foreach ($inputFields as $field) {
            $fieldToTag = $fields->get($field);

            if (is_null($fieldToTag)) {
                $output->writeln("\n<error> > Field '{$field}' does not exist!</error>");
                continue;
            }

            try {
                $fieldToTag->tags = $tag;
                $fieldToTag->save();
                $output->writeln("\n<info> > Field '{$field}' edited successfully!</info>");
            } catch (\WireException $e) {
                $output->writeln("\n<error> > {$e->getMessage()}</error>");
            }
        }
    }

}

