<?php

/* Copyright (C) 2007-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2023       Alice Adminson          <aadminson@example.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
use DoliCore\Lib\ExtraFields;
use DoliModules\BookCal\Model\Calendar;

/**
 *  \file       htdocs/bookcal/calendar_note.php
 *  \ingroup    bookcal
 *  \brief      Tab for notes on Calendar
 */

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BookCal/Lib/BookCalCalendar.php';

class BookCalCalendarNoteController extends DolibarrController
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
        $langs->loadLangs(["agenda", "companies"]);

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
        $object = new Calendar($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->bookcal->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(['calendarnote', 'globalcard']); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->bookcal->multidir_output[empty($object->entity) ? $conf->entity : $object->entity] . "/" . $object->id;
        }


// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
        $enablepermissioncheck = 0;
        if ($enablepermissioncheck) {
            $permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
            $permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write');
            $permissionnote = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_setnotes.inc.php
        } else {
            $permissiontoread = 1;
            $permissiontoadd = 1;
            $permissionnote = 1;
        }

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object->id, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled("bookcal")) {
            accessforbidden();
        }
        if (!$permissiontoread) {
            accessforbidden();
        }


        /*
         * Actions
         */

        $parameters = [];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }
        if (empty($reshook)) {
            include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not include_once
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/calendar_note.php');

        $db->close();

        return true;
    }
}
