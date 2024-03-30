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
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancycategory.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancysystem.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fiscalyear.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcategory.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

use AccountingAccount;
use Categorie;
use DoliCore\Base\DolibarrController;
use Fiscalyear;
use Form;
use FormAccounting;
use FormAdmin;
use FormCategory;
use FormCompany;
use Product;
use stdClass;

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
        $form = new Form($db);
        $formaccounting = new FormAccounting($db);

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $langs->trans("ListAccounts"), $help_url);

        if ($action == 'delete') {
            $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $id, $langs->trans('DeleteAccount'), $langs->trans('ConfirmDeleteAccount'), 'confirm_delete', '', 0, 1);
            print $formconfirm;
        }

        $pcgver = getDolGlobalInt('CHARTOFACCOUNTS');

        $sql = "SELECT aa.rowid, aa.fk_pcg_version, aa.pcg_type, aa.account_number, aa.account_parent, aa.label, aa.labelshort, aa.fk_accounting_category,";
        $sql .= " aa.reconcilable, aa.active, aa.import_key,";
        $sql .= " a2.rowid as rowid2, a2.label as label2, a2.account_number as account_number2";

// Add fields from hooks
        $parameters = [];
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql = preg_replace('/,\s*$/', '', $sql);

        $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_account as aa";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version AND aa.entity = " . ((int) $conf->entity);
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as a2 ON a2.rowid = aa.account_parent AND a2.entity = " . ((int) $conf->entity);

// Add table from hooks
        $parameters = [];
        $reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;

        $sql .= " WHERE asy.rowid = " . ((int) $pcgver);

        if (strlen(trim($search_account))) {
            $lengthpaddingaccount = 0;
            if (getDolGlobalInt('ACCOUNTING_LENGTH_GACCOUNT') || getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
                $lengthpaddingaccount = max(getDolGlobalInt('ACCOUNTING_LENGTH_GACCOUNT'), getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT'));
            }
            $search_account_tmp = $search_account;
            $weremovedsomezero = 0;
            if (strlen($search_account_tmp) <= $lengthpaddingaccount) {
                for ($i = 0; $i < $lengthpaddingaccount; $i++) {
                    if (preg_match('/0$/', $search_account_tmp)) {
                        $weremovedsomezero++;
                        $search_account_tmp = preg_replace('/0$/', '', $search_account_tmp);
                    }
                }
            }

            //var_dump($search_account); exit;
            if ($search_account_tmp) {
                if ($weremovedsomezero) {
                    $search_account_tmp_clean = $search_account_tmp;
                    $search_account_clean = $search_account;
                    $startchar = '%';
                    if (substr($search_account_tmp, 0, 1) === '^') {
                        $startchar = '';
                        $search_account_tmp_clean = preg_replace('/^\^/', '', $search_account_tmp);
                        $search_account_clean = preg_replace('/^\^/', '', $search_account);
                    }
                    $sql .= " AND (aa.account_number LIKE '" . $db->escape($startchar . $search_account_tmp_clean) . "'";
                    $sql .= " OR aa.account_number LIKE '" . $db->escape($startchar . $search_account_clean) . "%')";
                } else {
                    $sql .= natural_search("aa.account_number", $search_account_tmp);
                }
            }
        }
        if (strlen(trim($search_label))) {
            $sql .= natural_search("aa.label", $search_label);
        }
        if (strlen(trim($search_labelshort))) {
            $sql .= natural_search("aa.labelshort", $search_labelshort);
        }
        if (strlen(trim($search_accountparent)) && $search_accountparent != '-1') {
            $sql .= natural_search("aa.account_parent", $search_accountparent, 2);
        }
        if (strlen(trim($search_pcgtype))) {
            $sql .= natural_search("aa.pcg_type", $search_pcgtype);
        }
        if (strlen(trim($search_import_key))) {
            $sql .= natural_search("aa.import_key", $search_import_key);
        }

// Add where from hooks
        $parameters = [];
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;

        $sql .= $db->order($sortfield, $sortorder);
//print $sql;

// Count total nb of records
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            $resql = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($resql);
            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
        }

        $sql .= $db->plimit($limit + 1, $offset);

        dol_syslog('accountancy/admin/account.php:: $sql=' . $sql);
        $resql = $db->query($sql);

        if ($resql) {
            $num = $db->num_rows($resql);

            $arrayofselected = is_array($toselect) ? $toselect : [];

            $param = '';
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }
            if ($search_account) {
                $param .= '&search_account=' . urlencode($search_account);
            }
            if ($search_label) {
                $param .= '&search_label=' . urlencode($search_label);
            }
            if ($search_labelshort) {
                $param .= '&search_labelshort=' . urlencode($search_labelshort);
            }
            if ($search_accountparent > 0 || $search_accountparent == '0') {
                $param .= '&search_accountparent=' . urlencode($search_accountparent);
            }
            if ($search_pcgtype) {
                $param .= '&search_pcgtype=' . urlencode($search_pcgtype);
            }
            if ($search_import_key) {
                $param .= '&search_import_key=' . urlencode($search_import_key);
            }
            if ($optioncss != '') {
                $param .= '&optioncss=' . urlencode($optioncss);
            }

            // Add $param from hooks
            $parameters = [];
            $reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            $param .= $hookmanager->resPrint;

            if (!empty($conf->use_javascript_ajax)) {
                print '<!-- Add javascript to reload page when we click "Change plan" -->
			<script type="text/javascript">
			$(document).ready(function () {
		    	$("#change_chart").on("click", function (e) {
					console.log("chartofaccounts selected = "+$("#chartofaccounts").val());
					// reload page
					window.location.href = "' . $_SERVER['PHP_SELF'] . '?valid_change_chart=1&chartofaccounts="+$("#chartofaccounts").val();
			    });
			});
	    	</script>';
            }

            // List of mass actions available
            $arrayofmassactions = [];
            if ($user->hasRight('accounting', 'chartofaccount')) {
                $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>' . $langs->trans("Delete");
            }
            if (in_array($massaction, ['presend', 'predelete', 'closed'])) {
                $arrayofmassactions = [];
            }

            $massactionbutton = $form->selectMassAction('', $arrayofmassactions);

            $newcardbutton = '';
            $newcardbutton = dolGetButtonTitle($langs->trans('Addanaccount'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/accountancy/admin/card.php?action=create', '', $permissiontoadd);


            print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="action" value="list">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
            print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            print_barre_liste($langs->trans('ListAccounts'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'accounting_account', 0, $newcardbutton, '', $limit, 0, 0, 1);

            include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

            // Box to select active chart of account
            print $langs->trans("Selectchartofaccounts") . " : ";
            print '<select class="flat minwidth200" name="chartofaccounts" id="chartofaccounts">';
            $sql = "SELECT a.rowid, a.pcg_version, a.label, a.active, c.code as country_code";
            $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_system as a";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_country as c ON a.fk_country = c.rowid AND c.active = 1";
            $sql .= " WHERE a.active = 1";
            dol_syslog('accountancy/admin/account.php $sql=' . $sql);

            $resqlchart = $db->query($sql);
            if ($resqlchart) {
                $numbis = $db->num_rows($resqlchart);
                $i = 0;
                print '<option value="-1">&nbsp;</option>';
                while ($i < $numbis) {
                    $obj = $db->fetch_object($resqlchart);
                    if ($obj) {
                        print '<option value="' . $obj->rowid . '"';
                        print ($pcgver == $obj->rowid) ? ' selected' : '';
                        print '>' . $obj->pcg_version . ' - ' . $obj->label . ' - (' . $obj->country_code . ')</option>';
                    }
                    $i++;
                }
            } else {
                dol_print_error($db);
            }
            print "</select>";
            print ajax_combobox("chartofaccounts");
            print '<input type="' . (empty($conf->use_javascript_ajax) ? 'submit' : 'button') . '" class="button button-edit small" name="change_chart" id="change_chart" value="' . dol_escape_htmltag($langs->trans("ChangeAndLoad")) . '">';

            print '<br>';

            $parameters = [];
            $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            print '<br>';

            $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
            $selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) : ''); // This also change content of $arrayfields
            $selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

            $moreforfilter = '';
            if ($moreforfilter) {
                print '<div class="liste_titre liste_titre_bydiv centpercent">';
                print $moreforfilter;
                print '</div>';
            }

            $accountstatic = new AccountingAccount($db);
            $accountparent = new AccountingAccount($db);
            $totalarray = [];
            $totalarray['nbfield'] = 0;

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

            // Fields title search
            // --------------------------------------------------------------------
            print '<tr class="liste_titre_filter">';

            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre center maxwidthsearch">';
                $searchpicto = $form->showFilterButtons('left');
                print $searchpicto;
                print '</td>';
            }
            if (!empty($arrayfields['aa.account_number']['checked'])) {
                print '<td class="liste_titre"><input type="text" class="flat width100" name="search_account" value="' . $search_account . '"></td>';
            }
            if (!empty($arrayfields['aa.label']['checked'])) {
                print '<td class="liste_titre"><input type="text" class="flat width150" name="search_label" value="' . $search_label . '"></td>';
            }
            if (!empty($arrayfields['aa.labelshort']['checked'])) {
                print '<td class="liste_titre"><input type="text" class="flat width100" name="search_labelshort" value="' . $search_labelshort . '"></td>';
            }
            if (!empty($arrayfields['aa.account_parent']['checked'])) {
                print '<td class="liste_titre">';
                print $formaccounting->select_account($search_accountparent, 'search_accountparent', 2, [], 0, 0, 'maxwidth150');
                print '</td>';
            }
            // Predefined group
            if (!empty($arrayfields['aa.pcg_type']['checked'])) {
                print '<td class="liste_titre"><input type="text" class="flat width75" name="search_pcgtype" value="' . $search_pcgtype . '"></td>';
            }
            // Custom groups
            if (!empty($arrayfields['categories']['checked'])) {
                print '<td class="liste_titre"></td>';
            }

            // Fields from hook
            $parameters = ['arrayfields' => $arrayfields];
            $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            // Import key
            if (!empty($arrayfields['aa.import_key']['checked'])) {
                print '<td class="liste_titre"><input type="text" class="flat width75" name="search_import_key" value="' . $search_import_key . '"></td>';
            }
            if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
                if (!empty($arrayfields['aa.reconcilable']['checked'])) {
                    print '<td class="liste_titre">&nbsp;</td>';
                }
            }
            if (!empty($arrayfields['aa.active']['checked'])) {
                print '<td class="liste_titre">&nbsp;</td>';
            }
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre center maxwidthsearch">';
                $searchpicto = $form->showFilterButtons();
                print $searchpicto;
                print '</td>';
            }
            print '</tr>' . "\n";

            $totalarray = [];
            $totalarray['nbfield'] = 0;

            // Fields title label
            // --------------------------------------------------------------------
            print '<tr class="liste_titre">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
                $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['aa.account_number']['checked'])) {
                print_liste_field_titre($arrayfields['aa.account_number']['label'], $_SERVER['PHP_SELF'], "aa.account_number", "", $param, '', $sortfield, $sortorder);
                $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['aa.label']['checked'])) {
                print_liste_field_titre($arrayfields['aa.label']['label'], $_SERVER['PHP_SELF'], "aa.label", "", $param, '', $sortfield, $sortorder);
                $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['aa.labelshort']['checked'])) {
                print_liste_field_titre($arrayfields['aa.labelshort']['label'], $_SERVER['PHP_SELF'], "aa.labelshort", "", $param, '', $sortfield, $sortorder);
                $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['aa.account_parent']['checked'])) {
                print_liste_field_titre($arrayfields['aa.account_parent']['label'], $_SERVER['PHP_SELF'], "aa.account_parent", "", $param, '', $sortfield, $sortorder, 'left ');
                $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['aa.pcg_type']['checked'])) {
                print_liste_field_titre($arrayfields['aa.pcg_type']['label'], $_SERVER['PHP_SELF'], 'aa.pcg_type,aa.account_number', '', $param, '', $sortfield, $sortorder, '', $arrayfields['aa.pcg_type']['help'], 1);
                $totalarray['nbfield']++;
            }
            if (!empty($arrayfields['categories']['checked'])) {
                print_liste_field_titre($arrayfields['categories']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, '', $arrayfields['categories']['help'], 1);
                $totalarray['nbfield']++;
            }

            // Hook fields
            $parameters = ['arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder];
            $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            if (!empty($arrayfields['aa.import_key']['checked'])) {
                print_liste_field_titre($arrayfields['aa.import_key']['label'], $_SERVER['PHP_SELF'], 'aa.import_key', '', $param, '', $sortfield, $sortorder, '', $arrayfields['aa.import_key']['help'], 1);
                $totalarray['nbfield']++;
            }
            if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
                if (!empty($arrayfields['aa.reconcilable']['checked'])) {
                    print_liste_field_titre($arrayfields['aa.reconcilable']['label'], $_SERVER['PHP_SELF'], 'aa.reconcilable', '', $param, '', $sortfield, $sortorder);
                    $totalarray['nbfield']++;
                }
            }
            if (!empty($arrayfields['aa.active']['checked'])) {
                print_liste_field_titre($arrayfields['aa.active']['label'], $_SERVER['PHP_SELF'], 'aa.active', '', $param, '', $sortfield, $sortorder);
                $totalarray['nbfield']++;
            }
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
                $totalarray['nbfield']++;
            }
            print "</tr>\n";

            // Loop on record
            // --------------------------------------------------------------------
            $i = 0;
            while ($i < min($num, $limit)) {
                $obj = $db->fetch_object($resql);

                $accountstatic->id = $obj->rowid;
                $accountstatic->label = $obj->label;
                $accountstatic->account_number = $obj->account_number;

                print '<tr class="oddeven">';

                // Action column
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center nowraponall">';
                    if ($user->hasRight('accounting', 'chartofaccount')) {
                        print '<a class="editfielda" href="./card.php?action=update&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?' . $param) . '">';
                        print img_edit();
                        print '</a>';
                        print '&nbsp;';
                        print '<a class="marginleftonly" href="./card.php?action=delete&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?' . $param) . '">';
                        print img_delete();
                        print '</a>';
                        print '&nbsp;';
                        if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                            $selected = 0;
                            if (in_array($obj->rowid, $arrayofselected)) {
                                $selected = 1;
                            }
                            print '<input id="cb' . $obj->rowid . '" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
                        }
                    }
                    print '</td>' . "\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Account number
                if (!empty($arrayfields['aa.account_number']['checked'])) {
                    print "<td>";
                    print $accountstatic->getNomUrl(1, 0, 0, '', 0, 1, 0, 'accountcard');
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Account label
                if (!empty($arrayfields['aa.label']['checked'])) {
                    print '<td class="tdoverflowmax150" title="' . dol_escape_htmltag($obj->label) . '">';
                    print dol_escape_htmltag($obj->label);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Account label to show (label short)
                if (!empty($arrayfields['aa.labelshort']['checked'])) {
                    print "<td>";
                    print dol_escape_htmltag($obj->labelshort);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Account parent
                if (!empty($arrayfields['aa.account_parent']['checked'])) {
                    // Note: obj->account_parent is a foreign key to a rowid. It is field in child table and obj->rowid2 is same, but in parent table.
                    // So for orphans, obj->account_parent is set but not obj->rowid2
                    if (!empty($obj->account_parent) && !empty($obj->rowid2)) {
                        print "<td>";
                        print '<!-- obj->account_parent = ' . $obj->account_parent . ' obj->rowid2 = ' . $obj->rowid2 . ' -->';
                        $accountparent->id = $obj->rowid2;
                        $accountparent->label = $obj->label2;
                        $accountparent->account_number = $obj->account_number2; // Store an account number for output
                        print $accountparent->getNomUrl(1);
                        print "</td>\n";
                        if (!$i) {
                            $totalarray['nbfield']++;
                        }
                    } else {
                        print '<td>';
                        if (!empty($obj->account_parent)) {
                            print '<!-- Bad value for obj->account_parent = ' . $obj->account_parent . ': is a rowid that does not exists -->';
                        }
                        print '</td>';
                        if (!$i) {
                            $totalarray['nbfield']++;
                        }
                    }
                }

                // Predefined group (deprecated)
                if (!empty($arrayfields['aa.pcg_type']['checked'])) {
                    print "<td>";
                    print dol_escape_htmltag($obj->pcg_type);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }
                // Custom accounts
                if (!empty($arrayfields['categories']['checked'])) {
                    print "<td>";
                    // TODO Get all custom groups labels the account is in
                    print dol_escape_htmltag($obj->fk_accounting_category);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Fields from hook
                $parameters = ['arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray];
                $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;

                // Import id
                if (!empty($arrayfields['aa.import_key']['checked'])) {
                    print "<td>";
                    print dol_escape_htmltag($obj->import_key);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
                    // Activated or not reconciliation on an accounting account
                    if (!empty($arrayfields['aa.reconcilable']['checked'])) {
                        print '<td class="center">';
                        if (empty($obj->reconcilable)) {
                            print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=enable&mode=1&token=' . newToken() . '">';
                            print img_picto($langs->trans("Disabled"), 'switch_off');
                            print '</a>';
                        } else {
                            print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=disable&mode=1&token=' . newToken() . '">';
                            print img_picto($langs->trans("Activated"), 'switch_on');
                            print '</a>';
                        }
                        print '</td>';
                        if (!$i) {
                            $totalarray['nbfield']++;
                        }
                    }
                }

                // Activated or not
                if (!empty($arrayfields['aa.active']['checked'])) {
                    print '<td class="center">';
                    if (empty($obj->active)) {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=enable&mode=0&token=' . newToken() . '">';
                        print img_picto($langs->trans("Disabled"), 'switch_off');
                        print '</a>';
                    } else {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=disable&mode=0&token=' . newToken() . '">';
                        print img_picto($langs->trans("Activated"), 'switch_on');
                        print '</a>';
                    }
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Action column
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center nowraponall">';
                    if ($user->hasRight('accounting', 'chartofaccount')) {
                        print '<a class="editfielda" href="./card.php?action=update&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?' . $param) . '">';
                        print img_edit();
                        print '</a>';
                        print '&nbsp;';
                        print '<a class="marginleftonly" href="./card.php?action=delete&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . '?' . $param) . '">';
                        print img_delete();
                        print '</a>';
                        print '&nbsp;';
                        if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                            $selected = 0;
                            if (in_array($obj->rowid, $arrayofselected)) {
                                $selected = 1;
                            }
                            print '<input id="cb' . $obj->rowid . '" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="' . $obj->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
                        }
                    }
                    print '</td>' . "\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                print "</tr>\n";
                $i++;
            }

            if ($num == 0) {
                $colspan = 1;
                foreach ($arrayfields as $key => $val) {
                    if (!empty($val['checked'])) {
                        $colspan++;
                    }
                }
                print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("None") . '</span></td></tr>';
            }

            $db->free($resql);

            $parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
            $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            print '</table>' . "\n";
            print '</div>' . "\n";

            print '</form>' . "\n";
        } else {
            dol_print_error($db);
        }

// End of page
        llxFooter();
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
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
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

        $form = new Form($db);
        $formadmin = new FormAdmin($db);

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $langs->trans("Pcg_version"), $help_url);

        $titre = $langs->trans($tablib[$id]);
        $linkback = '';

        print load_fiche_titre($titre, $linkback, 'title_accountancy');


// Confirmation de la suppression de la ligne
        if ($action == 'delete') {
            print $form->formconfirm($_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . urlencode((string) ($page)) . '&' : '') . 'sortfield=' . urlencode((string) ($sortfield)) . '&sortorder=' . urlencode((string) ($sortorder)) . '&rowid=' . urlencode((string) ($rowid)) . '&code=' . urlencode((string) ($code)) . '&id=' . urlencode((string) ($id)), $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
        }
//var_dump($elementList);

        /*
         * Show a dictionary
         */
        if ($id) {
            // Complete requete recherche valeurs avec critere de tri
            $sql = $tabsql[$id];

            if ($search_country_id > 0) {
                if (preg_match('/ WHERE /', $sql)) {
                    $sql .= " AND ";
                } else {
                    $sql .= " WHERE ";
                }
                $sql .= " c.rowid = " . ((int) $search_country_id);
            }

            // If sort order is "country", we use country_code instead
            if ($sortfield == 'country') {
                $sortfield = 'country_code';
            }
            $sql .= $db->order($sortfield, $sortorder);
            $sql .= $db->plimit($listlimit + 1, $offset);
            //print $sql;

            $fieldlist = explode(',', $tabfield[$id]);

            print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';

            print '<div class="div-table-responsive">';
            print '<table class="noborder centpercent">';

            // Form to add a new line

            if ($tabname[$id]) {
                $fieldlist = explode(',', $tabfield[$id]);

                // Line for title
                print '<tr class="liste_titre">';
                foreach ($fieldlist as $field => $value) {
                    // Determine le nom du champ par rapport aux noms possibles
                    // dans les dictionnaires de donnees
                    $valuetoshow = ucfirst($fieldlist[$field]); // By default
                    $valuetoshow = $langs->trans($valuetoshow); // try to translate
                    $class = "left";
                    if ($fieldlist[$field] == 'code') {
                        $valuetoshow = $langs->trans("Code");
                    }
                    if ($fieldlist[$field] == 'label') {
                        $valuetoshow = $langs->trans("Label");
                        $class = 'minwidth300';
                    }
                    if ($fieldlist[$field] == 'country') {
                        if (in_array('region_id', $fieldlist)) {
                            print '<td>&nbsp;</td>';
                            continue;
                        }       // For region page, we do not show the country input
                        $valuetoshow = $langs->trans("Country");
                    }
                    if ($fieldlist[$field] == 'country_id') {
                        $valuetoshow = '';
                    }
                    if ($fieldlist[$field] == 'pcg_version' || $fieldlist[$field] == 'fk_pcg_version') {
                        $valuetoshow = $langs->trans("Pcg_version");
                    }
                    //var_dump($value);

                    if ($valuetoshow != '') {
                        print '<td class="' . $class . '">';
                        if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
                            print '<a href="' . $tabhelp[$id][$value] . '">' . $valuetoshow . ' ' . img_help(1, $valuetoshow) . '</a>';
                        } elseif (!empty($tabhelp[$id][$value])) {
                            print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
                        } else {
                            print $valuetoshow;
                        }
                        print '</td>';
                    }
                }

                print '<td>';
                print '<input type="hidden" name="id" value="' . $id . '">';
                print '</td>';
                print '<td></td>';
                print '<td></td>';
                print '</tr>';

                // Line to enter new values
                print '<tr class="oddeven">';

                $obj = new stdClass();
                // If data was already input, we define them in obj to populate input fields.
                if (GETPOST('actionadd', 'alpha')) {
                    foreach ($fieldlist as $key => $val) {
                        if (GETPOST($val)) {
                            $obj->$val = GETPOST($val);
                        }
                    }
                }

                $tmpaction = 'create';
                $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                $reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                $error = $hookmanager->error;
                $errors = $hookmanager->errors;

                if (empty($reshook)) {
                    $this->fieldListAccountModel($fieldlist, $obj, $tabname[$id], 'add');
                }

                print '<td colspan="3" class="right">';
                print '<input type="submit" class="button button-add" name="actionadd" value="' . $langs->trans("Add") . '">';
                print '</td>';
                print "</tr>";

                $colspan = count($fieldlist) + 3;

                print '<tr><td colspan="' . $colspan . '">&nbsp;</td></tr>'; // Keep &nbsp; to have a line with enough height
            }


            // List of available values in database
            dol_syslog("htdocs/admin/dict", LOG_DEBUG);
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;

                $param = '&id=' . urlencode((string) ($id));
                if ($search_country_id > 0) {
                    $param .= '&search_country_id=' . urlencode((string) ($search_country_id));
                }
                $paramwithsearch = $param;
                if ($sortorder) {
                    $paramwithsearch .= '&sortorder=' . urlencode($sortorder);
                }
                if ($sortfield) {
                    $paramwithsearch .= '&sortfield=' . urlencode($sortfield);
                }

                // There is several pages
                if ($num > $listlimit) {
                    print '<tr class="none"><td class="right" colspan="' . (3 + count($fieldlist)) . '">';
                    print_fleche_navigation($page, $_SERVER['PHP_SELF'], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>' . $langs->trans("Page") . ' ' . ($page + 1) . '</span></li>');
                    print '</td></tr>';
                }

                // Title line with search boxes
                print '<tr class="liste_titre liste_titre_add">';
                foreach ($fieldlist as $field => $value) {
                    $showfield = 1; // By default

                    if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
                        $showfield = 0;
                    }

                    if ($showfield) {
                        if ($value == 'country') {
                            print '<td class="liste_titre">';
                            print $form->select_country($search_country_id, 'search_country_id', '', 28, 'maxwidth200 maxwidthonsmartphone');
                            print '</td>';
                        } else {
                            print '<td class="liste_titre"></td>';
                        }
                    }
                }
                print '<td class="liste_titre"></td>';
                print '<td class="liste_titre right" colspan="2">';
                $searchpicto = $form->showFilterAndCheckAddButtons(0);
                print $searchpicto;
                print '</td>';
                print '</tr>';

                // Title of lines
                print '<tr class="liste_titre">';
                print getTitleFieldOfList($langs->trans("Pcg_version"), 0, $_SERVER['PHP_SELF'], "pcg_version", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
                print getTitleFieldOfList($langs->trans("Label"), 0, $_SERVER['PHP_SELF'], "label", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
                print getTitleFieldOfList($langs->trans("Country"), 0, $_SERVER['PHP_SELF'], "country_code", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
                print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER['PHP_SELF'], "active", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, 'center ');
                print getTitleFieldOfList('');
                print getTitleFieldOfList('');
                print '</tr>';

                if ($num) {
                    $i = 0;
                    // Lines with values
                    while ($i < $num) {
                        $obj = $db->fetch_object($resql);
                        //print_r($obj);

                        print '<tr class="oddeven" id="rowid-' . $obj->rowid . '">';
                        if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
                            print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
                            print '<input type="hidden" name="token" value="' . newToken() . '">';
                            print '<input type="hidden" name="page" value="' . $page . '">';
                            print '<input type="hidden" name="rowid" value="' . $rowid . '">';

                            $tmpaction = 'edit';
                            $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                            $reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                            $error = $hookmanager->error;
                            $errors = $hookmanager->errors;

                            if (empty($reshook)) {
                                $this->fieldListAccountModel($fieldlist, $obj, $tabname[$id], 'edit');
                            }

                            print '<td colspan="3" class="right">';
                            print '<a name="' . (!empty($obj->rowid) ? $obj->rowid : $obj->code) . '">&nbsp;</a><input type="submit" class="button button-edit" name="actionmodify" value="' . $langs->trans("Modify") . '">';
                            print '&nbsp;<input type="submit" class="button button-cancel" name="actioncancel" value="' . $langs->trans("Cancel") . '">';
                            print '</td>';
                        } else {
                            $tmpaction = 'view';
                            $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                            $reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

                            $error = $hookmanager->error;
                            $errors = $hookmanager->errors;

                            if (empty($reshook)) {
                                foreach ($fieldlist as $field => $value) {
                                    $showfield = 1;
                                    $class = "left";
                                    $tmpvar = $fieldlist[$field];
                                    $valuetoshow = $obj->$tmpvar;
                                    if ($value == 'type_template') {
                                        $valuetoshow = isset($elementList[$valuetoshow]) ? $elementList[$valuetoshow] : $valuetoshow;
                                    }
                                    if ($value == 'element') {
                                        $valuetoshow = isset($elementList[$valuetoshow]) ? $elementList[$valuetoshow] : $valuetoshow;
                                    } elseif ($value == 'source') {
                                        $valuetoshow = isset($sourceList[$valuetoshow]) ? $sourceList[$valuetoshow] : $valuetoshow;
                                    } elseif ($valuetoshow == 'all') {
                                        $valuetoshow = $langs->trans('All');
                                    } elseif ($fieldlist[$field] == 'country') {
                                        if (empty($obj->country_code)) {
                                            $valuetoshow = '-';
                                        } else {
                                            $key = $langs->trans("Country" . strtoupper($obj->country_code));
                                            $valuetoshow = ($key != "Country" . strtoupper($obj->country_code) ? $obj->country_code . " - " . $key : $obj->country);
                                        }
                                    } elseif ($fieldlist[$field] == 'country_id') {
                                        $showfield = 0;
                                    }

                                    $class = 'tddict';
                                    if ($fieldlist[$field] == 'tracking') {
                                        $class .= ' tdoverflowauto';
                                    }
                                    // Show value for field
                                    if ($showfield) {
                                        print '<!-- ' . $fieldlist[$field] . ' --><td class="' . $class . '">' . $valuetoshow . '</td>';
                                    }
                                }
                            }

                            // Can an entry be erased or disabled ?
                            $iserasable = 1;
                            $canbedisabled = 1;
                            $canbemodified = 1; // true by default

                            $url = $_SERVER['PHP_SELF'] . '?token=' . newToken() . ($page ? '&page=' . $page : '') . '&sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . (!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')) . '&code=' . (!empty($obj->code) ? urlencode($obj->code) : '');
                            if ($param) {
                                $url .= '&' . $param;
                            }
                            $url .= '&';

                            // Active
                            print '<td class="center nowrap">';
                            if ($canbedisabled) {
                                print '<a href="' . $url . 'action=' . $acts[$obj->active] . '">' . $actl[$obj->active] . '</a>';
                            } else {
                                print $langs->trans("AlwaysActive");
                            }
                            print "</td>";

                            // Modify link
                            if ($canbemodified) {
                                print '<td class="center"><a class="reposition editfielda" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a></td>';
                            } else {
                                print '<td>&nbsp;</td>';
                            }

                            // Delete link
                            if ($iserasable) {
                                print '<td class="center"><a href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a></td>';
                            } else {
                                print '<td>&nbsp;</td>';
                            }

                            print "</tr>\n";
                        }

                        $i++;
                    }
                } else {
                    print '<tr><td colspan="6"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
                }
            } else {
                dol_print_error($db);
            }

            print '</table>';
            print '</div>';

            print '</form>';
        }

        print '<br>';

