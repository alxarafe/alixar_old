<?php

/* Copyright (C) 2007-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet	   	    <fmarcet@2byte.es>
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

namespace DoliModules\Asset\Controller;

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
require_once DOL_DOCUMENT_ROOT . '/asset/class/asset.class.php';
require_once DOL_DOCUMENT_ROOT . '/asset/class/assetaccountancycodes.class.php';
require_once DOL_DOCUMENT_ROOT . '/asset/class/assetdepreciationoptions.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/asset.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

use Asset;
use DoliCore\Base\DolibarrController;
use DoliCore\Lib\Fields;
use ExtraFields;

class AssetController extends DolibarrController
{
    /**
     *  \file       htdocs/asset/accountancy_code.php
     *  \ingroup    asset
     *  \brief      Card with accountancy code on Asset
     */
    public function accountancy_codes()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "companies"));

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');
        $backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
        $object = new Asset($db);
        $assetaccountancycodes = new AssetAccountancyCodes($db);
        $assetdepreciationoptions = new AssetDepreciationOptions($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetaccountancycodes', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->asset->multidir_output[isset($object->entity) ? $object->entity : 1] . "/" . $object->id;
        }

        $permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }

        $object->asset_depreciation_options = &$assetdepreciationoptions;
        $object->asset_accountancy_codes = &$assetaccountancycodes;
        if (!empty($id)) {
            $depreciationoptionserrors = $assetdepreciationoptions->fetchDeprecationOptions($object->id, 0);
            $accountancycodeserrors = $assetaccountancycodes->fetchAccountancyCodes($object->id, 0);

            if ($depreciationoptionserrors < 0) {
                setEventMessages($assetdepreciationoptions->error, $assetdepreciationoptions->errors, 'errors');
            }
            if ($accountancycodeserrors < 0) {
                setEventMessages($assetaccountancycodes->error, $assetaccountancycodes->errors, 'errors');
            }
        }


        /*
         * Actions
         */

        $reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }
        if (empty($reshook)) {
            $backurlforlist = '/asset/list.php';

            if (empty($backtopage) || ($cancel && empty($id))) {
                if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
                    if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                        $backtopage = $backurlforlist;
                    } else {
                        $backtopage = '/asset/accountancy_codes.php?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
                    }
                }
            }

            if ($cancel) {
                /*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
                if (!empty($backtopageforcancel)) {
                    header("Location: " . $backtopageforcancel);
                    exit;
                } elseif (!empty($backtopage)) {
                    header("Location: " . $backtopage);
                    exit;
                }
                $action = '';
            }

            if ($action == "update") {
                $assetaccountancycodes->setAccountancyCodesFromPost();

                $result = $assetaccountancycodes->updateAccountancyCodes($user, $object->id);
                if ($result < 0) {
                    setEventMessages($assetaccountancycodes->error, $assetaccountancycodes->errors, 'errors');
                    $action = 'edit';
                } else {
                    setEventMessage($langs->trans('RecordSaved'));
                    header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                    exit;
                }
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/accountancy_codes.php');

        $db->close();
        return true;
    }

    public function index(bool $executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        $this->list();
    }

    /**
     *  \file       htdocs/asset/agenda.php
     *  \ingroup    asset
     *  \brief      Tab of events on Asset
     */
    public function agenda()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "other"));

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');

        if (GETPOST('actioncode', 'array')) {
            $actioncode = GETPOST('actioncode', 'array', 3);
            if (!count($actioncode)) {
                $actioncode = '0';
            }
        } else {
            $actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
        }
        $search_rowid = GETPOST('search_rowid');
        $search_agenda_label = GETPOST('search_agenda_label');

        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST("sortfield", 'alpha');
        $sortorder = GETPOST("sortorder", 'alpha');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = 'a.datep,a.id';
        }
        if (!$sortorder) {
            $sortorder = 'DESC,DESC';
        }

