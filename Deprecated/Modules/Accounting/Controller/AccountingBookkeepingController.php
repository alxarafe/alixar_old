<?php
/* Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2020  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2024  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2016       Neil Orley              <neil.orley@oeris.fr>
 * Copyright (C) 2016-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2022  		Progiseize         		<a.bisotti@progiseize.fr>
 * Copyright (C) 2022  		Lionel Vessiller        <lvessiller@open-dsi.fr>
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
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancyexport.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

use AccountancyExport;
use AccountingAccount;
use BookKeeping;
use BookKeepingLine;
use DoliCore\Base\DolibarrController;
use Form;
use FormAccounting;
use FormFile;
use FormOther;

class AccountingBookkeepingController extends DolibarrController
{
    public function index()
    {
        $this->list();
    }

    /**
     *  \file       htdocs/accountancy/bookkeeping/balance.php
     *  \ingroup    Accountancy (Double entries)
     *  \brief      Balance of book keeping
     */
    public function balance()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("accountancy", "compta"));

        $action = GETPOST('action', 'aZ09');
        $optioncss = GETPOST('optioncss', 'alpha');
        $type = GETPOST('type', 'alpha');
        if ($type == 'sub') {
            $context_default = 'balancesubaccountlist';
        } else {
            $context_default = 'balancelist';
        }
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $context_default;
        $show_subgroup = GETPOST('show_subgroup', 'alpha');
        $search_date_start = dol_mktime(0, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
        $search_date_end = dol_mktime(23, 59, 59, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));
        $search_ledger_code = GETPOST('search_ledger_code', 'array');
        $search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
        if ($search_accountancy_code_start == - 1) {
            $search_accountancy_code_start = '';
        }
        $search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
        if ($search_accountancy_code_end == - 1) {
            $search_accountancy_code_end = '';
        }
        $search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
            $page = 0;
        }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if ($sortorder == "") {
            $sortorder = "ASC";
        }
        if ($sortfield == "") {
            $sortfield = "t.numero_compte";
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $object = new BookKeeping($db);
        $hookmanager->initHooks(array($contextpage));  // Note that conf->hooks_modules contains array

        $formaccounting = new FormAccounting($db);
        $formother = new FormOther($db);
        $form = new Form($db);

        if (empty($search_date_start) && !GETPOSTISSET('formfilteraction')) {
            $sql = "SELECT date_start, date_end from " . MAIN_DB_PREFIX . "accounting_fiscalyear ";
            $sql .= " WHERE date_start < '" . $db->idate(dol_now()) . "' AND date_end > '" . $db->idate(dol_now()) . "'";
            $sql .= $db->plimit(1);
            $res = $db->query($sql);

            if ($res->num_rows > 0) {
                $fiscalYear = $db->fetch_object($res);
                $search_date_start = strtotime($fiscalYear->date_start);
                $search_date_end = strtotime($fiscalYear->date_end);
            } else {
                $month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
                $year_start = dol_print_date(dol_now(), '%Y');
                if (dol_print_date(dol_now(), '%m') < $month_start) {
                    $year_start--; // If current month is lower that starting fiscal month, we start last year
                }
                $year_end = $year_start + 1;
                $month_end = $month_start - 1;
                if ($month_end < 1) {
                    $month_end = 12;
                    $year_end--;
                }
                $search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
                $search_date_end = dol_get_last_day($year_end, $month_end);
            }
        }

        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }

        /*
         * Action
         */

        $param = '';

        $parameters = array();
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $show_subgroup = '';
                $search_date_start = '';
                $search_date_end = '';
                $search_date_startyear = '';
                $search_date_startmonth = '';
                $search_date_startday = '';
                $search_date_endyear = '';
                $search_date_endmonth = '';
                $search_date_endday = '';
                $search_accountancy_code_start = '';
                $search_accountancy_code_end = '';
                $search_not_reconciled = '';
                $search_ledger_code = array();
                $filter = array();
            }

            // Must be after the remove filter action, before the export.
            $filter = array();

            if (!empty($search_date_start)) {
                $filter['t.doc_date>='] = $search_date_start;
                $param .= '&date_startmonth=' . GETPOSTINT('date_startmonth') . '&date_startday=' . GETPOSTINT('date_startday') . '&date_startyear=' . GETPOSTINT('date_startyear');
            }
            if (!empty($search_date_end)) {
                $filter['t.doc_date<='] = $search_date_end;
                $param .= '&date_endmonth=' . GETPOSTINT('date_endmonth') . '&date_endday=' . GETPOSTINT('date_endday') . '&date_endyear=' . GETPOSTINT('date_endyear');
            }
            if (!empty($search_doc_date)) {
                $filter['t.doc_date'] = $search_doc_date;
                $param .= '&doc_datemonth=' . GETPOSTINT('doc_datemonth') . '&doc_dateday=' . GETPOSTINT('doc_dateday') . '&doc_dateyear=' . GETPOSTINT('doc_dateyear');
            }
            if (!empty($search_accountancy_code_start)) {
                if ($type == 'sub') {
                    $filter['t.subledger_account>='] = $search_accountancy_code_start;
                } else {
                    $filter['t.numero_compte>='] = $search_accountancy_code_start;
                }
                $param .= '&search_accountancy_code_start=' . urlencode($search_accountancy_code_start);
            }
            if (!empty($search_accountancy_code_end)) {
                if ($type == 'sub') {
                    $filter['t.subledger_account<='] = $search_accountancy_code_end;
                } else {
                    $filter['t.numero_compte<='] = $search_accountancy_code_end;
                }
                $param .= '&search_accountancy_code_end=' . urlencode($search_accountancy_code_end);
            }
            if (!empty($search_ledger_code)) {
                $filter['t.code_journal'] = $search_ledger_code;
                foreach ($search_ledger_code as $code) {
                    $param .= '&search_ledger_code[]=' . urlencode($code);
                }
            }
            if (!empty($search_not_reconciled)) {
                $filter['t.reconciled_option'] = $search_not_reconciled;
                $param .= '&search_not_reconciled=' . urlencode($search_not_reconciled);
            }

            // param with type of list
            $url_param = substr($param, 1); // remove first "&"
            if (!empty($type)) {
                $param = '&type=' . $type . $param;
            }
        }

        if ($action == 'export_csv') {
            $sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

            $filename = 'balance';
            $type_export = 'balance';
            include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

            if ($type == 'sub') {
                $result = $object->fetchAllBalance($sortorder, $sortfield, $limit, 0, $filter, 'AND', 1);
            } else {
                $result = $object->fetchAllBalance($sortorder, $sortfield, $limit, 0, $filter);
            }
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            }

            foreach ($object->lines as $line) {
                if ($type == 'sub') {
                    print '"' . length_accounta($line->subledger_account) . '"' . $sep;
                    print '"' . $line->subledger_label . '"' . $sep;
                } else {
                    print '"' . length_accountg($line->numero_compte) . '"' . $sep;
                    print '"' . $object->get_compte_desc($line->numero_compte) . '"' . $sep;
                }
                print '"' . price($line->debit) . '"' . $sep;
                print '"' . price($line->credit) . '"' . $sep;
                print '"' . price($line->debit - $line->credit) . '"' . $sep;
                print "\n";
            }

            exit;
        }


        /*
         * View
         */

        if ($type == 'sub') {
            $title_page = $langs->trans("AccountBalanceSubAccount");
        } else {
            $title_page = $langs->trans("AccountBalance");
        }

        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';

        llxHeader('', $title_page, $help_url);


        if ($action != 'export_csv') {
            // List
            $nbtotalofrecords = '';
            if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
                if ($type == 'sub') {
                    $nbtotalofrecords = $object->fetchAllBalance($sortorder, $sortfield, 0, 0, $filter, 'AND', 1);
                } else {
                    $nbtotalofrecords = $object->fetchAllBalance($sortorder, $sortfield, 0, 0, $filter);
                }

                if ($nbtotalofrecords < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }

            if ($type == 'sub') {
                $result = $object->fetchAllBalance($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 1);
            } else {
                $result = $object->fetchAllBalance($sortorder, $sortfield, $limit, $offset, $filter);
            }

            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            }

            print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" id="action" value="list">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="type" value="' . $type . '">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
            print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';
            print '<input type="hidden" name="page" value="' . $page . '">';


            $parameters = array();
            $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

            if ($reshook < 0) {
                setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
            }

            $newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

            if (empty($reshook)) {
                $newcardbutton = '<input type="button" id="exportcsvbutton" name="exportcsvbutton" class="butAction" value="' . $langs->trans("Export") . ' (' . getDolGlobalString('ACCOUNTING_EXPORT_FORMAT') . ')" />';

                print '<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery("#exportcsvbutton").click(function(event) {
				event.preventDefault();
				console.log("Set action to export_csv");
				jQuery("#action").val("export_csv");
				jQuery("#searchFormList").submit();
				jQuery("#action").val("list");
			});
		});
		</script>';

                if ($type == 'sub') {
                    $newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance') . " - " . $langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
                    $newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance') . " - " . $langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?type=sub&' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
                } else {
                    $newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance') . " - " . $langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
                    $newcardbutton .= dolGetButtonTitle($langs->trans('AccountBalance') . " - " . $langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/balance.php?type=sub&' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
                }
                $newcardbutton .= dolGetButtonTitleSeparator();
                $newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', DOL_URL_ROOT . '/accountancy/bookkeeping/card.php?action=create');
            }
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }

            print_barre_liste($title_page, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $result, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

            $selectedfields = '';

            // Warning to explain why list of record is not consistent with the other list view (missing a lot of lines)
            if ($type == 'sub') {
                print info_admin($langs->trans("WarningRecordWithoutSubledgerAreExcluded"));
            }

            $moreforfilter = '';

            $moreforfilter .= '<div class="divsearchfield">';
            $moreforfilter .= $langs->trans('DateStart') . ': ';
            $moreforfilter .= $form->selectDate($search_date_start ? $search_date_start : -1, 'date_start', 0, 0, 1, '', 1, 0);
            $moreforfilter .= $langs->trans('DateEnd') . ': ';
            $moreforfilter .= $form->selectDate($search_date_end ? $search_date_end : -1, 'date_end', 0, 0, 1, '', 1, 0);
            $moreforfilter .= '</div>';

            $moreforfilter .= '<div class="divsearchfield">';
            $moreforfilter .= '<label for="show_subgroup">' . $langs->trans('ShowSubtotalByGroup') . '</label>: ';
            $moreforfilter .= '<input type="checkbox" name="show_subgroup" id="show_subgroup" value="show_subgroup"' . ($show_subgroup == 'show_subgroup' ? ' checked' : '') . '>';
            $moreforfilter .= '</div>';

            $moreforfilter .= '<div class="divsearchfield">';
            $moreforfilter .= $langs->trans("Journals") . ': ';
            $moreforfilter .= $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1);
            $moreforfilter .= '</div>';

            //$moreforfilter .= '<br>';
            $moreforfilter .= '<div class="divsearchfield">';
            // Accountancy account
            $moreforfilter .= $langs->trans('AccountAccounting') . ': ';
            if ($type == 'sub') {
                $moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), 'maxwidth200');
            } else {
                $moreforfilter .= $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), array(), 1, 1, 'maxwidth200', 'accounts');
            }
            $moreforfilter .= ' ';
            if ($type == 'sub') {
                $moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), 'maxwidth200');
            } else {
                $moreforfilter .= $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), array(), 1, 1, 'maxwidth200', 'accounts');
            }
            $moreforfilter .= '</div>';

            if (getDolGlobalString('ACCOUNTING_ENABLE_LETTERING')) {
                $moreforfilter .= '<div class="divsearchfield">';
                $moreforfilter .= '<label for="notreconciled">' . $langs->trans('NotReconciled') . '</label>: ';
                $moreforfilter .= '<input type="checkbox" name="search_not_reconciled" id="notreconciled" value="notreconciled"' . ($search_not_reconciled == 'notreconciled' ? ' checked' : '') . '>';
                $moreforfilter .= '</div>';
            }

            if (!empty($moreforfilter)) {
                print '<div class="liste_titre liste_titre_bydiv centpercent">';
                print $moreforfilter;
                $parameters = array();
                $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
                print $hookmanager->resPrint;
                print '</div>';
            }


            $colspan = (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE') ? 5 : 4);

            print '<table class="liste ' . ($moreforfilter ? "listwithfilterbefore" : "") . '">';

            print '<tr class="liste_titre_filter">';

            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre maxwidthsearch">';
                $searchpicto = $form->showFilterButtons();
                print $searchpicto;
                print '</td>';
            }

            print '<td class="liste_titre" colspan="' . $colspan . '">';
            print '</td>';

            // Fields from hook
            $parameters = array();
            $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="liste_titre maxwidthsearch">';
                $searchpicto = $form->showFilterButtons();
                print $searchpicto;
                print '</td>';
            }
            print '</tr>' . "\n";

            print '<tr class="liste_titre">';
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
            }
            print_liste_field_titre("AccountAccounting", $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
            // TODO : Retrieve the type of third party: Customer / Supplier / Employee
            //if ($type == 'sub') {
            //  print_liste_field_titre("Type", $_SERVER['PHP_SELF'], "t.type", "", $param, "", $sortfield, $sortorder);
            //}
            if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                print_liste_field_titre("OpeningBalance", $_SERVER['PHP_SELF'], "", $param, "", 'class="right"', $sortfield, $sortorder);
            }
            print_liste_field_titre("AccountingDebit", $_SERVER['PHP_SELF'], "t.debit", "", $param, 'class="right"', $sortfield, $sortorder);
            print_liste_field_titre("AccountingCredit", $_SERVER['PHP_SELF'], "t.credit", "", $param, 'class="right"', $sortfield, $sortorder);
            print_liste_field_titre("Balance", $_SERVER['PHP_SELF'], "", $param, "", 'class="right"', $sortfield, $sortorder);

            // Hook fields
            $parameters = array('param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
            $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print getTitleFieldOfList($selectedfields, 0, $_SERVER['PHP_SELF'], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ') . "\n";
            }
            print '</tr>' . "\n";

            $total_debit = 0;
            $total_credit = 0;
            $sous_total_debit = 0;
            $sous_total_credit = 0;
            $total_opening_balance = 0;
            $sous_total_opening_balance = 0;
            $displayed_account = "";

            $accountingaccountstatic = new AccountingAccount($db);

            // TODO Debug - This feature is dangerous, it takes all the entries and adds all the accounts
            // without time and class limits (Class 6 and 7 accounts ???) and does not take into account the "a-nouveau" journal.
            if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                $sql = "SELECT t.numero_compte, (SUM(t.debit) - SUM(t.credit)) as opening_balance";
                $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as t";
                $sql .= " WHERE t.entity = " . $conf->entity;        // Never do sharing into accounting features
                $sql .= " AND t.doc_date < '" . $db->idate($search_date_start) . "'";
                $sql .= " GROUP BY t.numero_compte";

                $resql = $db->query($sql);
                $nrows = $resql->num_rows;
                $opening_balances = array();
                for ($i = 0; $i < $nrows; $i++) {
                    $arr = $resql->fetch_array();
                    $opening_balances["'" . $arr['numero_compte'] . "'"] = $arr['opening_balance'];
                }
            }

            foreach ($object->lines as $line) {
                // reset before the fetch (in case of the fetch fails)
                $accountingaccountstatic->id = 0;
                $accountingaccountstatic->account_number = '';

                if ($type != 'sub') {
                    $accountingaccountstatic->fetch(null, $line->numero_compte, true);
                    if (!empty($accountingaccountstatic->account_number)) {
                        $accounting_account = $accountingaccountstatic->getNomUrl(0, 1, 1);
                    } else {
                        $accounting_account = length_accountg($line->numero_compte);
                    }
                }

                $link = '';
                $total_debit += $line->debit;
                $total_credit += $line->credit;
                $opening_balance = isset($opening_balances["'" . $line->numero_compte . "'"]) ? $opening_balances["'" . $line->numero_compte . "'"] : 0;
                $total_opening_balance += $opening_balance;

                $tmparrayforrootaccount = $object->getRootAccount($line->numero_compte);
                $root_account_description = $tmparrayforrootaccount['label'];
                $root_account_number = $tmparrayforrootaccount['account_number'];

                //var_dump($tmparrayforrootaccount);
                //var_dump($accounting_account);
                //var_dump($accountingaccountstatic);
                if (empty($accountingaccountstatic->label) && $accountingaccountstatic->id > 0) {
                    $link = '<a class="editfielda reposition" href="' . DOL_URL_ROOT . '/accountancy/admin/card.php?action=update&token=' . newToken() . '&id=' . $accountingaccountstatic->id . '">' . img_edit() . '</a>';
                } elseif ($accounting_account == 'NotDefined') {
                    $link = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/card.php?action=create&token=' . newToken() . '&accountingaccount=' . length_accountg($line->numero_compte) . '">' . img_edit_add() . '</a>';
                } elseif (empty($tmparrayforrootaccount['label'])) {
                    // $tmparrayforrootaccount['label'] not defined = the account has not parent with a parent.
                    // This is useless, we should not create a new account when an account has no parent, we must edit it to fix its parent.
                    // BUG 1: Accounts on level root or level 1 must not have a parent 2 level higher, so should not show a link to create another account.
                    // BUG 2: Adding a link to create a new accounting account here is useless because it is not add as parent of the orphelin.
                    //$link = '<a href="' . DOL_URL_ROOT . '/accountancy/admin/card.php?action=create&token=' . newToken() . '&accountingaccount=' . length_accountg($line->numero_compte) . '">' . img_edit_add() . '</a>';
                }

                if (!empty($show_subgroup)) {
                    // Show accounting account
                    if (empty($displayed_account) || $root_account_number != $displayed_account) {
                        // Show subtotal per accounting account
                        if ($displayed_account != "") {
                            print '<tr class="liste_total">';
                            print '<td class="right">' . $langs->trans("SubTotal") . ':</td>';
                            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                                print '<td></td>';
                            }
                            if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                                print '<td class="right nowraponall amount">' . price($sous_total_opening_balance) . '</td>';
                            }
                            print '<td class="right nowraponall amount">' . price($sous_total_debit) . '</td>';
                            print '<td class="right nowraponall amount">' . price($sous_total_credit) . '</td>';
                            if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                                print '<td class="right nowraponall amount">' . price(price2num($sous_total_opening_balance + $sous_total_debit - $sous_total_credit)) . '</td>';
                            } else {
                                print '<td class="right nowraponall amount">' . price(price2num($sous_total_debit - $sous_total_credit)) . '</td>';
                            }
                            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                                print "<td></td>\n";
                            }
                            print '</tr>';
                        }

                        // Show first line of a break
                        print '<tr class="trforbreak">';
                        print '<td colspan="' . ($colspan + 1) . '" class="tdforbreak">' . $root_account_number . ($root_account_description ? ' - ' . $root_account_description : '') . '</td>';
                        print '</tr>';

                        $displayed_account = $root_account_number;
                        $sous_total_debit = 0;
                        $sous_total_credit = 0;
                        $sous_total_opening_balance = 0;
                    }
                }

                print '<tr class="oddeven">';

                // Action column
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    print $link;
                    print '</td>';
                }

                // Accounting account
                if ($type == 'sub') {
                    print '<td>' . $line->subledger_account . ' <span class="opacitymedium">(' . $line->subledger_label . ')</span></td>';
                } else {
                    print '<td>' . $accounting_account . '</td>';
                }

                // Type
                // TODO Retrieve the type of third party: Customer / Supplier / Employee
                //if ($type == 'sub') {
                //  print '<td></td>';
                //}

                if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                    print '<td class="right nowraponall amount">' . price(price2num($opening_balance, 'MT')) . '</td>';
                }

                $urlzoom = '';
                if ($type == 'sub') {
                    if ($line->subledger_account) {
                        $urlzoom = DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&search_accountancy_code_start=' . urlencode($line->subledger_account) . '&search_accountancy_code_end=' . urlencode($line->subledger_account);
                        if (GETPOSTISSET('date_startmonth')) {
                            $urlzoom .= '&search_date_startmonth=' . GETPOSTINT('date_startmonth') . '&search_date_startday=' . GETPOSTINT('date_startday') . '&search_date_startyear=' . GETPOSTINT('date_startyear');
                        }
                        if (GETPOSTISSET('date_endmonth')) {
                            $urlzoom .= '&search_date_endmonth=' . GETPOSTINT('date_endmonth') . '&search_date_endday=' . GETPOSTINT('date_endday') . '&search_date_endyear=' . GETPOSTINT('date_endyear');
                        }
                    }
                } else {
                    if ($line->numero_compte) {
                        $urlzoom = DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?search_accountancy_code_start=' . urlencode($line->numero_compte) . '&search_accountancy_code_end=' . urlencode($line->numero_compte);
                        if (GETPOSTISSET('date_startmonth')) {
                            $urlzoom .= '&search_date_startmonth=' . GETPOSTINT('date_startmonth') . '&search_date_startday=' . GETPOSTINT('date_startday') . '&search_date_startyear=' . GETPOSTINT('date_startyear');
                        }
                        if (GETPOSTISSET('date_endmonth')) {
                            $urlzoom .= '&search_date_endmonth=' . GETPOSTINT('date_endmonth') . '&search_date_endday=' . GETPOSTINT('date_endday') . '&search_date_endyear=' . GETPOSTINT('date_endyear');
                        }
                    }
                }
                // Debit
                print '<td class="right nowraponall amount"><a href="' . $urlzoom . '">' . price(price2num($line->debit, 'MT')) . '</a></td>';
                // Credit
                print '<td class="right nowraponall amount"><a href="' . $urlzoom . '">' . price(price2num($line->credit, 'MT')) . '</a></td>';

                if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                    print '<td class="right nowraponall amount">' . price(price2num($opening_balance + $line->debit - $line->credit, 'MT')) . '</td>';
                } else {
                    print '<td class="right nowraponall amount">' . price(price2num($line->debit - $line->credit, 'MT')) . '</td>';
                }

                // Action column
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print '<td class="center">';
                    print $link;
                    print '</td>';
                }

                print "</tr>\n";

                // Records the sub-total
                $sous_total_debit += $line->debit;
                $sous_total_credit += $line->credit;
                $sous_total_opening_balance += $opening_balance;
            }

            if (!empty($show_subgroup)) {
                print '<tr class="liste_total">';
                // Action column
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print "<td></td>\n";
                }
                print '<td class="right">' . $langs->trans("SubTotal") . ':</td>';
                if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                    print '<td class="right nowraponall amount">' . price(price2num($sous_total_opening_balance, 'MT')) . '</td>';
                }
                print '<td class="right nowraponall amount">' . price(price2num($sous_total_debit, 'MT')) . '</td>';
                print '<td class="right nowraponall amount">' . price(price2num($sous_total_credit, 'MT')) . '</td>';
                if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                    print '<td class="right nowraponall amount">' . price(price2num($sous_total_opening_balance + $sous_total_debit - $sous_total_credit, 'MT')) . '</td>';
                } else {
                    print '<td class="right nowraponall amount">' . price(price2num($sous_total_debit - $sous_total_credit, 'MT')) . '</td>';
                }
                // Action column
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print "<td></td>\n";
                }
                print '</tr>';
            }

            print '<tr class="liste_total">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print "<td></td>\n";
            }
            print '<td class="right">' . $langs->trans("AccountBalance") . ':</td>';
            if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                print '<td class="nowrap right">' . price(price2num($total_opening_balance, 'MT')) . '</td>';
            }
            print '<td class="right nowraponall amount">' . price(price2num($total_debit, 'MT')) . '</td>';
            print '<td class="right nowraponall amount">' . price(price2num($total_credit, 'MT')) . '</td>';
            if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                print '<td class="right nowraponall amount">' . price(price2num($total_opening_balance + $total_debit - $total_credit, 'MT')) . '</td>';
            } else {
                print '<td class="right nowraponall amount">' . price(price2num($total_debit - $total_credit, 'MT')) . '</td>';
            }
            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print "<td></td>\n";
            }
            print '</tr>';

            // Accounting result
            if (getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT')) {
                print '<tr class="liste_total">';
                if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print "<td></td>\n";
                }
                print '<td class="right">' . $langs->trans("AccountingResult") . ':</td>';
                if (getDolGlobalString('ACCOUNTANCY_SHOW_OPENING_BALANCE')) {
                    print '<td></td>';
                }

                $accountingResult = $object->accountingResult($search_date_start, $search_date_end);
                if ($accountingResult < 0) {
                    $accountingResultDebit = price(price2num(abs($accountingResult), 'MT'));
                    $accountingResultClassCSS = ' error';
                } else {
                    $accountingResultCredit = price(price2num($accountingResult, 'MT'));
                    $accountingResultClassCSS = ' green';
                }
                print '<td class="right nowraponall amount' . $accountingResultClassCSS . '">' . $accountingResultDebit . '</td>';
                print '<td class="right nowraponall amount' . $accountingResultClassCSS . '">' . $accountingResultCredit . '</td>';

                print '<td></td>';
                if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                    print "<td></td>\n";
                }
                print '</tr>';
            }

            $parameters = array();
            $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            print "</table>";
            print '</form>';
        }

