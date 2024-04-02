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
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/asset.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

use Asset;
use DoliCore\Base\DolibarrController;
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

        $form = new Form($db);

        $help_url = '';
        llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_accountancy');

        if ($id > 0 || !empty($ref)) {
            $head = assetPrepareHead($object);
            print dol_get_fiche_head($head, 'accountancy_codes', $langs->trans("Asset"), -1, $object->picto);

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';

            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';
            print '</div>';

            if ($action == 'edit') {
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="update">';
                if ($backtopage) {
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                }
                if ($backtopageforcancel) {
                    print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
                }

                print dol_get_fiche_head(array(), '');

                include DOL_DOCUMENT_ROOT . '/asset/tpl/accountancy_codes_edit.tpl.php';

                print dol_get_fiche_end();

                print $form->buttonsSaveCancel();

                print '</form>';
            } else {
                include DOL_DOCUMENT_ROOT . '/asset/tpl/accountancy_codes_view.tpl.php';
            }

            print dol_get_fiche_end();

            if ($action != 'edit') {
                print '<div class="tabsAction">' . "\n";
                $parameters = array();
                $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                if ($reshook < 0) {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                }

                if (empty($reshook)) {
                    if ($object->status == $object::STATUS_DRAFT/* && !empty($object->enabled_modes)*/) {
                        print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);
                    }
                }
                print '</div>' . "\n";
            }
        }

// End of page
        llxFooter();
        $db->close();
    }

    public function index()
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
         *	View
         */

        $form = new Form($db);

        if ($object->id > 0) {
            $title = $langs->trans("Agenda");
            //if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$title;
            $help_url = 'EN:Module_Agenda_En|DE:Modul_Terminplanung';
            llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_agenda');

            if (isModEnabled('notification')) {
                $langs->load("mails");
            }
            $head = assetPrepareHead($object);


            print dol_get_fiche_head($head, 'agenda', $langs->trans("Asset"), -1, $object->picto);

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';


            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';

            $object->info($object->id);
            dol_print_object_info($object, 1);

            print '</div>';

            print dol_get_fiche_end();


            // Actions buttons

            $objthirdparty = $object;
            $objcon = new stdClass();

            $out = '&origin=' . urlencode((string) ($object->element . '@' . $object->module)) . '&originid=' . urlencode((string) ($object->id));
            $urlbacktopage = $_SERVER['PHP_SELF'] . '?id=' . $object->id;
            $out .= '&backtopage=' . urlencode($urlbacktopage);
            $permok = $user->hasRight('agenda', 'myactions', 'create');
            if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
                //$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
                if (get_class($objthirdparty) == 'Societe') {
                    $out .= '&socid=' . urlencode((string) ($objthirdparty->id));
                }
                $out .= (!empty($objcon->id) ? '&contactid=' . urlencode($objcon->id) : '');
                //$out.=$langs->trans("AddAnAction").' ';
                //$out.=img_picto($langs->trans("AddAnAction"),'filenew');
                //$out.="</a>";
            }


            print '<div class="tabsAction">';

            if (isModEnabled('agenda')) {
                if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
                    print '<a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create' . $out . '">' . $langs->trans("AddAction") . '</a>';
                } else {
                    print '<a class="butActionRefused classfortooltip" href="#">' . $langs->trans("AddAction") . '</a>';
                }
            }

            print '</div>';

            if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
                $param = '&id=' . $object->id . '&socid=' . $socid;
                if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                    $param .= '&contextpage=' . urlencode($contextpage);
                }
                if ($limit > 0 && $limit != $conf->liste_limit) {
                    $param .= '&limit=' . ((int) $limit);
                }


                print load_fiche_titre($langs->trans("ActionsOnAsset"), '', '');

                // List of all actions
                $filters = array();
                $filters['search_agenda_label'] = $search_agenda_label;
                $filters['search_rowid'] = $search_rowid;

                // TODO Replace this with same code than into list.php
                show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
            }
        }

// End of page
        llxFooter();
        $db->close();
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
         *
         */

        $form = new Form($db);
        $formfile = new FormFile($db);

        $title = $langs->trans("Asset") . ' - ' . $langs->trans("Card");
        $help_url = '';
        llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-asset page-card');

