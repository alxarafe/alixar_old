<?php

/* Copyright (C) 2017       Laurent Destailleur     <eldy@users.sourceforge.net>
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

use DoliCore\Base\Controller\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliModules\BookCal\Model\Calendar;

/**
 *      \file       htdocs/bookcal/calendar_card.php
 *      \ingroup    bookcal
 *      \brief      Page to create/edit/view calendar
 */

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BookCal/Lib/BookCalCalendar.php';

class BookCalCalendarCardController extends DolibarrController
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
        $lineid = GETPOSTINT('lineid');

        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $cancel = GETPOST('cancel', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');                   // if not set, a default page will be used
        $backtopageforcancel = GETPOST('backtopageforcancel', 'alpha'); // if not set, $backtopage will be used
        $backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
        $dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

        if (!empty($backtopagejsfields)) {
            $tmpbacktopagejsfields = explode(':', $backtopagejsfields);
            $dol_openinpopup = $tmpbacktopagejsfields[0];
        }

// Initialize technical objects
        $object = new Calendar($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->bookcal->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(['calendarcard', 'globalcard']); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
        $search_all = GETPOST("search_all", 'alpha');
        $search = [];
        foreach ($object->fields as $key => $val) {
            if (GETPOST('search_' . $key, 'alpha')) {
                $search[$key] = GETPOST('search_' . $key, 'alpha');
            }
        }

        if (empty($action) && empty($id) && empty($ref)) {
            $action = 'view';
        }

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
        $enablepermissioncheck = 0;
        if ($enablepermissioncheck) {
            $permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
            $permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
            $permissiontodelete = $user->hasRight('bookcal', 'calendar', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
            $permissionnote = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_setnotes.inc.php
            $permissiondellink = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_dellink.inc.php
        } else {
            $permissiontoread = 1;
            $permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
            $permissiontodelete = 1;
            $permissionnote = 1;
            $permissiondellink = 1;
        }

        $upload_dir = $conf->bookcal->multidir_output[isset($object->entity) ? $object->entity : 1] . '/calendar';

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (isset($object->status) && ($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->module, $object, $object->table_element, $object->element, 'fk_soc', 'rowid', $isdraft);
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
            $error = 0;

            $backurlforlist = dol_buildpath('/bookcal/calendar_list.php', 1);

            if (empty($backtopage) || ($cancel && empty($id))) {
                if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
                    if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                        $backtopage = $backurlforlist;
                    } else {
                        $backtopage = dol_buildpath('/bookcal/calendar_card.php', 1) . '?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
                    }
                }
            }

            $triggermodname = 'BOOKCAL_MYOBJECT_MODIFY'; // Name of trigger action code to execute when we modify record

            // Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
            include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

            // Actions when linking object each other
            include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

            // Actions when printing a doc from card
            include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

            // Action to move up and down lines of object
            //include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

            // Action to build doc
            include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

            if ($action == 'set_thirdparty' && $permissiontoadd) {
                $object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
            }
            if ($action == 'classin' && $permissiontoadd) {
                $object->setProject(GETPOSTINT('projectid'));
            }

            // Actions to send emails
            $triggersendname = 'BOOKCAL_MYOBJECT_SENTBYMAIL';
            $autocopy = 'MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
            $trackid = 'calendar' . $object->id;
            include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/calendar_card.php');

        $db->close();

        return true;
    }
}
