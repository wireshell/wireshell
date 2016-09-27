<?php namespace Wireshell\Commands\Template;

use ProcessWire\Template;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTables as WsTables;

/**
 * Class TemplateListCommand
 *
 * Creating ProcessWire templates
 *
 * @package Wireshell
 * @author Tabea David
 */
class TemplateListCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('template:list')
            ->setDescription('Lists ProcessWire templates')
            ->addOption('advanced', null, InputOption::VALUE_NONE, 'Show system templates. By default, system/internal templates are not shown.');
          ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $advanced = $input->getOption('advanced') ? true : false;

        $content = $this->getTemplateData($advanced);
        $headers = array('Template', 'Fields', 'Pages', 'Modified', 'Access');
        $tables = array(WsTables::buildTable($output, $content, $headers));
        WsTables::renderTables($output, $tables);
    }

    /**
     * get templates data
     *
     * @param boolean $advanced
     * @return array
     */
    private function getTemplateData($advanced) {
        $content = array();
        $advanced = \ProcessWire\wire('config')->advanced || $advanced;
        foreach (\ProcessWire\wire('templates') as $t) {
            if (!$advanced && ($t->flags & Template::flagSystem)) continue;

            $content[] = array(
                $t->name,
                count($t->fieldgroup),
                $t->getNumPages(),
                \ProcessWire\wireRelativeTimeStr($t->modified),
                $t->flags & Template::flagSystem ? '✖' : ''
            );
        }

        return $content;
    }

}
