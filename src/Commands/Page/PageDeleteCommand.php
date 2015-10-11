<?php namespace Wireshell\Commands\Page;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Wireshell\Helpers\PwUserTools;

/**
 * Class PageDeleteCommand
 *
 * Creating ProcessWire pages
 *
 * @package Wireshell
 * @author Tabea David <info@justonestep.de>
 */
class PageDeleteCommand extends PwUserTools
{

    /**
     * Configures the current command.
     */
    public function configure()
    {
        $this
            ->setName('page:delete')
            ->setAliases(['p:d'])
            ->setDescription('Deletes ProcessWire pages')
            ->addArgument('selector', InputArgument::REQUIRED)
            ->addOption('rm', null, InputOption::VALUE_NONE, 'Force deletion, do not move page to trash');
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

        foreach (explode(',', $input->getArgument('selector')) as $selector) {
            $select = (is_numeric($selector)) ? (int)$selector : "/{$selector}/";
            $trashPage = $pages->get($select);

            if ($trashPage instanceof \NullPage) {
                $output->writeln("<error>Page `{$selector}` doesn't exist.</error>");
            } else {
                $delete = $input->getOption('rm') === true ? true : false;

                if ($delete === true) {
                    $pages->delete($pages->get($select), true);
                    $output->writeln("<info>Page `{$selector}` was successfully deleted.</info>");
                } else {
                    $pages->trash($pages->get($select), true);
                    $output->writeln("<info>Page `{$selector}` has been successfully moved to the trash.</info>");
                }
            }
        }

    }

}
