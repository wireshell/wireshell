<?php namespace Wireshell\Helpers;

use Symfony\Component\Console\Command\Command as SymfonyCommand;

/**
 * Class PwConnector
 *
 * Serving as connector layer between Symfony Commands and ProcessWire
 *
 * @package Wireshell
 * @author Marcus Herrmann
 */
abstract class PwConnector extends SymfonyCommand
{

    const branchesURL = 'https://api.github.com/repos/ryancramerdesign/Processwire/branches';
    const versionURL = 'https://raw.githubusercontent.com/ryancramerdesign/ProcessWire/{branch}/wire/core/ProcessWire.php';
    const zipURL = 'https://github.com/ryancramerdesign/ProcessWire/archive/{branch}.zip';

    public $moduleServiceURL;
    public $moduleServiceKey;
    protected $userContainer;
    protected $roleContainer;
    protected $modulePath = "/site/modules/";

    /**
     * @param $output
     */
    protected function checkForProcessWire($output)
    {
        if (!is_dir(getcwd() . "/wire")) {

            $output->writeln("<error>No ProcessWire installation found.</error>");
            exit(1);
        }
    }

    /**
     * @param $output
     */
    protected function bootstrapProcessWire($output)
    {
        $this->checkForProcessWire($output);

        if (!function_exists('wire')) {
            include(getcwd() . '/index.php');
        }

        $this->userContainer = wire('pages')->get('29');
        $this->roleContainer = wire('pages')->get('30');

        $this->moduleServiceURL = wire('config')->moduleServiceURL;
        $this->moduleServiceKey = wire('config')->moduleServiceKey;

    }

    protected function getModuleDirectory()
    {
        return $this->modulePath;
    }

    /**
     * @param $output
     * @param boolean $dev
     * @return boolean
     */
    protected function checkForCoreUpgrades($output, $dev = false) {
        $branches = $this->getCoreBranches();
        $master = $branches['master'];
        $upgrade = false;
        $new = version_compare($master['version'], wire('config')->version);

        if ($new > 0 && $dev === false) {
            // master is newer than current
            $branch = $master;
            $upgrade = true;
        } else if ($new <= 0 || ($new > 0 && $dev === true)) {
            // we will assume dev branch
            $dev = $branches['dev'];
            $new = version_compare($dev['version'], wire('config')->version);
            $branch = $dev;
            if ($new > 0) $upgrade = true;
        }

        $versionStr = "$branch[name] $branch[version]";
        if ($upgrade) {
            $output->writeln("<info>A ProcessWire core upgrade is available: $versionStr</info>");
        } else {
            $output->writeln("<info>Your ProcessWire core is up-to-date: $versionStr</info>");
        }

        return array('upgrade' => $upgrade, 'branch' => $branch);
    }

    /**
     * Get Core Branches with further informations
     */
    protected function getCoreBranches() {
        $branches = array();
        $http = new \WireHttp();
        $http->setHeader('User-Agent', 'ProcessWireUpgrade');
        $json = $http->get(self::branchesURL);
        if (!$json) {
            $error = "Error loading GitHub branches " . self::branchesURL;
            if($throw) throw new WireException($error);
            $this->error($error);
            return array();
        }

        $data = json_decode($json, true);
        if (!$data) {
            $error = "Error JSON decoding GitHub branches " . self::branchesURL;
            if($throw) throw new WireException($error);
            $this->error($error);
            return array();
        }

        foreach ($data as $key => $info) {
            $name = $info['name'];
            $branch = array(
                'name' => $name,
                'title' => ucfirst($name),
                'zipURL' => str_replace('{branch}', $name, self::zipURL),
                'version' => '',
                'versionURL' => str_replace('{branch}', $name, self::versionURL),
            );

            if ($name == 'dev') $branch['title'] = 'Development';
            if ($name == 'master') $branch['title'] = 'Stable/Master';

            $content = $http->get($branch['versionURL']);
            if (!preg_match_all('/const\s+version(Major|Minor|Revision)\s*=\s*(\d+)/', $content, $matches)) {
                $branch['version'] = '?';
                continue;
            }

            $version = array();
            foreach ($matches[1] as $key => $var) {
                $version[$var] = (int) $matches[2][$key];
            }

            $branch['version'] = "$version[Major].$version[Minor].$version[Revision]";
            $branches[$name] = $branch;
        }

        return $branches;
    }


}
