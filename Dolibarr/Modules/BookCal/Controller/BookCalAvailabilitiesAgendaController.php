<?php

/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2022       Alice Adminson          <aadminson@example.com>
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
use DoliCore\Lib\ExtraFields;
use DoliModules\BookCal\Model\Availabilities;

/**
 *  \file       htdocs/bookcal/availabilities_agenda.php
 *  \ingroup    bookcal
 *  \brief      Tab of events on Availabilities
 */

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Company.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Functions2.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BookCal/Lib/BookCalAvailabilities.php';

class BookCalAvailabilitiesAgendaController extends DolibarrController
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
        $langs->loadLangs(["agenda", "other"]);

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');

        if (GETPOST('actioncode', 'array')) {
            $actioncode = GETPOST('actioncode', 'array', 3);
            if (!count($actioncode)) {
                $actioncode = '0';
            }
        } else {
            $actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (!getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT') ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
        }

        $search_rowid = GETPOST('search_rowid');
        $search_agenda_label = GETPOST('search_agenda_label');

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
        if (!$sortfield) {
            $sortfield = 'a.datep,a.id';
        }
        if (!$sortorder) {
            $sortorder = 'DESC,DESC';
        }

// Initialize technical objects
        $object = new Availabilities($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->bookcal->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(['availabilitiesagenda', 'globalcard']); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->bookcal->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity] . "/" . $object->id;
        }

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
        $enablepermissioncheck = 0;
        if ($enablepermissioncheck) {
            $permissiontoread = $user->hasRight('bookcal', 'availabilities', 'read');
            $permissiontoadd = $user->hasRight('bookcal', 'availabilities', 'write');
        } else {
            $permissiontoread = 1;
            $permissiontoadd = 1;
        }

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('bookcal')) {
            accessforbidden();
        }
        if (!$permissiontoread) {
            accessforbidden();
        }


        /*
         *  Actions
         */

        $parameters = ['id' => $id];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Cancel
            if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
                header("Location: " . $backtopage);
                exit;
            }

            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $actioncode = '';
                $search_agenda_label = '';
            }
        }


        /*
         *  View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/availabilities_agenda.php');

        $db->close();

        return true;
    }
}
