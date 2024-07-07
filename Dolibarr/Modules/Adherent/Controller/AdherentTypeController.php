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
require_once BASE_PATH . '/main.inc.php';

use DoliCore\Base\Controller\DolibarrController;
use DoliCore\Lib\ExtraFields;
use DoliCore\Tools\Load;
use DoliModules\Adherent\Model\AdherentType;

class AdherentTypeController extends DolibarrController
{
    public $rowid;
    public $extrafields;
    public $massaction;
    public $cancel;
    public $toselect;
    public $contextpage;
    public $backtopage;
    public $mode;
    public $sall;
    public $filter;
    public $search_ref;
    public $search_lastname;
    public $search_login;
    public $search_email;
    public $type;
    public $status;
    public $optioncss;
    public $limit;
    public $sortfield;
    public $sortorder;
    public $page;
    public $pagenext;
    public $label;
    public $morphy;
    public $offset;
    public $pageprev;
    public $subscription;
    public $amount;
    public $duration_value;
    public $duration_unit;
    public $vote;
    public $comment;
    public $mail_valid;
    public $caneditamount;

    /**
     *      \file       htdocs/adherents/type.php
     *      \ingroup    member
     *      \brief      Member's type setup
     */
    public function doIndex(): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        // Load translation files required by the page
        $this->langs->load("members");

        $action = $this->filterPost('action', 'aZ09');
        if ($action === 'create') {
            $this->template = '/page/adherent/type_edit';
            return true;
        }

        if ($action === 'cancel') {
            $this->template = '/page/adherent/type_list';
            return true;
        }

        /*
         *  Actions
         */