// Part to create
        if ($action == 'create') {
            print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Asset")), '', 'object_' . $object->picto);

            print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="add">';
            if ($backtopage) {
                print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
            }
            if ($backtopageforcancel) {
                print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
            }
            if (GETPOSTISSET('supplier_invoice_id')) {
                $object->fields['supplier_invoice_id'] = array('type' => 'integer:FactureFournisseur:fourn/class/fournisseur.facture.class.php:1:entity IN (__SHARED_ENTITIES__)', 'label' => 'SupplierInvoice', 'enabled' => '1', 'noteditable' => '1', 'position' => 280, 'notnull' => 0, 'visible' => 1, 'index' => 1, 'validate' => '1',);
                print '<input type="hidden" name="supplier_invoice_id" value="' . GETPOSTINT('supplier_invoice_id') . '">';
            }

            print dol_get_fiche_head(array(), '');

            // Set some default values
            //if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

            print '<table class="border centpercent tableforfieldcreate">' . "\n";

            // Common attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

            print '</table>' . "\n";

            print dol_get_fiche_end();

            print $form->buttonsSaveCancel("Create");

            print '</form>';

            //dol_set_focus('input[name="ref"]');
        }

// Part to edit record
        if (($id || $ref) && $action == 'edit') {
            print load_fiche_titre($langs->trans("Asset"), '', 'object_' . $object->picto);

            print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="' . $object->id . '">';
            if ($backtopage) {
                print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
            }
            if ($backtopageforcancel) {
                print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
            }

            print dol_get_fiche_head();

            print '<table class="border centpercent tableforfieldedit">' . "\n";

            // Common attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

            // Other attributes
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

            print '</table>';

            print dol_get_fiche_end();

            print $form->buttonsSaveCancel();

            print '</form>';
        }

