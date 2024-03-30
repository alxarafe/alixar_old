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

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

use AccountingAccount;
use DoliCore\Base\DolibarrController;
use ExpenseReport;
use Form;
use FormAccounting;
use FormOther;
use User;

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
        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader("", $langs->trans('FicheVentilation'), $help_url);

        if ($cancel == $langs->trans("Cancel")) {
            $action = '';
        }

// Create
        $form = new Form($db);
        $expensereport_static = new ExpenseReport($db);
        $formaccounting = new FormAccounting($db);

        if (!empty($id)) {
            $sql = "SELECT er.ref, er.rowid as facid, erd.fk_c_type_fees, erd.comments, erd.rowid, erd.fk_code_ventilation,";
            $sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label,";
            $sql .= " aa.account_number, aa.label";
            $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON erd.fk_code_ventilation = aa.rowid";
            $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
            $sql .= " WHERE er.fk_statut > 0 AND erd.rowid = " . ((int) $id);
            $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy

            dol_syslog("/accounting/expensereport/card.php", LOG_DEBUG);
            $result = $db->query($sql);

            if ($result) {
                $num_lines = $db->num_rows($result);
                $i = 0;

                if ($num_lines) {
                    $objp = $db->fetch_object($result);

                    print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="post">' . "\n";
                    print '<input type="hidden" name="token" value="' . newToken() . '">';
                    print '<input type="hidden" name="action" value="ventil">';
                    print '<input type="hidden" name="backtopage" value="' . dol_escape_htmltag($backtopage) . '">';

                    print load_fiche_titre($langs->trans('ExpenseReportsVentilation'), '', 'title_accountancy');

                    print dol_get_fiche_head();

                    print '<table class="border centpercent">';

                    // Ref
                    print '<tr><td class="titlefield">' . $langs->trans("ExpenseReport") . '</td>';
                    $expensereport_static->ref = $objp->ref;
                    $expensereport_static->id = $objp->erid;
                    print '<td>' . $expensereport_static->getNomUrl(1) . '</td>';
                    print '</tr>';

                    print '<tr><td>' . $langs->trans("Line") . '</td>';
                    print '<td>' . stripslashes(nl2br($objp->rowid)) . '</td></tr>';

                    print '<tr><td>' . $langs->trans("Description") . '</td>';
                    print '<td>' . stripslashes(nl2br($objp->comments)) . '</td></tr>';

                    print '<tr><td>' . $langs->trans("TypeFees") . '</td>';
                    print '<td>' . ($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code))) . '</td>';

                    print '<tr><td>' . $langs->trans("Account") . '</td><td>';
                    print $formaccounting->select_account($objp->fk_code_ventilation, 'codeventil', 1);
                    print '</td></tr>';
                    print '</table>';

                    print dol_get_fiche_end();

                    print '<div class="center">';
                    print '<input class="button button-save" type="submit" value="' . $langs->trans("Save") . '">';
                    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    print '<input class="button button-cancel" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
                    print '</div>';

                    print '</form>';
                } else {
                    print "Error";
                }
            } else {
                print "Error";
            }
        } else {
            print "Error ID incorrect";
        }

