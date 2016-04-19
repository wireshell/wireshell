<?php namespace Wireshell\Commands\Field;

use ProcessWire\Field;
use ProcessWire\Fieldgroup;
use ProcessWire\InputfieldText;
use ProcessWire\InputfieldWrapper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;

/**
 * Class FieldDeleteCommand
 *
 * Deletes a field
 *
 * @package Wireshell
 * @author Tabea David
 */
class FieldDeleteCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('field:delete')
            ->setDescription('Deletes fields')
            ->addArgument('field', InputArgument::REQUIRED, 'Comma separated list.');
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
        $fields = \ProcessWire\wire('fields');

        foreach ($inputFields as $field) {
            $fieldToDelete = $fields->get($field);

            if (is_null($fieldToDelete)) {
                $output->writeln("\n<error> > Field '{$field}' does not exist!</error>");
                continue;
            }

            try {
                $fields->delete($fieldToDelete);
                $output->writeln("\n<info> > Field '{$field}' deleted successfully!</info>");
            } catch (\WireException $e) {
                $output->writeln("\n<error> > {$e->getMessage()}</error>");
            }
        }
    }

}
