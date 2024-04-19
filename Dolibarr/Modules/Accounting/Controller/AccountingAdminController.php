<?php

/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2024  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2011-2019  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Remy Younes             <ryounes@gmail.com>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@ltairis.fr>
 * Copyright (C) 2011-2024  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2014-2015  Ari Elbaz (elarifr)     <github@accedinfo.com>
 * Copyright (C) 2014       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Jamal Elbaz             <jamelbaz@gmail.pro>
 * Copyright (C) 2016       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
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

namespace DoliModules\Accounting\Controller;

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
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fiscalyear.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';

use DoliCore\Base\DolibarrController;
use DoliCore\Form\FormAccounting;
use DoliCore\Form\FormAdmin;
use DoliCore\Form\FormCompany;
use DoliModules\Accounting\Model\AccountancyCategory;
use DoliModules\Accounting\Model\AccountancyExport;
use DoliModules\Accounting\Model\AccountingAccount;
use Fiscalyear;

class AccountingAdminController extends DolibarrController
{
    /**
     * \file        htdocs/accountancy/admin/account.php
     * \ingroup     Accountancy (Double entries)
     * \brief       List accounting account
     */
    public function account()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(['accountancy', 'admin', 'bills', 'compta', 'salaries']);

        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');
        $id = GETPOSTINT('id');
        $rowid = GETPOSTINT('rowid');
        $massaction = GETPOST('massaction', 'aZ09');
        $optioncss = GETPOST('optioncss', 'alpha');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accountingaccountlist'; // To manage different context of search
        $mode = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

        $search_account = GETPOST('search_account', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_labelshort = GETPOST('search_labelshort', 'alpha');
        $search_accountparent = GETPOST('search_accountparent', 'alpha');
        $search_pcgtype = GETPOST('search_pcgtype', 'alpha');
        $search_import_key = GETPOST('search_import_key', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $confirm = GETPOST('confirm', 'alpha');

        $chartofaccounts = GETPOSTINT('chartofaccounts');

        $permissiontoadd = $user->hasRight('accounting', 'chartofaccount');
        $permissiontodelete = $user->hasRight('accounting', 'chartofaccount');

// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

// Load variable for pagination
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
        if (!$sortfield) {
            $sortfield = "aa.account_number";
        }
        if (!$sortorder) {
            $sortorder = "ASC";
        }

        $arrayfields = [
            'aa.account_number' => ['label' => "AccountNumber", 'checked' => 1],
            'aa.label' => ['label' => "Label", 'checked' => 1],
            'aa.labelshort' => ['label' => "LabelToShow", 'checked' => 1],
            'aa.account_parent' => ['label' => "Accountparent", 'checked' => 1],
            'aa.pcg_type' => ['label' => "Pcgtype", 'checked' => 1, 'help' => 'PcgtypeDesc'],
            'categories' => ['label' => "AccountingCategories", 'checked' => -1, 'help' => 'AccountingCategoriesDesc'],
            'aa.reconcilable' => ['label' => "Reconcilable", 'checked' => 1],
            'aa.import_key' => ['label' => "ImportId", 'checked' => -1, 'help' => ''],
            'aa.active' => ['label' => "Activated", 'checked' => 1],
        ];

        if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
            unset($arrayfields['categories']);
            unset($arrayfields['aa.reconcilable']);
        }

        $accounting = new AccountingAccount($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
        $hookmanager->initHooks(['accountancyadminaccount']);


        /*
         * Actions
         */

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha')) {
            $massaction = '';
        }

        $parameters = ['chartofaccounts' => $chartofaccounts, 'permissiontoadd' => $permissiontoadd, 'permissiontodelete' => $permissiontodelete];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $accounting, $action); // Note that $action and $object may have been monowraponalldified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            if (!empty($cancel)) {
                $action = '';
            }

            $objectclass = 'AccountingAccount';
            $uploaddir = $conf->accounting->multidir_output[$conf->entity];
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

            if ($action == "delete") {
                $action = "";
            }
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
                $search_account = "";
                $search_label = "";
                $search_labelshort = "";
                $search_accountparent = "";
                $search_pcgtype = "";
                $search_import_key = "";
                $search_array_options = [];
            }
            if (
                (GETPOSTINT('valid_change_chart') && GETPOSTINT('chartofaccounts') > 0) // explicit click on button 'Change and load' with js on
                || (GETPOSTINT('chartofaccounts') > 0 && GETPOSTINT('chartofaccounts') != getDolGlobalInt('CHARTOFACCOUNTS'))
            ) {    // a submit of form is done and chartofaccounts combo has been modified
                $error = 0;

                if ($chartofaccounts > 0 && $permissiontoadd) {
                    $country_code = '';
                    // Get language code for this $chartofaccounts
                    $sql = 'SELECT code FROM ' . MAIN_DB_PREFIX . 'c_country as c, ' . MAIN_DB_PREFIX . 'accounting_system as a';
                    $sql .= ' WHERE c.rowid = a.fk_country AND a.rowid = ' . (int) $chartofaccounts;
                    $resql = $db->query($sql);
                    if ($resql) {
                        $obj = $db->fetch_object($resql);
                        if ($obj) {
                            $country_code = $obj->code;
                        }
                    } else {
                        dol_print_error($db);
                    }

                    // Try to load sql file
                    if ($country_code) {
                        $sqlfile = DOL_DOCUMENT_ROOT . '/install/mysql/data/llx_accounting_account_' . strtolower($country_code) . '.sql';

                        $offsetforchartofaccount = 0;
                        // Get the comment line '-- ADD CCCNNNNN to rowid...' to find CCCNNNNN (CCC is country num, NNNNN is id of accounting account)
                        // and pass CCCNNNNN + (num of company * 100 000 000) as offset to the run_sql as a new parameter to say to update sql on the fly to add offset to rowid and account_parent value.
                        // This is to be sure there is no conflict for each chart of account, whatever is country, whatever is company when multicompany is used.
                        $tmp = file_get_contents($sqlfile);
                        $reg = [];
                        if (preg_match('/-- ADD (\d+) to rowid/ims', $tmp, $reg)) {
                            $offsetforchartofaccount += $reg[1];
                        }
                        $offsetforchartofaccount += ($conf->entity * 100000000);

                        $result = run_sql($sqlfile, 1, $conf->entity, 1, '', 'default', 32768, 0, $offsetforchartofaccount);

                        if ($result > 0) {
                            setEventMessages($langs->trans("ChartLoaded"), null, 'mesgs');
                        } else {
                            setEventMessages($langs->trans("ErrorDuringChartLoad"), null, 'warnings');
                        }
                    }

                    if (!dolibarr_set_const($db, 'CHARTOFACCOUNTS', $chartofaccounts, 'chaine', 0, '', $conf->entity)) {
                        $error++;
                    }
                } else {
                    $error++;
                }
            }

            if ($action == 'disable' && $permissiontoadd) {
                if ($accounting->fetch($id)) {
                    $mode = GETPOSTINT('mode');
                    $result = $accounting->accountDeactivate($id, $mode);
                    if ($result < 0) {
                        setEventMessages($accounting->error, $accounting->errors, 'errors');
                    }
                }

                $action = 'update';
            } elseif ($action == 'enable' && $permissiontoadd) {
                if ($accounting->fetch($id)) {
                    $mode = GETPOSTINT('mode');
                    $result = $accounting->accountActivate($id, $mode);
                    if ($result < 0) {
                        setEventMessages($accounting->error, $accounting->errors, 'errors');
                    }
                }
                $action = 'update';
            }
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_account.php');

        $db->close();
    }

    /**
     *      \file       htdocs/accountancy/admin/accountmodel.php
     *      \ingroup    Accountancy (Double entries)
     *      \brief      Page to administer model of chart of accounts
     */
    public function accountmodel()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        if (isModEnabled('accounting')) {
        }

// Load translation files required by the page
        $langs->loadLangs(['accountancy', 'admin', 'companies', 'compta', 'errors', 'holiday', 'hrm', 'resource']);

        $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
        $confirm = GETPOST('confirm', 'alpha');
        $id = 31;
        $rowid = GETPOST('rowid', 'alpha');
        $code = GETPOST('code', 'alpha');

        $acts = [];
        $actl = [];
        $acts[0] = "activate";
        $acts[1] = "disable";
        $actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
        $actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

        $listoffset = GETPOST('listoffset', 'alpha');
        $listlimit = GETPOSTINT('listlimit') > 0 ? GETPOSTINT('listlimit') : 1000;
        $active = 1;

        $sortfield = GETPOST("sortfield", 'aZ09comma');
        $sortorder = GETPOST("sortorder", 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $listlimit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;

        $search_country_id = GETPOSTINT('search_country_id');


// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(['admin']);

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Name of SQL tables of dictionaries
        $tabname = [];

        $tabname[31] = MAIN_DB_PREFIX . "accounting_system";

// Dictionary labels
        $tablib = [];
        $tablib[31] = "Pcg_version";

// Requests to extract data
        $tabsql = [];
        $tabsql[31] = "SELECT s.rowid as rowid, pcg_version, s.label, s.fk_country as country_id, c.code as country_code, c.label as country, s.active FROM " . MAIN_DB_PREFIX . "accounting_system as s, " . MAIN_DB_PREFIX . "c_country as c WHERE s.fk_country=c.rowid and c.active=1";

// Criteria to sort dictionaries
        $tabsqlsort = [];
        $tabsqlsort[31] = "pcg_version ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
        $tabfield = [];
        $tabfield[31] = "pcg_version,label,country_id,country";

// Nom des champs d'edition pour modification d'un enregistrement
        $tabfieldvalue = [];
        $tabfieldvalue[31] = "pcg_version,label,country";

// Nom des champs dans la table pour insertion d'un enregistrement
        $tabfieldinsert = [];
        $tabfieldinsert[31] = "pcg_version,label,fk_country";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
        $tabrowid = [];
        $tabrowid[31] = "";

// List of help for fields
        $tabhelp = [];
        $tabhelp[31] = ['pcg_version' => $langs->trans("EnterAnyCode")];


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
        $elementList = [];
        $sourceList = [];


        /*
         * Actions
         */

        if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
            $search_country_id = '';
        }

