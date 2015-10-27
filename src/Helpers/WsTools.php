<?php namespace Wireshell\Helpers;

/**
 * Class WsTools
 *
 * Contains common methods that could be used in every command
 *
 * @package Wireshell
 * @author Camilo Castro
 */

abstract class WsTools
{
    const kTintError = "error";
    const kTintInfo = "info";
    const kTintComment = "comment";

    /**
     * Simple method for coloring output
     * Possible Types: error, info, comment
     * @param $string
     * @param $type
     * @return tinted string
     */
    public static function tint($string, $type)
    {
        return "<{$type}>{$string}</{$type}>";
    }

    /**
     * Simple method for listing output
     * one column
     *
     * @param string $header
     * @param array $items
     * @param OutputInterface $output
     */
    public static function renderList($header, $items, $output) {
        $output->writeln('<fg=yellow;options=underscore>' . ucfirst($header) . "</>\n");

        if (count($items) > 0) {
            foreach ($items as $item) {
                $output->writeln(" - $item");
            }
        }

        $output->writeln("\n" . self::tint('(' . count($items) . ' in set)', 'comment'));
    }

}