// End of page
        llxFooter();
        $db->close();
    }

    /**
     * \file        htdocs/accountancy/expensereport/index.php
     * \ingroup     Accountancy (Double entries)
     * \brief       Home expense report ventilation
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
        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader('', $langs->trans("ExpenseReportsVentilation"), $help_url);

        $textprevyear = '<a href="' . $_SERVER['PHP_SELF'] . '?year=' . ($year_current - 1) . '">' . img_previous() . '</a>';
        $textnextyear = '&nbsp;<a href="' . $_SERVER['PHP_SELF'] . '?year=' . ($year_current + 1) . '">' . img_next() . '</a>';


        print load_fiche_titre($langs->trans("ExpenseReportsVentilation") . "&nbsp;" . $textprevyear . "&nbsp;" . $langs->trans("Year") . "&nbsp;" . $year_start . "&nbsp;" . $textnextyear, '', 'title_accountancy');

        print '<span class="opacitymedium">' . $langs->trans("DescVentilExpenseReport") . '</span><br>';
        print '<span class="opacitymedium hideonsmartphone">' . $langs->trans("DescVentilExpenseReportMore", $langs->transnoentitiesnoconv("ValidateHistory"), $langs->transnoentitiesnoconv("ToBind")) . '<br>';
        print '</span><br>';


        $y = $year_current;

        $buttonbind = '<a class="button small" href="' . $_SERVER['PHP_SELF'] . '?action=validatehistory&token=' . newToken() . '&year=' . $year_current . '">' . img_picto('', 'link', 'class="paddingright fa-color-unset"') . $langs->trans("ValidateHistory") . '</a>';


        print_barre_liste(img_picto('', 'unlink', 'class="paddingright fa-color-unset"') . $langs->trans("OverviewOfAmountOfLinesNotBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1, 0, $buttonbind);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesNotBound"), $buttonbind, '');

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td class="minwidth100">' . $langs->trans("Account") . '</td>';
        print '<td>' . $langs->trans("Label") . '</td>';
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            $cursormonth = $j;
            if ($cursormonth > 12) {
                $cursormonth -= 12;
            }
            $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
            $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

            print '<td width="60" class="right">';
            if (!empty($tmp['mday'])) {
                $param = 'search_date_startday=1&search_date_startmonth=' . $cursormonth . '&search_date_startyear=' . $cursoryear;
                $param .= '&search_date_endday=' . $tmp['mday'] . '&search_date_endmonth=' . $tmp['mon'] . '&search_date_endyear=' . $tmp['year'];
                $param .= '&search_month=' . $tmp['mon'] . '&search_year=' . $tmp['year'];
                print '<a href="' . DOL_URL_ROOT . '/accountancy/expensereport/list.php?' . $param . '">';
            }
            print $langs->trans('MonthShort' . str_pad((int) $j, 2, '0', STR_PAD_LEFT));
            if (!empty($tmp['mday'])) {
                print '</a>';
            }
            print '</td>';
        }
        print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

        $sql = "SELECT " . $db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number') . " AS codecomptable,";
        $sql .= " " . $db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label') . " AS intitule,";
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            $sql .= "  SUM(" . $db->ifsql("MONTH(er.date_debut) = " . ((int) $j), "erd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
        }
        $sql .= " SUM(erd.total_ht) as total";
        $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
        $sql .= " WHERE er.date_debut >= '" . $db->idate($search_date_start) . "'";
        $sql .= " AND er.date_debut <= '" . $db->idate($search_date_end) . "'";
// Define begin binding date
        if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND er.date_debut >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
        $sql .= " AND er.fk_statut IN (" . ExpenseReport::STATUS_APPROVED . ", " . ExpenseReport::STATUS_CLOSED . ")";
        $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy
        $sql .= " AND aa.account_number IS NULL";
        $sql .= " GROUP BY erd.fk_code_ventilation,aa.account_number,aa.label";
        $sql .= ' ORDER BY aa.account_number';

        dol_syslog('/accountancy/expensereport/index.php', LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);

            while ($row = $db->fetch_row($resql)) {
                print '<tr class="oddeven">';
                print '<td>';
                if ($row[0] == 'tobind') {
                    print '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>';
                } else {
                    print length_accountg($row[0]);
                }
                print '</td>';
                print '<td>';
                if ($row[0] == 'tobind') {
                    $startmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1);
                    if ($startmonth > 12) {
                        $startmonth -= 12;
                    }
                    $startyear = ($startmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
                    $endmonth = getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + 11;
                    if ($endmonth > 12) {
                        $endmonth -= 12;
                    }
                    $endyear = ($endmonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
                    print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT . '/accountancy/expensereport/list.php?search_date_startday=1&search_date_startmonth=' . ((int) $startmonth) . '&search_date_startyear=' . ((int) $startyear) . '&search_date_endday=&search_date_endmonth=' . ((int) $endmonth) . '&search_date_endyear=' . ((int) $endyear), $langs->transnoentitiesnoconv("ToBind"));
                } else {
                    print $row[1];
                }
                print '</td>';
                for ($i = 2; $i <= 13; $i++) {
                    $cursormonth = (getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) + $i - 2);
                    if ($cursormonth > 12) {
                        $cursormonth -= 12;
                    }
                    $cursoryear = ($cursormonth < getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1)) ? $y + 1 : $y;
                    $tmp = dol_getdate(dol_get_last_day($cursoryear, $cursormonth, 'gmt'), false, 'gmt');

                    print '<td class="right nowraponall amount">';
                    print price($row[$i]);
                    // Add link to make binding
                    if (!empty(price2num($row[$i]))) {
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?action=validatehistory&year=' . $y . '&validatemonth=' . ((int) $cursormonth) . '&validateyear=' . ((int) $cursoryear) . '&token=' . newToken() . '">';
                        print img_picto($langs->trans("ValidateHistory") . ' (' . $langs->trans('Month' . str_pad($cursormonth, 2, '0', STR_PAD_LEFT)) . ' ' . $cursoryear . ')', 'link', 'class="marginleft2"');
                        print '</a>';
                    }
                    print '</td>';
                }
                print '<td class="right nowraponall amount"><b>' . price($row[14]) . '</b></td>';
                print '</tr>';
            }
            $db->free($resql);

            if ($num == 0) {
                print '<tr class="oddeven"><td colspan="16">';
                print '<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>';
                print '</td></tr>';
            }
        } else {
            print $db->lasterror(); // Show last sql error
        }
        print "</table>\n";
        print '</div>';


        print '<br>';


        print_barre_liste(img_picto('', 'link', 'class="paddingright fa-color-unset"') . $langs->trans("OverviewOfAmountOfLinesBound"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
//print load_fiche_titre($langs->trans("OverviewOfAmountOfLinesBound"), '', '');


        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre"><td class="minwidth100">' . $langs->trans("Account") . '</td>';
        print '<td>' . $langs->trans("Label") . '</td>';
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            print '<td width="60" class="right">' . $langs->trans('MonthShort' . str_pad((int) $j, 2, '0', STR_PAD_LEFT)) . '</td>';
        }
        print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

        $sql = "SELECT " . $db->ifsql('aa.account_number IS NULL', "'tobind'", 'aa.account_number') . " AS codecomptable,";
        $sql .= "  " . $db->ifsql('aa.label IS NULL', "'tobind'", 'aa.label') . " AS intitule,";
        for ($i = 1; $i <= 12; $i++) {
            $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
            if ($j > 12) {
                $j -= 12;
            }
            $sql .= " SUM(" . $db->ifsql("MONTH(er.date_debut) = " . ((int) $j), "erd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
        }
        $sql .= " ROUND(SUM(erd.total_ht),2) as total";
        $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
        $sql .= " WHERE er.date_debut >= '" . $db->idate($search_date_start) . "'";
        $sql .= " AND er.date_debut <= '" . $db->idate($search_date_end) . "'";
// Define begin binding date
        if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
            $sql .= " AND er.date_debut >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
        }
        $sql .= " AND er.fk_statut IN (" . ExpenseReport::STATUS_APPROVED . ", " . ExpenseReport::STATUS_CLOSED . ")";
        $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy
        $sql .= " AND aa.account_number IS NOT NULL";
        $sql .= " GROUP BY erd.fk_code_ventilation,aa.account_number,aa.label";

        dol_syslog('htdocs/accountancy/expensereport/index.php');
        $resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);

            while ($row = $db->fetch_row($resql)) {
                print '<tr class="oddeven">';
                print '<td>';
                if ($row[0] == 'tobind') {
                    print '<span class="opacitymedium">' . $langs->trans("Unknown") . '</span>';
                } else {
                    print length_accountg($row[0]);
                }
                print '</td>';

                print '<td>';
                if ($row[0] == 'tobind') {
                    print $langs->trans("UseMenuToSetBindindManualy", DOL_URL_ROOT . '/accountancy/expensereport/list.php?search_year=' . ((int) $y), $langs->transnoentitiesnoconv("ToBind"));
                } else {
                    print $row[1];
                }
                print '</td>';
                for ($i = 2; $i <= 13; $i++) {
                    print '<td class="right nowraponall amount">';
                    print price($row[$i]);
                    print '</td>';
                }
                print '<td class="right nowraponall amount"><b>' . price($row[14]) . '</b></td>';
                print '</tr>';
            }
            $db->free($resql);

            if ($num == 0) {
                print '<tr class="oddeven"><td colspan="16">';
                print '<span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span>';
                print '</td></tr>';
            }
        } else {
            print $db->lasterror(); // Show last sql error
        }
        print "</table>\n";
        print '</div>';



        if (getDolGlobalString('SHOW_TOTAL_OF_PREVIOUS_LISTS_IN_LIN_PAGE')) { // This part of code looks strange. Why showing a report that should rely on result of this step ?
            print '<br>';
            print '<br>';

            print_barre_liste($langs->trans("OtherInfo"), '', '', '', '', '', '', -1, '', '', 0, '', '', 0, 1, 1);
            //print load_fiche_titre($langs->trans("OtherInfo"), '', '');

            print '<div class="div-table-responsive-no-min">';
            print '<table class="noborder centpercent">';
            print '<tr class="liste_titre"><td class="left">' . $langs->trans("Total") . '</td>';
            for ($i = 1; $i <= 12; $i++) {
                $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
                if ($j > 12) {
                    $j -= 12;
                }
                print '<td width="60" class="right">' . $langs->trans('MonthShort' . str_pad($j, 2, '0', STR_PAD_LEFT)) . '</td>';
            }
            print '<td width="60" class="right"><b>' . $langs->trans("Total") . '</b></td></tr>';

            $sql = "SELECT '" . $db->escape($langs->trans("TotalExpenseReport")) . "' AS label,";
            for ($i = 1; $i <= 12; $i++) {
                $j = $i + getDolGlobalInt('SOCIETE_FISCAL_MONTH_START', 1) - 1;
                if ($j > 12) {
                    $j -= 12;
                }
                $sql .= " SUM(" . $db->ifsql("MONTH(er.date_create) = " . ((int) $j), "erd.total_ht", "0") . ") AS month" . str_pad((int) $j, 2, "0", STR_PAD_LEFT) . ",";
            }
            $sql .= " SUM(erd.total_ht) as total";
            $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
            $sql .= " WHERE er.date_debut >= '" . $db->idate($search_date_start) . "'";
            $sql .= " AND er.date_debut <= '" . $db->idate($search_date_end) . "'";
            // Define begin binding date
            if (getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) {
                $sql .= " AND er.date_debut >= '" . $db->idate(getDolGlobalString('ACCOUNTING_DATE_START_BINDING')) . "'";
            }
            $sql .= " AND er.fk_statut IN (" . ExpenseReport::STATUS_APPROVED . ", " . ExpenseReport::STATUS_CLOSED . ")";
            $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy

            dol_syslog('htdocs/accountancy/expensereport/index.php');
            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);

                while ($row = $db->fetch_row($resql)) {
                    print '<tr><td>' . $row[0] . '</td>';
                    for ($i = 1; $i <= 12; $i++) {
                        print '<td class="right nowraponall amount">' . price($row[$i]) . '</td>';
                    }
                    print '<td class="right nowraponall amount"><b>' . price($row[13]) . '</b></td>';
                    print '</tr>';
                }

                $db->free($resql);
            } else {
                print $db->lasterror(); // Show last sql error
            }
            print "</table>\n";
            print '</div>';
        }

// End of page
        llxFooter();
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

        $form = new Form($db);
        $formother = new FormOther($db);

        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader('', $langs->trans("ExpenseReportsVentilation") . ' - ' . $langs->trans("Dispatched"), $help_url);

        print '<script type="text/javascript">
			$(function () {
				$(\'#select-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = true;
				    });
			    });
			    $(\'#unselect-all\').click(function(event) {
				    // Iterate each checkbox
				    $(\':checkbox\').each(function() {
				    	this.checked = false;
				    });
			    });
			});
			 </script>';

        /*
         * Expense reports lines
         */
        $sql = "SELECT er.ref, er.rowid as erid,";
        $sql .= " erd.rowid, erd.fk_c_type_fees, erd.comments, erd.total_ht, erd.fk_code_ventilation, erd.tva_tx, erd.vat_src_code, erd.date,";
        $sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label,";
        $sql .= " u.rowid as userid, u.login, u.lastname, u.firstname, u.email, u.gender, u.employee, u.photo, u.statut,";
        $sql .= " aa.label, aa.labelshort, aa.account_number";
        $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport as er";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "expensereport_det as erd ON er.rowid = erd.fk_expensereport";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.rowid = erd.fk_code_ventilation";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = er.fk_user_author";
        $sql .= " WHERE erd.fk_code_ventilation > 0";
        $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy
        $sql .= " AND er.fk_statut IN (" . ExpenseReport::STATUS_APPROVED . ", " . ExpenseReport::STATUS_CLOSED . ")";
