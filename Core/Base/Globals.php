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

require_once BASE_PATH . '/core/class/hookmanager.class.php';
require_once BASE_PATH . '/core/class/translate.class.php';
require_once BASE_PATH . '/core/lib/functions.lib.php';

use Alxarafe\Deprecated\Config;
use HookManager;
use stdClass;
use Translate;

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


    public static function getConfig()
    {
        if (empty(static::$config)) {
            static::$config = Config::loadConfig();
        }
        return static::$config;
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
}
