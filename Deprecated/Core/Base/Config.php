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

use DoliCore\Tools\Debug;
use Conf;
use DoliCore\Lib\TraceableDB;
use DoliDB;
use DoliModules\User\Model\User;
use HookManager;
use Illuminate\Database\Capsule\Manager as DB;
use MenuManager;
use stdClass;
use Translate;

require_once BASE_PATH . '/core/class/conf.class.php';
require_once BASE_PATH . '/core/class/hookmanager.class.php';
require_once BASE_PATH . '/core/class/translate.class.php';
require_once BASE_PATH . '/core/lib/functions.lib.php';

/**
 * Generate an object with the configuration of the Dolibarr conf.php file.
 *
 * @info https://wiki.dolibarr.org/index.php/Configuration_file
 *
 * This class is only needed for compatibility with Dolibarr.
 *
 * @package DoliCore\Base
 */
abstract class Config
{
    const DEFAULT_DB_PREFIX = 'alx_';

    /**
     * Contains the information of the old $conf global var.
     *
     * Config::getConf() can be used at any point to retrieve the contents of the
     * $conf variable used globally by Dolibarr.
     *
     * The content of the variable is saved with the first call and this copy is
     * returned. If it is necessary to regenerate it, the parameter true can be
     * passed to it.
     *
     * @var null|Conf
     *
     * @deprecated Use $config instead
     */
    private static $dolibarrConfig = null;

    /**
     * Contains the information from the conf.php file in a normalized stdClass.
     *
     * The objective is to move what is really needed to this object and update the
     * configuration file to a data file outside of public space.
     *
     * @var null|stdClass
     */
    private static $config = null;

    /**
     * Contains a DoliDB connection.
     *
     * @var DoliDB, null
     */
    private static $db;

    /**
     * Contains a HookManager class.
     *
     * @var $hookManager
     */
    private static $hookManager;

    /**
     * Contains a Translate class
     *
     * @var Translate
     */
    private static $langs;

    /**
     * Contains a User class instance.
     *
     * @var User
     */
    private static $user;

    private static $menumanager;

    /**
     * Load the configuration file and return the content that the $conf variable
     * used globally by Dolibarr should have.
     *
     * @return Conf|null
     *
     * @deprecated Use loadConfig() instead!
     */
    private static function loadConf()
    {
        $filename = static::getDolibarrConfigFilename();
        $exists = file_exists($filename) && is_readable($filename);
        if ($exists) {
            include $filename;
        }

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
        $conf->db->type = $dolibarr_main_db_type ?? 'mysqli';
        $conf->db->prefix = $dolibarr_main_db_prefix ?? 'alx_';
        $conf->db->character_set = $dolibarr_main_db_character_set ?? 'utf8';
        $conf->db->dolibarr_main_db_collation = $dolibarr_main_db_collation ?? 'utf8-unicode-ci';
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
        $conf->file->dol_main_url_root = $dolibarr_main_url_root ?? BASE_URL;   // Define url inside the config file
        $conf->file->dol_document_root = ['main' => (string) DOL_DOCUMENT_ROOT]; // Define array of document root directories ('/home/htdocs')
        $conf->file->dol_url_root = ['main' => (string) DOL_URL_ROOT]; // Define array of url root path ('' or '/dolibarr')
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

        $conf->file->dol_data_root = $dolibarr_main_data_root ?? static::getDataDir(BASE_PATH);

        $conf->debug = intval($dolibarr_main_prod ?? 1) === 0;

        // Load the main includes of common libraries
        if (!defined('NOREQUIRETRAN')) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/translate.class.php';
        }