// Add search filter like
        if (strlen(trim($search_login))) {
            $sql .= natural_search("u.login", $search_login);
        }
        if (strlen(trim($search_expensereport))) {
            $sql .= natural_search("er.ref", $search_expensereport);
        }
        if (strlen(trim($search_label))) {
            $sql .= natural_search("f.label", $search_label);
        }
        if (strlen(trim($search_desc))) {
            $sql .= natural_search("er.comments", $search_desc);
        }
        if (strlen(trim($search_amount))) {
            $sql .= natural_search("erd.total_ht", $search_amount, 1);
        }
        if (strlen(trim($search_account))) {
            $sql .= natural_search("aa.account_number", $search_account);
        }
        if (strlen(trim($search_vat))) {
            $sql .= natural_search("erd.tva_tx", price2num($search_vat), 1);
        }
        if ($search_date_start) {
            $sql .= " AND erd.date >= '" . $db->idate($search_date_start) . "'";
        }
        if ($search_date_end) {
            $sql .= " AND erd.date <= '" . $db->idate($search_date_end) . "'";
        }
        $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy

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

        dol_syslog("accountancy/expensereport/lines.php", LOG_DEBUG);
        $result = $db->query($sql);
        if ($result) {
            $num_lines = $db->num_rows($result);
            $i = 0;

            $param = '';
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }
            if ($search_login) {
                $param .= '&search_login=' . urlencode($search_login);
            }
            if ($search_expensereport) {
                $param .= "&search_expensereport=" . urlencode($search_expensereport);
            }
            if ($search_label) {
                $param .= "&search_label=" . urlencode($search_label);
            }
            if ($search_desc) {
                $param .= "&search_desc=" . urlencode($search_desc);
            }
            if ($search_account) {
                $param .= "&search_account=" . urlencode($search_account);
            }
            if ($search_vat) {
                $param .= "&search_vat=" . urlencode($search_vat);
            }
            if ($search_date_startday) {
                $param .= '&search_date_startday=' . urlencode((string) ($search_date_startday));
            }
            if ($search_date_startmonth) {
                $param .= '&search_date_startmonth=' . urlencode((string) ($search_date_startmonth));
            }
            if ($search_date_startyear) {
                $param .= '&search_date_startyear=' . urlencode((string) ($search_date_startyear));
            }
            if ($search_date_endday) {
                $param .= '&search_date_endday=' . urlencode((string) ($search_date_endday));
            }
            if ($search_date_endmonth) {
                $param .= '&search_date_endmonth=' . urlencode((string) ($search_date_endmonth));
            }
            if ($search_date_endyear) {
                $param .= '&search_date_endyear=' . urlencode((string) ($search_date_endyear));
            }

            print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
            print '<input type="hidden" name="action" value="ventil">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
            print '<input type="hidden" name="page" value="' . $page . '">';

            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            print_barre_liste($langs->trans("ExpenseReportLinesDone"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);
            print '<span class="opacitymedium">' . $langs->trans("DescVentilDoneExpenseReport") . '</span><br>';

            print '<br><div class="inline-block divButAction paddingbottom">' . $langs->trans("ChangeAccount") . ' ';
            print $formaccounting->select_account($account_parent, 'account_parent', 2, array(), 0, 0, 'maxwidth300 maxwidthonsmartphone valignmiddle');
            print '<input type="submit" class="button small valignmiddle" value="' . $langs->trans("ChangeBinding") . '"/></div>';

            $moreforfilter = '';

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

            print '<tr class="liste_titre_filter">';
            print '<td class="liste_titre"><input type="text" name="search_login" class="maxwidth50" value="' . $search_login . '"></td>';
            print '<td class="liste_titre"></td>';
            print '<td><input type="text" class="flat maxwidth50" name="search_expensereport" value="' . dol_escape_htmltag($search_expensereport) . '"></td>';
            if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
                print '<td class="liste_titre"></td>';
            }
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
            print '</td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="flat maxwidth50" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
            print '<td class="liste_titre center"><input type="text" class="flat maxwidth50" name="search_vat" size="1" placeholder="%" value="' . dol_escape_htmltag($search_vat) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_account" value="' . dol_escape_htmltag($search_account) . '"></td>';
            print '<td class="liste_titre center">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';
            print "</tr>\n";

            print '<tr class="liste_titre">';
            print_liste_field_titre("Employees", $_SERVER['PHP_SELF'], "u.login", $param, "", "", $sortfield, $sortorder);
            print_liste_field_titre("LineId", $_SERVER['PHP_SELF'], "erd.rowid", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("ExpenseReport", $_SERVER['PHP_SELF'], "er.ref", "", $param, '', $sortfield, $sortorder);
            if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
                print_liste_field_titre("DateValidation", $_SERVER['PHP_SELF'], "er.date_valid", "", $param, '', $sortfield, $sortorder, 'center ');
            }
            print_liste_field_titre("DateOfLine", $_SERVER['PHP_SELF'], "erd.date, erd.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre("TypeFees", $_SERVER['PHP_SELF'], "f.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Description", $_SERVER['PHP_SELF'], "erd.comments", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Amount", $_SERVER['PHP_SELF'], "erd.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
            print_liste_field_titre("VATRate", $_SERVER['PHP_SELF'], "erd.tva_tx", "", $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre("AccountAccounting", $_SERVER['PHP_SELF'], "aa.account_number", "", $param, '', $sortfield, $sortorder);
            $checkpicto = $form->showCheckAddButtons();
            print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
            print "</tr>\n";

            $expensereportstatic = new ExpenseReport($db);
            $accountingaccountstatic = new AccountingAccount($db);
            $userstatic = new User($db);

            $i = 0;
            while ($i < min($num_lines, $limit)) {
                $objp = $db->fetch_object($result);

                $expensereportstatic->ref = $objp->ref;
                $expensereportstatic->id = $objp->erid;

                $userstatic->id = $objp->userid;
                $userstatic->ref = $objp->label;
                $userstatic->login = $objp->login;
                $userstatic->statut = $objp->statut;
                $userstatic->email = $objp->email;
                $userstatic->gender = $objp->gender;
                $userstatic->firstname = $objp->firstname;
                $userstatic->lastname = $objp->lastname;
                $userstatic->employee = $objp->employee;
                $userstatic->photo = $objp->photo;

                $accountingaccountstatic->rowid = $objp->fk_compte;
                $accountingaccountstatic->label = $objp->label;
                $accountingaccountstatic->labelshort = $objp->labelshort;
                $accountingaccountstatic->account_number = $objp->account_number;

                print '<tr class="oddeven">';

                // Login
                print '<td class="nowraponall">';
                print $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1);
                print '</td>';

                // Line id
                print '<td>' . $objp->rowid . '</td>';

                // Ref Expense report
                print '<td>' . $expensereportstatic->getNomUrl(1) . '</td>';

                // Date validation
                if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
                    print '<td class="center">' . dol_print_date($db->jdate($objp->date_valid), 'day') . '</td>';
                }

                print '<td class="center">' . dol_print_date($db->jdate($objp->date), 'day') . '</td>';

                // Fees label
                print '<td class="tdoverflow">' . ($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code))) . '</td>';

                // Fees description -- Can be null
                print '<td>';
                $text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->comments, 1));
                $trunclength = getDolGlobalString('ACCOUNTING_LENGTH_DESCRIPTION', 32);
                print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->comments);
                print '</td>';

                // Amount without taxes
                print '<td class="right nowraponall amount">' . price($objp->total_ht) . '</td>';

                // Vat rate
                print '<td class="center">' . vatrate($objp->tva_tx . ($objp->vat_src_code ? ' (' . $objp->vat_src_code . ')' : '')) . '</td>';

                // Accounting account affected
                print '<td>';
                print $accountingaccountstatic->getNomUrl(0, 1, 1, '', 1);
                print ' <a class="editfielda reposition marginleftonly marginrightonly" href="./card.php?id=' . $objp->rowid . '&backtopage=' . urlencode($_SERVER['PHP_SELF'] . ($param ? '?' . $param : '')) . '">';
                print img_edit();
                print '</a></td>';
                print '<td class="center"><input type="checkbox" class="checkforaction" name="changeaccount[]" value="' . $objp->rowid . '"/></td>';

                print "</tr>";
                $i++;
            }
            if ($num_lines == 0) {
                $colspan = 10;
                if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
                    $colspan++;
                }
                print '<tr><td colspan="' . $colspan . '"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }

            print "</table>";
            print "</div>";

            if ($nbtotalofrecords > $limit) {
                print_barre_liste('', $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num_lines, $nbtotalofrecords, '', 0, '', '', $limit, 1);
            }

            print '</form>';
        } else {
            print $db->lasterror();
        }

