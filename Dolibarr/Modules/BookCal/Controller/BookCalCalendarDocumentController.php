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
 *  \file       htdocs/bookcal/calendar_document.php
 *  \ingroup    bookcal
 *  \brief      Tab for documents linked to Calendar
 */

// Load Dolibarr environment
use DoliCore\Lib\ExtraFields;
use DoliModules\BookCal\Model\Calendar;

require BASE_PATH . '/main.inc.php';

require_once BASE_PATH . '/../Dolibarr/Lib/Company.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Files.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Images.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BookCal/Lib/BookCalCalendar.php';

class BookCalCalendarDocumentController extends DolibarrController
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


        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm');
        $id = (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));
        $ref = GETPOST('ref', 'alpha');

// Get parameters
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortorder) {
            $sortorder = "ASC";
        }
        if (!$sortfield) {
            $sortfield = "name";
        }
//if (! $sortfield) $sortfield="position_name";

// Initialize technical objects
        $object = new Calendar($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->bookcal->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(['calendardocument', 'globalcard']); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->bookcal->multidir_output[$object->entity ? $object->entity : $conf->entity] . "/calendar/" . get_exdir(0, 0, 0, 1, $object);
        }

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
        $enablepermissioncheck = 0;
        if ($enablepermissioncheck) {
            $permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
            $permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles.inc.php
        } else {
            $permissiontoread = 1;
            $permissiontoadd = 1;
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
        if (empty($object->id)) {
            accessforbidden();
        }


        /*
         * Actions
         */

        include DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/calendar_document.php');

        $db->close();

        return true;
    }
}