// Part to show record
        if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
            $res = $object->fetch_optionals();

            $head = assetPrepareHead($object);
            print dol_get_fiche_head($head, 'card', $langs->trans("Asset"), -1, $object->picto);

            $formconfirm = '';

            // Confirmation to delete
            if ($action == 'delete') {
                $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('DeleteAsset'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
            } elseif ($action == 'disposal') {
                // Disposal
                $langs->load('bills');

                $disposal_date = dol_mktime(12, 0, 0, GETPOSTINT('disposal_datemonth'), GETPOSTINT('disposal_dateday'), GETPOSTINT('disposal_dateyear')); // for date without hour, we use gmt
                $disposal_amount = GETPOSTINT('disposal_amount');
                $fk_disposal_type = GETPOSTINT('fk_disposal_type');
                $disposal_invoice_id = GETPOSTINT('disposal_invoice_id');
                $disposal_depreciated = GETPOSTISSET('disposal_depreciated') ? GETPOST('disposal_depreciated') : 1;
                $disposal_depreciated = !empty($disposal_depreciated) ? 1 : 0;
                $disposal_subject_to_vat = GETPOSTISSET('disposal_subject_to_vat') ? GETPOST('disposal_subject_to_vat') : 1;
                $disposal_subject_to_vat = !empty($disposal_subject_to_vat) ? 1 : 0;

                $object->fields['fk_disposal_type']['visible'] = 1;
                $disposal_type_form = $object->showInputField(null, 'fk_disposal_type', $fk_disposal_type, '', '', '', 0);
                $object->fields['fk_disposal_type']['visible'] = -2;

                $object->fields['disposal_invoice_id'] = array('type' => 'integer:Facture:compta/facture/class/facture.class.php::entity IN (__SHARED_ENTITIES__)', 'enabled' => '1', 'notnull' => 1, 'visible' => 1, 'index' => 1, 'validate' => '1',);
                $disposal_invoice_form = $object->showInputField(null, 'disposal_invoice_id', $disposal_invoice_id, '', '', '', 0);
                unset($object->fields['disposal_invoice_id']);

                // Create an array for form
                $formquestion = array(
                    array('type' => 'date', 'name' => 'disposal_date', 'tdclass' => 'fieldrequired', 'label' => $langs->trans("AssetDisposalDate"), 'value' => $disposal_date),
                    array('type' => 'text', 'name' => 'disposal_amount', 'tdclass' => 'fieldrequired', 'label' => $langs->trans("AssetDisposalAmount"), 'value' => $disposal_amount),
                    array('type' => 'other', 'name' => 'fk_disposal_type', 'tdclass' => 'fieldrequired', 'label' => $langs->trans("AssetDisposalType"), 'value' => $disposal_type_form),
                    array('type' => 'other', 'name' => 'disposal_invoice_id', 'label' => $langs->trans("InvoiceCustomer"), 'value' => $disposal_invoice_form),
                    array('type' => 'checkbox', 'name' => 'disposal_depreciated', 'label' => $langs->trans("AssetDisposalDepreciated"), 'value' => $disposal_depreciated),
                    array('type' => 'checkbox', 'name' => 'disposal_subject_to_vat', 'label' => $langs->trans("AssetDisposalSubjectToVat"), 'value' => $disposal_subject_to_vat),
                );
                $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('AssetDisposal'), $langs->trans('AssetConfirmDisposalAsk', $object->ref . ' - ' . $object->label), 'confirm_disposal', $formquestion, 'yes', 1);
            } elseif ($action == 'reopen') {
                // Re-open
                // Create an array for form
                $formquestion = array();
                $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $object->id, $langs->trans('ReOpen'), $langs->trans('AssetConfirmReOpenAsk', $object->ref), 'confirm_reopen', $formquestion, 'yes', 1);
            }
            // Clone confirmation
            /*  elseif ($action == 'clone') {
                // Create an array for form
                $formquestion = array();
                $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
            }*/

            // Call Hook formConfirm
            $parameters = array('formConfirm' => $formconfirm);
            $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            if (empty($reshook)) {
                $formconfirm .= $hookmanager->resPrint;
            } elseif ($reshook > 0) {
                $formconfirm = $hookmanager->resPrint;
            }

            // Print form confirm
            print $formconfirm;


            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';


            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


            print '<div class="fichecenter">';
            print '<div class="fichehalfleft">';
            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent tableforfield">' . "\n";

            // Common attributes
            $keyforbreak = 'date_acquisition';    // We change column just before this field
            //unset($object->fields['fk_project']);             // Hide field already shown in banner
            //unset($object->fields['fk_soc']);                 // Hide field already shown in banner
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

            // Other attributes. Fields from hook formObjectOptions and Extrafields.
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

            print '</table>';
            print '</div>';
            print '</div>';

            print '<div class="clearboth"></div>';

            print dol_get_fiche_end();

            // Buttons for actions
            if ($action != 'presend' && $action != 'editline') {
                print '<div class="tabsAction">' . "\n";
                $parameters = array();
                $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                if ($reshook < 0) {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                }

                if (empty($reshook)) {
                    // Send
                    if (empty($user->socid)) {
                        print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=presend&mode=init&token=' . newToken() . '#formmailbeforetitle');
                    }

                    if ($object->status == $object::STATUS_DRAFT) {
                        print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);
                    }

                    // Clone
                    //print dolGetButtonAction($langs->trans('ToClone'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone&token=' . newToken(), '', false && $permissiontoadd);

                    if ($object->status == $object::STATUS_DRAFT) {
                        print dolGetButtonAction($langs->trans('AssetDisposal'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=disposal&token=' . newToken(), '', $permissiontoadd);
                    } else {
                        print dolGetButtonAction($langs->trans('ReOpen'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=reopen&token=' . newToken(), '', $permissiontoadd);
                    }

                    // Delete (need delete permission, or if draft, just need create/modify permission)
                    print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
                }
                print '</div>' . "\n";
            }

            // Select mail models is same action as presend
            if (GETPOST('modelselected')) {
                $action = 'presend';
            }

            if ($action != 'presend') {
                print '<div class="fichecenter"><div class="fichehalfleft">';
                print '<a name="builddoc"></a>'; // ancre

                $includedocgeneration = 0;

                // Documents
                if ($includedocgeneration) {
                    $objref = dol_sanitizeFileName($object->ref);
                    $relativepath = $objref . '/' . $objref . '.pdf';
                    $filedir = $conf->asset->dir_output . '/' . $objref;
                    $urlsource = $_SERVER['PHP_SELF'] . "?id=" . $object->id;
                    $genallowed = $user->hasRight('asset', 'read'); // If you can read, you can build the PDF to read content
                    $delallowed = $user->hasRight('asset', 'write'); // If you can create/edit, you can remove a file on card
                    print $formfile->showdocuments('asset:Asset', $objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
                }

                // Show links to link elements
                $linktoelem = $form->showLinkToObjectBlock($object, null, array('asset'));
                $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


                print '</div><div class="fichehalfright">';

                $morehtmlcenter = '';
                $MAXEVENT = 10;

                $morehtmlcenter = dolGetButtonTitle($langs->trans('SeeAll'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT . '/asset/agenda.php?id=' . $object->id);

                // List of actions on element
                include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
                $formactions = new FormActions($db);
                $somethingshown = $formactions->showactions($object, $object->element, 0, 1, '', $MAXEVENT, '', $morehtmlcenter);

                print '</div></div>';
            }

            //Select mail models is same action as presend
            if (GETPOST('modelselected')) {
                $action = 'presend';
            }

            // Presend form
            $modelmail = 'asset';
            $defaulttopic = 'InformationMessage';
            $diroutput = $conf->asset->dir_output;
            $trackid = 'asset' . $object->id;

            include DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
        }

// End of page
        llxFooter();
        $db->close();
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

        $form = new Form($db);

        $help_url = '';
        llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_depreciation');

        if ($id > 0 || !empty($ref)) {
            $head = assetPrepareHead($object);
            print dol_get_fiche_head($head, 'depreciation', $langs->trans("Asset"), -1, $object->picto);

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';

            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';
            print '</div>';

            print dol_get_fiche_end();

            $parameters = array();
            $reshook = $hookmanager->executeHooks('listAssetDeprecation', $parameters, $object, $action);
            print $hookmanager->resPrint;
            if ($reshook < 0) {
                setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            } elseif (empty($reshook)) {
                $bookkeeping_icon = '<i class="fas fa-save" title="' . $langs->trans('AssetDispatchedInBookkeeping') . '"></i>';
                $future_icon = '<i class="fas fa-clock" title="' . $langs->trans('AssetFutureDepreciationLine') . '"></i>';
                $now = dol_now();

                foreach ($assetdepreciationoptions->deprecation_options_fields as $mode_key => $fields) {
                    $lines = $object->depreciation_lines[$mode_key];
                    if (!empty($lines)) {
                        $mode_info = $assetdepreciationoptions->deprecation_options_fields[$mode_key];
                        $depreciation_info = $assetdepreciationoptions->getGeneralDepreciationInfoForMode($mode_key);

                        print load_fiche_titre($langs->trans($mode_info['label']), '', '');

                        // Depreciation general info
                        //---------------------------------
                        print '<div class="fichecenter">';
                        print '<div class="fichehalfleft">';
                        print '<div class="underbanner clearboth"></div>';
                        print '<table class="border centpercent tableforfield">' . "\n";
                        print '<tr><td class="titlefield">' . $langs->trans('AssetBaseDepreciationHT') . '</td><td>' . price($depreciation_info['base_depreciation_ht']) . '</td></tr>';
                        print '<tr><td class="titlefield">' . $langs->trans('AssetDepreciationBeginDate') . '</td><td>' . dol_print_date($object->date_start > $object->date_acquisition ? $object->date_start : $object->date_acquisition, 'day') . '</td></tr>';
                        print '</table>';

                        // We close div and reopen for second column
                        print '</div>';
                        print '<div class="fichehalfright">';

                        print '<div class="underbanner clearboth"></div>';
                        print '<table class="border centpercent tableforfield">';
                        print '<tr><td class="titlefield">' . $langs->trans('AssetDepreciationDuration') . '</td><td>' . $depreciation_info['duration'] . ' ( ' . $depreciation_info['duration_type'] . ' )</td></tr>';
                        print '<tr><td class="titlefield">' . $langs->trans('AssetDepreciationRate') . '</td><td>' . $depreciation_info['rate'] . '</td></tr>';
                        print '</table>';
                        print '</div>';
                        print '</div>';
                        print '<div class="clearboth"></div>';

                        // Depreciation lines
                        //---------------------------------
                        print '<br>';
                        print '<div class="div-table-responsive-no-min">';
                        print '<table class="noborder allwidth">';

                        print '<tr class="liste_titre">';
                        print '<td class="width20"></td>';
                        print '<td>' . $langs->trans("Ref") . '</td>';
                        print '<td class="center">' . $langs->trans("AssetDepreciationDate") . '</td>';
                        print '<td class="right">' . $langs->trans("AssetDepreciationHT") . '</td>';
                        print '<td class="right">' . $langs->trans("AssetCumulativeDepreciationHT") . '</td>';
                        print '<td class="right">' . $langs->trans("AssetResidualHT") . '</td>';
                        print '</tr>';

                        if (empty($lines)) {
                            print '<tr><td class="impair center" colspan="6"><span class="opacitymedium">' . $langs->trans("None") . '</span></td></tr>';
                        } else {
                            foreach ($lines as $line) {
                                print '<tr class="oddeven">';
                                print '<td>' . ($line['bookkeeping'] ? $bookkeeping_icon : ($line['depreciation_date'] > $now ? $future_icon : '')) . '</td>';
                                print '<td >' . (empty($line['ref']) ? $langs->trans('AssetDepreciationReversal') : $line['ref']) . '</td>';
                                print '<td class="center">' . dol_print_date($line['depreciation_date'], 'day') . '</td>';
                                print '<td class="right">';
                                print price($line['depreciation_ht']);
                                print '</td>';
                                print '<td class="right">';
                                print price($line['cumulative_depreciation_ht']);
                                print '</td>';
                                print '<td class="right">';
                                print price(price2num($depreciation_info['base_depreciation_ht'] - $line['cumulative_depreciation_ht'], 'MT'));
                                print '</td>';
                                print "</tr>\n";
                            }
                        }

                        print '</table>';
                        print '</div>';
                    }
                }
            }
        }

// End of page
        llxFooter();
        $db->close();
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

        $form = new Form($db);

        $help_url = '';
        llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_depreciation_options');

        if ($id > 0 || !empty($ref)) {
            $head = assetPrepareHead($object);
            print dol_get_fiche_head($head, 'depreciation_options', $langs->trans("Asset"), -1, $object->picto);

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';

            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';
            print '</div>';

            if ($action == 'edit') {
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="update">';
                if ($backtopage) {
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                }
                if ($backtopageforcancel) {
                    print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';
                }

                print dol_get_fiche_head(array(), '');

                include DOL_DOCUMENT_ROOT . '/asset/tpl/depreciation_options_edit.tpl.php';

                print dol_get_fiche_end();

                print $form->buttonsSaveCancel();

                print '</form>';
            } else {
                include DOL_DOCUMENT_ROOT . '/asset/tpl/depreciation_options_view.tpl.php';
            }

            print dol_get_fiche_end();

            if ($action != 'edit') {
                print '<div class="tabsAction">' . "\n";
                $parameters = array();
                $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                if ($reshook < 0) {
                    setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
                }

                if (empty($reshook)) {
                    if ($object->status == $object::STATUS_DRAFT/* && !empty($object->enabled_modes)*/) {
                        print dolGetButtonAction($langs->trans('Modify'), '', 'default', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit&token=' . newToken(), '', $permissiontoadd);
                    }
                }
                print '</div>' . "\n";
            }
        }

// End of page
        llxFooter();
        $db->close();
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

        $form = new Form($db);

        $help_url = '';
        llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_disposal');

        if ($id > 0 || !empty($ref)) {
            $object->fetch_thirdparty();

            $head = assetPrepareHead($object);

            print dol_get_fiche_head($head, 'disposal', $langs->trans("Asset"), -1, $object->picto);

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';

            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent tableforfield">' . "\n";

            // Common attributes
            $show_fields = array('disposal_date', 'disposal_amount_ht', 'fk_disposal_type', 'disposal_depreciated', 'disposal_subject_to_vat');
            foreach ($object->fields as $field_key => $field_info) {
                $object->fields[$field_key]['visible'] = in_array($field_key, $show_fields) ? 1 : 0;
            }
            include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

            print '</table>';
            print '</div>';

            print dol_get_fiche_end();
        }

// End of page
        llxFooter();
        $db->close();
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

        $form = new Form($db);

        $title = $langs->trans("Asset") . ' - ' . $langs->trans("Files");
        $help_url = '';
        llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_documents');

        if ($object->id) {
            /*
             * Show tabs
             */
            $head = assetPrepareHead($object);

            print dol_get_fiche_head($head, 'document', $langs->trans("Asset"), -1, $object->picto);


            // Build file list
            $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
            $totalsize = 0;
            foreach ($filearray as $key => $file) {
                $totalsize += $file['size'];
            }

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . dol_buildpath('/asset/asset_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';

            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

            print '<div class="fichecenter">';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent tableforfield">';

            // Number of files
            print '<tr><td class="titlefield">' . $langs->trans("NbOfAttachedFiles") . '</td><td colspan="3">' . count($filearray) . '</td></tr>';

            // Total size
            print '<tr><td>' . $langs->trans("TotalSizeOfAttachedFiles") . '</td><td colspan="3">' . $totalsize . ' ' . $langs->trans("bytes") . '</td></tr>';

            print '</table>';

            print '</div>';

            print dol_get_fiche_end();

            $modulepart = 'asset';
            $permissiontoadd = $user->hasRight('asset', 'write');
            //  $permissiontoadd = 1;
            $permtoedit = $user->hasRight('asset', 'write');
            //  $permtoedit = 1;
            $param = '&id=' . $object->id;

            //$relativepathwithnofile='asset/' . dol_sanitizeFileName($object->id).'/';
            $relativepathwithnofile = dol_sanitizeFileName($object->ref) . '/';

            include DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
        } else {
            accessforbidden('', 0, 1);
        }

// End of page
        llxFooter();
        $db->close();
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
        $arrayfields = array();
        foreach ($object->fields as $key => $val) {
            // If $val['visible']==0, then we never show the field
            if (!empty($val['visible'])) {
                $visible = (int) dol_eval($val['visible'], 1);
                $arrayfields['t.' . $key] = array(
                    'label' => $val['label'],
                    'checked' => (($visible < 0) ? 0 : 1),
                    'enabled' => (abs($visible) != 3 && dol_eval($val['enabled'], 1)),
                    'position' => $val['position'],
                    'help' => isset($val['help']) ? $val['help'] : ''
                );
            }
        }
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

        $form = new Form($db);

        $now = dol_now();

        $help_url = '';
        $title = $langs->trans('ListOf', $langs->transnoentitiesnoconv("Assets"));
        $morejs = array();
        $morecss = array();


// Build and execute select
// --------------------------------------------------------------------
        $sql = 'SELECT ';
        $sql .= $object->getFieldList('t');
// Add fields from extrafields
        if (!empty($extrafields->attributes[$object->table_element]['label'])) {
            foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef." . $key . " as options_" . $key : '');
            }
        }
// Add fields from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql = preg_replace('/,\s*$/', '', $sql);

        $sqlfields = $sql; // $sql fields to remove for count total

        $sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as t";
        if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef on (t.rowid = ef.fk_object)";
        }
// Add table from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        if ($object->ismultientitymanaged == 1) {
            $sql .= " WHERE t.entity IN (" . getEntity($object->element) . ")";
        } else {
            $sql .= " WHERE 1 = 1";
        }
        foreach ($search as $key => $val) {
            if (array_key_exists($key, $object->fields)) {
                if ($key == 'status' && $search[$key] == -1) {
                    continue;
                }
                $mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
                if ((strpos($object->fields[$key]['type'], 'integer:') === 0) || (strpos($object->fields[$key]['type'], 'sellist:') === 0) || !empty($object->fields[$key]['arrayofkeyval'])) {
                    if ($search[$key] == '-1' || ($search[$key] === '0' && (empty($object->fields[$key]['arrayofkeyval']) || !array_key_exists('0', $object->fields[$key]['arrayofkeyval'])))) {
                        $search[$key] = '';
                    }
                    $mode_search = 2;
                }
                if ($search[$key] != '') {
                    $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
                }
            } else {
                if (preg_match('/(_dtstart|_dtend)$/', $key) && $search[$key] != '') {
                    $columnName = preg_replace('/(_dtstart|_dtend)$/', '', $key);
                    if (preg_match('/^(date|timestamp|datetime)/', $object->fields[$columnName]['type'])) {
                        if (preg_match('/_dtstart$/', $key)) {
                            $sql .= " AND t." . $columnName . " >= '" . $db->idate($search[$key]) . "'";
                        }
                        if (preg_match('/_dtend$/', $key)) {
                            $sql .= " AND t." . $columnName . " <= '" . $db->idate($search[$key]) . "'";
                        }
                    }
                }
            }
        }
        if ($search_all) {
            $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
        }
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;

        /* If a group by is required
        $sql .= " GROUP BY ";
        foreach($object->fields as $key => $val) {
            $sql .= "t.".$key.", ";
        }
        // Add fields from extrafields
        if (!empty($extrafields->attributes[$object->table_element]['label'])) {
            foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key.', ' : '');
            }
        }
        // Add where from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object);    // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql = preg_replace('/,\s*$/', '', $sql);
        */

// Add HAVING from hooks
        /*
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListHaving', $parameters, $object); // Note that $action and $object may have been modified by hook
        $sql .= !empty($hookmanager->resPrint) ? (" HAVING 1=1 " . $hookmanager->resPrint) : "";
        */

// Count total nb of records
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            /* The fast and low memory method to get and count full list converts the sql into a sql count */
            $sqlforcount = preg_replace('/^' . preg_quote($sqlfields, '/') . '/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
            $sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
            $resql = $db->query($sqlforcount);
            if ($resql) {
                $objforcount = $db->fetch_object($resql);
                $nbtotalofrecords = $objforcount->nbtotalofrecords;
            } else {
                dol_print_error($db);
            }

            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
            $db->free($resql);
        }

// Complete request and execute it with limit
        $sql .= $db->order($sortfield, $sortorder);
        if ($limit) {
            $sql .= $db->plimit($limit + 1, $offset);
        }

        $resql = $db->query($sql);
        if (!$resql) {
            dol_print_error($db);
            exit;
        }

        $num = $db->num_rows($resql);


// Direct jump if only one record found
        if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
            $obj = $db->fetch_object($resql);
            $id = $obj->rowid;
            header("Location: " . DOL_URL_ROOT . '/asset/card.php?id=' . $id);
            exit;
        }


// Output page
// --------------------------------------------------------------------

        llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'mod-asset page-list');

        $arrayofselected = is_array($toselect) ? $toselect : array();

        $param = '';
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . ((int) $limit);
        }
        foreach ($search as $key => $val) {
            if (is_array($search[$key]) && count($search[$key])) {
                foreach ($search[$key] as $skey) {
                    if ($skey != '') {
                        $param .= '&search_' . $key . '[]=' . urlencode($skey);
                    }
                }
            } elseif ($search[$key] != '') {
                $param .= '&search_' . $key . '=' . urlencode($search[$key]);
            }
        }
        if ($optioncss != '') {
            $param .= '&optioncss=' . urlencode($optioncss);
        }
