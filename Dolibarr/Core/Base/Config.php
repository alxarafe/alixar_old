<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace DoliCore\Base;

use Alxarafe\Base\Config as ConfigBase;
use DoliCore\Lib\Conf;

/**
 * Manage the Dolibarr configuration file.
 *
 * @info https://wiki.dolibarr.org/index.php/Configuration_file
 *
 * This class is only needed for compatibility with Dolibarr.
 *
 * @package DoliCore\Base
 */
abstract class Config extends ConfigBase
{
    /**
     * Dolibarr configuration filename.
     */
    private const CONFIG_FILENAME = '/conf/conf.php';

    /**
     * Defines the Dolibarr configuration file structure.
     */
    private const CONFIG_STRUCTURE = [
        'main' => [
            'dolibarr_main_document_root' => 'path',
            'dolibarr_main_url_root' => 'url',
            'dolibarr_main_data_root' => 'data',
        ],
        'db' => [
            'dolibarr_main_db_type' => 'type',
            'dolibarr_main_db_host' => 'host',
            'dolibarr_main_db_user' => 'user',
            'dolibarr_main_db_pass' => 'pass',
            'dolibarr_main_db_name' => 'name',
            'dolibarr_main_db_port' => 'port',
            'dolibarr_main_db_prefix' => 'prefix',
            'dolibarr_main_db_character_set' => 'charset',
            'dolibarr_main_db_collation' => 'collation',
            'dolibarr_main_db_encryption' => 'encryption',
            'dolibarr_main_db_cryptkey' => 'encrypt_type',
        ],
        'security' => [
            'dolibarr_main_authentication' => 'authentication_method',
            'dolibarr_main_instance_unique_id' => 'unique_id',
            'dolibarr_main_force_https' => 'https',
            'dolibarr_main_prod' => 'demo',
            'dolibarr_main_restrict_os_commands' => 'restrict_os_commands',
            'dolibarr_nocsrfcheck' => 'nocsrfcheck',
            'dolibarr_mailing_limit_sendbyweb' => 'mailing_limit_sendbyweb',
            'dolibarr_mailing_limit_sendbycli' => 'mailing_limit_sendbycli',
        ]
    ];
    private const DEFAULT_DB_PREFIX = 'alx_';
    private const DEFAULT_THEME = 'alixar';
    private const DEFAULT_DB_TYPE = 'mysqli';
    private const DEFAULT_CHARSET = 'utf8';
    private const DEFAULT_COLLATION = 'utf8_general_ci';
    private const DEFAULT_AUTHENTICATION_MODE = 'Dolibarr';
    /**
     * Contains Dolibarr configuration file information
     *
     * @var Conf|null
     */
    private static ?Conf $config = null;

    /**
     * Returns a Conf class with the Dolibarr configuration.
     * If the configuration file does not exist, is not accessible, or is not correct, returns null.
     *
     * @return Conf|null
     *
     * @deprecated Use
     */
    public static function getConf()
    {
        if (isset(self::$config)) {
            return self::$config;
        }

        self::$config = self::loadDolibarrConfig();
        return self::$config;
    }

