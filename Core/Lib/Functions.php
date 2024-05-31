<?php

/* Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * Obtains the main url
 *
 * @return string
 */
function getUrl()
{
    $ssl = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    $proto = strtolower($_SERVER['SERVER_PROTOCOL']);
    $proto = substr($proto, 0, strpos($proto, '/')) . ($ssl ? 's' : '');
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = $_SERVER['HTTP_HOST'];
    } else {
        $port = $_SERVER['SERVER_PORT'];
        $port = ((!$ssl && $port == '80') || ($ssl && $port == '443')) ? '' : ':' . $port;
        $host = $_SERVER['SERVER_NAME'] . $port;
    }

    $script = $_SERVER['SCRIPT_NAME'];

    $script = substr($script, 0, strlen($script) - strlen('/index.php'));
    return $proto . '://' . $host . $script;
}

/**
 * This function is used to obtain the value of a POST variable, and if it does not exist
 * (for example, the first time the form is loaded), take a default value.
 *
 * @param $postVar
 * @param $defaultValue
 * @return mixed
 */
function getIfIsset($postVar, $defaultValue)
{
    $return = filter_input(INPUT_POST, $postVar);
    if ($return === null || $return === false) {
        return $defaultValue;
    }
    return $return;
}