// Initialize technical objects
        $object = new Asset($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetagenda', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->asset->multidir_output[$object->entity] . "/" . $object->id;
        }

        $permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }


        /*
         *  Actions
         */

        $parameters = array('id' => $id);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Cancel
            if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
                header("Location: " . $backtopage);
                exit;
            }

            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $actioncode = '';
                $search_agenda_label = '';
            }
        }



        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/agenda.php');

        $db->close();
        return true;
    }

    /**
     *  \file       htdocs/asset/card.php
     *  \ingroup    asset
     *  \brief      Page to create/edit/view asset
     */
    public function card()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "other"));

// Get parameters
        $id         = GETPOSTINT('id');
        $ref        = GETPOST('ref', 'alpha');
        $action     = GETPOST('action', 'aZ09');
        $confirm    = GETPOST('confirm', 'alpha');
        $cancel     = GETPOST('cancel', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'assetcard'; // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');
        $backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
        $object = new Asset($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
        $search_all = GETPOST("search_all", 'alpha');
        $search = array();
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

        $permissiontoread = $user->hasRight('asset', 'read');
        $permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
        $permissiontodelete = $user->hasRight('asset', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
        $permissionnote = $user->hasRight('asset', 'write'); // Used by the include of actions_setnotes.inc.php
        $permissiondellink = $user->hasRight('asset', 'write'); // Used by the include of actions_dellink.inc.php
        $upload_dir = $conf->asset->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            $socid = $user->socid;
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }
        if (!$permissiontoread) {
            accessforbidden();
        }


        /*
         * Actions
         */

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            $error = 0;

            $backurlforlist = '/asset/list.php';

            if (empty($backtopage) || ($cancel && empty($id))) {
                if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
                    if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                        $backtopage = $backurlforlist;
                    } else {
                        $backtopage = '/asset/card.php?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
                    }
                }
            }

            $object->oldcopy = dol_clone($object, 2);
            $triggermodname = 'ASSET_MODIFY'; // Name of trigger action code to execute when we modify record

            // Action dispose object
            if ($action == 'confirm_disposal' && $confirm == 'yes' && $permissiontoadd) {
                $object->disposal_date = dol_mktime(12, 0, 0, GETPOSTINT('disposal_datemonth'), GETPOSTINT('disposal_dateday'), GETPOSTINT('disposal_dateyear')); // for date without hour, we use gmt
                $object->disposal_amount_ht = GETPOSTINT('disposal_amount');
                $object->fk_disposal_type = GETPOSTINT('fk_disposal_type');
                $disposal_invoice_id = GETPOSTINT('disposal_invoice_id');
                $object->disposal_depreciated = ((GETPOST('disposal_depreciated') == '1' || GETPOST('disposal_depreciated') == 'on') ? 1 : 0);
                $object->disposal_subject_to_vat = ((GETPOST('disposal_subject_to_vat') == '1' || GETPOST('disposal_subject_to_vat') == 'on') ? 1 : 0);

                $result = $object->dispose($user, $disposal_invoice_id);
                if ($result < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
                $action = '';
            } elseif ($action == "add") {
                $object->supplier_invoice_id = GETPOSTINT('supplier_invoice_id');
            }

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

            // Actions to send emails
            $triggersendname = 'ASSET_SENTBYMAIL';
            $autocopy = 'MAIN_MAIL_AUTOCOPY_ASSET_TO';
            $trackid = 'asset' . $object->id;
            include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/card.php');

        $db->close();
        return true;
    }

    /**
     *  \file       htdocs/asset/depreciation.php
     *  \ingroup    asset
     *  \brief      Card with depreciation on Asset
     */
    public function depreciation()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "companies"));

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
        $object = new Asset($db);
        $assetdepreciationoptions = new AssetDepreciationOptions($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetdepreciation', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->asset->multidir_output[$object->entity] . "/" . $object->id;
        }

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }
        if (!empty($object->not_depreciated)) {
            accessforbidden();
        }

        $object->asset_depreciation_options = &$assetdepreciationoptions;
        $result = $assetdepreciationoptions->fetchDeprecationOptions($object->id);
        if ($result < 0) {
            setEventMessages($assetdepreciationoptions->error, $assetdepreciationoptions->errors, 'errors');
        }
        $result = $object->fetchDepreciationLines();
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        }


        /*
         * Actions
         */

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }
        if (empty($reshook)) {
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/depreciation.php');
        $db->close();
        return true;
    }

    /**
     *  \file       htdocs/asset/depreciation_options.php
     *  \ingroup    asset
     *  \brief      Card with depreciation options on Asset
     */
    public function depreciation_options()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "companies"));

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');
        $backtopageforcancel = GETPOST('backtopageforcancel', 'alpha'); // if not set, $backtopage will be used