// End of page
        llxFooter();
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

        $form = new Form($db);
        $formother = new FormOther($db);

        $help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#Liaisons_comptables';

        llxHeader('', $langs->trans("ExpenseReportsVentilation"), $help_url);

        if (empty($chartaccountcode)) {
            print $langs->trans("ErrorChartOfAccountSystemNotSelected");
            // End of page
            llxFooter();
            $db->close();
            exit;
        }

// Expense report lines
        $sql = "SELECT er.ref, er.rowid as erid, er.date_debut, er.date_valid,";
        $sql .= " erd.rowid, erd.fk_c_type_fees, erd.comments, erd.total_ht as price, erd.fk_code_ventilation, erd.tva_tx as tva_tx_line, erd.vat_src_code, erd.date,";
        $sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label, f.accountancy_code as code_buy,";
        $sql .= " u.rowid as userid, u.login, u.lastname, u.firstname, u.email, u.gender, u.employee, u.photo, u.statut,";
        $sql .= " aa.rowid as aarowid";
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;
        $sql .= " FROM " . MAIN_DB_PREFIX . "expensereport as er";
        $sql .= " INNER JOIN " . MAIN_DB_PREFIX . "expensereport_det as erd ON er.rowid = erd.fk_expensereport";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u ON u.rowid = er.fk_user_author";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON f.accountancy_code = aa.account_number AND aa.fk_pcg_version = '" . $db->escape($chartaccountcode) . "' AND aa.entity = " . $conf->entity;
        $sql .= " WHERE er.fk_statut IN (" . ExpenseReport::STATUS_APPROVED . ", " . ExpenseReport::STATUS_CLOSED . ") AND erd.fk_code_ventilation <= 0";
