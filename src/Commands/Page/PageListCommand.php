<?php namespace Wireshell\Commands\Page;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wireshell\Helpers\PwUserTools;

/**
 * Class PageListCommand
 *
 * Creating ProcessWire pages
 *
 * @package Wireshell
 * @author Tabea David <info@justonestep.de>
 */
class PageListCommand extends PwUserTools
{

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Integer
     */
    private $indent;

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('page:list')
            ->setAliases(['p:l'])
            ->setDescription('Lists ProcessWire pages')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Start Page')
            ->addOption('level', null, InputOption::VALUE_REQUIRED, 'How many levels to show');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $pages = wire('pages');
        $this->output = $output;
        $this->indent = 0;
        $level = ((int)$input->getOption('level')) ? (int)$input->getOption('level') : 0;

        $start = '/';
        // start page submitted and existing?
        if (
          $input->getOption('start') &&
          !wire('pages')->get('/' . $input->getOption('start') . '/') instanceof \NullPage
        ) {
            $start = '/' . $input->getOption('start') . '/';
        }

        $this->listPages($pages->get($start), $level);
    }

    /**
     * @param Page $page
     * @param int $level
     */
    public function listPages($page, $level)
    {
        $indent = 4;
        $title = $page->title . ' { ' . $page->id . ', ' . $page->template . ' }';
        switch ($this->indent) {
        case 0:
            $out = '|-- ' . $title;
            break;
        default:
            $i = $this->indent - $indent / 2;
            $j = $indent / 2 + 1;
            $out = '|' . str_pad(' ' . $title, strlen($title) + $j, '-', STR_PAD_LEFT);
            $out = '|' . str_pad($out, strlen($out) + $i, ' ', STR_PAD_LEFT);
        }

        $this->output->writeln($out);

        if ($page->numChildren) {
            $this->indent = $this->indent + $indent;
            foreach ($page->children as $child) {
                if ($level === 0 || ($level != 0 && $level >= ($this->indent / $indent))) {
                    $this->listPages($child, $level);
                }
            }
            $this->indent = $this->indent - $indent;
        }
    }

}
