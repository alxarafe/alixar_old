<?php

/* Copyright (C) 2017       ATM Consulting          <contact@atm-consulting.fr>
 * Copyright (C) 2017-2018  Laurent Destailleur     <eldy@destailleur.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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

namespace DoliModules\BlockedLog\Controller;

global $conf;
global $db;
global $user;
global $hookmanager;
global $user;
global $menumanager;
global $langs;
global $mysoc;

/**
 *    \file       htdocs/blockedlog/admin/blockedlog_list.php
 *    \ingroup    blockedlog
 *    \brief      Page setup for blockedlog module
 */

use DoliCore\Base\Controller\DolibarrController;
use DoliModules\BlockedLog\Model\BlockedLog;
use DoliModules\BlockedLog\Model\BlockedLogAuthority;

// Load Dolibarr environment
require BASE_PATH . '/main.inc.php';
require_once BASE_PATH . '/../Dolibarr/Modules/BlockedLog/Lib/BlockedLog.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Admin.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Date.php';

class BlockedLogAdminListController extends DolibarrController
{
    public function blockedlog_list(bool $executeActions = true): bool
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;

// Load translation files required by the page
        $langs->loadLangs(['admin', 'bills', 'blockedlog', 'other']);

// Access Control
        if ((!$user->admin && !$user->hasRight('blockedlog', 'read')) || empty($conf->blockedlog->enabled)) {
            accessforbidden();
        }

// Get Parameters
        $action = GETPOST('action', 'aZ09');
        $contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'blockedloglist'; // To manage different context of search
        $backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
        $optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

        $search_showonlyerrors = GETPOSTINT('search_showonlyerrors');
        if ($search_showonlyerrors < 0) {
            $search_showonlyerrors = 0;
        }

        $search_startyear = GETPOSTINT('search_startyear');
        $search_startmonth = GETPOSTINT('search_startmonth');
        $search_startday = GETPOSTINT('search_startday');
        $search_endyear = GETPOSTINT('search_endyear');
        $search_endmonth = GETPOSTINT('search_endmonth');
        $search_endday = GETPOSTINT('search_endday');
        $search_id = GETPOST('search_id', 'alpha');
        $search_fk_user = GETPOST('search_fk_user', 'intcomma');
        $search_start = -1;
        if ($search_startyear != '') {
            $search_start = dol_mktime(0, 0, 0, $search_startmonth, $search_startday, $search_startyear);
        }
        $search_end = -1;
        if (GETPOST('search_endyear') != '') {
            $search_end = dol_mktime(23, 59, 59, GETPOST('search_endmonth'), GETPOST('search_endday'), GETPOST('search_endyear'));
        }
        $search_code = GETPOST('search_code', 'alpha');
        $search_ref = GETPOST('search_ref', 'alpha');
        $search_amount = GETPOST('search_amount', 'alpha');

        if (($search_start == -1 || empty($search_start)) && !GETPOSTISSET('search_startmonth') && !GETPOSTISSET('begin')) {
            $search_start = dol_time_plus_duree(dol_now(), '-1', 'w');
            $tmparray = dol_getdate($search_start);
            $search_startday = $tmparray['mday'];
            $search_startmonth = $tmparray['mon'];
            $search_startyear = $tmparray['year'];
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

        if (empty($sortfield)) {
            $sortfield = 'rowid';
        }
        if (empty($sortorder)) {
            $sortorder = 'DESC';
        }

        $block_static = new BlockedLog($db);
        $block_static->loadTrackedEvents();

        $result = restrictedArea($user, 'blockedlog', 0, '');

// Execution Time
        $max_execution_time_for_importexport = (!getDolGlobalString('EXPORT_MAX_EXECUTION_TIME') ? 300 : $conf->global->EXPORT_MAX_EXECUTION_TIME); // 5mn if not defined
        $max_time = @ini_get("max_execution_time");
        if ($max_time && $max_time < $max_execution_time_for_importexport) {
            dol_syslog("max_execution_time=" . $max_time . " is lower than max_execution_time_for_importexport=" . $max_execution_time_for_importexport . ". We try to increase it dynamically.");
            @ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
        }


        /*
         * Actions
         */

// Purge search criteria
        if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
            $search_id = '';
            $search_fk_user = '';
            $search_start = -1;
            $search_end = -1;
            $search_code = '';
            $search_ref = '';
            $search_amount = '';
            $search_showonlyerrors = 0;
            $search_startyear = '';
            $search_startmonth = '';
            $search_startday = '';
            $search_endyear = '';
            $search_endmonth = '';
            $search_endday = '';
            $toselect = [];
            $search_array_options = [];
        }

        if ($action === 'downloadblockchain') {
            $auth = new BlockedLogAuthority($db);

            $bc = $auth->getLocalBlockChain();

            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $auth->signature . ".certif\"");

            echo $bc;