// End of page
        llxFooter();
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

        $form = new Form($db);
        $formaccounting = new FormAccounting($db);

        $accountsystem = new AccountancySystem($db);
        $accountsystem->fetch(getDolGlobalInt('CHARTOFACCOUNTS'));

        $title = $langs->trans('AccountAccounting') . " - " . $langs->trans('Card');

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $title, $help_url);


// Create mode
        if ($action == 'create') {
            print load_fiche_titre($langs->trans('NewAccountingAccount'));

            print '<form name="add" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="add">';

            print dol_get_fiche_head();

            print '<table class="border centpercent">';

            // Chart of account
            print '<tr><td class="titlefieldcreate"><span class="fieldrequired">' . $langs->trans("Chartofaccounts") . '</span></td>';
            print '<td>';
            print $accountsystem->ref;
            print '</td></tr>';

            // Account number
            print '<tr><td class="titlefieldcreate"><span class="fieldrequired">' . $langs->trans("AccountNumber") . '</span></td>';
            print '<td><input name="account_number" size="30" value="' . $account_number . '"></td></tr>';

            // Label
            print '<tr><td><span class="fieldrequired">' . $langs->trans("Label") . '</span></td>';
            print '<td><input name="label" size="70" value="' . $object->label . '"></td></tr>';

            // Label short
            print '<tr><td>' . $langs->trans("LabelToShow") . '</td>';
            print '<td><input name="labelshort" size="70" value="' . $object->labelshort . '"></td></tr>';

            // Account parent
            print '<tr><td>' . $langs->trans("Accountparent") . '</td>';
            print '<td>';
            print $formaccounting->select_account($object->account_parent, 'account_parent', 1, [], 0, 0, 'minwidth200');
            print '</td></tr>';

            // Chart of accounts type
            print '<tr><td>';
            print $form->textwithpicto($langs->trans("Pcgtype"), $langs->transnoentitiesnoconv("PcgtypeDesc"));
            print '</td>';
            print '<td>';
            print '<input type="text" name="pcg_type" list="pcg_type_datalist" value="' . dol_escape_htmltag(GETPOSTISSET('pcg_type') ? GETPOST('pcg_type', 'alpha') : $object->pcg_type) . '">';
            // autosuggest from existing account types if found
            print '<datalist id="pcg_type_datalist">';
            $sql = "SELECT DISTINCT pcg_type FROM " . MAIN_DB_PREFIX . "accounting_account";
            $sql .= " WHERE fk_pcg_version = '" . $db->escape($accountsystem->ref) . "'";
            $sql .= ' AND entity in (' . getEntity('accounting_account', 0) . ')';      // Always limit to current entity. No sharing in accountancy.
            $sql .= ' LIMIT 50000'; // just as a sanity check
            $resql = $db->query($sql);
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    print '<option value="' . dol_escape_htmltag($obj->pcg_type) . '">';
                }
            }
            print '</datalist>';
            print '</td></tr>';

            // Category
            print '<tr><td>';
            print $form->textwithpicto($langs->trans("AccountingCategory"), $langs->transnoentitiesnoconv("AccountingAccountGroupsDesc"));
            print '</td>';
            print '<td>';
            print $formaccounting->select_accounting_category($object->account_category, 'account_category', 1, 0, 1);
            print '</td></tr>';

            print '</table>';

            print dol_get_fiche_end();

            print '<div class="center">';
            print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
            print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input class="button button-cancel" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
            print '</div>';

            print '</form>';
        } elseif ($id > 0 || $ref) {
            $result = $object->fetch($id, $ref, 1);

            if ($result > 0) {
                $head = accounting_prepare_head($object);

                // Edit mode
                if ($action == 'update') {
                    print dol_get_fiche_head($head, 'card', $langs->trans('AccountAccounting'), 0, 'accounting_account');

                    print '<form name="update" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="edit">';
                    print '<input type="hidden" name="id" value="' . $id . '">';
                    print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';

                    print '<table class="border centpercent">';

                    // Account number
                    print '<tr><td class="titlefieldcreate"><span class="fieldrequired">' . $langs->trans("AccountNumber") . '</span></td>';
                    print '<td><input name="account_number" size="30" value="' . $object->account_number . '"</td></tr>';

                    // Label
                    print '<tr><td><span class="fieldrequired">' . $langs->trans("Label") . '</span></td>';
                    print '<td><input name="label" size="70" value="' . $object->label . '"</td></tr>';

                    // Label short
                    print '<tr><td>' . $langs->trans("LabelToShow") . '</td>';
                    print '<td><input name="labelshort" size="70" value="' . $object->labelshort . '"</td></tr>';

                    // Account parent
                    print '<tr><td>' . $langs->trans("Accountparent") . '</td>';
                    print '<td>';
                    // Note: We accept disabled account as parent account so we can build a hierarchy and use only children
                    print $formaccounting->select_account($object->account_parent, 'account_parent', 1, [], 0, 0, 'minwidth100 maxwidth300 maxwidthonsmartphone', 1, '');
                    print '</td></tr>';

                    // Chart of accounts type
                    print '<tr><td>';
                    print $form->textwithpicto($langs->trans("Pcgtype"), $langs->transnoentitiesnoconv("PcgtypeDesc"));
                    print '</td>';
                    print '<td>';
                    print '<input type="text" name="pcg_type" list="pcg_type_datalist" value="' . dol_escape_htmltag(GETPOSTISSET('pcg_type') ? GETPOST('pcg_type', 'alpha') : $object->pcg_type) . '">';
                    // autosuggest from existing account types if found
                    print '<datalist id="pcg_type_datalist">';
                    $sql = 'SELECT DISTINCT pcg_type FROM ' . MAIN_DB_PREFIX . 'accounting_account';
                    $sql .= " WHERE fk_pcg_version = '" . $db->escape($accountsystem->ref) . "'";
                    $sql .= ' AND entity in (' . getEntity('accounting_account', 0) . ')';      // Always limit to current entity. No sharing in accountancy.
                    $sql .= ' LIMIT 50000'; // just as a sanity check
                    $resql = $db->query($sql);
                    if ($resql) {
                        while ($obj = $db->fetch_object($resql)) {
                            print '<option value="' . dol_escape_htmltag($obj->pcg_type) . '">';
                        }
                    }
                    print '</datalist>';
                    print '</td></tr>';

                    // Category
                    print '<tr><td>';
                    print $form->textwithpicto($langs->trans("AccountingCategory"), $langs->transnoentitiesnoconv("AccountingAccountGroupsDesc"));
                    print '</td>';
                    print '<td>';
                    print $formaccounting->select_accounting_category($object->account_category, 'account_category', 1);
                    print '</td></tr>';

                    print '</table>';

                    print dol_get_fiche_end();

                    print $form->buttonsSaveCancel();

                    print '</form>';
                } else {
                    // View mode
                    $linkback = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/account.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

                    print dol_get_fiche_head($head, 'card', $langs->trans('AccountAccounting'), -1, 'accounting_account');

                    dol_banner_tab($object, 'ref', $linkback, 1, 'account_number', 'ref');


                    print '<div class="fichecenter">';
                    print '<div class="underbanner clearboth"></div>';

                    print '<table class="border centpercent tableforfield">';

                    // Label
                    print '<tr><td class="titlefield">' . $langs->trans("Label") . '</td>';
                    print '<td colspan="2">' . $object->label . '</td></tr>';

                    // Label to show
                    print '<tr><td class="titlefield">' . $langs->trans("LabelToShow") . '</td>';
                    print '<td colspan="2">' . $object->labelshort . '</td></tr>';

                    // Account parent
                    $accp = new AccountingAccount($db);
                    if (!empty($object->account_parent)) {
                        $accp->fetch($object->account_parent, '');
                    }
                    print '<tr><td>' . $langs->trans("Accountparent") . '</td>';
                    print '<td colspan="2">' . $accp->account_number . ' - ' . $accp->label . '</td></tr>';

                    // Group of accounting account
                    print '<tr><td>';
                    print $form->textwithpicto($langs->trans("Pcgtype"), $langs->transnoentitiesnoconv("PcgtypeDesc"));
                    print '</td>';
                    print '<td colspan="2">' . $object->pcg_type . '</td></tr>';

                    // Custom group of accounting account
                    print "<tr><td>";
                    print $form->textwithpicto($langs->trans("AccountingCategory"), $langs->transnoentitiesnoconv("AccountingAccountGroupsDesc"));
                    print "</td><td colspan='2'>" . $object->account_category_label . "</td>";

                    print '</table>';

                    print '</div>';

                    print dol_get_fiche_end();

                    /*
                     * Actions buttons
                     */
                    print '<div class="tabsAction">';

                    if ($user->hasRight('accounting', 'chartofaccount')) {
                        print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=update&token=' . newToken() . '&id=' . $object->id . '">' . $langs->trans('Modify') . '</a>';
                    } else {
                        print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('Modify') . '</a>';
                    }

                    // Delete
                    $permissiontodelete = $user->hasRight('accounting', 'chartofaccount');
                    print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete&token=' . newToken(), 'delete', $permissiontodelete);

                    print '</div>';
                }
            } else {
                dol_print_error($db, $object->error, $object->errors);
            }
        }

