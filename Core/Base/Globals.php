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

use Alxarafe\Lib\Functions;
use Alxarafe\LibClass\Lang;

abstract class Globals
{
    private static $conf;
    private static $lang;
    private static $db;

    public static function init()
    {
        self::$conf = $conf = new Conf();
        self::$lang = new Lang(BASE_PATH, self::$conf);
        self::$lang->setDefaultLang('auto');

        $conf = self::$conf::getConfig();
        if (!empty($conf->main_db_pass)) {
            static::$db = Functions::getDoliDBInstance(
                $conf->main_db_type,
                $conf->main_db_host,
                $conf->main_db_user,
                $conf->main_db_pass,
                $conf->main_db_name,
                (int) $conf->main_db_port
            );
        }
    }

    public static function getConf()
    {
        return static::$conf;
    }

    public static function getLang()
    {
        return static::$lang;
    }

    public static function getDb()
    {
        return static::$db;
    }

}