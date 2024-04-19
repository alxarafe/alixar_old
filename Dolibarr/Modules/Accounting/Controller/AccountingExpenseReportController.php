<?php

/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005       Simon TOSSER            <simon@kornog-computing.com>
 * Copyright (C) 2013-2024  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2013-2016  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014	Florian Henry		    <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2024	Alexandre Spangaro	    <aspangaro@easya.solutions>
 * Copyright (C) 2014-2015	Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2014		Juanjo Menent		    <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
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

use DoliCore\Base\DolibarrController;
use DoliCore\Form\FormAccounting;
use DoliModules\Accounting\Model\AccountingAccount;

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

class AccountingExpenseReportController extends DolibarrController
{
    /**
     * \file        htdocs/accountancy/supplier/card.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Card expense report ventilation
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
        $langs->loadLangs(array("bills", "accountancy", "trips"));

        $action = GETPOST('action', 'aZ09');
        $cancel = GETPOST('cancel', 'alpha');
        $backtopage = GETPOST('backtopage', 'alpha');

        $codeventil = GETPOSTINT('codeventil');
        $id = GETPOSTINT('id');

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

        if ($action == 'ventil' && $user->hasRight('accounting', 'bind', 'write')) {
            if (!$cancel) {
                if ($codeventil < 0) {
                    $codeventil = 0;
                }

                $sql = " UPDATE " . MAIN_DB_PREFIX . "expensereport_det";
                $sql .= " SET fk_code_ventilation = " . ((int) $codeventil);
                $sql .= " WHERE rowid = " . ((int) $id);

                $resql = $db->query($sql);
                if (!$resql) {
                    setEventMessages($db->lasterror(), null, 'errors');
                } else {
                    setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
                    if ($backtopage) {
                        header("Location: " . $backtopage);
                        exit();
                    }
                }
            } else {
                header("Location: ./lines.php");
                exit();
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/expense_report_card.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/expensereport/index.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Home expense report ventilation
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
        global $mysoc;

// Load translation files required by the page
        $langs->loadLangs(array("compta", "bills", "other", "accountancy"));

        $validatemonth = GETPOSTINT('validatemonth');
        $validateyear = GETPOSTINT('validateyear');

        $month_start = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
        if (GETPOSTINT("year")) {
            $year_start = GETPOSTINT("year");
        } else {
            $year_start = dol_print_date(dol_now(), '%Y');
            if (dol_print_date(dol_now(), '%m') < $month_start) {
                $year_start--; // If current month is lower that starting fiscal month, we start last year
            }
        }
        $year_end = $year_start + 1;
        $month_end = $month_start - 1;
        if ($month_end < 1) {
            $month_end = 12;
            $year_end--;
        }
        $search_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
        $search_date_end = dol_get_last_day($year_end, $month_end);
        $year_current = $year_start;

// Validate History
        $action = GETPOST('action', 'aZ09');

        $chartaccountcode = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

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

        if (($action == 'clean' || $action == 'validatehistory') && $user->hasRight('accounting', 'bind', 'write')) {
            // Clean database
            $db->begin();
            $sql1 = "UPDATE " . MAIN_DB_PREFIX . "expensereport_det as erd";
            $sql1 .= " SET fk_code_ventilation = 0";
            $sql1 .= ' WHERE erd.fk_code_ventilation NOT IN';
            $sql1 .= '	(SELECT accnt.rowid ';
            $sql1 .= '	FROM ' . MAIN_DB_PREFIX . 'accounting_account as accnt';
            $sql1 .= '	INNER JOIN ' . MAIN_DB_PREFIX . 'accounting_system as syst';
            $sql1 .= '	ON accnt.fk_pcg_version = syst.pcg_version AND syst.rowid=' . ((int) getDolGlobalInt('CHARTOFACCOUNTS')) . ' AND accnt.entity = ' . ((int) $conf->entity) . ')';
            $sql1 .= ' AND erd.fk_expensereport IN (SELECT rowid FROM ' . MAIN_DB_PREFIX . 'expensereport WHERE entity = ' . ((int) $conf->entity) . ')';
            $sql1 .= ' AND fk_code_ventilation <> 0';
            dol_syslog("htdocs/accountancy/customer/index.php fixaccountancycode", LOG_DEBUG);
            $resql1 = $db->query($sql1);
            if (!$resql1) {
                $error++;
                $db->rollback();
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                $db->commit();
            }
            // End clean database
        }

        if ($action == 'validatehistory') {
            $error = 0;
            $nbbinddone = 0;
            $nbbindfailed = 0;
            $notpossible = 0;

            $db->begin();

            // Now make the binding
            $sql1 = "SELECT erd.rowid, accnt.rowid as suggestedid";
            $sql1 .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
            $sql1 .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as t ON erd.fk_c_type_fees = t.id";
            $sql1 .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as accnt ON t.accountancy_code = accnt.account_number AND accnt.active = 1 AND accnt.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND accnt.entity =" . ((int) $conf->entity) . ",";
            $sql1 .= " " . MAIN_DB_PREFIX . "expensereport as er";
            $sql1 .= " WHERE erd.fk_expensereport = er.rowid AND er.entity = " . ((int) $conf->entity);
            $sql1 .= " AND er.fk_statut IN (" . ExpenseReport::STATUS_APPROVED . ", " . ExpenseReport::STATUS_CLOSED . ") AND erd.fk_code_ventilation <= 0";
            if ($validatemonth && $validateyear) {
                $sql1 .= dolSqlDateFilter('erd.date', 0, $validatemonth, $validateyear);
            }

            dol_syslog('htdocs/accountancy/expensereport/index.php');

            $result = $db->query($sql1);
            if (!$result) {
                $error++;
                setEventMessages($db->lasterror(), null, 'errors');
            } else {
                $num_lines = $db->num_rows($result);

                $i = 0;
                while ($i < min($num_lines, 10000)) {   // No more than 10000 at once
                    $objp = $db->fetch_object($result);

                    $lineid = $objp->rowid;
                    $suggestedid = $objp->suggestedid;

                    if ($suggestedid > 0) {
                        $sqlupdate = "UPDATE " . MAIN_DB_PREFIX . "expensereport_det";
                        $sqlupdate .= " SET fk_code_ventilation = " . ((int) $suggestedid);
                        $sqlupdate .= " WHERE fk_code_ventilation <= 0 AND rowid = " . ((int) $lineid);

                        $resqlupdate = $db->query($sqlupdate);
                        if (!$resqlupdate) {
                            $error++;
                            setEventMessages($db->lasterror(), null, 'errors');
                            $nbbindfailed++;
                            break;
                        } else {
                            $nbbinddone++;
                        }
                    } else {
                        $notpossible++;
                        $nbbindfailed++;
                    }

                    $i++;
                }
                if ($num_lines > 10000) {
                    $notpossible += ($num_lines - 10000);
                }
            }

            if ($error) {
                $db->rollback();
            } else {
                $db->commit();
                setEventMessages($langs->trans('AutomaticBindingDone', $nbbinddone, $notpossible), null, ($notpossible ? 'warnings' : 'mesgs'));
                if ($nbbindfailed) {
                    setEventMessages($langs->trans('DoManualBindingForFailedRecord', $nbbindfailed), null, 'warnings');
                }
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/expense_report_index.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/expensereport/lines.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Page of detail of the lines of ventilation of expense reports
     */
    public function lines()
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
        $langs->loadLangs(array("compta", "bills", "other", "accountancy", "trips", "productbatch", "hrm"));

