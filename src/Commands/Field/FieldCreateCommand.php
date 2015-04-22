<?php namespace Wireshell\Commands\Field;

use Field;
use Fieldgroup;
use InputfieldText;
use InputfieldWrapper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwConnector;

/**
 * Class FieldCreateCommand
 *
 * Creates a field
 *
 * @package Wireshell
 * @author Marcus Herrmann
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
            ->setAliases(['f:c'])
            ->setDescription('Creates a field')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('label', null, InputOption::VALUE_REQUIRED, 'Label')
            ->addOption('desc', null, InputOption::VALUE_REQUIRED, 'Description')
            ->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type of field: text|textarea|email|datetime|checkbox|file|float|image|integer|page|url');
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

        $type = $this->getProperFieldtypeName($input->getOption('type'));

        $field = new Field();
        $field->type  = wire('modules')->get($type);
        $field->name = $name;
        $field->label = $label;
        $field->description = $input->getOption('desc');
        $field->save();

        if (!wire('fieldgroups')->get('wireshell')) {

            $fieldGroup = new Fieldgroup();
            $fieldGroup->name = 'wireshell';
        } else {

            $fieldGroup = wire('fieldgroups')->get('wireshell');
        }

        $fieldGroup->add($field);
        $fieldGroup->save();

        $output->writeln("<info>Field '{$name}' ($type) created successfully!</info>");


    }

    /**
     * @param $suppliedType
     * @return string
     */
    protected function getProperFieldtypeName($suppliedType)
    {
        switch ($suppliedType) {
            case "text":
                $type = 'FieldtypeText';
                break;
            case "textarea":
                $type = 'FieldtypeTextarea';
                break;
            case "email":
                $type = 'FieldtypeEmail';
                break;
            case "datetime":
                $type = 'FieldtypeDatetime';
                break;
            case "checkbox":
                $type = 'FieldtypeCheckbox';
                break;
            case "file":
                $type = 'FieldtypeFile';
                break;
            case "float":
                $type = 'FieldtypeFloat';
                break;
            case "image":
                $type = 'FieldtypeImage';
                break;
            case "integer":
                $type = 'FieldtypeInteger';
                break;
            case "page":
                $type = 'FieldtypePage';
                break;
            case "url":
                $type = 'FieldtypeUrl';
                break;
            default:
                $type = 'FieldtypeText';
        }

        return $type;
    }
}
