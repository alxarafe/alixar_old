<?php

/**
 * Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
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

use Alxarafe\Tools\Dispatcher;
use DoliCore\Base\Config;
use DoliModules\Api\Controller\ApiController;

/**
 * @deprecated It is needed only to display queries to the database using Dolibarr calls.
 */
global $db;

const BASE_PATH = __DIR__;
$autoload_filename = realpath(BASE_PATH . '/../vendor/autoload.php');
if (!file_exists($autoload_filename)) {
    die('<h1>COMPOSER ERROR</h1><p>You need to run: "composer install"</p>');
}

require_once $autoload_filename;

define('BASE_URL', getUrl());

/**
 * @deprecated Use BASE_PATH instead.
 */
const DOL_DOCUMENT_ROOT = BASE_PATH;

/**
 * @deprecated Use BASE_URL instead.
 */
const DOL_URL_ROOT = BASE_URL;

//$conf = Config::loadConfig();

/**
 * @deprecated Necessary for compatibility with Dolibarr
 */
define('DOL_DATA_ROOT', $conf->main->data_path ?? Config::getDataDir(BASE_PATH));


/**
 * Load the configuration file and initialize all the variables of the Config class.
 * At the moment it uses Config in Deprecated, but the functionalities will have to be moved to Core.
 */
try {
    Config::load();
} catch (\DebugBar\DebugBarException $e) {
    dump($e);
    die('loading-error');
}

$conf = Config::getConf()->modules;

/**
 * If a value has been defined for the GET controller variable, an attempt
 * is made to launch the controller.const CONTROLLER_VAR = 'controller';
 */
const MODULE_NAME_VAR = 'module';
const CONTROLLER_VAR = 'controller';
$module = filter_input(INPUT_GET, MODULE_NAME_VAR);
$controller = filter_input(INPUT_GET, CONTROLLER_VAR);
if (isset($module) && isset($controller)) {
	if (Dispatcher::run($module, $controller)) {
        die(); // The controller has been executed succesfully!
    }
}

/**
 * @see htdocs/.htaccess
 */
const GET_ROUTE_VAR = 'url_route';
const GET_FILENAME_VAR = 'url_filename';
const GET_API_VAR = 'api_route';

$page = filter_input(INPUT_GET, GET_ROUTE_VAR);
$ctrl = filter_input(INPUT_GET, GET_FILENAME_VAR);
$api = filter_input(INPUT_GET, GET_API_VAR);

if ($api) {
    new ApiController($api);
    die();
}

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
if (!file_exists($path)) {
    require BASE_PATH . DIRECTORY_SEPARATOR . 'index_dol.php';
    die();
}

require $path;
