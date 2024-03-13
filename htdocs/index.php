<?php

/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2024       Rafael San José         <rsanjose@alxarafe.com>
 * Copyright (C) 2024       Francesc Pineda         <fpineda@alxarafe.com>
 * Copyright (C) 2024       Cayetano Hernández      <chernandez@alxarafe.com>
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

use Alxarafe\Base\Globals;

require_once 'vendor/autoload.php';

const BASE_PATH = __DIR__;

$page = filter_input(INPUT_GET, 'page');
$ctrl = filter_input(INPUT_GET, 'ctrl');

Globals::init();

/**
 * If the configuration file does not exist, the installer is invoked.
 */
$config = Globals::getConfig();
if (empty($ctrl) && !isset($config)) {
    header('Location: index.php?page=Install&ctrl=Install');
    die();
}

/**
 * If no controller has been passed, execution of the original 'index.php' is assumed.
 */
if (empty($ctrl)) {
    require 'index_dol.php';
    die();
}

$pageName = str_replace('/', '\\', $page);
$namespace = 'Alixar\\' . $pageName . '\\' . $ctrl;

$controller = new $namespace();
$controller->view();