// Initialize technical objects
        $object = new Asset($db);
        $assetdepreciationoptions = new AssetDepreciationOptions($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetdepreciationoptions', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->asset->multidir_output[isset($object->entity) ? $object->entity : 1] . "/" . $object->id;
        }

        $permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }
        if (!empty($object->not_depreciated)) {
            accessforbidden();
        }

        $object->asset_depreciation_options = &$assetdepreciationoptions;
        $result = $assetdepreciationoptions->fetchDeprecationOptions($object->id);
        if ($result < 0) {
            setEventMessages($assetdepreciationoptions->error, $assetdepreciationoptions->errors, 'errors');
        }


        /*
         * Actions
         */

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }
        if (empty($reshook)) {
            $backurlforlist = '/asset/list.php';

            if (empty($backtopage) || ($cancel && empty($id))) {
                if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
                    if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
                        $backtopage = $backurlforlist;
                    } else {
                        $backtopage = '/asset/depreciation_options.php?id=' . ((!empty($id) && $id > 0) ? $id : '__ID__');
                    }
                }
            }

            if ($cancel) {
                /*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
                if (!empty($backtopageforcancel)) {
                    header("Location: " . $backtopageforcancel);
                    exit;
                } elseif (!empty($backtopage)) {
                    header("Location: " . $backtopage);
                    exit;
                }
                $action = '';
            }

            if ($action == "update") {
                $result = $assetdepreciationoptions->setDeprecationOptionsFromPost();
                if ($result > 0) {
                    $result = $assetdepreciationoptions->updateDeprecationOptions($user, $object->id);
                }
                if ($result < 0) {
                    setEventMessages($assetdepreciationoptions->error, $assetdepreciationoptions->errors, 'errors');
                    $action = 'edit';
                } else {
                    setEventMessage($langs->trans('RecordSaved'));
                    header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $object->id);
                    exit;
                }
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/depreciation_options.php');

        $db->close();
        return true;
    }

    /**
     *  \file       htdocs/asset/disposal.php
     *  \ingroup    asset
     *  \brief      Card with disposal info on Asset
     */
    public function disposal()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "companies"));

// Get parameters
        $id = GETPOSTINT('id');
        $ref        = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel     = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
        $object = new Asset($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetdisposal', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->asset->multidir_output[$object->entity] . "/" . $object->id;
        }

        $permissionnote = $user->hasRight('asset', 'write'); // Used by the include of actions_setnotes.inc.php
        $permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }
        if (!isset($object->disposal_date) || $object->disposal_date === "") {
            accessforbidden();
        }


        /*
         * Actions
         */

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }
        if (empty($reshook)) {
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/disposal.php');

        $db->close();
        return true;
    }

    /**
     *  \file       htdocs/asset/document.php
     *  \ingroup    asset
     *  \brief      Page for attached files on assets
     */
    public function document()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array('assets', 'companies', 'other', 'mails'));


        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');

// Get parameters
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST("sortfield", 'alpha');
        $sortorder = GETPOST("sortorder", 'alpha');
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

// Initialize technical objects
        $object = new Asset($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetdocument', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->asset->multidir_output[$object->entity ? $object->entity : $conf->entity] . "/" . get_exdir(0, 0, 0, 1, $object);
        }

        $permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles.inc.php

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        include DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/document.php');

        $db->close();
        return true;
    }

    /**
     *      \file       htdocs/asset/list.php
     *      \ingroup    asset
     *      \brief      List page for asset
     */
    public function list()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "other"));

