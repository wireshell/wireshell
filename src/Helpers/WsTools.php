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
}
