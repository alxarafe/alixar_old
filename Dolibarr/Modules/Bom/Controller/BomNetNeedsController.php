<?php

/* Copyright (C) 2017-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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

namespace DoliModules\Bom\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

/**
 *      \file       htdocs/bom/bom_net_needs.php
 *      \ingroup    bom
 *      \brief      Page to create/edit/view bom
 */

// Load Dolibarr environment
use DoliCore\Base\Controller\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliModules\Bom\Model\Bom;

require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bom/class/bom.class.php';
require_once BASE_PATH . '/../Dolibarr/Modules/Bom/Lib/Bom.php';

class BomNetNeedsController extends DolibarrController
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
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(["mrp", "other", "stocks"]);

// Get parameters
        $id = GETPOSTINT('id');
        $lineid = GETPOSTINT('lineid');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $cancel = GETPOST('cancel', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bomnet_needs'; // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');


// Initialize technical objects
        $object = new Bom($db);
        $extrafields = new ExtraFields($db);

// Initialize technical objects for hooks
        $hookmanager->initHooks(['bomnetneeds']); // Note that conf->hooks_modules contains array

// Massaction
        $diroutputmassaction = $conf->bom->dir_output . '/temp/massgeneration/' . $user->id;

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
        if ($object->id > 0) {
            $object->calculateCosts();
        }


// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        $result = restrictedArea($user, 'bom', $object->id, $object->table_element, '', '', 'rowid', $isdraft);

// Permissions
        $permissionnote = $user->hasRight('bom', 'write'); // Used by the include of actions_setnotes.inc.php
        $permissiondellink = $user->hasRight('bom', 'write'); // Used by the include of actions_dellink.inc.php
        $permissiontoadd = $user->hasRight('bom', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
        $permissiontodelete = $user->hasRight('bom', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
        $upload_dir = $conf->bom->multidir_output[isset($object->entity) ? $object->entity : 1];


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

            $backurlforlist = '/bom/bom_list.php';

            if (empty($backtopage) || ($cancel && empty($id))) {
                if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
                    if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                        $backtopage = $backurlforlist;
                    } else {
                        $backtopage = '/bom/bom_net_needs.php?id=' . ($id > 0 ? $id : '__ID__');
                    }
                }
            }
            if ($action == 'treeview') {
                $object->getNetNeedsTree($TChildBom, 1);
            } else {
                $object->getNetNeeds($TChildBom, 1);
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Bom/Views/bom_net_needs.php');


        $db->close();
        return true;
    }
}