    /**
     * Returns a Conf class with the Dolibarr configuration.
     * If the configuration file does not exist, is not accessible, or is not correct, returns null.
     *
     * @return Conf|null
     */
    public static function loadDolibarrConfig(): ?Conf
    {
        $filename = self::getDolibarrConfigFilename();
        if (file_exists($filename) && is_readable($filename)) {
            include $filename;
        }

        /*
         * Create $conf object
         */
        $conf = new Conf();

        // Set properties specific to database
        $conf->db->host = $dolibarr_main_db_host ?? '';
        $conf->db->port = $dolibarr_main_db_port ?? '';
        $conf->db->name = $dolibarr_main_db_name ?? '';
        $conf->db->user = $dolibarr_main_db_user ?? '';
        $conf->db->pass = $dolibarr_main_db_pass ?? '';
        $conf->db->type = $dolibarr_main_db_type ?? self::DEFAULT_DB_TYPE;
        $conf->db->prefix = $dolibarr_main_db_prefix ?? self::DEFAULT_DB_PREFIX;
        $conf->db->charset = $dolibarr_main_db_character_set ?? self::DEFAULT_CHARSET;
        $conf->db->collation = $dolibarr_main_db_collation ?? self::DEFAULT_COLLATION;
        $conf->db->encryption = $dolibarr_main_db_encryption ?? 0;
        $conf->db->cryptkey = $dolibarr_main_db_cryptkey ?? '';
        if (defined('TEST_DB_FORCE_TYPE')) {
            $conf->db->type = constant('TEST_DB_FORCE_TYPE'); // Force db type (for test purpose, by PHP unit for example)
        }

        // Set properties specific to conf file
        $conf->file->main_limit_users = $dolibarr_main_limit_users ?? null;
        $conf->file->mailing_limit_sendbyweb = $dolibarr_mailing_limit_sendbyweb ?? 0;
        $conf->file->mailing_limit_sendbycli = $dolibarr_mailing_limit_sendbycli ?? 0;
        $conf->file->mailing_limit_sendbyday = $dolibarr_mailing_limit_sendbyday ?? 0;
        $conf->file->main_authentication = $dolibarr_main_authentication ?? self::DEFAULT_AUTHENTICATION_MODE;
        $conf->file->main_force_https = isset($dolibarr_main_force_https) && $dolibarr_main_force_https ? true : false;
        $conf->file->strict_mode = $dolibarr_strict_mode ?? '';
        $conf->file->instance_unique_id = $dolibarr_main_instance_unique_id ?? '';
        $conf->file->main_path = $dolibarr_main_document_root ?? constant('BASE_PATH');
        $conf->file->main_url = $dolibarr_main_url_root ?? constant('BASE_URL');
        $conf->file->main_doc = $dolibarr_main_data_root ?? static::getDataDir($conf->file->main_path);
        $conf->file->path = ['main' => $conf->file->main_path];
        $conf->file->url = ['main' => '/'];
        $conf->file->dol_document_root = $conf->file->main_doc;
        if (!empty($dolibarr_main_document_root_alt)) {
            $path = preg_split('/[;,]/', $dolibarr_main_document_root_alt);
            $url = preg_split('/[;,]/', $dolibarr_main_url_root_alt ?? DIRECTORY_SEPARATOR);

            if (count($path) !== count($url)) {
                print '<b>Error:</b><br>$dolibarr_main_document_root_alt and $dolibarr_main_url_root_alt must contain the same number of elements.<br>';
                die();
            }

            $i = 0;
            foreach ($path as $value) {
                $conf->file->path['alt' . ($i++)] = (string)$value;
            }
            $values = preg_split('/[;,]/', $dolibarr_main_url_root_alt);
            $i = 0;
            foreach ($url as $value) {
                if (preg_match('/^http(s)?:/', $value)) {
                    // Show error message
                    $correct_value = str_replace($conf->file->url, '', $value);
                    print '<b>Error:</b><br>' . "\n";
                    print 'Wrong <b>$dolibarr_main_url_root_alt</b> value in <b>conf.php</b> file.<br>' . "\n";
                    print 'We now use a relative path to $dolibarr_main_url_root to build alternate URLs.<br>' . "\n";
                    print 'Value found: ' . $value . '<br>' . "\n";
                    print 'Should be replaced by: ' . $correct_value . '<br>' . "\n";
                    print "Or something like following examples:<br>\n";
                    print "\"/extensions\"<br>\n";
                    print "\"/extensions1,/extensions2,...\"<br>\n";
                    print "\"/../extensions\"<br>\n";
                    print "\"/custom\"<br>\n";
                    exit;
                }
                $conf->file->url['alt' . ($i++)] = (string)$value;
            }
        }

        $conf->file->theme = $dolibarr_main_theme ?? self::DEFAULT_THEME;
        $conf->file->dol_main_stream_to_disable = $dolibarr_main_stream_to_disable ?? null;
        $conf->debug = intval($dolibarr_main_prod ?? 1) === 0;

        // Detection browser (copy of code from main.inc.php)
        if (isset($_SERVER["HTTP_USER_AGENT"]) && is_object($conf) && empty($conf->browser->name)) {
            $tmp = getBrowserInfo($_SERVER["HTTP_USER_AGENT"]);
            $conf->browser->name = $tmp['browsername'];
            $conf->browser->os = $tmp['browseros'];
            $conf->browser->version = $tmp['browserversion'];
            $conf->browser->layout = $tmp['layout']; // 'classic', 'phone', 'tablet'
            //var_dump($conf->browser);

            if ($conf->browser->layout == 'phone') {
                $conf->dol_no_mouse_hover = 1;
            }
        }

        // Load the main includes of common libraries
        if (!defined('NOREQUIRETRAN')) {
            require_once BASE_PATH . '/core/class/translate.class.php';
        }

        return self::$config = $conf;
    }

    /**
     * Returns the Dolibarr conf.php complete path.
     *
     * @return string
     */
    private static function getDolibarrConfigFilename()
    {
        return BASE_PATH . self::CONFIG_FILENAME;
    }

    private static function getConfigFrom(string $filename): array
    {
        $result = [];

        if (!file_exists($filename)) {
            error_log($filename . ' does not exists!');
            return $result;
        }

        if (!is_readable($filename)) {
            error_log($filename . ' exists, but is not readable!');
            return $result;
        }

        require $filename;

        $data = [];
        foreach (self::CONFIG_STRUCTURE as $section => $values) {
            $data[$section] = [];
            foreach ($values as $key => $value) {
                if (!isset(${$key})) {
                    continue;
                }
                $data[$section][$value] = ${$key};
            }
        }

        return $data;
    }

    /**
     * Simply replace /htdocs with /documents in $pathDir
     *
     * @param $pathDir
     *
     * @return string
     */
    public static function getDataDir($pathDir)
    {
        return preg_replace("/\/htdocs$/", "", $pathDir) . '/documents';
    }

}
