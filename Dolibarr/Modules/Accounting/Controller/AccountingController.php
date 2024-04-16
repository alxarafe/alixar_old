<?php

/* Copyright (C) 2016-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2016-2023  Alexandre Spangaro		<aspangaro@easya.solutions>
 * Copyright (C) 2019-2021  Frédéric France			<frederic.france@netlogic.fr>
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

use DoliCore\Base\DolibarrController;
use DoliCore\Form\FormOther;

class AccountingController extends DolibarrController
{
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
        $langs->loadLangs(["compta", "bills", "other", "accountancy", "loans", "banks", "admin", "dict"]);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
        $hookmanager->initHooks(['accountancyindex']);

// Security check
        if ($user->socid > 0) {
            accessforbidden();
        }
        if (!isModEnabled('comptabilite') && !isModEnabled('accounting') && !isModEnabled('asset') && !isModEnabled('intracommreport')) {
            accessforbidden();
        }
        if (!$user->hasRight('compta', 'resultat', 'lire') && !$user->hasRight('accounting', 'comptarapport', 'lire') && !$user->hasRight('accounting', 'mouvements', 'lire') && !$user->hasRight('asset', 'read') && !$user->hasRight('intracommreport', 'read')) {
            accessforbidden();
        }

        $pcgver = getDolGlobalInt('CHARTOFACCOUNTS');


        /*
         * Actions
         */

        if (GETPOST('addbox')) {
            // Add box (when submit is done from a form when ajax disabled)
            require_once DOL_DOCUMENT_ROOT . '/core/class/infobox.class.php';
            $zone = GETPOSTINT('areacode');
            $userid = GETPOSTINT('userid');
            $boxorder = GETPOST('boxorder', 'aZ09');
            $boxorder .= GETPOST('boxcombo', 'aZ09');

            $result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
            if ($result > 0) {
                setEventMessages($langs->trans("BoxAdded"), null);
            }
        }


        /*
         * View
         */
        require_once realpath(BASE_PATH . '/../Dolibarr/Modules/Accounting/Views/index.php');

        $db->close();
    }
}
