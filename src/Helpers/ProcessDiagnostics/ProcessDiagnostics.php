<?php namespace Wireshell\Helpers\ProcessDiagnostics;

class ProcessDiagnostics {
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

    public function __construct() {
        self::$ok = 'OK';
        self::$fail = 'Failure';
        self::$warn = 'Warning';

        self::$exists = 'Exists';
        self::$not_exists = 'Does not exist.';
        self::$read = 'is readable';
        self::$not_read = 'is not readable';
        self::$write = 'is writable';
        self::$not_write  = 'is not writable';

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


