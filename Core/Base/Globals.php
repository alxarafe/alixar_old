<?php
/* Copyright (C) 2024 Rafael San José     <rsanjose@alxarafe.com>
 * Copyright (C) 2024 Francesc Pineda     <fpineda@alxarafe.com>
 * Copyright (C) 2024 Cayetano Hernández  <chernandez@alxarafe.com>
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

use Alxarafe\DB\DB;
use Alxarafe\Lib\Functions;
use Alxarafe\LibClass\Lang;

abstract class Globals
{
    const DEFAULT_DB_PREFIX = 'llx_';

    /**
     * Contains all configuration data
     *
     * @var \stdClass
     */
    private static $config;

    /**
     * Contains a Lang instance
     *
     * @var Lang
     */
    private static $lang;

    /**
     * Contains a DB instance
     *
     * @var DB
     */
    private static $db;

    /**
     * Contains a Conf instance
     *
     * @var Conf
     * @deprecated
     */
    private static $conf;

    private static function url($forwarded_host = false)
    {
        $ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
        $proto = strtolower($_SERVER['SERVER_PROTOCOL']);
        $proto = substr($proto, 0, strpos($proto, '/')) . ($ssl ? 's' : '');
        if ($forwarded_host && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } else {
            if (isset($_SERVER['HTTP_HOST'])) {
                $host = $_SERVER['HTTP_HOST'];
            } else {
                $port = $_SERVER['SERVER_PORT'];
                $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
                $host = $_SERVER['SERVER_NAME'] . $port;
            }
        }
        $request = $_SERVER['REQUEST_URI'];
        return $proto . '://' . $host . $request;
    }

    public static function resetConfig()
    {
        self::$conf = $conf = null;
        self::$config = null;
        Config::resetConfig();
        DB::disconnect();
        self::$db = null;
        self::$lang = null;
    }

    public static function loadConfig()
    {
        self::$conf = $conf = new Conf();
        self::$config = Config::loadConfig();
        self::$db = self::dbConnect();
        self::$lang = new Lang(BASE_PATH);
        self::$lang->setDefaultLang('auto');
    }

    public static function init()
    {
        static::loadConfig();

        if (!defined('DOL_APPLICATION_TITLE')) {
            define('DOL_APPLICATION_TITLE', 'Alixar');
        }
        if (!defined('DOL_VERSION')) {
            define('DOL_VERSION', '20.0.0-alpha'); // a.b.c-alpha, a.b.c-beta, a.b.c-rcX or a.b.c
        }

        if (!defined('EURO')) {
            define('EURO', chr(128));
        }


        $url = static::url();
        $pos = strpos($url, '/htdocs/index.php') + strlen('/htdocs');
        $dol_url_root = substr($url, 0, $pos);
        define('DOL_URL_ROOT', $dol_url_root);
    }

    public static function dbConnect()
    {
        static::$db = null;
        if (!isset(self::$config->DB)) {
            return false;
        }
        $db = self::$config->DB;
        return static::$db = Functions::getDoliDBInstance(
            $db->DB_CONNECTION,
            $db->DB_HOST,
            $db->DB_USERNAME,
            $db->DB_PASSWORD,
            $db->DB_DATABASE,
            (int) $db->DB_PORT
        );
    }

    /**
     * Returns Dolibarr config obsolete info
     *
     * @return Conf
     *
     * @deprecated Use new configuration info
     */
    public static function getConf()
    {
        return static::$conf;
    }

    public static function getDolibarrConfig()
    {
        return static::$conf::getConfig();
    }

    /**
     * Returns the configuration info.
     *
     * @return \stdClass
     */
    public static function getConfig()
    {
        return static::$config;
    }

    /**
     * Returns a Lang instance
     *
     * @return Lang
     */
    public static function getLang()
    {
        return static::$lang;
    }

    /**
     * Returns a DB instance
     *
     * @return DB
     */
    public static function getDb()
    {
        return static::$db;
    }

}