<?php

/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
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

use DoliCore\Base\Controller\DolibarrController;
use DoliCore\Form\Form;
use DoliCore\Lib\ExtraFields;
use http\Encoding\Stream;

/**
 *   \file       htdocs/bookcal/admin/availabilities_extrafields.php
 *   \ingroup    bookcal
 *   \brief      Page to setup extra fields of availabilities
 */

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BookCal/Lib/BookCal.php';

class BookCalAdminAvailabilitiesExtrafieldsController extends DolibarrController
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
        $langs->loadLangs(['agenda', 'admin']);

        $extrafields = new ExtraFields($db);
        $form = new Form($db);

// List of supported format
        $tmptype2label = ExtraFields::$type2label;
        $type2label = [''];
        foreach ($tmptype2label as $key => $val) {
            $type2label[$key] = $langs->transnoentitiesnoconv($val);
        }

        $action = GETPOST('action', 'aZ09');
        $attrname = GETPOST('attrname', 'alpha');
        $elementtype = 'bookcal_availabilities'; //Must be the $table_element of the class that manage extrafield

        if (!$user->admin) {
            accessforbidden();
        }


        /*
         * Actions
         */

        require DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/admin_availabilities_extrafields.php');

        $db->close();

        return true;
    }
}
