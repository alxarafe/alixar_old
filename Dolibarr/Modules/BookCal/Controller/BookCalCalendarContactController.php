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

/**
 *  \file       htdocs/bookcal/calendar_contact.php
 *  \ingroup    bookcal
 *  \brief      Tab for contacts linked to Calendar
 */

// Load Dolibarr environment
use DoliCore\Base\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliModules\BookCal\Model\Calendar;

require BASE_PATH . '/main.inc.php';

dol_include_once('/bookcal/class/calendar.class.php');
dol_include_once('/bookcal/lib/bookcal_calendar.lib.php');

class BookCalCalendarContactController extends DolibarrController
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
        $langs->loadLangs(["agenda", "companies", "other", "mails"]);

        $id = (GETPOST('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
        $ref = GETPOST('ref', 'alpha');
        $lineid = GETPOSTINT('lineid');
        $socid = GETPOSTINT('socid');
        $action = GETPOST('action', 'aZ09');

// Initialize technical objects
        $object = new Calendar($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->bookcal->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(['calendarcontact', 'globalcard']); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
        $enablepermissioncheck = 0;
        if ($enablepermissioncheck) {
            $permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
            $permission = $user->hasRight('bookcal', 'calendar', 'write');
        } else {
            $permissiontoread = 1;
            $permission = 1;
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
         * Add a new contact
         */

        if ($action == 'addcontact' && $permission) {
            $contactid = (GETPOST('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
            $typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
            $result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));

            if ($result >= 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
                exit;
            } else {
                if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                    $langs->load("errors");
                    setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }
        } elseif ($action == 'swapstatut' && $permission) {
            // Toggle the status of a contact
            $result = $object->swapContactStatus(GETPOSTINT('ligne'));
        } elseif ($action == 'deletecontact' && $permission) {
            // Deletes a contact
            $result = $object->delete_contact($lineid);

            if ($result >= 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $object->id);
                exit;
            } else {
                dol_print_error($db);
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/calendar_contact.php');

        $db->close();

        return true;
    }
}
