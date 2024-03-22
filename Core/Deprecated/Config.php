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
    static private $config = null;

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
            return $config;
        }

        $filename = static::getDolibarrConfigFilename();
        if (!file_exists($filename) || !is_readable($filename)) {
            return null;
        }

        include $filename;

        $config = new stdClass();

        // 'main' section
        $config->main = new stdClass();
        $config->main->base_path = trim($dolibarr_main_document_root ?? BASE_PATH);     // /home/www/dolibarr/htdocs
        $config->main->base_url = trim($dolibarr_main_url_root ?? BASE_URL);            // http://mydomain.com/dolibarr
        $config->main->data_path = trim($dolibarr_main_data_root ?? static::getDataDir());

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
}
