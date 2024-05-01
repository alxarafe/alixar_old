<?php

/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
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

require BASE_PATH . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Functions2.php';
require_once BASE_PATH . '/../Dolibarr/Lib/Member.php';

use DolGraph;
use DoliCore\Base\DolibarrController;

class AdherentStatsController extends DolibarrController
{
    /**
     *      \file       htdocs/adherents/stats/byproperties.php
     *      \ingroup    member
     *      \brief      Page with statistics on members
     */
    public function byproperties()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

        $graphwidth = 700;
        $mapratio = 0.5;
        $graphheight = round($graphwidth * $mapratio);

        $mode = GETPOST('mode') ? GETPOST('mode') : '';


// Security check
        if ($user->socid > 0) {
            $action = '';
            $socid = $user->socid;
        }
        $result = restrictedArea($user, 'adherent', '', '', 'cotisation');

        $year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
        $startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
        $endyear = $year;

// Load translation files required by the page
        $langs->loadLangs(["companies", "members"]);


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/stats_byproperties.php');

        $db->close();
        return true;
    }

    /**
     *      \file       htdocs/adherents/stats/geo.php
     *      \ingroup    member
     *      \brief      Page with geographical statistics on members
     */
    public function geo()
    {
        global $conf;
        global $db;
        global $user;
        global $hookmanager;
        global $user;
        global $menumanager;
        global $langs;
        global $mysoc;

        $graphwidth = DolGraph::getDefaultGraphSizeForStats('width', 700);
        $mapratio = 0.5;
        $graphheight = round($graphwidth * $mapratio);

        $mode = GETPOST('mode') ? GETPOST('mode') : '';


// Security check
        if ($user->socid > 0) {
            $action = '';
            $socid = $user->socid;
        }
        $result = restrictedArea($user, 'adherent', '', '', 'cotisation');

        $year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
        $startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
        $endyear = $year;

// Load translation files required by the page
        $langs->loadLangs(["companies", "members", "banks"]);


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/stats_geo.php');

        $db->close();
        return true;
    }

    /**
     *      \file       htdocs/adherents/stats/index.php
     *      \ingroup    member
     *      \brief      Page of subscription members statistics
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

        $WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
        $HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

        $userid = GETPOSTINT('userid');
        if ($userid < 0) {
            $userid = 0;
        }
        $socid = GETPOSTINT('socid');
        if ($socid < 0) {
            $socid = 0;
        }

// Security check
        if ($user->socid > 0) {
            $action = '';
            $socid = $user->socid;
        }
        $result = restrictedArea($user, 'adherent', '', '', 'cotisation');

        $year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
        $startyear = $year - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
        $endyear = $year;
        if (getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER')) {
            $endyear = dol_print_date(dol_time_plus_duree(dol_now('gmt'), (int) substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), 0, -1), substr(getDolGlobalString('MEMBER_SUBSCRIPTION_START_AFTER'), -1)), "%Y", 'gmt');
        }

// Load translation files required by the page
        $langs->loadLangs(["companies", "members"]);


        /*
         * View
         */

        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Adherent/Views/stats_index.php');

        $db->close();

        return true;
    }
}