// End of page
        llxFooter();
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

        $form = new Form($db);
        $formaccounting = new FormAccounting($db);

        $title = $langs->trans('AccountingCategory');
        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $title, $help_url);

        $linkback = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/categories_list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
        $titlepicto = 'setup';

        print load_fiche_titre($langs->trans('AccountingCategory'), $linkback, $titlepicto);

        print '<form name="add" action="' . $_SERVER['PHP_SELF'] . '" method="POST">' . "\n";
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="display">';

        print dol_get_fiche_head();

        print '<table class="border centpercent">';

// Select the category
        print '<tr><td class="titlefield">' . $langs->trans("AccountingCategory") . '</td>';
        print '<td>';
        $s = $formaccounting->select_accounting_category($cat_id, 'account_category', 1, 0, 0, 0);
        if ($formaccounting->nbaccounts_category <= 0) {
            print '<span class="opacitymedium">' . $s . '</span>';
        } else {
            print $s;
            print '<input type="submit" class="button small" value="' . $langs->trans("Select") . '">';
        }
        print '</td></tr>';

        print '</table>';

        print dol_get_fiche_end();


// Select the accounts
        if (!empty($cat_id)) {
            $return = $accountingcategory->getAccountsWithNoCategory($cat_id);
            if ($return < 0) {
                setEventMessages(null, $accountingcategory->errors, 'errors');
            }
            print '<br>';

            $arraykeyvalue = [];
            foreach ($accountingcategory->lines_cptbk as $key => $val) {
                $doc_ref = !empty($val->doc_ref) ? $val->doc_ref : '';
                $arraykeyvalue[length_accountg($val->numero_compte)] = length_accountg($val->numero_compte) . ' - ' . $val->label_compte . ($doc_ref ? ' ' . $doc_ref : '');
            }

            if (is_array($accountingcategory->lines_cptbk) && count($accountingcategory->lines_cptbk) > 0) {
                print img_picto($langs->trans("AccountingAccount"), 'accounting_account', 'class="pictofixedwith"');
                print $form->multiselectarray('cpt_bk', $arraykeyvalue, GETPOST('cpt_bk', 'array'), 0, 0, '', 0, "80%", '', '', $langs->transnoentitiesnoconv("AddAccountFromBookKeepingWithNoCategories"));
                print '<input type="submit" class="button button-add small" id="" class="action-delete" value="' . $langs->trans("Add") . '"> ';
            }
        }

        print '</form>';


        if ((empty($action) || $action == 'display' || $action == 'delete') && $cat_id > 0) {
            $param = 'account_category=' . ((int) $cat_id);

            print '<br>';
            print '<table class="noborder centpercent">' . "\n";
            print '<tr class="liste_titre">';
            print getTitleFieldOfList('AccountAccounting', 0, $_SERVER['PHP_SELF'], 'account_number', '', $param, '', $sortfield, $sortorder, '') . "\n";
            print getTitleFieldOfList('Label', 0, $_SERVER['PHP_SELF'], 'label', '', $param, '', $sortfield, $sortorder, '') . "\n";
            print getTitleFieldOfList('', 0, $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, '') . "\n";
            print '</tr>' . "\n";

            if (!empty($cat_id)) {
                $return = $accountingcategory->display($cat_id); // This load ->lines_display
                if ($return < 0) {
                    setEventMessages(null, $accountingcategory->errors, 'errors');
                }

                if (is_array($accountingcategory->lines_display) && count($accountingcategory->lines_display) > 0) {
                    $accountingcategory->lines_display = dol_sort_array($accountingcategory->lines_display, $sortfield, $sortorder, -1, 0, 1);

                    foreach ($accountingcategory->lines_display as $cpt) {
                        print '<tr class="oddeven">';
                        print '<td>' . length_accountg($cpt->account_number) . '</td>';
                        print '<td>' . $cpt->label . '</td>';
                        print '<td class="right">';
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=delete&token=' . newToken() . '&account_category=' . $cat_id . '&cptid=' . $cpt->rowid . '">';
                        print $langs->trans("DeleteFromCat");
                        print img_picto($langs->trans("DeleteFromCat"), 'unlink', 'class="paddingleft"');
                        print "</a>";
                        print "</td>";
                        print "</tr>\n";
                    }
                } else {
                    print '<tr><td colspan="3"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
                }
            }

            print "</table>";
        }

// End of page
        llxFooter();
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

        $form = new Form($db);
        $formadmin = new FormAdmin($db);

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $langs->trans('DictionaryAccountancyCategory'), $help_url);

        $titre = $langs->trans($tablib[$id]);
        $linkback = '';
        $titlepicto = 'setup';

        print load_fiche_titre($titre, $linkback, $titlepicto);

        print '<span class="opacitymedium">' . $langs->trans("AccountingAccountGroupsDesc", $langs->transnoentitiesnoconv("ByPersonalizedAccountGroups")) . '</span><br><br>';

// Confirmation of the deletion of the line
        if ($action == 'delete') {
            print $form->formconfirm($_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . $rowid . '&code=' . $code . '&id=' . $id . ($search_country_id > 0 ? '&search_country_id=' . $search_country_id : ''), $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
        }

// Complete search query with sorting criteria
        $sql = $tabsql[$id];

        if ($search_country_id > 0) {
            if (preg_match('/ WHERE /', $sql)) {
                $sql .= " AND ";
            } else {
                $sql .= " WHERE ";
            }
            $sql .= " (a.fk_country = " . ((int) $search_country_id) . " OR a.fk_country = 0)";
        }

// If sort order is "country", we use country_code instead
        if ($sortfield == 'country') {
            $sortfield = 'country_code';
        }
        if (empty($sortfield)) {
            $sortfield = 'position';
        }

        $sql .= $db->order($sortfield, $sortorder);
        $sql .= $db->plimit($listlimit + 1, $offset);


        $fieldlist = explode(',', $tabfield[$id]);

        $param = '&id=' . $id;
        if ($search_country_id > 0) {
            $param .= '&search_country_id=' . urlencode((string) ($search_country_id));
        }
        $paramwithsearch = $param;
        if ($sortorder) {
            $paramwithsearch .= '&sortorder=' . urlencode($sortorder);
        }
        if ($sortfield) {
            $paramwithsearch .= '&sortfield=' . urlencode($sortfield);
        }
        if (GETPOST('from', 'alpha')) {
            $paramwithsearch .= '&from=' . urlencode(GETPOST('from', 'alpha'));
        }
        if ($listlimit) {
            $paramwithsearch .= '&listlimit=' . urlencode((string) (GETPOSTINT('listlimit')));
        }
        print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="from" value="' . dol_escape_htmltag(GETPOST('from', 'alpha')) . '">';
        print '<input type="hidden" name="sortfield" value="' . dol_escape_htmltag($sortfield) . '">';
        print '<input type="hidden" name="sortorder" value="' . dol_escape_htmltag($sortorder) . '">';


        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';

// Form to add a new line
        if ($tabname[$id]) {
            $fieldlist = explode(',', $tabfield[$id]);

            // Line for title
            print '<tr class="liste_titre">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td></td>';
            }
            foreach ($fieldlist as $field => $value) {
                // Determine le nom du champ par rapport aux noms possibles
                // dans les dictionnaires de donnees
                $valuetoshow = ucfirst($fieldlist[$field]); // By default
                $valuetoshow = $langs->trans($valuetoshow); // try to translate
                $class = "left";
                if ($fieldlist[$field] == 'type') {
                    if ($tabname[$id] == MAIN_DB_PREFIX . "c_paiement") {
                        $valuetoshow = $form->textwithtooltip($langs->trans("Type"), $langs->trans("TypePaymentDesc"), 2, 1, img_help(1, ''));
                    } else {
                        $valuetoshow = $langs->trans("Type");
                    }
                }
                if ($fieldlist[$field] == 'code') {
                    $valuetoshow = $langs->trans("Code");
                    $class = 'width75';
                }
                if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
                    $valuetoshow = $langs->trans("Label");
                }
                if ($fieldlist[$field] == 'libelle_facture') {
                    $valuetoshow = $langs->trans("LabelOnDocuments");
                }
                if ($fieldlist[$field] == 'country') {
                    $valuetoshow = $langs->trans("Country");
                }
                if ($fieldlist[$field] == 'accountancy_code') {
                    $valuetoshow = $langs->trans("AccountancyCode");
                }
                if ($fieldlist[$field] == 'accountancy_code_sell') {
                    $valuetoshow = $langs->trans("AccountancyCodeSell");
                }
                if ($fieldlist[$field] == 'accountancy_code_buy') {
                    $valuetoshow = $langs->trans("AccountancyCodeBuy");
                }
                if ($fieldlist[$field] == 'pcg_version' || $fieldlist[$field] == 'fk_pcg_version') {
                    $valuetoshow = $langs->trans("Pcg_version");
                }
                if ($fieldlist[$field] == 'range_account') {
                    $valuetoshow = $langs->trans("Comment");
                    $class = 'width75';
                }
                if ($fieldlist[$field] == 'category_type') {
                    $valuetoshow = $langs->trans("Calculated");
                }

                if ($valuetoshow != '') {
                    print '<td class="' . $class . '">';
                    if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
                        print '<a href="' . $tabhelp[$id][$value] . '">' . $valuetoshow . ' ' . img_help(1, $valuetoshow) . '</a>';
                    } elseif (!empty($tabhelp[$id][$value])) {
                        print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
                    } else {
                        print $valuetoshow;
                    }
                    print '</td>';
                }
            }

            print '<td>';
            print '<input type="hidden" name="id" value="' . $id . '">';
            print '</td>';
            print '<td></td>';
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td></td>';
            }
            print '</tr>';

            // Line to enter new values
            print '<tr class="oddeven nodrag nodrop nohover">';

            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td></td>';
            }

            $obj = new stdClass();
            // If data was already input, we define them in obj to populate input fields.
            if (GETPOST('actionadd', 'alpha')) {
                foreach ($fieldlist as $key => $val) {
                    if (GETPOST($val) != '') {
                        $obj->$val = GETPOST($val);
                    }
                }
            }

            $tmpaction = 'create';
            $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
            $reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
            $error = $hookmanager->error;
            $errors = $hookmanager->errors;

            if (empty($reshook)) {
                fieldListAccountingCategories($fieldlist, $obj, $tabname[$id], 'add');
            }

            print '<td colspan="2" class="right">';
            print '<input type="submit" class="button button-add" name="actionadd" value="' . $langs->trans("Add") . '">';
            print '</td>';

            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td></td>';
            }

            print "</tr>";

            $colspan = count($fieldlist) + 3;
            if ($id == 32) {
                $colspan++;
            }
        }

        print '</table>';
        print '</div>';

        print '<div class="div-table-responsive">';
        print '<table class="noborder centpercent">';