            exit;
        } elseif (GETPOST('downloadcsv', 'alpha')) {
            $error = 0;

            $previoushash = '';
            $firstid = '';

            if (!$error) {
                // Get ID of first line
                $sql = "SELECT rowid,date_creation,tms,user_fullname,action,amounts,element,fk_object,date_object,ref_object,signature,fk_user,object_data";
                $sql .= " FROM " . MAIN_DB_PREFIX . "blockedlog";
                $sql .= " WHERE entity = " . $conf->entity;
                if (GETPOSTINT('monthtoexport') > 0 || GETPOSTINT('yeartoexport') > 0) {
                    $dates = dol_get_first_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 1);
                    $datee = dol_get_last_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 12);
                    $sql .= " AND date_creation BETWEEN '" . $db->idate($dates) . "' AND '" . $db->idate($datee) . "'";
                }
                $sql .= " ORDER BY rowid ASC"; // Required so we get the first one
                $sql .= $db->plimit(1);

                $res = $db->query($sql);
                if ($res) {
                    // Make the first fetch to get first line
                    $obj = $db->fetch_object($res);
                    if ($obj) {
                        $previoushash = $block_static->getPreviousHash(0, $obj->rowid);
                        $firstid = $obj->rowid;
                    } else {    // If not data found for filter, we do not need previoushash neither firstid
                        $previoushash = 'nodata';
                        $firstid = '';
                    }
                } else {
                    $error++;
                    setEventMessages($db->lasterror, null, 'errors');
                }
            }

            if (!$error) {
                // Now restart request with all data = no limit(1) in sql request
                $sql = "SELECT rowid, date_creation, tms, user_fullname, action, amounts, element, fk_object, date_object, ref_object, signature, fk_user, object_data, object_version";
                $sql .= " FROM " . MAIN_DB_PREFIX . "blockedlog";
                $sql .= " WHERE entity = " . ((int) $conf->entity);
                if (GETPOSTINT('monthtoexport') > 0 || GETPOSTINT('yeartoexport') > 0) {
                    $dates = dol_get_first_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 1);
                    $datee = dol_get_last_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 12);
                    $sql .= " AND date_creation BETWEEN '" . $db->idate($dates) . "' AND '" . $db->idate($datee) . "'";
                }
                $sql .= " ORDER BY rowid ASC"; // Required so later we can use the parameter $previoushash of checkSignature()

                $res = $db->query($sql);
                if ($res) {
                    header('Content-Type: application/octet-stream');
                    header("Content-Transfer-Encoding: Binary");
                    header("Content-disposition: attachment; filename=\"unalterable-log-archive-" . $dolibarr_main_db_name . "-" . (GETPOSTINT('yeartoexport') > 0 ? GETPOSTINT('yeartoexport') . (GETPOSTINT('monthtoexport') > 0 ? sprintf("%02d", GETPOSTINT('monthtoexport')) : '') . '-' : '') . $previoushash . ".csv\"");

                    print $langs->transnoentities('Id')
                        . ';' . $langs->transnoentities('Date')
                        . ';' . $langs->transnoentities('User')
                        . ';' . $langs->transnoentities('Action')
                        . ';' . $langs->transnoentities('Element')
                        . ';' . $langs->transnoentities('Amounts')
                        . ';' . $langs->transnoentities('ObjectId')
                        . ';' . $langs->transnoentities('Date')
                        . ';' . $langs->transnoentities('Ref')
                        . ';' . $langs->transnoentities('Fingerprint')
                        . ';' . $langs->transnoentities('Status')
                        . ';' . $langs->transnoentities('Note')
                        . ';' . $langs->transnoentities('Version')
                        . ';' . $langs->transnoentities('FullData')
                        . "\n";

                    $loweridinerror = 0;
                    $i = 0;

                    while ($obj = $db->fetch_object($res)) {
                        // We set here all data used into signature calculation (see checkSignature method) and more
                        // IMPORTANT: We must have here, the same rule for transformation of data than into the fetch method (db->jdate for date, ...)
                        $block_static->id = $obj->rowid;
                        $block_static->date_creation = $db->jdate($obj->date_creation);
                        $block_static->date_modification = $db->jdate($obj->tms);
                        $block_static->action = $obj->action;
                        $block_static->fk_object = $obj->fk_object;
                        $block_static->element = $obj->element;
                        $block_static->amounts = (float) $obj->amounts;
                        $block_static->ref_object = $obj->ref_object;
                        $block_static->date_object = $db->jdate($obj->date_object);
                        $block_static->user_fullname = $obj->user_fullname;
                        $block_static->fk_user = $obj->fk_user;
                        $block_static->signature = $obj->signature;
                        $block_static->object_data = $block_static->dolDecodeBlockedData($obj->object_data);
                        $block_static->object_version = $obj->object_version;

                        $checksignature = $block_static->checkSignature($previoushash); // If $previoushash is not defined, checkSignature will search it

                        if ($checksignature) {
                            $statusofrecord = 'Valid';
                            if ($loweridinerror > 0) {
                                $statusofrecordnote = 'ValidButFoundAPreviousKO';
                            } else {
                                $statusofrecordnote = '';
                            }
                        } else {
                            $statusofrecord = 'KO';
                            $statusofrecordnote = 'LineCorruptedOrNotMatchingPreviousOne';
                            $loweridinerror = $obj->rowid;
                        }

                        if ($i == 0) {
                            $statusofrecordnote = $langs->trans("PreviousFingerprint") . ': ' . $previoushash . ($statusofrecordnote ? ' - ' . $statusofrecordnote : '');
                        }
                        print $obj->rowid;
                        print ';' . $obj->date_creation;
                        print ';"' . str_replace('"', '""', $obj->user_fullname) . '"';
                        print ';' . $obj->action;
                        print ';' . $obj->element;
                        print ';' . $obj->amounts;
                        print ';' . $obj->fk_object;
                        print ';' . $obj->date_object;
                        print ';"' . str_replace('"', '""', $obj->ref_object) . '"';
                        print ';' . $obj->signature;
                        print ';' . $statusofrecord;
                        print ';' . $statusofrecordnote;
                        print ';' . $obj->object_version;
                        print ';"' . str_replace('"', '""', $obj->object_data) . '"';
                        print "\n";

                        // Set new previous hash for next fetch
                        $previoushash = $obj->signature;

                        $i++;
                    }

                    exit;
                } else {
                    setEventMessages($db->lasterror, null, 'errors');
                }
            }
        }


        /*
         *	View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/BlockedLog/Views/admin_blocked_log_list.php');

        $db->close();

        return true;
    }
}

