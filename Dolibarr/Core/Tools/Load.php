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

namespace DoliCore\Tools;

use DoliCore\Base\Config;
use DoliCore\Base\Constants;
use DoliCore\Base\Translate;
use DoliCore\Lib\Conf;
use DoliCore\Lib\HookManager;
use DoliDB;
use DoliModules\Company\Model\Company;
use DoliModules\User\Model\User;
use MenuManager;

/**
 * Class Load
 *
 * This class loads Dolibarr's global variables.
 * Replaces main.inc.php
 *
 * @package DoliCore\Tools
 */
abstract class Load
{
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
    private static $hook_manager;

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

    private static $menu_manager;

    private static $mysoc;

    /**
     * Returns a stdClass with the information contained in the conf.php file.
     *
     * @param $reload
     *
     * @return Conf|null
     */
    public static function getConfig($reload = false): ?Conf
    {
        if ($reload || !isset(self::$config)) {
            self::$config = self::loadConfig();
        }

        return self::$config;
    }

    private static function loadConfig()
    {

        self::$config = Config::loadDolibarrConfig();
        return self::$config;
    }

    /**
     * Returns a HookManager class instance.
     *
     * @return HookManager|null
     */
    public static function getHookManager(): ?HookManager
    {
        if (empty(self::$hook_manager)) {
            self::$hook_manager = self::loadHookManager();
        }
        return self::$hook_manager;
    }

    /**
     * Returns a HookManager class instance.
     *
     * @return mixed
     */
    private static function loadHookManager()
    {
        self::$hook_manager = new HookManager(self::$db);
        return self::$hook_manager;
    }

    /**
     * Returns a DoliDB connection instance.
     *
     * @return DoliDB|null
     */
    public static function getDb(): ?DoliDB
    {
        if (!isset(self::$config)) {
            return null;
        }

        if (!isset(self::$db)) {
            self::$db = self::loadDb();
        }

        return self::$db;
    }

    /**
     * Returns a Dolibarr DB connection (DoliDB) instance.
     *
     * @return DoliDb
     * @throws \Exception
     */
    private static function loadDb()
    {
        $conf = self::$config;
        self::$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int)$conf->db->port);
        self::$config->setValues(self::$db);

        return self::$db;
    }

    /**
     * Returns a Translate class instance.
     *
     * @return Translate|null
     */
    public static function getLangs(): ?Translate
    {
        if (!isset(self::$langs)) {
            self::$langs = self::loadLangs();
        }
        return self::$langs;
    }

    /**
     * Returns a Translate class instance.
     *
     * @return Translate
     */
    private static function loadLangs()
    {
        self::$langs = new Translate('', self::$config);
        return self::$langs;
    }

    /**
     * Returns a User class instance.
     *
     * @return User|null
     */
    public static function getUser(): ?User
    {
        if (!isset(self::$user)) {
            self::$user = self::loadUser();
        }
        return self::$user;
    }

    /**
     * Returns a user class instance
     *
     * @return User
     */
    private static function loadUser()
    {
        self::$user = new User(self::$db);
        return self::$user;
    }

    /**
     * Returns a MenuManager class instance.
     *
     * @return mixed
     */
    public static function getMenuManager()
    {
        if (!isset(self::$menu_manager)) {
            self::$menu_manager = self::loadMenuManager();
        }
        return self::$menu_manager;
    }

    private static function loadMenuManager()
    {
        $conf = self::$config;
        $db = self::$db;
        $user = self::$user;

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
                $dirmenus = array_merge(["/../Dolibarr/Core/Menu/"], (array)$conf->modules_parts['menus']);
                foreach ($dirmenus as $dirmenu) {
                    $menufound = dol_include_once($dirmenu . "standard/" . $file_menu);
                    if (class_exists('MenuManager')) {
                        break;
                    }
                }
                if (!class_exists('MenuManager')) { // If failed to include, we try with standard eldy_menu.php
                    dol_syslog("You define a menu manager '" . $file_menu . "' that can not be loaded.", LOG_WARNING);
                    $file_menu = 'eldy_menu.php';
                    include_once DOL_DOCUMENT_ROOT . "/../Dolibarr/Core/Menu/standard/" . $file_menu;
                }
            }
            $menumanager = new \MenuManager($db, empty($user->socid) ? 0 : 1);
            $menumanager->loadMenu();
        }

        return self::$menu_manager = $menumanager;
    }

    public static function getMySoc()
    {
        if (!isset(self::$db)) {
            return null;
        }

        if (!isset(self::$mysoc)) {
            self::$mysoc = self::loadMySoc();
        }
        return self::$mysoc;
    }

    private static function loadMySoc()
    {
        $mysoc = new Company(self::$db);
        $mysoc->setMysoc(self::$config);

        // We set some specific default values according to country

        if ($mysoc->country_code == 'DE' && !isset(self::$config->global->MAIN_INVERT_SENDER_RECIPIENT)) {
            // For DE, we need to invert our address with customer address
            self::$config->global->MAIN_INVERT_SENDER_RECIPIENT = 1;
        }
        if ($mysoc->country_code == 'FR' && !isset(self::$config->global->INVOICE_CATEGORY_OF_OPERATION)) {
            // For FR, default value of option to show category of operations is on by default. Decret n°2099-1299 2022-10-07
            self::$config->global->INVOICE_CATEGORY_OF_OPERATION = 1;
        }
        if ($mysoc->country_code == 'FR' && !isset(self::$config->global->INVOICE_DISABLE_REPLACEMENT)) {
            // For FR, the replacement invoice type is not allowed.
            // From an accounting point of view, this creates holes in the numbering of the invoice.
            // This is very problematic during a fiscal control.
            self::$config->global->INVOICE_DISABLE_REPLACEMENT = 1;
        }
        if ($mysoc->country_code == 'GR' && !isset(self::$config->global->INVOICE_DISABLE_REPLACEMENT)) {
            // The replacement invoice type is not allowed in Greece.
            self::$config->global->INVOICE_DISABLE_REPLACEMENT = 1;
        }
        if ($mysoc->country_code == 'GR' && !isset(self::$config->global->INVOICE_DISABLE_DEPOSIT)) {
            // The deposit invoice type is not allowed in Greece.
            self::$config->global->INVOICE_DISABLE_DEPOSIT = 1;
        }
        if ($mysoc->country_code == 'GR' && !isset(self::$config->global->INVOICE_CREDIT_NOTE_STANDALONE)) {
            // Standalone credit note is compulsory in Greece.
            self::$config->global->INVOICE_CREDIT_NOTE_STANDALONE = 1;
        }
        if ($mysoc->country_code == 'GR' && !isset(self::$config->global->INVOICE_SUBTYPE_ENABLED)) {
            // Invoice subtype is a requirement for Greece.
            self::$config->global->INVOICE_SUBTYPE_ENABLED = 1;
        }

        if (($mysoc->localtax1_assuj || $mysoc->localtax2_assuj) && !isset(self::$config->global->MAIN_NO_INPUT_PRICE_WITH_TAX)) {
            // For countries using the 2nd or 3rd tax, we disable input/edit of lines using the price including tax (because 2nb and 3rd tax not yet taken into account).
            // Work In Progress to support all taxes into unit price entry when MAIN_UNIT_PRICE_WITH_TAX_IS_FOR_ALL_TAXES is set.
            self::$config->global->MAIN_NO_INPUT_PRICE_WITH_TAX = 1;
        }

        return $mysoc;
    }

}
