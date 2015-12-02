<?php namespace Wireshell\Commands\Field;

use Field;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\PwTools;

/**
 * Class FieldCreateCommand
 *
 * Creates a field
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Tabea David
 */
class FieldCreateCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('field:create')
            ->setDescription('Creates a field')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Label')
            ->addOption('desc', null, InputOption::VALUE_REQUIRED, 'Description')
            ->addOption('tag', null, InputOption::VALUE_REQUIRED, 'Tag')
            ->addOption('type', null, InputOption::VALUE_REQUIRED,
                'Type of field: text|textarea|email|datetime|checkbox|file|float|image|integer|page|url');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $name = $input->getArgument('name');
        $label = $input->getOption('label') !== "" ? $input->getOption('label') : $name;

        $type = PwTools::getProperFieldtypeName($input->getOption('type'));
        $check = $this->checkFieltype($type);

        if ($check === true) {
            $field = new Field();
            $field->type = wire('modules')->get($type);
            $field->name = $name;
            $field->label = $label;
            $field->description = $input->getOption('desc');
            if ($input->getOption('tag')) $field->tags = $input->getOption('tag');
            $field->save();

            $output->writeln("<info>Field '{$name}' ($type) created successfully!</info>");
        } else {
            $output->writeln("<error>This fieldtype `$type` does not exists.</error>");
        }
    }

    /**
     * @param $type
     */
    protected function checkFieltype($type) {
        // get available fieldtypes
        $fieldtypes = array();
        foreach (wire('modules') as $module) {
            if (preg_match('/^Fieldtype/', $module->name)) {
                $fieldtypes[] = $module->name;
            }
        }

        // check whether fieldtype exists
        return in_array($type, $fieldtypes) ? true : false;
    }
}
