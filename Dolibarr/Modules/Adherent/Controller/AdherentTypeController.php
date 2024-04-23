<?php

/* Copyright (C) 2001-2002  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2019-2022	Thibault Foucart		<support@ptibogxiv.net>
 * Copyright (C) 2020		Josep Lluís Amador		<joseplluis@lliuretic.cat>
 * Copyright (C) 2021		Waël Almoman			<info@almoman.com>
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

namespace DoliModules\Adherent\Controller;

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

use DoliCore\Base\DolibarrController;
use DoliCore\Lib\Menu;
use DoliModules\Adherent\Model\AdherentType;
use ExtraFields;

class AdherentTypeController extends DolibarrController
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

        $menu = Menu::loadMenu();
        // dd($menu);

        // Load translation files required by the page
        $this->langs->load("members");

        $rowid = GETPOSTINT('rowid');
        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $cancel = GETPOST('cancel', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');
        $mode = GETPOST('mode', 'alpha');

        $sall = GETPOST("sall", "alpha");
        $filter = GETPOST("filter", 'alpha');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_lastname = GETPOST('search_lastname', 'alpha');
        $search_login = GETPOST('search_login', 'alpha');
        $search_email = GETPOST('search_email', 'alpha');
        $type = GETPOST('type', 'intcomma');
        $status = GETPOST('status', 'alpha');
        $optioncss = GETPOST('optioncss', 'alpha');

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
            $sortfield = "d.lastname";
        }

        $label = GETPOST("label", "alpha");
        $morphy = GETPOST("morphy", "alpha");
        $status = GETPOSTINT("status");
        $subscription = GETPOSTINT("subscription");
        $amount = GETPOST('amount', 'alpha');
        $duration_value = GETPOSTINT('duration_value');
        $duration_unit = GETPOST('duration_unit', 'alpha');
        $vote = GETPOSTINT("vote");
        $comment = GETPOST("comment", 'restricthtml');
        $mail_valid = GETPOST("mail_valid", 'restricthtml');
        $caneditamount = GETPOSTINT("caneditamount");

        // Initialize technical objects
        $object = new AdherentType($db);
        $extrafields = new ExtraFields($db);
        $this->hookmanager->initHooks(['membertypecard', 'globalcard']);

        // Fetch optionals attributes and labels
        $extrafields->fetch_name_optionals_label($object->table_element);

        // Security check
        $result = restrictedArea($user, 'adherent', $rowid, 'adherent_type');

        /*
         *  Actions
         */

        if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
            $search_ref = "";
            $search_lastname = "";
            $search_login = "";
            $search_email = "";
            $type = "";
            $sall = "";
        }

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }

        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
            $massaction = '';
        }

        if ($cancel) {
            $action = '';

            if (!empty($backtopage)) {
                header("Location: " . $backtopage);
                exit;
            }
        }

        if ($action == 'add' && $user->hasRight('adherent', 'configurer')) {
            $object->label = trim($label);
            $object->morphy = trim($morphy);
            $object->status = (int) $status;
            $object->subscription = (int) $subscription;
            $object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
            $object->caneditamount = $caneditamount;
            $object->duration_value = $duration_value;
            $object->duration_unit = $duration_unit;
            $object->note_public = trim($comment);
            $object->note_private = '';
            $object->mail_valid = trim($mail_valid);
            $object->vote = (int) $vote;

            // Fill array 'array_options' with data from add form
            $ret = $extrafields->setOptionalsFromPost(null, $object);
            if ($ret < 0) {
                $error++;
            }

            if (empty($object->label)) {
                $error++;
                setEventMessages($this->langs->trans("ErrorFieldRequired", $this->langs->transnoentities("Label")), null, 'errors');
            } else {
                $sql = "SELECT libelle FROM " . MAIN_DB_PREFIX . "adherent_type WHERE libelle = '" . $this->db->escape($object->label) . "'";
                $sql .= " WHERE entity IN (" . getEntity('member_type') . ")";
                $result = $this->db->query($sql);
                $num = null;
                if ($result) {
                    $num = $this->db->num_rows($result);
                }
                if ($num) {
                    $error++;
                    $this->langs->load("errors");
                    setEventMessages($this->langs->trans("ErrorLabelAlreadyExists", $login), null, 'errors');
                }
            }

            if (!$error) {
                $id = $object->create($user);
                if ($id > 0) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $action = 'create';
                }
            } else {
                $action = 'create';
            }
        }

        if ($action == 'update' && $user->hasRight('adherent', 'configurer')) {
            $object->fetch($rowid);

            $object->oldcopy = dol_clone($object, 2);

            $object->label = trim($label);
            $object->morphy = trim($morphy);
            $object->status = (int) $status;
            $object->subscription = (int) $subscription;
            $object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
            $object->caneditamount = $caneditamount;
            $object->duration_value = $duration_value;
            $object->duration_unit = $duration_unit;
            $object->note_public = trim($comment);
            $object->note_private = '';
            $object->mail_valid = trim($mail_valid);
            $object->vote = (bool) trim($vote);

            // Fill array 'array_options' with data from add form
            $ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
            if ($ret < 0) {
                $error++;
            }

            $ret = $object->update($user);

            if ($ret >= 0 && !count($object->errors)) {
                setEventMessages($this->langs->trans("MemberTypeModified"), null, 'mesgs');
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?rowid=" . $object->id);
            exit;
        }

        if ($action == 'confirm_delete' && $user->hasRight('adherent', 'configurer')) {
            $object->fetch($rowid);
            $res = $object->delete($user);

            if ($res > 0) {
                setEventMessages($this->langs->trans("MemberTypeDeleted"), null, 'mesgs');
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                setEventMessages($this->langs->trans("MemberTypeCanNotBeDeleted"), null, 'errors');
                $action = '';
            }
        }

        $this->db->close();

        $this->template = '/page/adherent/type_list';

        return true;
    }
}
