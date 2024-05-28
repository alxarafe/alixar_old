<?php

/* Copyright (C) 2002-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2023-2024  Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/bookcal/booking_list.php
 *  \ingroup    bookcal
 *  \brief      Management of direct debit order or credit transfer of invoices
 */

// Load Dolibarr environment
use DoliModules\BookCal\Model\Calendar;

require BASE_PATH . '/main.inc.php';

require_once BASE_PATH . '/../Dolibarr/Lib/Date.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Company.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BookCal/Lib/BookCalCalendar.php';

// load module libraries
require_once __DIR__ . '/class/calendar.class.php';

class BookCalBookingListController extends DolibarrController
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

        $id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
        $ref = GETPOST('ref', 'alpha');
        $socid = GETPOSTINT('socid');
        $action = GETPOST('action', 'aZ09');
        $type = GETPOST('type', 'aZ09');

        $fieldid = (!empty($ref) ? 'ref' : 'rowid');
        if ($user->socid) {
            $socid = $user->socid;
        }

        $moreparam = '';

        $object = new Calendar($db);

// Load object
        if ($id > 0 || !empty($ref)) {
            $ret = $object->fetch($id, $ref);
            $isdraft = (($object->status == Calendar::STATUS_DRAFT) ? 1 : 0);
            if ($ret > 0) {
                $object->fetch_thirdparty();
            }
        }

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

        if (!isModEnabled("bookcal")) {
            accessforbidden();
        }
        if (!$permissiontoread) {
            accessforbidden();
        }

        /*
         * Actions
         */

        $parameters = '';
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookCal/Views/booking_list.php');

        $db->close();

        return true;
    }
}
