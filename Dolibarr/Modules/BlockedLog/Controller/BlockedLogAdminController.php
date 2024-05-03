<?php

/* Copyright (C) 2017       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018  Laurent Destailleur     <eldy@destailleur.fr>
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

namespace DoliModules\BlockedLog\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

/**
 *  \file       htdocs/blockedlog/admin/blockedlog.php
 *  \ingroup    blockedlog
 *  \brief      Page setup for blockedlog module
 */

use DoliCore\Base\DolibarrController;

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BlockedLog/Lib/BlockedLog.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';


class BlockedLogAdminController extends DolibarrController
{
    public function blockedlog(bool $executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(['admin', 'blockedlog', 'other']);

// Access Control
        if (!$user->admin || empty($conf->blockedlog->enabled)) {
            accessforbidden();
        }

// Get Parameters
        $action = GETPOST('action', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');
        $withtab = GETPOSTINT('withtab');


        /*
         * Actions
         */

        $reg = [];
        if (preg_match('/set_(.*)/', $action, $reg)) {
            $code = $reg[1];
            $values = GETPOST($code);
            if (is_array($values)) {
                $values = implode(',', $values);
            }

            if (dolibarr_set_const($db, $code, $values, 'chaine', 0, '', $conf->entity) > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . ($withtab ? '?withtab=' . $withtab : ''));
                exit;
            } else {
                dol_print_error($db);
            }
        }

        if (preg_match('/del_(.*)/', $action, $reg)) {
            $code = $reg[1];
            if (dolibarr_del_const($db, $code, 0) > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . ($withtab ? '?withtab=' . $withtab : ''));
                exit;
            } else {
                dol_print_error($db);
            }
        }


        /*
         *	View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BlockedLog/Views/admin_blocked_log.php');

        $db->close();

        return true;
    }
}
