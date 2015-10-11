<?php namespace Wireshell\Helpers;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

/**
 * Class WsTables
 *
 * Contains table methods that could be used in every command
 *
 * @package Wireshell
 * @author Tabea David
 */

abstract class WsTables
{

    /**
     * @param OutputInterface $output
     * @param array $content
     * @param array $headers
     */
    public static function buildTable(OutputInterface $output, $content, $headers)
    {
        $tablePW = new Table($output);
        $tablePW
            ->setStyle('borderless')
            ->setHeaders($headers)
            ->setRows($content);

        return $tablePW;
    }

    /**
     * @param OutputInterface $output
     * @param $tables
     */
    public static function renderTables(OutputInterface $output, $tables)
    {
        $output->writeln("\n");

        foreach ($tables as $table)
        {
            $table->render();
            $output->writeln("\n");
        }
    }
}