// Add $param from extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
        $param .= $hookmanager->resPrint;

// List of mass actions available
        $arrayofmassactions = array(
            //'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
            //'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
            //'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
            //'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
        );
        if ($permissiontodelete) {
            $arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans("Delete");
        }
        if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
            $arrayofmassactions = array();
        }
        $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

        print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
        if ($optioncss != '') {
            print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
        }
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="page" value="' . $page . '">';
        print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

        $newcardbutton = '';
        $newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/asset/card.php?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

        print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_' . $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
        $topicmail = "SendAssetRef";
        $modelmail = "asset";
        $objecttmp = new Asset($db);
        $trackid = 'asset' . $object->id;
        include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

        if ($search_all) {
            foreach ($fieldstosearchall as $key => $val) {
                $fieldstosearchall[$key] = $langs->trans($val);
            }
            print '<div class="divsearchfieldfilter">' . $langs->trans("FilterOnInto", $search_all) . implode(', ', $fieldstosearchall) . '</div>';
        }

        $moreforfilter = '';
        /*$moreforfilter.='<div class="divsearchfield">';
        $moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
        $moreforfilter.= '</div>';*/

        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
        if (empty($reshook)) {
            $moreforfilter .= $hookmanager->resPrint;
        } else {
            $moreforfilter = $hookmanager->resPrint;
        }

        if (!empty($moreforfilter)) {
            print '<div class="liste_titre liste_titre_bydiv centpercent">';
            print $moreforfilter;
            print '</div>';
        }

        $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
        $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
        $selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

        print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
        print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";