// List of available record in database
        dol_syslog("htdocs/accountancy/admin/categories_list.php", LOG_DEBUG);

        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            // There is several pages
            if ($num > $listlimit) {
                print '<tr class="none"><td class="right" colspan="' . (2 + count($fieldlist)) . '">';
                print_fleche_navigation($page, $_SERVER['PHP_SELF'], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>' . $langs->trans("Page") . ' ' . ($page + 1) . '</span></li>');
                print '</td></tr>';
            }

            $filterfound = 0;
            foreach ($fieldlist as $field => $value) {
                $showfield = 1; // By default
                if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
                    $showfield = 0;
                }
                if ($showfield) {
                    if ($value == 'country') {
                        $filterfound++;
                    }
                }
            }

            // Title line with search boxes
            print '<tr class="liste_titre liste_titre_add liste_titre_filter">';

            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre center">';
                if ($filterfound) {
                    $searchpicto = $form->showFilterAndCheckAddButtons(0);
                    print $searchpicto;
                }
                print '</td>';
            }

            $filterfound = 0;
            foreach ($fieldlist as $field => $value) {
                $showfield = 1; // By default

                if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
                    $showfield = 0;
                }

                if ($showfield) {
                    if ($value == 'country') {
                        print '<td class="liste_titre">';
                        print $form->select_country($search_country_id, 'search_country_id', '', 28, 'maxwidth150 maxwidthonsmartphone');
                        print '</td>';
                        $filterfound++;
                    } else {
                        print '<td class="liste_titre"></td>';
                    }
                }
            }
            print '<td class="liste_titre"></td>';
            print '<td class="liste_titre"></td>';
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre center">';
                if ($filterfound) {
                    $searchpicto = $form->showFilterAndCheckAddButtons(0);
                    print $searchpicto;
                }
                print '</td>';
            }
            print '</tr>';

            // Title of lines
            print '<tr class="liste_titre">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print getTitleFieldOfList('');
            }
            foreach ($fieldlist as $field => $value) {
                // Determines the name of the field in relation to the possible names
                // in data dictionaries
                $showfield = 1; // By default
                $class = "left";
                $sortable = 1;
                $valuetoshow = '';

                $valuetoshow = ucfirst($fieldlist[$field]); // By default
                $valuetoshow = $langs->trans($valuetoshow); // try to translate
                if ($fieldlist[$field] == 'source') {
                    $valuetoshow = $langs->trans("Contact");
                }
                if ($fieldlist[$field] == 'price') {
                    $valuetoshow = $langs->trans("PriceUHT");
                }
                if ($fieldlist[$field] == 'taux') {
                    if ($tabname[$id] != MAIN_DB_PREFIX . "c_revenuestamp") {
                        $valuetoshow = $langs->trans("Rate");
                    } else {
                        $valuetoshow = $langs->trans("Amount");
                    }
                    $class = 'center';
                }
                if ($fieldlist[$field] == 'type') {
                    $valuetoshow = $langs->trans("Type");
                }
                if ($fieldlist[$field] == 'code') {
                    $valuetoshow = $langs->trans("Code");
                }
                if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
                    $valuetoshow = $langs->trans("Label");
                }
                if ($fieldlist[$field] == 'country') {
                    $valuetoshow = $langs->trans("Country");
                }
                if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
                    $showfield = 0;
                }
                if ($fieldlist[$field] == 'accountancy_code') {
                    $valuetoshow = $langs->trans("AccountancyCode");
                }
                if ($fieldlist[$field] == 'accountancy_code_sell') {
                    $valuetoshow = $langs->trans("AccountancyCodeSell");
                    $sortable = 0;
                }
                if ($fieldlist[$field] == 'accountancy_code_buy') {
                    $valuetoshow = $langs->trans("AccountancyCodeBuy");
                    $sortable = 0;
                }
                if ($fieldlist[$field] == 'fk_pcg_version') {
                    $valuetoshow = $langs->trans("Pcg_version");
                }
                if ($fieldlist[$field] == 'account_parent') {
                    $valuetoshow = $langs->trans("Accountsparent");
                }
                if ($fieldlist[$field] == 'pcg_type') {
                    $valuetoshow = $langs->trans("Pcg_type");
                }
                if ($fieldlist[$field] == 'type_template') {
                    $valuetoshow = $langs->trans("TypeOfTemplate");
                }
                if ($fieldlist[$field] == 'range_account') {
                    $valuetoshow = $langs->trans("Comment");
                }
                if ($fieldlist[$field] == 'category_type') {
                    $valuetoshow = $langs->trans("Calculated");
                }
                // Affiche nom du champ
                if ($showfield) {
                    print getTitleFieldOfList($valuetoshow, 0, $_SERVER['PHP_SELF'], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page=' . $page . '&' : ''), $param, "", $sortfield, $sortorder, $class . ' ');
                }
            }
            print getTitleFieldOfList($langs->trans("ListOfAccounts"), 0, $_SERVER['PHP_SELF'], "", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, '');
            print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER['PHP_SELF'], "active", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, 'center ');
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print getTitleFieldOfList('');
            }
            print '</tr>';


            if ($num) {
                $imaxinloop = ($listlimit ? min($num, $listlimit) : $num);

                // Lines with values
                while ($i < $imaxinloop) {
                    $obj = $db->fetch_object($resql);

                    //print_r($obj);
                    print '<tr class="oddeven" id="rowid-' . $obj->rowid . '">';
                    if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
                        $tmpaction = 'edit';
                        $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                        $reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                        $error = $hookmanager->error;
                        $errors = $hookmanager->errors;

                        // Actions
                        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            print '<td></td>';
                        }

                        // Show fields
                        if (empty($reshook)) {
                            fieldListAccountingCategories($fieldlist, $obj, $tabname[$id], 'edit');
                        }

                        print '<td></td>';
                        print '<td class="center">';
                        print '<div name="' . (!empty($obj->rowid) ? $obj->rowid : $obj->code) . '"></div>';
                        print '<input type="hidden" name="page" value="' . $page . '">';
                        print '<input type="hidden" name="rowid" value="' . $rowid . '">';
                        print '<input type="submit" class="button button-edit smallpaddingimp" name="actionmodify" value="' . $langs->trans("Modify") . '">';
                        print '<input type="submit" class="button button-cancel smallpaddingimp" name="actioncancel" value="' . $langs->trans("Cancel") . '">';
                        print '</td>';
                        // Actions
                        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            print '<td></td>';
                        }
                    } else {
                        // Can an entry be erased or disabled ?
                        $iserasable = 1;
                        $canbedisabled = 1;
                        $canbemodified = 1; // true by default
                        if (isset($obj->code)) {
                            if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i', $obj->code))) {
                                $iserasable = 0;
                                $canbedisabled = 0;
                            }
                        }
                        $url = $_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . (!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')) . '&code=' . (!empty($obj->code) ? urlencode($obj->code) : '');
                        if ($param) {
                            $url .= '&' . $param;
                        }
                        $url .= '&';

                        $canbemodified = $iserasable;

                        $tmpaction = 'view';
                        $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                        $reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

                        $error = $hookmanager->error;
                        $errors = $hookmanager->errors;

                        // Actions
                        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            print '<td class="center">';
                            if ($canbemodified) {
                                print '<a class="reposition editfielda marginleftonly marginrightonly" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a>';
                            }
                            if ($iserasable) {
                                if ($user->admin) {
                                    print '<a class="marginleftonly marginrightonly" href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a>';
                                }
                            }
                            print '</td>';
                        }

                        if (empty($reshook)) {
                            foreach ($fieldlist as $field => $value) {
                                $showfield = 1;
                                $title = '';
                                $class = 'tddict';

                                $tmpvar = $fieldlist[$field];
                                $valuetoshow = $obj->$tmpvar;
                                if ($value == 'category_type') {
                                    $valuetoshow = yn($valuetoshow);
                                } elseif ($valuetoshow == 'all') {
                                    $valuetoshow = $langs->trans('All');
                                } elseif ($fieldlist[$field] == 'country') {
                                    if (empty($obj->country_code)) {
                                        $valuetoshow = '-';
                                    } else {
                                        $key = $langs->trans("Country" . strtoupper($obj->country_code));
                                        $valuetoshow = ($key != "Country" . strtoupper($obj->country_code) ? $obj->country_code . " - " . $key : $obj->country);
                                    }
                                } elseif (in_array($fieldlist[$field], ['label', 'formula'])) {
                                    $class = "tdoverflowmax250";
                                    $title = $valuetoshow;
                                } elseif (in_array($fieldlist[$field], ['range_account'])) {
                                    $class = "tdoverflowmax250 small";
                                    $title = $valuetoshow;
                                } elseif ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
                                    $showfield = 0;
                                }

                                // Show value for field
                                if ($showfield) {
                                    print '<!-- ' . $fieldlist[$field] . ' --><td class="' . $class . '"' . ($title ? ' title="' . dol_escape_htmltag($title) . '"' : '') . '>' . dol_escape_htmltag($valuetoshow) . '</td>';
                                }
                            }
                        }

                        // Link to setup the group
                        print '<td>';
                        if (empty($obj->formula)) {
                            // Count number of accounts into group
                            $nbofaccountintogroup = 0;
                            $listofaccountintogroup = $accountingcategory->getCptsCat($obj->rowid);
                            $nbofaccountintogroup = count($listofaccountintogroup);

                            print '<a href="' . DOL_URL_ROOT . '/accountancy/admin/categories.php?action=display&save_lastsearch_values=1&account_category=' . $obj->rowid . '">';
                            print $langs->trans("NAccounts", $nbofaccountintogroup);
                            print '</a>';
                        } else {
                            print '<span class="opacitymedium">' . $langs->trans("Formula") . '</span>';
                        }
                        print '</td>';

                        // Active
                        print '<td class="center" class="nowrap">';
                        if ($canbedisabled) {
                            print '<a href="' . $url . 'action=' . $acts[$obj->active] . '">' . $actl[$obj->active] . '</a>';
                        } else {
                            print $langs->trans("AlwaysActive");
                        }
                        print "</td>";

                        // Actions
                        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                            print '<td class="center">';
                            if ($canbemodified) {
                                print '<a class="reposition editfielda paddingleft marginleftonly marginrightonly paddingright" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a>';
                            }
                            if ($iserasable) {
                                if ($user->admin) {
                                    print '<a class="paddingleft marginleftonly marginrightonly paddingright" href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a>';
                                }
                            }
                            print '</td>';
                        }
                    }
                    print "</tr>\n";
                    $i++;
                }
            } else {
                $colspan = 10;
                print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("None") . '</td></tr>';
            }
        } else {
            dol_print_error($db);
        }

        print '</table>';
        print '</div>';

        print '</form>';

        print '<br>';

// End of page
        llxFooter();
        $db->close();


        /**
         *  Show fields in insert/edit mode
         *
         * @param array  $fieldlist Array of fields
         * @param Object $obj       If we show a particular record, obj is filled with record fields
         * @param string $tabname   Name of SQL table
         * @param string $context   'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we don't want it to be rendered
         *
         * @return     void
         */
        function fieldListAccountingCategories($fieldlist, $obj = null, $tabname = '', $context = '')
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

        $form = new Form($db);
        $formaccounting = new FormAccounting($db);

        $title = $langs->trans('Closure');

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $title, $help_url);

        $linkback = '';
        print load_fiche_titre($langs->trans('MenuClosureAccounts'), $linkback, 'title_accountancy');

        print '<span class="opacitymedium">' . $langs->trans("DefaultClosureDesc") . '</span><br>';
        print '<br>';

        print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="update">';

// Define main accounts for closure
        print '<table class="noborder centpercent">';

        foreach ($list_account_main as $key) {
            print '<tr class="oddeven value">';
            // Param
            $label = $langs->trans($key);
            $keydesc = $key . '_Desc';

            $htmltext = $langs->trans($keydesc);
            print '<td class="fieldrequired" width="50%">';
            print $form->textwithpicto($label, $htmltext);
            print '</td>';
            // Value
            print '<td>'; // Do not force class=right, or it align also the content of the select box
            print $formaccounting->select_account(getDolGlobalString($key), $key, 1, '', 1, 1);
            print '</td>';
            print '</tr>';
        }

// Journal
        print '<tr class="oddeven">';
        print '<td class="fieldrequired">' . $langs->trans("ACCOUNTING_CLOSURE_DEFAULT_JOURNAL") . '</td>';
        print '<td>';
        $defaultjournal = getDolGlobalString('ACCOUNTING_CLOSURE_DEFAULT_JOURNAL');
        print $formaccounting->select_journal($defaultjournal, "ACCOUNTING_CLOSURE_DEFAULT_JOURNAL", 9, 1, 0, 0);
        print '</td></tr>';

// Accounting groups used for the balance sheet account
        print '<tr class="oddeven">';
        print '<td class="fieldrequired">' . $langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT") . '</td>';
        print '<td>';
        print '<input type="text" size="100" id="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT" name="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT" value="' . dol_escape_htmltag(getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT')) . '">';
        print '</td></tr>';

// Accounting groups used for the income statement
        print '<tr class="oddeven">';
        print '<td class="fieldrequired">' . $langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT") . '</td>';
        print '<td>';
        print '<input type="text" size="100" id="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT" name="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT" value="' . dol_escape_htmltag(getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT')) . '">';
        print '</td></tr>';

        print "</table>\n";

        print '<div class="center"><input type="submit" class="button button-edit" name="button" value="' . $langs->trans('Modify') . '"></div>';

        print '</form>';

// End of page
        llxFooter();
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

        $form = new Form($db);
        $formaccounting = new FormAccounting($db);

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $langs->trans('MenuDefaultAccounts'), $help_url);

        $linkback = '';
        print load_fiche_titre($langs->trans('MenuDefaultAccounts'), $linkback, 'title_accountancy');

        print '<span class="opacitymedium">' . $langs->trans("DefaultBindingDesc") . '</span><br>';
        print '<br>';

        print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="update">';


// Define main accounts for thirdparty

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td>' . $langs->trans("ThirdParties") . ' | ' . $langs->trans("Users") . '</td><td></td></tr>';

        foreach ($list_account_main as $key) {
            print '<tr class="oddeven value">';
            // Param
            $label = $langs->trans($key);
            $keydesc = $key . '_Desc';

            $htmltext = $langs->trans($keydesc);
            print '<td class="fieldrequired">';
            if ($key == 'ACCOUNTING_ACCOUNT_CUSTOMER') {
                print img_picto('', 'company', 'class="pictofixedwidth"');
            } elseif ($key == 'ACCOUNTING_ACCOUNT_SUPPLIER') {
                print img_picto('', 'company', 'class="pictofixedwidth"');
            } else {
                print img_picto('', 'user', 'class="pictofixedwidth"');
            }
            print $form->textwithpicto($label, $htmltext);
            print '</td>';
            // Value
            print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
            $key_value = getDolGlobalString($key);
            print $formaccounting->select_account($key_value, $key, 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accountsmain');
            print '</td>';
            print '</tr>';
        }
        print "</table>\n";
        print "</div>\n";


        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';

        foreach ($list_account as $key) {
            $reg = [];
            if (preg_match('/---(.*)---/', $key, $reg)) {
                print '<tr class="liste_titre"><td>' . $langs->trans($reg[1]) . '</td><td></td></tr>';
            } else {
                print '<tr class="oddeven value">';
                // Param
                $label = $langs->trans($key);
                print '<td>';
                if (preg_match('/^ACCOUNTING_PRODUCT/', $key)) {
                    print img_picto('', 'product', 'class="pictofixedwidth"');
                } elseif (preg_match('/^ACCOUNTING_SERVICE/', $key)) {
                    print img_picto('', 'service', 'class="pictofixedwidth"');
                } elseif (preg_match('/^ACCOUNTING_VAT_PAY_ACCOUNT/', $key)) {
                    print img_picto('', 'payment_vat', 'class="pictofixedwidth"');
                } elseif (preg_match('/^ACCOUNTING_VAT/', $key)) {
                    print img_picto('', 'vat', 'class="pictofixedwidth"');
                } elseif (preg_match('/^ACCOUNTING_ACCOUNT_CUSTOMER/', $key)) {
                    print img_picto('', 'bill', 'class="pictofixedwidth"');
                } elseif (preg_match('/^LOAN_ACCOUNTING_ACCOUNT/', $key)) {
                    print img_picto('', 'loan', 'class="pictofixedwidth"');
                } elseif (preg_match('/^DONATION_ACCOUNTING/', $key)) {
                    print img_picto('', 'donation', 'class="pictofixedwidth"');
                } elseif (preg_match('/^ADHERENT_SUBSCRIPTION/', $key)) {
                    print img_picto('', 'member', 'class="pictofixedwidth"');
                } elseif (preg_match('/^ACCOUNTING_ACCOUNT_TRANSFER/', $key)) {
                    print img_picto('', 'bank_account', 'class="pictofixedwidth"');
                } elseif (preg_match('/^ACCOUNTING_ACCOUNT_SUSPENSE/', $key)) {
                    print img_picto('', 'question', 'class="pictofixedwidth"');
                }
                // Note: account for revenue stamp are store into dictionary of revenue stamp. There is no default value.
                print $label;
                print '</td>';
                // Value
                print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
                print $formaccounting->select_account(getDolGlobalString($key), $key, 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accounts');
                print '</td>';
                print '</tr>';
            }
        }


// Customer deposit account
        print '<tr class="oddeven value">';
// Param
        print '<td>';
        print img_picto('', 'bill', 'class="pictofixedwidth"') . $langs->trans('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT');
        print '</td>';
// Value
        print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
        print $formaccounting->select_account(getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT'), 'ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT', 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accounts');
        print '</td>';
        print '</tr>';

        if (isModEnabled('societe') && getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT') && getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER_DEPOSIT') != '-1') {
            print '<tr class="oddeven">';
            print '<td>' . img_picto('', 'bill', 'class="pictofixedwidth"') . $langs->trans("UseAuxiliaryAccountOnCustomerDeposit") . '</td>';
            if (getDolGlobalInt('ACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT')) {
                print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT&value=0">';
                print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
                print '</a></td>';
            } else {
                print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_CUSTOMER_USE_AUXILIARY_ON_DEPOSIT&value=1">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a></td>';
            }
            print '</tr>';
        }

// Supplier deposit account
        print '<tr class="oddeven value">';
// Param
        print '<td>';
        print img_picto('', 'supplier_invoice', 'class="pictofixedwidth"') . $langs->trans('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT');
        print '</td>';
// Value
        print '<td class="right">'; // Do not force class=right, or it align also the content of the select box
        print $formaccounting->select_account(getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT'), 'ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT', 1, '', 1, 1, 'minwidth100 maxwidth300 maxwidthonsmartphone', 'accounts');
        print '</td>';
        print '</tr>';

        if (isModEnabled('societe') && getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT') && getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER_DEPOSIT') != '-1') {
            print '<tr class="oddeven">';
            print '<td>' . img_picto('', 'supplier_invoice', 'class="pictofixedwidth"') . $langs->trans("UseAuxiliaryAccountOnSupplierDeposit") . '</td>';
            if (getDolGlobalInt('ACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT')) {
                print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT&value=0">';
                print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
                print '</a></td>';
            } else {
                print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_ACCOUNT_SUPPLIER_USE_AUXILIARY_ON_DEPOSIT&value=1">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a></td>';
            }
            print '</tr>';
        }

        print "</table>\n";
        print "</div>\n";

        print '<div class="center"><input type="submit" class="button button-edit" name="button" value="' . $langs->trans('Save') . '"></div>';

        print '</form>';

// End of page
        llxFooter();
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

        $form = new Form($db);

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
        $title = $langs->trans('ExportOptions');
        llxHeader('', $title, $help_url);

        $linkback = '';
// $linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
        print load_fiche_titre($langs->trans('ExportOptions'), $linkback, 'accountancy');


        print "\n" . '<script type="text/javascript">' . "\n";
        print 'jQuery(document).ready(function () {' . "\n";
        print '    function initfields()' . "\n";
        print '    {' . "\n";
        foreach ($listparam as $key => $param) {
            print '        if (jQuery("#ACCOUNTING_EXPORT_MODELCSV").val()=="' . $key . '")' . "\n";
            print '        {' . "\n";
            print '            //console.log("' . $param['label'] . '");' . "\n";
            if (empty($param['ACCOUNTING_EXPORT_FORMAT'])) {
                print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("' . getDolGlobalString('ACCOUNTING_EXPORT_FORMAT') . '");' . "\n";
                print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").prop("disabled", true);' . "\n";
            } else {
                print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").val("' . $param['ACCOUNTING_EXPORT_FORMAT'] . '");' . "\n";
                print '            jQuery("#ACCOUNTING_EXPORT_FORMAT").removeAttr("disabled");' . "\n";
            }
            if (empty($param['ACCOUNTING_EXPORT_SEPARATORCSV'])) {
                print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("");' . "\n";
                print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").prop("disabled", true);' . "\n";
            } else {
                print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").val("' . getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV') . '");' . "\n";
                print '            jQuery("#ACCOUNTING_EXPORT_SEPARATORCSV").removeAttr("disabled");' . "\n";
            }
            if (empty($param['ACCOUNTING_EXPORT_ENDLINE'])) {
                print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").prop("disabled", true);' . "\n";
            } else {
                print '            jQuery("#ACCOUNTING_EXPORT_ENDLINE").removeAttr("disabled");' . "\n";
            }
            if (empty($param['ACCOUNTING_EXPORT_DATE'])) {
                print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("");' . "\n";
                print '            jQuery("#ACCOUNTING_EXPORT_DATE").prop("disabled", true);' . "\n";
            } else {
                print '            jQuery("#ACCOUNTING_EXPORT_DATE").val("' . getDolGlobalString('ACCOUNTING_EXPORT_DATE') . '");' . "\n";
                print '            jQuery("#ACCOUNTING_EXPORT_DATE").removeAttr("disabled");' . "\n";
            }
            print '        }' . "\n";
        }
        print '    }' . "\n";
        print '    initfields();' . "\n";
        print '    jQuery("#ACCOUNTING_EXPORT_MODELCSV").change(function() {' . "\n";
        print '        initfields();' . "\n";
        print '    });' . "\n";
        print '})' . "\n";
        print '</script>' . "\n";

        print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="update">';

        /*
         * Main Options
         */

        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td colspan="3">' . $langs->trans('Options') . '</td>';
        print "</tr>\n";

        $num = count($main_option);
        if ($num) {
            foreach ($main_option as $key) {
                print '<tr class="oddeven value">';

                // Param
                $label = $langs->trans($key);
                print '<td width="50%">' . $label . '</td>';

                // Value
                print '<td>';
                print '<input type="text" size="20" id="' . $key . '" name="' . $key . '" value="' . getDolGlobalString($key) . '">';
                print '</td></tr>';
            }
        }

        print "</table>\n";

        print "<br>\n";

        /*
         * Export model
         */
        print '<table class="noborder centpercent">';

        print '<tr class="liste_titre">';
        print '<td colspan="2">' . $langs->trans("Modelcsv") . '</td>';
        print '</tr>';


        print '<tr class="oddeven">';
        print '<td width="50%">' . $langs->trans("Selectmodelcsv") . '</td>';
        if (!$conf->use_javascript_ajax) {
            print '<td class="nowrap">';
            print $langs->trans("NotAvailableWhenAjaxDisabled");
            print "</td>";
        } else {
            print '<td>';
            $listmodelcsv = $accountancyexport->getType();
            print $form->selectarray("ACCOUNTING_EXPORT_MODELCSV", $listmodelcsv, getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV'), 0, 0, 0, '', 0, 0, 0, '', '', 1);

            print '</td>';
        }
        print "</td></tr>";
        print "</table>";

        print "<br>\n";

        /*
         *  Parameters
         */

        $num2 = count($model_option);
        if ($num2) {
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<td colspan="3">' . $langs->trans('OtherOptions') . '</td>';
            print "</tr>\n";

            foreach ($model_option as $key) {
                print '<tr class="oddeven value">';

                // Param
                $label = $key['label'];
                print '<td width="50%">' . $langs->trans($label) . '</td>';

                // Value
                print '<td>';
                if (is_array($key['param'])) {
                    print $form->selectarray($label, $key['param'], getDolGlobalString($label), 0);
                } else {
                    print '<input type="text" size="20" id="' . $label . '" name="' . $key['label'] . '" value="' . getDolGlobalString($label) . '">';
                }

                print '</td></tr>';
            }

            print "</table>\n";
        }

        print '<div class="center"><input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Modify')) . '" name="button"></div>';

        print '</form>';

