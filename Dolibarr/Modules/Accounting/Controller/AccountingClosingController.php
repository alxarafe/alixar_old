<?php

/* Copyright (C) 2019-2023  Open-DSI                <support@open-dsi.fr>
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
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fiscalyear.class.php';

use DoliCore\Base\DolibarrController;
use DoliModules\Accounting\Model\BookKeeping;

class AccountingClosingController extends DolibarrController
{
    /**
     * \file        htdocs/accountancy/closure/index.php
     * \ingroup     Accountancy
     * \brief       Home closure page
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
        $langs->loadLangs(["compta", "bills", "other", "accountancy"]);

        $action = GETPOST('action', 'aZ09');
        $confirm = GETPOST('confirm', 'aZ09');
        $fiscal_period_id = GETPOSTINT('fiscal_period_id');
        $validatemonth = GETPOSTINT('validatemonth');
        $validateyear = GETPOSTINT('validateyear');

// Security check
        if (!isModEnabled('accounting')) {
            accessforbidden();
        }
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!$user->hasRight('accounting', 'fiscalyear', 'write')) {
            accessforbidden();
        }

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
        $hookmanager->initHooks(['accountancyclosure']);

        $object = new BookKeeping($db);

        $now = dol_now();
        $fiscal_periods = $object->getFiscalPeriods();
        if (!is_array($fiscal_periods)) {
            setEventMessages($object->error, $object->errors, 'errors');
        }

        $active_fiscal_periods = [];
        $last_fiscal_period = null;
        $current_fiscal_period = null;
        $next_fiscal_period = null;
        $next_active_fiscal_period = null;
        if (is_array($fiscal_periods)) {
            foreach ($fiscal_periods as $fiscal_period) {
                if (empty($fiscal_period['status'])) {
                    $active_fiscal_periods[] = $fiscal_period;
                }
                if (isset($current_fiscal_period)) {
                    if (!isset($next_fiscal_period)) {
                        $next_fiscal_period = $fiscal_period;
                    }
                    if (!isset($next_active_fiscal_period) && empty($fiscal_period['status'])) {
                        $next_active_fiscal_period = $fiscal_period;
                    }
                } else {
                    if ($fiscal_period_id == $fiscal_period['id'] || (empty($fiscal_period_id) && $fiscal_period['date_start'] <= $now && $now <= $fiscal_period['date_end'])) {
                        $current_fiscal_period = $fiscal_period;
                    } else {
                        $last_fiscal_period = $fiscal_period;
                    }
                }
            }
        }

        $accounting_groups_used_for_balance_sheet_account = array_filter(array_map('trim', explode(',', getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT'))), 'strlen');
        $accounting_groups_used_for_income_statement = array_filter(array_map('trim', explode(',', getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT'))), 'strlen');


        /*
         * Actions
         */

        $parameters = ['fiscal_periods' => $fiscal_periods, 'last_fiscal_period' => $last_fiscal_period, 'current_fiscal_period' => $current_fiscal_period, 'next_fiscal_period' => $next_fiscal_period];
        $reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
        if ($reshook < 0) {
            setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        }

        if (empty($reshook)) {
            if (isset($current_fiscal_period) && $user->hasRight('accounting', 'fiscalyear', 'write')) {
                if ($action == 'confirm_step_1' && $confirm == "yes") {
                    $date_start = dol_mktime(0, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
                    $date_end = dol_mktime(23, 59, 59, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));

                    $result = $object->validateMovementForFiscalPeriod($date_start, $date_end);
                    if ($result > 0) {
                        setEventMessages($langs->trans("AllMovementsWereRecordedAsValidated"), null, 'mesgs');

                        header("Location: " . $_SERVER['PHP_SELF'] . (isset($current_fiscal_period) ? '?fiscal_period_id=' . $current_fiscal_period['id'] : ''));
                        exit;
                    } else {
                        setEventMessages($langs->trans("NotAllMovementsCouldBeRecordedAsValidated"), null, 'errors');
                        setEventMessages($object->error, $object->errors, 'errors');
                        $action = '';
                    }
                } elseif ($action == 'confirm_step_2' && $confirm == "yes") {
                    $new_fiscal_period_id = GETPOSTINT('new_fiscal_period_id');
                    $separate_auxiliary_account = GETPOST('separate_auxiliary_account', 'aZ09');
                    $generate_bookkeeping_records = GETPOST('generate_bookkeeping_records', 'aZ09');

                    $result = $object->closeFiscalPeriod($current_fiscal_period['id'], $new_fiscal_period_id, $separate_auxiliary_account, $generate_bookkeeping_records);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                    } else {
                        setEventMessages($langs->trans("AccountancyClosureCloseSuccessfully"), null, 'mesgs');

                        header("Location: " . $_SERVER['PHP_SELF'] . (isset($current_fiscal_period) ? '?fiscal_period_id=' . $current_fiscal_period['id'] : ''));
                        exit;
                    }
                } elseif ($action == 'confirm_step_3' && $confirm == "yes") {
                    $inventory_journal_id = GETPOSTINT('inventory_journal_id');
                    $new_fiscal_period_id = GETPOSTINT('new_fiscal_period_id');
                    $date_start = dol_mktime(0, 0, 0, GETPOSTINT('date_startmonth'), GETPOSTINT('date_startday'), GETPOSTINT('date_startyear'));
                    $date_end = dol_mktime(23, 59, 59, GETPOSTINT('date_endmonth'), GETPOSTINT('date_endday'), GETPOSTINT('date_endyear'));

                    $result = $object->insertAccountingReversal($current_fiscal_period['id'], $inventory_journal_id, $new_fiscal_period_id, $date_start, $date_end);
                    if ($result < 0) {
                        setEventMessages($object->error, $object->errors, 'errors');
                    } else {
                        setEventMessages($langs->trans("AccountancyClosureInsertAccountingReversalSuccessfully"), null, 'mesgs');

                        header("Location: " . $_SERVER['PHP_SELF'] . (isset($current_fiscal_period) ? '?fiscal_period_id=' . $current_fiscal_period['id'] : ''));
                        exit;
                    }
                }
            }
        }

        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/closing_index.php');

        $db->close();

        return true;
    }
}
