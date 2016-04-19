<?php namespace Wireshell\Helpers;

use ProcessWire\WireHttp;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Kfi\LocalCoachBundle\Test\FunctionalTester;

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
    const BRANCH_DEV = 'dev';
    const BRANCH_DEVNS = 'devns';
    const BRANCH_MASTER = 'master';

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

            foreach (new \DirectoryIterator(getcwd()) as $fileInfo) {
                if (is_dir($fileInfo->getPathname() . '/wire')) {
                    chdir($fileInfo->getPathname());
                }
            }

            if (!is_dir(getcwd() . "/wire")) {
                chdir('..');

                if (empty(pathinfo(getcwd())['basename'])) {
                    $output->writeln("<error>No ProcessWire installation found.</error>");
                    exit(1);
                } else {
                    $this->checkForProcessWire($output);
                }
            } else {
                $output->writeln("<info>Working directory changed to `" . getcwd() . "`.</info>\n");
            }
        }
    }

    /**
     * @param $output
     */
    protected function bootstrapProcessWire($output)
    {
        $this->checkForProcessWire($output);

        if (!function_exists('\ProcessWire\wire')) {
            include(getcwd() . '/index.php');
        }

        $this->userContainer = \ProcessWire\wire('pages')->get('29');
        $this->roleContainer = \ProcessWire\wire('pages')->get('30');

        $this->moduleServiceURL = \ProcessWire\wire('config')->moduleServiceURL;
        $this->moduleServiceKey = \ProcessWire\wire('config')->moduleServiceKey;

    }

    protected function getModuleDirectory()
    {
        return $this->modulePath;
    }

    protected function determineBranch($input) {
        if ($input->getOption('dev')) {
            $branch = self::BRANCH_DEV;
        } elseif ($input->getOption('devns')) {
            $branch = self::BRANCH_DEVNS;
        } elseif ($input->getOption('sha')) {
            $branch = $input->getOption('sha');
        } else {
            $branch = self::BRANCH_MASTER;
        }

        return $branch;
    }

    /**
     * @param $output
     * @param InputInterface $input - whether branch name or commit
     * @return boolean
     */
    protected function checkForCoreUpgrades($output, $input) {
        $config = \ProcessWire\wire('config');
        $targetBranch = $this->determineBranch($input);
        $branches = $this->getCoreBranches($targetBranch);
        $upgrade = false;
        $new = version_compare($branches['master']['version'], $config->version);

        // branch does not exist - assume commit hash
        if (!array_key_exists($targetBranch, $branches)) {
            $branch = $branches['sha'];
            $upgrade = true;
        } elseif ($new > 0 && $targetBranch === self::BRANCH_MASTER) {
            // master is newer than current
            $branch = $branches['master'];
            $upgrade = true;
        } else {
            // we will assume dev branch
            if ($targetBranch === self::BRANCH_MASTER) $targetBranch = self::BRANCH_DEV;

            if ($targetBranch === self::BRANCH_DEV) {
                $new = version_compare($branches['dev']['version'], $config->version);
                $branch = $branches['dev'];
            }

            // we will assume devns branch
            if ($new < 0) $targetBranch = self::BRANCH_DEVNS;

            if ($targetBranch === self::BRANCH_DEVNS) {
                $new = version_compare($branches['devns']['version'], $config->version);
                $branch = $branches['devns'];
            }

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
     *
     * @param string $targetBranch - whether branch name or commit
     */
    protected function getCoreBranches($targetBranch = 'master') {
        $branches = array();
        $http = new WireHttp();
        $http->setHeader('User-Agent', 'ProcessWireUpgrade');
        $json = $http->get(self::branchesURL);

        if (!$json) {
            $error = "Error loading GitHub branches " . self::branchesURL;
            throw new \WireException($error);
            $this->error($error);
            return array();
        }

        $data = json_decode($json, true);
        if (!$data) {
            $error = "Error JSON decoding GitHub branches " . self::branchesURL;
            throw new \WireException($error);
            $this->error($error);
            return array();
        }

        foreach ($data as $info) {
            $name = $info['name'];
            $branches[$name] = $this->getBranchInformations($name, $http);
        }

        // branch does not exist - assume sha
        if (!array_key_exists($targetBranch, $branches)) {
            $http = new WireHttp();
            $http->setHeader('User-Agent', 'ProcessWireUpgrade');
            $versionUrl = str_replace('{branch}', $targetBranch, self::versionURL);
            $json = $http->get($versionUrl);

            if (!$json) {
                $error = "Error loading sha `$targetBranch`.";
                throw new \WireException($error);
                $this->error($error);
                return array();
            }

            $name = $targetBranch;
            $branches['sha'] = $this->getBranchInformations($name, $http);
        }

        return $branches;
    }

    /**
     * Get branch with further informations
     *
     * @param string $name
     * @param WireHttp $http
     */
    protected function getBranchInformations($name, $http) {
        $branch = array(
            'name' => $name,
            'title' => ucfirst($name),
            'zipURL' => str_replace('{branch}', $name, self::zipURL),
            'version' => '',
            'versionURL' => str_replace('{branch}', $name, self::versionURL),
        );

        switch ($name) {
            case 'dev':
                $branch['title'] = 'Development';
                break;
            case 'devns':
                $branch['title'] = 'Development with namespace support';
                break;
            case 'master':
                $branch['title'] = 'Stable/Master';
                break;
            default:
                $branch['title'] = 'Specific commit sha';
                break;
        }

        $content = $http->get($branch['versionURL']);
        if (!preg_match_all('/const\s+version(Major|Minor|Revision)\s*=\s*(\d+)/', $content, $matches)) {
            $branch['version'] = '?';
            return;
        }

        $version = array();
        foreach ($matches[1] as $key => $var) {
            $version[$var] = (int) $matches[2][$key];
        }

        $branch['version'] = "$version[Major].$version[Minor].$version[Revision]";

        return $branch;
    }


}
