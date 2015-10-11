<?php namespace Wireshell\Commands\Backup;

use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;

/**
 * Class Images Backup Command
 *
 * @package Wireshell
 * @author Tabea David
 */
class BackupImagesCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('backup:images')
            ->setAliases(['b:i'])
            ->setDescription('Performs images backup')
            ->addOption('selector', null, InputOption::VALUE_REQUIRED, 'Provide a pages selector')
            ->addOption('field', null, InputOption::VALUE_REQUIRED, 'Provide a image field name')
            ->addOption('target', null, InputOption::VALUE_REQUIRED, 'Provide a destination folder');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::bootstrapProcessWire($output);

        if ($input->getOption('target')) {
            $path = wire('config')->paths->root . $input->getOption('target');
        } else {
            $path = wire('config')->paths->root . 'dump-' . date('Y-m-d-H-i-s');
        }

        if (!file_exists($path)) mkdir($path);

        $pages = wire('pages');
        if ($input->getOption('selector')) {
            $pages = wire('pages')->find($input->getOption('selector'));
        } else {
            $pages = wire('pages')->find("has_parent!=2,id!=2|7,status<" . \Page::statusTrash . ",include=all");
        }

        $fieldname = ($input->getOption('field')) ? $input->getOption('field') : 'images';

        if ($pages) {
            $total = 0;
            foreach ($pages as $page) {
                if ($page->$fieldname) {
                    foreach ($page->$fieldname as $img) {
                        if (function_exists('copy')) {
                            // php 5.5+
                            copy($img->filename, $path . '/' . $img->name);
                        } else {
                            $content = file_get_contents($img->filename);
                            $fp = fopen($path, "w");
                            fwrite($fp, $content);
                            fclose($fp);
                        }
                        $total++;
                    }
                }
            }
        }

        if ($total > 0) {
            $output->writeln("<info>Dumped {$total} images into {$path} successfully.</info>");
        } else {
            $output->writeln("<error>No images found. Recheck your options.</error>");
        }
    }

}
