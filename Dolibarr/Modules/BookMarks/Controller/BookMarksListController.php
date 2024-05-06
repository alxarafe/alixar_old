<?php

/* Copyright (C) 2005-2022  Laurent Destailleur     <eldy@users.sourceforge.net>
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


/**
 *    \file       htdocs/bookmarks/list.php
 *    \ingroup    bookmark
 *    \brief      Page to display list of bookmarks
 */

// Load Dolibarr environment
use DoliCore\Form\Form;
use DoliCore\Lib\ExtraFields;
use DoliCore\Model\Bookmark;

require BASE_PATH . '/main.inc.php';

class BookMarksListController extends DolibarrController
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
        $langs->loadLangs(['bookmarks', 'admin']);

// Get Parameters
        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $show_files = GETPOSTINT('show_files');
        $confirm = GETPOST('confirm', 'alpha');
        $cancel = GETPOST('cancel', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bookmarklist'; // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');
        $optioncss = GETPOST('optioncss', 'alpha');
        $mode = GETPOST('mode', 'aZ');

        $id = GETPOSTINT("id");
        $search_title = GETPOST('search_title', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
            // If $page is not defined, or '' or -1 or if we click on clear filters
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = 'b.position';
        }
        if (!$sortorder) {
            $sortorder = 'ASC';
        }

// Initialize Objects
        $object = new Bookmark($db);
        $extrafields = new ExtraFields($db);
        $arrayfields = [];
        $hookmanager->initHooks(['bookmarklist']); // Note that conf->hooks_modules contains array

        if ($id > 0) {
            $object->fetch($id);
        }

        $object->fields = dol_sort_array($object->fields, 'position');
        $arrayfields = dol_sort_array($arrayfields, 'position');

// Security check
        restrictedArea($user, 'bookmark', $object);

// Permissions
        $permissiontoread = $user->hasRight('bookmark', 'lire');
        $permissiontoadd = $user->hasRight('bookmark', 'creer');
        $permissiontodelete = ($user->hasRight('bookmark', 'supprimer') || ($permissiontoadd && $object->fk_user == $user->id));


        /*
         * Actions
         */

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
            $massaction = '';
        }

        $parameters = [];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Selection of new fields
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            if (
                GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
                || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')
            ) {
                $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
            }

            // Mass actions
            $objectclass = 'Bookmark';
            $objectlabel = 'Bookmark';
            $uploaddir = $conf->bookmark->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

            if ($action == 'delete' && $permissiontodelete) {
                $object->fetch($id);
                $res = $object->delete($user);
                if ($res > 0) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $action = '';
                }
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BookMarks/Views/list.php');

        $db->close();

        return true;
    }
}
