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

namespace Alxarafe\Base;

use DoliCore\Base\Config;
use HookManager;
use stdClass;
use Translate;
use User;

require_once BASE_PATH . '/core/class/hookmanager.class.php';
require_once BASE_PATH . '/core/class/translate.class.php';
require_once BASE_PATH . '/core/lib/functions.lib.php';
require_once BASE_PATH . '/user/class/user.class.php';

abstract class Globals
{
    /**
     * Contains the information from the conf.php file.
     *
     * @var null|stdClass
     */
    protected static $config;

    protected static $db;

    protected static $hookManager;

    protected static $langs;
    protected static $user;
    protected static $menumanager;


    public static function getConfig($conf)
    {
        if (empty(static::$config)) {
            static::$config = Config::loadConfig();
        }

        return static::$config;
    }

    public static function setConfigValues($conf, $db)
    {
        // Here we read database (llx_const table) and define conf var $conf->global->XXX.
        //print "We work with data into entity instance number '".$conf->entity."'";
        $conf->setValues($db);
    }

    public static function getDb($conf)
    {
        if (empty(static::$db)) {
            static::$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);
        }
        return static::$db;
    }

    public static function getHookManager()
    {
        if (empty(static::$hookManager)) {
            static::$hookManager = new HookManager(static::$db);
        }
        return static::$hookManager;
    }

    public static function getLangs($conf)
    {
        if (empty(static::$langs)) {
            static::$langs = new Translate('', $conf);
        }
        return static::$langs;
    }

    public static function getUser()
    {
        if (empty(static::$user)) {
            static::$user = new User(static::$db);
        }
        return static::$user;
    }

    public static function getMenuManager($conf)
    {
        if (empty(static::$menumanager)) {
            static::$menumanager = Config::getMenuManager($conf);
        }
        return static::$menumanager;
    }
}
