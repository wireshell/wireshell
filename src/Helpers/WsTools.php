<?php namespace Wireshell\Helpers;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\QuestionHelper as BaseQuestionHelper;

/**
 * Class WsTools
 *
 * Contains common methods that could be used in every command
 *
 * @package Wireshell
 * @author Camilo Castro
 * @author Tabea David
 */

Class WsTools extends BaseQuestionHelper {
    const kTintError = 'error';
    const kTintInfo = 'info';
    const kTintComment = 'comment';

    /* const kTintSuccess = 'bg=green;fg=white;options=bold'; */
    const kTintSuccess = 'bg=green;fg=white;options=bold';
    const kTintHeader = 'bg=blue;fg=white';

    /**
     * Simple method for coloring output
     * Possible Types: error, info, comment
     * @param $string
     * @param $type
     * @return tinted string
     */
    public function tint($string, $type) {
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
    public function renderList($header, $items, $output) {
        $output->writeln('<fg=yellow;options=underscore>' . ucfirst($header) . "</>\n");

        if (count($items) > 0) {
            foreach ($items as $item) {
                $output->writeln(" - $item");
            }
        }

        $output->writeln("\n" . self::tint('(' . count($items) . ' in set)', 'comment'));
    }

    /**
     * Get question green text, white brackets/semicolon, yellow default
     *
     * @param string $question
     * @param string $default
     * @param string $sep
     * @return string
     */
    public function getQuestion($question, $default, $sep = ':') {
        $que = self::tint($question, self::kTintInfo);
        $def = ' [' . self::tint($default, self::kTintComment) . ']';

        return $default ? "{$que}{$def}{$sep}" : "{$que}{$sep}";
    }

    public function writeSection(OutputInterface $output, $formatter, $text, $style = self::kTintHeader) {
        $output->writeln(array(
            '',
            $formatter->formatBlock($text, $style, true),
            '',
        ));
    }

}
