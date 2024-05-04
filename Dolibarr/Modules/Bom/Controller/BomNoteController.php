<?php

/* Copyright (C) 2007-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 *    \file       htdocs/bom/bom_note.php
 *    \ingroup    bom
 *    \brief      Card with notes on BillOfMaterials
 */

// Load Dolibarr environment
use DoliCore\Base\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliModules\Bom\Model\Bom;

require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/bom/class/bom.class.php';
require_once BASE_PATH . '/../Dolibarr/Modules/Bom/Lib/Bom.php';

class BomNoteController extends DolibarrController
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
        $langs->loadLangs(["mrp", "companies"]);

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
        $object = new Bom($db);
        $extrafields = new ExtraFields($db);

// Initialize technical objects for hooks
        $hookmanager->initHooks(['bomnote', 'globalcard']); // Note that conf->hooks_modules contains array

// Massactions
        $diroutputmassaction = $conf->bom->dir_output . '/temp/massgeneration/' . $user->id;

// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$result = restrictedArea($user, 'bom', $id);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = (!empty($conf->bom->multidir_output[$object->entity]) ? $conf->bom->multidir_output[$object->entity] : $conf->bom->dir_output) . "/" . $object->id;
        }

        $permissionnote = $user->hasRight('bom', 'write'); // Used by the include of actions_setnotes.inc.php

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, 'bom', $object->id, $object->table_element, '', '', 'rowid', $isdraft);


        /*
         * Actions
         */

        $parameters = [];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }
        if (empty($reshook)) {
            include DOL_DOCUMENT_ROOT . '/core/actions_setnotes.inc.php'; // Must be include, not include_once
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Bom/Views/bom_note.php');

        $db->close();
        return true;
    }
}