// End of page
        llxFooter();
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/bookkeeping/card.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page to show book-entry
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
        $langs->loadLangs(array("accountancy", "bills", "compta"));

        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'aZ09');

        $optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

        $id = GETPOSTINT('id'); // id of record
        $mode = GETPOST('mode', 'aZ09'); // '' or '_tmp'
        $piece_num = GETPOSTINT("piece_num"); // id of transaction (several lines share the same transaction id)

        $accountingaccount = new AccountingAccount($db);
        $accountingjournal = new AccountingJournal($db);

        $accountingaccount_number = GETPOST('accountingaccount_number', 'alphanohtml');
        $accountingaccount->fetch(null, $accountingaccount_number, true);
        $accountingaccount_label = $accountingaccount->label;

        $journal_code = GETPOST('code_journal', 'alpha');
        $accountingjournal->fetch(null, $journal_code);
        $journal_label = $accountingjournal->label;

        $subledger_account = GETPOST('subledger_account', 'alphanohtml');
        if ($subledger_account == -1) {
            $subledger_account = null;
        }
        $subledger_label = GETPOST('subledger_label', 'alphanohtml');

        $label_operation = GETPOST('label_operation', 'alphanohtml');
        $debit = (float) price2num(GETPOST('debit', 'alpha'));
        $credit = (float) price2num(GETPOST('credit', 'alpha'));

        $save = GETPOST('save', 'alpha');
        if (!empty($save)) {
            $action = 'add';
        }
        $update = GETPOST('update', 'alpha');
        if (!empty($update)) {
            $action = 'confirm_update';
        }

        $object = new BookKeeping($db);

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        if ($cancel) {
            header("Location: " . DOL_URL_ROOT . '/accountancy/bookkeeping/list.php');
            exit;
        }

        if ($action == "confirm_update") {
            $error = 0;

            if (((float) $debit != 0.0) && ((float) $credit != 0.0)) {
                $error++;
                setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
                $action = 'update';
            }
            if (empty($accountingaccount_number) || $accountingaccount_number == '-1') {
                $error++;
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountAccountingShort")), null, 'errors');
                $action = 'update';
            }

            if (!$error) {
                $object = new BookKeeping($db);

                $result = $object->fetch($id, null, $mode);
                if ($result < 0) {
                    $error++;
                    setEventMessages($object->error, $object->errors, 'errors');
                } else {
                    $object->numero_compte = $accountingaccount_number;
                    $object->subledger_account = $subledger_account;
                    $object->subledger_label = $subledger_label;
                    $object->label_compte = $accountingaccount_label;
                    $object->label_operation = $label_operation;
                    $object->debit = $debit;
                    $object->credit = $credit;

                    if ((float) $debit != 0.0) {
                        $object->montant = $debit; // deprecated
                        $object->amount = $debit;
                        $object->sens = 'D';
                    }
                    if ((float) $credit != 0.0) {
                        $object->montant = $credit; // deprecated
                        $object->amount = $credit;
                        $object->sens = 'C';
                    }

                    $result = $object->update($user, false, $mode);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                    } else {
                        if ($mode != '_tmp') {
                            setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
                        }

                        $debit = 0;
                        $credit = 0;

                        $action = '';
                    }
                }
            }
        } elseif ($action == "add") {
            $error = 0;

            if (((float) $debit != 0.0) && ((float) $credit != 0.0)) {
                $error++;
                setEventMessages($langs->trans('ErrorDebitCredit'), null, 'errors');
                $action = '';
            }
            if (empty($accountingaccount_number) || $accountingaccount_number == '-1') {
                $error++;
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("AccountAccountingShort")), null, 'errors');
                $action = '';
            }

            if (!$error) {
                $object = new BookKeeping($db);

                $object->numero_compte = $accountingaccount_number;
                $object->subledger_account = $subledger_account;
                $object->subledger_label = $subledger_label;
                $object->label_compte = $accountingaccount_label;
                $object->label_operation = $label_operation;
                $object->debit = $debit;
                $object->credit = $credit;
                $object->doc_date = (string) GETPOST('doc_date', 'alpha');
                $object->doc_type = (string) GETPOST('doc_type', 'alpha');
                $object->piece_num = $piece_num;
                $object->doc_ref = (string) GETPOST('doc_ref', 'alpha');
                $object->code_journal = $journal_code;
                $object->journal_label = $journal_label;
                $object->fk_doc = GETPOSTINT('fk_doc');
                $object->fk_docdet = GETPOSTINT('fk_docdet');

                if ((float) $debit != 0.0) {
                    $object->montant = $debit; // deprecated
                    $object->amount = $debit;
                    $object->sens = 'D';
                }

                if ((float) $credit != 0.0) {
                    $object->montant = $credit; // deprecated
                    $object->amount = $credit;
                    $object->sens = 'C';
                }

                $result = $object->createStd($user, false, $mode);
                if ($result < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                } else {
                    if ($mode != '_tmp') {
                        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
                    }

                    $debit = 0;
                    $credit = 0;

                    $action = '';
                }
            }
        } elseif ($action == "confirm_delete") {
            $object = new BookKeeping($db);

            $result = $object->fetch($id, null, $mode);
            $piece_num = $object->piece_num;

            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                $result = $object->delete($user, false, $mode);
                if ($result < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                }
            }
            $action = '';
        } elseif ($action == "confirm_create") {
            $error = 0;

            $object = new BookKeeping($db);

            if (!$journal_code || $journal_code == '-1') {
                setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Journal")), null, 'errors');
                $action = 'create';
                $error++;
            }
            if (!GETPOST('doc_ref', 'alpha')) {
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Piece")), null, 'errors');
                $action = 'create';
                $error++;
            }

            if (!$error) {
                $object->label_compte = '';
                $object->debit = 0;
                $object->credit = 0;
                $object->doc_date = $date_start = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
                $object->doc_type = GETPOST('doc_type', 'alpha');
                $object->piece_num = GETPOSTINT('next_num_mvt');
                $object->doc_ref = GETPOST('doc_ref', 'alpha');
                $object->code_journal = $journal_code;
                $object->journal_label = $journal_label;
                $object->fk_doc = 0;
                $object->fk_docdet = 0;
                $object->montant = 0; // deprecated
                $object->amount = 0;

                $result = $object->createStd($user, 0, $mode);
                if ($result < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                } else {
                    if ($mode != '_tmp') {
                        setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
                    }
                    $action = '';
                    $id = $object->id;
                    $piece_num = $object->piece_num;
                }
            }
        }

        if ($action == 'setdate') {
            $datedoc = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
            $result = $object->updateByMvt($piece_num, 'doc_date', $db->idate($datedoc), $mode);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                if ($mode != '_tmp') {
                    setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
                }
                $action = '';
            }
        }

        if ($action == 'setjournal') {
            $result = $object->updateByMvt($piece_num, 'code_journal', $journal_code, $mode);
            $result = $object->updateByMvt($piece_num, 'journal_label', $journal_label, $mode);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                if ($mode != '_tmp') {
                    setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
                }
                $action = '';
            }
        }

        if ($action == 'setdocref') {
            $refdoc = GETPOST('doc_ref', 'alpha');
            $result = $object->updateByMvt($piece_num, 'doc_ref', $refdoc, $mode);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                if ($mode != '_tmp') {
                    setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
                }
                $action = '';
            }
        }

// Validate transaction
        if ($action == 'valid') {
            $result = $object->transformTransaction(0, $piece_num);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                header("Location: list.php?sortfield=t.piece_num&sortorder=asc");
                exit;
            }
        }


        /*
         * View
         */

        $form = new Form($db);
        $formaccounting = new FormAccounting($db);

        $title = $langs->trans("CreateMvts");
        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
        llxHeader('', $title, $help_url);

// Confirmation to delete the command
        if ($action == 'delete') {
            $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?id=' . $id . '&mode=' . $mode, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'confirm_delete', '', 0, 1);
            print $formconfirm;
        }

        if ($action == 'create') {
            print load_fiche_titre($title);

            $object = new BookKeeping($db);
            $next_num_mvt = $object->getNextNumMvt('_tmp');

            if (empty($next_num_mvt)) {
                dol_print_error(null, 'Failed to get next piece number');
            }

            print '<form action="' . $_SERVER['PHP_SELF'] . '" name="create_mvt" method="POST">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="confirm_create">' . "\n";
            print '<input type="hidden" name="next_num_mvt" value="' . $next_num_mvt . '">' . "\n";
            print '<input type="hidden" name="mode" value="_tmp">' . "\n";

            print dol_get_fiche_head();

            print '<table class="border centpercent">';

            /*print '<tr>';
            print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("NumPiece") . '</td>';
            print '<td>' . $next_num_mvt . '</td>';
            print '</tr>';*/

            print '<tr>';
            print '<td class="titlefieldcreate fieldrequired">' . $langs->trans("Docdate") . '</td>';
            print '<td>';
            print $form->selectDate('', 'doc_date', 0, 0, 0, "create_mvt", 1, 1);
            print '</td>';
            print '</tr>';

            print '<tr>';
            print '<td class="fieldrequired">' . $langs->trans("Codejournal") . '</td>';
            print '<td>' . $formaccounting->select_journal($journal_code, 'code_journal', 0, 0, 1, 1) . '</td>';
            print '</tr>';

            print '<tr>';
            print '<td class="fieldrequired">' . $langs->trans("Piece") . '</td>';
            print '<td><input type="text" class="minwidth200" name="doc_ref" value="' . GETPOST('doc_ref', 'alpha') . '"></td>';
            print '</tr>';

            /*
            print '<tr>';
            print '<td>' . $langs->trans("Doctype") . '</td>';
            print '<td><input type="text" class="minwidth200 name="doc_type" value=""/></td>';
            print '</tr>';
            */

            print '</table>';

            print dol_get_fiche_end();

            print $form->buttonsSaveCancel("Create");

            print '</form>';
        } else {
            $object = new BookKeeping($db);
            $result = $object->fetchPerMvt($piece_num, $mode);
            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            }

            if (!empty($object->piece_num)) {
                $backlink = '<a href="' . DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?restore_lastsearch_values=1">' . $langs->trans('BackToList') . '</a>';

                if ($mode == '_tmp') {
                    print load_fiche_titre($langs->trans("CreateMvts"), $backlink);
                } else {
                    print load_fiche_titre($langs->trans("UpdateMvts"), $backlink);
                }

                $head = array();
                $h = 0;
                $head[$h][0] = $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . ($mode ? '&mode=' . $mode : '');
                $head[$h][1] = $langs->trans("Transaction");
                $head[$h][2] = 'transaction';
                $h++;

                print dol_get_fiche_head($head, 'transaction', '', -1);

                //dol_banner_tab($object, '', $backlink);

                print '<div class="fichecenter">';
                print '<div class="fichehalfleft">';

                print '<div class="underbanner clearboth"></div>';
                print '<table class="border tableforfield" width="100%">';

                // Account movement
                print '<tr>';
                print '<td class="titlefield">' . $langs->trans("NumMvts") . '</td>';
                print '<td>' . ($mode == '_tmp' ? '<span class="opacitymedium" title="Id tmp ' . $object->piece_num . '">' . $langs->trans("Draft") . '</span>' : $object->piece_num) . '</td>';
                print '</tr>';

                // Date
                print '<tr><td>';
                print '<table class="nobordernopadding centpercent"><tr><td>';
                print $langs->trans('Docdate');
                print '</td>';
                if ($action != 'editdate') {
                    print '<td class="right"><a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=editdate&token=' . newToken() . '&piece_num=' . urlencode((string) ($object->piece_num)) . '&mode=' . urlencode((string) ($mode)) . '">' . img_edit($langs->transnoentitiesnoconv('SetDate'), 1) . '</a></td>';
                }
                print '</tr></table>';
                print '</td><td colspan="3">';
                if ($action == 'editdate') {
                    print '<form name="setdate" action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
                    if ($optioncss != '') {
                        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
                    }
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="setdate">';
                    print '<input type="hidden" name="mode" value="' . $mode . '">';
                    print $form->selectDate($object->doc_date ? $object->doc_date : - 1, 'doc_date', 0, 0, 0, "setdate");
                    print '<input type="submit" class="button button-edit" value="' . $langs->trans('Modify') . '">';
                    print '</form>';
                } else {
                    print $object->doc_date ? dol_print_date($object->doc_date, 'day') : '&nbsp;';
                }
                print '</td>';
                print '</tr>';

                // Journal
                print '<tr><td>';
                print '<table class="nobordernopadding" width="100%"><tr><td>';
                print $langs->trans('Codejournal');
                print '</td>';
                if ($action != 'editjournal') {
                    print '<td class="right"><a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=editjournal&token=' . newToken() . '&piece_num=' . urlencode((string) ($object->piece_num)) . '&mode=' . urlencode((string) ($mode)) . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a></td>';
                }
                print '</tr></table>';
                print '</td><td>';
                if ($action == 'editjournal') {
                    print '<form name="setjournal" action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
                    if ($optioncss != '') {
                        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
                    }
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="setjournal">';
                    print '<input type="hidden" name="mode" value="' . $mode . '">';
                    print $formaccounting->select_journal($object->code_journal, 'code_journal', 0, 0, array(), 1, 1);
                    print '<input type="submit" class="button button-edit" value="' . $langs->trans('Modify') . '">';
                    print '</form>';
                } else {
                    print $object->code_journal;
                }
                print '</td>';
                print '</tr>';

                // Ref document
                print '<tr><td>';
                print '<table class="nobordernopadding centpercent"><tr><td>';
                print $langs->trans('Piece');
                print '</td>';
                if ($action != 'editdocref') {
                    print '<td class="right"><a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=editdocref&token=' . newToken() . '&piece_num=' . urlencode((string) ($object->piece_num)) . '&mode=' . urlencode((string) ($mode)) . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a></td>';
                }
                print '</tr></table>';
                print '</td><td>';
                if ($action == 'editdocref') {
                    print '<form name="setdocref" action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
                    if ($optioncss != '') {
                        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
                    }
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="setdocref">';
                    print '<input type="hidden" name="mode" value="' . $mode . '">';
                    print '<input type="text" size="20" name="doc_ref" value="' . dol_escape_htmltag($object->doc_ref) . '">';
                    print '<input type="submit" class="button button-edit" value="' . $langs->trans('Modify') . '">';
                    print '</form>';
                } else {
                    print $object->doc_ref;
                }
                print '</td>';
                print '</tr>';

                print '</table>';

                print '</div>';

                print '<div class="fichehalfright">';

                print '<div class="underbanner clearboth"></div>';
                print '<table class="border tableforfield centpercent">';

                // Doc type
                if (!empty($object->doc_type)) {
                    print '<tr>';
                    print '<td class="titlefield">' . $langs->trans("Doctype") . '</td>';
                    print '<td>' . $object->doc_type . '</td>';
                    print '</tr>';
                }

                // Date document creation
                print '<tr>';
                print '<td class="titlefield">' . $langs->trans("DateCreation") . '</td>';
                print '<td>';
                print $object->date_creation ? dol_print_date($object->date_creation, 'day') : '&nbsp;';
                print '</td>';
                print '</tr>';

                // Don't show in tmp mode, inevitably empty
                if ($mode != "_tmp") {
                    // Date document export
                    print '<tr>';
                    print '<td class="titlefield">' . $langs->trans("DateExport") . '</td>';
                    print '<td>';
                    print $object->date_export ? dol_print_date($object->date_export, 'dayhour') : '&nbsp;';
                    print '</td>';
                    print '</tr>';

                    // Date document validation
                    print '<tr>';
                    print '<td class="titlefield">' . $langs->trans("DateValidation") . '</td>';
                    print '<td>';
                    print $object->date_validation ? dol_print_date($object->date_validation, 'dayhour') : '&nbsp;';
                    print '</td>';
                    print '</tr>';
                }

                // Validate
                /*
                print '<tr>';
                print '<td class="titlefield">' . $langs->trans("Status") . '</td>';
                print '<td>';
                    if (empty($object->validated)) {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $line->id . '&action=enable&token='.newToken().'">';
                        print img_picto($langs->trans("Disabled"), 'switch_off');
                        print '</a>';
                    } else {
                        print '<a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $line->id . '&action=disable&token='.newToken().'">';
                        print img_picto($langs->trans("Activated"), 'switch_on');
                        print '</a>';
                    }
                    print '</td>';
                print '</tr>';
                */

                // check data
                /*
                print '<tr>';
                print '<td class="titlefield">' . $langs->trans("Control") . '</td>';
                if ($object->doc_type == 'customer_invoice')
                {
                 $sqlmid = 'SELECT rowid as ref';
                    $sqlmid .= " FROM ".MAIN_DB_PREFIX."facture as fac";
                    $sqlmid .= " WHERE fac.rowid=" . ((int) $object->fk_doc);
                    dol_syslog("accountancy/bookkeeping/card.php::sqlmid=" . $sqlmid, LOG_DEBUG);
                    $resultmid = $db->query($sqlmid);
                    if ($resultmid) {
                        $objmid = $db->fetch_object($resultmid);
                        $invoicestatic = new Facture($db);
                        $invoicestatic->fetch($objmid->ref);
                        $ref=$langs->trans("Invoice").' '.$invoicestatic->getNomUrl(1);
                    }
                    else dol_print_error($db);
                }
                print '<td>' . $ref .'</td>';
                print '</tr>';
                */
                print "</table>\n";

                print '</div>';

                print dol_get_fiche_end();

                print '<div class="clearboth"></div>';

                print '<br>';

                $result = $object->fetchAllPerMvt($piece_num, $mode);   // This load $object->linesmvt

                if ($result < 0) {
                    setEventMessages($object->error, $object->errors, 'errors');
                } else {
                    // List of movements
                    print load_fiche_titre($langs->trans("ListeMvts"), '', '');

                    print '<form action="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '" method="post">';
                    if ($optioncss != '') {
                        print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
                    }
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="doc_date" value="' . $object->doc_date . '">' . "\n";
                    print '<input type="hidden" name="doc_type" value="' . $object->doc_type . '">' . "\n";
                    print '<input type="hidden" name="doc_ref" value="' . $object->doc_ref . '">' . "\n";
                    print '<input type="hidden" name="code_journal" value="' . $object->code_journal . '">' . "\n";
                    print '<input type="hidden" name="fk_doc" value="' . $object->fk_doc . '">' . "\n";
                    print '<input type="hidden" name="fk_docdet" value="' . $object->fk_docdet . '">' . "\n";
                    print '<input type="hidden" name="mode" value="' . $mode . '">' . "\n";

                    if (count($object->linesmvt) > 0) {
                        print '<div class="div-table-responsive-no-min">';
                        print '<table class="noborder centpercent">';

                        $total_debit = 0;
                        $total_credit = 0;

                        print '<tr class="liste_titre">';

                        print_liste_field_titre("AccountAccountingShort");
                        print_liste_field_titre("SubledgerAccount");
                        print_liste_field_titre("LabelOperation");
                        print_liste_field_titre("AccountingDebit", "", "", "", "", 'class="right"');
                        print_liste_field_titre("AccountingCredit", "", "", "", "", 'class="right"');
                        if (empty($object->date_validation)) {
                            print_liste_field_titre("Action", "", "", "", "", 'width="60"', "", "", 'center ');
                        } else {
                            print_liste_field_titre("");
                        }

                        print "</tr>\n";

                        // Add an empty line if there is not yet
                        if (!empty($object->linesmvt[0])) {
                            $tmpline = $object->linesmvt[0];
                            if (!empty($tmpline->numero_compte)) {
                                $line = new BookKeepingLine($db);
                                $object->linesmvt[] = $line;
                            }
                        }

                        foreach ($object->linesmvt as $line) {
                            print '<tr class="oddeven" data-lineid="' . ((int) $line->id) . '">';
                            $total_debit += $line->debit;
                            $total_credit += $line->credit;

                            if ($action == 'update' && $line->id == $id) {
                                print '<!-- td columns in edit mode -->';
                                print '<td>';
                                print $formaccounting->select_account((GETPOSTISSET("accountingaccount_number") ? GETPOST("accountingaccount_number", "alpha") : $line->numero_compte), 'accountingaccount_number', 1, array(), 1, 1, 'minwidth200 maxwidth500');
                                print '</td>';
                                print '<td>';
                                // TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
                                // It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
                                // Also, it is not possible to use a value that is not in the list.
                                // Also, the label is not automatically filled when a value is selected.
                                if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
                                    print $formaccounting->select_auxaccount((GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account), 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
                                } else {
                                    print '<input type="text" class="maxwidth150" name="subledger_account" value="' . (GETPOSTISSET("subledger_account") ? GETPOST("subledger_account", "alpha") : $line->subledger_account) . '" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccount")) . '">';
                                }
                                // Add also input for subledger label
                                print '<br><input type="text" class="maxwidth150" name="subledger_label" value="' . (GETPOSTISSET("subledger_label") ? GETPOST("subledger_label", "alpha") : $line->subledger_label) . '" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccountLabel")) . '">';
                                print '</td>';
                                print '<td><input type="text" class="minwidth200" name="label_operation" value="' . (GETPOSTISSET("label_operation") ? GETPOST("label_operation", "alpha") : $line->label_operation) . '"></td>';
                                print '<td class="right"><input type="text" size="6" class="right" name="debit" value="' . (GETPOSTISSET("debit") ? GETPOST("debit", "alpha") : price($line->debit)) . '"></td>';
                                print '<td class="right"><input type="text" size="6" class="right" name="credit" value="' . (GETPOSTISSET("credit") ? GETPOST("credit", "alpha") : price($line->credit)) . '"></td>';
                                print '<td>';
                                print '<input type="hidden" name="id" value="' . $line->id . '">' . "\n";
                                print '<input type="submit" class="button" name="update" value="' . $langs->trans("Update") . '">';
                                print '</td>';
                            } elseif (empty($line->numero_compte) || (empty($line->debit) && empty($line->credit))) {
                                if ($action == "" || $action == 'add') {
                                    print '<!-- td columns in add mode -->';
                                    print '<td>';
                                    print $formaccounting->select_account('', 'accountingaccount_number', 1, array(), 1, 1, 'minwidth200 maxwidth500');
                                    print '</td>';
                                    print '<td>';
                                    // TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because:
                                    // It does not use the setup of "key pressed" to select a thirdparty and this hang browser on large databases.
                                    // Also, it is not possible to use a value that is not in the list.
                                    // Also, the label is not automatically filled when a value is selected.
                                    if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
                                        print $formaccounting->select_auxaccount('', 'subledger_account', 1, 'maxwidth250', '', 'subledger_label');
                                    } else {
                                        print '<input type="text" class="maxwidth150" name="subledger_account" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccount")) . '">';
                                    }
                                    print '<br><input type="text" class="maxwidth150" name="subledger_label" value="" placeholder="' . dol_escape_htmltag($langs->trans("SubledgerAccountLabel")) . '">';
                                    print '</td>';
                                    print '<td><input type="text" class="minwidth200" name="label_operation" value="' . $label_operation . '"/></td>';
                                    print '<td class="right"><input type="text" size="6" class="right" name="debit" value=""/></td>';
                                    print '<td class="right"><input type="text" size="6" class="right" name="credit" value=""/></td>';
                                    print '<td class="center"><input type="submit" class="button small" name="save" value="' . $langs->trans("Add") . '"></td>';
                                }
                            } else {
                                print '<!-- td columns in display mode -->';
                                $resultfetch = $accountingaccount->fetch(null, $line->numero_compte, true);
                                print '<td>';
                                if ($resultfetch > 0) {
                                    print $accountingaccount->getNomUrl(0, 1, 1, '', 0);
                                } else {
                                    print $line->numero_compte . ' <span class="warning">(' . $langs->trans("AccountRemovedFromCurrentChartOfAccount") . ')</span>';
                                }
                                print '</td>';
                                print '<td>' . length_accounta($line->subledger_account);
                                if ($line->subledger_label) {
                                    print ' - <span class="opacitymedium">' . $line->subledger_label . '</span>';
                                }
                                print '</td>';
                                print '<td>' . $line->label_operation . '</td>';
                                print '<td class="right nowraponall amount">' . ($line->debit != 0 ? price($line->debit) : '') . '</td>';
                                print '<td class="right nowraponall amount">' . ($line->credit != 0 ? price($line->credit) : '') . '</td>';

                                print '<td class="center nowraponall">';
                                if (empty($line->date_export) && empty($line->date_validation)) {
                                    print '<a class="editfielda reposition" href="' . $_SERVER['PHP_SELF'] . '?action=update&id=' . $line->id . '&piece_num=' . urlencode($line->piece_num) . '&mode=' . urlencode($mode) . '&token=' . urlencode(newToken()) . '">';
                                    print img_edit('', 0, 'class="marginrightonly"');
                                    print '</a> &nbsp;';
                                } else {
                                    print '<a class="editfielda nohover cursornotallowed reposition disabled" href="#" title="' . dol_escape_htmltag($langs->trans("ForbiddenTransactionAlreadyExported")) . '">';
                                    print img_edit($langs->trans("ForbiddenTransactionAlreadyExported"), 0, 'class="marginrightonly"');
                                    print '</a> &nbsp;';
                                }

                                if (empty($line->date_validation)) {
                                    $actiontodelete = 'delete';
                                    if ($mode == '_tmp' || $action != 'delmouv') {
                                        $actiontodelete = 'confirm_delete';
                                    }

                                    print '<a href="' . $_SERVER['PHP_SELF'] . '?action=' . $actiontodelete . '&id=' . $line->id . '&piece_num=' . urlencode($line->piece_num) . '&mode=' . urlencode($mode) . '&token=' . urlencode(newToken()) . '">';
                                    print img_delete();
                                    print '</a>';
                                } else {
                                    print '<a class="editfielda nohover cursornotallowed disabled" href="#" title="' . dol_escape_htmltag($langs->trans("ForbiddenTransactionAlreadyExported")) . '">';
                                    print img_delete($langs->trans("ForbiddenTransactionAlreadyValidated"));
                                    print '</a>';
                                }

                                print '</td>';
                            }
                            print "</tr>\n";
                        }

                        $total_debit = price2num($total_debit, 'MT');
                        $total_credit = price2num($total_credit, 'MT');

                        if ($total_debit != $total_credit) {
                            setEventMessages(null, array($langs->trans('MvtNotCorrectlyBalanced', $total_debit, $total_credit)), 'warnings');
                        }

                        print '</table>';
                        print '</div>';

                        if ($mode == '_tmp' && $action == '') {
                            print '<br>';
                            print '<div class="center">';
                            if ($total_debit == $total_credit) {
                                print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?piece_num=' . $object->piece_num . '&action=valid">' . $langs->trans("ValidTransaction") . '</a>';
                            } else {
                                print '<input type="submit" class="button" disabled="disabled" href="#" title="' . dol_escape_htmltag($langs->trans("MvtNotCorrectlyBalanced", $debit, $credit)) . '" value="' . dol_escape_htmltag($langs->trans("ValidTransaction")) . '">';
                            }

                            print ' &nbsp; ';
                            print '<a class="button button-cancel" href="' . DOL_URL_ROOT . '/accountancy/bookkeeping/list.php">' . $langs->trans("Cancel") . '</a>';

                            print "</div>";
                        }
                    }

                    print '</form>';
                }
            } else {
                print load_fiche_titre($langs->trans("NoRecords"));
            }
        }

        print dol_get_fiche_end();

