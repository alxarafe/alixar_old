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

use Alxarafe\Deprecated\Config;

abstract class Globals
{
    /**
     * Contains the information from the conf.php file.
     *
     * @var null|stdClass
     */
    private static $config;

    /**
     * @var
     */
    private static $db;

    public static function init()
    {
        static::$db = null;

        static::$config = Config::loadConfig();
        if (static::$config === null) {
            return false;
        }

        static::$db = new \Alxarafe\Base\Database(static::$config->db);
    }

    public static function getConfig()
    {
        return static::$config;
    }

    public static function getDb()
    {
        return static::$db;
    }
}
