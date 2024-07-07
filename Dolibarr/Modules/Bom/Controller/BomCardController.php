<?php

/* Copyright (C) 2017-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2023       Charlene Benke          <charlene@patas-monkey.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *    \file       htdocs/bom/bom_card.php
 *    \ingroup    bom
 *    \brief      Page to create/edit/view Bill Of Material
 */

use DoliCore\Base\Controller\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliModules\Bom\Model\Bom;
use DoliModules\Bom\Model\BomLine;

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/mrp/lib/mrp.lib.php';
require_once BASE_PATH . '/../Dolibarr/Modules/Bom/Lib/Bom.php';

class BomCardController extends DolibarrController
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
        $langs->loadLangs(['mrp', 'other']);

// Get parameters
        $id = GETPOSTINT('id');
        $lineid = GETPOSTINT('lineid');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $cancel = GETPOST('cancel', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bomcard'; // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');


// PDF
        $hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
        $hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
        $hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

// Initialize technical objects
        $object = new Bom($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->bom->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(['bomcard', 'globalcard']); // Note that conf->hooks_modules contains array

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
                        $backtopage = '/bom/bom_card.php?id=' . ($id > 0 ? $id : '__ID__');
                    }
                }
            }

            $triggermodname = 'BOM_MODIFY'; // Name of trigger action code to execute when we modify record


            // Actions cancel, add, update, delete or clone
            include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';
            // The fetch/fetch_lines was redone into the inc.php so we must recall the calculateCosts()
            if ($action == 'confirm_validate' && $object->id > 0) {
                $object->calculateCosts();
            }

            // Actions when linking object each other
            include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

            // Actions when printing a doc from card
            include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

            // Action to move up and down lines of object
            //include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

            // Action to build doc
            include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

            // Actions to send emails
            $triggersendname = 'BOM_SENTBYMAIL';
            $autocopy = 'MAIN_MAIL_AUTOCOPY_BOM_TO';
            $trackid = 'bom' . $object->id;
            include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';

            // Add line
            if ($action == 'addline' && $user->hasRight('bom', 'write')) {
                $langs->load('errors');
                $error = 0;
                $predef = '';

                // Set if we used free entry or predefined product
                $bom_child_id = GETPOSTINT('bom_id');
                if ($bom_child_id > 0) {
                    $bom_child = new Bom($db);
                    $res = $bom_child->fetch($bom_child_id);
                    if ($res) {
                        $idprod = $bom_child->fk_product;
                    }
                } else {
                    $idprod = (!empty(GETPOSTINT('idprodservice')) ? GETPOSTINT('idprodservice') : GETPOSTINT('idprod'));
                }

                $qty = price2num(GETPOST('qty', 'alpha'), 'MS');
                $qty_frozen = price2num(GETPOST('qty_frozen', 'alpha'), 'MS');
                $disable_stock_change = GETPOSTINT('disable_stock_change');
                $efficiency = price2num(GETPOST('efficiency', 'alpha'));
                $fk_unit = GETPOST('fk_unit', 'alphanohtml');

                $fk_default_workstation = 0;
                if (!empty($idprod) && isModEnabled('workstation')) {
                    $product = new Product($db);
                    $res = $product->fetch($idprod);
                    if ($res > 0 && $product->type == Product::TYPE_SERVICE) {
                        $fk_default_workstation = $product->fk_default_workstation;
                    }
                    if (empty($fk_unit)) {
                        $fk_unit = $product->fk_unit;
                    }
                }

                if ($qty == '') {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
                    $error++;
                }
                if (!($idprod > 0)) {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Product')), null, 'errors');
                    $error++;
                }

                if ($object->fk_product == $idprod) {
                    setEventMessages($langs->trans('TheProductXIsAlreadyTheProductToProduce'), null, 'errors');
                    $error++;
                }

                // We check if we're allowed to add this bom
                $TParentBom = [];
                $object->getParentBomTreeRecursive($TParentBom);
                if ($bom_child_id > 0 && !empty($TParentBom) && in_array($bom_child_id, $TParentBom)) {
                    $n_child = new Bom($db);
                    $n_child->fetch($bom_child_id);
                    setEventMessages($langs->transnoentities('BomCantAddChildBom', $n_child->getNomUrl(1), $object->getNomUrl(1)), null, 'errors');
                    $error++;
                }

                if (!$error) {
                    // Extrafields
                    $extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
                    $array_options = $extrafields->getOptionalsFromPost($object->table_element_line, $predef);
                    // Unset extrafield
                    if (is_array($extralabelsline)) {
                        // Get extra fields
                        foreach ($extralabelsline as $key => $value) {
                            unset($_POST["options_" . $key]);
                        }
                    }

                    $result = $object->addLine($idprod, $qty, $qty_frozen, $disable_stock_change, $efficiency, -1, $bom_child_id, null, $fk_unit, $array_options, $fk_default_workstation);

                    if ($result <= 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = '';
                    } else {
                        unset($_POST['idprod']);
                        unset($_POST['idprodservice']);
                        unset($_POST['qty']);
                        unset($_POST['qty_frozen']);
                        unset($_POST['disable_stock_change']);
                    }

                    $object->fetchLines();

                    $object->calculateCosts();
                }
            }

            // Update line
            if ($action == 'updateline' && $user->hasRight('bom', 'write')) {
                $langs->load('errors');
                $error = 0;

                // Set if we used free entry or predefined product
                $qty = price2num(GETPOST('qty', 'alpha'), 'MS');
                $qty_frozen = price2num(GETPOST('qty_frozen', 'alpha'), 'MS');
                $disable_stock_change = GETPOSTINT('disable_stock_change');
                $efficiency = price2num(GETPOST('efficiency', 'alpha'));
                $fk_unit = GETPOST('fk_unit', 'alphanohtml');

                if ($qty == '') {
                    setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
                    $error++;
                }

                if (!$error) {
                    // Extrafields
                    $extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
                    $array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
                    // Unset extrafield
                    if (is_array($extralabelsline)) {
                        // Get extra fields
                        foreach ($extralabelsline as $key => $value) {
                            unset($_POST["options_" . $key]);
                        }
                    }

                    $bomline = new BomLine($db);
                    $bomline->fetch($lineid);

                    $fk_default_workstation = $bomline->fk_default_workstation;
                    if (isModEnabled('workstation') && GETPOSTISSET('idworkstations')) {
                        $fk_default_workstation = GETPOSTINT('idworkstations');
                    }

                    $result = $object->updateLine($lineid, $qty, (int) $qty_frozen, (int) $disable_stock_change, $efficiency, $bomline->position, $bomline->import_key, $fk_unit, $array_options, $fk_default_workstation);

                    if ($result <= 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = '';
                    } else {
                        unset($_POST['idprod']);
                        unset($_POST['idprodservice']);
                        unset($_POST['qty']);
                        unset($_POST['qty_frozen']);
                        unset($_POST['disable_stock_change']);
                    }

                    $object->fetchLines();

                    $object->calculateCosts();
                }
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Bom/Views/bom_card.php');

        $db->close();

        return true;
    }
}
