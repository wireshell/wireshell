<?php namespace Wireshell\Commands\Common;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Wireshell\PwConnector;


/**
 * Class StatusCommand
 *
 * Returns versions, paths and environment info
 *
 * @package Wireshell
 * @author Marcus Herrmann
 * @author Camilo Castro
 */

class StatusCommand extends PwConnector
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName('status')
            ->setDescription('Returns versions, paths and environment info')
            ->addOption('image', null, InputOption::VALUE_NONE, 'get Diagnose for Imagehandling')
            ->addOption('php', null, InputOption::VALUE_NONE, 'get Diagnose for PHP');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        parent::bootstrapProcessWire($output);

        $pwStatus = [
            ['Version', wire('config')->version],
            ['Admin URL', $this->getAdminUrl()],
            ['Advanced mode', wire('config')->advanced ? 'On' : 'Off'],
            ['Debug mode', wire('config')->debug ? '<error>On</error>' : '<info>Off</info>'],
            ['Timezone', wire('config')->timezone],
            ['HTTP hosts', implode(", ", wire('config')->httpHosts)],
            ['Admin theme', wire('config')->defaultAdminTheme],
            ['Database host', wire('config')->dbHost],
            ['Database name', wire('config')->dbName],
            ['Database user', wire('config')->dbUser],
            ['Database port', wire('config')->dbPort],
            ['Installation path', getcwd()]
        ];

        $envStatus = [
            ['PHP version', PHP_VERSION],
            ['PHP binary', PHP_BINARY],
            ['MySQL version', $this->getMySQLVersion()]
        ];

        $wsStatus = [
            ['Version',  $this->getApplication()->getVersion()],
            ['Forum', 'https://processwire.com/talk/topic/9494-wireshell-an-extendable-processwire-command-line-interface/']
        ];


        $tables = [];
        $tables[] = $this->buildTable($output, $pwStatus, 'ProcessWire');
        $tables[] = $this->buildTable($output, $envStatus, 'Environment');
        $tables[] = $this->buildTable($output, $wsStatus, 'wireshell');


        if ($input->getOption('php')) {
            $phpStatus = $this->getDiagnosePhp();
            $tables[] = $this->buildTable($output, $phpStatus, 'PHP Diagnostics');
        }

        if ($input->getOption('image')) {
            $phpStatus = $this->getDiagnoseImagehandling();
            $tables[] = $this->buildTable($output, $phpStatus, 'Image Diagnostics');
        }


        $this->renderTables($output, $tables);
    }


    protected function buildTable(OutputInterface $output, $statusArray, $label)
    {
        $tablePW = new Table($output);
        $tablePW
            ->setStyle('borderless')
            ->setHeaders(["<comment>{$label}</comment>"])
            ->setRows($statusArray);

        return $tablePW;
    }

    /**
     * @return string
     */
    protected function getAdminUrl()
    {
        $admin = wire('pages')->get('template=admin');

        $url = wire('config')->urls->admin;

        if (!($admin instanceof \NullPage) && isset($admin->httpUrl)) {

            $url = $admin->httpUrl;
        }

        return $url;
    }

    /**
     * @return string
     */
    function getMySQLVersion() {

        ob_start();
        phpinfo(INFO_MODULES);
        $info = ob_get_contents();
        ob_end_clean();
        $info = stristr($info, 'Client API version');
        preg_match('/[1-9].[0-9].[1-9][0-9]/', $info, $match);
        $gd = $match[0];

        return $gd;

    }

    /**
     * @param OutputInterface $output
     * @param $tablePW
     * @param $tableEnv
     * @param $tableWs
     */
    protected function renderTables(OutputInterface $output, $tables)
    {
        $output->writeln("\n");
        foreach($tables as $table) {
            $table->render();
            $output->writeln("\n");
        }
    }


    /**
    * wrapper method for the Diagnose PHP submodule from @netcarver
    */
    protected function getDiagnosePhp()
    {
        $sub = new DiagnosePhp();
        $rows = $sub->GetDiagnostics();
        $result = [];
        foreach($rows as $row)
        {
            $result[] = [$row['title'], $row['value']];
        }
        return $result;
    }


    /**
    * wrapper method for the Diagnose Imagehandling submodule from @netcarver & @horst
    */
    protected function getDiagnoseImagehandling()
    {
        $sub = new DiagnoseImagehandling();
        $rows = $sub->GetDiagnostics();
        $result = [];
        foreach($rows as $row)
        {
            $result[] = [$row['title'], $row['value']];
        }
        return $result;
    }

}



class ProcessDiagnostics
{
    public static $ok;
    public static $fail;
    public static $warn;

    public static $exists;
    public static $not_exists;
    public static $read;
    public static $not_read;
    public static $write;
    public static $not_write;

    public static $verbosity;
    public static $disabled_functions;

