<?php

/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2023  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
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

namespace DoliModules\Adherent\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Functions2.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Member.php';

use DoliCore\Base\Controller\DolibarrController;
use DoliModules\Adherent\Model\Subscription;
use DoliCore\Lib\ExtraFields;

class AdherentSubscriptionController extends DolibarrController
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

        $this->card();

        return true;
    }

    /**
     *       \file       htdocs/adherents/subscription/card.php
     *       \ingroup    member
     *       \brief      Page to add/edit/remove a member subscription
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
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(array("companies", "members", "bills", "users", "banks"));

        $adh = new Adherent($db);
        $adht = new AdherentType($db);
        $object = new Subscription($db);
        $errmsg = '';

        $action = GETPOST("action", 'alpha');
        $rowid = GETPOSTINT("rowid") ? GETPOSTINT("rowid") : GETPOSTINT("id");
        $typeid = GETPOSTINT("typeid");
        $cancel = GETPOST('cancel', 'alpha');
        $confirm = GETPOST('confirm');
        $note = GETPOST('note', 'alpha');
        $typeid = GETPOSTINT('typeid');
        $amount = (float) price2num(GETPOST('amount', 'alpha'), 'MT');

        if (!$user->hasRight('adherent', 'cotisation', 'lire')) {
            accessforbidden();
        }

        $permissionnote = $user->hasRight('adherent', 'cotisation', 'creer'); // Used by the include of actions_setnotes.inc.php
        $permissiondellink = $user->hasRight('adherent', 'cotisation', 'creer'); // Used by the include of actions_dellink.inc.php
        $permissiontoedit = $user->hasRight('adherent', 'cotisation', 'creer'); // Used by the include of actions_lineupdonw.inc.php

        $hookmanager->initHooks(array('subscriptioncard', 'globalcard'));

// Security check
        $result = restrictedArea($user, 'subscription', 0); // TODO Check on object id


        /*
         *  Actions
         */

        if ($cancel) {
            $action = '';
        }

//include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

        include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php'; // Must be include, not include_once

