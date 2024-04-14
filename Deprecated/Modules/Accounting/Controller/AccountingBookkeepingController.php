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
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';

use DoliCore\Base\DolibarrController;
use DoliModules\Accounting\Model\BookKeeping;
use DoliCore\Form\Form;
use FormAccounting;
use FormFile;
use DoliCore\Form\FormOther;

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
        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Accounting/Views/bookkeeping_balance.php');

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
        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Accounting/Views/bookkeeping_card.php');

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
        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Accounting/Views/bookkeeping_export.php');

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
        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Accounting/Views/bookkeeping_list.php');

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
        require_once realpath(BASE_PATH . '/../Deprecated/Modules/Accounting/Views/bookkeeping_listbyaccount.php');

        $db->close();
    }
}
