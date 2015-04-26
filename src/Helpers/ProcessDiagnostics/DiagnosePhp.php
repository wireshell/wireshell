<?php namespace Wireshell\Helpers\ProcessDiagnostics;

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