    const LOW_VERBOSITY    = 1;
    const MEDIUM_VERBOSITY = 2;
    const HIGH_VERBOSITY   = 4;


    public function __constructor()
    {
        self::$ok   = $this->_('OK');
        self::$fail = $this->_('Failure');
        self::$warn = $this->_('Warning');

        self::$exists     = $this->_('Exists');
        self::$not_exists = $this->_('Does not exist.');
        self::$read       = $this->_('is readable');
        self::$not_read   = $this->_('is not readable');
        self::$write      = $this->_('is writable');
        self::$not_write  = $this->_('is not writable');

        self::$verbosity = self::HIGH_VERBOSITY;

        self::$disabled_functions = explode(',' , str_replace(' ', '', strtolower(ini_get('disable_functions'))));
    }


    /**
     * Converts a number of bytes into a more human readable form
     */
    public static function humanSize($b)
    {
        if ($b < 1024) {
            return "$b bytes";
        } else if (strlen($b) <= 9 && strlen($b) >= 7) {
            return number_format($b / 1048576, 2)." MB";
        } elseif (strlen($b) >= 10) {
            return number_format($b / 1073741824, 2)." GB";
        }
        return number_format($b / 1024, 2)." KB";
    }


    /**
     * Reads basic FS parameters for the given file or directory.
     */
    public static function getFileSystemAttribs($name, $pathspec)
    {
        $fs_info = array(
            'name'   => $name,
            'path'   => $pathspec,
            'exists' => file_exists($pathspec),
            'isfile' => false,
            'islink' => false,
            'isdir'  => false,
            'read'   => false,
            'write'  => false,
            'exec'   => false,
            'perms'  => false,
        );

        if ($fs_info['exists']) {
            $fs_info['isfile'] = is_file($pathspec);
            $fs_info['islink'] = is_link($pathspec);
            $fs_info['isdir']  = is_dir($pathspec);
            $fs_info['read']   = is_readable($pathspec);
            $fs_info['write']  = is_writable($pathspec);
            $fs_info['exec']   = is_executable($pathspec);
            $fs_info['perms']  = fileperms($pathspec);
        }

        return $fs_info;
    }


    /**
     *
     */
    public static function chooseStatus(array $warnings, array $fails)
    {
        if (count($fails)) {
            return ProcessDiagnostics::$fail;
        }

        if (count($warnings)) {
            return ProcessDiagnostics::$warn;
        }

        return ProcessDiagnostics::$ok;
    }


    /**
     * Creates a text description from the given file information.
     */
    public static function describeFSInfo($info)
    {
        $out = array();

        if ($info['exists']) {
            $out[] = self::$exists;

            if ($info['read']) {
                $out[] = self::$read;
            } else {
                $out[] = self::$not_read;
            }

            if ($info['write']) {
                $out[] = self::$write;
            } else {
                $out[] = self::$not_write;
            }

            $out[] = substr(sprintf('%o', $info['perms']), -4);
        } else {
            $out[] = self::$not_exists;
        }

        return implode(', ', $out);
    }


    /**
     * Capitialise the initial character of the given string.
     */
    public static function initCap($string)
    {
        return strtoupper($string[0]) . substr($string, 1);
    }


    /**
     * returns if function is disabled in php
     *
     * @return boolean: true, false
     */
    static public function isDisabled($function) {
        return in_array(strtolower($function), self::$disabled_functions);
    }

}



class DiagnosePhp extends ProcessDiagnostics
{
    public function __constructor() { parent::__constructor(); }