// End of page
        llxFooter();
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/bookkeeping/export.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Export operation of book keeping
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
        $langs->loadLangs(array("accountancy", "compta"));

        $socid = GETPOSTINT('socid');

        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bookkeepinglist';
        $search_mvt_num = GETPOSTINT('search_mvt_num');
        $search_doc_type = GETPOST("search_doc_type", 'alpha');
        $search_doc_ref = GETPOST("search_doc_ref", 'alpha');
        $search_date_startyear =  GETPOSTINT('search_date_startyear');
        $search_date_startmonth =  GETPOSTINT('search_date_startmonth');
        $search_date_startday =  GETPOSTINT('search_date_startday');
        $search_date_endyear =  GETPOSTINT('search_date_endyear');
        $search_date_endmonth =  GETPOSTINT('search_date_endmonth');
        $search_date_endday =  GETPOSTINT('search_date_endday');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
        $search_doc_date = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
        $search_date_creation_startyear =  GETPOSTINT('search_date_creation_startyear');
        $search_date_creation_startmonth =  GETPOSTINT('search_date_creation_startmonth');
        $search_date_creation_startday =  GETPOSTINT('search_date_creation_startday');
        $search_date_creation_endyear =  GETPOSTINT('search_date_creation_endyear');
        $search_date_creation_endmonth =  GETPOSTINT('search_date_creation_endmonth');
        $search_date_creation_endday =  GETPOSTINT('search_date_creation_endday');
        $search_date_creation_start = dol_mktime(0, 0, 0, $search_date_creation_startmonth, $search_date_creation_startday, $search_date_creation_startyear);
        $search_date_creation_end = dol_mktime(23, 59, 59, $search_date_creation_endmonth, $search_date_creation_endday, $search_date_creation_endyear);
        $search_date_modification_startyear =  GETPOSTINT('search_date_modification_startyear');
        $search_date_modification_startmonth =  GETPOSTINT('search_date_modification_startmonth');
        $search_date_modification_startday =  GETPOSTINT('search_date_modification_startday');
        $search_date_modification_endyear =  GETPOSTINT('search_date_modification_endyear');
        $search_date_modification_endmonth =  GETPOSTINT('search_date_modification_endmonth');
        $search_date_modification_endday =  GETPOSTINT('search_date_modification_endday');
        $search_date_modification_start = dol_mktime(0, 0, 0, $search_date_modification_startmonth, $search_date_modification_startday, $search_date_modification_startyear);
        $search_date_modification_end = dol_mktime(23, 59, 59, $search_date_modification_endmonth, $search_date_modification_endday, $search_date_modification_endyear);
        $search_date_export_startyear =  GETPOSTINT('search_date_export_startyear');
        $search_date_export_startmonth =  GETPOSTINT('search_date_export_startmonth');
        $search_date_export_startday =  GETPOSTINT('search_date_export_startday');
        $search_date_export_endyear =  GETPOSTINT('search_date_export_endyear');
        $search_date_export_endmonth =  GETPOSTINT('search_date_export_endmonth');
        $search_date_export_endday =  GETPOSTINT('search_date_export_endday');
        $search_date_export_start = dol_mktime(0, 0, 0, $search_date_export_startmonth, $search_date_export_startday, $search_date_export_startyear);
        $search_date_export_end = dol_mktime(23, 59, 59, $search_date_export_endmonth, $search_date_export_endday, $search_date_export_endyear);
        $search_date_validation_startyear =  GETPOSTINT('search_date_validation_startyear');
        $search_date_validation_startmonth =  GETPOSTINT('search_date_validation_startmonth');
        $search_date_validation_startday =  GETPOSTINT('search_date_validation_startday');
        $search_date_validation_endyear =  GETPOSTINT('search_date_validation_endyear');
        $search_date_validation_endmonth =  GETPOSTINT('search_date_validation_endmonth');
        $search_date_validation_endday =  GETPOSTINT('search_date_validation_endday');
        $search_date_validation_start = dol_mktime(0, 0, 0, $search_date_validation_startmonth, $search_date_validation_startday, $search_date_validation_startyear);
        $search_date_validation_end = dol_mktime(23, 59, 59, $search_date_validation_endmonth, $search_date_validation_endday, $search_date_validation_endyear);
        $search_import_key = GETPOST("search_import_key", 'alpha');

//var_dump($search_date_start);exit;
        if (GETPOST("button_delmvt_x") || GETPOST("button_delmvt.x") || GETPOST("button_delmvt")) {
            $action = 'delbookkeepingyear';
        }
        if (GETPOST("button_export_file_x") || GETPOST("button_export_file.x") || GETPOST("button_export_file")) {
            $action = 'export_file';
        }

        $search_account_category = GETPOSTINT('search_account_category');

        $search_accountancy_code = GETPOST("search_accountancy_code", 'alpha');
        $search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
        if ($search_accountancy_code_start == - 1) {
            $search_accountancy_code_start = '';
        }
        $search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
        if ($search_accountancy_code_end == - 1) {
            $search_accountancy_code_end = '';
        }

        $search_accountancy_aux_code = GETPOST("search_accountancy_aux_code", 'alpha');
        $search_accountancy_aux_code_start = GETPOST('search_accountancy_aux_code_start', 'alpha');
        if ($search_accountancy_aux_code_start == - 1) {
            $search_accountancy_aux_code_start = '';
        }
        $search_accountancy_aux_code_end = GETPOST('search_accountancy_aux_code_end', 'alpha');
        if ($search_accountancy_aux_code_end == - 1) {
            $search_accountancy_aux_code_end = '';
        }
        $search_mvt_label = GETPOST('search_mvt_label', 'alpha');
        $search_direction = GETPOST('search_direction', 'alpha');
        $search_debit = GETPOST('search_debit', 'alpha');
        $search_credit = GETPOST('search_credit', 'alpha');
        $search_ledger_code = GETPOST('search_ledger_code', 'array');
        $search_lettering_code = GETPOST('search_lettering_code', 'alpha');
        $search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $optioncss = GETPOST('optioncss', 'alpha');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if ($sortorder == "") {
            $sortorder = "ASC";
        }
        if ($sortfield == "") {
            $sortfield = "t.piece_num,t.rowid";
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $object = new BookKeeping($db);
        $hookmanager->initHooks(array('bookkeepingexport'));

        $formaccounting = new FormAccounting($db);
        $form = new Form($db);

        if (!in_array($action, array('export_file', 'delmouv', 'delmouvconfirm')) && !GETPOSTISSET('begin') && !GETPOSTISSET('formfilteraction') && GETPOSTINT('page') == '' && !GETPOSTINT('noreset') && $user->hasRight('accounting', 'mouvements', 'export')) {
            if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('restore_lastsearch_values') && !GETPOST('search_accountancy_code_start')) {
                $query = "SELECT date_start, date_end from " . MAIN_DB_PREFIX . "accounting_fiscalyear ";
                $query .= " where date_start < '" . $db->idate(dol_now()) . "' and date_end > '" . $db->idate(dol_now()) . "' limit 1";
                $res = $db->query($query);

                if ($res->num_rows > 0) {
                    $fiscalYear = $db->fetch_object($res);
                    $search_date_start = strtotime($fiscalYear->date_start);
                    $search_date_end = strtotime($fiscalYear->date_end);
                } else {
                    $month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
                    $year_start = dol_print_date(dol_now(), '%Y');
                    if (dol_print_date(dol_now(), '%m') < $month_start) {
                        $year_start--; // If current month is lower that starting fiscal month, we start last year
                    }
                    $year_end = $year_start + 1;
                    $month_end = $month_start - 1;
                    if ($month_end < 1) {
                        $month_end = 12;
                        $year_end--;
                    }
                    $search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
                    $search_date_end = dol_get_last_day($year_end, $month_end);
                }
            }
        }


        $arrayfields = array(
            't.piece_num' => array('label' => $langs->trans("TransactionNumShort"), 'checked' => 1),
            't.code_journal' => array('label' => $langs->trans("Codejournal"), 'checked' => 1),
            't.doc_date' => array('label' => $langs->trans("Docdate"), 'checked' => 1),
            't.doc_ref' => array('label' => $langs->trans("Piece"), 'checked' => 1),
            't.numero_compte' => array('label' => $langs->trans("AccountAccountingShort"), 'checked' => 1),
            't.subledger_account' => array('label' => $langs->trans("SubledgerAccount"), 'checked' => 1),
            't.label_operation' => array('label' => $langs->trans("Label"), 'checked' => 1),
            't.debit' => array('label' => $langs->trans("AccountingDebit"), 'checked' => 1),
            't.credit' => array('label' => $langs->trans("AccountingCredit"), 'checked' => 1),
            't.lettering_code' => array('label' => $langs->trans("LetteringCode"), 'checked' => 1),
            't.date_creation' => array('label' => $langs->trans("DateCreation"), 'checked' => 0),
            't.tms' => array('label' => $langs->trans("DateModification"), 'checked' => 0),
            't.date_export' => array('label' => $langs->trans("DateExport"), 'checked' => 1),
            't.date_validated' => array('label' => $langs->trans("DateValidationAndLock"), 'checked' => 1, 'enabled' => !getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")),
            't.import_key' => array('label' => $langs->trans("ImportId"), 'checked' => 0, 'position' => 1100),
        );

        if (!getDolGlobalString('ACCOUNTING_ENABLE_LETTERING')) {
            unset($arrayfields['t.lettering_code']);
        }

        $accountancyexport = new AccountancyExport($db);
        $listofformat = $accountancyexport->getType();
        $formatexportset = getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV');
        if (empty($listofformat[$formatexportset])) {
            $formatexportset = 1;
        }

        $error = 0;

        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        $param = '';

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha')) {
            $massaction = '';
        }

        $parameters = array('socid' => $socid);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $search_mvt_num = '';
                $search_doc_type = '';
                $search_doc_ref = '';
                $search_doc_date = '';
                $search_account_category = '';
                $search_accountancy_code = '';
                $search_accountancy_code_start = '';
                $search_accountancy_code_end = '';
                $search_accountancy_aux_code = '';
                $search_accountancy_aux_code_start = '';
                $search_accountancy_aux_code_end = '';
                $search_mvt_label = '';
                $search_direction = '';
                $search_ledger_code = array();
                $search_date_startyear = '';
                $search_date_startmonth = '';
                $search_date_startday = '';
                $search_date_endyear = '';
                $search_date_endmonth = '';
                $search_date_endday = '';
                $search_date_start = '';
                $search_date_end = '';
                $search_date_creation_startyear = '';
                $search_date_creation_startmonth = '';
                $search_date_creation_startday = '';
                $search_date_creation_endyear = '';
                $search_date_creation_endmonth = '';
                $search_date_creation_endday = '';
                $search_date_creation_start = '';
                $search_date_creation_end = '';
                $search_date_modification_startyear = '';
                $search_date_modification_startmonth = '';
                $search_date_modification_startday = '';
                $search_date_modification_endyear = '';
                $search_date_modification_endmonth = '';
                $search_date_modification_endday = '';
                $search_date_modification_start = '';
                $search_date_modification_end = '';
                $search_date_export_startyear = '';
                $search_date_export_startmonth = '';
                $search_date_export_startday = '';
                $search_date_export_endyear = '';
                $search_date_export_endmonth = '';
                $search_date_export_endday = '';
                $search_date_export_start = '';
                $search_date_export_end = '';
                $search_date_validation_startyear = '';
                $search_date_validation_startmonth = '';
                $search_date_validation_startday = '';
                $search_date_validation_endyear = '';
                $search_date_validation_endmonth = '';
                $search_date_validation_endday = '';
                $search_date_validation_start = '';
                $search_date_validation_end = '';
                $search_debit = '';
                $search_credit = '';
                $search_lettering_code = '';
                $search_not_reconciled = '';
                $search_import_key = '';
                $toselect = array();
            }

            // Must be after the remove filter action, before the export.
            $filter = array();
            if (!empty($search_date_start)) {
                $filter['t.doc_date>='] = $search_date_start;
                $tmp = dol_getdate($search_date_start);
                $param .= '&search_date_startmonth=' . urlencode($tmp['mon']) . '&search_date_startday=' . urlencode($tmp['mday']) . '&search_date_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_end)) {
                $filter['t.doc_date<='] = $search_date_end;
                $tmp = dol_getdate($search_date_end);
                $param .= '&search_date_endmonth=' . urlencode($tmp['mon']) . '&search_date_endday=' . urlencode($tmp['mday']) . '&search_date_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_doc_date)) {
                $filter['t.doc_date'] = $search_doc_date;
                $tmp = dol_getdate($search_doc_date);
                $param .= '&doc_datemonth=' . urlencode($tmp['mon']) . '&doc_dateday=' . urlencode($tmp['mday']) . '&doc_dateyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_doc_type)) {
                $filter['t.doc_type'] = $search_doc_type;
                $param .= '&search_doc_type=' . urlencode($search_doc_type);
            }
            if (!empty($search_doc_ref)) {
                $filter['t.doc_ref'] = $search_doc_ref;
                $param .= '&search_doc_ref=' . urlencode($search_doc_ref);
            }
            if ($search_account_category != '-1' && !empty($search_account_category)) {
                require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancycategory.class.php';
                $accountingcategory = new AccountancyCategory($db);

                $listofaccountsforgroup = $accountingcategory->getCptsCat(0, 'fk_accounting_category = ' . ((int) $search_account_category));
                $listofaccountsforgroup2 = array();
                if (is_array($listofaccountsforgroup)) {
                    foreach ($listofaccountsforgroup as $tmpval) {
                        $listofaccountsforgroup2[] = "'" . $db->escape($tmpval['id']) . "'";
                    }
                }
                $filter['t.search_accounting_code_in'] = implode(',', $listofaccountsforgroup2);
                $param .= '&search_account_category=' . urlencode((string) ($search_account_category));
            }
            if (!empty($search_accountancy_code)) {
                $filter['t.numero_compte'] = $search_accountancy_code;
                $param .= '&search_accountancy_code=' . urlencode($search_accountancy_code);
            }
            if (!empty($search_accountancy_code_start)) {
                $filter['t.numero_compte>='] = $search_accountancy_code_start;
                $param .= '&search_accountancy_code_start=' . urlencode($search_accountancy_code_start);
            }
            if (!empty($search_accountancy_code_end)) {
                $filter['t.numero_compte<='] = $search_accountancy_code_end;
                $param .= '&search_accountancy_code_end=' . urlencode($search_accountancy_code_end);
            }
            if (!empty($search_accountancy_aux_code)) {
                $filter['t.subledger_account'] = $search_accountancy_aux_code;
                $param .= '&search_accountancy_aux_code=' . urlencode($search_accountancy_aux_code);
            }
            if (!empty($search_accountancy_aux_code_start)) {
                $filter['t.subledger_account>='] = $search_accountancy_aux_code_start;
                $param .= '&search_accountancy_aux_code_start=' . urlencode($search_accountancy_aux_code_start);
            }
            if (!empty($search_accountancy_aux_code_end)) {
                $filter['t.subledger_account<='] = $search_accountancy_aux_code_end;
                $param .= '&search_accountancy_aux_code_end=' . urlencode($search_accountancy_aux_code_end);
            }
            if (!empty($search_mvt_label)) {
                $filter['t.label_operation'] = $search_mvt_label;
                $param .= '&search_mvt_label=' . urlencode($search_mvt_label);
            }
            if (!empty($search_direction)) {
                $filter['t.sens'] = $search_direction;
                $param .= '&search_direction=' . urlencode($search_direction);
            }
            if (!empty($search_ledger_code)) {
                $filter['t.code_journal'] = $search_ledger_code;
                foreach ($search_ledger_code as $code) {
                    $param .= '&search_ledger_code[]=' . urlencode($code);
                }
            }
            if (!empty($search_mvt_num)) {
                $filter['t.piece_num'] = $search_mvt_num;
                $param .= '&search_mvt_num=' . urlencode((string) ($search_mvt_num));
            }
            if (!empty($search_date_creation_start)) {
                $filter['t.date_creation>='] = $search_date_creation_start;
                $tmp = dol_getdate($search_date_creation_start);
                $param .= '&search_date_creation_startmonth=' . urlencode($tmp['mon']) . '&search_date_creation_startday=' . urlencode($tmp['mday']) . '&search_date_creation_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_creation_end)) {
                $filter['t.date_creation<='] = $search_date_creation_end;
                $tmp = dol_getdate($search_date_creation_end);
                $param .= '&search_date_creation_endmonth=' . urlencode($tmp['mon']) . '&search_date_creation_endday=' . urlencode($tmp['mday']) . '&search_date_creation_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_modification_start)) {
                $filter['t.tms>='] = $search_date_modification_start;
                $tmp = dol_getdate($search_date_modification_start);
                $param .= '&search_date_modification_startmonth=' . urlencode($tmp['mon']) . '&search_date_modification_startday=' . urlencode($tmp['mday']) . '&search_date_modification_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_modification_end)) {
                $filter['t.tms<='] = $search_date_modification_end;
                $tmp = dol_getdate($search_date_modification_end);
                $param .= '&search_date_modification_endmonth=' . urlencode($tmp['mon']) . '&search_date_modification_endday=' . urlencode($tmp['mday']) . '&search_date_modification_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_export_start)) {
                $filter['t.date_export>='] = $search_date_export_start;
                $tmp = dol_getdate($search_date_export_start);
                $param .= '&search_date_export_startmonth=' . urlencode($tmp['mon']) . '&search_date_export_startday=' . urlencode($tmp['mday']) . '&search_date_export_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_export_end)) {
                $filter['t.date_export<='] = $search_date_export_end;
                $tmp = dol_getdate($search_date_export_end);
                $param .= '&search_date_export_endmonth=' . urlencode($tmp['mon']) . '&search_date_export_endday=' . urlencode($tmp['mday']) . '&search_date_export_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_validation_start)) {
                $filter['t.date_validated>='] = $search_date_validation_start;
                $tmp = dol_getdate($search_date_validation_start);
                $param .= '&search_date_validation_startmonth=' . urlencode($tmp['mon']) . '&search_date_validation_startday=' . urlencode($tmp['mday']) . '&search_date_validation_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_validation_end)) {
                $filter['t.date_validated<='] = $search_date_validation_end;
                $tmp = dol_getdate($search_date_validation_end);
                $param .= '&search_date_validation_endmonth=' . urlencode($tmp['mon']) . '&search_date_validation_endday=' . urlencode($tmp['mday']) . '&search_date_validation_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_debit)) {
                $filter['t.debit'] = $search_debit;
                $param .= '&search_debit=' . urlencode($search_debit);
            }
            if (!empty($search_credit)) {
                $filter['t.credit'] = $search_credit;
                $param .= '&search_credit=' . urlencode($search_credit);
            }
            if (!empty($search_lettering_code)) {
                $filter['t.lettering_code'] = $search_lettering_code;
                $param .= '&search_lettering_code=' . urlencode($search_lettering_code);
            }
            if (!empty($search_not_reconciled)) {
                $filter['t.reconciled_option'] = $search_not_reconciled;
                $param .= '&search_not_reconciled=' . urlencode($search_not_reconciled);
            }
            if (!empty($search_import_key)) {
                $filter['t.import_key'] = $search_import_key;
                $param .= '&search_import_key=' . urlencode($search_import_key);
            }

            if ($action == 'setreexport') {
                $setreexport = GETPOSTINT('value');
                if (!dolibarr_set_const($db, "ACCOUNTING_REEXPORT", $setreexport, 'yesno', 0, '', $conf->entity)) {
                    $error++;
                }

                if (!$error) {
                    if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {
                        setEventMessages($langs->trans("ExportOfPiecesAlreadyExportedIsDisable"), null, 'mesgs');
                    } else {
                        setEventMessages($langs->trans("ExportOfPiecesAlreadyExportedIsEnable"), null, 'warnings');
                    }
                } else {
                    setEventMessages($langs->trans("Error"), null, 'errors');
                }
            }

            // Mass actions
            $objectclass = 'Bookkeeping';
            $objectlabel = 'Bookkeeping';
            $permissiontoread = $user->hasRight('societe', 'lire');
            $permissiontodelete = $user->hasRight('societe', 'supprimer');
            $permissiontoadd = $user->hasRight('societe', 'creer');
            $uploaddir = $conf->societe->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
        }

// Build and execute select (used by page and export action)
// must de set after the action that set $filter
// --------------------------------------------------------------------

        $sql = 'SELECT';
        $sql .= ' t.rowid,';
        $sql .= " t.doc_date,";
        $sql .= " t.doc_type,";
        $sql .= " t.doc_ref,";
        $sql .= " t.fk_doc,";
        $sql .= " t.fk_docdet,";
        $sql .= " t.thirdparty_code,";
        $sql .= " t.subledger_account,";
        $sql .= " t.subledger_label,";
        $sql .= " t.numero_compte,";
        $sql .= " t.label_compte,";
        $sql .= " t.label_operation,";
        $sql .= " t.debit,";
        $sql .= " t.credit,";
        $sql .= " t.lettering_code,";
        $sql .= " t.date_lettering,";
        $sql .= " t.montant as amount,";
        $sql .= " t.sens,";
        $sql .= " t.fk_user_author,";
        $sql .= " t.import_key,";
        $sql .= " t.code_journal,";
        $sql .= " t.journal_label,";
        $sql .= " t.piece_num,";
        $sql .= " t.date_creation,";
        $sql .= " t.date_lim_reglement,";
        $sql .= " t.tms as date_modification,";
        $sql .= " t.date_export,";
        $sql .= " t.date_validated as date_validation,";
        $sql .= " t.import_key";

        $sqlfields = $sql; // $sql fields to remove for count total

        $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element . ' as t';
// Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.doc_date') {
                    $sqlwhere[] = $db->sanitize($key) . " = '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date>=') {
                    $sqlwhere[] = "t.doc_date >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date<=') {
                    $sqlwhere[] = "t.doc_date <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date>') {
                    $sqlwhere[] = "t.doc_date > '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date<') {
                    $sqlwhere[] = "t.doc_date < '" . $db->idate($value) . "'";
                } elseif ($key == 't.numero_compte>=') {
                    $sqlwhere[] = "t.numero_compte >= '" . $db->escape($value) . "'";
                } elseif ($key == 't.numero_compte<=') {
                    $sqlwhere[] = "t.numero_compte <= '" . $db->escape($value) . "'";
                } elseif ($key == 't.subledger_account>=') {
                    $sqlwhere[] = "t.subledger_account >= '" . $db->escape($value) . "'";
                } elseif ($key == 't.subledger_account<=') {
                    $sqlwhere[] = "t.subledger_account <= '" . $db->escape($value) . "'";
                } elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
                    $sqlwhere[] = $db->sanitize($key) . '=' . ((int) $value);
                } elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
                    $sqlwhere[] = $db->sanitize($key) . " LIKE '" . $db->escape($db->escapeforlike($value)) . "%'";
                } elseif ($key == 't.subledger_account') {
                    $sqlwhere[] = natural_search($key, $value, 0, 1);
                } elseif ($key == 't.tms>=') {
                    $sqlwhere[] = "t.tms >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.tms<=') {
                    $sqlwhere[] = "t.tms <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_creation>=') {
                    $sqlwhere[] = "t.date_creation >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_creation<=') {
                    $sqlwhere[] = "t.date_creation <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_export>=') {
                    $sqlwhere[] = "t.date_export >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_export<=') {
                    $sqlwhere[] = "t.date_export <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_validated>=') {
                    $sqlwhere[] = "t;date_validate >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_validated<=') {
                    $sqlwhere[] = "t;date_validate <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.credit' || $key == 't.debit') {
                    $sqlwhere[] = natural_search($key, $value, 1, 1);
                } elseif ($key == 't.reconciled_option') {
                    $sqlwhere[] = 't.lettering_code IS NULL';
                } elseif ($key == 't.code_journal' && !empty($value)) {
                    if (is_array($value)) {
                        $sqlwhere[] = natural_search("t.code_journal", implode(',', $value), 3, 1);
                    } else {
                        $sqlwhere[] = natural_search("t.code_journal", $value, 3, 1);
                    }
                } elseif ($key == 't.search_accounting_code_in' && !empty($value)) {
                    $sqlwhere[] = 't.numero_compte IN (' . $db->sanitize($value, 1) . ')';
                } else {
                    $sqlwhere[] = natural_search($key, $value, 0, 1);
                }
            }
        }
        $sql .= ' WHERE t.entity IN (' . getEntity('accountancy') . ')';
        if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {   // Reexport not enabled (default mode)
            $sql .= " AND t.date_export IS NULL";
        }
        if (count($sqlwhere) > 0) {
            $sql .= ' AND ' . implode(' AND ', $sqlwhere);
        }
//print $sql;


// Export into a file with format defined into setup (FEC, CSV, ...)
// Must be after definition of $sql
        if ($action == 'export_fileconfirm' && $user->hasRight('accounting', 'mouvements', 'export')) {
            // Export files then exit
            $accountancyexport = new AccountancyExport($db);

            $error = 0;
            $nbtotalofrecords = 0;

            // Open transaction to read lines to export, export them and update field date_export or date_validated
            $db->begin();

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

            $db->free($resql);

            //$sqlforexport = $sql;
            //$sqlforexport .= $db->order($sortfield, $sortorder);


            // TODO Call the fetchAll for a $limit and $offset
            // Replace the fetchAll to get all ->line followed by call to ->export(). fetchAll() currently consumes too much memory on large export.
            // Replace this with the query($sqlforexport) on a limited block and loop on each line to export them.
            $limit = 0;
            $offset = 0;
            $result = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter, 'AND', (getDolGlobalString('ACCOUNTING_REEXPORT') ? 1 : 0));

            if ($result < 0) {
                $error++;
                setEventMessages($object->error, $object->errors, 'errors');
            } else {
                $formatexport = GETPOSTINT('formatexport');
                $notexportlettering = GETPOST('notexportlettering', 'alpha');


                if (!empty($notexportlettering)) {
                    if (is_array($object->lines)) {
                        foreach ($object->lines as $k => $movement) {
                            unset($object->lines[$k]->lettering_code);
                            unset($object->lines[$k]->date_lettering);
                        }
                    }
                }

                $notifiedexportdate = GETPOST('notifiedexportdate', 'alpha');
                $notifiedvalidationdate = GETPOST('notifiedvalidationdate', 'alpha');
                $withAttachment = !empty(trim(GETPOST('notifiedexportfull', 'alphanohtml'))) ? 1 : 0;

                // Output data on screen or download
                //$result = $accountancyexport->export($object->lines, $formatexport, $withAttachment);
                $result = $accountancyexport->export($object->lines, $formatexport, $withAttachment, 1, 1, 1);

                if ($result < 0) {
                    $error++;
                } else {
                    if (!empty($notifiedexportdate) || !empty($notifiedvalidationdate)) {
                        if (is_array($object->lines)) {
                            dol_syslog("/accountancy/bookkeeping/list.php Function export_file Specify movements as exported", LOG_DEBUG);

                            // TODO Merge update for each line into one global using rowid IN (list of movement ids)
                            foreach ($object->lines as $movement) {
                                // Update the line to set date_export and/or date_validated (if not already set !)
                                $now = dol_now();

                                $setfields = '';
                                if (!empty($notifiedexportdate) && empty($movement->date_export)) {
                                    $setfields .= ($setfields ? "," : "") . " date_export = '" . $db->idate($now) . "'";
                                }
                                if (!empty($notifiedvalidationdate) && empty($movement->date_validation)) {
                                    $setfields .= ($setfields ? "," : "") . " date_validated = '" . $db->idate($now) . "'";
                                }

                                if ($setfields) {
                                    $sql = " UPDATE " . MAIN_DB_PREFIX . "accounting_bookkeeping";
                                    $sql .= " SET " . $db->sanitize($setfields);
                                    $sql .= " WHERE rowid = " . ((int) $movement->id);

                                    $result = $db->query($sql);
                                    if (!$result) {
                                        $error++;
                                        break;
                                    }
                                }
                            }

                            if ($error) {
                                $accountancyexport->errors[] = $langs->trans('NotAllExportedMovementsCouldBeRecordedAsExportedOrValidated');
                            }
                        }
                    }
                }
            }

            if (!$error) {
                $db->commit();

                $downloadFilePath = $accountancyexport->generatedfiledata['downloadFilePath'];
                $downloadFileMimeType = $accountancyexport->generatedfiledata['downloadFileMimeType'];
                $downloadFileFullName = $accountancyexport->generatedfiledata['downloadFileFullName'];

                // No error, we can output the file
                top_httphead($downloadFileMimeType);

                header('Content-Description: File Transfer');
                // Add MIME Content-Disposition from RFC 2183 (inline=automatically displayed, attachment=need user action to open)
                $attachment = 1;
                if ($attachment) {
                    header('Content-Disposition: attachment; filename="' . $downloadFileFullName . '"');
                } else {
                    header('Content-Disposition: inline; filename="' . $downloadFileFullName . '"');
                }
                // Ajout directives pour resoudre bug IE
                header('Cache-Control: Public, must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . dol_filesize($downloadFilePath));

                readfileLowMemory($downloadFilePath);
            } else {
                $db->rollback();

                setEventMessages('', $accountancyexport->errors, 'errors');
                header('Location: ' . $_SERVER['PHP_SELF']);
            }
            exit(); // download or show errors
        }


        /*
         * View
         */

        $formother = new FormOther($db);
        $formfile = new FormFile($db);

        $title_page = $langs->trans("Operations") . ' - ' . $langs->trans("ExportAccountancy");

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

        $arrayofselected = is_array($toselect) ? $toselect : array();

// Output page
// --------------------------------------------------------------------
        $help_url = 'EN:Module_Double_Entry_Accounting#Exports|FR:Module_Comptabilit&eacute;_en_Partie_Double#Exports';

        llxHeader('', $title_page, $help_url);

        $formconfirm = '';

        if ($action == 'export_file') {
            $form_question = array();

            $form_question['formatexport'] = array(
                'name' => 'formatexport',
                'type' => 'select',
                'label' => $langs->trans('Modelcsv'),       // TODO  Use Selectmodelcsv and show a select combo
                'values' => $listofformat,
                'default' => $formatexportset,
                'morecss' => 'minwidth200 maxwidth200'
            );

            $form_question['separator0'] = array('name' => 'separator0', 'type' => 'separator');

            if (getDolGlobalInt("ACCOUNTING_ENABLE_LETTERING")) {
                // If 1, we check by default.
                $checked = getDolGlobalString('ACCOUNTING_DEFAULT_NOT_EXPORT_LETTERING') ? 'true' : 'false';
                $form_question['notexportlettering'] = array(
                    'name' => 'notexportlettering',
                    'type' => 'checkbox',
                    'label' => $langs->trans('NotExportLettering'),
                    'value' => $checked,
                );

                $form_question['separator1'] = array('name' => 'separator1', 'type' => 'separator');
            }

            // If 1 or not set, we check by default.
            $checked = (!isset($conf->global->ACCOUNTING_DEFAULT_NOT_NOTIFIED_EXPORT_DATE) || getDolGlobalString('ACCOUNTING_DEFAULT_NOT_NOTIFIED_EXPORT_DATE'));
            $form_question['notifiedexportdate'] = array(
                'name' => 'notifiedexportdate',
                'type' => 'checkbox',
                'label' => $langs->trans('NotifiedExportDate'),
                'value' => (getDolGlobalString('ACCOUNTING_DEFAULT_NOT_NOTIFIED_EXPORT_DATE') ? 'false' : 'true'),
            );

            $form_question['separator2'] = array('name' => 'separator2', 'type' => 'separator');

            if (!getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")) {
                // If 0 or not set, we NOT check by default.
                $checked = (isset($conf->global->ACCOUNTING_DEFAULT_NOT_NOTIFIED_VALIDATION_DATE) || getDolGlobalString('ACCOUNTING_DEFAULT_NOT_NOTIFIED_VALIDATION_DATE'));
                $form_question['notifiedvalidationdate'] = array(
                    'name' => 'notifiedvalidationdate',
                    'type' => 'checkbox',
                    'label' => $langs->trans('NotifiedValidationDate', $langs->transnoentitiesnoconv("MenuAccountancyClosure")),
                    'value' => $checked,
                );

                $form_question['separator3'] = array('name' => 'separator3', 'type' => 'separator');
            }

            // add documents in an archive for accountancy export (Quadratus)
            if (getDolGlobalString('ACCOUNTING_EXPORT_MODELCSV') == AccountancyExport::$EXPORT_TYPE_QUADRATUS) {
                $form_question['notifiedexportfull'] = array(
                    'name' => 'notifiedexportfull',
                    'type' => 'checkbox',
                    'label' => $langs->trans('NotifiedExportFull'),
                    'value' => 'false',
                );
            }

            $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'] . '?' . $param, $langs->trans("ExportFilteredList") . '...', $langs->trans('ConfirmExportFile'), 'export_fileconfirm', $form_question, '', 1, 420, 600);
        }

// Print form confirm
        print $formconfirm;

//$param='';    param started before
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . urlencode($limit);
        }

// List of mass actions available
        $arrayofmassactions = array();
        $massactionbutton = $form->selectMassAction($massaction, $arrayofmassactions);

        print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="list">';
        if ($optioncss != '') {
            print '<input type="hidden" name="optioncss" value="' . urlencode($optioncss) . '">';
        }
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

        if (count($filter)) {
            $buttonLabel = $langs->trans("ExportFilteredList");
        } else {
            $buttonLabel = $langs->trans("ExportList");
        }

        $parameters = array('param' => $param);
        $reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        $newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;
        if (empty($reshook)) {
            // Button re-export
            if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {
                $newcardbutton .= '<a class="valignmiddle" href="' . $_SERVER['PHP_SELF'] . '?action=setreexport&token=' . newToken() . '&value=1' . ($param ? '&' . $param : '') . '&sortfield=' . urlencode($sortfield) . '&sortorder=' . urlencode($sortorder) . '">' . img_picto($langs->trans("ClickToShowAlreadyExportedLines"), 'switch_off', 'class="small size15x valignmiddle"');
                $newcardbutton .= '<span class="valignmiddle marginrightonly paddingleft">' . $langs->trans("ClickToShowAlreadyExportedLines") . '</span>';
                $newcardbutton .= '</a>';
            } else {
                $newcardbutton .= '<a class="valignmiddle" href="' . $_SERVER['PHP_SELF'] . '?action=setreexport&token=' . newToken() . '&value=0' . ($param ? '&' . $param : '') . '&sortfield=' . urlencode($sortfield) . '&sortorder=' . urlencode($sortorder) . '">' . img_picto($langs->trans("DocsAlreadyExportedAreIncluded"), 'switch_on', 'class="warning size15x valignmiddle"');
                $newcardbutton .= '<span class="valignmiddle marginrightonly paddingleft">' . $langs->trans("DocsAlreadyExportedAreIncluded") . '</span>';
                $newcardbutton .= '</a>';
            }

            if ($user->hasRight('accounting', 'mouvements', 'export')) {
                $newcardbutton .= dolGetButtonTitle($buttonLabel, $langs->trans("ExportFilteredList"), 'fa fa-file-export paddingleft', $_SERVER['PHP_SELF'] . '?action=export_file&token=' . newToken() . ($param ? '&' . $param : '') . '&sortfield=' . urlencode($sortfield) . '&sortorder=' . urlencode($sortorder), $user->hasRight('accounting', 'mouvements', 'export'));
            }
        }

        print_barre_liste($title_page, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Not display message when all the list of docs are included
        if (!getDolGlobalString('ACCOUNTING_REEXPORT')) {
            print info_admin($langs->trans("WarningDataDisappearsWhenDataIsExported"), 0, 0, 0, 'hideonsmartphone info');
        }

//$topicmail = "Information";
//$modelmail = "accountingbookkeeping";
//$objecttmp = new BookKeeping($db);
//$trackid = 'bk'.$object->id;
        include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

        $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
        $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
        if ($massactionbutton && $contextpage != 'poslist') {
            $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
        }

        $moreforfilter = '';
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('AccountingCategory') . ': ';
        $moreforfilter .= '<div class="nowrap inline-block">';
        $moreforfilter .= $formaccounting->select_accounting_category($search_account_category, 'search_account_category', 1, 0, 0, 0);
        $moreforfilter .= '</div>';
        $moreforfilter .= '</div>';

        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if (empty($reshook)) {
            $moreforfilter .= $hookmanager->resPrint;
        } else {
            $moreforfilter = $hookmanager->resPrint;
        }

        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';

        print '<div class="div-table-responsive">';
        print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">';

// Filters lines
        print '<tr class="liste_titre_filter">';
// Action column
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons('left');
            print $searchpicto;
            print '</td>';
        }
// Movement number
        if (!empty($arrayfields['t.piece_num']['checked'])) {
            print '<td class="liste_titre"><input type="text" name="search_mvt_num" size="6" value="' . dol_escape_htmltag($search_mvt_num) . '"></td>';
        }
// Code journal
        if (!empty($arrayfields['t.code_journal']['checked'])) {
            print '<td class="liste_titre center">';
            print $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1, 'small maxwidth75');
            print '</td>';
        }
// Date document
        if (!empty($arrayfields['t.doc_date']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Ref document
        if (!empty($arrayfields['t.doc_ref']['checked'])) {
            print '<td class="liste_titre"><input type="text" name="search_doc_ref" size="8" value="' . dol_escape_htmltag($search_doc_ref) . '"></td>';
        }
// Accountancy account
        if (!empty($arrayfields['t.numero_compte']['checked'])) {
            print '<td class="liste_titre">';
            print '<div class="nowrap">';
            print $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), array(), 1, 1, 'maxwidth150', 'account');
            print '</div>';
            print '<div class="nowrap">';
            print $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), array(), 1, 1, 'maxwidth150', 'account');
            print '</div>';
            print '</td>';
        }
// Subledger account
        if (!empty($arrayfields['t.subledger_account']['checked'])) {
            print '<td class="liste_titre">';
            // TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
            // use setup of keypress to select thirdparty and this hang browser on large database.
            if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
                print '<div class="nowrap">';
                //print $langs->trans('From').' ';
                print $formaccounting->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', $langs->trans('From'), 'maxwidth250', 'subledgeraccount');
                print '</div>';
                print '<div class="nowrap">';
                print $formaccounting->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', $langs->trans('to'), 'maxwidth250', 'subledgeraccount');
                print '</div>';
            } else {
                print '<input type="text" class="maxwidth75" name="search_accountancy_aux_code" value="' . dol_escape_htmltag($search_accountancy_aux_code) . '">';
            }
            print '</td>';
        }
// Label operation
        if (!empty($arrayfields['t.label_operation']['checked'])) {
            print '<td class="liste_titre">';
            print '<input type="text" size="7" class="flat" name="search_mvt_label" value="' . dol_escape_htmltag($search_mvt_label) . '"/>';
            print '</td>';
        }
// Debit
        if (!empty($arrayfields['t.debit']['checked'])) {
            print '<td class="liste_titre right">';
            print '<input type="text" class="flat" name="search_debit" size="4" value="' . dol_escape_htmltag($search_debit) . '">';
            print '</td>';
        }
// Credit
        if (!empty($arrayfields['t.credit']['checked'])) {
            print '<td class="liste_titre right">';
            print '<input type="text" class="flat" name="search_credit" size="4" value="' . dol_escape_htmltag($search_credit) . '">';
            print '</td>';
        }
// Lettering code
        if (!empty($arrayfields['t.lettering_code']['checked'])) {
            print '<td class="liste_titre center">';
            print '<input type="text" size="3" class="flat" name="search_lettering_code" value="' . dol_escape_htmltag($search_lettering_code) . '"/>';
            print '<br><span class="nowrap"><input type="checkbox" id="search_not_reconciled" name="search_not_reconciled" value="notreconciled"' . ($search_not_reconciled == 'notreconciled' ? ' checked' : '') . '><label for="search_not_reconciled">' . $langs->trans("NotReconciled") . '</label></span>';
            print '</td>';
        }

// Fields from hook
        $parameters = array('arrayfields' => $arrayfields);
        $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