// Get parameters
        $action         = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
        $massaction     = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
        $show_files     = GETPOSTINT('show_files'); // Show files area generated by bulk actions ?
        $confirm        = GETPOST('confirm', 'alpha'); // Result of a confirmation
        $cancel         = GETPOST('cancel', 'alpha'); // We click on a Cancel button
        $toselect       = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
        $contextpage    = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'assetlist'; // To manage different context of search
        $backtopage     = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
        $optioncss      = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
        $mode           = GETPOST('mode', 'alpha');  // mode view (kanban or common)
        $id             = GETPOSTINT('id');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
            $page = 0;
        }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;

// Initialize technical objects
        $object = new Asset($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetlist')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

        $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
        if (!$sortfield) {
            reset($object->fields);                 // Reset is required to avoid key() to return null.
            $sortfield = "t." . key($object->fields); // Set here default search field. By default 1st field in definition.
        }
        if (!$sortorder) {
            $sortorder = "ASC";
        }

// Initialize array of search criteria
        $search_all = GETPOST('search_all', 'alphanohtml');
        $search = array();
        foreach ($object->fields as $key => $val) {
            if (GETPOST('search_' . $key, 'alpha') !== '') {
                $search[$key] = GETPOST('search_' . $key, 'alpha');
            }
            if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
                $search[$key . '_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_' . $key . '_dtstartmonth'), GETPOSTINT('search_' . $key . '_dtstartday'), GETPOSTINT('search_' . $key . '_dtstartyear'));
                $search[$key . '_dtend'] = dol_mktime(23, 59, 59, GETPOSTINT('search_' . $key . '_dtendmonth'), GETPOSTINT('search_' . $key . '_dtendday'), GETPOSTINT('search_' . $key . '_dtendyear'));
            }
        }

// List of fields to search into when doing a "search in all"
        $fieldstosearchall = array();
        foreach ($object->fields as $key => $val) {
            if (!empty($val['searchall'])) {
                $fieldstosearchall['t.' . $key] = $val['label'];
            }
        }

// Definition of array of fields for columns
        $arrayfields = Fields::getArrayFields($object->fields);

        // Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

        $object->fields = dol_sort_array($object->fields, 'position');
        $arrayfields = dol_sort_array($arrayfields, 'position');

        $permissiontoread = $user->hasRight('asset', 'read');
        $permissiontoadd = $user->hasRight('asset', 'write');
        $permissiontodelete = $user->hasRight('asset', 'delete');

// Security check
        if (!isModEnabled('asset')) {
            accessforbidden('Module not enabled');
        }

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $socid = 0; if ($user->socid > 0) {
        $socid = $user->socid;
    }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }
        if (!$permissiontoread) {
            accessforbidden();
        }



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

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Selection of new fields
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                foreach ($object->fields as $key => $val) {
                    $search[$key] = '';
                    if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
                        $search[$key . '_dtstart'] = '';
                        $search[$key . '_dtend'] = '';
                    }
                }
                $toselect = array();
                $search_array_options = array();
            }
            if (
                GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
                || GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')
            ) {
                $massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
            }

            // Mass actions
            $objectclass = 'Asset';
            $objectlabel = 'Asset';
            $uploaddir = $conf->asset->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/list.php');

        $db->close();
        return true;
    }

    /**
     *  \file       htdocs/asset/note.php
     *  \ingroup    asset
     *  \brief      Card with notes on Asset
     */
    public function note()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("assets", "companies"));

// Get parameters
        $id = GETPOSTINT('id');
        $ref        = GETPOST('ref', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cancel     = GETPOST('cancel', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
        $object = new Asset($db);
        $extrafields = new ExtraFields($db);
        $diroutputmassaction = $conf->asset->dir_output . '/temp/massgeneration/' . $user->id;
        $hookmanager->initHooks(array('assetnote', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
        if ($id > 0 || !empty($ref)) {
            $upload_dir = $conf->asset->multidir_output[$object->entity] . "/" . $object->id;
        }

        $permissionnote = $user->hasRight('asset', 'write'); // Used by the include of actions_setnotes.inc.php
        $permissiontoadd = $user->hasRight('asset', 'write'); // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
        if ($user->socid > 0) {
            accessforbidden();
        }
        $isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
        restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
        if (!isModEnabled('asset')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        $parameters = array();
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
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Asset/Views/note.php');

        $db->close();
        return true;
    }
}
