<?php namespace Wireshell\Commands\Page;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wireshell\Helpers\PwUserTools;

/**
 * Class PageCreateCommand
 *
 * Creating ProcessWire pages
 *
 * @package Wireshell
 * @author Tabea David
 */
class PageCreateCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('page:create')
            ->setAliases(['p:c'])
            ->setDescription('Creates a ProcessWire page')
            ->addArgument('name', InputArgument::REQUIRED)
            ->addOption('template', null, InputOption::VALUE_REQUIRED, 'Template')
            ->addOption('parent', null, InputOption::VALUE_REQUIRED, 'Parent Page')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, 'Title');
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
        $pages = wire('pages');
        $template = $this->getTemplate($input, $output);
        $parent = $this->getParent($input, $output);

        foreach ($names as $name) {
          $sanitizedName = wire('sanitizer')->pageName($name);
            if (!wire('pages')->get($parent . $sanitizedName . '/') instanceof \NullPage) {
                $output->writeln("<error>The page name  '{$name}' is already taken.</error>");
                continue;
            }

            $p = new \Page();
            $p->template = $template;
            $p->parent = wire('pages')->get($parent);
            $p->name = $sanitizedName; // give it a name used in the url for the page
            $p->title = $input->getOption('title') ? $input->getOption('title') : $name;
            $p->save();
        }
    }


    /**
     * get template
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function getTemplate($input, $output) {
        $templateName = $input->getOption('template');
        if (!$templateName) {
            $helper = $this->getHelper('question');
            $question = new Question('Please enter the template : ', 'template');
            $templateName = $helper->ask($input, $output, $question);
        }

        $template = wire('templates')->get($templateName);
        if (empty($template)) {
            $output->writeln("<error>Template '{$templateName}' doesn't exist!</error>");
            exit(1);
        }

        if (!empty($template->noParents)) {
            $output->writeln("<error>Template '{$templateName}' is not allowed to be used for new pages!</error>");
            exit(1);
        }

        $this->template = $template;
        return $templateName;
    }

    /**
     * get parent
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    private function getParent($input, $output) {
        $parent = '/';

        // parent page submitted and existing?
        if (
          $input->getOption('parent') &&
          !wire('pages')->get('/' . $input->getOption('parent') . '/') instanceof \NullPage
        ) {
            $parent = '/' . $input->getOption('parent') . '/';
        }

        $parentPage = wire('pages')->get($parent);
        $parentTemplate = $parentPage->template;

        // may pages using this template have children?
        if (!empty($parentTemplate->noChildren)) {
            $output->writeln("<error>The parent page '{$parent}' is not allowed to have children!</error>");
            exit(1);
        }

        // allowed template(s) for parents
        if (
          is_array($this->template->parentTemplates)
          && !empty($this->template->parentTemplates)
          && !in_array($parentTemplate->id, $this->template->parentTemplates)
        ) {
            $output->writeln("<error>The parent page '{$parent}' is not allowed to be parent for this template!</error>");
            exit(1);
        }

        // allowed template(s) for children
        if (
          is_array($parentTemplate->childTemplates)
          && !empty($parentTemplate->childTemplates)
          && !in_array($this->template->id, $parentTemplate->childTemplates)
        ) {
            $output->writeln("<error>This template '{$this->template}' is not allowed to be children of template '{$parentTemplate}'!</error>");
            exit(1);
        }

        return $parent;
    }

}