// Actions add or modify an entry into a dictionary
        if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha')) {
            $listfield = explode(',', str_replace(' ', '', $tabfield[$id]));
            $listfieldinsert = explode(',', $tabfieldinsert[$id]);
            $listfieldmodify = explode(',', $tabfieldinsert[$id]);
            $listfieldvalue = explode(',', $tabfieldvalue[$id]);

            // Check that all fields are filled
            $ok = 1;
            foreach ($listfield as $f => $value) {
                if ($value == 'country_id' && in_array($tablib[$id], ['Pcg_version'])) {
                    continue; // For some pages, country is not mandatory
                }
                if ((!GETPOSTISSET($value)) || GETPOST($value) == '') {
                    $ok = 0;
                    $fieldnamekey = $listfield[$f];
                    // We take translate key of field

                    if ($fieldnamekey == 'pcg_version') {
                        $fieldnamekey = 'Pcg_version';
                    }
                    if ($fieldnamekey == 'label') {
                        $fieldnamekey = 'Label';
                    }
                    if ($fieldnamekey == 'country') {
                        $fieldnamekey = "Country";
                    }

                    setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
                }
            }
            // Other checks
            if (GETPOSTISSET("pcg_version")) {
                if (GETPOST("pcg_version") == '0') {
                    $ok = 0;
                    setEventMessages($langs->transnoentities('ErrorCodeCantContainZero'), null, 'errors');
                }
            }
            if (GETPOSTISSET("country") && (GETPOST("country") == '0') && ($id != 2)) {
                $ok = 0;
                setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities("Country")), null, 'errors');
            }

            // Si verif ok et action add, on ajoute la ligne
            if ($ok && GETPOST('actionadd', 'alpha')) {
                $newid = 0;
                if ($tabrowid[$id]) {
                    // Get free id for insert
                    $sql = "SELECT MAX(" . $db->sanitize($tabrowid[$id]) . ") as newid FROM " . $db->sanitize($tabname[$id]);
                    $result = $db->query($sql);
                    if ($result) {
                        $obj = $db->fetch_object($result);
                        $newid = ($obj->newid + 1);
                    } else {
                        dol_print_error($db);
                    }
                }

                // Add new entry
                $sql = "INSERT INTO " . $db->sanitize($tabname[$id]) . " (";
                // List of fields
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
                    $sql .= $db->sanitize($tabrowid[$id]) . ",";
                }
                $sql .= $db->sanitize($tabfieldinsert[$id]);
                $sql .= ",active)";
                $sql .= " VALUES(";

                // List of values
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
                    $sql .= $newid . ",";
                }
                $i = 0;
                foreach ($listfieldinsert as $f => $value) {
                    if ($value == 'price' || preg_match('/^amount/i', $value) || $value == 'taux') {
                        $_POST[$listfieldvalue[$i]] = price2num(GETPOST($listfieldvalue[$i]), 'MU');
                    } elseif ($value == 'entity') {
                        $_POST[$listfieldvalue[$i]] = $conf->entity;
                    }
                    if ($i) {
                        $sql .= ",";
                    }
                    if (GETPOST($listfieldvalue[$i]) == '') {
                        $sql .= "null";
                    } else {
                        $sql .= "'" . $db->escape(GETPOST($listfieldvalue[$i])) . "'";
                    }
                    $i++;
                }
                $sql .= ",1)";

                dol_syslog("actionadd", LOG_DEBUG);
                $result = $db->query($sql);
                if ($result) {  // Add is ok
                    setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
                    $_POST = ['id' => $id]; // Clean $_POST array, we keep only
                } else {
                    if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
                    } else {
                        dol_print_error($db);
                    }
                }
            }

            // Si verif ok et action modify, on modifie la ligne
            if ($ok && GETPOST('actionmodify', 'alpha')) {
                if ($tabrowid[$id]) {
                    $rowidcol = $tabrowid[$id];
                } else {
                    $rowidcol = "rowid";
                }

                // Modify entry
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET ";
                // Modifie valeur des champs
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldmodify)) {
                    $sql .= $db->sanitize($tabrowid[$id]) . " = ";
                    $sql .= "'" . $db->escape($rowid) . "', ";
                }
                $i = 0;
                foreach ($listfieldmodify as $field) {
                    if ($field == 'price' || preg_match('/^amount/i', $field) || $field == 'taux') {
                        $_POST[$listfieldvalue[$i]] = price2num(GETPOST($listfieldvalue[$i]), 'MU');
                    } elseif ($field == 'entity') {
                        $_POST[$listfieldvalue[$i]] = $conf->entity;
                    }
                    if ($i) {
                        $sql .= ",";
                    }
                    $sql .= $field . "=";
                    if (GETPOST($listfieldvalue[$i]) == '') {
                        $sql .= "null";
                    } else {
                        $sql .= "'" . $db->escape(GETPOST($listfieldvalue[$i])) . "'";
                    }
                    $i++;
                }
                $sql .= " WHERE " . $rowidcol . " = " . ((int) $rowid);

                dol_syslog("actionmodify", LOG_DEBUG);
                //print $sql;
                $resql = $db->query($sql);
                if (!$resql) {
                    setEventMessages($db->error(), null, 'errors');
                }
            }
        }

        if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
            if ($tabrowid[$id]) {
                $rowidcol = $tabrowid[$id];
            } else {
                $rowidcol = "rowid";
            }

            $sql = "DELETE from " . $db->sanitize($tabname[$id]) . " WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);

            dol_syslog("delete", LOG_DEBUG);
            $result = $db->query($sql);
            if (!$result) {
                if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
                    setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
                } else {
                    dol_print_error($db);
                }
            }
        }

// activate
        if ($action == 'activate') {
            $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 1 WHERE rowid = " . ((int) $rowid);
            $result = $db->query($sql);
            if (!$result) {
                dol_print_error($db);
            }
        }

// disable
        if ($action == $acts[1]) {
            $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 0 WHERE rowid = " . ((int) $rowid);
            $result = $db->query($sql);
            if (!$result) {
                dol_print_error($db);
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_accountmodel.php');

        $db->close();
    }

    /**
     *  \file       htdocs/accountancy/admin/card.php
     *  \ingroup    Accountancy (Double entries)
     *  \brief      Card of accounting account
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

        $error = 0;

// Load translation files required by the page
        $langs->loadLangs(['accountancy', 'bills', 'compta']);

        $action = GETPOST('action', 'aZ09');
        $backtopage = GETPOST('backtopage', 'alpha');
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');
        $rowid = GETPOSTINT('rowid');
        $cancel = GETPOST('cancel', 'alpha');

        $account_number = GETPOST('account_number', 'alphanohtml');
        $label = GETPOST('label', 'alpha');

// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }


        $object = new AccountingAccount($db);


        /*
         * Action
         */

        if (GETPOST('cancel', 'alpha')) {
            $urltogo = $backtopage ? $backtopage : DOL_URL_ROOT . '/accountancy/admin/account.php';
            header("Location: " . $urltogo);
            exit;
        }

        if ($action == 'add' && $user->hasRight('accounting', 'chartofaccount')) {
            if (!$cancel) {
                if (!$account_number) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountNumber")), null, 'errors');
                    $action = 'create';
                } elseif (!$label) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
                    $action = 'create';
                } else {
                    $sql = "SELECT pcg_version FROM " . MAIN_DB_PREFIX . "accounting_system WHERE rowid = " . ((int) getDolGlobalInt('CHARTOFACCOUNTS'));

                    dol_syslog('accountancy/admin/card.php:: $sql=' . $sql);
                    $result = $db->query($sql);
                    $obj = $db->fetch_object($result);

                    // Clean code

                    // To manage zero or not at the end of the accounting account
                    if (!getDolGlobalString('ACCOUNTING_MANAGE_ZERO')) {
                        $account_number = clean_account($account_number);
                    }

                    $account_parent = (GETPOSTINT('account_parent') > 0) ? GETPOSTINT('account_parent') : 0;

                    $object->fk_pcg_version = $obj->pcg_version;
                    $object->pcg_type = GETPOST('pcg_type', 'alpha');
                    $object->account_number = $account_number;
                    $object->account_parent = $account_parent;
                    $object->account_category = GETPOSTINT('account_category');
                    $object->label = $label;
                    $object->labelshort = GETPOST('labelshort', 'alpha');
                    $object->active = 1;

                    $res = $object->create($user);
                    if ($res == -3) {
                        $error = 1;
                        $action = "create";
                        setEventMessages($object->error, $object->errors, 'errors');
                    } elseif ($res == -4) {
                        $error = 2;
                        $action = "create";
                        setEventMessages($object->error, $object->errors, 'errors');
                    } elseif ($res < 0) {
                        $error++;
                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = "create";
                    }
                    if (!$error) {
                        setEventMessages("RecordCreatedSuccessfully", null, 'mesgs');
                        $urltogo = $backtopage ? $backtopage : DOL_URL_ROOT . '/accountancy/admin/account.php';
                        header("Location: " . $urltogo);
                        exit;
                    }
                }
            }
        } elseif ($action == 'edit' && $user->hasRight('accounting', 'chartofaccount')) {
            if (!$cancel) {
                if (!$account_number) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("AccountNumber")), null, 'errors');
                    $action = 'update';
                } elseif (!$label) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")), null, 'errors');
                    $action = 'update';
                } else {
                    $result = $object->fetch($id);

                    $sql = "SELECT pcg_version FROM " . MAIN_DB_PREFIX . "accounting_system WHERE rowid=" . ((int) getDolGlobalInt('CHARTOFACCOUNTS'));

                    dol_syslog('accountancy/admin/card.php:: $sql=' . $sql);
                    $result2 = $db->query($sql);
                    $obj = $db->fetch_object($result2);

                    // Clean code

                    // To manage zero or not at the end of the accounting account
                    if (!getDolGlobalString('ACCOUNTING_MANAGE_ZERO')) {
                        $account_number = clean_account($account_number);
                    }

                    $account_parent = (GETPOSTINT('account_parent') > 0) ? GETPOSTINT('account_parent') : 0;

                    $object->fk_pcg_version = $obj->pcg_version;
                    $object->pcg_type = GETPOST('pcg_type', 'alpha');
                    $object->account_number = $account_number;
                    $object->account_parent = $account_parent;
                    $object->account_category = GETPOSTINT('account_category');
                    $object->label = $label;
                    $object->labelshort = GETPOST('labelshort', 'alpha');

                    $result = $object->update($user);

                    if ($result > 0) {
                        $urltogo = $backtopage ? $backtopage : ($_SERVER['PHP_SELF'] . "?id=" . $id);
                        header("Location: " . $urltogo);
                        exit();
                    } elseif ($result == -2) {
                        setEventMessages($langs->trans("ErrorAccountNumberAlreadyExists", $object->account_number), null, 'errors');
                    } else {
                        setEventMessages($object->error, null, 'errors');
                    }
                }
            } else {
                $urltogo = $backtopage ? $backtopage : ($_SERVER['PHP_SELF'] . "?id=" . $id);
                header("Location: " . $urltogo);
                exit();
            }
        } elseif ($action == 'delete' && $user->hasRight('accounting', 'chartofaccount')) {
            $result = $object->fetch($id);

            if (!empty($object->id)) {
                $result = $object->delete($user);

                if ($result > 0) {
                    header("Location: account.php");
                    exit;
                }
            }

            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_card.php');

        $db->close();
    }

    /**
     * \file    htdocs/accountancy/admin/categories.php
     * \ingroup Accountancy (Double entries)
     * \brief   Page to assign mass categories to accounts
     */
    public function categories()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        $error = 0;