        $optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

        $account_parent = GETPOSTINT('account_parent');
        $changeaccount = GETPOST('changeaccount');
// Search Getpost
        $search_login = GETPOST('search_login', 'alpha');
        $search_expensereport = GETPOST('search_expensereport', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_desc = GETPOST('search_desc', 'alpha');
        $search_amount = GETPOST('search_amount', 'alpha');
        $search_account = GETPOST('search_account', 'alpha');
        $search_vat = GETPOST('search_vat', 'alpha');
        $search_date_startday = GETPOSTINT('search_date_startday');
        $search_date_startmonth = GETPOSTINT('search_date_startmonth');
        $search_date_startyear = GETPOSTINT('search_date_startyear');
        $search_date_endday = GETPOSTINT('search_date_endday');
        $search_date_endmonth = GETPOSTINT('search_date_endmonth');
        $search_date_endyear = GETPOSTINT('search_date_endyear');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);   // Use tzserver
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = "erd.date, erd.rowid";
        }
        if (!$sortorder) {
            if (getDolGlobalInt('ACCOUNTING_LIST_SORT_VENTILATION_DONE') > 0) {
                $sortorder = "DESC";
            } else {
                $sortorder = "ASC";
            }
        }

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


        $formaccounting = new FormAccounting($db);


        /*
         * Actions
         */