// Add search filter like
        if (strlen(trim($search_login))) {
            $sql .= natural_search("u.login", $search_login);
        }
        if (strlen(trim($search_expensereport))) {
            $sql .= natural_search("er.ref", $search_expensereport);
        }
        if (strlen(trim($search_label))) {
            $sql .= natural_search("f.label", $search_label);
        }
        if (strlen(trim($search_desc))) {
            $sql .= natural_search("erd.comments", $search_desc);
        }
        if (strlen(trim($search_amount))) {
            $sql .= natural_search("erd.total_ht", $search_amount, 1);
        }
        if (strlen(trim($search_account))) {
            $sql .= natural_search("aa.account_number", $search_account);
        }
        if (strlen(trim($search_vat))) {
            $sql .= natural_search("erd.tva_tx", $search_vat, 1);
        }
        if ($search_date_start) {
            $sql .= " AND erd.date >= '" . $db->idate($search_date_start) . "'";
        }
        if ($search_date_end) {
            $sql .= " AND erd.date <= '" . $db->idate($search_date_end) . "'";
        }
        $sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")"; // We don't share object for accountancy

// Add where from hooks
        $parameters = array();
        $reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
        $sql .= $hookmanager->resPrint;

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
//print $sql;

        $sql .= $db->plimit($limit + 1, $offset);

        dol_syslog("accountancy/expensereport/list.php", LOG_DEBUG);