// Load translation files required by the page
        $langs->loadLangs(["bills", "accountancy", "compta"]);

        $id = GETPOSTINT('id');
        $cancel = GETPOST('cancel', 'alpha');
        $action = GETPOST('action', 'aZ09');
        $cat_id = GETPOSTINT('account_category');
        $selectcpt = GETPOST('cpt_bk', 'array');
        $cpt_id = GETPOSTINT('cptid');

        if ($cat_id == 0) {
            $cat_id = null;
        }

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

        if (empty($sortfield)) {
            $sortfield = 'account_number';
        }
        if (empty($sortorder)) {
            $sortorder = 'ASC';
        }

// Security check
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

        $accountingcategory = new AccountancyCategory($db);


        /*
         * Actions
         */

// If we add account
        if (!empty($selectcpt)) {
            $cpts = [];
            foreach ($selectcpt as $selectedoption) {
                if (!array_key_exists($selectedoption, $cpts)) {
                    $cpts[$selectedoption] = "'" . $selectedoption . "'";
                }
            }

            $return = $accountingcategory->updateAccAcc($cat_id, $cpts);

            if ($return < 0) {
                setEventMessages($langs->trans('errors'), $accountingcategory->errors, 'errors');
            } else {
                setEventMessages($langs->trans('RecordModifiedSuccessfully'), null, 'mesgs');
            }
        }

        if ($action == 'delete') {
            if ($cpt_id) {
                if ($accountingcategory->deleteCptCat($cpt_id)) {
                    setEventMessages($langs->trans('AccountRemovedFromGroup'), null, 'mesgs');
                } else {
                    setEventMessages($langs->trans('errors'), null, 'errors');
                }
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_categories.php');

        $db->close();
    }

    /**
     *      \file       htdocs/accountancy/admin/categories_list.php
     *      \ingroup    setup
     *      \brief      Page to administer data tables
     */
    public function categories_list()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(["errors", "admin", "companies", "resource", "holiday", "accountancy", "hrm"]);

        $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
        $confirm = GETPOST('confirm', 'alpha');
        $id = 32;
        $rowid = GETPOST('rowid', 'alpha');
        $code = GETPOST('code', 'alpha');

// Security access
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

        $acts = [];
        $acts[0] = "activate";
        $acts[1] = "disable";
        $actl = [];
        $actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
        $actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

        $listoffset = GETPOST('listoffset', 'alpha');
        $listlimit = GETPOSTINT('listlimit') > 0 ? GETPOSTINT('listlimit') : 1000;

        $sortfield = GETPOST("sortfield", 'aZ09comma');
        $sortorder = GETPOST("sortorder", 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
            // If $page is not defined, or '' or -1 or if we click on clear filters
            $page = 0;
        }
        $offset = $listlimit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;

        $search_country_id = GETPOSTINT('search_country_id');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(['admin']);

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Sort order to show dictionary (0 is space). All other dictionaries (added by modules) will be at end of this.
        $taborder = [32];

// Name of SQL tables of dictionaries
        $tabname = [];
        $tabname[32] = MAIN_DB_PREFIX . "c_accounting_category";

// Dictionary labels
        $tablib = [];
        $tablib[32] = "DictionaryAccountancyCategory";

// Requests to extract data
        $tabsql = [];
        $tabsql[32] = "SELECT a.rowid as rowid, a.code as code, a.label, a.range_account, a.category_type, a.formula, a.position as position, a.fk_country as country_id, c.code as country_code, c.label as country, a.active FROM " . MAIN_DB_PREFIX . "c_accounting_category as a, " . MAIN_DB_PREFIX . "c_country as c WHERE a.fk_country=c.rowid and c.active=1";

// Criteria to sort dictionaries
        $tabsqlsort = [];
        $tabsqlsort[32] = "position ASC";

// Name of the fields in the result of select to display the dictionary
        $tabfield = [];
        $tabfield[32] = "code,label,range_account,category_type,formula,position,country";

// Name of editing fields for record modification
        $tabfieldvalue = [];
        $tabfieldvalue[32] = "code,label,range_account,category_type,formula,position,country_id";

// Name of the fields in the table for inserting a record
        $tabfieldinsert = [];
        $tabfieldinsert[32] = "code,label,range_account,category_type,formula,position,fk_country";

// Name of the rowid if the field is not of type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
        $tabrowid = [];
        $tabrowid[32] = "";

// Condition to show dictionary in setup page
        $tabcond = [];
        $tabcond[32] = isModEnabled('accounting');

// List of help for fields
        $tabhelp = [];
        $tabhelp[32] = ['code' => $langs->trans("EnterAnyCode"), 'category_type' => $langs->trans("SetToYesIfGroupIsComputationOfOtherGroups"), 'formula' => $langs->trans("EnterCalculationRuleIfPreviousFieldIsYes")];

// List of check for fields (NOT USED YET)
        $tabfieldcheck = [];
        $tabfieldcheck[32] = [];

// Complete all arrays with entries found into modules
        complete_dictionary_with_modules($taborder, $tabname, $tablib, $tabsql, $tabsqlsort, $tabfield, $tabfieldvalue, $tabfieldinsert, $tabrowid, $tabcond, $tabhelp, $tabfieldcheck);

        $accountingcategory = new AccountancyCategory($db);


        /*
         * Actions
         */

        if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
            $search_country_id = '';
        }

