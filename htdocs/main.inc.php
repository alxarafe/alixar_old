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

use Alxarafe\Base\Config as BaseConfig;
use DoliCore\Tools\Load;
use DoliLib\DolibarrAuth;

global $conf;
global $config;
global $db;
global $hookmanager;
global $langs;
global $user;
global $menumanager;
global $mysoc; // From master.inc.php

$conf = Load::getConfig();
$config = BaseConfig::getConfig();
$db = Load::getDB();
$hookmanager = Load::getHookManager();
$conf->setValues($db);
$langs = Load::getLangs();
$user = Load::getUser();
$menumanager = Load::getMenuManager();
if (isset($menumanager)) {
    $menumanager->loadMenu();
}
$mysoc = Load::getMySoc();

if (!DolibarrAuth::isLogged()) {
    header('Location: ' . constant('BASE_URL') . '/index.php?module=Auth&controller=Login');
    die();
}

$langs->setDefaultLang($config->main->language);