    public function GetDiagnostics()
    {
        // PHP Version & System :: Version
        $fail_limit = '5.3.8';
        $status = ProcessDiagnostics::$ok;
        $action = '';
        if (version_compare(PHP_VERSION, $fail_limit) < 0) {
            $status = ProcessDiagnostics::$fail;
            $action = "Upgrade your PHP installation to at least version " . $fail_limit;
        }

        // build results array PHP Version
        $results[] = array(
            'title'  => 'Version',
            'value'  => PHP_VERSION,
            'status' => $status,
            'action' => $action
        );

        ob_start();
        phpinfo(INFO_GENERAL);
        $buffer = str_replace("\r\n", "\n", ob_get_contents());
        ob_end_clean();

        $pattern = preg_match('#</td>#msi', $buffer)===1 ? '#>Server API.*?</td><td.*?>(.*?)</td>#msi' : '#\nServer API.*?=>(.*?)\n#msi';
        $api = preg_match($pattern, $buffer, $matches)===1 ? trim($matches[1]) : null;

        $pattern = preg_match('#</td>#msi', $buffer)===1 ? '#>System.*?</td><td.*?>(.*?)</td>#msi' : '#\nSystem.*?=>(.*?)\n#msi';
        $sys = preg_match($pattern, $buffer, $matches)===1 ? trim($matches[1]) : null;

        // build results array PHP Handler
        $results[] = array(
            'title'  => 'Handler',
            'value'  => $api,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );

        // build results array PHP system info
        $results[] = array(
            'title'  => 'System Information',
            'value'  => $sys,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );


        // build results array PHP Timezone
        $results[] = array(
            'title'  => 'Timezone',
            'value'  => $this->wire->config->timezone,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );


        // build results array PHP Max Memory
        $mem = trim(ini_get('memory_limit'));
        $results[] = array(
            'title'  => 'Max Memory',
            'value'  => $mem,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );


        // build results array PHP Max Execution Time
        $max_execution_time = trim(ini_get('max_execution_time'));
        $can_change = set_time_limit($max_execution_time);
        $can_change = ($can_change) ?
            "This can be extended by calling set_time_limit()." :
            "Fixed. Calling set_time_limit() has no effect.";
        $results[] = array(
            'title'  => 'Max execution time',
            'value'  => $max_execution_time,
            'status' => ProcessDiagnostics::$ok,
            'action' => $can_change
        );


        // POST & Upload related :: Max Input Time
        $max_input_time = trim(ini_get('max_input_time'));
        $results[] = array(
            'title'  => 'Maximum input time',
            'value'  => $max_input_time,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );

        // POST & Upload related :: Upload Max Filesize
        $upload_max_filesize = trim(ini_get('upload_max_filesize'));
        $results[] = array(
            'title'  => 'Upload Max Filesize',
            'value'  => $upload_max_filesize,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );

        // POST & Upload related :: Post Max Size
        $post_max_size = trim(ini_get('post_max_size'));
        $results[] = array(
            'title'  => 'Post Max Size',
            'value'  => $post_max_size,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );

        // POST & Upload related :: Max Input Vars
        $max_input_vars = trim(ini_get('max_input_vars'));
        $results[] = array(
            'title'  => 'Max Input Vars',
            'value'  => $max_input_vars,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );

        // POST & Upload related :: Max Input Nesting Level
        $max_input_nesting_level = trim(ini_get('max_input_nesting_level'));
        $results[] = array(
            'title'  => 'Max Input Nesting Level',
            'value'  => $max_input_nesting_level,
            'status' => ProcessDiagnostics::$ok,
            'action' => ''
        );


    // Debugger may slow down systems, lets have a check for loaded debuggers

        // XDebug, with special attention for "max_nesting_level"
        $xdebug = @extension_loaded('xdebug') || function_exists('xdebug_get_code_coverage');
        if ($xdebug) {
            $xd_value = 'is loaded (*)';
            $xd_nestingLevel = (int) ini_get('xdebug.max_nesting_level');
            if ($xd_nestingLevel > 0 && $xd_nestingLevel < 190) {
                $xd_status = ProcessDiagnostics::$warn;
                $xd_action = sprintf('(*) xdebug.max_nesting_level = %d', $xd_nestingLevel);
                $xd_action .= '<br />' . sprintf('A value lower then 200 can result in problems, read more about it in the %s', '<a href="https://processwire.com/talk/tags/forums/xdebug/" target="_blank">PW Forums</a>.');
            } else {
                $xd_status = ProcessDiagnostics::$ok;
                $xd_action = sprintf('(*) xdebug.max_nesting_level = %d', $xd_nestingLevel);
                $xd_action .= '<br />' . sprintf('Read more on PW with XDebug in the %s', '<a href="https://processwire.com/talk/tags/forums/xdebug/" target="_blank">PW Forums</a>.');
            }
            $xd_action .= '<br />Debugger-Website: <a target="_blank" href="http://xdebug.org/">http://xdebug.org/</a>';

            $results[] = array(
                'title'  => 'XDebug Extension',
                'value'  => $xd_value,
                'status' => $xd_status,
                'action' => $xd_action
            );
        }


        // DBG, - http://www.nusphere.com/dbg
        $dbg = @extension_loaded('dbg');
        if ($dbg) {
            $results[] = array(
                'title'  => 'DBG Extension',
                'value'  => 'is loaded',
                'status' => ProcessDiagnostics::$ok,
                'action' => 'Debugger-Website: <a target="_blank" href="http://www.nusphere.com/dbg">http://www.nusphere.com/dbg</a>'
            );
        }


        // Debug Bar, - http://phpdebugbar.com
        $debugbar = class_exists('StandardDebugBar');
        if ($debugbar) {
            $results[] = array(
                'title'  => 'Debug Bar (JS-Implementation)',
                'value'  => 'is available (*)',
                'status' => ProcessDiagnostics::$ok,
                'action' => '(*) PHP Debug Bar is not a PHP-Extension but a PHP-Class that can be used to build JS-Code for viewing debugresults.' .
                            '</br />Debugger-Website: <a target="_blank" href="http://phpdebugbar.com/">http://phpdebugbar.com/</a>'
            );
        }


        // return all results
        return $results;
    }
}