// Purge search criteria
        if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
            $search_login = '';
            $search_expensereport = '';
            $search_label = '';
            $search_desc = '';
            $search_amount = '';
            $search_account = '';
            $search_vat = '';
            $search_date_startday = '';
            $search_date_startmonth = '';
            $search_date_startyear = '';
            $search_date_endday = '';
            $search_date_endmonth = '';
            $search_date_endyear = '';
            $search_date_start = '';
            $search_date_end = '';
        }

        if (is_array($changeaccount) && count($changeaccount) > 0 && $user->hasRight('accounting', 'bind', 'write')) {
            $error = 0;

            if (!(GETPOSTINT('account_parent') >= 0)) {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Account")), null, 'errors');
            }

            if (!$error) {
                $db->begin();

                $sql1 = "UPDATE " . MAIN_DB_PREFIX . "expensereport_det as erd";
                $sql1 .= " SET erd.fk_code_ventilation=" . (GETPOSTINT('account_parent') > 0 ? GETPOSTINT('account_parent') : '0');
                $sql1 .= ' WHERE erd.rowid IN (' . $db->sanitize(implode(',', $changeaccount)) . ')';

                dol_syslog('accountancy/expensereport/lines.php::changeaccount sql= ' . $sql1);
                $resql1 = $db->query($sql1);
                if (!$resql1) {
                    $error++;
                    setEventMessages($db->lasterror(), null, 'errors');
                }
                if (!$error) {
                    $db->commit();
                    setEventMessages($langs->trans("Save"), null, 'mesgs');
                } else {
                    $db->rollback();
                    setEventMessages($db->lasterror(), null, 'errors');
                }

                $account_parent = ''; // Protection to avoid to mass apply it a second time
            }
        }

        if (GETPOST('sortfield') == 'erd.date, erd.rowid') {
            $value = (GETPOST('sortorder') == 'asc,asc' ? 0 : 1);
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
            $res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_DONE", $value, 'yesno', 0, '', $conf->entity);
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/expense_report_lines.php');

        $db->close();
    }

    /**
     * \file        htdocs/accountancy/expensereport/list.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Ventilation page from expense reports
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
        $langs->loadLangs(array("bills", "companies", "compta", "accountancy", "other", "trips", "productbatch", "hrm"));

        $action = GETPOST('action', 'aZ09');
        $massaction = GETPOST('massaction', 'alpha');
        $confirm = GETPOST('confirm', 'alpha');
        $toselect = GETPOST('toselect', 'array');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'accountancyexpensereportlist'; // To manage different context of search
        $optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')


// Select Box
        $mesCasesCochees = GETPOST('toselect', 'array');

// Search Getpost
        $search_login = GETPOST('search_login', 'alpha');
        $search_lineid = GETPOST('search_lineid', 'alpha');
        $search_expensereport = GETPOST('search_expensereport', 'alpha');
        $search_label = GETPOST('search_label', 'alpha');
        $search_desc = GETPOST('search_desc', 'alpha');
        $search_amount = GETPOST('search_amount', 'alpha');
        $search_account = GETPOST('search_account', 'alpha');
        $search_vat = GETPOST('search_vat', 'alpha');
        $search_date_startday = GETPOSTINT('search_date_startday');
        $search_date_startmonth = GETPOSTINT('search_date_startmonth');
        $search_date_startyear = GETPOSTINT('search_date_startyear');
        $search_date_endday = GETPOSTINT('search_date_endday');
        $search_date_endmonth = GETPOSTINT('search_date_endmonth');
        $search_date_endyear = GETPOSTINT('search_date_endyear');
        $search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear);   // Use tzserver
        $search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);

// Define begin binding date
        if (empty($search_date_start) && getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $search_date_start = $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING'));
        }

// Load variable for pagination
        $limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : getDolGlobalString('ACCOUNTING_LIMIT_LIST_VENTILATION', $conf->liste_limit);
        $sortfield = GETPOST('sortfield', 'aZ09comma');
        $sortorder = GETPOST('sortorder', 'aZ09comma');
        $page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
        if (empty($page) || $page < 0) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;
        if (!$sortfield) {
            $sortfield = "erd.date, erd.rowid";
        }
        if (!$sortorder) {
            if (getDolGlobalInt('ACCOUNTING_LIST_SORT_VENTILATION_TODO') > 0) {
                $sortorder = "DESC";
            } else {
                $sortorder = "ASC";
            }
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(array('accountancyexpensereportlist'));

        $formaccounting = new FormAccounting($db);
        $accounting = new AccountingAccount($db);

        $chartaccountcode = dol_getIdFromCode($db, getDolGlobalInt('CHARTOFACCOUNTS'), 'accounting_system', 'rowid', 'pcg_version');

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
            // Purge search criteria
            if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
                $search_login = '';
                $search_expensereport = '';
                $search_label = '';
                $search_desc = '';
                $search_amount = '';
                $search_account = '';
                $search_vat = '';
                $search_date_startday = '';
                $search_date_startmonth = '';
                $search_date_startyear = '';
                $search_date_endday = '';
                $search_date_endmonth = '';
                $search_date_endyear = '';
                $search_date_start = '';
                $search_date_end = '';
                $search_country = '';
                $search_tvaintra = '';
            }

            // Mass actions
            $objectclass = 'ExpenseReport';
            $objectlabel = 'ExpenseReport';
            $permissiontoread = $user->hasRight('accounting', 'read');
            $permissiontodelete = $user->hasRight('accounting', 'delete');
            $uploaddir = $conf->expensereport->dir_output;
            include DOL_DOCUMENT_ROOT . '/core/actions_massactions.inc.php';
        }


        if ($massaction == 'ventil' && $user->hasRight('accounting', 'bind', 'write')) {
            $msg = '';

            if (!empty($mesCasesCochees)) {
                $msg = '<div>' . $langs->trans("SelectedLines") . ': ' . count($mesCasesCochees) . '</div>';
                $msg .= '<div class="detail">';
                $cpt = 0;
                $ok = 0;
                $ko = 0;

                foreach ($mesCasesCochees as $maLigneCochee) {
                    $maLigneCourante = explode("_", $maLigneCochee);
                    $monId = $maLigneCourante[0];
                    $monCompte = GETPOST('codeventil' . $monId);

                    if ($monCompte <= 0) {
                        $msg .= '<div><span class="error">' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NoAccountSelected") . '</span></div>';
                        $ko++;
                    } else {
                        $sql = " UPDATE " . MAIN_DB_PREFIX . "expensereport_det";
                        $sql .= " SET fk_code_ventilation = " . ((int) $monCompte);
                        $sql .= " WHERE rowid = " . ((int) $monId);

                        $accountventilated = new AccountingAccount($db);
                        $accountventilated->fetch($monCompte, '', 1);

                        dol_syslog('accountancy/expensereport/list.php:: sql=' . $sql, LOG_DEBUG);
                        if ($db->query($sql)) {
                            $msg .= '<div><span class="green">' . $langs->trans("LineOfExpenseReport") . ' ' . $monId . ' - ' . $langs->trans("VentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '</span></div>';
                            $ok++;
                        } else {
                            $msg .= '<div><span class="error">' . $langs->trans("ErrorDB") . ' : ' . $langs->trans("Lineofinvoice") . ' ' . $monId . ' - ' . $langs->trans("NotVentilatedinAccount") . ' : ' . length_accountg($accountventilated->account_number) . '<br> <pre>' . $sql . '</pre></span></div>';
                            $ko++;
                        }
                    }

                    $cpt++;
                }
                $msg .= '</div>';
                $msg .= '<div>' . $langs->trans("EndProcessing") . '</div>';
            }
        }

        if (GETPOST('sortfield') == 'erd.date, erd.rowid') {
            $value = (GETPOST('sortorder') == 'asc,asc' ? 0 : 1);
            require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
            $res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_TODO", $value, 'yesno', 0, '', $conf->entity);
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/expense_report_list.php');

        $db->close();
    }
}
