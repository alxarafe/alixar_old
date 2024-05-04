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
 *    \file       htdocs/bom/bom_document.php
 *    \ingroup    bom
 *    \brief      Tab for documents linked to BillOfMaterials
 */

// Load Dolibarr environment
use DoliCore\Base\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliModules\Bom\Model\Bom;

require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Company.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Files.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Images.php';
require_once DOL_DOCUMENT_ROOT . '/bom/class/bom.class.php';
require_once BASE_PATH . '/../Dolibarr/Modules/Bom/Lib/Bom.php';

class BomDocumentController extends DolibarrController
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
        $langs->loadLangs(["mrp", "companies", "other", "mails"]);

// Get parameters
        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $id = (GETPOSTINT('socid') ? GETPOSTINT('socid') : GETPOSTINT('id'));
        $ref = GETPOST('ref', 'alpha');

// Security check - Protection if external user
// if ($user->socid > 0) accessforbidden();
// if ($user->socid > 0) $socid = $user->socid;
// $result = restrictedArea($user, 'bom', $id);

// Load variables for pagination
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
        $object = new Bom($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->bom->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(['bomdocument', 'globalcard']); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->bom->multidir_output[$object->entity ? $object->entity : 1] . "/" . get_exdir(0, 0, 0, 1, $object);
        }

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, 'bom', $object->id, $object->table_element, '', '', 'rowid', $isdraft);

        $permissiontoadd = $user->hasRight('bom', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles.inc.php


        /*
         * Actions
         */

        include DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Bom/Views/bom_document.php');


        $db->close();

        return true;
    }
}