// End of page
        llxFooter();
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

        $max = 100;

        $form = new Form($db);
        $fiscalyearstatic = new Fiscalyear($db);

        $title = $langs->trans('AccountingPeriods');

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $title, $help_url);

        $sql = "SELECT f.rowid, f.label, f.date_start, f.date_end, f.statut as status, f.entity";
        $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_fiscalyear as f";
        $sql .= " WHERE f.entity = " . $conf->entity;
        $sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            $result = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($result);
            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
        }

        $sql .= $db->plimit($limit + 1, $offset);

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $param = '';

            $parameters = ['param' => $param];
            $reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            if ($reshook < 0) {
                setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            }

            $newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

            if (empty($reshook)) {
                $newcardbutton .= dolGetButtonTitle($langs->trans('NewFiscalYear'), '', 'fa fa-plus-circle', 'fiscalyear_card.php?action=create', '', $user->hasRight('accounting', 'fiscalyear', 'write'));
            }

            $title = $langs->trans('AccountingPeriods');
            print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'calendar', 0, $newcardbutton, '', $limit, 1);

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste centpercent">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Ref") . '</td>';
            print '<td>' . $langs->trans("Label") . '</td>';
            print '<td>' . $langs->trans("DateStart") . '</td>';
            print '<td>' . $langs->trans("DateEnd") . '</td>';
            print '<td class="center">' . $langs->trans("NumberOfAccountancyEntries") . '</td>';
            print '<td class="center">' . $langs->trans("NumberOfAccountancyMovements") . '</td>';
            print '<td class="right">' . $langs->trans("Status") . '</td>';
            print '</tr>';

            // Loop on record
            // --------------------------------------------------------------------
            $i = 0;
            if ($num) {
                while ($i < $num && $i < $max) {
                    $obj = $db->fetch_object($result);

                    $fiscalyearstatic->ref = $obj->rowid;
                    $fiscalyearstatic->id = $obj->rowid;
                    $fiscalyearstatic->date_start = $obj->date_start;
                    $fiscalyearstatic->date_end = $obj->date_end;
                    $fiscalyearstatic->statut = $obj->status;
                    $fiscalyearstatic->status = $obj->status;

                    print '<tr class="oddeven">';
                    print '<td>';
                    print $fiscalyearstatic->getNomUrl(1);
                    print '</td>';
                    print '<td class="left">' . $obj->label . '</td>';
                    print '<td class="left">' . dol_print_date($db->jdate($obj->date_start), 'day') . '</td>';
                    print '<td class="left">' . dol_print_date($db->jdate($obj->date_end), 'day') . '</td>';
                    print '<td class="center">' . $object->getAccountancyEntriesByFiscalYear($obj->date_start, $obj->date_end) . '</td>';
                    print '<td class="center">' . $object->getAccountancyMovementsByFiscalYear($obj->date_start, $obj->date_end) . '</td>';
                    print '<td class="right">' . $fiscalyearstatic->LibStatut($obj->status, 5) . '</td>';
                    print '</tr>';
                    $i++;
                }
            } else {
                print '<tr class="oddeven"><td colspan="7"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }
            print '</table>';
            print '</div>';
        } else {
            dol_print_error($db);
        }

// End of page
        llxFooter();
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

        $form = new Form($db);

        $title = $langs->trans("Fiscalyear") . " - " . $langs->trans("Card");
        if ($action == 'create') {
            $title = $langs->trans("NewFiscalYear");
        }

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $title, $help_url);

        if ($action == 'create') {
            print load_fiche_titre($title, '', 'object_' . $object->picto);

            print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="add">';

            print dol_get_fiche_head();

            print '<table class="border centpercent">';

            // Label
            print '<tr><td class="titlefieldcreate fieldrequired">' . $langs->trans("Label") . '</td><td><input name="label" size="32" value="' . GETPOST('label', 'alpha') . '"></td></tr>';

            // Date start
            print '<tr><td class="fieldrequired">' . $langs->trans("DateStart") . '</td><td>';
            print $form->selectDate(($date_start ? $date_start : ''), 'fiscalyear');
            print '</td></tr>';

            // Date end
            print '<tr><td class="fieldrequired">' . $langs->trans("DateEnd") . '</td><td>';
            print $form->selectDate(($date_end ? $date_end : -1), 'fiscalyearend');
            print '</td></tr>';

            /*
            // Status
            print '<tr>';
            print '<td class="fieldrequired">' . $langs->trans("Status") . '</td>';
            print '<td class="valeur">';
            print $form->selectarray('status', $status2label, GETPOST('status', 'int'));
            print '</td></tr>';
            */

            print '</table>';

            print dol_get_fiche_end();

            print $form->buttonsSaveCancel("Create");

            print '</form>';
        }


// Part to edit record
        if (($id || $ref) && $action == 'edit') {
            print load_fiche_titre($langs->trans("Fiscalyear"), '', 'object_' . $object->picto);

            print '<form method="POST" name="update" action="' . $_SERVER['PHP_SELF'] . '">' . "\n";
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

            // Ref
            print "<tr>";
            print '<td class="titlefieldcreate titlefield">' . $langs->trans("Ref") . '</td><td>';
            print $object->ref;
            print '</td></tr>';

            // Label
            print '<tr><td class="fieldrequired">' . $langs->trans("Label") . '</td><td>';
            print '<input name="label" class="flat" size="32" value="' . $object->label . '">';
            print '</td></tr>';

            // Date start
            print '<tr><td class="fieldrequired">' . $langs->trans("DateStart") . '</td><td>';
            print $form->selectDate($object->date_start ? $object->date_start : -1, 'fiscalyear');
            print '</td></tr>';

            // Date end
            print '<tr><td class="fieldrequired">' . $langs->trans("DateEnd") . '</td><td>';
            print $form->selectDate($object->date_end ? $object->date_end : -1, 'fiscalyearend');
            print '</td></tr>';

            // Status
            print '<tr><td>' . $langs->trans("Status") . '</td><td>';
            print $object->getLibStatut(4);
            print '</td></tr>';

            print '</table>';

            print dol_get_fiche_end();

            print $form->buttonsSaveCancel();

            print '</form>';
        }

// Part to show record
        if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
            $head = fiscalyear_prepare_head($object);

            print dol_get_fiche_head($head, 'card', $langs->trans("Fiscalyear"), 0, 'calendar');

            $formconfirm = '';

            // Confirmation to delete
            if ($action == 'delete') {
                $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . "?id=" . $object->id, $langs->trans("DeleteFiscalYear"), $langs->trans("ConfirmDeleteFiscalYear"), "confirm_delete", '', 0, 1);
            }

            // Print form confirm
            print $formconfirm;

            // Object card
            // ------------------------------------------------------------
            $linkback = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/fiscalyear.php">' . $langs->trans("BackToList") . '</a>';

            print '<table class="border centpercent">';

            // Ref
            print '<tr><td class="titlefield">' . $langs->trans("Ref") . '</td><td width="50%">';
            print $object->ref;
            print '</td><td>';
            print $linkback;
            print '</td></tr>';

            // Label
            print '<tr><td class="tdtop">';
            print $form->editfieldkey("Label", 'label', $object->label, $object, 1, 'alpha:32');
            print '</td><td colspan="2">';
            print $form->editfieldval("Label", 'label', $object->label, $object, 1, 'alpha:32');
            print "</td></tr>";

            // Date start
            print '<tr><td>';
            print $form->editfieldkey("DateStart", 'date_start', $object->date_start, $object, 1, 'datepicker');
            print '</td><td colspan="2">';
            print $form->editfieldval("DateStart", 'date_start', $object->date_start, $object, 1, 'datepicker');
            print '</td></tr>';

            // Date end
            print '<tr><td>';
            print $form->editfieldkey("DateEnd", 'date_end', $object->date_end, $object, 1, 'datepicker');
            print '</td><td colspan="2">';
            print $form->editfieldval("DateEnd", 'date_end', $object->date_end, $object, 1, 'datepicker');
            print '</td></tr>';

            // Status
            print '<tr><td>' . $langs->trans("Status") . '</td><td colspan="2">' . $object->getLibStatut(4) . '</td></tr>';

            print "</table>";

            print dol_get_fiche_end();

            /*
             * Action bar
             */
            if ($user->hasRight('accounting', 'fiscalyear', 'write')) {
                print '<div class="tabsAction">';

                print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=edit&token=' . newToken() . '&id=' . $id . '">' . $langs->trans('Modify') . '</a>';

                //print dolGetButtonAction($langs->trans("Delete"), '', 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken(), 'delete', $permissiontodelete);

                print '</div>';
            }
        }

// End of page
        llxFooter();
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


// View

        $title = $langs->trans("Fiscalyear") . " - " . $langs->trans("Info");

        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        llxHeader('', $title, $help_url);

        if ($id) {
            $object = new Fiscalyear($db);
            $object->fetch($id);
            $object->info($id);

            $head = fiscalyear_prepare_head($object);

            print dol_get_fiche_head($head, 'info', $langs->trans("Fiscalyear"), 0, 'calendar');

            print '<table width="100%"><tr><td>';
            dol_print_object_info($object);
            print '</td></tr></table>';

            print '</div>';
        }

// End of page
        llxFooter();
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/admin/index.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Setup page to configure accounting expert module
     */
    public function index()
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

        $form = new Form($db);

        $title = $langs->trans('ConfigAccountingExpert');
        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
        llxHeader('', $title, $help_url);


        $linkback = '';
//$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
        print load_fiche_titre($title, $linkback, 'accountancy');

        print '<br>';

// Show message if accountancy hidden options are activated to help to resolve some problems
        if (!$user->admin) {
            if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
                print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
            }
            if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
                print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
            }
            if (getDolGlobalString('ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY')) {
                print '<div class="info">' . $langs->trans("ConstantIsOn", "ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY") . '</div>';
            }
            if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
                print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_COMPANY_PERENTITY_SHARED") . '</div>';
            }
            if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
                print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_PRODUCT_PERENTITY_SHARED") . '</div>';
            }
        }

        print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="update">';

// Params
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td colspan="2">' . $langs->trans('Options') . '</td>';
        print "</tr>\n";

// TO DO Mutualize code for yes/no constants

        /* Set this option as a hidden option but keep it for some needs.
        print '<tr>';
        print '<td>'.$langs->trans("ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL").'</td>';
        if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL')) {
            print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enabledraftexport&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enabledraftexport&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';
        */

        print '<tr class="oddeven">';
        print '<td>' . $langs->trans("BANK_DISABLE_DIRECT_INPUT") . '</td>';
        if (getDolGlobalString('BANK_DISABLE_DIRECT_INPUT')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setBANK_DISABLE_DIRECT_INPUT&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setBANK_DISABLE_DIRECT_INPUT&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td>' . $langs->trans("ACCOUNTANCY_COMBO_FOR_AUX");
        print ' - <span class="opacitymedium">' . $langs->trans("NotRecommended") . '</span>';
        print '</td>';

        if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTANCY_COMBO_FOR_AUX&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTANCY_COMBO_FOR_AUX&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td>' . $langs->trans("ACCOUNTING_MANAGE_ZERO") . '</td>';
        if (getDolGlobalInt('ACCOUNTING_MANAGE_ZERO')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_MANAGE_ZERO&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setACCOUNTING_MANAGE_ZERO&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

// Param a user $user->hasRights('accounting', 'chartofaccount') can access
        foreach ($list as $key) {
            print '<tr class="oddeven value">';

            if (getDolGlobalInt('ACCOUNTING_MANAGE_ZERO') && ($key == 'ACCOUNTING_LENGTH_GACCOUNT' || $key == 'ACCOUNTING_LENGTH_AACCOUNT')) {
                continue;
            }

            // Param
            $label = $langs->trans($key);
            print '<td>' . $label . '</td>';
            // Value
            print '<td class="right">';
            print '<input type="text" class="maxwidth50 right" id="' . $key . '" name="' . $key . '" value="' . getDolGlobalString($key) . '">';

            print '</td>';
            print '</tr>';
        }
        print '</table>';
        print '</div>';

        print '<br>';

// Binding params
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td colspan="2">' . $langs->trans('BindingOptions') . '</td>';
        print "</tr>\n";

// Param a user $user->hasRights('accounting', 'chartofaccount') can access
        foreach ($list_binding as $key) {
            print '<tr class="oddeven value">';

            // Param
            $label = $langs->trans($key);
            print '<td>' . $label . '</td>';
            // Value
            print '<td class="right minwidth75imp parentonrightofpage">';
            if ($key == 'ACCOUNTING_DATE_START_BINDING') {
                print $form->selectDate((getDolGlobalInt($key) ? (int) getDolGlobalInt($key) : -1), $key, 0, 0, 1);
            } elseif ($key == 'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER') {
                $array = [0 => $langs->trans("PreviousMonth"), 1 => $langs->trans("CurrentMonth"), 2 => $langs->trans("Fiscalyear")];
                print $form->selectarray($key, $array, getDolGlobalInt('ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER', 0), 0, 0, 0, '', 0, 0, 0, '', 'onrightofpage width200');
            } else {
                print '<input type="text" class="maxwidth100" id="' . $key . '" name="' . $key . '" value="' . getDolGlobalString($key) . '">';
            }

            print '</td>';
            print '</tr>';
        }

        print '<tr class="oddeven">';
        print '<td>' . $langs->trans("ACCOUNTING_DISABLE_BINDING_ON_SALES") . '</td>';
        if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_SALES')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonsales&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonsales&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td>' . $langs->trans("ACCOUNTING_DISABLE_BINDING_ON_PURCHASES") . '</td>';
        if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_PURCHASES')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonpurchases&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonpurchases&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

        print '<tr class="oddeven">';
        print '<td>' . $langs->trans("ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS") . '</td>';
        if (getDolGlobalString('ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonexpensereports&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setdisablebindingonexpensereports&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

        print '</table>';
        print '</div>';


// Show advanced options
        print '<br>';


// Advanced params
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td colspan="2">' . $langs->trans('OptionsAdvanced') . '</td>';
        print "</tr>\n";

        print '<tr class="oddeven">';
        print '<td>';
        print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_LETTERING"), $langs->trans("ACCOUNTING_ENABLE_LETTERING_DESC", $langs->transnoentitiesnoconv("NumMvts")) . '<br>' . $langs->trans("EnablingThisFeatureIsNotNecessary")) . '</td>';
        if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablelettering&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablelettering&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

        if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
            // Number of letters for lettering (3 by default (AAA), min 2 (AA))
            print '<tr class="oddeven">';
            print '<td>';
            print $form->textwithpicto($langs->trans("ACCOUNTING_LETTERING_NBLETTERS"), $langs->trans("ACCOUNTING_LETTERING_NBLETTERS_DESC")) . '</td>';
            print '<td class="right">';

            if (empty($letter)) {
                if (getDolGlobalInt('ACCOUNTING_LETTERING_NBLETTERS')) {
                    $nbletter = getDolGlobalInt('ACCOUNTING_LETTERING_NBLETTERS');
                } else {
                    $nbletter = 3;
                }
            }

            print '<input class="flat right" name="ACCOUNTING_LETTERING_NBLETTERS" id="ACCOUNTING_LETTERING_NBLETTERS" value="' . $nbletter . '" type="number" step="1" min="2" max="3" >' . "\n";
            print '</tr>';

            // Auto Lettering when transfer in accountancy is realized
            print '<tr class="oddeven">';
            print '<td>';
            print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING"), $langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING_DESC")) . '</td>';
            if (getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
                print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=0">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a></td>';
            } else {
                print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=1">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a></td>';
            }
            print '</tr>';
        }

        print '<tr class="oddeven">';
        print '<td>';
        print $form->textwithpicto($langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE"), $langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE_DESC", $langs->transnoentities("MenuDefaultAccounts"))) . '</td>';
        if (getDolGlobalString('ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE')) {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }
        print '</tr>';

        print '</table>';
        print '</div>';


        print '<div class="center"><input type="submit" class="button button-edit" name="button" value="' . $langs->trans('Modify') . '"></div>';

        print '</form>';