//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be include, not include_once


        if ($user->hasRight('adherent', 'cotisation', 'creer') && $action == 'update' && !$cancel) {
            // Load current object
            $result = $object->fetch($rowid);
            if ($result > 0) {
                $db->begin();

                $errmsg = '';

                $newdatestart = dol_mktime(GETPOSTINT('datesubhour'), GETPOSTINT('datesubmin'), 0, GETPOSTINT('datesubmonth'), GETPOSTINT('datesubday'), GETPOSTINT('datesubyear'));
                $newdateend = dol_mktime(GETPOSTINT('datesubendhour'), GETPOSTINT('datesubendmin'), 0, GETPOSTINT('datesubendmonth'), GETPOSTINT('datesubendday'), GETPOSTINT('datesubendyear'));

                if ($object->fk_bank > 0) {
                    $accountline = new AccountLine($db);
                    $result = $accountline->fetch($object->fk_bank);

                    // If transaction consolidated
                    if ($accountline->rappro) {
                        $errmsg = $langs->trans("SubscriptionLinkedToConciliatedTransaction");
                    } else {
                        $accountline->datev = $newdatestart;
                        $accountline->dateo = $newdatestart;
                        $accountline->amount = $amount;

                        $result = $accountline->update($user);
                        if ($result < 0) {
                            $errmsg = $accountline->error;
                        }
                    }
                }

                if (!$errmsg) {
                    // Modify values
                    $object->dateh = $newdatestart;
                    $object->datef = $newdateend;
                    $object->fk_type = $typeid;
                    $object->note_public = $note;
                    $object->note_private = $note;

                    $object->amount = $amount;

                    $result = $object->update($user);
                    if ($result >= 0 && !count($object->errors)) {
                        $db->commit();

                        header("Location: card.php?rowid=" . $object->id);
                        exit;
                    } else {
                        $db->rollback();

                        if ($object->error) {
                            $errmsg = $object->error;
                        } else {
                            foreach ($object->errors as $error) {
                                if ($errmsg) {
                                    $errmsg .= '<br>';
                                }
                                $errmsg .= $error;
                            }
                        }
                        $action = '';
                    }
                } else {
                    $db->rollback();
                }
            }
        }

        if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('adherent', 'cotisation', 'creer')) {
            $result = $object->fetch($rowid);
            $result = $object->delete($user);
            if ($result > 0) {
                header("Location: " . DOL_URL_ROOT . "/adherents/card.php?rowid=" . $object->fk_adherent);
                exit;
            } else {
                $errmesg = $adh->error;
            }
        }

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/subscription_index.php');

        $db->close();
        return true;
    }

    /**
     *      \file       htdocs/adherents/subscription/info.php
     *      \ingroup    member
     *      \brief      Page with information of subscriptions of a member
     */
    public function info()
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
        $langs->loadLangs(array("companies", "members", "bills", "users"));

        if (!$user->hasRight('adherent', 'lire')) {
            accessforbidden();
        }

        $rowid = GETPOSTINT("rowid");

        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/subscription_info.php');

        $db->close();
        return true;
    }

    /**
     *      \file       htdocs/adherents/subscription/list.php
     *      \ingroup    member
     *      \brief      list of subscription
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
        global $mysoc;

        $langs->loadLangs(array("members", "companies", "banks"));

        $action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'create'/'add', 'edit'/'update', 'view', ...
        $massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
        $show_files = GETPOSTINT('show_files'); // Show files area generated by bulk actions ?
        $confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
        $cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
        $toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
        $optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
        $mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

        $statut = (GETPOSTISSET("statut") ? GETPOST("statut", "alpha") : 1);
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_type = GETPOST('search_type', 'alpha');
        $search_lastname = GETPOST('search_lastname', 'alpha');
        $search_firstname = GETPOST('search_firstname', 'alpha');
        $search_login = GETPOST('search_login', 'alpha');
        $search_note = GETPOST('search_note', 'alpha');
        $search_account = GETPOSTINT('search_account');
        $search_amount = GETPOST('search_amount', 'alpha');
        $search_all = '';

        $date_select = GETPOST("date_select", 'alpha');

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
        if (!$sortorder) {
            $sortorder = "DESC";
        }
        if (!$sortfield) {
            $sortfield = "c.dateadh";
        }

// Initialize technical objects
        $object = new Subscription($db);
        $extrafields = new ExtraFields($db);
        $hookmanager->initHooks(array('subscriptionlist'));

// Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        $search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
        $fieldstosearchall = array(
        );
        $arrayfields = array(
            'd.ref' => array('label' => "Ref", 'checked' => 1),
            'd.fk_type' => array('label' => "Type", 'checked' => 1),
            'd.lastname' => array('label' => "Lastname", 'checked' => 1),
            'd.firstname' => array('label' => "Firstname", 'checked' => 1),
            'd.login' => array('label' => "Login", 'checked' => 1),
            't.libelle' => array('label' => "Label", 'checked' => 1),
            'd.bank' => array('label' => "BankAccount", 'checked' => 1, 'enabled' => (isModEnabled('bank'))),
            /*'d.note_public'=>array('label'=>"NotePublic", 'checked'=>0),
             'd.note_private'=>array('label'=>"NotePrivate", 'checked'=>0),*/
            'c.dateadh' => array('label' => "DateSubscription", 'checked' => 1, 'position' => 100),
            'c.datef' => array('label' => "EndSubscription", 'checked' => 1, 'position' => 101),
            'd.amount' => array('label' => "Amount", 'checked' => 1, 'position' => 102),
            'c.datec' => array('label' => "DateCreation", 'checked' => 0, 'position' => 500),
            'c.tms' => array('label' => "DateModificationShort", 'checked' => 0, 'position' => 500),
//  'd.statut'=>array('label'=>"Status", 'checked'=>1, 'position'=>1000)
        );

// Security check
        $result = restrictedArea($user, 'adherent', '', '', 'cotisation');

        $permissiontodelete = $user->hasRight('adherent', 'cotisation', 'creer');


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

        $parameters = array('socid' => isset($socid) ? $socid : null);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            // Selection of new fields
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $search_type = "";
                $search_ref = "";
                $search_lastname = "";
                $search_firstname = "";
                $search_login = "";
                $search_note = "";
                $search_amount = "";
                $search_account = "";
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
            $objectclass = 'Subscription';
            $objectlabel = 'Subscription';
            $uploaddir = $conf->adherent->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/subscription_list.php');

        $db->close();
        return true;
    }
}
