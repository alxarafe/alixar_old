<?php
/**
 *  Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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
 */

require_once realpath('../vendor/autoload.php');

use Alxarafe\Base\Globals;
use Alxarafe\Deprecated\Constants;

/**
 * Obtains main url
 *
 * @return string
 */
function get_url()
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

const BASE_PATH = __DIR__;
define('BASE_URL', get_url());

/**
 * @deprecated Use BASE_PATH instead.
 */
const DOL_DOCUMENT_ROOT = BASE_PATH;

/**
 * @deprecated Use BASE_URL instead.
 */
const DOL_URL_ROOT = BASE_URL;

/**
 * @deprecated Use $config instead
 */
$conf = \Alxarafe\Deprecated\Config::loadConf();

$db = Globals::getDb($conf);
$config = Globals::getConfig($conf);
Constants::define($config);

$hookmanager = Globals::getHookManager();
$langs = Globals::getLangs($conf);
$user = Globals::getUser();
$menumanager = Globals::getMenuManager($conf);

Globals::setConfigValues($conf, $db);

const GET_ROUTE_VAR = 'url_route';
const GET_FILENAME_VAR = 'url_filename';
const GET_API_VAR = 'api_route';

$page = filter_input(INPUT_GET, GET_ROUTE_VAR);
$ctrl = filter_input(INPUT_GET, GET_FILENAME_VAR);
$api = filter_input(INPUT_GET, GET_API_VAR);

if (empty($page) && empty($ctrl)) {
    require BASE_PATH . DIRECTORY_SEPARATOR . 'index_dol.php';
    die();
}

chdir(BASE_PATH . DIRECTORY_SEPARATOR . $page);

$_SERVER['PHP_SELF'] = DIRECTORY_SEPARATOR . $page . DIRECTORY_SEPARATOR . $ctrl . '.php';
if (!empty($api)) {
    $_SERVER['PHP_SELF'] = DIRECTORY_SEPARATOR . $page . DIRECTORY_SEPARATOR . $ctrl . '.php' . DIRECTORY_SEPARATOR . $api;
}

$path = BASE_PATH . DIRECTORY_SEPARATOR . $page . DIRECTORY_SEPARATOR . $ctrl . '.php';
require $path;