// End of page
        llxFooter();
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

        if (!defined('CSRFCHECK_WITH_TOKEN')) {
            define('CSRFCHECK_WITH_TOKEN', '1'); // Force use of CSRF protection with tokens even for GET
        }

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

        $form = new Form($db);
        $formadmin = new FormAdmin($db);

        $title = $langs->trans('AccountingJournals');
        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
        llxHeader('', $title, $help_url);

        $titre = $langs->trans("DictionarySetup");
        $linkback = '';
        if ($id) {
            $titre .= ' - ' . $langs->trans($tablib[$id]);
            $titlepicto = 'title_accountancy';
        }

        print load_fiche_titre($titre, $linkback, $titlepicto);


// Confirmation de la suppression de la ligne
        if ($action == 'delete') {
            print $form->formconfirm($_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . $rowid . '&code=' . $code . '&id=' . $id, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
        }

        /*
         * Show a dictionary
         */
        if ($id) {
            // Complete requete recherche valeurs avec critere de tri
            $sql = $tabsql[$id];
            $sql .= " WHERE a.entity = " . ((int) $conf->entity);

            // If sort order is "country", we use country_code instead
            if ($sortfield == 'country') {
                $sortfield = 'country_code';
            }
            $sql .= $db->order($sortfield, $sortorder);
            $sql .= $db->plimit($listlimit + 1, $offset);

            $fieldlist = explode(',', $tabfield[$id]);

            print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="from" value="' . dol_escape_htmltag(GETPOST('from', 'alpha')) . '">';

            print '<div class="div-table-responsive">';
            print '<table class="noborder centpercent">';

            // Form to add a new line
            if ($tabname[$id]) {
                $fieldlist = explode(',', $tabfield[$id]);

                // Line for title
                print '<tr class="liste_titre">';
                foreach ($fieldlist as $field => $value) {
                    // Determine le nom du champ par rapport aux noms possibles
                    // dans les dictionnaires de donnees
                    $valuetoshow = ucfirst($fieldlist[$field]); // By default
                    $valuetoshow = $langs->trans($valuetoshow); // try to translate
                    $class = "left";
                    if ($fieldlist[$field] == 'code') {
                        $valuetoshow = $langs->trans("Code");
                    }
                    if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
                        $valuetoshow = $langs->trans("Label");
                    }
                    if ($fieldlist[$field] == 'nature') {
                        $valuetoshow = $langs->trans("NatureOfJournal");
                    }

                    if ($valuetoshow != '') {
                        print '<td class="' . $class . '">';
                        if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
                            print '<a href="' . $tabhelp[$id][$value] . '">' . $valuetoshow . ' ' . img_help(1, $valuetoshow) . '</a>';
                        } elseif (!empty($tabhelp[$id][$value])) {
                            print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
                        } else {
                            print $valuetoshow;
                        }
                        print '</td>';
                    }
                }

                print '<td>';
                print '<input type="hidden" name="id" value="' . $id . '">';
                print '</td>';
                print '<td></td>';
                print '<td></td>';
                print '<td></td>';
                print '</tr>';

                // Line to enter new values
                print '<tr class="oddeven nodrag nodrap nohover">';

                $obj = new stdClass();
                // If data was already input, we define them in obj to populate input fields.
                if (GETPOST('actionadd', 'alpha')) {
                    foreach ($fieldlist as $key => $val) {
                        if (GETPOST($val) != '') {
                            $obj->$val = GETPOST($val);
                        }
                    }
                }

                $tmpaction = 'create';
                $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                $reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                $error = $hookmanager->error;
                $errors = $hookmanager->errors;

                if (empty($reshook)) {
                    $this->fieldListJournal($fieldlist, $obj, $tabname[$id], 'add');
                }

                print '<td colspan="4" class="right">';
                print '<input type="submit" class="button button-add" name="actionadd" value="' . $langs->trans("Add") . '">';
                print '</td>';
                print "</tr>";

                print '<tr><td colspan="7">&nbsp;</td></tr>'; // Keep &nbsp; to have a line with enough height
            }


            // List of available record in database
            dol_syslog("htdocs/admin/dict", LOG_DEBUG);
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);
                $i = 0;

                $param = '&id=' . ((int) $id);
                if ($search_country_id > 0) {
                    $param .= '&search_country_id=' . urlencode((string) ($search_country_id));
                }
                $paramwithsearch = $param;
                if ($sortorder) {
                    $paramwithsearch .= '&sortorder=' . $sortorder;
                }
                if ($sortfield) {
                    $paramwithsearch .= '&sortfield=' . $sortfield;
                }
                if (GETPOST('from', 'alpha')) {
                    $paramwithsearch .= '&from=' . GETPOST('from', 'alpha');
                }

                // There is several pages
                if ($num > $listlimit) {
                    print '<tr class="none"><td class="right" colspan="' . (3 + count($fieldlist)) . '">';
                    print_fleche_navigation($page, $_SERVER['PHP_SELF'], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>' . $langs->trans("Page") . ' ' . ($page + 1) . '</span></li>');
                    print '</td></tr>';
                }

                // Title line with search boxes
                /*print '<tr class="liste_titre_filter liste_titre_add">';
                print '<td class="liste_titre"></td>';
                print '<td class="liste_titre"></td>';
                print '<td class="liste_titre"></td>';
                print '<td class="liste_titre"></td>';
                print '<td class="liste_titre"></td>';
                print '<td class="liste_titre"></td>';
                print '<td class="liste_titre center">';
                $searchpicto=$form->showFilterButtons();
                print $searchpicto;
                print '</td>';
                print '</tr>';
                */

                // Title of lines
                print '<tr class="liste_titre liste_titre_add">';
                foreach ($fieldlist as $field => $value) {
                    // Determine le nom du champ par rapport aux noms possibles
                    // dans les dictionnaires de donnees
                    $showfield = 1; // By default
                    $class = "left";
                    $sortable = 1;
                    $valuetoshow = '';
                    /*
                    $tmparray=getLabelOfField($fieldlist[$field]);
                    $showfield=$tmp['showfield'];
                    $valuetoshow=$tmp['valuetoshow'];
                    $align=$tmp['align'];
                    $sortable=$tmp['sortable'];
                    */
                    $valuetoshow = ucfirst($fieldlist[$field]); // By default
                    $valuetoshow = $langs->trans($valuetoshow); // try to translate
                    if ($fieldlist[$field] == 'code') {
                        $valuetoshow = $langs->trans("Code");
                    }
                    if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
                        $valuetoshow = $langs->trans("Label");
                    }
                    if ($fieldlist[$field] == 'nature') {
                        $valuetoshow = $langs->trans("NatureOfJournal");
                    }

                    // Affiche nom du champ
                    if ($showfield) {
                        print getTitleFieldOfList($valuetoshow, 0, $_SERVER['PHP_SELF'], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page=' . $page . '&' : ''), $param, "", $sortfield, $sortorder, $class . ' ');
                    }
                }
                print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER['PHP_SELF'], "active", ($page ? 'page=' . $page . '&' : ''), $param, '', $sortfield, $sortorder, 'center ');
                print getTitleFieldOfList('');
                print getTitleFieldOfList('');
                print getTitleFieldOfList('');
                print '</tr>';

                if ($num) {
                    // Lines with values
                    while ($i < $num) {
                        $obj = $db->fetch_object($resql);
                        //print_r($obj);
                        print '<tr class="oddeven" id="rowid-' . $obj->rowid . '">';
                        if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
                            $tmpaction = 'edit';
                            $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                            $reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
                            $error = $hookmanager->error;
                            $errors = $hookmanager->errors;

                            // Show fields
                            if (empty($reshook)) {
                                $this->fieldListJournal($fieldlist, $obj, $tabname[$id], 'edit');
                            }

                            print '<td class="center" colspan="4">';
                            print '<input type="hidden" name="page" value="' . $page . '">';
                            print '<input type="hidden" name="rowid" value="' . $rowid . '">';
                            print '<input type="submit" class="button button-edit" name="actionmodify" value="' . $langs->trans("Modify") . '">';
                            print '<input type="submit" class="button button-cancel" name="actioncancel" value="' . $langs->trans("Cancel") . '">';
                            print '<div name="' . (!empty($obj->rowid) ? $obj->rowid : $obj->code) . '"></div>';
                            print '</td>';
                        } else {
                            $tmpaction = 'view';
                            $parameters = ['fieldlist' => $fieldlist, 'tabname' => $tabname[$id]];
                            $reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

                            $error = $hookmanager->error;
                            $errors = $hookmanager->errors;

                            if (empty($reshook)) {
                                $langs->load("accountancy");
                                foreach ($fieldlist as $field => $value) {
                                    $showfield = 1;
                                    $class = "left";
                                    $tmpvar = $fieldlist[$field];
                                    $valuetoshow = $obj->$tmpvar;
                                    if ($valuetoshow == 'all') {
                                        $valuetoshow = $langs->trans('All');
                                    } elseif ($fieldlist[$field] == 'nature' && $tabname[$id] == MAIN_DB_PREFIX . 'accounting_journal') {
                                        $key = $langs->trans("AccountingJournalType" . strtoupper($obj->nature));
                                        $valuetoshow = ($obj->nature && $key != "AccountingJournalType" . strtoupper($langs->trans($obj->nature)) ? $key : $obj->{$fieldlist[$field]});
                                    } elseif ($fieldlist[$field] == 'label' && $tabname[$id] == MAIN_DB_PREFIX . 'accounting_journal') {
                                        $valuetoshow = $langs->trans($obj->label);
                                    }

                                    $class = 'tddict';
                                    // Show value for field
                                    if ($showfield) {
                                        print '<!-- ' . $fieldlist[$field] . ' --><td class="' . $class . '">' . dol_escape_htmltag($valuetoshow) . '</td>';
                                    }
                                }
                            }

                            // Can an entry be erased or disabled ?
                            $iserasable = 1;
                            $canbedisabled = 1;
                            $canbemodified = 1; // true by default
                            if (isset($obj->code) && $id != 10) {
                                if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i', $obj->code))) {
                                    $iserasable = 0;
                                    $canbedisabled = 0;
                                }
                            }

                            $canbemodified = $iserasable;

                            $url = $_SERVER['PHP_SELF'] . '?' . ($page ? 'page=' . $page . '&' : '') . 'sortfield=' . $sortfield . '&sortorder=' . $sortorder . '&rowid=' . (!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')) . '&code=' . (!empty($obj->code) ? urlencode($obj->code) : '');
                            if ($param) {
                                $url .= '&' . $param;
                            }
                            $url .= '&';

                            // Active
                            print '<td class="nowrap center">';
                            if ($canbedisabled) {
                                print '<a href="' . $url . 'action=' . $acts[$obj->active] . '&token=' . newToken() . '">' . $actl[$obj->active] . '</a>';
                            } else {
                                print $langs->trans("AlwaysActive");
                            }
                            print "</td>";

                            // Modify link
                            if ($canbemodified) {
                                print '<td class="center"><a class="reposition editfielda" href="' . $url . 'action=edit&token=' . newToken() . '">' . img_edit() . '</a></td>';
                            } else {
                                print '<td>&nbsp;</td>';
                            }

                            // Delete link
                            if ($iserasable) {
                                print '<td class="center">';
                                if ($user->admin) {
                                    print '<a href="' . $url . 'action=delete&token=' . newToken() . '">' . img_delete() . '</a>';
                                }
                                //else print '<a href="#">'.img_delete().'</a>';    // Some dictionary can be edited by other profile than admin
                                print '</td>';
                            } else {
                                print '<td>&nbsp;</td>';
                            }

                            print '<td></td>';

                            print '</td>';
                        }

                        print "</tr>\n";
                        $i++;
                    }
                }
            } else {
                dol_print_error($db);
            }

            print '</table>';
            print '</div>';

            print '</form>';
        }

        print '<br>';

// End of page
        llxFooter();
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

        $form = new FormAccounting($db);

// Default AccountingAccount RowId Product / Service
// at this time ACCOUNTING_SERVICE_SOLD_ACCOUNT & ACCOUNTING_PRODUCT_SOLD_ACCOUNT are account number not accountingacount rowid
// so we need to get those the rowid of those default value first
        $accounting = new AccountingAccount($db);