// Actions add or modify an entry into a dictionary
        if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha')) {
            $listfield = explode(',', str_replace(' ', '', $tabfield[$id]));
            $listfieldinsert = explode(',', $tabfieldinsert[$id]);
            $listfieldmodify = explode(',', $tabfieldinsert[$id]);
            $listfieldvalue = explode(',', $tabfieldvalue[$id]);

            // Check that all fields are filled
            $ok = 1;
            foreach ($listfield as $f => $value) {
                if ($value == 'formula' && !GETPOST('formula')) {
                    continue;
                }
                if ($value == 'range_account' && !GETPOST('range_account')) {
                    continue;
                }
                if (($value == 'country' || $value == 'country_id') && GETPOST('country_id')) {
                    continue;
                }
                if (!GETPOSTISSET($value) || GETPOST($value) == '') {
                    $ok = 0;
                    $fieldnamekey = $listfield[$f];
                    // We take translate key of field
                    if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label')) {
                        $fieldnamekey = 'Label';
                    }
                    if ($fieldnamekey == 'code') {
                        $fieldnamekey = 'Code';
                    }
                    if ($fieldnamekey == 'note') {
                        $fieldnamekey = 'Note';
                    }
                    if ($fieldnamekey == 'type') {
                        $fieldnamekey = 'Type';
                    }
                    if ($fieldnamekey == 'position') {
                        $fieldnamekey = 'Position';
                    }
                    if ($fieldnamekey == 'category_type') {
                        $fieldnamekey = 'Calculated';
                    }
                    if ($fieldnamekey == 'country') {
                        $fieldnamekey = 'Country';
                    }

                    setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
                }
            }
            if (GETPOSTISSET("code")) {
                if (GETPOST("code") == '0') {
                    $ok = 0;
                    setEventMessages($langs->transnoentities('ErrorCodeCantContainZero'), null, 'errors');
                }
            }
            if (GETPOST('position') && !is_numeric(GETPOST('position', 'alpha'))) {
                $langs->loadLangs(["errors"]);
                $ok = 0;
                setEventMessages($langs->transnoentities('ErrorFieldMustBeANumeric', $langs->transnoentities("Position")), null, 'errors');
            }

            // Si verif ok et action add, on ajoute la ligne
            if ($ok && GETPOST('actionadd', 'alpha')) {
                $newid = 0;

                if ($tabrowid[$id]) {
                    // Get free id for insert
                    $sql = "SELECT MAX(" . $db->sanitize($tabrowid[$id]) . ") newid FROM " . $db->sanitize($tabname[$id]);
                    $result = $db->query($sql);
                    if ($result) {
                        $obj = $db->fetch_object($result);
                        $newid = ($obj->newid + 1);
                    } else {
                        dol_print_error($db);
                    }
                }

                // Add new entry
                $sql = "INSERT INTO " . $db->sanitize($tabname[$id]) . " (";
                // List of fields
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
                    $sql .= $db->sanitize($tabrowid[$id]) . ",";
                }
                $sql .= $db->sanitize($tabfieldinsert[$id]);
                $sql .= ",active)";
                $sql .= " VALUES(";

                // List of values
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
                    $sql .= $newid . ",";
                }
                $i = 0;
                foreach ($listfieldinsert as $f => $value) {
                    if ($value == 'entity') {
                        $_POST[$listfieldvalue[$i]] = $conf->entity;
                    }
                    if ($i) {
                        $sql .= ",";
                    }
                    if (GETPOST($listfieldvalue[$i]) == '' && !$listfieldvalue[$i] == 'formula') {
                        $sql .= "null"; // For vat, we want/accept code = ''
                    } else {
                        $sql .= "'" . $db->escape(GETPOST($listfieldvalue[$i])) . "'";
                    }
                    $i++;
                }
                $sql .= ",1)";

                dol_syslog("actionadd", LOG_DEBUG);
                $result = $db->query($sql);
                if ($result) {  // Add is ok
                    setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
                    $_POST = ['id' => $id]; // Clean $_POST array, we keep only
                } else {
                    if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
                    } else {
                        dol_print_error($db);
                    }
                }
            }

            // If check ok and action modify, we modify the line
            if ($ok && GETPOST('actionmodify', 'alpha')) {
                if ($tabrowid[$id]) {
                    $rowidcol = $tabrowid[$id];
                } else {
                    $rowidcol = "rowid";
                }

                // Modify entry
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET ";
                // Modifie valeur des champs
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldmodify)) {
                    $sql .= $db->sanitize($tabrowid[$id]) . " = ";
                    $sql .= "'" . $db->escape($rowid) . "', ";
                }
                $i = 0;
                foreach ($listfieldmodify as $field) {
                    if ($field == 'fk_country' && GETPOST('country') > 0) {
                        $_POST[$listfieldvalue[$i]] = GETPOST('country');
                    } elseif ($field == 'entity') {
                        $_POST[$listfieldvalue[$i]] = $conf->entity;
                    }
                    if ($i) {
                        $sql .= ",";
                    }
                    $sql .= $field . "=";
                    if (GETPOST($listfieldvalue[$i]) == '' && !$listfieldvalue[$i] == 'range_account') {
                        $sql .= "null"; // For range_account, we want/accept code = ''
                    } else {
                        $sql .= "'" . $db->escape(GETPOST($listfieldvalue[$i])) . "'";
                    }
                    $i++;
                }
                $sql .= " WHERE " . $rowidcol . " = " . ((int) $rowid);

                dol_syslog("actionmodify", LOG_DEBUG);
                //print $sql;
                $resql = $db->query($sql);
                if (!$resql) {
                    setEventMessages($db->error(), null, 'errors');
                }
            }
            //$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
        }

// if (GETPOST('actioncancel', 'alpha')) {
//  $_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
// }

        if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
            $rowidcol = "rowid";

            $sql = "DELETE from " . $db->sanitize($tabname[$id]) . " WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);

            dol_syslog("delete", LOG_DEBUG);
            $result = $db->query($sql);
            if (!$result) {
                if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
                    setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
                } else {
                    dol_print_error($db);
                }
            }
        }

// activate
        if ($action == $acts[0]) {
            $rowidcol = "rowid";

            if ($rowid) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 1 WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
            } elseif ($code) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 1 WHERE code = '" . $db->escape($code) . "'";
            }

            if ($sql) {
                $result = $db->query($sql);
                if (!$result) {
                    dol_print_error($db);
                }
            }
        }

// disable
        if ($action == $acts[1]) {
            $rowidcol = "rowid";

            if ($rowid) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 0 WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
            } elseif ($code) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 0 WHERE code = '" . $db->escape($code) . "'";
            }

            if ($sql) {
                $result = $db->query($sql);
                if (!$result) {
                    dol_print_error($db);
                }
            }
        }

// favorite
        if ($action == 'activate_favorite') {
            $rowidcol = "rowid";

            if ($rowid) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET favorite = 1 WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
            } elseif ($code) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET favorite = 1 WHERE code = '" . $db->escape($code) . "'";
            }

            if ($sql) {
                $result = $db->query($sql);
                if (!$result) {
                    dol_print_error($db);
                }
            }
        }

// disable favorite
        if ($action == 'disable_favorite') {
            $rowidcol = "rowid";

            if ($rowid) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET favorite = 0 WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
            } elseif ($code) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET favorite = 0 WHERE code = '" . $db->escape($code) . "'";
            }

            if ($sql) {
                $result = $db->query($sql);
                if (!$result) {
                    dol_print_error($db);
                }
            }
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_categories_list.php');

        $db->close();
    }


    /**
     *  Show fields in insert/edit mode
     *
     * @param array  $fieldlist Array of fields
     * @param Object $obj       If we show a particular record, obj is filled with record fields
     * @param string $tabname   Name of SQL table
     * @param string $context   'add'=Output field for the "add form", 'edit'=Output field for the "edit form",
     *                          'hide'=Output field for the "add form" but we don't want it to be rendered
     *
     * @return     void
     */
    public function fieldListAccountingCategories($fieldlist, $obj = null, $tabname = '', $context = '')
    {
        global $conf, $langs, $db;
        global $form, $mysoc;

        $formadmin = new FormAdmin($db);
        $formcompany = new FormCompany($db);
        if (isModEnabled('accounting')) {
            $formaccounting = new FormAccounting($db);
        }

        foreach ($fieldlist as $field => $value) {
            if ($fieldlist[$field] == 'country') {
                print '<td>';
                $fieldname = 'country';
                if ($context == 'add') {
                    $fieldname = 'country_id';
                    $preselectcountrycode = GETPOSTISSET('country_id') ? GETPOSTINT('country_id') : $mysoc->country_code;
                    print $form->select_country($preselectcountrycode, $fieldname, '', 28, 'maxwidth150 maxwidthonsmartphone');
                } else {
                    $preselectcountrycode = (empty($obj->country_code) ? (empty($obj->country) ? $mysoc->country_code : $obj->country) : $obj->country_code);
                    print $form->select_country($preselectcountrycode, $fieldname, '', 28, 'maxwidth150 maxwidthonsmartphone');
                }
                print '</td>';
            } elseif ($fieldlist[$field] == 'country_id') {
                if (!in_array('country', $fieldlist)) { // If there is already a field country, we don't show country_id (avoid duplicate)
                    $country_id = (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : 0);
                    print '<td>';
                    print '<input type="hidden" name="' . $fieldlist[$field] . '" value="' . $country_id . '">';
                    print '</td>';
                }
            } elseif ($fieldlist[$field] == 'category_type') {
                print '<td>';
                print $form->selectyesno($fieldlist[$field], (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : ''), 1);
                print '</td>';
            } elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
                print '<td><input type="text" class="flat minwidth100" value="' . (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '') . '" name="' . $fieldlist[$field] . '"></td>';
            } else {
                print '<td>';
                $class = '';
                if (in_array($fieldlist[$field], ['code', 'formula'])) {
                    $class = 'maxwidth75';
                }
                if (in_array($fieldlist[$field], ['label', 'range_account'])) {
                    $class = 'maxwidth150';
                }
                if ($fieldlist[$field] == 'position') {
                    $class = 'maxwidth50';
                }
                print '<input type="text" class="flat' . ($class ? ' ' . $class : '') . '" value="' . (isset($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '') . '" name="' . $fieldlist[$field] . '">';
                print '</td>';
            }
        }
    }

    /**
     * \file        htdocs/accountancy/admin/closure.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Setup page to configure accounting expert module
     */
    public function closure()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(["compta", "admin", "accountancy"]);