        static::$dolibarrConfig = $conf;
        return $conf;
    }

    /**
     * Returns a normalized config file.
     *
     * @return stdClass|null
     */
    private static function loadConfig()
    {
        $conf = static::loadConf();
        if (empty($conf)) {
            return null;
        }

        $config = new stdClass();

        // 'main' section
        $config->main = new stdClass();
        $config->main->base_path = $conf->file->dol_document_root['main'] ?? constant('BASE_PATH');
        $config->main->base_url = $conf->file->dol_main_url_root ?? constant('BASE_URL');
        $config->main->data_path = $conf->file->dol_data_root ?? '';

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
        $config->db = $conf->db;
        $config->db->charset = $conf->db->character_set;
        $config->db->collation = $conf->db->dolibarr_main_db_collation;

        // 'security' section
        $config->security = new stdClass();
        $config->security->authentication_type = $conf->file->main_authentication;
        $config->security->force_https = $conf->file->main_force_https;
        $config->security->unique_id = $conf->file->instance_unique_id;

        $config->file = $conf->file;

        // Others
        $demo = $dolibarr_main_demo ?? false;
        if ($demo !== false) {
            $credentials = explode(',', $demo);
            if (count($credentials) === 2) {
                $config->demo->user = trim($credentials[0]);
                $config->demo->pass = trim($credentials[1]);
            }
        }

        $config->debug = $conf->debug;

        // 'Server' section
        $config->server = new stdClass();
        $config->server->detailed_info = !empty($_SERVER['MAIN_SHOW_TUNING_INFO']);

        static::$dolibarrConfig = $conf;
        static::$config = $config;

        return $config;
    }

    /**
     * Returns a Dolibarr DB connection (DoliDB) instance.
     *
     * @return DoliDb
     * @throws \Exception
     */
    private static function loadDb()
    {
        $conf = static::$dolibarrConfig;
        static::$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);
        static::$dolibarrConfig->setValues(static::$db);
        return static::$db;
    }

    /**
     * Returns a HookManager class instance.
     *
     * @return mixed
     */
    private static function loadHookManager()
    {
        static::$hookManager = new HookManager(static::$db);
        return static::$hookManager;
    }

    /**
     * Returns a Translate class instance.
     *
     * @return Translate
     */
    private static function loadLangs()
    {
        static::$langs = new Translate('', static::$dolibarrConfig);
        return static::$langs;
    }

    private static function loadUser()
    {
        static::$user = new User(static::$db);
        return static::$user;
    }

    private static function loadMenuManager()
    {
        $conf = static::$dolibarrConfig;
        $db = static::$db;

        $menumanager = null;
        if (!defined('NOREQUIREMENU')) {
            if (empty($user->socid)) {    // If internal user or not defined
                $conf->standard_menu = (!getDolGlobalString('MAIN_MENU_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENU_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENU_STANDARD) : $conf->global->MAIN_MENU_STANDARD_FORCED);
            } else {
                // If external user
                $conf->standard_menu = (!getDolGlobalString('MAIN_MENUFRONT_STANDARD_FORCED') ? (!getDolGlobalString('MAIN_MENUFRONT_STANDARD') ? 'eldy_menu.php' : $conf->global->MAIN_MENUFRONT_STANDARD) : $conf->global->MAIN_MENUFRONT_STANDARD_FORCED);
            }

            // Load the menu manager (only if not already done)
            $file_menu = $conf->standard_menu;
            if (GETPOST('menu', 'alpha')) {
                $file_menu = GETPOST('menu', 'alpha'); // example: menu=eldy_menu.php
            }
            if (!class_exists('MenuManager')) {
                $menufound = 0;
                $dirmenus = array_merge(["/core/menus/"], (array) $conf->modules_parts['menus']);
                foreach ($dirmenus as $dirmenu) {
                    $menufound = dol_include_once($dirmenu . "standard/" . $file_menu);
                    if (class_exists('MenuManager')) {
                        break;
                    }
                }
                if (!class_exists('MenuManager')) { // If failed to include, we try with standard eldy_menu.php
                    dol_syslog("You define a menu manager '" . $file_menu . "' that can not be loaded.", LOG_WARNING);
                    $file_menu = 'eldy_menu.php';
                    include_once DOL_DOCUMENT_ROOT . "/core/menus/standard/" . $file_menu;
                }
            }
            $menumanager = new MenuManager($db, empty($user->socid) ? 0 : 1);
            $menumanager->loadMenu();
        }

        static::$menumanager = $menumanager;
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
     * Load the Dolibarr configuration file and enter the content for the Dolibarr global
     * variable $conf.
     *
     * The result is cached for future queries. If we want to reload the configuration file
     * we will have to pass the parameter true.
     *
     * @param $reload
     *
     * @return stdClass|null
     */
    public static function getConf($reload = false): ?stdClass
    {
        if ($reload || !isset(static::$dolibarrConfig)) {
            static::$dolibarrConfig = static::loadConf();
        }

        return static::$dolibarrConfig;
    }

    /**
     * Returns a stdClass with the information contained in the conf.php file.
     *
     * @param $reload
     *
     * @return stdClass|null
     */
    public static function getConfig($reload = false): ?stdClass
    {
        if ($reload || !isset(static::$config)) {
            static::$config = static::loadConfig();
        }

        return static::$config;
    }

    /**
     * Returns a DoliDB connection instance.
     *
     * @return DoliDB|null
     */
    public static function getDb(): ?DoliDB
    {
        return static::$db;
    }

    /**
     * Fills in the additional data of the $conf variable, taken once the database
     * is initialized
     *
     * @param $conf
     */
    public static function setConfigValues($conf)
    {
        // Here we read database (llx_const table) and define conf var $conf->global->XXX.
        // print "We work with data into entity instance number '".$conf->entity."'";
        $conf->setValues(static::$db);
    }

    /**
     * Returns a TraceableDB connection instance.
     *
     * @return TraceableDB|null
     */
    public static function debugDb(): ?TraceableDB
    {
        if (isModEnabled('debugbar')) {
            static::$db = new TraceableDB(static::$db);
        }
        return static::$db;
    }

    /**
     * Returns a HookManager class instance.
     *
     * @return HookManager|null
     */
    public static function getHookManager(): ?HookManager
    {
        if (empty(static::$hookManager)) {
            static::$hookManager = static::loadHookManager();
        }
        return static::$hookManager;
    }

    /**
     * Returns a Translate class instance.
     *
     * @return Translate|null
     */
    public static function getLangs(): ?Translate
    {
        if (empty(static::$langs)) {
            static::$langs = static::loadLangs();
        }
        return static::$langs;
    }

    /**
     * Returns a User class instance.
     *
     * @return User|null
     */
    public static function getUser(): ?User
    {
        if (empty(static::$user)) {
            static::$user = static::getUser();
        }
        return static::$user;
    }

    public static function getMenuManager($conf)
    {
        if (!empty(static::$menumanager)) {
            static::$menumanager = static::loadMenuManager();
        }
        return static::$menumanager;
    }

    /**
     * Load all Dolibar global variables.
     *
     * @return false|void
     * @throws \DebugBar\DebugBarException
     */
    public static function load()
    {
        global $conf;
        global $config;
        global $db;
        global $hookmanager;
        global $langs;
        global $user;
        global $menumanager;

        $conf = static::$dolibarrConfig = static::loadConf();
        if (empty($conf->db->name ?? '')) {
            return false;
        }

        $config = static::$config = static::loadConfig();
        $db = static::$db = static::loadDb();
        $hookmanager = static::$hookManager = static::loadHookManager();
        $langs = static::$langs = static::loadLangs();
        $user = static::$user = static::loadUser();
        if ($user->id > 0) {
            $menumanager = static::$menumanager = static::loadMenuManager();
        }
        Debug::load();

        new Database($config->db);

        // TODO: Example of calling a SELECT from Eloquent and from Dolibarr
        // DB::select('SELECT * FROM alx_user'); // use Illuminate\Database\Capsule\Manager as DB;
        // $db->query('SELECT * FROM alx_user');
    }
}
