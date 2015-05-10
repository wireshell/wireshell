<?php namespace Wireshell\Helpers;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * PwModuleTools
 *
 * Reusable methods for module generation, download, activation
 *
 * @package Wireshell
 * @author Tabea David <td@kf-interactive.com>
 * @author Marcus Herrmann
 */
class PwModuleTools extends PwConnector
{
    /**
     * check if a module already exists
     *
     * @param string $module
     * @return boolean
     */
    public function checkIfModuleExists($module)
    {
        $moduleDir = wire('config')->paths->siteModules . $module;
        if (wire("modules")->get("{$module}")) {
            $return = true;
        }

        if (is_dir($moduleDir) && !$this->isEmptyDirectory($moduleDir)) {
            $return = true;
        }

        return (isset($return)) ? $return : false;
    }

    /**
     * Checks whether the given directory is empty or not.
     *
     * @param  string $dir the path of the directory to check
     * @return bool
     */
    public function isEmptyDirectory($dir)
    {
        // glob() cannot be used because it doesn't take into account hidden files
        // scandir() returns '.'  and '..'  for an empty dir
        return 2 === count(scandir($dir . '/'));
    }
}
