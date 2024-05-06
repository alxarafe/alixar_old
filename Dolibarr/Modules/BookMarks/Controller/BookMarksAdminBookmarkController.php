<?php

/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009  Laurent Destailleur     <eldy@users.sourceforge.org>
 * Copyright (C) 2011-2012  Juanjo Menent           <jmenent@2byte.es>
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

namespace DoliModules\BookMarks\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

use DoliCore\Base\DolibarrController;


/**     \file       htdocs/bookmarks/admin/bookmark.php
 *      \ingroup    bookmark
 *      \brief      Page to setup bookmark module
 */

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';

class BookMarksAdminBookmarkController extends DolibarrController
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
        $langs->load("admin");

        if (!$user->admin) {
            accessforbidden();
        }

        $action = GETPOST('action', 'aZ09');

        if ($action == 'setvalue') {
            $showmenu = GETPOST('BOOKMARKS_SHOW_IN_MENU', 'alpha');
            $res = dolibarr_set_const($db, "BOOKMARKS_SHOW_IN_MENU", $showmenu, 'chaine', 0, '', $conf->entity);

            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                $db->commit();
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                $db->rollback();
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookMarks/Views/admin_bookmark.php');

        $db->close();

        return true;
    }
}