// Date creation
        if (!empty($arrayfields['t.date_creation']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_creation_start, 'search_date_creation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_creation_end, 'search_date_creation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Date modification
        if (!empty($arrayfields['t.tms']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_modification_start, 'search_date_modification_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_modification_end, 'search_date_modification_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '</td>';
        }
// Date export
        if (!empty($arrayfields['t.date_export']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_export_start, 'search_date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_export_end, 'search_date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Date validation
        if (!empty($arrayfields['t.date_validated']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_validation_start, 'search_date_validation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_validation_end, 'search_date_validation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
        if (!empty($arrayfields['t.import_key']['checked'])) {
            print '<td class="liste_titre center">';
            print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="' . dol_escape_htmltag($search_import_key) . '">';
            print '</td>';
        }
// Action column
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';
        }
        print "</tr>\n";

        print '<tr class="liste_titre">';
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
        }
        if (!empty($arrayfields['t.piece_num']['checked'])) {
            print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.code_journal']['checked'])) {
            print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.doc_date']['checked'])) {
            print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.doc_ref']['checked'])) {
            print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.numero_compte']['checked'])) {
            print_liste_field_titre($arrayfields['t.numero_compte']['label'], $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.subledger_account']['checked'])) {
            print_liste_field_titre($arrayfields['t.subledger_account']['label'], $_SERVER['PHP_SELF'], "t.subledger_account", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.label_operation']['checked'])) {
            print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.debit']['checked'])) {
            print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
        }
        if (!empty($arrayfields['t.credit']['checked'])) {
            print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
        }
        if (!empty($arrayfields['t.lettering_code']['checked'])) {
            print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
        }
// Hook fields
        $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (!empty($arrayfields['t.date_creation']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_creation']['label'], $_SERVER['PHP_SELF'], "t.date_creation", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.tms']['checked'])) {
            print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER['PHP_SELF'], "t.tms", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.date_export']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.date_validated']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_validated']['label'], $_SERVER['PHP_SELF'], "t.date_validated,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.import_key']['checked'])) {
            print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER['PHP_SELF'], "t.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
        }
        print "</tr>\n";


        $line = new BookKeepingLine($db);

// Loop on record
// --------------------------------------------------------------------
        $i = 0;
        $totalarray = array();
        $totalarray['nbfield'] = 0;
        $total_debit = 0;
        $total_credit = 0;
        $totalarray['val'] = array();
        $totalarray['val']['totaldebit'] = 0;
        $totalarray['val']['totalcredit'] = 0;

        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);
            if (empty($obj)) {
                break; // Should not happen
            }

            $line->id = $obj->rowid;
            $line->doc_date = $db->jdate($obj->doc_date);
            $line->doc_type = $obj->doc_type;
            $line->doc_ref = $obj->doc_ref;
            $line->fk_doc = $obj->fk_doc;
            $line->fk_docdet = $obj->fk_docdet;
            $line->thirdparty_code = $obj->thirdparty_code;
            $line->subledger_account = $obj->subledger_account;
            $line->subledger_label = $obj->subledger_label;
            $line->numero_compte = $obj->numero_compte;
            $line->label_compte = $obj->label_compte;
            $line->label_operation = $obj->label_operation;
            $line->debit = $obj->debit;
            $line->credit = $obj->credit;
            $line->montant = $obj->amount; // deprecated
            $line->amount = $obj->amount;
            $line->sens = $obj->sens;
            $line->lettering_code = $obj->lettering_code;
            $line->fk_user_author = $obj->fk_user_author;
            $line->import_key = $obj->import_key;
            $line->code_journal = $obj->code_journal;
            $line->journal_label = $obj->journal_label;
            $line->piece_num = $obj->piece_num;
            $line->date_creation = $db->jdate($obj->date_creation);
            $line->date_modification = $db->jdate($obj->date_modification);
            $line->date_export = $db->jdate($obj->date_export);
            $line->date_validation = $db->jdate($obj->date_validation);

            $total_debit += $line->debit;
            $total_credit += $line->credit;

            print '<tr class="oddeven">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="nowraponall center">';
                if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                    $selected = 0;
                    if (in_array($line->id, $arrayofselected)) {
                        $selected = 1;
                    }
                    print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
                }
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Piece number
            if (!empty($arrayfields['t.piece_num']['checked'])) {
                print '<td>';
                $object->id = $line->id;
                $object->piece_num = $line->piece_num;
                print $object->getNomUrl(1, '', 0, '', 1);
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Journal code
            if (!empty($arrayfields['t.code_journal']['checked'])) {
                $accountingjournal = new AccountingJournal($db);
                $result = $accountingjournal->fetch('', $line->code_journal);
                $journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
                print '<td class="center tdoverflowmax150">' . $journaltoshow . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Document date
            if (!empty($arrayfields['t.doc_date']['checked'])) {
                print '<td class="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Document ref
            if (!empty($arrayfields['t.doc_ref']['checked'])) {
                if ($line->doc_type == 'customer_invoice') {
                    $langs->loadLangs(array('bills'));

                    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                    $objectstatic = new Facture($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'facture';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
                } elseif ($line->doc_type == 'supplier_invoice') {
                    $langs->loadLangs(array('bills'));

                    require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
                    $objectstatic = new FactureFournisseur($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'invoice_supplier';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($line->fk_doc, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
                    $subdir = get_exdir($objectstatic->id, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $subdir, $filedir);
                } elseif ($line->doc_type == 'expense_report') {
                    $langs->loadLangs(array('trips'));

                    require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
                    $objectstatic = new ExpenseReport($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'expensereport';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->expensereport->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
                } elseif ($line->doc_type == 'bank') {
                    require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                    $objectstatic = new AccountLine($db);
                    $objectstatic->fetch($line->fk_doc);
                } else {
                    // Other type
                }

                $labeltoshow = '';
                $labeltoshowalt = '';
                if ($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice' || $line->doc_type == 'expense_report') {
                    $labeltoshow .= $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
                    $labeltoshow .= $documentlink;
                    $labeltoshowalt .= $objectstatic->ref;
                } elseif ($line->doc_type == 'bank') {
                    $labeltoshow .= $objectstatic->getNomUrl(1);
                    $labeltoshowalt .= $objectstatic->ref;
                    $bank_ref = strstr($line->doc_ref, '-');
                    $labeltoshow .= " " . $bank_ref;
                    $labeltoshowalt .= " " . $bank_ref;
                } else {
                    $labeltoshow .= $line->doc_ref;
                    $labeltoshowalt .= $line->doc_ref;
                }

                print '<td class="nowraponall tdoverflowmax200" title="' . dol_escape_htmltag($labeltoshowalt) . '">';
                print $labeltoshow;
                print "</td>\n";
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Account number
            if (!empty($arrayfields['t.numero_compte']['checked'])) {
                print '<td>' . length_accountg($line->numero_compte) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Subledger account
            if (!empty($arrayfields['t.subledger_account']['checked'])) {
                print '<td>' . length_accounta($line->subledger_account) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Label operation
            if (!empty($arrayfields['t.label_operation']['checked'])) {
                print '<td class="small tdoverflowmax200" title="' . dol_escape_htmltag($line->label_operation) . '">' . dol_escape_htmltag($line->label_operation) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Amount debit
            if (!empty($arrayfields['t.debit']['checked'])) {
                print '<td class="right nowraponall amount">' . ($line->debit != 0 ? price($line->debit) : '') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
                }
                $totalarray['val']['totaldebit'] += $line->debit;
            }

            // Amount credit
            if (!empty($arrayfields['t.credit']['checked'])) {
                print '<td class="right nowraponall amount">' . ($line->credit != 0 ? price($line->credit) : '') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 'totalcredit';
                }
                $totalarray['val']['totalcredit'] += $line->credit;
            }

            // Lettering code
            if (!empty($arrayfields['t.lettering_code']['checked'])) {
                print '<td class="center">' . $line->lettering_code . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Fields from hook
            $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
            $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            // Creation operation date
            if (!empty($arrayfields['t.date_creation']['checked'])) {
                print '<td class="center">' . dol_print_date($line->date_creation, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Modification operation date
            if (!empty($arrayfields['t.tms']['checked'])) {
                print '<td class="center">' . dol_print_date($line->date_modification, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Exported operation date
            if (!empty($arrayfields['t.date_export']['checked'])) {
                print '<td class="center nowraponall">' . dol_print_date($line->date_export, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Validated operation date
            if (!empty($arrayfields['t.date_validated']['checked'])) {
                print '<td class="center nowraponall">' . dol_print_date($line->date_validation, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            if (!empty($arrayfields['t.import_key']['checked'])) {
                print '<td class="tdoverflowmax100">' . $obj->import_key . "</td>\n";
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="nowraponall center">';
                if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                    $selected = 0;
                    if (in_array($line->id, $arrayofselected)) {
                        $selected = 1;
                    }
                    print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
                }
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }


            print "</tr>\n";

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

        $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print "</table>";
        print '</div>';

        print '</form>';

// End of page
        llxFooter();

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/bookkeeping/list.php
     * \ingroup     Accountancy (Double entries)
     * \brief       List operation of book keeping
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

// Load translation files required by the page
        $langs->loadLangs(array("accountancy", "compta"));

// Get Parameters
        $socid = GETPOSTINT('socid');

// action+display Parameters
        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'bookkeepinglist';

// Search Parameters
        $search_mvt_num = GETPOSTINT('search_mvt_num');
        $search_doc_type = GETPOST("search_doc_type", 'alpha');
        $search_doc_ref = GETPOST("search_doc_ref", 'alpha');
        $search_date_startyear =  GETPOSTINT('search_date_startyear');
        $search_date_startmonth =  GETPOSTINT('search_date_startmonth');
        $search_date_startday =  GETPOSTINT('search_date_startday');
        $search_date_endyear =  GETPOSTINT('search_date_endyear');
        $search_date_endmonth =  GETPOSTINT('search_date_endmonth');
        $search_date_endday =  GETPOSTINT('search_date_endday');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
        $search_doc_date = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
        $search_date_creation_startyear =  GETPOSTINT('search_date_creation_startyear');
        $search_date_creation_startmonth =  GETPOSTINT('search_date_creation_startmonth');
        $search_date_creation_startday =  GETPOSTINT('search_date_creation_startday');
        $search_date_creation_endyear =  GETPOSTINT('search_date_creation_endyear');
        $search_date_creation_endmonth =  GETPOSTINT('search_date_creation_endmonth');
        $search_date_creation_endday =  GETPOSTINT('search_date_creation_endday');
        $search_date_creation_start = dol_mktime(0, 0, 0, $search_date_creation_startmonth, $search_date_creation_startday, $search_date_creation_startyear);
        $search_date_creation_end = dol_mktime(23, 59, 59, $search_date_creation_endmonth, $search_date_creation_endday, $search_date_creation_endyear);
        $search_date_modification_startyear =  GETPOSTINT('search_date_modification_startyear');
        $search_date_modification_startmonth =  GETPOSTINT('search_date_modification_startmonth');
        $search_date_modification_startday =  GETPOSTINT('search_date_modification_startday');
        $search_date_modification_endyear =  GETPOSTINT('search_date_modification_endyear');
        $search_date_modification_endmonth =  GETPOSTINT('search_date_modification_endmonth');
        $search_date_modification_endday =  GETPOSTINT('search_date_modification_endday');
        $search_date_modification_start = dol_mktime(0, 0, 0, $search_date_modification_startmonth, $search_date_modification_startday, $search_date_modification_startyear);
        $search_date_modification_end = dol_mktime(23, 59, 59, $search_date_modification_endmonth, $search_date_modification_endday, $search_date_modification_endyear);
        $search_date_export_startyear =  GETPOSTINT('search_date_export_startyear');
        $search_date_export_startmonth =  GETPOSTINT('search_date_export_startmonth');
        $search_date_export_startday =  GETPOSTINT('search_date_export_startday');
        $search_date_export_endyear =  GETPOSTINT('search_date_export_endyear');
        $search_date_export_endmonth =  GETPOSTINT('search_date_export_endmonth');
        $search_date_export_endday =  GETPOSTINT('search_date_export_endday');
        $search_date_export_start = dol_mktime(0, 0, 0, $search_date_export_startmonth, $search_date_export_startday, $search_date_export_startyear);
        $search_date_export_end = dol_mktime(23, 59, 59, $search_date_export_endmonth, $search_date_export_endday, $search_date_export_endyear);
        $search_date_validation_startyear =  GETPOSTINT('search_date_validation_startyear');
        $search_date_validation_startmonth =  GETPOSTINT('search_date_validation_startmonth');
        $search_date_validation_startday =  GETPOSTINT('search_date_validation_startday');
        $search_date_validation_endyear =  GETPOSTINT('search_date_validation_endyear');
        $search_date_validation_endmonth =  GETPOSTINT('search_date_validation_endmonth');
        $search_date_validation_endday =  GETPOSTINT('search_date_validation_endday');
        $search_date_validation_start = dol_mktime(0, 0, 0, $search_date_validation_startmonth, $search_date_validation_startday, $search_date_validation_startyear);
        $search_date_validation_end = dol_mktime(23, 59, 59, $search_date_validation_endmonth, $search_date_validation_endday, $search_date_validation_endyear);
        $search_import_key = GETPOST("search_import_key", 'alpha');

        $search_account_category = GETPOSTINT('search_account_category');

        $search_accountancy_code = GETPOST("search_accountancy_code", 'alpha');
        $search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
        if ($search_accountancy_code_start == - 1) {
            $search_accountancy_code_start = '';
        }
        $search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
        if ($search_accountancy_code_end == - 1) {
            $search_accountancy_code_end = '';
        }

        $search_accountancy_aux_code = GETPOST("search_accountancy_aux_code", 'alpha');
        $search_accountancy_aux_code_start = GETPOST('search_accountancy_aux_code_start', 'alpha');
        if ($search_accountancy_aux_code_start == - 1) {
            $search_accountancy_aux_code_start = '';
        }
        $search_accountancy_aux_code_end = GETPOST('search_accountancy_aux_code_end', 'alpha');
        if ($search_accountancy_aux_code_end == - 1) {
            $search_accountancy_aux_code_end = '';
        }
        $search_mvt_label = GETPOST('search_mvt_label', 'alpha');
        $search_direction = GETPOST('search_direction', 'alpha');
        $search_debit = GETPOST('search_debit', 'alpha');
        $search_credit = GETPOST('search_credit', 'alpha');
        $search_ledger_code = GETPOST('search_ledger_code', 'array');
        $search_lettering_code = GETPOST('search_lettering_code', 'alpha');
        $search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $optioncss = GETPOST('optioncss', 'alpha');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if ($sortorder == "") {
            $sortorder = "ASC";
        }
        if ($sortfield == "") {
            $sortfield = "t.piece_num,t.rowid";
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $object = new BookKeeping($db);
        $hookmanager->initHooks(array('bookkeepinglist'));

        $formaccounting = new FormAccounting($db);
        $form = new Form($db);

        if (!in_array($action, array('delmouv', 'delmouvconfirm')) && !GETPOSTISSET('begin') && !GETPOSTISSET('formfilteraction') && GETPOSTINT('page') == '' && !GETPOSTINT('noreset') && $user->hasRight('accounting', 'mouvements', 'export')) {
            if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('restore_lastsearch_values') && !GETPOST('search_accountancy_code_start')) {
                $query = "SELECT date_start, date_end from " . MAIN_DB_PREFIX . "accounting_fiscalyear ";
                $query .= " where date_start < '" . $db->idate(dol_now()) . "' and date_end > '" . $db->idate(dol_now()) . "' limit 1";
                $res = $db->query($query);

                if ($res->num_rows > 0) {
                    $fiscalYear = $db->fetch_object($res);
                    $search_date_start = strtotime($fiscalYear->date_start);
                    $search_date_end = strtotime($fiscalYear->date_end);
                } else {
                    $month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
                    $year_start = dol_print_date(dol_now(), '%Y');
                    if (dol_print_date(dol_now(), '%m') < $month_start) {
                        $year_start--; // If current month is lower that starting fiscal month, we start last year
                    }
                    $year_end = $year_start + 1;
                    $month_end = $month_start - 1;
                    if ($month_end < 1) {
                        $month_end = 12;
                        $year_end--;
                    }
                    $search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
                    $search_date_end = dol_get_last_day($year_end, $month_end);
                }
            }
        }


        $arrayfields = array(
            't.piece_num' => array('label' => $langs->trans("TransactionNumShort"), 'checked' => 1),
            't.code_journal' => array('label' => $langs->trans("Codejournal"), 'checked' => 1),
            't.doc_date' => array('label' => $langs->trans("Docdate"), 'checked' => 1),
            't.doc_ref' => array('label' => $langs->trans("Piece"), 'checked' => 1),
            't.numero_compte' => array('label' => $langs->trans("AccountAccountingShort"), 'checked' => 1),
            't.subledger_account' => array('label' => $langs->trans("SubledgerAccount"), 'checked' => 1),
            't.label_operation' => array('label' => $langs->trans("Label"), 'checked' => 1),
            't.debit' => array('label' => $langs->trans("AccountingDebit"), 'checked' => 1),
            't.credit' => array('label' => $langs->trans("AccountingCredit"), 'checked' => 1),
            't.lettering_code' => array('label' => $langs->trans("LetteringCode"), 'checked' => 1),
            't.date_creation' => array('label' => $langs->trans("DateCreation"), 'checked' => 0),
            't.tms' => array('label' => $langs->trans("DateModification"), 'checked' => 0),
            't.date_export' => array('label' => $langs->trans("DateExport"), 'checked' => 0),
            't.date_validated' => array('label' => $langs->trans("DateValidationAndLock"), 'checked' => 0, 'enabled' => !getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")),
            't.import_key' => array('label' => $langs->trans("ImportId"), 'checked' => 0, 'position' => 1100),
        );

        if (!getDolGlobalString('ACCOUNTING_ENABLE_LETTERING')) {
            unset($arrayfields['t.lettering_code']);
        }

        $error = 0;

        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }


        /*
         * Actions
         */

        $param = '';

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'preunletteringauto' && $massaction != 'preunletteringmanual' && $massaction != 'predeletebookkeepingwriting') {
            $massaction = '';
        }

        $parameters = array('socid' => $socid);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $search_mvt_num = '';
                $search_doc_type = '';
                $search_doc_ref = '';
                $search_doc_date = '';
                $search_account_category = '';
                $search_accountancy_code = '';
                $search_accountancy_code_start = '';
                $search_accountancy_code_end = '';
                $search_accountancy_aux_code = '';
                $search_accountancy_aux_code_start = '';
                $search_accountancy_aux_code_end = '';
                $search_mvt_label = '';
                $search_direction = '';
                $search_ledger_code = array();
                $search_date_startyear = '';
                $search_date_startmonth = '';
                $search_date_startday = '';
                $search_date_endyear = '';
                $search_date_endmonth = '';
                $search_date_endday = '';
                $search_date_start = '';
                $search_date_end = '';
                $search_date_creation_startyear = '';
                $search_date_creation_startmonth = '';
                $search_date_creation_startday = '';
                $search_date_creation_endyear = '';
                $search_date_creation_endmonth = '';
                $search_date_creation_endday = '';
                $search_date_creation_start = '';
                $search_date_creation_end = '';
                $search_date_modification_startyear = '';
                $search_date_modification_startmonth = '';
                $search_date_modification_startday = '';
                $search_date_modification_endyear = '';
                $search_date_modification_endmonth = '';
                $search_date_modification_endday = '';
                $search_date_modification_start = '';
                $search_date_modification_end = '';
                $search_date_export_startyear = '';
                $search_date_export_startmonth = '';
                $search_date_export_startday = '';
                $search_date_export_endyear = '';
                $search_date_export_endmonth = '';
                $search_date_export_endday = '';
                $search_date_export_start = '';
                $search_date_export_end = '';
                $search_date_validation_startyear = '';
                $search_date_validation_startmonth = '';
                $search_date_validation_startday = '';
                $search_date_validation_endyear = '';
                $search_date_validation_endmonth = '';
                $search_date_validation_endday = '';
                $search_date_validation_start = '';
                $search_date_validation_end = '';
                $search_debit = '';
                $search_credit = '';
                $search_lettering_code = '';
                $search_not_reconciled = '';
                $search_import_key = '';
                $toselect = array();
            }

            // Must be after the remove filter action, before the export.
            $filter = array();
            if (!empty($search_date_start)) {
                $filter['t.doc_date>='] = $search_date_start;
                $tmp = dol_getdate($search_date_start);
                $param .= '&search_date_startmonth=' . urlencode($tmp['mon']) . '&search_date_startday=' . urlencode($tmp['mday']) . '&search_date_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_end)) {
                $filter['t.doc_date<='] = $search_date_end;
                $tmp = dol_getdate($search_date_end);
                $param .= '&search_date_endmonth=' . urlencode($tmp['mon']) . '&search_date_endday=' . urlencode($tmp['mday']) . '&search_date_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_doc_date)) {
                $filter['t.doc_date'] = $search_doc_date;
                $tmp = dol_getdate($search_doc_date);
                $param .= '&doc_datemonth=' . urlencode($tmp['mon']) . '&doc_dateday=' . urlencode($tmp['mday']) . '&doc_dateyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_doc_type)) {
                $filter['t.doc_type'] = $search_doc_type;
                $param .= '&search_doc_type=' . urlencode($search_doc_type);
            }
            if (!empty($search_doc_ref)) {
                $filter['t.doc_ref'] = $search_doc_ref;
                $param .= '&search_doc_ref=' . urlencode($search_doc_ref);
            }
            if ($search_account_category != '-1' && !empty($search_account_category)) {
                require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancycategory.class.php';
                $accountingcategory = new AccountancyCategory($db);

                $listofaccountsforgroup = $accountingcategory->getCptsCat(0, 'fk_accounting_category = ' . ((int) $search_account_category));
                $listofaccountsforgroup2 = array();
                if (is_array($listofaccountsforgroup)) {
                    foreach ($listofaccountsforgroup as $tmpval) {
                        $listofaccountsforgroup2[] = "'" . $db->escape($tmpval['id']) . "'";
                    }
                }
                $filter['t.search_accounting_code_in'] = implode(',', $listofaccountsforgroup2);
                $param .= '&search_account_category=' . urlencode((string) ($search_account_category));
            }
            if (!empty($search_accountancy_code)) {
                $filter['t.numero_compte'] = $search_accountancy_code;
                $param .= '&search_accountancy_code=' . urlencode($search_accountancy_code);
            }
            if (!empty($search_accountancy_code_start)) {
                $filter['t.numero_compte>='] = $search_accountancy_code_start;
                $param .= '&search_accountancy_code_start=' . urlencode($search_accountancy_code_start);
            }
            if (!empty($search_accountancy_code_end)) {
                $filter['t.numero_compte<='] = $search_accountancy_code_end;
                $param .= '&search_accountancy_code_end=' . urlencode($search_accountancy_code_end);
            }
            if (!empty($search_accountancy_aux_code)) {
                $filter['t.subledger_account'] = $search_accountancy_aux_code;
                $param .= '&search_accountancy_aux_code=' . urlencode($search_accountancy_aux_code);
            }
            if (!empty($search_accountancy_aux_code_start)) {
                $filter['t.subledger_account>='] = $search_accountancy_aux_code_start;
                $param .= '&search_accountancy_aux_code_start=' . urlencode($search_accountancy_aux_code_start);
            }
            if (!empty($search_accountancy_aux_code_end)) {
                $filter['t.subledger_account<='] = $search_accountancy_aux_code_end;
                $param .= '&search_accountancy_aux_code_end=' . urlencode($search_accountancy_aux_code_end);
            }
            if (!empty($search_mvt_label)) {
                $filter['t.label_operation'] = $search_mvt_label;
                $param .= '&search_mvt_label=' . urlencode($search_mvt_label);
            }
            if (!empty($search_direction)) {
                $filter['t.sens'] = $search_direction;
                $param .= '&search_direction=' . urlencode($search_direction);
            }
            if (!empty($search_ledger_code)) {
                $filter['t.code_journal'] = $search_ledger_code;
                foreach ($search_ledger_code as $code) {
                    $param .= '&search_ledger_code[]=' . urlencode($code);
                }
            }
            if (!empty($search_mvt_num)) {
                $filter['t.piece_num'] = $search_mvt_num;
                $param .= '&search_mvt_num=' . urlencode((string) ($search_mvt_num));
            }
            if (!empty($search_date_creation_start)) {
                $filter['t.date_creation>='] = $search_date_creation_start;
                $tmp = dol_getdate($search_date_creation_start);
                $param .= '&search_date_creation_startmonth=' . urlencode($tmp['mon']) . '&search_date_creation_startday=' . urlencode($tmp['mday']) . '&search_date_creation_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_creation_end)) {
                $filter['t.date_creation<='] = $search_date_creation_end;
                $tmp = dol_getdate($search_date_creation_end);
                $param .= '&search_date_creation_endmonth=' . urlencode($tmp['mon']) . '&search_date_creation_endday=' . urlencode($tmp['mday']) . '&search_date_creation_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_modification_start)) {
                $filter['t.tms>='] = $search_date_modification_start;
                $tmp = dol_getdate($search_date_modification_start);
                $param .= '&search_date_modification_startmonth=' . urlencode($tmp['mon']) . '&search_date_modification_startday=' . urlencode($tmp['mday']) . '&search_date_modification_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_modification_end)) {
                $filter['t.tms<='] = $search_date_modification_end;
                $tmp = dol_getdate($search_date_modification_end);
                $param .= '&search_date_modification_endmonth=' . urlencode($tmp['mon']) . '&search_date_modification_endday=' . urlencode($tmp['mday']) . '&search_date_modification_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_export_start)) {
                $filter['t.date_export>='] = $search_date_export_start;
                $tmp = dol_getdate($search_date_export_start);
                $param .= '&search_date_export_startmonth=' . urlencode($tmp['mon']) . '&search_date_export_startday=' . urlencode($tmp['mday']) . '&search_date_export_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_export_end)) {
                $filter['t.date_export<='] = $search_date_export_end;
                $tmp = dol_getdate($search_date_export_end);
                $param .= '&search_date_export_endmonth=' . urlencode($tmp['mon']) . '&search_date_export_endday=' . urlencode($tmp['mday']) . '&search_date_export_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_validation_start)) {
                $filter['t.date_validated>='] = $search_date_validation_start;
                $tmp = dol_getdate($search_date_validation_start);
                $param .= '&search_date_validation_startmonth=' . urlencode($tmp['mon']) . '&search_date_validation_startday=' . urlencode($tmp['mday']) . '&search_date_validation_startyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_date_validation_end)) {
                $filter['t.date_validated<='] = $search_date_validation_end;
                $tmp = dol_getdate($search_date_validation_end);
                $param .= '&search_date_validation_endmonth=' . urlencode($tmp['mon']) . '&search_date_validation_endday=' . urlencode($tmp['mday']) . '&search_date_validation_endyear=' . urlencode($tmp['year']);
            }
            if (!empty($search_debit)) {
                $filter['t.debit'] = $search_debit;
                $param .= '&search_debit=' . urlencode($search_debit);
            }
            if (!empty($search_credit)) {
                $filter['t.credit'] = $search_credit;
                $param .= '&search_credit=' . urlencode($search_credit);
            }
            if (!empty($search_lettering_code)) {
                $filter['t.lettering_code'] = $search_lettering_code;
                $param .= '&search_lettering_code=' . urlencode($search_lettering_code);
            }
            if (!empty($search_not_reconciled)) {
                $filter['t.reconciled_option'] = $search_not_reconciled;
                $param .= '&search_not_reconciled=' . urlencode($search_not_reconciled);
            }
            if (!empty($search_import_key)) {
                $filter['t.import_key'] = $search_import_key;
                $param .= '&search_import_key=' . urlencode($search_import_key);
            }

            // Mass actions
            $objectclass = 'Bookkeeping';
            $objectlabel = 'Bookkeeping';
            $permissiontoread = $user->hasRight('societe', 'lire');
            $permissiontodelete = $user->hasRight('societe', 'supprimer');
            $permissiontoadd = $user->hasRight('societe', 'creer');
            $uploaddir = $conf->societe->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

            if (!$error && $action == 'deletebookkeepingwriting' && $confirm == "yes" && $user->hasRight('accounting', 'mouvements', 'supprimer')) {
                $db->begin();

                if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->bookkeepingLetteringAll($toselect, true);
                    if ($nb_lettering < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                        $error++;
                    }
                }

                $nbok = 0;
                if (!$error) {
                    foreach ($toselect as $toselectid) {
                        $result = $object->fetch($toselectid);
                        if ($result > 0 && (!isset($object->date_validation) || $object->date_validation === '')) {
                            $result = $object->deleteMvtNum($object->piece_num);
                            if ($result > 0) {
                                $nbok++;
                            } else {
                                setEventMessages($object->error, $object->errors, 'errors');
                                $error++;
                                break;
                            }
                        } elseif ($result < 0) {
                            setEventMessages($object->error, $object->errors, 'errors');
                            $error++;
                            break;
                        } elseif (isset($object->date_validation) && $object->date_validation != '') {
                            setEventMessages($langs->trans("ValidatedRecordWhereFound"), null, 'errors');
                            $error++;
                            break;
                        }
                    }
                }

                if (!$error) {
                    $db->commit();

                    // Message for elements well deleted
                    if ($nbok > 1) {
                        setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
                    } elseif ($nbok > 0) {
                        setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
                    } else {
                        setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
                    }

                    header("Location: " . $_SERVER['PHP_SELF'] . "?noreset=1" . ($param ? '&' . $param : ''));
                    exit;
                } else {
                    $db->rollback();
                }
            }

            // others mass actions
            if (!$error && getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && $user->hasRight('accounting', 'mouvements', 'creer')) {
                if ($massaction == 'letteringauto') {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->bookkeepingLetteringAll($toselect);
                    if ($nb_lettering < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                        $error++;
                        $nb_lettering = max(0, abs($nb_lettering) - 2);
                    } elseif ($nb_lettering == 0) {
                        $nb_lettering = 0;
                        setEventMessages($langs->trans('AccountancyNoLetteringModified'), array(), 'mesgs');
                    }
                    if ($nb_lettering == 1) {
                        setEventMessages($langs->trans('AccountancyOneLetteringModifiedSuccessfully'), array(), 'mesgs');
                    } elseif ($nb_lettering > 1) {
                        setEventMessages($langs->trans('AccountancyLetteringModifiedSuccessfully', $nb_lettering), array(), 'mesgs');
                    }

                    if (!$error) {
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                } elseif ($massaction == 'letteringmanual') {
                    $lettering = new Lettering($db);
                    $result = $lettering->updateLettering($toselect);
                    if ($result < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                    } else {
                        setEventMessages($langs->trans('AccountancyOneLetteringModifiedSuccessfully'), array(), 'mesgs');
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                } elseif ($action == 'unletteringauto' && $confirm == "yes") {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->bookkeepingLetteringAll($toselect, true);
                    if ($nb_lettering < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                        $error++;
                        $nb_lettering = max(0, abs($nb_lettering) - 2);
                    } elseif ($nb_lettering == 0) {
                        $nb_lettering = 0;
                        setEventMessages($langs->trans('AccountancyNoUnletteringModified'), array(), 'mesgs');
                    }
                    if ($nb_lettering == 1) {
                        setEventMessages($langs->trans('AccountancyOneUnletteringModifiedSuccessfully'), array(), 'mesgs');
                    } elseif ($nb_lettering > 1) {
                        setEventMessages($langs->trans('AccountancyUnletteringModifiedSuccessfully', $nb_lettering), array(), 'mesgs');
                    }

                    if (!$error) {
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                } elseif ($action == 'unletteringmanual' && $confirm == "yes") {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->deleteLettering($toselect);
                    if ($result < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                    } else {
                        setEventMessages($langs->trans('AccountancyOneUnletteringModifiedSuccessfully'), array(), 'mesgs');
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                }
            }
        }

// Build and execute select (used by page and export action)
// must de set after the action that set $filter
// --------------------------------------------------------------------

        $sql = 'SELECT';
        $sql .= ' t.rowid,';
        $sql .= " t.doc_date,";
        $sql .= " t.doc_type,";
        $sql .= " t.doc_ref,";
        $sql .= " t.fk_doc,";
        $sql .= " t.fk_docdet,";
        $sql .= " t.thirdparty_code,";
        $sql .= " t.subledger_account,";
        $sql .= " t.subledger_label,";
        $sql .= " t.numero_compte,";
        $sql .= " t.label_compte,";
        $sql .= " t.label_operation,";
        $sql .= " t.debit,";
        $sql .= " t.credit,";
        $sql .= " t.lettering_code,";
        $sql .= " t.montant as amount,";
        $sql .= " t.sens,";
        $sql .= " t.fk_user_author,";
        $sql .= " t.import_key,";
        $sql .= " t.code_journal,";
        $sql .= " t.journal_label,";
        $sql .= " t.piece_num,";
        $sql .= " t.date_creation,";
        $sql .= " t.tms as date_modification,";
        $sql .= " t.date_export,";
        $sql .= " t.date_validated as date_validation,";
        $sql .= " t.import_key";

        $sqlfields = $sql; // $sql fields to remove for count total

        $sql .= ' FROM ' . MAIN_DB_PREFIX . $object->table_element . ' as t';
// Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.doc_date') {
                    $sqlwhere[] = $db->sanitize($key) . " = '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date>=') {
                    $sqlwhere[] = "t.doc_date >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date<=') {
                    $sqlwhere[] = "t.doc_date <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date>') {
                    $sqlwhere[] = "t.doc_date > '" . $db->idate($value) . "'";
                } elseif ($key == 't.doc_date<') {
                    $sqlwhere[] = "t.doc_date < '" . $db->idate($value) . "'";
                } elseif ($key == 't.numero_compte>=') {
                    $sqlwhere[] = "t.numero_compte >= '" . $db->escape($value) . "'";
                } elseif ($key == 't.numero_compte<=') {
                    $sqlwhere[] = "t.numero_compte <= '" . $db->escape($value) . "'";
                } elseif ($key == 't.subledger_account>=') {
                    $sqlwhere[] = "t.subledger_account >= '" . $db->escape($value) . "'";
                } elseif ($key == 't.subledger_account<=') {
                    $sqlwhere[] = "t.subledger_account <= '" . $db->escape($value) . "'";
                } elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
                    $sqlwhere[] = $db->sanitize($key) . ' = ' . ((int) $value);
                } elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
                    $sqlwhere[] = $db->sanitize($key) . " LIKE '" . $db->escape($db->escapeforlike($value)) . "%'";
                } elseif ($key == 't.subledger_account') {
                    $sqlwhere[] = natural_search($key, $value, 0, 1);
                } elseif ($key == 't.tms>=') {
                    $sqlwhere[] = "t.tms >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.tms<=') {
                    $sqlwhere[] = "t.tms <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_creation>=') {
                    $sqlwhere[] = "t.date_creation >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_creation<=') {
                    $sqlwhere[] = "t.date_creation <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_export>=') {
                    $sqlwhere[] = "t.date_export >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_export<=') {
                    $sqlwhere[] = "t.date_export <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_validated>=') {
                    $sqlwhere[] = "t;date_validate >= '" . $db->idate($value) . "'";
                } elseif ($key == 't.date_validated<=') {
                    $sqlwhere[] = "t;date_validate <= '" . $db->idate($value) . "'";
                } elseif ($key == 't.credit' || $key == 't.debit') {
                    $sqlwhere[] = natural_search($key, $value, 1, 1);
                } elseif ($key == 't.reconciled_option') {
                    $sqlwhere[] = 't.lettering_code IS NULL';
                } elseif ($key == 't.code_journal' && !empty($value)) {
                    if (is_array($value)) {
                        $sqlwhere[] = natural_search("t.code_journal", implode(',', $value), 3, 1);
                    } else {
                        $sqlwhere[] = natural_search("t.code_journal", $value, 3, 1);
                    }
                } elseif ($key == 't.search_accounting_code_in' && !empty($value)) {
                    $sqlwhere[] = 't.numero_compte IN (' . $db->sanitize($value, 1) . ')';
                } else {
                    $sqlwhere[] = natural_search($key, $value, 0, 1);
                }
            }
        }
        $sql .= ' WHERE t.entity IN (' . getEntity('accountancy') . ')';

        if (count($sqlwhere) > 0) {
            $sql .= ' AND ' . implode(' AND ', $sqlwhere);
        }
//print $sql;

        /*
         * View
         */

        $formother = new FormOther($db);
        $formfile = new FormFile($db);

        $title_page = $langs->trans("Operations") . ' - ' . $langs->trans("Journals");

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

        $arrayofselected = is_array($toselect) ? $toselect : array();

// Output page
// --------------------------------------------------------------------
        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
        llxHeader('', $title_page, $help_url);

        $formconfirm = '';

// Print form confirm
        print $formconfirm;

//$param='';    param started before
        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . ((int) $limit);
        }

// List of mass actions available
        $arrayofmassactions = array();
        if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && $user->hasRight('accounting', 'mouvements', 'creer')) {
            $arrayofmassactions['letteringauto'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringAuto');
            $arrayofmassactions['preunletteringauto'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringAuto');
            $arrayofmassactions['letteringmanual'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringManual');
            $arrayofmassactions['preunletteringmanual'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringManual');
        }
        if ($user->hasRight('accounting', 'mouvements', 'supprimer')) {
            $arrayofmassactions['predeletebookkeepingwriting'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans("Delete");
        }
        if (GETPOSTINT('nomassaction') || in_array($massaction, array('preunletteringauto', 'preunletteringmanual', 'predeletebookkeepingwriting'))) {
            $arrayofmassactions = array();
        }
        $massactionbutton = $form->selectMassAction($massaction, $arrayofmassactions);

        print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="list">';
        if ($optioncss != '') {
            print '<input type="hidden" name="optioncss" value="' . urlencode($optioncss) . '">';
        }
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

        if (count($filter)) {
            $buttonLabel = $langs->trans("ExportFilteredList");
        } else {
            $buttonLabel = $langs->trans("ExportList");
        }

        $parameters = array('param' => $param);
        $reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        $newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

        if (empty($reshook)) {
            $newcardbutton .= dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?' . $param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
            $newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $param, '', 1, array('morecss' => 'marginleftonly'));
            $newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub' . $param, '', 1, array('morecss' => 'marginleftonly'));

            $url = './card.php?action=create';
            if (!empty($socid)) {
                $url .= '&socid=' . $socid;
            }
            $newcardbutton .= dolGetButtonTitleSeparator();
            $newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', $url, '', $user->hasRight('accounting', 'mouvements', 'creer'));
        }

        print_barre_liste($title_page, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

        if ($massaction == 'preunletteringauto') {
            print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringAuto"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringauto", null, '', 0, 200, 500, 1);
        } elseif ($massaction == 'preunletteringmanual') {
            print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringManual"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringmanual", null, '', 0, 200, 500, 1);
        } elseif ($massaction == 'predeletebookkeepingwriting') {
            print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassDeleteBookkeepingWriting"), $langs->trans("ConfirmMassDeleteBookkeepingWritingQuestion", count($toselect)), "deletebookkeepingwriting", null, '', 0, 200, 500, 1);
        }

//$topicmail = "Information";
//$modelmail = "accountingbookkeeping";
//$objecttmp = new BookKeeping($db);
//$trackid = 'bk'.$object->id;
        include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

        $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
        $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
        if ($massactionbutton && $contextpage != 'poslist') {
            $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
        }

        $moreforfilter = '';
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('AccountingCategory') . ': ';
        $moreforfilter .= '<div class="nowrap inline-block">';
        $moreforfilter .= $formaccounting->select_accounting_category($search_account_category, 'search_account_category', 1, 0, 0, 0);
        $moreforfilter .= '</div>';
        $moreforfilter .= '</div>';

        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if (empty($reshook)) {
            $moreforfilter .= $hookmanager->resPrint;
        } else {
            $moreforfilter = $hookmanager->resPrint;
        }

        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';

        print '<div class="div-table-responsive">';
        print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">';

// Filters lines
        print '<tr class="liste_titre_filter">';
// Action column
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons('left');
            print $searchpicto;
            print '</td>';
        }
// Movement number
        if (!empty($arrayfields['t.piece_num']['checked'])) {
            print '<td class="liste_titre"><input type="text" name="search_mvt_num" size="6" value="' . dol_escape_htmltag($search_mvt_num) . '"></td>';
        }
// Code journal
        if (!empty($arrayfields['t.code_journal']['checked'])) {
            print '<td class="liste_titre center">';
            print $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1, 'small maxwidth75');
            print '</td>';
        }
// Date document
        if (!empty($arrayfields['t.doc_date']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Ref document
        if (!empty($arrayfields['t.doc_ref']['checked'])) {
            print '<td class="liste_titre"><input type="text" name="search_doc_ref" size="8" value="' . dol_escape_htmltag($search_doc_ref) . '"></td>';
        }
// Accountancy account
        if (!empty($arrayfields['t.numero_compte']['checked'])) {
            print '<td class="liste_titre">';
            print '<div class="nowrap">';
            print $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), array(), 1, 1, 'maxwidth150', 'account');
            print '</div>';
            print '<div class="nowrap">';
            print $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), array(), 1, 1, 'maxwidth150', 'account');
            print '</div>';
            print '</td>';
        }
// Subledger account
        if (!empty($arrayfields['t.subledger_account']['checked'])) {
            print '<td class="liste_titre">';
            // TODO For the moment we keep a free input text instead of a combo. The select_auxaccount has problem because it does not
            // use setup of keypress to select thirdparty and this hang browser on large database.
            if (getDolGlobalString('ACCOUNTANCY_COMBO_FOR_AUX')) {
                print '<div class="nowrap">';
                //print $langs->trans('From').' ';
                print $formaccounting->select_auxaccount($search_accountancy_aux_code_start, 'search_accountancy_aux_code_start', $langs->trans('From'), 'maxwidth250', 'subledgeraccount');
                print '</div>';
                print '<div class="nowrap">';
                print $formaccounting->select_auxaccount($search_accountancy_aux_code_end, 'search_accountancy_aux_code_end', $langs->trans('to'), 'maxwidth250', 'subledgeraccount');
                print '</div>';
            } else {
                print '<input type="text" class="maxwidth75" name="search_accountancy_aux_code" value="' . dol_escape_htmltag($search_accountancy_aux_code) . '">';
            }
            print '</td>';
        }
// Label operation
        if (!empty($arrayfields['t.label_operation']['checked'])) {
            print '<td class="liste_titre">';
            print '<input type="text" size="7" class="flat" name="search_mvt_label" value="' . dol_escape_htmltag($search_mvt_label) . '"/>';
            print '</td>';
        }
// Debit
        if (!empty($arrayfields['t.debit']['checked'])) {
            print '<td class="liste_titre right">';
            print '<input type="text" class="flat" name="search_debit" size="4" value="' . dol_escape_htmltag($search_debit) . '">';
            print '</td>';
        }
// Credit
        if (!empty($arrayfields['t.credit']['checked'])) {
            print '<td class="liste_titre right">';
            print '<input type="text" class="flat" name="search_credit" size="4" value="' . dol_escape_htmltag($search_credit) . '">';
            print '</td>';
        }
// Lettering code
        if (!empty($arrayfields['t.lettering_code']['checked'])) {
            print '<td class="liste_titre center">';
            print '<input type="text" size="3" class="flat" name="search_lettering_code" value="' . dol_escape_htmltag($search_lettering_code) . '"/>';
            print '<br><span class="nowrap"><input type="checkbox" name="search_not_reconciled" value="notreconciled"' . ($search_not_reconciled == 'notreconciled' ? ' checked' : '') . '>' . $langs->trans("NotReconciled") . '</span>';
            print '</td>';
        }

// Fields from hook
        $parameters = array('arrayfields' => $arrayfields);
        $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

// Date creation
        if (!empty($arrayfields['t.date_creation']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_creation_start, 'search_date_creation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_creation_end, 'search_date_creation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Date modification
        if (!empty($arrayfields['t.tms']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_modification_start, 'search_date_modification_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_modification_end, 'search_date_modification_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '</td>';
        }
// Date export
        if (!empty($arrayfields['t.date_export']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_export_start, 'search_date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_export_end, 'search_date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Date validation
        if (!empty($arrayfields['t.date_validated']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_validation_start, 'search_date_validation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_validation_end, 'search_date_validation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
        if (!empty($arrayfields['t.import_key']['checked'])) {
            print '<td class="liste_titre center">';
            print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="' . dol_escape_htmltag($search_import_key) . '">';
            print '</td>';
        }
// Action column
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';
        }
        print "</tr>\n";

        print '<tr class="liste_titre">';
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
        }
        if (!empty($arrayfields['t.piece_num']['checked'])) {
            print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.code_journal']['checked'])) {
            print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.doc_date']['checked'])) {
            print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.doc_ref']['checked'])) {
            print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.numero_compte']['checked'])) {
            print_liste_field_titre($arrayfields['t.numero_compte']['label'], $_SERVER['PHP_SELF'], "t.numero_compte", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.subledger_account']['checked'])) {
            print_liste_field_titre($arrayfields['t.subledger_account']['label'], $_SERVER['PHP_SELF'], "t.subledger_account", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.label_operation']['checked'])) {
            print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.debit']['checked'])) {
            print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
        }
        if (!empty($arrayfields['t.credit']['checked'])) {
            print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
        }
        if (!empty($arrayfields['t.lettering_code']['checked'])) {
            print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
        }
// Hook fields
        $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (!empty($arrayfields['t.date_creation']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_creation']['label'], $_SERVER['PHP_SELF'], "t.date_creation", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.tms']['checked'])) {
            print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER['PHP_SELF'], "t.tms", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.date_export']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.date_validated']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_validated']['label'], $_SERVER['PHP_SELF'], "t.date_validated,t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.import_key']['checked'])) {
            print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER['PHP_SELF'], "t.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
        }
        print "</tr>\n";


        $line = new BookKeepingLine($db);

// Loop on record
// --------------------------------------------------------------------
        $i = 0;
        $totalarray = array();
        $totalarray['nbfield'] = 0;
        $total_debit = 0;
        $total_credit = 0;
        $totalarray['val'] = array();
        $totalarray['val']['totaldebit'] = 0;
        $totalarray['val']['totalcredit'] = 0;

        while ($i < min($num, $limit)) {
            $obj = $db->fetch_object($resql);
            if (empty($obj)) {
                break; // Should not happen
            }

            $line->id = $obj->rowid;
            $line->doc_date = $db->jdate($obj->doc_date);
            $line->doc_type = $obj->doc_type;
            $line->doc_ref = $obj->doc_ref;
            $line->fk_doc = $obj->fk_doc;
            $line->fk_docdet = $obj->fk_docdet;
            $line->thirdparty_code = $obj->thirdparty_code;
            $line->subledger_account = $obj->subledger_account;
            $line->subledger_label = $obj->subledger_label;
            $line->numero_compte = $obj->numero_compte;
            $line->label_compte = $obj->label_compte;
            $line->label_operation = $obj->label_operation;
            $line->debit = $obj->debit;
            $line->credit = $obj->credit;
            $line->montant = $obj->amount; // deprecated
            $line->amount = $obj->amount;
            $line->sens = $obj->sens;
            $line->lettering_code = $obj->lettering_code;
            $line->fk_user_author = $obj->fk_user_author;
            $line->import_key = $obj->import_key;
            $line->code_journal = $obj->code_journal;
            $line->journal_label = $obj->journal_label;
            $line->piece_num = $obj->piece_num;
            $line->date_creation = $db->jdate($obj->date_creation);
            $line->date_modification = $db->jdate($obj->date_modification);
            $line->date_export = $db->jdate($obj->date_export);
            $line->date_validation = $db->jdate($obj->date_validation);

            $total_debit += $line->debit;
            $total_credit += $line->credit;

            print '<tr class="oddeven">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="nowraponall center">';
                if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                    $selected = 0;
                    if (in_array($line->id, $arrayofselected)) {
                        $selected = 1;
                    }
                    print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
                }
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Piece number
            if (!empty($arrayfields['t.piece_num']['checked'])) {
                print '<td>';
                $object->id = $line->id;
                $object->piece_num = $line->piece_num;
                print $object->getNomUrl(1, '', 0, '', 1);
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Journal code
            if (!empty($arrayfields['t.code_journal']['checked'])) {
                $accountingjournal = new AccountingJournal($db);
                $result = $accountingjournal->fetch('', $line->code_journal);
                $journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
                print '<td class="center tdoverflowmax150">' . $journaltoshow . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Document date
            if (!empty($arrayfields['t.doc_date']['checked'])) {
                print '<td class="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Document ref
            if (!empty($arrayfields['t.doc_ref']['checked'])) {
                if ($line->doc_type == 'customer_invoice') {
                    $langs->loadLangs(array('bills'));

                    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                    $objectstatic = new Facture($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'facture';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
                } elseif ($line->doc_type == 'supplier_invoice') {
                    $langs->loadLangs(array('bills'));

                    require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
                    $objectstatic = new FactureFournisseur($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'invoice_supplier';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($line->fk_doc, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
                    $subdir = get_exdir($objectstatic->id, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $subdir, $filedir);
                } elseif ($line->doc_type == 'expense_report') {
                    $langs->loadLangs(array('trips'));

                    require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
                    $objectstatic = new ExpenseReport($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'expensereport';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->expensereport->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
                } elseif ($line->doc_type == 'bank') {
                    require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                    $objectstatic = new AccountLine($db);
                    $objectstatic->fetch($line->fk_doc);
                } else {
                    // Other type
                }

                $labeltoshow = '';
                $labeltoshowalt = '';
                if ($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice' || $line->doc_type == 'expense_report') {
                    $labeltoshow .= $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
                    $labeltoshow .= $documentlink;
                    $labeltoshowalt .= $objectstatic->ref;
                } elseif ($line->doc_type == 'bank') {
                    $labeltoshow .= $objectstatic->getNomUrl(1);
                    $labeltoshowalt .= $objectstatic->ref;
                    $bank_ref = strstr($line->doc_ref, '-');
                    $labeltoshow .= " " . $bank_ref;
                    $labeltoshowalt .= " " . $bank_ref;
                } else {
                    $labeltoshow .= $line->doc_ref;
                    $labeltoshowalt .= $line->doc_ref;
                }

                print '<td class="nowraponall tdoverflowmax250" title="' . dol_escape_htmltag($labeltoshowalt) . '">';
                print $labeltoshow;
                print "</td>\n";
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Account number
            if (!empty($arrayfields['t.numero_compte']['checked'])) {
                print '<td>' . length_accountg($line->numero_compte) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Subledger account
            if (!empty($arrayfields['t.subledger_account']['checked'])) {
                print '<td>' . length_accounta($line->subledger_account) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Label operation
            if (!empty($arrayfields['t.label_operation']['checked'])) {
                print '<td class="small tdoverflowmax200" title="' . dol_escape_htmltag($line->label_operation) . '">' . dol_escape_htmltag($line->label_operation) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Amount debit
            if (!empty($arrayfields['t.debit']['checked'])) {
                print '<td class="right nowraponall amount">' . ($line->debit != 0 ? price($line->debit) : '') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
                }
                $totalarray['val']['totaldebit'] += $line->debit;
            }

            // Amount credit
            if (!empty($arrayfields['t.credit']['checked'])) {
                print '<td class="right nowraponall amount">' . ($line->credit != 0 ? price($line->credit) : '') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 'totalcredit';
                }
                $totalarray['val']['totalcredit'] += $line->credit;
            }

            // Lettering code
            if (!empty($arrayfields['t.lettering_code']['checked'])) {
                print '<td class="center">' . $line->lettering_code . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Fields from hook
            $parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
            $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            // Creation operation date
            if (!empty($arrayfields['t.date_creation']['checked'])) {
                print '<td class="center">' . dol_print_date($line->date_creation, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Modification operation date
            if (!empty($arrayfields['t.tms']['checked'])) {
                print '<td class="center">' . dol_print_date($line->date_modification, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Exported operation date
            if (!empty($arrayfields['t.date_export']['checked'])) {
                print '<td class="center nowraponall">' . dol_print_date($line->date_export, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Validated operation date
            if (!empty($arrayfields['t.date_validated']['checked'])) {
                print '<td class="center nowraponall">' . dol_print_date($line->date_validation, 'dayhour', 'tzuserrel') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            if (!empty($arrayfields['t.import_key']['checked'])) {
                print '<td class="tdoverflowmax100">' . $obj->import_key . "</td>\n";
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="nowraponall center">';
                if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                    $selected = 0;
                    if (in_array($line->id, $arrayofselected)) {
                        $selected = 1;
                    }
                    print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
                }
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }


            print "</tr>\n";

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

        $parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print "</table>";
        print '</div>';

        print '</form>';

// End of page
        llxFooter();

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/bookkeeping/listbyaccount.php
     * \ingroup     Accountancy (Double entries)
     * \brief       List operation of ledger ordered by account number
     */
    public function listbyaccount()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(array("accountancy", "compta"));

        $action = GETPOST('action', 'aZ09');
        $socid = GETPOSTINT('socid');
        $massaction = GETPOST('massaction', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $type = GETPOST('type', 'alpha');
        if ($type == 'sub') {
            $context_default = 'bookkeepingbysubaccountlist';
        } else {
            $context_default = 'bookkeepingbyaccountlist';
        }
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : $context_default;
        $search_date_startyear =  GETPOSTINT('search_date_startyear');
        $search_date_startmonth =  GETPOSTINT('search_date_startmonth');
        $search_date_startday =  GETPOSTINT('search_date_startday');
        $search_date_endyear =  GETPOSTINT('search_date_endyear');
        $search_date_endmonth =  GETPOSTINT('search_date_endmonth');
        $search_date_endday =  GETPOSTINT('search_date_endday');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
        $search_doc_date = dol_mktime(0, 0, 0, GETPOSTINT('doc_datemonth'), GETPOSTINT('doc_dateday'), GETPOSTINT('doc_dateyear'));
        $search_date_export_startyear =  GETPOSTINT('search_date_export_startyear');
        $search_date_export_startmonth =  GETPOSTINT('search_date_export_startmonth');
        $search_date_export_startday =  GETPOSTINT('search_date_export_startday');
        $search_date_export_endyear =  GETPOSTINT('search_date_export_endyear');
        $search_date_export_endmonth =  GETPOSTINT('search_date_export_endmonth');
        $search_date_export_endday =  GETPOSTINT('search_date_export_endday');
        $search_date_export_start = dol_mktime(0, 0, 0, $search_date_export_startmonth, $search_date_export_startday, $search_date_export_startyear);
        $search_date_export_end = dol_mktime(23, 59, 59, $search_date_export_endmonth, $search_date_export_endday, $search_date_export_endyear);
        $search_date_validation_startyear =  GETPOSTINT('search_date_validation_startyear');
        $search_date_validation_startmonth =  GETPOSTINT('search_date_validation_startmonth');
        $search_date_validation_startday =  GETPOSTINT('search_date_validation_startday');
        $search_date_validation_endyear =  GETPOSTINT('search_date_validation_endyear');
        $search_date_validation_endmonth =  GETPOSTINT('search_date_validation_endmonth');
        $search_date_validation_endday =  GETPOSTINT('search_date_validation_endday');
        $search_date_validation_start = dol_mktime(0, 0, 0, $search_date_validation_startmonth, $search_date_validation_startday, $search_date_validation_startyear);
        $search_date_validation_end = dol_mktime(23, 59, 59, $search_date_validation_endmonth, $search_date_validation_endday, $search_date_validation_endyear);
        $search_import_key = GETPOST("search_import_key", 'alpha');

        $search_account_category = GETPOSTINT('search_account_category');

        $search_accountancy_code_start = GETPOST('search_accountancy_code_start', 'alpha');
        if ($search_accountancy_code_start == - 1) {
            $search_accountancy_code_start = '';
        }
        $search_accountancy_code_end = GETPOST('search_accountancy_code_end', 'alpha');
        if ($search_accountancy_code_end == - 1) {
            $search_accountancy_code_end = '';
        }
        $search_doc_ref = GETPOST('search_doc_ref', 'alpha');
        $search_label_operation = GETPOST('search_label_operation', 'alpha');
        $search_mvt_num = GETPOSTINT('search_mvt_num');
        $search_direction = GETPOST('search_direction', 'alpha');
        $search_ledger_code = GETPOST('search_ledger_code', 'array');
        $search_debit = GETPOST('search_debit', 'alpha');
        $search_credit = GETPOST('search_credit', 'alpha');
        $search_lettering_code = GETPOST('search_lettering_code', 'alpha');
        $search_not_reconciled = GETPOST('search_not_reconciled', 'alpha');

        if (GETPOST("button_delmvt_x") || GETPOST("button_delmvt.x") || GETPOST("button_delmvt")) {
            $action = 'delbookkeepingyear';
        }

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $optioncss = GETPOST('optioncss', 'alpha');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if ($sortorder == "") {
            $sortorder = "ASC";
        }
        if ($sortfield == "") {
            $sortfield = "t.doc_date,t.rowid";
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $object = new BookKeeping($db);
        $formfile = new FormFile($db);
        $hookmanager->initHooks(array($context_default));

        $formaccounting = new FormAccounting($db);
        $form = new Form($db);

        if (empty($search_date_start) && empty($search_date_end) && !GETPOSTISSET('search_date_startday') && !GETPOSTISSET('search_date_startmonth') && !GETPOSTISSET('search_date_starthour')) {
            $sql = "SELECT date_start, date_end from " . MAIN_DB_PREFIX . "accounting_fiscalyear ";
            $sql .= " where date_start < '" . $db->idate(dol_now()) . "' and date_end > '" . $db->idate(dol_now()) . "'";
            $sql .= $db->plimit(1);
            $res = $db->query($sql);

            if ($res->num_rows > 0) {
                $fiscalYear = $db->fetch_object($res);
                $search_date_start = strtotime($fiscalYear->date_start);
                $search_date_end = strtotime($fiscalYear->date_end);
            } else {
                $month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
                $year_start = dol_print_date(dol_now(), '%Y');
                if (dol_print_date(dol_now(), '%m') < $month_start) {
                    $year_start--; // If current month is lower that starting fiscal month, we start last year
                }
                $year_end = $year_start + 1;
                $month_end = $month_start - 1;
                if ($month_end < 1) {
                    $month_end = 12;
                    $year_end--;
                }
                $search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
                $search_date_end = dol_get_last_day($year_end, $month_end);
            }
        }

        $arrayfields = array(
            // 't.subledger_account'=>array('label'=>$langs->trans("SubledgerAccount"), 'checked'=>1),
            't.piece_num' => array('label' => $langs->trans("TransactionNumShort"), 'checked' => 1),
            't.code_journal' => array('label' => $langs->trans("Codejournal"), 'checked' => 1),
            't.doc_date' => array('label' => $langs->trans("Docdate"), 'checked' => 1),
            't.doc_ref' => array('label' => $langs->trans("Piece"), 'checked' => 1),
            't.label_operation' => array('label' => $langs->trans("Label"), 'checked' => 1),
            't.lettering_code' => array('label' => $langs->trans("Lettering"), 'checked' => 1),
            't.debit' => array('label' => $langs->trans("AccountingDebit"), 'checked' => 1),
            't.credit' => array('label' => $langs->trans("AccountingCredit"), 'checked' => 1),
            't.balance' => array('label' => $langs->trans("Balance"), 'checked' => 1),
            't.date_export' => array('label' => $langs->trans("DateExport"), 'checked' => -1),
            't.date_validated' => array('label' => $langs->trans("DateValidation"), 'checked' => -1, 'enabled' => !getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")),
            't.import_key' => array('label' => $langs->trans("ImportId"), 'checked' => -1, 'position' => 1100),
        );

        if (!getDolGlobalString('ACCOUNTING_ENABLE_LETTERING')) {
            unset($arrayfields['t.lettering_code']);
        }

        if ($search_date_start && empty($search_date_startyear)) {
            $tmparray = dol_getdate($search_date_start);
            $search_date_startyear = $tmparray['year'];
            $search_date_startmonth = $tmparray['mon'];
            $search_date_startday = $tmparray['mday'];
        }
        if ($search_date_end && empty($search_date_endyear)) {
            $tmparray = dol_getdate($search_date_end);
            $search_date_endyear = $tmparray['year'];
            $search_date_endmonth = $tmparray['mon'];
            $search_date_endday = $tmparray['mday'];
        }

        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
            accessforbidden();
        }

        $error = 0;


        /*
         * Action
         */

        $param = '';

        if (GETPOST('cancel', 'alpha')) {
            $action = 'list';
            $massaction = '';
        }
        if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'preunletteringauto' && $massaction != 'preunletteringmanual' && $massaction != 'predeletebookkeepingwriting') {
            $massaction = '';
        }

        $parameters = array('socid' => $socid);
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
                $search_doc_date = '';
                $search_account_category = '';
                $search_accountancy_code_start = '';
                $search_accountancy_code_end = '';
                $search_label_account = '';
                $search_doc_ref = '';
                $search_label_operation = '';
                $search_mvt_num = '';
                $search_direction = '';
                $search_ledger_code = array();
                $search_date_start = '';
                $search_date_end = '';
                $search_date_startyear = '';
                $search_date_startmonth = '';
                $search_date_startday = '';
                $search_date_endyear = '';
                $search_date_endmonth = '';
                $search_date_endday = '';
                $search_date_export_start = '';
                $search_date_export_end = '';
                $search_date_export_startyear = '';
                $search_date_export_startmonth = '';
                $search_date_export_startday = '';
                $search_date_export_endyear = '';
                $search_date_export_endmonth = '';
                $search_date_export_endday = '';
                $search_date_validation_start = '';
                $search_date_validation_end = '';
                $search_date_validation_startyear = '';
                $search_date_validation_startmonth = '';
                $search_date_validation_startday = '';
                $search_date_validation_endyear = '';
                $search_date_validation_endmonth = '';
                $search_date_validation_endday = '';
                $search_lettering_code = '';
                $search_debit = '';
                $search_credit = '';
                $search_not_reconciled = '';
                $search_import_key = '';
                $toselect = array();
            }

            // Must be after the remove filter action, before the export.
            $filter = array();

            if (!empty($search_date_start)) {
                $filter['t.doc_date>='] = $search_date_start;
                $param .= '&search_date_startmonth=' . $search_date_startmonth . '&search_date_startday=' . $search_date_startday . '&search_date_startyear=' . $search_date_startyear;
            }
            if (!empty($search_date_end)) {
                $filter['t.doc_date<='] = $search_date_end;
                $param .= '&search_date_endmonth=' . $search_date_endmonth . '&search_date_endday=' . $search_date_endday . '&search_date_endyear=' . $search_date_endyear;
            }
            if (!empty($search_doc_date)) {
                $filter['t.doc_date'] = $search_doc_date;
                $param .= '&doc_datemonth=' . GETPOSTINT('doc_datemonth') . '&doc_dateday=' . GETPOSTINT('doc_dateday') . '&doc_dateyear=' . GETPOSTINT('doc_dateyear');
            }
            if ($search_account_category != '-1' && !empty($search_account_category)) {
                require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountancycategory.class.php';
                $accountingcategory = new AccountancyCategory($db);

                $listofaccountsforgroup = $accountingcategory->getCptsCat(0, 'fk_accounting_category = ' . ((int) $search_account_category));
                $listofaccountsforgroup2 = array();
                if (is_array($listofaccountsforgroup)) {
                    foreach ($listofaccountsforgroup as $tmpval) {
                        $listofaccountsforgroup2[] = "'" . $db->escape($tmpval['id']) . "'";
                    }
                }
                $filter['t.search_accounting_code_in'] = implode(',', $listofaccountsforgroup2);
                $param .= '&search_account_category=' . urlencode((string) ($search_account_category));
            }
            if (!empty($search_accountancy_code_start)) {
                if ($type == 'sub') {
                    $filter['t.subledger_account>='] = $search_accountancy_code_start;
                } else {
                    $filter['t.numero_compte>='] = $search_accountancy_code_start;
                }
                $param .= '&search_accountancy_code_start=' . urlencode($search_accountancy_code_start);
            }
            if (!empty($search_accountancy_code_end)) {
                if ($type == 'sub') {
                    $filter['t.subledger_account<='] = $search_accountancy_code_end;
                } else {
                    $filter['t.numero_compte<='] = $search_accountancy_code_end;
                }
                $param .= '&search_accountancy_code_end=' . urlencode($search_accountancy_code_end);
            }
            if (!empty($search_label_account)) {
                $filter['t.label_compte'] = $search_label_account;
                $param .= '&search_label_compte=' . urlencode($search_label_account);
            }
            if (!empty($search_mvt_num)) {
                $filter['t.piece_num'] = $search_mvt_num;
                $param .= '&search_mvt_num=' . urlencode((string) ($search_mvt_num));
            }
            if (!empty($search_doc_ref)) {
                $filter['t.doc_ref'] = $search_doc_ref;
                $param .= '&search_doc_ref=' . urlencode($search_doc_ref);
            }
            if (!empty($search_label_operation)) {
                $filter['t.label_operation'] = $search_label_operation;
                $param .= '&search_label_operation=' . urlencode($search_label_operation);
            }
            if (!empty($search_direction)) {
                $filter['t.sens'] = $search_direction;
                $param .= '&search_direction=' . urlencode($search_direction);
            }
            if (!empty($search_ledger_code)) {
                $filter['t.code_journal'] = $search_ledger_code;
                foreach ($search_ledger_code as $code) {
                    $param .= '&search_ledger_code[]=' . urlencode($code);
                }
            }
            if (!empty($search_lettering_code)) {
                $filter['t.lettering_code'] = $search_lettering_code;
                $param .= '&search_lettering_code=' . urlencode($search_lettering_code);
            }
            if (!empty($search_debit)) {
                $filter['t.debit'] = $search_debit;
                $param .= '&search_debit=' . urlencode($search_debit);
            }
            if (!empty($search_credit)) {
                $filter['t.credit'] = $search_credit;
                $param .= '&search_credit=' . urlencode($search_credit);
            }
            if (!empty($search_not_reconciled)) {
                $filter['t.reconciled_option'] = $search_not_reconciled;
                $param .= '&search_not_reconciled=' . urlencode($search_not_reconciled);
            }
            if (!empty($search_date_export_start)) {
                $filter['t.date_export>='] = $search_date_export_start;
                $param .= '&search_date_export_startmonth=' . $search_date_export_startmonth . '&search_date_export_startday=' . $search_date_export_startday . '&search_date_export_startyear=' . $search_date_export_startyear;
            }
            if (!empty($search_date_export_end)) {
                $filter['t.date_export<='] = $search_date_export_end;
                $param .= '&search_date_export_endmonth=' . $search_date_export_endmonth . '&search_date_export_endday=' . $search_date_export_endday . '&search_date_export_endyear=' . $search_date_export_endyear;
            }
            if (!empty($search_date_validation_start)) {
                $filter['t.date_validated>='] = $search_date_validation_start;
                $param .= '&search_date_validation_startmonth=' . $search_date_validation_startmonth . '&search_date_validation_startday=' . $search_date_validation_startday . '&search_date_validation_startyear=' . $search_date_validation_startyear;
            }
            if (!empty($search_date_validation_end)) {
                $filter['t.date_validated<='] = $search_date_validation_end;
                $param .= '&search_date_validation_endmonth=' . $search_date_validation_endmonth . '&search_date_validation_endday=' . $search_date_validation_endday . '&search_date_validation_endyear=' . $search_date_validation_endyear;
            }
            if (!empty($search_import_key)) {
                $filter['t.import_key'] = $search_import_key;
                $param .= '&search_import_key=' . urlencode($search_import_key);
            }
            // param with type of list
            $url_param = substr($param, 1); // remove first "&"
            if (!empty($type)) {
                $param = '&type=' . $type . $param;
            }

            //if ($action == 'delbookkeepingyearconfirm' && $user->hasRight('accounting', 'mouvements', 'supprimer')_tous) {
            //  $delmonth = GETPOST('delmonth', 'int');
            //  $delyear = GETPOST('delyear', 'int');
            //  if ($delyear == -1) {
            //      $delyear = 0;
            //  }
            //  $deljournal = GETPOST('deljournal', 'alpha');
            //  if ($deljournal == -1) {
            //      $deljournal = 0;
            //  }
            //
            //  if (!empty($delmonth) || !empty($delyear) || !empty($deljournal)) {
            //      $result = $object->deleteByYearAndJournal($delyear, $deljournal, '', ($delmonth > 0 ? $delmonth : 0));
            //      if ($result < 0) {
            //          setEventMessages($object->error, $object->errors, 'errors');
            //      } else {
            //          setEventMessages("RecordDeleted", null, 'mesgs');
            //      }
            //
            //      // Make a redirect to avoid to launch the delete later after a back button
            //      header("Location: ".$_SERVER['PHP_SELF'].($param ? '?'.$param : ''));
            //      exit;
            //  } else {
            //      setEventMessages("NoRecordDeleted", null, 'warnings');
            //  }
            //}

            // Mass actions
            $objectclass = 'Bookkeeping';
            $objectlabel = 'Bookkeeping';
            $permissiontoread = $user->hasRight('societe', 'lire');
            $permissiontodelete = $user->hasRight('societe', 'supprimer');
            $permissiontoadd = $user->hasRight('societe', 'creer');
            $uploaddir = $conf->societe->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';

            if (!$error && $action == 'deletebookkeepingwriting' && $confirm == "yes" && $user->hasRight('accounting', 'mouvements', 'supprimer')) {
                $db->begin();

                if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING')) {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->bookkeepingLetteringAll($toselect, true);
                    if ($nb_lettering < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                        $error++;
                    }
                }

                $nbok = 0;
                if (!$error) {
                    foreach ($toselect as $toselectid) {
                        $result = $object->fetch($toselectid);
                        if ($result > 0 && (!isset($object->date_validation) || $object->date_validation === '')) {
                            $result = $object->deleteMvtNum($object->piece_num);
                            if ($result > 0) {
                                $nbok++;
                            } else {
                                setEventMessages($object->error, $object->errors, 'errors');
                                $error++;
                                break;
                            }
                        } elseif ($result < 0) {
                            setEventMessages($object->error, $object->errors, 'errors');
                            $error++;
                            break;
                        } elseif (isset($object->date_validation) && $object->date_validation != '') {
                            setEventMessages($langs->trans("ValidatedRecordWhereFound"), null, 'errors');
                            $error++;
                            break;
                        }
                    }
                }

                if (!$error) {
                    $db->commit();

                    // Message for elements well deleted
                    if ($nbok > 1) {
                        setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
                    } elseif ($nbok > 0) {
                        setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
                    } elseif (!$error) {
                        setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
                    }

                    header("Location: " . $_SERVER['PHP_SELF'] . "?noreset=1" . ($param ? '&' . $param : ''));
                    exit;
                } else {
                    $db->rollback();
                }
            }

            // others mass actions
            if (!$error && getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && $user->hasRight('accounting', 'mouvements', 'creer')) {
                if ($massaction == 'letteringauto') {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->bookkeepingLetteringAll($toselect);
                    if ($nb_lettering < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                        $error++;
                        $nb_lettering = max(0, abs($nb_lettering) - 2);
                    } elseif ($nb_lettering == 0) {
                        $nb_lettering = 0;
                        setEventMessages($langs->trans('AccountancyNoLetteringModified'), array(), 'mesgs');
                    }
                    if ($nb_lettering == 1) {
                        setEventMessages($langs->trans('AccountancyOneLetteringModifiedSuccessfully'), array(), 'mesgs');
                    } elseif ($nb_lettering > 1) {
                        setEventMessages($langs->trans('AccountancyLetteringModifiedSuccessfully', $nb_lettering), array(), 'mesgs');
                    }

                    if (!$error) {
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                } elseif ($massaction == 'letteringmanual') {
                    $lettering = new Lettering($db);
                    $result = $lettering->updateLettering($toselect);
                    if ($result < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                    } else {
                        setEventMessages($langs->trans('AccountancyOneLetteringModifiedSuccessfully'), array(), 'mesgs');
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                } elseif ($action == 'unletteringauto' && $confirm == "yes") {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->bookkeepingLetteringAll($toselect, true);
                    if ($nb_lettering < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                        $error++;
                        $nb_lettering = max(0, abs($nb_lettering) - 2);
                    } elseif ($nb_lettering == 0) {
                        $nb_lettering = 0;
                        setEventMessages($langs->trans('AccountancyNoUnletteringModified'), array(), 'mesgs');
                    }
                    if ($nb_lettering == 1) {
                        setEventMessages($langs->trans('AccountancyOneUnletteringModifiedSuccessfully'), array(), 'mesgs');
                    } elseif ($nb_lettering > 1) {
                        setEventMessages($langs->trans('AccountancyUnletteringModifiedSuccessfully', $nb_lettering), array(), 'mesgs');
                    }

                    if (!$error) {
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                } elseif ($action == 'unletteringmanual' && $confirm == "yes") {
                    $lettering = new Lettering($db);
                    $nb_lettering = $lettering->deleteLettering($toselect);
                    if ($result < 0) {
                        setEventMessages('', $lettering->errors, 'errors');
                    } else {
                        setEventMessages($langs->trans('AccountancyOneUnletteringModifiedSuccessfully'), array(), 'mesgs');
                        header('Location: ' . $_SERVER['PHP_SELF'] . '?noreset=1' . $param);
                        exit();
                    }
                }
            }
        }


        /*
         * View
         */

        $formaccounting = new FormAccounting($db);
        $formfile = new FormFile($db);
        $formother = new FormOther($db);
        $form = new Form($db);

        $title_page = $langs->trans("Operations") . ' - ' . $langs->trans("VueByAccountAccounting") . ' (';
        if ($type == 'sub') {
            $title_page .= $langs->trans("BookkeepingSubAccount");
        } else {
            $title_page .= $langs->trans("Bookkeeping");
        }
        $title_page .= ')';
        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
        llxHeader('', $title_page, $help_url);

// List
        $nbtotalofrecords = '';
        if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
            // TODO Perf Replace this by a count
            if ($type == 'sub') {
                $nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter, 'AND', 1, 1);
            } else {
                $nbtotalofrecords = $object->fetchAllByAccount($sortorder, $sortfield, 0, 0, $filter, 'AND', 0, 1);
            }

            if ($nbtotalofrecords < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
                $error++;
            }
        }

        if (!$error) {
            if ($type == 'sub') {
                $result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 1);
            } else {
                $result = $object->fetchAllByAccount($sortorder, $sortfield, $limit, $offset, $filter, 'AND', 0);
            }

            if ($result < 0) {
                setEventMessages($object->error, $object->errors, 'errors');
            }
        }

        $arrayofselected = is_array($toselect) ? $toselect : array();

        $num = count($object->lines);


///if ($action == 'delbookkeepingyear') {
//  $form_question = array();
//  $delyear = GETPOST('delyear', 'int');
//  $deljournal = GETPOST('deljournal', 'alpha');
//
//  if (empty($delyear)) {
//      $delyear = dol_print_date(dol_now(), '%Y');
//  }
//  $month_array = array();
//  for ($i = 1; $i <= 12; $i++) {
//      $month_array[$i] = $langs->trans("Month".sprintf("%02d", $i));
//  }
//  $year_array = $formaccounting->selectyear_accountancy_bookkepping($delyear, 'delyear', 0, 'array');
//  $journal_array = $formaccounting->select_journal($deljournal, 'deljournal', '', 1, 1, 1, '', 0, 1);
//
//  $form_question['delmonth'] = array(
//      'name' => 'delmonth',
//      'type' => 'select',
//      'label' => $langs->trans('DelMonth'),
//      'values' => $month_array,
//      'default' => ''
//  );
//  $form_question['delyear'] = array(
//      'name' => 'delyear',
//      'type' => 'select',
//      'label' => $langs->trans('DelYear'),
//      'values' => $year_array,
//      'default' => $delyear
//  );
//  $form_question['deljournal'] = array(
//      'name' => 'deljournal',
//      'type' => 'other', // We don't use select here, the journal_array is already a select html component
//      'label' => $langs->trans('DelJournal'),
//      'value' => $journal_array,
//      'default' => $deljournal
//  );
//
//  $formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?'.$param, $langs->trans('DeleteMvt'), $langs->trans('ConfirmDeleteMvt', $langs->transnoentitiesnoconv("RegistrationInAccounting")), 'delbookkeepingyearconfirm', $form_question, '', 1, 300);
//}

// Print form confirm
        $formconfirm = '';
        print $formconfirm;

// List of mass actions available
        $arrayofmassactions = array();
        if (getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && $user->hasRight('accounting', 'mouvements', 'creer')) {
            $arrayofmassactions['letteringauto'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringAuto');
            $arrayofmassactions['preunletteringauto'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringAuto');
            $arrayofmassactions['letteringmanual'] = img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans('LetteringManual');
            $arrayofmassactions['preunletteringmanual'] = img_picto('', 'uncheck', 'class="pictofixedwidth"') . $langs->trans('UnletteringManual');
        }
        if ($user->hasRight('accounting', 'mouvements', 'supprimer')) {
            $arrayofmassactions['predeletebookkeepingwriting'] = img_picto('', 'delete', 'class="pictofixedwidth"') . $langs->trans("Delete");
        }
        if (GETPOSTINT('nomassaction') || in_array($massaction, array('preunletteringauto', 'preunletteringmanual', 'predeletebookkeepingwriting'))) {
            $arrayofmassactions = array();
        }
        $massactionbutton = $form->selectMassAction($massaction, $arrayofmassactions);

        print '<form method="POST" id="searchFormList" action="' . $_SERVER['PHP_SELF'] . '">';
        print '<input type="hidden" name="token" value="' . newToken() . '">';
        print '<input type="hidden" name="action" value="list">';
        if ($optioncss != '') {
            print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
        }
        print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
        print '<input type="hidden" name="type" value="' . $type . '">';
        print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
        print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
        print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

        $parameters = array('param' => $param);
        $reshook = $hookmanager->executeHooks('addMoreActionsButtonsList', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        $newcardbutton = empty($hookmanager->resPrint) ? '' : $hookmanager->resPrint;

        if (empty($reshook)) {
            $newcardbutton = dolGetButtonTitle($langs->trans('ViewFlatList'), '', 'fa fa-list paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/list.php?' . $param);
            if ($type == 'sub') {
                $newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
                $newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
            } else {
                $newcardbutton .= dolGetButtonTitle($langs->trans('GroupByAccountAccounting'), '', 'fa fa-stream paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?' . $url_param, '', 1, array('morecss' => 'marginleftonly btnTitleSelected'));
                $newcardbutton .= dolGetButtonTitle($langs->trans('GroupBySubAccountAccounting'), '', 'fa fa-align-left vmirror paddingleft imgforviewmode', DOL_URL_ROOT . '/accountancy/bookkeeping/listbyaccount.php?type=sub&' . $url_param, '', 1, array('morecss' => 'marginleftonly'));
            }
            $newcardbutton .= dolGetButtonTitleSeparator();
            $newcardbutton .= dolGetButtonTitle($langs->trans('NewAccountingMvt'), '', 'fa fa-plus-circle paddingleft', DOL_URL_ROOT . '/accountancy/bookkeeping/card.php?action=create');
        }

        if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
            $param .= '&contextpage=' . urlencode($contextpage);
        }
        if ($limit > 0 && $limit != $conf->liste_limit) {
            $param .= '&limit=' . ((int) $limit);
        }

        print_barre_liste($title_page, $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $result, $nbtotalofrecords, 'title_accountancy', 0, $newcardbutton, '', $limit, 0, 0, 1);

        if ($massaction == 'preunletteringauto') {
            print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringAuto"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringauto", null, '', 0, 200, 500, 1);
        } elseif ($massaction == 'preunletteringmanual') {
            print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassUnletteringManual"), $langs->trans("ConfirmMassUnletteringQuestion", count($toselect)), "unletteringmanual", null, '', 0, 200, 500, 1);
        } elseif ($massaction == 'predeletebookkeepingwriting') {
            print $form->formconfirm($_SERVER['PHP_SELF'], $langs->trans("ConfirmMassDeleteBookkeepingWriting"), $langs->trans("ConfirmMassDeleteBookkeepingWritingQuestion", count($toselect)), "deletebookkeepingwriting", null, '', 0, 200, 500, 1);
        }
//DeleteMvt=Supprimer des lignes d'opérations de la comptabilité
//DelMonth=Mois à effacer
//DelYear=Année à supprimer
//DelJournal=Journal à supprimer
//ConfirmDeleteMvt=Cette action supprime les lignes des opérations pour l'année/mois et/ou pour le journal sélectionné (au moins un critère est requis). Vous devrez utiliser de nouveau la fonctionnalité '%s' pour retrouver vos écritures dans la comptabilité.
//ConfirmDeleteMvtPartial=Cette action supprime l'écriture de la comptabilité (toutes les lignes opérations liées à une même écriture seront effacées).

//$topicmail = "Information";
//$modelmail = "accountingbookkeeping";
//$objecttmp = new BookKeeping($db);
//$trackid = 'bk'.$object->id;
        include DOL_DOCUMENT_ROOT . '/core/tpl/massactions_pre.tpl.php';

        $varpage = empty($contextpage) ? $_SERVER['PHP_SELF'] : $contextpage;
        $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
        if ($massactionbutton && $contextpage != 'poslist') {
            $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
        }

// Reverse sort order
        if (preg_match('/^asc/i', $sortorder)) {
            $sortorder = "asc";
        } else {
            $sortorder = "desc";
        }

// Warning to explain why list of record is not consistent with the other list view (missing a lot of lines)
        if ($type == 'sub') {
            print info_admin($langs->trans("WarningRecordWithoutSubledgerAreExcluded"));
        }

        $moreforfilter = '';

// Search on accountancy custom groups or account
        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('AccountAccounting') . ': ';
        $moreforfilter .= '<div class="nowrap inline-block">';
        if ($type == 'sub') {
            $moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), 'maxwidth200');
        } else {
            $moreforfilter .= $formaccounting->select_account($search_accountancy_code_start, 'search_accountancy_code_start', $langs->trans('From'), array(), 1, 1, 'maxwidth200');
        }
        $moreforfilter .= ' ';
        if ($type == 'sub') {
            $moreforfilter .= $formaccounting->select_auxaccount($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), 'maxwidth200');
        } else {
            $moreforfilter .= $formaccounting->select_account($search_accountancy_code_end, 'search_accountancy_code_end', $langs->trans('to'), array(), 1, 1, 'maxwidth200');
        }
        $stringforfirstkey = $langs->trans("KeyboardShortcut");
        if ($conf->browser->name == 'chrome') {
            $stringforfirstkey .= ' ALT +';
        } elseif ($conf->browser->name == 'firefox') {
            $stringforfirstkey .= ' ALT + SHIFT +';
        } else {
            $stringforfirstkey .= ' CTL +';
        }
        $moreforfilter .= '&nbsp;&nbsp;&nbsp;<a id="previous_account" accesskey="p" title="' . $stringforfirstkey . ' p" class="classfortooltip" href="#"><i class="fa fa-chevron-left"></i></a>';
        $moreforfilter .= '&nbsp;&nbsp;&nbsp;<a id="next_account" accesskey="n" title="' . $stringforfirstkey . ' n" class="classfortooltip" href="#"><i class="fa fa-chevron-right"></i></a>';
        $moreforfilter .= <<<SCRIPT
<script type="text/javascript">
	jQuery(document).ready(function() {
		var searchFormList = $('#searchFormList');
		var searchAccountancyCodeStart = $('#search_accountancy_code_start');
		var searchAccountancyCodeEnd = $('#search_accountancy_code_end');
		jQuery('#previous_account').on('click', function() {
			var previousOption = searchAccountancyCodeStart.find('option:selected').prev('option');
			if (previousOption.length == 1) searchAccountancyCodeStart.val(previousOption.attr('value'));
			searchAccountancyCodeEnd.val(searchAccountancyCodeStart.val());
			searchFormList.submit();
		});
		jQuery('#next_account').on('click', function() {
			var nextOption = searchAccountancyCodeStart.find('option:selected').next('option');
			if (nextOption.length == 1) searchAccountancyCodeStart.val(nextOption.attr('value'));
			searchAccountancyCodeEnd.val(searchAccountancyCodeStart.val());
			searchFormList.submit();
		});
		jQuery('input[name="search_mvt_num"]').on("keypress", function(event) {
			console.log(event);
		});
	});
</script>
SCRIPT;
        $moreforfilter .= '</div>';
        $moreforfilter .= '</div>';

        $moreforfilter .= '<div class="divsearchfield">';
        $moreforfilter .= $langs->trans('AccountingCategory') . ': ';
        $moreforfilter .= '<div class="nowrap inline-block">';
        $moreforfilter .= $formaccounting->select_accounting_category($search_account_category, 'search_account_category', 1, 0, 0, 0);
        $moreforfilter .= '</div>';
        $moreforfilter .= '</div>';

        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
        if (empty($reshook)) {
            $moreforfilter .= $hookmanager->resPrint;
        } else {
            $moreforfilter = $hookmanager->resPrint;
        }

        print '<div class="liste_titre liste_titre_bydiv centpercent">';
        print $moreforfilter;
        print '</div>';

        print '<div class="div-table-responsive">';
        print '<table class="tagtable liste centpercent">';

// Filters lines
        print '<tr class="liste_titre_filter">';
// Action column
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons('left');
            print $searchpicto;
            print '</td>';
        }
// Movement number
        if (!empty($arrayfields['t.piece_num']['checked'])) {
            print '<td class="liste_titre"><input type="text" name="search_mvt_num" class="width50" value="' . dol_escape_htmltag($search_mvt_num) . '"></td>';
        }
// Code journal
        if (!empty($arrayfields['t.code_journal']['checked'])) {
            print '<td class="liste_titre center">';
            print $formaccounting->multi_select_journal($search_ledger_code, 'search_ledger_code', 0, 1, 1, 1, 'maxwidth75');
            print '</td>';
        }
// Date document
        if (!empty($arrayfields['t.doc_date']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_start, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_end, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Ref document
        if (!empty($arrayfields['t.doc_ref']['checked'])) {
            print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_doc_ref" value="' . dol_escape_htmltag($search_doc_ref) . '"/></td>';
        }
// Label operation
        if (!empty($arrayfields['t.label_operation']['checked'])) {
            print '<td class="liste_titre"><input type="text" size="7" class="flat" name="search_label_operation" value="' . dol_escape_htmltag($search_label_operation) . '"/></td>';
        }
// Lettering code
        if (!empty($arrayfields['t.lettering_code']['checked'])) {
            print '<td class="liste_titre center">';
            print '<input type="text" size="3" class="flat" name="search_lettering_code" value="' . $search_lettering_code . '"/>';
            print '<br><span class="nowrap"><input type="checkbox" name="search_not_reconciled" value="notreconciled"' . ($search_not_reconciled == 'notreconciled' ? ' checked' : '') . '>' . $langs->trans("NotReconciled") . '</span>';
            print '</td>';
        }
// Debit
        if (!empty($arrayfields['t.debit']['checked'])) {
            print '<td class="liste_titre right"><input type="text" class="flat" name="search_debit" size="4" value="' . dol_escape_htmltag($search_debit) . '"></td>';
        }
// Credit
        if (!empty($arrayfields['t.credit']['checked'])) {
            print '<td class="liste_titre right"><input type="text" class="flat" name="search_credit" size="4" value="' . dol_escape_htmltag($search_credit) . '"></td>';
        }
// Balance
        if (!empty($arrayfields['t.balance']['checked'])) {
            print '<td></td>';
        }
// Date export
        if (!empty($arrayfields['t.date_export']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_export_start, 'search_date_export_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_export_end, 'search_date_export_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
// Date validation
        if (!empty($arrayfields['t.date_validated']['checked'])) {
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_validation_start, 'search_date_validation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("From"));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_validation_end, 'search_date_validation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans("to"));
            print '</div>';
            print '</td>';
        }
        if (!empty($arrayfields['t.import_key']['checked'])) {
            print '<td class="liste_titre center">';
            print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="' . dol_escape_htmltag($search_import_key) . '">';
            print '</td>';
        }

// Fields from hook
        $parameters = array('arrayfields' => $arrayfields);
        $reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

// Action column
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';
        }
        print "</tr>\n";

        print '<tr class="liste_titre">';
        if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
        }
        if (!empty($arrayfields['t.piece_num']['checked'])) {
            print_liste_field_titre($arrayfields['t.piece_num']['label'], $_SERVER['PHP_SELF'], "t.piece_num", "", $param, '', $sortfield, $sortorder, 'tdoverflowmax80imp ');
        }
        if (!empty($arrayfields['t.code_journal']['checked'])) {
            print_liste_field_titre($arrayfields['t.code_journal']['label'], $_SERVER['PHP_SELF'], "t.code_journal", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.doc_date']['checked'])) {
            print_liste_field_titre($arrayfields['t.doc_date']['label'], $_SERVER['PHP_SELF'], "t.doc_date", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.doc_ref']['checked'])) {
            print_liste_field_titre($arrayfields['t.doc_ref']['label'], $_SERVER['PHP_SELF'], "t.doc_ref", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.label_operation']['checked'])) {
            print_liste_field_titre($arrayfields['t.label_operation']['label'], $_SERVER['PHP_SELF'], "t.label_operation", "", $param, "", $sortfield, $sortorder);
        }
        if (!empty($arrayfields['t.lettering_code']['checked'])) {
            print_liste_field_titre($arrayfields['t.lettering_code']['label'], $_SERVER['PHP_SELF'], "t.lettering_code", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.debit']['checked'])) {
            print_liste_field_titre($arrayfields['t.debit']['label'], $_SERVER['PHP_SELF'], "t.debit", "", $param, '', $sortfield, $sortorder, 'right ');
        }
        if (!empty($arrayfields['t.credit']['checked'])) {
            print_liste_field_titre($arrayfields['t.credit']['label'], $_SERVER['PHP_SELF'], "t.credit", "", $param, '', $sortfield, $sortorder, 'right ');
        }
        if (!empty($arrayfields['t.balance']['checked'])) {
            print_liste_field_titre($arrayfields['t.balance']['label'], "", "", "", $param, '', $sortfield, $sortorder, 'right ');
        }
        if (!empty($arrayfields['t.date_export']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_export']['label'], $_SERVER['PHP_SELF'], "t.date_export", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.date_validated']['checked'])) {
            print_liste_field_titre($arrayfields['t.date_validated']['label'], $_SERVER['PHP_SELF'], "t.date_validated", "", $param, '', $sortfield, $sortorder, 'center ');
        }
        if (!empty($arrayfields['t.import_key']['checked'])) {
            print_liste_field_titre($arrayfields['t.import_key']['label'], $_SERVER['PHP_SELF'], "t.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
        }
// Hook fields
        $parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
        $reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
            print_liste_field_titre($selectedfields, $_SERVER['PHP_SELF'], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
        }
        print "</tr>\n";

        $displayed_account_number = null; // Start with undefined to be able to distinguish with empty

// Loop on record
// --------------------------------------------------------------------
        $i = 0;

        $totalarray = array();
        $totalarray['val'] = array();
        $totalarray['nbfield'] = 0;
        $total_debit = 0;
        $total_credit = 0;
        $sous_total_debit = 0;
        $sous_total_credit = 0;
        $totalarray['val']['totaldebit'] = 0;
        $totalarray['val']['totalcredit'] = 0;

        while ($i < min($num, $limit)) {
            $line = $object->lines[$i];

            $total_debit += $line->debit;
            $total_credit += $line->credit;

            if ($type == 'sub') {
                $accountg = length_accounta($line->subledger_account);
            } else {
                $accountg = length_accountg($line->numero_compte);
            }
            //if (empty($accountg)) $accountg = '-';

            $colspan = 0;           // colspan before field 'label of operation'
            $colspanend = 0;        // colspan after debit/credit
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                $colspan++;
            }
            if (!empty($arrayfields['t.piece_num']['checked'])) {
                $colspan++;
            }
            if (!empty($arrayfields['t.code_journal']['checked'])) {
                $colspan++;
            }
            if (!empty($arrayfields['t.doc_date']['checked'])) {
                $colspan++;
            }
            if (!empty($arrayfields['t.doc_ref']['checked'])) {
                $colspan++;
            }
            if (!empty($arrayfields['t.label_operation']['checked'])) {
                $colspan++;
            }
            if (!empty($arrayfields['t.lettering_code']['checked'])) {
                $colspan++;
            }

            if (!empty($arrayfields['t.balance']['checked'])) {
                $colspanend++;
            }
            if (!empty($arrayfields['t.date_export']['checked'])) {
                $colspanend++;
            }
            if (!empty($arrayfields['t.date_validated']['checked'])) {
                $colspanend++;
            }
            if (!empty($arrayfields['t.lettering_code']['checked'])) {
                $colspanend++;
            }
            if (!empty($arrayfields['t.import_key']['checked'])) {
                $colspanend++;
            }
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                $colspan++;
                $colspanend--;
            }

            // Is it a break ?
            if ($accountg != $displayed_account_number || !isset($displayed_account_number)) {
                // Show a subtotal by accounting account
                if (isset($displayed_account_number)) {
                    print '<tr class="liste_total">';
                    if ($type == 'sub') {
                        print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . length_accounta($displayed_account_number) . ':</td>';
                    } else {
                        print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . length_accountg($displayed_account_number) . ':</td>';
                    }
                    print '<td class="nowrap right">' . price(price2num($sous_total_debit, 'MT')) . '</td>';
                    print '<td class="nowrap right">' . price(price2num($sous_total_credit, 'MT')) . '</td>';
                    if ($colspanend > 0) {
                        print '<td colspan="' . $colspanend . '"></td>';
                    }
                    print '</tr>';
                    // Show balance of last shown account
                    $balance = $sous_total_debit - $sous_total_credit;
                    print '<tr class="liste_total">';
                    print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("Balance") . ':</td>';
                    if ($balance > 0) {
                        print '<td class="nowraponall right">';
                        print price(price2num($sous_total_debit - $sous_total_credit, 'MT'));
                        print '</td>';
                        print '<td></td>';
                    } else {
                        print '<td></td>';
                        print '<td class="nowraponall right">';
                        print price(price2num($sous_total_credit - $sous_total_debit, 'MT'));
                        print '</td>';
                    }
                    if ($colspanend > 0) {
                        print '<td colspan="' . $colspanend . '"></td>';
                    }
                    print '</tr>';
                }

                // Show the break account
                print '<tr class="trforbreak">';
                print '<td colspan="' . ($totalarray['nbfield'] ? $totalarray['nbfield'] : count($arrayfields) + 1) . '" class="tdforbreak">';
                if ($type == 'sub') {
                    if ($line->subledger_account != "" && $line->subledger_account != '-1') {
                        print empty($line->subledger_label) ? '<span class="error">' . $langs->trans("Unknown") . '</span>' : $line->subledger_label;
                        print ' : ';
                        print length_accounta($line->subledger_account);
                    } else {
                        // Should not happen: subledger account must be null or a non empty value
                        print '<span class="error">' . $langs->trans("Unknown");
                        if ($line->subledger_label) {
                            print ' (' . $line->subledger_label . ')';
                            $htmltext = 'EmptyStringForSubledgerAccountButSubledgerLabelDefined';
                        } else {
                            $htmltext = 'EmptyStringForSubledgerAccountAndSubledgerLabel';
                        }
                        print $form->textwithpicto('', $htmltext);
                        print '</span>';
                    }
                } else {
                    if ($line->numero_compte != "" && $line->numero_compte != '-1') {
                        print length_accountg($line->numero_compte) . ' : ' . $object->get_compte_desc($line->numero_compte);
                    } else {
                        print '<span class="error">' . $langs->trans("Unknown") . '</span>';
                    }
                }
                print '</td>';
                print '</tr>';

                $displayed_account_number = $accountg;
                //if (empty($displayed_account_number)) $displayed_account_number='-';
                $sous_total_debit = 0;
                $sous_total_credit = 0;

                $colspan = 0;
            }

            print '<tr class="oddeven">';
            // Action column
            if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="nowraponall center">';
                if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                    $selected = 0;
                    if (in_array($line->id, $arrayofselected)) {
                        $selected = 1;
                    }
                    print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
                }
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }
            // Piece number
            if (!empty($arrayfields['t.piece_num']['checked'])) {
                print '<td>';
                $object->id = $line->id;
                $object->piece_num = $line->piece_num;
                print $object->getNomUrl(1, '', 0, '', 1);
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Journal code
            if (!empty($arrayfields['t.code_journal']['checked'])) {
                $accountingjournal = new AccountingJournal($db);
                $result = $accountingjournal->fetch('', $line->code_journal);
                $journaltoshow = (($result > 0) ? $accountingjournal->getNomUrl(0, 0, 0, '', 0) : $line->code_journal);
                print '<td class="center tdoverflowmax80">' . $journaltoshow . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Document date
            if (!empty($arrayfields['t.doc_date']['checked'])) {
                print '<td class="center">' . dol_print_date($line->doc_date, 'day') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Document ref
            if (!empty($arrayfields['t.doc_ref']['checked'])) {
                if ($line->doc_type == 'customer_invoice') {
                    $langs->loadLangs(array('bills'));

                    require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                    $objectstatic = new Facture($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'facture';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
                } elseif ($line->doc_type == 'supplier_invoice') {
                    $langs->loadLangs(array('bills'));

                    require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
                    $objectstatic = new FactureFournisseur($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'invoice_supplier';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->fournisseur->facture->dir_output . '/' . get_exdir($line->fk_doc, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
                    $subdir = get_exdir($objectstatic->id, 2, 0, 0, $objectstatic, $modulepart) . dol_sanitizeFileName($line->doc_ref);
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $subdir, $filedir);
                } elseif ($line->doc_type == 'expense_report') {
                    $langs->loadLangs(array('trips'));

                    require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
                    $objectstatic = new ExpenseReport($db);
                    $objectstatic->fetch($line->fk_doc);
                    //$modulepart = 'expensereport';

                    $filename = dol_sanitizeFileName($line->doc_ref);
                    $filedir = $conf->expensereport->dir_output . '/' . dol_sanitizeFileName($line->doc_ref);
                    $urlsource = $_SERVER['PHP_SELF'] . '?id=' . $objectstatic->id;
                    $documentlink = $formfile->getDocumentsLink($objectstatic->element, $filename, $filedir);
                } elseif ($line->doc_type == 'bank') {
                    require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                    $objectstatic = new AccountLine($db);
                    $objectstatic->fetch($line->fk_doc);
                } else {
                    // Other type
                }

                print '<td class="tdoverflowmax250">';

                // Picto + Ref
                if ($line->doc_type == 'customer_invoice' || $line->doc_type == 'supplier_invoice' || $line->doc_type == 'expense_report') {
                    print $objectstatic->getNomUrl(1, '', 0, 0, '', 0, -1, 1);
                    print $documentlink;
                } elseif ($line->doc_type == 'bank') {
                    print $objectstatic->getNomUrl(1);
                    $bank_ref = strstr($line->doc_ref, '-');
                    print " " . $bank_ref;
                } else {
                    print $line->doc_ref;
                }

                print "</td>\n";
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Label operation
            if (!empty($arrayfields['t.label_operation']['checked'])) {
                // Affiche un lien vers la facture client/fournisseur
                $doc_ref = preg_replace('/\(.*\)/', '', $line->doc_ref);
                if (strlen(length_accounta($line->subledger_account)) == 0) {
                    print '<td class="small tdoverflowmax350 classfortooltip" title="' . dol_escape_htmltag($line->label_operation) . '">' . dol_escape_htmltag($line->label_operation) . '</td>';
                } else {
                    print '<td class="small tdoverflowmax350 classfortooltip" title="' . dol_escape_htmltag($line->label_operation . ($line->label_operation ? '<br>' : '') . '<span style="font-size:0.8em">(' . length_accounta($line->subledger_account) . ')') . '">' . dol_escape_htmltag($line->label_operation) . ($line->label_operation ? '<br>' : '') . '<span style="font-size:0.8em">(' . dol_escape_htmltag(length_accounta($line->subledger_account)) . ')</span></td>';
                }
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Lettering code
            if (!empty($arrayfields['t.lettering_code']['checked'])) {
                print '<td class="center">' . dol_escape_htmltag($line->lettering_code) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Amount debit
            if (!empty($arrayfields['t.debit']['checked'])) {
                print '<td class="right nowraponall amount">' . ($line->debit != 0 ? price($line->debit) : '') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 'totaldebit';
                }
                $totalarray['val']['totaldebit'] += $line->debit;
            }

            // Amount credit
            if (!empty($arrayfields['t.credit']['checked'])) {
                print '<td class="right nowraponall amount">' . ($line->credit != 0 ? price($line->credit) : '') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 'totalcredit';
                }
                $totalarray['val']['totalcredit'] += $line->credit;
            }

            // Amount balance
            if (!empty($arrayfields['t.balance']['checked'])) {
                print '<td class="right nowraponall amount">' . price(price2num($sous_total_debit + $line->debit - $sous_total_credit - $line->credit, 'MT')) . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
                if (!$i) {
                    $totalarray['pos'][$totalarray['nbfield']] = 'totalbalance';
                };
                $totalarray['val']['totalbalance'] += $line->debit - $line->credit;
            }

            // Exported operation date
            if (!empty($arrayfields['t.date_export']['checked'])) {
                print '<td class="center">' . dol_print_date($line->date_export, 'dayhour') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Validated operation date
            if (!empty($arrayfields['t.date_validated']['checked'])) {
                print '<td class="center">' . dol_print_date($line->date_validation, 'dayhour') . '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            if (!empty($arrayfields['t.import_key']['checked'])) {
                print '<td class="tdoverflowmax100">' . dol_escape_htmltag($line->import_key) . "</td>\n";
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Fields from hook
            $parameters = array('arrayfields' => $arrayfields, 'obj' => $line);
            $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

            // Action column
            if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
                print '<td class="nowraponall center">';
                if (($massactionbutton || $massaction) && $contextpage != 'poslist') {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
                    $selected = 0;
                    if (in_array($line->id, $arrayofselected)) {
                        $selected = 1;
                    }
                    print '<input id="cb' . $line->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $line->id . '"' . ($selected ? ' checked="checked"' : '') . ' />';
                }
                print '</td>';
                if (!$i) {
                    $totalarray['nbfield']++;
                }
            }

            // Comptabilise le sous-total
            $sous_total_debit += $line->debit;
            $sous_total_credit += $line->credit;

            print "</tr>\n";

            $i++;
        }

        if ($num > 0 && $colspan > 0) {
            print '<tr class="liste_total">';
            print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("TotalForAccount") . ' ' . $accountg . ':</td>';
            print '<td class="nowrap right">' . price(price2num($sous_total_debit, 'MT')) . '</td>';
            print '<td class="nowrap right">' . price(price2num($sous_total_credit, 'MT')) . '</td>';
            if ($colspanend > 0) {
                print '<td colspan="' . $colspanend . '"></td>';
            }
            print '</tr>';
            // Show balance of last shown account
            $balance = $sous_total_debit - $sous_total_credit;
            print '<tr class="liste_total">';
            print '<td class="right" colspan="' . $colspan . '">' . $langs->trans("Balance") . ':</td>';
            if ($balance > 0) {
                print '<td class="nowraponall right">';
                print price(price2num($sous_total_debit - $sous_total_credit, 'MT'));
                print '</td>';
                print '<td></td>';
            } else {
                print '<td></td>';
                print '<td class="nowraponall right">';
                print price(price2num($sous_total_credit - $sous_total_debit, 'MT'));
                print '</td>';
            }
            if ($colspanend > 0) {
                print '<td colspan="' . $colspanend . '"></td>';
            }
            print '</tr>';
        }


// Clean total values to round them
        if (!empty($totalarray['val']['totaldebit'])) {
            $totalarray['val']['totaldebit'] = price2num($totalarray['val']['totaldebit'], 'MT');
        }
        if (!empty($totalarray['val']['totalcredit'])) {
            $totalarray['val']['totalcredit'] = price2num($totalarray['val']['totalcredit'], 'MT');
        }
        if (!empty($totalarray['val']['totalbalance'])) {
            $totalarray['val']['totalbalance'] = price2num($totalarray['val']['totaldebit'] - $totalarray['val']['totalcredit'], 'MT');
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

        $parameters = array('arrayfields' => $arrayfields);
        $reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;

        print "</table>";
        print '</div>';

// TODO Replace this with mass delete action
//if ($user->hasRight('accounting', 'mouvements, 'supprimer_tous')) {
//  print '<div class="tabsAction tabsActionNoBottom">'."\n";
//  print '<a class="butActionDelete" name="button_delmvt" href="'.$_SERVER['PHP_SELF'].'?action=delbookkeepingyear&token='.newToken().($param ? '&'.$param : '').'">'.$langs->trans("DeleteMvt").'</a>';
//  print '</div>';
//}

        print '</form>';

// End of page
        llxFooter();
        $db->close();
    }

}