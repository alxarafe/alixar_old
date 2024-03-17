<?php

/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017 Regis Houssin      	<regis.houssin@inodbox.com>
 * Copyright (C) 2006 	   Jean Heimburger    	<jean@tiaris.info>
 * Copyright (C) 2024      Rafael San José      <rsanjose@alxarafe.com>
 * Copyright (C) 2024      Francesc Pineda      <fpineda@alxarafe.com>
 * Copyright (C) 2024      Cayetano Hernández   <chernandez@alxarafe.com>
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

namespace Alxarafe\Base;

use Alxarafe\Lib\Functions;
use Symfony\Component\Yaml\Yaml;

/**
 *  Class to stock current configuration
 */
class Config
{
    /**
     * Defines the equivalence between Dolibarr and Alixar variables to be able
     * to use them where appropriate.
     */
    private const VARS = [
        'main' => [
            'app_basepath' => 'main_document_root',
            'app_basepath_alt' => 'main_document_root_alt',
            'app_basepath_documents' => 'main_data_root',
            'app_url' => 'main_url_root',
            'app_url_alt' => 'main_url_root_alt',
        ],
        'db' => [
            'db_host' => 'main_db_host',
            'db_port' => 'main_db_port',
            'db_database' => 'main_db_name',
            'db_prefix' => 'main_db_prefix',
            'db_username' => 'main_db_user',
            'db_password' => 'main_db_pass',
            'db_connection' => 'main_db_type',
            'db_charset' => 'main_db_character_set',
            'db_collation' => 'main_db_collation',
        ],
        'login' => [
            'app_main_authentication' => 'main_authentication',
            // 'app_main_demo' => 'main_demo',
            'app_main_prod' => 'main_prod',
        ],
        'security' => [
            'app_force_https' => 'main_force_https',
            'app_restrict_os_commands' => 'main_restrict_os_commands',
            'app_nocsrfcheck' => 'nocsrfcheck',
            'app_unique_id' => 'main_instance_unique_id',
            'app_mailing_limit_sendbyweb' => 'mailing_limit_sendbyweb',
            'app_mailing_limit_sendbycli' => 'mailing_limit_sendbycli',
            'app_main_distribution' => 'main_distrib',
        ],
        // 'EXTERNAL_LIBRARIES' => [
        // '' => 'lib_FPDF_PATH',
        // '' => 'lib_TCPDF_PATH',
        // '' => 'lib_FPDI_PATH',
        // '' => 'lib_TCPDI_PATH',
        // '' => 'lib_GEOIP_PATH',
        // '' => 'lib_NUSOAP_PATH',
        // '' => 'lib_ODTPHP_PATH',
        // '' => 'lib_ODTPHP_PATHTOPCLZIP',
        // '' => 'js_CKEDITOR',
        // '' => 'js_JQUERY',
        // '' => 'js_JQUERY_UI',
        // '' => 'font_DOL_DEFAULT_TTF',
        // '' => 'font_DOL_DEFAULT_TTF_BOLD',
        // ],

    ];

    /**
     * Contains the contents of the config.yaml configuration file
     *
     * @var \stdClass
     */
    private static $config;

    /**
     * Reset the $config attribute.
     */
    public static function resetConfig()
    {
        static::$config = null;
    }

    /**
     * Returns the config.yaml complete path.
     *
     * @return string
     */
    public static function getConfigFilename()
    {
        $directory = realpath(BASE_PATH . '/..');
        return $directory . '/config.yaml';
    }

    /**
     * Returns the Dolibarr conf.php complete path.
     *
     * @return string
     * @deprecated Maintained for compatibility with Dolibarr.
     */
    public static function getDolibarrConfigFilename()
    {
        return BASE_PATH . '/conf/conf.php';
    }

    /**
     * Returns the Dolibarr install.forced.php config filename complete path.
     *
     * @return string
     * @deprecated Maintained for compatibility with Dolibarr.
     */
    public static function getDolibarrForcedConfigFilename()
    {
        return BASE_PATH . '/conf/install.forced.php';
    }

