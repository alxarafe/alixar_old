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

namespace DoliCore\Lib;

abstract class Filter
{
    public static function GetPostIsset($paramname)
    {
        $relativepathstring = static::getCleanRelativePath();
        if (!empty($_GET['restore_lastsearch_values'])) {
            return static::checkSessionForParameter($relativepathstring, $paramname);
        }
        return isset($_POST[$paramname]) || isset($_GET[$paramname]);
    }

    public static function getCleanRelativePath()
    {
        $relativepathstring = $_SERVER['PHP_SELF'];
        $relativepathstring = preg_replace('/^' . preg_quote(constant('BASE_URL'), '/') . '/', '', $relativepathstring);
        $relativepathstring = preg_replace('/^\//', '', $relativepathstring);
        $relativepathstring = preg_replace('/^custom\//', '', $relativepathstring);
        return $relativepathstring;
    }

    public static function checkSessionForParameter($relativepathstring, $paramname)
    {
        $sessionKeyBase = 'lastsearch_values_' . $relativepathstring;
        if (!empty($_SESSION[$sessionKeyBase])) {
            $tmp = json_decode($_SESSION[$sessionKeyBase], true);
            if (is_array($tmp) && array_key_exists($paramname, $tmp)) {
                return true;
            }
        }
        return static::checkSpecificSessionKeys($relativepathstring, $paramname);
    }

    public static function checkSpecificSessionKeys($relativepathstring, $paramname)
    {
        if ($paramname == 'contextpage' && !empty($_SESSION['lastsearch_contextpage_' . $relativepathstring])) {
            return true;
        }
        if ($paramname == 'limit' && !empty($_SESSION['lastsearch_limit_' . $relativepathstring])) {
            return true;
        }
        if ($paramname == 'page' && !empty($_SESSION['lastsearch_page_' . $relativepathstring])) {
            return true;
        }
        if ($paramname == 'mode' && !empty($_SESSION['lastsearch_mode_' . $relativepathstring])) {
            return true;
        }
        return false;
    }
}
