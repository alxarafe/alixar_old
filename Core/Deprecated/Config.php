<?php
/* Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
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

namespace Alxarafe\Deprecated;

require_once BASE_PATH . '/core/class/conf.class.php';

use Conf;
use stdClass;

/**
 * Generate an object with the configuration of the Dolibarr conf.php file.
 *
 * @info https://wiki.dolibarr.org/index.php/Configuration_file
 *
 * @deprecated This class is only needed for compatibility with Dolibarr.
 */
abstract class Config
{
    /**
     * Contains the information from the conf.php file.
     *
     * @var null|stdClass
     */
    static protected $config = null;

    /**
     * Contains the information of the old conf global var.
     *
     * @var null|stdClass
     */
    static protected $conf = null;

    /**
     * Simply replace /htdocs with /documents in $pathDir
     *
     * @param $pathDir
     *
     * @return string
     */
    private static function getDataDir($pathDir)
    {
        return preg_replace("/\/htdocs$/", "", $pathDir) . '/documents';
    }

    /**
     * Returns the Dolibarr conf.php complete path.
     *
     * @return string
     */
    public static function getDolibarrConfigFilename()
    {
        return BASE_PATH . '/conf/conf.php';
    }

    /**
     * Returns a stdClass with the information contained in the conf.php file.
     *
     * @param $reload
     *
     * @return stdClass|null
     */
    public static function loadConfig($reload = false): ?stdClass
    {
        if (isset(static::$config) && !$reload) {
            return static::$config;
        }

        $filename = static::getDolibarrConfigFilename();
        if (!file_exists($filename) || !is_readable($filename)) {
            return null;
        }

        include $filename;

        $config = new stdClass();

        // 'main' section
        $config->main = new stdClass();
        $config->main->base_path = trim($dolibarr_main_document_root ?? constant('BASE_PATH'));
        $config->main->base_url = trim($dolibarr_main_url_root ?? constant('BASE_URL'));
        $config->main->data_path = trim($dolibarr_main_data_root ?? static::getDataDir($config->main->base_path));

        $alt_base_path = $dolibarr_main_document_root_alt ?? false;
        if ($alt_base_path !== false) {
            $config->main->alt_base_path = trim($dolibarr_main_document_root_alt);
        }

        $alt_base_url = $dolibarr_main_url_root_alt ?? false;
        if ($alt_base_url !== false) {
            $config->main->alt_base_url = trim($dolibarr_main_url_root_alt);
        }

        $alt_data_path = $dolibarr_main_data_root_alt ?? false;
        if ($alt_data_path !== false) {
            $config->main->alt_data_path = trim($dolibarr_main_data_root_alt);
        }

        // 'db' section
        $config->db = new stdClass();
        $config->db->type = trim($dolibarr_main_db_type ?? 'mysql');
        $config->db->host = trim($dolibarr_main_db_host ?? 'localhost');
        $config->db->port = trim($dolibarr_main_db_port ?? '');
        $config->db->name = trim($dolibarr_main_db_name ?? 'dolibarr');
        $config->db->user = trim($dolibarr_main_db_user ?? 'dolibarr');
        $config->db->pass = trim($dolibarr_main_db_pass ?? '');
        $config->db->prefix = trim($dolibarr_main_db_prefix ?? '');
        $config->db->charset = trim($dolibarr_main_db_character_set ?? 'utf8');
        $config->db->collation = trim($dolibarr_main_db_collation ?? 'utf8mb4_unicode_ci');

        // 'security' section
        $config->security = new stdClass();
        $config->security->authentication_type = $dolibarr_main_authentication ?? 'dolibarr';
        $config->security->force_https = intval($dolibarr_main_force_https ?? 1);
        $config->security->unique_id = $dolibarr_main_instance_unique_id ?? null;

        $config->file = new stdClass();
        $config->file->instance_unique_id = $config->security->unique_id;

        // Others
        $demo = $dolibarr_main_demo ?? false;
        if ($demo !== false) {
            $credentials = explode(',', $demo);
            if (count($credentials) === 2) {
                $config->demo->user = trim($credentials[0]);
                $config->demo->pass = trim($credentials[1]);
            }
        }

        $config->debug = intval($dolibarr_main_prod ?? 1) === 0;

        // 'Server' section
        $config->server = new stdClass();
        $config->server->detailed_info = !empty($_SERVER['MAIN_SHOW_TUNING_INFO']);

        return $config;
    }

