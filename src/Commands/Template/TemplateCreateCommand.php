<?php namespace Wireshell\Commands\Template;

use ProcessWire\Template;
use ProcessWire\Fieldgroup;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;

/**
 * Class TemplateCreateCommand
 *
 * Creating ProcessWire templates
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */
class TemplateCreateCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('template:create')
            ->setDescription('Creates a ProcessWire template')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('fields', null, InputOption::VALUE_REQUIRED,
                'Attach existing fields to template, comma separated')
            ->addOption('nofile', null, InputOption::VALUE_NONE, 'Prevents template file creation');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $name = $input->getArgument('name');
        $fields = explode(",", $input->getOption('fields'));


        if (\ProcessWire\wire("templates")->get("{$name}")) {

            $output->writeln("<error>Template '{$name}' already exists!</error>");
            exit(1);
        }

        $fieldgroup = new Fieldgroup();
        $fieldgroup->name = $name;
        $fieldgroup->add("title");

        if ($input->getOption('fields')) {
            foreach ($fields as $field) {

                $this->checkIfFieldExists($field, $output);
                $fieldgroup->add($field);
            }
        }

        $fieldgroup->save();

        $template = new Template();
        $template->name = $name;
        $template->fieldgroup = $fieldgroup;
        $template->save();

        if (!$input->getOption('nofile')) {
            $this->createTemplateFile($name);
        }

        $output->writeln("<info>Template '{$name}' created successfully!</info>");

    }

    /**
     * @param $name
     */
    private function createTemplateFile($name)
    {
        if ($templateFile = fopen('site/templates/' . $name . '.php', 'w')) {
            $content = "<?php namespace ProcessWire; \n/* Template {$name} */\n";

            fwrite($templateFile, $content, 1024);
            fclose($templateFile);
        }
    }

    /**
     * @param $field
     * @param $output
     * @return bool
     */
    private function checkIfFieldExists($field, $output)
    {
        if (!\ProcessWire\wire("fields")->get("{$field}")) {
            $output->writeln("<comment>Field '{$field}' does not exist!</comment>");

            return false;
        }
    }

}