// Security check
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

        $action = GETPOST('action', 'aZ09');


        $list_account_main = [
            'ACCOUNTING_RESULT_PROFIT',
            'ACCOUNTING_RESULT_LOSS',
        ];

        /*
         * Actions
         */

        if ($action == 'update') {
            $error = 0;

            $defaultjournal = GETPOST('ACCOUNTING_CLOSURE_DEFAULT_JOURNAL', 'alpha');

            if (!empty($defaultjournal)) {
                if (!dolibarr_set_const($db, 'ACCOUNTING_CLOSURE_DEFAULT_JOURNAL', $defaultjournal, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
            } else {
                $error++;
            }

            $accountinggroupsusedforbalancesheetaccount = GETPOST('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT', 'alphanohtml');
            if (!empty($accountinggroupsusedforbalancesheetaccount)) {
                if (!dolibarr_set_const($db, 'ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT', $accountinggroupsusedforbalancesheetaccount, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
            } else {
                $error++;
            }

            $accountinggroupsusedforincomestatement = GETPOST('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT', 'alpha');
            if (!empty($accountinggroupsusedforincomestatement)) {
                if (!dolibarr_set_const($db, 'ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT', $accountinggroupsusedforincomestatement, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
            } else {
                $error++;
            }

            foreach ($list_account_main as $constname) {
                $constvalue = GETPOST($constname, 'alpha');
                if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_closure.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/defaultaccounts.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Setup page to configure accounting expert module
     */
    public function defaultaccounts()
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
        $langs->loadLangs(["compta", "bills", "admin", "accountancy", "salaries", "loan"]);

// Security check
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

        $action = GETPOST('action', 'aZ09');


        $list_account_main = [
            'ACCOUNTING_ACCOUNT_CUSTOMER',
            'ACCOUNTING_ACCOUNT_SUPPLIER',
            'SALARIES_ACCOUNTING_ACCOUNT_PAYMENT',
        ];

        $list_account = [];

        $list_account[] = '---Product---';
        $list_account[] = 'ACCOUNTING_PRODUCT_SOLD_ACCOUNT';
        if ($mysoc->isInEEC()) {
            $list_account[] = 'ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT';
        }
        $list_account[] = 'ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT';
        $list_account[] = 'ACCOUNTING_PRODUCT_BUY_ACCOUNT';
        if ($mysoc->isInEEC()) {
            $list_account[] = 'ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT';
        }
        $list_account[] = 'ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT';

        $list_account[] = '---Service---';
        $list_account[] = 'ACCOUNTING_SERVICE_SOLD_ACCOUNT';
        if ($mysoc->isInEEC()) {
            $list_account[] = 'ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT';
        }
        $list_account[] = 'ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT';
        $list_account[] = 'ACCOUNTING_SERVICE_BUY_ACCOUNT';
        if ($mysoc->isInEEC()) {
            $list_account[] = 'ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT';
        }
        $list_account[] = 'ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT';

        $list_account[] = '---Others---';
        $list_account[] = 'ACCOUNTING_VAT_SOLD_ACCOUNT';
        $list_account[] = 'ACCOUNTING_VAT_BUY_ACCOUNT';

        /*if ($mysoc->useRevenueStamp()) {
            $list_account[] = 'ACCOUNTING_REVENUESTAMP_SOLD_ACCOUNT';
            $list_account[] = 'ACCOUNTING_REVENUESTAMP_BUY_ACCOUNT';
        }*/

        $list_account[] = 'ACCOUNTING_VAT_PAY_ACCOUNT';

        if (getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
            $list_account[] = 'ACCOUNTING_VAT_BUY_REVERSE_CHARGES_CREDIT';
            $list_account[] = 'ACCOUNTING_VAT_BUY_REVERSE_CHARGES_DEBIT';
        }
        if (isModEnabled('bank')) {
            $list_account[] = 'ACCOUNTING_ACCOUNT_TRANSFER_CASH';
        }
        if (getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
            $list_account[] = 'ACCOUNTING_ACCOUNT_CUSTOMER_RETAINED_WARRANTY';
        }
        if (isModEnabled('don')) {
            $list_account[] = 'DONATION_ACCOUNTINGACCOUNT';
        }
        if (isModEnabled('member')) {
            $list_account[] = 'ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT';
        }
        if (isModEnabled('loan')) {
            $list_account[] = 'LOAN_ACCOUNTING_ACCOUNT_CAPITAL';
            $list_account[] = 'LOAN_ACCOUNTING_ACCOUNT_INTEREST';
            $list_account[] = 'LOAN_ACCOUNTING_ACCOUNT_INSURANCE';
        }
        $list_account[] = 'ACCOUNTING_ACCOUNT_SUSPENSE';
        if (isModEnabled('societe')) {
            $list_account[] = '---Deposits---';
        }

        /*
         * Actions
         */

        if ($action == 'update') {
            $error = 0;
            // Process $list_account_main
            foreach ($list_account_main as $constname) {
                $constvalue = GETPOST($constname, 'alpha');

                if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
            }
            // Process $list_account
            foreach ($list_account as $constname) {
                $reg = [];
                if (preg_match('/---(.*)---/', $constname, $reg)) { // This is a separator
                    continue;
                }

                $constvalue = GETPOST($constname, 'alpha');

                if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
            }

            $constname = 'ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT';
            $constvalue = GETPOSTINT($constname);
            if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                $error++;
            }

            $constname = 'ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT';
            $constvalue = GETPOSTINT($constname);
            if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                $error++;
            }


            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        }

        if ($action == 'setACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT') {
            $setDisableAuxiliaryAccountOnCustomerDeposit = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT", $setDisableAuxiliaryAccountOnCustomerDeposit, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT') {
            $setDisableAuxiliaryAccountOnSupplierDeposit = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT", $setDisableAuxiliaryAccountOnSupplierDeposit, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_defaultaccounts.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/export.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Setup page to configure accounting export module
     */
    public function export()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(["compta", "bills", "admin", "accountancy"]);

// Security access
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

        $action = GETPOST('action', 'aZ09');

// Parameters ACCOUNTING_EXPORT_*
        $main_option = [
            'ACCOUNTING_EXPORT_PREFIX_SPEC',
        ];

        $accountancyexport = new AccountancyExport($db);
        $configuration = $accountancyexport->getTypeConfig();

        $listparam = $configuration['param'];

        $listformat = $configuration['format'];

        $listcr = $configuration['cr'];


        $model_option = [
            '1' => [
                'label' => 'ACCOUNTING_EXPORT_FORMAT',
                'param' => $listformat,
            ],
            '2' => [
                'label' => 'ACCOUNTING_EXPORT_SEPARATORCSV',
                'param' => '',
            ],
            '3' => [
                'label' => 'ACCOUNTING_EXPORT_ENDLINE',
                'param' => $listcr,
            ],
            '4' => [
                'label' => 'ACCOUNTING_EXPORT_DATE',
                'param' => '',
            ],
        ];


        /*
         * Actions
         */

        if ($action == 'update') {
            $error = 0;

            $modelcsv = GETPOSTINT('ACCOUNTING_EXPORT_MODELCSV');

            if (!empty($modelcsv)) {
                if (!dolibarr_set_const($db, 'ACCOUNTING_EXPORT_MODELCSV', $modelcsv, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
                //if ($modelcsv==AccountancyExport::$EXPORT_TYPE_QUADRATUS || $modelcsv==AccountancyExport::$EXPORT_TYPE_CIEL) {
                //  dolibarr_set_const($db, 'ACCOUNTING_EXPORT_FORMAT', 'txt', 'chaine', 0, '', $conf->entity);
                //}
            } else {
                $error++;
            }

            foreach ($main_option as $constname) {
                $constvalue = GETPOST($constname, 'alpha');

                if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                    $error++;
                }
            }

            foreach ($listparam[$modelcsv] as $key => $value) {
                $constante = $key;

                if (strpos($constante, 'ACCOUNTING') !== false) {
                    $constvalue = GETPOST($key, 'alpha');
                    if (!dolibarr_set_const($db, $constante, $constvalue, 'chaine', 0, '', $conf->entity)) {
                        $error++;
                    }
                }
            }

            if (!$error) {
                // reload
                $configuration = $accountancyexport->getTypeConfig();
                $listparam = $configuration['param'];
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'errors');
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_export.php');

        $db->close();
    }

    /**
     *  \file       htdocs/accountancy/admin/fiscalyear.php
     *  \ingroup    Accountancy (Double entries)
     *  \brief      Setup page to configure fiscal year
     */
    public function fiscalyear()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        $action = GETPOST('action', 'aZ09');

// Load variable for pagination
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
        if (!$sortfield) {
            $sortfield = "f.rowid"; // Set here default search field
        }
        if (!$sortorder) {
            $sortorder = "ASC";
        }

// Load translation files required by the page
        $langs->loadLangs(["admin", "compta"]);

        $error = 0;
        $errors = [];

// List of status
        static $tmpstatut2label = [
            '0' => 'OpenFiscalYear',
            '1' => 'CloseFiscalYear',
        ];

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $object = new Fiscalyear($db);
        $hookmanager->initHooks(['fiscalyearlist']);

// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'fiscalyear', 'write')) {              // If we can read accounting records, we should be able to see fiscal year.
            accessforbidden();
        }

        /*
         * Actions
         */


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_fiscalyear.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/fiscalyear_card.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page to show a fiscal year
     */
    public function fiscalyear_card()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(["admin", "compta"]);

// Get parameters
        $id = GETPOSTINT('id');
        $ref = GETPOST('ref', 'alpha');

        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'alpha');
        $cancel = GETPOST('cancel', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)) . basename(__FILE__, '.php')); // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha');                   // if not set, a default page will be used
        $backtopageforcancel = GETPOST('backtopageforcancel', 'alpha'); // if not set, $backtopage will be used
        $backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
        $dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

        if (!empty($backtopagejsfields)) {
            $tmpbacktopagejsfields = explode(':', $backtopagejsfields);
            $dol_openinpopup = $tmpbacktopagejsfields[0];
        }

        $error = 0;

// Initialize technical objects
        $object = new Fiscalyear($db);
        $extrafields = new ExtraFields($db);

// Load object
        include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// List of status
        static $tmpstatus2label = [
            '0' => 'OpenFiscalYear',
            '1' => 'CloseFiscalYear',
        ];
        $status2label = [
            '',
        ];
        foreach ($tmpstatus2label as $key => $val) {
            $status2label[$key] = $langs->trans($val);
        }

        $date_start = dol_mktime(0, 0, 0, GETPOSTINT('fiscalyearmonth'), GETPOSTINT('fiscalyearday'), GETPOSTINT('fiscalyearyear'));
        $date_end = dol_mktime(0, 0, 0, GETPOSTINT('fiscalyearendmonth'), GETPOSTINT('fiscalyearendday'), GETPOSTINT('fiscalyearendyear'));

        $permissiontoadd = $user->hasRight('accounting', 'fiscalyear', 'write');

// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$permissiontoadd) {
            accessforbidden();
        }


        /*
         * Actions
         */

        $parameters = [];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if ($action == 'confirm_delete' && $confirm == "yes") {
            $result = $object->delete($user);
            if ($result >= 0) {
                header("Location: fiscalyear.php");
                exit();
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        } elseif ($action == 'add') {
            if (!GETPOST('cancel', 'alpha')) {
                $error = 0;

                $object->date_start = $date_start;
                $object->date_end = $date_end;
                $object->label = GETPOST('label', 'alpha');
                $object->status = GETPOSTINT('status');
                $object->datec = dol_now();

                if (empty($object->date_start) && empty($object->date_end)) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
                    $error++;
                }
                if (empty($object->label)) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
                    $error++;
                }

                if (!$error) {
                    $db->begin();

                    $id = $object->create($user);

                    if ($id > 0) {
                        $db->commit();

                        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
                        exit();
                    } else {
                        $db->rollback();

                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = 'create';
                    }
                } else {
                    $action = 'create';
                }
            } else {
                header("Location: ./fiscalyear.php");
                exit();
            }
        } elseif ($action == 'update') {
            // Update record
            if (!GETPOST('cancel', 'alpha')) {
                $result = $object->fetch($id);

                $object->date_start = GETPOST("fiscalyear") ? $date_start : '';
                $object->date_end = GETPOST("fiscalyearend") ? $date_end : '';
                $object->label = GETPOST('label', 'alpha');
                $object->status = GETPOSTINT('status');

                $result = $object->update($user);

                if ($result > 0) {
                    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
                    exit();
                } else {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            } else {
                header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $id);
                exit();
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_fiscalyear_card.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/fiscalyear_info.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page to show info of a fiscal year
     */
    public function fiscalyear_info()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(["admin", "compta"]);

// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'fiscalyear', 'write')) {
            accessforbidden();
        }

        $id = GETPOSTINT('id');

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_fiscalyear_info.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/index.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Setup page to configure accounting expert module
     */
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
        $langs->loadLangs(["compta", "bills", "admin", "accountancy", "other"]);

// Security access
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

        $action = GETPOST('action', 'aZ09');

        $nbletter = GETPOSTINT('ACCOUNTING_LETTERING_NBLETTERS');

// Parameters ACCOUNTING_* and others
        $list = [
            'ACCOUNTING_LENGTH_GACCOUNT',
            'ACCOUNTING_LENGTH_AACCOUNT',
//  'ACCOUNTING_LIMIT_LIST_VENTILATION'        // there is already a global parameter to define the nb of records in lists, we must use it in priority. Having one parameter for nb of record for each page is deprecated.
//  'ACCOUNTING_LENGTH_DESCRIPTION',         // adjust size displayed for lines description for dol_trunc
//  'ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT', // adjust size displayed for select account description for dol_trunc
        ];

        $list_binding = [
            'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER',
            'ACCOUNTING_DATE_START_BINDING',
        ];

        $error = 0;


        /*
         * Actions
         */

        if (in_array($action, ['setBANK_DISABLE_DIRECT_INPUT', 'setACCOUNTANCY_COMBO_FOR_AUX', 'setACCOUNTING_MANAGE_ZERO'])) {
            $constname = preg_replace('/^set/', '', $action);
            $constvalue = GETPOSTINT('value');
            $res = dolibarr_set_const($db, $constname, $constvalue, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'update') {
            $error = 0;

            if (!$error) {
                foreach ($list as $constname) {
                    $constvalue = GETPOST($constname, 'alpha');
                    if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                        $error++;
                    }
                }
                if ($error) {
                    setEventMessages($langs->trans("Error"), null, 'errors');
                }

                // option in section binding
                foreach ($list_binding as $constname) {
                    $constvalue = GETPOST($constname, 'alpha');

                    if ($constname == 'ACCOUNTING_DATE_START_BINDING') {
                        $constvalue = dol_mktime(0, 0, 0, GETPOSTINT($constname . 'month'), GETPOSTINT($constname . 'day'), GETPOSTINT($constname . 'year'));
                    }

                    if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
                        $error++;
                    }
                }

                // options in section other
                if (GETPOSTISSET('ACCOUNTING_LETTERING_NBLETTERS')) {
                    if (!dolibarr_set_const($db, 'ACCOUNTING_LETTERING_NBLETTERS', GETPOST('ACCOUNTING_LETTERING_NBLETTERS'), 'chaine', 0, '', $conf->entity)) {
                        $error++;
                    }
                }

                if ($error) {
                    setEventMessages($langs->trans("Error"), null, 'errors');
                }
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            }
        }

        if ($action == 'setmanagezero') {
            $setmanagezero = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_MANAGE_ZERO", $setmanagezero, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setdisabledirectinput') {
            $setdisabledirectinput = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "BANK_DISABLE_DIRECT_INPUT", $setdisabledirectinput, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setenabledraftexport') {
            $setenabledraftexport = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL", $setenabledraftexport, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setenablesubsidiarylist') {
            $setenablesubsidiarylist = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTANCY_COMBO_FOR_AUX", $setenablesubsidiarylist, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setdisablebindingonsales') {
            $setdisablebindingonsales = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_SALES", $setdisablebindingonsales, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setdisablebindingonpurchases') {
            $setdisablebindingonpurchases = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_PURCHASES", $setdisablebindingonpurchases, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setdisablebindingonexpensereports') {
            $setdisablebindingonexpensereports = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS", $setdisablebindingonexpensereports, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setenablelettering') {
            $setenablelettering = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_LETTERING", $setenablelettering, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setenableautolettering') {
            $setenableautolettering = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_AUTOLETTERING", $setenableautolettering, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }

        if ($action == 'setenablevatreversecharge') {
            $setenablevatreversecharge = GETPOSTINT('value');
            $res = dolibarr_set_const($db, "ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE", $setenablevatreversecharge, 'yesno', 0, '', $conf->entity);
            if (!($res > 0)) {
                $error++;
            }

            if (!$error) {
                setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("Error"), null, 'mesgs');
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_index.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/journals_list.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Setup page to configure journals
     */
    public function journals_list()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

        global $sourceList;

        defineIfNotDefined('CSRFCHECK_WITH_TOKEN', '1'); // Force use of CSRF protection with tokens even for GET

// Load translation files required by the page
        $langs->loadLangs(["admin", "compta", "accountancy"]);

        $action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
        $confirm = GETPOST('confirm', 'alpha');
        $id = 35;
        $rowid = GETPOST('rowid', 'alpha');
        $code = GETPOST('code', 'alpha');

// Security access
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

        $acts = [];
        $acts[0] = "activate";
        $acts[1] = "disable";
        $actl = [];
        $actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
        $actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

        $listoffset = GETPOST('listoffset', 'alpha');
        $listlimit = GETPOSTINT('listlimit') > 0 ? GETPOSTINT('listlimit') : 1000;
        $active = 1;

        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $listlimit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (empty($sortfield)) {
            $sortfield = 'code';
        }
        if (empty($sortorder)) {
            $sortorder = 'ASC';
        }

        $error = 0;

        $search_country_id = GETPOSTINT('search_country_id');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(['admin']);

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Sort order to show dictionary (0 is space). All other dictionaries (added by modules) will be at end of this.
        $taborder = [35];

// Name of SQL tables of dictionaries
        $tabname = [];
        $tabname[35] = MAIN_DB_PREFIX . "accounting_journal";

// Dictionary labels
        $tablib = [];
        $tablib[35] = "DictionaryAccountancyJournal";

// Requests to extract data
        $tabsql = [];
        $tabsql[35] = "SELECT a.rowid as rowid, a.code as code, a.label, a.nature, a.active FROM " . MAIN_DB_PREFIX . "accounting_journal as a";

// Criteria to sort dictionaries
        $tabsqlsort = [];
        $tabsqlsort[35] = "code ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
        $tabfield = [];
        $tabfield[35] = "code,label,nature";

// Nom des champs d'edition pour modification d'un enregistrement
        $tabfieldvalue = [];
        $tabfieldvalue[35] = "code,label,nature";

// Nom des champs dans la table pour insertion d'un enregistrement
        $tabfieldinsert = [];
        $tabfieldinsert[35] = "code,label,nature";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
        $tabrowid = [];
        $tabrowid[35] = "";

// Condition to show dictionary in setup page
        $tabcond = [];
        $tabcond[35] = isModEnabled('accounting');

// List of help for fields
        $tabhelp = [];
        $tabhelp[35] = ['code' => $langs->trans("EnterAnyCode")];

// List of check for fields (NOT USED YET)
        $tabfieldcheck = [];
        $tabfieldcheck[35] = [];

// Complete all arrays with entries found into modules
        complete_dictionary_with_modules($taborder, $tabname, $tablib, $tabsql, $tabsqlsort, $tabfield, $tabfieldvalue, $tabfieldinsert, $tabrowid, $tabcond, $tabhelp, $tabfieldcheck);


// Define elementList and sourceList (used for dictionary type of contacts "llx_c_type_contact")
        $elementList = [];
// Must match ids defined into eldy.lib.php
        $sourceList = [
            '1' => $langs->trans('AccountingJournalType1'),
            '2' => $langs->trans('AccountingJournalType2'),
            '3' => $langs->trans('AccountingJournalType3'),
            '4' => $langs->trans('AccountingJournalType4'),
            '5' => $langs->trans('AccountingJournalType5'),
            '8' => $langs->trans('AccountingJournalType8'),
            '9' => $langs->trans('AccountingJournalType9'),
        ];

        /*
         * Actions
         */

        if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
            $search_country_id = '';
        }

// Actions add or modify an entry into a dictionary
        if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha')) {
            $listfield = explode(',', str_replace(' ', '', $tabfield[$id]));
            $listfieldinsert = explode(',', $tabfieldinsert[$id]);
            $listfieldmodify = explode(',', $tabfieldinsert[$id]);
            $listfieldvalue = explode(',', $tabfieldvalue[$id]);

            // Check that all fields are filled
            $ok = 1;

            // Other checks
            if (GETPOSTISSET("code")) {
                if (GETPOST("code") == '0') {
                    $ok = 0;
                    setEventMessages($langs->transnoentities('ErrorCodeCantContainZero'), null, 'errors');
                }
            }
            if (!GETPOST('label', 'alpha')) {
                setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
                $ok = 0;
            }

            // Si verif ok et action add, on ajoute la ligne
            if ($ok && GETPOST('actionadd', 'alpha')) {
                if ($tabrowid[$id]) {
                    // Get free id for insert
                    $newid = 0;
                    $sql = "SELECT MAX(" . $db->sanitize($tabrowid[$id]) . ") newid FROM " . $db->sanitize($tabname[$id]);
                    $result = $db->query($sql);
                    if ($result) {
                        $obj = $db->fetch_object($result);
                        $newid = ($obj->newid + 1);
                    } else {
                        dol_print_error($db);
                    }
                }

                // Add new entry
                $sql = "INSERT INTO " . $db->sanitize($tabname[$id]) . " (";
                // List of fields
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
                    $sql .= $tabrowid[$id] . ",";
                }
                $sql .= $db->sanitize($tabfieldinsert[$id]);
                $sql .= ",active,entity)";
                $sql .= " VALUES(";

                // List of values
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
                    $sql .= $newid . ",";
                }
                $i = 0;
                foreach ($listfieldinsert as $f => $value) {
                    if ($i) {
                        $sql .= ",";
                    }
                    if (GETPOST($listfieldvalue[$i]) == '') {
                        $sql .= "null"; // For vat, we want/accept code = ''
                    } else {
                        $sql .= "'" . $db->escape(GETPOST($listfieldvalue[$i])) . "'";
                    }
                    $i++;
                }
                $sql .= ",1," . $conf->entity . ")";

                dol_syslog("actionadd", LOG_DEBUG);
                $result = $db->query($sql);
                if ($result) {  // Add is ok
                    setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
                    $_POST = ['id' => $id]; // Clean $_POST array, we keep only id
                } else {
                    if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                        setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
                    } else {
                        dol_print_error($db);
                    }
                }
            }

            // Si verif ok et action modify, on modifie la ligne
            if ($ok && GETPOST('actionmodify', 'alpha')) {
                if ($tabrowid[$id]) {
                    $rowidcol = $tabrowid[$id];
                } else {
                    $rowidcol = "rowid";
                }

                // Modify entry
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET ";
                // Modifie valeur des champs
                if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldmodify)) {
                    $sql .= $db->sanitize($tabrowid[$id]) . " = ";
                    $sql .= "'" . $db->escape($rowid) . "', ";
                }
                $i = 0;
                foreach ($listfieldmodify as $field) {
                    if ($i) {
                        $sql .= ",";
                    }
                    $sql .= $field . " = ";
                    $sql .= "'" . $db->escape(GETPOST($listfieldvalue[$i])) . "'";
                    $i++;
                }
                $sql .= " WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
                $sql .= " AND entity = " . ((int) $conf->entity);

                dol_syslog("actionmodify", LOG_DEBUG);
                //print $sql;
                $resql = $db->query($sql);
                if (!$resql) {
                    setEventMessages($db->error(), null, 'errors');
                }
            }
            //$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
        }