        if ($this->filterPost('button_removefilter_x', 'alpha') || $this->filterPost('button_removefilter_x', 'alpha') || $this->filterPost('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
            $search_ref = "";
            $search_lastname = "";
            $search_login = "";
            $search_email = "";
            $type = "";
            $sall = "";
        }

        if ($this->filterPost('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }

        if (!$this->filterPost('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
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
            $this->object->label = trim($label);
            $this->object->morphy = trim($morphy);
            $this->object->status = (int)$status;
            $this->object->subscription = (int)$subscription;
            $this->object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
            $this->object->caneditamount = $caneditamount;
            $this->object->duration_value = $duration_value;
            $this->object->duration_unit = $duration_unit;
            $this->object->note_public = trim($comment);
            $this->object->note_private = '';
            $this->object->mail_valid = trim($mail_valid);
            $this->object->vote = (int)$vote;

            // Fill array 'array_options' with data from add form
            $ret = $extrafields->setOptionalsFromPost(null, $this->object);
            if ($ret < 0) {
                $error++;
            }

            if (empty($this->object->label)) {
                $error++;
                setEventMessages($this->langs->trans("ErrorFieldRequired", $this->langs->transnoentities("Label")), null, 'errors');
            } else {
                $sql = "SELECT libelle FROM " . MAIN_DB_PREFIX . "adherent_type WHERE libelle = '" . $this->db->escape($this->object->label) . "'";
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
                $id = $this->object->create($user);
                if ($id > 0) {
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    setEventMessages($this->object->error, $this->object->errors, 'errors');
                    $action = 'create';
                }
            } else {
                $action = 'create';
            }
        }

        if ($action == 'update' && $user->hasRight('adherent', 'configurer')) {
            $this->object->fetch($this->rowid);

            $this->object->oldcopy = dol_clone($this->object, 2);

            $this->object->label = trim($label);
            $this->object->morphy = trim($morphy);
            $this->object->status = (int)$status;
            $this->object->subscription = (int)$subscription;
            $this->object->amount = ($amount == '' ? '' : price2num($amount, 'MT'));
            $this->object->caneditamount = $caneditamount;
            $this->object->duration_value = $duration_value;
            $this->object->duration_unit = $duration_unit;
            $this->object->note_public = trim($comment);
            $this->object->note_private = '';
            $this->object->mail_valid = trim($mail_valid);
            $this->object->vote = (bool)trim($vote);

            // Fill array 'array_options' with data from add form
            $ret = $extrafields->setOptionalsFromPost(null, $this->object, '@GETPOSTISSET');
            if ($ret < 0) {
                $error++;
            }

            $ret = $this->object->update($user);

            if ($ret >= 0 && !count($this->object->errors)) {
                setEventMessages($this->langs->trans("MemberTypeModified"), null, 'mesgs');
            } else {
                setEventMessages($this->object->error, $this->object->errors, 'errors');
            }

            header("Location: " . $_SERVER['PHP_SELF'] . "?rowid=" . $this->object->id);
            exit;
        }

        if ($action == 'confirm_delete' && $user->hasRight('adherent', 'configurer')) {
            $this->object->fetch($this->rowid);
            $res = $this->object->delete($user);

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
        if ($this->rowid) {
            if ($_GET['action'] === 'edit') {
                $this->template = '/page/adherent/type_edit';
            } else {
                $this->template = '/page/adherent/type_show';
            }
        }


        $this->menu = [];
        foreach ($menu as $item) {
            if ($item['type'] === 'left' || !$item['enabled']) {
                continue;
            }
            $this->menu[] = [
                'name' => $item['mainmenu'],
                'href' => BASE_URL . $item['url'],
                'prefix' => $item['prefix'],
                'title' => $item['titre'],
                'selected' => false,
            ];
        }

        return true;
    }

    public function loadRecord()
    {
        // Initialize technical objects
        $this->object = new AdherentType($this->db);
        $this->extrafields = new ExtraFields($this->db);
        $this->hookmanager->initHooks(['membertypecard', 'globalcard']);

        // Fetch optionals attributes and labels
        $this->extrafields->fetch_name_optionals_label($this->object->table_element);

        $this->rowid = $this->filterPostInt('rowid');
        if ($this->rowid) {
            $this->object->fetch($this->rowid);
        }

        return true;
    }

    public function loadPost()
    {
        $this->massaction = $this->filterPost('massaction', 'alpha');
        $this->cancel = $this->filterPost('cancel', 'alpha');
        $this->toselect = $this->filterPost('toselect', 'array');
        $this->contextpage = $this->filterPost('contextpage', 'aZ') ? $this->filterPost('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
        $this->backtopage = $this->filterPost('backtopage', 'alpha');
        $this->mode = $this->filterPost('mode', 'alpha');

        $this->sall = $this->filterPost("sall", "alpha");
        $this->filter = $this->filterPost("filter", 'alpha');
        $this->search_ref = $this->filterPost('search_ref', 'alpha');
        $this->search_lastname = $this->filterPost('search_lastname', 'alpha');
        $this->search_login = $this->filterPost('search_login', 'alpha');
        $this->search_email = $this->filterPost('search_email', 'alpha');
        $this->type = $this->filterPost('type', 'intcomma');
        $this->status = $this->filterPost('status', 'alpha');
        $this->optioncss = $this->filterPost('optioncss', 'alpha');

        // Load variable for pagination (move to trait? Create a class? A component?)
        $this->limit = $this->filterPostInt('limit') ? $this->filterPostInt('limit') : $this->conf->liste_limit;
        $this->sortfield = $this->filterPost('sortfield', 'aZ09comma');
        $this->sortorder = $this->filterPost('sortorder', 'aZ09comma');
        $this->page = GETPOSTISSET('pageplusone') ? ($this->filterPostInt('pageplusone') - 1) : $this->filterPostInt("page");
        if (empty($this->page) || $this->page < 0 || $this->filterPost('button_search', 'alpha') || $this->filterPost('button_removefilter', 'alpha')) {
            // If $this->page is not defined, or '' or -1 or if we click on clear filters
            $this->page = 0;
        }
        $this->offset = $this->limit * $this->page;
        $this->pageprev = $this->page - 1;
        $this->pagenext = $this->page + 1;
        if (!$this->sortorder) {
            $this->sortorder = "DESC";
        }
        if (!$this->sortfield) {
            $this->sortfield = "d.lastname";
        }

        $this->label = $this->filterPost("label", "alpha");
        $this->morphy = $this->filterPost("morphy", "alpha");
        $this->status = $this->filterPostInt("status");
        $this->subscription = $this->filterPostInt("subscription");
        $this->amount = $this->filterPost('amount', 'alpha');
        $this->duration_value = $this->filterPostInt('duration_value');
        $this->duration_unit = $this->filterPost('duration_unit', 'alpha');
        $this->vote = $this->filterPostInt("vote");
        $this->comment = $this->filterPost("comment", 'restricthtml');
        $this->mail_valid = $this->filterPost("mail_valid", 'restricthtml');
        $this->caneditamount = $this->filterPostInt("caneditamount");

        return true;
    }
}
