<?php

/* Copyright (C) 2011-2015 Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2021      Laurent Destailleur <eldy@users.sourceforge.net>
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

/**
 *       \file       htdocs/core/ajax/constantonoff.php
 *       \brief      File to set or del an on/off constant
 */

if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
defineIfNotDefined('NOREQUIREMENU', '1');
defineIfNotDefined('NOREQUIREHTML', '1');
defineIfNotDefined('NOREQUIREAJAX', '1');
defineIfNotDefined('NOREQUIRESOC', '1');
defineIfNotDefined('NOREQUIRETRAN', '1');
if (!defined('CSRFCHECK_WITH_TOKEN')) {
    define('CSRFCHECK_WITH_TOKEN', '1'); // Token is required even in GET mode
}

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';

$action = GETPOST('action', 'aZ09'); // set or del
$name = GETPOST('name', 'alpha');
$entity = GETPOSTINT('entity');
$value = (GETPOST('value', 'aZ09') != '' ? GETPOST('value', 'aZ09') : 1);

// Security check
if (empty($user->admin)) {
    httponly_accessforbidden('This ajax component can be called by admin user only');
}


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Registering the new value of constant
if (!empty($action) && !empty($name)) {
    if ($action == 'set') {
        dolibarr_set_const($db, $name, $value, 'chaine', 0, '', $entity);
    } elseif ($action == 'del') {
        dolibarr_del_const($db, $name, $entity);
    }
} else {
    http_response_code(403);
}