//if (GETPOST('actioncancel', 'alpha'))
//{
//  $_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
//}

        if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
            if ($tabrowid[$id]) {
                $rowidcol = $tabrowid[$id];
            } else {
                $rowidcol = "rowid";
            }

            $sql = "DELETE from " . $db->sanitize($tabname[$id]) . " WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
            $sql .= " AND entity = " . ((int) $conf->entity);

            dol_syslog("delete", LOG_DEBUG);
            $result = $db->query($sql);
            if (!$result) {
                if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
                    setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
                } else {
                    dol_print_error($db);
                }
            }
        }

// activate
        if ($action == $acts[0]) {
            if ($tabrowid[$id]) {
                $rowidcol = $tabrowid[$id];
            } else {
                $rowidcol = "rowid";
            }

            if ($rowid) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 1 WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
            } elseif ($code) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 1 WHERE code = '" . $db->escape($code) . "'";
            }
            $sql .= " AND entity = " . $conf->entity;

            $result = $db->query($sql);
            if (!$result) {
                dol_print_error($db);
            }
        }

// disable
        if ($action == $acts[1]) {
            if ($tabrowid[$id]) {
                $rowidcol = $tabrowid[$id];
            } else {
                $rowidcol = "rowid";
            }

            if ($rowid) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 0 WHERE " . $db->sanitize($rowidcol) . " = " . ((int) $rowid);
            } elseif ($code) {
                $sql = "UPDATE " . $db->sanitize($tabname[$id]) . " SET active = 0 WHERE code='" . $db->escape($code) . "'";
            }
            $sql .= " AND entity = " . $conf->entity;

            $result = $db->query($sql);
            if (!$result) {
                dol_print_error($db);
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_journals_list.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/productaccount.php
     * \ingroup     Accountancy (Double entries)
     * \brief       To define accounting account on product / service
     */
    public function productaccount()
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
        $langs->loadLangs(["companies", "compta", "accountancy", "products"]);

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'bind', 'write')) {
            accessforbidden();
        }

