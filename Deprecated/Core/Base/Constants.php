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

/**
 * Defines Dolibarr constants.
 *
 * @deprecated This class is only needed for compatibility with Dolibarr.
 */
abstract class Constants
{
    public static function defineIfNotExists($name, $value)
    {
        if (!defined($name) && isset($value)) {
            define($name, $value);
        }
    }

    public static function define($config)
    {
        static::defineIfNotExists('DOL_DATA_ROOT', $config->main->data_path);
        static::defineIfNotExists('MAIN_DB_PREFIX', $config->db->prefix);
    }
}
