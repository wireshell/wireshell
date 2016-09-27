<?php namespace Wireshell\Commands\Page;

use ProcessWire\Page;
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
     * @var String
     */
    private $select;


    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('page:list')
            ->setDescription('Lists ProcessWire pages')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Start Page')
            ->addOption('level', null, InputOption::VALUE_REQUIRED, 'How many levels to show')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Get a list of all pages (recursiv) without admin-pages')
            ->addOption('trash', null, InputOption::VALUE_NONE, 'Get a list of trashed pages (recursiv) without admin-pages');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        $pages = \ProcessWire\wire('pages');
        $this->output = $output;
        $this->indent = 0;

        $level = ((int)$input->getOption('level')) ? (int)$input->getOption('level') : 0;
        $start = $this->getStartPage($input);
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
            foreach ($page->children($this->select) as $child) {
                if ($level === 0 || ($level != 0 && $level >= ($this->indent / $indent))) {
                    $this->listPages($child, $level);
                }
            }
            $this->indent = $this->indent - $indent;
        }
    }

    /**
     * @param InputInterface $input
     * @param $start
     */
    private function setSelector($input, $start) {
        $config = \ProcessWire\wire('config');
        $inclAll = $input->getOption('all') === true ? true : false;
        $inclTrashed = $input->getOption('trash') === true ? true : false;

        if ($inclAll === true && $inclTrashed === true) {
            $select = "has_parent!={$config->adminRootPageID},";
            $select .= "id!={$config->adminRootPageID},";
            $select .= "include=all";
        } elseif ($inclAll === true) {
            $select = "has_parent!={$config->adminRootPageID},";
            $select .= "id!={$config->adminRootPageID}|{$config->trashPageID},";
            $select .= "status<" . Page::statusTrash . ",include=all";
        } elseif ($inclTrashed === true) {
            $select = "include=all";
            $start = $config->trashPageID;
        } else {
            $select = '';
        }

        $this->select = $select;
        return $start;
    }

    /**
     * @param InputInterface $input
     */
    private function getStartPage($input) {
        $start = '/';
        // start page submitted and existing?
        if ($input->getOption('start')) {
            $startPage = $input->getOption('start');
            $startPage = (is_numeric($startPage)) ? (int)$startPage : "/{$startPage}/";

            if (!\ProcessWire\wire('pages')->get($startPage) instanceof \ProcessWire\NullPage) {
                $start = $startPage;
            } else {
                $this->output->writeln("<error>Startpage `{$startPage}` could not be found, using root page instead.</error>\n");
            }
        }

        return $this->setSelector($input, $start);
    }

}
