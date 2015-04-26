<?php namespace Wireshell\Helpers\ProcessDiagnostics;

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