// Fields title search
// --------------------------------------------------------------------
        print '<tr class="liste_titre">';
        foreach ($object->fields as $key => $val) {
            $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
            if ($key == 'status') {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
            } elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
            } elseif (in_array($val['type'], array('timestamp'))) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
            } elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
            }
            if (!empty($arrayfields['t.' . $key]['checked'])) {
                print '<td class="liste_titre' . ($cssforfield ? ' ' . $cssforfield : '') . '">';
                if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
                    print $form->selectarray('search_' . $key, $val['arrayofkeyval'], (isset($search[$key]) ? $search[$key] : ''), $val['notnull'], 0, 0, '', 1, 0, 0, '', 'maxwidth100', 1);
                } elseif ((strpos($val['type'], 'integer:') === 0) || (strpos($val['type'], 'sellist:') === 0)) {
                    print $object->showInputField($val, $key, (isset($search[$key]) ? $search[$key] : ''), '', '', 'search_', 'maxwidth125', 1);
                } elseif (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
                    print '<div class="nowrap">';
                    print $form->selectDate($search[$key . '_dtstart'] ? $search[$key . '_dtstart'] : '', "search_" . $key . "_dtstart", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
                    print '</div>';
                    print '<div class="nowrap">';
                    print $form->selectDate($search[$key . '_dtend'] ? $search[$key . '_dtend'] : '', "search_" . $key . "_dtend", 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
                    print '</div>';
                } elseif ($key == 'lang') {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
                    $formadmin = new FormAdmin($db);
                    print $formadmin->select_language($search[$key], 'search_lang', 0, null, 1, 0, 0, 'minwidth150 maxwidth200', 2);
                } else {
                    print '<input type="text" class="flat maxwidth75" name="search_' . $key . '" value="' . dol_escape_htmltag(isset($search[$key]) ? $search[$key] : '') . '">';
                }
                print '</td>';
            }
        }
// Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
        $parameters = array('arrayfields' => $arrayfields);
        $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
// Action column
        print '<td class="liste_titre maxwidthsearch">';
        $searchpicto = $form->showFilterButtons();
        print $searchpicto;
        print '</td>';
        print '</tr>' . "\n";


// Fields title label
// --------------------------------------------------------------------
        print '<tr class="liste_titre">';
        foreach ($object->fields as $key => $val) {
            $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
            if ($key == 'status') {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
            } elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
            } elseif (in_array($val['type'], array('timestamp'))) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
            } elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID' && empty($val['arrayofkeyval'])) {
                $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
            }
            if (!empty($arrayfields['t.' . $key]['checked'])) {
                print getTitleFieldOfList($arrayfields['t.' . $key]['label'], 0, $_SERVER['PHP_SELF'], 't.' . $key, '', $param, ($cssforfield ? 'class="' . $cssforfield . '"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield . ' ' : '')) . "\n";
            }
        }
// Extra fields
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
        $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
// Action column
        print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
        print '</tr>' . "\n";


// Detect if we need a fetch on each output line
        $needToFetchEachLine = 0;
        if (isset($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0) {
            foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val) {
                if (!is_null($val) && preg_match('/\$object/', $val)) {
                    $needToFetchEachLine++; // There is at least one compute field that use $object
                }
            }
        }


// Loop on record
// --------------------------------------------------------------------
        $i = 0;
        $totalarray = array();
        $totalarray['nbfield'] = 0;
        while ($i < ($limit ? min($num, $limit) : $num)) {
            $obj = $db->fetch_object($resql);
            if (empty($obj)) {
                break; // Should not happen
            }

            // Store properties in $object
            $object->setVarsFromFetchObj($obj);

            // Show here line of result
            print '<tr class="oddeven">';
            foreach ($object->fields as $key => $val) {
                $cssforfield = (empty($val['csslist']) ? (empty($val['css']) ? '' : $val['css']) : $val['csslist']);
                if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) {
                    $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
                } elseif ($key == 'status') {
                    $cssforfield .= ($cssforfield ? ' ' : '') . 'center';
                }

                if (in_array($val['type'], array('timestamp'))) {
                    $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
                } elseif ($key == 'ref') {
                    $cssforfield .= ($cssforfield ? ' ' : '') . 'nowrap';
                }

                if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && !in_array($key, array('rowid', 'status')) && empty($val['arrayofkeyval'])) {
                    $cssforfield .= ($cssforfield ? ' ' : '') . 'right';
                }
                //if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

                if (!empty($arrayfields['t.' . $key]['checked'])) {
                    print '<td' . ($cssforfield ? ' class="' . $cssforfield . '"' : '') . '>';
                    if ($key == 'status') {
                        print $object->getLibStatut(5);
                    } elseif ($key == 'rowid') {
                        print $object->showOutputField($val, $key, $object->id, '');
                    } else {
                        print $object->showOutputField($val, $key, $object->$key, '');
                    }
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                    if (!empty($val['isameasure']) && $val['isameasure'] == 1) {
                        if (!$i) {
                            $totalarray['pos'][$totalarray['nbfield']] = 't.' . $key;
                        }
                        if (!isset($totalarray['val'])) {
                            $totalarray['val'] = array();
                        }
                        if (!isset($totalarray['val']['t.' . $key])) {
                            $totalarray['val']['t.' . $key] = 0;
                        }
                        $totalarray['val']['t.' . $key] += $object->$key;
                    }
                }
            }
            // Extra fields
            include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_print_fields.tpl.php';
            // Fields from hook
            $parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
            $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            // Action column
            print '<td class="nowrap center">';
            if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                $selected = 0;
                if (in_array($object->id, $arrayofselected)) {
                    $selected = 1;
                }
                print '<input id="cb' . $object->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $object->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
            }
            print '</td>';
            if (!$i) {
                $totalarray['nbfield']++;
            }

            print '</tr>' . "\n";

            $i++;
        }