    public static function loadConf($reload = false): ?stdClass
    {
        if (isset(static::$conf) && !$reload) {
            return static::$conf;
        }

        $filename = static::getDolibarrConfigFilename();
        if (!file_exists($filename) || !is_readable($filename)) {
            return null;
        }

        include $filename;

        $conf = new Conf();

        /*
         * Create $conf object
         */

        $conf = new Conf();

// Set properties specific to database
        $conf->db->host = empty($dolibarr_main_db_host) ? '' : $dolibarr_main_db_host;
        $conf->db->port = empty($dolibarr_main_db_port) ? '' : $dolibarr_main_db_port;
        $conf->db->name = empty($dolibarr_main_db_name) ? '' : $dolibarr_main_db_name;
        $conf->db->user = empty($dolibarr_main_db_user) ? '' : $dolibarr_main_db_user;
        $conf->db->pass = empty($dolibarr_main_db_pass) ? '' : $dolibarr_main_db_pass;
        $conf->db->type = $dolibarr_main_db_type;
        $conf->db->prefix = $dolibarr_main_db_prefix;
        $conf->db->character_set = $dolibarr_main_db_character_set;
        $conf->db->dolibarr_main_db_collation = $dolibarr_main_db_collation;
        $conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption ?? null;
        $conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey ?? null;
        if (defined('TEST_DB_FORCE_TYPE')) {
            $conf->db->type = constant('TEST_DB_FORCE_TYPE'); // Force db type (for test purpose, by PHP unit for example)
        }

// Set properties specific to conf file
        $conf->file->main_limit_users = $dolibarr_main_limit_users ?? null;
        $conf->file->mailing_limit_sendbyweb = empty($dolibarr_mailing_limit_sendbyweb) ? 0 : $dolibarr_mailing_limit_sendbyweb;
        $conf->file->mailing_limit_sendbycli = empty($dolibarr_mailing_limit_sendbycli) ? 0 : $dolibarr_mailing_limit_sendbycli;
        $conf->file->mailing_limit_sendbyday = empty($dolibarr_mailing_limit_sendbyday) ? 0 : $dolibarr_mailing_limit_sendbyday;
        $conf->file->main_authentication = empty($dolibarr_main_authentication) ? 'dolibarr' : $dolibarr_main_authentication; // Identification mode
        $conf->file->main_force_https = empty($dolibarr_main_force_https) ? '' : $dolibarr_main_force_https; // Force https
        $conf->file->strict_mode = empty($dolibarr_strict_mode) ? '' : $dolibarr_strict_mode; // Force php strict mode (for debug)
        $conf->file->instance_unique_id = empty($dolibarr_main_instance_unique_id) ? (empty($dolibarr_main_cookie_cryptkey) ? '' : $dolibarr_main_cookie_cryptkey) : $dolibarr_main_instance_unique_id; // Unique id of instance
        $conf->file->dol_main_url_root = $dolibarr_main_url_root;   // Define url inside the config file
        $conf->file->dol_document_root = array('main' => (string) DOL_DOCUMENT_ROOT); // Define array of document root directories ('/home/htdocs')
        $conf->file->dol_url_root = array('main' => (string) DOL_URL_ROOT); // Define array of url root path ('' or '/dolibarr')
        if (!empty($dolibarr_main_document_root_alt)) {
            // dolibarr_main_document_root_alt can contains several directories
            $values = preg_split('/[;,]/', $dolibarr_main_document_root_alt);
            $i = 0;
            foreach ($values as $value) {
                $conf->file->dol_document_root['alt' . ($i++)] = (string) $value;
            }
            $values = preg_split('/[;,]/', $dolibarr_main_url_root_alt);
            $i = 0;
            foreach ($values as $value) {
                if (preg_match('/^http(s)?:/', $value)) {
                    // Show error message
                    $correct_value = str_replace($dolibarr_main_url_root, '', $value);
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
                $conf->file->dol_url_root['alt' . ($i++)] = (string) $value;
            }
        }

        return $conf;
    }
}