// MAX_JOIN_SIZE can be very low (ex: 300000) on some limited configurations (ex: https://www.online.net/fr/hosting/online-perso)
// This big SELECT command may exceed the MAX_JOIN_SIZE limit => Therefore we use SQL_BIG_SELECTS=1 to disable the MAX_JOIN_SIZE security
        if ($db->type == 'mysqli') {
            $db->query("SET SQL_BIG_SELECTS=1");
        }

        $result = $db->query($sql);
        if ($result) {
            $num_lines = $db->num_rows($result);
            $i = 0;

            $arrayofselected = is_array($toselect) ? $toselect : array();

            $param = '';
            if (!empty($contextpage) && $contextpage != $_SERVER['PHP_SELF']) {
                $param .= '&contextpage=' . urlencode($contextpage);
            }
            if ($limit > 0 && $limit != $conf->liste_limit) {
                $param .= '&limit=' . ((int) $limit);
            }
            if ($search_login) {
                $param .= '&search_login=' . urlencode($search_login);
            }
            if ($search_lineid) {
                $param .= '&search_lineid=' . urlencode($search_lineid);
            }
            if ($search_date_startday) {
                $param .= '&search_date_startday=' . urlencode((string) ($search_date_startday));
            }
            if ($search_date_startmonth) {
                $param .= '&search_date_startmonth=' . urlencode((string) ($search_date_startmonth));
            }
            if ($search_date_startyear) {
                $param .= '&search_date_startyear=' . urlencode((string) ($search_date_startyear));
            }
            if ($search_date_endday) {
                $param .= '&search_date_endday=' . urlencode((string) ($search_date_endday));
            }
            if ($search_date_endmonth) {
                $param .= '&search_date_endmonth=' . urlencode((string) ($search_date_endmonth));
            }
            if ($search_date_endyear) {
                $param .= '&search_date_endyear=' . urlencode((string) ($search_date_endyear));
            }
            if ($search_expensereport) {
                $param .= '&search_expensereport=' . urlencode($search_expensereport);
            }
            if ($search_label) {
                $param .= '&search_label=' . urlencode($search_label);
            }
            if ($search_desc) {
                $param .= '&search_desc=' . urlencode($search_desc);
            }
            if ($search_amount) {
                $param .= '&search_amount=' . urlencode($search_amount);
            }
            if ($search_vat) {
                $param .= '&search_vat=' . urlencode($search_vat);
            }

            $arrayofmassactions = array(
                'ventil' => img_picto('', 'check', 'class="pictofixedwidth"') . $langs->trans("Ventilate")
            );
            $massactionbutton = $form->selectMassAction('ventil', $arrayofmassactions, 1);

            print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">' . "\n";
            print '<input type="hidden" name="action" value="ventil">';
            if ($optioncss != '') {
                print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
            }
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
            print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
            print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
            print '<input type="hidden" name="page" value="' . $page . '">';

            // @phan-suppress-next-line PhanPluginSuspiciousParamOrder
            print_barre_liste($langs->trans("ExpenseReportLines"), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, $massactionbutton, $num_lines, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit);

            print '<span class="opacitymedium">' . $langs->trans("DescVentilTodoExpenseReport") . '</span></br><br>';

            if (!empty($msg)) {
                print $msg . '<br>';
            }

            $moreforfilter = '';

            print '<div class="div-table-responsive">';
            print '<table class="tagtable liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

            // We add search filter
            print '<tr class="liste_titre_filter">';
            print '<td class="liste_titre"><input type="text" name="search_login" class="maxwidth50" value="' . $search_login . '"></td>';
            print '<td class="liste_titre"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_expensereport" value="' . dol_escape_htmltag($search_expensereport) . '"></td>';
            if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
                print '<td class="liste_titre"></td>';
            }
            print '<td class="liste_titre center">';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
            print '</div>';
            print '<div class="nowrapfordate">';
            print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
            print '</div>';
            print '</td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidth50" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
            print '<td class="liste_titre"><input type="text" class="flat maxwidthonsmartphone" name="search_desc" value="' . dol_escape_htmltag($search_desc) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_amount" value="' . dol_escape_htmltag($search_amount) . '"></td>';
            print '<td class="liste_titre right"><input type="text" class="flat maxwidth50 right" name="search_vat" placeholder="%" size="1" value="' . dol_escape_htmltag($search_vat) . '"></td>';
            print '<td class="liste_titre"></td>';
            print '<td class="liste_titre"></td>';
            print '<td class="center liste_titre">';
            $searchpicto = $form->showFilterButtons();
            print $searchpicto;
            print '</td>';
            print '</tr>';

            print '<tr class="liste_titre">';
            print_liste_field_titre("Employee", $_SERVER['PHP_SELF'], "u.login", $param, "", "", $sortfield, $sortorder);
            print_liste_field_titre("LineId", $_SERVER['PHP_SELF'], "erd.rowid", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("ExpenseReport", $_SERVER['PHP_SELF'], "er.ref", "", $param, '', $sortfield, $sortorder);
            if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
                print_liste_field_titre("DateValidation", $_SERVER['PHP_SELF'], "er.date_valid", "", $param, '', $sortfield, $sortorder, 'center ');
            }
            print_liste_field_titre("DateOfLine", $_SERVER['PHP_SELF'], "erd.date, erd.rowid", "", $param, '', $sortfield, $sortorder, 'center ');
            print_liste_field_titre("TypeFees", $_SERVER['PHP_SELF'], "f.label", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Description", $_SERVER['PHP_SELF'], "erd.comments", "", $param, '', $sortfield, $sortorder);
            print_liste_field_titre("Amount", $_SERVER['PHP_SELF'], "erd.total_ht", "", $param, '', $sortfield, $sortorder, 'right maxwidth50 ');
            print_liste_field_titre("VATRate", $_SERVER['PHP_SELF'], "erd.tva_tx", "", $param, '', $sortfield, $sortorder, 'right ');
            print_liste_field_titre("DataUsedToSuggestAccount", '', '', '', '', '', '', '', 'nowraponall ');
            print_liste_field_titre("AccountAccountingSuggest", '', '', '', '', '', '', '', '');
            $checkpicto = '';
            if ($massactionbutton) {
                $checkpicto = $form->showCheckAddButtons('checkforselect', 1);
            }
            print_liste_field_titre($checkpicto, '', '', '', '', '', '', '', 'center ');
            print "</tr>\n";


            $expensereport_static = new ExpenseReport($db);
            $userstatic = new User($db);
            $form = new Form($db);

            while ($i < min($num_lines, $limit)) {
                $objp = $db->fetch_object($result);

                $objp->aarowid_suggest = '';
                $objp->aarowid_suggest = $objp->aarowid;

                $expensereport_static->ref = $objp->ref;
                $expensereport_static->id = $objp->erid;

                $userstatic->id = $objp->userid;
                $userstatic->login = $objp->login;
                $userstatic->statut = $objp->statut;
                $userstatic->email = $objp->email;
                $userstatic->gender = $objp->gender;
                $userstatic->firstname = $objp->firstname;
                $userstatic->lastname = $objp->lastname;
                $userstatic->employee = $objp->employee;
                $userstatic->photo = $objp->photo;

                print '<tr class="oddeven">';

                // Login
                print '<td class="nowraponall">';
                print $userstatic->getNomUrl(-1, '', 0, 0, 24, 1, 'login', '', 1);
                print '</td>';

                // Line id
                print '<td>' . $objp->rowid . '</td>';

                // Ref Expense report
                print '<td>' . $expensereport_static->getNomUrl(1) . '</td>';

                // Date validation
                if (getDolGlobalString('ACCOUNTANCY_USE_EXPENSE_REPORT_VALIDATION_DATE')) {
                    print '<td class="center">' . dol_print_date($db->jdate($objp->date_valid), 'day') . '</td>';
                }

                // Date
                print '<td class="center">' . dol_print_date($db->jdate($objp->date), 'day') . '</td>';

                // Fees label
                print '<td>';
                print($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code)));
                print '</td>';

                // Fees description -- Can be null
                print '<td>';
                $text = dolGetFirstLineOfText(dol_string_nohtmltag($objp->comments, 1));
                $trunclength = getDolGlobalInt('ACCOUNTING_LENGTH_DESCRIPTION', 32);
                print $form->textwithtooltip(dol_trunc($text, $trunclength), $objp->comments);
                print '</td>';

                // Amount without taxes
                print '<td class="right nowraponall amount">';
                print price($objp->price);
                print '</td>';

                // Vat rate
                print '<td class="right">';
                print vatrate($objp->tva_tx_line . ($objp->vat_src_code ? ' (' . $objp->vat_src_code . ')' : ''));
                print '</td>';

                // Current account
                print '<td>';
                print length_accountg(html_entity_decode($objp->code_buy));
                print '</td>';

                // Suggested accounting account
                print '<td>';
                print $formaccounting->select_account($objp->aarowid_suggest, 'codeventil' . $objp->rowid, 1, array(), 0, 0, 'codeventil maxwidth200 maxwidthonsmartphone', 'cachewithshowemptyone');
                print '</td>';

                print '<td class="center">';
                print '<input type="checkbox" class="flat checkforselect checkforselect' . $objp->rowid . '" name="toselect[]" value="' . $objp->rowid . "_" . $i . '"' . ($objp->aarowid ? "checked" : "") . '/>';
                print '</td>';

                print "</tr>";
                $i++;
            }
            if ($num_lines == 0) {
                print '<tr><td colspan="13"><span class="opacitymedium">' . $langs->trans("NoRecordFound") . '</span></td></tr>';
            }

            print '</table>';
            print "</div>";

            print '</form>';
        } else {
            print $db->error();
        }
        if ($db->type == 'mysqli') {
            $db->query("SET SQL_BIG_SELECTS=0"); // Enable MAX_JOIN_SIZE limitation
        }

// Add code to auto check the box when we select an account
        print '<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery(".codeventil").change(function() {
		var s=$(this).attr("id").replace("codeventil", "")
		console.log(s+" "+$(this).val());
		if ($(this).val() == -1) jQuery(".checkforselect"+s).prop("checked", false);
		else jQuery(".checkforselect"+s).prop("checked", true);
	});
});
</script>';

// End of page
        llxFooter();
        $db->close();
    }
}