// TODO: we should need to check if result is already exists accountaccount rowid.....
        $aarowid_servbuy = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT'), 1);
        $aarowid_servbuy_intra = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT'), 1);
        $aarowid_servbuy_export = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT'), 1);
        $aarowid_prodbuy = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT'), 1);
        $aarowid_prodbuy_intra = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT'), 1);
        $aarowid_prodbuy_export = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT'), 1);
        $aarowid_servsell = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT'), 1);
        $aarowid_servsell_intra = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT'), 1);
        $aarowid_servsell_export = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT'), 1);
        $aarowid_prodsell = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT'), 1);
        $aarowid_prodsell_intra = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT'), 1);
        $aarowid_prodsell_export = $accounting->fetch(0, getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT'), 1);

        $aacompta_servbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_servbuy_intra = getDolGlobalString('ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_servbuy_export = getDolGlobalString('ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_prodbuy_intra = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_prodbuy_export = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_servsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_servsell_intra = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_servsell_export = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_prodsell_intra = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
        $aacompta_prodsell_export = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));


        $title = $langs->trans("ProductsBinding");
        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

        $paramsCat = '';
        foreach ($searchCategoryProductList as $searchCategoryProduct) {
            $paramsCat .= "&search_category_product_list[]=" . urlencode($searchCategoryProduct);
        }

        llxHeader('', $title, $help_url, '', 0, 0, [], [], $paramsCat, '');

        $pcgverid = getDolGlobalString('CHARTOFACCOUNTS');
        $pcgvercode = dol_getIdFromCode($db, $pcgverid, 'accounting_system', 'rowid', 'pcg_version');
        if (empty($pcgvercode)) {
            $pcgvercode = $pcgverid;
        }

        $sql = "SELECT p.rowid, p.ref, p.label, p.description, p.tosell, p.tobuy, p.tva_tx,";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " ppe.accountancy_code_sell, ppe.accountancy_code_sell_intra, ppe.accountancy_code_sell_export,";
            $sql .= " ppe.accountancy_code_buy, ppe.accountancy_code_buy_intra, ppe.accountancy_code_buy_export,";
        } else {
            $sql .= " p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,";
            $sql .= " p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export,";
        }
        $sql .= " p.tms, p.fk_product_type as product_type,";
        $sql .= " aa.rowid as aaid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
        if (getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_perentity as ppe ON ppe.fk_product = p.rowid AND ppe.entity = " . ((int) $conf->entity);
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.account_number = ppe." . $db->sanitize($accountancy_field_name) . " AND aa.fk_pcg_version = '" . $db->escape($pcgvercode) . "'";
        } else {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.account_number = p." . $db->sanitize($accountancy_field_name) . " AND aa.fk_pcg_version = '" . $db->escape($pcgvercode) . "'";
        }
        if (!empty($searchCategoryProductList)) {
            $sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . "categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
        }
        $sql .= ' WHERE p.entity IN (' . getEntity('product') . ')';
        if (strlen(trim($search_current_account))) {
            $sql .= natural_search((!getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p." : "ppe.") . $db->sanitize($accountancy_field_name), $search_current_account);
        }
        if ($search_current_account_valid == 'withoutvalidaccount') {
            $sql .= " AND aa.account_number IS NULL";
        }
        if ($search_current_account_valid == 'withvalidaccount') {
            $sql .= " AND aa.account_number IS NOT NULL";
        }
        $searchCategoryProductSqlList = [];
        if ($searchCategoryProductOperator == 1) {
            foreach ($searchCategoryProductList as $searchCategoryProduct) {
                if (intval($searchCategoryProduct) == -2) {
                    $searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
                } elseif (intval($searchCategoryProduct) > 0) {
                    $searchCategoryProductSqlList[] = "cp.fk_categorie = " . ((int) $searchCategoryProduct);
                }
            }
            if (!empty($searchCategoryProductSqlList)) {
                $sql .= " AND (" . implode(' OR ', $searchCategoryProductSqlList) . ")";
            }
        } else {
            foreach ($searchCategoryProductList as $searchCategoryProduct) {
                if (intval($searchCategoryProduct) == -2) {
                    $searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
                } elseif (intval($searchCategoryProduct) > 0) {
                    $searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_categorie = " . ((int) $searchCategoryProduct) . ")";
                }
            }
            if (!empty($searchCategoryProductSqlList)) {
                $sql .= " AND (" . implode(' AND ', $searchCategoryProductSqlList) . ")";
            }
        }
// Add search filter like
        if (strlen(trim($search_ref))) {
            $sql .= natural_search("p.ref", $search_ref);
        }
        if (strlen(trim($search_label))) {
            $sql .= natural_search("p.label", $search_label);
        }
        if (strlen(trim($search_desc))) {
            $sql .= natural_search("p.description", $search_desc);
        }
        if (strlen(trim($search_vat))) {
            $sql .= natural_search("p.tva_tx", price2num($search_vat), 1);
        }
        if ($search_onsell != '' && $search_onsell != '-1') {
            $sql .= natural_search('p.tosell', $search_onsell, 1);
        }
        if ($search_onpurchase != '' && $search_onpurchase != '-1') {
            $sql .= natural_search('p.tobuy', $search_onpurchase, 1);
        }

        $sql .= " GROUP BY p.rowid, p.ref, p.label, p.description, p.tosell, p.tobuy, p.tva_tx,";
        $sql .= " p.fk_product_type,";
        $sql .= ' p.tms,';
        $sql .= ' aa.rowid,';
        if (!getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED')) {
            $sql .= " p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export, p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export";
        } else {
            $sql .= " ppe.accountancy_code_sell, ppe.accountancy_code_sell_intra, ppe.accountancy_code_sell_export, ppe.accountancy_code_buy, ppe.accountancy_code_buy_intra, ppe.accountancy_code_buy_export";
        }

        $sql .= $db->order($sortfield, $sortorder);

        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            $resql = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($resql);
            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
        }

        $sql .= $db->plimit($limit + 1, $offset);

        dol_syslog("/accountancy/admin/productaccount.php", LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;

            $param = '';
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }
            if ($searchCategoryProductOperator == 1) {
                $param .= "&search_category_product_operator=" . urlencode((string) ($searchCategoryProductOperator));
            }
            foreach ($searchCategoryProductList as $searchCategoryProduct) {
                $param .= "&search_category_product_list[]=" . urlencode($searchCategoryProduct);
            }
            if ($search_ref > 0) {
                $param .= "&search_ref=" . urlencode($search_ref);
            }
            if ($search_label > 0) {
                $param .= "&search_label=" . urlencode($search_label);
            }
            if ($search_desc > 0) {
                $param .= "&search_desc=" . urlencode($search_desc);
            }
            if ($search_vat > 0) {
                $param .= '&search_vat=' . urlencode($search_vat);
            }
            if ($search_current_account > 0) {
                $param .= "&search_current_account=" . urlencode($search_current_account);
            }
            if ($search_current_account_valid && $search_current_account_valid != '-1') {
                $param .= "&search_current_account_valid=" . urlencode($search_current_account_valid);
            }
            if ($accounting_product_mode) {
                $param .= '&accounting_product_mode=' . urlencode($accounting_product_mode);
            }

            print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';

            print load_fiche_titre($langs->trans("ProductsBinding"), '', 'title_accountancy');
            print '<br>';

            print '<span class="opacitymedium">' . $langs->trans("InitAccountancyDesc") . '</span><br>';
            print '<br>';

            // Select mode
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans('Options') . '</td><td>' . $langs->trans('Description') . '</td>';
            print "</tr>\n";
            print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode1" name="accounting_product_mode" value="ACCOUNTANCY_SELL"' . ($accounting_product_mode == 'ACCOUNTANCY_SELL' ? ' checked' : '') . '> <label for="accounting_product_mode1">' . $langs->trans('OptionModeProductSell') . '</label></td>';
            print '<td>' . $langs->trans('OptionModeProductSellDesc');
            print "</td></tr>\n";
            if ($mysoc->isInEEC()) {
                print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode2" name="accounting_product_mode" value="ACCOUNTANCY_SELL_INTRA"' . ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' ? ' checked' : '') . '> <label for="accounting_product_mode2">' . $langs->trans('OptionModeProductSellIntra') . '</label></td>';
                print '<td>' . $langs->trans('OptionModeProductSellIntraDesc');
                print "</td></tr>\n";
            }
            print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode3" name="accounting_product_mode" value="ACCOUNTANCY_SELL_EXPORT"' . ($accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT' ? ' checked' : '') . '> <label for="accounting_product_mode3">' . $langs->trans('OptionModeProductSellExport') . '</label></td>';
            print '<td>' . $langs->trans('OptionModeProductSellExportDesc');
            print "</td></tr>\n";
            print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode4" name="accounting_product_mode" value="ACCOUNTANCY_BUY"' . ($accounting_product_mode == 'ACCOUNTANCY_BUY' ? ' checked' : '') . '> <label for="accounting_product_mode4">' . $langs->trans('OptionModeProductBuy') . '</label></td>';
            print '<td>' . $langs->trans('OptionModeProductBuyDesc') . "</td></tr>\n";
            if ($mysoc->isInEEC()) {
                print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode5" name="accounting_product_mode" value="ACCOUNTANCY_BUY_INTRA"' . ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA' ? ' checked' : '') . '> <label for="accounting_product_mode5">' . $langs->trans('OptionModeProductBuyIntra') . '</label></td>';
                print '<td>' . $langs->trans('OptionModeProductBuyDesc') . "</td></tr>\n";
            }
            print '<tr class="oddeven"><td><input type="radio" id="accounting_product_mode6" name="accounting_product_mode" value="ACCOUNTANCY_BUY_EXPORT"' . ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT' ? ' checked' : '') . '> <label for="accounting_product_mode6">' . $langs->trans('OptionModeProductBuyExport') . '</label></td>';
            print '<td>' . $langs->trans('OptionModeProductBuyDesc') . "</td></tr>\n";
            print "</table>\n";

            print '<div class="center"><input type="submit" class="button" value="' . $langs->trans('Refresh') . '" name="changetype"></div>';

            print "<br>\n";


            // Filter on categories
            $moreforfilter = '';
            $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
            $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

            if ($massaction !== 'set_default_account') {
                $arrayofmassactions = [
                    'changeaccount' => img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans("Save")
                    , 'set_default_account' => img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans("ConfirmPreselectAccount"),
                ];
                $massactionbutton = $form->selectMassAction('', $arrayofmassactions, 1);
            }

            $buttonsave = '<input type="submit" class="button button-save" id="changeaccount" name="changeaccount" value="' . $langs->trans("Save") . '">';
            //print '<br><div class="center">'.$buttonsave.'</div>';

            $texte = $langs->trans("ListOfProductsServices");
            print_barre_liste($texte, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit, 0, 0, 1);

            if ($massaction == 'set_default_account') {
                $formquestion = [];
                $formquestion[] = [
                    'type' => 'other',
                    'name' => 'set_default_account',
                    'label' => $langs->trans("AccountancyCode"),
                    'value' => $form->select_account('', 'default_account', 1, [], 0, 0, 'maxwidth200 maxwidthonsmartphone', 'cachewithshowemptyone'),
                ];
                print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmPreselectAccount"), $langs->trans("ConfirmPreselectAccountQuestion", count($chk_prod)), "confirm_set_default_account", $formquestion, 1, 0, 200, 500, 1);
            }

            // Filter on categories
            $moreforfilter = '';
            if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
                $formcategory = new FormCategory($db);
                $moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_PRODUCT, $searchCategoryProductList, 'minwidth300', $searchCategoryProductList ? $searchCategoryProductList : 0);
                /*
                $moreforfilter .= '<div class="divsearchfield">';
                $moreforfilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"');
                $categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
                $categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
                $moreforfilter .= Form::multiselectarray('search_category_product_list', $categoriesProductArr, $searchCategoryProductList, 0, 0, 'minwidth300');
                $moreforfilter .= ' <input type="checkbox" class="valignmiddle" id="search_category_product_operator" name="search_category_product_operator" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/> <label for="search_category_product_operator"><span class="none">'.$langs->trans('UseOrOperatorForCategories').'</span></label>';
                $moreforfilter .= '</div>';
                */
            }

            // Show/hide child products. Hidden by default
            if (isModEnabled('variants') && getDolGlobalInt('PRODUIT_ATTRIBUTES_HIDECHILD')) {
                $moreforfilter .= '<div class="divsearchfield">';
                $moreforfilter .= '<input type="checkbox" id="search_show_childproducts" name="search_show_childproducts"' . ($show_childproducts ? 'checked="checked"' : '') . '>';
                $moreforfilter .= ' <label for="search_show_childproducts">' . $langs->trans('ShowChildProducts') . '</label>';
                $moreforfilter .= '</div>';
            }

            $parameters = [];
            $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
            if (empty($reshook)) {
                $moreforfilter .= $hookmanager->resPrint;
            } else {
                $moreforfilter = $hookmanager->resPrint;
            }

            if ($moreforfilter) {
                print '<div class="liste_titre liste_titre_bydiv centpercent">';
                print $moreforfilter;
                print '</div>';
            }

            print '<div class="div-table-responsive">';
            print '<table class="liste ' . ($moreforfilter ? "listwithfilterbefore" : "") . '">';

            print '<tr class="liste_titre_filter">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="center liste_titre">';
                $searchpicto = $form->showFilterButtons();
                print $searchpicto;
                print '</td>';
            }
            print '<td class="liste_titre"><input type="text" class="flat" size="8" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" value="' . dol_escape_htmltag($search_vat) . '"></td>';

            if (getDolGlobalInt('ACCOUNTANCY_SHOW_PROD_DESC')) {
                print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
            }
            // On sell
            if ($accounting_product_mode == 'ACCOUNTANCY_SELL' || $accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' || $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
                print '<td class="liste_titre center">' . $form->selectyesno('search_onsell', $search_onsell, 1, false, 1) . '</td>';
            } else {
                // On buy
                print '<td class="liste_titre center">' . $form->selectyesno('search_onpurchase', $search_onpurchase, 1, false, 1) . '</td>';
            }
            // Current account
            print '<td class="liste_titre">';
            print '<input type="text" class="flat" size="6" name="search_current_account" id="search_current_account" value="' . dol_escape_htmltag($search_current_account) . '">';
            $listofvals = ['withoutvalidaccount' => $langs->trans("WithoutValidAccount"), 'withvalidaccount' => $langs->trans("WithValidAccount")];
            print ' ' . $langs->trans("or") . ' ' . $form->selectarray('search_current_account_valid', $listofvals, $search_current_account_valid, 1);
            print '</td>';
            print '<td class="liste_titre">&nbsp;</td>';
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="center liste_titre">';
                $searchpicto = $form->showFilterButtons();
                print $searchpicto;
                print '</td>';
            }
            print '</tr>';

            print '<tr class="liste_titre">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                $clickpitco = $form->showCheckAddButtons('checkforselect', 1);
                print_liste_field_titre($clickpitco, '', '', '', '', '', '', '', 'center ');
            }
            print_liste_field_titre("Ref", $_SERVER['PHP_SELF'], "p.ref", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Label", $_SERVER['PHP_SELF'], "p.label", "", $param, '', $sortfield, $sortorder);
            if (getDolGlobalInt('ACCOUNTANCY_SHOW_PROD_DESC')) {
                print_liste_field_titre("Description", $_SERVER['PHP_SELF'], "p.description", "", $param, '', $sortfield, $sortorder);
            }
            print_liste_field_titre("VATRate", $_SERVER['PHP_SELF'], "p.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
            // On sell / On purchase
            if ($accounting_product_mode == 'ACCOUNTANCY_SELL' || $accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' || $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
                print_liste_field_titre("OnSell", $_SERVER['PHP_SELF'], "p.tosell", "", $param, '', $sortfield, $sortorder, 'center ');
            } else {
                print_liste_field_titre("OnBuy", $_SERVER['PHP_SELF'], "p.tobuy", "", $param, '', $sortfield, $sortorder, 'center ');
            }
            print_liste_field_titre("CurrentDedicatedAccountingAccount", $_SERVER['PHP_SELF'], (!getDolGlobalString('MAIN_PRODUCT_PERENTITY_SHARED') ? "p." : "ppe.") . $accountancy_field_name, "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("AssignDedicatedAccountingAccount");
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                $clickpitco = $form->showCheckAddButtons('checkforselect', 1);
                print_liste_field_titre($clickpitco, '', '', '', '', '', '', '', 'center ');
            }
            print '</tr>';

            $product_static = new Product($db);

            $i = 0;
            while ($i < min($num, $limit)) {
                $obj = $db->fetch_object($resql);

                // Ref produit as link
                $product_static->ref = $obj->ref;
                $product_static->id = $obj->rowid;
                $product_static->type = $obj->product_type;
                $product_static->label = $obj->label;
                $product_static->description = $obj->description;
                $product_static->status = $obj->tosell;
                $product_static->status_buy = $obj->tobuy;

                // Sales
                if ($obj->product_type == 0) {
                    if ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodsell_id = $aarowid_prodsell;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodsell_id = $aarowid_prodsell_intra;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodsell_id = $aarowid_prodsell_export;
                    } else {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_PRODUCT_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodsell_id = $aarowid_prodsell;
                    }
                } else {
                    if ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodsell_id = $aarowid_servsell;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodsell_id = $aarowid_servsell_intra;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));

                        $compta_prodsell_id = $aarowid_servsell_export;
                    } else {
                        $compta_prodsell = getDolGlobalString('ACCOUNTING_SERVICE_SOLD_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodsell_id = $aarowid_servsell;
                    }
                }

                // Purchases
                if ($obj->product_type == 0) {
                    if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_prodbuy;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_prodbuy_intra;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_prodbuy_export;
                    } else {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_PRODUCT_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_prodbuy;
                    }
                } else {
                    if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_servbuy;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_INTRA_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_servbuy_intra;
                    } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_EXPORT_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_servbuy_export;
                    } else {
                        $compta_prodbuy = getDolGlobalString('ACCOUNTING_SERVICE_BUY_ACCOUNT', $langs->trans("CodeNotDef"));
                        $compta_prodbuy_id = $aarowid_servbuy;
                    }
                }

                $selected = 0;
                if (!empty($chk_prod)) {
                    if (in_array($product_static->id, $chk_prod)) {
                        $selected = 1;
                    }
                }

                print '<tr class="oddeven">';

                // Action column
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    print '<input type="checkbox" class="checkforselect productforselectcodeventil_' . $product_static->id . '" name="chk_prod[]" ' . ($selected ? "checked" : "") . ' value="' . $obj->rowid . '"/>';
                    print '</td>';
                }

                print '<td>';
                print $product_static->getNomUrl(1);
                print '</td>';

                print '<td class="left">' . $obj->label . '</td>';

                if (getDolGlobalInt('ACCOUNTANCY_SHOW_PROD_DESC')) {
                    // TODO ADJUST DESCRIPTION SIZE
                    // print '<td class="left">' . $obj->description . '</td>';
                    // TODO: we should set a user defined value to adjust user square / wide screen size
                    $trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION', 32);
                    print '<td>' . nl2br(dol_trunc($obj->description, $trunclength)) . '</td>';
                }

                // VAT
                print '<td class="right">';
                print vatrate($obj->tva_tx);
                print '</td>';

                // On sell / On purchase
                if ($accounting_product_mode == 'ACCOUNTANCY_SELL' || $accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA' || $accounting_product_mode == 'ACCOUNTANCY_SELL_EXPORT') {
                    print '<td class="center">' . $product_static->getLibStatut(3, 0) . '</td>';
                } else {
                    print '<td class="center">' . $product_static->getLibStatut(3, 1) . '</td>';
                }

                // Current accounting account
                print '<td class="left">';
                if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
                    print length_accountg($obj->accountancy_code_buy);
                    if ($obj->accountancy_code_buy && empty($obj->aaid)) {
                        print ' ' . img_warning($langs->trans("ValueNotIntoChartOfAccount"));
                    }
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
                    print length_accountg($obj->accountancy_code_buy_intra);
                    if ($obj->accountancy_code_buy_intra && empty($obj->aaid)) {
                        print ' ' . img_warning($langs->trans("ValueNotIntoChartOfAccount"));
                    }
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
                    print length_accountg($obj->accountancy_code_buy_export);
                    if ($obj->accountancy_code_buy_export && empty($obj->aaid)) {
                        print ' ' . img_warning($langs->trans("ValueNotIntoChartOfAccount"));
                    }
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
                    print length_accountg($obj->accountancy_code_sell);
                    if ($obj->accountancy_code_sell && empty($obj->aaid)) {
                        print ' ' . img_warning($langs->trans("ValueNotIntoChartOfAccount"));
                    }
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
                    print length_accountg($obj->accountancy_code_sell_intra);
                    if ($obj->accountancy_code_sell_intra && empty($obj->aaid)) {
                        print ' ' . img_warning($langs->trans("ValueNotIntoChartOfAccount"));
                    }
                } else {
                    print length_accountg($obj->accountancy_code_sell_export);
                    if ($obj->accountancy_code_sell_export && empty($obj->aaid)) {
                        print ' ' . img_warning($langs->trans("ValueNotIntoChartOfAccount"));
                    }
                }
                print '</td>';

                // New account to set
                $defaultvalue = '';
                if ($accounting_product_mode == 'ACCOUNTANCY_BUY') {
                    // Accounting account buy
                    print '<td class="left">';
                    //$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
                    if (empty($defaultvalue)) {
                        $defaultvalue = $compta_prodbuy;
                    }
                    $codesell = length_accountg($obj->accountancy_code_buy);
                    if (!empty($obj->aaid)) {
                        $defaultvalue = ''; // Do not suggest default new value is code is already valid
                    }
                    print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $chk_prod)) ? $default_account : $defaultvalue, 'codeventil_' . $product_static->id, 1, [], 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
                    print '</td>';
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_INTRA') {
                    // Accounting account buy intra (In EEC)
                    print '<td class="left">';
                    //$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
                    if (empty($defaultvalue)) {
                        $defaultvalue = $compta_prodbuy;
                    }
                    $codesell = length_accountg($obj->accountancy_code_buy_intra);
                    //var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
                    if (!empty($obj->aaid)) {
                        $defaultvalue = ''; // Do not suggest default new value is code is already valid
                    }
                    print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $chk_prod)) ? $default_account : $defaultvalue, 'codeventil_' . $product_static->id, 1, [], 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
                    print '</td>';
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_BUY_EXPORT') {
                    // Accounting account buy export (Out of EEC)
                    print '<td class="left">';
                    //$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
                    if (empty($defaultvalue)) {
                        $defaultvalue = $compta_prodbuy;
                    }
                    $codesell = length_accountg($obj->accountancy_code_buy_export);
                    //var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
                    if (!empty($obj->aaid)) {
                        $defaultvalue = ''; // Do not suggest default new value is code is already valid
                    }
                    print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $chk_prod)) ? $default_account : $defaultvalue, 'codeventil_' . $product_static->id, 1, [], 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
                    print '</td>';
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL') {
                    // Accounting account sell
                    print '<td class="left">';
                    //$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
                    if (empty($defaultvalue)) {
                        $defaultvalue = $compta_prodsell;
                    }
                    $codesell = length_accountg($obj->accountancy_code_sell);
                    //var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
                    if (!empty($obj->aaid)) {
                        $defaultvalue = ''; // Do not suggest default new value is code is already valid
                    }
                    print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $chk_prod)) ? $default_account : $defaultvalue, 'codeventil_' . $product_static->id, 1, [], 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
                    print '</td>';
                } elseif ($accounting_product_mode == 'ACCOUNTANCY_SELL_INTRA') {
                    // Accounting account sell intra (In EEC)
                    print '<td class="left">';
                    //$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
                    if (empty($defaultvalue)) {
                        $defaultvalue = $compta_prodsell;
                    }
                    $codesell = length_accountg($obj->accountancy_code_sell_intra);
                    //var_dump($defaultvalue.' - '.$codesell.' - '.$compta_prodsell);
                    if (!empty($obj->aaid)) {
                        $defaultvalue = ''; // Do not suggest default new value is code is already valid
                    }
                    print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $chk_prod)) ? $default_account : $defaultvalue, 'codeventil_' . $product_static->id, 1, [], 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
                    print '</td>';
                } else {
                    // Accounting account sell export (Out of EEC)
                    print '<td class="left">';
                    //$defaultvalue=GETPOST('codeventil_' . $product_static->id,'alpha');        This is id and we need a code
                    if (empty($defaultvalue)) {
                        $defaultvalue = $compta_prodsell;
                    }
                    $codesell = length_accountg($obj->accountancy_code_sell_export);
                    if (!empty($obj->aaid)) {
                        $defaultvalue = ''; // Do not suggest default new value is code is already valid
                    }
                    print $form->select_account(($default_account > 0 && $confirm === 'yes' && in_array($product_static->id, $chk_prod)) ? $default_account : $defaultvalue, 'codeventil_' . $product_static->id, 1, [], 1, 0, 'maxwidth300 maxwidthonsmartphone productforselect');
                    print '</td>';
                }

                // Action column
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    print '<input type="checkbox" class="checkforselect productforselectcodeventil_' . $product_static->id . '" name="chk_prod[]" ' . ($selected ? "checked" : "") . ' value="' . $obj->rowid . '"/>';
                    print '</td>';
                }

                print "</tr>";
                $i++;
            }
            print '</table>';
            print '</div>';

            print '<script type="text/javascript">
        jQuery(document).ready(function() {
        	function init_savebutton()
        	{
	            console.log("We check if at least one line is checked")

    			atleastoneselected=0;
	    		jQuery(".checkforselect").each(function( index ) {
	  				/* console.log( index + ": " + $( this ).text() ); */
	  				if ($(this).is(\':checked\')) atleastoneselected++;
	  			});

	            if (atleastoneselected) jQuery("#changeaccount").removeAttr(\'disabled\');
	            else jQuery("#changeaccount").attr(\'disabled\',\'disabled\');
	            if (atleastoneselected) jQuery("#changeaccount").attr(\'class\',\'button\');
	            else jQuery("#changeaccount").attr(\'class\',\'button\');
        	}

        	jQuery(".checkforselect").change(function() {
        		init_savebutton();
        	});
        	jQuery(".productforselect").change(function() {
				console.log($(this).attr("id")+" "+$(this).val());
				if ($(this).val() && $(this).val() != -1) {
					$(".productforselect"+$(this).attr("id")).prop(\'checked\', true);
				} else {
					$(".productforselect"+$(this).attr("id")).prop(\'checked\', false);
				}
        		init_savebutton();
        	});

        	init_savebutton();

            jQuery("#search_current_account").keyup(function() {
        		if (jQuery("#search_current_account").val() != \'\')
                {
                    console.log("We set a value of account to search "+jQuery("#search_current_account").val()+", so we disable the other search criteria on account");
                    jQuery("#search_current_account_valid").val(-1);
                }
        	});
        });
        </script>';

            print '</form>';

            $db->free($resql);
        } else {
            dol_print_error($db);
        }

// End of page
        llxFooter();
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

        $form = new Form($db);


// Page Header
        $help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';
        $title = $langs->trans('ChartOfIndividualAccountsOfSubsidiaryLedger');
        llxHeader('', $title, $help_url);


// Customer
        $sql = "SELECT sa.rowid, sa.nom as label, sa.code_compta as subaccount, '1' as type, sa.entity, sa.client as nature";
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe sa";
        $sql .= " WHERE sa.entity IN (" . getEntity('societe') . ")";
        $sql .= " AND sa.code_compta <> ''";
//print $sql;
        if (strlen(trim($search_subaccount))) {
            $lengthpaddingaccount = 0;
            if (getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
                $lengthpaddingaccount = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
            }
            $search_subaccount_tmp = $search_subaccount;
            $weremovedsomezero = 0;
            if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
                for ($i = 0; $i < $lengthpaddingaccount; $i++) {
                    if (preg_match('/0$/', $search_subaccount_tmp)) {
                        $weremovedsomezero++;
                        $search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
                    }
                }
            }

            //var_dump($search_subaccount); exit;
            if ($search_subaccount_tmp) {
                if ($weremovedsomezero) {
                    $search_subaccount_tmp_clean = $search_subaccount_tmp;
                    $search_subaccount_clean = $search_subaccount;
                    $startchar = '%';
                    if (strpos($search_subaccount_tmp, '^') === 0) {
                        $startchar = '';
                        $search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
                        $search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
                    }
                    $sql .= " AND (sa.code_compta LIKE '" . $db->escape($startchar . $search_subaccount_tmp_clean) . "'";
                    $sql .= " OR sa.code_compta LIKE '" . $db->escape($startchar . $search_subaccount_clean) . "%')";
                } else {
                    $sql .= natural_search("sa.code_compta", $search_subaccount_tmp);
                }
            }
        }
        if (strlen(trim($search_label))) {
            $sql .= natural_search("sa.nom", $search_label);
        }
        if (!empty($search_type) && $search_type >= 0) {
            $sql .= " HAVING type LIKE '" . $db->escape($search_type) . "'";
        }

// Supplier
        $sql .= " UNION ";
        $sql .= " SELECT sa.rowid, sa.nom as label, sa.code_compta_fournisseur as subaccount, '2' as type, sa.entity, '0' as nature FROM " . MAIN_DB_PREFIX . "societe sa";
        $sql .= " WHERE sa.entity IN (" . getEntity('societe') . ")";
        $sql .= " AND sa.code_compta_fournisseur <> ''";
//print $sql;
        if (strlen(trim($search_subaccount))) {
            $lengthpaddingaccount = 0;
            if (getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
                $lengthpaddingaccount = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
            }
            $search_subaccount_tmp = $search_subaccount;
            $weremovedsomezero = 0;
            if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
                for ($i = 0; $i < $lengthpaddingaccount; $i++) {
                    if (preg_match('/0$/', $search_subaccount_tmp)) {
                        $weremovedsomezero++;
                        $search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
                    }
                }
            }

            //var_dump($search_subaccount); exit;
            if ($search_subaccount_tmp) {
                if ($weremovedsomezero) {
                    $search_subaccount_tmp_clean = $search_subaccount_tmp;
                    $search_subaccount_clean = $search_subaccount;
                    $startchar = '%';
                    if (strpos($search_subaccount_tmp, '^') === 0) {
                        $startchar = '';
                        $search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
                        $search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
                    }
                    $sql .= " AND (sa.code_compta_fournisseur LIKE '" . $db->escape($startchar . $search_subaccount_tmp_clean) . "'";
                    $sql .= " OR sa.code_compta_fournisseur LIKE '" . $db->escape($startchar . $search_subaccount_clean) . "%')";
                } else {
                    $sql .= natural_search("sa.code_compta_fournisseur", $search_subaccount_tmp);
                }
            }
        }
        if (strlen(trim($search_label))) {
            $sql .= natural_search("sa.nom", $search_label);
        }
        if (!empty($search_type) && $search_type >= 0) {
            $sql .= " HAVING type LIKE '" . $db->escape($search_type) . "'";
        }

// User - Employee
        $sql .= " UNION ";
        $sql .= " SELECT u.rowid, u.lastname as label, u.accountancy_code as subaccount, '3' as type, u.entity, '0' as nature FROM " . MAIN_DB_PREFIX . "user u";
        $sql .= " WHERE u.entity IN (" . getEntity('user') . ")";
        $sql .= " AND u.accountancy_code <> ''";
//print $sql;
        if (strlen(trim($search_subaccount))) {
            $lengthpaddingaccount = 0;
            if (getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT')) {
                $lengthpaddingaccount = getDolGlobalInt('ACCOUNTING_LENGTH_AACCOUNT');
            }
            $search_subaccount_tmp = $search_subaccount;
            $weremovedsomezero = 0;
            if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
                for ($i = 0; $i < $lengthpaddingaccount; $i++) {
                    if (preg_match('/0$/', $search_subaccount_tmp)) {
                        $weremovedsomezero++;
                        $search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
                    }
                }
            }

            //var_dump($search_subaccount); exit;
            if ($search_subaccount_tmp) {
                if ($weremovedsomezero) {
                    $search_subaccount_tmp_clean = $search_subaccount_tmp;
                    $search_subaccount_clean = $search_subaccount;
                    $startchar = '%';
                    if (strpos($search_subaccount_tmp, '^') === 0) {
                        $startchar = '';
                        $search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
                        $search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
                    }
                    $sql .= " AND (u.accountancy_code LIKE '" . $db->escape($startchar . $search_subaccount_tmp_clean) . "'";
                    $sql .= " OR u.accountancy_code LIKE '" . $db->escape($startchar . $search_subaccount_clean) . "%')";
                } else {
                    $sql .= natural_search("u.accountancy_code", $search_subaccount_tmp);
                }
            }
        }
        if (strlen(trim($search_label))) {
            $sql .= natural_search("u.lastname", $search_label);
        }
        if (!empty($search_type) && $search_type >= 0) {
            $sql .= " HAVING type LIKE '" . $db->escape($search_type) . "'";
        }

        $sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            $resql = $db->query($sql);
            $nbtotalofrecords = $db->num_rows($resql);
            if (($page * $limit) > $nbtotalofrecords) { // if total resultset is smaller then paging size (filtering), goto and load page 0
                $page = 0;
                $offset = 0;
            }
        }

        $sql .= $db->plimit($limit + 1, $offset);

        dol_syslog('accountancy/admin/subaccount.php:: $sql=' . $sql);
        $resql = $db->query($sql);

        if ($resql) {
            $num = $db->num_rows($resql);

            $param = '';
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }
            if ($search_subaccount) {
                $param .= '&search_subaccount=' . urlencode($search_subaccount);
            }
            if ($search_label) {
                $param .= '&search_label=' . urlencode($search_label);
            }
            if ($optioncss != '') {
                $param .= '&optioncss=' . urlencode($optioncss);
            }

            // List of mass actions available
            $arrayofmassactions = [];

            print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="action" value="list">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
            print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

            print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit, 0, 0, 1);

            print '<div class="info">' . $langs->trans("WarningCreateSubAccounts") . '</div>';

            $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
            $selectedfields = ($mode != 'kanban' ? $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) : ''); // This also change content of $arrayfields
            $selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

            $moreforfilter = '';
            $massactionbutton = '';

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

            // Line for search fields
            print '<tr class="liste_titre_filter">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre center maxwidthsearch">';
                $searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
                print $searchpicto;
                print '</td>';
            }
            if (!empty($arrayfields['subaccount']['checked'])) {
                print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_subaccount" value="' . $search_subaccount . '"></td>';
            }
            if (!empty($arrayfields['label']['checked'])) {
                print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_label" value="' . $search_label . '"></td>';
            }
            if (!empty($arrayfields['type']['checked'])) {
                print '<td class="liste_titre center">' . $form->selectarray('search_type', ['1' => $langs->trans('Customer'), '2' => $langs->trans('Supplier'), '3' => $langs->trans('Employee')], $search_type, 1) . '</td>';
            }
            if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
                if (!empty($arrayfields['reconcilable']['checked'])) {
                    print '<td class="liste_titre">&nbsp;</td>';
                }
            }
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre maxwidthsearch">';
                $searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
                print $searchpicto;
                print '</td>';
            }
            print '</tr>';

            print '<tr class="liste_titre">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
            }
            if (!empty($arrayfields['subaccount']['checked'])) {
                print_liste_field_titre($arrayfields['subaccount']['label'], $_SERVER['PHP_SELF'], "subaccount", "", $param, '', $sortfield, $sortorder);
            }
            if (!empty($arrayfields['label']['checked'])) {
                print_liste_field_titre($arrayfields['label']['label'], $_SERVER['PHP_SELF'], "label", "", $param, '', $sortfield, $sortorder);
            }
            if (!empty($arrayfields['type']['checked'])) {
                print_liste_field_titre($arrayfields['type']['label'], $_SERVER['PHP_SELF'], "type", "", $param, '', $sortfield, $sortorder, 'center ');
            }
            if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
                if (!empty($arrayfields['reconcilable']['checked'])) {
                    print_liste_field_titre($arrayfields['reconcilable']['label'], $_SERVER['PHP_SELF'], 'reconcilable', '', $param, '', $sortfield, $sortorder, 'center ');
                }
            }
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
            }
            print "</tr>\n";

            $totalarray = [];
            $totalarray['nbfield'] = 0;
            $i = 0;
            while ($i < min($num, $limit)) {
                $obj = $db->fetch_object($resql);

                print '<tr class="oddeven">';

                // Action column
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    $e = '';

                    // Customer
                    if ($obj->type == 1) {
                        $e .= '<a class="editfielda" title="' . $langs->trans("Customer") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
                    } elseif ($obj->type == 2) {
                        // Supplier
                        $e .= '<a class="editfielda" title="' . $langs->trans("Supplier") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
                    } elseif ($obj->type == 3) {
                        // User - Employee
                        $e .= '<a class="editfielda" title="' . $langs->trans("Employee") . '" href="' . DOL_URL_ROOT . '/user/card.php?action=edit&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
                    }
                    print $e;
                    print '</td>' . "\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Account number
                if (!empty($arrayfields['subaccount']['checked'])) {
                    print "<td>";
                    print length_accounta($obj->subaccount);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Subaccount label
                if (!empty($arrayfields['label']['checked'])) {
                    print "<td>";
                    print dol_escape_htmltag($obj->label);
                    print "</td>\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                // Type
                if (!empty($arrayfields['type']['checked'])) {
                    print '<td class="center">';
                    $s = '';

                    // Customer
                    if ($obj->type == 1) {
                        $s .= '<a class="customer-back" style="padding-left: 6px; padding-right: 6px" title="' . $langs->trans("Customer") . '" href="' . DOL_URL_ROOT . '/comm/card.php?socid=' . $obj->rowid . '">' . $langs->trans("Customer") . '</a>';
                    } elseif ($obj->type == 2) {
                        // Supplier
                        $s .= '<a class="vendor-back" style="padding-left: 6px; padding-right: 6px" title="' . $langs->trans("Supplier") . '" href="' . DOL_URL_ROOT . '/fourn/card.php?socid=' . $obj->rowid . '">' . $langs->trans("Supplier") . '</a>';
                    } elseif ($obj->type == 3) {
                        // User - Employee
                        $s .= '<a class="user-back" style="padding-left: 6px; padding-right: 6px" title="' . $langs->trans("Employee") . '" href="' . DOL_URL_ROOT . '/user/card.php?id=' . $obj->rowid . '">' . $langs->trans("Employee") . '</a>';
                    }
                    print $s;
                    if ($obj->nature == 2) {
                        print ' <span class="warning bold">(' . $langs->trans("Prospect") . ')</span>';
                    }
                    print '</td>';
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
                    // Activated or not reconciliation on accounting account
                    if (!empty($arrayfields['reconcilable']['checked'])) {
                        print '<td class="center">';
                        if (empty($obj->reconcilable)) {
                            print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=enable&mode=1&token=' . newToken() . '">';
                            print img_picto($langs->trans("Disabled"), 'switch_off');
                            print '</a>';
                        } else {
                            print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?id=' . $obj->rowid . '&action=disable&mode=1&token=' . newToken() . '">';
                            print img_picto($langs->trans("Activated"), 'switch_on');
                            print '</a>';
                        }
                        print '</td>';
                        if (!$i) {
                            $totalarray['nbfield']++;
                        }
                    }
                }

                // Action column
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    $e = '';

                    // Customer
                    if ($obj->type == 1) {
                        $e .= '<a class="editfielda" title="' . $langs->trans("Customer") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
                    } elseif ($obj->type == 2) {
                        // Supplier
                        $e .= '<a class="editfielda" title="' . $langs->trans("Supplier") . '" href="' . DOL_URL_ROOT . '/societe/card.php?action=edit&token=' . newToken() . '&socid=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
                    } elseif ($obj->type == 3) {
                        // User - Employee
                        $e .= '<a class="editfielda" title="' . $langs->trans("Employee") . '" href="' . DOL_URL_ROOT . '/user/card.php?action=edit&token=' . newToken() . '&id=' . $obj->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF']) . '">' . img_edit() . '</a>';
                    }
                    print $e;
                    print '</td>' . "\n";
                    if (!$i) {
                        $totalarray['nbfield']++;
                    }
                }

                print '</tr>' . "\n";
                $i++;
            }

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

            $parameters = ['arrayfields' => $arrayfields, 'sql' => $sql];
            $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            print "</table>";
            print "</div>";

            print '</form>';
        } else {
            dol_print_error($db);
        }

// End of page
        llxFooter();
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
        global $conf, $langs, $db;
        global $form, $mysoc;
        global $region_id;
        global $elementList, $sourceList, $localtax_typeList;
        global $bc;

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