class DiagnoseImagehandling extends ProcessDiagnostics
{
    public function __constructor() { parent::__constructor(); }

    public function GetDiagnostics()
    {
        if(!function_exists('gd_info')) {
            $results[] = array(
                'title'  => 'GD library',
                'value'  => $ver,
                'status' => ProcessDiagnostics::$fail,
                'action' => 'There seems to be no GD-library installed!'
            );
        } else {
            $gd  = gd_info();
            $ver = isset($gd['GD Version']) ? $gd['GD Version'] : 'Version-Info not available';
            $jpg = isset($gd['JPEG Support']) ? $gd['JPEG Support'] : false;
            $png = isset($gd['PNG Support']) ? $gd['PNG Support'] : false;
            $gif = isset($gd['GIF Read Support']) && isset($gd['GIF Create Support']) ? $gd['GIF Create Support'] : false;
            $freetype = isset($gd['FreeType Support']) ? $gd['FreeType Support'] : false;

            // GD version
            $results[] = array(
                'title'  => 'GD library',
                'value'  => ProcessDiagnostics::initCap($ver),
                'status' => ProcessDiagnostics::$ok,
                'action' => ''
            );

            // PHP with GD bug ?
            if((version_compare(PHP_VERSION, '5.5.8', '>') && version_compare(PHP_VERSION, '5.5.11', '<'))) {
                if(version_compare($this->config->version, '2.4.1', '<')) {
                    $results[] = array(
                        'title'  => 'GD library Bug',
                        'value'  => 'Possible bug in GD-Version',
                        'status' => ProcessDiagnostics::$warn,  // @steve: or better use ProcessDiagnostics::fail ?
                        'action' => 'Bundled GD libraries in PHP versions 5.5.9 and 5.5.10 are known as buggy. You should update A) your PHP version to 5.5.11 or newer, or B) the ProcessWire version to 2.4.2 or newer'
                    );
                }
            }

            // GD supported types
            foreach(array('JPG', 'PNG', 'GIF', 'FreeType') as $v) {
                $name = sprintf('GD %s Support', $v);
                $v = strtolower($v);
                $value = $$v ? 'Supported' : 'Not supported';
                $status = $$v ? ProcessDiagnostics::$ok : ProcessDiagnostics::$fail;
                $results[] = array(
                    'title'  => $name,
                    'value'  => $value,
                    'status' => $status,
                    'action' => ''
                );
            }
        }


        // check if we can read exif data

        $res = function_exists('exif_read_data');
        $action = $res ? '' : "Not needed for PW core, might be needed by third party modules.";
        $results[] = array(
            'title'  => 'Exif read data',
            'value'  => $res ? 'Available' : 'Not available',
            'status' => $res ? ProcessDiagnostics::$ok : ProcessDiagnostics::$warn,
            'action' => $action
        );


        // check if Imagick is supported

        if(!class_exists('Imagick')) {
            $results[] = array(
                'title'  => 'Imagick Extension',
                'value'  => 'Not available',
                'status' => ProcessDiagnostics::$warn,
                'action' => 'Not needed for PW core, might be needed by third party modules.'
            );
        } else {
            if(ProcessDiagnostics::isDisabled('phpinfo')) {
                $results[] = array(
                    'title'  => 'Imagick Extension',
                    'value'  => 'Available',
                    'status' => ProcessDiagnostics::$warn,
                    'action' => 'Odd, retrieving phpinfo on your server is disabled! We cannot get further informations here.'
                );
            } else {
                $results[] = array(
                    'title'  => 'Imagick Extension',
                    'value'  => 'Available',
                    'status' => ProcessDiagnostics::$ok,
                    'action' => ''
                );
                ob_start();
                phpinfo();
                $buffer = ob_get_clean();
                $pattern = '/>imagick<.*?<table.*?(<tr>.*?<\/table>)/msi';
                preg_match($pattern, $buffer, $matches);
                if(isset($matches[1])) {
                    $buf = trim(str_replace('</table>', '', $matches[1]));
                    $a = explode("\n", strip_tags(str_replace(array("\r\n", "\r", '</td><td'), array("\n", "\n", '</td>###<td'), $buf)));
                    $info = array();
                    foreach($a as $line) {
                        if(preg_match('/ImageMagick supported formats/i', $line)) continue;
                        $tmp = explode('###', $line);
                        $k = trim($tmp[0], ': ');
                        $v = str_replace(' http://www.imagemagick.org', '', trim($tmp[1]));
                        #$results['images'][] = array(
                        $results[] = array(
                            'title'  => $k,
                            'value'  => $v,
                            'status' => ProcessDiagnostics::$ok,
                            'action' => ''
                        );
                    }
                }
            }
        }
        return $results;
    }
}