    /**
     * Save the config in the conf/conf.php Dolibarr configuration file.
     *
     * @param $config
     */
    private static function saveDolibarrConfig($config)
    {
        $fp = fopen(static::getDolibarrConfigFilename(), "w");
        if ($fp) {
            clearstatcache();
            fwrite($fp, "<?php\n\n");
            foreach ($config as $section => $block) {
                fwrite($fp, "// Section: $section\n\n");
                foreach ($block as $var => $value) {
                    if (!isset(static::VARS[$section][$var])) {
                        continue;
                    }

                    // Other
                    fwrite($fp, '$dolibarr_' . static::VARS[$section][$var] . '=\'' . Functions::dol_escape_php(trim($value), 1) . '\';');
                    fwrite($fp, "\n");
                }
                fwrite($fp, "\n");
            }
            fclose($fp);
        }
    }

    /**
     * Add the $params data to the config.yaml file.
     * If something has been modified in the DB section,
     * a reconnection is made to the database.
     * Returns the the DB connection or false if error.
     *
     * @param $params
     *
     * @return \Alxarafe\DB\DB|false
     *
     */
    public static function saveParams($params)
    {
        $filename = static::getConfigFilename();
        $config = file_exists($filename) ? Yaml::parseFile($filename) : [];
        foreach ($params as $section => $block) {
            foreach ($block as $var => $value) {
                $config[$section][$var] = $value;
            }
        }

        if (file_put_contents(static::getConfigFilename(), Yaml::dump($config)) === false) {
            return false;
        }

        static::saveDolibarrConfig($config);

        if (!isset($params['db'])) {
            return Globals::getDb();
        }

        Globals::resetConfig();
        Globals::loadConfig();
        return Globals::getDb();
    }

    /**
     * Reads the Dolibarr configuration file and passes it to the new version of
     * yaml configuration file.
     *
     * @param $yamlFile
     * @param $confFile
     *
     * @return array|null
     */
    private static function migrateConfig($yamlFile, $confFile)
    {
        include $confFile;

        $data = [];
        foreach (static::VARS as $section => $block) {
            $data[$section] = [];
            foreach ($block as $envName => $_var) {
                $var = 'dolibarr_' . $_var;
                if (isset($$var)) {
                    $data[$section][$envName] = $$var;
                }
            }
        }

        if (file_put_contents($yamlFile, Yaml::dump($data)) === false) {
            return null;
        }

        return $data;
    }

    /**
     * Reads the Dolibarr forced configuration file and passes it to the new version
     * of the yaml configuration file.
     *
     * TODO: It has not been implemented yet
     *
     * @param $yamlFile
     * @param $confFile
     *
     * @return array|null
     */
    private static function migrateForced($yamlFile, $confFile)
    {
        // TODO: It has not been implemented yet
    }

    /**
     * Read the config.yaml configuration file.
     * If the file does not exist, try creating it from the Dolibarr
     * configuration files.
     *
     * @return array|\stdClass|void|null
     */
    public static function loadConfig()
    {
        if (isset(static::$config)) {
            return static::$config;
        }

        $yamlConfigFile = static::getConfigFilename();
        if (file_exists($yamlConfigFile)) {
            static::$config = Yaml::parseFile($yamlConfigFile, Yaml::PARSE_OBJECT_FOR_MAP);
            return static::$config;
        }

        $dolibarrConfigFile = static::getDolibarrConfigFilename();
        if (file_exists($dolibarrConfigFile)) {
            return static::migrateConfig($yamlConfigFile, $dolibarrConfigFile);
        }

        $dolibarrConfigFile = static::getDolibarrForcedConfigFilename();
        if (file_exists($dolibarrConfigFile)) {
            return static::migrateForced($yamlConfigFile, $dolibarrConfigFile);
        }
    }
}
