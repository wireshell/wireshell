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
    const timeout = 4.5;

    /**
     * check if a module already exists
     *
     * @param string $module
     * @return boolean
     */
    public function checkIfModuleExists($module)
    {
        $moduleDir = wire('config')->paths->siteModules . $module;
        if (wire('modules')->getModule($module, array('noPermissionCheck' => true, 'noInit' => true))) {
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

    /**
     * Check all site modules for newer versions from the directory
     *
     * @param bool $onlyNew Only return array of modules with new versions available
     * @param OutputInterface $output
     * @return array of array(
     *  'ModuleName' => array(
     *    'title' => 'Module Title',
     *    'local' => '1.2.3', // current installed version
     *     'remote' => '1.2.4', // directory version available, or boolean false if not found in directory
     *     'new' => true|false, // true if newer version available, false if not
     *     'requiresVersions' => array('ModuleName' => array('>', '1.2.3')), // module requirements
     *   )
     * )
     * @throws WireException
     *
     */
    public function getModuleVersions($onlyNew = false, $output) {
        $url = wire('config')->moduleServiceURL .
            "?apikey=" . wire('config')->moduleServiceKey .
            "&limit=100" .
            "&field=module_version,version,requires_versions" .
            "&class_name=";

        $names = array();
        $versions = array();

        foreach (wire('modules') as $module) {
            $name = $module->className();
            $info = wire('modules')->getModuleInfoVerbose($name);
            if ($info['core']) continue;
            $names[] = $name;
            $versions[$name] = array(
                'title' => $info['title'],
                'local' => wire('modules')->formatVersion($info['version']),
                'remote' => false,
                'new' => 0,
                'requiresVersions' => $info['requiresVersions']
            );
        }

        if (!count($names)) return array();
        $url .= implode(',', $names);

        $http = new \WireHttp();
        $http->setTimeout(self::timeout);
        $data = $http->getJSON($url);

        if (!is_array($data)) {
            $error = $http->getError();
            if (!$error) $error = 'Error retrieving modules directory data';
            $output->writeln("<error>$error</error>");
            return array();
        }

        foreach ($data['items'] as $item) {
            $name = $item['class_name'];
            $versions[$name]['remote'] = $item['module_version'];
            $new = version_compare($versions[$name]['remote'], $versions[$name]['local']);
            $versions[$name]['new'] = $new;
            if ($new <= 0) {
                // local is up-to-date or newer than remote
                if ($onlyNew) unset($versions[$name]);
            } else {
                // remote is newer than local
                $versions[$name]['requiresVersions'] = $item['requires_versions'];
            }
        }

        if ($onlyNew) foreach($versions as $name => $data) {
            if($data['remote'] === false) unset($versions[$name]);
        }

        return $versions;
    }

    /**
     * Check all site modules for newer versions from the directory
     *
     * @param bool $onlyNew Only return array of modules with new versions available
     * @param OutputInterface $output
     * @return array of array(
     *  'ModuleName' => array(
     *    'title' => 'Module Title',
     *    'local' => '1.2.3', // current installed version
     *     'remote' => '1.2.4', // directory version available, or boolean false if not found in directory
     *     'new' => true|false, // true if newer version available, false if not
     *     'requiresVersions' => array('ModuleName' => array('>', '1.2.3')), // module requirements
     *   )
     * )
     * @throws WireException
     *
     */
    public function getModuleVersion($onlyNew = false, $output, $module) {
        // get current module data
        $info = wire('modules')->getModuleInfoVerbose($module);
        $versions = array(
            'title' => $info['title'],
            'local' => wire('modules')->formatVersion($info['version']),
            'remote' => false,
            'new' => 0,
            'requiresVersions' => $info['requiresVersions']
        );

        // get latest module data
        $url = trim(wire('config')->moduleServiceURL, '/') . "/$module/?apikey=" . wire('sanitizer')->name(wire('config')->moduleServiceKey);
        $http = new \WireHttp();
        $data = $http->getJSON($url);

        if (!$data || !is_array($data)) {
            $output->writeln("<error>Error retrieving data from web service URL - {$http->getError()}</error>");
            return array();
        }

        if ($data['status'] !== 'success') {
            $error = wire('sanitizer')->entities($data['error']);
            $output->writeln("<error>Error reported by web service: $error</error>");
            return array();
        }

        // yeah, received data sucessfully!
        // get versions and compare them
        $versions['remote'] = $data['module_version'];
        $new = version_compare($versions['remote'], $versions['local']);
        $versions['new'] = $new;
        $versions['download_url'] = $data['project_url'] . '/archive/master.zip';

        // local is up-to-date or newer than remote
        if ($new <= 0) {
            if ($onlyNew) $versions = array();
        } else {
            // remote is newer than local
            $versions['requiresVersions'] = $data['requires_versions'];
        }

        if ($onlyNew && !$versions['remote']) $versions = array();

        return $versions;
    }

}