// Show total line
        include DOL_DOCUMENT_ROOT . '/core/tpl/list_print_total.tpl.php';

// If no record found
        if ($num == 0) {
            $colspan = 1;
            foreach ($arrayfields as $key => $val) {
                if (!empty($val['checked'])) {
                    $colspan++;
                }
            }
            print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
        }


        $db->free($resql);

        $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print '</table>' . "\n";
        print '</div>' . "\n";

        print '</form>' . "\n";

        if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
            $hidegeneratedfilelistifempty = 1;
            if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
                $hidegeneratedfilelistifempty = 0;
            }

            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
            $formfile = new FormFile($db);

            // Show list of available documents
            $urlsource = $_SERVER['PHP_SELF'] . '?sortfield=' . $sortfield . '&sortorder=' . $sortorder;
            $urlsource .= str_replace('&amp;', '&', $param);

            $filedir = $diroutputmassaction;
            $genallowed = $permissiontoread;
            $delallowed = $permissiontoadd;

            print $formfile->showdocuments('massfilesarea_asset', '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
        }

// End of page
        llxFooter();
        $db->close();
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

        $form = new Form($db);

        $help_url = '';
        llxHeader('', $langs->trans('Asset'), $help_url, '', 0, 0, '', '', '', 'mod-asset page-card_notes');

        if ($id > 0 || !empty($ref)) {
            $object->fetch_thirdparty();

            $head = assetPrepareHead($object);

            print dol_get_fiche_head($head, 'note', $langs->trans("Asset"), -1, $object->picto);

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/asset/list.php?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

            $morehtmlref = '<div class="refidno">';
            $morehtmlref .= '</div>';


            dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


            print '<div class="fichecenter">';
            print '<div class="underbanner clearboth"></div>';


            $cssclass = "titlefield";
            include DOL_DOCUMENT_ROOT . '/core/tpl/notes.tpl.php';

            print '</div>';

            print dol_get_fiche_end();
        }

// End of page
        llxFooter();
        $db->close();
    }

}
