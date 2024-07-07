<?php

/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin           <regis.houssin@inodbox.com>
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

namespace DoliModules\Admin\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;


// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';

use DoliCore\Base\Controller\DolibarrController;

class AdminMenuController extends DolibarrController
{
    /**
     *      \file       htdocs/adherents/type.php
     *      \ingroup    member
     *      \brief      Member's type setup
     */
    public function index($executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');

// Load translation files required by the page
        $langs->loadLangs(["companies", "products", "admin", "users", "other"]);

// Security check
        if (!$user->admin) {
            accessforbidden();
        }

        $dirstandard = [];
        $dirsmartphone = [];
        $dirmenus = array_merge(["/core/menus/"], (array) $conf->modules_parts['menus']);
        foreach ($dirmenus as $dirmenu) {
            $dirstandard[] = $dirmenu . 'standard';
            $dirsmartphone[] = $dirmenu . 'smartphone';
        }

        $error = 0;

// This can be a big page.  The execution time limit is increased.
// This setting can only be changed when the 'safe_mode' is inactive.
        $err = error_reporting();
        error_reporting(0); // Disable all errors
//error_reporting(E_ALL);
        @set_time_limit(300); // Need more than 240 on Windows 7/64
        error_reporting($err);


        /*
         * Actions
         */

        if ($action == 'update' && !$cancel) {
            $_SESSION["mainmenu"] = "home"; // The menu manager may have changed

            dolibarr_set_const($db, "MAIN_MENU_STANDARD", GETPOST('MAIN_MENU_STANDARD', 'alpha'), 'chaine', 0, '', $conf->entity);
            dolibarr_set_const($db, "MAIN_MENU_SMARTPHONE", GETPOST('MAIN_MENU_SMARTPHONE', 'alpha'), 'chaine', 0, '', $conf->entity);

            dolibarr_set_const($db, "MAIN_MENUFRONT_STANDARD", GETPOST('MAIN_MENUFRONT_STANDARD', 'alpha'), 'chaine', 0, '', $conf->entity);
            dolibarr_set_const($db, "MAIN_MENUFRONT_SMARTPHONE", GETPOST('MAIN_MENUFRONT_SMARTPHONE', 'alpha'), 'chaine', 0, '', $conf->entity);

            // Define list of menu handlers to initialize
            $listofmenuhandler = [];
            $listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENU_STANDARD', 'alpha'))] = 1;
            $listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENUFRONT_STANDARD', 'alpha'))] = 1;
            if (GETPOST('MAIN_MENU_SMARTPHONE', 'alpha')) {
                $listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENU_SMARTPHONE', 'alpha'))] = 1;
            }
            if (GETPOST('MAIN_MENUFRONT_SMARTPHONE', 'alpha')) {
                $listofmenuhandler[preg_replace('/(_backoffice|_frontoffice|_menu)?\.php/i', '', GETPOST('MAIN_MENUFRONT_SMARTPHONE', 'alpha'))] = 1;
            }

            // Initialize menu handlers
            foreach ($listofmenuhandler as $key => $val) {
                // Load sql init_menu_handler.sql file
                $dirmenus = array_merge(["/core/menus/"], (array) $conf->modules_parts['menus']);
                foreach ($dirmenus as $dirmenu) {
                    $file = 'init_menu_' . $key . '.sql';
                    $fullpath = dol_buildpath($dirmenu . $file);
                    //print 'action='.$action.' Search menu into fullpath='.$fullpath.'<br>';exit;

                    if (file_exists($fullpath)) {
                        $db->begin();

                        $result = run_sql($fullpath, 1, '', 1, $key, 'none');
                        if ($result > 0) {
                            $db->commit();
                        } else {
                            $error++;
                            setEventMessages($langs->trans("FailedToInitializeMenu") . ' ' . $key, null, 'errors');
                            $db->rollback();
                        }
                    }
                }
            }

            if (!$error) {
                $db->close();

                // We make a header redirect because we need to change menu NOW.
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Admin/Views/admin_menu.php');

        $db->close();

        return true;
    }
}