// search & action GETPOST
        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $optioncss = GETPOST('optioncss', 'alpha');

        $codeventil_buy = GETPOST('codeventil_buy', 'array');
        $codeventil_sell = GETPOST('codeventil_sell', 'array');
        $chk_prod = GETPOST('chk_prod', 'array');
        $default_account = GETPOSTINT('default_account');
        $account_number_buy = GETPOST('account_number_buy');
        $account_number_sell = GETPOST('account_number_sell');
        $changeaccount = GETPOST('changeaccount', 'array');
        $changeaccount_buy = GETPOST('changeaccount_buy', 'array');
        $changeaccount_sell = GETPOST('changeaccount_sell', 'array');
        $searchCategoryProductOperator = (GETPOSTINT('search_category_product_operator') ? GETPOSTINT('search_category_product_operator') : 0);
        $searchCategoryProductList = GETPOST('search_category_product_list', 'array');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_desc = GETPOST('search_desc', 'alpha');
        $search_vat = GETPOST('search_vat', 'alpha');
        $search_current_account = GETPOST('search_current_account', 'alpha');
        $search_current_account_valid = GETPOST('search_current_account_valid', 'alpha');
        if ($search_current_account_valid == '') {
            $search_current_account_valid = 'withoutvalidaccount';
        }
        $search_onsell = GETPOST('search_onsell', 'alpha');
        $search_onpurchase = GETPOST('search_onpurchase', 'alpha');

        $accounting_product_mode = GETPOST('accounting_product_mode', 'alpha');
        $btn_changetype = GETPOST('changetype', 'alpha');

        if (empty($accounting_product_mode)) {
            $accounting_product_mode = 'ACCOUNTANCY_SELL';
        }

        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalInt('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1) {
            $page = 0;
        }     // If $page is not defined, or '' or -1
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = "p.ref";
        }
        if (!$sortorder) {
            $sortorder = "ASC";
        }

        if (empty($action)) {
            $action = 'list';
        }

        $arrayfields = [];

        $accounting_product_modes = [
            'ACCOUNTANCY_SELL',
            'ACCOUNTANCY_SELL_INTRA',
            'ACCOUNTANCY_SELL_EXPORT',
            'ACCOUNTANCY_BUY',
            'ACCOUNTANCY_BUY_INTRA',
            'ACCOUNTANCY_BUY_EXPORT',
        ];

        if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
            $accountancy_field_name = "accountancy_code_buy";
        } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
            $accountancy_field_name = "accountancy_code_buy_intra";
        } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
            $accountancy_field_name = "accountancy_code_buy_export";
        } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
            $accountancy_field_name = "accountancy_code_sell";
        } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
            $accountancy_field_name = "accountancy_code_sell_intra";
        } else { // $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT'
            $accountancy_field_name = "accountancy_code_sell_export";
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

        $parameters = [];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

// Purge search criteria
        if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
            $searchCategoryProductOperator = 0;
            $searchCategoryProductList = [];
            $search_ref = '';
            $search_label = '';
            $search_desc = '';
            $search_vat = '';
            $search_onsell = '';
            $search_onpurchase = '';
            $search_current_account = '';
            $search_current_account_valid = '-1';
        }

