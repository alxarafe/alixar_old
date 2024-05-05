<?php

/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Jean-François Ferry	    <jfefe@aternatik.fr>
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

namespace DoliModules\BookCal\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

use DoliCore\Base\DolibarrController;

/**
 *  \file       htdocs/bookcal/bookcalindex.php
 *  \ingroup    bookcal
 *  \brief      Home page of bookcal top menu
 */

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';

class BookCalBookCalIndexController extends DolibarrController
{

    public function index(bool $executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;


// Load translation files required by the page
        $langs->loadLangs(["agenda"]);

        $action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->hasRight('bookcal', 'myobject', 'read')) {
//  accessforbidden();
// }
        $socid = GETPOSTINT('socid');
        if (isset($user->socid) && $user->socid > 0) {
            $action = '';
            $socid = $user->socid;
        }

        $max = 5;
        $now = dol_now();


        /*
         * Actions
         */

// None


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/bookcalindex.php');

        $db->close();

        return true;
    }
}
