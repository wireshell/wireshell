<?php namespace Wireshell;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateTemplateCommand
 *
 * Creating ProcessWire templates
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */

class CreateTemplateCommand extends PwConnector
{

    use PwUserTrait;

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('create-template')
            ->setAliases(['ct', 'template'])
            ->setDescription('Creates a ProcessWire template')
            ->addArgument('name', InputArgument::REQUIRED)
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

        if (wire("templates")->get("{$name}")) {

            $output->writeln("<error>Template '{$name}' already exists!</error>");
            exit(1);
        }

        $fieldgroup = new \Fieldgroup();
        $fieldgroup->name = $name;
        $fieldgroup->add("title");
        $fieldgroup->save();

        $template = new \Template();
        $template->name = $name;
        $template->fieldgroup = $fieldgroup;
        $template->save();

        if (!$input->getOption('nofile')) $this->createTemplateFile($name);

        $output->writeln("<info>Template '{$name}' created successfully!</info>");

    }

    private function createTemplateFile($name)
    {
        if ($templateFile = fopen('site/templates/' . $name . '.php', 'w')) {
            $content = "<?php \n/* Template {$name} */\n";

            fwrite($templateFile, $content, 1024);
            fclose($templateFile);
        }
    }

}