// Sales or Purchase mode ?
        if ($action == 'update') {
            if (!empty($btn_changetype)) {
                $error = 0;

                if (in_array($accounting_product_mode, $accounting_product_modes)) {
                    if (!dolibarr_set_const($db, 'ACCOUNTING_PRODUCT_MODE', $accounting_product_mode, 'chaine', 0, '', $conf->entity)) {
                        $error++;
                    }
                } else {
                    $error++;
                }
            }

            if (!empty($chk_prod) && $massaction === 'changeaccount') {
                //$msg = '<div><span class="accountingprocessing">' . $langs->trans("Processing") . '...</span></div>';
                if (!empty($chk_prod) && in_array($accounting_product_mode, $accounting_product_modes)) {
                    $accounting = new AccountingAccount($db);

                    //$msg .= '<div><span class="accountingprocessing">' . count($chk_prod) . ' ' . $langs->trans("SelectedLines") . '</span></div>';
                    $arrayofdifferentselectedvalues = [];

                    $cpt = 0;
                    $ok = 0;
                    $ko = 0;
                    foreach ($chk_prod as $productid) {
                        $accounting_account_id = GETPOST('codeventil_' . $productid);

                        $result = 0;
                        if ($accounting_account_id > 0) {
                            $arrayofdifferentselectedvalues[$accounting_account_id] = $accounting_account_id;
                            $result = $accounting->fetch($accounting_account_id, null, 1);
                        }
                        if ($result <= 0) {
                            // setEventMessages(null, $accounting->errors, 'errors');
                            $msg .= '<div><span class="error">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Product") . ' ' . $productid . ' ' . $langs->trans("NotVentilatedinAccount") . ' : id=' . $accounting_account_id . '<br> <pre>' . $sql . '</pre></span></div>';
                            $ko++;
                        } else {
                            $sql = '';
                            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                                $sql_exists = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product_perentity";
                                $sql_exists .= " WHERE fk_product = " . ((int) $productid) . " AND entity = " . ((int) $conf->entity);
                                $resql_exists = $db->query($sql_exists);
                                if (!$resql_exists) {
                                    $msg .= '<div><span class="error">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Product") . ' ' . $productid . ' ' . $langs->trans("NotVentilatedinAccount") . ' : id=' . $accounting_account_id . '<br> <pre>' . json_encode($resql_exists) . '</pre></span></div>';
                                    $ko++;
                                } else {
                                    $nb_exists = $db->num_rows($resql_exists);
                                    if ($nb_exists <= 0) {
                                        // insert
                                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_perentity (fk_product, entity, " . $db->sanitize($accountancy_field_name) . ")";
                                        $sql .= " VALUES (" . ((int) $productid) . ", " . ((int) $conf->entity) . ", '" . $db->escape($accounting->account_number) . "')";
                                    } else {
                                        $obj_exists = $db->fetch_object($resql_exists);
                                        // update
                                        $sql = "UPDATE " . MAIN_DB_PREFIX . "product_perentity";
                                        $sql .= " SET " . $db->sanitize($accountancy_field_name) . " = '" . $db->escape($accounting->account_number) . "'";
                                        $sql .= " WHERE rowid = " . ((int) $obj_exists->rowid);
                                    }
                                }
                            } else {
                                $sql = " UPDATE " . MAIN_DB_PREFIX . "product";
                                $sql .= " SET " . $db->sanitize($accountancy_field_name) . " = '" . $db->escape($accounting->account_number) . "'";
                                $sql .= " WHERE rowid = " . ((int) $productid);
                            }

                            dol_syslog("/accountancy/admin/productaccount.php", LOG_DEBUG);

                            $db->begin();

                            if ($db->query($sql)) {
                                $ok++;
                                $db->commit();
                            } else {
                                $ko++;
                                $db->rollback();
                            }
                        }

                        $cpt++;
                    }
                }

                if ($ko) {
                    setEventMessages($langs->trans("XLineFailedToBeBinded", $ko), null, 'errors');
                }
                if ($ok) {
                    setEventMessages($langs->trans("XLineSuccessfullyBinded", $ok), null, 'mesgs');
                }
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_productaccount.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/subaccount.php
     * \ingroup     Accountancy (Double entries)
     * \brief       List of accounting sub-account (auxiliary accounts)
     */
    public function subaccount()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(["accountancy", "admin", "bills", "compta", "errors", "hrm", "salaries"]);

        $mesg = '';
        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');
        $id = GETPOSTINT('id');
        $rowid = GETPOSTINT('rowid');
        $massaction = GETPOST('massaction', 'aZ09');
        $optioncss = GETPOST('optioncss', 'alpha');
        $mode = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accountingsubaccountlist'; // To manage different context of search

        $search_subaccount = GETPOST('search_subaccount', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_type = GETPOSTINT('search_type');

// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'chartofaccount')) {
            accessforbidden();
        }

// Load variable for pagination
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
        if (!$sortfield) {
            $sortfield = "label";
        }
        if (!$sortorder) {
            $sortorder = "ASC";
        }

        $arrayfields = [
            'subaccount' => ['label' => $langs->trans("AccountNumber"), 'checked' => 1],
            'label' => ['label' => $langs->trans("Label"), 'checked' => 1],
            'type' => ['label' => $langs->trans("Type"), 'checked' => 1],
            'reconcilable' => ['label' => $langs->trans("Reconcilable"), 'checked' => 1],
        ];

        if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
            unset($arrayfields['reconcilable']);
        }


        /*
         * Actions
         */

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha')) {
            $massaction = '';
        }

        $parameters = [];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            if (!empty($cancel)) {
                $action = '';
            }

            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
                $search_subaccount = "";
                $search_label = "";
                $search_type = "";
                $search_array_options = [];
            }
        }


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/admin_subaccount.php');

        $db->close();
    }

    /**
     *  Show fields in insert/edit mode
     *
     * @param array  $fieldlist Array of fields
     * @param Object $obj       If we show a particular record, obj is filled with record fields
     * @param string $tabname   Name of SQL table
     * @param string $context   'add'=Output field for the "add form", 'edit'=Output field for the "edit form",
     *                          'hide'=Output field for the "add form" but we don't want it to be rendered
     *
     * @return     void
     */
    private function fieldListAccountModel($fieldlist, $obj = null, $tabname = '', $context = '')
    {
        global $langs, $db;
        global $form;
        global $elementList, $sourceList;

        $formadmin = new FormAdmin($db);
        $formcompany = new FormCompany($db);
        $formaccounting = new FormAccounting($db);

        foreach ($fieldlist as $field => $value) {
            if ($fieldlist[$field] == 'country') {
                if (in_array('region_id', $fieldlist)) {
                    print '<td>';
                    //print join(',',$fieldlist);
                    print '</td>';
                    continue;
                }   // For state page, we do not show the country input (we link to region, not country)
                print '<td>';
                $fieldname = 'country';
                print $form->select_country((!empty($obj->country_code) ? $obj->country_code : (!empty($obj->country) ? $obj->country : '')), $fieldname, '', 28, 'maxwidth200 maxwidthonsmartphone');
                print '</td>';
            } elseif ($fieldlist[$field] == 'country_id') {
                if (!in_array('country', $fieldlist)) { // If there is already a field country, we don't show country_id (avoid duplicate)
                    $country_id = (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : 0);
                    print '<td>';
                    print '<input type="hidden" name="' . $fieldlist[$field] . '" value="' . $country_id . '">';
                    print '</td>';
                }
            } elseif ($fieldlist[$field] == 'type_cdr') {
                if ($fieldlist[$field] == 'type_cdr') {
                    print '<td class="center">';
                } else {
                    print '<td>';
                }
                if ($fieldlist[$field] == 'type_cdr') {
                    print $form->selectarray($fieldlist[$field], [0 => $langs->trans('None'), 1 => $langs->trans('AtEndOfMonth'), 2 => $langs->trans('CurrentNext')], (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : ''));
                } else {
                    print $form->selectyesno($fieldlist[$field], (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : ''), 1);
                }
                print '</td>';
            } elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
                print '<td><input type="text" class="flat" value="' . (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '') . '" size="10" name="' . $fieldlist[$field] . '"></td>';
            } else {
                print '<td>';
                $class = '';
                if ($fieldlist[$field] == 'pcg_version') {
                    $class = 'width150';
                }
                if ($fieldlist[$field] == 'label') {
                    $class = 'width300';
                }
                print '<input type="text" class="flat' . ($class ? ' ' . $class : '') . '" value="' . (isset($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '') . '" name="' . $fieldlist[$field] . '">';
                print '</td>';
            }
        }
    }

    /**
     *  Show fields in insert/edit mode
     *
     * @param array  $fieldlist Array of fields
     * @param Object $obj       If we show a particular record, obj is filled with record fields
     * @param string $tabname   Name of SQL table
     * @param string $context   'add'=Output field for the "add form", 'edit'=Output field for the "edit form",
     *                          'hide'=Output field for the "add form" but we don't want it to be rendered
     *
     * @return     void
     */
    private function fieldListJournal($fieldlist, $obj = null, $tabname = '', $context = '')
    {
        global $db, $form, $sourceList;

        $formadmin = new FormAdmin($db);
        $formcompany = new FormCompany($db);

        foreach ($fieldlist as $field => $value) {
            if ($fieldlist[$field] == 'nature') {
                print '<td>';
                print $form->selectarray('nature', $sourceList, (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : ''));
                print '</td>';
            } elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
                print '<td><input type="text" class="flat minwidth100" value="' . (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '') . '" name="' . $fieldlist[$field] . '"></td>';
            } else {
                print '<td>';
                $size = '';
                $class = '';
                if ($fieldlist[$field] == 'code') {
                    $class = 'maxwidth100';
                }
                if ($fieldlist[$field] == 'label') {
                    $class = 'quatrevingtpercent';
                }
                if ($fieldlist[$field] == 'sortorder' || $fieldlist[$field] == 'sens' || $fieldlist[$field] == 'category_type') {
                    $size = 'size="2" ';
                }
                print '<input type="text" ' . $size . 'class="flat' . ($class ? ' ' . $class : '') . '" value="' . (isset($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '') . '" name="' . $fieldlist[$field] . '">';
                print '</td>';
            }
        }
    }
}
