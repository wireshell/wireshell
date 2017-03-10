<?php namespace Wireshell\Commands\Backup;

use ProcessWire\Page;
use ProcessWire\Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\Helpers\PwConnector;
use Wireshell\Helpers\WsTools as Tools;

/**
 * Class Images Backup Command
 *
 * @package Wireshell
 * @author Tabea David
 */
class BackupImagesCommand extends PwConnector {

  /**
   * Configures the current command.
   */
  protected function configure() {
    $this
      ->setName('backup:images')
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
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->init($input, $output);
    $tools = new Tools($output);
    $tools->writeBlockCommand($this->getName());

    if ($input->getOption('target')) {
      $path = \ProcessWire\wire('config')->paths->root . $input->getOption('target');
    } else {
      $path = \ProcessWire\wire('config')->paths->root . 'dump-' . date('Y-m-d-H-i-s');
    }

    if (!file_exists($path)) mkdir($path);

    $pages = \ProcessWire\wire('pages');
    if ($input->getOption('selector')) {
      $pages = \ProcessWire\wire('pages')->find($input->getOption('selector'));
    } else {
      $pages = \ProcessWire\wire('pages')->find("has_parent!=2,id!=2|7,status<" . Page::statusTrash . ",include=all");
    }

    $fieldname = ($input->getOption('field')) ? $input->getOption('field') : 'images';

    if ($pages) {
      $total = 0;
      $imgNames = array();
      foreach ($pages as $page) {
        if ($page->$fieldname) {
          foreach ($page->$fieldname as $img) {
            if (!in_array($img->name, $imgNames)) {
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
              $imgNames[] = $img->name;
            }
          }
        }
      }
    }

    if ($total > 0) {
      $tools->writeSuccess("Dumped {$total} images into `{$path}` successfully.");
    } else {
      $tools->writeError("No images found. Recheck your options.");
    }
  }

}
